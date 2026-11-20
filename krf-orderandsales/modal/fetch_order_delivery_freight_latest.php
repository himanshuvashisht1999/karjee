<?php
  //error_reporting(E_ALL);
  // require('../../connection.php');
  // require('../../loginCell/functions/login_functions.php');
  // include("../functions/orders_functions.php");
// include("../functions/product_functions.php");

  $orderid = trim($_POST['orderid'] ?? '');
  $clientid = trim($_POST['clientid'] ?? '');
  $companyid = (int) ($_POST['companyid'] ?? 0);
  $subcompanyid = (int) ($_POST['subcompanyid'] ?? 0);
  $projectid = (int) ($_POST['projectid'] ?? 0);
  $clientname = trim($_POST['clientname'] ?? '');

  $createddate = trim($_POST['createddate'] ?? '');
  $loginid == trim($_POST['loginid'] ?? '');

  //  $orderid = 'KF-PS-00036';//trim($_POST['orderid'] ?? '');
  // $clientid ='C00050';// trim($_POST['clientid'] ?? '');
  // $companyid = 19;//intval($_POST['companyid'] ?? 0);
  // $subcompanyid =12;// intval($_POST['subcompanyid'] ?? 0);
  // $projectid =17;// intval($_POST['projectid'] ?? 0);
  // $clientname ="abc";
  // $rooturl = "http://localhost/krf_amanaccounting/";
  $isProvisional = filter_var(
      $_POST['is_provisional'] ?? true,
      FILTER_VALIDATE_BOOLEAN,
      FILTER_NULL_ON_FAILURE
  );
  $isProvisional = $isProvisional ?? true;

  // Resolve the application root from the current request instead of tying
  // freight lookups to one localhost installation.
  $scriptPath = str_replace('\\', '/', parse_url($_SERVER['SCRIPT_NAME'] ?? '', PHP_URL_PATH) ?: '');
  $fragmentRequestPath = str_replace(
      '\\',
      '/',
      parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: ''
  );
  $moduleMarker = '/krf-orderandsales/';
  $pathWithModule = strpos($scriptPath, $moduleMarker) !== false
      ? $scriptPath
      : $fragmentRequestPath;
  $modulePosition = strpos($pathWithModule, $moduleMarker);
  $applicationPath = $modulePosition !== false
      ? substr($pathWithModule, 0, $modulePosition + 1)
      : rtrim(str_replace('\\', '/', dirname($scriptPath ?: $fragmentRequestPath)), '/.') . '/';
  if ($applicationPath === '') {
      $applicationPath = '/';
  }
  $requestScheme = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
      ? 'https'
      : 'http';
  $requestHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $rooturl = $requestScheme . '://' . $requestHost . $applicationPath;
  // Use an absolute application path. Relative fetch URLs otherwise resolve
  // against admin_dashboard.php when this fragment is inserted dynamically.
  $orderDetailsUrl = $applicationPath
      . 'krf-orderandsales/modal/ordereditquery.php?action=getdetails';

  // if (empty($orderid)) {
  //     echo '<div class="alert alert-danger p-3">Invalid Order ID.</div>';
  //     exit;
  // } 
?> 
<style>
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
    max-height: 420px;
    overflow-x: auto !important;
    overflow-y: auto !important;
    max-width: 100%;
    border-radius: 8px;
    position: relative;
    border: 1px solid #cbd5e1;
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
    min-width: 1280px !important;
    width: 100%;
    white-space: nowrap;
    border-collapse: separate;
    border-spacing: 0;
  }
  #tblstockdatamaster thead th {
    position: sticky;
    top: 0;
    z-index: 10;
    background-color: #e2f2fe !important;
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

  <?php if (intval($loginroleid ?? 0) > 1): ?>
  .expecteddelivery {
    pointer-events: none !important;
    background-color: #f8fafc !important;
    cursor: not-allowed !important;
  }
  <?php endif; ?>

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

  /* Full-screen delivery-history modal. Scoped to avoid parent modal CSS. */
  #clientDeliveryHistoryModal {
    position: fixed !important;
    inset: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    padding: 0 !important;
    overflow: hidden !important;
    z-index: 20000 !important;
  }

  #clientDeliveryHistoryModal .modal-dialog {
    width: calc(100vw - 40px) !important;
    max-width: none !important;
    height: calc(100vh - 40px) !important;
    max-height: none !important;
    margin: 20px auto !important;
    padding: 0 !important;
  }

  #clientDeliveryHistoryModal .modal-content {
    width: 100% !important;
    height: 100% !important;
    max-height: none !important;
    display: flex !important;
    flex-direction: column !important;
    overflow: hidden !important;
    border-radius: 12px !important;
  }

  #clientDeliveryHistoryModal .modal-header,
  #clientDeliveryHistoryModal .modal-footer {
    flex: 0 0 auto !important;
  }

  #clientDeliveryHistoryModal .modal-body {
    flex: 1 1 auto !important;
    min-height: 0 !important;
    overflow: auto !important;
  }

  #clientDeliveryHistoryModal .table-responsive {
    width: 100% !important;
    height: 100% !important;
    overflow: auto !important;
  }

  #historyAddressTbl {
    width: 100% !important;
    min-width: 900px !important;
    table-layout: auto !important;
  }

  #historyAddressTbl thead th {
    position: sticky !important;
    top: 0 !important;
    z-index: 5 !important;
    vertical-align: middle !important;
  }

  #historyAddressTbl .chk-cell {
    width: 70px !important;
    min-width: 70px !important;
    padding: 10px !important;
    text-align: center !important;
    vertical-align: middle !important;
  }

  #historyAddressTbl .history-address-radio {
    position: static !important;
    display: inline-block !important;
    float: none !important;
    width: 20px !important;
    height: 20px !important;
    margin: 0 !important;
    padding: 0 !important;
    transform: none !important;
    vertical-align: middle !important;
    cursor: pointer !important;
  }

  #clientDeliveryHistoryModal .modal-footer {
    display: flex !important;
    justify-content: flex-end !important;
    align-items: center !important;
    flex-wrap: wrap !important;
    gap: 10px !important;
    padding: 12px 16px !important;
  }

  #clientDeliveryHistoryModal .modal-footer .btn {
    position: static !important;
    float: none !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: auto !important;
    min-width: 120px !important;
    margin: 0 !important;
    white-space: nowrap !important;
  }

  @media (max-width: 575.98px) {
    #clientDeliveryHistoryModal .modal-dialog {
      width: calc(100vw - 16px) !important;
      height: calc(100vh - 16px) !important;
      margin: 8px auto !important;
    }
  }

  /* Coordinate modal: large, responsive and independent of global modal CSS. */
  #adddeliveryaddressModal {
    position: fixed !important;
    inset: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    padding: 0 !important;
    z-index: 20010 !important;
  }

  #adddeliveryaddressModal .modal-dialog {
    width: calc(100vw - 40px) !important;
    max-width: none !important;
    height: calc(100vh - 40px) !important;
    margin: 20px auto !important;
  }

  #adddeliveryaddressModal .modal-content {
    width: 100% !important;
    height: 100% !important;
    max-height: none !important;
    display: flex !important;
    flex-direction: column !important;
    overflow: hidden !important;
    border-radius: 14px !important;
  }

  #adddeliveryaddressModal .modal-body {
    flex: 1 1 auto !important;
    min-height: 0 !important;
    overflow: auto !important;
    background: #f8fafc;
  }

  #adddeliveryaddressModal .coordinate-entry-card {
    position: sticky;
    top: 0;
    z-index: 5;
  }

  #adddeliveryaddressModal .coordinate-result-panel {
    width: 100%;
    min-height: 420px;
    overflow: auto;
    background: #fff;
    border: 1px solid #dbe5f0;
    border-radius: 10px;
  }

  #adddeliveryaddressModal .modal-footer {
    display: flex !important;
    justify-content: flex-end !important;
    gap: 10px !important;
    flex-wrap: wrap !important;
  }

  #deliveryaddresstbl .row-history-btn,
  #deliveryaddresstbl .row-latlong-btn {
    width: 34px !important;
    height: 32px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 0 !important;
  }

  #deliveryaddresstbl .delivery-expected-date-heading,
  #deliveryaddresstbl .delivery-expected-date-cell {
    width: 140px !important;
    min-width: 140px !important;
  }

  @media (max-width: 575.98px) {
    #adddeliveryaddressModal .modal-dialog {
      width: calc(100vw - 16px) !important;
      height: calc(100vh - 16px) !important;
      margin: 8px auto !important;
    }

    #adddeliveryaddressModal .modal-footer .btn {
      flex: 1 1 100%;
    }
  }
</style> 

<div class="modal fade" id="clientDeliveryHistoryModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-secondary text-white">
        <h5 class="modal-title"><i class="fa fa-history me-2"></i>Previous Delivery Addresses</h5>
        <button type="button" class="btn-close btn-close-white close-history-modal" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <div class="table-responsive">
          <table class="table table-hover table-striped mb-0" id="historyAddressTbl">
            <thead class="table-dark">
              <tr>
                <th style="width: 70px;" class="text-center chk-cell">Select</th>
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
        <button type="button" class="btn btn-outline-secondary close-history-modal" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="btnApplySelectedHistory">
          <i class="fa fa-check-circle me-1"></i> Use Selected Address
        </button>
      </div>
    </div>
  </div>
</div>


<div class="modal fade" id="adddeliveryaddressModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content shadow-lg border-0">
      <div class="modal-header bg-primary text-white py-2 px-3">
        <h5 class="modal-title fw-bold fs-15 mb-0">
          <i class="fa fa-map-marker-alt me-2"></i>Delivery Address From Coordinates
        </h5>
        <button type="button" class="btn-close btn-close-white close-coordinate-modal" aria-label="Close"></button>
      </div>

      <div class="modal-body p-3">
        <div class="card p-3 mb-3 border-0 bg-light shadow-sm coordinate-entry-card">
          <div class="row g-2 align-items-end">
            <div class="col-lg-6">
              <label for="latlonginput" class="form-label fw-bold mb-1 small text-dark">
                <i class="fa fa-crosshairs text-primary me-1"></i>Latitude, Longitude
              </label>
              <input type="text" class="form-control form-control-sm border-primary-subtle shadow-sm"
                     name="latlong" id="latlonginput" autocomplete="off"
                     placeholder="e.g. 23.122173, 83.199275">
            </div>
            <div class="col-lg-3 col-md-6">
              <label for="latinput" class="form-label fw-bold mb-1 small text-muted">Latitude</label>
              <input type="number" step="any" min="-90" max="90"
                     class="form-control form-control-sm bg-white" name="latinput"
                     id="latinput" placeholder="Latitude">
            </div>
            <div class="col-lg-3 col-md-6">
              <label for="longinput" class="form-label fw-bold mb-1 small text-muted">Longitude</label>
              <input type="number" step="any" min="-180" max="180"
                     class="form-control form-control-sm bg-white" name="longinput"
                     id="longinput" placeholder="Longitude">
            </div>
          </div>
        </div>

        <div id="appenddeliveryaddressdiv" class="coordinate-result-panel"></div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary close-coordinate-modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="btnApplyCoordinatesToRow">
          <i class="fa fa-check-circle me-1"></i>Apply Coordinates to This Row
        </button>

        
      </div>
    </div>
  </div>
</div>

 
<form
    id="orderDeliveryFreightForm"
    novalidate
    data-save-url="<?php echo htmlspecialchars(
        $rooturl . 'krf-orderandsales/modal/save_order_delivery_freight_NEW.php'
    ); ?>"
