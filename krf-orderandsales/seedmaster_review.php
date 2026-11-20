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
  include('functions/seed_functions.php');
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

// Fetch Master Maps for Category & Subcategory Names
$categoryMap = [];
$catRes = $krfconn->query("SELECT CategoryID, CategoryName FROM plant_category_master");
if ($catRes) {
  while ($cr = $catRes->fetch_assoc()) {
    $categoryMap[$cr['CategoryID']] = $cr['CategoryName'];
  }
}

$subcategoryMap = [];
$subcatRes = $krfconn->query("SELECT SubCategoryID, SubCategoryName FROM plant_subcategory_master");
if ($subcatRes) {
  while ($scr = $subcatRes->fetch_assoc()) {
    $subcategoryMap[$scr['SubCategoryID']] = $scr['SubCategoryName'];
  }
}

// Fetch Seed Data for Stats
$seedData = getSeedData($krfconn, ["ProjectId" => $projectid]);
$totalSeeds = count($seedData);
$hybridCount = 0;
$opCount = 0;
$desiCount = 0;
$cropsList = [];

if ($totalSeeds > 0) {
  foreach ($seedData as $sdItem) {
    $typeStr = strtolower(trim($sdItem['TypeOfSeed'] ?? ''));
    if (strpos($typeStr, 'hybrid') !== false) {
      $hybridCount++;
    } elseif (strpos($typeStr, 'open') !== false || strpos($typeStr, 'op') !== false) {
      $opCount++;
    } elseif (strpos($typeStr, 'desi') !== false) {
      $desiCount++;
    }

    $cId = $sdItem['SubCategoryID'] ?? '';
    $cropName = $subcategoryMap[$cId] ?? '';
    if (!empty($cropName)) {
      $cropsList[$cropName] = ($cropsList[$cropName] ?? 0) + 1;
    }
  }
}
ksort($cropsList);
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
  :root {
    --seed-primary: #0d9488;
    --seed-primary-dark: #0f766e;
    --seed-primary-light: #14b8a6;
    --seed-primary-subtle: #f0fdfa;
    --seed-accent: #f59e0b;
    --seed-card-bg: #ffffff;
    --seed-border: #e2e8f0;
    --seed-text: #0f172a;
    --seed-text-muted: #64748b;
    --seed-radius-lg: 16px;
    --seed-radius-md: 10px;
    --seed-radius-sm: 6px;
  }

  /* Main Container */
  .seed-container {
    padding: 1.25rem 1rem;
    max-width: 100%;
  }

  @media (min-width: 768px) {
    .seed-container {
      padding: 1.5rem 1.5rem;
    }
  }

  /* Main Card */
  .seed-main-card {
    border: none;
    border-radius: var(--seed-radius-lg);
    background: var(--seed-card-bg);
    box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.06), 0 8px 16px -6px rgba(0, 0, 0, 0.04);
    overflow: hidden;
    margin-bottom: 2rem;
  }

  /* Header Section */
  .seed-card-header {
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

  /* Stat Chips */
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
    border-radius: var(--seed-radius-md);
    padding: 12px 14px;
    margin-top: 1rem;
    backdrop-filter: blur(6px);
    border: 1px solid rgba(255, 255, 255, 0.22);
  }

  .search-box-wrapper {
    position: relative;
    flex: 1 1 260px;
    min-width: 240px;
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

  .filter-select {
    height: 40px;
    border-radius: 22px;
    font-size: 0.84rem;
    font-weight: 600;
    border: 1px solid rgba(255, 255, 255, 0.3);
    background: #ffffff !important;
    color: #1e293b !important;
    padding: 0 32px 0 14px;
    cursor: pointer;
    min-width: 140px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
  }

  .filter-select:focus {
    background: #ffffff !important;
    border-color: #ffffff;
    box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.35);
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

  .btn-toolbar:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  }

  /* Table Section */
  .table-responsive-wrapper {
    border-radius: var(--seed-radius-md);
    overflow: auto;
    max-height: calc(100vh - 270px);
    min-height: 380px;
    border: 1px solid var(--seed-border);
    position: relative;
    background: #ffffff;
  }

  /* Custom Sleek Scrollbar */
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

  .custom-seed-table {
    width: 100%;
    margin-bottom: 0;
    border-collapse: separate;
    border-spacing: 0;
  }

  .custom-seed-table thead th {
    background: #f8fafc !important;
    color: #334155 !important;
    font-weight: 700;
    font-size: 0.76rem;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    padding: 13px 14px;
    border-bottom: 2px solid #e2e8f0;
    position: sticky;
    top: 0;
    z-index: 10;
    box-shadow: 0 2px 4px rgba(0,0,0,0.04);
    white-space: nowrap;
  }

  .custom-seed-table tbody tr {
    transition: all 0.15s ease-in-out;
  }

  .custom-seed-table tbody tr:nth-of-type(even) {
    background-color: #fafbfc;
  }

  .custom-seed-table tbody tr:hover {
    background-color: #f0fdfa !important;
  }

  .custom-seed-table tbody td {
    padding: 12px 14px;
    font-size: 0.85rem;
    color: #334155;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
  }

  /* Seed Image Thumbnail */
  .seed-thumb-box {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    flex-shrink: 0;
  }

  .seed-thumb-box:hover {
    transform: scale(1.08);
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
  }

  .seed-thumb-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .seed-avatar-placeholder {
    font-size: 1.1rem;
    font-weight: 700;
    color: #0d9488;
    background: #ccfbf1;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  /* Badges & Pills */
  .badge-crop {
    background-color: #f0fdf4;
    color: #166534;
    border: 1px solid #bbf7d0;
    font-weight: 700;
    font-size: 0.74rem;
    padding: 3px 8px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    letter-spacing: 0.2px;
  }

  .badge-category {
    background-color: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
    font-size: 0.72rem;
    padding: 2px 7px;
    border-radius: 6px;
    display: inline-block;
  }

  .badge-crop-type {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.74rem;
    font-weight: 600;
    display: inline-block;
  }

  .badge-hybrid {
    background-color: #dcfce7;
    color: #15803d;
    border: 1px solid #bbf7d0;
  }

  .badge-open-pollinated {
    background-color: #e0f2fe;
    color: #0369a1;
    border: 1px solid #bae6fd;
  }

  .badge-desi {
    background-color: #fef3c7;
    color: #b45309;
    border: 1px solid #fde68a;
  }

  .badge-default {
    background-color: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
  }

  .sku-badge-pill {
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
    max-width: 220px;
    word-break: break-all;
  }

  .sku-copy-btn {
    border: none;
    background: transparent;
    color: #0d9488;
    cursor: pointer;
    padding: 0;
    font-size: 0.8rem;
    display: inline-flex;
    align-items: center;
    transition: transform 0.15s ease;
  }

  .sku-copy-btn:hover {
    transform: scale(1.2);
    color: #0f766e;
  }

  /* Action Buttons */
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

  /* Modal Redesign Styles */
  .modal-dialog-custom {
    max-width: 960px;
    margin: 1.75rem auto;
  }

  .modal-content-styled {
    border: none;
    border-radius: 20px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    overflow: hidden;
    background: #ffffff;
  }

  .modal-header-styled {
    background: linear-gradient(135deg, #094e47 0%, #0d9488 60%, #14b8a6 100%);
    color: #ffffff;
    padding: 1.25rem 1.75rem;
    border-bottom: none;
  }

  .form-section-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 1.5rem 1.5rem 1.25rem;
    margin-bottom: 1.25rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    overflow: hidden;
    box-sizing: border-box;
    width: 100%;
  }

  /* Grid Spacing & Negative Margin containment */
  #seedModal .row {
    margin-left: -8px !important;
    margin-right: -8px !important;
  }

  #seedModal .row > [class*="col-"] {
    padding-left: 8px !important;
    padding-right: 8px !important;
    min-width: 0 !important;
    box-sizing: border-box !important;
  }

  .form-section-header {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.88rem;
    font-weight: 700;
    color: #0f766e;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #f1f5f9;
  }

  /* Form Labels - Normal left-aligned with gap */
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

  /* STRICT 100% WIDTH ENFORCEMENT ON STANDALONE MODAL CONTROLS */
  #seedModal .form-group {
    width: 100% !important;
    margin-bottom: 0.25rem;
    box-sizing: border-box !important;
  }

  #seedModal .form-group > select,
  #seedModal .form-group > .form-select,
  #seedModal .form-group > .form-control,
  #seedModal .form-group > .form-select-styled,
  #seedModal .form-group > .form-control-styled,
  #seedModal .form-group > input:not([type="checkbox"]):not([type="radio"]):not([type="file"]),
  #seedModal textarea {
    width: 100% !important;
    min-width: 100% !important;
    max-width: 100% !important;
    display: block !important;
    box-sizing: border-box !important;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    font-size: 0.86rem;
    padding: 8px 12px;
    height: 40px;
    background-color: #f8fafc;
    color: #0f172a;
    transition: all 0.2s ease;
  }

  #seedModal textarea {
    height: auto !important;
  }

  #seedModal .form-control-styled:focus, 
  #seedModal .form-select-styled:focus,
  #seedModal select:focus,
  #seedModal input:focus {
    background-color: #ffffff;
    border-color: #0d9488 !important;
    box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15) !important;
  }

  /* Modal Input Groups - Fully Contained, Never Overflows */
  #seedModal .input-group {
    width: 100% !important;
    max-width: 100% !important;
    display: flex !important;
    flex-wrap: nowrap !important;
    align-items: stretch !important;
    box-sizing: border-box !important;
    position: relative !important;
  }

  #seedModal .input-group input:not([type="checkbox"]):not([type="radio"]):not([type="file"]),
  #seedModal .input-group > input,
  #seedModal .input-group > .form-control,
  #seedModal .input-group > .form-control-styled {
    flex: 1 1 auto !important;
    width: 1% !important;
    min-width: 0 !important;
    max-width: none !important;
    height: 40px !important;
    padding: 8px 12px !important;
    border-top-right-radius: 0 !important;
    border-bottom-right-radius: 0 !important;
    border-top-left-radius: 8px !important;
    border-bottom-left-radius: 8px !important;
    border: 1px solid #cbd5e1 !important;
    border-right: none !important;
    background-color: #f8fafc !important;
    color: #0f172a !important;
    box-sizing: border-box !important;
  }

  #seedModal .input-group-append {
    display: flex !important;
    margin-left: 0 !important;
    flex-shrink: 0 !important;
  }

  #seedModal .input-group-append .input-group-text,
  #seedModal .input-group > .input-group-text {
    height: 40px !important;
    background-color: #f1f5f9 !important;
    border: 1px solid #cbd5e1 !important;
    border-left: 1px solid #e2e8f0 !important;
    color: #475569 !important;
    font-size: 0.78rem !important;
    font-weight: 700 !important;
    padding: 0 10px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    white-space: nowrap !important;
    border-top-right-radius: 8px !important;
    border-bottom-right-radius: 8px !important;
    border-top-left-radius: 0 !important;
    border-bottom-left-radius: 0 !important;
    box-sizing: border-box !important;
  }
    align-items: center !important;
    white-space: nowrap !important;
    border-top-right-radius: 8px !important;
    border-bottom-right-radius: 8px !important;
    border-top-left-radius: 0 !important;
    border-bottom-left-radius: 0 !important;
    box-sizing: border-box !important;
  }

  /* Existing Specs Auto-Fill Container & Compact Switch Cards */
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

  .spec-switch-card {
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

  .spec-switch-card:hover {
    border-color: #0d9488;
    background: #f0fdfa;
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(13, 148, 136, 0.12);
  }

  .spec-switch-card.active-switch {
    background: #ecfdf5;
    border-color: #0d9488;
    box-shadow: 0 0 0 2px rgba(13, 148, 136, 0.25);
  }

  /* Compact Pure CSS Toggle Switch - Reduced Size & Perfectly Contained */
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

  .spec-toggle-wrap input.spec-switch-input {
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

  .spec-toggle-wrap input.spec-switch-input:checked + .spec-toggle-slider {
    background-color: #0d9488 !important;
    border-color: #0f766e !important;
  }

  .spec-toggle-wrap input.spec-switch-input:checked + .spec-toggle-slider::before {
    transform: translateX(12px) !important;
  }

  .spec-card-content {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    margin: 0 !important;
    padding: 0 !important;
    cursor: pointer;
    font-size: 0.76rem;
    font-weight: 600;
    color: #334155;
    flex-wrap: wrap;
  }

  .spec-pill {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 2px 7px;
    font-size: 0.74rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    white-space: nowrap;
    color: #1e293b;
    line-height: 1.3;
  }

  .spec-switch-card.active-switch .spec-pill {
    border-color: #99f6e4;
    background: #ffffff;
  }

  .spec-autofilled-glow {
    animation: autofillPulse 1.2s ease;
    border-color: #0d9488 !important;
  }

  @keyframes autofillPulse {
    0% { background-color: #d1fae5; box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.3); }
    100% { background-color: #ffffff; box-shadow: none; }
  }

  /* SKU Highlight Box */
  .sku-highlight-box {
    background: linear-gradient(135deg, #f0fdfa 0%, #e6fffa 100%);
    border: 1.5px dashed #0d9488;
    border-radius: 12px;
    padding: 1rem 1.25rem;
    margin-bottom: 1.25rem;
  }

  .sku-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    width: 100% !important;
  }

  .sku-input-styled {
    font-family: 'JetBrains Mono', 'Courier New', monospace;
    font-size: 0.96rem;
    font-weight: 700;
    color: #0f766e;
    background: #ffffff !important;
    border: 1px solid #99f6e4;
    border-radius: 8px;
    padding: 9px 42px 9px 14px;
    letter-spacing: 0.5px;
    width: 100% !important;
  }

  .sku-copy-addon {
    position: absolute;
    right: 8px;
    background: #ccfbf1;
    color: #0d9488;
    border: none;
    border-radius: 6px;
    width: 30px;
    height: 30px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s ease;
  }

  .sku-copy-addon:hover {
    background: #0d9488;
    color: #ffffff;
  }

  /* File Upload Cards */
  .upload-dropzone {
    border: 1.5px dashed #cbd5e1;
    border-radius: 10px;
    padding: 12px;
    background: #f8fafc;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    min-height: 100px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
  }

  .upload-dropzone:hover {
    border-color: #0d9488;
    background: #f0fdfa;
  }

  .upload-dropzone input[type="file"] {
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    opacity: 0;
    cursor: pointer;
  }

  .preview-thumb-container {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 8px;
  }

  .preview-thumb-img {
    width: 52px;
    height: 52px;
    border-radius: 8px;
    object-fit: cover;
    border: 1px solid #cbd5e1;
  }

  /* Modal Footer */
  .modal-footer-styled {
    background: #ffffff;
    border-top: 1px solid #e2e8f0;
    padding: 1rem 1.75rem;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
  }

  /* Responsive Media Queries */
  @media (max-width: 991.98px) {
    .modal-dialog-custom {
      max-width: 94%;
      margin: 1rem auto;
    }
  }

  @media (max-width: 767.98px) {
    .seed-card-header {
      padding: 1.25rem 1rem;
    }

    .toolbar-wrapper {
      padding: 10px;
    }

    .toolbar-flex-row {
      flex-direction: column;
      align-items: stretch !important;
      gap: 10px;
    }

    .search-box-wrapper {
      width: 100%;
      max-width: 100%;
    }

    .filters-group {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      width: 100%;
    }

    .filter-select {
      flex: 1 1 130px;
    }

    .action-btns-group {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      width: 100%;
    }

    .action-btns-group .btn-toolbar,
    .action-btns-group .dropdown {
      flex: 1 1 auto;
    }

    .action-btns-group .btn-toolbar {
      width: 100%;
      justify-content: center;
    }

    .modal-dialog-custom {
      max-width: 100%;
      margin: 0.5rem;
    }

    .modal-header-styled {
      padding: 1rem 1.25rem;
    }

    .form-section-card {
      padding: 1rem;
    }
  }

  @media (max-width: 575.98px) {
    .header-top-bar {
      flex-direction: column;
      align-items: flex-start !important;
      gap: 12px;
    }

    .stat-chips-row {
      width: 100%;
      overflow-x: auto;
      padding-bottom: 4px;
    }
  }
</style>

<div class="container-fluid seed-container">
  <div class="card seed-main-card">
    
    <!-- Top Header Banner -->
    <div class="card-header seed-card-header">
      
      <!-- Title & Live Stats -->
      <div class="d-flex justify-content-between align-items-center header-top-bar w-100">
        <div class="d-flex align-items-center gap-3">
          <div class="header-title-badge shadow-sm">
            <i class="fa fa-seedling"></i>
          </div>
          <div>
            <h4 class="mb-0 text-white fw-bold d-flex align-items-center gap-2">
              Seed Master Directory
            </h4>
            <p class="mb-0 text-white-50 fs-13 mt-1">
              Manage hybrid & desi seed crops, specifications, SKU codes, and suppliers.
            </p>
          </div>
        </div>

        <!-- Stat Chips -->
        <div class="d-flex align-items-center gap-2 stat-chips-row">
          <span class="stat-chip" title="Showing active seeds count">
            <i class="fa fa-layer-group text-warning"></i> 
            <span>Seeds: <strong id="showingSeedCount"><?php echo $totalSeeds; ?></strong> / <?php echo $totalSeeds; ?></span>
          </span>
          <span class="stat-chip" title="Total unique crops">
            <i class="fa fa-leaf text-success"></i> 
            <span>Crops: <strong><?php echo count($cropsList); ?></strong></span>
          </span>
          <span class="stat-chip d-none d-sm-inline-flex" title="Hybrid varieties count">
            <i class="fa fa-cut text-info"></i> 
            <span>Hybrid: <strong><?php echo $hybridCount; ?></strong></span>
          </span>
          <?php if ($opCount > 0): ?>
            <span class="stat-chip d-none d-md-inline-flex" title="Open Pollinated varieties count">
              <i class="fa fa-sun text-warning"></i> 
              <span>OP: <strong><?php echo $opCount; ?></strong></span>
            </span>
          <?php endif; ?>
        </div>
      </div>

      <!-- Action Toolbar (Search, Crop Filter, Type Filter, Action Buttons) -->
      <div class="toolbar-wrapper">
        <div class="d-flex align-items-center justify-content-between gap-2 toolbar-flex-row">
          
          <!-- Live Real-Time Search -->
          <div class="search-box-wrapper">
            <i class="fa fa-search search-icon"></i>
            <input type="text" id="seedSearchInput" class="form-control" placeholder="Search by Seed, Crop, Variety, Brand, Company, SKU..." autocomplete="off">
            <i class="fa fa-times clear-icon" id="clearSearchBtn" title="Clear Search"></i>
          </div>

          <!-- Dropdown Filters Group -->
          <div class="d-flex align-items-center gap-2 filters-group">
            
            <!-- Crop Filter Dropdown -->
            <select class="form-select filter-select" id="cropFilterSelect" title="Filter by Crop">
              <option value="">All Crops (<?php echo count($cropsList); ?>)</option>
              <?php foreach ($cropsList as $cropName => $cropCount): ?>
                <option value="<?php echo htmlspecialchars($cropName); ?>">
                  <?php echo htmlspecialchars($cropName); ?> (<?php echo $cropCount; ?>)
                </option>
              <?php endforeach; ?>
            </select>

            <!-- Seed Type Filter Dropdown -->
            <select class="form-select filter-select" id="typeFilterSelect" title="Filter by Seed Type">
              <option value="">All Types</option>
              <option value="Hybrid">Hybrid (<?php echo $hybridCount; ?>)</option>
              <option value="Open Pollinated">Open Pollinated (<?php echo $opCount; ?>)</option>
              <option value="Desi">Desi (<?php echo $desiCount; ?>)</option>
            </select>
          </div>

          <!-- Action Buttons Group -->
          <div class="d-flex align-items-center gap-2 action-btns-group">
            
            <!-- Export Options Dropdown -->
            <div class="dropdown">
              <button class="btn btn-light text-dark btn-toolbar shadow-sm dropdown-toggle" type="button" id="exportDropdownBtn" data-bs-toggle="dropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fa fa-file-export text-success"></i> Export <span class="caret"></span>
              </button>
              <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3 p-2" aria-labelledby="exportDropdownBtn">
                <a class="dropdown-item py-2 px-3 rounded-2 fs-13 d-flex align-items-center gap-2" href="javascript:void(0)" onclick="exportSeedData('excel')">
                  <i class="fa fa-file-excel text-success"></i> Export to Excel (.csv)
                </a>
                <a class="dropdown-item py-2 px-3 rounded-2 fs-13 d-flex align-items-center gap-2" href="javascript:void(0)" onclick="exportSeedData('csv')">
                  <i class="fa fa-file-csv text-primary"></i> Export to CSV
                </a>
                <div class="dropdown-divider my-1"></div>
                <a class="dropdown-item py-2 px-3 rounded-2 fs-13 d-flex align-items-center gap-2" href="javascript:void(0)" onclick="window.print()">
                  <i class="fa fa-print text-secondary"></i> Print Page
                </a>
              </div>
            </div>

            <!-- Import Excel Button -->
            <button class="btn btn-success btn-toolbar shadow-sm" onclick="$('#importSeedModal').modal('show')" style="background-color: #059669; border-color: #059669;">
              <i class="fa fa-file-excel"></i> <span class="d-none d-sm-inline">Import Excel</span>
            </button>

            <!-- Add New Seed Button -->
            <button class="btn btn-warning text-dark btn-toolbar shadow-sm" onclick="openSeedModal()">
              <i class="fa fa-plus-circle"></i> <span>New Seed</span>
            </button>

            <!-- Refresh Button -->
            <button class="btn btn-outline-light text-white btn-toolbar shadow-sm px-3" onclick="window.location.reload();" title="Refresh Table">
              <i class="fa fa-sync-alt"></i>
            </button>
          </div>

        </div>
      </div>

    </div>

    <!-- Card Body & Responsive Table -->
    <div class="card-body p-3">
      <div class="table-responsive-wrapper shadow-xs">
        <table class="table custom-seed-table" id="seedTable">
          <thead>
            <tr>
              <th class="text-center" style="width: 50px;">S.No.</th>
              <th style="width: 60px;" class="text-center">Photo</th>
              <th>Seed Details & Variety</th>
              <th>Crop & Category</th>
              <th>Type & Brand</th>
              <th>Vendor & Packaging</th>
              <th>Suggested SKU</th>
              <th class="text-center">Growing Days</th>
              <th class="text-center" style="width: 90px;">Action</th>
            </tr>
          </thead>
          <tbody id="seedTableBody">
            <?php
            if (count($seedData) > 0):
              $sno = 1;
              foreach ($seedData as $row):
                $VendorName = $row['VendorName'] ?? '';
                $typeVal = trim($row['TypeOfSeed'] ?? '');
                $typeBadgeClass = 'badge-default';
                if (stripos($typeVal, 'hybrid') !== false) {
                  $typeBadgeClass = 'badge-hybrid';
                } elseif (stripos($typeVal, 'open') !== false || stripos($typeVal, 'op') !== false) {
                  $typeBadgeClass = 'badge-open-pollinated';
                } elseif (stripos($typeVal, 'desi') !== false) {
                  $typeBadgeClass = 'badge-desi';
                }

                $subCatId = $row['SubCategoryID'] ?? '';
                $catId = $row['CategoryID'] ?? '';
                $cropName = $subcategoryMap[$subCatId] ?? '';
                $catName = $categoryMap[$catId] ?? $catId;

                // Photo resolution
                $photoPath = trim($row['MainPhotoPath'] ?? '');
                $fullPhotoUrl = '';
                $hasPhoto = false;
                if (!empty($photoPath)) {
                  if (strpos($photoPath, 'http') === 0) {
                    $fullPhotoUrl = $photoPath;
                    $hasPhoto = true;
                  } else {
                    $fullPhotoUrl = $prefix . $photoPath;
                    $hasPhoto = true;
                  }
                }
                $seedInitial = mb_substr(trim($row['SeedName'] ?? 'S'), 0, 1, 'UTF-8');
                ?>
                <tr id="seed_row_<?php echo $row['SeedID']; ?>" 
                    data-crop="<?php echo htmlspecialchars(strtolower($cropName)); ?>" 
                    data-type="<?php echo htmlspecialchars(strtolower($typeVal)); ?>">
                  
                  <!-- S.No. -->
                  <td class="text-center text-muted font-monospace fw-semibold"><?php echo $sno++; ?></td>
                  
                  <!-- Photo Thumbnail -->
                  <td class="text-center">
                    <div class="seed-thumb-box" onclick="previewSeedImage('<?php echo htmlspecialchars($fullPhotoUrl); ?>', '<?php echo htmlspecialchars($row['SeedName']); ?>')">
                      <?php if ($hasPhoto): ?>
                        <img src="<?php echo htmlspecialchars($fullPhotoUrl); ?>" class="seed-thumb-img" alt="Seed" onerror="this.onerror=null; this.src=''; this.parentNode.innerHTML='<div class=\'seed-avatar-placeholder\'><?php echo $seedInitial; ?></div>';">
                      <?php else: ?>
                        <div class="seed-avatar-placeholder"><?php echo $seedInitial; ?></div>
                      <?php endif; ?>
                    </div>
                  </td>

                  <!-- Seed Details & Variety -->
                  <td>
                    <div class="fw-bold text-dark fs-14"><?php echo htmlspecialchars($row['SeedName']); ?></div>
                    <div class="d-flex align-items-center gap-2 mt-1">
                      <?php if (!empty($row['VarietyofSeed'])): ?>
                        <span class="badge bg-light text-secondary border px-2 py-0 fs-12 fw-normal">
                          <i class="fa fa-tag me-1 text-teal"></i><?php echo htmlspecialchars($row['VarietyofSeed']); ?>
                        </span>
                      <?php endif; ?>
                      <?php if (!empty($row['CompanyName'])): ?>
                        <small class="text-muted fs-12">
                          <i class="fa fa-building me-1 text-muted"></i><?php echo htmlspecialchars($row['CompanyName']); ?>
                        </small>
                      <?php endif; ?>
                    </div>
                  </td>

                  <!-- Crop & Category -->
                  <td>
                    <?php if (!empty($cropName)): ?>
                      <div class="mb-1">
                        <span class="badge-crop">
                          <i class="fa fa-leaf me-1 text-success"></i><?php echo htmlspecialchars($cropName); ?>
                        </span>
                      </div>
                    <?php endif; ?>
                    <?php if (!empty($catName)): ?>
                      <span class="badge-category text-truncate" style="max-width: 140px;" title="<?php echo htmlspecialchars($catName); ?>">
                        <?php echo htmlspecialchars($catName); ?>
                      </span>
                    <?php endif; ?>
                  </td>

                  <!-- Type & Brand -->
                  <td>
                    <?php if (!empty($typeVal)): ?>
                      <div class="mb-1">
                        <span class="badge-crop-type <?php echo $typeBadgeClass; ?>">
                          <?php echo htmlspecialchars($typeVal); ?>
                        </span>
                      </div>
                    <?php endif; ?>
                    <?php if (!empty($row['BrandName'])): ?>
                      <small class="text-dark fw-semibold d-block">
                        <i class="fa fa-copyright me-1 text-muted"></i><?php echo htmlspecialchars($row['BrandName']); ?>
                      </small>
                    <?php endif; ?>
                  </td>

                  <!-- Vendor & Packaging -->
                  <td>
                    <div class="d-flex align-items-center gap-1 fs-13 text-dark fw-semibold">
                      <i class="fa fa-scale-balanced me-1 text-muted"></i>
                      <span><?php echo !empty($row['WeightPerPacket']) ? htmlspecialchars($row['WeightPerPacket']) . ' gm' : '-'; ?></span>
                      <?php if (!empty($row['CountPerPackate'])): ?>
                        <span class="text-muted fw-normal ms-1">/ <?php echo number_format($row['CountPerPackate']); ?> seeds</span>
                      <?php endif; ?>
                    </div>
                    <?php if (!empty($VendorName)): ?>
                      <small class="text-muted d-block mt-1 text-truncate" style="max-width: 160px;" title="<?php echo htmlspecialchars($VendorName); ?>">
                        <i class="fa fa-store me-1 text-secondary"></i><?php echo htmlspecialchars($VendorName); ?>
                      </small>
                    <?php endif; ?>
                  </td>

                  <!-- Suggested SKU -->
                  <td>
                    <?php if (!empty($row['sku'])): ?>
                      <div class="sku-badge-pill" title="<?php echo htmlspecialchars($row['sku']); ?>">
                        <span><?php echo htmlspecialchars($row['sku']); ?></span>
                        <button type="button" class="sku-copy-btn" onclick="copyText('<?php echo htmlspecialchars($row['sku']); ?>', this)" title="Copy SKU">
                          <i class="fa fa-copy"></i>
                        </button>
                      </div>
                    <?php else: ?>
                      <span class="text-muted fs-12 fst-italic">No SKU</span>
                    <?php endif; ?>
                  </td>

                  <!-- Growing Days -->
                  <td class="text-center">
                    <span class="badge bg-light text-dark border px-2 py-1 fs-12">
                      <i class="fa fa-clock text-warning me-1"></i><?php echo htmlspecialchars($row['growingdays']); ?> Days
                    </span>
                  </td>

                  <!-- Action Buttons -->
                  <td class="text-center text-nowrap">
                    <button class="btn btn-sm btn-info text-white me-1 editseedbtn btn-action-icon" 
                      title="Edit Seed Details"
                      data-seedid="<?php echo $row['SeedID']; ?>"
                      data-seedname="<?php echo htmlspecialchars($row['SeedName']); ?>"
                      data-sku="<?php echo htmlspecialchars($row['sku'] ?? ''); ?>"
                      data-categoryid="<?php echo $row['CategoryID']; ?>"
                      data-subcategoryid="<?php echo $row['SubCategoryID']; ?>"
                      data-typeofseed="<?php echo htmlspecialchars($row['TypeOfSeed']); ?>"
                      data-variety="<?php echo htmlspecialchars($row['VarietyofSeed']); ?>"
                      data-brand="<?php echo htmlspecialchars($row['BrandName']); ?>"
                      data-company="<?php echo htmlspecialchars($row['CompanyName']); ?>"
                      data-vendorid="<?php echo $row['VendorID']; ?>" 
                      data-weight="<?php echo $row['WeightPerPacket']; ?>"
                      data-count="<?php echo $row['CountPerPackate']; ?>"
                      data-growingdays="<?php echo $row['growingdays']; ?>"
                      data-desc="<?php echo htmlspecialchars($row['Description']); ?>"
                      data-photo="<?php echo htmlspecialchars($fullPhotoUrl); ?>"
                      onclick="openEditSeedModal(this)">
                      <i class="fa fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger deleteseedbtn btn-action-icon"
                      title="Delete Seed"
                      onclick="deleteSeedData('<?php echo $row['SeedID']; ?>')">
                      <i class="fa fa-trash-alt"></i>
                    </button>
                  </td>
                </tr>
                <?php
              endforeach;
            else:
              ?>
              <tr id="noSeedFoundRow">
                <td colspan="9" class="text-center text-muted py-5">
                  <i class="fa fa-seedling fa-3x text-muted mb-3 d-block"></i>
                  <h6 class="fw-bold text-secondary mb-1">No Seeds Registered</h6>
                  <small>Click <strong>"+ New Seed"</strong> to register your first seed master record.</small>
                </td>
              </tr>
            <?php endif; ?>

            <!-- Dynamic Search No Result Row -->
            <tr id="noSearchResultRow" style="display: none;">
              <td colspan="9" class="text-center text-muted py-5">
                <i class="fa fa-search-minus fa-3x text-muted mb-3 d-block"></i>
                <h6 class="fw-bold text-secondary mb-1">No Matching Seeds Found</h6>
                <small>Try adjusting your search criteria, crop filter, or clear filters.</small>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Add / Edit Seed Modal (Redesigned & Fully Responsive) -->
<div class="modal fade" id="seedModal" tabindex="-1" role="dialog" aria-labelledby="seedModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-custom modal-dialog-centered modal-dialog-scrollable" role="document">
    <div class="modal-content modal-content-styled">
      
      <!-- Modal Header -->
      <div class="modal-header modal-header-styled">
        <div class="d-flex align-items-center gap-2">
          <i class="fa fa-seedling fs-18"></i>
          <div>
            <h5 class="modal-title fw-bold mb-0 text-white" id="seedModalLabel">Add New Seed</h5>
            <small class="text-white-50 fs-12">Complete crop classification, specs & auto-generate unique SKU.</small>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white close text-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body p-3 p-md-4" style="background-color: #f8fafc;">
        <form id="seedForm" enctype="multipart/form-data">
          <input type="hidden" name="SeedID" id="SeedID">
          <input type="hidden" name="projectid" value="<?php echo $projectid; ?>">
          <input type="hidden" name="companyid" value="<?php echo $companyid; ?>">
          <input type="hidden" name="subcompanyid" value="<?php echo $subcompanyid; ?>">
          <input type="hidden" name="loginid" value="<?php echo $loginid; ?>">

          <!-- SECTION 1: Crop Classification & Variety -->
          <div class="form-section-card">
            <div class="form-section-header">
              <i class="fa fa-tags text-teal"></i>
              <span>1. Classification & Variety</span>
            </div>

            <div class="row g-3">
              <!-- Category -->
              <div class="col-md-4 col-sm-6">
                <div class="form-group w-100">
                  <label class="form-label-styled">Category <span class="text-danger">*</span></label>
                  <select class="form-control form-select form-select-styled w-100" name="SeedCategory" id="SeedCategory" required style="width: 100% !important; min-width: 100% !important;">
                    <option value="">Select Category</option>
                  </select>
                </div>
              </div>

              <!-- Sub Category / Crop -->
              <div class="col-md-4 col-sm-6">
                <div class="form-group w-100">
                  <label class="form-label-styled">Sub Category (Crop) <span class="text-danger">*</span></label>
                  <select class="form-control form-select form-select-styled w-100" name="SubSeedCategory" id="SubSeedCategory" required style="width: 100% !important; min-width: 100% !important;">
                    <option value="">Select Sub Category</option>
                  </select>
                </div>
              </div>

              <!-- Type Of Seed -->
              <div class="col-md-4 col-sm-12">
                <div class="form-group w-100">
                  <label class="form-label-styled">Type Of Seed</label>
                  <select class="form-control form-select form-select-styled w-100" name="TypeOfSeed" id="TypeOfSeed" style="width: 100% !important; min-width: 100% !important;">
                    <option value="Hybrid">Hybrid</option>
                    <option value="Open Pollinated">Open Pollinated</option>
                    <option value="Desi">Desi</option>
                  </select>
                </div>
              </div>

              <!-- Variety Name -->
              <div class="col-md-4 col-sm-6">
                <div class="form-group w-100">
                  <label class="form-label-styled">Variety Name <span class="text-danger">*</span></label>
                  <input type="text" class="form-control form-control-styled w-100" name="VarietyName" id="VarietyName" required placeholder="e.g. JANNAT, Saaho 3251" style="width: 100% !important;">
                </div>
              </div>

              <!-- Brand Name -->
              <div class="col-md-4 col-sm-6">
                <div class="form-group w-100">
                  <label class="form-label-styled">Brand Name</label>
                  <input type="text" class="form-control form-control-styled w-100" name="BrandName" id="BrandName" placeholder="e.g. KNOWN-YOU, Syngenta" style="width: 100% !important;">
                </div>
              </div>

              <!-- Company Name -->
              <div class="col-md-4 col-sm-12">
                <div class="form-group w-100">
                  <label class="form-label-styled">Company Name</label>
                  <input type="text" class="form-control form-control-styled w-100" name="CompanyName" id="CompanyName" placeholder="e.g. Known-You Seed Co." style="width: 100% !important;">
                </div>
              </div>
            </div>
          </div>

          <!-- SECTION 2: Seed Specifications & Supplier -->
          <div class="form-section-card">
            <div class="form-section-header">
              <i class="fa fa-cubes-stacked text-teal"></i>
              <span>2. Specifications & Supplier</span>
            </div>

            <div class="row g-3">
              <!-- Seed Name (Auto-Generated) -->
              <div class="col-md-6 col-sm-12">
                <div class="form-group w-100">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label-styled mb-0">Seed Name <small class="text-muted fw-normal fs-11">(Auto-Generated if empty)</small></label>
                    <span class="badge bg-light text-muted border fs-11" id="seedNameBadge">Auto</span>
                  </div>
                  <input type="text" class="form-control form-control-styled w-100" name="SeedName" id="SeedName" placeholder="e.g. WATERMELON JANNAT" style="width: 100% !important;">
                </div>
              </div>

              <!-- Select Vendor -->
              <div class="col-md-6 col-sm-12">
                <div class="form-group w-100">
                  <label class="form-label-styled">Select Vendor</label>
                  <select class="form-control form-select form-select-styled w-100" name="VendorID" id="VendorID" style="width: 100% !important; min-width: 100% !important;">
                    <option value="">Select Vendor</option>
                  </select>
                </div>
              </div>

              <!-- EXISTING SPECIFICATIONS AUTO-FILL SWITCHES CONTAINER -->
              <div class="col-12" id="existingSpecsContainer" style="display: none;">
                <div class="existing-specs-box p-3 rounded-3 border">
                  <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="fs-12 fw-bold text-teal d-flex align-items-center gap-1">
                      <i class="fa fa-toggle-on fs-15 text-success"></i> Existing Packaging Specs for this Crop:
                    </span>
                    <span class="badge bg-light text-dark border fs-11" id="specsCountBadge">0 presets</span>
                  </div>
                  <div class="fs-12 text-muted mb-2">
                    <i class="fa fa-info-circle text-teal me-1"></i> Switch <strong>ON</strong> to auto-fill <strong>Weight</strong>, <strong>Count</strong> & <strong>Growing Days</strong> <small class="text-muted">(only one can be selected at a time)</small>:
                  </div>
                  <div class="d-flex flex-wrap gap-2" id="existingSpecsList">
                    <!-- Dynamic Switch Items -->
                  </div>
                </div>
              </div>

              <!-- Weight Per Packet -->
              <div class="col-12 col-md-4">
                <div class="form-group w-100">
                  <label class="form-label-styled">Weight Per Packet</label>
                  <div class="input-group">
                    <input type="number" step="0.01" class="form-control form-control-styled" name="SeedWeightPerPacket" id="SeedWeightPerPacket" placeholder="e.g. 50">
                    <div class="input-group-append">
                      <span class="input-group-text">gm</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Count Per Packet -->
              <div class="col-12 col-md-4">
                <div class="form-group w-100">
                  <label class="form-label-styled">Count Per Packet</label>
                  <div class="input-group">
                    <input type="number" class="form-control form-control-styled" name="SeedCountPerPacket" id="SeedCountPerPacket" placeholder="e.g. 1000">
                    <div class="input-group-append">
                      <span class="input-group-text">seeds</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Growing Days -->
              <div class="col-12 col-md-4">
                <div class="form-group w-100">
                  <label class="form-label-styled">Growing Days</label>
                  <div class="input-group">
                    <input type="number" class="form-control form-control-styled" name="growingdays" id="growingdays" value="30">
                    <div class="input-group-append">
                      <span class="input-group-text">days</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- SECTION 3: Auto-Generated Unique SKU Box -->
          <div class="sku-highlight-box">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <label class="form-label fw-bold text-teal mb-0 fs-13 d-flex align-items-center gap-1">
                <i class="fa fa-barcode fs-15"></i> Suggested Seed SKU
              </label>
              <span class="badge bg-white text-teal border px-2 py-1 fs-11" id="skuStatusPill">
                <i class="fa fa-sparkles text-warning me-1"></i> Unique & Auto-Generated
              </span>
            </div>
            <div class="sku-input-wrapper w-100" style="width: 100% !important;">
              <input type="text" class="form-control sku-input-styled w-100" name="suggestedsku" id="suggestedsku" readonly placeholder="Fill Category, Crop & Variety above to generate SKU..." style="width: 100% !important;">
              <button type="button" class="sku-copy-addon" onclick="copyModalSKU()" title="Copy Suggested SKU">
                <i class="fa fa-copy"></i>
              </button>
            </div>
            <div class="d-flex align-items-center justify-content-between mt-2 fs-11 text-muted">
              <span><i class="fa fa-shield-check text-teal me-1"></i> Code format: [Category]-[Crop]-[Variety]-[Type]-[Brand]</span>
              <span class="text-teal fw-semibold"><i class="fa fa-check-circle me-1"></i>Guaranteed Unique</span>
            </div>
          </div>

          <!-- SECTION 4: Media & Remarks -->
          <div class="form-section-card mb-0">
            <div class="form-section-header">
              <i class="fa fa-image text-teal"></i>
              <span>3. Remarks & Photos</span>
            </div>

            <div class="row g-3">
              <!-- Description -->
              <div class="col-md-6 col-sm-12">
                <div class="form-group w-100">
                  <label class="form-label-styled">Description / Remarks</label>
                  <textarea class="form-control form-control-styled w-100" name="Description" id="Description" rows="4" placeholder="Additional seed details, germination rates, seasonal remarks..." style="width: 100% !important;"></textarea>
                </div>
              </div>

              <!-- Main Photo -->
              <div class="col-md-3 col-sm-6">
                <div class="form-group w-100">
                  <label class="form-label-styled">Main Photo</label>
                  <div class="upload-dropzone" id="mainPhotoDropzone">
                    <input type="file" name="uploadMainPhoto" id="uploadMainPhoto" accept="image/*" onchange="previewMainPhoto(this)">
                    <div id="mainPhotoPrompt">
                      <i class="fa fa-cloud-arrow-up text-teal fs-22 mb-1"></i>
                      <div class="fs-12 fw-bold text-dark">Click or Drag</div>
                      <small class="text-muted fs-11">JPG, PNG, WebP</small>
                    </div>
                    <div id="mainPhotoPreview" style="display: none; position: relative;">
                      <img id="mainPhotoImg" class="preview-thumb-img" alt="Preview">
                      <span class="badge bg-danger position-absolute top-0 end-0 p-1" style="cursor: pointer;" onclick="clearMainPhoto(event)" title="Remove Photo">&times;</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Other Photos -->
              <div class="col-md-3 col-sm-6">
                <div class="form-group w-100">
                  <label class="form-label-styled">Other Photos</label>
                  <div class="upload-dropzone" id="otherPhotosDropzone">
                    <input type="file" name="uploadOtherPhotos[]" id="uploadOtherPhotos" multiple accept="image/*" onchange="previewOtherPhotos(this)">
                    <div id="otherPhotosPrompt">
                      <i class="fa fa-images text-muted fs-22 mb-1"></i>
                      <div class="fs-12 fw-bold text-dark">Upload Multiple</div>
                      <small class="text-muted fs-11">Multiple images</small>
                    </div>
                    <div class="preview-thumb-container" id="otherPhotosPreviewContainer"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Sticky Action Footer -->
          <div class="modal-footer-styled mt-4 pt-3">
            <button type="button" class="btn btn-light px-4 py-2 border rounded-pill" data-bs-dismiss="modal" data-dismiss="modal">
              Cancel
            </button>
            <button type="submit" class="btn btn-success px-4 py-2 rounded-pill fw-bold shadow-sm d-flex align-items-center gap-2" id="saveSeedBtn" style="background-color: #0d9488; border-color: #0d9488;">
              <i class="fa fa-save"></i> <span>Save Seed Details</span>
            </button>
          </div>
        </form>
      </div>

    </div>
  </div>
</div>

<!-- Import Excel Modal (Redesigned) -->
<div class="modal fade" id="importSeedModal" tabindex="-1" role="dialog" aria-labelledby="importSeedModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content modal-content-styled">
      <div class="modal-header bg-success text-white py-3 px-4">
        <h5 class="modal-title fw-bold mb-0 d-flex align-items-center gap-2" id="importSeedModalLabel">
          <i class="fa fa-file-excel"></i> Import Seeds from Excel
        </h5>
        <button type="button" class="btn-close btn-close-white close text-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <form id="importSeedForm" enctype="multipart/form-data">
          <input type="hidden" name="projectid" value="<?php echo $projectid; ?>">
          <input type="hidden" name="loginid" value="<?php echo $loginid; ?>">

          <div class="form-group mb-3">
            <label class="form-label-styled">Upload Excel/CSV File (.xlsx, .xls, .csv)</label>
            <input type="file" class="form-control form-control-styled w-100" name="excelFile" accept=".xlsx, .xls, .csv" required style="width: 100% !important;">
            <div class="alert alert-info py-2 px-3 mt-3 mb-0 fs-12 border-0 shadow-xs rounded-3">
              <i class="fa fa-info-circle me-1"></i> Ensure columns match standard template:
              <br><strong>CATEGORY NAME, CROP, TYPE OF CROP, VARIETY, SEED BRAND NAME, SEED CODE, COMPANY NAME, NAME OF VENDOR, PER PACKET WEIGHT, NO. OF SEED PER PACKET, REMARK, MAIN PHOTO</strong>
            </div>
          </div>

          <div class="d-flex justify-content-end gap-2 mt-4 pt-2 border-top">
            <button type="button" class="btn btn-light px-4 py-2 border rounded-pill" data-bs-dismiss="modal" data-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-success px-4 py-2 rounded-pill fw-bold shadow-sm" style="background-color: #059669; border-color: #059669;">
              <i class="fa fa-upload me-1"></i> Upload & Import
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Image Preview Lightbox Modal -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 480px;">
    <div class="modal-content modal-content-styled p-2 text-center">
      <div class="d-flex justify-content-between align-items-center px-3 pt-2 pb-1">
        <h6 class="fw-bold mb-0 text-dark" id="previewSeedTitle">Seed Photo</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="p-2">
        <img id="lightboxImg" src="" class="img-fluid rounded-3 shadow-sm" style="max-height: 420px; width: 100%; object-fit: contain;" alt="Seed Photo">
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
  // Set global data attributes for JS functions
  $("body").data("routurl", "<?php echo $rooturl; ?>");
  $("body").data("projectid", "<?php echo $projectid; ?>");
  $("body").data("companyid", "<?php echo $companyid; ?>");
  $("body").data("subcompanyid", "<?php echo $subcompanyid; ?>");
  $("body").data("loginid", "<?php echo $loginid; ?>");

  $(document).ready(function () {
    // Initialize dropdowns
    GetProductCategoryFrommaster_option("SeedCategory");
    GetVendorDataOnlyOption("VendorID");

    // Auto Generate Seed Name & Unique SKU
    function autoUpdateSeedNameAndSKU() {
      var catId = $("#SeedCategory").val();
      var subCatId = $("#SubSeedCategory").val();
      var subCatText = $("#SubSeedCategory option:selected").text().trim();
      if (subCatText === "Select" || subCatText === "Select Sub Category" || subCatText.startsWith("Select")) {
        subCatText = "";
      }
      var variety = $("#VarietyName").val().trim();
      var type = $("#TypeOfSeed").val();
      var brand = $("#BrandName").val().trim();
      var seedId = $("#SeedID").val();

      // Auto update SeedName if empty or marked auto-generated
      var currentSeedName = $("#SeedName").val().trim();
      var isAuto = $("#SeedName").data("autoGenerated");
      if (currentSeedName === "" || isAuto) {
        if (subCatText || variety) {
          var autoName = (subCatText + " " + variety).trim();
          $("#SeedName").val(autoName);
          $("#SeedName").data("autoGenerated", true);
          $("#seedNameBadge").text("Auto").removeClass("bg-primary text-white").addClass("bg-light text-muted border");
        }
      }

      if (catId && subCatId && variety && type) {
        GetSuggestedSKUSeed(catId, subCatId, variety, type, brand, "suggestedsku", seedId);
      }
    }

    $("#SeedName").on("input", function() {
      $(this).data("autoGenerated", false);
      $("#seedNameBadge").text("Custom").removeClass("bg-light text-muted border").addClass("bg-primary text-white");
    });

    // Subcategory dependency & Load Existing Specs Presets
    $("#SeedCategory").change(function () {
      var catId = $(this).val();
      GetProductSubCategoryFrommaster_option("SubSeedCategory", "CategoryID", catId);
      $("#suggestedsku").val("");
      $("#existingSpecsContainer").slideUp(150);
      $("#existingSpecsList").empty();
    });

    $("#SubSeedCategory").change(function () {
      var catId = $("#SeedCategory").val();
      var subCatId = $(this).val();
      loadExistingSpecs(catId, subCatId);
    });

    // Auto Generate SKU on input / change
    $(document).on("input change", "#VarietyName, #TypeOfSeed, #BrandName, #SubSeedCategory", function () {
      autoUpdateSeedNameAndSKU();
    });

    // Switch ON/OFF for Existing Specifications ("at a time only one can select")
    $(document).on("change", ".spec-switch-input", function () {
      var isChecked = $(this).is(":checked");
      
      if (isChecked) {
        // Mutually exclusive: turn off all other switch inputs
        $(".spec-switch-input").not(this).prop("checked", false);
        $(".spec-switch-card").removeClass("active-switch");
        $(this).closest(".spec-switch-card").addClass("active-switch");

        var weight = $(this).data("weight");
        var count = $(this).data("count");
        var days = $(this).data("days");

        if (weight !== undefined && weight !== null && weight !== "") {
          $("#SeedWeightPerPacket").val(weight);
        }
        if (count !== undefined && count !== null && count !== "") {
          $("#SeedCountPerPacket").val(count);
        }
        if (days !== undefined && days !== null && days !== "") {
          $("#growingdays").val(days);
        }

        // Highlight fields to indicate auto-fill
        $("#SeedWeightPerPacket, #SeedCountPerPacket, #growingdays").addClass("spec-autofilled-glow");
        setTimeout(function () {
          $("#SeedWeightPerPacket, #SeedCountPerPacket, #growingdays").removeClass("spec-autofilled-glow");
        }, 1200);

      } else {
        $(this).closest(".spec-switch-card").removeClass("active-switch");
      }
    });

    // Clicking anywhere on the card toggles the switch smoothly
    $(document).on("click", ".spec-switch-card", function (e) {
      if (!$(e.target).is("input[type='checkbox'], label, label *")) {
        var $sw = $(this).find(".spec-switch-input");
        $sw.prop("checked", !$sw.prop("checked")).trigger("change");
      }
    });

    // When user manually types into the 3 fields, update active switch state
    $(document).on("input change", "#SeedWeightPerPacket, #SeedCountPerPacket, #growingdays", function () {
      checkMatchingSpecSwitch();
    });

    // Real-Time Multi-Filter (Text Query + Crop Dropdown + Seed Type Dropdown)
    function applyTableFilters() {
      var query = $("#seedSearchInput").val().toLowerCase().trim();
      var selectedCrop = $("#cropFilterSelect").val().toLowerCase().trim();
      var selectedType = $("#typeFilterSelect").val().toLowerCase().trim();

      if (query.length > 0) {
        $("#clearSearchBtn").show();
      } else {
        $("#clearSearchBtn").hide();
      }

      var visibleCount = 0;
      var $rows = $("#seedTableBody tr").not("#noSeedFoundRow, #noSearchResultRow");

      $rows.each(function () {
        var rowText = $(this).text().toLowerCase();
        var rowCrop = ($(this).data("crop") || "").toString().toLowerCase();
        var rowType = ($(this).data("type") || "").toString().toLowerCase();

        var matchesQuery = query === "" || rowText.indexOf(query) > -1;
        var matchesCrop = selectedCrop === "" || rowCrop === selectedCrop;
        var matchesType = selectedType === "" || rowType.indexOf(selectedType) > -1;

        if (matchesQuery && matchesCrop && matchesType) {
          $(this).show();
          visibleCount++;
        } else {
          $(this).hide();
        }
      });

      if (visibleCount === 0 && $rows.length > 0) {
        $("#noSearchResultRow").show();
      } else {
        $("#noSearchResultRow").hide();
      }

      $("#showingSeedCount").text(visibleCount);
    }

    $("#seedSearchInput").on("keyup search input", applyTableFilters);
    $("#cropFilterSelect").on("change", applyTableFilters);
    $("#typeFilterSelect").on("change", applyTableFilters);

    // Clear Search Input Button
    $("#clearSearchBtn").on("click", function () {
      $("#seedSearchInput").val("");
      applyTableFilters();
      $("#seedSearchInput").focus();
    });

    // Form Submission
    $("#seedForm").submit(function (e) {
      e.preventDefault();
      var formData = new FormData(this);

      Swal.fire({
        title: 'Saving Seed Details...',
        text: 'Please wait while we update seed master data',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      $.ajax({
        type: "POST",
        url: "<?php echo $rooturl; ?>krf-orderandsales/modal/seed_modal.php?action=saveupdateseeddetails",
        data: formData,
        contentType: false,
        processData: false,
        dataType: "JSON",
        success: function (res) {
          if (res.status == 'success') {
            Swal.fire({
              icon: 'success',
              title: 'Success!',
              text: res.message,
              timer: 1600,
              showConfirmButton: false
            }).then(() => {
              window.location.reload();
            });
          } else {
            Swal.fire('Error', res.message, 'error');
          }
        },
        error: function (err) {
          Swal.fire('Error', 'Something went wrong while saving seed details.', 'error');
        }
      });
    });

    // Import Excel Submission
    $("#importSeedForm").submit(function (e) {
      e.preventDefault();
      var formData = new FormData(this);

      Swal.fire({
        title: 'Importing Seeds...',
        text: 'Processing Excel file. This may take a moment.',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      $.ajax({
        type: "POST",
        url: "<?php echo $rooturl; ?>krf-orderandsales/modal/import_seed_excel.php",
        data: formData,
        contentType: false,
        processData: false,
        dataType: "JSON",
        success: function (res) {
          if (res.status == 'success') {
            let msg = res.message;
            if (res.errors && res.errors.length > 0) {
              msg += "<br><br><b>Errors:</b><br>" + res.errors.join("<br>");
              Swal.fire({
                title: 'Partial Success',
                html: msg,
                icon: 'warning'
              }).then(() => {
                window.location.reload();
              });
            } else {
              Swal.fire('Success', msg, 'success').then(() => {
                window.location.reload();
              });
            }
          } else {
            Swal.fire('Error', res.message, 'error');
          }
        },
        error: function (xhr, status, error) {
          console.error(xhr.responseText);
          Swal.fire('Error', 'Failed to import file: ' + error, 'error');
        }
      });
    });
  });

  // Load Existing Specs Presets for Category & Crop
  function loadExistingSpecs(catId, subCatId) {
    if (!subCatId) {
      $("#existingSpecsContainer").slideUp(150);
      $("#existingSpecsList").empty();
      return;
    }

    var routurl = $("body").data("routurl");
    var projectid = $("body").data("projectid");

    $.ajax({
      type: "POST",
      url: `${routurl}krf-orderandsales/modal/seed_modal.php?action=GetExistingSeedSpecs`,
      data: {
        projectid: projectid,
        CategoryID: catId,
        SubCategoryID: subCatId
      },
      dataType: "JSON",
      success: function (res) {
        if (Array.isArray(res) && res.length > 0) {
          var html = "";
          res.forEach(function (item, idx) {
            var weightFormatted = item.weight ? item.weight + " gm" : "-";
            var countFormatted = item.count ? parseInt(item.count).toLocaleString() + " seeds" : "-";
            var daysFormatted = item.growingdays ? item.growingdays + " days" : "30 days";
            var usageText = item.usage_count ? `<small class="text-muted ms-1">(${item.usage_count} seed${item.usage_count > 1 ? 's' : ''})</small>` : "";

            html += `
              <div class="spec-switch-card" id="spec_card_${idx}">
                <div class="spec-toggle-wrap">
                  <input type="checkbox" class="spec-switch-input" id="spec_sw_${idx}"
                         data-weight="${item.weight}" data-count="${item.count}" data-days="${item.growingdays}">
                  <label class="spec-toggle-slider" for="spec_sw_${idx}" title="Toggle Preset"></label>
                </div>
                <label class="spec-card-content" for="spec_sw_${idx}">
                  <span class="spec-pill spec-pill-wt"><i class="fa fa-balance-scale me-1 text-teal"></i>${weightFormatted}</span>
                  <span class="spec-pill spec-pill-cnt"><i class="fa fa-cubes me-1 text-teal"></i>${countFormatted}</span>
                  <span class="spec-pill spec-pill-days"><i class="fa fa-clock-o me-1 text-warning"></i>${daysFormatted}</span>
                  ${usageText}
                </label>
              </div>
            `;
          });

          $("#existingSpecsList").html(html);
          $("#specsCountBadge").text(res.length + (res.length > 1 ? " presets found" : " preset found"));
          $("#existingSpecsContainer").slideDown(200);

          // Auto-check if current inputs match any preset
          checkMatchingSpecSwitch();
        } else {
          $("#existingSpecsContainer").slideUp(150);
          $("#existingSpecsList").empty();
        }
      },
      error: function () {
        $("#existingSpecsContainer").hide();
      }
    });
  }

  function checkMatchingSpecSwitch() {
    var curWeight = ($("#SeedWeightPerPacket").val() || "").toString().trim();
    var curCount = ($("#SeedCountPerPacket").val() || "").toString().trim();
    var curDays = ($("#growingdays").val() || "").toString().trim();

    var matched = false;
    $(".spec-switch-input").each(function () {
      var swWeight = ($(this).data("weight") || "").toString().trim();
      var swCount = ($(this).data("count") || "").toString().trim();
      var swDays = ($(this).data("days") || "").toString().trim();

      if (swWeight === curWeight && swCount === curCount && swDays === curDays && (curWeight !== "" || curCount !== "")) {
        $(this).prop("checked", true);
        $(this).closest(".spec-switch-card").addClass("active-switch");
        matched = true;
        return false; // Break
      } else {
        $(this).prop("checked", false);
        $(this).closest(".spec-switch-card").removeClass("active-switch");
      }
    });
    return matched;
  }

  // Modal Open / Edit Functions
  function openSeedModal() {
    $("#seedForm")[0].reset();
    $("#SeedID").val("");
    $("#SeedName").data("autoGenerated", true);
    $("#seedNameBadge").text("Auto").removeClass("bg-primary text-white").addClass("bg-light text-muted border");
    $("#suggestedsku").val("");
    $("#existingSpecsContainer").hide();
    $("#existingSpecsList").empty();
    $("#mainPhotoPreview").hide();
    $("#mainPhotoPrompt").show();
    $("#otherPhotosPreviewContainer").empty();
    $("#otherPhotosPrompt").show();
    $("#seedModalLabel").html('<i class="fa fa-seedling me-2"></i>Add New Seed');
    $("#seedModal").modal("show");
  }

  function openEditSeedModal(btn) {
    var seedId = $(btn).data("seedid");
    var seedName = $(btn).data("seedname");
    var categoryId = $(btn).data("categoryid");
    var subCategoryId = $(btn).data("subcategoryid");
    var typeOfSeed = $(btn).data("typeofseed");
    var variety = $(btn).data("variety");
    var brand = $(btn).data("brand");
    var company = $(btn).data("company");
    var vendorId = $(btn).data("vendorid");
    var weight = $(btn).data("weight");
    var count = $(btn).data("count");
    var growingdays = $(btn).data("growingdays");
    var desc = $(btn).data("desc");
    var sku = $(btn).data("sku") || "";
    var photo = $(btn).data("photo") || "";

    $("#seedForm")[0].reset();
    $("#SeedID").val(seedId);
    $("#SeedName").val(seedName);
    $("#SeedName").data("autoGenerated", false);
    $("#seedNameBadge").text("Edit").removeClass("bg-light text-muted border").addClass("bg-secondary text-white");
    $("#TypeOfSeed").val(typeOfSeed);
    $("#VarietyName").val(variety);
    $("#BrandName").val(brand);
    $("#CompanyName").val(company);
    $("#SeedWeightPerPacket").val(weight);
    $("#SeedCountPerPacket").val(count);
    $("#growingdays").val(growingdays);
    $("#Description").val(desc);
    $("#suggestedsku").val(sku);

    // Pre-fill categories and trigger updates
    $("#SeedCategory").val(categoryId);
    GetProductSubCategoryFrommaster_option("SubSeedCategory", "CategoryID", categoryId, subCategoryId);

    $("#VendorID").val(vendorId);

    // Load existing specs for this subcategory
    loadExistingSpecs(categoryId, subCategoryId);

    // Existing photo preview
    if (photo) {
      $("#mainPhotoImg").attr("src", photo);
      $("#mainPhotoPreview").show();
      $("#mainPhotoPrompt").hide();
    } else {
      $("#mainPhotoPreview").hide();
      $("#mainPhotoPrompt").show();
    }

    $("#otherPhotosPreviewContainer").empty();
    $("#otherPhotosPrompt").show();

    $("#seedModalLabel").html('<i class="fa fa-edit me-2"></i>Edit Seed Details');
    $("#seedModal").modal("show");
  }

  // Delete Seed Data
  function deleteSeedData(seedId) {
    Swal.fire({
      title: "Delete this Seed?",
      text: "This seed master record will be permanently removed!",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#dc2626",
      cancelButtonColor: "#64748b",
      confirmButtonText: "<i class='fa fa-trash-alt me-1'></i> Yes, delete it!"
    }).then((result) => {
      if (result.isConfirmed) {
        Swal.fire({
          title: "Deleting...",
          allowOutsideClick: false,
          didOpen: () => { Swal.showLoading(); }
        });
        $.ajax({
          type: "POST",
          url: "<?php echo $rooturl; ?>krf-orderandsales/modal/seed_modal.php?action=deleteSeed",
          data: { SeedID: seedId },
          dataType: "JSON",
          success: function (res) {
            if (res.status == 'success') {
              Swal.fire('Deleted!', res.message, 'success').then(() => {
                $("#seed_row_" + seedId).fadeOut(300, function() {
                  $(this).remove();
                  let newTotal = $("#seedTableBody tr").not("#noSeedFoundRow, #noSearchResultRow").length;
                  $("#showingSeedCount").text(newTotal);
                });
              });
            } else {
              Swal.fire('Error', res.message, 'error');
            }
          },
          error: function () {
            Swal.fire('Error', 'Failed to delete seed master record.', 'error');
          }
        });
      }
    });
  }

  // File Upload Previews (Main Photo & Other Photos)
  function previewMainPhoto(input) {
    if (input.files && input.files[0]) {
      var reader = new FileReader();
      reader.onload = function(e) {
        $("#mainPhotoImg").attr("src", e.target.result);
        $("#mainPhotoPrompt").hide();
        $("#mainPhotoPreview").show();
      }
      reader.readAsDataURL(input.files[0]);
    }
  }

  function clearMainPhoto(e) {
    e.stopPropagation();
    $("#uploadMainPhoto").val("");
    $("#mainPhotoImg").attr("src", "");
    $("#mainPhotoPreview").hide();
    $("#mainPhotoPrompt").show();
  }

  function previewOtherPhotos(input) {
    var $container = $("#otherPhotosPreviewContainer");
    $container.empty();
    if (input.files && input.files.length > 0) {
      $("#otherPhotosPrompt").hide();
      Array.from(input.files).forEach(file => {
        var reader = new FileReader();
        reader.onload = function(e) {
          $container.append(`<img src="${e.target.result}" class="preview-thumb-img" title="${file.name}">`);
        }
        reader.readAsDataURL(file);
      });
    } else {
      $("#otherPhotosPrompt").show();
    }
  }

  // Lightbox Image Preview
  function previewSeedImage(url, title) {
    if (!url) return;
    $("#previewSeedTitle").text(title || "Seed Photo");
    $("#lightboxImg").attr("src", url);
    $("#imagePreviewModal").modal("show");
  }

  // Copy to Clipboard Helpers
  function copyText(text, btn) {
    if (!text) return;
    navigator.clipboard.writeText(text).then(function() {
      var $icon = $(btn).find("i");
      $icon.removeClass("fa-copy").addClass("fa-check text-success");
      setTimeout(function() {
        $icon.removeClass("fa-check text-success").addClass("fa-copy");
      }, 1500);
    }).catch(function() {
      var temp = $("<input>");
      $("body").append(temp);
      temp.val(text).select();
      document.execCommand("copy");
      temp.remove();
    });
  }

  function copyModalSKU() {
    var skuVal = $("#suggestedsku").val();
    if (!skuVal) return;
    navigator.clipboard.writeText(skuVal).then(function() {
      var $btn = $(".sku-copy-addon");
      $btn.html('<i class="fa fa-check text-white"></i>').css("background", "#059669");
      setTimeout(function() {
        $btn.html('<i class="fa fa-copy"></i>').css("background", "");
      }, 1500);
    });
  }

  // Export Seed Master Table Data to Excel / CSV
  function exportSeedData(type) {
    let rows = [];
    let headers = ["S.NO.", "SEED NAME", "VARIETY", "CROP", "CATEGORY", "TYPE OF CROP", "BRAND NAME", "COMPANY NAME", "VENDOR", "WEIGHT / PACKET", "SEED / PACKET", "SKU", "GROWING DAYS"];
    rows.push(headers.map(h => `"${h}"`).join(","));

    let $visibleRows = $("#seedTableBody tr:visible").not("#noSeedFoundRow, #noSearchResultRow");
    if ($visibleRows.length === 0) {
      Swal.fire({
        icon: "warning",
        title: "No Data Available",
        text: "There are no seed records to export matching your filter."
      });
      return;
    }

    $visibleRows.each(function (index) {
      let $editBtn = $(this).find(".editseedbtn");
      let crop = $(this).data("crop") || "";
      let rowData = [
        index + 1,
        `"${($editBtn.data("seedname") || "").toString().replace(/"/g, '""')}"`,
        `"${($editBtn.data("variety") || "").toString().replace(/"/g, '""')}"`,
        `"${crop.toString().toUpperCase().replace(/"/g, '""')}"`,
        `"${($editBtn.data("categoryid") || "").toString().replace(/"/g, '""')}"`,
        `"${($editBtn.data("typeofseed") || "").toString().replace(/"/g, '""')}"`,
        `"${($editBtn.data("brand") || "").toString().replace(/"/g, '""')}"`,
        `"${($editBtn.data("company") || "").toString().replace(/"/g, '""')}"`,
        `"${($editBtn.data("vendorid") || "").toString().replace(/"/g, '""')}"`,
        `"${($editBtn.data("weight") || "").toString().replace(/"/g, '""')}"`,
        `"${($editBtn.data("count") || "").toString().replace(/"/g, '""')}"`,
        `"${($editBtn.data("sku") || "").toString().replace(/"/g, '""')}"`,
        `"${($editBtn.data("growingdays") || "").toString().replace(/"/g, '""')}"`
      ];
      rows.push(rowData.join(","));
    });

    let csvContent = "\uFEFF" + rows.join("\r\n");
    let blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
    let url = URL.createObjectURL(blob);
    let link = document.createElement("a");
    let todayStr = new Date().toISOString().slice(0, 10);
    link.setAttribute("href", url);
    link.setAttribute("download", `Seed_Master_Report_${todayStr}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    Swal.fire({
      icon: "success",
      title: "Export Downloaded",
      text: `Exported ${$visibleRows.length} seed record(s) successfully!`,
      timer: 1800,
      showConfirmButton: false
    });
  }
</script>
