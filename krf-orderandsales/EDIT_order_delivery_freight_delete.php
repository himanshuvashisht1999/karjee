<?php 
  include('../header.php');

   $orderid = 'KF-PS-00005';//trim($_POST['orderid'] ?? '');
  $clientid ='C00006';// trim($_POST['clientid'] ?? '');
  $companyid = 19;//intval($_POST['companyid'] ?? 0);
  $subcompanyid =12;// intval($_POST['subcompanyid'] ?? 0);
  $projectid =17;// intval($_POST['projectid'] ?? 0);
  $clientname ="abc";

 
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
</style> 

<form id="orderDeliveryFreightForm">
  <input type="hidden" name="orderid" value="<?php echo htmlspecialchars($orderid); ?>">
  <input type="hidden" name="clientid" value="<?php echo htmlspecialchars($clientid); ?>">
  <input type="hidden" name="projectid" value="<?php echo intval($order['projectid'] ?? $projectid); ?>">
  <input type="hidden" name="is_provisional" value="<?php echo $isProvisional ? '1' : '0'; ?>">

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
          <small class="text-muted text-uppercase fw-bold d-block" style="font-size: 0.72rem;">Order Date</small>
          <span class="text-secondary"> </span>
        </div>
      </div>
    </div>
  </div>

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

  
    <div id="deliveryaddressdiv" class="mt-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0 text-primary"><i
            class="fa fa-map-marked-alt me-2"></i> Client Delivery Address</h6>
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
              <th style="width:60px;" class="text-center">Action</th>
              
            </tr>
          </thead>
          <tbody></tbody>
        </table>
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
    </div>
 

</form>

<?php include('../footer.php'); ?>
<script>
 
</script>