>
  <input type="hidden" name="orderid" value="<?php echo htmlspecialchars($orderid); ?>">
  <input type="hidden" id="clientid" name="clientid" value="<?php echo htmlspecialchars($clientid); ?>">
  <input type="hidden" name="projectid" value="<?php echo intval($order['projectid'] ?? $projectid); ?>">
  <input type="hidden" name="is_provisional" value="<?php echo $isProvisional ? '1' : '0'; ?>">
  <input type="hidden" name="sono" value="<?php echo htmlspecialchars($sono ?? ''); ?>">
  <input type="hidden" name="loginid" value="<?php echo htmlspecialchars($loginid ?? ''); ?>">

  <!-- Order Summary Header Card -->
  <div class="card bg-light border-0 shadow-sm mb-3">
    <div class="card-body p-3">
      <div class="row g-2 align-items-center">
        <div class="col-md-4">
          <small class="text-muted text-uppercase fw-bold d-block" style="font-size: 0.72rem;">Order Reference</small>
          <strong class="text-primary fs-6 orderno_box"><?php echo htmlspecialchars($orderid); ?></strong>
        </div>
        <div class="col-md-4">
          <small class="text-muted text-uppercase fw-bold d-block" style="font-size: 0.72rem;">Client Name</small>
          <strong class="text-dark"><?php echo htmlspecialchars($clientname); ?></strong>
        </div>
        <div class="col-md-4">
          <small class="text-muted text-uppercase fw-bold d-block" style="font-size: 0.72rem;">Order Creation Date</small>
          <span class="text-secondary fw-bold"> <?php echo htmlspecialchars($createddate); ?></span>
        </div>
      </div>
    </div>
  </div>



 
    <div class="wizard-steps mb-5">
       
        <div class="step-item" data-step="1">1 <span class="step-label">Delivery</span></div>
        <div class="step-item" data-step="2">2 <span class="step-label">Freight</span></div>
    </div>

     <div class="wizard-content-step" id="step1">
        <!-- Delivery Mode Selector -->
  <div class="card border border-primary-subtle shadow-sm mb-3">
    <div class="card-header bg-white py-2 px-3">
      <strong class="text-dark" style="font-size: 0.9rem;"><i class="fa fa-truck me-2 text-primary"></i>Delivery Mode</strong>
    </div>
    <div class="card-body p-3">
      <div class="d-flex gap-3 flex-wrap align-items-center">
        <label class="custom-mode-card" id="labelModeDelivered">
          <input type="radio" name="pickupfrom" id="modeDelivered" value="delivered"  >
          <span class="fw-bold text-success"><i class="fa fa-truck me-1"></i> Delivery by the Farm</span>
        </label>
        <label class="custom-mode-card" id="labelModePickup">
          <input type="radio" name="pickupfrom" id="modePickup" value="pickup">
          <span class="fw-bold text-secondary"><i class="fa fa-walking me-1"></i> Farmer Self Pickup</span>
        </label>


        <label class="custom-mode-card" id="labelModebox">
          <input type="radio" name="pickupfrom" id="Modebox" value="boxpacking">
          <span class="fw-bold text-secondary"><i class="fa fa-box me-1"></i> Box Packing</span>
        </label>

      </div>
    </div>
  </div>
    <div id="deliveryaddressdiv" class="mt-4" >
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0 text-primary"><i
            class="fa fa-map-marked-alt me-2"></i> Client Delivery Address</h6>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-outline-info btn-sm px-3 d-none" id="addBlankAddressRow">
            <i class="fa fa-plus me-1"></i> Add Blank Row
          </button>
          <button type="button" class="btn btn-outline-secondary btn-sm px-3 d-none" id="showOldDeliveryHistory">
            <i class="fa fa-history me-1"></i> Old Delivery History
          </button>
          <button type="button" class="btn btn-primary-premium btn-sm px-3 d-none" id="isadddeliveryaddress">
            <i class="fa fa-map-marker-alt me-1"></i> Get From Lat & Long
          </button>
        </div>
      </div>
      <div class="table-responsive-premium">
        <table class="table premium-table table-striped" id="deliveryaddresstbl">
          <thead>
            <tr>
              <th style="width:180px; min-width:160px;">Product Name</th>
              <th class="delivery-expected-date-heading" style="width:140px; min-width:140px;">Exp. Del. Date</th>
              <th style="width:120px;">Delivery Qty</th>
              <th style="width:100px;">Distance (Km)</th>
              <th class="d-none">Lat/Long No</th>
              <th style="width:150px;">State Name</th>
              <th style="width:150px;">District Name</th>
              <th style="width:150px;">City/Village Name</th>
              <th style="width:120px;">Pincode</th>
              <th>Full Address</th>
              <th style="width:60px;" class="text-center">Action</th>
              
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
      <div class="row">
        <div class="col-md-12 mt-2 text-right">
          <button type="button" class="btn btn-primary-premium next-step" data-next="2">
                Next: Freight Charges <i class="fa fa-arrow-right ms-2"></i>
          </button>
        </div>
      </div>
    </div>    

    <div id="deliveryboxdiv" class="mt-4 d-none" >
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0 text-primary"><i
            class="fa fa-map-marked-alt me-2"></i> Delivery Address for  </h6> 
      </div>
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
      <div class="row">
        <div class="col-md-12 mt-2 text-right">
          <button type="button" class="btn btn-primary-premium next-step" data-next="2">
                Next: Freight Charges <i class="fa fa-arrow-right ms-2"></i>
          </button>
        </div>
      </div>
    </div>
  </div>

        <div class="wizard-content-step" id="step2">
        <div class="premium-card mb-4" id="freightSection">
          <div class="card-header-premium justify-content-between">
            <div class="d-flex align-items-center gap-2">
              <i class="fa fa-hand-holding-usd"></i>
              Step 02: Freight Charge Details (Grouped by Address & Date)
            </div>
          </div>
          <div class="premium-card-body">
            <div id="freeFreightNotification"></div>
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
                                    <span class="fw-bold text-primary" id="spanratedbl"></span>
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
                <?php //echo _t('remarks'); ?></label>
              <textarea name="remarks" class="form-control border-primary-subtle shadow-sm" rows="3" placeholder="Any special instructions for this order..."></textarea>
            </div>
          </div>
        </div>
        <div class="wizard-footer">
          <button type="button" class="btn btn-outline-secondary prev-step" data-prev="1">
            <i class="fa fa-arrow-left me-2"></i> Previous
          </button>

          <div id="submitSection">
            <!-- <input type="hidden" name="companyid" value="<?php echo $companyid; ?>" />
            <input type="hidden" name="subcompanyid" value="<?php echo $subcompanyid; ?>" />
            <input type="hidden" name="projectid" value="<?php echo $projectid; ?>" />
            <input type="hidden" name="loginid" value="<?php echo $loginid; ?>" />
            <input type="hidden" id="clientid" name="clientid" value="" /> -->
            <button type="submit" id="submitOrderBtn" class="btn btn-primary-premium shadow-lg px-5">
              <i class="fa fa-check-circle me-2"></i> Submit to Create Provisional Order
            </button>
          </div>
        </div>
    </div>
 

</form>

