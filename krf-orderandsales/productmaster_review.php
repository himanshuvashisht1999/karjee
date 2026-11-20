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
  include('functions/vendor_functions.php');
  include('functions/product_functions.php');
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
  include('functions/product_functions.php');
  include('functions/vendor_functions.php');
  include('functions/seed_functions.php');
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
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
  :root {
    --prod-primary: #0d9488;
    --prod-primary-dark: #0f766e;
    --prod-primary-light: #14b8a6;
    --prod-accent: #f59e0b;
    --prod-card-bg: #ffffff;
    --prod-border: #e2e8f0;
    --prod-text: #0f172a;
    --prod-text-muted: #64748b;
    --prod-radius-lg: 16px;
    --prod-radius-md: 10px;
    --prod-radius-sm: 6px;
  }

  .product-main-card {
    border: none;
    border-radius: var(--prod-radius-lg);
    box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.06), 0 8px 16px -6px rgba(0, 0, 0, 0.04);
    background: #ffffff;
    overflow: hidden;
    margin-bottom: 2rem;
  }

  .product-card-header {
    background: linear-gradient(135deg, #094e47 0%, #0d9488 55%, #14b8a6 100%);
    color: #ffffff;
    padding: 1.5rem 1.5rem 1.25rem;
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

  /* Stat Chips - Light Background & Dark Crisp Text */
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
  }

  /* Filter & Actions Bar */
  .toolbar-wrapper {
    background: rgba(255, 255, 255, 0.12);
    border-radius: var(--prod-radius-md);
    padding: 12px 14px;
    margin-top: 1rem;
    backdrop-filter: blur(6px);
    border: 1px solid rgba(255, 255, 255, 0.22);
  }

  .search-box-wrapper {
    position: relative;
    flex: 1 1 280px;
    min-width: 240px;
    max-width: 460px;
  }

  .search-box-wrapper .form-control {
    padding-left: 48px !important;
    padding-right: 36px !important;
    border-radius: 22px;
    border: 1px solid rgba(255, 255, 255, 0.4);
    background: #ffffff !important;
    color: #0f172a !important;
    font-size: 0.86rem;
    height: 40px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
    transition: all 0.2s ease;
  }

  .search-box-wrapper .form-control::placeholder {
    color: #64748b !important;
    opacity: 0.85;
  }

  .search-box-wrapper .form-control:focus {
    background: #ffffff !important;
    border-color: #ffffff;
    box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.4);
  }

  .search-box-wrapper .search-icon {
    position: absolute;
    left: 18px;
    top: 50%;
    transform: translateY(-50%);
    color: #0d9488;
    font-size: 0.95rem;
    pointer-events: none;
    z-index: 2;
  }

  .search-box-wrapper .clear-icon {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    cursor: pointer;
    display: none;
    font-size: 0.88rem;
    z-index: 2;
  }

  .search-box-wrapper .clear-icon:hover {
    color: #0f172a;
  }

  .btn-toolbar {
    height: 40px;
    border-radius: 22px;
    padding: 6px 18px;
    font-size: 0.84rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px !important;
    white-space: nowrap;
    transition: all 0.2s ease;
  }

  .btn-toolbar i {
    font-size: 0.92rem;
    margin-right: 3px;
  }

  .btn-new-product {
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%) !important;
    color: #0f172a !important;
    border: none !important;
    font-weight: 700;
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
  }

  .btn-new-product:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
    color: #000000 !important;
  }

  /* Table Section */
  .table-responsive-wrapper {
    border-radius: var(--prod-radius-md);
    overflow: auto;
    max-height: calc(100vh - 270px);
    min-height: 380px;
    border: 1px solid var(--prod-border);
    position: relative;
    background: #ffffff;
  }

  .table-responsive-wrapper::-webkit-scrollbar {
    width: 7px;
    height: 7px;
  }
  .table-responsive-wrapper::-webkit-scrollbar-track {
    background: #f1f5f9;
  }
  .table-responsive-wrapper::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
  }
  .table-responsive-wrapper::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
  }

  .custom-prod-table {
    width: 100%;
    margin-bottom: 0;
    border-collapse: separate;
    border-spacing: 0;
  }

  .custom-prod-table thead th {
    background: #f8fafc !important;
    color: #334155 !important;
    font-weight: 700;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    padding: 13px 14px;
    border-bottom: 2px solid #e2e8f0;
    position: sticky;
    top: 0;
    z-index: 20;
    box-shadow: 0 2px 4px rgba(0,0,0,0.04);
    white-space: nowrap;
  }

  .custom-prod-table tbody tr {
    transition: all 0.15s ease-in-out;
  }

  .custom-prod-table tbody tr:nth-of-type(even) {
    background-color: #fafbfc;
  }

  .custom-prod-table tbody tr:hover {
    background-color: #f0fdfa !important;
  }

  .custom-prod-table tbody td {
    padding: 12px 14px;
    font-size: 0.85rem;
    color: #334155;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
  }

  .product-sku-pill {
    font-family: 'JetBrains Mono', 'Courier New', monospace;
    font-weight: 700;
    font-size: 0.78rem;
    color: #0f766e;
    background: #f0fdfa;
    border: 1px solid #99f6e4;
    padding: 3px 8px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }

  .seed-badge-root {
    background-color: #f0fdf4;
    color: #166534;
    border: 1px solid #bbf7d0;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 0.76rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
  }

  .seed-badge-scion {
    background-color: #f0fdfa;
    color: #0f766e;
    border: 1px solid #99f6e4;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 0.76rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
  }

  .btn-action-icon {
    width: 32px;
    height: 32px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    transition: all 0.2s ease;
    border: none;
  }

  .btn-action-icon:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
  }

  .export-dropdown-menu {
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    padding: 8px 0;
  }

  .export-dropdown-menu .dropdown-item {
    font-size: 0.85rem;
    padding: 8px 16px;
    font-weight: 500;
    color: #334155;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .export-dropdown-menu .dropdown-item:hover {
    background-color: #f0fdf4;
    color: #0d9488;
  }

  @media (max-width: 991.98px) {
    .header-top-bar {
      flex-direction: column;
      align-items: flex-start !important;
      gap: 12px;
    }
    .toolbar-flex-row {
      flex-direction: column;
      align-items: stretch !important;
      gap: 12px;
    }
    .search-box-wrapper {
      width: 100%;
      max-width: 100%;
    }
    .action-btns-group {
      width: 100%;
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
    }
    .action-btns-group .btn-toolbar,
    .action-btns-group .dropdown {
      flex: 1 1 auto;
    }
    .action-btns-group .btn-toolbar {
      width: 100%;
      justify-content: center;
    }
  }

  @media (max-width: 575.98px) {
    .stat-chips-row {
      width: 100%;
      overflow-x: auto;
      padding-bottom: 4px;
    }
  }
