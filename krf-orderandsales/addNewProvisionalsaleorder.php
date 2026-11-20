<?php
include('../header.php');
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
$orderPsono = trim($_GET['psono'] ?? '');
$orderMode = (!empty($orderPsono) && (($_GET['mode'] ?? '') === 'edit' || !isset($_GET['mode']))) ? 'edit' : 'add';
$isEdit = ($orderMode === 'edit');
?>
<script>
  window.CURRENT_USER_ROLE_ID = <?php echo json_encode(intval($loginroleid ?? 99)); ?>;
</script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
  .swal2-container {
    z-index: 99999 !important;
  }
  :root {
    --primary-blue: #0d6efd;
    --deep-blue: #052c65;
    --premium-green: #1b9e77;
    --light-bg: #f8fafc;
    --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    --border-color: #e2e8f0;
  }

  /* Horizontal & Vertical Scrollbar Styling for Stock Allocation Table */
  .tablestockshow .table-responsive {
    max-height: 380px !important; /* Shows at least 5-8 entries at a time */
    overflow-x: auto !important;
    overflow-y: auto !important;
    max-width: 100% !important;
    border-radius: 8px !important;
    position: relative !important;
    border: 1px solid #cbd5e1 !important;
    background: #ffffff !important;
    -webkit-overflow-scrolling: touch;
  }
  .tablestockshow .table-responsive::-webkit-scrollbar {
    height: 10px;
    width: 8px;
  }
  .tablestockshow .table-responsive::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 6px;
  }
  .tablestockshow .table-responsive::-webkit-scrollbar-thumb {
    background: #0d6efd;
    border-radius: 6px;
  }
  .tablestockshow .table-responsive::-webkit-scrollbar-thumb:hover {
    background: #0b5ed7;
  }
  #tblstockdatamaster {
    min-width: 1200px !important;
    width: 100% !important;
    border-collapse: separate !important;
    border-spacing: 0 !important;
  }
  #tblstockdatamaster thead th {
    position: sticky !important;
    top: 0 !important;
    z-index: 1020 !important;
    background-color: #e2f2fe !important;
    background: linear-gradient(135deg, #e2f2fe 0%, #dbeafe 100%) !important;
    color: #052c65 !important;
    border-bottom: 2px solid #cbd5e1 !important;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
  }

  /* Live Search Dropdown Styling */
  .search-results-container {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid var(--border-color);
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    z-index: 1050;
    max-height: 300px;
    overflow-y: auto;
    display: none;
    margin-top: 5px;
  }

  .search-result-item {
    padding: 12px 15px;
    border-bottom: 1px solid #f1f5f9;
    cursor: pointer;
    transition: background 0.2s;
    display: flex;
    flex-direction: column;
    gap: 2px;
  }

  .search-result-item:last-child {
    border-bottom: none;
  }

  .search-result-item:hover,
  .search-result-item.active {
    background: #f8fbff;
    border-left: 3px solid var(--primary-blue);
  }

  .search-result-name {
    font-weight: 700;
    color: var(--deep-blue);
    font-size: 0.95rem;
  }

  .search-result-meta {
    font-size: 0.8rem;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .search-result-meta i {
    color: var(--primary-blue);
    font-size: 0.75rem;
  }

  .search-result-address {
    font-size: 0.75rem;
    color: #94a3b8;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  body {
    font-family: 'Outfit', sans-serif;
    background-color: #f1f5f9;
  }

  .premium-container {
    max-width: 1400px;
    margin: 2rem auto;
    padding: 0 1.5rem;
  }

  .page-header {
    background: linear-gradient(135deg, var(--deep-blue) 0%, var(--primary-blue) 100%);
    color: white;
    padding: 1.5rem;
    border-radius: 16px;
    margin-bottom: 2rem;
    box-shadow: var(--card-shadow);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
  }

  .page-header h3 {
    margin: 0;
    font-weight: 700;
    font-size: 1.5rem;
  }

  .premium-card {
    background: white;
    border-radius: 16px;
    border: 1px solid var(--border-color);
    box-shadow: var(--card-shadow);
    margin-bottom: 1.5rem;
    transition: transform 0.2s ease;
  }

  .card-header-premium {
    background: #f8fafc;
    border-bottom: 1px solid var(--border-color);
    padding: 1rem 1.5rem;
    border-radius: 16px 16px 0 0;
    font-weight: 700;
    color: var(--deep-blue);
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }

  .premium-card-body {
    padding: 1.5rem;
  }

  .form-label {
    font-weight: 600;
    font-size: 0.85rem;
    color: #475569;
    margin-bottom: 0.4rem;
    display: flex;
    align-items: center;
    gap: 0.4rem;
  }

  .form-control,
  .form-select {
    border-radius: 10px;
    border: 1px solid #cbd5e1;
    padding: 0.6rem 0.85rem;
    font-size: 0.95rem;
    height: 44px;
    box-sizing: border-box;
    transition: all 0.2s;
  }

  .form-control:focus,
  .form-select:focus {
    border-color: var(--primary-blue);
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15);
  }

  .select2-container {
    width: 100% !important;
  }

  .select2-container--default .select2-selection--single {
    border-radius: 10px;
    border: 1px solid #cbd5e1;
    height: 44px;
    display: flex;
    align-items: center;
    padding: 0 0.85rem;
    background-color: #fff;
    box-sizing: border-box;
    transition: all 0.2s;
  }

  .select2-container--default.select2-container--open .select2-selection--single,
  .select2-container--default .select2-selection--single:focus {
    border-color: var(--primary-blue);
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15);
  }

  .select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #334155;
    line-height: 42px;
    font-size: 0.95rem;
    padding-left: 0;
    padding-right: 20px;
  }

  .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 42px;
    right: 10px;
  }

  /* Table Responsiveness */
  .table-responsive-premium {
    border-radius: 12px;
    border: 1px solid var(--border-color);
    overflow-x: auto;
    background: white;
    -webkit-overflow-scrolling: touch;
  }

  .premium-table {
    margin-bottom: 0;
    font-size: 0.8rem !important;
    min-width: 100%;
  }

  .premium-table thead th,
  #provisionorderitemsTable thead th,
  #deliveryaddresstbl thead th,
  #freightGroupTable thead th,
  #tblstockdatamaster thead th {
    position: sticky;
    top: 0;
    z-index: 100;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 0.72rem !important;
    padding: 3px 5px !important;
    border-bottom: 2px solid #cbd5e1 !important;
    text-align: center;
    vertical-align: middle;
    background: linear-gradient(135deg, #e2f2fe 0%, #dbeafe 100%) !important;
    background-color: #e2f2fe !important;
    color: #052c65 !important;
  }

  .premium-table tbody td {
    padding: 2px 4px !important;
    font-size: 0.8rem !important;
    vertical-align: middle;
  }

  /* Fix Bootstrap 4 position: absolute conflict for checkboxes */
  .f_zero_charge_chk,
  #freightGroupTable .form-check-input,
  #freightGroupTable input[type="checkbox"] {
    position: static !important;
    margin: 0 auto !important;
    display: inline-block !important;
    vertical-align: middle !important;
    float: none !important;
    width: 1.35rem !important;
    height: 1.35rem !important;
    cursor: pointer;
  }

  .invrowcell_rm,
  .productname,
  .expecteddelivery,
  .payableamount_td {
    min-width: 80px;
    height: 28px !important;
  }

  /* Highlight existing production stock order row when quantity is edited */
  #provisionorderitemsTable tbody tr.stockorder-qty-edited,
  #provisionorderitemsTable tbody tr.stockorder-qty-edited > td {
    background-color: #fff9c4 !important; /* Soft distinct light amber/yellow */
    transition: background-color 0.25s ease;
  }
  #provisionorderitemsTable tbody tr.stockorder-qty-edited:hover,
  #provisionorderitemsTable tbody tr.stockorder-qty-edited:hover > td {
    background-color: #fff59d !important;
  }
  #provisionorderitemsTable tbody tr.stockorder-qty-edited input.total_amount_td,
  #provisionorderitemsTable tbody tr.stockorder-qty-edited input.payableamount_td {
    background-color: #fff59d !important;
  }
  #provisionorderitemsTable tbody tr.stockorder-qty-edited input.qty_td {
    background-color: #fffde7 !important;
    border-color: #f59e0b !important;
    font-weight: 700 !important;
    color: #92400e !important;
  }
  #provisionorderitemsTable tbody tr.stockorder-qty-edited .stockorder-badge {
    background-color: #d97706 !important; /* Amber badge to indicate modified */
    color: #ffffff !important;
  }

  /* Client Selection UI */
  #alreadyexistclientdataview {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
    margin: 1rem 0;
  }

  .client-selection-card {
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1rem;
    cursor: pointer;
    transition: all 0.2s;
    background: white;
  }

  .client-selection-card:hover {
    border-color: var(--primary-blue);
    background-color: #f0f7ff;
  }

  .client-selection-card.selected {
    border-color: var(--primary-blue);
    background-color: #f0f7ff;
    box-shadow: 0 0 0 2px var(--primary-blue);
  }

  /* Responsive Adjustments */
  @media (max-width: 768px) {
    .premium-container {
      margin: 1rem auto;
      padding: 0 0.75rem;
    }

    .page-header {
      flex-direction: column;
      text-align: center;
    }
  }

  .btn-deepblue {
    background-color: var(--deep-blue) !important;
    color: white !important;
    border-radius: 10px !important;
    padding: 0.5rem 1.25rem !important;
    font-weight: 600 !important;
  }

  .btn-primary-premium {
    background-color: var(--primary-blue) !important;
    color: white !important;
    border-radius: 12px !important;
    padding: 0.75rem 2rem !important;
    font-weight: 700 !important;
    box-shadow: 0 4px 6px -1px rgba(13, 110, 253, 0.3);
  }

  .btn-primary-premium:disabled,
  .btn-primary-premium.disabled,
  .next-step:disabled,
  .next-step.disabled {
    background-color: #94a3b8 !important;
    border-color: #94a3b8 !important;
    color: #f1f5f9 !important;
    opacity: 0.55 !important;
    cursor: not-allowed !important;
    box-shadow: none !important;
    transform: none !important;
  }

  .btn-success-premium {
    background-color: var(--premium-green) !important;
    color: white !important;
    border-radius: 10px !important;
    font-weight: 600 !important;
  }

  .radio-premium-group {
    background: white;
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1rem;
    margin-top: 1rem;
  }

  /* Wizard Step Indicator */
  .wizard-steps {
    display: flex;
    justify-content: space-between;
    margin-bottom: 2rem;
    position: relative;
    padding: 0 1rem;
  }

  .wizard-steps::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 2px;
    background: #e2e8f0;
    z-index: 1;
    transform: translateY(-50%);
  }

  .step-item {
    position: relative;
    z-index: 2;
    background: #f1f5f9;
    width: 45px;
    height: 45px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    color: #64748b;
    border: 3px solid white;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
  }

  .step-label {
    position: absolute;
    top: 55px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    white-space: nowrap;
    color: #94a3b8;
  }

  /* Step Colors */
  .step-item[data-step="1"].active { background: #0d6efd; color: white; box-shadow: 0 0 15px rgba(13, 110, 253, 0.4); }
  .step-item[data-step="2"].active { background: #6610f2; color: white; box-shadow: 0 0 15px rgba(102, 16, 242, 0.4); }
  .step-item[data-step="3"].active { background: #008080; color: white; box-shadow: 0 0 15px rgba(0, 128, 128, 0.4); }
  .step-item[data-step="4"].active { background: #fd7e14; color: white; box-shadow: 0 0 15px rgba(253, 126, 20, 0.4); }

  .step-item.active .step-label { color: var(--deep-blue); }
  
  .step-item.completed { background: #1b9e77 !important; color: white; }

  .wizard-content-step {
    display: none;
    animation: fadeInStep 0.4s ease-out;
  }

  @keyframes fadeInStep {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
  }

  .wizard-footer {
    display: flex;
    justify-content: space-between;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e2e8f0;
  }
</style>

<div class="premium-container"> 
  <div class="page-header shadow">
    <div class="d-flex align-items-center gap-3">
      <i class="fa <?php echo $isEdit ? 'fa-edit text-warning' : 'fa-file-invoice-dollar'; ?> fa-2x"></i>
      <h3>
        <?php if ($isEdit): ?>
          Edit Provisional Sales Order: <span class="badge bg-warning text-dark font-monospace"><?php echo htmlspecialchars($orderPsono); ?></span>
        <?php else: ?>
          <?php echo _t('create_provisional_sales_order'); ?>
        <?php endif; ?>
      </h3>
    </div>
    <div class="d-flex gap-2">
      <a href="provisionalsaleorder_review.php?k=<?php echo urlencode($key); ?>&mdid=<?php echo urlencode($mdid); ?>&iframe=true" class="btn btn-light text-primary fw-bold border border-primary-subtle d-flex align-items-center gap-2">
        <i class="fa fa-list"></i> <?php echo _t('provisional_orders'); ?>
      </a>
      <button class="btn btn-refresh" onclick="window.location.reload();">
        <i class="fa fa-sync me-2"></i><?php echo _t('refresh'); ?>
      </button>
    </div>
  </div>

  <form id="salesOrderForm" novalidate autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
    <input type="hidden" id="order_mode" name="mode" value="<?php echo $orderMode; ?>" />
    <input type="hidden" id="edit_psono" name="edit_psono" value="<?php echo htmlspecialchars($orderPsono); ?>" />
    <input type="hidden" id="is_emergency_sowing" name="is_emergency_sowing" value="no" />
    <!-- Dummy inputs to trick browser password and address autofill -->
    <input type="text" name="fake_usernamenotremember" style="display:none!important;" aria-hidden="true" tabindex="-1" autocomplete="new-password" />
    <input type="password" name="fake_passwordnotremember" style="display:none!important;" aria-hidden="true" tabindex="-1" autocomplete="new-password" />

    <!-- Step Indicator -->
    <div class="wizard-steps mb-5">
        <div class="step-item active" data-step="1">1 <span class="step-label">Client Info</span></div>
        <div class="step-item" data-step="2">2 <span class="step-label">Order Details</span></div>
        <div class="step-item" data-step="3">3 <span class="step-label">Delivery</span></div>
        <div class="step-item" data-step="4">4 <span class="step-label">Freight</span></div>
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
                <div id="clientname-validation-msg" class="small mt-1 fw-bold"></div>
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
                <label class="form-label"><i class="fa fa-landmark"></i> Block/Tahseel </label>
                <input type="text" id="tahseel" name="tahseel" class="form-control restrict-double-quote" autocomplete="one-time-code" autocorrect="off" autocapitalize="off" spellcheck="false" placeholder="Enter block / tahseel"  />
              </div>
              <div class="col-md-3">
                <label class="form-label"><i class="fa fa-building"></i> Nearest City/Village <span class="text-danger text-xl">*</span></label>
                <input type="text" id="cityname" name="cityname" class="form-control restrict-double-quote" autocomplete="one-time-code" autocorrect="off" autocapitalize="off" spellcheck="false" placeholder="Enter nearest city/village" required/>
              </div>
            </div>

            <div class="row g-3 mt-1">
              <div class="col-md-12">
                <label class="form-label d-flex justify-content-between align-items-center">
                  <span><i class="fa fa-map-marker-alt"></i> <?php echo _t('address'); ?></span>
                  <small id="address_char_count" class="text-muted fw-normal">0/500 characters</small>
                </label>
                <textarea id="delivery_address" name="delivery_address" class="form-control restrict-double-quote" autocomplete="one-time-code" autocorrect="off" autocapitalize="off" spellcheck="false" rows="2" maxlength="500"
                  placeholder="Full address (Max 500 characters)"></textarea>
              </div>
            </div>
          </div>
        </div>
        <div class="wizard-footer">
          <div></div>
          <button type="button" class="btn btn-primary-premium next-step disabled" data-next="2" disabled title="Please complete all required client information to proceed">
            Next: Order Details <i class="fa fa-arrow-right ms-2"></i>
          </button>
        </div>
    </div>

    <!-- Step 2: Provisional Order Details -->
    <div class="wizard-content-step" id="step2">
        <!-- New Div for Lead Client & Product Details Check -->
        <div id="lead-info-banner" style="display: none; background: #fafafa; border: 1px solid #e2e8f0;" class="border-2 rounded-4 p-4 mb-4 shadow-sm"></div>

        <div class="premium-card mb-4" id="card2">
          <div class="card-header-premium">
            <i class="fa fa-shopping-basket"></i>
            Step 02: <?php echo _t('provisional_sales_order_items'); ?>
          </div>
          <div class="premium-card-body">
            <!-- Out-of-catalog instruction banner -->
            <div class="alert alert-light border d-flex align-items-center justify-content-between p-3 mb-3 rounded-3" style="background-color: #fffdf5; border-color: #fef08a !important;">
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
                      <input type="hidden" name="order_item_id[]" class="order_item_id" value="" />
                      <select name="productname[]" class="form-select select2 productname" required></select>
                    </td>
                    <td>
                      <input type="text" name="rate[]" class="form-control invrowcell_rm ratecal rate_td"
                        onKeyPress="return isFloatNumber(event,this);" <?php if (intval($loginroleid ?? 0) > 2) echo 'readonly style="background-color: #f8fafc;"'; ?> />
                    </td>
                    <td>
                      <input type="text" name="quantity[]" class="form-control invrowcell_rm qtycal qty_td"
                        onKeyPress="return isFloatNumber(event,this);" required />
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
                      <input type="date" name="expecteddelivery[]" class="form-control expecteddelivery" <?php if (intval($loginroleid ?? 0) > 2) echo 'readonly style="background-color: #f8fafc; pointer-events: none;"'; ?> />
                    </td>
                    <td class="text-center">
                      <button type="button" class="btn btn-outline-danger btn-sm removeRow">
                        <i class="fa fa-trash-alt"></i>
                      </button>
                      <input type="hidden" name="stockdbid[]" class="form-control stockdbid" />
                    </td>
                    <th class="text-center d-none" style="width:60px;" rowspan="2">is readytosale</th>
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
            Step 03: Delivery Address & Remarks
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

            <!-- Step 3 Remarks -->
            <div class="mt-4" id="step3RemarksDiv">
              <label class="form-label fw-bold"><i class="fa fa-comment-dots text-muted"></i>
                <?php echo _t('remarks'); ?></label>
              <textarea name="delivery_remarks" id="delivery_remarks" class="form-control border-primary-subtle shadow-sm order-remarks-sync" rows="2" placeholder="Any special instructions / remarks for delivery..."></textarea>
            </div>

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
              Step 04: Freight Charge Details (Grouped by Address & Date)
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
              <textarea name="remarks" id="remarks" class="form-control border-primary-subtle shadow-sm order-remarks-sync" rows="3" placeholder="Any special instructions for this order..."></textarea>
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
            <input type="hidden" id="clientid" name="clientid" value="" />
            <button type="submit" id="submitOrderBtn" class="btn btn-primary-premium shadow-lg px-5">
              <i class="fa <?php echo $isEdit ? 'fa-save' : 'fa-check-circle'; ?> me-2"></i> 
              <?php echo $isEdit ? 'Update Provisional Order' : 'Submit to Create Provisional Order'; ?>
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

<style>
  #historyAddressTbl input[type="checkbox"] {
    transform: scale(1.3) !important;
    margin: 0 !important;
    cursor: pointer;
    border: 1px solid #0d6efd !important;
    box-shadow: 0 0 5px rgba(13, 110, 253, 0.2);
  }
  #historyAddressTbl .chk-cell {
    background-color: #f0f7ff !important;
    border-right: 2px solid #dee2e6;
  }
  #historyAddressTbl th, #historyAddressTbl td {
    vertical-align: middle;
  }
</style>

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
              <thead class="small text-uppercase fw-bold">
                <tr>
                  <th class="text-center" style="width: 45px;">S.No</th>
                  <th class="text-center" style="width: 50px;">Select</th>
                  <th style="min-width: 170px; max-width: 260px; white-space: normal; text-align: left;">Product Name</th>
                  <th style="min-width: 130px; white-space: nowrap; text-align: left;">Prod. Order No</th>
                  <th style="min-width: 180px; text-align: left;">Location / Polyhouse</th>
                  <th class="text-center" style="min-width: 130px; white-space: nowrap;">Expected Ready Date</th>
                  <th class="text-center" style="min-width: 90px; white-space: nowrap;">Order Qty</th>
                  <th class="text-center" style="min-width: 95px; white-space: nowrap;">Conf. Alloc</th>
                  <th class="text-center" style="min-width: 105px; white-space: nowrap;">Unconf. Alloc</th>
                  <th class="text-center" style="min-width: 105px; white-space: nowrap; color: #6f42c1;">RSO-Movement</th>
                  <th class="text-center text-success" style="min-width: 95px; white-space: nowrap;">Balance</th>
                  <th class="text-center text-primary" style="width: 100px; min-width: 100px; white-space: nowrap;">Alloc. Qty</th>
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
                <input type="tel" id="enq_mobileno" name="mobileno" class="form-control form-control-sm bg-white" required placeholder="Mobile number">
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

                <select id="enq_statename"   class="form-select select2"  >
                  <option value="">Select State</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-bold text-muted mb-1"><i class="fa fa-city text-secondary"></i> District</label>
                <!-- <input type="text" id="enq_districtname" name="districtname" class="form-control form-control-sm bg-white" placeholder="District"> -->

                <select id="enq_districtname"   class="form-select select2" >
                  <option value="">Select District</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-bold text-muted mb-1"><i class="fa fa-building text-secondary"></i> Nearest City</label>
                <input type="text" id="enq_cityname" name="cityname restrict-double-quote" class="form-control form-control-sm bg-white" placeholder="Nearest city">
              </div>
              <div class="col-md-12">
                <label class="form-label small fw-bold text-muted mb-1"><i class="fa fa-map-marker-alt text-secondary"></i> Address</label>
                <textarea id="enq_address" name="address" class="form-control form-control-sm restrict-double-quote bg-white" rows="2" placeholder="Full address"></textarea>
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
            <div class="row g-3">
              <div class="col-md-12">
                <label class="form-label small fw-bold mb-1" style="color: #854d0e;"><i class="fa fa-tag"></i> Product Name (Demanded by Client) *</label>
                <input type="text" id="enq_product_name" name="product_name" class="form-control" required placeholder="Enter demanded product name, specs or description" style="border-color: #fde047;">
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold mb-1" style="color: #854d0e;"><i class="fa fa-cubes"></i> Expected Quantity / Volume</label>
                <input type="text" id="enq_quantity" name="quantity" class="form-control" placeholder="e.g. 50 Bags, 100 MT, 500 Pcs">
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold mb-1" style="color: #854d0e;"><i class="fa fa-calendar-alt"></i> Expected Date</label>
                <input type="date" id="enq_expected_date" name="expected_date" class="form-control">
              </div>
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

<?php include('../footer.php'); ?>
<script src="<?php echo $prefix; ?>krf-orderandsales/js/latlong_handler.js"></script>
<script type="text/javascript" src="<?php echo $prefix; ?>krf-orderandsales/js/pjs_function.js"></script>
<script type="text/javascript" src="<?php echo $prefix; ?>krf-orderandsales/js/invoice_calculation.js"></script>
<script src="<?php echo $prefix; ?>krf-orderandsales/js/vehicle_calculator.js?v=<?php echo time(); ?>"></script>
<script>
  let boxPackingMaster = []; 
  let stateDistrictArray = [];

  document.addEventListener("input", function (event) { 
    if (!event.target.classList.contains("restrict-double-quote")) {
        return;
    }

    if (event.target.value.includes('"')) {
        event.target.value = event.target.value.replace(/"/g, "");
    }
});
  function handleFreightSelection(data) {
    // console.log("Freight Data Received:", data);
    console.log(data);
    // Target fields
    const $freightAmount = $("#freight_amount");
    const $vehicleNo = $("#vehicleno_hidden");
    const $distanceKm = $("#distance_hidden");

    // 1. Populate the rate and trigger calculation logic
    $freightAmount.val(data.charge || 0).trigger('input');

    // 2. Populate visible/hidden details fields
    if ($("#vehiclename_hidden").length) $("#vehiclename_hidden").val(data.vehiclename || '').trigger('change');
    if ($vehicleNo.length) $vehicleNo.val(data.vehicleno || '').trigger('change');
    if ($distanceKm.length) $distanceKm.val(data.calc_km || '').trigger('change');

    // 3. Update Meta Info Display
    if (data.vehiclename) {
        $("#freight-meta-info").html(`
            <div class="d-flex flex-column gap-1">
                <span><i class="fa fa-shuttle-van me-1"></i> ${data.vehiclename}</span>
                <span><i class="fa fa-route me-1"></i> ${data.calc_km} KM</span>
            </div>
        `);
    }
  }

  $(document).ready(function () {
    const target = document.getElementById('grandtotalamount');
    if (target) {
      const observer = new MutationObserver(function (mutations) {
        if (typeof calculateGrandTotalFreight === "function") {
          calculateGrandTotalFreight();
        } else {
          $("#grandtotalamount_final").text($(target).text());
        }
      });
      observer.observe(target, { childList: true, characterData: true, subtree: true });
    }

    // Wizard Navigation Logic
    $(".next-step").on("click", function(e) { 
        if ($(this).prop("disabled") || $(this).hasClass("disabled")) {
            e.preventDefault();
            e.stopPropagation();
            if (typeof window.updateStep1NextButtonState === 'function') {
                window.updateStep1NextButtonState();
            }
            iziToast.warning({
                title: 'Incomplete / Invalid Information',
                message: 'Please resolve any invalid fields and fill all required client information to proceed.',
                position: 'topRight'
            });
            return false;
        }

        $(".freightratemodal").prop("disabled",true);
        const nextStep = $(this).data("next");
        const currentStep = nextStep - 1;

        // Validation logic for each step
        if (currentStep === 1) {
            const clientName = $("#clientname").val();
            const mobileNo = $("#mobileno").val();
            statename= $("#statename").val();
            districtname= $("#districtname").val();
            tahseel= $("#tahseel").val();
            cityname= $("#cityname").val();
            const emailId = ($("#emailid").val() || "").trim();

            if (!mobileNo ) {
                iziToast.warning({ title: 'Required Fields', message: 'Please fill mobile number to proceed.', position: 'topRight' });
                if (typeof window.validateMobileNumber === 'function') window.validateMobileNumber();
                $("#mobileno").focus();
                return;
            }

            if (typeof window.validateMobileNumber === 'function' && !window.validateMobileNumber()) {
                iziToast.warning({ title: 'Invalid Mobile Number', message: 'Please enter a valid 10-digit mobile number starting with 6, 7, 8, or 9.', position: 'topRight' });
                $("#mobileno").focus();
                return;
            }

            if (!clientName || (typeof window.validateClientName === 'function' && !window.validateClientName())) {
                iziToast.warning({ title: 'Invalid Client Name', message: 'Please enter a valid client name (at least 3 characters, cannot start with a number; suffixes are allowed).', position: 'topRight' });
                if (typeof window.validateClientName === 'function') window.validateClientName();
                $("#clientname").focus();
                return;
            }

            if (emailId !== "" && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailId)) {
                iziToast.warning({ title: 'Invalid Email', message: 'Please enter a valid email address or leave it blank.', position: 'topRight' });
                $("#emailid").focus();
                return;
            }

            if (!statename ) {
                iziToast.warning({ title: 'Required Fields', message: 'Please select a state to proceed.', position: 'topRight' });
                $("#statename").focus();
                return;
            }
            if (!districtname ) {
                iziToast.warning({ title: 'Required Fields', message: 'Please select a district to proceed.', position: 'topRight' }); 
                $("#districtname").focus();
                return;
            }
            if (!cityname ) {
                iziToast.warning({ title: 'Required Fields', message: 'Please enter nearest city/village to proceed.', position: 'topRight' });
                $("#cityname").focus();
                return;
            }

            if ($("#step1 .is-invalid").length > 0) {
                iziToast.warning({ title: 'Invalid Fields', message: 'Please correct highlighted fields before proceeding.', position: 'topRight' });
                $("#step1 .is-invalid").first().focus();
                return;
            }

            loginRoleId = parseInt($("#hidden_loginroleid").val() || 0, 10);
            $(".distprt_td").prop("disabled", loginRoleId > 3);
            $(".distamt_td").prop("disabled", loginRoleId > 3);


        } else if (currentStep === 2) {
            let isValid = true;
            let $rows = $("#provisionorderitemsTable tbody tr");

            if ($rows.length === 0) {
                iziToast.warning({ title: 'Missing Items', message: 'Please add at least one product to the order.', position: 'topRight' });
                return;
            }

            $rows.each(function() {
                let product = $(this).find(".productname").val();
                let qty = $(this).find(".qty_td").val();
                let rate = $(this).find(".rate_td").val();
                let deliveryDate = $(this).find(".expecteddelivery").val();

                if (!product || !qty || parseFloat(qty) <= 0 || !rate || !deliveryDate) {
                    isValid = false;
                    $(this).css("background-color", "#fff5f5");
                } else {
                    $(this).css("background-color", "");
                }
            });

            if (!isValid) {
                iziToast.warning({ 
                    title: 'Required Missing', 
                    message: 'Please ensure all items have a Product, Rate, Quantity, and Expected Delivery Date filled.', 
                    position: 'topRight' 
                });
                return;
            }
            const isEditStep = $("#order_mode").val() === "edit";
            const hasDeliverySelected = $('input[name="pickupfrom"]:checked').length > 0;
            if (!isEditStep && !hasDeliverySelected) {
              $('input[name="pickupfrom"]').prop('checked', false);
              $("#deliveryboxdiv").addClass("d-none");
              $("#deliveryaddressdiv").fadeOut();
              $("#deliveryboxtbl tbody").empty();
              $("#deliveryaddresstbl tbody").empty();
            } else if (typeof syncDeliveryTablesWithStep2 === 'function') {
              syncDeliveryTablesWithStep2();
            }

        } else if (currentStep === 3) {
          var pickupfrom = $('input[name="pickupfrom"]:checked').val();
          if (!pickupfrom){
            alert("You have Not select any option  Delivery Address")
            return false;
          }
          
            // Only validate address if 'delivered' is selected and user added rows

          if ($("#deliverbox_opt").is(":checked")) {

             $(".freightratemodal").prop("disabled",true);
                const $rows = $("#deliveryboxtbl tbody tr"); 
              if ($rows.length <= 0) {
                  Swal.fire(
                      "Validation",
                      "Please add at least one Box Packing detail.",
                      "warning"
                  );
                  return;
              }

              let isValid = true;

              $rows.each(function () {

                  const $row = $(this);

                  const pickupPoint = $row.find(".pickuppoint").val();
                  const stateId     = $row.find(".state_box").val();
                  const districtId  = $row.find(".district_box").val();
                  const locationId  = $row.find(".city_box").val();
                  const plantsPerBox = parseInt($row.find(".plants_per_box").val()) || 0;
                  const noOfBox      = parseInt($row.find(".no_of_box").val()) || 0;

                  if (!pickupPoint) {
                      Swal.fire("Validation", "Please select Pickup Point.", "warning");
                      $row.find(".pickuppoint").focus();
                      isValid = false;
                      return false;
                  }

                  if (!stateId) {
                      Swal.fire("Validation", "Please select State.", "warning");
                      $row.find(".state_box").focus();
                      isValid = false;
                      return false;
                  }

                  if (!districtId) {
                      Swal.fire("Validation", "Please select District.", "warning");
                      $row.find(".district_box").focus();
                      isValid = false;
                      return false;
                  }

                  if (!locationId) {
                      Swal.fire("Validation", "Please select Location.", "warning");
                      $row.find(".city_box").focus();
                      isValid = false;
                      return false;
                  }

                  if (plantsPerBox <= 0) {
                      Swal.fire("Validation", "Plants per Box is invalid.", "warning");
                      isValid = false;
                      return false;
                  }

                  if (noOfBox <= 0) {
                      Swal.fire("Validation", "No. of Boxes is invalid.", "warning");
                      isValid = false;
                      return false;
                  }
              });

              if (!isValid) {
                  return;
              }

          }  

          else if ($("#deliver_opt").is(":checked")) {

                const $rows = $("#deliveryaddresstbl tbody tr");
                if ($rows.length > 0) {
                    let incompleteRows = false;
                    $rows.each(function() {
                        const prod = $(this).find(".delivery_product_select").val();
                        const qty = $(this).find(".delivery_qty_input").val();
                        const addr = $(this).find("input[name='delivery_fulladdress[]']").val();
                        if (!prod || !qty || parseFloat(qty) <= 0 || !addr) {
                            incompleteRows = true;
                            $(this).css("background-color", "#fff5f5");
                        } else {
                            $(this).css("background-color", "");
                        }
                    });

                    if (incompleteRows) {
                        iziToast.error({ title: 'Incomplete Data', message: 'Please fill all mandatory fields (Product, Qty, Full Address) in the delivery table, or click "Skip & Update Later".', position: 'topRight' });
                        return;
                    }

                    // MATCHING QUANTITY VALIDATION
                    // 1. Get ordered quantities per product
                    const orderedMap = {};
                    let totalOrderedQty = 0;
                    $("#provisionorderitemsTable tbody tr").each(function() {
                        const pid = $(this).find(".productname").val();
                        const qty = parseFloat($(this).find(".qtycal").val()) || 0;
                        if (pid) {
                            orderedMap[pid] = (orderedMap[pid] || 0) + qty;
                            totalOrderedQty += qty;
                        }
                    });

                    // 2. Get delivery quantities per product
                    const deliveryMap = {};
                    let allProductsDeliveryQty = 0;
                    let totalDeliveryQty = 0;
                    $rows.each(function() {
                        const pid = $(this).find(".delivery_product_select").val();
                        const qty = parseFloat($(this).find(".delivery_qty_input").val()) || 0;
                        totalDeliveryQty += qty;
                        if (pid === 'all') {
                            allProductsDeliveryQty += qty;
                        } else if (pid) {
                            deliveryMap[pid] = (deliveryMap[pid] || 0) + qty;
                        }
                    });

                    const orderedProductIds = Object.keys(orderedMap);
                    if (allProductsDeliveryQty > 0 && orderedProductIds.length === 1) {
                        const singlePid = orderedProductIds[0];
                        deliveryMap[singlePid] = (deliveryMap[singlePid] || 0) + allProductsDeliveryQty;
                    }

                    // 3. Compare
                    let mismatched = [];
                    if (allProductsDeliveryQty > 0 && orderedProductIds.length > 1) {
                        if (Math.abs(totalOrderedQty - totalDeliveryQty) > 0.001) {
                            mismatched.push(`Total Ordered: <b>${totalOrderedQty.toLocaleString()}</b>, Assigned in Delivery: <b>${totalDeliveryQty.toLocaleString()}</b>`);
                        }
                    } else {
                        for (let pid in orderedMap) {
                            const oQty = orderedMap[pid];
                            const dQty = deliveryMap[pid] || 0;
                            if (Math.abs(oQty - dQty) > 0.001) {
                                const pName = $("#provisionorderitemsTable tbody tr").find(`.productname option[value="${pid}"]`).first().text() || pid;
                                mismatched.push(`<strong>${pName}</strong>: Ordered <b>${oQty}</b>, Assigned <b>${dQty}</b>`);
                            }
                        }
                    }

                    if (mismatched.length > 0) {
                        Swal.fire({
                            title: 'Quantity Mismatch!',
                            html: '<div class="text-start">Total delivery quantity must match ordered quantity for all products:<br><br><ul class="mb-0">' + 
                                  mismatched.map(m => `<li>${m}</li>`).join('') + '</ul></div>',
                            icon: 'error',
                            confirmButtonColor: '#ef4444'
                        });
                        return;
                    }
                }
            }

        }

        goToStep(nextStep);

        // Special logic for entering Step 4

        if (nextStep === 4) {
             
            generateFreightGroups();
        }
    });

    // Skip & Update Later Button Click Handler
    $(document).on("click", "#btnSkipDeliveryStep", function() {
      $("#deliveryaddresstbl tbody").empty();
      $("#deliveryboxtbl tbody").empty();
      goToStep(4);
      generateFreightGroups(true);
    });

    $(".prev-step").on("click", function() {
        const prevStep = $(this).data("prev");
        goToStep(prevStep);
    });

    $(document).on("click", ".step-item", function() {
        const targetStep = parseInt($(this).data("step"), 10);
        if (targetStep && targetStep >= 1 && targetStep <= 4) {
            goToStep(targetStep);
            if (targetStep === 4) {
                generateFreightGroups();
            }
        }
    });

    function goToStep(stepNumber) { 
        if (stepNumber === 3 || stepNumber === 4) {
            if (typeof syncDeliveryTablesWithStep2 === 'function') {
                syncDeliveryTablesWithStep2();
            }
        }
        $(".wizard-content-step").hide();
        $("#step" + stepNumber).fadeIn(400);
        
        // Update Step Indicator
        $(".step-item").removeClass("active completed");

        for (let i = 1; i <= 4; i++) {
            const $item = $(".step-item[data-step='" + i + "']");
            if (i < stepNumber) {
                $item.addClass("completed");
                $item.html('<i class="fa fa-check"></i> <span class="step-label">' + getStepLabel(i) + '</span>');
            } else if (i == stepNumber) {
                $item.addClass("active");
                $item.html(i + ' <span class="step-label">' + getStepLabel(i) + '</span>');
            } else {
                $item.html(i + ' <span class="step-label">' + getStepLabel(i) + '</span>');
            }
        }

        $('html, body').animate({ scrollTop: $(".wizard-steps").offset().top - 20 }, 500);
    }

    function getStepLabel(step) {
        const labels = { 1: 'Client Info', 2: 'Order Details', 3: 'Delivery', 4: 'Freight' };
        return labels[step] || '';
    }
 

    $("#addBlankAddressRow").on("click", function() {
      const rowId = 'row_' + Date.now();
      const productOptions = getStep2ProductsAsOptions();

      const stateid = $("#stateid").val() || '';
      const districtid = $("#districtid").val() || '';

      const html = `
          <tr id="${rowId}">
              <td>
                  <select name="delivery_productid[]" class="form-select delivery_product_select" required>
                      ${productOptions}
                  </select>
              </td>

              <td>
                  <input type="text"
                         name="delivery_qty[]"
                         class="form-control delivery_qty_input"
                         placeholder="0"
                         onKeyPress="return isFloatNumber(event,this);"
                         required />
                  <div class="pending-qty-badge mt-1"
                       style="font-size: 0.75rem; display: none;"></div>
              </td>

              <td>
                  <input type="text"
                         name="delivery_distance[]"
                         class="form-control delivery_distance_input"
                         placeholder="0.0"
                         onKeyPress="return isFloatNumber(event,this);" />
              </td>

              <td class="d-none">
                  <input type="hidden"
                         name="delivery_latlong[]"
                         value="" />
              </td>

              <td>
                  <select
                      class="form-control del_state"
                      name="del_state[]"
                      required
                  >
                      ${getStateOptions(stateid)}
                  </select>

                  <input type="hidden"
                         class="delivery_state"
                         name="delivery_state[]"
                         value="">

                  <input type="hidden"
                         class="delivery_stateid"
                         name="delivery_stateid[]"
                         value="${stateid}">
              </td>

              <td>
                  <select
                      class="form-control del_district"
                      name="del_district[]"
                      required
                  >
                      ${getDistrictOptions(stateid, districtid)}
                  </select>

                  <input type="hidden"
                         class="delivery_district"
                         name="delivery_district[]"
                         value="">

                  <input type="hidden"
                         class="delivery_district_id"
                         name="delivery_district_id[]"
                         value="${districtid}">
              </td>

              <td>
                  <input type="text"
                         name="delivery_city[]"
                         class="form-control"
                         placeholder="City/Village" />
              </td>

              <td>
                  <input type="text"
                         name="delivery_pincode[]"
                         class="form-control"
                         placeholder="Pincode"
                         onKeyPress="return isNumber(event);" />
              </td>

              <td>
                  <input type="text"
                         name="delivery_fulladdress[]"
                         class="form-control"
                         placeholder="Full Address"
                         required />
              </td>

              <td class="text-center">
                  <div class="d-flex gap-1 justify-content-center">
                      <button type="button"
                              class="btn btn-outline-primary btn-sm"
                              onclick="copyDeliveryRow(this)"
                              title="Copy Row">
                          <i class="fa fa-copy"></i>
                      </button>

                      <button type="button"
                              class="btn btn-outline-danger btn-sm"
                              onclick="$(this).closest('tr').remove();">
                          <i class="fa fa-trash"></i>
                      </button>
                  </div>
              </td>
          </tr>
      `;

      $("#deliveryaddresstbl tbody").append(html);
  });

    // Show Old Delivery History
    $("#showOldDeliveryHistory").on("click", function() {
        const clientId = $("#clientid").val();
        if (!clientId) {
            Swal.fire('No Client Selected', 'Please select a client first.', 'warning');
            return;
        }
        
        $("#historyAddressTbl tbody").html('<tr><td colspan="5" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><br>Loading history...</td></tr>');
        $("#clientDeliveryHistoryModal").modal("show"); 
        GetUniqeClientDeliveryAddressData(clientId, function(res) {
            let html = '';
            if (res && res.length > 0) {
                res.forEach((row, i) => {
                    html += `
                        <tr>
                            <td class="text-center chk-cell">
                                <input type="checkbox" class="form-check-input history-checkbox" 
                                    data-row='${JSON.stringify(row).replace(/'/g, "&apos;")}'>
                            </td>
                            <td 
                              data-stateid="${row.stateid || ''}"
                              data-districtid="${row.districtid || ''}"
                              >${row.dest_state || '-'}<br><small class="text-muted">${row.dest_district || '-'}</small></td>
                            <td>${row.dest_city || '-'}<br><small class="text-muted">${row.dest_pincode || '-'}</small></td>
                            <td style="max-width:300px; white-space: normal;">${row.deliveryaddress || '-'}</td>
                            <td>${row.distance || 0} KM</td>
                        </tr>
                    `;
                });
            } else {
                html = '<tr><td colspan="5" class="text-center py-4 text-muted">No previous delivery addresses found for this client.</td></tr>';
            }
            $("#historyAddressTbl tbody").html(html);
        });
    });

    // Select All History
    $("#selectAllHistory").on("change", function() {
        $(".history-checkbox").prop('checked', $(this).is(':checked'));
    });

    // Apply Selected History
  /*  $("#btnApplySelectedHistory").on("click", function() {
        const selected = [];
        $(".history-checkbox:checked").each(function() {
            selected.push($(this).data('row'));
        });

        if (selected.length === 0) {
            Swal.fire('No Selection', 'Please select at least one address.', 'info');
            return;
        }

        const productOptionsHtml = getStep2ProductsAsOptions();

        selected.forEach(rowData => {

            const stateid = rowData.stateid || '';
            const districtid = rowData.districtid || '';

            const statename = rowData.dest_state || '';
            const districtname = rowData.dest_district || '';
            const $newRow = $(`
                <tr class="align-middle">
                    <td>
                        <select name="delivery_productid[]" class="form-select select2 delivery_product_select">${productOptionsHtml}</select>
                    </td>
                    <td>
                        <input type="text" name="delivery_qty[]" class="form-control delivery_qty_input" placeholder="0" onKeyPress="return isFloatNumber(event,this);" required />
                    </td>
                    <td>
                        <input type="text" name="delivery_distance[]" class="form-control delivery_distance_input" value="${rowData.distance || 0}" onKeyPress="return isFloatNumber(event,this);" />
                    </td>
                    <td class="d-none">
                        <input type="hidden" name="delivery_latlong[]" value="${rowData.dest_latitude || ''},${rowData.dest_longitude || ''}" />
                    </td>
                 
                  <!-- STATE -->
                  <td>
                      <select class="form-control del_state" name="del_state[]" required
                      >
                          ${getStateOptions(stateid)}
                      </select>
                      <input type="hidden" class="delivery_state"  name="delivery_state[]" value="${statename}">

                      <input type="hidden"
                             class="delivery_stateid"
                             name="delivery_stateid[]"
                             value="${stateid}">

                  </td>
                  <!-- DISTRICT -->
                  <td>
                      <select  class="form-control del_district"  name="del_district[]"
                          required
                      >
                          ${getDistrictOptions(stateid, districtid)}
                      </select>

                      <input type="hidden" class="delivery_district"  name="delivery_district[]"  value="${districtname}">

                      <input type="hidden"   class="delivery_district_id"   name="delivery_district_id[]"   value="${districtid}"> 
                  </td>
                    


                    <td><input type="text" name="delivery_city[]" class="form-control" value="${rowData.dest_city || ''}"></td>
                    <td><input type="text" name="delivery_pincode[]" class="form-control" value="${rowData.dest_pincode || ''}"></td>
                    <td><input type="text" name="delivery_fulladdress[]" class="form-control" value="${rowData.deliveryaddress || ''}" required></td>
                    <td class="text-center">
                        <div class="d-flex gap-1 justify-content-center">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="copyDeliveryRow(this)" title="Copy Row">
                                <i class="fa fa-copy"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger rmdeliveryaddbtn">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `);

            $("#deliveryaddresstbl tbody").append($newRow);
            $newRow.find(".delivery_product_select").select2({ width: '100%' });
        });

        $("#clientDeliveryHistoryModal").modal("hide");
        iziToast.success({ title: 'Success', message: selected.length + ' addresses added.' });
    });*/
    $("#btnApplySelectedHistory").on("click", function() {

        const selected = [];

        $(".history-checkbox:checked").each(function() {
            selected.push($(this).data('row'));
        });

        if (selected.length === 0) {
            Swal.fire(
                'No Selection',
                'Please select at least one address.',
                'info'
            );
            return;
        }

        const productOptionsHtml = getStep2ProductsAsOptions();

        selected.forEach(rowData => {

            const stateid = rowData.stateid || '';
            const districtid = rowData.districtid || '';

            const statename = rowData.dest_state || '';
            const districtname = rowData.dest_district || '';

            const $newRow = $(`
                <tr class="align-middle">

                    <td>
                        <select name="delivery_productid[]"
                                class="form-select select2 delivery_product_select">
                            ${productOptionsHtml}
                        </select>
                    </td>

                    <td>
                        <input type="text"
                               name="delivery_qty[]"
                               class="form-control delivery_qty_input"
                               placeholder="0"
                               onKeyPress="return isFloatNumber(event,this);"
                               required />
                    </td>

                    <td>
                        <input type="text"
                               name="delivery_distance[]"
                               class="form-control delivery_distance_input"
                               value="${rowData.distance || 0}"
                               onKeyPress="return isFloatNumber(event,this);" />
                    </td>

                    <td class="d-none">
                        <input type="hidden"
                               name="delivery_latlong[]"
                               value="${rowData.dest_latitude || ''},${rowData.dest_longitude || ''}" />
                    </td>


                    <!-- STATE -->
                    <td>

                        <select
                            class="form-control del_state"
                            name="del_state[]"
                            required
                        >
                            ${getStateOptions(stateid)}
                        </select>

                        <input type="hidden"
                               class="delivery_state"
                               name="delivery_state[]"
                               value="${statename}">

                        <input type="hidden"
                               class="delivery_stateid"
                               name="delivery_stateid[]"
                               value="${stateid}">

                    </td>


                    <!-- DISTRICT -->
                    <td>

                        <select
                            class="form-control del_district"
                            name="del_district[]"
                            required
                        >
                            ${getDistrictOptions(stateid, districtid)}
                        </select>

                        <input type="hidden"
                               class="delivery_district"
                               name="delivery_district[]"
                               value="${districtname}">

                        <input type="hidden"
                               class="delivery_district_id"
                               name="delivery_district_id[]"
                               value="${districtid}">

                    </td>


                    <td>
                        <input type="text"
                               name="delivery_city[]"
                               class="form-control"
                               value="${rowData.dest_city || ''}">
                    </td>

                    <td>
                        <input type="text"
                               name="delivery_pincode[]"
                               class="form-control"
                               value="${rowData.dest_pincode || ''}">
                    </td>

                    <td>
                        <input type="text"
                               name="delivery_fulladdress[]"
                               class="form-control"
                               value="${rowData.deliveryaddress || ''}"
                               required>
                    </td>

                    <td class="text-center">
                        <div class="d-flex gap-1 justify-content-center">

                            <button type="button"
                                    class="btn btn-outline-primary btn-sm"
                                    onclick="copyDeliveryRow(this)"
                                    title="Copy Row">
                                <i class="fa fa-copy"></i>
                            </button>

                            <button type="button"
                                    class="btn btn-sm btn-outline-danger rmdeliveryaddbtn">
                                <i class="fa fa-trash"></i>
                            </button>

                        </div>
                    </td>

                </tr>
            `);

            $("#deliveryaddresstbl tbody").append($newRow);

            $newRow
                .find(".delivery_product_select")
                .select2({
                    width: '100%'
                });
        });

        $("#clientDeliveryHistoryModal").modal("hide");

        iziToast.success({
            title: 'Success',
            message: selected.length + ' addresses added.'
        });

    });

    function getStep2ProductsAsOptions() {
        let options = '<option value="">Select Product</option>';
        options += '<option value="all" class="fw-bold text-primary">-- All Products (Entire Order) --</option>';
        $("#provisionorderitemsTable tbody tr").each(function() {
            const $select = $(this).find(".productname");
            const productId = $select.val();
            const productName = $select.find("option:selected").text();
            if (productId) {
                options += `<option value="${productId}">${productName}</option>`;
            }
        });
        return options;
    }

    function refreshProductDropdownsInDelivery() {
        const options = getStep2ProductsAsOptions();
        $(".delivery_product_select").each(function() {
            const currentVal = $(this).val();
            $(this).html(options).val(currentVal);
        });
    }

    // Auto-fill default delivery address from client details
    window.autoFillDefaultDeliveryAddress = function() {
        if ($("#deliveryaddresstbl tbody tr").length > 0) {
            return;
        }
        const productOptions = typeof getStep2ProductsAsOptions === 'function' ? getStep2ProductsAsOptions() : '<option value="all">All Products</option>';
        
        // Sum total quantity from Step 2
        let totalQty = 0;
        $("#provisionorderitemsTable tbody tr").each(function() {
            totalQty += parseFloat($(this).find(".qty_td").val()) || 0;
        });

        let cStateText = $("#statename").is("select") ? ($("#statename option:selected").text().replace(/--.*--|Select.*/i, '').trim()) : ($("#statename").val() || '');
        let cState = cStateText || (window.savedOrderFreightData && window.savedOrderFreightData.client ? window.savedOrderFreightData.client.statename : '') || (window.savedOrderFreightData && window.savedOrderFreightData.header ? window.savedOrderFreightData.header.statename : '') || '';
        let cStateId = $("#stateid").val() || (window.savedOrderFreightData && window.savedOrderFreightData.client ? window.savedOrderFreightData.client.stateid : '') || '';
        let cDistrictText = $("#districtname").is("select") ? ($("#districtname option:selected").text().replace(/--.*--|Select.*/i, '').trim()) : ($("#districtname").val() || '');
        let cDistrict = cDistrictText || (window.savedOrderFreightData && window.savedOrderFreightData.client ? window.savedOrderFreightData.client.districtname : '') || (window.savedOrderFreightData && window.savedOrderFreightData.header ? window.savedOrderFreightData.header.districtname : '') || '';
        let cDistrictId = $("#districtid").val() || (window.savedOrderFreightData && window.savedOrderFreightData.client ? window.savedOrderFreightData.client.districtid : '') || '';
        let cCity = $("#cityname").val() || (window.savedOrderFreightData && window.savedOrderFreightData.client ? window.savedOrderFreightData.client.cityname : '') || '';
        let cAddress = $("#delivery_address").val() || (window.savedOrderFreightData && window.savedOrderFreightData.header ? window.savedOrderFreightData.header.delivery_address : '') || (window.savedOrderFreightData && window.savedOrderFreightData.client ? window.savedOrderFreightData.client.invoicing_address : '') || '';

        if ((!cStateId || !cDistrictId) && typeof findStateDistrictIds === 'function' && Array.isArray(stateDistrictArray) && stateDistrictArray.length > 0) {
            const foundIds = findStateDistrictIds(cState, cDistrict);
            if (!cStateId && foundIds.stateid) cStateId = foundIds.stateid;
            if (!cDistrictId && foundIds.districtid) cDistrictId = foundIds.districtid;
        }

        let savedAttrs = '';
        if (window.savedOrderFreightData && window.savedOrderFreightData.freight) {
            const fr = window.savedOrderFreightData.freight;
            savedAttrs = `
                data-deliverycharges="${fr.freight_amount || 0}"
                data-freightamt="${fr.payable_freightamount || 0}"
                data-vehicleno="${escapeAttr(fr.vehicle_no || '')}"
                data-totaldistance="${fr.distance_km || 0}"
                data-freedistance="0"
                data-payabledistance="${fr.distance_km || 0}"
                data-remark="${escapeAttr(fr.remarks || '')}"
            `;
        }

        const rowId = 'del_row_' + Date.now();
        const $row = $(`
            <tr id="${rowId}" class="align-middle" ${savedAttrs}>
                <td>
                    <select name="delivery_productid[]" class="form-select select2 delivery_product_select" required>
                        ${productOptions}
                    </select>
                </td>
                <td>
                    <input type="text" name="delivery_qty[]" class="form-control delivery_qty_input is-valid" value="${totalQty}" placeholder="0" onKeyPress="return isFloatNumber(event,this);" required />
                    <div class="pending-qty-badge mt-1" style="font-size: 0.75rem; display: none;"></div>
                </td>
                <td>
                    <input type="text" name="delivery_distance[]" class="form-control delivery_distance_input" value="${(window.savedOrderFreightData && window.savedOrderFreightData.freight && window.savedOrderFreightData.freight.distance_km) ? window.savedOrderFreightData.freight.distance_km : '0'}" placeholder="0.0" onKeyPress="return isFloatNumber(event,this);" />
                </td>
                <td class="d-none">
                    <input type="hidden" name="delivery_latlong[]" value="" />
                </td>
                <td>
                    <select class="form-control del_state" name="del_state[]" required>
                        ${typeof getStateOptions === 'function' ? getStateOptions(cStateId, cState) : `<option value="${cStateId}">${cState}</option>`}
                    </select>
                    <input type="hidden" class="delivery_state" name="delivery_state[]" value="${escapeAttr(cState)}">
                    <input type="hidden" class="delivery_stateid" name="delivery_stateid[]" value="${escapeAttr(cStateId)}">
                </td>
                <td>
                    <select class="form-control del_district" name="del_district[]" required>
                        ${typeof getDistrictOptions === 'function' ? getDistrictOptions(cStateId, cDistrictId, cDistrict) : `<option value="${cDistrictId}">${cDistrict}</option>`}
                    </select>
                    <input type="hidden" class="delivery_district" name="delivery_district[]" value="${escapeAttr(cDistrict)}">
                    <input type="hidden" class="delivery_district_id" name="delivery_district_id[]" value="${escapeAttr(cDistrictId)}">
                </td>
                <td>
                    <input type="text" name="delivery_city[]" class="form-control" value="${escapeAttr(cCity)}" placeholder="City/Village" />
                </td>
                <td>
                    <input type="text" name="delivery_pincode[]" class="form-control" value="" placeholder="Pincode" onKeyPress="return isNumber(event);" />
                </td>
                <td>
                    <input type="text" name="delivery_fulladdress[]" class="form-control" value="${escapeAttr(cAddress)}" placeholder="Full Address" required />
                </td>
                <td class="text-center">
                    <div class="d-flex gap-1 justify-content-center">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="copyDeliveryRow(this)" title="Copy Row">
                            <i class="fa fa-copy"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm rmdeliveryaddbtn" onclick="$(this).closest('tr').remove();">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `);
        $("#deliveryaddresstbl tbody").append($row);
        $row.find('.delivery_product_select').val('all').select2({ width: '100%' });

        if (typeof generateFreightGroups === 'function') {
            generateFreightGroups(true);
        }
    };

    // Populate Delivery and Freight for Edit Order
    window.populateDeliveryAndFreightForEdit = function(header, client, items, delivery, freight) {
        window.savedOrderFreightData = {
            header: header || {},
            client: client || {},
            items: items || [],
            delivery: delivery || [],
            freight: freight || null
        };

        const pickup = (header && header.pickupfrom) ? header.pickupfrom : 'pickup';
        $('input[name="pickupfrom"]').prop('checked', false);
        $(`input[name='pickupfrom'][value='${pickup}']`).prop('checked', true);

        // Pre-fill remarks in Step 3 and Step 4
        const orderRemarks = (header && header.remarks) ? header.remarks : "";
        $("#delivery_remarks").val(orderRemarks);
        $("#remarks").val(orderRemarks);
        $("textarea[name='remarks']").val(orderRemarks);

        // Step 3 Delivery / Box Packing
        if (pickup === 'delivered') {
            $("#deliveryboxdiv").addClass("d-none");
            $("#deliveryaddressdiv").show();
            $("#btnSkipDeliveryStep").addClass("d-none");
            $("#deliveryaddresstbl tbody").empty();

            if (delivery && delivery.length > 0) {
                delivery.forEach(function (loc) {
                    const productOptions = typeof getStep2ProductsAsOptions === 'function' ? getStep2ProductsAsOptions() : '<option value="all">All Products</option>';
                    const stateid = loc.stateid || loc.dest_stateid || '';
                    const districtid = loc.districtid || loc.dest_districtid || '';
                    const statename = loc.dest_state || '';
                    const districtname = loc.dest_district || '';
                    const cityname = loc.dest_city || '';
                    const pincode = loc.dest_pincode || '';
                    const fulladdress = loc.deliveryaddress || '';
                    const distance = parseFloat(loc.totaldistance || loc.distance || 0) || 0;
                    const latlong = (loc.dest_latitude && loc.dest_longitude) ? (loc.dest_latitude + ',' + loc.dest_longitude) : (loc.gpslocation || '');
                    const rowId = 'del_row_' + Date.now() + '_' + Math.floor(Math.random() * 1000);

                    const $delRow = $(`
                        <tr id="${rowId}" class="align-middle"
                            data-deliverycharges="${loc.deliverycharges || 0}"
                            data-freightamt="${loc.freightamt || 0}"
                            data-vehicleno="${escapeAttr(loc.vehicleno || '')}"
                            data-totaldistance="${loc.totaldistance || distance}"
                            data-freedistance="${loc.freedistance || 0}"
                            data-payabledistance="${loc.distance || 0}"
                            data-remark="${escapeAttr(loc.remark || '')}">
                            <td>
                                <select name="delivery_productid[]" class="form-select select2 delivery_product_select" required>
                                    ${productOptions}
                                </select>
                            </td>
                            <td>
                                <input type="text" name="delivery_qty[]" class="form-control delivery_qty_input is-valid" value="${loc.deliveryqty || loc.delivery_qty || 0}" placeholder="0" onKeyPress="return isFloatNumber(event,this);" required />
                                <div class="pending-qty-badge mt-1" style="font-size: 0.75rem; display: none;"></div>
                            </td>
                            <td>
                                <input type="text" name="delivery_distance[]" class="form-control delivery_distance_input" value="${distance}" placeholder="0.0" onKeyPress="return isFloatNumber(event,this);" />
                            </td>
                            <td class="d-none">
                                <input type="hidden" name="delivery_latlong[]" value="${escapeAttr(latlong)}" />
                            </td>
                            <td>
                                <select class="form-control del_state" name="del_state[]" required>
                                    ${typeof getStateOptions === 'function' ? getStateOptions(stateid, statename) : `<option value="${stateid}" selected>${statename}</option>`}
                                </select>
                                <input type="hidden" class="delivery_state" name="delivery_state[]" value="${escapeAttr(statename)}">
                                <input type="hidden" class="delivery_stateid" name="delivery_stateid[]" value="${escapeAttr(stateid)}">
                            </td>
                            <td>
                                <select class="form-control del_district" name="del_district[]" required>
                                    ${typeof getDistrictOptions === 'function' ? getDistrictOptions(stateid, districtid, districtname) : `<option value="${districtid}" selected>${districtname}</option>`}
                                </select>
                                <input type="hidden" class="delivery_district" name="delivery_district[]" value="${escapeAttr(districtname)}">
                                <input type="hidden" class="delivery_district_id" name="delivery_district_id[]" value="${escapeAttr(districtid)}">
                            </td>
                            <td>
                                <input type="text" name="delivery_city[]" class="form-control" value="${escapeAttr(cityname)}" placeholder="City/Village">
                            </td>
                            <td>
                                <input type="text" name="delivery_pincode[]" class="form-control" value="${escapeAttr(pincode)}" placeholder="Pincode" onKeyPress="return isNumber(event);">
                            </td>
                            <td>
                                <input type="text" name="delivery_fulladdress[]" class="form-control" value="${escapeAttr(fulladdress)}" placeholder="Full Address" required>
                            </td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="copyDeliveryRow(this)" title="Copy Row">
                                        <i class="fa fa-copy"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger rmdeliveryaddbtn" onclick="$(this).closest('tr').remove();">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `);
                    $("#deliveryaddresstbl tbody").append($delRow);
                    const selProd = String(loc.productid || loc.addressforproduct || 'all');
                    const $pSel = $delRow.find('.delivery_product_select');
                    if ($pSel.find(`option[value="${selProd}"]`).length === 0 && selProd !== 'all') {
                        $pSel.append(new Option(selProd, selProd, true, true));
                    }
                    $pSel.val(selProd).select2({ width: '100%' });
                });
            } else {
                if (typeof window.autoFillDefaultDeliveryAddress === 'function') {
                    window.autoFillDefaultDeliveryAddress();
                }
            }
        } else if (pickup === 'boxpacking') {
            $("#deliveryaddressdiv").hide();
            $("#deliveryboxdiv").removeClass("d-none");
            $("#btnSkipDeliveryStep").addClass("d-none");
            if (typeof fillboxdata === 'function') {
                fillboxdata().then(function () {
                    if (delivery && delivery.length > 0) {
                        $("#deliveryboxtbl tbody tr").each(function (idx) {
                            if (delivery[idx]) {
                                const b = delivery[idx];
                                const $tr = $(this);
                                if (b.stateid) {
                                    $tr.find(".state_box").val(b.stateid).trigger("change");
                                    $tr.find(".box_state_id").val(b.stateid);
                                    $tr.find(".box_state_name").val(b.dest_state || "");
                                }
                                if (b.districtid) {
                                    setTimeout(function() {
                                        $tr.find(".district_box").val(b.districtid).trigger("change");
                                        $tr.find(".box_district_id").val(b.districtid);
                                        $tr.find(".box_district_name").val(b.dest_district || "");
                                        if (b.cityid) {
                                            setTimeout(function() {
                                                $tr.find(".city_box").val(b.cityid).trigger("change");
                                                $tr.find(".box_city_id").val(b.cityid);
                                                $tr.find(".box_city_name").val(b.dest_city || "");
                                                if (b.pickuppoint) {
                                                    setTimeout(function() {
                                                        $tr.find(".pickuppoint").val(b.pickuppoint);
                                                    }, 100);
                                                }
                                            }, 100);
                                        }
                                    }, 100);
                                }
                                if (b.noofbox) $tr.find(".no_of_box").val(b.noofbox);
                                if (b.boxperpack) $tr.find(".plants_per_box").val(b.boxperpack);
                                if (b.chargesperbox || b.boxchargesperpack) $tr.find(".freighcharges").val(b.chargesperbox || b.boxchargesperpack);
                                if (b.freightamt || b.boxtotalamt) $tr.find(".freightamount").val(b.freightamt || b.boxtotalamt);
                            }
                        });
                    }
                });
            }
        } else {
            // pickup
            $("#deliveryaddressdiv").fadeOut();
            $("#deliveryboxdiv").addClass("d-none");
            $("#btnSkipDeliveryStep").removeClass("d-none");
        }

        // Generate Step 4 Freight Groups
        if (typeof generateFreightGroups === 'function') {
            generateFreightGroups(true);
        }
    };

    // Keep Remarks in Step 03 and Step 04 synchronized
    $(document).on("input change", ".order-remarks-sync", function() {
        const val = $(this).val();
        $(".order-remarks-sync").not(this).val(val);
        $("textarea[name='remarks']").val(val);
    });

    // Real-time validation and Auto-fill for Delivery Quantities
    $(document).on("input change", ".delivery_qty_input, .delivery_product_select", function(e) {
        const $row = $(this).closest("tr");
        const productId = $row.find(".delivery_product_select").val();
        
        // Auto-fill Delivery Qty with PENDING qty when a specific product is selected
        if (e.type === 'change' && $(this).hasClass('delivery_product_select') && productId && productId !== 'all') {
            // 1. Total Ordered
            let totalOrderedQty = 0;
            $("#provisionorderitemsTable tbody tr").each(function() {
                if ($(this).find(".productname").val() === productId) {
                    totalOrderedQty += parseFloat($(this).find(".qtycal").val()) || 0;
                }
            });

            // 2. Already Allocated in OTHER rows
            let alreadyAllocated = 0;
            $("#deliveryaddresstbl tbody tr").not($row).each(function() {
                if ($(this).find(".delivery_product_select").val() === productId) {
                    alreadyAllocated += parseFloat($(this).find(".delivery_qty_input").val()) || 0;
                }
            });

            const remainingQty = Number((totalOrderedQty - alreadyAllocated).toFixed(3));
            
            // Only auto-fill if the input is currently empty or 0
            const currentQty = parseFloat($row.find(".delivery_qty_input").val()) || 0;
            if (currentQty === 0 && remainingQty > 0) {
                $row.find(".delivery_qty_input").val(remainingQty).trigger('input');
            }
        }

        if (productId === 'all') {
            handleAllProductsSelection($row);
            return;
        }

        if (!productId) return;
        validateProductQuantities(productId);
    });

    function handleAllProductsSelection($currentRow) {
        // 1. Get address details from current row

      const district = $currentRow.find("input[name='delivery_district[]']").val() || '';
      const districtid = $currentRow.find(".delivery_district_id").val() || '';

      const state = $currentRow.find("input[name='delivery_state[]']").val() || '';
      const stateid = $currentRow.find(".delivery_stateid").val() || ''; 
      const city = $currentRow.find("input[name='delivery_city[]']").val() || '';   
        
       
        const pincode = $currentRow.find("input[name='delivery_pincode[]']").val() || '';
        const address = $currentRow.find("input[name='delivery_fulladdress[]']").val() || ''; 
        const distance = $currentRow.find("input[name='delivery_distance[]']").val() || '0';
        const latlong = $currentRow.find("input[name='delivery_latlong[]']").val() || '';

        // 2. Get all products from Step 2
        const items = [];
        $("#provisionorderitemsTable tbody tr").each(function() {
            const id = $(this).find(".productname").val();
            const qty = parseFloat($(this).find(".qtycal").val()) || 0;
            if (id && qty > 0) {
                items.push({ id, qty });
            }
        });

        if (items.length === 0) {
            iziToast.warning({ title: 'Empty Order', message: 'No products found in Step 2.' });
            return;
        }

        // 3. Confirm with user
        Swal.fire({
            title: 'Ship All Products?',
            text: `This will allocate all ${items.length} items to this address.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#6366f1',
            confirmButtonText: 'Yes, Apply All'
        }).then((result) => {
            if (result.isConfirmed) {
                const productOptions = getStep2ProductsAsOptions();

                // Add rows for each product
                items.forEach(item => {
                    const $newRow = $(`
                        <tr class="align-middle">
                            <td>
                                <select name="delivery_productid[]" class="form-select select2 delivery_product_select" required>
                                    ${productOptions}
                                </select>
                            </td>
                            <td>
                                <input type="text" name="delivery_qty[]" class="form-control delivery_qty_input is-valid" value="${item.qty}" placeholder="0" onKeyPress="return isFloatNumber(event,this);" required />
                                <div class="pending-qty-badge mt-1" style="font-size: 0.75rem; display: none;"></div>
                            </td>
                            <td>
                                <input type="text" name="delivery_distance[]" class="form-control delivery_distance_input" value="${distance}" onKeyPress="return isFloatNumber(event,this);" />
                            </td>
                            <td class="d-none">
                                <input type="hidden" name="delivery_latlong[]" value="${latlong}" />
                            </td>

                            <td>
                              <select
                                  class="form-control del_state"
                                  name="del_state[]"
                                    ${getStateOptions(stateid)}
                                  required
                              > 
                              </select>
                                 <input type="hidden" class="delivery_state" name="delivery_state[]" value="">
                                 <input type="hidden" class="delivery_stateid" name="delivery_stateid" value=""> 
                            </td>
                            <td>
                              <select
                                  class="form-control del_district"
                                  name="del_district[]"
                                   ${getDistrictOptions(stateid, districtid)}
                                  required
                              > 
                              </select>
                                 <input type="hidden" class="delivery_district" name="delivery_district[]" value="">
                                 <input type="hidden" class="delivery_district_id" name="delivery_district_id" value="">  
                           </td>
                           


 
                            <td><input type="text" name="delivery_city[]" class="form-control" value="${city}" placeholder="City/Village" /></td>
                            <td><input type="text" name="delivery_pincode[]" class="form-control" value="${pincode}" placeholder="Pincode" /></td>
                            <td><input type="text" name="delivery_fulladdress[]" class="form-control" value="${address}" placeholder="Full Address" required /></td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="copyDeliveryRow(this)" title="Copy Row">
                                        <i class="fa fa-copy"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger rmdeliveryaddbtn">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `);
                    $("#deliveryaddresstbl tbody").append($newRow);
                    
                    // Set value and init select2
                    $newRow.find(".delivery_product_select").val(item.id).select2({ width: '100%' });
                });

                // 4. Remove the original "All Products" placeholder row
                $currentRow.remove();
                iziToast.success({ title: 'Applied', message: 'All items allocated to this address.' });
            } else {
                // Reset select if cancelled
                $currentRow.find(".delivery_product_select").val('');
            }
        });
    }

    window.copyDeliveryRow = function(btn) {
        const $row = $(btn).closest("tr");
        
        // Clone the row
        const $newRow = $row.clone();
        
        // 1. Clean up Select2 artifacts from the new row
        $newRow.find('.select2-container').remove();
        $newRow.find('select').removeClass('select2-hidden-accessible').removeAttr('data-select2-id').removeAttr('aria-hidden').removeAttr('tabindex');
        
        // 2. Reset Product and Quantity for the new row
        $newRow.find(".delivery_product_select").val("");
        $newRow.find(".delivery_qty_input").val("");
        $newRow.find(".is-valid").removeClass("is-valid");
        $newRow.find(".pending-qty-badge").empty().hide();

        // 3. Append after current row
        $row.after($newRow);
        
        // 4. Initialize select2 for the new row
        $newRow.find(".delivery_product_select").select2({ width: '100%' });
        
        iziToast.info({ title: 'Copied', message: 'Row address details copied. Please select a product.', position: 'bottomRight' });
    }

    $(document).on("change", ".delivery_product_select, input[name='delivery_fulladdress[]']", function() {
        const $thisRow = $(this).closest("tr");
        const productId = $thisRow.find(".delivery_product_select").val();
        const address = $thisRow.find("input[name='delivery_fulladdress[]']").val();

        if (!productId || productId === 'all' || !address) return;

        let $match = null;
        $("#deliveryaddresstbl tbody tr").not($thisRow).each(function() {
            const p = $(this).find(".delivery_product_select").val();
            const a = $(this).find("input[name='delivery_fulladdress[]']").val();
            if (p === productId && a === address) {
                $match = $(this);
                return false;
            }
        });

        if ($match) {
            Swal.fire({
                title: 'Duplicate Entry!',
                text: 'This product is already mentioned with the same address. Please manage the order quantity in the existing highlighted row.',
                icon: 'warning',
                timer: 5000,
                timerProgressBar: true
            });

            // Highlight matching row for 5 seconds
            $match.addClass("table-warning");
            $match.css("transition", "background-color 0.5s ease");
            
            setTimeout(() => {
                $match.removeClass("table-warning");
            }, 5000);

            // Optional: reset current select to prevent duplicate
            $thisRow.find(".delivery_product_select").val("").trigger("change.select2");
        }
    });

    function validateProductQuantities(productId) {
        if (!productId || productId === 'all') return;

        // 1. Get total ordered qty from Step 2
        let totalOrderedQty = 0;
        $("#provisionorderitemsTable tbody tr").each(function() {
            if ($(this).find(".productname").val() === productId) {
                totalOrderedQty += parseFloat($(this).find(".qtycal").val()) || 0;
            }
        });

        // 2. Get total allocated qty in Step 3
        let totalAllocatedQty = 0;
        $("#deliveryaddresstbl tbody tr").each(function() {
            if ($(this).find(".delivery_product_select").val() === productId) {
                totalAllocatedQty += parseFloat($(this).find(".delivery_qty_input").val()) || 0;
            }
        });

        const pendingQty = Number((totalOrderedQty - totalAllocatedQty).toFixed(3));

        // 3. Update UI for all rows with this product
        $("#deliveryaddresstbl tbody tr").each(function() {
            if ($(this).find(".delivery_product_select").val() === productId) {
                const $input = $(this).find(".delivery_qty_input");
                const $badge = $(this).find(".pending-qty-badge");

                if (pendingQty > 0) {
                    $badge.html(`<span class="text-primary fw-bold"><i class="fa fa-info-circle"></i> Pending: ${pendingQty}</span>`).show();
                    $input.removeClass("is-invalid is-valid");
                } else if (pendingQty === 0) {
                    $badge.html(`<span class="text-success fw-bold"><i class="fa fa-check-circle"></i> Fully Assigned</span>`).show();
                    $input.addClass("is-valid").removeClass("is-invalid");
                } else {
                    $badge.html(`<span class="text-danger fw-bold"><i class="fa fa-exclamation-triangle"></i> Excess: ${Math.abs(pendingQty)}</span>`).show();
                    $input.addClass("is-invalid").removeClass("is-valid");
                }
            }
        });

        if (totalAllocatedQty > totalOrderedQty) {
            iziToast.destroy(); // Clear existing to avoid spam
            iziToast.error({ 
                title: 'Qty Overflow', 
                message: `Allocated (${totalAllocatedQty}) exceeds ordered (${totalOrderedQty}) for this product.`,
                position: 'bottomRight'
            });
        }
    }

    function getDaysDifference(d1, d2) {
        if (!d1 || !d2 || d1 === 'N/A' || d2 === 'N/A') return 999;
        try {
            const date1 = new Date(d1);
            const date2 = new Date(d2);
            if (isNaN(date1.getTime()) || isNaN(date2.getTime())) return 999;
            const diffTime = Math.abs(date2.getTime() - date1.getTime());
            return Math.floor(diffTime / (1000 * 3600 * 24));
        } catch(e) {
            return 999;
        }
    }

    function checkAndPromptConsolidatedDelivery(groups) {
        const groupKeys = Object.keys(groups);
        if (groupKeys.length < 2) return false;

        let candidates = [];
        for (let i = 0; i < groupKeys.length; i++) {
            for (let j = i + 1; j < groupKeys.length; j++) {
                const g1 = groups[groupKeys[i]];
                const g2 = groups[groupKeys[j]];
                
                const daysDiff = getDaysDifference(g1.date, g2.date);
                const kmDiff = Math.abs((parseFloat(g1.distance) || 0) - (parseFloat(g2.distance) || 0));

                if (daysDiff < 10 && kmDiff < 15) {
                    if (!candidates.includes(groupKeys[i])) candidates.push(groupKeys[i]);
                    if (!candidates.includes(groupKeys[j])) candidates.push(groupKeys[j]);
                }
            }
        }

        if (candidates.length < 2) return false;

        // Unique dates from candidate groups
        const candidateDates = [...new Set(candidates.map(k => groups[k].date))].filter(d => d && d !== 'N/A');
        if (candidateDates.length < 2) return false;

        // Build option list for date selection
        let dateOptionsHtml = '';
        candidateDates.forEach(d => {
            let formatted = d;
            try {
                let parts = d.split('-');
                if (parts.length === 3) formatted = `${parts[2]}-${parts[1]}-${parts[0]}`;
            } catch(e) {}
            dateOptionsHtml += `<option value="${d}">${formatted} (Existing Date)</option>`;
        });
        dateOptionsHtml += `<option value="manual">Custom / Manual Date...</option>`;

        Swal.fire({
            title: 'Consolidate Delivery Dates?',
            html: `
                <div class="text-start">
                    <p class="mb-2">Multiple delivery groups detected with delivery date difference <strong>&lt; 10 days</strong> (${candidateDates.join(', ')}) and distance difference <strong>&lt; 15 KM</strong>.</p>
                    <p class="fw-bold text-primary mb-0">Can all products be delivered once on a single date to save freight charge?</p>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Consolidate Delivery',
            cancelButtonText: 'No, Keep Separate',
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#6b7280'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Select Delivery Date',
                    html: `
                        <div class="text-start p-2">
                            <label class="form-label fw-bold mb-2">Select single delivery date:</label>
                            <select id="swal_consolidated_date_select" class="form-select mb-3">
                                ${dateOptionsHtml}
                            </select>
                            <div id="swal_manual_date_wrapper" style="display:none;">
                                <label class="form-label fw-bold mb-1">Enter Manual Date:</label>
                                <input type="date" id="swal_manual_date_input" class="form-control" />
                            </div>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Update Date & Recalculate Freight',
                    cancelButtonText: 'Cancel',
                    didOpen: () => {
                        $('#swal_consolidated_date_select').on('change', function() {
                            if ($(this).val() === 'manual') {
                                $('#swal_manual_date_wrapper').slideDown();
                            } else {
                                $('#swal_manual_date_wrapper').slideUp();
                            }
                        });
                    },
                    preConfirm: () => {
                        const sel = $('#swal_consolidated_date_select').val();
                        let finalDate = sel;
                        if (sel === 'manual') {
                            finalDate = $('#swal_manual_date_input').val();
                            if (!finalDate) {
                                Swal.showValidationMessage('Please select a valid manual date.');
                                return false;
                            }
                        }
                        return finalDate;
                    }
                }).then((dateResult) => {
                    if (dateResult.isConfirmed && dateResult.value) {
                        const chosenDate = dateResult.value;
                        
                        // Update delivery date for all items in Step 2 table
                        $("#provisionorderitemsTable tbody tr").each(function() {
                            $(this).find(".expecteddelivery").val(chosenDate);
                        });

                        iziToast.success({
                            title: 'Delivery Consolidated',
                            message: `Delivery date updated to ${chosenDate}. Freight recalculated for 1-time delivery!`,
                            position: 'topRight'
                        });

                        // Re-generate freight groups with skipPrompt = true
                        generateFreightGroups(true);
                    }
                });
            }
        });

        return true;
    }

    window.getOrSetRowKey = function($row) {
        if (!$row || !$row.length) return '';
        let key = $row.attr('data-row-key');
        if (!key) {
            let itemId = $row.find('.order_item_id').val() || $row.attr('data-item-id') || '';
            key = 'item_row_' + (itemId ? itemId + '_' : '') + Date.now() + '_' + Math.random().toString(36).substr(2, 6);
            $row.attr('data-row-key', key);
        }
        return key;
    };

    window.syncDeliveryTablesWithStep2 = function(removedRowKey = null, removedProdId = null, removedItemId = null) {
        // 1. If explicit removed rowKey is provided, delete matching row immediately from #deliveryboxtbl
        if (removedRowKey) {
            $("#deliveryboxtbl tbody tr.box-delivery-row[data-row-key='" + removedRowKey + "']").remove();
        }

        // 2. Collect all active items from Step 2 (#provisionorderitemsTable)
        const activeRowKeys = new Set();
        const activeProdIds = new Set();
        const activeRowsData = [];

        $("#provisionorderitemsTable tbody tr").each(function () {
            const $r = $(this);
            const k = getOrSetRowKey($r);
            const pid = $r.find(".productname").val();
            const pname = $r.find(".productname option:selected").text().trim();
            const qty = parseFloat($r.find(".qty_td").val()) || 0;
            const ddate = $r.find(".expecteddelivery").val() || "";

            if (pid) {
                activeRowKeys.add(k);
                activeProdIds.add(String(pid));
                activeRowsData.push({
                    rowKey: k,
                    productId: pid,
                    productName: pname,
                    qty: qty,
                    deliveryDate: ddate,
                    $sourceRow: $r
                });
            }
        });

        // 3. For Delivered Mode (#deliveryaddresstbl):
        // If a product is no longer present in Step 2, remove rows assigned to it
        if ($("#deliveryaddresstbl tbody tr").length > 0) {
            $("#deliveryaddresstbl tbody tr").each(function () {
                const $dRow = $(this);
                const p = $dRow.find(".delivery_product_select").val();
                if (p && p !== 'all' && !activeProdIds.has(String(p))) {
                    $dRow.remove();
                }
            });
            if (typeof refreshProductDropdownsInDelivery === 'function') {
                refreshProductDropdownsInDelivery();
            }
        }

        // 4. For Box Packing Mode (#deliveryboxtbl):
        // Remove any row whose data-row-key is no longer in activeRowKeys (or matching removed product)
        $("#deliveryboxtbl tbody tr.box-delivery-row").each(function () {
            const $bRow = $(this);
            const bKey = $bRow.attr("data-row-key");
            const bPid = String($bRow.attr("data-productid") || $bRow.find(".box_productid").val() || "");

            if (bKey) {
                if (!activeRowKeys.has(bKey)) {
                    $bRow.remove();
                }
            } else if (removedRowKey || (removedProdId && bPid === String(removedProdId))) {
                $bRow.remove();
            } else if (bPid && !activeProdIds.has(bPid)) {
                $bRow.remove();
            }
        });

        // 5. Update remaining box delivery rows with current qty and delivery date from Step 2
        activeRowsData.forEach(function (item) {
            let $bRow = $("#deliveryboxtbl tbody tr.box-delivery-row[data-row-key='" + item.rowKey + "']");
            if (!$bRow.length && item.productId) {
                // If not found by rowKey, fallback search by productId if only 1 exists
                const $matches = $("#deliveryboxtbl tbody tr.box-delivery-row[data-productid='" + item.productId + "']");
                if ($matches.length === 1 && !$matches.attr("data-row-key")) {
                    $bRow = $matches;
                    $bRow.attr("data-row-key", item.rowKey);
                }
            }

            if ($bRow.length > 0) {
                // Update product name
                if (item.productName) {
                    $bRow.find("td:eq(1) strong").text(item.productName);
                    $bRow.find(".box_productname").val(item.productName);
                }
                // Update expected delivery date
                $bRow.find(".boxdate").text(item.deliveryDate);

                // Update quantity and box counts
                const oldQty = parseFloat($bRow.attr("data-productqty")) || 0;
                if (oldQty !== item.qty || $bRow.find(".product_qty").text().trim() != item.qty.toString()) {
                    $bRow.attr("data-productqty", item.qty);
                    $bRow.find(".box_product_qty").val(item.qty);
                    $bRow.find(".product_qty").html(`${item.qty} <input type="hidden" class="box_product_qty" name="box_product_qty[]" value="${escapeHtml(item.qty)}">`);

                    const ppb = parseInt($bRow.find(".plants_per_box").val()) || 500;
                    const newBoxes = (item.qty > 0 && ppb > 0) ? Math.ceil(item.qty / ppb) : 0;
                    $bRow.find(".no_of_box").val(newBoxes);
                    const charge = parseFloat($bRow.find(".freighcharges").val()) || 0;
                    $bRow.find(".freightamount").val((newBoxes * charge).toFixed(2));
                }
            }
        });

        // 6. If Box Packing table has existing rows, check if newly added Step 2 items need box rows
        if ($("#deliveryboxtbl tbody tr.box-delivery-row").length > 0) {
            const stateOptions = (typeof getBoxStateOptions === 'function') ? getBoxStateOptions() : '';
            activeRowsData.forEach(function (item) {
                const $existing = $("#deliveryboxtbl tbody tr.box-delivery-row[data-row-key='" + item.rowKey + "']");
                if ($existing.length === 0 && item.productId) {
                    const newRowId = "boxrow_" + Date.now() + "_" + Math.random().toString(36).substr(2, 5);
                    const newBoxes = (item.qty > 0) ? Math.ceil(item.qty / 500) : 0;
                    const newRowHtml = `
                        <tr id="${newRowId}" class="box-delivery-row" data-row-key="${escapeHtml(item.rowKey)}" data-productid="${escapeHtml(item.productId)}" data-productqty="${item.qty}">
                            <td class="text-center">0</td>
                            <td>
                                <strong>${escapeHtml(item.productName)}</strong>
                                <input type="hidden" class="box_productname" name="box_productname[]" value="${escapeHtml(item.productName)}">
                                <input type="hidden" class="box_productid" name="box_productid[]" value="${escapeHtml(item.productId)}">
                            </td>
                            <td>
                                <strong class="boxdate">${escapeHtml(item.deliveryDate)}</strong>
                            </td>
                            <td class="text-center product_qty">
                                ${item.qty}
                                <input type="hidden" class="box_product_qty" name="box_product_qty[]" value="${escapeHtml(item.qty)}">
                            </td>
                            <td>
                                <select class="form-control state_box" name="state_box[]" required>
                                    ${stateOptions}
                                </select>
                                <input type="hidden" class="box_state_name" name="box_state_name[]" value="">
                                <input type="hidden" class="box_state_id" name="box_state_id[]" value="">
                            </td>
                            <td>
                                <select class="form-control district_box" name="district_box[]" required disabled>
                                    <option value="">-- Select District --</option>
                                </select>
                                <input type="hidden" class="box_district_name" name="box_district_name[]" value="">
                                <input type="hidden" class="box_district_id" name="box_district_id[]" value="">
                            </td>
                            <td>
                                <select class="form-control city_box" name="city_box[]" required disabled>
                                    <option value="">-- Select Location --</option>
                                </select>
                                <input type="hidden" class="box_city_name" name="box_city_name[]" value="">
                                <input type="hidden" class="box_city_id" name="box_city_id[]" value="">
                            </td>
                            <td class="text-center">
                                <input type="number" class="form-control plants_per_box" name="plants_per_box[]" value="500" min="1" step="1" readonly>
                            </td>
                            <td>
                                <input type="number" class="form-control no_of_box" name="no_of_box[]" value="${newBoxes}" min="0" step="1">
                            </td>
                            <td>
                                <select class="form-control pickuppoint" name="pickuppoint[]" required disabled>
                                    <option value="">-- Select Location First --</option>
                                </select>
                            </td>
                            <td>
                                <input type="number" class="form-control freighcharges" name="box_freighcharges[]" value="0.00" min="0" step="0.01" inputmode="decimal" readonly>
                            </td>
                            <td>
                                <input type="number" class="form-control freightamount" name="box_freightamount[]" value="0.00" min="0" step="0.01" readonly>
                            </td>
                            <td class="text-center">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input apply_box_all" style="width:18px; height:18px; cursor:pointer;">
                                </div>
                            </td>
                        </tr>
                    `;
                    $("#deliveryboxtbl tbody").append(newRowHtml);
                }
            });
        }

        // 7. Renumber S.No in #deliveryboxtbl
        let boxRowCount = 0;
        $("#deliveryboxtbl tbody tr.box-delivery-row").each(function () {
            boxRowCount++;
            $(this).find("td:first").text(boxRowCount);
        });

        if (boxRowCount === 0 && $("#deliveryboxtbl tbody tr").length === 0) {
            $("#deliveryboxtbl tbody").html(`
                <tr>
                    <td colspan="13" class="text-center text-muted">No product found.</td>
                </tr>
            `);
        }

        // 8. Re-generate freight groups so Step 4 is immediately updated
        if (typeof generateFreightGroups === 'function') {
            generateFreightGroups(true);
        }
    };

    function generateFreightGroups(skipPrompt = false) {
      var pickupfrom = $('input[name="pickupfrom"]:checked').val() || $("#orderDeliveryFreightForm input[name='pickupfrom']:checked").val() || 'delivered';
        const $tbody = $("#freightGroupTable tbody");
        $tbody.empty();

        if (pickupfrom === 'boxpacking'){ // ajay ak
          $("#freeFreightNotification").empty().hide();
          $("#spanratedbl").html("").addClass("d-none");
          const groups = {};
          /* =========================================================
             BOX PACKING -> FREIGHT GROUP TABLE
             Source: #deliveryboxtbl
          ========================================================= */

          $("#deliveryboxtbl tbody tr.box-delivery-row").each(function () {
              const $row = $(this);
              // -----------------------------------------------------
              // Product
              // -----------------------------------------------------
              const productId =  $row.find(".box_productid").val() || "";
              const productName = $row.find(".box_productname").val() || "";
              const qty =  parseFloat(
                      $row.find(".box_product_qty").val()
                  ) || 0;
 
              // -----------------------------------------------------
              const deliveryDate = $row.find(".boxdate").text().trim();
              // -----------------------------------------------------
              // State
              // -----------------------------------------------------
              const stateId = $row.find(".state_box").val() || "";

              const stateName =  $row.find(".state_box option:selected").text().trim();


              // -----------------------------------------------------
              // District
              // -----------------------------------------------------
              const districtId =
                  $row.find(".district_box").val() || "";

              const districtName =
                  $row.find(".district_box option:selected")
                      .text()
                      .trim();


              // -----------------------------------------------------
              // City / Village
              // -----------------------------------------------------
              const locationId =
                  $row.find(".city_box").val() || "";

              const locationName =
                  $row.find(".city_box option:selected")
                      .text()
                      .trim();


              // -----------------------------------------------------
              // Pickup Point
              // -----------------------------------------------------
              const pickupPoint =
                  $row.find(".pickuppoint").val() || "";


              // -----------------------------------------------------
              // Box Detail
              // -----------------------------------------------------
              const plantsPerBox =
                  parseFloat(
                      $row.find(".plants_per_box").val()
                  ) || 0;

              const noOfBoxes =
                  parseFloat(
                      $row.find(".no_of_box").val()
                  ) || 0;


              // -----------------------------------------------------
              // Freight Detail
              // -----------------------------------------------------
              const freightCharge =
                  parseFloat(
                      $row.find(".freighcharges").val()
                  ) || 0;

              const freightAmount =
                  parseFloat(
                      $row.find(".freightamount").val()
                  ) || 0;


              // -----------------------------------------------------
              // Group Key
              //
              // Same Pickup + State + District + City + Date
              // will become one freight row.
              // -----------------------------------------------------
              const groupKey = [
                  pickupPoint,
                  stateId,
                  districtId,
                  locationId,
                  deliveryDate,
                  freightCharge
              ].join("|");


              if (!groups[groupKey]) {

                  groups[groupKey] = {

                      pickupPoint: pickupPoint,

                      stateId: stateId,
                      stateName: stateName,

                      districtId: districtId,
                      districtName: districtName,

                      locationId: locationId,
                      locationName: locationName,

                      date: deliveryDate,

                      plantsPerBox: plantsPerBox,

                      freightCharge: freightCharge,

                      freightAmount: 0,

                      noOfBoxes: 0,

                      distance: 0,

                      items: []
                  };
              }


              // -----------------------------------------------------
              // Group Totals
              // -----------------------------------------------------
              groups[groupKey].freightAmount += freightAmount;

              groups[groupKey].noOfBoxes += noOfBoxes;


              // -----------------------------------------------------
              // Product Detail
              // -----------------------------------------------------
              groups[groupKey].items.push({

                  productId: productId,

                  name: productName,

                  qty: qty,

                  noOfBoxes: noOfBoxes
              });

          });

          let groupIndex = 0;

          const projectid = '<?php echo $projectid; ?>';
          const rooturl   = '<?php echo $rooturl; ?>';


          for (const key in groups) {

              const g = groups[key];


              // -----------------------------------------------------
              // Total Product Qty
              // -----------------------------------------------------
              const totalGroupQty =
                  g.items.reduce(
                      (sum, item) =>
                          sum + (parseFloat(item.qty) || 0),
                      0
                  );


              // -----------------------------------------------------
              // Freight already calculated in deliveryboxtbl
              // -----------------------------------------------------
              const freightAmount =
                  parseFloat(g.freightAmount) || 0;


              // -----------------------------------------------------
              // No map/distance logic for Box Packing
              // -----------------------------------------------------
              const distance = 0;


              const loginRoleId = parseInt($('body').data('loginroleid') || $('#hidden_loginroleid').val() || '<?php echo (int)($loginroleid ?? ($_SESSION['loginroleid'] ?? 0)); ?>') || 0;
              const isDiscReadOnly = (loginRoleId > 3);

              const html = `

                  <tr
                      class="group-row"
                      data-totalqty="${totalGroupQty}"
                  >

                      <!-- =========================================
                           ADDRESS
                           Pickup Point + State + District + City
                      ========================================== -->
                      <td>

                          <div class="d-flex align-items-start gap-2">

                              <i class="fa fa-map-marker-alt text-primary mt-1"></i>

                              <div>

                                  <!-- PICKUP POINT -->
                                  <div class="fw-bold text-dark">

                                      ${
                                          g.pickupPoint
                                              ? escapeHtml(g.pickupPoint)
                                              : "N/A"
                                      }

                                  </div>


                                  <!-- STATE -->
                                  <div class="small text-muted">

                                      ${
                                          g.stateName
                                              ? escapeHtml(g.stateName)
                                              : ""
                                      }

                                  </div>


                                  <!-- DISTRICT -->
                                  <div class="small text-muted">

                                      ${
                                          g.districtName
                                              ? escapeHtml(g.districtName)
                                              : ""
                                      }

                                  </div>


                                  <!-- CITY / VILLAGE -->
                                  <div
                                      class="small text-secondary"
                                      style="max-width:200px;"
                                  >

                                      ${
                                          g.locationName
                                              ? escapeHtml(g.locationName)
                                              : ""
                                      }

                                  </div>


                                  <!-- Existing field name kept same -->
                                  <input
                                      type="hidden"
                                      name="f_address_key[]"
                                      value="${escapeHtml(key)}"
                                  >


                                  <!-- Additional useful transaction values -->
                                  <input
                                      type="hidden"
                                      name="f_pickup_point[]"
                                      value="${escapeHtml(g.pickupPoint)}"
                                  >

                                  <input
                                      type="hidden"
                                      name="f_stateid[]"
                                      value="${escapeHtml(g.stateId)}"
                                  >

                                  <input
                                      type="hidden"
                                      name="f_statename[]"
                                      value="${escapeHtml(g.stateName)}"
                                  >

                                  <input
                                      type="hidden"
                                      name="f_districtid[]"
                                      value="${escapeHtml(g.districtId)}"
                                  >

                                  <input
                                      type="hidden"
                                      name="f_districtname[]"
                                      value="${escapeHtml(g.districtName)}"
                                  >

                                  <input
                                      type="hidden"
                                      name="f_locationid[]"
                                      value="${escapeHtml(g.locationId)}"
                                  >

                                  <input
                                      type="hidden"
                                      name="f_locationname[]"
                                      value="${escapeHtml(g.locationName)}"
                                  >

                              </div>

                          </div>

                      </td>


                      <!-- =========================================
                           DATE
                      ========================================== -->
                      <td class="text-center">

                          <div
                              class="fw-bold text-primary"
                              style="font-size:0.95rem;"
                          >

                              ${escapeHtml(g.date)}

                          </div>

                      </td>


                      <!-- =========================================
                           PRODUCT DETAILS
                      ========================================== -->
                      <td>

                          <div class="d-flex flex-wrap gap-1 mb-1">

                              ${g.items.map(item => `

                                  <span
                                      class="
                                          badge
                                          bg-white
                                          text-dark
                                          border
                                          shadow-sm
                                          px-2
                                          py-1
                                      "
                                  >

                                      ${escapeHtml(item.name)}

                                      <strong class="text-primary">
                                          (${item.qty})
                                      </strong>

                                  </span>

                              `).join("")}

                          </div>


                          <!-- NO OF BOXES -->
                          <div
                              class="
                                  small
                                  text-muted
                                  d-flex
                                  align-items-center
                                  gap-2
                              "
                          >

                              <i class="fa fa-cubes me-1"></i>

                              No of Boxes:

                              <strong>
                                  ${g.noOfBoxes}
                              </strong>

                          </div>

                      </td>


                      <!-- =========================================
                           DISTANCE
                           No map for Box Packing
                      ========================================== -->
                      <td class="text-center">

                          <span class="fw-bold text-muted">
                              N/A
                          </span>

                          <br>

                          <small
                              class="text-muted text-uppercase fw-bold"
                              style="font-size:0.7rem;"
                          >
                              No Map
                          </small>

                      </td>


                      <!-- =========================================
                           FREIGHT AMOUNT
                           PICKED FROM deliveryboxtbl
                      ========================================== -->
                      <td>

                          <div class="input-group input-group-sm">

                              <span
                                  class="
                                      input-group-text
                                      bg-white
                                      border-end-0
                                  "
                              >
                                  Rs
                              </span>


                              <input
                                  type="text"
                                  name="f_amount[]"
                                  class="
                                      form-control
                                      f_amt_input
                                      border-start-0
                                      fw-bold
                                  "
                                  value="${freightAmount.toFixed(2)}"
                                  onKeyPress="
                                      return isFloatNumber(event,this);
                                  "
                              >

                          </div>


                          <!-- BOX CHARGE INFO -->
                          <div
                              class="
                                  small
                                  text-muted
                                  mt-1
                              "
                              style="font-size:0.75rem;"
                          >

                              Box Rate:

                              <strong>
                                  Rs ${g.freightCharge}
                              </strong>

                          </div>


                          <input
                              type="hidden"
                              name="f_box_freight_charge[]"
                              value="${g.freightCharge}"
                          >

                      </td>


                      <!-- =========================================
                           DISCOUNT
                           Existing name/class kept
                      ========================================== -->
                      <td>

                          <div class="input-group input-group-sm">

                              <input
                                  type="text"
                                  name="f_discount[]"
                                  class="
                                      form-control
                                      f_disc_input
                                      text-center
                                      ${isDiscReadOnly ? 'bg-light' : ''}
                                  "
                                  value="0"
                                  ${isDiscReadOnly ? 'readonly' : ''}
                                  onKeyPress="
                                      return isFloatNumber(event,this);
                                  "
                              >

                              <span
                                  class="
                                      input-group-text
                                      bg-white
                                      border-start-0
                                  "
                              >
                                  %
                              </span>

                          </div>

                      </td>


                      <!-- =========================================
                           PAYABLE
                      ========================================== -->
                      <td>

                          <div class="input-group input-group-sm">

                              <span
                                  class="
                                      input-group-text
                                      bg-light
                                      border-end-0
                                  "
                              >
                                  Rs
                              </span>


                              <input
                                  type="text"
                                  name="f_payable[]"
                                  class="
                                      form-control
                                      f_payable_input
                                      bg-light
                                      fw-bold
                                      text-primary
                                      border-start-0
                                  "
                                  readonly
                                  value="${freightAmount.toFixed(2)}"
                              >

                          </div>

                      </td>


                      <!-- =========================================
                           ACTION / INFORMATION
                      ========================================== -->
                      <td class="text-center">

                          <div class="f_vehicle_badge mt-1">

                              <span
                                  class="
                                      badge
                                      bg-info-subtle
                                      text-info
                                      border
                                      border-info-subtle
                                  "
                                  style="font-size:0.75rem;"
                              >

                                  <i class="fa fa-box me-1"></i>

                                  Box Packing

                              </span>

                          </div>


                          <!-- Existing name/class kept -->
                          <input
                              type="hidden"
                              name="f_vehicle[]"
                              class="f_vehicle_info"
                              value="${escapeHtml(g.pickupPoint)}"
                          >


                          <!-- Existing field retained -->
                          <input
                              type="hidden"
                              name="f_distance[]"
                              class="f_distance_info"
                              value="0"
                          >
                          <input type="hidden" name="f_totaldistance[]" class="f_totaldistance_input" value="0" />
                          <input type="hidden" name="f_freedistance[]" class="f_freedistance_input" value="0" />
                          <input type="hidden" name="f_payabledistance[]" class="f_payabledistance_input" value="0" />
                          <input type="hidden" name="f_narration[]" class="f_narration_input" value="" />

                      </td>

                  </tr>
              `;


              $tbody.append(html);

              groupIndex++;
          }

          if (groupIndex === 0) {
              $tbody.append('<tr><td colspan="8" class="text-center py-4 text-muted">No box packing data found. Please check Step 3.</td></tr>');
          }

          $("#freeFreightNotification").empty().hide();
          $("#spanratedbl").html("").addClass("d-none");
          calculateGrandTotalFreight();
          updateDiscountRemarksNarration();

             if(pickupfrom ==="delivered"){
                $(".f_disc_input").prop("disabled", false);
                $(".f_amt_input").prop("disabled", false);

             }else{
                  $(".f_disc_input").prop("disabled", true);
                  $(".f_amt_input").prop("disabled", true);
             }

               if(pickupfrom ==="delivered"){
                $(".f_disc_input").prop("disabled", false);
                $(".f_amt_input").prop("disabled", false);

             }else{
                  $(".f_disc_input").prop("disabled", true);
                  $(".f_amt_input").prop("disabled", true);
             }
          return;
        } 

        if (pickupfrom === 'pickup') {
            $("#freeFreightNotification").empty().hide();
            $("#spanratedbl").html("").addClass("d-none");
            $tbody.append('<tr><td colspan="8" class="text-center py-4 text-muted">Self Pickup selected. No freight charges applicable.</td></tr>');
             if(pickupfrom ==="delivered"){
                $(".f_disc_input").prop("disabled", false);
                $(".f_amt_input").prop("disabled", false);

             }else{
                  $(".f_disc_input").prop("disabled", true);
                  $(".f_amt_input").prop("disabled", true);
             }
            calculateGrandTotalFreight();
            updateDiscountRemarksNarration();
             if(pickupfrom ==="delivered"){
                $(".f_disc_input").prop("disabled", false);
                $(".f_amt_input").prop("disabled", false);

             }else{
                  $(".f_disc_input").prop("disabled", true);
                  $(".f_amt_input").prop("disabled", true);
             }
            return;
        } 
        // 1. Map Product -> Delivery Date and Grafted Status from Step 2
        const productInfo = {};
        $("#provisionorderitemsTable tbody tr").each(function() {
            const $pSelect = $(this).find(".productname");
            const productId = $pSelect.val();
            const date = $(this).find(".expecteddelivery").val();
            const selectedOpt = $pSelect.find("option:selected");
            const optText = selectedOpt.text() || '';
            const optIsGrafted = selectedOpt.data("isgrafted");
            
            let isGrafted = false;
            if (optIsGrafted) {
                isGrafted = String(optIsGrafted).toLowerCase() === 'grafted';
            } else if (window.allProductsMap && window.allProductsMap[productId]) {
                const prodData = window.allProductsMap[productId];
                const gVal = prodData.IsGrafted || (prodData.sku && prodData.sku.toUpperCase().includes('NONGRAFTED') ? 'NonGrafted' : (prodData.sku && prodData.sku.toUpperCase().includes('GRAFTED') ? 'Grafted' : ''));
                isGrafted = String(gVal).toLowerCase() === 'grafted';
            } else {
                isGrafted = (/^grafted/i.test(optText) && !/^nongrafted|non-grafted/i.test(optText));
            }

            if (productId && date) {
                productInfo[productId] = {
                    date: date,
                    isGrafted: isGrafted
                };
            }
        });

        // 2. Group Step 3 rows by Address + Date
        const groups = {};
        $("#deliveryaddresstbl tbody tr").each(function() {
            const productId = $(this).find(".delivery_product_select").val();
            const productName = $(this).find(".delivery_product_select option:selected").text();
            const qty = parseFloat($(this).find(".delivery_qty_input").val()) || 0;
            const distance = parseFloat($(this).find(".delivery_distance_input").val()) || 0;
            const pInfo = productInfo[productId] || {};
            let date = pInfo.date || '';
            const isGrafted = pInfo.isGrafted || false;

            if (productId === 'all' || !date || date === 'N/A') {
                const firstDate = Object.values(productInfo).map(p => p.date).filter(Boolean)[0];
                date = firstDate || $("#provisionorderitemsTable tbody tr:first .expecteddelivery").val() || 'N/A';
            }
            
            // Address details
            const city = $(this).find("input[name='delivery_city[]']").val() || '';
            const district = $(this).find("input[name='delivery_district[]']").val() || '';
            const address = $(this).find("input[name='delivery_fulladdress[]']").val() || '';
            const stateid = $(this).find(".delivery_stateid").val() || $(this).find(".del_state").val() || '';
            const districtid = $(this).find(".delivery_district_id").val() || $(this).find(".del_district").val() || '';
            
            const addressKey = `${city}|${district}|${address}`;
            const groupKey = `${addressKey}@@@${date}`;

            if (!groups[groupKey]) {
                groups[groupKey] = {
                    address: { city, district, address, stateid, districtid },
                    date: date,
                    distance: distance, // Use the distance from the first row in the group
                    items: []
                };
            }

            // Capture saved freight data from row attributes if present
            const savedDeliveryCharges = parseFloat($(this).data("deliverycharges")) || 0;
            const savedFreightAmt = parseFloat($(this).data("freightamt")) || 0;
            const savedVehicleno = $(this).data("vehicleno") || '';
            const savedTotalDist = parseFloat($(this).data("totaldistance")) || distance;
            const savedFreeDist = parseFloat($(this).data("freedistance")) || 0;
            const savedPayableDist = parseFloat($(this).data("payabledistance")) || 0;
            const savedRemark = $(this).data("remark") || '';

            if (savedDeliveryCharges > 0 || savedFreightAmt > 0 || (savedVehicleno && savedVehicleno.trim() !== '') || savedTotalDist > 0 || (savedRemark && savedRemark.trim() !== '')) {
                groups[groupKey].savedFreight = {
                    deliverycharges: savedDeliveryCharges,
                    freightamt: savedFreightAmt,
                    vehicleno: savedVehicleno,
                    totaldistance: savedTotalDist,
                    freedistance: savedFreeDist,
                    payabledistance: savedPayableDist,
                    remark: savedRemark
                };
            }

            if (productId === 'all') {
                let hasItems = false;
                $("#provisionorderitemsTable tbody tr").each(function() {
                    const $pSelect = $(this).find(".productname");
                    const pId = $pSelect.val();
                    const pName = $pSelect.find("option:selected").text() || pId;
                    const pQty = parseFloat($(this).find(".qty_td").val()) || 0;
                    const pInf = productInfo[pId] || {};
                    if (pId && pQty > 0) {
                        groups[groupKey].items.push({ name: pName, qty: pQty, isGrafted: pInf.isGrafted || false, productId: pId });
                        hasItems = true;
                    }
                });
                if (!hasItems) {
                    groups[groupKey].items.push({ name: productName || 'All Products', qty: qty, isGrafted: isGrafted, productId: productId });
                }
            } else {
                groups[groupKey].items.push({ name: productName, qty: qty, isGrafted: isGrafted, productId: productId });
            }
        });

        // Fallback if Step 3 delivery rows were skipped
        if (Object.keys(groups).length === 0) {
            const defaultAddress = $("#delivery_address").val() || 'Client Delivery Address (Update Later)';
            const defaultCity = $("#cityname").val() || '';
            const defaultDistrict = $("#districtname").val() || '';
            const defaultDate = Object.values(productInfo)[0] ? Object.values(productInfo)[0].date : 'N/A';

            const defaultItems = [];
            $("#provisionorderitemsTable tbody tr").each(function() {
                const $pSelect = $(this).find(".productname");
                const productId = $pSelect.val();
                const pName = $pSelect.find("option:selected").text() || $pSelect.val();
                const pQty = parseFloat($(this).find(".qty_td").val()) || 0;
                const pInfo = productInfo[productId] || {};
                const isGrafted = pInfo.isGrafted || false;
                if (pName && pQty > 0) {
                    defaultItems.push({ name: pName, qty: pQty, isGrafted: isGrafted, productId: productId });
                }
            });

            if (defaultItems.length > 0) {
                const groupKey = `${defaultCity}|${defaultDistrict}|${defaultAddress}@@@${defaultDate}`;
                groups[groupKey] = {
                    address: { city: defaultCity, district: defaultDistrict, address: defaultAddress },
                    date: defaultDate,
                    distance: 0,
                    items: defaultItems
                };
            }
        }

        if (!skipPrompt && Object.keys(groups).length > 1) {
            checkAndPromptConsolidatedDelivery(groups);
        }

        // 3. Render Groups
        let groupIndex = 0;
        const projectid = '<?php echo $projectid; ?>';
        const rooturl = '<?php echo $rooturl; ?>';
        const loginRoleId = parseInt($('body').data('loginroleid') || $('#hidden_loginroleid').val() || '<?php echo (int)($loginroleid ?? ($_SESSION['loginroleid'] ?? 0)); ?>') || 0;
        const isDiscReadOnly = (loginRoleId > 3);

        for (const key in groups) {
            const g = groups[key];
            const totalGroupQty = g.items.reduce((sum, i) => sum + (parseFloat(i.qty) || 0), 0);
            const totalGraftedQty = g.items.filter(i => i.isGrafted).reduce((sum, i) => sum + (parseFloat(i.qty) || 0), 0);
            const plantType = (totalGraftedQty > 0 && totalGraftedQty === totalGroupQty) ? 'Grafted' : (totalGraftedQty === 0 ? 'Nongrafted' : 'Both');
            let km = parseFloat(g.distance) || 0;

            // Resolve saved freight
            let savedFreight = g.savedFreight;
            if (!savedFreight && window.savedOrderFreightData) {
                const sDelList = window.savedOrderFreightData.delivery || [];
                const sFr = window.savedOrderFreightData.freight || null;
                const matchedDel = sDelList.find(d => {
                    const dCity = (d.dest_city || '').trim().toLowerCase();
                    const gCity = (g.address.city || '').trim().toLowerCase();
                    return dCity && gCity && dCity === gCity;
                }) || (sDelList.length === 1 ? sDelList[0] : null);

                if (matchedDel && (parseFloat(matchedDel.deliverycharges) > 0 || parseFloat(matchedDel.freightamt) > 0 || matchedDel.vehicleno || parseFloat(matchedDel.totaldistance) > 0)) {
                    savedFreight = {
                        deliverycharges: parseFloat(matchedDel.deliverycharges) || 0,
                        freightamt: parseFloat(matchedDel.freightamt) || 0,
                        vehicleno: matchedDel.vehicleno || '',
                        totaldistance: parseFloat(matchedDel.totaldistance) || km,
                        freedistance: parseFloat(matchedDel.freedistance) || 0,
                        payabledistance: parseFloat(matchedDel.distance) || 0,
                        remark: matchedDel.remark || ''
                    };
                } else if (sFr && (parseFloat(sFr.freight_amount) > 0 || parseFloat(sFr.payable_freightamount) > 0 || sFr.vehicle_no || parseFloat(sFr.distance_km) > 0)) {
                    savedFreight = {
                        deliverycharges: parseFloat(sFr.freight_amount) || 0,
                        freightamt: parseFloat(sFr.payable_freightamount) || 0,
                        vehicleno: sFr.vehicle_no || '',
                        totaldistance: parseFloat(sFr.distance_km) || km,
                        freedistance: 0,
                        payabledistance: parseFloat(sFr.distance_km) || km,
                        discount: parseFloat(sFr.freight_discountpercent) || 0,
                        remark: sFr.remarks || ''
                    };
                }
            }

            const hasSavedFreight = savedFreight && (
                savedFreight.deliverycharges > 0 ||
                savedFreight.freightamt > 0 ||
                (savedFreight.vehicleno && savedFreight.vehicleno.trim() !== '') ||
                (savedFreight.discount && savedFreight.discount > 0) ||
                (savedFreight.totaldistance && savedFreight.totaldistance > 0) ||
                (savedFreight.remark && savedFreight.remark.trim() !== '')
            );

            let rowGrossAmt = 0;
            let rowDiscount = 0;
            let rowPayable = 0;
            let rowVehicle = '';
            let rowTotalDist = km;
            let rowFreeDist = 0;
            let rowPayableDist = km;
            let rowNarration = '';
            let isUserSelectedAttr = '';

            if (hasSavedFreight) {
                rowGrossAmt = parseFloat(savedFreight.deliverycharges) || 0;
                rowPayable = parseFloat(savedFreight.freightamt) || 0;
                if (savedFreight.discount !== undefined) {
                    rowDiscount = parseFloat(savedFreight.discount) || 0;
                } else if (rowGrossAmt > 0) {
                    rowDiscount = Math.max(0, Math.round(((rowGrossAmt - rowPayable) / rowGrossAmt) * 100 * 100) / 100);
                }
                rowVehicle = savedFreight.vehicleno || '';
                rowTotalDist = (parseFloat(savedFreight.totaldistance) > 0) ? parseFloat(savedFreight.totaldistance) : km;
                rowFreeDist = parseFloat(savedFreight.freedistance) || 0;
                rowPayableDist = (parseFloat(savedFreight.payabledistance) > 0) ? parseFloat(savedFreight.payabledistance) : Math.max(0, rowTotalDist - rowFreeDist);
                rowNarration = savedFreight.remark || '';
                isUserSelectedAttr = 'data-user-selected="true"';
            }

            const html = `
                <tr class="group-row" ${isUserSelectedAttr} data-totalqty="${totalGroupQty}" data-graftedqty="${totalGraftedQty}" data-planttype="${plantType}" data-stateid="${g.address.stateid || ''}" data-districtid="${g.address.districtid || ''}">
                    <td>
                        <div class="d-flex align-items-start gap-2">
                            <i class="fa fa-map-marker-alt text-primary mt-1"></i>
                            <div>
                                <div class="fw-bold text-dark">${g.address.city || 'N/A'}</div>
                                <div class="small text-muted">${g.address.district}</div>
                                <div class="small text-secondary" style="max-width: 200px;">${g.address.address}</div>
                                <input type="hidden" name="f_address_key[]" value="${key}" />
                            </div>
                        </div>
                    </td>
                    <td class="text-center">
                        <div class="fw-bold text-primary" style="font-size: 0.95rem;">${g.date}</div>
                    </td>
                    <td>
                        <div class="d-flex flex-wrap gap-1 mb-1">
                            ${g.items.map(i => `<span class="badge bg-white text-dark border shadow-sm px-2 py-1">${i.name} <strong class="text-primary">(${i.qty})</strong> ${i.isGrafted ? '<span class="badge bg-success-subtle text-success border border-success-subtle ms-1" style="font-size:0.65rem;">Grafted</span>' : '<span class="badge bg-warning-subtle text-dark border border-warning-subtle ms-1" style="font-size:0.65rem;">Non-Grafted</span>'}</span>`).join('')}
                        </div>
                        <div class="small text-muted d-flex align-items-center gap-2">
                            <span><i class="fa fa-boxes me-1"></i>Total Qty: <strong>${totalGroupQty.toLocaleString()}</strong></span>
                            ${totalGraftedQty > 0 ? `<span class="text-success ms-2"><i class="fa fa-seedling me-1"></i>Grafted: <strong>${totalGraftedQty.toLocaleString()}</strong></span>` : ''}
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="fw-bold text-dark fs-5">${rowTotalDist}</span><br>
                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Km</small>
                    </td>
                    <td>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0">Rs</span>
                            <input type="text" name="f_amount[]" class="form-control f_amt_input border-start-0 fw-bold" value="${rowGrossAmt.toFixed(2)}" onKeyPress="return isFloatNumber(event,this);" />
                        </div>
                    </td>
                    <td>
                        <div class="input-group input-group-sm">
                            <input type="text" name="f_discount[]" class="form-control f_disc_input text-center ${isDiscReadOnly ? 'bg-light' : ''}" value="${rowDiscount}" ${isDiscReadOnly ? 'readonly' : ''} onKeyPress="return isFloatNumber(event,this);" />
                            <span class="input-group-text bg-white border-start-0">%</span>
                        </div>
                        <div class="f_discount_info small text-success mt-1" style="font-size: 0.72rem; ${rowNarration ? '' : 'display:none;'}">${escapeHtml(rowNarration)}</div>
                    </td>
                    <td>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0">Rs</span>
                            <input type="text" name="f_payable[]" class="form-control f_payable_input bg-light fw-bold text-primary border-start-0" readonly value="${rowPayable.toFixed(2)}" />
                        </div>
                    </td>
                    <td class="text-center">
                        <div class="d-flex gap-1 justify-content-center align-items-center">
                            <button type="button" class="btn btn-primary btn-sm rounded-circle freightratemodal" onclick="openFreightRowCalculator(this, ${rowTotalDist}, ${totalGroupQty})" title="Calculate Freight">
                                <i class="fa fa-calculator"></i>
                            </button>
                        </div>
                        <div class="f_vehicle_badge mt-1">
                            ${rowVehicle ? `
                                <span class="badge bg-success-subtle text-success border border-success-subtle auto-vehicle-badge" style="font-size: 0.75rem;"><i class="fa fa-truck me-1"></i>${escapeHtml(rowVehicle)}</span>
                            ` : `
                                <span class="badge bg-info-subtle text-info border border-info-subtle auto-vehicle-badge" style="font-size: 0.75rem;"><i class="fa fa-spinner fa-spin me-1"></i>Auto...</span>
                            `}
                        </div>
                        <input type="hidden" name="f_vehicle[]" class="f_vehicle_info" value="${escapeHtml(rowVehicle)}" />
                        <input type="hidden" name="f_distance[]" class="f_distance_info" value="${rowTotalDist}" />
                        <input type="hidden" name="f_totaldistance[]" class="f_totaldistance_input" value="${rowTotalDist}" />
                        <input type="hidden" name="f_freedistance[]" class="f_freedistance_input" value="${rowFreeDist}" />
                        <input type="hidden" name="f_payabledistance[]" class="f_payabledistance_input" value="${rowPayableDist}" />
                        <input type="hidden" name="f_narration[]" class="f_narration_input" value="${escapeHtml(rowNarration)}" />
                    </td>
                </tr>
            `;
            $tbody.append(html);
            groupIndex++;
        }

        renderFreightEligibilityRules();

        if (groupIndex === 0) {
            $tbody.append('<tr><td colspan="8" class="text-center py-4 text-muted">No delivery data found. Please check Step 3.</td></tr>');
        } else {
            // Auto-trigger calculation for group rows that don't have user-selected/saved freight
            $("#freightGroupTable tbody tr.group-row").each(function() {
                if ($(this).attr('data-user-selected') !== 'true') {
                    autoCalculateRowFreight($(this), projectid, rooturl);
                }
            });
            calculateGrandTotalFreight();
            updateDiscountRemarksNarration();
        }
    }

    /**
     * Render the Freight Eligibility Rules banner dynamically with full-width row layout and Apply/Clear buttons
     * Only displays when delivery is by Company ('delivered') and only lists matched/eligible rules.
     */
    window.renderFreightEligibilityRules = function(autoApply = true) {
        const $notif = $("#freeFreightNotification");
        const customerType = $("#customer_type").val() || 'Farmer';
        
        // 1. Check Delivery Method: Only show Freight Eligibility Rules when Delivery by Company ('delivered') is selected
        const pickupfrom = $('input[name="pickupfrom"]:checked').val() || 
                           $("#orderDeliveryFreightForm input[name='pickupfrom']:checked").val();

        if (pickupfrom !== 'delivered') {
            $notif.empty().hide();
            return;
        }

        var rooturl = $('body').data('routurl') || '../';
        var cleanRoot = rooturl || '';
        if (cleanRoot.endsWith('/')) {
            cleanRoot = cleanRoot.substring(0, cleanRoot.length - 1);
        }
        var ajaxUrl = cleanRoot + "/freight_discount_module/modal/freight_discount_modal.php?action=get_active_rules";

        // Collect current delivery row states
        let orderRows = [];
        $("#freightGroupTable tbody tr.group-row").each(function() {
            const $r = $(this);
            const qty = parseFloat($r.data("totalqty")) || 0;
            const dist = parseFloat($r.find(".f_distance_info").val()) || 0;
            const plantType = $r.data("planttype") || 'Grafted';
            const discInput = parseFloat($r.find(".f_disc_input").val()) || 0;
            const payable = parseFloat($r.find(".f_payable_input").val()) || 0;
            const amt = parseFloat($r.find(".f_amt_input").val()) || 0;
            const isCleared = $r.attr("data-discount-cleared") === "true";
            if (qty > 0 || dist > 0 || amt > 0) {
                orderRows.push({
                    qty: qty,
                    distance: dist,
                    plantType: plantType,
                    discount: discInput,
                    payable: payable,
                    amt: amt,
                    isCleared: isCleared,
                    row: $r
                });
            }
        });

        if (orderRows.length === 0) {
            $notif.empty().hide();
            return;
        }

        $.ajax({
            url: ajaxUrl,
            type: "POST",
            data: {
                customer_type: customerType,
                plant_type: 'All'
            },
            dataType: "JSON",
            success: function(res) {
                if (!res || !res.status || !Array.isArray(res.data) || res.data.length === 0) {
                    $notif.empty().hide();
                    return;
                }

                const rules = res.data;
                window.currentFreightRules = rules;
                let anyEligible = false;
                let matchedRuleNames = [];
                let matchedRulesToAutoApply = [];
                let rulesHtmlArray = [];

                rules.forEach(function(rule) {
                    const minQty = parseInt(rule.min_order_qty) || 0;
                    const maxQty = parseInt(rule.max_order_qty) || 0;
                    const minDist = parseFloat(rule.min_distance_km) || 0;
                    const maxDist = parseFloat(rule.max_distance_km) || 0;
                    const discVal = parseFloat(rule.discount_value) || 0;
                    const discType = rule.discount_type;

                    // Condition string for Qty: order qt >= min qty && <= max qty
                    let qtyStr = '';
                    if (minQty > 0 && maxQty > 0) {
                        qtyStr = `Order Qty &ge; <strong>${minQty.toLocaleString()}</strong> && &le; <strong>${maxQty.toLocaleString()}</strong>`;
                    } else if (minQty > 0) {
                        qtyStr = `Order Qty &ge; <strong>${minQty.toLocaleString()}</strong>`;
                    } else if (maxQty > 0) {
                        qtyStr = `Order Qty &le; <strong>${maxQty.toLocaleString()}</strong>`;
                    } else {
                        qtyStr = `Any Order Qty`;
                    }

                    // Condition string for Distance: distance <=
                    let distStr = '';
                    if (minDist > 0 && maxDist > 0) {
                        distStr = `Distance &ge; <strong>${minDist} KM</strong> && &le; <strong>${maxDist} KM</strong>`;
                    } else if (minDist > 0) {
                        distStr = `Distance &le; <strong>${minDist} KM</strong>`;
                    } else if (maxDist > 0) {
                        distStr = `Distance &le; <strong>${maxDist} KM</strong>`;
                    } else {
                        distStr = `Any Distance`;
                    }

                    // Benefit badge
                    let benefitBadge = '';
                    let benefitText = '';
                    if (discType === 'Percentage') {
                        if (discVal >= 100) {
                            benefitBadge = '<span class="badge bg-success text-white px-2 py-1 fs-7"><i class="fa fa-gift me-1"></i>100% Free Freight</span>';
                            benefitText = '100% Free Freight';
                        } else {
                            benefitBadge = `<span class="badge bg-info text-dark px-2 py-1 fs-7"><i class="fa fa-percent me-1"></i>${discVal}% Off</span>`;
                            benefitText = `${discVal}% Off`;
                        }
                    } else if (discType === 'Fixed_Amount') {
                        benefitBadge = `<span class="badge bg-primary text-white px-2 py-1 fs-7">Rs ${discVal} Off</span>`;
                        benefitText = `Rs ${discVal} Off`;
                    } else if (discType === 'Per_Plant') {
                        benefitBadge = `<span class="badge bg-primary text-white px-2 py-1 fs-7">Rs ${discVal}/Plant Off</span>`;
                        benefitText = `Rs ${discVal}/Plant Off`;
                    } else if (discType === 'Per_KM') {
                        benefitBadge = `<span class="badge bg-primary text-white px-2 py-1 fs-7">Rs ${discVal}/KM Off</span>`;
                        benefitText = `Rs ${discVal}/KM Off`;
                    }

                    // Plant type badge
                    let plantBadge = '';
                    if (rule.plant_type === 'Grafted') {
                        plantBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle me-1"><i class="fa fa-seedling me-1"></i>Grafted</span>';
                    } else if (rule.plant_type === 'Nongrafted') {
                        plantBadge = '<span class="badge bg-warning-subtle text-dark border border-warning-subtle me-1"><i class="fa fa-leaf me-1"></i>Non-Grafted</span>';
                    } else {
                        plantBadge = '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle me-1">All Crops</span>';
                    }

                    // Customer type badge
                    let custBadge = '';
                    if (rule.customer_type === 'Dealer') {
                        custBadge = '<span class="badge bg-info-subtle text-info border border-info-subtle me-1"><i class="fa fa-briefcase me-1"></i>Dealer</span>';
                    } else if (rule.customer_type === 'Farmer') {
                        custBadge = '<span class="badge bg-light text-dark border me-1"><i class="fa fa-user me-1"></i>Farmer</span>';
                    } else {
                        custBadge = '<span class="badge bg-light text-muted border me-1">All Customers</span>';
                    }

                    // Check if any row in current order satisfies this rule: distance and order qty matched
                    let isMatched = false;
                    let isApplied = false;
                    let canAutoApplyThisRule = false;
                    for (let r of orderRows) {
                        const custOk = (rule.customer_type === 'All' || rule.customer_type === customerType);
                        const plantOk = (rule.plant_type === 'Both' || rule.plant_type === 'All' || rule.plant_type.toLowerCase() === r.plantType.toLowerCase());
                        const qtyOk = (minQty === 0 || r.qty >= minQty) && (maxQty === 0 || r.qty <= maxQty);
                        const distOk = (maxDist === 0 || r.distance <= maxDist);

                        if (custOk && plantOk && qtyOk && distOk && r.qty > 0 && r.distance > 0) {
                            isMatched = true;
                            anyEligible = true;
                            if (r.discount > 0) {
                                isApplied = true;
                            } else if (!r.isCleared && r.amt > 0) {
                                canAutoApplyThisRule = true;
                            }
                            if (!matchedRuleNames.includes(rule.plan_name)) {
                                matchedRuleNames.push(rule.plan_name);
                            }
                        }
                    }

                    if (canAutoApplyThisRule && !matchedRulesToAutoApply.includes(rule.id)) {
                        matchedRulesToAutoApply.push(rule.id);
                    }

                    // ONLY render matched rules in the list!
                    if (!isMatched) {
                        return;
                    }

                    const cardClass = 'border-success bg-success-subtle shadow-sm';

                    rulesHtmlArray.push(`
                        <div class="col-12 mb-2">
                            <div class="card ${cardClass} rounded-3 border p-2 p-md-3" style="transition: all 0.2s ease;">
                                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2">
                                    <!-- Left Column: Plan Title, Badges & Criteria -->
                                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center gap-2 gap-md-4 flex-grow-1">
                                        <div style="min-width: 290px;">
                                            <div class="fw-bold text-success fs-6 mb-1">
                                                <i class="fa fa-file-invoice text-success me-1"></i>${escapeHtml(rule.plan_name)}
                                            </div>
                                            <div class="d-flex flex-wrap gap-1 align-items-center">
                                                <span class="badge bg-light text-muted border" style="font-size:0.72rem;">${escapeHtml(rule.plan_code)}</span>
                                                ${custBadge}
                                                ${plantBadge}
                                                <span class="badge bg-success text-white"><i class="fa fa-check-circle me-1"></i>Eligible & Matched</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Criteria in the middle -->
                                        <div class="border-start-md ps-md-3 text-secondary small flex-grow-1" style="font-size: 0.84rem; line-height: 1.5;">
                                            <div class="d-flex flex-wrap gap-3 align-items-center">
                                                <div><i class="fa fa-road text-muted me-1"></i>${distStr}</div>
                                                <div><i class="fa fa-boxes text-muted me-1"></i>${qtyStr}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Right Column: Benefit & Action Buttons (Apply & Clear) -->
                                    <div class="d-flex align-items-center justify-content-between justify-content-lg-end gap-2 mt-2 mt-lg-0 pt-2 pt-lg-0 border-top border-top-lg-0">
                                        <div class="me-2">${benefitBadge}</div>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-success px-3 shadow-sm" onclick="applyPlanDiscountToFreight(${rule.id})" title="Apply ${benefitText} to matching ${rule.plant_type} delivery rows">
                                                <i class="fa fa-check-circle me-1"></i>Apply
                                            </button>
                                            <button type="button" class="btn btn-outline-danger px-3" onclick="clearPlanDiscountFromFreight(${rule.id})" title="Clear discount for ${rule.plant_type} delivery rows">
                                                <i class="fa fa-times me-1"></i>Clear
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `);
                });

                // Auto apply matched rules if any
                if (autoApply && matchedRulesToAutoApply.length > 0) {
                    matchedRulesToAutoApply.forEach(function(rId) {
                        applyPlanDiscountToFreight(rId, true);
                    });
                    return;
                }

                // If no rules matched, hide banner completely
                if (!anyEligible || rulesHtmlArray.length === 0) {
                    $notif.empty().hide();
                    return;
                }

                let rulesHtml = rulesHtmlArray.join('');

                let bannerHtml = `
                    <div class="alert alert-success border-success shadow-sm rounded-3 mb-3 p-3">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2 pb-2 border-bottom">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa fa-check-circle text-success fs-5"></i>
                                <strong class="fs-6 text-success">Freight Eligibility Rules</strong>
                                <span class="badge bg-white text-secondary border ms-1">${customerType} Active Plans</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-success text-white px-3 py-2 fs-7 shadow-sm"><i class="fa fa-gift me-1"></i>Free Freight / Discount Applied!</span>
                                <button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2" onclick="clearPlanDiscountFromFreight(0)" title="Clear all applied discounts">
                                    <i class="fa fa-undo me-1"></i>Clear All Discounts
                                </button>
                            </div>
                        </div>
                        <div class="row g-2">
                            ${rulesHtml}
                        </div>
                    </div>
                `;

                $notif.html(bannerHtml).slideDown(200);
            },
            error: function() {
                $notif.empty().hide();
            }
        });
    };

    /**
     * Apply a specific discount plan to matching delivery group rows in the freight table
     */
    window.applyPlanDiscountToFreight = function(planId, isAuto = false) {
        const pickupfrom = $('input[name="pickupfrom"]:checked').val() || 
                           $("#orderDeliveryFreightForm input[name='pickupfrom']:checked").val();
        if (pickupfrom !== 'delivered') {
            return;
        }

        const rules = window.currentFreightRules || [];
        const plan = rules.find(r => String(r.id) === String(planId));
        if (!plan) {
            if (!isAuto) {
                Swal.fire('Notice', 'Plan details not found. Please refresh the rules.', 'warning');
            }
            return;
        }

        let appliedCount = 0;
        const planPlant = String(plan.plant_type || '').toLowerCase();
        const discType = plan.discount_type;
        const discVal = parseFloat(plan.discount_value) || 0;
        const maxDiscAmt = parseFloat(plan.max_discount_amount) || 0;
        const minQty = parseInt(plan.min_order_qty) || 0;
        const maxQty = parseInt(plan.max_order_qty) || 0;
        const minDist = parseFloat(plan.min_distance_km) || 0;
        const maxDist = parseFloat(plan.max_distance_km) || 0;
        const customerType = $("#customer_type").val() || 'Farmer';

        $("#freightGroupTable tbody tr.group-row").each(function() {
            const $row = $(this);
            const rowPlant = String($row.data("planttype") || '').toLowerCase();
            const rowQty = parseFloat($row.data("totalqty")) || 0;
            const rowDist = parseFloat($row.find(".f_distance_info").val()) || 0;
            const rowAmt = parseFloat($row.find(".f_amt_input").val()) || 0;

            if (rowAmt <= 0) return;

            // Check strict conditions per rule criteria for THIS specific row:
            // 1. Customer Type check
            const custOk = (plan.customer_type === 'All' || plan.customer_type === customerType);
            // 2. Plant Type check (Grafted / Nongrafted / Both)
            const plantOk = (planPlant === 'both' || planPlant === 'all' || planPlant === rowPlant || rowPlant === 'both');
            // 3. Order Qty check: rowQty >= minQty && (maxQty == 0 || rowQty <= maxQty)
            const qtyOk = (minQty === 0 || rowQty >= minQty) && (maxQty === 0 || rowQty <= maxQty);
            // 4. Distance KM check: rowDist > 0 && (maxDist == 0 || rowDist <= maxDist)
            const distOk = (rowDist > 0) && (maxDist === 0 || rowDist <= maxDist);

            if (custOk && plantOk && qtyOk && distOk) {
                $row.removeAttr("data-discount-cleared");

                // Calculate discount amount & percentage & free/payable distances
                let discountAmt = 0;
                let discPct = 0;
                let freeDist = 0;
                let payableDist = rowDist;

                if (discType === 'Percentage') {
                    if (discVal >= 100) {
                        if (minDist > 0 && rowDist > minDist) {
                            // Up to minDist is free (0 freight), remaining distance (rowDist - minDist) is chargeable
                            freeDist = minDist;
                            payableDist = Math.max(0, rowDist - minDist);
                            const freeRatio = minDist / rowDist;
                            discountAmt = rowAmt * freeRatio;
                            discPct = freeRatio * 100;
                        } else {
                            freeDist = rowDist;
                            payableDist = 0;
                            discPct = 100;
                            discountAmt = rowAmt;
                        }
                    } else {
                        freeDist = (rowDist * discVal) / 100.0;
                        payableDist = Math.max(0, rowDist - freeDist);
                        discPct = discVal;
                        discountAmt = (rowAmt * discVal) / 100.0;
                    }
                } else if (discType === 'Fixed_Amount') {
                    discountAmt = Math.min(discVal, rowAmt);
                    discPct = (rowAmt > 0) ? (discountAmt / rowAmt) * 100 : 0;
                    freeDist = (rowAmt > 0 && rowDist > 0) ? (discountAmt / rowAmt) * rowDist : 0;
                    payableDist = Math.max(0, rowDist - freeDist);
                } else if (discType === 'Per_Plant') {
                    discountAmt = Math.min(rowQty * discVal, rowAmt);
                    discPct = (rowAmt > 0) ? (discountAmt / rowAmt) * 100 : 0;
                    freeDist = (rowAmt > 0 && rowDist > 0) ? (discountAmt / rowAmt) * rowDist : 0;
                    payableDist = Math.max(0, rowDist - freeDist);
                } else if (discType === 'Per_KM') {
                    discountAmt = Math.min(rowDist * discVal, rowAmt);
                    discPct = (rowAmt > 0) ? (discountAmt / rowAmt) * 100 : 0;
                    freeDist = (rowAmt > 0 && rowDist > 0) ? (discountAmt / rowAmt) * rowDist : 0;
                    payableDist = Math.max(0, rowDist - freeDist);
                }

                if (maxDiscAmt > 0 && discountAmt > maxDiscAmt) {
                    discountAmt = maxDiscAmt;
                    discPct = (rowAmt > 0) ? (discountAmt / rowAmt) * 100 : 0;
                }

                discPct = Math.min(100, Math.max(0, Math.round(discPct * 100) / 100));
                const payable = Math.max(0, rowAmt - discountAmt);

                $row.find(".f_disc_input").val(discPct);
                $row.find(".f_payable_input").val(payable.toFixed(2));
                
                let tagText = escapeHtml(plan.plan_name);
                if (discVal >= 100 && minDist > 0 && rowDist > minDist) {
                    tagText += ` (Free up to ${minDist} KM, -Rs ${discountAmt.toFixed(2)})`;
                } else {
                    tagText += ` (-Rs ${discountAmt.toFixed(2)})`;
                }

                // Update totaldistance, freedistance, payabledistance, narration hidden inputs
                $row.find(".f_totaldistance_input").val(rowDist);
                $row.find(".f_freedistance_input").val(freeDist);
                $row.find(".f_payabledistance_input").val(payableDist);
                $row.find(".f_narration_input").val(tagText);

                const infoHtml = `<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fa fa-tag me-1"></i>${tagText}</span> <button type="button" class="btn btn-link btn-sm text-danger p-0 ms-1 text-decoration-none" onclick="clearRowFreightDiscount($(this).closest('tr'))" title="Clear Discount"><i class="fa fa-times-circle"></i> Clear</button>`;
                $row.find(".f_discount_info").html(infoHtml).show();

                appliedCount++;
            }
        });

        calculateGrandTotalFreight();
        renderFreightEligibilityRules(false);
        updateDiscountRemarksNarration();

        if (!isAuto) {
            if (appliedCount > 0) {
                iziToast.success({
                    title: 'Discount Applied',
                    message: `Applied "${plan.plan_name}" to ${appliedCount} eligible delivery row(s)!`,
                    position: 'topRight'
                });
            } else {
                iziToast.warning({
                    title: 'No Eligible Rows',
                    message: `No delivery row matches "${plan.plan_name}" criteria (Requires: ${plan.plant_type}, Distance &ge; ${minDist} KM, Qty &ge; ${minQty.toLocaleString()}).`,
                    position: 'topRight'
                });
            }
        }
    };

    /**
     * Clear discount for a specific plan or for all delivery rows
     */
    window.clearPlanDiscountFromFreight = function(planId) {
        const rules = window.currentFreightRules || [];
        const plan = rules.find(r => String(r.id) === String(planId));
        const planPlant = plan ? String(plan.plant_type || '').toLowerCase() : 'all';
        const minQty = plan ? (parseInt(plan.min_order_qty) || 0) : 0;
        const maxQty = plan ? (parseInt(plan.max_order_qty) || 0) : 0;
        const minDist = plan ? (parseFloat(plan.min_distance_km) || 0) : 0;
        const maxDist = plan ? (parseFloat(plan.max_distance_km) || 0) : 0;

        let clearedCount = 0;
        $("#freightGroupTable tbody tr.group-row").each(function() {
            const $row = $(this);
            const rowPlant = String($row.data("planttype") || '').toLowerCase();
            const rowQty = parseFloat($row.data("totalqty")) || 0;
            const rowDist = parseFloat($row.find(".f_distance_info").val()) || 0;
            const rowAmt = parseFloat($row.find(".f_amt_input").val()) || 0;

            let shouldClear = false;
            if (!plan || planId === 0) {
                shouldClear = true;
            } else {
                const discInfo = $row.find(".f_discount_info").text();
                const plantOk = (planPlant === 'both' || planPlant === 'all' || planPlant === rowPlant || rowPlant === 'both');
                const qtyOk = (minQty === 0 || rowQty >= minQty) && (maxQty === 0 || rowQty <= maxQty);
                const distOk = (minDist === 0 || rowDist >= minDist) && (maxDist === 0 || rowDist <= maxDist);
                
                if ((discInfo && discInfo.indexOf(plan.plan_name) !== -1) || (plantOk && qtyOk && distOk)) {
                    shouldClear = true;
                }
            }

            if (shouldClear) {
                $row.attr("data-discount-cleared", "true");
                $row.find(".f_disc_input").val("0");
                $row.find(".f_payable_input").val(rowAmt.toFixed(2));
                $row.find(".f_totaldistance_input").val(rowDist);
                $row.find(".f_freedistance_input").val(0);
                $row.find(".f_payabledistance_input").val(rowDist);
                $row.find(".f_narration_input").val("");
                $row.find(".f_discount_info").hide().empty();
                clearedCount++;
            }
        });

        calculateGrandTotalFreight();
        renderFreightEligibilityRules(false);
        updateDiscountRemarksNarration();

        iziToast.info({
            title: 'Discount Cleared',
            message: `Cleared discount for ${clearedCount} delivery row(s).`,
            position: 'topRight'
        });
    };

    /**
     * Clear discount for an individual freight table row
     */
    window.clearRowFreightDiscount = function($row) {
        const amt = parseFloat($row.find(".f_amt_input").val()) || 0;
        const rowDist = parseFloat($row.find(".f_distance_info").val()) || 0;
        $row.attr("data-discount-cleared", "true");
        $row.find(".f_disc_input").val("0");
        $row.find(".f_payable_input").val(amt.toFixed(2));
        $row.find(".f_totaldistance_input").val(rowDist);
        $row.find(".f_freedistance_input").val(0);
        $row.find(".f_payabledistance_input").val(rowDist);
        $row.find(".f_narration_input").val("");
        $row.find(".f_discount_info").hide().empty();
        calculateGrandTotalFreight();
        renderFreightEligibilityRules(false);
        updateDiscountRemarksNarration();
        iziToast.info({
            title: 'Discount Cleared',
            message: 'Discount reset to 0% for this delivery.',
            position: 'bottomRight'
        });
    };

    /**
     * Updates the Remarks textarea with narration for any applied freight discount rules
     */
    window.updateDiscountRemarksNarration = function() {
        const $remarks = $('textarea[name="remarks"]');
        if ($remarks.length === 0) return;

        let discountLines = [];
        $("#freightGroupTable tbody tr.group-row").each(function(index) {
            const $row = $(this);
            const discPct = parseFloat($row.find(".f_disc_input").val()) || 0;
            const amt = parseFloat($row.find(".f_amt_input").val()) || 0;
            const payable = parseFloat($row.find(".f_payable_input").val()) || 0;
            const discAmt = amt - payable;
            
            // Extract clean badge text without the "Clear" button text
            let tagText = $row.find(".f_discount_info .badge").text().trim();
            if (!tagText) {
                tagText = $row.find(".f_discount_info").clone().children('button').remove().end().text().trim();
            }
            
            if (discPct > 0 || discAmt > 0 || tagText) {
                let lineDesc = tagText ? tagText : `Discount: ${discPct}% (-Rs ${discAmt.toFixed(2)})`;
                discountLines.push(`Row ${index + 1}: ${lineDesc}`);
            }
        });

        let currentVal = $remarks.val() || '';
        
        // Remove previous auto-generated discount lines from remarks
        let filteredLines = currentVal.split('\n').filter(function(line) {
            let l = line.trim();
            if (l.startsWith("Freight Discount:") || l.startsWith("Freight Discount Applied:") || l.startsWith("[Freight Discount]")) {
                return false;
            }
            if (/^Row \d+:\s*(All Customers|Dealer|Farmer|Discount|Free|Grafted|Non-Grafted|Plan)/i.test(l)) {
                return false;
            }
            if (l.startsWith("- Row ") && (l.includes("Discount") || l.includes("Plan") || l.includes("Free up to"))) {
                return false;
            }
            return true;
        });
        
        let baseRemark = filteredLines.join('\n').trim();

        if (discountLines.length > 0) {
            let discNarration = "";
            if (discountLines.length === 1) {
                discNarration = `Freight Discount: ${discountLines[0]}`;
            } else {
                discNarration = `Freight Discount Applied:\n` + discountLines.map(d => `- ${d}`).join('\n');
            }
            $remarks.val(baseRemark ? (baseRemark + '\n\n' + discNarration) : discNarration);
        } else {
            $remarks.val(baseRemark);
        }
    };

    /**
     * jQuery API function to fetch applicable freight discount plan from krf_freight_discount_plan
     */
    window.getFreightDiscountPlan = function(params, callback) {
        var rooturl = $('body').data('routurl') || '../';
        var cleanRoot = rooturl || '';
        if (cleanRoot.endsWith('/')) {
            cleanRoot = cleanRoot.substring(0, cleanRoot.length - 1);
        }
        
        var ajaxUrl = cleanRoot + "/freight_discount_module/modal/freight_discount_modal.php?action=calculate_discount";
        
        $.ajax({
            url: ajaxUrl,
            type: "POST",
            data: {
                customer_type: params.customer_type || $("#customer_type").val() || 'Farmer',
                plant_type: params.plant_type || 'Grafted',
                order_qty: params.order_qty || 0,
                freight_amount: params.freight_amount || 0,
                distance_km: params.distance_km || 0,
                stateid: params.stateid || $("#stateid").val() || 'ALL',
                districtid: params.districtid || $("#districtid").val() || 'ALL',
                projectid: params.projectid || $('body').data('projectid') || 1
            },
            dataType: "JSON",
            success: function(res) {
                if (typeof callback === 'function') {
                    callback(res);
                }
            },
            error: function(xhr, status, error) {
                console.error("Freight Discount API Error:", error);
                if (typeof callback === 'function') {
                    callback({ status: false, message: error });
                }
            }
        });
    };

    /**
     * Apply freight discount from plan to a specific table row
     */
    window.applyRowFreightDiscount = function($row) {
        const pickupfrom = $('input[name="pickupfrom"]:checked').val() || 
                           $("#orderDeliveryFreightForm input[name='pickupfrom']:checked").val();
        if (pickupfrom !== 'delivered') {
            return;
        }

        const amt = parseFloat($row.find(".f_amt_input").val()) || 0;
        if (amt <= 0) {
            $row.find(".f_disc_input").val("0");
            $row.find(".f_payable_input").val("0.00");
            $row.find(".f_discount_info").hide().empty();
            calculateGrandTotalFreight();
            renderFreightEligibilityRules();
            return;
        }

        const totalQty = parseFloat($row.data("totalqty")) || 0;
        const km = parseFloat($row.find(".f_distance_info").val()) || 0;
        const plantType = $row.data("planttype") || 'Grafted';
        const customerType = $("#customer_type").val() || 'Farmer';
        const stateId = $row.data("stateid") || $("#stateid").val() || 'ALL';
        const districtId = $row.data("districtid") || $("#districtid").val() || 'ALL';
        const projectid = '<?php echo $projectid; ?>' || 1;

        getFreightDiscountPlan({
            customer_type: customerType,
            plant_type: plantType,
            order_qty: totalQty,
            freight_amount: amt,
            distance_km: km,
            stateid: stateId,
            districtid: districtId,
            projectid: projectid
        }, function(res) {
            if (res && res.status && res.data && res.data.has_discount) {
                const discPct = parseFloat(res.data.discount_percent) || 0;
                const payable = parseFloat(res.data.payable_freight) || 0;
                $row.find(".f_disc_input").val(discPct);
                $row.find(".f_payable_input").val(payable.toFixed(2));
                
                if (res.data.applied_plan) {
                    const plan = res.data.applied_plan;
                    const minDist = parseFloat(plan.min_distance_km) || 0;
                    const discVal = parseFloat(plan.discount_value) || 0;
                    let freeDist = 0;
                    let payableDist = km;
                    if (plan.discount_type === 'Percentage' && discVal >= 100) {
                        if (minDist > 0 && km > minDist) {
                            freeDist = minDist;
                            payableDist = Math.max(0, km - minDist);
                        } else {
                            freeDist = km;
                            payableDist = 0;
                        }
                    } else if (amt > 0) {
                        freeDist = (parseFloat(res.data.discount_amount) / amt) * km;
                        payableDist = Math.max(0, km - freeDist);
                    }
                    
                    let tagText = plan.plan_name;
                    if (discVal >= 100 && minDist > 0 && km > minDist) {
                        tagText += ` (Free up to ${minDist} KM, -Rs ${parseFloat(res.data.discount_amount).toFixed(2)})`;
                    } else {
                        tagText += ` (-Rs ${parseFloat(res.data.discount_amount).toFixed(2)})`;
                    }

                    $row.find(".f_totaldistance_input").val(km);
                    $row.find(".f_freedistance_input").val(freeDist);
                    $row.find(".f_payabledistance_input").val(payableDist);
                    $row.find(".f_narration_input").val(tagText);

                    const infoHtml = `<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fa fa-tag me-1"></i>${escapeHtml(tagText)}</span> <button type="button" class="btn btn-link btn-sm text-danger p-0 ms-1 text-decoration-none" onclick="clearRowFreightDiscount($(this).closest('tr'))" title="Clear Discount"><i class="fa fa-times-circle"></i> Clear</button>`;
                    $row.find(".f_discount_info").html(infoHtml).show();
                }
            } else {
                // No discount plan matched
                const disc = parseFloat($row.find(".f_disc_input").val()) || 0;
                const payable = amt - (amt * (disc / 100));
                $row.find(".f_payable_input").val(payable.toFixed(2));
                $row.find(".f_totaldistance_input").val(km);
                $row.find(".f_freedistance_input").val(0);
                $row.find(".f_payabledistance_input").val(km);
                $row.find(".f_narration_input").val("");
                $row.find(".f_discount_info").hide().empty();
            }
            calculateGrandTotalFreight();
            renderFreightEligibilityRules(false);
            updateDiscountRemarksNarration();
        });
    };

    function autoCalculateRowFreight($row, projectid, rooturl) {
         $("#spanratedbl").html("");
         $("#spanratedbl").addClass("d-none");
         if ($row.attr('data-user-selected') === 'true') return;

        const km = parseFloat($row.find(".f_distance_info").val()) || 0;
        const totalQty = parseFloat($row.data("totalqty")) || 0;

        if (!km || km <= 0) {
            $row.find(".auto-vehicle-badge").html('<i class="fa fa-exclamation-circle me-1"></i>No Distance').removeClass('bg-info-subtle text-info border-info-subtle bg-success-subtle text-success border-success-subtle').addClass('bg-warning-subtle text-warning border-warning-subtle');
            return;
        }

        var cleanRoot = rooturl || '';
        if (cleanRoot.endsWith('/')) {
            cleanRoot = cleanRoot.substring(0, cleanRoot.length - 1);
        }
        
        var subPath = "/krf-orderandsales/modal/vehicle_modal.php?action=calculateFreight";
        if (cleanRoot.indexOf('krf-orderandsales') !== -1) {
            subPath = "/modal/vehicle_modal.php?action=calculateFreight";
        }
        var ajaxUrl = cleanRoot + subPath;
        $.ajax({
            url: ajaxUrl,
            type: "POST",
            data: { km: km, projectid: projectid, total_qty: totalQty },
            dataType: "JSON",
            success: function(res) {
                if ($row.attr('data-user-selected') === 'true') return;

                if (res.status === 'success' && res.best_vehicle && parseFloat(res.recommended_charge) > 0) {
                    let charge = parseFloat(res.recommended_charge) || 0;
                    if ($('input[name="pickupfrom"]:checked').val() === "delivered" && res.calcType && res.calcType.toLowerCase() === "rate") {
                        charge = charge * 2;
                        $("#spanratedbl").html("Freight is calculated for both onward and return journeys.");
                        $("#spanratedbl").removeClass("d-none");
                    }
                    const v = res.best_vehicle;
                    const vehicleText = v.vehiclename + (v.vehicleno ? ' (' + v.vehicleno + ')' : '');
                    
                    $row.find(".f_amt_input").val(charge.toFixed(2));
                    $row.find(".f_vehicle_info").val(vehicleText);
                    
                    let badgeText = `<i class="fa fa-truck me-1"></i>${v.vehiclename}`;
                    if (res.trips_needed > 1) {
                        badgeText += ` (x${res.trips_needed} trips)`;
                    }
                    $row.find(".auto-vehicle-badge").html(badgeText).removeClass('bg-info-subtle text-info border-info-subtle bg-warning-subtle text-warning border-warning-subtle').addClass('bg-success-subtle text-success border-success-subtle');

                    applyRowFreightDiscount($row);
                } else if (res.status === 'success' && res.suggestions && res.suggestions.some(v => parseFloat(v.charge) > 0)) {
                    const validSug = res.suggestions.filter(v => parseFloat(v.charge) > 0);
                    const v = validSug[0];
                    const charge = parseFloat(v.charge) || 0;
                    $row.find(".f_amt_input").val(charge.toFixed(2));
                    $row.find(".f_vehicle_info").val(v.vehiclename);
                    $row.find(".auto-vehicle-badge").html(`<i class="fa fa-truck me-1"></i>${v.vehiclename}`).removeClass('bg-info-subtle text-info border-info-subtle bg-warning-subtle text-warning border-warning-subtle').addClass('bg-primary-subtle text-primary border-primary-subtle');

                    applyRowFreightDiscount($row);
                } else {
                    $row.find(".auto-vehicle-badge").html('<i class="fa fa-exclamation-triangle me-1"></i>No rate defined').removeClass('bg-info-subtle text-info border-info-subtle bg-success-subtle text-success border-success-subtle').addClass('bg-warning-subtle text-warning border-warning-subtle');
                    applyRowFreightDiscount($row);
                }
            },
            error: function() {
                $row.find(".auto-vehicle-badge").html('<i class="fa fa-times me-1"></i>Error').removeClass('bg-info-subtle text-info border-info-subtle bg-success-subtle text-success border-success-subtle').addClass('bg-danger-subtle text-danger border-danger-subtle');
            }
        });
    }

    window.openFreightRowCalculator = function(btn, distance, totalQty) {
        const $row = $(btn).closest("tr");
        const projectid = '<?php echo $projectid; ?>';
        const rooturl = '<?php echo $rooturl; ?>';

        openFreightCalculator(distance, projectid, rooturl, function(data) {
            let chargeVal = parseFloat(data.charge || 0);
            const isDelivered = ($('input[name="pickupfrom"]:checked').val() || $("#orderDeliveryFreightForm input[name='pickupfrom']:checked").val() || 'delivered') === "delivered";
            const isRate = (data.calculationtype && data.calculationtype.toLowerCase() === "rate") || (data.calcType && data.calcType.toLowerCase() === "rate");

            if (isDelivered && isRate) {
                $("#spanratedbl").html("Freight is calculated for both onward and return journeys.");
                $("#spanratedbl").removeClass("d-none");
            } else {
                $("#spanratedbl").html("").addClass("d-none");
            }

            $row.attr('data-user-selected', 'true');
            $row.find(".f_amt_input").val(chargeVal.toFixed(2)).trigger('input').trigger('change');
            $row.find(".f_vehicle_info").val(data.vehiclename || '');
            $row.find(".f_distance_info").val(data.calc_km || '');
            $row.find(".auto-vehicle-badge").html(`<i class="fa fa-truck me-1"></i>${data.vehiclename}`).removeClass('bg-info-subtle text-info border-info-subtle bg-warning-subtle text-warning border-warning-subtle').addClass('bg-success-subtle text-success border-success-subtle');
            
            applyRowFreightDiscount($row);

            iziToast.success({ 
                title: 'Estimated', 
                message: `Freight calculated for ${data.vehiclename} (${data.calc_km} KM): Rs ${chargeVal.toFixed(2)}`,
                position: 'bottomRight'
            });
        });
    }

    // Freight Table Calculations
    $(document).on("input change keyup blur", ".f_amt_input", function() {
        const $row = $(this).closest("tr");
        applyRowFreightDiscount($row);
    });

    $(document).on("input change keyup blur", ".f_disc_input", function() {
        const loginRoleId = parseInt($('body').data('loginroleid') || $('#hidden_loginroleid').val() || '<?php echo (int)($loginroleid ?? ($_SESSION['loginroleid'] ?? 0)); ?>') || 0;
        if (loginRoleId > 3 && $(this).prop('readonly')) {
            return;
        }
        const $row = $(this).closest("tr");
        const amt = parseFloat($row.find(".f_amt_input").val()) || 0;
        let disc = parseFloat($row.find(".f_disc_input").val()) || 0;
        if (disc > 100) {
            disc = 100;
            $row.find(".f_disc_input").val(100);
        }
        
        const payable = amt - (amt * (disc / 100));
        $row.find(".f_payable_input").val(payable.toFixed(2));

        calculateGrandTotalFreight();
    });

    window.calculateGrandTotalFreight = function() {
        let totalAmt = 0;
        let totalPayable = 0;

        $(".f_amt_input").each(function() { totalAmt += parseFloat($(this).val()) || 0; });
        $(".f_payable_input").each(function() { totalPayable += parseFloat($(this).val()) || 0; });

        $("#total_freight_amt").text(totalAmt.toFixed(2));
        $("#total_payable_freight_amt").text(totalPayable.toFixed(2));

        // Dynamically update the overall order payable grand total
        let itemsPayable = parseFloat($("#grandtotalamount").text()) || 0;
        if (!itemsPayable) {
            $(".payableamount_td").each(function() {
                itemsPayable += parseFloat($(this).val()) || 0;
            });
        }
        let finalGrandTotal = itemsPayable + totalPayable;
        $("#grandtotalamount_final").text(finalGrandTotal.toFixed(2));
    };

    // 📍 Auto-populate Full Address from individual inputs in real-time
    $(document).on("input change", "input[name='delivery_state[]'], input[name='delivery_district[]'], input[name='delivery_city[]'], input[name='delivery_pincode[]']", function() {
        const $row = $(this).closest("tr");
        const state = $row.find("input[name='delivery_state[]']").val().trim();
        const district = $row.find("input[name='delivery_district[]']").val().trim();
        const city = $row.find("input[name='delivery_city[]']").val().trim();
        const pincode = $row.find("input[name='delivery_pincode[]']").val().trim();

        let addressParts = [];
        // Following the sequence: State, District, City, Pincode
        if (state) addressParts.push(state);
        if (district) addressParts.push(district);
        if (city) addressParts.push(city);
        if (pincode) addressParts.push(pincode);
        
        let fullAddress = addressParts.join(", ");
        $row.find("input[name='delivery_fulladdress[]']").val(fullAddress);
    });
  });
</script>

<script>
  function cleanNumber(val) {
    if (typeof val === 'number') return isNaN(val) ? 0 : val;
    if (!val) return 0;
    let str = String(val).replace(/,/g, '').trim();
    let num = parseFloat(str);
    return isNaN(num) ? 0 : num;
  }
  window.cleanNumber = cleanNumber;

  function getEffectiveLoginRoleId() {
    if (typeof window.CURRENT_USER_ROLE_ID !== 'undefined') {
      let r = parseInt(window.CURRENT_USER_ROLE_ID, 10);
      if (!isNaN(r)) return r;
    }
    let bodyRole = $('body').data('loginroleid');
    if (bodyRole !== undefined && bodyRole !== null && bodyRole !== '') {
      let r = parseInt(bodyRole, 10);
      if (!isNaN(r)) return r;
    }
    let hiddenRole = $('#hidden_loginroleid').val();
    if (hiddenRole !== undefined && hiddenRole !== null && hiddenRole !== '') {
      let r = parseInt(hiddenRole, 10);
      if (!isNaN(r)) return r;
    }
    return 99;
  }
  window.getEffectiveLoginRoleId = getEffectiveLoginRoleId;

  function escapeAttr(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  }
  window.escapeAttr = escapeAttr;

  // mobileno
  // clientname
  document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".expecteddelivery").forEach(function (input) {
      input.removeAttribute("min");
      input.removeAttribute("max");
      input.removeAttribute("readonly");
      input.style.pointerEvents = "";
      input.style.backgroundColor = "";
      input.removeAttribute("tabindex");
    });
  });
  $(document).on("click", "#isadddeliveryaddress", function () {
    $("#adddeliveryaddressModal").modal("show");
    // $("#adddeliveryaddressModal").modal("hide");
    // if($(this).is(":checked")){
    // }
  });


  // Global function called from the Iframe in locatioinaddressget_api.php
  window.appendAddressRow = function (data) {
    if (!data || !data.full_address) {
      Swal.fire('Incomplete Data', 'Delivery address is required', 'warning');
      return false;
    }
    // console.log(data);
    const latinput = data.lat || "";
    const longinput = data.lon || "";
    const statename = data.state || "";
    const districtname = data.district || "";
    const cityname = data.city || "";
    const pincode = data.pincode || "";
    const fulladdress = data.full_address || "";
    const locationIds = findStateDistrictIds(statename, districtname);

    const stateid = locationIds.stateid;
    const districtid = locationIds.districtid;


    let productOptionsHtml = '<option value="all">All Products</option>';
    $("#provisionorderitemsTable tbody tr").each(function () {
      const $productSelect = $(this).find("select[name='productname[]'], select[name='productname']");
      if (!$productSelect.length) return;
      const productId = $productSelect.val();
      if (!productId) return;
      const productText = $productSelect.find("option:selected").text();
      // Prevent duplicate options
      if (!productOptionsHtml.includes(`value="${productId}"`)) {
        productOptionsHtml += `<option value="${productId}">${productText}</option>`;
      }
    });

    const $newRow = $(`
        <tr class="align-middle">
            <td>
                <select name="delivery_productid[]" class="form-select select2 delivery_product_select">${productOptionsHtml}</select>
            </td>
            <td>
                <input type="text" name="delivery_qty[]" class="form-control delivery_qty_input" placeholder="0" onKeyPress="return isFloatNumber(event,this);" required />
            </td>
            <td>
                <input type="text" name="delivery_distance[]" class="form-control delivery_distance_input" value="${data.calc_km || data.distance_km || 0}" onKeyPress="return isFloatNumber(event,this);" />
            </td>
            <td class="d-none">
                <input type="hidden" name="delivery_latlong[]" value="${latinput},${longinput}" />
            </td>         

            <td>
                  <select
                      class="form-control del_state"
                      name="del_state[]"
                      required
                  >
                      ${getStateOptions(stateid)}
                  </select>

                  <input type="hidden"
                         class="delivery_state"
                         name="delivery_state[]"
                         value="${statename}">

                  <input type="hidden"
                         class="delivery_stateid"
                         name="delivery_stateid[]"
                         value="${stateid}">
            </td>
            <td>
                <select
                    class="form-control del_district"
                    name="del_district[]"
                    required
                >
                    ${getDistrictOptions(stateid, districtid)}
                </select>

                <input type="hidden"
                       class="delivery_district"
                       name="delivery_district[]"
                       value="${districtname}">

                <input type="hidden"
                       class="delivery_district_id"
                       name="delivery_district_id[]"
                       value="${districtid}">
            </td>
            <td>
                <input type="text" name="delivery_city[]" class="form-control" value="${cityname}">
            </td>
            <td>
                <input type="text" name="delivery_pincode[]" class="form-control" value="${pincode}">
            </td>
            <td>
                <input type="text" name="delivery_fulladdress[]" class="form-control" value="${fulladdress}">
            </td>
            <td class="text-center">
                <div class="d-flex gap-1 justify-content-center">
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="copyDeliveryRow(this)" title="Copy Row">
                        <i class="fa fa-copy"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger rmdeliveryaddbtn">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `);

    // Append the row immediately to the table
    $("#deliveryaddresstbl tbody").append($newRow);

    // Initialize Select2 for the new product dropdown
    $newRow.find('.select2').select2({
      width: '100%',
      placeholder: 'Select Product'
    });

    // SweetAlert confirmation for adding more addresses
    Swal.fire({
      title: "Address Added Successfully!",
      text: "Would you like to add another delivery address to this order?",
      icon: "success",
      showCancelButton: true,
      confirmButtonText: "Yes, Add More",
      cancelButtonText: "No, I'm Done",
      customClass: {
        confirmButton: 'btn btn-primary px-4 me-2',
        cancelButton: 'btn btn-outline-secondary px-4'
      },
      buttonsStyling: false
    }).then((result) => {
      if (result.isConfirmed) {
        // 1. Clear parent window coordinate inputs
        $("#latlonginput, #latinput, #longinput").val("");
        
        // 2. Reset the form inside the Iframe
        const iframe = document.getElementById("location-iframe");
        if (iframe && iframe.contentWindow && typeof iframe.contentWindow.resetForm === 'function') {
          iframe.contentWindow.resetForm();
        }

        // 3. Optional: Scroll the modal back to top if needed
        $("#adddeliveryaddressModal .modal-body").animate({ scrollTop: 0 }, 300);
      } else {
        $("#adddeliveryaddressModal").modal("hide");
        Swal.fire({
          title: "All Set!",
          text: "Delivery details have been updated.",
          icon: "info",
          timer: 1000,
          showConfirmButton: false
        });
      }
    });
  };


  // Check & Update Step 1 "Next" Button Disabled State
  window.updateStep1NextButtonState = function() {
    const $nextBtn = $('#step1 .next-step, button[data-next="2"]');
    if (!$nextBtn.length) return;

    const mobileVal = ($('#mobileno').val() || '').trim();
    const isMobileValid = /^[6-9]\d{9}$/.test(mobileVal) && !$('#mobileno').hasClass('is-invalid');

    const nameVal = ($('#clientname').val() || '').trim();
    const isNameValid = (nameVal.length >= 3) &&
      !/^[0-9]/.test(nameVal) &&
      /^[A-Za-z\u0900-\u097F]/.test(nameVal) &&
      /^[A-Za-z\u0900-\u097F][A-Za-z0-9\u0900-\u097F\s\.\'\-\_\(\)\/\&\,\#]*$/.test(nameVal) &&
      ((nameVal.match(/[A-Za-z\u0900-\u097F]/g) || []).length >= 2) &&
      !$('#clientname').hasClass('is-invalid');

    const stateVal = ($('#statename').val() || '').trim();
    const isStateValid = stateVal !== '' && !$('#statename').hasClass('is-invalid');

    const districtVal = ($('#districtname').val() || '').trim();
    const isDistrictValid = districtVal !== '' && !$('#districtname').hasClass('is-invalid');

    const cityVal = ($('#cityname').val() || '').trim();
    const isCityValid = cityVal.length > 0 && !$('#cityname').hasClass('is-invalid');

    const emailVal = ($('#emailid').val() || '').trim();
    const isEmailValid = (emailVal === '' || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) && !$('#emailid').hasClass('is-invalid');

    // Check if any field inside step 1 is marked as invalid
    const hasAnyInvalid = $('#step1 .is-invalid').length > 0;

    const isStep1Complete = isMobileValid && isNameValid && isStateValid && isDistrictValid && isCityValid && isEmailValid && !hasAnyInvalid;

    if (isStep1Complete) {
      $nextBtn.prop('disabled', false).removeClass('disabled').removeAttr('title');
    } else {
      $nextBtn.prop('disabled', true).addClass('disabled').attr('title', 'Please resolve any invalid fields and complete all required client information to proceed');
    }
  };

  // Live Search & Validation for Mobile Number
  window.validateMobileNumber = function() {
    const $mobileInput = $('#mobileno');
    const $msgDiv = $('#mobileno-validation-msg');
    const val = $mobileInput.val().trim();
    const mobileRegex = /^[6-9]\d{9}$/;

    if (val === '') {
      $mobileInput.removeClass('is-invalid is-valid');
      $msgDiv.empty().removeClass('text-danger text-success');
      if (typeof window.updateStep1NextButtonState === 'function') window.updateStep1NextButtonState();
      return false;
    }

    if (mobileRegex.test(val)) {
      $mobileInput.removeClass('is-invalid').addClass('is-valid');
      $msgDiv.removeClass('text-danger').addClass('text-success')
             .html('<i class="fa fa-check-circle me-1"></i> Valid mobile number');
      if (typeof window.updateStep1NextButtonState === 'function') window.updateStep1NextButtonState();
      return true;
    } else {
      $mobileInput.removeClass('is-valid').addClass('is-invalid');
      $msgDiv.removeClass('text-success').addClass('text-danger');
      if (val.length < 10) {
        $msgDiv.html('<i class="fa fa-exclamation-triangle me-1"></i> Mobile number must be 10 digits (entered: ' + val.length + ')');
      } else if (!/^[6-9]/.test(val)) {
        $msgDiv.html('<i class="fa fa-exclamation-triangle me-1"></i> Mobile number must start with 6, 7, 8, or 9');
      } else {
        $msgDiv.html('<i class="fa fa-exclamation-triangle me-1"></i> Invalid mobile number');
      }
      if (typeof window.updateStep1NextButtonState === 'function') window.updateStep1NextButtonState();
      return false;
    }
  };

  // Live Validation for Client Name
  window.validateClientName = function() {
    const $nameInput = $('#clientname');
    const $msgDiv = $('#clientname-validation-msg');
    const val = $nameInput.val().trim();

    if (val === '') {
      $nameInput.removeClass('is-invalid is-valid');
      $msgDiv.empty().removeClass('text-danger text-success');
      if (typeof window.updateStep1NextButtonState === 'function') window.updateStep1NextButtonState();
      return false;
    }

    if (val.length < 3) {
      $nameInput.removeClass('is-valid').addClass('is-invalid');
      $msgDiv.removeClass('text-success').addClass('text-danger')
             .html('<i class="fa fa-exclamation-triangle me-1"></i> Client name must be at least 3 characters (entered: ' + val.length + ')');
      if (typeof window.updateStep1NextButtonState === 'function') window.updateStep1NextButtonState();
      return false;
    }

    // Must not start with a number (e.g. 0-9)
    if (/^[0-9]/.test(val)) {
      $nameInput.removeClass('is-valid').addClass('is-invalid');
      $msgDiv.removeClass('text-success').addClass('text-danger')
             .html('<i class="fa fa-exclamation-triangle me-1"></i> Client name cannot start with a number (numbers allowed as suffix)');
      if (typeof window.updateStep1NextButtonState === 'function') window.updateStep1NextButtonState();
      return false;
    }

    // Must start with a letter (English or Hindi)
    if (!/^[A-Za-z\u0900-\u097F]/.test(val)) {
      $nameInput.removeClass('is-valid').addClass('is-invalid');
      $msgDiv.removeClass('text-success').addClass('text-danger')
             .html('<i class="fa fa-exclamation-triangle me-1"></i> Client name must start with a letter');
      if (typeof window.updateStep1NextButtonState === 'function') window.updateStep1NextButtonState();
      return false;
    }

    // Allowed: Letters, spaces, dots, hyphens, apostrophes, underscores, (), /, &, comma, hash, and numbers as suffix/middle
    const nameRegex = /^[A-Za-z\u0900-\u097F][A-Za-z0-9\u0900-\u097F\s\.\'\-\_\(\)\/\&\,\#]*$/;
    if (!nameRegex.test(val)) {
      $nameInput.removeClass('is-valid').addClass('is-invalid');
      $msgDiv.removeClass('text-success').addClass('text-danger')
             .html('<i class="fa fa-exclamation-triangle me-1"></i> Client name contains invalid special characters');
      if (typeof window.updateStep1NextButtonState === 'function') window.updateStep1NextButtonState();
      return false;
    }

    const lettersOnly = (val.match(/[A-Za-z\u0900-\u097F]/g) || []).length;
    if (lettersOnly < 2) {
      $nameInput.removeClass('is-valid').addClass('is-invalid');
      $msgDiv.removeClass('text-success').addClass('text-danger')
             .html('<i class="fa fa-exclamation-triangle me-1"></i> Client name must contain letters');
      if (typeof window.updateStep1NextButtonState === 'function') window.updateStep1NextButtonState();
      return false;
    }

    $nameInput.removeClass('is-invalid').addClass('is-valid');
    $msgDiv.removeClass('text-danger').addClass('text-success')
           .html('<i class="fa fa-check-circle me-1"></i> Valid client name');
    if (typeof window.updateStep1NextButtonState === 'function') window.updateStep1NextButtonState();
    return true;
  };

  $(document).ready(function () {
    const $mobileInput = $('#mobileno');
    const $resultsDiv = $('#mobileno-results');
    const $nameInput = $('#clientname');
    const $nameResultsDiv = $('#clientname-results');
    let searchTimeout;
    let nameSearchTimeout;

    function renderClientResults(data, $targetDiv) {
      if (data && data.length > 0) {
        let html = '';
        data.forEach(function (item) {
          html += `
            <div class="search-result-item"
               data-clientid="${escapeAttr(item.client_id || '')}"
               data-clientname="${escapeAttr(item.client_name || '')}"
               data-mobileno="${escapeAttr(item.phones || '')}"
               data-emailaddress="${escapeAttr(item.client_email || '')}"
               data-address="${escapeAttr(item.invoicing_address || '')}"
               data-statename="${escapeAttr(item.statename || '')}"
               data-stateid="${escapeAttr(item.stateid || '')}"
               data-districtname="${escapeAttr(item.districtname || '')}"
               data-districtid="${escapeAttr(item.districtid || '')}"
               data-tahseel="${escapeAttr(item.tahseel || '')}"
               data-cityname="${escapeAttr(item.cityname || '')}"
               data-isdealer="${escapeAttr(item.is_dealer || '0')}"
               data-customertype="${(parseInt(item.is_dealer) === 1 || item.is_dealer === '1') ? 'Dealer' : 'Farmer'}">
            <div class="search-result-name">${escapeHtml(item.client_name || '')}</div>
            <div class="search-result-meta">
              <span><i class="fa fa-phone"></i> ${item.phones || 'N/A'}</span>
              <span><i class="fa fa-map-marker-alt"></i> ${item.districtname || item.cityname || item.statename || ''}</span>
            </div>
            <div class="search-result-address">${item.invoicing_address || ''}</div>
          </div>`;
        });
        $targetDiv.html(html).show();
      } else {
        $targetDiv.hide().empty();
      }
    }

    function setupKeyboardNav($input, $container) {
      $input.on('keydown', function (e) {
        const $items = $container.find('.search-result-item');
        let $active = $container.find('.search-result-item.active');

        if (e.key === "ArrowDown") {
          e.preventDefault();
          if (!$items.length) return;
          if (!$active.length) {
            $items.first().addClass('active');
          } else {
            $active.removeClass('active').next().addClass('active');
            if (!$container.find('.search-result-item.active').length) {
              $items.first().addClass('active');
            }
          }
          const newActive = $container.find('.search-result-item.active');
          if (newActive.length) {
            newActive[0].scrollIntoView({ block: 'nearest' });
          }
        } else if (e.key === "ArrowUp") {
          e.preventDefault();
          if (!$items.length) return;
          if (!$active.length) {
            $items.last().addClass('active');
          } else {
            $active.removeClass('active').prev().addClass('active');
            if (!$container.find('.search-result-item.active').length) {
              $items.last().addClass('active');
            }
          }
          const newActive = $container.find('.search-result-item.active');
          if (newActive.length) {
            newActive[0].scrollIntoView({ block: 'nearest' });
          }
        } else if (e.key === "Enter") {
          if ($active.length) {
            e.preventDefault();
            $active.trigger('click');
          }
        } else if (e.key === "Escape") {
          $container.hide();
        }
      });
    }

    setupKeyboardNav($mobileInput, $resultsDiv);
    setupKeyboardNav($nameInput, $nameResultsDiv);

    $mobileInput.on('input keyup change blur', function() {
      window.validateMobileNumber();
    });

    $nameInput.on('input keyup change blur', function() {
      window.validateClientName();
    });

    $('#emailid').on('input keyup change blur', function() {
      const val = $(this).val().trim();
      if (val === '') {
        $(this).removeClass('is-invalid is-valid');
      } else if (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
        $(this).removeClass('is-invalid').addClass('is-valid');
      } else {
        $(this).removeClass('is-valid').addClass('is-invalid');
      }
      if (typeof window.updateStep1NextButtonState === 'function') {
        window.updateStep1NextButtonState();
      }
    });

    $('#cityname').on('input keyup change', function() {
      if ($(this).val().trim().length > 0) {
        $(this).removeClass('is-invalid');
      }
      if (typeof window.updateStep1NextButtonState === 'function') {
        window.updateStep1NextButtonState();
      }
    });

    $('#cityname').on('blur', function() {
      if ($(this).val().trim().length === 0) {
        $(this).addClass('is-invalid');
      } else {
        $(this).removeClass('is-invalid');
      }
      if (typeof window.updateStep1NextButtonState === 'function') {
        window.updateStep1NextButtonState();
      }
    });

    $('#statename, #districtname').on('change select2:select select2:clear', function() {
      if ($(this).val() && String($(this).val()).trim() !== '') {
        $(this).removeClass('is-invalid');
      }
      if (typeof window.updateStep1NextButtonState === 'function') {
        window.updateStep1NextButtonState();
      }
    });

    $('#tahseel, #delivery_address').on('input keyup change blur', function() {
      if (typeof window.updateStep1NextButtonState === 'function') {
        window.updateStep1NextButtonState();
      }
    });

    // Check step 1 Next button state on initial load
    if (typeof window.updateStep1NextButtonState === 'function') {
      window.updateStep1NextButtonState();
    }
    setTimeout(function() {
      if (typeof window.updateStep1NextButtonState === 'function') {
        window.updateStep1NextButtonState();
      }
    }, 300);
    setTimeout(function() {
      if (typeof window.updateStep1NextButtonState === 'function') {
        window.updateStep1NextButtonState();
      }
    }, 1200);

    // Disable browser native autofill suggestions dynamically
    $('#salesOrderForm input, #salesOrderForm textarea').attr({
      'autocomplete': 'one-time-code',
      'autocorrect': 'off',
      'autocapitalize': 'off',
      'spellcheck': 'false'
    });

    // Live search by Mobile Number
    $mobileInput.on('input', function () {
      const term = $(this).val().trim();
      clearTimeout(searchTimeout);

      if (term.length < 3) {
        $resultsDiv.hide().empty();
        return;
      }

      searchTimeout = setTimeout(function () {
        const routurl = $("body").data("routurl") || "../";
        const companyid = $("body").data("companyid");
        const subcompanyid = $("body").data("subcompanyid");
        const projectid = $("body").data("projectid");

        $.ajax({
          url: routurl + "krf-orderandsales/modal/client_modal.php?action=clientDetailsFrommaster",
          type: 'POST',
          dataType: 'json',
          data: {
            companyid: companyid,
            subcompanyid: subcompanyid,
            projectid: projectid,
            sortby: 'mobileno',
            sortbyvalue: term
          },
          success: function (data) {
            renderClientResults(data, $resultsDiv);
          }
        });
      }, 300);
    });

    // Live search by Client Name
    $nameInput.on('input', function () {
      const term = $(this).val().trim();
      clearTimeout(nameSearchTimeout);

      if (term.length < 2) {
        $nameResultsDiv.hide().empty();
        return;
      }

      nameSearchTimeout = setTimeout(function () {
        const routurl = $("body").data("routurl") || "../";
        const companyid = $("body").data("companyid");
        const subcompanyid = $("body").data("subcompanyid");
        const projectid = $("body").data("projectid");

        $.ajax({
          url: routurl + "krf-orderandsales/modal/client_modal.php?action=clientDetailsFrommaster",
          type: 'POST',
          dataType: 'json',
          data: {
            companyid: companyid,
            subcompanyid: subcompanyid,
            projectid: projectid,
            sortby: 'searchclientname',
            sortbyvalue: term
          },
          success: function (data) {
            renderClientResults(data, $nameResultsDiv);
          }
        });
      }, 300);
    });

    function escapeAttr(value) {
      return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
    }

    // Handle selection from custom results
    $(document).on('click', '.search-result-item', function () { 
      const $item = $(this);
      handleExistingClientSelection($item[0]);
      $resultsDiv.hide().empty();
      $nameResultsDiv.hide().empty();
      if (typeof window.validateClientName === 'function') window.validateClientName();
      if (typeof window.validateMobileNumber === 'function') window.validateMobileNumber();
      if (typeof window.updateStep1NextButtonState === 'function') window.updateStep1NextButtonState();
      setTimeout(function() {
        if (typeof window.validateClientName === 'function') window.validateClientName();
        if (typeof window.validateMobileNumber === 'function') window.validateMobileNumber();
        if (typeof window.updateStep1NextButtonState === 'function') window.updateStep1NextButtonState();
      }, 350);
      setTimeout(function() {
        if (typeof window.updateStep1NextButtonState === 'function') window.updateStep1NextButtonState();
      }, 1000);
    });

    // Close results when clicking outside
    $(document).on('click', function (e) {
      if (!$(e.target).closest('.position-relative').length) {
        $resultsDiv.hide();
        $nameResultsDiv.hide();
      }
    });
  });

  // Remove old blur handlers and results grid generation
  // (Removed #mobileno blur and #clientname blur logic)

  // ===============================
  // Click Event
  // ===============================
  $(document).on("change", "input[name='existclient']", function () {
    $(".client-selection-card").removeClass("selected");
    if ($(this).is(":checked")) {
      $(this).closest(".client-selection-card").addClass("selected");
    }
    handleExistingClientSelection(this);
    if (typeof window.validateClientName === 'function') window.validateClientName();
    if (typeof window.validateMobileNumber === 'function') window.validateMobileNumber();
    if (typeof window.updateStep1NextButtonState === 'function') window.updateStep1NextButtonState();
    setTimeout(function() {
      if (typeof window.validateClientName === 'function') window.validateClientName();
      if (typeof window.validateMobileNumber === 'function') window.validateMobileNumber();
      if (typeof window.updateStep1NextButtonState === 'function') window.updateStep1NextButtonState();
    }, 400);
  });

  // ===============================
  // Auto Apply On Page Load / Modal Open
  // ===============================
  $(document).ready(function () {
    applyCheckedExistingClient();
  });

  // ===============================
  // Remove Delivery Address Row
  // ===============================
  $(document).on("click", ".rmdeliveryaddbtn", function () {
    $(this).closest("tr").remove();
  });

 

  var globalProductOptions = "";
  GetProductListFrommaster_callback(function (res) {
    if (res.length > 0) {
      globalProductOptions = '<option value="" selected disabled><?php echo _t('select'); ?></option>';
      for (var i = 0; i < res.length; i++) {
        var rawName = res[i].ProductName || '';
        var displayName = rawName.replace(/^(NonGrafted|Non-Grafted)\s*[-:]?\s*/i, '');
        window.allProductsMap = window.allProductsMap || {};
        var isGrafted = res[i].IsGrafted || (res[i].sku && res[i].sku.toUpperCase().includes('NONGRAFTED') ? 'NonGrafted' : (res[i].sku && res[i].sku.toUpperCase().includes('GRAFTED') ? 'Grafted' : (rawName.match(/^Grafted/i) ? 'Grafted' : 'NonGrafted')));
        window.allProductsMap[res[i].ProductID] = res[i];
        window.allProductsMap[res[i].ProductID].isGrafted = isGrafted;
        globalProductOptions += '<option value="' + res[i].ProductID + '" data-priceofproduct="' + res[i].PriceOfProduct + '" data-isgrafted="' + isGrafted + '">' + displayName + '</option>';
      }
      $(".productname").each(function () {
        $(this).html(globalProductOptions);
      });

      // Initialize Select2 on page load
      $('.productname.select2').select2({
        placeholder: "Select Product",
        allowClear: true,
        width: '100%'
      });

      // If in edit mode, trigger order loading
      const isEditPage = $("#order_mode").val() === "edit";
      const editPsonoNumber = $("#edit_psono").val() || "";
      if (isEditPage && editPsonoNumber !== "") {
        loadEditProvisionalOrder(editPsonoNumber);
      }
    }
  });

  // ==========================================
  // UNIFIED EDIT MODE: LOAD PROVISIONAL ORDER
  // ==========================================
  function loadEditProvisionalOrder(psono) {
    if (!psono) return;
    if (typeof fetches_states === 'function') {
      fetches_states();
    }

    Swal.fire({
      title: 'Loading Order Details...',
      text: 'Fetching details for ' + psono,
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });

    const projectid = $("body").data("projectid") || $("#hidden_projectid").val() || "<?php echo $projectid; ?>";

    $.ajax({
      url: "modal/fetch_order_edit_data.php",
      type: "POST",
      data: {
        psono: psono,
        projectid: projectid
      },
      dataType: "json",
      success: function (res) {
        Swal.close();
        if (res.status !== 'success' || !res.data) {
          Swal.fire('Error', res.message || 'Could not load order details.', 'error');
          return;
        }

        const header = res.data.header || {};
        const client = res.data.client || {};
        const items = res.data.items || [];
        const delivery = res.data.delivery || [];
        const freight = res.data.freight || {};

        // 1. POPULATE STEP 1: CLIENT DETAILS (Fetched from karjeefarm.krf_client_master as per clientid)
        const cId = client.client_id || header.clientid || "";
        const cName = client.client_name || header.clientname || "";
        const cPhone = client.phones || header.mobileno || "";
        const cEmail = client.client_email || header.emailid || "";
        const cAddress = header.delivery_address || client.invoicing_address || "";
        const cState = client.statename || header.statename || "";
        const cDistrict = client.districtname || header.districtname || "";
        const cCity = client.cityname || "";
        const cTahseel = client.tahseel || "";
        const cStateId = client.stateid || "";
        const cDistrictId = client.districtid || "";

        $("#clientid").val(cId);
        $("#clientname").val(cName);
        $("#mobileno").val(cPhone);
        $("#emailid").val(cEmail);
        $("#delivery_address").val(cAddress).trigger('input');
        $("#cityname").val(cCity);
        $("#tahseel").val(cTahseel);
        $("#stateid").val(cStateId);
        $("#districtid").val(cDistrictId);

        // Immediate option pre-fill so Select2 displays selected State & District immediately
        if (cState) {
          if ($("#statename option[value='" + cState + "']").length === 0) {
            $("#statename").append(new Option(cState, cState, true, true));
          }
          $("#statename").val(cState).trigger('change.select2');
        }
        if (cDistrict) {
          if ($("#districtname option[value='" + cDistrict + "']").length === 0) {
            $("#districtname").append(new Option(cDistrict, cDistrict, true, true));
          }
          $("#districtname").val(cDistrict).trigger('change.select2');
        }

        // Full load of states and districts with active selection
        if (typeof loadMasterStates === 'function') {
          loadMasterStates(cState, cDistrict);
        }

        if (typeof window.validateMobileNumber === 'function') {
          window.validateMobileNumber();
        }

        // 2. POPULATE STEP 2: ITEMS & PRODUCTS
        const populateItemsWhenReady = function (retries = 0) {
          if ((!globalProductOptions || globalProductOptions === "") && retries < 30) {
            setTimeout(() => populateItemsWhenReady(retries + 1), 100);
            return;
          }

          const $tbody = $("#provisionorderitemsTable tbody");
          $tbody.empty();

          if (items.length === 0) {
            $("#addRow").trigger("click");
          } else {
            items.forEach(function (item) {
              const stockOrderNo = item.stockorder_no || '';
              const origQty = parseFloat(item.quantity) || 0;
              const stockSowingDate = item.stock_sowingdate || '';
              const clientMentionId = item.resolved_clientid || item.clientid || item.stock_clientid || '';
              const clientMentionName = item.stock_clientname || header.clientname || '';
              let clientBadgeText = '';
              if (clientMentionName && clientMentionId) {
                clientBadgeText = `${clientMentionName} (${clientMentionId})`;
              } else if (clientMentionName) {
                clientBadgeText = clientMentionName;
              } else if (clientMentionId) {
                clientBadgeText = clientMentionId;
              }
              const isExistingItem = !!item.id;
              const sowingStatus = (item.sowing_schedule_status || 'pending').toLowerCase().trim();
              const isSelfStock = !!item.is_self_stock;
              const sowingStarted = !!item.sowing_started;
              const isSelfSown = (isSelfStock && sowingStarted);
              const sowingQtyLimit = parseInt(item.sowing_qty || item.quantity || 0);
              const stockClientId = item.stock_clientid || item.resolved_clientid || '';
              const isTrader = (parseInt(item.is_trader) === 1 || stockClientId === 'C00001');
              const isAllocatedFromRsoOrSo = /^(RSO-|SO-)/i.test(stockOrderNo.trim());
              const rowKey = 'item_row_' + (item.id || (Date.now() + '_' + Math.random().toString(36).substr(2, 6)));


              const rowHtml = `
                <tr class="hover-bg ${stockOrderNo ? 'has-stockorder' : ''}" 
                    data-row-key="${escapeAttr(rowKey)}"
                    data-orig-qty="${origQty}" 
                    data-stockorder-no="${escapeAttr(stockOrderNo)}"
                    data-stock-id="${escapeAttr(item.stock_id || '')}"
                    data-stock-clientid="${escapeAttr(stockClientId)}"
                    data-stock-clientname="${escapeAttr(clientMentionName)}"
                    data-stock-sowingdate="${escapeAttr(stockSowingDate)}"
                    data-sowing-status="${escapeAttr(sowingStatus)}"
                    data-is-self-sown="${isSelfSown ? '1' : '0'}"
                    data-sowing-qty="${sowingQtyLimit}"
                    data-dispatched-qty="${escapeAttr(item.dispatchedqty || 0)}"
                    data-is-trader="${isTrader ? '1' : '0'}"
                    data-item-id="${escapeAttr(item.id || '')}">
                  <td>
                    <input type="hidden" name="order_item_id[]" class="order_item_id" value="${escapeAttr(item.id || '')}" />
                    <select name="productname[]" class="form-select select2 productname" required>${globalProductOptions}</select>
                  </td>
                  <td>
                    <input type="text" name="rate[]" class="form-control invrowcell_rm ratecal rate_td"
                      onKeyPress="return isFloatNumber(event,this);" value="${item.rate || 0}" />
                  </td>
                  <td>
                    <input type="text" name="quantity[]" class="form-control invrowcell_rm qtycal qty_td"
                      onKeyPress="return isFloatNumber(event,this);" value="${item.quantity || 0}" required />
                  </td>
                  <td class="d-none">
                    <input type="text" name="taxableamount[]" class="form-control txableamount_td" value="${item.taxableamount || 0}" readonly />
                  </td>
                  <td class="d-none">
                    <input type="text" name="cgstprt[]" class="form-control cgstcal cgstprt_td" value="${item.cgst_percent || 0}" onKeyPress="return isFloatNumber(event,this);" />
                  </td>
                  <td class="d-none">
                    <input type="text" name="cgstamt[]" class="form-control cgstcal cgstamt_td" value="${item.cgst_amount || 0}" onKeyPress="return isFloatNumber(event,this);" />
                  </td>
                  <td class="d-none">
                    <input type="text" name="sgstprt[]" class="form-control sgstcal sgstprt_td" value="${item.sgst_percent || 0}" onKeyPress="return isFloatNumber(event,this);" />
                  </td>
                  <td class="d-none">
                    <input type="text" name="sgstamt[]" class="form-control sgstcal sgstamt_td" value="${item.sgst_amount || 0}" onKeyPress="return isFloatNumber(event,this);" />
                  </td>
                  <td class="d-none">
                    <input type="text" name="igstprt[]" class="form-control igstcal igstprt_td" value="${item.igst_percent || 0}" onKeyPress="return isFloatNumber(event,this);" />
                  </td>
                  <td class="d-none">
                    <input type="text" name="igstamt[]" class="form-control igstcal igstamt_td" value="${item.igst_amount || 0}" onKeyPress="return isFloatNumber(event,this);" />
                  </td>
                  <td>
                    <input type="text" name="distprt[]" class="form-control distcal distprt_td" value="${item.discount_percent || 0}" onKeyPress="return isFloatNumber(event,this);" />
                  </td>
                  <td>
                    <input type="text" name="distamt[]" class="form-control distcal distamt_td" value="${item.discount_amount || 0}" onKeyPress="return isFloatNumber(event,this);" />
                  </td>
                  <td>
                    <input type="text" name="total_amount[]" class="form-control total_amount_td bg-light fw-bold text-center" value="${item.amount || 0}" readonly />
                  </td>
                  <td>
                    <input type="text" name="amount[]" class="form-control payableamount payableamount_td bg-light fw-bold text-center" value="${item.amount || 0}" readonly />
                    <input type="hidden" name="roundoffamount[]" class="roundoffamount_td" value="${item.roundoffamount || '0.00'}" />
                  </td>
                  <td>
                    <input type="date" name="expecteddelivery[]" class="form-control expecteddelivery" value="${item.expecteddeliverydate || ''}" data-old-date="${item.expecteddeliverydate || ''}" min="<?php echo date('Y-m-d'); ?>" />
                    ${stockOrderNo ? `
                      <div class="mt-1 d-flex flex-wrap gap-1 align-items-center stockorder-badge-container">
                        <span class="badge bg-secondary font-monospace stockorder-badge" style="font-size:0.68rem;" title="Allocated Stock Order">
                          <i class="fa fa-layer-group me-1"></i>${stockOrderNo}
                        </span>
                        ${stockSowingDate ? `
                          <span class="badge bg-info text-dark font-monospace stock-sowingdate-badge" style="font-size:0.68rem;" title="Stock Sowing Date">
                            <i class="fa fa-calendar-alt me-1"></i>Sowing: ${stockSowingDate}
                          </span>
                        ` : ''}
                        ${clientBadgeText ? `
                          <span class="badge bg-primary-subtle text-primary-emphasis border border-primary-subtle font-monospace stock-client-badge" style="font-size:0.68rem;" title="Client Name & ID">
                            <i class="fa fa-user me-1"></i>${escapeAttr(clientBadgeText)}
                          </span>
                        ` : ''}
                        ${!isAllocatedFromRsoOrSo ? `
                          <span class="badge ${sowingStatus === 'pending' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger text-white'} font-monospace stock-schedule-badge" style="font-size:0.68rem;" title="Sowing Schedule Status: ${sowingStatus}">
                            <i class="fa ${sowingStatus === 'pending' ? 'fa-clock' : 'fa-lock'} me-1"></i>Sowing: ${sowingStatus}
                          </span>
                        ` : ''}
                        ${isSelfSown ? `
                          <span class="badge bg-warning text-dark font-monospace border border-warning" style="font-size:0.68rem;" title="Sown Qty: ${sowingQtyLimit}. Order Qty can be edited up to ${sowingQtyLimit} only.">
                            <i class="fa fa-seedling me-1 text-success"></i>Sown: ${sowingQtyLimit} (Editable &le; ${sowingQtyLimit})
                          </span>
                        ` : ''}
                      </div>
                    ` : ''}
                  </td>
                  <td class="text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm removeRow">
                      <i class="fa fa-trash-alt"></i>
                    </button>
                    <input type="hidden" name="stockdbid[]" class="form-control stockdbid" value="${item.stock_id || ''}" />
                  </td>
                  <td class="text-center d-none">
                    <input type="hidden" name="isreadytosale[]" class="form-control isreadytosale" value="${item.Qty_from_readysale || 0}" />
                  </td>
                </tr>
              `;
              const $row = $(rowHtml);
              $tbody.append($row);
              const $prodSelect = $row.find('.productname');
              $prodSelect.val(item.product_id).select2({ width: '100%', placeholder: 'Select Product' });

              // If existing item: disable product select so product name cannot be altered directly
              if (isExistingItem) {
                $prodSelect.prop('disabled', true);
                $row.find('td:first').append(`<input type="hidden" name="productname[]" class="locked_productname_val" value="${escapeAttr(item.product_id)}" />`);
              }

              // Apply role-based permissions (Sales Executive vs Manager/Admin)
              let curLoginRole = parseInt($("body").data("loginroleid") || $("#hidden_loginroleid").val() || "<?php echo intval($loginroleid ?? 0); ?>") || 0;
              if (curLoginRole > 2) {
                $row.find(".rate_td").prop("readonly", true).css("background-color", "#f8fafc");
                $row.find(".expecteddelivery").prop("readonly", true).css({"background-color": "#f8fafc", "pointer-events": "none"});
              } else {
                $row.find(".rate_td").prop("readonly", false).css("background-color", "");
                $row.find(".expecteddelivery").prop("readonly", false).css({"background-color": "", "pointer-events": ""});
              }

              // Sowing Schedule & Trader / Own Production Stock Rules
              if (isExistingItem) {
                // Allow removing item (if sowing has started, item is cancelled from order and sown stock remains active for open stock)
                $row.find(".removeRow").prop("disabled", false).removeClass("disabled opacity-50").removeAttr("title");

                if (isTrader) {
                  // If production order client is a trader, quantity can be edited
                  $row.find(".qty_td").prop("readonly", false).css({"background-color": "#fff", "cursor": "text"}).attr("title", "Trader stock: Quantity can be edited.");
                } else if (isSelfSown) {
                  // Own production stock order and sowing started -> quantity editable up to sowing qty, item can be removed
                  $row.find(".qty_td").prop("readonly", false).css({"background-color": "#fff", "cursor": "text"}).attr("data-is-self-sown", "1").attr("data-sowing-qty", sowingQtyLimit).attr("title", "Sown Qty: " + sowingQtyLimit + ". Editable up to " + sowingQtyLimit + " (Order Qty <= Sowing Qty).");
                  $row.find(".rate_td").prop("readonly", true).css({"background-color": "#e9ecef", "cursor": "not-allowed"});
                  $row.find(".expecteddelivery").prop("readonly", true).css({"background-color": "#e9ecef", "cursor": "not-allowed", "pointer-events": "none"}).attr("title", "Sowing has started. Cannot change delivery date.");
                  $row.find(".distprt_td, .distamt_td").prop("readonly", true).css({"background-color": "#e9ecef", "cursor": "not-allowed"});
                } else if (sowingStatus !== 'pending') {
                  $row.find(".qty_td").prop("readonly", true).css({"background-color": "#e9ecef", "cursor": "not-allowed"}).attr("title", "Sowing schedule is " + sowingStatus + ". Cannot edit quantity.");
                  $row.find(".rate_td").prop("readonly", true).css({"background-color": "#e9ecef", "cursor": "not-allowed"});
                  $row.find(".expecteddelivery").prop("readonly", true).css({"background-color": "#e9ecef", "cursor": "not-allowed", "pointer-events": "none"}).attr("title", "Sowing schedule is " + sowingStatus + ". Cannot change delivery date.");
                  $row.find(".distprt_td, .distamt_td").prop("readonly", true).css({"background-color": "#e9ecef", "cursor": "not-allowed"});
                }
              }
            });
          }

          if (typeof Invoicegrandtotalgstamont === 'function') {
            Invoicegrandtotalgstamont('totalamount', 'gsttotalamount', 'ttldiscountamount', 'grandtotalamount', 'payableamount_td', 'cgstamt_td', 'sgstamt_td', 'igstamt_td', 'distamt_td', 'provisionorderitemsTable');
          }

          // Step 3 & Step 4: Populate Delivery, Box Packing, Remarks, and Freight Groups
          if (typeof window.populateDeliveryAndFreightForEdit === 'function') {
            window.populateDeliveryAndFreightForEdit(header, client, items, delivery, freight);
          }

          if (typeof iziToast !== 'undefined') {
            iziToast.info({
              title: 'Order Loaded',
              message: 'Details for ' + psono + ' have been loaded into form.',
              position: 'topRight'
            });
          }
        };
        populateItemsWhenReady();
      },
      error: function (xhr, status, error) {
        Swal.close();
        console.error("Error loading order for edit:", error);
        Swal.fire('Error', 'Failed to communicate with server to load order details.', 'error');
      }
    });
  }

  $(document).ready(function () {
    let initLoginRoleId = parseInt($("body").data("loginroleid") || $("#hidden_loginroleid").val() || "<?php echo intval($loginroleid ?? 0); ?>") || 0;
    if (initLoginRoleId > 2) {
      $("#provisionorderitemsTable tbody .rate_td").prop("readonly", true).css("background-color", "#f8fafc");
      $("#provisionorderitemsTable tbody .expecteddelivery").prop("readonly", true).css({"background-color": "#f8fafc", "pointer-events": "none"});
    } else {
      $("#provisionorderitemsTable tbody .rate_td").prop("readonly", false).css("background-color", "");
      $("#provisionorderitemsTable tbody .expecteddelivery").prop("readonly", false).css({"background-color": "", "pointer-events": ""});
    }

    $("#delivery_address").on("input", function() {
      const len = $(this).val().length;
      $("#address_char_count").text(len + "/500 characters");
      if (len >= 500) {
        $("#address_char_count").addClass("text-danger").removeClass("text-muted");
      } else {
        $("#address_char_count").removeClass("text-danger").addClass("text-muted");
      }
    });
    if ($("#delivery_address").val()) {
      $("#delivery_address").trigger("input");
    }

    // Recalculate total when a row is added
    $("#addRow").click(function () {
      var table = $('#provisionorderitemsTable');
      var $tbody = table.find('tbody');

      // Validation: Check if the last row is filled before adding a new one
      var $lastRow = $tbody.find('tr:last');
      if ($lastRow.length > 0) {
        var lastProduct = $lastRow.find('.productname').val();
        var lastRate = $lastRow.find('.rate_td').val();
        var lastQty = $lastRow.find('.qty_td').val();

        if (!lastProduct || !lastRate || parseFloat(lastQty) <= 0) {
          iziToast.warning({
            title: 'Incomplete Row',
            message: 'Please select a Product and enter Rate & Quantity before adding a new item.',
            position: 'topRight'
          });
          return;
        }
      }

      var $firstRow = $tbody.find('tr:first');

      // Clone the first row but remove Select2 artifacts first
      var $newRow = $firstRow.clone();
      const newRowKey = 'item_row_' + Date.now() + '_' + Math.random().toString(36).substr(2, 6);
      $newRow.attr('data-row-key', newRowKey);

      // Clean up the cloned row
      $newRow.removeClass('row_ignore has-stockorder stockorder-qty-edited'); // Ensure new row isn't ignored or marked as stockorder
      $newRow.removeAttr('data-orig-qty')
             .removeAttr('data-stockorder-no')
             .removeAttr('data-stock-id')
             .removeAttr('data-item-id')
             .removeAttr('data-stock-clientid')
             .removeAttr('data-stock-clientname')
             .removeAttr('data-stock-sowingdate')
             .removeAttr('data-sowing-status')
             .removeAttr('data-is-self-sown')
             .removeAttr('data-sowing-qty')
             .removeAttr('data-dispatched-qty')
             .removeAttr('data-is-trader');
      $newRow.removeData('stockorder-no')
             .removeData('sowing-status')
             .removeData('is-self-sown')
             .removeData('is-trader');
      $newRow.css('background-color', '');
      $newRow.find('td').css('background-color', '');

      // CRITICAL FIX: Completely remove the production stock order badge and its container from newly added row!
      $newRow.find('.stockorder-badge-container, .stockorder-badge, .stock-sowingdate-badge, .badge').remove();
      $newRow.find('.locked_productname_val').remove(); // remove any locked hidden product

      $newRow.find('.select2-container').remove(); // Remove existing Select2 containers
      $newRow.find('.select2').removeClass('select2-hidden-accessible').prop('disabled', false).removeAttr('disabled'); // Reset select2 class and enable
      $newRow.find('select').attr('data-select2-id', null);
      $newRow.find('option').attr('data-select2-id', null);

      // Reset values
      $newRow.find('input').val('');
      $newRow.find('.order_item_id').val(''); // ensure newly added row has empty order_item_id
      $newRow.find('.roundoffamount_td').val('0.00');
      $newRow.find('.stockdbid').val('');
      $newRow.find('.isreadytosale').val('0');
      $newRow.find('select').val('');

      // Ensure product options are present
      if (globalProductOptions) {
        $newRow.find('.productname').html(globalProductOptions);
      }

      $newRow.find('.qty_td').prop('readonly', false).removeAttr('readonly').removeAttr('data-is-self-sown').removeAttr('data-sowing-qty').removeAttr('title').css({'background-color': '', 'cursor': ''});
      $newRow.find('.removeRow').prop('disabled', false).removeClass('disabled opacity-50').removeAttr('title');
      $newRow.find('.distprt_td, .distamt_td').prop('readonly', false).removeAttr('readonly').css({'background-color': '', 'cursor': ''});

      $newRow.find(".expecteddelivery").removeAttr("min").removeAttr("max");
      $newRow.find(".expecteddelivery").prop("readonly", false).css({"pointer-events": "", "background-color": ""}).removeAttr("tabindex");
      let loginRoleId = parseInt($("body").data("loginroleid") || $("#hidden_loginroleid").val() || "<?php echo intval($loginroleid ?? 0); ?>") || 0;
      if (loginRoleId > 2) {
        $newRow.find(".rate_td").prop("readonly", true).css("background-color", "#f8fafc");
      } else {
        $newRow.find(".rate_td").prop("readonly", false).css("background-color", "");
      }

      // Append and Initialize
      $tbody.append($newRow);

      // Re-initialize Select2 for the new row specifically
      $newRow.find('.productname.select2').select2({
        placeholder: "Select Product",
        allowClear: true,
        width: '100%'
      });

      loginRoleId = parseInt($("#hidden_loginroleid").val() || 0, 10);
      $(".distprt_td").prop("disabled", loginRoleId > 3);
      $(".distamt_td").prop("disabled", loginRoleId > 3);
      Invoicegrandtotalgstamont('totalamount', 'gsttotalamount', 'ttldiscountamount', 'grandtotalamount', 'payableamount_td', 'cgstamt_td', 'sgstamt_td', 'igstamt_td', 'distamt_td', 'provisionorderitemsTable');
    });

    
    // Handle Enter key on quantity column: apply mouseout and focus out (blur)
    $(document).on("keydown", "#provisionorderitemsTable .qty_td, #provisionorderitemsTable .qtycal", function (e) {
      if (e.key === "Enter") {
        e.preventDefault();
        $(this).trigger("change");
        $(this).trigger("mouseout").trigger("mouseleave");
        this.blur();
        if (document.activeElement && document.activeElement === this) {
          document.activeElement.blur();
        }
      }
    });

    // Prevent default enter key on other table inputs/selects
    $(document).on("keydown", "#provisionorderitemsTable input:not(.qty_td):not(.qtycal), #provisionorderitemsTable select", function (e) {
      if (e.key === "Enter") {
        e.preventDefault();
      }
    });

    // Recalculate total when a row is removed, and track removed item ID if it was an existing order item
    $(document).on("click", ".removeRow", function () {
      let $tr = $(this).closest("tr");
      let dispatchedQty = parseFloat($tr.attr("data-dispatched-qty") || $tr.data("dispatched-qty") || 0);
      let psono = $("#edit_psono").val() || $("body").data("psono") || "";
      if (dispatchedQty > 0) {
        Swal.fire({
          icon: 'error',
          title: 'Cannot Remove Item',
          html: `This item cannot be removed against <b>${psono || 'this order'}</b> because <b>${dispatchedQty}</b> plants have already been dispatched.`
        });
        return false;
      }

      if ($("#provisionorderitemsTable tbody tr").length <= 1) {
        iziToast.warning({
          title: 'Cannot Remove',
          message: 'At least one item row is required in the order.',
          position: 'topRight'
        });
        return;
      }
      let existingItemId = $tr.find(".order_item_id").val() || $tr.attr("data-item-id") || "";
      if (existingItemId) {
        $("#salesOrderForm").append(`<input type="hidden" name="removed_item_ids[]" value="${existingItemId}">`);
      }
      let removedRowKey = $tr.attr("data-row-key") || "";
      let removedProdId = $tr.find(".productname").val() || "";
      $tr.remove();
      Invoicegrandtotalgstamont('totalamount', 'gsttotalamount', 'ttldiscountamount', 'grandtotalamount', 'payableamount_td', 'cgstamt_td', 'sgstamt_td', 'igstamt_td', 'distamt_td', 'provisionorderitemsTable');
      if (typeof syncDeliveryTablesWithStep2 === 'function') {
        syncDeliveryTablesWithStep2(removedRowKey, removedProdId, existingItemId);
      }
    });

    // Real-time validation on editing quantity for products with active sowing on own stock order
    $(document).on("input change", "#provisionorderitemsTable .qty_td", function () {
      let $input = $(this);
      let $row = $input.closest("tr");
      let enteredQty = parseFloat($input.val() || 0);
      let dispatchedQty = parseFloat($row.attr("data-dispatched-qty") || $row.data("dispatched-qty") || 0);
      if (dispatchedQty > 0 && enteredQty < dispatchedQty) {
        Swal.fire({
          icon: 'warning',
          title: 'Order Qty Cannot Be Less Than Dispatched Qty',
          html: `Order quantity (<b>${enteredQty}</b>) cannot be reduced below already dispatched quantity (<b>${dispatchedQty}</b>).`,
          confirmButtonColor: '#0d6efd'
        });
        $input.val(dispatchedQty);
        $input.trigger('input');
        if (typeof Invoicegrandtotalgstamont === 'function') {
          Invoicegrandtotalgstamont('totalamount', 'gsttotalamount', 'ttldiscountamount', 'grandtotalamount', 'payableamount_td', 'cgstamt_td', 'sgstamt_td', 'igstamt_td', 'distamt_td', 'provisionorderitemsTable');
        }
        return;
      }

      let isSelfSown = ($input.attr("data-is-self-sown") === "1" || $input.data("is-self-sown") == "1");
      if (isSelfSown) {
        let maxSowingQty = parseFloat($input.attr("data-sowing-qty") || 0);
        let prodName = $row.find(".productname option:selected").text().trim() || "Selected product";
        if (maxSowingQty > 0 && enteredQty > maxSowingQty) {
          Swal.fire({
            icon: 'warning',
            title: 'Order Qty Cannot Exceed Sown Qty',
            html: `Order quantity (<b>${enteredQty}</b>) cannot exceed sown quantity (<b>${maxSowingQty}</b>) for <b>${escapeHtml(prodName)}</b>.<br><br><span class="text-danger">To increase order quantity above ${maxSowingQty}, please discuss and coordinate with the Sowing Department first.</span>`,
            confirmButtonColor: '#0d6efd'
          });
          $input.val(maxSowingQty);
          $input.trigger('input');
          if (typeof Invoicegrandtotalgstamont === 'function') {
            Invoicegrandtotalgstamont('totalamount', 'gsttotalamount', 'ttldiscountamount', 'grandtotalamount', 'payableamount_td', 'cgstamt_td', 'sgstamt_td', 'igstamt_td', 'distamt_td', 'provisionorderitemsTable');
          }
        }
      }
    });

    $(document).on("change blur", "#provisionorderitemsTable .qty_td, #provisionorderitemsTable .expecteddelivery", function () {
      if ($("#deliveryboxtbl tbody tr.box-delivery-row").length > 0 || $("#deliveryaddresstbl tbody tr").length > 0) {
        if (typeof syncDeliveryTablesWithStep2 === 'function') {
          syncDeliveryTablesWithStep2();
        }
      }
    });

    $(document).on("change", ".productname", function () {
      let row = $(this).closest("tr");
      row.find(".expecteddelivery").val('');
      row.find(".isreadytosale").val('');
      row.find(".stockdbid").val('');
      let selected = row.find(".productname option:selected");
      let price = selected.length ? parseFloat(selected.data("priceofproduct")) : 0;
      row.find("input.ratecal").val(price).trigger("input");

      let productId = $(this).val();
      if (productId) {
        let projectid = $("body").data("projectid") || $("#projectid").val() || 17;
        $.ajax({
          url: "modal/stockverification.php?action=fecthgrowingdate",
          type: "POST",
          data: { projectid: projectid, productid: productId },
          dataType: "json",
          success: function (res) {
            if (res && res.length > 0 && res[0].mindeliverydate) {
              row.find(".expecteddelivery").attr("min", res[0].mindeliverydate);
            }
          }
        });
        fetchClientLastPurchaseHistory(productId, row);
        fetchAndApplyClientDiscount(productId, row, true);
      } else {
        $("#product-last-purchase-suggestion").slideUp(200).empty();
        $("#product-discount-suggestion").slideUp(200).empty();
      }
    });

    // Auto-focus quantity ONLY when user actively selects a product from Select2 dropdown
    $(document).on("select2:select", "#provisionorderitemsTable .productname", function () {
      let row = $(this).closest("tr");
      if ($(this).val()) {
        setTimeout(function () {
          row.find(".qty_td").focus().select();
        }, 100);
      }
    });

    $(document).on("input change", ".qtycal", function () {
      let row = $(this).closest("tr");
      let productId = row.find(".productname").val();
      if (productId) {
        fetchAndApplyClientDiscount(productId, row, true);
      }
    });

    // Highlight existing production stock order row when quantity is edited
    $(document).on("input change keyup blur", "#provisionorderitemsTable .qty_td, #provisionorderitemsTable .qtycal", function () {
      let $row = $(this).closest("tr");
      let hasStockOrder = $row.hasClass("has-stockorder") || $row.find(".stockorder-badge").length > 0 || !!$row.attr("data-stockorder-no");
      if (hasStockOrder) {
        let currentQty = parseFloat($(this).val()) || 0;
        let origQty = parseFloat($row.attr("data-orig-qty"));

        if (!isNaN(origQty) && currentQty !== origQty) {
          $row.addClass("stockorder-qty-edited");
        } else if (!isNaN(origQty) && currentQty === origQty) {
          $row.removeClass("stockorder-qty-edited");
        } else if (isNaN(origQty)) {
          $row.addClass("stockorder-qty-edited");
        }
      }
    });

  // Safe HTML escaper
  function escapeClientHtml(value) {
    return $('<div>').text(value == null ? '' : value).html();
  }

  // Dedicated function to fetch and display last purchase rate & date for the client
  function fetchClientLastPurchaseHistory(productId, $row) {
    const $suggestionDiv = $("#product-last-purchase-suggestion");
    if (!productId) {
      $suggestionDiv.slideUp(200).empty();
      return;
    }

    const clientId = $("#clientid").val() || "";
    const clientMobile = $("#mobileno").val() || "";
    const clientName = $("#clientname").val() || "Client";
    const routurl = $("body").data("routurl") || "../";
    const projectid = $("body").data("projectid") || "";

    if (!clientId && !clientMobile) {
      $suggestionDiv.slideUp(200).empty();
      return;
    }

    const rowIndex = $row ? $row.index() : 0;
    let masterPrice = 0;
    if ($row && $row.length) {
      let selectedOpt = $row.find(".productname option:selected");
      masterPrice = selectedOpt.length ? parseFloat(selectedOpt.data("priceofproduct")) : 0;
    }

    $.ajax({
      url: routurl + "krf-orderandsales/modal/product_modal.php?action=GetClientLastPurchaseHistory",
      type: "POST",
      dataType: "json",
      data: {
        clientid: clientId,
        mobileno: clientMobile,
        productid: productId,
        projectid: projectid
      },
      success: function (res) {
        if (res && res.status === "success" && res.data) {
          const dataList = Array.isArray(res.data) ? res.data : [res.data];
          if (dataList.length === 0) {
            $suggestionDiv.slideUp(200).empty();
            return;
          }

          const safeClientName = escapeClientHtml(clientName);
          const formattedMasterRate = masterPrice > 0 ? parseFloat(masterPrice).toFixed(2) : null;

          let entriesHtml = dataList.map((d, index) => {
            const formattedRate = parseFloat(d.rate).toFixed(2);
            const safeOrderType = escapeClientHtml(d.order_type || 'Order');
            const safeOrderNo = escapeClientHtml(d.order_no || '');
            const safeSono = escapeClientHtml(d.sono || '');
            const safePsono = escapeClientHtml(d.psono || '');
            const previewTitle = safeSono 
              ? `Click to preview Final Sale Invoice (${safeSono})` 
              : `Click to preview ${safeOrderType} (${safeOrderNo})`;

            return `
              <div class="d-flex flex-wrap align-items-center justify-content-between p-2 px-3 rounded-2 bg-white border shadow-2xs gap-2" style="border-color: #cbd5e1 !important;">
                <div class="d-flex flex-wrap align-items-center gap-2">
                  <span class="badge bg-light text-dark border px-2 py-1 fw-bold" style="font-size: 0.78rem;">#${index + 1}</span>
                  <span class="badge bg-soft-success text-success border border-success-subtle px-2 py-1" style="font-size: 0.85rem;"><i class="fa fa-tag me-1"></i> Rate: <strong style="font-size: 0.92rem;">₹${formattedRate}</strong></span>
                  <span class="badge bg-light text-dark border px-2 py-1" style="font-size: 0.82rem;"><i class="fa fa-calendar-alt text-primary me-1"></i> Date: <strong>${d.order_date || 'N/A'}</strong></span>
                  <span class="badge bg-light text-dark border px-2 py-1" style="font-size: 0.82rem;"><i class="fa fa-boxes text-info me-1"></i> Qty: <strong>${d.quantity || 'N/A'}</strong></span>
                  ${d.order_no ? `
                    <span class="badge bg-light text-secondary border px-2 py-1 preview-invoice-badge" 
                          style="font-size: 0.82rem; cursor: pointer; transition: all 0.2s ease; user-select: none;" 
                          title="${previewTitle}"
                          onclick="previewSaleOrderInvoice('${safeOrderType}', '${safeOrderNo}', '${safeSono}', '${safePsono}')"
                          onmouseover="this.style.backgroundColor='#e0f2fe'; this.style.color='#0369a1'; this.style.borderColor='#7dd3fc';"
                          onmouseout="this.style.backgroundColor=''; this.style.color=''; this.style.borderColor='';">
                      <i class="fa fa-receipt text-primary me-1"></i> ${safeOrderType}: <strong class="text-primary text-decoration-underline">${safeOrderNo}</strong>
                      <i class="fa fa-external-link-alt ms-1 text-muted" style="font-size: 0.72rem;"></i>
                    </span>` : ''}
                </div>
                <div>
                  <button type="button" class="btn btn-sm btn-primary shadow-xs fw-bold d-flex align-items-center gap-1" onclick="applyLastPurchaseRate(${d.rate}, ${rowIndex})" style="border-radius: 6px; padding: 4px 12px; font-size: 0.82rem;">
                    <i class="fa fa-check-circle"></i> Apply Rate (₹${formattedRate})
                  </button>
                </div>
              </div>
            `;
          }).join('');

          const suggestionHtml = `
            <div class="alert alert-info border-0 shadow-sm p-3 rounded-3" style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border: 1px solid #bae6fd !important; animation: fadeIn 0.3s ease-out;">
              <div class="d-flex flex-wrap align-items-center justify-content-between pb-2 mb-2 border-bottom" style="border-color: #bae6fd !important; gap: 8px;">
                <div class="d-flex align-items-center gap-2">
                  <div class="rounded-circle p-1 d-flex align-items-center justify-content-center bg-white shadow-sm flex-shrink-0" style="width: 32px; height: 32px; border: 1px solid #7dd3fc;">
                    <i class="fa fa-history text-primary" style="font-size: 0.95rem;"></i>
                  </div>
                  <div>
                    <div class="fw-bold" style="color: #0369a1; font-size: 0.92rem;">
                      <i class="fa fa-lightbulb text-warning me-1"></i> Last Sale History for <span class="text-dark">${safeClientName}</span>:
                      <span class="badge bg-white text-info border ms-1" style="font-size: 0.78rem;">${dataList.length} ${dataList.length === 1 ? 'record' : 'recent records'}</span>
                    </div>
                  </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                  ${formattedMasterRate ? `
                    <span class="badge bg-white text-dark border py-1 px-2 d-none d-sm-inline-block" style="font-size: 0.82rem;">
                      <i class="fa fa-database text-primary me-1"></i> Master Rate: <strong class="text-primary">₹${formattedMasterRate}</strong>
                    </span>
                    <button type="button" class="btn btn-sm shadow-xs fw-bold d-flex align-items-center gap-1" onclick="applyLastPurchaseRate(${masterPrice}, ${rowIndex})" style="border-radius: 6px; padding: 4px 10px; font-size: 0.8rem; background: #e0f2fe; border: 1px solid #7dd3fc; color: #0369a1; transition: all 0.2s ease;" onmouseover="this.style.backgroundColor='#0284c7'; this.style.color='#ffffff'; this.style.borderColor='#0284c7';" onmouseout="this.style.backgroundColor='#e0f2fe'; this.style.color='#0369a1'; this.style.borderColor='#7dd3fc';">
                      <i class="fa fa-undo"></i> Reset Master (₹${formattedMasterRate})
                    </button>
                  ` : ''}
                  <button type="button" class="btn-close ms-1" onclick="$('#product-last-purchase-suggestion').slideUp(200)" aria-label="Close" style="font-size: 0.8rem;"></button>
                </div>
              </div>

              <div class="d-flex flex-column gap-2 mt-2">
                ${entriesHtml}
              </div>
            </div>
          `;

          $suggestionDiv.removeClass("d-none").html(suggestionHtml).stop(true, true).slideDown(250);
        } else {
          $suggestionDiv.slideUp(200).empty();
        }
      },
      error: function () {
        $suggestionDiv.slideUp(200).empty();
      }
    });
  }

  // Global helper to preview final sale invoice in a modal (without opening new tab)
  window.previewSaleOrderInvoice = function (orderType, orderNo, sono, psono) {
    const routurl = $("body").data("routurl") || "../";
    const companyid = $("body").data("companyid") || "";
    const subcompanyid = $("body").data("subcompanyid") || "";
    const projectid = $("body").data("projectid") || "";
    const targetSono = sono || orderNo || "";

    if (!targetSono) return;

    const invoiceUrl = `${routurl}krf-orderandsales/modal/finalsalesorderPDF.php?sono=${encodeURIComponent(targetSono)}&companyid=${encodeURIComponent(companyid)}&subcompanyid=${encodeURIComponent(subcompanyid)}&projectid=${encodeURIComponent(projectid)}&mode=I`;

    $("#previewModalSono").text(targetSono);
    $("#previewModalNewTabBtn").attr("href", invoiceUrl);

    const loaderEl = document.getElementById("previewModalLoader");
    const iframeEl = document.getElementById("previewInvoiceIframe");

    // Display loader overlay
    if (loaderEl) {
      loaderEl.style.display = "flex";
      loaderEl.style.opacity = "1";
    }

    const hideLoader = function () {
      if (loaderEl) {
        loaderEl.style.transition = "opacity 0.25s ease";
        loaderEl.style.opacity = "0";
        setTimeout(function () {
          loaderEl.style.display = "none";
        }, 250);
      }
    };

    if (iframeEl) {
      iframeEl.onload = hideLoader;
      iframeEl.src = invoiceUrl;
    }

    // Safety timeout: ensure loader disappears even if browser suppresses iframe onload for inline PDF plugins
    setTimeout(hideLoader, 800);

    // Open modal (BS5, BS4, BS3 & vanilla JS fallback)
    const modalEl = document.getElementById("finalSaleInvoicePreviewModal");
    if (typeof bootstrap !== "undefined" && bootstrap.Modal) {
      try {
        let bsModal = bootstrap.Modal.getInstance(modalEl);
        if (!bsModal) {
          bsModal = new bootstrap.Modal(modalEl, { backdrop: true, keyboard: true });
        }
        bsModal.show();
      } catch (e) {
        if (typeof $ !== "undefined" && typeof $.fn.modal === "function") {
          $("#finalSaleInvoicePreviewModal").modal("show");
        }
      }
    } else if (typeof $ !== "undefined" && typeof $.fn.modal === "function") {
      $("#finalSaleInvoicePreviewModal").modal("show");
    } else if (modalEl) {
      modalEl.style.display = "block";
      modalEl.classList.add("show");
      document.body.classList.add("modal-open");
    }
  };

  window.closeInvoicePreviewModal = function () {
    const modalEl = document.getElementById("finalSaleInvoicePreviewModal");
    if (typeof bootstrap !== "undefined" && bootstrap.Modal) {
      try {
        const bsModal = bootstrap.Modal.getInstance(modalEl);
        if (bsModal) {
          bsModal.hide();
        }
      } catch (e) {}
    }
    if (typeof $ !== "undefined" && typeof $.fn.modal === "function") {
      try {
        $("#finalSaleInvoicePreviewModal").modal("hide");
      } catch(e) {}
    }
    if (modalEl) {
      modalEl.style.display = "none";
      modalEl.classList.remove("show");
    }
    document.body.classList.remove("modal-open");
    $(".modal-backdrop").remove();
    const iframeEl = document.getElementById("previewInvoiceIframe");
    if (iframeEl) {
      iframeEl.src = "about:blank";
    }
  };

  // Global helper to apply the suggested rate to the target row
  window.applyLastPurchaseRate = function (rate, targetRowIndex) {
    let $targetRow;
    if (targetRowIndex !== undefined && targetRowIndex !== null) {
      $targetRow = $("#provisionorderitemsTable tbody tr").eq(targetRowIndex);
    }
    if (!$targetRow || !$targetRow.length) {
      $targetRow = $("#provisionorderitemsTable tbody tr:last");
    }
    const $rateInput = $targetRow.find("input.ratecal");
    $rateInput.val(rate).trigger("input");

    // Smooth visual highlight feedback
    $rateInput.css({ "background-color": "#dcfce7", "border-color": "#22c55e", "transition": "all 0.3s" });
    setTimeout(() => {
      let loginRoleId = parseInt($("body").data("loginroleid") || $("#hidden_loginroleid").val() || "<?php echo intval($loginroleid ?? 0); ?>") || 0;
      let defaultBg = (loginRoleId > 2) ? "#f8fafc" : "";
      $rateInput.css({ "background-color": defaultBg, "border-color": "" });
    }, 1200);
  };

  // Dedicated function to fetch, display, and auto-apply client discount for the selected product
  function fetchAndApplyClientDiscount(productId, $row, autoApply) {
    if (autoApply === undefined) autoApply = true;
    const $discountDiv = $("#product-discount-suggestion");
    if (!productId) {
      $discountDiv.slideUp(200).empty();
      return;
    }

    const clientId = $("#clientid").val() || "";
    const clientMobile = $("#mobileno").val() || "";
    const clientName = $("#clientname").val() || "Client";
    const routurl = $("body").data("routurl") || "../";
    const projectid = $("body").data("projectid") || "";

    if (!clientId && !clientMobile) {
      $discountDiv.slideUp(200).empty();
      return;
    }

    const rowIndex = $row ? $row.index() : 0;
    let qty = 0;
    let price = 0;
    if ($row && $row.length) {
      qty = parseFloat($row.find("input.qty_td").val()) || 0;
      let selectedOpt = $row.find(".productname option:selected");
      price = parseFloat($row.find("input.rate_td").val()) || (selectedOpt.length ? parseFloat(selectedOpt.data("priceofproduct")) : 0);
    }

    $.ajax({
      url: routurl + "krf-orderandsales/modal/product_modal.php?action=getClientProductDiscount",
      type: "POST",
      dataType: "json",
      data: {
        clientid: clientId,
        mobileno: clientMobile,
        productid: productId,
        quantity: qty,
        price: price,
        projectid: projectid
      },
      success: function (res) {
        if (res && res.status === "success" && (res.has_discount || res.policy_enabled)) {
          const safeClientName = escapeClientHtml(clientName);
          const isGrafted = res.is_grafted;
          const plantTypeLabel = isGrafted ? 'Grafted Plant' : 'Non-Grafted Plant';
          const plantBadge = isGrafted
            ? `<span class="badge bg-white text-dark border px-2 py-1" style="font-size: 0.8rem;"><i class="fa fa-seedling text-success me-1"></i> Grafted</span>`
            : `<span class="badge bg-white text-dark border px-2 py-1" style="font-size: 0.8rem;"><i class="fa fa-leaf text-success me-1"></i> Non-Grafted</span>`;

          const rule = res.applicable_rule || 'none';
          const discType = res.discount_type || 'rate';
          const discPercent = parseFloat(res.discount_percent || 0);
          const discAmount = parseFloat(res.discount_amount || 0);
          const discRate = parseFloat(res.discount_rate || 0);
          const activePolicy = res.active_policy || {};

          let ruleText = rule === 'asperplane' ? 'As Per Plan (Quantity Slabs)' : (rule === 'everyorder' ? 'Every Order' : 'None');
          let typeText = discType === 'rate' ? 'Rate (₹ / Plant)' : (discType === 'percent' ? 'Percentage (%)' : 'Fixed Amount (₹ / Order)');

          let policyDetailsHtml = '';

          if (rule === 'everyorder') {
            let valFormatted = discType === 'percent' ? `${discPercent}%` : `₹${discAmount.toFixed(2)}`;
            policyDetailsHtml = `
              <div class="d-flex flex-wrap align-items-center justify-content-between p-2 px-3 rounded-2 bg-white border shadow-2xs gap-2" style="border-color: #a7f3d0 !important;">
                <div class="d-flex flex-wrap align-items-center gap-2">
                  <span class="badge bg-soft-success text-success border border-success-subtle px-2 py-1" style="font-size: 0.85rem;">
                    <i class="fa fa-percentage me-1"></i> Calculation: <strong>${typeText}</strong>
                  </span>
                  <span class="badge bg-light text-dark border px-2 py-1" style="font-size: 0.85rem;">
                    <i class="fa fa-tag text-success me-1"></i> Value: <strong class="text-success" style="font-size: 0.92rem;">${valFormatted}</strong>
                  </span>
                  <span class="badge bg-light text-secondary border px-2 py-1" style="font-size: 0.82rem;">
                    <i class="fa fa-check-circle text-primary me-1"></i> Rule: <strong>Every Order</strong>
                  </span>
                </div>
                <div>
                  <button type="button" class="btn btn-sm btn-success shadow-xs fw-bold d-flex align-items-center gap-1" onclick="applyClientDiscountToRow('${discType}', ${discType === 'percent' ? discPercent : discAmount}, ${rowIndex})" style="border-radius: 6px; padding: 4px 12px; font-size: 0.82rem;">
                    <i class="fa fa-check-circle"></i> Apply Discount (${valFormatted})
                  </button>
                </div>
              </div>
            `;
          } else if (rule === 'asperplane') {
            const slabs = activePolicy.slabs || [];
            let slabsHtml = slabs.map((s, sIdx) => {
              const minQ = parseInt(s.min_qty) || 0;
              const maxQ = parseInt(s.max_qty) || 0;
              const maxDisplay = maxQ > 0 ? maxQ.toLocaleString() : 'Above';
              const sType = s.type || 'rate';
              const sVal = sType === 'percent' ? `${parseFloat(s.percent || 0)}%` : `₹${parseFloat(s.amount || 0).toFixed(2)}`;
              const isMatched = res.matched_slab && (res.matched_slab.min_qty == s.min_qty);

              return `
                <div class="d-flex flex-wrap align-items-center justify-content-between p-2 px-3 rounded-2 bg-white border shadow-2xs gap-2 ${isMatched ? 'border-success' : ''}" style="border-color: ${isMatched ? '#10b981' : '#cbd5e1'} !important; ${isMatched ? 'background-color: #ecfdf5 !important;' : ''}">
                  <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="badge ${isMatched ? 'bg-success text-white' : 'bg-light text-dark'} border px-2 py-1 fw-bold" style="font-size: 0.78rem;">
                      ${isMatched ? '<i class="fa fa-check me-1"></i> MATCHED SLAB' : `Slab #${sIdx + 1}`}
                    </span>
                    <span class="badge bg-light text-dark border px-2 py-1" style="font-size: 0.82rem;">
                      <i class="fa fa-boxes text-info me-1"></i> Qty: <strong>${minQ.toLocaleString()} - ${maxDisplay}</strong>
                    </span>
                    <span class="badge bg-soft-success text-success border border-success-subtle px-2 py-1" style="font-size: 0.85rem;">
                      <i class="fa fa-tag me-1"></i> ${sType === 'percent' ? 'Discount %' : 'Rate/Amt'}: <strong>${sVal}</strong>
                    </span>
                  </div>
                  <div>
                    <button type="button" class="btn btn-sm ${isMatched ? 'btn-success' : 'btn-outline-success'} shadow-xs fw-bold d-flex align-items-center gap-1" onclick="applyClientDiscountToRow('${sType}', ${sType === 'percent' ? (s.percent || 0) : (s.amount || 0)}, ${rowIndex})" style="border-radius: 6px; padding: 4px 12px; font-size: 0.82rem;">
                      <i class="fa fa-check-circle"></i> Apply (${sVal})
                    </button>
                  </div>
                </div>
              `;
            }).join('');

            policyDetailsHtml = `
              <div class="d-flex flex-column gap-2">
                ${slabsHtml}
              </div>
            `;
          }

          const discountCardHtml = `
            <div class="alert alert-success border-0 shadow-sm p-3 rounded-3" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 1px solid #86efac !important; animation: fadeIn 0.3s ease-out;">
              <div class="d-flex flex-wrap align-items-center justify-content-between pb-2 mb-2 border-bottom" style="border-color: #86efac !important; gap: 8px;">
                <div class="d-flex align-items-center gap-2">
                  <div class="rounded-circle p-1 d-flex align-items-center justify-content-center bg-white shadow-sm flex-shrink-0" style="width: 32px; height: 32px; border: 1px solid #86efac;">
                    <i class="fa fa-percent text-success" style="font-size: 0.95rem;"></i>
                  </div>
                  <div>
                    <div class="fw-bold" style="color: #166534; font-size: 0.92rem;">
                      <i class="fa fa-tags text-success me-1"></i> Applicable Discount for <span class="text-dark">${safeClientName}</span>:
                      ${plantBadge}
                      <span class="badge bg-success text-white ms-1" style="font-size: 0.78rem;"><i class="fa fa-check-circle me-1"></i> Active Policy (${ruleText})</span>
                    </div>
                  </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <button type="button" class="btn btn-sm shadow-xs fw-bold d-flex align-items-center gap-1" onclick="clearClientDiscountFromRow(${rowIndex})" style="border-radius: 6px; padding: 4px 10px; font-size: 0.8rem; background: #fee2e2; border: 1px solid #fca5a5; color: #b91c1c; transition: all 0.2s ease;" onmouseover="this.style.backgroundColor='#ef4444'; this.style.color='#ffffff'; this.style.borderColor='#ef4444';" onmouseout="this.style.backgroundColor='#fee2e2'; this.style.color='#b91c1c'; this.style.borderColor='#fca5a5';">
                    <i class="fa fa-times-circle"></i> Clear Discount
                  </button>
                  <button type="button" class="btn-close ms-1" onclick="$('#product-discount-suggestion').slideUp(200)" aria-label="Close" style="font-size: 0.8rem;"></button>
                </div>
              </div>

              <div class="mt-2">
                ${policyDetailsHtml}
              </div>
            </div>
          `;

          $discountDiv.removeClass("d-none").html(discountCardHtml).stop(true, true).slideDown(250);

          // Auto-apply discount to row if autoApply is enabled
          if (autoApply && $row && $row.length && res.has_discount) {
            applyClientDiscountToRowInternal(res, $row);
          }
        } else {
          $discountDiv.slideUp(200).empty();
          if (autoApply && $row && $row.length) {
            // Reset discount to 0 if no policy
            $row.find("input.distprt_td").val('');
            $row.find("input.distamt_td").val('');
            $row.find("input.ratecal").trigger("input");
          }
        }
      },
      error: function () {
        $discountDiv.slideUp(200).empty();
      }
    });
  }

  // Internal helper to apply discount object to a specific row
  function applyClientDiscountToRowInternal(res, $targetRow) {
    if (!res || !res.has_discount || !$targetRow || !$targetRow.length) return;

    const discType = res.discount_type;
    const discPercent = parseFloat(res.discount_percent || 0);
    const discAmount = parseFloat(res.discount_amount || 0);
    const discRate = parseFloat(res.discount_rate || 0);
    const qty = parseFloat($targetRow.find("input.qty_td").val()) || 0;
    const rate = parseFloat($targetRow.find("input.rate_td").val()) || 0;

    let $distprt = $targetRow.find("input.distprt_td");
    let $distamt = $targetRow.find("input.distamt_td");

    if (discType === 'percent' && discPercent > 0) {
      $distprt.val(discPercent).trigger("input");
    } else if (discType === 'rate' && (discRate > 0 || discAmount > 0)) {
      const effectiveRateDisc = discRate > 0 ? discRate : discAmount;
      if (qty > 0) {
        const totalDiscAmt = (effectiveRateDisc * qty).toFixed(2);
        $distamt.val(totalDiscAmt).trigger("input");
      } else if (rate > 0) {
        const pctEquiv = ((effectiveRateDisc / rate) * 100).toFixed(2);
        $distprt.val(pctEquiv).trigger("input");
      }
    } else if (discType === 'fixed' && discAmount > 0) {
      $distamt.val(discAmount).trigger("input");
    }

    // Highlight animation
    $distprt.add($distamt).css({ "background-color": "#dcfce7", "border-color": "#22c55e", "transition": "all 0.3s" });
    setTimeout(() => {
      $distprt.add($distamt).css({ "background-color": "", "border-color": "" });
    }, 1200);
  }

  // Global helper to apply client discount from UI button click
  window.applyClientDiscountToRow = function (type, value, targetRowIndex) {
    let $targetRow;
    if (targetRowIndex !== undefined && targetRowIndex !== null) {
      $targetRow = $("#provisionorderitemsTable tbody tr").eq(targetRowIndex);
    }
    if (!$targetRow || !$targetRow.length) {
      $targetRow = $("#provisionorderitemsTable tbody tr:last");
    }

    const numVal = parseFloat(value) || 0;
    const qty = parseFloat($targetRow.find("input.qty_td").val()) || 0;
    const rate = parseFloat($targetRow.find("input.rate_td").val()) || 0;
    let $distprt = $targetRow.find("input.distprt_td");
    let $distamt = $targetRow.find("input.distamt_td");

    if (type === 'percent') {
      $distprt.val(numVal).trigger("input");
    } else if (type === 'rate') {
      if (qty > 0) {
        $distamt.val((numVal * qty).toFixed(2)).trigger("input");
      } else if (rate > 0) {
        $distprt.val(((numVal / rate) * 100).toFixed(2)).trigger("input");
      } else {
        $distamt.val(numVal.toFixed(2)).trigger("input");
      }
    } else if (type === 'fixed') {
      $distamt.val(numVal.toFixed(2)).trigger("input");
    }

    $distprt.add($distamt).css({ "background-color": "#dcfce7", "border-color": "#22c55e", "transition": "all 0.3s" });
    setTimeout(() => {
      $distprt.add($distamt).css({ "background-color": "", "border-color": "" });
    }, 1200);
  };

  // Global helper to clear client discount
  window.clearClientDiscountFromRow = function (targetRowIndex) {
    let $targetRow;
    if (targetRowIndex !== undefined && targetRowIndex !== null) {
      $targetRow = $("#provisionorderitemsTable tbody tr").eq(targetRowIndex);
    }
    if (!$targetRow || !$targetRow.length) {
      $targetRow = $("#provisionorderitemsTable tbody tr:last");
    }
    $targetRow.find("input.distprt_td").val('');
    $targetRow.find("input.distamt_td").val('');
    $targetRow.find("input.ratecal").trigger("input");

    // Soft highlight on cleared fields
    $targetRow.find("input.distprt_td, input.distamt_td").css({ "background-color": "#fee2e2", "border-color": "#ef4444", "transition": "all 0.3s" });
    setTimeout(() => {
      $targetRow.find("input.distprt_td, input.distamt_td").css({ "background-color": "", "border-color": "" });
    }, 800);
  };


    $(document).on("change", "input[name='pickupfrom']", function () {
      const mode = $("input[name='pickupfrom']:checked").val();
      $("#deliveryboxdiv").addClass("d-none");
      $("#deliveryaddressdiv").fadeOut();
      $("#btnSkipDeliveryStep").removeClass("d-none");

      if (mode === "delivered") {
        $("#deliveryaddressdiv").fadeIn();
        $("#btnSkipDeliveryStep").addClass("d-none");
        fetches_states();
        if ($("#deliveryaddresstbl tbody tr").length === 0) {
          if (window.savedOrderFreightData && window.savedOrderFreightData.delivery && window.savedOrderFreightData.delivery.length > 0) {
            window.populateDeliveryAndFreightForEdit(
              window.savedOrderFreightData.header,
              window.savedOrderFreightData.client,
              window.savedOrderFreightData.items,
              window.savedOrderFreightData.delivery,
              window.savedOrderFreightData.freight
            );
          } else if (typeof window.autoFillDefaultDeliveryAddress === 'function') {
            window.autoFillDefaultDeliveryAddress();
          }
        }
      } else if (mode === "boxpacking") {
        $("#btnSkipDeliveryStep").addClass("d-none");
        $("#deliveryboxdiv").removeClass("d-none");
        fillboxdata();
      } else {
        $("#deliveryaddressdiv").fadeOut();
      }

      if (typeof generateFreightGroups === 'function') {
        generateFreightGroups(true);
      }
    });

    $(document).ready(function () {
      calculateFreightPayable("#freight_amount", "#discount_percent", "#payablefreight_amount");

      $("#provisionorderitemsTable tbody tr").each(function () {
        if (typeof getOrSetRowKey === 'function') {
          getOrSetRowKey($(this));
        }
      });

      // Initial state sync for delivery address
      if ($("input[name='pickupfrom']:checked").val() === "delivered") {
        $("#deliveryaddressdiv").show();
      } else {
        $("#deliveryaddressdiv").hide();
      }
    });

    // Submit form using AJAX
    $("#salesOrderForm").submit(function (e) {
      e.preventDefault();  
      const $form = $(this);
      const $submitBtn = $form.find('button[type="submit"]');

      // 1. Basic Validation: Ensure at least one product is added
      if ($("#provisionorderitemsTable tbody tr").length === 0) {
        Swal.fire({
          icon: 'warning',
          title: 'Empty Order',
          text: 'Please add at least one product to the provisional order.'
        });
        return false;
      }

      // 2. Check if 'pickupfrom' selection is made
      let pickupOption = $("input[name='pickupfrom']:checked").val();
      if (!pickupOption) {
        Swal.fire({
          icon: 'warning',
          title: 'Action Required',
          text: 'Please select whether the order will be Picked Up or Delivered.'
        });
        return false;
      }

      // 3. If 'delivered' is selected, ensure freight charge is provided
        let totalFreight = 0;
        let hasEmptyFreight = false;
      if (pickupOption === "delivered") { 
        $(".f_amt_input").each(function() {
            let val = $(this).val().trim();
            if (val === "" || isNaN(parseFloat(val))) {
                hasEmptyFreight = true;
                $(this).addClass("is-invalid");
            } else {
                $(this).removeClass("is-invalid");
                totalFreight += parseFloat(val);
            }
        });

        if (hasEmptyFreight && $(".f_amt_input").length > 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Freight Charges Missing',
                text: 'Please enter freight charges for all delivery locations in Step 4.'
            });
            goToStep(4);
            return false;
        }

        // Optional: Ensure at least some freight if delivered
        if (totalFreight === 0 && $(".f_amt_input").length > 0) {
            // Allow 0 if explicitly entered, but warn if nothing entered
        }
      }else if (pickupOption === "boxpacking") {
        
        $(".freightamount").each(function() {
            let val = $(this).val().trim();
            if (val === "" || isNaN(parseFloat(val))) {
                hasEmptyFreight = true;
                $(this).addClass("is-invalid");
            } else {
                $(this).removeClass("is-invalid");
                totalFreight += parseFloat(val);
            }
        });

        if (hasEmptyFreight && $(".freightamount").length > 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Freight Charges Missing',
                text: 'Please enter freight charges for all delivery locations in Step 4.'
            });
            goToStep(4);
            return false;
        }

        // Optional: Ensure at least some freight if delivered
        if (totalFreight === 0 && $(".freightamount").length > 0) {
            // Allow 0 if explicitly entered, but warn if nothing entered
        }


      }

      // 4. Update Remarks with any applied discount narration before confirmation
      if (typeof window.updateDiscountRemarksNarration === 'function') {
        window.updateDiscountRemarksNarration();
      }

      // 4b. Sowing Department Rule: Order qty cannot exceed sown qty on own stock orders where sowing started
      let sowingQtyViolation = false;
      let violationMsg = "";
      $("#provisionorderitemsTable tbody tr").each(function () {
        let $row = $(this);
        let isSelfSown = ($row.attr("data-is-self-sown") === "1" || $row.data("is-self-sown") == "1");
        if (isSelfSown) {
          let maxSow = parseFloat($row.find(".qty_td").attr("data-sowing-qty") || 0);
          let entered = parseFloat($row.find(".qty_td").val() || 0);
          let pName = $row.find(".productname option:selected").text().trim() || "Item";
          if (maxSow > 0 && entered > maxSow) {
            sowingQtyViolation = true;
            violationMsg = "Order quantity (" + entered + ") cannot exceed sown quantity (" + maxSow + ") for " + pName + ". Please coordinate with the Sowing Department first.";
            return false;
          }
        }
      });
      if (sowingQtyViolation) {
        Swal.fire({
          icon: 'error',
          title: 'Quantity Exceeds Sowing Limit',
          text: violationMsg,
          confirmButtonColor: '#0d6efd'
        });
        return false;
      }

      const isEditOrder = $("#order_mode").val() === "edit";
      const editPsonoVal = $("#edit_psono").val() || "";

      // 5. Confirm before saving
      Swal.fire({
        title: isEditOrder ? 'Confirm Order Update' : 'Confirm Provisional Order',
        text: isEditOrder ? ('Are you sure you want to update this provisional sale order (' + editPsonoVal + ')?') : 'Are you sure you want to submit and create this provisional sale order?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0d6efd',
        cancelButtonColor: '#6c757d',
        confirmButtonText: isEditOrder ? '<i class="fa fa-save me-1"></i> Yes, Update Order' : '<i class="fa fa-check-circle me-1"></i> Yes, Submit Order',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (!result.isConfirmed) {
          return;
        }

        const key = $("body").data("key");
        const mdid = "<?php echo $mdid; ?>";
        const routurl = $("body").data("routurl");

        // Disable button and show loading state
        $submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-2"></i> Saving...');

        // Freight data is now sent via arrays f_amount[], f_vehicle[], f_distance[], f_discount[]
        // so we don't need the old hidden fields if the PHP is updated to loop through them. 
        $.ajax({
          url: "modal/save_provisionalsales_order.php?action=provisionalsalesorder",
          type: "POST",
          data: $form.serialize(),
          dataType: "json",
          success: function (response) {
            console.log(response)
            if (response.status === "success") {
              // Trigger background email process
              if (response.clientid && response.psono) {
                SendEmailProvisionalOrderDetails(response.clientid, response.psono, function (eres) {
                  console.log("Email Notification Response:", eres);
                });
              }

              // Trigger WhatsApp Notification
              whatsapp_sendprovisionalorder(response.clientid, response.psono, function (wa_res) {
                console.log("WhatsApp Response:", wa_res);
              });

              Swal.fire({
                title: (response.mode === "edit" || isEditOrder) ? "Order Updated Successfully!" : "Order Saved Successfully!",
                text: "Provisional Order No: " + response.psono + "\nWhat would you like to do next?",
                icon: "success",
                showDenyButton: true,
                showCancelButton: false,
                confirmButtonColor: '#3085d6',
                denyButtonColor: '#007bff',
                confirmButtonText: (response.mode === "edit" || isEditOrder) ? "Stay on this Order" : "Add More Order",
                denyButtonText: "Review All Orders",
              }).then((result) => {
                if (result.isConfirmed) {
                  window.location.reload();
                } else if (result.isDenied) {
                  window.location.href = "provisionalsaleorder_review.php?k=" + key + "&mdid=" + mdid + "&iframe=true";
                }
              });

            } else {
              $submitBtn.prop('disabled', false).html(isEditOrder ? '<i class="fa fa-save me-2"></i> Update Provisional Order' : '<i class="fa fa-check-circle me-2"></i> Submit to Create Provisional Order');
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: response.message || 'Failed to save the order.'
              });
            }
          },
          error: function (xhr, status, error) {
            $submitBtn.prop('disabled', false).text('Save Provisional Order');
            console.error("Submission Error:", error, xhr.responseText);
            Swal.fire({
              icon: 'error',
              title: 'System Error',
              text: 'An unexpected error occurred while saving the order. Please check the logs.'
            });
          }
        });
      });
    });

    // whatsapp_sendprovisionalorder('C00002', 'PSO-2026-17-00001', function (wa_res) {
    //   console.log("WhatsApp Response:", wa_res);
    // });

    $(document).on("click", ".gotoprovisionalorderlist", function () {
      let companyid = $("body").data("companyid");
      let subcompanyid = $("body").data("subcompanyid");
      let projectid = $("body").data("projectid");
      let routurl = $("body").data("routurl");
      let key = $("body").data("key");
      window.location.href = routurl + "krf-orderandsales/provisionalsaleorder_review.php?k=" + key + "&mdid=<?php echo $mdid; ?>&psono=&iframe=true";
    });
    //ajay
    $(document).on("change", ".chkavqty", function () {
      let $row = $(this).closest('tr');
      let requiredQty = cleanNumber($("#requriedqty_client").val());

      if ($(this).is(":checked")) {
        // --- 🟢 DUPLICATE VALIDATION START 🟢 ---
        let productid = $row.data("productid");
        let deliverydate = $row.data("expecteddelivery_stk"); // The raw date for comparison (YYYY-MM-DD)
        let displayDate = $row.find(".display_ready_date").text().trim() || $row.data("displaydate") || $row.find("td:eq(5)").text().trim(); // The formatted date for the message (DD-MM-YYYY)
        let $activeRow = $("#productname_client").data("rowdata");
        let exists = false;

        // 1. Check against other rows in the main order table
        $("#provisionorderitemsTable tbody tr").not($activeRow).each(function () {
          let $pRow = $(this);
          let pId = $pRow.find(".productname").val();
          let pDate = $pRow.find(".expecteddelivery").val();

          if (pId == productid && pDate == deliverydate) {
            exists = true;
            return false; // break
          }
        });

        // 2. Also check if another row in THIS modal is already checked with the same date
        if (!exists) {
          $(".chkavqty:checked").not(this).each(function () {
            let $mRow = $(this).closest("tr");
            if ($mRow.data("expecteddelivery_stk") == deliverydate) {
              exists = true;
              return false; // break
            }
          });
        }

        if (exists) {
          $(this).prop("checked", false);
          Swal.fire({
            icon: 'warning',
            title: 'Already Added',
            text: 'Already added this product for delivery date: ' + displayDate + '. Please choose a different delivery date.',
            confirmButtonText: 'OK'
          });
          return; // 🛑 Stop here - do not update any allocation values
        }
        // --- 🔴 DUPLICATE VALIDATION END 🔴 ---

        let otherAllotedTotal = 0;
        $(".chkavqty:checked").not(this).each(function () {
          otherAllotedTotal += cleanNumber($(this).closest("tr").find(".alloted_stk").text());
        });

        let remainingNeeded = Math.max(0, requiredQty - otherAllotedTotal);
        let avilableqty_stk = cleanNumber($row.attr("data-availableqty") || $row.find(".balance-display-val").text() || $row.find(".avilableqty_stk").text());

        let allocateQty = Math.min(avilableqty_stk, remainingNeeded);
        $row.find(".alloted_stk").text(allocateQty > 0 ? allocateQty : 0);
        $row.find(".alloted_stk").prop("contenteditable", true);
      } else {
        $row.find(".alloted_stk").text("");
        $row.find(".alloted_stk").prop("contenteditable", false);
      }

      let finalTotal = 0;
      $(".chkavqty:checked").each(function () {
        finalTotal += cleanNumber($(this).closest("tr").find(".alloted_stk").text());
      });

      let remainingNeededAfter = Math.max(0, requiredQty - finalTotal);
      if (remainingNeededAfter > 0 && $(".chkavqty:checked").length > 0) {
        $(".chkavqty:checked").each(function () {
          if (remainingNeededAfter <= 0) return false;
          let $r = $(this).closest("tr");
          let avail = cleanNumber($r.attr("data-availableqty") || $r.find(".balance-display-val").text() || $r.find(".avilableqty_stk").text());
          let curr = cleanNumber($r.find(".alloted_stk").text());
          let canAdd = Math.min(avail - curr, remainingNeededAfter);
          if (canAdd > 0) {
            let updated = curr + canAdd;
            $r.find(".alloted_stk").text(updated);
            finalTotal += canAdd;
            remainingNeededAfter -= canAdd;
          }
        });
      }

      $("#allocatestock").val(finalTotal > 0 ? finalTotal : "");
      let balRemaining = Math.max(0, requiredQty - finalTotal);
      $("#balqty_client").val(balRemaining);

      if (finalTotal > 0) {
        if (balRemaining <= 0) {
          $("#headingstk").text("Required quantity is filled from the stock order?");
          $("#chkboxuncheckall").prop("checked", false);
          $("#chkboxuncheckall").prop("disabled", true);
          if (!$(".chkavqty:not(:checked)").prop("disabled")) {
            Swal.fire({
              icon: 'success',
              title: 'Allocation Complete',
              text: 'The required quantity has been fully allocated.',
              timer: 1500,
              showConfirmButton: false
            });
          }
          $(".chkavqty:not(:checked)").prop("disabled", true);
        } else {
          $("#headingstk").text("Do you want to create a new stock order for the remaining quantity (" + balRemaining + ")?");
          $("#chkboxuncheckall").prop("checked", false);
          $("#chkboxuncheckall").prop("disabled", false);
          $(".chkavqty").prop("disabled", false);
        }
      } else {
        $("#headingstk").text("Do you want to create a completely new stock order?");
        $("#chkboxuncheckall").prop("checked", false);
        $("#chkboxuncheckall").prop("disabled", false);
        $(".chkavqty").prop("disabled", false);
      }
    });

    $(document).on('input', '.alloted_stk', function (e) {
      let $row = $(this).closest('tr');
      let el = this;
      const sel = window.getSelection();
      const range = sel.rangeCount > 0 ? sel.getRangeAt(0) : null;
      let caretOffset = 0;
      if (range) {
        const preCaretRange = range.cloneRange();
        preCaretRange.selectNodeContents(el);
        preCaretRange.setEnd(range.endContainer, range.endOffset);
        caretOffset = preCaretRange.toString().length;
      }

      // --- Clean value: keep only digits
      let originalText = $(this).text();
      let cleanedText = originalText.replace(/\D/g, '');

      // --- Calculate caret shift due to removed non-digit chars
      let nonDigitBeforeCaret = (originalText.slice(0, caretOffset).match(/\D/g) || []).length;
      caretOffset -= nonDigitBeforeCaret;
      if (caretOffset < 0) caretOffset = 0;
      if (caretOffset > cleanedText.length) caretOffset = cleanedText.length;

      // --- Update text only if changed
      if (cleanedText !== originalText) {
        $(this).text(cleanedText);
      }

      // --- Restore caret at same logical position
      const rangeNew = document.createRange();
      const selNew = window.getSelection();
      rangeNew.setStart(el.firstChild || el, caretOffset);
      rangeNew.collapse(true);
      selNew.removeAllRanges();
      selNew.addRange(rangeNew);

      let otherTotal = 0;
      let requiredQty = cleanNumber($("#requriedqty_client").val());

      $(".chkavqty:checked").each(function () {
        let $r = $(this).closest("tr");
        if ($r.is($row)) return; // skip current row
        let qty = cleanNumber($r.find(".alloted_stk").text());
        otherTotal += qty;
      });

      let maxQty = cleanNumber($row.attr('data-availableqty') || $row.find('.balance-display-val').text() || $row.find('.avilableqty_stk').text());
      let bal_qty = Math.max(0, requiredQty - otherTotal);
      let currentVal = parseInt(cleanedText || 0, 10);

      let finalAlloted = otherTotal;
      if (currentVal > 0) {
        if (currentVal <= bal_qty && currentVal <= maxQty) {
          finalAlloted = otherTotal + currentVal;
        } else if (currentVal > maxQty || currentVal > bal_qty) {
          let capped = Math.min(bal_qty, maxQty);
          $(this).text(capped > 0 ? capped : "");
          finalAlloted = otherTotal + capped;
        } else {
          $(this).text("");
        }
      }

      $("#allocatestock").val(finalAlloted > 0 ? finalAlloted : "");
      let balRemaining = Math.max(0, requiredQty - finalAlloted);
      $("#balqty_client").val(balRemaining);

      if (finalAlloted > 0) {
        if (balRemaining <= 0) {
          $("#headingstk").text("Required quantity is filled from the stock order?");
          $("#chkboxuncheckall").prop("checked", false);
          $("#chkboxuncheckall").prop("disabled", true);
          if (!$(".chkavqty:not(:checked)").prop("disabled")) {
            Swal.fire({
              icon: 'success',
              title: 'Allocation Complete',
              text: 'The required quantity has been fully allocated.',
              timer: 1500,
              showConfirmButton: false
            });
          }
          $(".chkavqty:not(:checked)").prop("disabled", true);
        } else {
          $("#headingstk").text("Do you want to create a new stock order for the remaining quantity (" + balRemaining + ")?");
          $("#chkboxuncheckall").prop("checked", false);
          $("#chkboxuncheckall").prop("disabled", false);
          $(".chkavqty").prop("disabled", false);
        }
      } else {
        $("#headingstk").text("Do you want to create a completely new stock order?");
        $("#chkboxuncheckall").prop("checked", false);
        $("#chkboxuncheckall").prop("disabled", false);
        $(".chkavqty").prop("disabled", false);
      }
    });
    $(document).on('paste', '.alloted_stk', function (e) {
      let paste = (e.originalEvent || e).clipboardData.getData('text');
      // only digits allowed
      if (!/^\d+$/.test(paste)) {
        e.preventDefault();
      }
    });

    $(document).on("change", "#provisionorderitemsTable .expecteddelivery", function () {
      let $input = $(this);
      let $row = $input.closest("tr");
      let val = $input.val();
      if (!val) return;

      // Check sowing schedule status
      let sowingStatus = ($row.attr("data-sowing-status") || $row.data("sowing-status") || "").toString().toLowerCase().trim();
      let psono = $("#edit_psono").val() || $("body").data("psono") || "";
      if (sowingStatus && sowingStatus !== "pending") {
        Swal.fire({
          icon: 'error',
          title: 'Cannot Edit Delivery Date',
          html: `This item cannot be edited against <b>${psono || 'this order'}</b> because its sowing schedule status is <b>${sowingStatus}</b>.<br><br><span class="text-muted small">Only items with <b>pending</b> sowing schedule can be modified.</span>`
        });
        $input.val($input.data("old-date") || "");
        return;
      }

      let productVal = $row.find(".productname").val();
      let projectid = $("body").data("projectid") || $("#projectid").val() || 17;
      let stockClient = ($row.attr("data-stock-clientid") || $row.data("stock-clientid") || "").toString().trim().toUpperCase();
      let orderClient = ($("#clientid").val() || "").toString().trim().toUpperCase();
      let stockOrderNo = ($row.attr("data-stockorder-no") || $row.data("stockorder-no") || $row.find(".stockorder-badge").text() || "").toString().trim();
      let clientName = ($("#clientname").val() || $row.attr("data-stock-clientname") || $row.data("stock-clientname") || "").toString().trim();
      if (!clientName && $row.find(".stock-client-badge").length) {
        clientName = $row.find(".stock-client-badge").text().replace(/\s*\([^)]*\)\s*$/, '').trim();
      }

      function proceedDeliveryAfterSowingCheck(calculatedSowingDate, minAllowedDelivery) {
        if (minAllowedDelivery) {
          $input.attr("min", minAllowedDelivery);
        }
        $input.data("old-date", val);

        // Update the Sowing Date Badge dynamically
        if (calculatedSowingDate) {
          $row.attr("data-stock-sowingdate", calculatedSowingDate);
          $row.data("stock-sowingdate", calculatedSowingDate);
          let $sowingBadge = $row.find(".stock-sowingdate-badge");
          if ($sowingBadge.length) {
            $sowingBadge.html(`<i class="fa fa-calendar-alt me-1"></i>Sowing: ${calculatedSowingDate}`);
          } else if ($row.find(".stockorder-badge-container").length) {
            $row.find(".stockorder-badge-container .stockorder-badge").after(`
              <span class="badge bg-info text-dark font-monospace stock-sowingdate-badge" style="font-size:0.68rem;" title="Stock Sowing Date">
                <i class="fa fa-calendar-alt me-1"></i>Sowing: ${calculatedSowingDate}
              </span>
            `);
          }
        }

        // Check if stock order clientid and provisional order clientid are the same
        if (stockClient && orderClient && stockClient === orderClient && stockOrderNo) {
          let clientLabel = clientName ? `Self (${clientName})` : `Self (${stockClient})`;
          Swal.fire({
            icon: 'info',
            title: 'Existing Production Stock Order',
            html: `
              <p class="mb-2">Production stock order <b>${stockOrderNo}</b> is already created for this client <b>${clientLabel}</b> and is unconfirmed.</p>
              <div class="alert alert-success py-2 px-3 text-start mb-2" style="font-size:0.86rem; border-radius:8px;">
                <div><i class="fa fa-calendar-check me-1"></i> <b>New Delivery Date:</b> ${val}</div>
                ${calculatedSowingDate ? `<div><i class="fa fa-seedling me-1"></i> <b>Calculated Sowing Date:</b> ${calculatedSowingDate}</div>` : ''}
              </div>
              <p class="text-muted mb-2" style="font-size:0.88rem;">Stock re-allocation is not required. Expected delivery date and sowing schedule date have been updated.</p>
              <p class="text-muted mb-0" style="font-size:0.84rem;">Do you want to re-allocate this item to another production order instead?</p>
            `,
            showCancelButton: true,
            confirmButtonText: `<i class="fa fa-check me-1"></i> Keep Stock Order (${stockOrderNo})`,
            cancelButtonText: `<i class="fa fa-exchange-alt me-1"></i> Allocate to Other Order`,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#0d6efd'
          }).then((result) => {
            if (result.dismiss === Swal.DismissReason.cancel) {
              $row.data("reallocating-from", stockOrderNo);
              handleExpectedDeliveryChange($row, "tblstockdatamaster");
            }
          });
          return;
        }

        // If product and quantity are already entered, verify stock for the new date
        let qty = parseFloat($row.find(".qty_td").val()) || 0;
        if (productVal && qty > 0) {
          handleExpectedDeliveryChange($row, "tblstockdatamaster");
        }
      }

      if (productVal) {
        $.ajax({
          url: "modal/stockverification.php?action=fecthgrowingdate",
          type: "POST",
          data: {
            projectid: projectid,
            productid: productVal,
            deliverydate: val
          },
          dataType: "json",
          success: function (res) {
            if (res && res.length > 0) {
              let info = res[0];
              let minAllowedDelivery = info.mindeliverydate || "";
              let sowingDate = info.sowingdate || info.rootsowingdate || "";
              let todayDate = info.today || "<?php echo date('Y-m-d'); ?>";

              // Enforce: Sowing date CANNOT be less than today (no back-date sowing allowed under any condition)
              if (info.is_backdate || (sowingDate && sowingDate < todayDate)) {
                Swal.fire({
                  icon: 'error',
                  title: 'Back-Date Sowing Not Allowed',
                  html: `
                    <p class="mb-2">The selected delivery date <b>${val}</b> results in a sowing date of <b class="text-danger">${sowingDate}</b>, which is before today (<b class="text-dark">${todayDate}</b>).</p>
                    <div class="alert alert-danger py-2 px-3 text-start mb-0" style="font-size:0.86rem; border-radius:8px;">
                      <i class="fa fa-ban me-1"></i> <b>Rule:</b> Sowing date cannot be less than today. Back date sowing is not allowed under any condition.<br>
                      <span class="mt-1 d-block text-dark">Earliest allowed delivery date: <b class="text-success">${minAllowedDelivery}</b> (sowing date: ${todayDate}).</span>
                    </div>
                  `,
                  confirmButtonText: minAllowedDelivery ? `Set Earliest Delivery Date (${minAllowedDelivery})` : 'OK',
                  confirmButtonColor: '#0d6efd',
                  showCancelButton: true,
                  cancelButtonText: 'Cancel',
                  cancelButtonColor: '#6c757d',
                  allowOutsideClick: false
                }).then((choice) => {
                  if (choice.isConfirmed && minAllowedDelivery) {
                    $input.val(minAllowedDelivery).trigger("change");
                  } else {
                    $input.val($input.data("old-date") || minAllowedDelivery);
                  }
                });
                return;
              }

              proceedDeliveryAfterSowingCheck(sowingDate, minAllowedDelivery);
            } else {
              proceedDeliveryAfterSowingCheck("", "");
            }
          },
          error: function () {
            proceedDeliveryAfterSowingCheck("", "");
          }
        });
      } else {
        proceedDeliveryAfterSowingCheck("", "");
      }
    });

    $(document).on("blur", ".qty_td", function () {
      let $input = $(this);
      let $row = $input.closest("tr");
      let oldDate = $input.data("old-date") || "";
      let qty = parseFloat($input.val()) || 0;
      let productVal = $row.find(".productname").val();

      // Check sowing schedule status
      let isTrader = ($row.attr("data-is-trader") === "1" || $row.data("is-trader") == "1" || ($row.attr("data-stock-clientid") || "").toUpperCase() === "C00001");
      let sowingStatus = ($row.attr("data-sowing-status") || $row.data("sowing-status") || "").toString().toLowerCase().trim();
      let psono = $("#edit_psono").val() || $("body").data("psono") || "";
      if (!isTrader && sowingStatus && sowingStatus !== "pending") {
        Swal.fire({
          icon: 'error',
          title: 'Cannot Edit Quantity',
          html: `This item cannot be edited against <b>${psono || 'this order'}</b> because its sowing schedule status is <b>${sowingStatus}</b>.<br><br><span class="text-muted small">Only items with <b>pending</b> sowing schedule can be modified.</span>`
        });
        let origQty = parseFloat($row.attr("data-orig-qty")) || 0;
        $input.val(origQty);
        return;
      }

      /* 🔴 Product validation */
      if (!productVal) {
        Swal.fire({
          icon: "warning",
          title: "Product Required",
          text: "Please select or enter product name before setting requested delivery date."
        }).then(() => {
          $input.val("");
        });
        return;
      }

      /* 🔴 Quantity validation */
      if (qty <= 0) {
        Swal.fire({
          icon: "warning",
          title: "Invalid Quantity",
          text: "Please enter a valid quantity before changing requested delivery date."
        }).then(() => {
          $input.val("");
        });
        return;
      }

      // Check if stock order clientid and provisional order clientid are the same
      let stockClient = ($row.attr("data-stock-clientid") || $row.data("stock-clientid") || "").toString().trim().toUpperCase();
      let orderClient = ($("#clientid").val() || "").toString().trim().toUpperCase();
      let stockOrderNo = ($row.attr("data-stockorder-no") || $row.data("stockorder-no") || $row.find(".stockorder-badge").text() || "").toString().trim();
      let clientName = ($("#clientname").val() || $row.attr("data-stock-clientname") || $row.data("stock-clientname") || "").toString().trim();
      if (!clientName && $row.find(".stock-client-badge").length) {
        clientName = $row.find(".stock-client-badge").text().replace(/\s*\([^)]*\)\s*$/, '').trim();
      }

      if (stockClient && orderClient && stockClient === orderClient && stockOrderNo) {
        let clientLabel = clientName ? `Self (${clientName})` : `Self (${stockClient})`;
        // Same client: No need to open 2nd screenshot (Stock Allocation & Verification modal)
        // Show warning prompt
        Swal.fire({
          icon: 'warning',
          title: 'Existing Production Stock Order',
          html: `
            <p class="mb-2">Production stock order <b>${stockOrderNo}</b> is already created for this client <b>${clientLabel}</b> and is unconfirmed.</p>
            <p class="mb-2">Stock re-allocation is not required. Quantity has been updated to <b>${qty}</b>.</p>
            <p class="text-muted mb-2" style="font-size:0.95rem;">Do you want to re-allocate this item to another production order instead?</p>
            <div class="alert alert-info py-2 px-3 mt-3 text-start mb-0 border-0" style="font-size:0.84rem; background-color:#e0f2fe; color:#0369a1; border-radius: 8px;">
              <i class="fa fa-info-circle me-1"></i> <b>Notice:</b> If allocated to another production order, it will update <code>tedit</code> with remark 'itself production order' when updating the provisional order.
            </div>
          `,
          showCancelButton: true,
          confirmButtonText: `<i class="fa fa-check me-1"></i> Keep Stock Order (${stockOrderNo})`,
          cancelButtonText: `<i class="fa fa-exchange-alt me-1"></i> Allocate to Other Order`,
          confirmButtonColor: '#198754',
          cancelButtonColor: '#0d6efd'
        }).then((result) => {
          if (result.dismiss === Swal.DismissReason.cancel) {
            $row.data("reallocating-from", stockOrderNo);
            handleExpectedDeliveryChange($row, "tblstockdatamaster");
          } else {
            $row.find(".ratecal").trigger("input");
          }
        });
        return;
      }

      /* 🔎 Check if stock already allocated */
      let stkid = "";
      $("#provisionorderitemsTable tbody tr").each(function () {
        let $r = $(this);
        if (
          $r.find(".productname").val() === productVal &&
          ($r.find(".stockdbid").val() || "")
        ) {
          stkid = $r.find(".stockdbid").val();
          return false; // break loop
        }
      });

      /* ⚠️ Confirmation if stock exists */
      if (stkid) {
        Swal.fire({
          icon: "warning",
          title: "Stock Already Allocated",
          text: "Stock has already been allocated for this product. Do you want to proceed again?",
          showCancelButton: true,
          confirmButtonText: "Yes, Proceed",
          cancelButtonText: "No, Cancel"
        }).then((result) => {
          if (!result.isConfirmed) {
            $row.find(".expecteddelivery").val(oldDate);
            return;
          }
          handleExpectedDeliveryChange($row, "tblstockdatamaster");
        });
      } else {
        handleExpectedDeliveryChange($row, "tblstockdatamaster");
      }
    });



  });
  let activeStockCheckXhr = null;
  function handleExpectedDeliveryChange(rowElement, tableid) {
    $("#balqty_client").val('');

    var $table = $("#" + tableid + " tbody");
    $table.empty();
    var projectid = $('body').data('projectid');
    var loginid = $('body').data('loginid');
    const $row = $(rowElement);
    $("#productname_client").removeData();
    $("#productname_client").data("rowdata", $row);
    const productid = $row.find(".productname").val()
    const deliverydate = $row.find(".expecteddelivery").val();

    // 🛑 Removed mergeDuplicateProductsInTable to prevent clearing existing dates/rows
    // mergeDuplicateProductsInTable("provisionorderitemsTable", productid);

    const productname = $row.find(".productname option:selected").text()
    //$row.find(".productname").find("select").val();  
    const qty = $row.find(".qty_td").val();

    let dateObj = new Date(deliverydate);
    let day = String(dateObj.getDate()).padStart(2, '0');
    let month = String(dateObj.getMonth() + 1).padStart(2, '0'); // months are 0-based
    let year = dateObj.getFullYear();
    let formattedDate = `${day}-${month}-${year}`;
    $("#productname_client").val(productname);
    // $("#expecteddelivery_client").val(formattedDate);
    $("#requriedqty_client").val(qty);
    $("#allocatestock").val('');
    $("#balqty_client").val(qty);
    let formData = new FormData();
    formData.append('projectid', projectid);
    formData.append('loginid', loginid);
    formData.append('productid', productid);
    formData.append('requiredqty', qty);
    formData.append('deliverydate', deliverydate);
    formData.append('current_psono', $("#edit_psono").val() || "");
    let consumedQty = 0;
    var rows = "";
    var k = 1;

    if (activeStockCheckXhr) {
      activeStockCheckXhr.abort();
    }

    $table.html(`
      <tr>
        <td colspan="11" class="text-center py-4 text-primary">
          <div class="spinner-border spinner-border-sm me-2" role="status"></div>
          <span class="fw-semibold">Loading available production stock...</span>
        </td>
      </tr>
    `);
    $("#stockdataModal").modal("show");

    activeStockCheckXhr = $.ajax({
      url: "modal/stockverification.php?action=chkstock",
      type: "POST",
      data: formData,
      dataType: "json",
      contentType: false,  // ✅ Required when using FormData
      processData: false,  // ✅ Required when using FormData
      cache: false,
      error: function (xhr, status, error) {
        if (status === 'abort') return;
        $table.html(`
          <tr>
            <td colspan="11" class="text-center py-4 text-danger fw-semibold">
              <i class="fa fa-exclamation-triangle me-2"></i>Failed to fetch stock data (${error || 'Server error'}).
            </td>
          </tr>
        `);
      },
      success: function (res) {
        $table.empty();
        $("#searchStockTableInput").val('');
        if (res && res.length > 0) {
          for (var i = 0; i < res.length; i++) {
            let allottedItem = "";
            let alreadyAllotted = !!allottedItem;
            consumedQty = allottedItem ? allottedItem.consumedqty : 0;
            finalstk = Number(res[i].stkqty || 0)   + Number(res[i].readytosale_qty || 0); 

            rows += `
                <tr data-dbsno="${res[i].stksno}"
                    data-productid="${res[i].productid}"
                    data-displaydate="${res[i].displaydate || ''}"
                    data-expecteddelivery_stk="${res[i].expected_delivery_date}"
                    data-orderstkqty="${res[i].stkqty}"
                    data-readytosale_qty="${res[i].readytosale_qty}"
                    data-confirmed_allocated="${res[i].confirmed_allocated}"
                    data-unconfirmed_allocated="${res[i].unconfirmed_allocated}"
                    data-rso_movement="${res[i].rso_movement || 0}"
                    data-availableqty="${res[i].availableqty || 0}"
                    >
                    <td class="text-center text-muted font-monospace">${k}</td>
                    <td class='row-checkbox text-center noexport'>
                        <input type='checkbox' class='chkavqty' name='chkavqty'
                                style='transform: scale(1.4); cursor: pointer;' ${alreadyAllotted ? "checked" : ""}>
                    </td>
                    <td class="fw-semibold text-dark" style="min-width: 170px; max-width: 260px; white-space: normal; word-break: break-word;">${res[i].productname || ''}</td>
                    <td class="text-nowrap font-monospace text-secondary">
                        <div class="fw-bold">${res[i].stockorder_no || ''}</div>
                        ${res[i].stock_type === 'client_surplus' ? `
                            <div class="mt-1">
                                <span class="badge bg-warning text-dark px-1.5 py-0.5" style="font-size: 0.68rem; font-weight: 600; border: 1px solid #d39e00; letter-spacing: 0.2px;">
                                    <i class="fa fa-layer-group me-1"></i>Surplus After Dispatched
                                </span>
                            </div>
                            ${(res[i].client_name || res[i].clientid) ? `
                                <div class="mt-1">
                                    <span class="badge bg-primary text-white px-2 py-0.5 text-truncate" style="font-size: 0.7rem; font-weight: 600; max-width: 175px; display: inline-block; vertical-align: middle; box-shadow: 0 1px 2px rgba(0,0,0,0.1);" title="Client: ${res[i].client_name || res[i].clientid}">
                                        <i class="fa fa-user me-1"></i>${res[i].client_name || res[i].clientid}
                                    </span>
                                </div>
                            ` : ''}
                        ` : ''}
                    </td>
                    <td class="text-nowrap">
                        <div class="fw-bold text-dark"><i class="fa fa-map-marker-alt text-danger me-1"></i>${res[i].locationname || 'N/A'}</div>
                        ${res[i].polyhouse_details ? `
                            <div class="small text-secondary mt-0.5" style="font-size:0.75rem; line-height: 1.35;">
                                <i class="fa fa-warehouse text-primary me-1"></i>${res[i].polyhouse_details}
                            </div>
                        ` : (res[i].total_trays > 0 ? `
                            <div class="small text-muted" style="font-size:0.75rem;">
                                ${res[i].total_trays} Trays (${Number(res[i].total_plants || 0).toLocaleString()} Plants)
                            </div>
                        ` : '<div class="small text-muted" style="font-size:0.75rem;">-</div>')}
                    </td>
                    <td class="text-nowrap display_ready_date">${res[i].displaydate || ''}</td>
                    <td class="text-center fw-semibold">${Number(finalstk || 0).toLocaleString()}</td>
                    <td class="text-center">
                        ${Number(res[i].confirmed_allocated || 0) > 0 ? `
                            <span class="badge bg-primary text-white px-2 py-1 view-stock-alloc-btn"
                                  style="cursor: pointer; font-size: 0.82rem; font-weight: 600; box-shadow: 0 1px 3px rgba(13,110,253,0.25);"
                                  data-stockorderno="${res[i].stockorder_no || ''}"
                                  data-productid="${res[i].productid || ''}"
                                  data-productname="${(res[i].productname || '').replace(/"/g, '&quot;')}"
                                  data-type="confirmed"
                                  data-qty="${res[i].confirmed_allocated}"
                                  title="Click to view Confirmed Allocations (${res[i].confirmed_allocated})">
                                <i class="fa fa-users me-1" style="font-size: 0.72rem;"></i>${Number(res[i].confirmed_allocated).toLocaleString()}
                            </span>
                        ` : `<span class="text-muted fw-semibold">0</span>`}
                    </td>
                    <td class="text-center">
                        ${Number(res[i].unconfirmed_allocated || 0) > 0 ? `
                            <span class="badge bg-warning text-dark px-2 py-1 view-stock-alloc-btn"
                                  style="cursor: pointer; font-size: 0.82rem; font-weight: 600; border: 1px solid #d97706; box-shadow: 0 1px 3px rgba(245,158,11,0.2);"
                                  data-stockorderno="${res[i].stockorder_no || ''}"
                                  data-productid="${res[i].productid || ''}"
                                  data-productname="${(res[i].productname || '').replace(/"/g, '&quot;')}"
                                  data-type="unconfirmed"
                                  data-qty="${res[i].unconfirmed_allocated}"
                                  title="Click to view Unconfirmed Allocations (${res[i].unconfirmed_allocated})">
                                <i class="fa fa-clock me-1" style="font-size: 0.72rem;"></i>${Number(res[i].unconfirmed_allocated).toLocaleString()}
                            </span>
                        ` : `<span class="text-muted fw-semibold">0</span>`}
                    </td>
                    <td class="text-center">
                        ${Number(res[i].rso_movement || 0) > 0 ? `
                            <span class="badge text-white px-2 py-1"
                                  style="background-color: #6f42c1; font-size: 0.82rem; font-weight: 600; box-shadow: 0 1px 3px rgba(111,66,193,0.25);"
                                  title="RSO Movement: ${Number(res[i].rso_movement).toLocaleString()} Plants">
                                <i class="fa fa-truck-ramp-box me-1" style="font-size: 0.72rem;"></i>${Number(res[i].rso_movement).toLocaleString()}
                            </span>
                        ` : `<span class="text-muted fw-semibold">0</span>`}
                    </td>
                    <td class="avilableqty_stk text-center fw-bold text-success text-nowrap" data-availableqty="${res[i].availableqty || 0}">
                        <span class="balance-display-val">${Number(res[i].availableqty || 0).toLocaleString()}</span>
                        ${(getEffectiveLoginRoleId() <= 3) ? `
                        <button type="button" class="btn btn-sm btn-outline-success border-0 p-1 ms-1 edit-balance-btn"
                                style="line-height: 1; vertical-align: middle; border-radius: 4px;"
                                data-stockorderno="${res[i].stockorder_no || ''}"
                                data-productid="${res[i].productid || ''}"
                                data-productname="${(res[i].productname || '').replace(/"/g, '&quot;')}"
                                data-projectid="${res[i].projectid || ''}"
                                data-balance="${res[i].availableqty || 0}"
                                title="Edit Balance / Consume difference as Karjee Farm">
                            <i class="fa fa-pencil-alt text-success" style="font-size: 0.8rem;"></i>
                        </button>
                        ` : ''}
                    </td>
                    <td class="alloted_stk text-center fw-bold bg-light"></td>
                </tr>`;
            k++;
          }
        } else {
          rows += `
        <tr>
          <td colspan="12" style="text-align:center;font-weight:bold;">
            Stock is not available for this product
          </td>
        </tr>`;
        }
        $table.html(rows);
        $("#searchStockTableInput").val('');
        updateStockSummaryBadges();
        if (consumedQty > 0) {
          $("#allocatestock").val(consumedQty);
        }
        let finalBalQty = cleanNumber($("#requriedqty_client").val()) - cleanNumber($("#allocatestock").val());
        $("#balqty_client").val(finalBalQty);
        sowingcalculation();

        if (finalBalQty <= 0 && cleanNumber($("#allocatestock").val()) > 0) {
          $("#headingstk").text("Required quantity is filled from the stock order?");
          setTimeout(() => { $(".chkavqty:not(:checked)").prop("disabled", true); }, 100);
        } else {
          $("#headingstk").text("Do you want to create a completely new stock order?");
        }
        $(".emergencycheckbox").prop("checked", false);
         $(".emergencycheckbox").removeData("sowingdt");
         $(".emergencycheckbox").removeData("sowingRddt");
        $("#stockdataModal").modal("show");
      }
    });
  }

  function updateStockSummaryBadges() {
    let totConf = 0;
    let totUnconf = 0;
    let totRsoMv = 0;
    let totBal = 0;

    $("#tblstockdatamaster tbody tr").each(function () {
      const $tr = $(this);
      if ($tr.find("td").length <= 1) return;
      if ($tr.is(":hidden")) return;

      totConf += Number($tr.attr("data-confirmed_allocated") || 0);
      totUnconf += Number($tr.attr("data-unconfirmed_allocated") || 0);
      totRsoMv += Number($tr.attr("data-rso_movement") || 0);
      totBal += Number($tr.attr("data-availableqty") || 0);
    });

    $("#badgeTotalConfirmed").text(totConf.toLocaleString());
    $("#badgeTotalUnconfirmed").text(totUnconf.toLocaleString());
    $("#badgeTotalRsoMovement").text(totRsoMv.toLocaleString());
    $("#badgeTotalBalance").text(totBal.toLocaleString());
  }

  $(document).on("input", "#searchStockTableInput", function () {
    let filter = $(this).val().toLowerCase().trim();
    $("#tblstockdatamaster tbody tr").each(function () {
      if ($(this).find("td").length <= 1) { return; }
      let text = $(this).text().toLowerCase();
      $(this).toggle(!filter || text.indexOf(filter) > -1);
    });
    updateStockSummaryBadges();
  });

  /* ==========================================================
   * STOCK ALLOCATION MODAL & BALANCE ADJUSTMENT HANDLERS
   * ========================================================== */
  let currentAllocModalStockOrder = '';
  let currentAllocModalProductId = 0;
  let currentAllocModalProductName = '';
  let currentAllocModalType = '';

  function refreshStockDataMasterTable() {
    let $row = $("#productname_client").data("rowdata");
    if ($row && $row.length > 0) {
      handleExpectedDeliveryChange($row[0], "tblstockdatamaster");
    }
  }

  function loadStockAllocationsModal(stockorder, productId, productName, type) {
    currentAllocModalStockOrder = stockorder;
    currentAllocModalProductId = productId;
    currentAllocModalProductName = productName;
    currentAllocModalType = type;

    const isConf = (type === 'confirmed');
    $('#allocModalTitlePrefix').text(isConf ? 'Confirmed Allocations' : 'Unconfirmed Allocations');
    $('#stockAllocModalHeader').css('background', isConf 
      ? 'linear-gradient(135deg, #0d6efd 0%, #052c65 100%)' 
      : 'linear-gradient(135deg, #d97706 0%, #78350f 100%)'
    );
    $('#stockAllocModalIcon').attr('class', isConf ? 'fa fa-users text-primary' : 'fa fa-clock text-warning');
    $('#allocModalStockOrderNo').text(stockorder);
    $('#allocModalProductName').text(productName);
    $('#allocModalTotalBadge').text('Loading...');
    $('#searchAllocModalTable').val('');

    const canSeeActions = (getEffectiveLoginRoleId() <= 3);
    $('#tblStockAllocClients .col-alloc-actions').toggle(canSeeActions);

    const $tbody = $('#tblStockAllocClientsBody');
    $tbody.html(`
      <tr>
        <td colspan="${canSeeActions ? 10 : 9}" class="text-center py-4">
          <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
          <span class="text-muted fw-semibold">Loading allocations data...</span>
        </td>
      </tr>
    `);

    $('#stockAllocClientsModal').modal('show');

    $.ajax({
      url: 'modal/stockverification.php?action=get_stock_allocations',
      type: 'POST',
      data: {
        stockorder_no: stockorder,
        product_id: productId,
        type: type
      },
      dataType: 'json',
      success: function(resp) {
        if (resp && resp.status && resp.data) {
          const list = resp.data;
          const totalQty = resp.total_qty || 0;
          $('#allocModalTotalBadge').text(`Total: ${Number(totalQty).toLocaleString()} Plants`);
          $('#allocModalRowCount').text(list.length);

          if (list.length === 0) {
            $tbody.html(`
              <tr>
                <td colspan="${canSeeActions ? 10 : 9}" class="text-center py-4 text-muted">
                  <i class="fa fa-info-circle me-1"></i> No ${isConf ? 'confirmed' : 'unconfirmed'} allocations found for this stock order.
                </td>
              </tr>
            `);
            return;
          }

          let html = '';
          list.forEach((item, index) => {
            const isInternal = item.is_internal || (item.psono === 'KF-INTERNAL');
            const statusClass = (item.confirmstatus && item.confirmstatus.toLowerCase() === 'confirmed')
              ? 'bg-success text-white'
              : 'bg-warning text-dark';
            const displayStatus = item.confirmstatus || item.pso_status || 'Pending';

            const dispStatus = item.dispatch_status || 'Not Dispatched';
            const dispBadgeClass = item.dispatch_badge_class || 'bg-light text-secondary border';
            const isDispatched = !!item.is_dispatched;

            html += `
              <tr data-consumed-id="${item.consumed_id}" data-psono="${item.psono || ''}">
                <td class="text-center font-monospace text-muted">${index + 1}</td>
                <td class="font-monospace">
                  ${isInternal ? `
                    <span class="badge bg-secondary px-2 py-1">KF-INTERNAL</span>
                  ` : `
                    <span class="fw-bold text-primary">${item.psono || '-'}</span>
                  `}
                </td>
                <td>
                  <div class="fw-bold text-dark">${item.clientname || 'Karjee Farm'}</div>
                  ${item.mobileno && item.mobileno !== '-' ? `
                    <div class="small text-muted"><i class="fa fa-phone me-1 text-success"></i>${item.mobileno}</div>
                  ` : ''}
                </td>
                <td class="text-center text-nowrap">${item.order_date || '-'}</td>
                <td class="text-center text-nowrap">${item.expected_delivery_date || '-'}</td>
                <td class="text-center fw-bold ${isConf ? 'text-primary' : 'text-warning-emphasis'}">
                  ${Number(item.consumed_qty || 0).toLocaleString()}
                </td>
                <td class="text-center">
                  ${isInternal ? `
                    <span class="badge bg-info text-dark px-2 py-1">Internal Farm</span>
                  ` : `
                    <span class="badge ${statusClass} px-2 py-1">${displayStatus}</span>
                  `}
                </td>
                <td class="text-center text-nowrap">
                  <span class="badge ${dispBadgeClass} px-2 py-1" style="font-size: 0.78rem;">
                    <i class="fa ${isDispatched ? 'fa-truck' : (dispStatus === 'Partial Dispatch' ? 'fa-boxes' : (dispStatus.indexOf('Ready') > -1 ? 'fa-dolly' : 'fa-clock'))} me-1"></i>
                    ${dispStatus}
                  </span>
                  ${item.dispatched_at ? `
                    <div class="small text-muted mt-0.5" style="font-size:0.72rem;" title="Dispatched At">
                      <i class="fa fa-calendar-check text-success me-0.5"></i>${item.dispatched_at}
                    </div>
                  ` : ''}
                  ${item.invoicenos ? `
                    <div class="small text-primary mt-0.5 text-truncate" style="font-size:0.72rem; max-width: 140px;" title="Invoice(s): ${item.invoicenos}">
                      <i class="fa fa-file-invoice me-0.5"></i>${item.invoicenos}
                    </div>
                  ` : ''}
                </td>
                <td class="small text-secondary" style="max-width: 220px; white-space: normal; word-break: break-word;">
                  ${item.consumed_remark || item.item_remark || '-'}
                </td>
                ${canSeeActions ? `
                <td class="text-center text-nowrap col-alloc-actions">
                  ${!isInternal && item.psono ? `
                    <button type="button" class="btn btn-sm btn-outline-primary py-0.5 px-2 fw-semibold btn-edit-pso me-1" 
                            data-psono="${item.psono}" title="Edit Provisional Order #${item.psono}">
                      <i class="fa fa-edit me-1"></i> Edit Order
                    </button>
                  ` : ''}
                  ${isDispatched ? `
                    <button type="button" class="btn btn-sm btn-outline-secondary py-0.5 px-2 fw-semibold" disabled title="Cannot remove: This order allocation is already dispatched">
                      <i class="fa fa-lock me-1"></i> Dispatched
                    </button>
                  ` : `
                    <button type="button" class="btn btn-sm btn-outline-danger py-0.5 px-2 fw-semibold btn-remove-alloc-prod"
                            data-consumed-id="${item.consumed_id}"
                            data-psoi-id="${item.psoi_id || ''}"
                            data-psono="${item.psono || ''}"
                            data-stockorder="${stockorder}"
                            data-productid="${productId}"
                            data-productname="${(item.product_name || productName || '').replace(/"/g, '&quot;')}"
                            data-qty="${item.consumed_qty}"
                            data-clientname="${(item.clientname || '').replace(/"/g, '&quot;')}"
                            data-is-internal="${isInternal ? '1' : '0'}"
                            title="${isInternal ? 'Cancel internal consumption to restore balance' : 'Remove product from provisional order with remark'}">
                      <i class="fa ${isInternal ? 'fa-undo' : 'fa-trash'} me-1"></i> ${isInternal ? 'Restore' : 'Remove'}
                    </button>
                  `}
                </td>
                ` : ''}
              </tr>
            `;
          });
          $tbody.html(html);
        } else {
          $tbody.html(`
            <tr>
              <td colspan="${canSeeActions ? 10 : 9}" class="text-center py-4 text-danger">
                <i class="fa fa-exclamation-triangle me-1"></i> Failed to load allocations: ${resp && resp.message ? resp.message : 'Unknown error'}
              </td>
            </tr>
          `);
        }
      },
      error: function(xhr, status, error) {
        $tbody.html(`
          <tr>
            <td colspan="${canSeeActions ? 10 : 9}" class="text-center py-4 text-danger">
              <i class="fa fa-exclamation-triangle me-1"></i> Communication error: ${error}
            </td>
          </tr>
        `);
      }
    });
  }

  // Click on Conf. Alloc or Unconf. Alloc Badge
  $(document).on('click', '.view-stock-alloc-btn', function (e) {
    e.preventDefault();
    const stockorder = $(this).data('stockorderno');
    const productId = $(this).data('productid');
    const productName = $(this).data('productname');
    const type = $(this).data('type');
    loadStockAllocationsModal(stockorder, productId, productName, type);
  });

  // Click Edit Order button in Allocation Modal
  $(document).on('click', '.btn-edit-pso', function (e) {
    e.preventDefault();
    if (getEffectiveLoginRoleId() > 3) {
      Swal.fire('Access Denied', 'Only Admin roles (Role <= 3) can edit orders.', 'error');
      return;
    }
    const psono = $(this).data('psono');
    if (!psono) return;
    const urlParams = new URLSearchParams(window.location.search);
    const key = urlParams.get('k') || '';
    const mdid = urlParams.get('mdid') || '<?php echo addslashes($mdid); ?>';
    const routurl = $('body').data('routurl') || '../';
    const editUrl = `${routurl}krf-orderandsales/addNewProvisionalsaleorder.php?mode=edit&psono=${encodeURIComponent(psono)}&k=${encodeURIComponent(key)}&mdid=${encodeURIComponent(mdid)}&iframe=true`;
    window.open(editUrl, '_blank');
  });

  // Click Remove Product button in Allocation Modal
  $(document).on('click', '.btn-remove-alloc-prod', function (e) {
    e.preventDefault();
    if (getEffectiveLoginRoleId() > 3) {
      Swal.fire('Access Denied', 'Only Admin roles (Role <= 3) can remove allocations.', 'error');
      return;
    }
    const $btn = $(this);
    const consumedId = $btn.data('consumed-id');
    const psoiId = $btn.data('psoi-id') || '';
    const psono = $btn.data('psono');
    const stockorder = $btn.data('stockorder');
    const productId = $btn.data('productid') || '';
    const productName = $btn.data('productname') || 'Product';
    const qty = $btn.data('qty') || 0;
    const clientName = $btn.data('clientname') || '';
    const isInternal = $btn.data('is-internal') == '1';

    if (isInternal) {
      Swal.fire({
        title: 'Restore Stock Balance?',
        html: `
          <div class="text-start">
            <p class="mb-2">This will cancel the Karjee Farm internal consumption of <strong>${Number(qty).toLocaleString()} plants</strong> for Stock Order <strong>${stockorder}</strong> and restore the balance.</p>
            <label class="form-label fw-bold small text-secondary">Remark / Reason for Restoration <span class="text-danger">*</span></label>
            <textarea id="swal_remove_remark" class="form-control" rows="3" placeholder="Enter reason for restoring stock balance (Mandatory)..."></textarea>
          </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fa fa-undo me-1"></i> Yes, Restore Balance',
        cancelButtonText: 'Cancel',
        focusConfirm: false,
        preConfirm: () => {
          const remark = $('#swal_remove_remark').val().trim();
          if (!remark) {
            Swal.showValidationMessage('Remark / reason is mandatory.');
            return false;
          }
          return { remark: remark, financial_action: 'credit_note' };
        }
      }).then((result) => {
        if (result.isConfirmed) {
          executeRemoveAllocation(consumedId, psoiId, psono, productId, stockorder, result.value.remark, result.value.financial_action, productName);
        }
      });
      return;
    }

    // Step 1: Pre-check impact of removal via AJAX
    Swal.fire({
      title: 'Checking Order Details...',
      text: 'Verifying order items and payment status...',
      allowOutsideClick: false,
      didOpen: () => { Swal.showLoading(); }
    });

    $.ajax({
      url: 'modal/stockverification.php?action=check_removal_impact',
      type: 'POST',
      data: {
        consumed_id: consumedId,
        psoi_id: psoiId,
        psono: psono,
        product_id: productId,
        stockorder_no: stockorder
      },
      dataType: 'json',
      success: function(chk) {
        if (!chk || !chk.status) {
          Swal.fire('Cannot Remove', (chk && chk.message) ? chk.message : 'Failed to verify order details.', 'error');
          return;
        }

        const isFullRemoval = !!chk.is_full_removal;
        const totalPaid = parseFloat(chk.total_paid || 0);
        const clName = chk.clientname || clientName || 'Customer';
        const pso = chk.psono || psono;
        const remaining = chk.remaining_active_items || 0;

        let titleText = isFullRemoval ? '⚠️ Full Order Cancellation & Settlement' : 'Remove Product from Provisional Order?';
        let bodyHtml = '';

        if (isFullRemoval) {
          if (totalPaid > 0) {
            bodyHtml = `
              <div class="text-start">
                <div class="alert alert-danger py-2 px-3 mb-3 small d-flex align-items-start gap-2 shadow-xs">
                  <i class="fa fa-exclamation-triangle text-danger fs-5 mt-0.5"></i>
                  <div>
                    <strong class="d-block text-danger">Warning: Full Order Cancellation!</strong>
                    Removing <strong>${productName}</strong> will cancel the <strong>ENTIRE Provisional Order #${pso}</strong> for <strong>${clName}</strong> because all items will be cancelled.
                  </div>
                </div>

                <div class="p-2.5 rounded border mb-3 bg-light d-flex justify-content-between align-items-center">
                  <div>
                    <div class="small text-muted fw-semibold">Advance Payment Received Against Order:</div>
                    <div class="fs-5 fw-bold text-success">
                      <i class="fa fa-money-bill-wave me-1"></i> ₹ ${totalPaid.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                    </div>
                  </div>
                  <span class="badge bg-success text-white px-2 py-1">Payment Received</span>
                </div>

                <label class="form-label fw-bold small text-dark mb-1">
                  Choose Financial Settlement Option <span class="text-danger">*</span>:
                </label>
                <div class="mb-3">
                  <div class="p-2.5 border rounded mb-2 bg-white shadow-xs d-flex align-items-center" style="cursor: pointer;" onclick="$('#settle_cn').prop('checked', true);">
                    <input class="form-check-input mt-0" type="radio" name="swal_financial_settlement" id="settle_cn" value="credit_note" checked style="transform: none !important; -webkit-transform: none !important; position: static !important; accent-color: #0d6efd; width: 18px; height: 18px; margin: 0 8px 0 0 !important; flex-shrink: 0; cursor: pointer;">
                    <label class="form-check-label mb-0" for="settle_cn" style="cursor: pointer; margin-left: 6px;">
                      <div class="fw-bold text-primary"><i class="fa fa-file-invoice me-1"></i> Issue Credit Note</div>
                      <div class="text-muted" style="font-size: 0.78rem;">Generate an official Credit Note & credit ₹ ${totalPaid.toLocaleString('en-IN', { minimumFractionDigits: 2 })} to customer ledger balance.</div>
                    </label>
                  </div>
                  <div class="p-2.5 border rounded bg-white shadow-xs d-flex align-items-center" style="cursor: pointer;" onclick="$('#settle_cash').prop('checked', true);">
                    <input class="form-check-input mt-0" type="radio" name="swal_financial_settlement" id="settle_cash" value="refund" style="transform: none !important; -webkit-transform: none !important; position: static !important; accent-color: #dc3545; width: 18px; height: 18px; margin: 0 8px 0 0 !important; flex-shrink: 0; cursor: pointer;">
                    <label class="form-check-label mb-0" for="settle_cash" style="cursor: pointer; margin-left: 6px;">
                      <div class="fw-bold text-danger"><i class="fa fa-hand-holding-usd me-1"></i> Return Cash / Refund</div>
                      <div class="text-muted" style="font-size: 0.78rem;">Return payment to customer as Cash / Bank refund and record write-off in client ledger.</div>
                    </label>
                  </div>
                </div>

                <label class="form-label fw-bold small text-secondary">Cancellation Remark / Reason <span class="text-danger">*</span></label>
                <textarea id="swal_remove_remark" class="form-control" rows="2" placeholder="Enter mandatory cancellation reason / remark..."></textarea>
              </div>
            `;
          } else {
            bodyHtml = `
              <div class="text-start">
                <div class="alert alert-warning py-2 px-3 mb-3 small d-flex align-items-start gap-2 shadow-xs">
                  <i class="fa fa-exclamation-triangle text-warning fs-5 mt-0.5"></i>
                  <div>
                    <strong class="d-block text-dark">Full Provisional Order Cancellation</strong>
                    This is the only/last active item in <strong>${pso}</strong> (${clName}). Removing it will cancel the entire provisional order.
                  </div>
                </div>
                <div class="p-2 rounded border mb-3 bg-light small text-muted">
                  <i class="fa fa-info-circle text-info me-1"></i> No advance payment was received (₹ 0.00). No credit note or refund required.
                </div>
                <label class="form-label fw-bold small text-secondary">Cancellation Remark / Reason <span class="text-danger">*</span></label>
                <textarea id="swal_remove_remark" class="form-control" rows="2" placeholder="Enter mandatory cancellation remark..."></textarea>
              </div>
            `;
          }
        } else {
          bodyHtml = `
            <div class="text-start">
              <div class="alert alert-warning py-2 mb-3 small">
                <i class="fa fa-exclamation-triangle me-1"></i> You are removing <strong>${productName}</strong> (Allocated: <strong>${Number(qty).toLocaleString()}</strong>) from Order <strong>${pso}</strong> (${clName}).
                <div class="mt-1 text-muted"><em>(${remaining} other product(s) will remain active in this order).</em></div>
              </div>
              <label class="form-label fw-bold small text-secondary">Cancellation Remark / Reason <span class="text-danger">*</span></label>
              <textarea id="swal_remove_remark" class="form-control" rows="3" placeholder="Enter mandatory cancellation remark..."></textarea>
            </div>
          `;
        }

        Swal.fire({
          title: titleText,
          html: bodyHtml,
          icon: isFullRemoval ? (totalPaid > 0 ? 'warning' : 'question') : 'warning',
          showCancelButton: true,
          confirmButtonColor: '#dc3545',
          cancelButtonColor: '#6c757d',
          confirmButtonText: isFullRemoval 
            ? (totalPaid > 0 ? '<i class="fa fa-check me-1"></i> Yes, Confirm & Settle' : '<i class="fa fa-trash me-1"></i> Yes, Cancel Whole Order') 
            : '<i class="fa fa-trash me-1"></i> Yes, Remove Product',
          cancelButtonText: 'Cancel / Keep Order',
          focusConfirm: false,
          preConfirm: () => {
            const remark = $('#swal_remove_remark').val().trim();
            if (!remark) {
              Swal.showValidationMessage('Remark / reason is mandatory.');
              return false;
            }
            const finAction = $("input[name='swal_financial_settlement']:checked").val() || 'credit_note';
            return { remark: remark, financial_action: finAction };
          }
        }).then((result) => {
          if (result.isConfirmed) {
            executeRemoveAllocation(consumedId, psoiId, psono, productId, stockorder, result.value.remark, result.value.financial_action, productName);
          }
        });
      },
      error: function(xhr, status, error) {
        Swal.fire('Error', 'Communication error checking order details: ' + error, 'error');
      }
    });
  });

  function executeRemoveAllocation(consumedId, psoiId, psono, productId, stockorder, remark, financialAction, productName) {
    Swal.fire({
      title: 'Processing...',
      text: 'Removing product allocation and processing settlement...',
      allowOutsideClick: false,
      didOpen: () => { Swal.showLoading(); }
    });

    $.ajax({
      url: 'modal/stockverification.php?action=remove_stock_allocation',
      type: 'POST',
      data: {
        consumed_id: consumedId,
        psoi_id: psoiId,
        psono: psono,
        product_id: productId,
        stockorder_no: stockorder,
        remark: remark,
        financial_action: financialAction || 'credit_note',
        projectid: $('body').data('projectid') || 1,
        loginroleid: getEffectiveLoginRoleId()
      },
      dataType: 'json',
      success: function(resp) {
        if (resp && resp.status) {
          Swal.fire({
            icon: 'success',
            title: resp.is_full_removal ? 'Order Cancelled' : 'Product Removed',
            text: resp.message || 'Product removed successfully.',
            confirmButtonColor: '#10b981'
          });
          // Refresh allocations modal table
          loadStockAllocationsModal(stockorder, productId, productName, currentAllocModalType);
          // Refresh parent stock verification table
          refreshStockDataMasterTable();
        } else {
          Swal.fire('Error', resp && resp.message ? resp.message : 'Failed to remove product.', 'error');
        }
      },
      error: function(xhr, status, error) {
        Swal.fire('Error', 'Communication error with server: ' + error, 'error');
      }
    });
  }

  // Click Edit Balance button in Stock Table
  $(document).on('click', '.edit-balance-btn', function (e) {
    e.preventDefault();
    e.stopPropagation();
    if (getEffectiveLoginRoleId() > 3) {
      Swal.fire('Access Denied', 'Only Admin roles (Role <= 3) can edit stock balance.', 'error');
      return;
    }
    const $btn = $(this);
    const stockorder = $btn.data('stockorderno');
    const productId = $btn.data('productid');
    const productName = $btn.data('productname');
    const projectId = $btn.data('projectid') || $('body').data('projectid') || 1;
    const currentBalance = parseInt($btn.data('balance') || 0);

    const modalHtml = `
      <div class="text-start">
        <div class="p-2 mb-3 rounded bg-light border">
          <div class="d-flex justify-content-between mb-1">
            <span class="text-muted small">Stock Order:</span>
            <span class="fw-bold font-monospace">${stockorder}</span>
          </div>
          <div class="d-flex justify-content-between mb-1">
            <span class="text-muted small">Product:</span>
            <span class="fw-semibold text-truncate" style="max-width: 250px;">${productName}</span>
          </div>
          <div class="d-flex justify-content-between">
            <span class="text-muted small">Current Available Balance:</span>
            <span class="fw-bold text-success" id="curr_bal_display">${currentBalance.toLocaleString()} Plants</span>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-bold small text-secondary">
            New Balance Quantity <span class="text-danger">*</span>
          </label>
          <input type="number" id="swal_new_balance" class="form-control form-control-lg text-center fw-bold text-primary" 
                 value="${currentBalance}" min="0" step="1" placeholder="Enter new balance">
          <div class="form-text small">Enter updated balance quantity (can be increased or decreased).</div>
        </div>

        <div id="swal_lessed_info_box" class="alert alert-secondary py-2 px-3 mb-3 small">
          <i class="fa fa-info-circle me-1"></i> Enter a new balance quantity to adjust stock.
        </div>

        <div class="mb-2">
          <label class="form-label fw-bold small text-secondary">
            Correction Remark / Reason <span class="text-danger">*</span>
          </label>
          <textarea id="swal_balance_remark" class="form-control" rows="2" placeholder="e.g. Damage plants / Karjee Farm consumption / stock count correction (Mandatory)..."></textarea>
        </div>
      </div>
    `;

    Swal.fire({
      title: '<i class="fa fa-balance-scale text-primary me-2"></i>Edit Stock Balance',
      html: modalHtml,
      showCancelButton: true,
      confirmButtonColor: '#198754',
      cancelButtonColor: '#6c757d',
      confirmButtonText: '<i class="fa fa-check-circle me-1"></i> Update Balance',
      cancelButtonText: 'Cancel',
      focusConfirm: false,
      willOpen: () => {
        $(document).off('focusin.bs.modal');
      },
      didOpen: () => {
        $(document).off('focusin.bs.modal');
        const $newBal = $('#swal_new_balance');
        const $infoBox = $('#swal_lessed_info_box');
        const confirmBtn = Swal.getConfirmButton();

        function updateDiff() {
          const val = $newBal.val();
          if (val === '' || isNaN(val)) {
            $infoBox.removeClass('alert-info alert-success alert-warning alert-danger').addClass('alert-secondary')
              .html('<i class="fa fa-info-circle me-1"></i> Please enter a valid balance quantity.');
            confirmBtn.disabled = true;
            return;
          }
          const n = parseInt(val);
          if (n < 0) {
            $infoBox.removeClass('alert-info alert-success alert-warning alert-secondary').addClass('alert-danger')
              .html('<i class="fa fa-exclamation-circle me-1"></i> New balance cannot be negative.');
            confirmBtn.disabled = true;
          } else if (n === currentBalance) {
            $infoBox.removeClass('alert-danger alert-warning alert-secondary').addClass('alert-info')
              .html('<i class="fa fa-info-circle me-1"></i> Balance quantity: <strong>' + currentBalance.toLocaleString() + '</strong> Plants. Enter a new balance to adjust, or add a remark.');
            confirmBtn.disabled = false;
          } else if (n < currentBalance) {
            const diffQty = currentBalance - n;
            $infoBox.removeClass('alert-danger alert-success alert-secondary alert-info').addClass('alert-warning')
              .html('<i class="fa fa-arrow-down text-danger me-1"></i> Balance will be <strong>reduced</strong> by <strong class="text-danger">' + diffQty.toLocaleString() + '</strong> plants (consumed as Karjee Farm).');
            confirmBtn.disabled = false;
          } else {
            const diffQty = n - currentBalance;
            $infoBox.removeClass('alert-danger alert-warning alert-secondary alert-info').addClass('alert-success')
              .html('<i class="fa fa-arrow-up text-success me-1"></i> Balance will be <strong>increased</strong> by <strong class="text-success">' + diffQty.toLocaleString() + '</strong> plants (New balance: <strong>' + n.toLocaleString() + '</strong>).');
            confirmBtn.disabled = false;
          }
        }

        $newBal.on('input keyup change paste', updateDiff);
        setTimeout(() => {
          $newBal.focus().select();
        }, 80);
        updateDiff();
      },
      didClose: () => {
        $(document).off('focusin.bs.modal');
      },
      preConfirm: () => {
        const val = $('#swal_new_balance').val();
        const n = parseInt(val);
        const remark = $('#swal_balance_remark').val().trim();

        if (isNaN(n) || n < 0) {
          Swal.showValidationMessage('Please enter a valid non-negative balance.');
          return false;
        }
        if (!remark) {
          Swal.showValidationMessage('Correction remark is mandatory.');
          return false;
        }
        return {
          new_balance: n,
          remark: remark
        };
      }
    }).then((result) => {
      if (result.isConfirmed) {
        const newBal = result.value.new_balance;
        const remark = result.value.remark;

        Swal.fire({
          title: 'Updating Balance...',
          text: 'Saving updated stock balance...',
          allowOutsideClick: false,
          didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
          url: 'modal/stockverification.php?action=adjust_stock_balance',
          type: 'POST',
          data: {
            stockorder_no: stockorder,
            product_id: productId,
            product_name: productName,
            projectid: projectId,
            current_balance: currentBalance,
            new_balance: newBal,
            remark: remark,
            loginroleid: getEffectiveLoginRoleId()
          },
          dataType: 'json',
          success: function(resp) {
            if (resp && resp.status) {
              Swal.fire({
                title: 'Balance Updated!',
                text: resp.message || 'Balance updated successfully.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
              });
              // Refresh parent stock verification table immediately
              refreshStockDataMasterTable();
            } else {
              Swal.fire('Error', resp && resp.message ? resp.message : 'Failed to update balance.', 'error');
            }
          },
          error: function(xhr, status, error) {
            Swal.fire('Error', 'Communication error with server: ' + error, 'error');
          }
        });
      }
    });
  });

  // Filter in Allocation Modal
  $(document).on("input", "#searchAllocModalTable", function () {
    let filter = $(this).val().toLowerCase().trim();
    $("#tblStockAllocClients tbody tr").each(function () {
      let text = $(this).text().toLowerCase();
      $(this).toggle(!filter || text.indexOf(filter) > -1);
    });
  });

  // Ensure parent modal stays scrollable when child modal closes
  $('#stockAllocClientsModal').on('hidden.bs.modal', function () {
    if ($('#stockdataModal').hasClass('show') || $('#stockdataModal').is(':visible')) {
      $('body').addClass('modal-open');
    }
  });

  $(document).on("click", ".emergencycheckbox", function (e) {
    const $chk = $(this);
    const isChecking = $chk.is(":checked");

    // Cache baseline dates if not already saved
    if (!$chk.data("sowingdt")) {
      $chk.data("sowingdt", $("#basesowingdate").text());
      $chk.data("sowingRddt", $("#stkreadydt").val());
    }
    if (!$chk.data("sowingRddt") && $("#stkreadydt").val()) {
      $chk.data("sowingRddt", $("#stkreadydt").val());
    }

    if (isChecking) {
      // Revert checked state temporarily until user confirms
      $chk.prop("checked", false);

      const minDate = "<?php echo date('Y-m-d', strtotime('+1 day')); ?>";
      const maxDate = "<?php echo date('Y-m-d', strtotime('+3 days')); ?>";

      Swal.fire({
        title: 'Emergency Sowing Confirmation',
        html: `
          <div class="text-start p-1" style="font-size: 0.92rem;">
            <p class="mb-3 text-dark fw-semibold">
              Have you confirmed with both the <span class="text-primary">Sowing Department</span> and <span class="text-primary">Procurement Department</span> regarding availability?
            </p>
            <div class="p-3 bg-light rounded-3 border mb-3">
              <div class="d-flex align-items-center gap-2 mb-2 text-dark">
                <i class="fa fa-seedling text-success"></i> <span><strong>Seed Availability & Stock</strong></span>
              </div>
              <div class="d-flex align-items-center gap-2 mb-2 text-dark">
                <i class="fa fa-th-large text-primary"></i> <span><strong>Trays & Nursery Space Capacity</strong></span>
              </div>
              <div class="d-flex align-items-center gap-2 text-dark">
                <i class="fa fa-calendar-check text-info"></i> <span><strong>Emergency Sowing Timeline Feasibility</strong></span>
              </div>
            </div>

          <!-- Sowing Date -->
            <div class="mb-3">
              <label for="emergencySowingDate" class="form-label fw-bold text-dark mb-1">
                <i class="fa fa-calendar-alt text-primary me-1"></i>
                Sowing Date <span class="text-muted small fw-normal">(Next 3 days only)</span>
              </label>

              <input type="date"
                     id="emergencySowingDate"
                     class="form-control"
                     value="${minDate}"
                     min="${minDate}"
                     max="${maxDate}"
                     placeholder="Select Sowing Date"
                     autocomplete="off"
                      >
            </div>
            <div class="alert alert-warning border-0 p-2 rounded-2 mb-0 d-flex align-items-center gap-2" style="font-size: 0.85rem; background-color: #fef3c7; color: #92400e;">
              <i class="fa fa-exclamation-triangle flex-shrink-0"></i>
              <span>Please proceed only if both departments have verified and approved availability.</span>
            </div>
          </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '<i class="fa fa-check-circle me-1"></i> Yes, Confirmed & Proceed',
        cancelButtonText: '<i class="fa fa-times me-1"></i> Cancel',
        confirmButtonColor: '#0d6efd',
        cancelButtonColor: '#64748b',
        focusConfirm: false,
        allowOutsideClick: false,
        customClass: {
          confirmButton: 'btn btn-primary px-4 py-2 fw-bold',
          cancelButton: 'btn btn-secondary px-4 py-2 fw-semibold'
        },
        preConfirm: () => {
          const selectedDate = $("#emergencySowingDate").val();
          if (!selectedDate) {
            Swal.showValidationMessage("Please select a sowing date.");
            return false;
          }
          if (selectedDate < minDate || selectedDate > maxDate) {
            Swal.showValidationMessage(`Please choose a sowing date within the next 3 days (${minDate} to ${maxDate}).`);
            return false;
          }
          return selectedDate;
        }
      }).then((result) => {
        if (result.isConfirmed) {
          $chk.prop("checked", true);
          const chosenSowingDate = result.value;
          $("#is_emergency_sowing").val("yes");
          const formattedSowingDate = (typeof window.changeDateFormatYmd_dmY === "function")
            ? window.changeDateFormatYmd_dmY(chosenSowingDate)
            : (typeof changeDateFormatYmd_dmY === "function" ? changeDateFormatYmd_dmY(chosenSowingDate) : chosenSowingDate);
          $("#basesowingdate").html(formattedSowingDate);

          // Calculate growing days from baseline original sowing date to baseline ready date
          const origSowingStr = dmyToYmd($chk.data("sowingdt") || "");
          const origReadyStr = dmyToYmd($chk.data("sowingRddt") || "");

          let growingDays = 0;
          if (origSowingStr && origReadyStr) {
            const dSowing = new Date(origSowingStr + "T00:00:00");
            const dReady = new Date(origReadyStr + "T00:00:00");
            growingDays = Math.round((dReady - dSowing) / (1000 * 60 * 60 * 24));
          }

          if (growingDays > 0) {
            const finalDate = addDaysToDate(chosenSowingDate, growingDays);
            $("#stkreadydt").val(finalDate).attr("min", chosenSowingDate);
          } else {
            $("#stkreadydt").val(chosenSowingDate).attr("min", chosenSowingDate);
          }
          
        } else {
          $chk.prop("checked", false);
          $("#is_emergency_sowing").val("no");
        }
      });
    } else {
      // Unchecked: restore baseline original sowing date
      $("#is_emergency_sowing").val("no");
      $("#basesowingdate").html($chk.data("sowingdt"));

      // Restore baseline original ready date
      const sowingRddt = $chk.data("sowingRddt");
      if (sowingRddt) {
        $("#stkreadydt").val(sowingRddt).attr("min", sowingRddt);
      }
    }
  });

  $(document).on("change", "#stkreadydt", function () {
    let newDeliveryDate = $(this).val();
    if (!newDeliveryDate) return;
    let $row = $("#productname_client").data("rowdata");
    if (!$row || !$row.length) return;
    let projectid = $("body").data("projectid") || 17;
    let productid = $row.find(".productname").val();
    if (!productid) return;

    let formData = new FormData();
    formData.append("projectid", projectid);
    formData.append("productid", productid);
    formData.append("deliverydate", newDeliveryDate);
    formData.append("is_emergency", $(".emergencycheckbox").is(":checked") ? "yes" : "no");

    $.ajax({
      url: "modal/stockverification.php?action=fecthgrowingdate",
      type: "POST",
      data: formData,
      dataType: "json",
      contentType: false,
      processData: false,
      cache: false,
      success: function (res) {
        if (res && res.length > 0) {
          let rawSowingDate = res[0].sowingdate || res[0].rootsowingdate || "";
          let formattedSowingDate = (typeof window.changeDateFormatYmd_dmY === "function")
            ? window.changeDateFormatYmd_dmY(rawSowingDate)
            : (typeof changeDateFormatYmd_dmY === "function" ? changeDateFormatYmd_dmY(rawSowingDate) : rawSowingDate);

          if (formattedSowingDate) {
            $("#basesowingdate").html(formattedSowingDate);
            $(".emergencycheckbox").data("sowingdt", formattedSowingDate);
            $(".emergencycheckbox").data("sowingRddt", newDeliveryDate);
          }
        }
      }
    });
  });
function addDaysToDate(ymdDate, days) {
    const date = new Date(ymdDate + "T00:00:00");
    date.setDate(date.getDate() + Number(days));
    return (
        date.getFullYear() + "-" +
        String(date.getMonth() + 1).padStart(2, "0") + "-" +
        String(date.getDate()).padStart(2, "0")
    );
}
function dmyToYmd(dateStr) {
    if (!dateStr) return '';
    dateStr = dateStr.trim();
    const parts = dateStr.split('-');
    if (parts.length !== 3) return '';
    // If already in YYYY-MM-DD format
    if (parts[0].length === 4) return dateStr;
    // If in DD-MM-YYYY format
    const [day, month, year] = parts;
    return `${year}-${month}-${day}`;
}
  function clearnotaccepted() {
    let $row = $("#productname_client").data("rowdata");
    let totalRequiredQty = parseInt($row.find(".qty_td").val()) || 0;
    const productId = $row.find(".productname").val();
    const discount = $row.find(".distprt_td").val();
    const rate_td = $row.find(".rate_td").val();
    $row.find(".expecteddelivery").val("");
    $row.find(".stockdbid").val('');
    $row.find(".rate_td").val('');
    $row.find(".qty_td").val('');
    $row.find(".distprt_td").val('');
    $row.find(".distamt_td").val('');
    $row.find(".productname").val('').trigger("change");
    Invoicegrandtotalgstamont('totalamount', 'gsttotalamount', 'ttldiscountamount', 'grandtotalamount', 'payableamount_td', 'cgstamt_td', 'sgstamt_td', 'igstamt_td', 'distamt_td', 'provisionorderitemsTable');
    $("#stockdataModal").modal("hide");
  }


  // function closemodalfunction() {
  //   let allqty = parseInt($("#allocatestock").val(), 10) || 0;
  //   let requiredqty = parseInt($("#requriedqty_client").val(), 10) || 0;
  //   let createstkqty = parseInt($("#balqty_client").val(), 10) || 0;

  //   // Strict Validation: Ensure existing stock covers the full required quantity
  //   // Unallocated Balance check logic:
  //   // If Allocated Qty is less than Required Qty, stop and ask to allocate from other production order.
  //   if (createstkqty > 0) {
  //     Swal.fire({
  //       icon: "error",
  //       title: "Insufficient Stock Allocation",
  //       html: `
  //               You have allocated <b>${allqty}</b> out of <b>${requiredqty}</b> required.<br>
  //               Remaining Quantity: <b>${createstkqty}</b><br><br>
  //               Please select another production order to fulfill the remaining quantity or ensure full allocation from available stock.
  //           `,
  //       confirmButtonText: "OK, I'll Select More",
  //       buttonsStyling: false,
  //       customClass: {
  //         confirmButton: 'btn btn-primary fw-bold px-4'
  //       }
  //     });
  //     return;
  //   }

  //   finalclosemodalfunction();
  // }


  function finalclosemodalfunction() {
    let $row = $("#productname_client").data("rowdata");
    if (!$row || !$row.length) return;

    // ─── Helper: format Date object as YYYY-MM-DD ───────────────────────────
    function _fmtYMD(d) {
      const y  = d.getFullYear();
      const m  = ('0' + (d.getMonth() + 1)).slice(-2);
      const dd = ('0' + d.getDate()).slice(-2);
      return y + '-' + m + '-' + dd;
    }
    // ─── Helper: format YYYY-MM-DD → DD-Mon-YYYY for display ────────────────
    function _fmtDisplay(ymd) {
      if (!ymd) return ymd;
      const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
      const parts = ymd.split('-');
      if (parts.length !== 3) return ymd;
      return parts[2] + '-' + months[parseInt(parts[1], 10) - 1] + '-' + parts[0];
    }

    const today    = new Date(); today.setHours(0, 0, 0, 0);
    const tomorrow = new Date(today); tomorrow.setDate(today.getDate() + 1);
    const todayYMD    = _fmtYMD(today);
    const tomorrowYMD = _fmtYMD(tomorrow);

    // ─── Step 1: Scan selected rows for any past expected-ready dates ────────
    let olderRows = []; // collect stocks whose ready date < today

    $("#tblstockdatamaster tbody tr input[type='checkbox']:checked").each(function () {
      const $stkrow      = $(this).closest("tr");
      const allotedQty   = cleanNumber($stkrow.find(".alloted_stk").text());
      if (allotedQty <= 0) return;
      const expectedDelivery = $stkrow.data("expecteddelivery_stk") || ""; // YYYY-MM-DD
      if (!expectedDelivery) return;

      const expDate = new Date(expectedDelivery);
      expDate.setHours(0, 0, 0, 0);
      if (expDate < today) {
        const diffDays   = Math.round((today - expDate) / (1000 * 60 * 60 * 24));
        const displayDate = $stkrow.find(".display_ready_date").text().trim() || _fmtDisplay(expectedDelivery);
        const stockOrderNo = $stkrow.find("td:eq(3) div.fw-bold").text().trim() || $stkrow.find("td:eq(3)").text().trim() || "N/A";
        olderRows.push({ $stkrow: $stkrow, expectedDelivery: expectedDelivery, diffDays: diffDays, stockOrderNo: stockOrderNo, allotedQty: allotedQty, displayDate: displayDate });
      }
    });

    // ─── Step 2: If past dates found, ask user to pick Today or Tomorrow ─────
    if (olderRows.length > 0) {
      var rowsHtml = '';
      for (var ri = 0; ri < olderRows.length; ri++) {
        var r = olderRows[ri];
        rowsHtml += '<div class="d-flex align-items-start gap-2 py-2 border-bottom">' +
          '<div class="flex-shrink-0 mt-1">' +
            '<span class="badge bg-secondary font-monospace" style="font-size:0.72rem;">' +
              '<i class="fa fa-layer-group me-1"></i>' + r.stockOrderNo +
            '</span>' +
          '</div>' +
          '<div class="flex-grow-1">' +
            '<div class="small text-muted mb-1">' +
              '<i class="fa fa-calendar-alt text-warning me-1"></i>' +
              'Stock Ready Date: <strong>' + r.displayDate + '</strong>' +
            '</div>' +
            '<span class="badge bg-danger px-2 py-1" style="font-size:0.78rem;">' +
              '<i class="fa fa-clock me-1"></i>' + r.diffDays + ' day' + (r.diffDays === 1 ? '' : 's') + ' older than today' +
            '</span>' +
          '</div>' +
        '</div>';
      }

      var swalHtml =
        '<div class="text-start">' +
          '<div class="alert alert-warning border-0 py-2 px-3 mb-3 rounded-3 d-flex align-items-center gap-2" style="background:#fff3cd;">' +
            '<i class="fa fa-exclamation-triangle text-warning fs-5"></i>' +
            '<span class="small fw-semibold text-dark">' +
              'The selected stock order' + (olderRows.length > 1 ? 's have' : ' has') + ' an expected ready date <strong>older than today</strong>.' +
              ' Please choose a new delivery date for this allocation:' +
            '</span>' +
          '</div>' +
          '<div class="mb-3 px-1" style="max-height:180px; overflow-y:auto;">' +
            rowsHtml +
          '</div>' +
          '<label class="form-label fw-bold mb-2 text-dark">' +
            '<i class="fa fa-calendar-check text-primary me-1"></i>Select New Delivery Date:' +
          '</label>' +
          '<div class="d-flex flex-column gap-2">' +

            /* ── Today ── */
            '<label id="lbl_date_today" style="cursor:pointer; border:2px solid #0d6efd; background:#f0f6ff; border-radius:10px; padding:10px 14px; display:flex; align-items:center; gap:12px;">' +
              '<input type="radio" name="swal_delivery_date_opt" value="' + todayYMD + '" ' +
                     'id="swal_opt_today" checked ' +
                     'style="width:17px;height:17px;flex-shrink:0;accent-color:#0d6efd;cursor:pointer;">' +
              '<div style="line-height:1.3;">' +
                '<div style="font-weight:700;font-size:0.9rem;color:#0d6efd;">Today</div>' +
                '<div style="font-size:0.78rem;color:#6c757d;">' + _fmtDisplay(todayYMD) + '</div>' +
              '</div>' +
            '</label>' +

            /* ── Tomorrow ── */
            '<label id="lbl_date_tomorrow" style="cursor:pointer; border:2px solid #dee2e6; border-radius:10px; padding:10px 14px; display:flex; align-items:center; gap:12px;">' +
              '<input type="radio" name="swal_delivery_date_opt" value="' + tomorrowYMD + '" ' +
                     'id="swal_opt_tomorrow" ' +
                     'style="width:17px;height:17px;flex-shrink:0;accent-color:#198754;cursor:pointer;">' +
              '<div style="line-height:1.3;">' +
                '<div style="font-weight:700;font-size:0.9rem;color:#198754;">Tomorrow</div>' +
                '<div style="font-size:0.78rem;color:#6c757d;">' + _fmtDisplay(tomorrowYMD) + '</div>' +
              '</div>' +
            '</label>' +

            /* ── Keep actual stock ready date ── */
            '<label id="lbl_date_keepactual" style="cursor:pointer; border:2px solid #dee2e6; border-radius:10px; padding:10px 14px; display:flex; align-items:center; gap:12px;">' +
              '<input type="radio" name="swal_delivery_date_opt" value="__keep_actual__" ' +
                     'id="swal_opt_keepactual" ' +
                     'style="width:17px;height:17px;flex-shrink:0;accent-color:#6f42c1;cursor:pointer;">' +
              '<div style="line-height:1.3;">' +
                '<div style="font-weight:700;font-size:0.9rem;color:#6f42c1;">' +
                  '<i class="fa fa-history me-1"></i>Keep Actual Stock Date' +
                '</div>' +
                '<div style="font-size:0.78rem;color:#6c757d;" id="lbl_keep_actual_dates"></div>' +
              '</div>' +
            '</label>' +

          '</div>' +
        '</div>';

      Swal.fire({
        title: '<i class="fa fa-calendar-times text-warning me-2"></i>Delivery Date Outdated',
        html: swalHtml,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '<i class="fa fa-check-circle me-1"></i> Confirm &amp; Allocate',
        cancelButtonText: '<i class="fa fa-times me-1"></i> Cancel',
        confirmButtonColor: '#0d6efd',
        cancelButtonColor: '#6c757d',
        width: '520px',
        didOpen: function () {
          // Populate the "actual dates" label from olderRows
          var actualDatesText = olderRows.map(function(r) {
            return r.stockOrderNo + ': ' + r.displayDate;
          }).join(' | ');
          $('#lbl_keep_actual_dates').text(actualDatesText);

          function _highlightCards() {
            var val = $('input[name="swal_delivery_date_opt"]:checked').val();
            // Reset all
            $('#lbl_date_today').css({ 'border-color': '#dee2e6', 'background': '' });
            $('#lbl_date_tomorrow').css({ 'border-color': '#dee2e6', 'background': '' });
            $('#lbl_date_keepactual').css({ 'border-color': '#dee2e6', 'background': '' });
            if (val === todayYMD) {
              $('#lbl_date_today').css({ 'border-color': '#0d6efd', 'background': '#f0f6ff' });
            } else if (val === tomorrowYMD) {
              $('#lbl_date_tomorrow').css({ 'border-color': '#198754', 'background': '#f0fff4' });
            } else if (val === '__keep_actual__') {
              $('#lbl_date_keepactual').css({ 'border-color': '#6f42c1', 'background': '#f5f0ff' });
            }
          }
          $('input[name="swal_delivery_date_opt"]').on('change', _highlightCards);
          _highlightCards(); // init highlight for today (default checked)
        },
        preConfirm: function () {
          var chosen = $('input[name="swal_delivery_date_opt"]:checked').val();
          if (!chosen) {
            Swal.showValidationMessage('Please select a delivery date.');
            return false;
          }
          return chosen;
        }
      }).then(function (result) {
        if (!result.isConfirmed || !result.value) return; // cancelled — do nothing
        _doAllocate(result.value);
      });

    } else {
      // ─── No past dates – proceed immediately without prompt ───────────────
      _doAllocate(null);
    }

    // ─── Core allocation logic ────────────────────────────────────────────────
    // overrideDate: YYYY-MM-DD string to use for any row whose stock ready date is past.
    //               null = use each row's own expectedDelivery date as-is.
    function _doAllocate(overrideDate) {
      $row.find(".stockdbid").val("");
      let totalRequiredQty = parseInt($row.find(".qty_td").val(), 10) || 0;
      const productId = $row.find(".productname").val();
      const discount  = $row.find(".distprt_td").val();
      const rate_td   = $row.find(".rate_td").val();
      let rowCreated   = false;
      let fromReadyStk = 0;

      /* 🔁 Allocate selected stock rows */
      $("#tblstockdatamaster tbody tr input[type='checkbox']:checked").each(function (i) {
        const $stkrow      = $(this).closest("tr");
        const allotedQty   = cleanNumber($stkrow.find(".alloted_stk").text());
        if (allotedQty <= 0) return;

        const expectedDelivery = $stkrow.data("expecteddelivery_stk") || ""; // original YYYY-MM-DD
        const stkid            = $stkrow.data("dbsno") || "";
        const readytosale_qty  = cleanNumber($stkrow.data("readytosale_qty"));
        const orderStkQty      = cleanNumber($stkrow.data("orderstkqty"));
        const confirmedQty     = cleanNumber($stkrow.data("confirmed_allocated"));
        const unconfirmedQty   = cleanNumber($stkrow.data("unconfirmed_allocated"));
        const allottedQty      = cleanNumber(allotedQty);

        const actualStk = orderStkQty - (confirmedQty + unconfirmedQty) + allottedQty;
        fromReadyStk = 0;
        if (actualStk > 0 && readytosale_qty > 0) fromReadyStk = actualStk;

        totalRequiredQty -= allotedQty;

        // Determine delivery date to put in the order row:
        // '__keep_actual__' → use the stock's own original date (even if past)
        // overrideDate (Today/Tomorrow) → use it for any past-dated rows
        // null → no override, use stock's own date
        let deliveryDateToUse = expectedDelivery;
        if (overrideDate && overrideDate !== '__keep_actual__') {
          const expDate = new Date(expectedDelivery);
          expDate.setHours(0, 0, 0, 0);
          if (expDate < today) deliveryDateToUse = overrideDate;
        }
        // if '__keep_actual__': deliveryDateToUse stays as expectedDelivery (original stock date)

        let $targetRow;
        if (i === 0) {
          $targetRow = $row;
        } else {
          $("#addRow").trigger("click");
          $targetRow = $("#provisionorderitemsTable tbody tr:last");
          $targetRow.addClass("row_ignore");
        }

        $targetRow.find(".productname").val(productId).trigger("change");
        $targetRow.find(".rate_td").val(rate_td);
        $targetRow.find(".qty_td").val(allotedQty);
        $targetRow.find(".expecteddelivery").val(deliveryDateToUse); // ← chosen date (or original if not past)
        $targetRow.find(".distprt_td").val(discount);
        $targetRow.find(".stockdbid").val(stkid);
        $targetRow.find(".isreadytosale").val(fromReadyStk);

        const newStockNo = $stkrow.find("td:eq(3) div.fw-bold").text().trim() || $stkrow.find("td:eq(3)").text().trim();
        if (newStockNo) {
          $targetRow.attr("data-stockorder-no", newStockNo);
          $targetRow.attr("data-stock-id", stkid);
          $targetRow.find(".stockorder-badge-container").remove();
          // Badge always shows the original stock ready date (not the delivery date chosen by user)
          $targetRow.find(".expecteddelivery").after(
            '<div class="mt-1 d-flex flex-wrap gap-1 align-items-center stockorder-badge-container">' +
              '<span class="badge bg-secondary font-monospace stockorder-badge" style="font-size:0.68rem;" title="Allocated Stock Order">' +
                '<i class="fa fa-layer-group me-1"></i>' + newStockNo +
              '</span>' +
              '<span class="badge bg-info text-dark font-monospace stock-sowingdate-badge" style="font-size:0.68rem;" title="Expected Ready Date">' +
                '<i class="fa fa-calendar-alt me-1"></i>Ready: ' + expectedDelivery +
              '</span>' +
            '</div>'
          );
        }

        $targetRow.find(".ratecal").trigger("input");
        rowCreated = true;
      });

      /* ➕ Handle remaining quantity (new stock) */
      let remainingQty = parseInt($("#balqty_client").val(), 10) || 0;
      if (remainingQty > 0) {
        let $targetRow;
        if (rowCreated) {
          $("#addRow").trigger("click");
          $targetRow = $("#provisionorderitemsTable tbody tr:last");
        } else {
          $targetRow = $row;
        }
        $targetRow.addClass("row_ignore");
        $targetRow.find(".productname").val(productId).trigger("change");
        $targetRow.find(".rate_td").val(rate_td);
        $targetRow.find(".qty_td").val(remainingQty);
        $targetRow.find(".expecteddelivery").val($("#stkreadydt").val());
        $targetRow.find(".distprt_td").val(discount);
        $targetRow.find(".stockdbid").val("");
        $targetRow.find(".isreadytosale").val('');
        $targetRow.find(".ratecal").trigger("input");
      }

      /* 🔄 Recalculate totals */
      Invoicegrandtotalgstamont(
        'totalamount',
        'gsttotalamount',
        'ttldiscountamount',
        'grandtotalamount',
        'payableamount_td',
        'cgstamt_td',
        'sgstamt_td',
        'igstamt_td',
        'distamt_td',
        'provisionorderitemsTable'
      );

      /* ❌ Close modal */
      $("#stockdataModal").modal("hide");
    }
  }

  function checkDuplicateProducts() {
    let productIds = [];
    let isDuplicate = false;
    let duplicateProduct = "";
    $(".productname").each(function () {
      let $row = $(this).closest("tr");
      // Ignore system-generated rows
      if ($row.hasClass("row_ignore")) return;
      let val = $(this).val() || "";
      if (val !== "") {
        if (productIds.includes(val)) {
          isDuplicate = true;
          duplicateProduct = val;
          $(this).addClass("is-invalid"); // visual feedback
        } else {
          productIds.push(val);
          $(this).removeClass("is-invalid");
        }
      }
    });
    if (isDuplicate) {
      Swal.fire({
        icon: "error",
        title: "Duplicate Product Detected",
        text: "The same product has been added more than once. Please correct it before proceeding.",
        confirmButtonText: "OK"
      });
      return false;
    }
    return true; // ✅ No duplicates
  }

  function sowingcalculation() { 
    $(".growingdaydiv").addClass("d-none");
    $("#growingday").text("");
    let $row = $("#productname_client").data("rowdata");
    if (!$row || !$row.length) return;
    let projectid = $("body").data("projectid");
    let productid = $row.find(".productname").val();
    let deliverydate = $row.find(".expecteddelivery").val(); // YYYY-MM-DD  
    let formData = new FormData();
    formData.append("projectid", projectid);
    formData.append("productid", productid);
    formData.append("deliverydate", deliverydate);
    $("#basesowingdate").html('<span class="spinner-border spinner-border-sm" role="status"></span>');
    $.ajax({
      url: "modal/stockverification.php?action=fecthgrowingdate",
      type: "POST",
      data: formData,
      dataType: "json",
      contentType: false,
      processData: false,
      cache: false,
      success: function (res) {
        // console.log(res);
        if (res && res.length > 0) {    
          if(parseInt(res[0].growingdays||0) <=0){
            $(".growingdaydiv").removeClass("d-none");
            $("#growingday").text("Sowing growing days are not configured for this product. Please update the master.");

          }

          let expecteddeliverydate = res[0].expecteddeliverydate;
          let rawSowingDate = res[0].sowingdate || res[0].rootsowingdate || "";
          let formattedSowingDate = (typeof window.changeDateFormatYmd_dmY === "function")
            ? window.changeDateFormatYmd_dmY(rawSowingDate)
            : (typeof changeDateFormatYmd_dmY === "function" ? changeDateFormatYmd_dmY(rawSowingDate) : rawSowingDate);

          if (expecteddeliverydate) {
            $("#stkreadydt").val(expecteddeliverydate);
            $("#stkreadydt").attr("min", expecteddeliverydate);
          }
          if (formattedSowingDate) {
            $("#basesowingdate").html(formattedSowingDate);
            $(".emergencycheckbox").data("sowingdt", formattedSowingDate);
            $(".emergencycheckbox").data("sowingRddt", expecteddeliverydate);
          } else {
            $("#basesowingdate").html("N/A");
          } 
 
        } else {
          $("#basesowingdate").html("N/A");
          $(".emergencycheckbox").prop("checked", false);
          $(".emergencycheckbox").removeData("sowingdt");
          $(".emergencycheckbox").removeData("sowingRddt");
          $("#is_emergency_sowing").val("no");
          $("#stockdataModal").modal("show");
        }
      },

      error: function () {
        $("#basesowingdate").html("N/A");
        Swal.fire({
          icon: "error",
          title: "Server Error",
          text: "Unable to calculate sowing date. Please try again."
        });
      }
    });
  }

  function mergeDuplicateProductsInTable(tableId, productid) {
    let productMap = {}; // Track only the specific product
    $("#" + tableId + " tbody tr").each(function () {
      let $row = $(this);
      let currentProductId = $row.find(".productname").val();
      if (currentProductId != productid) return;
      let qty = parseFloat($row.find(".qty_td").val()) || 0;
      if (!productMap[productid]) {
        // first matching row
        productMap[productid] = {
          mainRow: $row,
          totalQty: qty,
          duplicates: []
        };
      } else {
        // duplicate row → add qty and mark removal
        productMap[productid].totalQty += qty;
        productMap[productid].duplicates.push($row);
      }
    });

    // If no duplicates found → nothing to do
    if (!productMap[productid]) return;

    // Apply merge
    let item = productMap[productid];

    // Update total quantity in first row
    item.mainRow.find(".qty_td").val(item.totalQty);
    item.mainRow.find(".ratecal").trigger("input");
    item.mainRow.find(".stockdbid").val("");
    item.mainRow.find(".expecteddelivery").val("");
    item.mainRow.find(".isreadytosale").val("");

    // Remove all duplicates
    item.duplicates.forEach($row => $row.remove());
  }
  function closemodaldirectly() {
    // 🛑 Removed clearing of expecteddelivery to preserve user's choice
    // let $row = $("#productname_client").data("rowdata");
    // $row.find(".expecteddelivery").val("");
    $("#stockdataModal").modal("hide");
  }
  function confirmmessage(title1, title2) {
    return Swal.fire({
      title: title1,
      text: title2,
      showDenyButton: true,
      confirmButtonText: "YES",
      denyButtonText: "NO"
    }).then((result) => {
      return result.isConfirmed;   // true = YES, false = NO
    });
  }  

  // --- Dynamic Enquiry/Lead Importing & Client Checker Logic ---
  $(document).ready(function () {
    const urlParams = new URLSearchParams(window.location.search);
    const phone = urlParams.get('phone');
    const name = urlParams.get('name');
    const state = urlParams.get('state');
    const district = urlParams.get('district');
    const city = urlParams.get('city');
    const remarks = urlParams.get('remarks');
    const productsStr = urlParams.get('products');

    // Initialize State & District master dropdowns (skip in edit mode, which loads order data)
    const isEditMode = urlParams.get('mode') === 'edit' || !!urlParams.get('psono');
    if (!isEditMode) {
      loadMasterStates(state || '', district || '');
    }

    // State change event to dynamically load districts
    $(document).on('change', '#statename', function () {
      var stateVal = $(this).val();
      var stateId = $(this).find('option:selected').data('stateid') || stateVal;
      loadMasterDistricts(stateId);
    });

    // Auto-focus search input when State or District dropdown opens
    $(document).on('select2:open', function () {
      setTimeout(function () {
        const searchInput = document.querySelector('.select2-container--open .select2-search__field');
        if (searchInput) {
          searchInput.focus();
        }
      }, 50);
    });

    if (phone) {
      // 1. Fill Step 1 Form Fields
      $("#mobileno").val(phone);
      if (name) $("#clientname").val(name);
      if (city) $("#cityname").val(city);
      if (city) {
        $("#delivery_address").val(city + (district ? ", " + district : "") + (state ? ", " + state : ""));
      }
      if (remarks) {
        $("#remarks").val(remarks);
      }

      // 2. Fetch Client Info from DB via AJAX
      const routurl = $("body").data("routurl") || "../";
      const companyid = $("body").data("companyid");
      const subcompanyid = $("body").data("subcompanyid");
      const projectid = $("body").data("projectid");

      $.ajax({
        url: routurl + "krf-orderandsales/modal/client_modal.php?action=clientDetailsFrommaster",
        type: 'POST',
        dataType: 'json',
        data: {
          companyid: companyid,
          subcompanyid: subcompanyid,
          projectid: projectid,
          sortby: 'mobileno',
          sortbyvalue: phone
        },
        success: function (data) {
          const $banner = $("#lead-info-banner");
          let clientStatusHtml = "";

          if (data && data.length > 0) {
            const client = data[0];
            clientStatusHtml = `
              <div class="d-flex align-items-center gap-3 p-3 rounded-3" style="background-color: #f0fdf4; border: 1px solid #bbf7d0; color: #166534;">
                <i class="fa fa-check-circle fa-2x" style="color: #15803d;"></i>
                <div>
                  <h6 class="fw-bold mb-1" style="font-size: 1rem;">Existing Client Matched!</h6>
                  <p class="small mb-0" style="font-size: 0.88rem;">Client <strong>${client.client_name}</strong> is already registered. Auto-linking client account (ID: <strong>${client.client_id}</strong>).</p>
                </div>
              </div>
            `;

            // Auto-populate existing client details & lock fields
            $("#clientid").val(client.client_id);
            $("#clientname").val(client.client_name);
            $("#emailid").val(client.client_email || '');
            $("#cityname").val(client.cityname || '');
            $("#tahseel").val(client.tahseel || '');
            $("#stateid").val(client.stateid || '');
            $("#districtid").val(client.districtid || '');
            $("#delivery_address").val(client.invoicing_address || '');
            loadMasterStates(client.statename || client.stateid || '', client.districtname || client.districtid || '');
          } else {
            clientStatusHtml = `
              <div class="d-flex align-items-center gap-3 p-3 rounded-3" style="background-color: #fef3c7; border: 1px solid #fde68a; color: #92400e;">
                <i class="fa fa-user-plus fa-2x" style="color: #b45309;"></i>
                <div>
                  <h6 class="fw-bold mb-1" style="font-size: 1rem;">New Client Account</h6>
                  <p class="small mb-0" style="font-size: 0.88rem;">No registered client found for mobile <strong>${phone}</strong>. This client will be registered automatically upon order submission.</p>
                </div>
              </div>
            `;
          }

          let productsHtml = "";
          if (productsStr) {
            try {
              const products = JSON.parse(productsStr);
              if (products && products.length > 0) {
                productsHtml = `
                  <div class="p-3 bg-white border border-light-subtle rounded-3 shadow-sm h-100">
                    <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2" style="font-size: 0.95rem;">
                      <i class="fa fa-cubes text-primary"></i> Enquiry Product Details
                    </h6>
                    <div class="table-responsive">
                      <table class="table table-sm table-borderless mb-0 align-middle">
                        <thead>
                          <tr class="text-muted border-bottom" style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.5px;">
                            <th class="pb-2">Product Name</th>
                            <th class="text-end pb-2">Quantity</th>
                          </tr>
                        </thead>
                        <tbody>
                `;

                products.forEach(p => {
                  productsHtml += `
                    <tr class="border-bottom-dashed">
                      <td class="py-2 text-secondary fw-semibold" style="font-size: 0.85rem;">${p.product_name}</td>
                      <td class="py-2 text-end fw-bold text-primary" style="font-size: 0.9rem;">${parseFloat(p.quantity).toLocaleString()}</td>
                    </tr>
                  `;
                });

                productsHtml += `
                        </tbody>
                      </table>
                    </div>
                    <div class="mt-3 text-end">
                      <button type="button" class="btn btn-primary btn-sm px-3 py-1.5 rounded-3 fw-bold" id="btn-autofill-lead-products" style="font-size: 0.82rem; transition: all 0.2s;">
                        <i class="fa fa-bolt me-1"></i> Auto-Fill Into Order Table
                      </button>
                    </div>
                  </div>
                `;

                // Handle Auto-fill Button Click
                $(document).on("click", "#btn-autofill-lead-products", function () {
                  console.log("=== AUTO-FILL CLICKED ===");
                  const table = $('#provisionorderitemsTable');
                  const $tbody = table.find('tbody');

                  console.log("Tbody rows count:", $tbody.find('tr').length);
                  console.log("globalProductOptions loaded:", !!globalProductOptions);

                  // Clear default row if it hasn't selected any product
                  const rows = $tbody.find('tr');
                  let firstRowEmpty = false;
                  if (rows.length === 1) {
                    const firstVal = rows.eq(0).find('.productname').val();
                    console.log("First row product val:", firstVal);
                    if (!firstVal) {
                      firstRowEmpty = true;
                    }
                  }
                  console.log("firstRowEmpty determined as:", firstRowEmpty);

                  // Safe recursive loader for product options
                  function autoFillLeadProducts(prodList) {
                    console.log("Executing autoFillLeadProducts with list:", prodList);
                    try {
                      prodList.forEach((p, idx) => {
                        console.log(`Processing product index ${idx}:`, p);
                        let $row;
                        if (idx === 0 && firstRowEmpty) {
                          $row = rows.eq(0);
                          console.log("Using existing first row");
                        } else {
                          console.log("Clicking addRow to append row");
                          $("#addRow").trigger("click");
                          $row = $tbody.find('tr:last');
                          console.log("Target row is now last row");
                        }

                        const $select = $row.find('.productname');
                        console.log("Found select element:", $select.length);
                        
                        // Self-healing Select2 option dynamic insertion
                        let $option = $select.find(`option[value="${p.product_id}"]`);
                        console.log("Existing option search result length:", $option.length);
                        if ($option.length === 0) {
                          console.log(`Product ID ${p.product_id} not found in dropdown. Dynamically appending Option...`);
                          const newOpt = new Option(p.product_name, p.product_id, true, true);
                          $select.append(newOpt);
                        } else {
                          console.log(`Product ID ${p.product_id} found. Selecting option...`);
                          $select.val(p.product_id);
                        }
                        
                        console.log("Triggering change on select...");
                        $select.trigger('change');
                        
                        console.log(`Setting qty ${p.quantity} and triggering input...`);
                        $row.find('.qty_td').val(p.quantity).trigger('input');
                        
                        console.log("Triggering change.select2 on select...");
                        $select.trigger('change.select2');
                      });

                      console.log("Calculating totals...");
                      // Recalculate invoice totals
                      Invoicegrandtotalgstamont('totalamount', 'gsttotalamount', 'ttldiscountamount', 'grandtotalamount', 'payableamount_td', 'cgstamt_td', 'sgstamt_td', 'igstamt_td', 'distamt_td', 'provisionorderitemsTable');

                      console.log("Disabling auto-fill button...");
                      // Disable button after successful import
                      $("#btn-autofill-lead-products").prop("disabled", true).addClass("btn-success").removeClass("btn-primary").html('<i class="fa-solid fa-circle-check"></i> Auto-Filled Successfully');
                      
                      iziToast.success({
                        title: 'Success',
                        message: `${prodList.length} product(s) successfully imported from enquiry!`,
                        position: 'topRight'
                      });
                    } catch (err) {
                      console.error("Error inside autoFillLeadProducts:", err);
                    }
                  }

                  // Wait for options to load completely
                  function waitForOptionsAndImport() {
                    if (globalProductOptions && globalProductOptions !== "") {
                      autoFillLeadProducts(products);
                    } else {
                      setTimeout(waitForOptionsAndImport, 100);
                    }
                  }

                  waitForOptionsAndImport();
                });
              }
            } catch (e) {
              console.error("Error parsing lead products:", e);
            }
          }

          // Render combined Banner
          $banner.html(`
            <div class="row g-3">
              <div class="col-md-6 d-flex flex-column justify-content-between">
                <div>
                  <h5 class="fw-bold mb-3 d-flex align-items-center gap-2" style="font-size: 1.1rem; color: #1e3a8a;">
                    <i class="fa fa-info-circle text-primary"></i> Enquiry Import Status
                  </h5>
                  ${clientStatusHtml}
                </div>
              </div>
              <div class="col-md-6">
                ${productsHtml}
              </div>
            </div>
          `).slideDown(400);
        }
      });
    }
  });

  // --- Product Enquiry Modal Global Functions ---
  function openProductEnquiryModal() { 
    enquiryStates($("#statename option:selected").data("stateid"));  
 
 
    $('input[name="priority"][value="Low"]').prop("checked", true);
    $('#enq_clientid').val($('#clientid').val() || '');
    $('#enq_mobileno').val($('#mobileno').val() || '');
    $('#enq_clientname').val($('#clientname').val() || '');
    $('#enq_emailid').val($('#emailid').val() || '');
    $('#enq_statename').val($('#statename').val() || '');
    $('#enq_stateid').val($('#stateid').val() || '');
    $('#enq_districtname').val($('#districtname').val() || '');
    $('#enq_districtid').val($('#districtid').val() || '');
    $('#enq_cityname').val($('#cityname').val() || '');
    $('#enq_address').val($('#delivery_address').val() || '');

    // Reset enquiry inputs
    $('#enq_product_name').val('');
    $('#enq_quantity').val('');
    $('#enq_expected_date').val('');
    $('#enq_remarks').val('');

    // Open modal supporting both Bootstrap 4 and Bootstrap 5
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
      try {
        var modalEl = document.getElementById('newProductEnquiryModal');
        var modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modalInstance.show();
      } catch (e) {
        $('#newProductEnquiryModal').modal('show');
      }
    } else {
      $('#newProductEnquiryModal').modal('show');
    }
  }

  function closeProductEnquiryModal() {
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
      try {
        var modalEl = document.getElementById('newProductEnquiryModal');
        var modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (modalInstance) modalInstance.hide();
      } catch (e) {}
    }
    $('#newProductEnquiryModal').modal('hide');
  }



function getStateOptions(selectedStateId = "", fallbackName = "") {

    selectedStateId = String(selectedStateId ?? "").trim();

    let options = `<option value="" ${!selectedStateId ? "selected" : ""}>
        -- Select State --
    </option>`;

    const usedStates = new Set();
    let hasSelected = false;

    if (Array.isArray(stateDistrictArray) && stateDistrictArray.length > 0) {
        stateDistrictArray.forEach(item => {

            if (usedStates.has(String(item.stateid))) {
                return;
            }

            usedStates.add(String(item.stateid));

            const selected =
                selectedStateId &&
                String(item.stateid) === String(selectedStateId)
                    ? "selected"
                    : "";
            if (selected) hasSelected = true;

            options += `
                <option
                    value="${item.stateid}"
                    data-name="${item.statename}"
                    data-hindi="${item.statename_hn || ''}"
                    ${selected}
                >
                    ${item.statename}
                </option>
            `;
        });
    }

    if (selectedStateId && !hasSelected) {
        options += `<option value="${selectedStateId}" selected data-name="${fallbackName || selectedStateId}">${fallbackName || selectedStateId}</option>`;
    }

    return options;
}
  function getDistrictOptions(stateid, selectedDistrictId = "", fallbackName = "") {

    stateid = String(stateid ?? "").trim();
    selectedDistrictId = String(selectedDistrictId ?? "").trim();
    let options = `<option value="">-- Select District --</option>`;

    if (!stateid) {
        if (selectedDistrictId) {
            options += `<option value="${selectedDistrictId}" selected data-name="${fallbackName || selectedDistrictId}">${fallbackName || selectedDistrictId}</option>`;
        }
        return options;
    }

    let hasSelected = false;
    if (Array.isArray(stateDistrictArray) && stateDistrictArray.length > 0) {
        stateDistrictArray.forEach(item => {

            if (String(item.stateid) !== String(stateid)) {
                return;
            }

            const selected =
                selectedDistrictId &&
                String(item.districtid) === String(selectedDistrictId)
                    ? "selected"
                    : "";
            if (selected) hasSelected = true;

            options += `
                <option
                    value="${item.districtid}"
                    data-name="${item.districtname}"
                    data-hindi="${item.districtname_hn || ''}"
                    ${selected}
                >
                    ${item.districtname}
                </option>
            `;
        });
    }

    if (selectedDistrictId && !hasSelected) {
        options += `<option value="${selectedDistrictId}" selected data-name="${fallbackName || selectedDistrictId}">${fallbackName || selectedDistrictId}</option>`;
    }

    return options;
}
 $(document).on("change", ".del_state", function () {

    const $row = $(this).closest("tr");

    const stateId = $(this).val();
    const stateName = $(this).find("option:selected").data("name") || "";

    $row.find(".delivery_stateid").val(stateId);
    $row.find(".delivery_state").val(stateName);

    const $district = $row.find(".del_district");

    $district.html(
        getDistrictOptions(stateId, "")
    );

    // Since first district is automatically selected
    const districtId = $district.val() || "";
    const districtName =
        $district.find("option:selected").data("name") || "";

    $row.find(".delivery_district_id").val(districtId);
    $row.find(".delivery_district").val(districtName);
});
 

$(document).on("change", ".del_district", function () {

    const $row = $(this).closest("tr");

    const districtid = $(this).val();
    const districtName = $(this).find("option:selected").data("name") || "";

    $row.find(".delivery_district_id").val(districtid);
    $row.find(".delivery_district").val(districtName);
});

function findStateDistrictIds(stateName, districtName) {

    const clean = value => String(value || '').trim().toLowerCase();

    const stateText = clean(stateName);
    const districtText = clean(districtName);

    let stateid = '';
    let districtid = '';

    // Find State - English OR Hindi
    const stateRow = stateDistrictArray.find(item =>
        clean(item.statename) === stateText ||
        clean(item.statename_hn) === stateText
    );

    if (stateRow) {
        stateid = stateRow.stateid;
    }

    // Find District only inside selected State
    if (stateid && districtText) {

        const districtRow = stateDistrictArray.find(item =>
            String(item.stateid) === String(stateid) &&
            (
                clean(item.districtname) === districtText ||
                clean(item.districtname_hn) === districtText
            )
        );

        if (districtRow) {
            districtid = districtRow.districtid;
        }
    }

    return {
        stateid,
        districtid
    };
}
  //ajay

    function initModalSelect2() {

        if ($('#enq_statename').hasClass("select2-hidden-accessible")) {
            $('#enq_statename').select2('destroy');
        }

        if ($('#enq_districtname').hasClass("select2-hidden-accessible")) {
            $('#enq_districtname').select2('destroy');
        }

        $('#enq_statename').select2({
            dropdownParent: $('#newProductEnquiryModal'),
            width: '100%',
            //dropdownAutoWidth: true
        });

        $('#enq_districtname').select2({
            dropdownParent: $('#newProductEnquiryModal'),
            width: '100%',
           // dropdownAutoWidth: true
        });
    }
 $(document).on("change", "#enq_statename", function () {
      const stateid = $(this).val();
      if (!stateid) {
          $("#enq_districtname")
              .html('<option value="">Select District</option>')
              .trigger("change");
          return;
      }
      const selectedid =$("#districtname option:selected").data("districtid")||"";
      $.ajax({
          url: "modal/direct_sowing.php?action=getdistrict",
          type: "POST",
          dataType: "json",

          data: {
              stateid: stateid
          },

          success: function (res) {

              const $districtSelect = $("#enq_districtname");

              $districtSelect.empty();

              $districtSelect.append(
                  `<option value="">Select District</option>`
              );

              if (res.status === "success" && Array.isArray(res.data)) {
                  res.data.forEach(function (district) {
                      const selected =
                          String(district.districtid) === String(selectedid)
                              ? "selected"
                              : "";
                      $districtSelect.append(`
                          <option
                              value="${district.districtid}"
                              ${selected}
                          >
                              ${district.districtname}
                          </option>
                      `);
                  });
                  $districtSelect.trigger("change");
              }
          },
          error: function (xhr, status, error) {
              console.log("District loading error:", error);
          }
      });
  });

  function enquiryStates(selectedid = "") {
    initModalSelect2();
      $.ajax({
          url: "modal/direct_sowing.php?action=getstate",
          type: "POST",
          dataType: "json",
          success: function (res) {
              const $stateSelect = $("#enq_statename");
              $stateSelect.empty();

              $stateSelect.append(
                  `<option value="">Select State</option>`
              );

              if (res.status === "success" && Array.isArray(res.data)) {

                  res.data.forEach(function (state) {

                      const selected =
                          String(state.stateid) === String(selectedid)
                              ? "selected"
                              : "";

                      $stateSelect.append(`
                          <option
                              value="${state.stateid}"
                              data-stateid="${state.stateid}"
                              ${selected}
                          >
                              ${state.statename}
                          </option>
                      `);
                  });

                  $stateSelect.trigger("change");
              }
          },

          error: function (xhr, status, error) {
              console.log("State loading error:", error);
          }
      });
  }

  function fetches_states() {     
    if (stateDistrictArray.length > 0) return;
      $.ajax({
          url: "modal/direct_sowing.php?action=getstate_district",
          type: "POST",
          dataType: "json",
          success: function (res) {
              const $stateSelect = $("#enq_statename");
              $stateSelect.empty();

              $stateSelect.append(
                  `<option value="">Select State</option>`
              );

              if (res.status === "success" && Array.isArray(res.data)) {
                  stateDistrictArray = res.data;
                  // Refresh any existing delivery address dropdowns without changing selected values
                  $("#deliveryaddresstbl tbody tr").each(function() {
                      const $row = $(this);
                      const curStateId = $row.find(".delivery_stateid").val() || $row.find(".del_state").val();
                      const curStateName = $row.find(".delivery_state").val();
                      const curDistId = $row.find(".delivery_district_id").val() || $row.find(".del_district").val();
                      const curDistName = $row.find(".delivery_district").val();
                      if (curStateId) {
                          $row.find(".del_state").html(getStateOptions(curStateId, curStateName));
                          $row.find(".del_district").html(getDistrictOptions(curStateId, curDistId, curDistName));
                      }
                  });
              }
          },

          error: function (xhr, status, error) {
              console.log("State loading error:", error);
          }
      });
  }

    /* ========== STEP 1 =========== */
    function loadBoxPackingMaster() {
        const projectid = $("#hidden_projectid").val();
        return $.ajax({
            url: "modal/box_packquery.php?action=getboxpackingfreightmaster",
            type: "POST",
            dataType: "json",
            data: {
                projectid: projectid
            }
        }).then(function (res) {
            if (res.status !== "success") {
                boxPackingMaster = [];
                throw new Error(res.message || "Unable to load Box Packing Master.");
            }
            boxPackingMaster = res.data || [];
            return boxPackingMaster;
        });
    }

   /* ================= STEP 2 EVERY PRODUCT ROW========= */
    async function fillboxdata() {
        try {
            /* Load master first */
            if (boxPackingMaster.length === 0) {
                await loadBoxPackingMaster();
            }
            const stateOptions = getBoxStateOptions();
            let rowsHtml = "";
            let rowCounter = 0;
            $("#provisionorderitemsTable tbody tr").each(function () {
                const $sourceRow = $(this);
                const $productSelect = $sourceRow.find(".productname");
                const productId = $productSelect.val();
                const productName = $productSelect.find("option:selected").text().trim();
                const qtyText = $sourceRow.find(".qty_td").val().trim();
                const disp_date =$sourceRow.find(".expecteddelivery").val();
                const qty = parseFloat(qtyText) || 0;
                if (!productId) {
                    return true;
                }
                const rowKey = (typeof getOrSetRowKey === 'function') ? getOrSetRowKey($sourceRow) : ($sourceRow.attr("data-row-key") || ('item_row_' + Date.now() + '_' + rowCounter));
                rowCounter++;
                const rowId =
                    "boxrow_" +
                    Date.now() +
                    "_" +
                    rowCounter;
                rowsHtml += `
                    <tr
                        id="${rowId}"
                        class="box-delivery-row"
                        data-row-key="${escapeHtml(rowKey)}"
                        data-productid="${escapeHtml(productId)}"
                        data-productqty="${qty}"                 

                    >

                        <td class="text-center">
                            ${rowCounter}
                        </td>


                        <td>
                            <strong>
                                ${escapeHtml(productName)}
                            </strong>
                            <input
                                type="hidden"
                                class="box_productname"
                                name="box_productname[]"
                                value="${escapeHtml(productName)}"
                            >

                            <input
                                type="hidden"
                                class="box_productid"
                                 name="box_productid[]"
                                value="${escapeHtml(productId)}"
                            >
                        </td>
                         <td>
                            <strong class="boxdate">
                                ${escapeHtml(disp_date)}
                            </strong>
                        </td>
                        <td class="text-center product_qty">
                            ${qty}

                            <input
                                type="hidden"
                                class="box_product_qty"
                                 name="box_product_qty[]"
                                value="${escapeHtml(qty)}"
                            >
                        </td>
                        <td>
                            <select
                                class="form-control state_box"
                                name="state_box[]"
                                required
                            >
                                ${stateOptions}
                            </select>
                           <input
                              type="hidden"
                              class="box_state_name"
                              name="box_state_name[]"
                              value=""
                          >
                          <input
                                      type="hidden"
                                      class="box_state_id"
                                      name="box_state_id[]"
                                      value=""
                                  >
                        </td>


                        <td>
                            <select
                                class="form-control district_box" 
                                 name="district_box[]"
                                required
                                disabled
                            >
                                <option value="">
                                    -- Select District --
                                </option>
                            </select>

                           <input
                              type="hidden"
                              class="box_district_name"
                              name="box_district_name[]"
                              value=""
                          >
                           <input
                              type="hidden"
                              class="box_district_id"
                              name="box_district_id[]"
                              value=""
                          >
                        </td>


                        <td>
                            <select
                                class="form-control city_box" 
                                 name="city_box[]"
                                required
                                disabled
                            >
                                <option value="">
                                    -- Select Location --
                                </option>
                            </select>


                           <input
                              type="hidden"
                              class="box_city_name"
                              name="box_city_name[]"
                              value=""
                          >
                           <input
                              type="hidden"
                              class="box_city_id"
                              name="box_city_id[]"
                              value=""
                          >
                        </td>


                        <td class="text-center">

                            <input
                                type="number"
                                class="form-control plants_per_box"
                                 name="plants_per_box[]"
                                value="500"
                                min="1"
                                step="1"
                                readonly
                            >
                        </td>
                        <td>
                            <input
                                type="number"
                                class="form-control no_of_box"
                                 name="no_of_box[]"
                                value="${qty > 0 ? Math.ceil(qty / 500) : 0}"
                                min="0"
                                step="1" 
                            >
                        </td> 

                        <td>
                              <select
                                  class="form-control pickuppoint"
                                  name="pickuppoint[]"
                                  required
                                  disabled
                              >
                                  <option value="">
                                      -- Select Location First --
                                  </option>
                              </select>
                          </td>

                        <!-- CHARGES PER PACK 
                       <td>
                            <select
                                class="form-control freighcharges"
                                 name="box_freighcharges[]"
                                disabled
                            >
                                <option value="">
                                    -- Select Location --
                                </option>
                            </select>
                        </td>-->

                    <td>
                        <input
                            type="number"
                            class="form-control freighcharges"
                            name="box_freighcharges[]"
                            value="0.00"
                            min="0"
                            step="0.01"
                            inputmode="decimal"
                            readonly
                        >
                    </td>


                          <!-- AMOUNT -->
                          <td>

                              <input
                                  type="number"
                                  class="form-control freightamount"
                                  name="box_freightamount[]"
                                  value="0.00"
                                  min="0"
                                  step="0.01"
                                  readonly
                              >

                          </td>

                      <td class="text-center">
                            <div class="form-check">
                                <input type="checkbox"
                                       class="form-check-input apply_box_all"
                                       style="width:18px; height:18px; cursor:pointer;">

                                 
                            </div>
                        </td>                  

                    </tr>
                `;

            });


            /* Clear old generated rows first */
            $("#deliveryboxtbl tbody").empty();


            if (rowCounter === 0) {

                $("#deliveryboxtbl tbody").html(`
                    <tr>
                        <td colspan="11"
                            class="text-center text-muted">
                            No product found.
                        </td>
                    </tr>
                `);

                return;
            }


            /*
             * IMPORTANT:
             * If source table contains 2 products,
             * rowsHtml contains 2 <tr>.
             */
            $("#deliveryboxtbl tbody").html(rowsHtml);


        } catch (error) {

            console.error(error);

            Swal.fire(
                "Error",
                error.message || "Unable to prepare Box Packing data.",
                "error"
            );

        }
    }

    function loadPickupPointOptions($row) {

      const $locationOption =
          $row.find(".city_box option:selected");

      const locationId =
          $locationOption.val();

      const $pickup =
          $row.find(".pickuppoint");


      // No location selected
      if (!locationId) {

          $pickup
              .html(`
                  <option value="">
                      -- Select Location First --
                  </option>
              `)
              .prop("disabled", true);

          return;
      }


      const airportCharge =
          parseFloat(
              $locationOption.data("airport")
          ) || 0;

      const busCharge =
          parseFloat(
              $locationOption.data("bus")
          ) || 0;

      const railwayCharge =
          parseFloat(
              $locationOption.data("railway")
          ) || 0;


      let html = `
          <option value="">
              -- Select Pickup Point --
          </option>
      `;


      // Bus
      if (busCharge > 0) {

          html += `
              <option
                  value="Bus"
                  data-charge="${busCharge}"
              >
                  Bus Stop (${busCharge.toFixed(2)})
              </option>
          `;
      }


      // Railway
      if (railwayCharge > 0) {

          html += `
              <option
                  value="Railway"
                  data-charge="${railwayCharge}"
              >
                  Railway Station (${railwayCharge.toFixed(2)})
              </option>
          `;
      }


      // Airport
      if (airportCharge > 0) {

          html += `
              <option
                  value="Airport"
                  data-charge="${airportCharge}"
              >
                  Airport (${airportCharge.toFixed(2)})
              </option>
          `;
      }


      // No pickup charge defined
      if (
          busCharge <= 0 &&
          railwayCharge <= 0 &&
          airportCharge <= 0
      ) {

          html = `
              <option value="">
                  No Pickup Point Available
              </option>
          `;

          $pickup
              .html(html)
              .prop("disabled", true);

          return;
      }


      $pickup
          .html(html)
          .prop("disabled", false);
  }

     function loadFreightChargeOptions($row) {

      const pickupType =
          $row.find(".pickuppoint").val() || "";

      const $locationOption =
          $row.find(".city_box option:selected");

      const locationId =
          $locationOption.val();

      const $freight =
          $row.find(".freighcharges");


      // -----------------------------------------
      // No location
      // -----------------------------------------
      if (!locationId) {

          $freight
              .html(`
                  <option value="">
                      -- Select Location --
                  </option>
              `)
              .prop("disabled", true);

          $row.find(".freightamount").val("0.00");

          return;
      }


      // -----------------------------------------
      // Rates from selected State/District/Location
      // -----------------------------------------
      const airportCharge =
          parseFloat($locationOption.data("airport")) || 0;

      const busCharge =
          parseFloat($locationOption.data("bus")) || 0;

      const railwayCharge =
          parseFloat($locationOption.data("railway")) || 0;


      // -----------------------------------------
      // Pickup related default rate
      // -----------------------------------------
      let selectedCharge = null;

      switch (pickupType) {

          case "Airport":
              selectedCharge = airportCharge;
              break;

          case "Bus":
              selectedCharge = busCharge;
              break;

          case "Railway":
              selectedCharge = railwayCharge;
              break;
      }


      // -----------------------------------------
      // DISTINCT available rates
      // -----------------------------------------
      const distinctCharges = [
          ...new Set([
              airportCharge,
              busCharge,
              railwayCharge
          ].map(v => Number(v)))
      ];


      let html = "";


      // No Pickup Point selected
      if (selectedCharge === null) {

          html += `
              <option value="" selected>
                  -- Select Charges --
              </option>
          `;
      }


      // -----------------------------------------
      // Selected Pickup rate FIRST
      // -----------------------------------------
      if (selectedCharge !== null) {

          html += `
              <option
                  value="${selectedCharge}"
                  selected
              >
                  ${Number(selectedCharge).toFixed(2)}
              </option>
          `;
      }


      // -----------------------------------------
      // Remaining distinct rates
      // -----------------------------------------
      distinctCharges.forEach(function (charge) {

          if (
              selectedCharge !== null &&
              Number(charge) === Number(selectedCharge)
          ) {
              return;
          }

          html += `
              <option value="${charge}">
                  ${Number(charge).toFixed(2)}
              </option>
          `;
      });


      // -----------------------------------------
      // IMPORTANT
      // Rebuild dropdown completely
      // -----------------------------------------
      $freight
          .html(html)
          .prop("disabled", false);


      // Explicitly set selected value
      if (selectedCharge !== null) {

          $freight.val(
              String(selectedCharge)
          );
      }


      // -----------------------------------------
      // Recalculate amount
      // -----------------------------------------
      calculateBoxFreightAmount($row);
  }


    /* ================ UNIQUE STATE OPTIONS ========== */
    function getBoxStateOptions() {
        let html = `<option value="">-- Select State --</option>`;
        const unique = new Map();
        boxPackingMaster.forEach(function (item) {
            if (!unique.has(item.stateid)) {
                unique.set(item.stateid, item.statename);
            }

        });
        unique.forEach(function (stateName, stateId) {
            html += `
                <option value="${escapeHtml(stateId)}">
                    ${escapeHtml(stateName)}
                </option>
            `;
        });
        return html;
    }

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : value).html();
    } 
    function getBoxLocationOptions(stateid, districtid) {

    let html = `<option value="">-- Select Location --</option>`;

    boxPackingMaster
        .filter(item =>
            String(item.stateid) === String(stateid) &&
            String(item.districtid) === String(districtid)
        )
        .forEach(function (item) {

            html += `
                <option
                    value="${escapeHtml(item.id)}"

                    data-location="${escapeHtml(item.location)}"

                    data-plantsperbox="${
                        parseInt(item.plants_per_box || 500)
                    }"

                    data-airport="${
                        parseFloat(item.airport_charges || 0)
                    }"

                    data-bus="${
                        parseFloat(item.bus_charges || 0)
                    }"

                    data-railway="${
                        parseFloat(item.railway_charges || 0)
                    }"
                >
                    ${escapeHtml(item.location)}
                </option>
            `;
        });

    return html;
}
    $(document).on("change", ".state_box", function () {

    const $row = $(this).closest("tr");

    const stateid = $(this).val();
    const stateName = $(this).find("option:selected").text().trim();

    // Store State Name
    $row.find(".box_state_name").val(stateName);
    $row.find(".box_state_id").val(stateid);

    const $district = $row.find(".district_box");
    const $location = $row.find(".city_box");


    // Reset dependent fields
    $district
        .prop("disabled", true)
        .html(`
            <option value="">
                -- Select District --
            </option>
        `);

    $location
        .prop("disabled", true)
        .html(`
            <option value="">
                -- Select Location --
            </option>
        `);


    resetBoxFreightRow($row);


    if (!stateid) {
        return;
    }


    $district
        .html(getBoxDistrictOptions(stateid))
        .prop("disabled", false);

});
    $(document).on("change", ".district_box", function () {

    const $row = $(this).closest("tr");
    const stateid =  $row.find(".state_box").val();
    const districtid =  $(this).val();
    const $location = $row.find(".city_box");
    const districtName = $(this).find("option:selected").text().trim();
    // Store State Name
    $row.find(".box_district_name").val(districtName);
    $row.find(".box_district_id").val(districtid);


    $location
        .prop("disabled", true)
        .html(`
            <option value="">
                -- Select Location --
            </option>
        `);


    resetBoxFreightRow($row);


    if (!stateid || !districtid) {
        return;
    }


    $location
        .html(
            getBoxLocationOptions(
                stateid,
                districtid
            )
        )
        .prop("disabled", false);

}); 

   /* $(document).on("change", ".city_box", function () {
      const $row = $(this).closest("tr"); 
      const $option = $(this).find("option:selected");
      const cityName = $(this).find("option:selected").text().trim();

      const locationid =  $(this).val();
       $row.find(".box_city_name").val(cityName);
       $row.find(".box_city_id").val(locationid);

      prepareBoxDeliveryRow($row);

      loadPickupPointOptions($row);

      // Reset charge until pickup point selected
      $row.find(".freighcharges")
          .val("0.00")
          .prop("readonly", true);

      $row.find(".freightamount")
          .val("0.00");
  });*/

  $(document).on("change", ".city_box", function () {
    const $row = $(this).closest("tr");
    const cityId   = $(this).val();
    const cityName = $(this).find("option:selected").text().trim();
    $row.find(".box_city_name").val(cityName);
    $row.find(".box_city_id").val(cityId);
    prepareBoxDeliveryRow($row);
    loadPickupPointOptions($row);
    // Reset charge until pickup point selected
    $row.find(".freighcharges")
        .val("0.00")
        .prop("readonly", true);

    $row.find(".freightamount")
        .val("0.00");
});
  function calculateBoxFreightAmount($row) {

    const noOfBoxes =
        parseFloat(
            $row.find(".no_of_box").val()
        ) || 0;

    const chargePerPack =
        parseFloat(
            $row.find(".freighcharges").val()
        ) || 0;

    const amount =
        noOfBoxes * chargePerPack;

    $row.find(".freightamount")
        .val(amount.toFixed(2));
}

  $(document).on(
    "input change",
    ".freighcharges",
    function () {

        const $row = $(this).closest("tr");

        calculateBoxFreightAmount($row);
    }
);

    function prepareBoxDeliveryRow($row) {

    const $location =
        $row.find(".city_box");

    const $option =
        $location.find("option:selected");


    const masterId =
        parseInt($option.val()) || 0;


    if (!masterId) {

        resetBoxFreightRow($row);

        return;
    }


    // ----------------------------------------
    // Master values
    // ----------------------------------------

    const plantsPerBox =
        parseInt(
            $option.data("plantsperbox")
        ) || 500;


    /*
     * IMPORTANT:
     * This freight is resolved from
     * State + District + Location.
     */
    const freightPerPack =
        parseFloat(
            $option.data("freight")
        ) || 0;


    // ----------------------------------------
    // Product quantity
    // ----------------------------------------

    const qty =
        parseFloat(
            $row.data("productqty")
        ) || 0;


    // ----------------------------------------
    // Number of boxes
    // ----------------------------------------

    const noOfBoxes =
        qty > 0 && plantsPerBox > 0
            ? Math.ceil(qty / plantsPerBox)
            : 0;


    // ----------------------------------------
    // Update box fields
    // ----------------------------------------

    $row
        .find(".plants_per_box")
        .val(plantsPerBox);


    $row
        .find(".no_of_box")
        .val(noOfBoxes);


    // ----------------------------------------
    // Charges Per Pack
    // ----------------------------------------

    const $freight =
        $row.find(".freighcharges");


    if (freightPerPack > 0) {

        $freight
            .html(`
                <option
                    value="${freightPerPack}"
                    selected
                >
                    ${freightPerPack.toFixed(2)}
                </option>
            `)
            .prop("disabled", false);

    } else {

        $freight
            .html(`
                <option value="0">
                    No Rate Defined
                </option>
            `)
            .prop("disabled", false);
    }


    // ----------------------------------------
    // Store master information
    // ----------------------------------------

    $row.attr(
        "data-boxmasterid",
        masterId
    );


    // ----------------------------------------
    // Calculate amount
    // ----------------------------------------

    calculateBoxFreightAmount($row);
}

function calculateBoxFreightAmount($row) {

    const noOfBoxes =
        parseFloat(
            $row.find(".no_of_box").val()
        ) || 0;


    const chargePerPack =
        parseFloat(
            $row.find(".freighcharges").val()
        ) || 0;


    const amount =
        noOfBoxes * chargePerPack;


    $row
        .find(".freightamount")
        .val(
            amount.toFixed(2)
        );
}
$(document).on(
    "input change",
    ".no_of_box, .freighcharges",
    function () {

        const $row =
            $(this).closest("tr");

        calculateBoxFreightAmount($row);
    }
);
function resetBoxFreightRow($row) {

    $row
        .find(".plants_per_box")
        .val(500);


    const qty =
        parseFloat(
            $row.data("productqty")
        ) || 0;


    const defaultBoxes =
        qty > 0
            ? Math.ceil(qty / 500)
            : 0;


    $row
        .find(".no_of_box")
        .val(defaultBoxes);


    $row
        .find(".freighcharges")
        .html(`
            <option value="0">
                -- Select Location First --
            </option>
        `)
        .prop("disabled", true);


    $row
        .find(".freightamount")
        .val("0.00");


    $row.removeAttr(
        "data-boxmasterid"
    );
}
/*$(document).on("change", ".pickuppoint", function () {

    const $row =
        $(this).closest("tr");

    updatePickupCharge($row);

});*/

$(document).on("change", ".pickuppoint", function () {

    const $row =
        $(this).closest("tr");

    loadFreightChargeOptions($row);

});
$(document).on("change", ".pickuppoint", function () {

    const $row = $(this).closest("tr");

    const pickupPoint = $(this).val();

    const selectedCharge =
        parseFloat(
            $(this)
                .find("option:selected")
                .data("charge")
        ) || 0;

    const $freight =
        $row.find(".freighcharges");

    if (!pickupPoint) {

        $freight
            .val("0.00")
            .prop("readonly", true);

        $row.find(".freightamount")
            .val("0.00");

        return;
    }

    // Default rate from master,
    // but operator can change it.
    $freight
        .val(selectedCharge.toFixed(2))
        .prop("readonly", false);

    calculateBoxFreightAmount($row);
});
/*function updatePickupCharge($row) {

    const pickupType =
        $row.find(".pickuppoint").val();

    const $option =
        $row.find(".city_box option:selected");


    let pickupCharge = 0;


    if (!$option.val()) {

        $row.data(
            "pickupcharge",
            0
        );

        return;
    }


    switch (pickupType) {

        case "Airport":

            pickupCharge =
                parseFloat(
                    $option.data("airport")
                ) || 0;

            break;


        case "Bus":

            pickupCharge =
                parseFloat(
                    $option.data("bus")
                ) || 0;

            break;


        case "Railway":

            pickupCharge =
                parseFloat(
                    $option.data("railway")
                ) || 0;

            break;
    }


    $row.data(
        "pickupcharge",
        pickupCharge
    );
}*/

 $(document).on("change", ".apply_box_all", function () {

    const $checkbox = $(this);

    if (!this.checked) {
        return;
    }

    const $sourceRow = $checkbox.closest("tr");

    const pickupType =
        $sourceRow.find(".pickuppoint").val() || "";

    const stateId =
        $sourceRow.find(".state_box").val() || "";

    const districtId =
        $sourceRow.find(".district_box").val() || "";

    const locationId =
        $sourceRow.find(".city_box").val() || "";


    // ----------------------------------------
    // Validation
    // ----------------------------------------

    if (!stateId) {

        Swal.fire(
            "Required",
            "Please select State first.",
            "warning"
        );

        $checkbox.prop("checked", false);

        return;
    }


    if (!districtId) {

        Swal.fire(
            "Required",
            "Please select District first.",
            "warning"
        );

        $checkbox.prop("checked", false);

        return;
    }


    if (!locationId) {

        Swal.fire(
            "Required",
            "Please select Location first.",
            "warning"
        );

        $checkbox.prop("checked", false);

        return;
    }


    // ----------------------------------------
    // Apply to all other rows
    // ----------------------------------------

    $("#deliveryboxtbl tbody .box-delivery-row")
        .not($sourceRow)
        .each(function () {

            const $row = $(this);


            // --------------------------------
            // State
            // --------------------------------

            $row
                .find(".state_box")
                .val(stateId);


            // --------------------------------
            // District
            // --------------------------------

            $row
                .find(".district_box")
                .html(
                    getBoxDistrictOptions(stateId)
                )
                .prop("disabled", false)
                .val(districtId);


            // --------------------------------
            // Location
            // --------------------------------

            $row
                .find(".city_box")
                .html(
                    getBoxLocationOptions(
                        stateId,
                        districtId
                    )
                )
                .prop("disabled", false)
                .val(locationId);


            // --------------------------------
            // Pickup Point
            // --------------------------------

            $row
                .find(".pickuppoint")
                .val(pickupType);


            // --------------------------------
            // Recalculate Box Qty etc.
            // --------------------------------

            prepareBoxDeliveryRow($row);


            // --------------------------------
            // Populate Freight Charges
            //
            // Pickup selected value becomes
            // default, but all distinct rates
            // remain in dropdown.
            // --------------------------------

            loadFreightChargeOptions($row);

        });


    Swal.fire({
        icon: "success",
        title: "Applied",
        text: "Delivery details applied to all products.",
        timer: 1200,
        showConfirmButton: false
    });

});
 /* ============DISTRICT OPTIONS BASED ON STATE======= */
    function getBoxDistrictOptions(stateid) {
        let html = `<option value="">-- Select District --</option>`;
        const unique = new Map();
        boxPackingMaster
            .filter(item => String(item.stateid) === String(stateid))
            .forEach(function (item) {

                if (!unique.has(item.districtid)) {
                    unique.set(item.districtid, item.districtname);
                }
            });
        unique.forEach(function (districtName, districtId) {

            html += `
                <option value="${escapeHtml(districtId)}">
                    ${escapeHtml(districtName)}
                </option>
            `;
        });

        return html;
    }

  // ajay

  $(document).ready(function () {
    $('#productEnquiryForm').on('submit', function (e) {
      e.preventDefault();

      const clientname = $('#enq_clientname').val().trim();
      const mobileno = $('#enq_mobileno').val().trim();
      const product_name = $('#enq_product_name').val().trim();

      if (!mobileno || !clientname || !product_name) {
        if (typeof Swal !== 'undefined') {
          Swal.fire('Required Fields', 'Please fill in Mobile Number, Client Name, and Demanded Product Name.', 'warning');
        } else {
          alert('Please fill in Mobile Number, Client Name, and Demanded Product Name.');
        }
        return;
      }

      const routurl = $('body').data('routurl') || '../';
      const companyid = $('body').data('companyid') || '';
      const subcompanyid = $('body').data('subcompanyid') || '';
      const projectid = $('body').data('projectid') || '';
      var priority = $('input[name="priority"]:checked').val();

      let districtname = $('#enq_districtname option:selected').val() ? ($('#enq_districtname option:selected').text() || "").trim() : "";

      let statename = $('#enq_statename option:selected').val() ? ($('#enq_statename option:selected').text() || "").trim() : ""; 

      const formData = {
        companyid: companyid,
        subcompanyid: subcompanyid,
        projectid: projectid,
        clientid: $('#enq_clientid').val(),
        clientname: clientname,
        mobileno: mobileno,
        emailid: $('#enq_emailid').val(),
        stateid: $('#enq_stateid').val(), 
        statename: statename,

        districtid: $('#enq_districtid').val(),
        districtname: districtname,
        cityname: $('#enq_cityname').val(),
        address: $('#enq_address').val(),
        product_name: product_name,
        quantity: $('#enq_quantity').val(),
        expected_date: $('#enq_expected_date').val(),
        remarks: $('#enq_remarks').val(),
        priority:priority
      };

      const $btn = $('#btnSubmitEnquiry');
      $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Saving...');

      $.ajax({
        url: routurl + 'krf-orderandsales/modal/client_modal.php?action=saveProductEnquiry',
        type: 'POST',
        dataType: 'json',
        data: formData,
        success: function (res) {
          $btn.prop('disabled', false).html('<i class="fa fa-save me-1"></i> Save Enquiry');
          if (res && res.status === 'success') {
            closeProductEnquiryModal();
            if (typeof Swal !== 'undefined') {
              Swal.fire({
                icon: 'success',
                title: 'Enquiry Saved!',
                text: res.message || 'Product enquiry recorded successfully.',
                confirmButtonColor: '#3085d6',
                timer: 2000,
                timerProgressBar: true
              }).then(function () {
                window.location.reload();
              });
            } else {
              alert(res.message || 'Product enquiry recorded successfully.');
              window.location.reload();
            }
          } else {
            const errMsg = res && res.message ? res.message : 'Failed to save enquiry.';
            if (typeof Swal !== 'undefined') {
              Swal.fire('Error', errMsg, 'error');
            } else {
              alert('Error: ' + errMsg);
            }
          }
        },
        error: function (xhr, status, error) {
          $btn.prop('disabled', false).html('<i class="fa fa-save me-1"></i> Save Enquiry');
          console.error('Save enquiry AJAX error:', error);
          if (typeof Swal !== 'undefined') {
            Swal.fire('Error', 'Failed to communicate with server. Please try again.', 'error');
          } else {
            alert('Failed to communicate with server. Please try again.');
          }
        }
      });
    });
  });
</script>
<script src="js/latlong_handler.js"></script>

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