<script>
goToStep(1);
    $(".prev-step").on("click", function() {
      debugger;
        const prevStep = $(this).data("prev");
        goToStep(prevStep);
    });
    function escapeHtml(value) {
    return $('<div>').text(value ?? '').html();
}
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

          $("#deliveryboxtbl tbody tr").each(function () {
              const $row = $(this);
              // -----------------------------------------------------
              // Product
              // -----------------------------------------------------
              const productId = $row.find(".box_productid, input[name='box_productid[]']").val() || "";
              const productName =
                  $row.find(".box_productname").val() ||
                  $row.find("td:nth-child(2) .fw-bold").text().trim() ||
                  "";
              const qty =  parseFloat(
                      $row.find(".box_product_qty, .box_delivery_qty").val()
                  ) || 0;
 
              // -----------------------------------------------------
              const deliveryDate =
                  $row.find(".box_expected_delivery").val() ||
                  $row.find(".boxdate").text().trim();
              // -----------------------------------------------------
              // State
              // -----------------------------------------------------
              const stateId = $row.find(".state_box, .box_state").val() || "";

              const stateName = $row.find(".state_box option:selected, .box_state option:selected").text().trim();


              // -----------------------------------------------------
              // District
              // -----------------------------------------------------
              const districtId =
                  $row.find(".district_box, .box_district").val() || "";

              const districtName =
                  $row.find(".district_box option:selected, .box_district option:selected")
                      .text()
                      .trim();


              // -----------------------------------------------------
              // City / Village
              // -----------------------------------------------------
              const locationId =
                  $row.find(".city_box, .box_city").val() || "";

              const locationName =
                  $row.find(".city_box option:selected").text().trim() ||
                  $row.find(".box_city").val() || "";


              // -----------------------------------------------------
              // Pickup Point
              // -----------------------------------------------------
              const pickupPoint =
                  $row.find(".pickuppoint, .box_pickup_point").val() || "";


              // -----------------------------------------------------
              // Box Detail
              // -----------------------------------------------------
              const plantsPerBox =
                  parseFloat(
                      $row.find(".plants_per_box, .box_per_pack").val()
                  ) || 0;

              const noOfBoxes =
                  parseFloat(
                      $row.find(".no_of_box, .box_count").val()
                  ) || 0;


              // -----------------------------------------------------
              // Freight Detail
              // -----------------------------------------------------
              const freightCharge =
                  parseFloat(
                      $row.find(".freighcharges, .box_charge_per_pack").val()
                  ) || 0;

              const freightAmount =
                  parseFloat(
                      $row.find(".freightamount, .box_amount").val()
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

                              `).join('')}

                          </div>


                          <div class="small text-muted">

                              <i class="fa fa-boxes me-1"></i>

                              Total Qty:

                              <strong>
                                  ${totalGroupQty}
                              </strong>

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
                           ZERO CHARGES

                           Keep existing class/name structure.
                      ========================================== -->
                      <td
                          class="text-center align-middle"
                          style="
                              text-align:center !important;
                              vertical-align:middle !important;
                          "
                      >

                          <input
                              type="checkbox"
                              class="form-check-input f_zero_charge_chk"
                              name="f_zero_charges[]"
                              value="yes"

                              style="
                                  position:static !important;
                                  width:1.35rem;
                                  height:1.35rem;
                                  cursor:pointer;
                                  margin:0 auto;
                                  display:inline-block;
                              "
                          >

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
              $tbody.append('<tr><td colspan="8" class="text-center py-4 text-muted">No box packing data found. Please check Step 1.</td></tr>');
          }

          $("#freeFreightNotification").empty().hide();
          $("#spanratedbl").html("").addClass("d-none");
          calculateGrandTotalFreight();
          updateDiscountRemarksNarration();

          return;
        }

        if (pickupfrom === 'pickup') {
            $("#freeFreightNotification").empty().hide();
            $("#spanratedbl").html("").addClass("d-none");
            $tbody.append('<tr><td colspan="8" class="text-center py-4 text-muted">Self Pickup selected. No freight charges applicable.</td></tr>');
            calculateGrandTotalFreight();
            updateDiscountRemarksNarration();
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
            if (optIsGrafted !== undefined && optIsGrafted !== null && optIsGrafted !== '') {
                isGrafted = String(optIsGrafted).toLowerCase() === 'grafted' || String(optIsGrafted).toLowerCase() === 'yes';
            } else if (window.allProductsMap && window.allProductsMap[productId]) {
                const prodData = window.allProductsMap[productId];
                const gVal = prodData.IsGrafted || prodData.isGrafted || (prodData.sku && prodData.sku.toUpperCase().includes('NONGRAFTED') ? 'NonGrafted' : (prodData.sku && prodData.sku.toUpperCase().includes('GRAFTED') ? 'Grafted' : ''));
                isGrafted = String(gVal).toLowerCase() === 'grafted' || String(gVal).toLowerCase() === 'yes';
            } else {
                isGrafted = (/grafted/i.test(optText) && !/nongrafted|non-grafted/i.test(optText));
            }

            if (productId) {
                productInfo[productId] = {
                    date: date || 'N/A',
                    isGrafted: isGrafted,
                    name: optText
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
            const date = $(this).find(".delivery_expected_delivery").val() || pInfo.date || 'N/A';
            let isGrafted = pInfo.isGrafted;
            if (isGrafted === undefined) {
                isGrafted = (/grafted/i.test(productName) && !/nongrafted|non-grafted/i.test(productName));
            }
            
            // Address details
            const city = $(this).find("input[name='delivery_city[]']").val() || '';
            const district = $(this).find("input[name='delivery_district[]']").val() || '';
            const address = $(this).find("input[name='delivery_fulladdress[]']").val() || '';
            
            const addressKey = `${city}|${district}|${address}`;
            const groupKey = `${addressKey}@@@${date}`;

            if (!groups[groupKey]) {
                groups[groupKey] = {
                    address: { city, district, address },
                    date: date,
                    distance: distance, // Use the distance from the first row in the group
                    items: []
                };
            }
            groups[groupKey].items.push({ name: productName, qty: qty, isGrafted: isGrafted, productId: productId });
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
            const km = parseFloat(g.distance) || 0;

            const html = `
                <tr class="group-row" data-totalqty="${totalGroupQty}" data-graftedqty="${totalGraftedQty}" data-planttype="${plantType}" data-stateid="${g.address.stateid || ''}" data-districtid="${g.address.districtid || ''}">
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
                        <span class="fw-bold text-dark fs-5">${g.distance}</span><br>
                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Km</small>
                    </td>
                    <td>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0">Rs</span>
                            <input type="text" name="f_amount[]" class="form-control f_amt_input border-start-0 fw-bold" value="0" onKeyPress="return isFloatNumber(event,this);" />
                        </div>
                    </td>
                    <td>
                        <div class="input-group input-group-sm">
                            <input type="text" name="f_discount[]" class="form-control f_disc_input text-center ${isDiscReadOnly ? 'bg-light' : ''}" value="0" ${isDiscReadOnly ? 'readonly' : ''} onKeyPress="return isFloatNumber(event,this);" />
                            <span class="input-group-text bg-white border-start-0">%</span>
                        </div>
                        <div class="f_discount_info small text-success mt-1" style="font-size: 0.72rem; display:none;"></div>
                    </td>
                    <td>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0">Rs</span>
                            <input type="text" name="f_payable[]" class="form-control f_payable_input bg-light fw-bold text-primary border-start-0" readonly value="0.00" />
                        </div>
                    </td>
                    <td class="text-center">
                        <div class="d-flex gap-1 justify-content-center align-items-center">
                            <button type="button" class="btn btn-primary btn-sm rounded-circle freightratemodal" onclick="openFreightRowCalculator(this, ${g.distance}, ${totalGroupQty})" title="Calculate Freight">
                                <i class="fa fa-calculator"></i>
                            </button>
                        </div>
                        <div class="f_vehicle_badge mt-1">
                            <span class="badge bg-info-subtle text-info border border-info-subtle auto-vehicle-badge" style="font-size: 0.75rem;"><i class="fa fa-spinner fa-spin me-1"></i>Auto...</span>
                        </div>
                        <input type="hidden" name="f_vehicle[]" class="f_vehicle_info" value="" />
                        <input type="hidden" name="f_distance[]" class="f_distance_info" value="${g.distance}" />
                        <input type="hidden" name="f_totaldistance[]" class="f_totaldistance_input" value="${g.distance}" />
                        <input type="hidden" name="f_freedistance[]" class="f_freedistance_input" value="0" />
                        <input type="hidden" name="f_payabledistance[]" class="f_payabledistance_input" value="${g.distance}" />
                        <input type="hidden" name="f_narration[]" class="f_narration_input" value="" />
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
            // Auto-trigger calculation for group rows
            $("#freightGroupTable tbody tr.group-row").each(function() {
                autoCalculateRowFreight($(this), projectid, rooturl);
            });
            calculateGrandTotalFreight();
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
            if (!isAuto) Swal.fire('Notice', 'Plan details not found. Please refresh the rules.', 'warning');
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
            const plantOk = (planPlant === 'both' || planPlant === 'all' || plantOkCheck(planPlant, rowPlant));
            // 3. Order Qty check: rowQty >= minQty && (maxQty == 0 || rowQty <= maxQty)
            const qtyOk = (minQty === 0 || rowQty >= minQty) && (maxQty === 0 || rowQty <= maxQty);
            // 4. Distance KM check: rowDist > 0 && (maxDist == 0 || rowDist <= maxDist)
            const distOk = (rowDist > 0) && (maxDist === 0 || rowDist <= maxDist);

            if (custOk && plantOk && qtyOk && distOk) {
                // Clear any previous clear flag
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

        if (isAuto) return;

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
                const plantOk = (planPlant === 'both' || planPlant === 'all' || plantMatchCheck(planPlant, rowPlant));
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

    function plantMatchCheck(planPlant, rowPlant) {
        return planPlant === 'both' || planPlant === 'all' || planPlant === rowPlant || rowPlant === 'both';
    }

    /**
     * Clear discount for an individual freight table row
     */
    window.clearRowFreightDiscount = function($row) {
        $row.attr("data-discount-cleared", "true");
        const amt = parseFloat($row.find(".f_amt_input").val()) || 0;
        const rowDist = parseFloat($row.find(".f_distance_info").val()) || 0;
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

    async function autoCalculateRowFreight($row, projectid, rooturl) {
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

        const requestController = new AbortController();
        const requestTimeout = window.setTimeout(() => requestController.abort(), 15000);

        try {
            const requestData = new URLSearchParams({
                km: String(km),
                projectid: String(projectid),
                total_qty: String(totalQty)
            });
            const response = await fetch(ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: requestData.toString(),
                credentials: 'same-origin',
                cache: 'no-store',
                signal: requestController.signal
            });
            const responseText = await response.text();
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${responseText || response.statusText}`);
            }
            const res = JSON.parse(responseText);

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

                $row.find(".f_amt_input").val(charge.toFixed(2)).trigger('input').trigger('change');
                $row.find(".f_vehicle_info").val(vehicleText);

                let badgeText = `<i class="fa fa-truck me-1"></i>${v.vehiclename}`;
                if (res.trips_needed > 1) {
                    badgeText += ` (x${res.trips_needed} trips)`;
                }
                $row.find(".auto-vehicle-badge").html(badgeText).removeClass('bg-info-subtle text-info border-info-subtle bg-warning-subtle text-warning border-warning-subtle').addClass('bg-success-subtle text-success border-success-subtle');

                applyRowFreightDiscount($row);
            } else if (res.status === 'success' && Array.isArray(res.suggestions) && res.suggestions.some(v => parseFloat(v.charge) > 0)) {
                const validSug = res.suggestions.filter(v => parseFloat(v.charge) > 0);
                const v = validSug[0];
                const charge = parseFloat(v.charge) || 0;
                $row.find(".f_amt_input").val(charge.toFixed(2)).trigger('input').trigger('change');
                $row.find(".f_vehicle_info").val(v.vehiclename);
                $row.find(".auto-vehicle-badge").html(`<i class="fa fa-truck me-1"></i>${v.vehiclename}`).removeClass('bg-info-subtle text-info border-info-subtle bg-warning-subtle text-warning border-warning-subtle').addClass('bg-primary-subtle text-primary border-primary-subtle');

                applyRowFreightDiscount($row);
            } else {
                $row.find(".auto-vehicle-badge").html('<i class="fa fa-exclamation-triangle me-1"></i>No rate defined').removeClass('bg-info-subtle text-info border-info-subtle bg-success-subtle text-success border-success-subtle').addClass('bg-warning-subtle text-warning border-warning-subtle');
                applyRowFreightDiscount($row);
            }
        } catch (error) {
            console.error('Unable to calculate freight:', error);
            $row.find(".auto-vehicle-badge").html('<i class="fa fa-times me-1"></i>Connection error').removeClass('bg-info-subtle text-info border-info-subtle bg-success-subtle text-success border-success-subtle').addClass('bg-danger-subtle text-danger border-danger-subtle');
        } finally {
            window.clearTimeout(requestTimeout);
        }
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
    $(document).on("input change keyup blur", ".f_amt_input, .f_disc_input", function() {
        const loginRoleId = parseInt($('body').data('loginroleid') || $('#hidden_loginroleid').val() || '<?php echo (int)($loginroleid ?? ($_SESSION['loginroleid'] ?? 0)); ?>') || 0;
        if ($(this).hasClass('f_disc_input') && loginRoleId > 3 && $(this).prop('readonly')) {
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
  function goToStep(stepNumber) { 
        $(".wizard-content-step").hide();
        $("#step" + stepNumber).fadeIn(400);
        
        // Update Step Indicator
        $(".step-item").removeClass("active completed");

        for (let i = 1; i <= 2; i++) {
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
     // Wizard Navigation Logic
    $(".next-step").on("click", function() {
        const nextStep = $(this).data("next");
        const currentStep = nextStep - 1;

        // Validation logic for each step
       /* if (currentStep === 1) {
            const clientName = $("#clientname").val();
            const mobileNo = $("#mobileno").val();
            if (!clientName || !mobileNo) {
                iziToast.warning({ title: 'Required Fields', message: 'Please fill client name and mobile number to proceed.', position: 'topRight' });
                if (typeof window.validateMobileNumber === 'function') window.validateMobileNumber();
                return;
            }
            if (typeof window.validateMobileNumber === 'function' && !window.validateMobileNumber()) {
                iziToast.warning({ title: 'Invalid Mobile Number', message: 'Please enter a valid 10-digit mobile number starting with 6, 7, 8, or 9.', position: 'topRight' });
                $("#mobileno").focus();
                return;
            }
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
            $('input[name="pickupfrom"]').prop('checked', false) ;
            $("#deliveryboxdiv").addClass("d-none");
            $("#deliveryaddressdiv").fadeOut();
            $("#deliveryboxtbl tbody").empty();
            $("#deliveryaddresstbl tbody").empty();



        } else */if (currentStep === 1) {
          var pickupfrom = $('input[name="pickupfrom"]:checked').val();
          if (!pickupfrom){
            alert("You have Not select any option  Delivery Address")
            return false;
          }
          
            // Only validate address if 'delivered' is selected and user added rows

          if (pickupfrom==="boxpacking") {

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

                  const pickupPoint = $row.find(".box_pickup_point").val();
                  const stateId     = $row.find(".box_state").val();
                  const districtId  = $row.find(".box_district").val();
                  const locationId  = $row.find(".box_city").val();
                  const plantsPerBox = parseInt($row.find(".box_per_pack").val()) || 0;
                  const noOfBox      = parseInt($row.find(".box_count").val()) || 0;

                  if (!pickupPoint) {
                      Swal.fire("Validation", "Please select Pickup Point.", "warning");
                      $row.find(".box_pickup_point").focus();
                      isValid = false;
                      return false;
                  }

                  if (!stateId) {
                      Swal.fire("Validation", "Please select State.", "warning");
                      $row.find(".box_state").focus();
                      isValid = false;
                      return false;
                  }

                  if (!districtId) {
                      Swal.fire("Validation", "Please select District.", "warning");
                      $row.find(".box_district").focus();
                      isValid = false;
                      return false;
                  }

                  if (!locationId) {
                      Swal.fire("Validation", "Please select Location.", "warning");
                      $row.find(".box_city").focus();
                      isValid = false;
                      return false;
                  }

                  if (plantsPerBox <= 0) {
                      Swal.fire("Validation", "Plants per Box is invalid.", "warning");
                      $row.find(".box_per_pack").focus();
                      isValid = false;
                      return false;
                  }

                  if (noOfBox <= 0) {
                      Swal.fire("Validation", "No. of Boxes is invalid.", "warning");
                      $row.find(".box_count").focus();
                      isValid = false;
                      return false;
                  }
              });

              if (!isValid) {
                  return;
              }

          }  

          else if (pickupfrom==="delivered") {
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
                    $("#provisionorderitemsTable tbody tr").each(function() {
                        const pid = $(this).find(".productname").val();
                        const qty = parseFloat($(this).find(".qtycal").val()) || 0;
                        if (pid) {
                            orderedMap[pid] = (orderedMap[pid] || 0) + qty;
                        }
                    });

                    // 2. Get delivery quantities per product
                    const deliveryMap = {};
                    $rows.each(function() {
                        const pid = $(this).find(".delivery_product_select").val();
                        const qty = parseFloat($(this).find(".delivery_qty_input").val()) || 0;
                        if (pid && pid !== 'all') {
                            deliveryMap[pid] = (deliveryMap[pid] || 0) + qty;
                        }
                    });

                    // 3. Compare
                    let mismatched = [];
                    for (let pid in orderedMap) {
                        const oQty = orderedMap[pid];
                        const dQty = deliveryMap[pid] || 0;
                        if (Math.abs(oQty - dQty) > 0.001) {
                            const pName = $("#provisionorderitemsTable tbody tr").find(`.productname option[value="${pid}"]`).first().text();
                            mismatched.push(`<strong>${pName}</strong>: Ordered <b>${oQty}</b>, Assigned <b>${dQty}</b>`);
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
        if (nextStep === 2) {
            generateFreightGroups();
        }
    });
(function orderDeliveryFreightScript() {
/* =========================================================
   SMALL NATIVE DOM HELPER
   This file no longer requires jQuery. It supports only the
   DOM operations used by this delivery/freight form.
========================================================= */
const delegatedEvents = new WeakMap();

class DomCollection {
    constructor(elements) {
        this.elements = Array.from(elements || []).filter(Boolean);
        this.length = this.elements.length;
    }

    each(callback) {
        this.elements.forEach((element, index) => callback.call(element, index, element));
        return this;
    }

    find(selector) {
        return new DomCollection(this.elements.flatMap(element => Array.from(element.querySelectorAll(selector))));
    }

    closest(selector) {
        return new DomCollection(this.elements.map(element => element.closest?.(selector)).filter(Boolean));
    }

    val(value) {
        if (value === undefined) return this.elements[0]?.value ?? '';
        return this.each(function () { this.value = value ?? ''; });
    }

    prop(name, value) {
        if (value === undefined) return this.elements[0]?.[name];
        return this.each(function () { this[name] = value; });
    }

    data(name) {
        const element = this.elements[0];
        return element ? (element.dataset?.[name] ?? element.getAttribute(`data-${name}`)) : undefined;
    }

    html(value) {
        if (value === undefined) return this.elements[0]?.innerHTML ?? '';
        return this.each(function () { this.innerHTML = value ?? ''; });
    }

    text(value) {
        if (value === undefined) return this.elements[0]?.textContent ?? '';
        return this.each(function () { this.textContent = value ?? ''; });
    }

    empty() {
        return this.html('');
    }

    append(content) {
        const nodes = content instanceof DomCollection ? content.elements :
            (content instanceof Node ? [content] : []);

        return this.each(function (parentIndex) {
            nodes.forEach(node => parentIndex === 0
                ? this.appendChild(node)
                : this.appendChild(node.cloneNode(true)));
        });
    }

    addClass(...names) {
        return this.each(function () { this.classList.add(...names.filter(Boolean)); });
    }

    removeClass(...names) {
        return this.each(function () { this.classList.remove(...names.filter(Boolean)); });
    }

    show() {
        return this.each(function () { this.style.display = ''; });
    }

    hide() {
        return this.each(function () { this.style.display = 'none'; });
    }

    remove() {
        return this.each(function () { this.remove(); });
    }

    clone() {
        return new DomCollection(this.elements.map(element => element.cloneNode(true)));
    }

    is(other) {
        const otherElement = other instanceof DomCollection ? other.elements[0] : other;
        return this.elements[0] === otherElement;
    }

    removeAttr(names) {
        const attributes = String(names || '').split(/\s+/).filter(Boolean);
        return this.each(function () { attributes.forEach(name => this.removeAttribute(name)); });
    }

    trigger(eventName) {
        // Select2 is not present without jQuery, so its display-only refresh is unnecessary.
        if (String(eventName).includes('.select2')) return this;
        const type = String(eventName).split('.')[0];
        return this.each(function () { this.dispatchEvent(new Event(type, { bubbles: true })); });
    }

    off(events, selector) {
        const root = this.elements[0];
        const registry = delegatedEvents.get(root);
        if (!root || !registry) return this;

        String(events).split(/\s+/).filter(Boolean).forEach(eventName => {
            const type = eventName.split('.')[0];
            const key = `${eventName}|${selector}`;
            const listener = registry.get(key);
            if (listener) {
                root.removeEventListener(type, listener);
                registry.delete(key);
            }
        });
        return this;
    }

    on(events, selector, handler) {
        const root = this.elements[0];
        if (!root) return this;

        let registry = delegatedEvents.get(root);
        if (!registry) {
            registry = new Map();
            delegatedEvents.set(root, registry);
        }

        String(events).split(/\s+/).filter(Boolean).forEach(eventName => {
            const type = eventName.split('.')[0];
            const key = `${eventName}|${selector}`;
            const oldListener = registry.get(key);
            if (oldListener) root.removeEventListener(type, oldListener);

            const listener = function (event) {
                const matched = event.target.closest?.(selector);
                if (matched && (root === document || root.contains(matched))) {
                    handler.call(matched, event);
                }
            };

            registry.set(key, listener);
            root.addEventListener(type, listener);
        });
        return this;
    }
}

function $(input) {
    if (input instanceof DomCollection) return input;
    if (input === document || input === window || input instanceof Element) {
        return new DomCollection([input]);
    }
    if (typeof input !== 'string') return new DomCollection([]);

    const value = input.trim();
    if (value.startsWith('<')) {
        const template = document.createElement('template');
        template.innerHTML = value;
        return new DomCollection(Array.from(template.content.children));
    }

    return new DomCollection(document.querySelectorAll(input));
}

// Keeps the existing Select2 guard false without loading jQuery/Select2.
$.fn = {};

const psono   = <?= json_encode($orderid) ?>;
const clientid = <?= json_encode($clientid) ?>;
const orderDetailsUrl = <?= json_encode($orderDetailsUrl) ?>;

let allStates = [];
let boxFreightMaster = [];
let productDetails = [];
let deliveryDetails = [];
let mastersLoaded = false;
let savedPickupMode = 'pickup';
let currentPickupMode = '';

/* =========================================================
   INITIALIZE
   The PHP fragment may itself be loaded through AJAX, so
   execute initialization directly instead of depending on a
   new $(document).ready() callback.
========================================================= */
 
loadAllMasters();

 

async function loadAllMasters() {
    const projectId = Number(
        $('#orderDeliveryFreightForm input[name="projectid"]').val() ||
        <?= json_encode($projectid) ?>
    );

    if (!projectId) {
        console.error('Invalid Project ID.');
        return;
    }

    if (!psono) {
        console.error('PSO No is missing.');
        return;
    }

    const requestData = new URLSearchParams();
    requestData.append('projectid', projectId);
    requestData.append('psono', psono);

    const requestController = new AbortController();
    const requestTimeout = window.setTimeout(() => requestController.abort(), 20000);

    try {
        const response = await fetch(orderDetailsUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: requestData.toString(),
            credentials: 'same-origin',
            cache: 'no-store',
            signal: requestController.signal
        });

        const responseText = await response.text();

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${responseText || response.statusText}`);
        }

        if (!responseText.trim()) {
            throw new Error('The server returned an empty response.');
        }

        let res;
        try {
            res = JSON.parse(responseText);
        } catch (parseError) {
            throw new Error(`Invalid JSON returned by PHP: ${responseText}`);
        }

        if (!res || res.status !== 'success') {
            throw new Error(res?.message || 'Unable to load order details.');
        }

        allStates = Array.isArray(res.allstates) ? res.allstates : [];
        boxFreightMaster = Array.isArray(res.boxfreightmaster) ? res.boxfreightmaster : [];
        productDetails = Array.isArray(res.productdetails) ? res.productdetails : [];
        deliveryDetails = Array.isArray(res.deliverydetails) ? res.deliverydetails : [];
        mastersLoaded = true;
debugger;
        // DB column returned by getdeliverylocation() is pickuptype.
        savedPickupMode = normalizePickupMode(deliveryDetails[0]?.pickuptype);
        const pickupMode = savedPickupMode;
        const $radio = $(`#orderDeliveryFreightForm input[name="pickupfrom"][value="${pickupMode}"]`);
        $radio.prop('checked', true);
        handlePickupFromChange(pickupMode, true);
    } catch (error) {
        mastersLoaded = false;
        const message = error?.name === 'AbortError'
            ? 'The server took more than 20 seconds to respond. Check the database connection or slow SQL query.'
            : (error?.message || 'Unable to load order details.');

        console.error('Unable to load order details:', error);

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Unable to load delivery details',
                text: message
            });
        }
    } finally {
        window.clearTimeout(requestTimeout);
    }
}