</style>

<div class="container-fluid px-3 px-md-4 py-3">
  <div class="card product-main-card">
    
    <!-- Card Header -->
    <div class="card-header product-card-header">
      
      <!-- Top Title & Stats Row -->
      <div class="d-flex justify-content-between align-items-center header-top-bar w-100 mb-2">
        <div class="d-flex align-items-center gap-3">
          <div class="header-title-badge">
            <i class="fa fa-boxes-stacked"></i>
          </div>
          <div>
            <h4 class="mb-0 text-white fw-bold d-flex align-items-center gap-2">
              Product Master Directory
            </h4>
            <p class="mb-0 text-white-50 fs-13 mt-1">
              Manage finished production products, seed varieties, prices, and growth stages.
            </p>
          </div>
        </div>

        <!-- Quick Summary Chips -->
        <div class="d-flex align-items-center gap-2 stat-chips-row">
          <span class="stat-chip" title="Total Master Products">
            <i class="fa fa-layer-group text-warning"></i> 
            <span>Products: <strong id="showingProductCount">0</strong></span>
          </span>
          <span class="stat-chip d-none d-sm-inline-flex" title="Products with growth stages defined">
            <i class="fa fa-chart-line text-success"></i> 
            <span>With Stages: <strong id="stagesCount">0</strong></span>
          </span>
          <span class="stat-chip d-none d-md-inline-flex" title="Grafted products (Root + Scion)">
            <i class="fa fa-code-branch text-info"></i> 
            <span>Grafted: <strong id="graftedCount">0</strong></span>
          </span>
        </div>
      </div>

      <!-- Action & Search Toolbar -->
      <div class="toolbar-wrapper">
        <div class="d-flex align-items-center justify-content-between gap-2 toolbar-flex-row">
          
          <!-- Real-time Live Search Input -->
          <div class="search-box-wrapper">
            <i class="fa fa-search search-icon"></i>
            <input type="text" id="productSearchInput" class="form-control" placeholder="Search Product Name, SKU, Seed, Price..." autocomplete="off">
            <i class="fa fa-times clear-icon" id="clearSearchBtn" title="Clear Search"></i>
          </div>

          <!-- Action Buttons Group -->
          <div class="action-btns-group d-flex align-items-center gap-2">
            
            <!-- Export Options Dropdown -->
            <div class="dropdown">
              <button class="btn btn-light text-dark btn-toolbar shadow-sm dropdown-toggle" type="button" id="exportDropdownBtn" data-bs-toggle="dropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fa fa-file-export text-success"></i> Export <span class="caret"></span>
              </button>
              <div class="dropdown-menu dropdown-menu-end export-dropdown-menu shadow-lg" aria-labelledby="exportDropdownBtn">
                <a class="dropdown-item" href="javascript:void(0)" onclick="exportProductData('excel')">
                  <i class="fa fa-file-excel text-success"></i> Export to Excel (.csv)
                </a>
                <a class="dropdown-item" href="javascript:void(0)" onclick="exportProductData('csv')">
                  <i class="fa fa-file-csv text-primary"></i> Export to CSV
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="javascript:void(0)" onclick="window.print()">
                  <i class="fa fa-print text-secondary"></i> Print Page
                </a>
              </div>
            </div>

            <!-- Add New Product Button -->
            <button class="btn btn-toolbar btn-new-product shadow-sm newproductmaster">
              <i class="fa fa-plus-circle"></i> <span>New Product</span>
            </button>

            <!-- Refresh Button -->
            <button class="btn btn-toolbar shadow-sm" onclick="window.location.reload();" style="background: rgba(255, 255, 255, 0.2); color: #ffffff; width: 40px; padding: 0 !important; justify-content: center;" title="Reload Page Data">
              <i class="fa fa-sync-alt" style="margin: 0;"></i>
            </button>
          </div>

        </div>
      </div>

    </div>

    <!-- Card Body & Responsive Table -->
    <div class="card-body p-3">
      <div class="table-responsive-wrapper">
        <table class="table custom-prod-table" id="ProductionMasterlisttbl">
          <thead>
            <tr>
              <th class="text-center" style="width: 100px;">S.NO. / ACTION</th>
              <th>PRODUCT NAME & SKU</th>
              <th>SEED VARIETIES (ROOT / SCION)</th>
              <th class="text-end">SUGGESTED PRICE</th>
              <th class="text-center">GROWING DAYS & STAGES</th>
            </tr>
          </thead>
          <tbody id="productTableBody">
            <tr id="loadingRow">
              <td colspan="5" class="text-center text-muted py-5">
                <i class="fa fa-spinner fa-spin fa-2x text-teal mb-2" style="color: #0d9488;"></i>
                <p class="mb-0">Loading product master list...</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<div class="modal fade" id="ProductMasterModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document" style="max-width:98%;">
    <div class="modal-content shadow-lg border-0" style="border-radius: 16px; overflow: hidden;">
      <div class="modal-header text-white" style="background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%);">
        <h5 class="modal-title fw-bold"><i class="fa fa-boxes me-2"></i>Product Details</h5>
        <button type="button" class="btn-close btn-close-white close text-white" onclick="$('#ProductMasterModal').modal('hide')" data-bs-dismiss="modal" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body p-4" id="ProductMasterContent">
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="ProductGrowthModalView" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document" style="max-width:98%;">
    <div class="modal-content shadow-lg border-0" style="border-radius: 16px; overflow: hidden;">
      <div class="modal-header text-white" style="background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%);">
        <h5 class="modal-title fw-bold"><i class="fa fa-chart-line me-2"></i>Product Growth Stages Details</h5>
        <button type="button" class="btn-close btn-close-white close text-white" onclick="$('#ProductGrowthModalView').modal('hide')" data-bs-dismiss="modal" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body p-4" id="ProductGrowthModalViewContent">
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
  GetFullProductListFrommaster_callback('all', 'all', function (response) {
    let PtableBody = $("#productTableBody");
    PtableBody.empty();

    if (response && response.length > 0) {
      let ptbltr = "";
      let stagesCount = 0;
      let graftedCount = 0;
      for (let i = 0; i < response.length; i++) {
        if (response[i].productGrowthid != "") {
          stagesCount++;
        }
        if (response[i].RootSeedName && response[i].ScionSeedName) {
          graftedCount++;
        }
        let growthstatus = "";
        if (response[i].productGrowthid != "") {
          growthstatus = `<button class="btn btn-sm btn-info text-white fw-bold editProductgrowth px-3" data-growthid="${response[i].productGrowthid}" onclick="AddUpdateProductionGrowthhtml(${response[i].ProductId},${response[i].productGrowthid});" style="border-radius: 16px;"><i class="fa fa-clock me-1"></i> <span class="badge bg-white text-primary me-1">${response[i].fullsycledays} Days</span> Edit Stage</button>`;
        } else {
          growthstatus = `<button class="btn btn-sm btn-success text-white fw-bold addProductgrowth px-3" data-growthid="" onclick="AddUpdateProductionGrowthhtml('${response[i].ProductId}','');" style="border-radius: 16px; background-color: #059669; border-color: #059669;"><i class="fa fa-plus-circle me-1"></i> Add Growth Days</button>`;
        }

        let scionInfo = response[i].ScionSeedName ? `${response[i].ScionSeedName} - ${response[i].ScionSeedVariety}` : 'N/A';

        ptbltr += `
            <tr>
                <td class="text-center">
                    <div class="d-flex align-items-center justify-content-center gap-1">
                      <span class="text-muted font-monospace me-1">${i + 1}.</span>
                      <button class="btn btn-sm btn-info text-white editproductmaster btn-action-icon" data-productid="${response[i].ProductId}" title="Edit Product">
                        <i class="fa fa-edit"></i>
                      </button>
                      <button class="btn btn-sm btn-danger removeproductmaster btn-action-icon" onclick="RemoveProductMaster('${response[i].ProductId}', this);" title="Delete Product">
                        <i class="fa fa-trash-alt"></i>
                      </button>
                    </div>
                </td>
                <td>
                  <div class="fw-bold text-dark fs-14 mb-1">${response[i].ProductName}</div>
                  <span class='product-sku-pill'>
                    ${response[i].ProductSKU}
                  </span>
                </td>
                <td>
                    <div class="mb-1">
                      <span class='seed-badge-root'>
                        <i class="fa fa-seedling me-1"></i>Root: ${response[i].RootSeedName || 'N/A'} - ${response[i].RootSeedVariety || ''}
                      </span>
                    </div>
                    <div>
                      <span class='seed-badge-scion'>
                        <i class="fa fa-cut me-1"></i>Scion: ${scionInfo}
                      </span>
                    </div>
                </td>
                <td class="text-end fw-bold text-dark fs-14">
                  ₹ ${parseFloat(response[i].PriceOfProduct || 0).toLocaleString('en-IN', {minimumFractionDigits: 2})}
                </td>
                <td class="text-center">${growthstatus}</td>
            </tr>
        `;
      }
      PtableBody.append(ptbltr);
      $("#showingProductCount").text(response.length);
      $("#stagesCount").text(stagesCount);
      $("#graftedCount").text(graftedCount);
    } else {
      PtableBody.html(`
        <tr id="noProductRow">
          <td colspan="5" class="text-center text-muted py-5">
            <i class="fa fa-boxes fa-3x text-muted mb-3 d-block"></i>
            <h6 class="fw-bold text-secondary mb-1">No Products Found</h6>
            <small>Click <strong>"+ New Product"</strong> to add your first product master record.</small>
          </td>
        </tr>
      `);
      $("#showingProductCount").text(0);
      $("#stagesCount").text(0);
      $("#graftedCount").text(0);
    }
  });

  // Real-time Live Search Filter
  $("#productSearchInput").on("keyup search input", function () {
    var query = $(this).val().toLowerCase().trim();
    if (query.length > 0) {
      $("#clearSearchBtn").show();
    } else {
      $("#clearSearchBtn").hide();
    }

    var visibleCount = 0;
    var $rows = $("#productTableBody tr").not("#noProductRow, #noSearchResultRow");

    $rows.each(function () {
      var rowText = $(this).text().toLowerCase();
      if (rowText.indexOf(query) > -1) {
        $(this).show();
        visibleCount++;
      } else {
        $(this).hide();
      }
    });

    $("#noSearchResultRow").remove();
    if (visibleCount === 0 && $rows.length > 0) {
      $("#productTableBody").append(`
        <tr id="noSearchResultRow">
          <td colspan="5" class="text-center text-muted py-5">
            <i class="fa fa-search-minus fa-3x text-muted mb-3 d-block"></i>
            <h6 class="fw-bold text-secondary mb-1">No Matching Products Found</h6>
            <small>Try adjusting your search query or clear the filter.</small>
          </td>
        </tr>
      `);
    }

    $("#showingProductCount").text(visibleCount);
  });

  // Clear Search Input Button
  $("#clearSearchBtn").on("click", function () {
    $("#productSearchInput").val("").trigger("keyup").focus();
  });

  // Export Product Master Table Data to Excel / CSV
  function exportProductData(type) {
    let rows = [];
    let headers = ["S.NO.", "PRODUCT NAME", "SKU", "ROOT SEED", "SCION SEED", "SUGGESTED PRICE (₹)"];
    rows.push(headers.map(h => `"${h}"`).join(","));

    let $visibleRows = $("#productTableBody tr:visible").not("#noProductRow, #noSearchResultRow");
    if ($visibleRows.length === 0) {
      Swal.fire({
        icon: "warning",
        title: "No Data Available",
        text: "There are no product records to export matching your search."
      });
      return;
    }

    $visibleRows.each(function (index) {
      let $tds = $(this).find("td");
      let prodName = $tds.eq(1).find("div").text().trim();
      let sku = $tds.eq(1).find(".product-sku-pill").text().trim();
      let rootSeed = $tds.eq(2).find(".seed-badge-root").text().trim();
      let scionSeed = $tds.eq(2).find(".seed-badge-scion").text().trim();
      let price = $tds.eq(3).text().trim().replace("₹", "").trim();

      let rowData = [
        index + 1,
        `"${prodName.replace(/"/g, '""')}"`,
        `"${sku.replace(/"/g, '""')}"`,
        `"${rootSeed.replace(/"/g, '""')}"`,
        `"${scionSeed.replace(/"/g, '""')}"`,
        `"${price.replace(/"/g, '""')}"`
      ];
      rows.push(rowData.join(","));
    });

    let csvContent = "\uFEFF" + rows.join("\r\n");
    let blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
    let url = URL.createObjectURL(blob);
    let link = document.createElement("a");
    let todayStr = new Date().toISOString().slice(0, 10);
    link.setAttribute("href", url);
    link.setAttribute("download", `Product_Master_Report_${todayStr}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    Swal.fire({
      icon: "success",
      title: "Export Downloaded",
      text: `Exported ${$visibleRows.length} product record(s) successfully!`,
      timer: 2000,
      showConfirmButton: false
    });
  }

  $(document).on("click", ".newproductmaster", function () {
    let companyid = $("body").data("companyid");
    let subcompanyid = $("body").data("subcompanyid");
    let projectid = $("body").data("projectid");
    let routurl = $("body").data("routurl");
    let key = $("body").data("key");
    window.location.href = routurl + "krf-orderandsales/newProduct.php?k=" + key + "&iframe=true";
  });

  $(document).on("click", ".editproductmaster", function () {
    let productid = $(this).data("productid");
    let routurl = $("body").data("routurl");
    let key = $("body").data("key");
    window.location.href = routurl + "krf-orderandsales/newProduct.php?k=" + key + "&iframe=true&productid=" + productid;
  });
</script>