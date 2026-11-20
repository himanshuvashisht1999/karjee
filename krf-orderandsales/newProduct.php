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
  include('functions/product_functions.php');
  include('functions/seed_functions.php');
  include('functions/tray_functions.php');
  include('functions/vendor_functions.php');
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
  include('functions/product_functions.php');
  include('functions/seed_functions.php');
  include('functions/tray_functions.php');
  include('functions/vendor_functions.php');
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

// get provisional order details
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

$editProductData = null;
if (isset($_GET['productid'])) {
  $pid = $_GET['productid'];
  $pData = getProductData($krfconn, $projectid, 'ProductID', $pid);
  if (!empty($pData)) {
    $editProductData = $pData[0];
  }
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
  :root {
    --prod-primary: #0d9488;
    --prod-primary-dark: #0f766e;
    --prod-primary-light: #14b8a6;
    --prod-primary-subtle: #f0fdfa;
    --prod-accent: #f59e0b;
    --prod-card-bg: #ffffff;
    --prod-border: #e2e8f0;
    --prod-text: #0f172a;
    --prod-text-muted: #64748b;
    --prod-radius-lg: 16px;
    --prod-radius-md: 10px;
    --prod-radius-sm: 6px;
  }

  /* Main Container */
  .product-page-container {
    padding: 1rem 0.75rem;
    max-width: 1200px;
    margin: 0 auto;
    box-sizing: border-box;
  }

  @media (min-width: 768px) {
    .product-page-container {
      padding: 1.5rem 1.25rem;
    }
  }

  /* Main Card */
  .product-main-card {
    border: none;
    border-radius: var(--prod-radius-lg);
    background: var(--prod-card-bg);
    box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.06), 0 8px 16px -6px rgba(0, 0, 0, 0.04);
    overflow: hidden;
    margin-bottom: 2rem;
  }

  /* Header Section */
  .product-card-header {
    background: linear-gradient(135deg, #094e47 0%, #0d9488 55%, #14b8a6 100%);
    color: #ffffff;
    padding: 1.35rem 1.5rem 1.25rem;
    border-bottom: none;
  }

  .header-title-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.18);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.25);
    font-size: 1.35rem;
    color: #ffffff;
    flex-shrink: 0;
  }

  /* Stat Chips / Header Buttons - Light Background & Crisp Dark Text */
  .stat-chip {
    background: #ffffff !important;
    border: 1px solid rgba(255, 255, 255, 0.9) !important;
    color: #1e293b !important;
    border-radius: 24px;
    padding: 7px 16px;
    font-size: 0.84rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    white-space: nowrap;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    transition: all 0.2s ease;
    text-decoration: none !important;
  }

  .stat-chip i {
    font-size: 0.95rem;
    margin-right: 4px;
  }

  .stat-chip strong {
    color: #0f766e !important;
    font-weight: 700;
  }

  .stat-chip:hover {
    background: #f8fafc !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
    color: #0f172a !important;
  }

  /* Form Section Cards */
  .form-section-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 1.25rem 1.25rem 1rem;
    margin-bottom: 1.25rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    overflow: hidden;
    box-sizing: border-box;
    width: 100%;
  }

  @media (min-width: 768px) {
    .form-section-card {
      padding: 1.5rem 1.5rem 1.25rem;
    }
  }

  .form-section-header {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.86rem;
    font-weight: 700;
    color: #0f766e;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #f1f5f9;
  }

  /* Form Labels */
  .form-label-styled {
    font-size: 0.82rem;
    font-weight: 700;
    color: #334155;
    margin-bottom: 0.35rem;
    display: flex;
    align-items: center;
    gap: 4px;
    justify-content: flex-start;
  }

  /* Standalone Form Controls 100% Width */
  .new-prod-form .form-group {
    width: 100% !important;
    margin-bottom: 1rem;
    box-sizing: border-box !important;
  }

  .new-prod-form .form-control,
  .new-prod-form .form-select,
  .new-prod-form select,
  .new-prod-form textarea {
    width: 100% !important;
    max-width: 100% !important;
    box-sizing: border-box !important;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    font-size: 0.86rem;
    padding: 8px 12px;
    background-color: #f8fafc;
    color: #0f172a;
    transition: all 0.2s ease;
  }

  .new-prod-form select,
  .new-prod-form input:not([type="checkbox"]):not([type="radio"]):not([type="file"]) {
    height: 42px;
  }

  .new-prod-form textarea {
    height: auto !important;
    min-height: 48px;
  }

  .new-prod-form .form-control:focus,
  .new-prod-form select:focus,
  .new-prod-form textarea:focus {
    background-color: #ffffff;
    border-color: #0d9488 !important;
    box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15) !important;
    outline: none;
  }

  /* Grid Spacing Containment */
  .new-prod-form .row {
    margin-left: -8px !important;
    margin-right: -8px !important;
  }

  .new-prod-form .row > [class*="col-"] {
    padding-left: 8px !important;
    padding-right: 8px !important;
    min-width: 0 !important;
    box-sizing: border-box !important;
  }

  /* Input Groups Containment */
  .new-prod-form .input-group {
    width: 100% !important;
    max-width: 100% !important;
    display: flex !important;
    flex-wrap: nowrap !important;
    align-items: stretch !important;
    box-sizing: border-box !important;
    position: relative !important;
  }

  .new-prod-form .input-group > input,
  .new-prod-form .input-group > .form-control {
    flex: 1 1 auto !important;
    width: 1% !important;
    min-width: 0 !important;
    height: 42px !important;
    padding: 8px 12px !important;
    border-radius: 0 !important;
    border: 1px solid #cbd5e1 !important;
    background-color: #f8fafc !important;
    color: #0f172a !important;
    box-sizing: border-box !important;
  }

  .new-prod-form .input-group > .input-group-text:first-child {
    border-top-left-radius: 8px !important;
    border-bottom-left-radius: 8px !important;
    border-top-right-radius: 0 !important;
    border-bottom-right-radius: 0 !important;
    border-right: none !important;
  }

  .new-prod-form .input-group > input:first-child {
    border-top-left-radius: 8px !important;
    border-bottom-left-radius: 8px !important;
  }

  .new-prod-form .input-group > .input-group-text:last-child,
  .new-prod-form .input-group > button:last-child {
    border-top-right-radius: 8px !important;
    border-bottom-right-radius: 8px !important;
    border-top-left-radius: 0 !important;
    border-bottom-left-radius: 0 !important;
    border-left: none !important;
  }

  .new-prod-form .input-group > input:last-child {
    border-top-right-radius: 8px !important;
    border-bottom-right-radius: 8px !important;
  }

  .new-prod-form .input-group-text {
    height: 42px !important;
    background-color: #f1f5f9 !important;
    border: 1px solid #cbd5e1 !important;
    color: #475569 !important;
    font-size: 0.82rem !important;
    font-weight: 700 !important;
    padding: 0 12px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    white-space: nowrap !important;
    box-sizing: border-box !important;
  }

  /* Grafted Radio Pill Group */
  .radio-pill-group {
    display: inline-flex;
    align-items: center;
    background: #f1f5f9;
    padding: 3px;
    border-radius: 10px;
    border: 1px solid #cbd5e1;
    width: 100%;
    max-width: 320px;
  }

  .radio-pill-item {
    flex: 1 1 50%;
    margin-bottom: 0 !important;
  }

  .radio-pill-item input[type="radio"] {
    display: none;
  }

  .radio-pill-label {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 7px 12px;
    border-radius: 8px;
    font-size: 0.82rem;
    font-weight: 700;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s ease;
    margin: 0;
    user-select: none;
    text-align: center;
  }

  .radio-pill-item input[type="radio"]:checked + .radio-pill-label {
    background: #0d9488;
    color: #ffffff;
    box-shadow: 0 2px 6px rgba(13, 148, 136, 0.3);
  }

  /* Quick Action Icon Buttons */
  .btn-xs {
    padding: 2px 8px;
    font-size: 0.74rem;
    line-height: 1.4;
    border-radius: 6px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: all 0.15s ease;
  }

  .btn-outline-teal {
    color: #0d9488;
    border: 1px solid #99f6e4;
    background: #f0fdfa;
  }

  .btn-outline-teal:hover {
    background: #0d9488;
    color: #ffffff;
    border-color: #0d9488;
  }

  /* Suggested Price Presets & Compact Toggle Switch */
  .existing-specs-box {
    background: linear-gradient(135deg, #f0fdfa 0%, #f8fafc 100%);
    border: 1.5px solid #ccfbf1 !important;
    border-radius: 12px;
    padding: 12px 14px;
    width: 100% !important;
    max-width: 100% !important;
    box-sizing: border-box !important;
    overflow: hidden;
  }

  .price-switch-card {
    background: #ffffff;
    border: 1.5px solid #cbd5e1;
    border-radius: 20px;
    padding: 4px 10px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    user-select: none;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    max-width: 100%;
    box-sizing: border-box;
    margin: 2px 0;
  }

  .price-switch-card:hover {
    border-color: #0d9488;
    background: #f0fdfa;
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(13, 148, 136, 0.12);
  }

  .price-switch-card.active-switch {
    background: #ecfdf5;
    border-color: #0d9488;
    box-shadow: 0 0 0 2px rgba(13, 148, 136, 0.25);
  }

  /* Compact Pure CSS Toggle Switch */
  .spec-toggle-wrap {
    position: relative;
    display: inline-flex;
    align-items: center;
    width: 28px !important;
    min-width: 28px !important;
    max-width: 28px !important;
    height: 16px !important;
    flex-shrink: 0;
    margin: 0 !important;
    padding: 0 !important;
    vertical-align: middle;
  }

  .spec-toggle-wrap input.price-switch-input {
    position: absolute !important;
    opacity: 0 !important;
    width: 0 !important;
    height: 0 !important;
    margin: 0 !important;
    padding: 0 !important;
    pointer-events: none !important;
  }

  .spec-toggle-slider {
    position: absolute !important;
    cursor: pointer !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    background-color: #cbd5e1 !important;
    border: 1px solid #94a3b8 !important;
    border-radius: 16px !important;
    transition: all 0.2s ease !important;
    margin: 0 !important;
    padding: 0 !important;
    box-sizing: border-box !important;
  }

  .spec-toggle-slider::before {
    position: absolute !important;
    content: "" !important;
    height: 10px !important;
    width: 10px !important;
    left: 2px !important;
    top: 2px !important;
    background-color: #ffffff !important;
    border-radius: 50% !important;
    transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.25) !important;
    box-sizing: border-box !important;
  }

  .spec-toggle-wrap input.price-switch-input:checked + .spec-toggle-slider {
    background-color: #0d9488 !important;
    border-color: #0f766e !important;
  }

  .spec-toggle-wrap input.price-switch-input:checked + .spec-toggle-slider::before {
    transform: translateX(12px) !important;
  }

  .price-card-content {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    margin: 0 !important;
    padding: 0 !important;
    cursor: pointer;
    font-size: 0.78rem;
    font-weight: 700;
    color: #1e293b;
    flex-wrap: wrap;
  }

  .price-pill {
    background: #ffffff;
    border: 1px solid #99f6e4;
    color: #0f766e;
    padding: 2px 7px;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 700;
  }

  /* Buttons */
  .btn-primary-gradient {
    background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%) !important;
    color: #ffffff !important;
    border: none !important;
    box-shadow: 0 4px 12px rgba(13, 148, 136, 0.3);
    transition: all 0.2s ease;
    border-radius: 8px;
  }

  .btn-primary-gradient:hover {
    background: linear-gradient(135deg, #0f766e 0%, #115e59 100%) !important;
    box-shadow: 0 6px 16px rgba(13, 148, 136, 0.4);
    transform: translateY(-1px);
    color: #ffffff !important;
  }

  /* Select2 Full Width Override */
  .select2-container {
    width: 100% !important;
  }

  .select2-container--default .select2-selection--single {
    height: 42px !important;
    border-radius: 8px !important;
    border: 1px solid #cbd5e1 !important;
    padding: 6px 12px !important;
    background-color: #f8fafc !important;
  }

  .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 28px !important;
    color: #0f172a !important;
    font-size: 0.86rem !important;
  }

  .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 40px !important;
  }

  /* Modals Modern Styling */
  .modal-content-styled {
    border: none;
    border-radius: 16px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    overflow: hidden;
    background: #ffffff;
  }

  .modal-header-styled {
    background: linear-gradient(135deg, #094e47 0%, #0d9488 60%, #14b8a6 100%);
    color: #ffffff;
    padding: 1.15rem 1.5rem;
    border-bottom: none;
  }

  @media (max-width: 767.98px) {
    .header-top-bar {
      flex-direction: column;
      align-items: flex-start !important;
      gap: 12px;
    }
    .header-actions-group {
      width: 100%;
      display: flex;
      justify-content: flex-start;
      gap: 8px;
      flex-wrap: wrap;
    }
  }
</style>

<div class="product-page-container">
  <div class="card product-main-card">
    
    <!-- Card Header -->
    <div class="card-header product-card-header">
      <div class="d-flex justify-content-between align-items-center header-top-bar w-100">
        <div class="d-flex align-items-center gap-3">
          <div class="header-title-badge">
            <i class="fa fa-boxes-stacked"></i>
          </div>
          <div>
            <h4 class="mb-0 text-white fw-bold d-flex align-items-center gap-2">
              <?php echo $editProductData ? 'Edit Product Master' : 'Add New Product Master'; ?>
            </h4>
            <p class="mb-0 text-white-50 fs-13 mt-1">
              Configure crop category, variety, grafting classification, SKU code, and indicative pricing.
            </p>
          </div>
        </div>

        <!-- Header Actions -->
        <div class="header-actions-group d-flex align-items-center gap-2">
          <button class="stat-chip" type="button" onclick="location.reload();" title="Refresh Page">
            <i class="fa fa-sync-alt text-teal"></i> <span>Refresh</span>
          </button>
          <a href="productmaster_review.php?k=<?php echo $key ?? ''; ?>&mdid=<?php echo $mdid ?? ''; ?>"
             class="stat-chip" title="View Product Directory">
            <i class="fa fa-list-check text-warning"></i> <span>Product Directory</span>
          </a>
        </div>
      </div>
    </div>

    <!-- Card Body Form -->
    <div class="card-body p-3 p-md-4">
      <form id="newProductForm" class="new-prod-form" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="ProductID" id="ProductID"
               value="<?php echo $editProductData ? htmlspecialchars($editProductData['ProductID']) : ''; ?>">

        <!-- SECTION 1: CROP CLASSIFICATION & GRAFTING -->
        <div class="form-section-card">
          <div class="form-section-header">
            <i class="fa fa-layer-group"></i> 1. Crop Classification & Grafting
          </div>

          <div class="row g-3">
            <!-- Crop Category -->
            <div class="col-12 col-md-4">
              <div class="form-group mb-0">
                <label for="ProductCategory" class="form-label-styled">
                  <i class="fa fa-folder text-teal me-1"></i> Category of Crop (Vegetable) <span class="text-danger">*</span>
                </label>
                <select class="form-control rmproductnameonchange" id="ProductCategory" name="ProductCategory" required>
                  <option value="">Loading categories...</option>
                </select>
              </div>
            </div>

            <!-- Name of the Crop / SubCategory -->
            <div class="col-12 col-md-5">
              <div class="form-group mb-0">
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <label for="ProductSubCategory" class="form-label-styled mb-0">
                    <i class="fa fa-carrot text-teal me-1"></i> Name of the Crop (Vegetable) <span class="text-danger">*</span>
                  </label>
                  <div class="d-flex align-items-center gap-1">
                    <button type="button" class="btn btn-xs btn-outline-teal addnewsubcategory" title="Add New SubCategory Type">
                      <i class="fa fa-plus"></i> New Crop
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-secondary" title="Refresh Crops"
                            onclick="GetProductSubCategoryFrommaster_option('ProductSubCategory', 'CategoryID', $('#ProductCategory').val(),'');">
                      <i class="fa fa-sync-alt"></i>
                    </button>
                  </div>
                </div>
                <select class="form-control select2 rmproductnameonchange" id="ProductSubCategory" name="ProductSubCategory" required>
                  <option value="">Select Category first</option>
                </select>
              </div>
            </div>

            <!-- Grafted Radio Selector -->
            <div class="col-12 col-md-3">
              <div class="form-group mb-0">
                <label class="form-label-styled">
                  <i class="fa fa-code-branch text-teal me-1"></i> Is Vegetable Grafted? <span class="text-danger">*</span>
                </label>
                <div class="radio-pill-group">
                  <label class="radio-pill-item">
                    <input type="radio" name="isgrafted" value="Grafted" checked class="rmproductnameonchange">
                    <span class="radio-pill-label">
                      <i class="fa fa-code-branch me-1"></i> YES
                    </span>
                  </label>
                  <label class="radio-pill-item">
                    <input type="radio" name="isgrafted" value="NonGrafted" class="rmproductnameonchange">
                    <span class="radio-pill-label">
                      <i class="fa fa-seedling me-1"></i> NO
                    </span>
                  </label>
                </div>
              </div>
            </div>

            <!-- Scientific / Botanical Name (Collapsible / Clean) -->
            <div class="col-12 d-none">
              <div class="form-group mb-0">
                <label for="ScientificName" class="form-label-styled">Scientific / Botanical Name</label>
                <input type="text" class="form-control" id="ScientificName" name="ScientificName" placeholder="e.g. Citrullus lanatus" />
              </div>
            </div>
          </div>
        </div>

        <!-- SECTION 2: SEED VARIETIES -->
        <div class="form-section-card">
          <div class="form-section-header">
            <i class="fa fa-seedling"></i> 2. Seed Varieties
          </div>

          <div class="row g-3">
            <!-- Scion Seed Dropdown -->
            <div class="col-12 col-md-6" id="scionseeddiv">
              <div class="form-group mb-0">
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <label for="scionseedname" class="form-label-styled mb-0" id="scionseedlabel">
                    <i class="fa fa-cut text-teal me-1"></i> Scion Seed <span class="text-danger">*</span>
                  </label>
                  <div class="d-flex align-items-center gap-1">
                    <button type="button" class="btn btn-xs btn-outline-teal creatseedmaster" title="Create New Seed">
                      <i class="fa fa-plus"></i> New Seed
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-secondary refressseedmaster" title="Refresh Seeds List">
                      <i class="fa fa-sync-alt"></i>
                    </button>
                  </div>
                </div>
                <select class="form-control select2 rmproductnameonchange" id="scionseedname" name="scionseedname">
                  <option value="">Select Scion Seed</option>
                </select>
              </div>
            </div>

            <!-- Root Seed Dropdown (Dynamically labeled "Scion Seed" when NonGrafted) -->
            <div class="col-12 col-md-6" id="rootseeddiv">
              <div class="form-group mb-0">
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <label for="rootseedname" class="form-label-styled mb-0" id="rootseedlabel">
                    <i class="fa fa-seedling text-teal me-1"></i> Root Seed <span class="text-danger">*</span>
                  </label>
                  <div class="d-flex align-items-center gap-1 non-grafted-actions" style="display:none;">
                    <button type="button" class="btn btn-xs btn-outline-teal creatseedmaster" title="Create New Seed">
                      <i class="fa fa-plus"></i> New Seed
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-secondary refressseedmaster" title="Refresh Seeds List">
                      <i class="fa fa-sync-alt"></i>
                    </button>
                  </div>
                </div>
                <select class="form-control select2 rmproductnameonchange" id="rootseedname" name="rootseedname" required>
                  <option value="">Select Root Seed</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- SECTION 3: PRODUCT NAME & UNIQUE SKU -->
        <div class="form-section-card">
          <div class="form-section-header">
            <i class="fa fa-barcode"></i> 3. Product Name & Unique SKU
          </div>

          <div class="row g-3">
            <!-- Suggested Name of Product -->
            <div class="col-12 col-lg-8">
              <div class="form-group mb-0">
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <label for="productname" class="form-label-styled mb-0">
                    <i class="fa fa-tag text-teal me-1"></i> Suggested Name of Product <span class="text-danger">*</span>
                  </label>
                  <span class="badge bg-light text-muted border fs-11" id="nameAutoHint">Auto-Generated (Editable)</span>
                </div>
                <textarea class="form-control" id="productname" name="productname" rows="2"
                          placeholder="Select category, crop and seeds to auto-generate suggested name..." required></textarea>
                <small class="text-muted fs-11 mt-1 d-block">
                  <i class="fa fa-info-circle text-info"></i> For Non-Grafted crops, name will not contain "Nongrafted-" prefix.
                </small>
              </div>
            </div>

            <!-- Suggested SKU -->
            <div class="col-12 col-lg-4">
              <div class="form-group mb-0">
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <label for="productsuggestedsku" class="form-label-styled mb-0">
                    <i class="fa fa-fingerprint text-teal me-1"></i> Suggested SKU Code <span class="text-danger">*</span>
                  </label>
                  <span class="badge bg-light text-teal border fs-11">Unique Code</span>
                </div>
                <div class="input-group">
                  <span class="input-group-text"><i class="fa fa-barcode"></i></span>
                  <input type="text" class="form-control font-monospace fw-bold text-teal" id="productsuggestedsku"
                         name="productsuggestedsku" placeholder="Auto-generated SKU" readonly required />
                  <button type="button" class="btn btn-light border px-2" id="copySkuBtn" title="Copy SKU to clipboard">
                    <i class="fa fa-copy text-teal"></i>
                  </button>
                </div>
                <small class="text-muted fs-11 mt-1 d-block">Format: [SeedSKU]-[SequentialNo]-[GRAFTED/NONGRAFTED]</small>
              </div>
            </div>
          </div>
        </div>

        <!-- SECTION 4: INDICATIVE PRICING & PRESETS -->
        <div class="form-section-card">
          <div class="form-section-header">
            <i class="fa fa-indian-rupee-sign"></i> 4. Indicative Pricing & Presets
          </div>

          <!-- Suggested Price Switch Options Box -->
          <div id="existingPriceContainer" style="display:none;" class="mb-3">
            <div class="existing-specs-box">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="fs-12 fw-bold text-teal d-flex align-items-center gap-1">
                  <i class="fa fa-tags"></i> Suggested Price Presets for this Crop:
                </span>
                <span class="badge bg-light text-dark border fs-11" id="priceCountBadge">0 presets</span>
              </div>
              <div class="text-muted fs-12 mb-2">
                <i class="fa fa-info-circle text-info me-1"></i> Switch <strong>ON</strong> to auto-fill Indicative Price (only one preset can be selected at a time):
              </div>
              <div class="d-flex flex-wrap gap-2" id="existingPriceList">
                <!-- Dynamically populated switch buttons -->
              </div>
            </div>
          </div>

          <div class="row g-3">
            <!-- Indicative Price Input -->
            <div class="col-12 col-md-6 col-lg-4">
              <div class="form-group mb-0">
                <label for="suggestedprice" class="form-label-styled">
                  <i class="fa fa-money-bill-wave text-teal me-1"></i> Indicative Price of Product <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                  <span class="input-group-text"><i class="fa fa-indian-rupee-sign text-teal"></i></span>
                  <input type="number" step="0.01" min="0" class="form-control fw-bold" id="suggestedprice"
                         name="suggestedprice" placeholder="e.g. 10.00" required />
                  <span class="input-group-text text-muted">per plant</span>
                </div>
                <small class="text-muted fs-11 mt-1 d-block">Select a preset switch above or type manually.</small>
              </div>
            </div>
          </div>
        </div>

        <!-- Hidden Tracking Inputs -->
        <input type="hidden" name="companyid" value="<?php echo htmlspecialchars($companyid ?? ''); ?>">
        <input type="hidden" name="subcompanyid" value="<?php echo htmlspecialchars($subcompanyid ?? ''); ?>">
        <input type="hidden" name="projectid" value="<?php echo htmlspecialchars($projectid ?? ''); ?>">
        <input type="hidden" name="loginid" value="<?php echo htmlspecialchars($loginid ?? ''); ?>">

        <!-- Submit & Actions Bar -->
        <div class="d-flex align-items-center justify-content-center gap-3 pt-2">
          <button type="submit" class="btn btn-primary-gradient px-4 py-2 fw-bold">
            <i class="fa fa-save me-2"></i> Save Product Details
          </button>
          <a href="productmaster_review.php?k=<?php echo $key ?? ''; ?>&mdid=<?php echo $mdid ?? ''; ?>"
             class="btn btn-light border px-4 py-2">
            <i class="fa fa-times me-1"></i> Cancel
          </a>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: New SubCategory / Crop Type -->
<div class="modal fade" id="NewSubcategoryModal" style="z-index: 1060;" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable" style="max-width: 600px;">
    <div class="modal-content modal-content-styled">
      <div class="modal-header modal-header-styled">
        <h5 class="modal-title fw-bold text-white d-flex align-items-center gap-2">
          <i class="fa fa-carrot"></i> <span id="NewSubcategoryModalheader">New Crop Sub-Category</span>
        </h5>
        <button type="button" class="btn-close btn-close-white close text-white"
                onclick="$('#NewSubcategoryModal').modal('hide')" data-bs-dismiss="modal" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body p-4" id="ContentNewSubcategoryModal">
        <form id="SubCategoryForm" method="POST" action="#">
          <!-- Category -->
          <div class="mb-3">
            <label for="nsbcCategoryID" class="form-label-styled">Category of the Crop (Vegetable) <span class="text-danger">*</span></label>
            <select class="form-select form-control" id="nsbcCategoryID" name="nsbcCategoryID" required>
              <option value="" selected disabled>Select Category</option>
            </select>
          </div>

          <!-- Crop Name -->
          <div class="mb-3">
            <label for="SubCategoryName" class="form-label-styled">Name of the Crop (Vegetable) <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="SubCategoryName" name="SubCategoryName" placeholder="e.g. WATERMELON, TOMATO..." required>
          </div>

          <!-- Description -->
          <div class="mb-3">
            <label for="DescriptionSubCategory" class="form-label-styled">Description / Remarks</label>
            <textarea class="form-control" id="DescriptionSubCategory" name="DescriptionSubCategory" rows="3" placeholder="Optional crop notes..."></textarea>
          </div>

          <!-- Submit Button -->
          <div class="text-center pt-2">
            <input type="hidden" name="companyid" value="<?php echo htmlspecialchars($companyid ?? ''); ?>" />
            <input type="hidden" name="subcompanyid" value="<?php echo htmlspecialchars($subcompanyid ?? ''); ?>" />
            <input type="hidden" name="projectid" value="<?php echo htmlspecialchars($projectid ?? ''); ?>" />
            <input type="hidden" name="loginid" value="<?php echo htmlspecialchars($loginid ?? ''); ?>" />
            <input type="hidden" id="SubCategoryID" name="SubCategoryID" value="" />
            <button type="submit" class="btn btn-primary-gradient px-4 py-2">
              <i class="fa fa-save me-1"></i> Save Sub-Category Details
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Create Seed Master -->
<div class="modal fade" id="pdrotherModal" tabindex="-1" role="dialog" style="z-index: 1065;" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="max-width: 95%;">
    <div class="modal-content modal-content-styled">
      <div class="modal-header modal-header-styled">
        <h5 class="modal-title fw-bold text-white d-flex align-items-center gap-2">
          <i class="fa fa-seedling"></i> <span id="pdrotherModalheader">New Seed Details</span>
        </h5>
        <button type="button" class="btn-close btn-close-white close text-white" onclick="$('#pdrotherModal').modal('hide')" data-bs-dismiss="modal" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body p-3 p-md-4" id="pdrotherModalContent">
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
  $(document).ready(function () {
    const editData = <?php echo json_encode($editProductData); ?>;

    // 1. Initialize Grafted Radio Button & Layout Visibility
    let initialGrafted = (editData && editData.IsGrafted) ? editData.IsGrafted : "Grafted";
    $(`input[name='isgrafted'][value='${initialGrafted}']`).prop('checked', true);
    handleGraftedChange(initialGrafted);

    // 2. Pre-fill edit mode data
    if (editData) {
      $("#ProductID").val(editData.ProductID);
      $("#productname").val(editData.ProductName);
      $("#productsuggestedsku").val(editData.sku);
      $("#suggestedprice").val(editData.PriceOfProduct);
      if (editData.ScientificName) $("#ScientificName").val(editData.ScientificName);
    }

    // 3. Dropdown Initialization Chain: Category -> SubCategory -> Seeds -> Price Presets
    let initialCatVal = (editData && editData.CategoryId) ? editData.CategoryId : "VEG10";
    GetProductCategoryFrommaster_option("ProductCategory", initialCatVal);

    // --- Dynamic Event Handlers ---

    // Category Change
    $(document).on("change", "#ProductCategory", function () {
      let selectedCat = $(this).val();
      let subCategoryPreselect = "";

      if (editData && editData.CategoryId == selectedCat) {
        subCategoryPreselect = editData.ProductTypeID || "";
      }

      GetProductSubCategoryFrommaster_option('ProductSubCategory', "CategoryID", selectedCat, subCategoryPreselect);
      updateProductName();
    });

    // SubCategory (Crop) Change
    $(document).on("change", "#ProductSubCategory", function () {
      let selectedSubCat = $(this).val();
      let selectedCat = $("#ProductCategory").val();
      let isGrafted = $("input[name='isgrafted']:checked").val() || "Grafted";

      let rootSeedPreselect = "";
      let scionSeedPreselect = "";

      if (editData && editData.ProductTypeID == selectedSubCat) {
        rootSeedPreselect = editData.SeedId || "";
        scionSeedPreselect = editData.ScionSeedId || "";
      }

      loadSeedDropdowns(selectedCat, selectedSubCat, isGrafted, rootSeedPreselect, scionSeedPreselect);
      loadExistingProductPrices(selectedCat, selectedSubCat, isGrafted);
      updateProductName();
    });

    // Grafted Radio Change
    $(document).on("change", "input[name='isgrafted']", function () {
      let val = $(this).val();
      handleGraftedChange(val);

      let cat = $("#ProductCategory").val();
      let subCat = $("#ProductSubCategory").val();

      let rootVal = "";
      let scionVal = "";
      if (editData && editData.IsGrafted === val) {
        rootVal = editData.SeedId || "";
        scionVal = editData.ScionSeedId || "";
      }

      loadSeedDropdowns(cat, subCat, val, rootVal, scionVal);
      loadExistingProductPrices(cat, subCat, val);
      refreshProductSKU();
      updateProductName();
    });

    // Seed Selection Changes
    $(document).on("change", "#rootseedname, #scionseedname", function () {
      refreshProductSKU();
      updateProductName();
    });

    // Helper: Toggle UI for Grafted vs Non-Grafted
    function handleGraftedChange(isGrafted) {
      if (isGrafted === 'Grafted') {
        $("#rootseeddiv").removeClass('col-12').addClass('col-md-6');
        $("#scionseeddiv").removeClass('col-12').addClass('col-md-6').show();
        $("#rootseedlabel").html('<i class="fa fa-seedling text-teal me-1"></i> Root Seed <span class="text-danger">*</span>');
        $("#scionseedlabel").html('<i class="fa fa-cut text-teal me-1"></i> Scion Seed <span class="text-danger">*</span>');
        $(".non-grafted-actions").hide();
        $("#scionseedname").prop('required', true);
      } else {
        // Non-Grafted: Root Seed dropdown is displayed full-width with label "Scion Seed"
        $("#rootseeddiv").removeClass('col-md-6').addClass('col-12');
        $("#scionseeddiv").hide();
        $("#rootseedlabel").html('<i class="fa fa-cut text-teal me-1"></i> Scion Seed <span class="text-danger">*</span>');
        $(".non-grafted-actions").show();
        $("#scionseedname").prop('required', false);
      }
    }

    // Helper: Load Seed Dropdowns based on crop and grafting
    function loadSeedDropdowns(catId, subCatId, isGrafted, rootVal, scionVal) {
      if (!catId || !subCatId) return;

      let combinedId = catId + '|' + subCatId;

      if (isGrafted === "Grafted") {
        // Grafted: Scion seed is from crop, Root seed is rootstock (RTSK11)
        GetSeedDataOnlyOption('scionseedname', 'categoryidwithsubcategory', combinedId, scionVal);
        GetSeedDataOnlyOption('rootseedname', 'categoryid', 'RTSK11', rootVal);
      } else {
        // Non-Grafted: Only one seed dropdown (rootseedname, labeled Scion Seed) filtered by crop
        GetSeedDataOnlyOption('rootseedname', 'categoryidwithsubcategory', combinedId, rootVal);
      }
    }

    // Helper: Load Existing Product Prices as Toggle Switch Presets
    function loadExistingProductPrices(catId, subCatId, isGrafted) {
      if (!subCatId) {
        $("#existingPriceContainer").slideUp(150);
        $("#existingPriceList").empty();
        return;
      }

      var routurl = $("body").data("routurl") || "<?php echo $prefix; ?>";
      var projectid = $("body").data("projectid") || "<?php echo $projectid; ?>";

      $.ajax({
        type: "POST",
        url: `${routurl}krf-orderandsales/modal/product_modal.php?action=GetExistingProductPrices`,
        data: {
          projectid: projectid,
          CategoryID: catId,
          ProductTypeID: subCatId,
          IsGrafted: isGrafted
        },
        dataType: "JSON",
        success: function (res) {
          if (Array.isArray(res) && res.length > 0) {
            var html = "";
            res.forEach(function (item, idx) {
              var priceFormatted = "₹ " + parseFloat(item.price).toFixed(2);
              var usageText = item.usage_count ? `<small class="text-muted ms-1">(${item.usage_count} product${item.usage_count > 1 ? 's' : ''})</small>` : "";

              html += `
                <div class="price-switch-card" id="price_card_${idx}">
                  <div class="spec-toggle-wrap">
                    <input type="checkbox" class="price-switch-input" id="price_sw_${idx}"
                           data-price="${item.price}">
                    <label class="spec-toggle-slider" for="price_sw_${idx}" title="Toggle Indicative Price"></label>
                  </div>
                  <label class="price-card-content" for="price_sw_${idx}">
                    <span class="price-pill"><i class="fa fa-tag me-1 text-teal"></i>${priceFormatted}</span>
                    ${usageText}
                  </label>
                </div>
              `;
            });

            $("#existingPriceList").html(html);
            $("#priceCountBadge").text(res.length + (res.length > 1 ? " presets found" : " preset found"));
            $("#existingPriceContainer").slideDown(200);

            // Auto-check if current input matches any preset
            checkMatchingPriceSwitch();
          } else {
            $("#existingPriceContainer").slideUp(150);
            $("#existingPriceList").empty();
          }
        },
        error: function () {
          $("#existingPriceContainer").hide();
        }
      });
    }

    // Helper: Check switch matching current price
    function checkMatchingPriceSwitch() {
      var curPrice = parseFloat($("#suggestedprice").val() || 0);
      var matched = false;

      $(".price-switch-input").each(function () {
        var itemPrice = parseFloat($(this).data("price") || 0);
        var $card = $(this).closest(".price-switch-card");

        if (!matched && curPrice > 0 && Math.abs(curPrice - itemPrice) < 0.001) {
          $(this).prop("checked", true);
          $card.addClass("active-switch");
          matched = true;
        } else {
          $(this).prop("checked", false);
          $card.removeClass("active-switch");
        }
      });
    }

    // Switch Click Event: Radio-style single selection that auto-fills #suggestedprice
    $(document).on("change", ".price-switch-input", function () {
      var $this = $(this);
      var isChecked = $this.is(":checked");
      var priceVal = $this.data("price");

      $(".price-switch-input").not($this).prop("checked", false)
        .closest(".price-switch-card").removeClass("active-switch");

      if (isChecked) {
        $this.closest(".price-switch-card").addClass("active-switch");
        $("#suggestedprice").val(priceVal).trigger("input");
      } else {
        $this.closest(".price-switch-card").removeClass("active-switch");
      }
    });

    // When user types in suggestedprice, update active switch highlight
    $(document).on("input change", "#suggestedprice", function () {
      checkMatchingPriceSwitch();
    });

    // Refresh SKU Code
    function refreshProductSKU() {
      var isGrafted = $("input[name='isgrafted']:checked").val() || 'Grafted';
      var seedId = "";
      if (isGrafted === "Grafted") {
        seedId = $("#scionseedname").val() || $("#rootseedname").val();
      } else {
        seedId = $("#rootseedname").val();
      }
      if (seedId) {
        GetSuggestedProductSKU(seedId, isGrafted, 'productsuggestedsku');
      }
    }

    // Copy SKU to clipboard
    $("#copySkuBtn").on("click", function () {
      var sku = $("#productsuggestedsku").val();
      if (!sku) {
        Swal.fire({ icon: 'info', title: 'No SKU', text: 'SKU is not generated yet.', timer: 1500, showConfirmButton: false });
        return;
      }
      navigator.clipboard.writeText(sku).then(function () {
        Swal.fire({ icon: 'success', title: 'Copied!', text: 'SKU copied to clipboard: ' + sku, timer: 1500, showConfirmButton: false });
      });
    });

    // Refresh Seeds Button
    $(document).on("click", ".refressseedmaster", function () {
      let cat = $("#ProductCategory").val();
      let sub = $("#ProductSubCategory").val();
      let isGrafted = $("input[name='isgrafted']:checked").val() || 'Grafted';
      loadSeedDropdowns(cat, sub, isGrafted, "", "");
    });

    // Subcategory Modal Logic
    $(".addnewsubcategory").on("click", function () {
      var subcatelbl = $("#ProductCategory").val();
      var subcatelbltxt = $("#ProductCategory option:selected").text();
      $("#NewSubcategoryModalheader").html("New Type of " + subcatelbltxt);
      GetProductCategoryFrommaster_option("nsbcCategoryID", subcatelbl);
      $("#NewSubcategoryModal").modal("show");
    });

    $("#SubCategoryForm").on("submit", function (e) {
      e.preventDefault();
      const $form = $(this);
      Swal.fire({
        title: "Please wait...",
        text: "Saving sub-category details",
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => { Swal.showLoading(); }
      });
      $.ajax({
        url: "modal/product_modal.php?action=saveupdateSubCategorydetails",
        type: "POST",
        data: $form.serialize(),
        dataType: "json",
        success: function (response) {
          Swal.close();
          if (response.status === "success" || response.status === "Success") {
            Swal.fire({
              icon: "success",
              title: "Saved Successfully",
              text: response.message,
              timer: 1500,
              showConfirmButton: false
            });
            $("#NewSubcategoryModal").modal("hide");
            let cat = $("#ProductCategory").val();
            GetProductSubCategoryFrommaster_option('ProductSubCategory', "CategoryID", cat, '');
          } else {
            Swal.fire({ icon: "error", title: "Error", text: response.message });
          }
        },
        error: function () {
          Swal.close();
          Swal.fire({ icon: "error", title: "Server Error", text: "Something went wrong." });
        }
      });
    });

    // Main Form Submit Handler
    $("#newProductForm").on("submit", function (e) {
      e.preventDefault();
      if ($("input[name='isgrafted']:checked").val() === "NonGrafted") {
        $("#scionseedname").prop("required", false);
      }

      Swal.fire({
        title: "Please wait...",
        text: "Saving product details",
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => { Swal.showLoading(); }
      });

      $.ajax({
        url: "modal/product_modal.php?action=saveupdateProductdetails",
        type: "POST",
        data: $(this).serialize(),
        dataType: "json",
        success: function (response) {
          Swal.close();
          if (response.status === "success") {
            Swal.fire({
              icon: "success",
              title: "Saved Successfully",
              text: response.message,
              confirmButtonText: "Go to Directory",
              showCancelButton: true,
              cancelButtonText: "Add Another Product"
            }).then((result) => {
              if (result.isConfirmed) {
                window.location.href = "productmaster_review.php?k=<?php echo $key ?? ''; ?>&mdid=<?php echo $mdid ?? ''; ?>";
              } else {
                location.reload();
              }
            });
          } else {
            Swal.fire({ icon: "error", title: "Error", text: response.message });
          }
        },
        error: function () {
          Swal.close();
          Swal.fire({ icon: "error", title: "Server Error", text: "Something went wrong. Please try again." });
        }
      });
    });

  });

  // Create Seed Master Popup
  $(document).on("click", ".creatseedmaster", function () {
    let $body = $("body");
    let routurl = $body.data("routurl") || "<?php echo $prefix; ?>";
    let key = $body.data("key") || "<?php echo $key ?? ''; ?>";
    let categoryid = $("#ProductCategory").val();
    let mdid = "<?php echo $mdid ?? ''; ?>";
    $("#pdrotherModalContent").empty();
    $("#pdrotherModalContent").load(routurl + "krf-orderandsales/addNewSeed.php?" +
      $.param({ k: key, mdid: mdid, pctid: categoryid, dz: 11 }),
      function (response, status, xhr) {
        if (status === "error") {
          Swal.fire('Error', "Error loading content: " + xhr.status + " " + xhr.statusText, 'error');
          return;
        }
        $("#pdrotherModalheader").html("New Seed Details for Create Master");
        $("#pdrotherModal").modal("show");
      }
    );
  });

  // Fix for nested modals freezing
  $(document).on('hidden.bs.modal', function () {
    if ($('.modal:visible').length) {
      $('body').addClass('modal-open');
    }
  });

  // Auto-Update Product Name:
  // Non-Grafted crops MUST NOT be prefixed with "Nongrafted-"
  // Grafted crops are prefixed with "Grafted-"
  function updateProductName() {
    var CategoryName_temm = ($("#ProductCategory option:selected").text() || '').trim();
    if (CategoryName_temm.toLowerCase().indexOf("select") > -1) CategoryName_temm = "";

    var SubCategory_temm = ($("#ProductSubCategory option:selected").text() || '').trim();
    if (SubCategory_temm.toLowerCase().indexOf("select") > -1) SubCategory_temm = "";

    var isgrafted = $("input[name='isgrafted']:checked").val() || 'Grafted';

    var rootSeedOpt = $("#rootseedname option:selected");
    var scionSeedOpt = $("#scionseedname option:selected");

    var rootVariety = (rootSeedOpt.data("varietyname") || '').trim();
    var scionVariety = (scionSeedOpt.data("varietyname") || '').trim();

    var tempname = "";

    if (isgrafted === "Grafted") {
      // Grafted mode: prefixed with 'Grafted-'
      var seedPart = "";
      if (scionVariety && rootVariety) {
        seedPart = ", " + scionVariety + " (" + rootVariety + ")";
      } else if (scionVariety) {
        seedPart = ", " + scionVariety;
      } else if (rootVariety) {
        seedPart = " (" + rootVariety + ")";
      }

      if (SubCategory_temm) {
        tempname = "Grafted-" + SubCategory_temm + seedPart;
      }
    } else {
      // Non-Grafted mode: CAN NOT be prefixed with 'Nongrafted-' or 'NonGrafted-'
      var activeVariety = rootVariety || scionVariety;
      var seedPart = "";
      if (activeVariety) {
        seedPart = ", " + activeVariety + " (" + activeVariety + ")";
      }

      if (SubCategory_temm) {
        tempname = SubCategory_temm + seedPart;
      }
    }

    if (tempname) {
      $("#productname").val(tempname);
    }
  }
</script>