/* =========================================================
   COMMON HELPERS
========================================================= */
function escapeHtml(value) {
    return $('<div>').text(value ?? '').html();
}

function normalizeText(value) {
    return String(value ?? '').trim().toLowerCase();
}

function toNumber(value) {
    const n = parseFloat(value);
    return Number.isFinite(n) ? n : 0;
}

function formatDateForInput(value) {
    if (!value) return '';
    const s = String(value).trim();

    const isoMatch = s.match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (isoMatch) return `${isoMatch[1]}-${isoMatch[2]}-${isoMatch[3]}`;

    const displayMatch = s.match(/^(\d{2})[-\/]([0-1]\d)[-\/](\d{4})$/);
    if (displayMatch) {
        return `${displayMatch[3]}-${displayMatch[2]}-${displayMatch[1]}`;
    }

    return '';
}

function getStateOptions(selectedStateId = '') {
    selectedStateId = String(selectedStateId ?? '');
    let html = '<option value="">-- Select State --</option>';
    const uniqueStates = new Map();

    allStates.forEach(row => {
        const id = String(row.stateid ?? '');
        const name = String(row.statename ?? '');
        if (id && !uniqueStates.has(id)) {
            uniqueStates.set(id, name);
        }
    });

    uniqueStates.forEach((name, id) => {
        html += `
            <option value="${escapeHtml(id)}"
                    data-name="${escapeHtml(name)}"
function getStateOptions(selectedStateId = '') {
    selectedStateId = String(selectedStateId ?? '').trim();
    const selectedNum = parseInt(selectedStateId, 10);
    let html = '<option value="">-- Select State --</option>';
    const uniqueStates = new Map();

    allStates.forEach(row => {
        const id = String(row.stateid ?? '').trim();
        const name = String(row.statename ?? '').trim();
        if (id && !uniqueStates.has(id)) {
            uniqueStates.set(id, name);
        }
    });

    uniqueStates.forEach((name, id) => {
        const isSelected = (id === selectedStateId) || (!isNaN(selectedNum) && selectedNum > 0 && parseInt(id, 10) === selectedNum);
        html += `
            <option value="${escapeHtml(id)}"
                    data-name="${escapeHtml(name)}"
                    ${isSelected ? 'selected' : ''}>
                ${escapeHtml(name)}
            </option>`;
    });

    return html;
}

function getDistrictOptions(stateId = '', selectedDistrictId = '') {
    stateId = String(stateId ?? '').trim();
    selectedDistrictId = String(selectedDistrictId ?? '').trim();
    const stateNum = parseInt(stateId, 10);
    const distNum = parseInt(selectedDistrictId, 10);
    let html = '<option value="">-- Select District --</option>';

    if (!stateId) return html;

    const uniqueDistricts = new Map();

    allStates.forEach(row => {
        const rStateId = String(row.stateid ?? '').trim();
        const stateMatches = (rStateId === stateId) || (!isNaN(stateNum) && stateNum > 0 && parseInt(rStateId, 10) === stateNum);
        if (!stateMatches) return;

        const id = String(row.districtid ?? '').trim();
        const name = String(row.districtname ?? '').trim();

        if (id && !uniqueDistricts.has(id)) {
            uniqueDistricts.set(id, name);
        }
    });

    uniqueDistricts.forEach((name, id) => {
        const isSelected = (id === selectedDistrictId) || (!isNaN(distNum) && distNum > 0 && parseInt(id, 10) === distNum);
        html += `
            <option value="${escapeHtml(id)}"
                    data-name="${escapeHtml(name)}"
                    ${isSelected ? 'selected' : ''}>
                ${escapeHtml(name)}
            </option>`;
    });

    return html;
}

function getProductOptions(selectedProductId = '') {
    selectedProductId = String(selectedProductId ?? '');
    let html = '<option value="">-- Select Product --</option>';

    productDetails.forEach(product => {
        const id = String(product.product_id ?? '');
        const name = String(product.product_name ?? '');

        html += `
            <option value="${escapeHtml(id)}"
                    ${id === selectedProductId ? 'selected' : ''}>
                ${escapeHtml(name)}
            </option>`;
    });

    return html;
}

function initSelect2($elements) {
    if (typeof $.fn.select2 !== 'function') return;

    $elements.each(function () {
        const $el = $(this);
        if ($el.hasClass('select2-hidden-accessible')) {
            $el.select2('destroy');
        }
        $el.select2({ width: '100%' });
    });
}

/* =========================================================
   DELIVERY MODE
========================================================= */
function normalizePickupMode(value) {
    const mode = String(value ?? '').trim().toLowerCase();
    return ['delivered', 'pickup', 'boxpacking'].includes(mode) ? mode : 'pickup';
}

function handlePickupFromChange(pickupFrom, forceFill = false) {
    pickupFrom = normalizePickupMode(pickupFrom);
    currentPickupMode = pickupFrom;
    const useSavedAddress = currentPickupMode === savedPickupMode;

    if (pickupFrom !== 'delivered') {
        $('#freeFreightNotification').empty().hide();
        $('#spanratedbl').html("").addClass("d-none");
        if (typeof updateDiscountRemarksNarration === 'function') {
            updateDiscountRemarksNarration();
        }
    }

    if (pickupFrom === 'delivered') {
        $('#deliveryaddressdiv').removeClass('d-none').show();
        $('#deliveryboxdiv').addClass('d-none').hide();

        // Always rebuild so values from a previously selected mode cannot remain.
        fillDeliveredProducts(useSavedAddress);
        return;
    }

    if (pickupFrom === 'boxpacking') {
        $('#deliveryaddressdiv').addClass('d-none').hide();
        $('#deliveryboxdiv').removeClass('d-none').show();

        fillBoxPackingProducts(useSavedAddress);
        return;
    }

    // Self pickup
    $('#deliveryaddressdiv').addClass('d-none').hide();
    $('#deliveryboxdiv').addClass('d-none').hide();
}

$(document)
    .off('change.orderFreightPickup', '#orderDeliveryFreightForm input[name="pickupfrom"]')
    .on('change.orderFreightPickup', '#orderDeliveryFreightForm input[name="pickupfrom"]', function () {
        if (!mastersLoaded) return;
        handlePickupFromChange(this.value, false);
    });

/* =========================================================
   DELIVERED MODE
========================================================= */
function getDeliveryLocationForProduct(item, index) {
    if (!deliveryDetails.length) return {};

    const productId = String(item?.product_id ?? item?.productid ?? '');

    if (productId) {
        const exactMatch = deliveryDetails.find(function (row) {
            const savedProductId = row.product_id ?? row.productid ??
                row.delivery_productid ?? row.productId ?? '';
            return String(savedProductId) === productId;
        });

        if (exactMatch) return exactMatch;
    }

    // Backward-compatible fallback when the location query has no product ID.
    if (deliveryDetails[index]) return deliveryDetails[index];
    return deliveryDetails.length === 1 ? deliveryDetails[0] : {};
}

function resolveSavedStateId(existing) {
    if (!existing) return '';
    const directId = String(existing.stateid ?? existing.dest_stateid ?? '').trim();
    const stateName = normalizeText(existing.dest_state ?? existing.statename);
    const fullAddress = normalizeText(existing.deliveryaddress ?? existing.fulladdress);

    // 1. Try matching by stateName
    if (stateName) {
        const match = allStates.find(row => normalizeText(row.statename) === stateName);
        if (match) return String(match.stateid ?? '');
    }

    // 2. Try directId if non-zero
    if (directId && directId !== '0') {
        const match = allStates.find(row => 
            String(row.stateid) === directId || 
            parseInt(row.stateid, 10) === parseInt(directId, 10)
        );
        if (match) return String(match.stateid ?? '');
    }

    // 3. Try parsing from fullAddress
    if (fullAddress) {
        const match = allStates.find(row => {
            const sName = normalizeText(row.statename);
            return sName && fullAddress.includes(sName);
        });
        if (match) return String(match.stateid ?? '');
    }

    return '';
}

function resolveSavedDistrictId(existing, stateId) {
    if (!existing) return '';
    const directId = String(existing.districtid ?? existing.dest_districtid ?? '').trim();
    const districtName = normalizeText(existing.dest_district ?? existing.districtname);
    const fullAddress = normalizeText(existing.deliveryaddress ?? existing.fulladdress);

    const stateNum = parseInt(stateId, 10);
    const stateRows = stateId 
        ? allStates.filter(row => String(row.stateid) === String(stateId) || (!isNaN(stateNum) && stateNum > 0 && parseInt(row.stateid, 10) === stateNum))
        : allStates;

    // 1. Try matching by districtName within state
    if (districtName) {
        const match = stateRows.find(row => normalizeText(row.districtname) === districtName);
        if (match) return String(match.districtid ?? '');
    }

    // 2. Try directId within state if non-zero
    if (directId && directId !== '0') {
        const match = stateRows.find(row => 
            String(row.districtid) === directId || 
            parseInt(row.districtid, 10) === parseInt(directId, 10)
        );
        if (match) return String(match.districtid ?? '');
    }

    // 3. Try parsing from fullAddress within state
    if (fullAddress) {
        const match = stateRows.find(row => {
            const dName = normalizeText(row.districtname);
            return dName && fullAddress.includes(dName);
        });
        if (match) return String(match.districtid ?? '');
    }

    return '';
}

function fillDeliveredProducts(useSavedAddress = false) {
    const $tbody = $('#deliveryaddresstbl tbody');
    $tbody.empty();

    if (!productDetails.length) {
        $tbody.html('<tr><td colspan="11" class="text-center text-muted py-3">No products found.</td></tr>');
        return;
    }

    productDetails.forEach((item, index) => {
        const existing = useSavedAddress
            ? getDeliveryLocationForProduct(item, index)
            : {};
        const productId = String(item.product_id ?? '');
        const qty = item.quantity ?? '';
        const expectedDate = formatDateForInput(item.expecteddeliverydate);
        const stateId = resolveSavedStateId(existing);
        const districtId = resolveSavedDistrictId(existing, stateId);
        const city = existing.dest_city ?? existing.city ?? '';
        const pincode = existing.dest_pincode ?? existing.pincode ?? '';
        const address = existing.deliveryaddress ?? existing.fulladdress ?? '';
        const distance = (existing.totaldistance !== undefined && existing.totaldistance !== null && parseFloat(existing.totaldistance) > 0)
            ? existing.totaldistance
            : (existing.distance ?? '');
        const matchedState = allStates.find(r => String(r.stateid) === String(stateId) || (parseInt(r.stateid, 10) === parseInt(stateId, 10)));
        const matchedDistrict = allStates.find(r => (String(r.stateid) === String(stateId) || (parseInt(r.stateid, 10) === parseInt(stateId, 10))) && (String(r.districtid) === String(districtId) || parseInt(r.districtid, 10) === parseInt(districtId, 10)));
        const resolvedStateName = existing.dest_state ?? existing.statename ?? matchedState?.statename ?? '';
        const resolvedDistrictName = existing.dest_district ?? existing.districtname ?? matchedDistrict?.districtname ?? '';
        const lat = existing.dest_latitude ?? existing.latitude ?? '';
        const lng = existing.dest_longitude ?? existing.longitude ?? '';
        const latlong = (lat !== '' || lng !== '') ? `${lat},${lng}` : '';

        const $row = $(`
            <tr class="align-middle">
                <td>
                    <input type="hidden" name="delivery_location_id[]"
                           value="${escapeHtml(existing.id ?? '')}">
                    <input type="hidden" name="delivery_productid[]"
                           value="${escapeHtml(productId)}">
                    <select class="form-select delivery_product_select" required disabled>
                        ${getProductOptions(productId)}
                    </select>
                </td>
                <td class="delivery-expected-date-cell">
                    <input type="date" name="delivery_expected_delivery[]"
                           class="form-control delivery_expected_delivery"
                           value="${escapeHtml(expectedDate)}" readonly>
                </td>
                <td>
                    <input type="text" name="delivery_qty[]" class="form-control delivery_qty_input"
                           value="${escapeHtml(qty)}" placeholder="0"
                           onkeypress="return isFloatNumber(event,this);" required readonly>
                    <div class="pending-qty-badge mt-1" style="font-size:.75rem;display:none;"></div>
                </td>
                <td>
                    <input type="text" name="delivery_distance[]" class="form-control delivery_distance_input"
                           value="${escapeHtml(distance)}" placeholder="0"
                           onkeypress="return isFloatNumber(event,this);">
                </td>
                <td class="d-none">
                    <input type="hidden" name="delivery_latlong[]" value="${escapeHtml(latlong)}">
                </td>
                <td>
                    <select class="form-control del_state" name="del_state[]" required>
                        ${getStateOptions(stateId)}
                    </select>
                    <input type="hidden" class="delivery_state" name="delivery_state[]"
                           value="${escapeHtml(resolvedStateName)}">
                    <input type="hidden" class="delivery_stateid" name="delivery_stateid[]"
                           value="${escapeHtml(stateId)}">
                </td>
                <td>
                    <select class="form-control del_district" name="del_district[]" required>
                        ${getDistrictOptions(stateId, districtId)}
                    </select>
                    <input type="hidden" class="delivery_district" name="delivery_district[]"
                           value="${escapeHtml(resolvedDistrictName)}">
                    <input type="hidden" class="delivery_district_id" name="delivery_district_id[]"
                           value="${escapeHtml(districtId)}">
                </td>
                <td>
                    <input type="text" name="delivery_city[]" class="form-control"
                           value="${escapeHtml(city)}" placeholder="City/Village">
                </td>
                <td>
                    <input type="text" name="delivery_pincode[]" class="form-control"
                           value="${escapeHtml(pincode)}" placeholder="Pincode" onkeypress="return isFloatNumber(event,this);">
                </td>
                <td>
                    <input type="text" name="delivery_fulladdress[]" class="form-control"
                           value="${escapeHtml(address)}" placeholder="Full Address" required>
                </td>
              <td class="text-center">
                  <div class="d-flex gap-1 justify-content-center">
                      <button
                          type="button"
                          class="btn btn-sm btn-outline-secondary row-history-btn"
                          title="Choose Previous Address"
                      >
                          <i class="fa fa-history"></i>
                      </button>

                      <button
                          type="button"
                          class="btn btn-sm btn-outline-primary row-latlong-btn"
                          title="Get Address From Latitude/Longitude"
                      >
                          <i class="fa fa-map-marker-alt"></i>
                      </button>
                  </div>
              </td>
            </tr>`);

        // Keep header/body alignment safe even if an older cached row template is mixed in.
        if (!$row.find('.delivery_expected_delivery').length) {
            const productCell = $row.elements[0]?.children?.[0];
            if (productCell) {
                const dateCell = document.createElement('td');
                dateCell.className = 'delivery-expected-date-cell';
                dateCell.innerHTML = `
                    <input type="date" name="delivery_expected_delivery[]"
                           class="form-control delivery_expected_delivery"
                           value="${escapeHtml(expectedDate)}">`;
                productCell.insertAdjacentElement('afterend', dateCell);
            }
        }

        $tbody.append($row);
        syncDeliveryLocationFields($row);
        initSelect2($row.find('.delivery_product_select, .del_state, .del_district'));
    });
}

function syncDeliveryLocationFields($row) {
    const $state = $row.find('.del_state');
    const $district = $row.find('.del_district');

    $row.find('.delivery_stateid').val($state.val() || '');
    $row.find('.delivery_state').val($state.find('option:checked').data('name') || '');
    $row.find('.delivery_district_id').val($district.val() || '');
    $row.find('.delivery_district').val($district.find('option:checked').data('name') || '');
}
let activeDeliveryAddressRow = null;
$(document)
    .off('change.orderFreightDelState', '#deliveryaddresstbl .del_state')
    .on('change.orderFreightDelState', '#deliveryaddresstbl .del_state', function () {
        const $row = $(this).closest('tr');
        const stateId = $(this).val() || '';
        const $district = $row.find('.del_district');

        $row.find('.delivery_stateid').val(stateId);
        $row.find('.delivery_state').val($(this).find('option:checked').data('name') || '');

        $district.html(getDistrictOptions(stateId)).val('');
        $row.find('.delivery_district_id, .delivery_district').val('');
        $district.trigger('change.select2');
    });

$(document)
    .off('change.orderFreightDelDistrict', '#deliveryaddresstbl .del_district')
    .on('change.orderFreightDelDistrict', '#deliveryaddresstbl .del_district', function () {
        const $row = $(this).closest('tr');
        $row.find('.delivery_district_id').val($(this).val() || '');
        $row.find('.delivery_district').val($(this).find('option:checked').data('name') || '');
    });

/* =========================================================
   BOX PACKING MODE
========================================================= */
function findBoxFreightMaster(stateId, districtId, location) {
    stateId = String(stateId ?? '');
    districtId = String(districtId ?? '');
    const loc = normalizeText(location);

    if (!stateId || !districtId || !boxFreightMaster.length) return null;

    // Prefer exact State + District + Location match.
    let match = boxFreightMaster.find(row =>
        String(row.stateid ?? '') === stateId &&
        String(row.districtid ?? '') === districtId &&
        normalizeText(row.location) === loc
    );

    // If the master has only one row for State + District, it is safe to use it.
    if (!match) {
        const candidates = boxFreightMaster.filter(row =>
            String(row.stateid ?? '') === stateId &&
            String(row.districtid ?? '') === districtId
        );
        if (candidates.length === 1) match = candidates[0];
    }

    return match || null;
}

function getBestPickupPoint(freight) {
    if (!freight) return { type: '', amount: 0 };

    const options = [
        { type: 'Airport', amount: toNumber(freight.airport_charges) },
        { type: 'Bus', amount: toNumber(freight.bus_charges) },
        { type: 'Railway', amount: toNumber(freight.railway_charges) }
    ].filter(x => x.amount > 0);

    if (!options.length) return { type: '', amount: 0 };

    options.sort((a, b) => b.amount - a.amount);
    return options[0];
}

function getPickupPointOptions(freight, selectedPoint = '') {
    let html = '<option value="">-- Select Pickup Point --</option>';
    if (!freight) return html;

    const options = [
        { value: 'Airport', label: 'Airport', amount: toNumber(freight.airport_charges) },
        { value: 'Bus', label: 'Bus Stop', amount: toNumber(freight.bus_charges) },
        { value: 'Railway', label: 'Railway Station', amount: toNumber(freight.railway_charges) }
    ];

    options.forEach(option => {
        if (option.amount <= 0) return;
        html += `
            <option value="${option.value}"
                    data-charge="${option.amount}"
                    ${option.value === selectedPoint ? 'selected' : ''}>
                ${option.label}
            </option>`;
    });

    return html;
}

function fillBoxPackingProducts(useSavedAddress = false) {
    const $tbody = $('#deliveryboxtbl tbody');
    $tbody.empty();

    if (!productDetails.length) {
        $tbody.html('<tr><td colspan="13" class="text-center text-muted py-3">No products found.</td></tr>');
        return;
    }

    productDetails.forEach((item, index) => {
        const existing = useSavedAddress
            ? getDeliveryLocationForProduct(item, index)
            : {};
        const productId = String(item.product_id ?? '');
        const productName = item.product_name ?? '';
        const qty = toNumber(item.quantity);
        const expectedDate = formatDateForInput(item.expecteddeliverydate);
        const stateId = resolveSavedStateId(existing);
        const districtId = resolveSavedDistrictId(existing, stateId);
        const city = existing.dest_city ?? existing.city ?? '';

        const freight = findBoxFreightMaster(stateId, districtId, city);
        const plantsPerBox = toNumber(freight?.plants_per_box);
        const noOfBoxes = plantsPerBox > 0 ? Math.ceil(qty / plantsPerBox) : 0;
        const bestPickup = getBestPickupPoint(freight);
        const chargePerPack = bestPickup.amount;
        const amount = noOfBoxes * chargePerPack;

        const $row = $(`
            <tr class="align-middle box-packing-row" data-productid="${escapeHtml(productId)}">
                <td class="text-center">${index + 1}</td>
                <td>
                    <div class="fw-bold">${escapeHtml(productName)}</div>
                    <input type="hidden" name="box_location_id[]"
                           value="${escapeHtml(existing.id ?? '')}">
                    <input type="hidden" name="box_productid[]" value="${escapeHtml(productId)}">
                </td>
                <td>
                    <input type="date" name="box_expected_delivery[]" class="form-control box_expected_delivery"
                           value="${escapeHtml(expectedDate)}" readonly>
                </td>
                <td>
                    <input type="text" name="box_delivery_qty[]" class="form-control box_delivery_qty"
                           value="${escapeHtml(qty)}" readonly>
                </td>
                <td>
                    <select name="box_state[]" class="form-control box_state" required>
                        ${getStateOptions(stateId)}
                    </select>
                    <input type="hidden" name="box_state_name[]" class="box_state_name"
                           value="${escapeHtml(existing.dest_state ?? existing.statename ?? '')}">
                </td>
                <td>
                    <select name="box_district[]" class="form-control box_district" required>
                        ${getDistrictOptions(stateId, districtId)}
                    </select>
                    <input type="hidden" name="box_district_name[]" class="box_district_name"
                           value="${escapeHtml(existing.dest_district ?? existing.districtname ?? '')}">
                </td>
                <td>
                    <input type="text" name="box_city[]" class="form-control box_city"
                           value="${escapeHtml(city)}" placeholder="City/Village">
                </td>
                <td>
                    <input type="text" name="box_per_pack[]" class="form-control box_per_pack"
                           value="${plantsPerBox}" readonly>
                </td>
                <td>
                    <input type="text" name="box_count[]" class="form-control box_count"
                           value="${noOfBoxes}" readonly>
                </td>
                <td>
                    <select name="box_pickup_point[]" class="form-control box_pickup_point">
                        ${getPickupPointOptions(freight, bestPickup.type)}
                    </select>
                </td>
                <td>
                    <input type="text" name="box_charge_per_pack[]" class="form-control box_charge_per_pack"
                           value="${chargePerPack.toFixed(2)}"
                           onkeypress="return isFloatNumber(event,this);">
                </td>
                <td>
                    <input type="text" name="box_amount[]" class="form-control box_amount"
                           value="${amount.toFixed(2)}" readonly>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-primary box_apply_all">
                        Apply All
                    </button>
                </td>
            </tr>`);

        $tbody.append($row);
        initSelect2($row.find('.box_state, .box_district'));
        syncBoxLocationFields($row);
    });
}

function syncBoxLocationFields($row) {
    const $state = $row.find('.box_state');
    const $district = $row.find('.box_district');

    $row.find('.box_state_name').val($state.find('option:checked').data('name') || '');
    $row.find('.box_district_name').val($district.find('option:checked').data('name') || '');
}

function resetBoxFreightRow($row) {
    $row.find('.box_per_pack').val('0');
    $row.find('.box_count').val('0');
    $row.find('.box_pickup_point').html('<option value="">-- Select Pickup Point --</option>');
    $row.find('.box_charge_per_pack').val('0.00');
    $row.find('.box_amount').val('0.00');
}

function prepareBoxPackingRow($row) {
    const stateId = $row.find('.box_state').val() || '';
    const districtId = $row.find('.box_district').val() || '';
    const city = $row.find('.box_city').val() || '';
    const qty = toNumber($row.find('.box_delivery_qty').val());
    const freight = findBoxFreightMaster(stateId, districtId, city);

    if (!freight) {
        resetBoxFreightRow($row);
        return;
    }

    const plantsPerBox = toNumber(freight.plants_per_box);
    const noOfBoxes = plantsPerBox > 0 ? Math.ceil(qty / plantsPerBox) : 0;
    const bestPickup = getBestPickupPoint(freight);

    $row.find('.box_per_pack').val(plantsPerBox);
    $row.find('.box_count').val(noOfBoxes);
    $row.find('.box_pickup_point').html(getPickupPointOptions(freight, bestPickup.type));
    $row.find('.box_charge_per_pack').val(bestPickup.amount.toFixed(2));
    calculateBoxPackingAmount($row);
}

function calculateBoxPackingAmount($row) {
    const boxes = toNumber($row.find('.box_count').val());
    const charge = toNumber($row.find('.box_charge_per_pack').val());
    $row.find('.box_amount').val((boxes * charge).toFixed(2));
}

$(document)
    .off('change.orderFreightBoxState', '#deliveryboxtbl .box_state')
    .on('change.orderFreightBoxState', '#deliveryboxtbl .box_state', function () {
        const $row = $(this).closest('tr');
        const stateId = $(this).val() || '';
        const $district = $row.find('.box_district');

        $row.find('.box_state_name').val($(this).find('option:checked').data('name') || '');
        $row.find('.box_district_name').val('');
        $district.html(getDistrictOptions(stateId)).val('').trigger('change.select2');
        resetBoxFreightRow($row);
    });

$(document)
    .off('change.orderFreightBoxDistrict', '#deliveryboxtbl .box_district')
    .on('change.orderFreightBoxDistrict', '#deliveryboxtbl .box_district', function () {
        const $row = $(this).closest('tr');
        $row.find('.box_district_name').val($(this).find('option:checked').data('name') || '');
        prepareBoxPackingRow($row);
    });

$(document)
    .off('change.orderFreightBoxCity', '#deliveryboxtbl .box_city')
    .on('change.orderFreightBoxCity', '#deliveryboxtbl .box_city', function () {
        prepareBoxPackingRow($(this).closest('tr'));
    });

$(document)
    .off('blur.orderFreightBoxCity', '#deliveryboxtbl .box_city')
    .on('blur.orderFreightBoxCity', '#deliveryboxtbl .box_city', function () {
        prepareBoxPackingRow($(this).closest('tr'));
    });

$(document)
    .off('change.orderFreightPickupPoint', '#deliveryboxtbl .box_pickup_point')
    .on('change.orderFreightPickupPoint', '#deliveryboxtbl .box_pickup_point', function () {
        const $row = $(this).closest('tr');
        const charge = toNumber($(this).find('option:checked').data('charge'));
        $row.find('.box_charge_per_pack').val(charge.toFixed(2));
        calculateBoxPackingAmount($row);
    });

$(document)
    .off('input.orderFreightBoxCharge change.orderFreightBoxCharge', '#deliveryboxtbl .box_charge_per_pack')
    .on('input.orderFreightBoxCharge change.orderFreightBoxCharge', '#deliveryboxtbl .box_charge_per_pack', function () {
        calculateBoxPackingAmount($(this).closest('tr'));
    });

$(document)
    .off('click.orderFreightApplyAll', '#deliveryboxtbl .box_apply_all')
    .on('click.orderFreightApplyAll', '#deliveryboxtbl .box_apply_all', function () {
        const $source = $(this).closest('tr');
        const stateId = $source.find('.box_state').val() || '';
        const districtId = $source.find('.box_district').val() || '';
        const city = $source.find('.box_city').val() || '';
        const stateName = $source.find('.box_state_name').val() || '';
        const districtName = $source.find('.box_district_name').val() || '';

        $('#deliveryboxtbl tbody tr.box-packing-row').each(function () {
            const $row = $(this);
            if ($row.is($source)) return;

            const $state = $row.find('.box_state');
            const $district = $row.find('.box_district');

            $state.val(stateId).trigger('change.select2');
            $row.find('.box_state_name').val(stateName);

            $district.html(getDistrictOptions(stateId, districtId));
            $district.val(districtId).trigger('change.select2');
            $row.find('.box_district_name').val(districtName);
            $row.find('.box_city').val(city);

            prepareBoxPackingRow($row);
        });
    });

/* =========================================================
   ADD BLANK DELIVERY ROW
========================================================= */
$(document)
    .off('click.orderFreightBlankRow', '#addBlankAddressRow')
    .on('click.orderFreightBlankRow', '#addBlankAddressRow', function () {
        const $tbody = $('#deliveryaddresstbl tbody');
        $tbody.find('tr td[colspan]').closest('tr').remove();

        const $row = $(`
            <tr class="align-middle">
                <td>
                    <input type="hidden" name="delivery_location_id[]" value="">
                    <select name="delivery_productid[]" class="form-select delivery_product_select" required>
                        ${getProductOptions('')}
                    </select>
                </td>
                <td class="delivery-expected-date-cell">
                    <input type="date" name="delivery_expected_delivery[]"
                           class="form-control delivery_expected_delivery" value="">
                </td>
                <td>
                    <input type="text" name="delivery_qty[]" class="form-control delivery_qty_input"
                           value="" placeholder="0" onkeypress="return isFloatNumber(event,this);" required>
                    <div class="pending-qty-badge mt-1" style="font-size:.75rem;display:none;"></div>
                </td>
                <td>
                    <input type="text" name="delivery_distance[]" class="form-control delivery_distance_input"
                           value="" placeholder="0" onkeypress="return isFloatNumber(event,this);">
                </td>
                <td class="d-none"><input type="hidden" name="delivery_latlong[]" value=""></td>
                <td>
                    <select class="form-control del_state" name="del_state[]" required>${getStateOptions('')}</select>
                    <input type="hidden" class="delivery_state" name="delivery_state[]" value="">
                    <input type="hidden" class="delivery_stateid" name="delivery_stateid[]" value="">
                </td>
                <td>
                    <select class="form-control del_district" name="del_district[]" required>${getDistrictOptions('', '')}</select>
                    <input type="hidden" class="delivery_district" name="delivery_district[]" value="">
                    <input type="hidden" class="delivery_district_id" name="delivery_district_id[]" value="">
                </td>
                <td><input type="text" name="delivery_city[]" class="form-control" placeholder="City/Village"></td>
                <td><input type="text" name="delivery_pincode[]" class="form-control" placeholder="Pincode"></td>
                <td><input type="text" name="delivery_fulladdress[]" class="form-control" placeholder="Full Address" required></td>
                <td class="text-center">
                    <div class="d-flex gap-1 justify-content-center">
                        <button type="button" class="btn btn-sm btn-outline-secondary row-history-btn" title="Choose Previous Address">
                            <i class="fa fa-history"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary row-latlong-btn" title="Get Address From Latitude/Longitude">
                            <i class="fa fa-map-marker-alt"></i>
                        </button>
                    </div>
                </td>
            </tr>`);

        $tbody.append($row);
        initSelect2($row.find('.delivery_product_select, .del_state, .del_district'));
    });

let clientDeliveryHistoryRows = [];
let clientDeliveryHistoryModalInstance = null;
let coordinateModalInstance = null;

function escapeHistoryHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function showClientHistoryModal() {
    const modalElement = document.getElementById(
        'clientDeliveryHistoryModal'
    );

    if (!modalElement) return;

    if (window.bootstrap && typeof bootstrap.Modal === 'function') {
        if (typeof bootstrap.Modal.getOrCreateInstance === 'function') {
            clientDeliveryHistoryModalInstance =
                bootstrap.Modal.getOrCreateInstance(modalElement);
        } else if (
            typeof bootstrap.Modal.getInstance === 'function' &&
            bootstrap.Modal.getInstance(modalElement)
        ) {
            clientDeliveryHistoryModalInstance =
                bootstrap.Modal.getInstance(modalElement);
        } else if (!clientDeliveryHistoryModalInstance) {
            clientDeliveryHistoryModalInstance = new bootstrap.Modal(modalElement);
        }

        clientDeliveryHistoryModalInstance.show();
        return;
    }

    /* Bootstrap modal fallback */
    modalElement.style.display = 'block';
    modalElement.classList.add('show');
    modalElement.removeAttribute('aria-hidden');
    modalElement.setAttribute('aria-modal', 'true');
    document.body.classList.add('modal-open');
}

function hideClientHistoryModal() {
    const modalElement = document.getElementById(
        'clientDeliveryHistoryModal'
    );

    if (!modalElement) return;

    if (window.bootstrap && typeof bootstrap.Modal === 'function') {
        if (!clientDeliveryHistoryModalInstance) {
            if (typeof bootstrap.Modal.getInstance === 'function') {
                clientDeliveryHistoryModalInstance =
                    bootstrap.Modal.getInstance(modalElement);
            }

            if (!clientDeliveryHistoryModalInstance) {
                clientDeliveryHistoryModalInstance = new bootstrap.Modal(modalElement);
            }
        }

        clientDeliveryHistoryModalInstance.hide();
        return;
    }

    modalElement.style.display = 'none';
    modalElement.classList.remove('show');
    modalElement.setAttribute('aria-hidden', 'true');
    modalElement.removeAttribute('aria-modal');
    document.body.classList.remove('modal-open');
}

document.addEventListener('click', function (event) {
    const historyButton = event.target.closest(
        '.row-history-btn'
    );

    if (!historyButton) return;

    activeDeliveryAddressRow = historyButton.closest('tr');
    if (!activeDeliveryAddressRow) return;

    const clientId =
        document.getElementById('clientid')?.value?.trim() || '';

    if (!clientId) {
        Swal.fire(
            'No Client Selected',
            'Please select a client first.',
            'warning'
        );
        return;
    }

    const historyBody = document.querySelector(
        '#historyAddressTbl tbody'
    );

    if (!historyBody) return;

    historyBody.innerHTML = `
        <tr>
            <td colspan="5" class="text-center py-4">
                <div
                    class="spinner-border text-primary"
                    role="status"
                ></div>
                <br>
                Loading history...
            </td>
        </tr>
    `;

    showClientHistoryModal();

    if (typeof GetUniqeClientDeliveryAddressData !== 'function') {
        historyBody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-4 text-danger">
                    Delivery-history function is not available.
                </td>
            </tr>
        `;
        console.error('GetUniqeClientDeliveryAddressData() is not defined.');
        return;
    }

    GetUniqeClientDeliveryAddressData(
        clientId,
        function (res) {
            clientDeliveryHistoryRows =
                Array.isArray(res) ? res : [];

            if (!clientDeliveryHistoryRows.length) {
                historyBody.innerHTML = `
                    <tr>
                        <td
                            colspan="5"
                            class="text-center py-4 text-muted"
                        >
                            No previous delivery addresses
                            found for this client.
                        </td>
                    </tr>
                `;
                return;
            }

            historyBody.innerHTML =
                clientDeliveryHistoryRows.map((row, index) => `
                    <tr>
                        <td class="text-center chk-cell">
                            <input
                                type="radio"
                                name="history_address_selection"
                                class="form-check-input history-address-radio"
                                data-index="${index}"
                            >
                        </td>

                        <td
                            data-stateid="${
                                escapeHistoryHtml(row.stateid)
                            }"
                            data-districtid="${
                                escapeHistoryHtml(row.districtid)
                            }"
                        >
                            ${
                                escapeHistoryHtml(
                                    row.dest_state || '-'
                                )
                            }
                            <br>
                            <small class="text-muted">
                                ${
                                    escapeHistoryHtml(
                                        row.dest_district || '-'
                                    )
                                }
                            </small>
                        </td>

                        <td>
                            ${
                                escapeHistoryHtml(
                                    row.dest_city || '-'
                                )
                            }
                            <br>
                            <small class="text-muted">
                                ${
                                    escapeHistoryHtml(
                                        row.dest_pincode || '-'
                                    )
                                }
                            </small>
                        </td>

                        <td
                            style="
                                max-width:300px;
                                white-space:normal;
                            "
                        >
                            ${
                                escapeHistoryHtml(
                                    row.deliveryaddress || '-'
                                )
                            }
                        </td>

                        <td>
                            ${
                                escapeHistoryHtml(
                                    row.distance || 0
                                )
                            } KM
                        </td>
                    </tr>
                `).join('');
        }
    );
});

function setDeliveryRowValue(deliveryRow, selector, value) {
    const input = deliveryRow?.querySelector(selector);
    if (input) input.value = value ?? '';
}

function applyAddressDataToDeliveryRow(deliveryRow, addressData) {
    if (!deliveryRow || !addressData) return;

    const stateSelect = deliveryRow.querySelector('.del_state');
    const districtSelect = deliveryRow.querySelector('.del_district');

    if (stateSelect) {
        stateSelect.value = String(addressData.stateid ?? '');
        stateSelect.dispatchEvent(new Event('change', { bubbles: true }));
    }

    if (districtSelect) {
        districtSelect.value = String(addressData.districtid ?? '');
        districtSelect.dispatchEvent(new Event('change', { bubbles: true }));
    }

    setDeliveryRowValue(deliveryRow, '[name="delivery_city[]"]', addressData.dest_city);
    setDeliveryRowValue(deliveryRow, '[name="delivery_pincode[]"]', addressData.dest_pincode);
    setDeliveryRowValue(deliveryRow, '[name="delivery_fulladdress[]"]', addressData.deliveryaddress);
    setDeliveryRowValue(deliveryRow, '[name="delivery_distance[]"]', addressData.distance);

    const latitude = addressData.dest_latitude ?? '';
    const longitude = addressData.dest_longitude ?? '';
    setDeliveryRowValue(
        deliveryRow,
        '[name="delivery_latlong[]"]',
        latitude !== '' || longitude !== '' ? `${latitude},${longitude}` : ''
    );

    deliveryRow.classList.add('table-success');
    window.setTimeout(function () {
        deliveryRow.classList.remove('table-success');
    }, 1200);
}

/* Apply one selected history address to only the clicked delivery row. */
document.addEventListener('click', function (event) {
    const applyButton = event.target.closest('#btnApplySelectedHistory');
    if (!applyButton) return;

    const selectedAddress = document.querySelector(
        '#historyAddressTbl .history-address-radio:checked'
    );

    if (!selectedAddress) {
        if (typeof Swal !== 'undefined') {
            Swal.fire('No Address Selected', 'Select one previous delivery address.', 'warning');
        }
        return;
    }

    const historyRow = clientDeliveryHistoryRows[Number(selectedAddress.dataset.index)];
    if (!historyRow || !activeDeliveryAddressRow?.isConnected) return;

    applyAddressDataToDeliveryRow(activeDeliveryAddressRow, historyRow);
    hideClientHistoryModal();
});

function showCoordinateModal() {
    const modalElement = document.getElementById('adddeliveryaddressModal');
    if (!modalElement) return;

    if (window.bootstrap && typeof bootstrap.Modal === 'function') {
        if (typeof bootstrap.Modal.getOrCreateInstance === 'function') {
            coordinateModalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
        } else if (
            typeof bootstrap.Modal.getInstance === 'function' &&
            bootstrap.Modal.getInstance(modalElement)
        ) {
            coordinateModalInstance = bootstrap.Modal.getInstance(modalElement);
        } else if (!coordinateModalInstance) {
            coordinateModalInstance = new bootstrap.Modal(modalElement);
        }

        coordinateModalInstance.show();
        return;
    }

    modalElement.style.display = 'block';
    modalElement.classList.add('show');
    modalElement.removeAttribute('aria-hidden');
    modalElement.setAttribute('aria-modal', 'true');
    document.body.classList.add('modal-open');
}

function hideCoordinateModal() {
    const modalElement = document.getElementById('adddeliveryaddressModal');
    if (!modalElement) return;

    if (coordinateModalInstance && typeof coordinateModalInstance.hide === 'function') {
        coordinateModalInstance.hide();
        return;
    }

    modalElement.style.display = 'none';
    modalElement.classList.remove('show');
    modalElement.setAttribute('aria-hidden', 'true');
    modalElement.removeAttribute('aria-modal');
    document.body.classList.remove('modal-open');
}

function setCoordinateInputs(latitude = '', longitude = '') {
    const latLongInput = document.getElementById('latlonginput');
    const latitudeInput = document.getElementById('latinput');
    const longitudeInput = document.getElementById('longinput');

    if (latLongInput) {
        latLongInput.value = latitude !== '' || longitude !== ''
            ? `${latitude}, ${longitude}`
            : '';
    }
    if (latitudeInput) latitudeInput.value = latitude;
    if (longitudeInput) longitudeInput.value = longitude;
}

document.addEventListener('click', function (event) {
    const coordinateButton = event.target.closest('.row-latlong-btn');
    if (!coordinateButton) return;

    activeDeliveryAddressRow = coordinateButton.closest('tr');
    if (!activeDeliveryAddressRow) return;

    const savedCoordinates = activeDeliveryAddressRow
        .querySelector('[name="delivery_latlong[]"]')?.value || '';
    const parts = savedCoordinates.split(',').map(value => value.trim());
    setCoordinateInputs(parts[0] || '', parts[1] || '');

    showCoordinateModal();

    // Preserve the existing map/geocoding loader attached to this hidden button.
    const existingMapLoader = document.getElementById('isadddeliveryaddress');
    if (existingMapLoader) existingMapLoader.click();
});

document.addEventListener('input', function (event) {
    if (event.target.id !== 'latlonginput') return;

    const parts = event.target.value.split(',').map(value => value.trim());
    const latitudeInput = document.getElementById('latinput');
    const longitudeInput = document.getElementById('longinput');
    if (latitudeInput) latitudeInput.value = parts[0] || '';
    if (longitudeInput) longitudeInput.value = parts[1] || '';
});

document.addEventListener('input', function (event) {
    if (event.target.id !== 'latinput' && event.target.id !== 'longinput') return;

    const latitude = document.getElementById('latinput')?.value?.trim() || '';
    const longitude = document.getElementById('longinput')?.value?.trim() || '';
    const combinedInput = document.getElementById('latlonginput');
    if (combinedInput) combinedInput.value = `${latitude}, ${longitude}`;
});

document.addEventListener('click', function (event) {
    if (!event.target.closest('#btnApplyCoordinatesToRow')) return;

    const latitude = document.getElementById('latinput')?.value?.trim() || '';
    const longitude = document.getElementById('longinput')?.value?.trim() || '';
    const latitudeNumber = Number(latitude);
    const longitudeNumber = Number(longitude);

    if (
        latitude === '' || longitude === '' ||
        !Number.isFinite(latitudeNumber) || !Number.isFinite(longitudeNumber) ||
        latitudeNumber < -90 || latitudeNumber > 90 ||
        longitudeNumber < -180 || longitudeNumber > 180
    ) {
        if (typeof Swal !== 'undefined') {
            Swal.fire(
                'Invalid Coordinates',
                'Latitude must be between -90 and 90, and longitude between -180 and 180.',
                'warning'
            );
        }
        return;
    }

    if (!activeDeliveryAddressRow?.isConnected) return;
    const updatedRow = activeDeliveryAddressRow;
    setDeliveryRowValue(
        updatedRow,
        '[name="delivery_latlong[]"]',
        `${latitudeNumber},${longitudeNumber}`
    );

    updatedRow.classList.add('table-success');
    window.setTimeout(function () {
        updatedRow.classList.remove('table-success');
    }, 1200);

    hideCoordinateModal();
});

document.addEventListener('click', function (event) {
    if (event.target.closest('.close-coordinate-modal')) {
        hideCoordinateModal();
    }
});

// Existing map/geocoder code can call this to fill the clicked row.
window.applyAddressToActiveDeliveryRow = function (addressData) {
    if (!activeDeliveryAddressRow?.isConnected) return false;
    applyAddressDataToDeliveryRow(activeDeliveryAddressRow, addressData || {});
    return true;
};

/* Close modal without jQuery */
document.addEventListener('click', function (event) {
    if (!event.target.closest('.close-history-modal')) {
        return;
    }

    hideClientHistoryModal();
});


// Keep exactly one submit handler when this fragment is opened repeatedly.
// Capture phase blocks stale document handlers from older fragment versions,
// while the current request uses native fetch().
if (window.__orderDeliveryFreightSubmitHandler) {
    document.removeEventListener(
        'submit',
        window.__orderDeliveryFreightSubmitHandler,
        true
    );
}

window.__orderDeliveryFreightSubmitHandler = async function (e) {
            const submittedForm = e.target.closest?.('#orderDeliveryFreightForm');
            if (!submittedForm) return;

            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            const form = submittedForm;
            const $form = $(form);
            const $btn = $('#submitOrderBtn');

            const pickupMode = $form
                .find('input[name="pickupfrom"]:checked')
                .val() || '';

            if (!pickupMode) {
                showSubmitMessage(
                    'Delivery Mode Required',
                    'Please select a delivery mode.',
                    'warning'
                );
                return;
            }

            let validationError = '';

            /*
             * Delivered mode validation
             */
            if (pickupMode === 'delivered') {
                const $rows = $('#deliveryaddresstbl tbody tr');

                if (!$rows.length) {
                    validationError =
                        'No delivery-address rows are available.';
                } else {
                    $rows.each(function (index) {
                        if (validationError) {
                            return false;
                        }

                        const $row = $(this);

                        const productId = $row
                            .find('.delivery_product_select')
                            .val();

                        const expectedDate = $row
                            .find('.delivery_expected_delivery')
                            .val();

                        const quantity = parseFloat(
                            $row.find('.delivery_qty_input').val()
                        ) || 0;

                        const stateId = $row
                            .find('.del_state')
                            .val();

                        const districtId = $row
                            .find('.del_district')
                            .val();

                        const city = (
                            $row
                                .find('[name="delivery_city[]"]')
                                .val() || ''
                        ).trim();

                        const fullAddress = (
                            $row
                                .find('[name="delivery_fulladdress[]"]')
                                .val() || ''
                        ).trim();

                        if (!productId) {
                            validationError =
                                `Please select a product in delivery row ${index + 1}.`;
                        } else if (!expectedDate) {
                            validationError =
                                `Please select Exp. Del. Date in delivery row ${index + 1}.`;
                        } else if (quantity <= 0) {
                            validationError =
                                `Please enter a valid quantity in delivery row ${index + 1}.`;
                        } else if (!stateId) {
                            validationError =
                                `Please select a state in delivery row ${index + 1}.`;
                        } else if (!districtId) {
                            validationError =
                                `Please select a district in delivery row ${index + 1}.`;
                        } else if (!city) {
                            validationError =
                                `Please enter a city/village in delivery row ${index + 1}.`;
                        } else if (!fullAddress) {
                            validationError =
                                `Please enter the full address in delivery row ${index + 1}.`;
                        }

                        if (validationError) {
                            $row.addClass('table-danger');

                            window.setTimeout(function () {
                                $row.removeClass('table-danger');
                            }, 2000);

                            return false;
                        }
                    });
                }
            }

            /*
             * Box-packing mode validation
             */
            if (pickupMode === 'boxpacking') {
                const $rows = $('#deliveryboxtbl tbody tr.box-packing-row');

                if (!$rows.length) {
                    validationError =
                        'No box-packing rows are available.';
                } else {
                    $rows.each(function (index) {
                        if (validationError) {
                            return false;
                        }

                        const $row = $(this);

                        const productId = $row
                            .find('[name="box_productid[]"]')
                            .val();

                        const expectedDate = $row
                            .find('.box_expected_delivery')
                            .val();

                        const quantity = parseFloat(
                            $row.find('.box_delivery_qty').val()
                        ) || 0;

                        const stateId = $row
                            .find('.box_state')
                            .val();

                        const districtId = $row
                            .find('.box_district')
                            .val();

                        const city = (
                            $row.find('.box_city').val() || ''
                        ).trim();

                        const pickupPoint = $row
                            .find('.box_pickup_point')
                            .val();

                        const boxPerPack = parseFloat(
                            $row.find('.box_per_pack').val()
                        ) || 0;

                        const boxCount = parseFloat(
                            $row.find('.box_count').val()
                        ) || 0;

                        if (!productId) {
                            validationError =
                                `Product is missing in box row ${index + 1}.`;
                        } else if (!expectedDate) {
                            validationError =
                                `Please select Exp. Del. Date in box row ${index + 1}.`;
                        } else if (quantity <= 0) {
                            validationError =
                                `Invalid delivery quantity in box row ${index + 1}.`;
                        } else if (!stateId) {
                            validationError =
                                `Please select a state in box row ${index + 1}.`;
                        } else if (!districtId) {
                            validationError =
                                `Please select a district in box row ${index + 1}.`;
                        } else if (!city) {
                            validationError =
                                `Please enter a city/village in box row ${index + 1}.`;
                        } else if (boxPerPack <= 0) {
                            validationError =
                                `Box Per Pack is invalid in row ${index + 1}.`;
                        } else if (boxCount <= 0) {
                            validationError =
                                `No. of Boxes is invalid in row ${index + 1}.`;
                        } else if (!pickupPoint) {
                            validationError =
                                `Please select a pickup point in box row ${index + 1}.`;
                        }

                        if (validationError) {
                            $row.addClass('table-danger');

                            window.setTimeout(function () {
                                $row.removeClass('table-danger');
                            }, 2000);

                            return false;
                        }
                    });
                }
            }

            if (validationError) {
                showSubmitMessage(
                    'Validation',
                    validationError,
                    'warning'
                );
                return;
            }

            const submitUrl =
                form.dataset.saveUrl ||
                form.getAttribute('action') ||
                '';

            if (!submitUrl) {
                showSubmitMessage(
                    'Configuration Error',
                    'The save URL has not been configured.',
                    'error'
                );
                return;
            }

            const formData = new FormData(form);

            /*
             * Remove fields belonging to an inactive delivery mode.
             */
            const formKeys = Array.from(formData.keys());

            formKeys.forEach(function (key) {
                if (
                    pickupMode === 'delivered' &&
                    key.startsWith('box_')
                ) {
                    formData.delete(key);
                }

                if (
                    pickupMode === 'boxpacking' &&
                    key.startsWith('delivery_')
                ) {
                    formData.delete(key);
                }

                if (
                    pickupMode === 'pickup' &&
                    (
                        key.startsWith('delivery_') ||
                        key.startsWith('box_')
                    )
                ) {
                    formData.delete(key);
                }
            });

            /*
             * Unchecked checkboxes are not automatically added to FormData.
             * Add yes/no for every freight row to maintain correct row indexes.
             */
            formData.delete('f_zero_charges[]');

            $('#freightGroupTable tbody tr.group-row').each(
                function () {
                    const isZeroCharge = $(this)
                        .find('.f_zero_charge_chk')
                        .prop('checked');

                    formData.append(
                        'f_zero_charges[]',
                        isZeroCharge ? 'yes' : 'no'
                    );
                }
            );

            formData.append(
                'total_freight_amount',
                $('#total_freight_amt').text().trim() || '0.00'
            );

            formData.append(
                'total_payable_freight',
                $('#total_payable_freight_amt').text().trim() ||
                    '0.00'
            );

            if (typeof updateDiscountRemarksNarration === 'function') {
                updateDiscountRemarksNarration();
            }

            const userRemarks =
                form.querySelector('[name="remarks"]')?.value?.trim() || '';
            if (userRemarks) {
                formData.set('remarks', userRemarks);
            }

            if (typeof Swal !== 'undefined') {
                const confResult = await Swal.fire({
                    title: 'Confirm Provisional Order',
                    text: 'Are you sure you want to submit and create this provisional sale order?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#0d6efd',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fa fa-check-circle me-1"></i> Yes, Submit Order',
                    cancelButtonText: 'Cancel'
                });
                if (!confResult.isConfirmed) {
                    return;
                }
            }

            const originalButtonHtml = $btn.html();

            $btn
                .prop('disabled', true)
                .html(
                    '<i class="fa fa-spinner fa-spin me-2"></i> Saving...'
                );

            try {
                const response = await fetch(submitUrl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const responseText = await response.text();
                let res;

                try {
                    res = responseText ? JSON.parse(responseText) : null;
                } catch (parseError) {
                    throw new Error(
                        `Invalid JSON returned by server: ${responseText || 'empty response'}`
                    );
                }

                if (!response.ok) {
                    const requestError = new Error(
                        res?.message || `HTTP ${response.status}`
                    );
                    requestError.httpStatus = response.status;
                    throw requestError;
                }

                if (res && res.status === 'success') {
                    const message = res.message ||
                        'Delivery and freight details saved successfully.';

                    if (typeof Swal !== 'undefined') {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Saved Successfully',
                            text: message,
                            confirmButtonColor: '#3085d6'
                        });
                    } else {
                        alert(message);
                    }

                    window.location.reload();
                    return;
                }

                showSubmitMessage(
                    'Save Failed',
                    res?.message || 'Unable to save delivery and freight details.',
                    'error'
                );
            } catch (error) {
                console.error('Delivery/Freight save error:', error);

                let errorMessage = error?.message ||
                    'Failed to communicate with the server.';

                if (error?.httpStatus === 404) {
                    errorMessage =
                        'Save URL was not found. Please check data-save-url.';
                }

                showSubmitMessage(
                    'Connection Error',
                    errorMessage,
                    'error'
                );
            } finally {
                $btn
                    .prop('disabled', false)
                    .html(originalButtonHtml);
            }
};

document.addEventListener(
    'submit',
    window.__orderDeliveryFreightSubmitHandler,
    true
);

function showSubmitMessage(title, message, icon) {
    if (typeof Swal !== 'undefined') {
        Swal.fire(title, message, icon);
    } else {
        alert(message);
    }
}

})();
</script>

