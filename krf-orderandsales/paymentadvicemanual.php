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

  if (empty($loginname) && !empty($loginid)) {
    $logininfo = loginuserdetailsfrommaster($authconn, $loginid, "loginid", $loginid);
    if (count($logininfo) > 0) {
      foreach ($logininfo as $lgkey) {
        $loginname = $lgkey['loginname'];
        if (empty($loginroleid)) {
          $loginroleid = $lgkey['loginroleid'];
        }
      }
    }
  }
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
  // print_r($prj);
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
  $hide_heading = true;
  require('../header.php');
  include('functions/orders_functions.php');
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

$user_role_id = intval($loginroleid ?? ($_SESSION['loginroleid'] ?? 99));
if (empty($loginname)) {
  $loginname = $_SESSION['loginname'] ?? '';
}

// get provisiotn order details
$psono = $_GET['psono'] ?? "";
$psodate = "";
$clientname = $_GET['cname'] ?? "";
$mobileno = $_GET['mob'] ?? "";
$emailid = "";
$deliverydate = "";
$psoamount = "";
$clientid = $_GET['clid'] ?? "";

$disp_sono = $_GET['sono'] ?? '';
$disp_dinvoiceno = $_GET['dinvoiceno'] ?? '';
$disp_grand_total = 0;

if (!empty($psono)) {
  $posnoinfo = getProvisionalSalesOrders($krfconn, $companyid, $subcompanyid, $projectid, "psono", $psono);
  if (count($posnoinfo) > 0) {
    $psodate = date('d-M-Y', strtotime($posnoinfo[0]['created_at']));
    if (empty($clientname)) $clientname = $posnoinfo[0]['clientname'];
    if (empty($mobileno)) $mobileno = $posnoinfo[0]['mobileno'];
    if (empty($emailid)) $emailid = $posnoinfo[0]['emailid'];
    if (empty($deliverydate)) $deliverydate = $posnoinfo[0]['expecteddelivery'];
    if (empty($psoamount)) $psoamount = $posnoinfo[0]['total_amount'];
    if (empty($clientid)) $clientid = $posnoinfo[0]['clientid'] ?? '';
  }

  // Check if active dispatched invoice exists in sales_invoice
  $chkSi = $krfconn->query("SELECT invoiceno, payableamount, invoiceamount, status FROM sales_invoice WHERE psono = '" . $krfconn->real_escape_string($psono) . "' AND LOWER(IFNULL(status, '')) != 'cancelled' ORDER BY id DESC LIMIT 1");
  if ($chkSi && $siR = $chkSi->fetch_assoc()) {
    $disp_dinvoiceno = $siR['invoiceno'];
    $disp_grand_total = floatval($siR['payableamount'] > 0 ? $siR['payableamount'] : $siR['invoiceamount']);
  } else {
    // Check in krf_sales_order
    $chkSo = $krfconn->query("SELECT sono, dinvoiceno, total_amount, grand_total, status FROM krf_sales_order WHERE psono = '" . $krfconn->real_escape_string($psono) . "' ORDER BY CASE WHEN invoicetype = 'sale' THEN 1 ELSE 2 END, id DESC LIMIT 1");
    if ($chkSo && $soR = $chkSo->fetch_assoc()) {
      if (empty($disp_sono)) $disp_sono = $soR['sono'] ?? '';
      if (empty($disp_dinvoiceno)) $disp_dinvoiceno = $soR['dinvoiceno'] ?? '';
      $disp_grand_total = floatval($soR['grand_total'] > 0 ? $soR['grand_total'] : $soR['total_amount']);
    }
  }

  if ($disp_grand_total > 0) {
    $psoamount = $disp_grand_total;
  }
}

$has_box_packing = !empty($posnoinfo[0]['has_box_packing']);
$order_box_packing_amt = floatval($posnoinfo[0]['box_packing_amount'] ?? 0);
$order_items_amt = floatval($posnoinfo[0]['items_amount'] ?? 0);
$order_freight_amt = floatval($posnoinfo[0]['freight_amount'] ?? 0);
$pso_calc_total = floatval($posnoinfo[0]['total_amount'] ?? ($order_items_amt + $order_box_packing_amt + $order_freight_amt));
if ($disp_grand_total > 0) {
  if ($order_box_packing_amt > 0 && $disp_grand_total < ($order_items_amt + $order_box_packing_amt)) {
    $order_total_amt = round($order_items_amt + $order_box_packing_amt + $order_freight_amt, 2);
  } else {
    $order_total_amt = $disp_grand_total;
  }
} else {
  $order_total_amt = $pso_calc_total;
}
$order_box_details = $posnoinfo[0]['box_packing_details'] ?? [];

$passed_amt = isset($_GET['amt']) ? trim($_GET['amt']) : (isset($_GET['amount']) ? trim($_GET['amount']) : '');
$default_amount = ($passed_amt !== '' && is_numeric($passed_amt)) ? floatval($passed_amt) : '';
$passed_purpose = $_GET['purpose'] ?? (!empty($psono) ? 'paymentagainstinvoice' : 'advancepayment');
$passed_remarks = $_GET['remarks'] ?? (!empty($psono) ? "Payment against Provisional Order #$psono" . (!empty($disp_dinvoiceno) ? " (Inv: $disp_dinvoiceno)" : '') : '');
$initial_pending_amount = 0;
if ($default_amount !== '' && $default_amount > 0) {
  $initial_pending_amount = floatval($default_amount);
} else if ($disp_grand_total > 0) {
  $pArr = ["'" . $krfconn->real_escape_string($psono) . "'"];
  if (!empty($disp_sono)) $pArr[] = "'" . $krfconn->real_escape_string($disp_sono) . "'";
  if (!empty($disp_dinvoiceno)) $pArr[] = "'" . $krfconn->real_escape_string($disp_dinvoiceno) . "'";
  $pIn = implode(',', array_unique($pArr));
  $totPRes = $krfconn->query("SELECT IFNULL(SUM(advanceamount), 0) AS actual_paid FROM advance_payment_transactions WHERE invoiceno IN ({$pIn}) AND (tedit IS NULL OR tedit = '')");
  $actualPaid = 0;
  if ($totPRes && $tpRow = $totPRes->fetch_assoc()) {
    $actualPaid = floatval($tpRow['actual_paid'] ?? 0);
  }
  $initial_pending_amount = max(0, $order_total_amt - $actualPaid);
  if ($default_amount === '' && $initial_pending_amount > 0) {
    $default_amount = $initial_pending_amount;
  }
} else if (!empty($posnoinfo)) {
  $initial_pending_amount = max(0, floatval($posnoinfo[0]['total_amount'] ?? 0) - floatval($posnoinfo[0]['paid_amount'] ?? 0));
  if ($default_amount === '' && $initial_pending_amount > 0) {
    $default_amount = $initial_pending_amount;
  }
}

// Fetch pending credit notes for client if client info available
$client_creditnotes = [];
$client_creditnotes_count = 0;
if (!empty($clientid) || !empty($clientname) || !empty($mobileno)) {
  $cCond = [];
  if (!empty($clientid)) $cCond[] = "clientid = '" . $krfconn->real_escape_string($clientid) . "'";
  if (!empty($clientname)) $cCond[] = "LOWER(clientname) = LOWER('" . $krfconn->real_escape_string($clientname) . "')";
  if (empty($clientid) && !empty($mobileno)) {
    $stCl = $krfconn->query("SELECT clientid FROM krf_provisional_sales_order WHERE mobileno = '" . $krfconn->real_escape_string($mobileno) . "' LIMIT 1");
    if ($stCl && $rCl = $stCl->fetch_assoc()) {
      if (!empty($rCl['clientid'])) $cCond[] = "clientid = '" . $krfconn->real_escape_string($rCl['clientid']) . "'";
    }
  }
  if (!empty($cCond)) {
    $cnRes = $krfconn->query("SELECT creditnote_id, creditnote_no, creditnote_date, projectid, companyid, subcompanyid, clientid, clientname, psono, sono, stockorder_no, product_name, amount_paid, credit_amount, narration, reason, status, created_by, created_at FROM krf_creditnote WHERE status = 'Active' AND (tedit IS NULL OR tedit = '') AND credit_amount > 0 AND (" . implode(" OR ", array_unique($cCond)) . ") ORDER BY creditnote_id DESC");
    if ($cnRes) {
      while ($cnR = $cnRes->fetch_assoc()) {
        $amt = floatval($cnR['credit_amount'] ?? 0);
        $client_creditnotes[] = [
          'creditnote_id' => $cnR['creditnote_id'],
          'creditnote_no' => $cnR['creditnote_no'],
          'creditnote_date' => $cnR['creditnote_date'],
          'formatted_date' => date('d-M-Y', strtotime($cnR['creditnote_date'])),
          'clientid' => $cnR['clientid'],
          'clientname' => $cnR['clientname'],
          'psono' => $cnR['psono'] ?? '',
          'sono' => $cnR['sono'] ?? '',
          'credit_amount' => $amt,
          'formatted_amount' => number_format($amt, 2),
          'reason' => !empty($cnR['reason']) ? $cnR['reason'] : ($cnR['narration'] ?? 'Credit Adjustment'),
          'created_by' => !empty($cnR['created_by']) ? $cnR['created_by'] : 'Admin',
          'created_at' => $cnR['created_at'],
          'formatted_created_at' => date('d-M-Y H:i', strtotime($cnR['created_at']))
        ];
      }
    }
    $client_creditnotes_count = count($client_creditnotes);
  }
}
?>

<!-- Load Premium Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<!-- Tesseract.js is loaded dynamically on-demand only when OCR is performed -->

<script>
  if (window.self !== window.top) {
    document.documentElement.classList.add('is-iframe');
    if (document.body) document.body.classList.add('is-iframe');
  }
</script>

<style>
  html.is-iframe .navbar,
  html.is-iframe nav.navbar,
  html.is-iframe .navbar-premium,
  html.is-iframe .company-header-strip,
  body.is-iframe .navbar,
  body.is-iframe nav.navbar,
  body.is-iframe .navbar-premium,
  body.is-iframe .company-header-strip,
  .is-iframe .navbar,
  .is-iframe nav.navbar,
  .is-iframe .navbar-premium,
  .is-iframe .company-header-strip,
  .is-iframe #backToProvisionalBtn,
  .is-iframe .payment-card > .card-header {
    display: none !important;
  }

  body.is-iframe {
    background: #f8fafc !important;
    min-height: 100vh !important;
    padding: 0 !important;
    margin: 0 !important;
  }
  .is-iframe .main-container {
    padding: 8px 12px !important;
  }
  .is-iframe .payment-card {
    border: none !important;
    box-shadow: none !important;
  }

  :root {
    --primary: #2563eb;
    --primary-dark: #1e40af;
    --secondary: #64748b;
    --accent: #0ea5e9;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --glass-bg: rgba(255, 255, 255, 0.96);
    --glass-border: rgba(255, 255, 255, 0.3);
    --card-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
  }

  body {
    background: radial-gradient(circle at top right, #e0e7ff, #f8fafc);
    background-attachment: fixed;
    font-family: 'Inter', sans-serif;
    color: #1e293b;
    margin: 0;
    min-height: 100vh;
  }

  .main-container {
    width: 100% !important;
    max-width: 100% !important;
    padding: 6px 12px !important;
    margin: 0 !important;
    animation: fadeIn 0.4s ease-out;
  }

  /* Glassmorphism Card */
  .payment-card {
    background: var(--glass-bg);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid var(--glass-border);
    border-radius: 12px;
    box-shadow: var(--card-shadow);
    overflow: hidden;
    width: 100%;
  }

  .card-header {
    background: linear-gradient(135deg, #1e3a8a, #2563eb);
    padding: 6px 16px !important;
    border: none;
    position: relative;
  }

  .card-header h4 {
    font-family: 'Outfit', sans-serif;
    color: #fff;
    font-weight: 700;
    font-size: 1.1rem;
    margin: 0;
    letter-spacing: -0.01em;
  }

  .card-header p {
    color: rgba(255, 255, 255, 0.85);
    margin: 0;
    font-size: 0.78rem;
  }

  .card-body {
    padding: 10px 14px !important;
  }

  /* Section Styling */
  .form-section {
    background: #f8fafc;
    border-radius: 8px;
    padding: 6px 10px;
    margin-bottom: 6px;
    border: 1px solid #e2e8f0;
    transition: all 0.2s ease;
  }

  .form-section:hover {
    border-color: #cbd5e1;
  }

  .section-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
    padding-bottom: 3px;
    border-bottom: 1px solid #e2e8f0;
  }

  .section-icon {
    width: 22px;
    height: 22px;
    background: #eff6ff;
    color: var(--primary);
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.78rem;
    flex-shrink: 0;
  }

  .section-title {
    font-family: 'Outfit', sans-serif;
    font-size: 0.88rem;
    font-weight: 600;
    color: #334155;
    text-transform: none;
    letter-spacing: -0.01em;
  }

  /* Input Styling */
  .form-label {
    font-weight: 600;
    font-size: 0.76rem;
    color: #475569;
    margin-bottom: 2px;
  }

  .form-control {
    border-radius: 6px;
    border: 1px solid #cbd5e1;
    padding: 4px 8px;
    font-size: 0.84rem;
    background: #fff;
    transition: all 0.2s;
    color: #1e293b;
    height: 32px;
  }

  textarea.form-control {
    height: auto;
    min-height: 44px;
  }

  .form-control:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    outline: none;
  }

  .input-group-text {
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    border-radius: 6px 0 0 6px;
    color: #64748b;
    font-weight: 600;
    padding: 4px 8px;
    font-size: 0.82rem;
  }

  .form-control.border-start-0 {
    border-radius: 0 6px 6px 0;
  }

  /* Custom Radio Buttons */
  .custom-radio-group {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
    gap: 6px;
  }

  .radio-check { display: none; }

  .radio-label {
    cursor: pointer;
    background: #fff;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    padding: 4px 6px;
    text-align: center;
    transition: all 0.2s;
    display: flex;
    flex-direction: row;
    justify-content: center;
    align-items: center;
    gap: 6px;
    margin-bottom: 0;
  }

  .radio-label i {
    font-size: 0.85rem;
    color: #64748b;
    transition: color 0.2s;
  }

  .radio-label span {
    font-weight: 600;
    font-size: 0.8rem;
    color: #475569;
  }

  .radio-check:checked + .radio-label {
    border-color: var(--primary);
    background: #eff6ff;
    box-shadow: 0 2px 4px rgba(37, 99, 235, 0.12);
  }

  .radio-check:checked + .radio-label i {
    color: var(--primary);
  }

  .radio-check:checked + .radio-label span {
    color: var(--primary);
  }

  /* Credit Note Styling */
  .text-purple { color: #8b5cf6 !important; }
  .bg-purple { background-color: #8b5cf6 !important; }
  .border-purple { border-color: #8b5cf6 !important; }
  #src_creditnote:checked + .radio-label {
    border-color: #8b5cf6 !important;
    background: #f5f3ff !important;
    box-shadow: 0 2px 5px rgba(139, 92, 246, 0.2) !important;
  }
  #src_creditnote:checked + .radio-label i,
  #src_creditnote:checked + .radio-label span {
    color: #7c3aed !important;
  }
  .cn-table-row {
    cursor: pointer;
    transition: background-color 0.15s ease;
  }
  .cn-table-row:hover {
    background-color: #f5f3ff !important;
  }
  .cn-table-row.table-active {
    background-color: #ede9fe !important;
  }
  .cn-table-row.table-active td {
    background-color: #ede9fe !important;
  }

  /* Buttons */
  .btn-primary {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    border: none;
    border-radius: 8px;
    padding: 7px 24px;
    font-weight: 600;
    font-size: 0.88rem;
    font-family: 'Outfit', sans-serif;
    letter-spacing: 0.01em;
    transition: all 0.2s;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.2);
  }

  .btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
  }

  .btn-back {
    background: #fff;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    color: #475569;
    font-weight: 500;
    padding: 4px 10px;
    font-size: 0.8rem;
    transition: all 0.2s;
  }

  .btn-back:hover {
    background: #f1f5f9;
    color: #0f172a;
    border-color: #94a3b8;
  }

  .btn-secondary {
    background: #64748b;
    border: none;
    border-radius: 8px;
    padding: 7px 20px;
    font-weight: 600;
    font-size: 0.88rem;
    font-family: 'Outfit', sans-serif;
    color: #fff;
    transition: all 0.2s;
  }

  .btn-secondary:hover {
    background: #475569;
    color: #fff;
    transform: translateY(-1px);
  }

  /* Utility */
  .badge-premium {
    background: #fef3c7;
    color: #92400e;
    padding: 3px 8px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.75rem;
  }

  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(6px); }
    to { opacity: 1; transform: translateY(0); }
  }

  /* Attachment & Preview Styling */
  .attachment-upload-box {
    background: #ffffff;
    border: 1px dashed #cbd5e1;
    border-radius: 8px;
    padding: 6px 10px;
    transition: all 0.2s ease;
  }
  .attachment-upload-box:hover {
    border-color: var(--primary);
    background: #f8fafc;
  }
  .attachment-preview-wrapper {
    animation: fadeIn 0.2s ease-out;
  }
  .preview-card-inner {
    border-radius: 6px;
    overflow: hidden;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    transition: all 0.2s ease;
  }
  .preview-thumbnail-container {
    position: relative;
    background: #f1f5f9;
    min-height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    border-radius: 6px;
    cursor: pointer;
  }
  .preview-thumbnail-img {
    max-height: 95px;
    max-width: 100%;
    object-fit: contain;
    border-radius: 4px;
    transition: transform 0.2s ease;
  }
  .preview-thumbnail-container:hover .preview-thumbnail-img {
    transform: scale(1.02);
  }
  .preview-overlay-btn {
    position: absolute;
    bottom: 4px;
    right: 4px;
    background: rgba(15, 23, 42, 0.75);
    color: #fff;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.68rem;
  }
  #attachmentLargeModal .modal-content {
    border-radius: 12px;
    overflow: hidden;
  }
  #attachmentLargeModal .modal-header {
    background: #0f172a;
    border-bottom: 1px solid #334155;
    padding: 8px 14px;
  }
  #attachmentLargeModal .modal-body {
    background: #020617;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 12px;
    min-height: 60vh;
  }

  @media (max-width: 768px) {
    .main-container { padding: 4px 6px !important; }
    .card-body { padding: 8px !important; }
    .form-section { padding: 6px 8px; }
    .custom-radio-group { grid-template-columns: 1fr; }
  }
</style>

<div class="main-container">
  <div class="card payment-card">
    <div class="card-header d-flex justify-content-between align-items-center py-2 px-3">
      <h4 class="d-flex align-items-center mb-0">
        <i class="fa fa-receipt mr-2 text-warning"></i>Payment Advice Manual
      </h4>
      <p class="mb-0 text-white-50 small">Securely record and verify client payments</p>
    </div>

    <div class="card-body">
      <form id="advancePaymentForm" method="POST" enctype="multipart/form-data">

        <!-- 2-Column Responsive Dashboard Layout -->
        <div class="row g-2">
          <!-- Left Column (6 cols): Client Info, Purpose, Booking Details, Source -->
          <div class="col-lg-6 col-md-12" id="leftDashboardCol">

            <!-- Client Details Section -->
            <div class="form-section">
              <div class="section-header">
                <div class="section-icon"><i class="fa fa-user-tie"></i></div>
                <div class="section-title">Client Information</div>
              </div>
              
              <div class="row g-2">
                <div class="col-md-6 mb-1">
                  <label class="form-label" for="clientmobileno">Mobile Number</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-phone"></i></span>
                    <input type="tel" id="clientmobileno" name="clientmobileno" class="form-control border-start-0"
                      placeholder="10-digit mobile" maxlength="10" pattern="[0-9]{10}" autocomplete="off"
                      onkeypress="return isNumberKey(event)" required value="<?php echo htmlspecialchars($mobileno); ?>">
                  </div>
                </div>

                <div class="col-md-6 mb-1">
                  <label class="form-label" for="clientname">Client Name <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-user"></i></span>
                    <input class="form-control border-start-0" id="clientname" name="clientname" placeholder="Client name" required value="<?php echo htmlspecialchars($clientname); ?>">
                  </div>
                </div>
              </div>

              <div id="existclientdata" class="col-12 p-0"></div>
            </div>

            <!-- Payment Purpose Section -->
            <div class="form-section">
              <div class="section-header">
                <div class="section-icon"><i class="fa fa-bullseye"></i></div>
                <div class="section-title">Payment Purpose</div>
              </div>
              
              <div class="custom-radio-group">
                <input type="radio" id="purpose_advance" name="purposeofamount" class="radio-check" value="advancepayment" <?php echo ($passed_purpose !== 'paymentagainstinvoice') ? 'checked' : ''; ?>>
                <label for="purpose_advance" class="radio-label">
                  <i class="fa fa-hand-holding-usd text-success"></i>
                  <span>Advance Payment</span>
                </label>

                <input type="radio" id="purpose_invoice" name="purposeofamount" class="radio-check" value="paymentagainstinvoice" <?php echo ($passed_purpose === 'paymentagainstinvoice') ? 'checked' : ''; ?>>
                <label for="purpose_invoice" class="radio-label">
                  <i class="fa fa-file-invoice text-primary"></i>
                  <span>Against Invoice</span>
                </label>
              </div>

              <div class="mt-2 <?php echo ($passed_purpose === 'paymentagainstinvoice') ? '' : 'd-none'; ?>" id="prvordnodiv">
                <label class="form-label">Provisional Order No</label>
                <input type="text" class="form-control" id="invoiceno" name="invoiceno"
                  placeholder="Enter Order No (e.g., ORD-2023-001)" value="<?php echo htmlspecialchars($psono); ?>">
              </div>
            </div>

            <!-- Manual Booking Details Section (Only visible for Advance Payment) -->
            <div class="form-section" id="manualBookingSection">
              <div class="section-header">
                <div class="section-icon"><i class="fa fa-book"></i></div>
                <div class="section-title">Manual Booking Details</div>
              </div>
              
              <div class="row g-2">
                <div class="col-md-6 mb-1">
                  <label class="form-label fw-bold" for="bookingno">Manual Booking No <span class="text-muted fw-normal">(Optional)</span></label>
                  <div class="input-group">
                    <span class="input-group-text bg-light"><i class="fa fa-hashtag text-primary"></i></span>
                    <input type="text" class="form-control border-start-0 font-monospace" id="bookingno" name="bookingno" placeholder="Enter Booking No" autocomplete="off">
                  </div>
                </div>

                <div class="col-md-6 mb-1">
                  <label class="form-label fw-bold" for="bookingreceipt">Manual Booking File <span class="text-muted fw-normal">(Optional)</span></label>
                  <div class="input-group">
                    <span class="input-group-text bg-light"><i class="fa fa-paperclip text-primary"></i></span>
                    <input type="file" class="form-control border-start-0" id="bookingreceipt" name="bookingreceipt" accept="image/*,.pdf" style="font-size: 0.78rem;">
                  </div>
                </div>
              </div>
            </div>

            <!-- Source of Payment Section -->
            <div class="form-section">
              <div class="section-header">
                <div class="section-icon"><i class="fa fa-wallet"></i></div>
                <div class="section-title">Source of Payment</div>
              </div>
              
              <div class="custom-radio-group mb-2">
                <?php //if ($user_role_id <= 3): ?>
                <input type="radio" id="src_cash" name="sourceofpayment" class="radio-check" value="cash" checked>
                <label for="src_cash" class="radio-label"><i class="fa fa-money-bill-wave text-success"></i><span>Cash</span></label>
                <?php //endif; ?>
                
                <input type="radio" id="src_cheque" name="sourceofpayment" class="radio-check" value="cheque">
                <label for="src_cheque" class="radio-label"><i class="fa fa-money-check text-warning"></i><span>Cheque</span></label>
                
                <input type="radio" id="src_upi" name="sourceofpayment" class="radio-check" value="upi" <?php echo ($user_role_id > 3) ? 'checked' : ''; ?>>
                <label for="src_upi" class="radio-label"><i class="fa fa-mobile-alt text-info"></i><span>UPI / Bank</span></label>

                <input type="radio" id="src_creditnote" name="sourceofpayment" class="radio-check" value="creditnote">
                <label for="src_creditnote" class="radio-label <?php echo ($client_creditnotes_count > 0 ? '' : 'd-none'); ?>" id="lbl_src_creditnote" title="Pay using available Credit Note">
                  <i class="fa fa-receipt text-purple" style="color: #8b5cf6 !important;"></i>
                  <span>Credit Note</span>
                  <span class="badge bg-danger text-white rounded-pill ms-1 px-1.5" id="cn_pending_badge" style="font-size: 0.68rem; <?php echo ($client_creditnotes_count > 0 ? '' : 'display: none;'); ?>"><?php echo $client_creditnotes_count; ?></span>
                </label>
              </div>

              <input type="hidden" name="creditnote_id" id="creditnote_id" value="">
              <input type="hidden" name="creditnote_no" id="creditnote_no" value="">
              <input type="hidden" name="creditnote_amount" id="creditnote_amount" value="">

              <!-- Credit Note Selection Grid (Shown only when Source of Payment is Credit Note) -->
              <div id="creditNoteSelectionSection" class="col-12 mt-2 p-2.5 rounded border d-none" style="background: #faf5ff; border-color: #d8b4fe !important; border-left: 4px solid #9333ea !important;">
                <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom" style="border-color: #e9d5ff !important;">
                  <span class="small font-weight-bold text-dark d-flex align-items-center">
                    <i class="fa fa-receipt mr-1.5" style="color: #9333ea;"></i> Available Credit Notes for Client
                  </span>
                  <span class="badge px-2 py-1 text-white" id="cn_total_available_badge" style="background-color: #9333ea; font-size: 0.75rem;">₹0.00 Total</span>
                </div>
                
                <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                  <table class="table table-sm table-hover table-bordered mb-0 bg-white" id="creditNotesTable" style="font-size: 0.78rem;">
                    <thead class="table-light" style="position: sticky; top: 0; background: #f3e8ff; z-index: 1;">
                      <tr>
                        <th class="text-center py-1" style="width: 45px;">Select</th>
                        <th class="py-1">Credit Note #</th>
                        <th class="py-1">Date</th>
                        <th class="text-end py-1">Amount</th>
                        <th class="py-1">Origin Order</th>
                        <th class="py-1">Created By</th>
                        <th class="py-1">Created At</th>
                        <th class="py-1">Reason</th>
                      </tr>
                    </thead>
                    <tbody id="creditNotesTableBody">
                    </tbody>
                  </table>
                </div>
                <div id="noCreditNotesMsg" class="text-center text-muted py-3 d-none">
                  <i class="fa fa-info-circle mr-1"></i> No active credit notes available for this client.
                </div>
                <div class="mt-2 text-muted small" style="font-size: 0.72rem;">
                  <i class="fa fa-info-circle text-primary mr-1"></i> Selecting a credit note applies the credited amount directly to confirm and fulfill the provisional order without requiring physical receipts or bank attachments.
                </div>
              </div>

              <!-- Cash Fields -->
              <div class="col-12 casgreceuvedvtdiv row mx-0 px-0 g-2">
                <div class="col-md-6 mb-1 px-1">
                  <label class="form-label">Received By</label>
                  <input type="text" class="form-control" id="casgreceivedby" name="casgreceivedby" placeholder="Person Name" autocomplete="off">
                </div>
                <div class="col-md-6 mb-1 px-1">
                  <label class="form-label">Location</label>
                  <input type="text" class="form-control" id="casgreceuvelocation" name="casgreceuvelocation" placeholder="Place of receipt" autocomplete="off">
                </div>
              </div>
            </div>

          </div>

          <!-- Right Column (6 cols): Payment Details & Proof Upload -->
          <div class="col-lg-6 col-md-12 d-flex flex-column" id="paymentAttachmentDetailsPanel">
            <div class="form-section flex-grow-1 d-flex flex-column justify-content-between mb-0">
              <div>
                <div class="section-header">
                  <div class="section-icon"><i class="fa fa-coins"></i></div>
                  <div class="section-title">Payment & Attachment Details</div>
                </div>

                <!-- Client Pending Balances & Orders Summary Widget -->
                <div id="clientOrdersSummaryWidget" class="mb-2 p-2 rounded border bg-light d-none" style="background: linear-gradient(135deg, #f8fafc 0%, #eef2f7 100%); border-left: 4px solid #1e3c72 !important;">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="small font-weight-bold text-dark"><i class="fa fa-info-circle text-primary mr-1"></i> Client Orders & Pending Summary</span>
                    <span class="badge badge-pill badge-danger px-2 py-1" id="totalClientPendingBadge" style="font-size: 0.78rem;">Total Pending: ₹0.00</span>
                  </div>
                  <div class="d-flex flex-wrap gap-2 mt-1">
                    <!-- Button for Draft / Un-Confirmed Orders -->
                    <button type="button" class="btn btn-sm btn-outline-warning text-dark flex-grow-1 d-flex justify-content-between align-items-center py-1 px-2 rounded shadow-xs" id="btnViewDraftOrdersModal" style="background: #fff9e6; border-color: #ffe082; font-size: 0.78rem;">
                      <span class="d-flex align-items-center">
                        <i class="fa fa-file-alt text-warning mr-1"></i>
                        <strong id="draftOrdersCountText">0 Draft / Un-Confirm</strong>
                      </span>
                      <span class="badge badge-warning text-dark ml-1 font-weight-bold" id="draftOrdersPendingText">₹0.00 Pending</span>
                    </button>

                    <!-- Button for Confirmed Orders -->
                    <button type="button" class="btn btn-sm btn-outline-success text-dark flex-grow-1 d-flex justify-content-between align-items-center py-1 px-2 rounded shadow-xs" id="btnViewConfirmedOrdersModal" style="background: #e8f5e9; border-color: #a5d6a7; font-size: 0.78rem;">
                      <span class="d-flex align-items-center">
                        <i class="fa fa-check-circle text-success mr-1"></i>
                        <strong id="confirmedOrdersCountText">0 Confirmed</strong>
                      </span>
                      <span class="badge badge-success ml-1 font-weight-bold" id="confirmedOrdersPendingText">₹0.00 Pending</span>
                    </button>
                  </div>
                </div>
                
                <div class="row g-2">
                  <!-- Left Sub-column: Amount, Date, Bank, Remarks -->
                  <div class="col-sm-6">
                    <!-- Special Discount Input (Shown when amount < pending amount or when discount applied) -->
                    <div id="special_discount_wrapper" class="mb-2 p-2 rounded border" style="display: none; background: #fffdf5; border-color: #fde68a !important; border-left: 4px solid #f59e0b !important;">
                      <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label fw-bold text-dark mb-0 small" for="special_discount">
                          <i class="fa fa-tag text-warning me-1"></i> Special Discount (₹)
                        </label>
                        <div class="d-flex align-items-center gap-1">
                          <span class="badge bg-warning text-dark font-weight-bold" id="special_discount_percent_badge" style="font-size: 0.70rem; display: none;"></span>
                          <button type="button" class="btn btn-sm btn-link text-muted p-0 text-decoration-none" id="btnClearSpecialDiscount" title="Clear Discount" style="font-size: 0.72rem; line-height: 1;">
                            <i class="fa fa-times-circle text-danger"></i>
                          </button>
                        </div>
                      </div>
                      <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white text-warning fw-bold">₹</span>
                        <input type="number" class="form-control font-weight-bold text-danger border-start-0" id="special_discount" name="special_discount" step="0.01" min="0" placeholder="0.00" value="0.00">
                      </div>
                      <div class="d-flex justify-content-between align-items-center mt-1">
                        <small class="text-muted" id="special_discount_summary_text" style="font-size: 0.71rem;">
                          <i class="fa fa-info-circle text-primary me-1"></i> Deducted from pending balance and saved as other discount.
                        </small>
                      </div>
                    </div>
                    <input type="hidden" id="otherdiscount" name="otherdiscount" value="0.00">

                    <!-- Received Amount Input -->
                    <div class="mb-2" id="amount_input_wrapper">
                      <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label fw-bold mb-0">
                          <b class="prefixamount"><?php echo ($passed_purpose === 'paymentagainstinvoice') ? 'Invoice Against Amount' : 'Advance Amount'; ?></b> <span class="text-danger">*</span>
                        </label>
                        <a href="javascript:void(0)" id="btnToggleSpecialDiscount" class="text-primary small text-decoration-none" style="font-size: 0.74rem;" title="Apply Special Discount">
                          <i class="fa fa-tag me-1"></i> Special Discount
                        </a>
                      </div>
                      <div class="input-group">
                        <span class="input-group-text bg-light fw-bold text-success">₹</span>
                        <input type="number" class="form-control border-start-0 font-weight-bold text-success" id="amount" name="amount" step="0.01" placeholder="0.00" value="<?php echo ($default_amount !== '' && $default_amount > 0) ? number_format($default_amount, 2, '.', '') : ''; ?>" required>
                      </div>
                      <small class="text-muted d-block mt-0.5" id="amount_balance_hint" style="font-size: 0.71rem; display: none;"></small>

                      <!-- Box Packing Charge Notice & Quick Presets -->
                      <div id="boxPackingChargeNotice" class="mt-2 p-2 rounded border <?php echo ($order_box_packing_amt > 0) ? '' : 'd-none'; ?>" style="background: linear-gradient(135deg, #fffdf0 0%, #fef9c3 100%); border-color: #fde047 !important; border-left: 4px solid #eab308 !important;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                          <span class="small font-weight-bold text-dark" style="font-size: 0.78rem;">
                            <i class="fa fa-box-open text-warning me-1"></i> Box Packing Charge: 
                            <b class="text-dark">₹<span id="bpc_display_boxamt"><?php echo number_format($order_box_packing_amt, 2, '.', ''); ?></span></b>
                          </span>
                          <span class="badge bg-warning text-dark font-weight-bold" id="bpc_display_boxinfo" style="font-size: 0.68rem; <?php echo (!empty($order_box_details['noofbox'])) ? '' : 'display: none;'; ?>">
                            <?php echo ($order_box_details['noofbox'] ?? 1); ?> Box<?php echo (!empty($order_box_details['chargesperbox'])) ? ' @ ₹' . number_format($order_box_details['chargesperbox'], 2, '.', '') : ''; ?><?php echo (!empty($order_box_details['pickuppoint'])) ? ' (' . htmlspecialchars($order_box_details['pickuppoint']) . ')' : ''; ?>
                          </span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-1 text-muted" style="font-size: 0.72rem;">
                          <span>Plant Items: <b class="text-dark">₹<span id="bpc_display_plantamt"><?php echo number_format($order_items_amt, 2, '.', ''); ?></span></b></span>
                          <span>+ Box: <b class="text-warning">₹<span id="bpc_display_boxamt_sub"><?php echo number_format($order_box_packing_amt, 2, '.', ''); ?></span></b></span>
                          <span>= Total: <b class="text-primary">₹<span id="bpc_display_totalamt"><?php echo number_format($order_total_amt, 2, '.', ''); ?></span></b></span>
                        </div>

                        <div class="d-flex flex-wrap gap-1 mt-1 pt-1 border-top" style="border-color: rgba(234, 179, 8, 0.3) !important;">
                          <button type="button" class="btn btn-xs <?php echo ($default_amount >= $order_total_amt && $order_total_amt > 0) ? 'btn-success text-white active' : 'btn-outline-success'; ?> bpc-preset-btn flex-grow-1" data-type="full" data-amt="<?php echo number_format($order_total_amt, 2, '.', ''); ?>" style="font-size: 0.72rem; padding: 2px 6px;">
                            <i class="fa fa-check-circle me-1"></i> Full (₹<span class="bpc-btn-full-amt"><?php echo number_format($order_total_amt, 2, '.', ''); ?></span>)
                          </button>
                          <button type="button" class="btn btn-xs <?php echo ($default_amount == $order_items_amt && $order_items_amt > 0) ? 'btn-primary text-white active' : 'btn-outline-primary'; ?> bpc-preset-btn flex-grow-1" data-type="plant" data-amt="<?php echo number_format($order_items_amt, 2, '.', ''); ?>" style="font-size: 0.72rem; padding: 2px 6px;">
                            <i class="fa fa-seedling me-1"></i> Plant Only (₹<span class="bpc-btn-plant-amt"><?php echo number_format($order_items_amt, 2, '.', ''); ?></span>)
                          </button>
                          <button type="button" class="btn btn-xs <?php echo ($default_amount == $order_box_packing_amt && $order_box_packing_amt > 0) ? 'btn-warning text-dark active' : 'btn-outline-warning text-dark'; ?> bpc-preset-btn flex-grow-1" data-type="box" data-amt="<?php echo number_format($order_box_packing_amt, 2, '.', ''); ?>" style="font-size: 0.72rem; padding: 2px 6px;">
                            <i class="fa fa-box me-1"></i> Box Only (₹<span class="bpc-btn-box-amt"><?php echo number_format($order_box_packing_amt, 2, '.', ''); ?></span>)
                          </button>
                        </div>
                      </div>
                    </div>

                    <div id="refrensenodiv" class="mb-2 d-none">
                      <label class="form-label fw-semibold">UTR / Transaction No <span class="text-danger" id="utr_required_star">*</span></label>
                      <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fa fa-fingerprint text-primary"></i></span>
                        <input type="text" class="form-control border-start-0 font-monospace" id="utrnumber" name="utrnumber" placeholder="UTR / Ref Number">
                      </div>
                    </div>

                    <div id="receivedbankSection" class="mb-2 d-none">
                      <label class="form-label fw-semibold">Deposited To Bank Account <span class="text-danger">*</span></label>
                      <select class="form-control form-select" id="receivedtoaccountid" name="receivedtoaccountid" style="height: 32px; padding: 4px 8px; font-size: 0.84rem;">
                        <option value="">Select Bank</option>
                      </select>
                    </div>

                    <div class="mb-2">
                      <label class="form-label fw-semibold">Payment Date <span class="text-danger">*</span></label>
                      <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fa fa-calendar-alt text-primary"></i></span>
                        <input type="date" class="form-control border-start-0" id="paymentdate" name="paymentdate" value="<?php echo date('Y-m-d'); ?>" required>
                      </div>
                    </div>

                    <div class="mb-1">
                      <label class="form-label fw-semibold"><i class="fa fa-comment-alt text-muted mr-1"></i> Remarks</label>
                      <textarea class="form-control" id="remarks" name="remarks" rows="2" placeholder="Any additional notes or remarks..."><?php echo htmlspecialchars($passed_remarks); ?></textarea>
                    </div>
                  </div>

                  <!-- Right Sub-column: Attachment Upload & Live Preview -->
                  <div class="col-sm-6 d-flex flex-column">
                    <div id="proofofpaymentdiv" class="attachment-upload-box flex-grow-1 d-flex flex-column justify-content-between p-2">
                      <div>
                        <div class="d-flex align-items-center justify-content-between mb-1">
                          <label class="form-label fw-bold mb-0 text-dark" for="paymentreceipt">
                            <i class="fa fa-paperclip text-primary mr-1"></i> Proof of Payment <span class="text-danger">*</span>
                          </label>
                          <span class="badge bg-light text-muted border px-1" style="font-size: 0.68rem;">JPG, PNG, PDF</span>
                        </div>

                        <div class="mb-1">
                          <input type="file" class="form-control p-1" id="paymentreceipt" name="paymentreceipt" accept="image/*,.pdf" style="font-size: 0.78rem;" required>
                        </div>

                        <div class="text-muted small d-flex align-items-center mb-1" style="font-size: 0.72rem;">
                          <i class="fa fa-robot text-primary mr-1"></i>
                          <span>AI scans receipt for UTR/Amount.</span>
                        </div>
                      </div>

                      <!-- Live Preview Card (Below upload input) -->
                      <div id="attachmentPreviewWrapper" class="attachment-preview-wrapper d-none mt-1">
                        <div class="preview-card-inner p-1.5 rounded border bg-white shadow-xs">
                          
                          <!-- File Info Header with Reset and Open Fullscreen Buttons -->
                          <div class="d-flex align-items-center justify-content-between pb-1 mb-1 border-bottom">
                            <div class="d-flex align-items-center text-truncate mr-1">
                              <i id="previewFileIcon" class="fa fa-file-image text-primary mr-1" style="font-size: 0.85rem;"></i>
                              <div>
                                <div id="previewFileName" class="fw-bold text-dark text-truncate small" style="max-width: 90px; font-size: 0.74rem;">Receipt.jpg</div>
                                <div id="previewFileSize" class="text-muted" style="font-size: 0.65rem;">0 KB</div>
                              </div>
                            </div>

                            <div class="d-flex align-items-center">
                              <button type="button" id="btnOpenLargeScreen" class="btn btn-sm btn-outline-primary py-0 px-1 mr-1" style="font-size: 0.7rem;" title="Fullscreen">
                                <i class="fa fa-expand"></i>
                              </button>
                              <button type="button" id="btnResetAttachment" class="btn btn-sm btn-outline-danger py-0 px-1" style="font-size: 0.7rem;" title="Reset">
                                <i class="fa fa-trash-alt"></i>
                              </button>
                            </div>
                          </div>

                          <!-- Image Thumbnail Preview Container -->
                          <div id="imagePreviewContainer" class="preview-thumbnail-container d-none position-relative bg-light rounded text-center p-1" style="min-height: 75px; cursor: pointer;">
                            <img id="imagePreviewImg" src="" class="preview-thumbnail-img img-fluid rounded" alt="Preview" style="max-height: 90px; object-fit: contain;">
                            <div class="preview-overlay-btn position-absolute bottom-0 end-0 m-1 badge bg-dark bg-opacity-75 text-white py-0 px-1" style="font-size: 0.65rem;">
                              <i class="fa fa-search-plus"></i> Zoom
                            </div>
                          </div>

                          <!-- PDF Preview Box -->
                          <div id="pdfPreviewBox" class="p-2 text-center bg-danger bg-opacity-10 border border-danger-subtle rounded d-none">
                            <i class="fa fa-file-pdf text-danger fa-2x mb-1"></i>
                            <div id="pdfFileNameDisplay" class="fw-bold text-dark small text-truncate mb-1" style="font-size: 0.74rem;">doc.pdf</div>
                            <button type="button" id="btnOpenPdfModal" class="btn btn-danger btn-sm py-0 px-2 rounded-pill" style="font-size: 0.72rem;">
                              <i class="fa fa-external-link-alt mr-1"></i> Open PDF
                            </button>
                          </div>

                        </div>
                      </div>

                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <input type="hidden" name="companyid" value="<?= $companyid ?>">
        <input type="hidden" name="subcompanyid" value="<?= $subcompanyid ?>">
        <input type="hidden" name="projectid" value="<?= $projectid ?>">
        <input type="hidden" name="loginid" value="<?= $loginid ?>">
        <input type="hidden" name="loginname" value="<?= $loginname ?>">
        <input type="hidden" name="clientid" id="clientid" value="<?php echo htmlspecialchars($clientid); ?>">

        <!-- Compact Bottom Button Strip -->
        <div class="text-center mt-2 pt-2 border-top d-flex justify-content-center align-items-center gap-2">
          <a href="provisionalsaleorder_review.php?k=<?php echo urlencode($key ?? ''); ?>&mdid=<?php echo urlencode($mdid ?? ''); ?><?php echo isset($_GET['dz']) ? '&dz=' . urlencode($_GET['dz']) : ''; ?>" class="btn btn-secondary mr-2" id="backToProvisionalBtn">
            <i class="fa fa-arrow-left mr-1"></i> Back
          </a>
          <button type="button" class="btn btn-primary mr-2" id="fetchthedata" onclick="getProvisionalOrderhtmlwithModal();">
            <i class="fa fa-search mr-1"></i> Fetch Data
          </button>
          <span class="text-danger small" id="returnmsgdiv"></span>
          <button type="submit" id="savepaymentdatabtn" class="btn btn-primary px-4 py-1.5" style="display:none;">
            <i class="fa fa-check-circle mr-1"></i> Save Payment Info
          </button>
        </div>

      </form>
    </div>
  </div>
</div>

<!-- Large Screen Attachment Preview Modal -->
<div class="modal fade" id="attachmentLargeModal" tabindex="-1" role="dialog" aria-labelledby="attachmentLargeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
    <div class="modal-content shadow-lg">
      <div class="modal-header py-3 px-4 d-flex justify-content-between align-items-center">
        <h5 class="modal-title m-0 text-white font-weight-bold d-flex align-items-center" id="attachmentLargeModalLabel">
          <i class="fa fa-receipt text-primary mr-2"></i> <span id="modalAttachmentTitle">Payment Receipt</span>
        </h5>
        <div class="d-flex align-items-center">
          <a id="btnDownloadAttachment" href="#" download class="btn btn-sm btn-outline-light mr-2">
            <i class="fa fa-download mr-1"></i> Download
          </a>
          <button type="button" class="close text-white" data-dismiss="modal" onclick="$('#attachmentLargeModal').modal('hide')" aria-label="Close" style="font-size: 1.5rem; opacity: 1; outline: none;">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      </div>
      <div class="modal-body p-3 text-center">
        <img id="modalLargeImage" src="" alt="Payment Receipt Large View" class="img-fluid rounded shadow d-none" style="max-height: 75vh; width: auto; object-fit: contain;">
        <iframe id="modalPdfIframe" src="" class="d-none w-100 rounded" style="height: 75vh; border: none; background: #fff;"></iframe>
      </div>
    </div>
  </div>
</div>

<!-- Edit Order Modal -->
<div class="modal fade" id="pamotherModal">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="pamotherModalheader">Provisional Orders</h5>
        <button type="button" class="close text-white" onclick="$('#pamotherModal').modal('hide')">&times;</button>
      </div>
      <div class="modal-body" id="pamotherModalContent">
      </div>
    </div>
  </div>
</div>

<!-- Confirmed Orders View-Only Modal -->
<div class="modal fade" id="confirmedOrdersViewModal" tabindex="-1" role="dialog" aria-labelledby="confirmedOrdersViewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered" role="document">
    <div class="modal-content shadow-lg border-0">
      <div class="modal-header bg-success text-white py-2 px-3 d-flex justify-content-between align-items-center">
        <h5 class="modal-title m-0 text-white font-weight-bold d-flex align-items-center" id="confirmedOrdersViewModalLabel" style="font-size: 1rem;">
          <i class="fa fa-check-circle mr-2"></i> Confirmed Orders (View Only)
        </h5>
        <button type="button" class="close text-white" onclick="$('#confirmedOrdersViewModal').modal('hide')" aria-label="Close" style="font-size: 1.5rem; opacity: 1; outline: none;">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body p-3" id="confirmedOrdersModalContent">
      </div>
      <div class="modal-footer py-2 px-3 bg-light d-flex justify-content-end">
        <button type="button" class="btn btn-secondary btn-sm px-3" onclick="$('#confirmedOrdersViewModal').modal('hide')">
          <i class="fa fa-times mr-1"></i> Close
        </button>
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
<script type="text/javascript" src="<?php echo $prefix; ?>krf-orderandsales/js/client_orders_summary.js"></script>
<script type="text/javascript" src="<?php echo $prefix; ?>whatsappnotification/whatsappaction.js"></script>

<script>
  const CURRENT_USER_ROLE_ID = <?php echo json_encode($user_role_id); ?>;
  const CURRENT_USER_NAME = <?php echo json_encode($loginname ?: ($_SESSION['loginname'] ?? 'User')); ?>;
  const CURRENT_USER_ID = <?php echo json_encode($loginid ?: ($_SESSION['loginid'] ?? '')); ?>;
</script>

<?php if (isset($_GET['dz'])): ?>
<script>
  // In dz mode, header.php is skipped, so we manually inject body data attributes
  $(document).ready(function() {
    $('body').attr({
      'data-routurl': '<?php echo $rooturl; ?>',
      'data-companyid': '<?php echo $companyid; ?>',
      'data-subcompanyid': '<?php echo $subcompanyid; ?>',
      'data-projectid': '<?php echo $projectid; ?>',
      'data-loginid': '<?php echo $loginid; ?>',
      'data-loginname': '<?php echo $loginname; ?>',
      'data-loginroleid': '<?php echo $user_role_id; ?>'
    });
  });
</script>
<?php endif; ?>

<script>
  $(document).ready(function() {
    const mobVal = $("#clientmobileno").val() ? $("#clientmobileno").val().trim() : "";
    if (mobVal.length === 10 && typeof performClientSearch === "function") {
      performClientSearch("clientmobileno", mobVal);
    }
  });

  // ----- UTR DUPLICATE CHECK -----
  $('#utrnumber').on('input', function() {
    const utr = $(this).val().trim();
    const projectid = $('body').data('projectid');
    const routurl = $('body').data('routurl');

    if (utr.length < 8) return; // Don't check for very short inputs

    $.ajax({
      url: `${routurl}krf-orderandsales/modal/paymentadvice_modal.php?action=CheckUTRExist`,
      type: 'POST',
      data: { utrnumber: utr, projectid: projectid },
      dataType: 'JSON',
      success: function(res) {
        if (res.status === 'exists') {
          $('#utrnumber').val('');
          const d = res.data;
          Swal.fire({
            title: "Duplicate UTR Detected!",
            html: `
              <div class="text-left small">
                <div class="alert alert-danger py-2 mb-3">This transaction has already been recorded.</div>
                <p><b>Order No:</b> ${d.psono}</p>
                <p><b>Client:</b> ${d.clientname}</p>
                <p><b>Product:</b> ${d.product}</p>
                <p><b>Quantity:</b> ${d.qty}</p>
                <p><b>Amount:</b> ₹${d.amount}</p>
                <p><b>Payment Date:</b> ${d.date}</p>
              </div>
            `,
            icon: "warning",
            confirmButtonColor: "#d33",
            confirmButtonText: "I'll Check"
          });
          // Optionally clear the input
          // $('#utrnumber').val('');
        }
      }
    });
  });

  // Helper to auto-invert dark receipts so Tesseract reads white text on dark background with 100% accuracy
  function getOcrReadyImage(file) {
    return new Promise((resolve) => {
      if (!file || file.type.indexOf('image/') === -1) {
        resolve(file);
        return;
      }
      const reader = new FileReader();
      reader.onload = function(e) {
        const img = new Image();
        img.onload = function() {
          try {
            const canvas = document.createElement('canvas');
            canvas.width = img.width;
            canvas.height = img.height;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0);

            const imgData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const d = imgData.data;

            // Sample brightness to detect dark mode screenshots
            let totalBrightness = 0;
            let sampleCount = 0;
            for (let i = 0; i < d.length; i += 40) {
              totalBrightness += (0.299 * d[i] + 0.587 * d[i+1] + 0.114 * d[i+2]);
              sampleCount++;
            }
            let avgBrightness = sampleCount > 0 ? (totalBrightness / sampleCount) : 255;

            // If dark background (e.g. Google Pay / PhonePe dark mode), invert and boost contrast
            if (avgBrightness < 125) {
              for (let i = 0; i < d.length; i += 4) {
                let r = 255 - d[i];
                let g = 255 - d[i + 1];
                let b = 255 - d[i + 2];
                let gray = 0.299 * r + 0.587 * g + 0.114 * b;
                let val = gray > 140 ? 255 : (gray < 80 ? 0 : gray);
                d[i] = val;
                d[i + 1] = val;
                d[i + 2] = val;
              }
              ctx.putImageData(imgData, 0, 0);
              canvas.toBlob(blob => {
                resolve(blob || file);
              }, 'image/png');
            } else {
              resolve(file);
            }
          } catch (err) {
            resolve(file);
          }
        };
        img.onerror = () => resolve(file);
        img.src = e.target.result;
      };
      reader.onerror = () => resolve(file);
      reader.readAsDataURL(file);
    });
  }

    // ----- ATTACHMENT PREVIEW & LARGE SCREEN LOGIC -----
  let currentAttachmentObjectUrl = null;

  function resetReceiptPreview() {
    if (currentAttachmentObjectUrl) {
      URL.revokeObjectURL(currentAttachmentObjectUrl);
      currentAttachmentObjectUrl = null;
    }
    $('#attachmentPreviewWrapper').addClass('d-none');
    $('#imagePreviewImg').attr('src', '').addClass('d-none');
    $('#pdfPreviewBox').addClass('d-none');
    $('#previewFileName').text('').attr('title', '');
    $('#previewFileSize').text('');
    $('#modalLargeImage').attr('src', '').addClass('d-none');
    $('#modalPdfIframe').attr('src', '').addClass('d-none');
    $('#btnDownloadAttachment').attr('href', '#').removeAttr('download');
  }

  function updateReceiptPreview(file) {
    if (!file) {
      resetReceiptPreview();
      return;
    }

    if (currentAttachmentObjectUrl) {
      URL.revokeObjectURL(currentAttachmentObjectUrl);
      currentAttachmentObjectUrl = null;
    }

    currentAttachmentObjectUrl = URL.createObjectURL(file);
    const fileName = file.name;
    const fileSize = (file.size < 1024 * 1024) 
      ? (file.size / 1024).toFixed(1) + ' KB' 
      : (file.size / (1024 * 1024)).toFixed(2) + ' MB';

    $('#previewFileName').text(fileName).attr('title', fileName);
    $('#previewFileSize').text(fileSize);
    $('#modalAttachmentTitle').text(fileName);
    $('#btnDownloadAttachment').attr('href', currentAttachmentObjectUrl).attr('download', fileName);

    const isPdf = file.type === 'application/pdf' || fileName.toLowerCase().endsWith('.pdf');

    if (isPdf) {
      $('#previewFileIcon').attr('class', 'fa fa-file-pdf text-danger mr-2');
      $('#imagePreviewContainer').addClass('d-none');
      $('#imagePreviewImg').addClass('d-none');
      $('#pdfPreviewBox').removeClass('d-none');
      $('#pdfFileNameDisplay').text(fileName);
      $('#modalLargeImage').addClass('d-none');
      $('#modalPdfIframe').attr('src', currentAttachmentObjectUrl).removeClass('d-none');
    } else {
      $('#previewFileIcon').attr('class', 'fa fa-file-image text-primary mr-2');
      $('#pdfPreviewBox').addClass('d-none');
      $('#imagePreviewContainer').removeClass('d-none');
      $('#imagePreviewImg').attr('src', currentAttachmentObjectUrl).removeClass('d-none');
      $('#modalPdfIframe').addClass('d-none').attr('src', '');
      $('#modalLargeImage').attr('src', currentAttachmentObjectUrl).removeClass('d-none');
    }

    $('#attachmentPreviewWrapper').removeClass('d-none');
  }

  // Handle Reset Attachment
  $(document).on('click', '#btnResetAttachment', function (e) {
    e.preventDefault();
    $('#paymentreceipt').val('');
    resetReceiptPreview();
  });

  // Handle Open Large Screen
  $(document).on('click', '#btnOpenLargeScreen, #imagePreviewContainer, #btnOpenPdfModal', function (e) {
    e.preventDefault();
    const fileInput = document.getElementById('paymentreceipt');
    if ((fileInput && fileInput.files.length > 0) || currentAttachmentObjectUrl) {
      $('#attachmentLargeModal').modal('show');
    }
  });

  // ----- UTR & AMOUNT EXTRACTION LOGIC (OCR) -----
  $('#paymentreceipt').on('change', async function () {
    const fileInput = this;
    if (fileInput.files.length === 0) {
      resetReceiptPreview();
      return;
    }

    const file = fileInput.files[0];
    updateReceiptPreview(file);

    const sourceofamount = $('input[name="sourceofpayment"]:checked').val() || "";
    if (sourceofamount === "upi") {

      // Check file type
      if (file.type.indexOf('image/') === -1) {
        Swal.fire("Warning", "OCR extraction works best with Images (JPG, PNG). PDF extraction is not supported in this mode.", "warning");
        if (file.type.indexOf('pdf') !== -1) return;
      }

      if (file) {
        Swal.fire({
          title: "Scanning Payment Receipt",
          html: "Extracting details via OCR... <b>0%</b>",
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });

        try {
          const imageToScan = await getOcrReadyImage(file);

          // Dynamically load Tesseract on-demand only when user scans a receipt
          if (typeof Tesseract === 'undefined') {
            await new Promise((resolve, reject) => {
              const script = document.createElement('script');
              script.src = 'https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js';
              script.onload = resolve;
              script.onerror = () => reject(new Error('Failed to load OCR library.'));
              document.head.appendChild(script);
            });
          }

          // Correct usage for Tesseract v5
          const worker = await Tesseract.createWorker('eng', 1, {
            logger: m => {
              if (m.status === 'recognizing text') {
                const progress = Math.round(m.progress * 100);
                const bTag = Swal.getHtmlContainer()?.querySelector('b');
                if (bTag) bTag.textContent = progress + '%';
              }
            }
          });

          const ret = await worker.recognize(imageToScan);
          await worker.terminate();

          const text = ret.data.text || "";
          console.log("=== Raw OCR Output ===\n", text);

          // --- OCR Preprocessing & Digit Normalization ---
          // 1. Convert letter O/o to 0 when near digits or standalone in number lines
          let normalizedText = text.replace(/([0-9₹€£¥$*?~=#>_]|\b)[oO]+(?=[oO0-9\s]|\b)/gi, (m) => m.replace(/[oO]/g, '0'));
          // 2. Convert misread comma symbols (?, ;, ', etc.) into commas
          normalizedText = normalizedText.replace(/(\d)\s*[?;'‘’`"]\s*(\d)/g, '$1,$2');
          // 3. Collapse spaces between adjacent digits (e.g. "0 0 0" -> "000", "1 0 0 0" -> "1000")
          normalizedText = normalizedText.replace(/(\d)\s+(\d)/g, '$1$2').replace(/(\d)\s+(\d)/g, '$1$2').replace(/(\d)\s+(\d)/g, '$1$2');
          // 4. Convert dot misread for comma in thousands (e.g. "1.000" -> "1000")
          normalizedText = normalizedText.replace(/(\d+)\.(\d{3})(?!\d)/g, '$1$2');

          // --- Heuristics to find UTR ---
          let foundUTR = null;

          // Strategy A: Look for UTR specifically
          const utrMatch = normalizedText.match(/UTR\s*[:.\-]*\s*(\d{12})/i);
          if (utrMatch) {
            foundUTR = utrMatch[1];
          }

          // Strategy B: If no UTR, look for Ref No or Transaction ID
          if (!foundUTR) {
            const refMatch = normalizedText.match(/(?:REF|TRANSACTION|TXN|REFERENCE)\s*[:.\-]*\s*(\d{12})/i);
            if (refMatch) {
              foundUTR = refMatch[1];
            }
          }

          // Strategy C: Generic 12-digit search (excluding UPI IDs)
          if (!foundUTR) {
            const words = normalizedText.split(/\s+/);
            for (let word of words) {
              if (word.includes('@')) continue;
              const match = word.match(/\d{12}/);
              if (match) {
                foundUTR = match[0];
                break; 
              }
            }
          }

          // Strategy D: Keyword search for non-standard length or noisy OCR
          if (!foundUTR) {
            const lines = normalizedText.split('\n');
            for (let line of lines) {
              let cleanLine = line.trim().toUpperCase();
              if (cleanLine.includes('@')) continue; 

              if (/UTR|REF|TRANSACTION|TXN|REFERENCE/.test(cleanLine)) {
                let matches = cleanLine.match(/[A-Z0-9]{10,25}/g);
                if (matches) {
                  for (let m of matches) {
                    if (!/UTR|REF|TRANSACTION|TXN|REFERENCE/.test(m) && /\d/.test(m)) {
                      foundUTR = m;
                      break;
                    }
                  }
                }
              }
              if (foundUTR) break;
            }
          }

          // --- Helper: Clean and parse amounts formatted in Indian or International styles ---
          function cleanIndianAmount(str) {
              if (!str) return null;
              let s = str.replace(/[oO]/gi, '0'); // Convert letter O to digit 0
              s = s.replace(/,/g, ''); // Strip all commas (e.g. 1,00,000 -> 100000, 10,000 -> 10000)
              
              // If multiple dots exist (e.g. 1.00.000), strip all dots
              if ((s.match(/\./g) || []).length > 1) {
                  s = s.replace(/\./g, '');
              } else if (/^\d+\.\d{3}$/.test(s)) {
                  // Dot followed by exactly 3 digits is a thousands separator (e.g. 10.000 -> 10000)
                  s = s.replace(/\./g, '');
              }
              
              s = s.replace(/[^0-9.]/g, '');
              let val = parseFloat(s);
              return (!isNaN(val) && val > 0 && val < 100000000) ? val : null;
          }

          // --- Intelligent Amount Extraction & Validation ---
          let foundAmount = null;
          const inputAmount = parseFloat($('#amount').val()) || 0;

          // 1. Sanitize OCR text safely (without eating financial amounts)
          let contextCleanedText = normalizedText.replace(/[\w.\-+]+@[\w.\-]+/g, ' [UPI_HANDLE] ');
          contextCleanedText = contextCleanedText.replace(/(?:\+91|SENT BY|FROM|MOBILE|PH|PHONE|CALL)\s*[0-9\s\-]{5,15}/gi, ' [SENDER_INFO] ');
          if (foundUTR) {
              contextCleanedText = contextCleanedText.replace(new RegExp(foundUTR, 'g'), ' [UTR_NUM] ');
          }
          contextCleanedText = contextCleanedText.replace(/\b\d{11,22}\b/g, ' [LONG_NUM] ');
          contextCleanedText = contextCleanedText.replace(/(?:TRANSACTION\s+ID|TXN\s+ID|REF\s+NO|GOOGLE\s+TRANSACTION\s+ID)[\s:.\-]*[A-Za-z0-9]+/gi, ' [TXN_ID] ');

          // Safely mask bank account suffixes (e.g. Bank of India 4049, SBI 6798, A/C 3115)
          contextCleanedText = contextCleanedText.replace(/(?:Bank\s+of\s+[A-Za-z]+|State\s+Bank\s+of\s+India|HDFC\s+Bank|ICICI\s+Bank|SBI|Axis\s+Bank|PNB|BOI|Canara|Union|Paytm|Kotak|Bank|Account|A\/C|Ending|Card)[\s\n\-:.]*[xX*]*(\d{3,6})\b/gi, ' [BANK] ');

          // Mask timestamps & dates
          contextCleanedText = contextCleanedText.replace(/\b\d{1,2}:\d{2}(?::\d{2})?\s*(?:am|pm|AM|PM)?\b/gi, ' [TIME] ');
          contextCleanedText = contextCleanedText.replace(/\b\d{1,2}[\s\/\-.](?:Jan(?:uary)?|Feb(?:ruary)?|Mar(?:ch)?|Apr(?:il)?|May|Jun(?:e)?|Jul(?:y)?|Aug(?:ust)?|Sep(?:tember)?|Oct(?:ober)?|Nov(?:ember)?|Dec(?:ember)?)[a-z]*[\s\/\-.,]*(?:\d{2,4})?\b/gi, ' [DATE] ');
          contextCleanedText = contextCleanedText.replace(/\b(?:Jan(?:uary)?|Feb(?:ruary)?|Mar(?:ch)?|Apr(?:il)?|May|Jun(?:e)?|Jul(?:y)?|Aug(?:ust)?|Sep(?:tember)?|Oct(?:ober)?|Nov(?:ember)?|Dec(?:ember)?)[a-z]*\s+\d{1,2},?\s*(?:\d{2,4})?\b/gi, ' [DATE] ');
          contextCleanedText = contextCleanedText.replace(/\b\d{1,2}[\/\-.]\d{1,2}[\/\-.]\d{2,4}\b/g, ' [DATE] ');
          contextCleanedText = contextCleanedText.replace(/(?:\b|\s)(?:202[0-9]|203[0-9])(?:\b|\s)/g, ' [YEAR] ');

          // 2. Extract candidate numbers from context-cleaned text
          let allTokens = contextCleanedText.match(/[₹€£¥$*?~=#>_]?[0-9\s,.]+(?:\.[0-9]{1,2})?/g) || [];
          
          let candidates = [];
          allTokens.forEach(tok => {
              let s = tok.replace(/[^0-9.]/g, '');
              if (s.length > 2 && /^[3287Zz]/.test(s)) {
                  let subVal = cleanIndianAmount(s.substring(1));
                  if (subVal && subVal >= 10) candidates.push(subVal);
              }
              let val = cleanIndianAmount(tok);
              if (val && val >= 10) candidates.push(val);
          });

          // 3. Match against input amount if user entered it
          if (inputAmount > 0) {
              for (let val of candidates) {
                  if (Math.abs(val - inputAmount) < 0.01) {
                      foundAmount = inputAmount;
                      break;
                  }
              }
          }

          // 4. Primary Strategy: Extract amount attached to Currency Symbols (₹, Rs, INR, Total, Paid)
          if (!foundAmount) {
              const primaryRegex = /(?:[₹€£¥$*?~=#>_]|\b(?:RS\.?|INR|RE\.?|AMOUNT|TOTAL|PAID|SUCCESSFUL|PAYMENT|AMT)\b)[\s:.\-]*([0-9\s,.]+(?:\.[0-9]{1,2})?)/gi;
              let match;
              while ((match = primaryRegex.exec(contextCleanedText)) !== null) {
                  let s = match[1].replace(/[^0-9.]/g, '');
                  if (s.length > 2 && /^[3287Zz]/.test(s)) {
                      let sub = cleanIndianAmount(s.substring(1));
                      if (sub && sub >= 10) {
                          foundAmount = sub;
                          break;
                      }
                  }
                  let val = cleanIndianAmount(match[1]);
                  if (val && val >= 10) {
                      foundAmount = val;
                      break;
                  }
              }
          }

          // 5. Fallback Strategy: Best remaining candidate (prefer >= 100)
          if (!foundAmount) {
              let major = candidates.filter(v => v >= 100);
              if (major.length > 0) {
                  foundAmount = major[0];
              } else if (candidates.length > 0) {
                  foundAmount = candidates[0];
              }
          }

          // Final Validation & UI Feedback
          if (foundAmount) {
              if (inputAmount > 0 && Math.abs(foundAmount - inputAmount) > 0.01) {
                  const effectiveRoleId = (typeof CURRENT_USER_ROLE_ID !== 'undefined') ? CURRENT_USER_ROLE_ID : (parseInt($('body').data('loginroleid')) || 99);
                  const effectiveUserName = (typeof CURRENT_USER_NAME !== 'undefined' && CURRENT_USER_NAME) ? CURRENT_USER_NAME : ($('body').data('loginname') || 'User');
                  const effectiveUserId = (typeof CURRENT_USER_ID !== 'undefined' && CURRENT_USER_ID) ? CURRENT_USER_ID : ($('body').data('loginid') || '');

                  if (effectiveRoleId <= 3) {
                      // Calculate difference between entered and scanned
                      const diff = inputAmount - foundAmount;
                      const diffText = diff > 0 ? `+₹${diff.toFixed(2)} (Over)` : (diff < 0 ? `-₹${Math.abs(diff).toFixed(2)} (Under)` : `₹0.00`);
                      const diffColor = diff > 0 ? '#b91c1c' : '#c2410c';
                      const utrDisplayHtml = foundUTR 
                          ? `<span class="badge bg-white text-dark border px-2 py-1" style="font-family: monospace; font-size: 0.85rem;"><i class="fa fa-fingerprint text-primary mr-1"></i>${foundUTR}</span>`
                          : `<span class="badge bg-white text-muted border px-2 py-1 font-italic font-weight-normal" style="font-size: 0.8rem;"><i class="fa fa-exclamation-circle text-warning mr-1"></i>Not detected in image</span>`;

                      // Privileged user (<= 3): Allow confirmation of entered amount with responsibility remark
                      const mismatchResult = await Swal.fire({
                          title: `<span style="font-family: 'Outfit', sans-serif; font-size: 1.35rem; font-weight: 700; color: #1e293b;">Amount Mismatch Detected</span>`,
                          html: `
                            <div class="text-left" style="font-family: 'Inter', sans-serif; font-size: 0.9rem; color: #1e293b; line-height: 1.5;">
                              
                              <!-- Metric Comparison Cards -->
                              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                                <!-- Receipt Amount Card -->
                                <div style="background: #fff1f2; border: 1px solid #fecdd3; border-radius: 12px; padding: 10px 12px; text-align: center;">
                                  <div style="font-size: 0.72rem; font-weight: 700; color: #e11d48; text-transform: uppercase; letter-spacing: 0.04em;">
                                    <i class="fa fa-file-invoice-dollar mr-1"></i> Scanned Receipt
                                  </div>
                                  <div style="font-size: 1.25rem; font-weight: 800; color: #9f1239; margin-top: 2px;">
                                    ₹${foundAmount.toFixed(2)}
                                  </div>
                                </div>

                                <!-- Entered Amount Card -->
                                <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 10px 12px; text-align: center;">
                                  <div style="font-size: 0.72rem; font-weight: 700; color: #2563eb; text-transform: uppercase; letter-spacing: 0.04em;">
                                    <i class="fa fa-edit mr-1"></i> Entered Amount
                                  </div>
                                  <div style="font-size: 1.25rem; font-weight: 800; color: #1e40af; margin-top: 2px;">
                                    ₹${inputAmount.toFixed(2)}
                                  </div>
                                </div>
                              </div>

                              <!-- UTR & Variance Pill Strip -->
                              <div style="display: flex; justify-content: space-between; align-items: center; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 8px 12px; margin-bottom: 12px; font-size: 0.82rem;">
                                <div>
                                  <span class="text-muted mr-1"><b>UTR / Ref:</b></span> ${utrDisplayHtml}
                                </div>
                                <div>
                                  <span class="text-muted mr-1"><b>Variance:</b></span> <span style="font-weight: 700; color: ${diffColor};">${diffText}</span>
                                </div>
                              </div>

                              <!-- Responsibility & Accountability Notice -->
                              <div style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border: 1px solid #fde68a; border-left: 4px solid #f59e0b; border-radius: 12px; padding: 12px 14px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                                <div style="display: flex; align-items: center; gap: 6px; font-weight: 700; color: #92400e; font-size: 0.88rem; margin-bottom: 4px;">
                                  <i class="fa fa-shield-alt" style="color: #d97706; font-size: 0.95rem;"></i>
                                  <span>Responsibility Disclaimer</span>
                                </div>
                                <p style="color: #78350f; font-size: 0.83rem; line-height: 1.45; margin: 0;">
                                  I, <b>${effectiveUserName}</b>, have reviewed the payment screenshot (Receipt: <b>₹${foundAmount.toFixed(2)}</b> ${foundUTR ? '| UTR: <b>' + foundUTR + '</b>' : ''}) and confirm the entered amount of <b>₹${inputAmount.toFixed(2)}</b>. I take full responsibility for submitting and confirming this amount mismatch.
                                </p>
                              </div>
                            </div>
                          `,
                          icon: "warning",
                          showCancelButton: true,
                          confirmButtonColor: "#2563eb",
                          cancelButtonColor: "#dc2626",
                          confirmButtonText: `<i class="fa fa-check-circle mr-1"></i> Confirm & Authorize (₹${inputAmount.toFixed(2)})`,
                          cancelButtonText: `<i class="fa fa-times mr-1"></i> Cancel & Re-attach`,
                          allowOutsideClick: false,
                          customClass: {
                              popup: 'rounded-4 shadow-lg'
                          }
                      });

                      if (mismatchResult.isConfirmed) {
                          // Autofill clear and professional remark message
                          const now = new Date();
                          const day = String(now.getDate()).padStart(2, '0');
                          const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                          const month = months[now.getMonth()];
                          const year = now.getFullYear();
                          let hours = now.getHours();
                          const minutes = String(now.getMinutes()).padStart(2, '0');
                          const ampm = hours >= 12 ? 'PM' : 'AM';
                          hours = hours % 12 || 12;
                          const formattedDateTime = `${day}-${month}-${year}, ${String(hours).padStart(2, '0')}:${minutes} ${ampm}`;

                          const utrInfo = foundUTR ? `UTR: ${foundUTR}` : `UTR: Not Detected`;
                          const mismatchNote = `Payment of ₹${inputAmount.toFixed(2)} confirmed against screenshot of ₹${foundAmount.toFixed(2)} (${utrInfo}). Confirmed by ${effectiveUserName} on ${formattedDateTime}, who takes full responsibility for this amount difference.`;
                          
                          let currentRemarks = $('#remarks').val().trim();
                          if (currentRemarks) {
                              if (!currentRemarks.includes('takes full responsibility for this amount difference') && !currentRemarks.includes('[Amount Mismatch')) {
                                  $('#remarks').val(currentRemarks + "\n" + mismatchNote);
                              }
                          } else {
                              $('#remarks').val(mismatchNote);
                          }

                          // Continue extracting date & UTR
                          let foundDate = extractPaymentDateFromTextAndFilename(text, file.name);
                          if (foundDate) {
                              $('#paymentdate').val(foundDate).trigger('change');
                          }

                          if (foundUTR) {
                              $('#utrnumber').val(foundUTR).trigger("input");
                              Swal.fire({
                                  title: "Mismatch Authorized",
                                  html: `
                                    <div class="text-left font-normal" style="font-size: 0.9rem; color: #1e293b;">
                                      <p class="mb-2 text-success font-weight-bold"><i class="fa fa-check-circle mr-1"></i> Entered amount <b>₹${inputAmount.toFixed(2)}</b> authorized under your accountability.</p>
                                      <p class="mb-1"><b>UTR / Ref:</b> ${foundUTR}</p>
                                      ${foundDate ? `<p class="mb-1"><b>Payment Date:</b> ${foundDate}</p>` : ''}
                                      <p class="mb-0 text-muted small mt-2"><i class="fa fa-info-circle mr-1"></i> Remarks field has been updated with the authorization audit record.</p>
                                    </div>
                                  `,
                                  icon: "success",
                                  confirmButtonColor: "#2563eb"
                              });
                          } else {
                              Swal.fire({
                                  title: "Mismatch Authorized",
                                  html: `
                                    <div class="text-left font-normal" style="font-size: 0.9rem; color: #1e293b;">
                                      <p class="mb-2 text-success font-weight-bold"><i class="fa fa-check-circle mr-1"></i> Entered amount <b>₹${inputAmount.toFixed(2)}</b> authorized under your accountability.</p>
                                      <p class="mb-1 text-warning font-weight-bold"><i class="fa fa-exclamation-triangle mr-1"></i> 12-digit UTR was not detected in the screenshot; please enter it manually.</p>
                                      ${foundDate ? `<p class="mb-1"><b>Payment Date:</b> ${foundDate}</p>` : ''}
                                      <p class="mb-0 text-muted small mt-2"><i class="fa fa-info-circle mr-1"></i> Remarks field has been updated with the authorization audit record.</p>
                                    </div>
                                  `,
                                  icon: "info",
                                  confirmButtonColor: "#2563eb"
                              });
                          }
                          return;
                      } else {
                          // User chose Cancel: clear receipt file input
                          $('#paymentreceipt').val('');
                          return;
                      }
                  } else {
                      // Standard staff (> 3): strictly blocked
                      // $('#paymentreceipt').val(''); resetReceiptPreview(); // Clear the file if amount mismatch
                      Swal.fire({
                          title: "Amount Mismatch!",
                          text: `The receipt shows ₹${foundAmount}, but you entered ₹${inputAmount}. Please check your entry or re-attach the document.`,
                          icon: "error",
                          confirmButtonColor: "#d33"
                      });
                      return;
                  }
              } else if (inputAmount <= 0) {
                  // Auto-fill amount input if it was left empty
                  $('#amount').val(foundAmount).trigger('input');
              }
          } else {
              // $('#paymentreceipt').val(''); resetReceiptPreview(); // Clear the file if amount not found
              Swal.fire({
                  title: "Amount Not Detected",
                  text: "Could not read the amount from the receipt. Please ensure the image is clear and contains the ₹ symbol.",
                  icon: "warning",
                  confirmButtonColor: "#f39c12"
              });
              // return;
          }

          // --- Extract Payment Date (from OCR text or filename) ---
          let foundDate = extractPaymentDateFromTextAndFilename(text, file.name);
          if (foundDate) {
              $('#paymentdate').val(foundDate).trigger('change');
          }

          if (foundUTR) {
            $('#utrnumber').val(foundUTR).trigger("input");        
            let msgText = `UTR Extracted: ${foundUTR}\nAmount Verified: ₹${foundAmount}`;
            if (foundDate) {
                msgText += `\nPayment Date: ${foundDate}`;
            }
            Swal.fire({
              title: "Verification Successful",
              text: msgText,
              icon: "success"
            });
          } else {
            let msgText = "Amount matched (₹" + foundAmount + "), but 12-digit UTR was not found. Please enter it manually.";
            if (foundDate) {
                msgText += "\nPayment Date Extracted: " + foundDate;
            }
            Swal.fire({
              title: "UTR Not Found",
              text: msgText,
              icon: "info"
            });
          }

        } catch (err) {
          console.error(err);
          Swal.fire("Error", "Failed to process image. Please try again or enter manually.", "error");
        }
      }
    }
  });

  function extractPaymentDateFromTextAndFilename(text, fileName) {
    const monthNames = {
        'jan': '01', 'january': '01',
        'feb': '02', 'february': '02',
        'mar': '03', 'march': '03',
        'apr': '04', 'april': '04',
        'may': '05',
        'jun': '06', 'june': '06',
        'jul': '07', 'july': '07',
        'aug': '08', 'august': '08',
        'sep': '09', 'september': '09', 'sept': '09',
        'oct': '10', 'october': '10',
        'nov': '11', 'november': '11',
        'dec': '12', 'december': '12'
    };

    function formatYMD(y, m, d) {
        let year = parseInt(y, 10);
        if (year < 100) year += 2000;
        let month = parseInt(m, 10);
        let day = parseInt(d, 10);

        if (month < 1 || month > 12) return null;
        if (day < 1 || day > 31) return null;
        if (year < 2000 || year > 2099) return null;

        const strY = String(year);
        const strM = String(month).padStart(2, '0');
        const strD = String(day).padStart(2, '0');
        return `${strY}-${strM}-${strD}`;
    }

    const sources = [text || '', fileName || ''];

    for (let source of sources) {
        if (!source) continue;

        // Pattern 1: YYYY-MM-DD or YYYY/MM/DD or YYYY.MM.DD (e.g. 2026-04-27 or 2026/04/27)
        let m1 = source.match(/\b(20\d{2})[\/\.\-](0?[1-9]|1[0-2])[\/\.\-](0?[1-9]|[12]\d|3[01])\b/);
        if (m1) {
            let res = formatYMD(m1[1], m1[2], m1[3]);
            if (res) return res;
        }

        // Pattern 2: DD-MM-YYYY or DD/MM/YYYY or DD.MM.YYYY (e.g. 27-04-2026 or 27/04/2026)
        let m2 = source.match(/\b(0?[1-9]|[12]\d|3[01])[\/\.\-](0?[1-9]|1[0-2])[\/\.\-](20\d{2})\b/);
        if (m2) {
            let res = formatYMD(m2[3], m2[2], m2[1]);
            if (res) return res;
        }

        // Pattern 3: DD Mon YYYY / DD Month YYYY (e.g. 27 Apr 2026 or 27-April-2026)
        let m3 = source.match(/\b(0?[1-9]|[12]\d|3[01])[\s\.\-,]+(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Sept|Oct|Nov|Dec|January|February|March|April|May|June|July|August|September|October|November|December)[\s\.\-,]+(20\d{2})\b/i);
        if (m3) {
            let monStr = m3[2].toLowerCase();
            let mon = monthNames[monStr];
            if (mon) {
                let res = formatYMD(m3[3], mon, m3[1]);
                if (res) return res;
            }
        }

        // Pattern 4: Mon DD, YYYY / Month DD, YYYY (e.g. Apr 27, 2026 or April 27 2026)
        let m4 = source.match(/\b(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Sept|Oct|Nov|Dec|January|February|March|April|May|June|July|August|September|October|November|December)[\s\.\-,]+(0?[1-9]|[12]\d|3[01])[\s\.\-,]+(20\d{2})\b/i);
        if (m4) {
            let monStr = m4[1].toLowerCase();
            let mon = monthNames[monStr];
            if (mon) {
                let res = formatYMD(m4[3], mon, m4[2]);
                if (res) return res;
            }
        }

        // Pattern 5: Compact WhatsApp / Screenshot dates like IMG-20260427-WA0001 or 20260427
        let m5 = source.match(/\b(20\d{2})(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])\b/);
        if (m5) {
            let res = formatYMD(m5[1], m5[2], m5[3]);
            if (res) return res;
        }

        // Pattern 6: DD-MM-YY (Short year, e.g. 27-04-26)
        let m6 = source.match(/\b(0?[1-9]|[12]\d|3[01])[\/\.\-](0?[1-9]|1[0-2])[\/\.\-](\d{2})\b/);
        if (m6) {
            let res = formatYMD(m6[3], m6[2], m6[1]);
            if (res) return res;
        }
    }

    return null;
}


  // ----- EXISTING LOGIC REFACTORED FOR NEW UI -----
  // ----- INCREMENTAL SEARCH FOR CLIENTS -----
  let searchTimeout = null;

  function performClientSearch(field, value) {
    if (searchTimeout) clearTimeout(searchTimeout);
    
    // Clear fields if value is empty
    if (value === "") {
        $("#existclientdata").hide().html("");
        if (field === "clientmobileno") {
            $("#clientname").val("");
            $("#clientid").val("");
        }
        return;
    }

    if (value.length < 3) {
      $("#existclientdata").hide().html("");
      return;
    }

    searchTimeout = setTimeout(function() {
      let sortBy = (field === "clientmobileno") ? "mobileno" : "searchclientname";
      
      GetClientDetailsFrommaster_Callback(sortBy, value, function (res) {
        var existcl = "";
        if (res && res.length > 0) {
          existcl += '<div class="alert alert-info py-2 px-3 mt-2 mb-2 small d-flex align-items-center">' +
                     '<i class="fa fa-info-circle mr-2"></i> Registered Client found. Click to select:' +
                     '</div>';
          
          for (var i = 0; i < res.length; i++) {
            existcl += '<div class="card p-2 mb-2 shadow-sm existclient-card" style="cursor: pointer; border-left: 4px solid var(--accent-color);">' +
              '<div class="d-flex align-items-center">' +
              '<div class="mr-3">' +
              '<input type="radio" class="existclient" name="existclient" ' +
              'data-clientid="' + res[i].client_id + '" ' +
              'data-clientname="' + res[i].client_name + '" ' +
              'data-mobileno="' + res[i].phones + '" ' +
              'data-email="' + (res[i].client_email || '') + '" ' +
              'data-address="' + (res[i].invoicing_address || '') + '" />' +
              '</div>' +
              '<div class="flex-grow-1">' +
              '<h6 class="mb-0 font-weight-bold">' + res[i].client_name + '</h6>' +
              '<small class="text-muted d-block" style="font-size: 0.75rem;">' + 
              '<i class="fa fa-phone mr-1"></i>' + (res[i].phones || 'N/A') + ' | ' + 
              '<i class="fa fa-map-marker-alt mr-1"></i>' + (res[i].invoicing_address || 'No Address') + 
              '</small>' +
              '</div>' +
              '</div>' +
              '</div>';
          }
          $("#existclientdata").html(existcl).fadeIn(200);

          // Auto-select if exactly 1 result AND it's a mobile search of 10 digits
          if (res.length === 1 && field === "clientmobileno" && value.length === 10) {
              $("input[name='existclient']").prop("checked", true).trigger("click");
          }
        } else {
          $("#existclientdata").hide().html("");
        }
      });
    }, 400); // 400ms debounce
  }

  $(document).on("input", "#clientmobileno", function () {
    const mob = $(this).val().trim();
    performClientSearch("clientmobileno", mob);
    if (mob.length === 10) {
      setTimeout(function() {
        if (typeof fetchClientPendingCreditNotes === "function") {
          fetchClientPendingCreditNotes();
        }
      }, 500);
    }
  });

  $(document).on("input", "#clientname", function () {
    // Only search by name if clientid is not yet set
    if ($("#clientid").val() === "") {
        performClientSearch("clientname", $(this).val().trim());
    }
  });

  // Clear name/ID if user changes the mobile number after selection
  $(document).on("keydown", "#clientmobileno", function() {
      if ($("#clientid").val() !== "") {
          $("#clientid").val("");
      }
  });

  // Clear search results when mobile is cleared
  $(document).on("change", "#clientmobileno", function() {
    if ($(this).val().trim() === "") {
        $("#existclientdata").hide().html("");
        $("#clientname").val("");
        $("#clientid").val("");
        fetchClientPendingCreditNotes();
    }
  });

  // Delegate click on the card to the radio
  $(document).on("click", ".existclient-card", function (e) {
    if ($(e.target).is('input[type="radio"]')) return;
    $(this).find("input[type=radio]").prop("checked", true).trigger("click");
  });

  $(document).on("click", "input[name='existclient']", function () {
    $("#clientname").val($(this).data("clientname"));
    $("#clientid").val($(this).data("clientid"));
    $("#clientmobileno").val($(this).data("mobileno"));
    
    // Clear results after selection
    $("#existclientdata").fadeOut(200);

    // Fetch Credit Notes for the chosen client
    fetchClientPendingCreditNotes();
  });

  // ----- PENDING CREDIT NOTES LOGIC -----
  window.pendingCreditNotesList = <?php echo json_encode($client_creditnotes ?? []); ?>;

  function fetchClientPendingCreditNotes(callback) {
    const clientId = $("#clientid").val()?.trim() || "";
    const clientName = $("#clientname").val()?.trim() || "";
    const mobileno = $("#clientmobileno").val()?.trim() || "";
    const projectId = $("input[name='projectid']").val() || $('body').data('projectid') || "";

    if (!clientId && !clientName && (!mobileno || mobileno.length < 10)) {
      $("#lbl_src_creditnote").addClass("d-none");
      $("#cn_pending_badge").hide().text("0");
      window.pendingCreditNotesList = [];
      renderCreditNotesGrid([]);
      if ($('input[name="sourceofpayment"]:checked').val() === 'creditnote') {
        $("#src_cash").prop("checked", true).trigger("change");
      }
      if (typeof callback === 'function') callback({ status: 'success', data: [], total_count: 0 });
      return;
    }

    $.ajax({
      url: "modal/paymentadvice_modal.php?action=GetClientCreditNotes",
      type: "GET",
      data: {
        clientid: clientId,
        clientname: clientName,
        mobileno: mobileno,
        projectid: projectId
      },
      dataType: "json",
      success: function(resp) {
        if (resp && resp.status === 'success') {
          window.pendingCreditNotesList = resp.data || [];
          const count = resp.total_count || 0;
          const totalAmt = resp.total_amount || 0;

          if (count > 0) {
            $("#lbl_src_creditnote").removeClass("d-none");
            $("#cn_pending_badge").text(count).show();
            $("#cn_total_available_badge").text("₹" + parseFloat(totalAmt).toFixed(2) + " Total");
            renderCreditNotesGrid(window.pendingCreditNotesList);
          } else {
            $("#lbl_src_creditnote").addClass("d-none");
            $("#cn_pending_badge").hide().text("0");
            if ($('input[name="sourceofpayment"]:checked').val() === 'creditnote') {
              $("#src_cash").prop("checked", true).trigger("change");
            }
            renderCreditNotesGrid([]);
          }
        }
        if (typeof callback === 'function') callback(resp);
      },
      error: function() {
        if (typeof callback === 'function') callback(null);
      }
    });
  }

  function renderCreditNotesGrid(list) {
    const tbody = $("#creditNotesTableBody");
    tbody.empty();

    if (!list || list.length === 0) {
      $("#noCreditNotesMsg").removeClass("d-none");
      $("#creditNotesTable").addClass("d-none");
      return;
    }

    $("#noCreditNotesMsg").addClass("d-none");
    $("#creditNotesTable").removeClass("d-none");

    const currentSelectedId = $("#creditnote_id").val();

    list.forEach(function(cn, index) {
      const isChecked = (String(cn.creditnote_id) === String(currentSelectedId)) || (!currentSelectedId && index === 0);
      const rowClass = isChecked ? 'table-active' : '';

      const tr = $(`
        <tr class="cn-table-row ${rowClass}" data-id="${cn.creditnote_id}">
          <td class="text-center align-middle py-1">
            <input type="radio" name="selected_creditnote_radio" class="selected_creditnote_radio" 
              value="${cn.creditnote_id}" 
              data-id="${cn.creditnote_id}"
              data-no="${cn.creditnote_no}"
              data-amt="${cn.credit_amount}"
              data-date="${cn.creditnote_date}"
              data-reason="${cn.reason || ''}"
              data-psono="${cn.psono || ''}"
              ${isChecked ? 'checked' : ''} style="cursor: pointer; width: 16px; height: 16px; accent-color: #9333ea;">
          </td>
          <td class="align-middle py-1 font-weight-bold font-monospace" style="color: #7c3aed;">
            <i class="fa fa-receipt mr-1"></i>${cn.creditnote_no}
          </td>
          <td class="align-middle py-1 text-nowrap">${cn.formatted_date || cn.creditnote_date}</td>
          <td class="align-middle py-1 text-end font-weight-bold text-success">₹${cn.formatted_amount || parseFloat(cn.credit_amount).toFixed(2)}</td>
          <td class="align-middle py-1 small font-monospace">${cn.psono || 'N/A'}${cn.sono ? '<br><span class="text-muted">' + cn.sono + '</span>' : ''}</td>
          <td class="align-middle py-1 small font-weight-semibold text-dark">${cn.created_by || 'Admin'}</td>
          <td class="align-middle py-1 small text-muted text-nowrap">${cn.formatted_created_at || cn.created_at}</td>
          <td class="align-middle py-1 small text-secondary" style="max-width: 200px; word-break: break-word;" title="${cn.reason || ''}">${cn.reason || 'N/A'}</td>
        </tr>
      `);

      tbody.append(tr);

      if (isChecked) {
        selectCreditNoteRow(cn);
      }
    });
  }

  function selectCreditNoteRow(cn) {
    if (!cn) return;
    $("#creditnote_id").val(cn.creditnote_id);
    $("#creditnote_no").val(cn.creditnote_no);
    $("#creditnote_amount").val(cn.credit_amount);

    // Set amount field to credit note amount
    $("#amount").val(parseFloat(cn.credit_amount).toFixed(2));
    
    // Set remarks
    const existingRemarks = $("#remarks").val().trim();
    if (!existingRemarks || existingRemarks.startsWith("Credit Note adjustment:")) {
      $("#remarks").val(`Credit Note adjustment: ${cn.creditnote_no} - ${cn.reason || 'Used against order'}`);
    }

    // Highlight row
    $(".cn-table-row").removeClass("table-active");
    $(`.cn-table-row[data-id="${cn.creditnote_id}"]`).addClass("table-active");
  }

  // Row click selects radio
  $(document).on("click", ".cn-table-row", function(e) {
    if (!$(e.target).is("input[type='radio']")) {
      $(this).find("input[type='radio']").prop("checked", true).trigger("change");
    }
  });

  $(document).on("change", "input[name='selected_creditnote_radio']", function() {
    const cnId = $(this).val();
    const cn = (window.pendingCreditNotesList || []).find(it => String(it.creditnote_id) === String(cnId));
    if (cn) {
      selectCreditNoteRow(cn);
    }
  });

  function updatePurposeUI() {
    const selectedPurpose = $('input[name="purposeofamount"]:checked').val();
    if (selectedPurpose === 'advancepayment') {
      $(".prefixamount").html("Advance Amount");
      $("#manualBookingSection").removeClass("d-none").fadeIn(200);
      $("#prvordnodiv").addClass("d-none");
    } else {
      $(".prefixamount").html("Invoice Against Amount");
      $("#manualBookingSection").addClass("d-none").hide();
      $("#bookingno").val("");
      $("#bookingreceipt").val("");
      $("#prvordnodiv").removeClass("d-none");
    }
  }

  $(document).on('change', 'input[name="purposeofamount"]', updatePurposeUI);
  $(document).ready(function() {
    updatePurposeUI();
  });

  // Show/Hide sections logic
  function updateSourceOfPaymentUI() {
    const selectedSource = $('input[name="sourceofpayment"]:checked').val();
    const receivedbankSection = document.getElementById('receivedbankSection');

    if (selectedSource === 'creditnote') {
      // CREDIT NOTE SELECTED
      $(".casgreceuvedvtdiv").addClass("d-none");
      $("#refrensenodiv").addClass("d-none");
      if (receivedbankSection) receivedbankSection.classList.add('d-none');
      $("#receivedtoaccountid").prop('required', false);
      $("#casgreceivedby, #casgreceuvelocation").prop('required', false);
      $("#utrnumber").prop('required', false);
      $("#utr_required_star").addClass('d-none');

      // Show Credit Note Selection Grid
      $("#creditNoteSelectionSection").removeClass("d-none");

      // Hide Payment & Attachment Details panel!
      $("#paymentAttachmentDetailsPanel").addClass("d-none");
      $("#leftDashboardCol").removeClass("col-lg-6").addClass("col-lg-12");

      // Remove required from payment proof file
      $("#paymentreceipt").prop('required', false);

      // Render grid if we have list or fetch it
      if (window.pendingCreditNotesList && window.pendingCreditNotesList.length > 0) {
        renderCreditNotesGrid(window.pendingCreditNotesList);
      } else {
        fetchClientPendingCreditNotes(function(resp) {
          if (!resp || !resp.data || resp.data.length === 0) {
            Swal.fire("No Credit Notes", "No active credit notes found for this client.", "info");
          }
        });
      }

      // Update submit/fetch buttons
      if (window.orderSelectionConfirmed || $('#invoiceno').val()) {
        $('#savepaymentdatabtn').html('<i class="fa fa-check-circle mr-1"></i> Proceed to Pay with Credit Note').show();
        $('#fetchthedata').hide();
      } else {
        $('#fetchthedata').html('<i class="fa fa-list mr-1"></i> Select Order to Apply Credit Note');
      }

    } else {
      // CASH, CHEQUE, OR UPI SELECTED
      $("#creditNoteSelectionSection").addClass("d-none");
      $("#paymentAttachmentDetailsPanel").removeClass("d-none");
      $("#leftDashboardCol").removeClass("col-lg-12").addClass("col-lg-6");
      $("#paymentreceipt").prop('required', true);
      $('#savepaymentdatabtn').html('<i class="fa fa-check-circle mr-1"></i> Save Payment Info');
      $('#fetchthedata').html('<i class="fa fa-search mr-1"></i> Fetch Data');

      if (selectedSource === 'cash') {
        $(".casgreceuvedvtdiv").removeClass("d-none");
        $("#refrensenodiv").addClass("d-none");
        if (receivedbankSection) receivedbankSection.classList.add('d-none');
        $("#receivedtoaccountid").prop('required', false);
        $("#casgreceivedby, #casgreceuvelocation").prop('required', true);
        $("#utrnumber").prop('required', false);
        $("#utr_required_star").addClass('d-none');
      } else {
        $(".casgreceuvedvtdiv").addClass("d-none");
        $("#refrensenodiv").removeClass("d-none");
        $("#casgreceivedby, #casgreceuvelocation").prop('required', false);
        $("#receivedtoaccountid").prop('required', true);
        if (receivedbankSection) receivedbankSection.classList.remove('d-none');
        $("#utrnumber").prop('required', true);
        $("#utr_required_star").removeClass('d-none');
        if (selectedSource === 'cheque') {
          $("#utrnumber").attr('placeholder', 'Cheque / Ref Number');
        } else {
          $("#utrnumber").attr('placeholder', 'UTR / Ref Number');
        }
      }
    }
  }

  $(document).on('change', 'input[name="sourceofpayment"]', updateSourceOfPaymentUI);
  $(document).ready(function() {
    updateSourceOfPaymentUI();
    // If pending credit notes already pre-loaded via PHP, render grid
    if (window.pendingCreditNotesList && window.pendingCreditNotesList.length > 0) {
      renderCreditNotesGrid(window.pendingCreditNotesList);
    }
  });

  $(document).on("click", "#fetchthedata", function () {
    getProvisionalOrderhtmlwithModal();
    
  });
  

  // Handle Order Checkbox Change
  $(document).on('change', '.order-checkbox', function () {
    const index = $(this).attr('id').split('_')[1];
    const $amountDiv = $(`#amount_div_${index}`);
    const $amtInput = $(`#order_amount_${index}`);
    const totalToAllocate = parseFloat($("#amount").val()) || 0; // Current Voucher Payment
    const orderTotal = parseFloat($(this).data('total')) || 0;
    const previouslyPaid = parseFloat($(this).data('paid')) || 0;
    const netUnpaidOrderTotal = Math.max(0, orderTotal - previouslyPaid);

    if (this.checked) {
      if (netUnpaidOrderTotal <= 0) {
        Swal.fire({
          title: "Order Fully Settled",
          text: "This order already has ₹0.00 balance (Already Paid: ₹" + previouslyPaid.toFixed(2) + ").",
          icon: "info"
        });
        $(this).prop('checked', false);
        $amtInput.val('0.00');
        $amountDiv.addClass('d-none');
        updateSelectedTotal();
        return;
      }

      // Calculate current total of other checked orders
      let currentAssigned = 0;
      $('.order-checkbox:checked').each(function () {
          const idx = $(this).attr('id').split('_')[1];
          if (idx !== index) {
            currentAssigned += parseFloat($(`#order_amount_${idx}`).val()) || 0;
          }
      });

      let availablePaymentBal = Math.max(0, totalToAllocate - currentAssigned);

      if (availablePaymentBal <= 0) {
          Swal.fire({
            title: "No Payment Balance Remaining",
            text: "The full voucher amount of ₹" + totalToAllocate.toFixed(2) + " is already assigned to other selected order(s).",
            icon: "warning"
          });
          $(this).prop('checked', false);
          $amtInput.val('0.00');
          $amountDiv.addClass('d-none');
          updateSelectedTotal();
          return;
      }

      // Auto-fill seamlessly with min(netUnpaidOrderTotal, availablePaymentBal)
      const autoAssign = Math.min(netUnpaidOrderTotal, availablePaymentBal);
      $amtInput.val(autoAssign.toFixed(2));
      $amountDiv.removeClass('d-none');

      // Sync modal box packing subchecks if present
      const plantAmt = parseFloat($amtInput.data('plant')) || 0;
      const boxAmt = parseFloat($amtInput.data('box')) || 0;
      if (boxAmt > 0) {
        if (Math.abs(autoAssign - boxAmt) < 0.01) {
          $(`#check_plant_${index}`).prop('checked', false);
          $(`#check_box_${index}`).prop('checked', true);
          $(`.modal-bpc-quick-btn[data-index="${index}"]`).removeClass('active btn-success btn-primary btn-warning text-white');
          $(`.modal-bpc-quick-btn[data-index="${index}"][data-mode="box"]`).addClass('active btn-warning text-dark');
        } else if (Math.abs(autoAssign - plantAmt) < 0.01) {
          $(`#check_plant_${index}`).prop('checked', true);
          $(`#check_box_${index}`).prop('checked', false);
          $(`.modal-bpc-quick-btn[data-index="${index}"]`).removeClass('active btn-success btn-primary btn-warning text-white');
          $(`.modal-bpc-quick-btn[data-index="${index}"][data-mode="plant"]`).addClass('active btn-primary text-white');
        } else {
          $(`#check_plant_${index}`).prop('checked', true);
          $(`#check_box_${index}`).prop('checked', true);
          $(`.modal-bpc-quick-btn[data-index="${index}"]`).removeClass('active btn-success btn-primary btn-warning text-white');
          $(`.modal-bpc-quick-btn[data-index="${index}"][data-mode="full"]`).addClass('active btn-success text-white');
        }
      }
    } else {
      $amtInput.val('0.00');
      $amountDiv.addClass('d-none');
    }
    updateSelectedTotal();
  });

  // Modal Box Packing Preset Buttons
  $(document).on('click', '.modal-bpc-quick-btn', function (e) {
    e.preventDefault();
    const idx = $(this).data('index');
    const mode = $(this).data('mode');
    const plant = parseFloat($(this).data('plant')) || 0;
    const box = parseFloat($(this).data('box')) || 0;
    const net = parseFloat($(this).data('net')) || 0;

    $(`.modal-bpc-quick-btn[data-index="${idx}"]`).removeClass('active btn-success btn-primary btn-warning text-white');
    $(this).addClass('active ' + (mode === 'full' ? 'btn-success text-white' : (mode === 'plant' ? 'btn-primary text-white' : 'btn-warning text-dark')));

    let assignAmt = net;
    if (mode === 'full') {
      $(`#check_plant_${idx}`).prop('checked', true);
      $(`#check_box_${idx}`).prop('checked', true);
      assignAmt = net;
    } else if (mode === 'plant') {
      $(`#check_plant_${idx}`).prop('checked', true);
      $(`#check_box_${idx}`).prop('checked', false);
      assignAmt = plant;
    } else if (mode === 'box') {
      $(`#check_plant_${idx}`).prop('checked', false);
      $(`#check_box_${idx}`).prop('checked', true);
      assignAmt = box;
    }

    $(`#order_amount_${idx}`).val(assignAmt.toFixed(2));

    // If only 1 order is checked, sync #amount and modal allocation banner
    if ($('.order-checkbox:checked').length === 1) {
      $('#amount').val(assignAmt.toFixed(2)).trigger('input');
      $('#pamotherModalheader .badge-warning').html(`<i class="fa fa-wallet mr-1"></i> Total to Allocate: ₹${assignAmt.toFixed(2)}`);
    }

    updateSelectedTotal();
  });

  // Modal Box Packing Sub-Checkboxes (Plant / Box toggle)
  $(document).on('change', '.modal-bpc-checkbox', function () {
    const idx = $(this).data('index');
    const plantChecked = $(`#check_plant_${idx}`).is(':checked');
    const boxChecked = $(`#check_box_${idx}`).is(':checked');
    const plant = parseFloat($(`#check_plant_${idx}`).data('val')) || 0;
    const box = parseFloat($(`#check_box_${idx}`).data('val')) || 0;

    let targetAmt = 0;
    let activeMode = '';
    if (plantChecked && boxChecked) {
      targetAmt = plant + box;
      activeMode = 'full';
    } else if (plantChecked) {
      targetAmt = plant;
      activeMode = 'plant';
    } else if (boxChecked) {
      targetAmt = box;
      activeMode = 'box';
    }

    $(`.modal-bpc-quick-btn[data-index="${idx}"]`).removeClass('active btn-success btn-primary btn-warning text-white');
    if (activeMode) {
      $(`.modal-bpc-quick-btn[data-index="${idx}"][data-mode="${activeMode}"]`).addClass('active ' + (activeMode === 'full' ? 'btn-success text-white' : (activeMode === 'plant' ? 'btn-primary text-white' : 'btn-warning text-dark')));
    }

    $(`#order_amount_${idx}`).val(targetAmt.toFixed(2));

    if ($('.order-checkbox:checked').length === 1) {
      $('#amount').val(targetAmt.toFixed(2)).trigger('input');
      $('#pamotherModalheader .badge-warning').html(`<i class="fa fa-wallet mr-1"></i> Total to Allocate: ₹${targetAmt.toFixed(2)}`);
    }

    updateSelectedTotal();
  });

  // Handle Individual Amount Change
  $(document).on('input', '.order-received-amount', function () {
    const totalToAllocate = parseFloat($("#amount").val()) || 0;
    const index = $(this).attr('id').split('_')[1];
    
    // Calculate total of OTHER orders
    let otherSum = 0;
    $('.order-checkbox:checked').each(function () {
      const idx = $(this).attr('id').split('_')[1];
      if (idx !== index) {
        otherSum += parseFloat($(`#order_amount_${idx}`).val()) || 0;
      }
    });

    const currentVal = parseFloat($(this).val()) || 0;
    const maxAllowedForThis = totalToAllocate - otherSum;

    if (currentVal > maxAllowedForThis) {
      const orderTotalForInput = parseFloat($(`#provisionalordercheck_${index}`).data('total')) || 0;
      const remainingOrderBal = Math.max(0, orderTotalForInput - maxAllowedForThis);

      Swal.fire({
        title: "Amount Exceeds Available Payment",
        html: `
          <div class="text-left font-weight-normal" style="font-size: 0.95rem; color: #1e293b;">
            <p class="mb-1"><b>Total Order Amount:</b> <span class="text-primary font-weight-bold">₹${orderTotalForInput.toFixed(2)}</span></p>
            <p class="mb-1"><b>Total Already Received Amount:</b> <span class="text-dark font-weight-bold">₹${totalToAllocate.toFixed(2)}</span></p>
            <p class="mb-1"><b>Assigned Amount for this Order:</b> <span class="text-success font-weight-bold">₹${maxAllowedForThis.toFixed(2)}</span></p>
            <p class="mb-1"><b>Remaining Order Balance:</b> <span class="text-danger font-weight-bold">₹${remainingOrderBal.toFixed(2)}</span></p>
            <hr class="my-2">
            <p class="mb-0 mt-2 text-dark">You can assign a maximum of <b>₹${maxAllowedForThis.toFixed(2)}</b> to this order.</p>
          </div>
        `,
        icon: "warning"
      });
      $(this).val(maxAllowedForThis.toFixed(2));
    }

    // Update subchecks states if box packing present
    const plantAmt = parseFloat($(this).data('plant')) || 0;
    const boxAmt = parseFloat($(this).data('box')) || 0;
    if (boxAmt > 0) {
      const val = parseFloat($(this).val()) || 0;
      if (Math.abs(val - boxAmt) < 0.01) {
        $(`#check_plant_${index}`).prop('checked', false);
        $(`#check_box_${index}`).prop('checked', true);
        $(`.modal-bpc-quick-btn[data-index="${index}"]`).removeClass('active btn-success btn-primary btn-warning text-white');
        $(`.modal-bpc-quick-btn[data-index="${index}"][data-mode="box"]`).addClass('active btn-warning text-dark');
      } else if (Math.abs(val - plantAmt) < 0.01) {
        $(`#check_plant_${index}`).prop('checked', true);
        $(`#check_box_${index}`).prop('checked', false);
        $(`.modal-bpc-quick-btn[data-index="${index}"]`).removeClass('active btn-success btn-primary btn-warning text-white');
        $(`.modal-bpc-quick-btn[data-index="${index}"][data-mode="plant"]`).addClass('active btn-primary text-white');
      } else if (Math.abs(val - (plantAmt + boxAmt)) < 0.01) {
        $(`#check_plant_${index}`).prop('checked', true);
        $(`#check_box_${index}`).prop('checked', true);
        $(`.modal-bpc-quick-btn[data-index="${index}"]`).removeClass('active btn-success btn-primary btn-warning text-white');
        $(`.modal-bpc-quick-btn[data-index="${index}"][data-mode="full"]`).addClass('active btn-success text-white');
      } else {
        $(`.modal-bpc-quick-btn[data-index="${index}"]`).removeClass('active btn-success btn-primary btn-warning text-white');
      }
    }

    updateSelectedTotal();
  });

  function updateSelectedTotal() {
    let sum = 0;
    const totalReceived = parseFloat($("#amount").val()) || 0;
    
    $('.order-checkbox:checked').each(function () {
      const index = $(this).attr('id').split('_')[1];
      const amtInput = $(`#order_amount_${index}`);
      const amt = parseFloat(amtInput.val()) || 0;
      sum += amt;
    });

    $('#selected_total_display').text(sum.toFixed(2));
    
    if (sum > totalReceived) {
      $('#selected_total_display').addClass('text-danger').removeClass('text-success');
      return false;
    } else {
      $('#selected_total_display').addClass('text-success').removeClass('text-danger');
      return true;
    }
  }

  // Handle Confirmation of Selection
  $(document).on('click', '#confirmOrderSelection', function () {
    const selectedOrders = [];
    let isValid = true;
    let sumConfirmed = 0;
    const totalReceived = parseFloat($("#amount").val()) || 0;

    $('.order-checkbox:checked').each(function () {
      const index = $(this).attr('id').split('_')[1];
      const psono = $(this).data('psono');
      const amtInput = $(`#order_amount_${index}`);
      const amt = parseFloat(amtInput.val()) || 0;

      if (amt <= 0) {
        isValid = false;
        amtInput.addClass('border-danger').focus();
        return false;
      }

      selectedOrders.push({
        psono: psono,
        amount: amt
      });
      sumConfirmed += amt;
    });

    if (selectedOrders.length === 0) {
      Swal.fire("Selection Required", "Please select at least one order.", "warning");
      return;
    }

    if (!isValid) {
      Swal.fire("Invalid Amount", "Please enter a valid amount for all selected orders.", "warning");
      return;
    }

    if (Math.abs(sumConfirmed - totalReceived) > 0.01) {
      if ($('.order-checkbox:checked').length === 1) {
        // Single order selected: auto-sync total voucher amount to confirmed allocation
        $('#amount').val(sumConfirmed.toFixed(2)).trigger('input');
        totalReceived = sumConfirmed;
      } else {
        const diffBalance = totalReceived - sumConfirmed;
        Swal.fire({
          title: "Allocation Mismatch",
          html: `
            <div class="text-left font-weight-normal" style="font-size: 0.95rem; color: #1e293b;">
              <p class="mb-1"><b>Total Already Received Amount:</b> <span class="text-dark font-weight-bold">₹${totalReceived.toFixed(2)}</span></p>
              <p class="mb-1"><b>Total Assigned Amount:</b> <span class="text-primary font-weight-bold">₹${sumConfirmed.toFixed(2)}</span></p>
              <p class="mb-1"><b>Unassigned Voucher Balance:</b> <span class="${diffBalance < 0 ? 'text-danger' : 'text-success'} font-weight-bold">₹${diffBalance.toFixed(2)}</span></p>
              <hr class="my-2">
              <p class="mb-0 mt-2 text-dark">The Total Assigned Amount (<b>₹${sumConfirmed.toFixed(2)}</b>) must match the Total Already Received Amount (<b>₹${totalReceived.toFixed(2)}</b>). Please adjust the allocation.</p>
            </div>
          `,
          icon: "error"
        });
        return;
      }
    }

    // Detail remarks if box packing was specified
    let boxBreakdownRemarks = [];
    $('.order-checkbox:checked').each(function () {
      const idx = $(this).attr('id').split('_')[1];
      const psono = $(this).data('psono');
      const plantChecked = $(`#check_plant_${idx}`).length ? $(`#check_plant_${idx}`).is(':checked') : true;
      const boxChecked = $(`#check_box_${idx}`).length ? $(`#check_box_${idx}`).is(':checked') : false;
      const boxAmt = parseFloat($(`#order_amount_${idx}`).data('box')) || 0;
      const plantAmt = parseFloat($(`#order_amount_${idx}`).data('plant')) || 0;

      if (boxAmt > 0) {
        if (boxChecked && !plantChecked) {
          boxBreakdownRemarks.push(`Box Packing Charges: ₹${boxAmt.toFixed(2)}`);
        } else if (!boxChecked && plantChecked) {
          boxBreakdownRemarks.push(`Plant Amount: ₹${plantAmt.toFixed(2)}`);
        } else if (boxChecked && plantChecked) {
          boxBreakdownRemarks.push(`Plant: ₹${plantAmt.toFixed(2)} + Box: ₹${boxAmt.toFixed(2)}`);
        }
      }
    });

    if (boxBreakdownRemarks.length > 0) {
      let currentRemarks = $('#remarks').val().trim();
      let extraNote = '(' + boxBreakdownRemarks.join('; ') + ')';
      if (!currentRemarks.includes(extraNote)) {
        if (!currentRemarks) {
          $('#remarks').val(`Payment against Provisional Order ${extraNote}`);
        } else if (!currentRemarks.includes('Box Packing') && !currentRemarks.includes('Plant Amount')) {
          $('#remarks').val(currentRemarks + ' ' + extraNote);
        }
      }
    }

    // Map to main form
    let orderDataJson = JSON.stringify(selectedOrders);
    if ($('#multi_order_data').length === 0) {
      $('#advancePaymentForm').append(`<input type="hidden" name="multi_order_data" id="multi_order_data">`);
    }
    $('#multi_order_data').val(orderDataJson);

    // Update main display
    $('#invoiceno').val(selectedOrders.map(o => o.psono).join(', '));

    window.orderSelectionConfirmed = true;
    $('#pamotherModal').modal('hide');
    $('#savepaymentdatabtn').show();
    $("#fetchthedata").hide();
    
    // Auto-submit the form after mapping
    $("#advancePaymentForm").submit();
  });

  $(document).ready(function () {
    var clienttemp = '<?php echo $clientname; ?>';
    // If clientname prefilled from PHP, try to fetch details?
    // Original logic: GetclientDetailsFrommaster("all", "all", "client", clienttemp);
    // This likely populated a dropdown or something, but we are using inputs now.
    // We'll keep it as is if needed by pjs_function.js or similar

    GetAccountdetailsFrommaster_option('receivedtoaccountid', '', 'receivedbankSection');

    // Base pending amount tracking
    window.basePendingAmount = <?php echo json_encode($initial_pending_amount); ?>;
    window.initialFieldAmount = parseFloat($('#amount').val()) || 0;
    if (!window.basePendingAmount || window.basePendingAmount <= 0) {
      window.basePendingAmount = window.initialFieldAmount;
    }

    function getEffectivePendingAmount() {
      if (window.basePendingAmount && window.basePendingAmount > 0) {
        return parseFloat(window.basePendingAmount);
      }
      if (typeof currentClientOrdersData !== 'undefined' && currentClientOrdersData && parseFloat(currentClientOrdersData.total_pending_balance) > 0) {
        return parseFloat(currentClientOrdersData.total_pending_balance);
      }
      if ($('#multi_order_data').length && $('#multi_order_data').val()) {
        try {
          let orders = JSON.parse($('#multi_order_data').val());
          if (Array.isArray(orders) && orders.length > 0) {
            let sumPending = 0;
            orders.forEach(o => {
              let tot = parseFloat(o.total_amount || 0);
              let pd = parseFloat(o.paid_amount || 0);
              if (tot > 0) sumPending += Math.max(0, tot - pd);
              else if (o.amount) sumPending += parseFloat(o.amount || 0);
            });
            if (sumPending > 0) return sumPending;
          }
        } catch(e) {}
      }
      if (window.initialFieldAmount && window.initialFieldAmount > 0) {
        return window.initialFieldAmount;
      }
      return 0;
    }

    let isUpdatingFromDiscount = false;
    let isUpdatingFromAmount = false;

    function handleAmountInput() {
      if (isUpdatingFromDiscount) return;
      isUpdatingFromAmount = true;

      let pending = getEffectivePendingAmount();
      let currentAmt = parseFloat($('#amount').val());
      if (isNaN(currentAmt)) currentAmt = 0;

      let disc = parseFloat($('#special_discount').val()) || 0;

      if (disc > 0 && pending > 0) {
        // Special discount was explicitly entered by user
        let payable = Math.max(0, pending - disc);
        let pct = ((disc / pending) * 100).toFixed(1);
        $('#special_discount_percent_badge').text(`${pct}% Off`).show();
        $('#special_discount_summary_text').html(`Pending: <b>₹${pending.toFixed(2)}</b> &minus; Special Discount: <b>₹${disc.toFixed(2)}</b> = Payable: <b>₹${payable.toFixed(2)}</b> | Received: <b class="text-success">₹${currentAmt.toFixed(2)}</b>`);
        $('#amount_balance_hint').html(`<span class="text-warning"><i class="fa fa-info-circle me-1"></i> Special Discount of ₹${disc.toFixed(2)} applied. Payable: ₹${payable.toFixed(2)} (Entered: ₹${currentAmt.toFixed(2)})</span>`).show();
      } else {
        // No special discount: Do NOT calculate or touch special discount!
        if (pending > 0 && currentAmt > 0 && currentAmt < pending) {
          let rem = (pending - currentAmt).toFixed(2);
          $('#amount_balance_hint').html(`<span class="text-secondary"><i class="fa fa-info-circle me-1"></i> Partial payment: ₹${currentAmt.toFixed(2)} of ₹${pending.toFixed(2)} (Remaining: ₹${rem})</span>`).show();
        } else if (pending > 0 && currentAmt >= pending) {
          if (currentAmt > pending) {
            let adv = (currentAmt - pending).toFixed(2);
            $('#amount_balance_hint').html(`<span class="text-info"><i class="fa fa-info-circle me-1"></i> Advance payment: ₹${adv} extra above pending balance of ₹${pending.toFixed(2)}</span>`).show();
          } else {
            $('#amount_balance_hint').html(`<span class="text-success"><i class="fa fa-check-circle me-1"></i> Full pending balance of ₹${pending.toFixed(2)}</span>`).show();
          }
        } else {
          $('#amount_balance_hint').hide();
        }
      }

      syncMultiOrderDataAmounts();
      window.orderSelectionConfirmed = false;
      isUpdatingFromAmount = false;
    }

    function updateAmountFromSpecialDiscount() {
      if (isUpdatingFromAmount) return;
      isUpdatingFromDiscount = true;

      let pending = getEffectivePendingAmount();
      let disc = parseFloat($('#special_discount').val());
      if (isNaN(disc) || disc < 0) disc = 0;

      if (pending > 0 && disc > pending) {
        disc = pending;
        $('#special_discount').val(disc.toFixed(2));
      }

      $('#otherdiscount').val(disc.toFixed(2));

      if (pending > 0) {
        let payable = Math.max(0, pending - disc);
        $('#amount').val(payable.toFixed(2));

        if (disc > 0) {
          let pct = ((disc / pending) * 100).toFixed(1);
          $('#special_discount_percent_badge').text(`${pct}% Off`).show();
          $('#special_discount_summary_text').html(`Pending: <b>₹${pending.toFixed(2)}</b> &minus; Special Discount: <b>₹${disc.toFixed(2)}</b> = Received: <b class="text-success">₹${payable.toFixed(2)}</b>`);
          $('#amount_balance_hint').html(`<span class="text-warning"><i class="fa fa-info-circle me-1"></i> Special Discount of ₹${disc.toFixed(2)} applied. Payable: ₹${payable.toFixed(2)}</span>`).show();
          $('#special_discount_wrapper').slideDown(150);
        } else {
          $('#special_discount_percent_badge').hide();
          $('#special_discount_summary_text').html('<i class="fa fa-info-circle text-primary me-1"></i> Deducted from pending balance and saved as other discount.');
          $('#amount_balance_hint').hide();
        }
      }

      syncMultiOrderDataAmounts();
      isUpdatingFromDiscount = false;
    }

    function syncMultiOrderDataAmounts() {
      if ($('#multi_order_data').length && $('#multi_order_data').val()) {
        try {
          let orders = JSON.parse($('#multi_order_data').val());
          if (Array.isArray(orders) && orders.length === 1) {
            orders[0].amount = parseFloat($('#amount').val()) || 0;
            orders[0].otherdiscount = parseFloat($('#special_discount').val()) || 0;
            orders[0].special_discount = parseFloat($('#special_discount').val()) || 0;
            $('#multi_order_data').val(JSON.stringify(orders));
          }
        } catch(e) {}
      }
    }

    window.onClientOrdersSummaryLoaded = function(summaryData) {
      if (window.basePendingAmount <= 0 && summaryData && summaryData.total_pending_balance > 0) {
        window.basePendingAmount = parseFloat(summaryData.total_pending_balance);
        handleAmountInput();
      }
    };

    $(document).on('input change', '#amount', handleAmountInput);
    $(document).on('input change', '#special_discount', updateAmountFromSpecialDiscount);

    $(document).on('click', '#btnToggleSpecialDiscount', function(e) {
      e.preventDefault();
      if ($('#special_discount_wrapper').is(':visible')) {
        let disc = parseFloat($('#special_discount').val()) || 0;
        if (disc <= 0) {
          $('#special_discount_wrapper').slideUp(150);
        } else {
          $('#special_discount').focus().select();
        }
      } else {
        $('#special_discount_wrapper').slideDown(150, function() {
          $('#special_discount').focus().select();
        });
      }
    });

    $(document).on('click', '#btnClearSpecialDiscount', function(e) {
      e.preventDefault();
      $('#special_discount').val('0.00');
      $('#otherdiscount').val('0.00');
      let pending = getEffectivePendingAmount();
      if (pending > 0) {
        $('#amount').val(pending.toFixed(2));
      }
      $('#special_discount_wrapper').slideUp(150);
      $('#special_discount_percent_badge').hide();
      $('#amount_balance_hint').hide();
      syncMultiOrderDataAmounts();
    });

    <?php 
    $isPassedOrderCancelled = false;
    if (!empty($posnoinfo)) {
        $st = strtolower(trim((string)($posnoinfo[0]['status'] ?? '')));
        if (in_array($st, ['cancel', 'cancelled', 'deleted', 'removed'], true)) {
            $isPassedOrderCancelled = true;
        }
    }
    ?>
    <?php if ($isPassedOrderCancelled): ?>
    Swal.fire({
      icon: 'error',
      title: 'Order Cancelled',
      text: 'Provisional Order #<?php echo htmlspecialchars($psono); ?> is cancelled and cannot receive payments.',
      confirmButtonColor: '#d33'
    });
    $('#savepaymentdatabtn').hide();
    $('#fetchthedata').hide();
    $('#amount_balance_hint').html('<span class="text-danger font-weight-bold"><i class="fa fa-ban mr-1"></i> This provisional order is Cancelled. Payments cannot be accepted.</span>').show();
    <?php elseif (!empty($psono)): ?>
    // Auto-map the passed provisional order so user does not need to click "Fetch Data" manually
    let autoOrder = [{
        psono: <?php echo json_encode($psono); ?>,
        total_amount: <?php echo floatval($posnoinfo[0]['total_amount'] ?? 0); ?>,
        paid_amount: <?php echo floatval($posnoinfo[0]['paid_amount'] ?? 0); ?>,
        allocated_amount: <?php echo floatval($posnoinfo[0]['allocated_amount'] ?? 0); ?>,
        amount: <?php echo floatval($default_amount); ?>,
        items_amount: <?php echo floatval($order_items_amt); ?>,
        box_packing_amount: <?php echo floatval($order_box_packing_amt); ?>,
        otherdiscount: 0,
        special_discount: 0
    }];
    if ($('#multi_order_data').length === 0) {
      $('#advancePaymentForm').append('<input type="hidden" name="multi_order_data" id="multi_order_data">');
    }
    $('#multi_order_data').val(JSON.stringify(autoOrder));
    $('#invoiceno').val(<?php echo json_encode($psono); ?>);
    $('#savepaymentdatabtn').show();
    $('#fetchthedata').hide();
    <?php endif; ?>

    // Handle Quick Box Packing Presets on Main Form
    $(document).on('click', '.bpc-preset-btn', function(e) {
      e.preventDefault();
      $('.bpc-preset-btn').removeClass('active btn-success btn-primary btn-warning text-white');
      $('.bpc-preset-btn').each(function() {
        const t = $(this).data('type');
        $(this).addClass('btn-outline-' + (t === 'full' ? 'success' : (t === 'plant' ? 'primary' : 'warning')));
      });

      const type = $(this).data('type');
      $(this).removeClass('btn-outline-success btn-outline-primary btn-outline-warning').addClass('active ' + (type === 'full' ? 'btn-success text-white' : (type === 'plant' ? 'btn-primary text-white' : 'btn-warning text-dark')));

      const amt = parseFloat($(this).data('amt')) || 0;
      $('#amount').val(amt.toFixed(2)).trigger('input');

      // Update remarks cleanly
      const targetPsono = '<?php echo $psono ?? ""; ?>' || $("#invoiceno").val() || "";
      if (targetPsono) {
        let remarkText = `Payment against Provisional Order #${targetPsono}`;
        if (type === 'box') {
          remarkText += ` (Box Packing Charges Only: ₹${amt.toFixed(2)})`;
        } else if (type === 'plant') {
          remarkText += ` (Plant Amount Only: ₹${amt.toFixed(2)})`;
        } else if (type === 'full') {
          const boxAmt = parseFloat($('#bpc_display_boxamt').text()) || 0;
          const plantAmt = parseFloat($('#bpc_display_plantamt').text()) || 0;
          if (boxAmt > 0) {
            remarkText += ` (Plant: ₹${plantAmt.toFixed(2)} + Box Packing: ₹${boxAmt.toFixed(2)})`;
          }
        }
        $('#remarks').val(remarkText);
      }
    });

    window.orderSelectionConfirmed = false;

    // Initialize amount hint on load without auto-calculating special discount
    handleAmountInput();

    // Reset confirmed state if amount or purpose changes
    $(document).on("input change", "#amount", function() {
      window.orderSelectionConfirmed = false;
    });

    $(document).on("change", "input[name='purposeofamount']", function() {
      window.orderSelectionConfirmed = false;
    });

    // When clicking "Save Payment Info", open the provisional order allocation modal if not already confirmed
    $(document).on("click", "#savepaymentdatabtn", function (e) {
      if (!window.orderSelectionConfirmed) {
        e.preventDefault();
        getProvisionalOrderhtmlwithModal();
        return false;
      }
    });

    $("#advancePaymentForm").on("submit", function (e) {
      if (!window.orderSelectionConfirmed) {
        e.preventDefault();
        getProvisionalOrderhtmlwithModal();
        return false;
      }
      e.preventDefault();
      const sourceofamount = $('input[name="sourceofpayment"]:checked').val() || "";

      const receivedBy = $("#casgreceivedby").val()?.trim() || "";
      const receivedLocation = $("#casgreceuvelocation").val()?.trim() || "";
      const amount = $("#amount").val()?.trim() || "";
      const clientmobileno = $("#clientmobileno").val()?.trim() || "";
      const clientname = $("#clientname").val()?.trim() || "";
      const paymentDate = $("#paymentdate").val()?.trim() || "";
      const utrnumber = $("#utrnumber").val()?.trim() || "";

      // Basic Validations
      if (!sourceofamount) {
        Swal.fire("Missing Field", "Please select source of payment", "warning"); return;
      }
      if (!clientname) {
        Swal.fire("Missing Field", "Client name is required", "warning"); return;
      }
      if (!clientmobileno) {
        Swal.fire("Missing Field", "Client mobile number is required", "warning"); return;
      }
      if (!/^[6-9]\d{9}$/.test(clientmobileno)) {
        Swal.fire("Invalid Mobile", "Enter a valid 10-digit mobile number", "warning"); return;
      }
      if (!amount || isNaN(amount) || Number(amount) <= 0) {
        Swal.fire("Invalid Amount", "Please enter a valid amount", "warning"); return;
      }
      if (sourceofamount === "creditnote") {
        const cnId = $("#creditnote_id").val();
        const cnNo = $("#creditnote_no").val();
        if (!cnId || !cnNo) {
          Swal.fire("Missing Credit Note", "Please choose an active Credit Note from the grid to proceed.", "warning");
          return;
        }
      } else {
        if (!paymentDate) {
          Swal.fire("Missing Payment Date", "Payment date is compulsory.", "warning");
          $("#paymentdate").focus();
          return;
        }

        const fileInput = document.getElementById('paymentreceipt');
        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
          Swal.fire("Missing Document", "Proof of Payment document is missing. Received payment details cannot be saved without attaching payment proof document (JPG, PNG, PDF).", "warning");
          $("#paymentreceipt").focus();
          return;
        }

        if (sourceofamount === "cash") {
          // For Cash: Transaction No is NOT required
          if (!receivedBy || !receivedLocation) {
            Swal.fire("Missing Cash Details", "Cash received by and received location are required", "warning");
            return;
          }
        } else if (sourceofamount === "cheque") {
          // For Cheque: Transaction No / Cheque No is COMPULSORY
          if (!utrnumber) {
            Swal.fire("Missing Cheque Number", "Cheque / Transaction No is compulsory for Cheque payment.", "warning");
            $("#utrnumber").focus();
            return;
          }
          const receivedToAccountId = $("#receivedtoaccountid").val()?.trim() || "";
          if (!receivedToAccountId) {
            Swal.fire("Missing Bank Account", "Please select deposited bank account.", "warning");
            $("#receivedtoaccountid").focus();
            return;
          }
        } else if (sourceofamount === "upi") {
          // For UPI / Bank: Transaction No is COMPULSORY
          if (!utrnumber) {
            Swal.fire("Missing Transaction No", "Transaction No / UTR Number is compulsory for UPI / Bank payment.", "warning");
            $("#utrnumber").focus();
            return;
          }
          const receivedToAccountId = $("#receivedtoaccountid").val()?.trim() || "";
          if (!receivedToAccountId) {
            Swal.fire("Missing Bank Account", "Please select deposited bank account.", "warning");
            $("#receivedtoaccountid").focus();
            return;
          }
        } else {
          // Any other non-cash payment method
          if (!utrnumber) {
            Swal.fire("Missing Transaction No", "Transaction No / Reference Number is compulsory.", "warning");
            $("#utrnumber").focus();
            return;
          }
        }
      }

      const selectedPurpose = $('input[name="purposeofamount"]:checked').val();
      const hasSelectedOrders = $('#multi_order_data').val() && $('#multi_order_data').val().length > 2;

      if (selectedPurpose === 'advancepayment' && !hasSelectedOrders && !window.bypassPendingOrderPrompt) {
        if (typeof checkClientPendingOrdersAndPrompt === "function") {
          const hasPrompted = checkClientPendingOrdersAndPrompt(amount, function() {
            // User confirmed Save as Future Advance
            window.bypassPendingOrderPrompt = true;
            $("#advancePaymentForm").submit();
          }, function() {
            // User selected Against Invoice -> switch purpose and open orders
            $("#purpose_invoice").prop("checked", true).trigger("change");
            getProvisionalOrderhtmlwithModal();
          });
          if (hasPrompted) return;
        }
      }
      window.bypassPendingOrderPrompt = false;

      const isCreditNote = (sourceofamount === "creditnote");
      const targetPso = $('#invoiceno').val() || '<?php echo $psono; ?>';
      const confirmTitle = isCreditNote ? "Confirm Credit Note Payment" : "Confirm Payment";
      const confirmText = isCreditNote 
        ? `Are you sure you want to apply Credit Note ${$("#creditnote_no").val()} (₹${Number(amount).toFixed(2)}) against Provisional Order #${targetPso} for confirmation and fulfillment?`
        : "Are you sure you want to submit this payment?";

      Swal.fire({
        title: confirmTitle,
        text: confirmText,
        icon: "question",
        showCancelButton: true,
        confirmButtonText: isCreditNote ? "Yes, Confirm & Fulfill" : "Yes, Submit",
        confirmButtonColor: isCreditNote ? "#7c3aed" : "#1e3c72"
      }).then((result) => {
        if (!result.isConfirmed) return;

        let formData = new FormData($("#advancePaymentForm")[0]);
        $.ajax({
          url: "modal/paymentadvice_modal.php?action=SaveAdvancePaymentManulal",
          type: "POST",
          data: formData,
          contentType: false,
          processData: false,
          beforeSend: function () {
            Swal.fire({
              title: "Processing...",
              allowOutsideClick: false,
              didOpen: () => Swal.showLoading()
            });
          },
          success: function (response) {
            Swal.close();
            try {
              // Attempt to parse JSON (handle if response has extra text)
              // Sometimes PHP appends warnings/errors before JSON
              let jsonStr = response;
              if (typeof response !== 'object') {
                // Try to extract JSON from string if mixed
                const match = response.match(/\{.*\}/);
                if (match) jsonStr = match[0];
              }

              let res = (typeof jsonStr === 'object') ? jsonStr : JSON.parse(jsonStr);

              if (res.status === "success") {
                // Only send client confirmation WhatsApp and Email if payment is verified and final sales order generated
                if ((res.is_verified === true || res.verification === 'verified') && res.sono) {
                  if (res.clientid && res.psono) {
                    SendEmailSalesOrderConfirm(res.clientid, res.psono, res.sono, res.receiptpath || '', function () { });
                  }
                  whatsapp_sendsalesorder(res.clientid, res.psono, res.receiptpath || '', function(rrs){
                  });
                }

                Swal.fire({
                  title: isCreditNote ? "Order Confirmed & Fulfilled" : "Payment Advice Submitted",
                  text: res.message || "Payment advice logged successfully.",
                  icon: "success",
                  confirmButtonColor: isCreditNote ? "#7c3aed" : "#1e3c72"
                }).then(() => {
                  if (window.parent && typeof window.parent.onPaymentAdviceModalSaved === 'function') {
                    window.parent.onPaymentAdviceModalSaved(res);
                  } else {
                    window.location.reload();
                  }
                });
              } else if (res.status === "same_day_duplicate_warning") {
                Swal.fire({
                  title: "Existing Payment on Same Day",
                  text: res.message,
                  icon: "warning",
                  showCancelButton: true,
                  confirmButtonColor: "#1e3c72",
                  cancelButtonColor: "#d33",
                  confirmButtonText: "Yes, Save Additional Payment",
                  cancelButtonText: "No, Cancel"
                }).then((confirmRes) => {
                  if (confirmRes.isConfirmed) {
                    formData.append('confirm_same_day', '1');
                    $.ajax({
                      url: "modal/paymentadvice_modal.php?action=SaveAdvancePaymentManulal",
                      type: "POST",
                      data: formData,
                      contentType: false,
                      processData: false,
                      beforeSend: function () {
                        Swal.fire({
                          title: "Processing...",
                          allowOutsideClick: false,
                          didOpen: () => Swal.showLoading()
                        });
                      },
                      success: function (subResponse) {
                        Swal.close();
                        try {
                          let subJsonStr = subResponse;
                          if (typeof subResponse !== 'object') {
                            const match = subResponse.match(/\{.*\}/);
                            if (match) subJsonStr = match[0];
                          }
                          let subRes = (typeof subJsonStr === 'object') ? subJsonStr : JSON.parse(subJsonStr);
                          if (subRes.status === "success") {
                            Swal.fire({
                              title: "Payment Advice Submitted",
                              text: subRes.message || "Payment advice logged successfully.",
                              icon: "success",
                              confirmButtonColor: "#1e3c72"
                            }).then(() => {
                              if (window.parent && typeof window.parent.onPaymentAdviceModalSaved === 'function') {
                                window.parent.onPaymentAdviceModalSaved(subRes);
                              } else {
                                window.location.reload();
                              }
                            });
                          } else {
                            Swal.fire("Error", subRes.message || "Failed to save payment", "error");
                          }
                        } catch (e) {
                          Swal.fire("Error", "Unexpected server response", "error");
                        }
                      },
                      error: function () {
                        Swal.close();
                        Swal.fire("Error", "Request failed to process", "error");
                      }
                    });
                  }
                });
              } else {
                Swal.fire({
                  title: "Payment Not Saved",
                  text: res.message || "Could not save payment details.",
                  icon: "error",
                  confirmButtonColor: "#d33"
                });
              }
            } catch (err) {
              console.error(err, response);
              Swal.fire("Error", "Unexpected server response", "error");
            }
          },
          error: function (xhr) {
            console.error(xhr);
            Swal.fire("Error", "Failed to save payment", "error");
          }
        });
      });
    });

  });

  function getProvisionalOrderhtmlwithModal() {
    const clientId = $("#clientid").val();
    const clientname = $("#clientname").val()?.trim() || "";
    const sourceofamount = $('input[name="sourceofpayment"]:checked').val() || "";
    const amount = $("#amount").val()?.trim() || "";
    const clientmobileno = $("#clientmobileno").val()?.trim() || "";

    // Basic Validations before opening modal
    if (sourceofamount === "creditnote") {
      const cnId = $("#creditnote_id").val();
      if (!cnId) {
        Swal.fire("Select Credit Note", "Please select a Credit Note from the grid first.", "warning");
        return;
      }
    }
    if (!clientname) {
      Swal.fire("Missing Field", "Client name is required", "warning"); return;
    }
    if (!clientmobileno) {
      Swal.fire("Missing Field", "Client mobile number is required", "warning"); return;
    }
    if (!/^[6-9]\d{9}$/.test(clientmobileno)) {
      Swal.fire("Invalid Mobile", "Enter a valid 10-digit mobile number", "warning"); return;
    }
    if (!amount || isNaN(amount) || Number(amount) <= 0) {
      Swal.fire("Invalid Amount", "Please enter a valid amount", "warning"); return;
    }

    let filterby = "clientid";
    let filterbyvalue = clientId;
    if (!filterbyvalue && clientmobileno) {
      filterby = "mobileno";
      filterbyvalue = clientmobileno;
    }

    $('#pamotherModalContent').html('');

    if (clientmobileno || clientId) {
      let extraParams = {
        purpose: $('input[name="purposeofamount"]:checked').val() || '<?php echo $passed_purpose; ?>',
        psono: '<?php echo $psono; ?>' || $('#invoiceno').val() || '',
        sono: '<?php echo $disp_sono; ?>',
        invoiceno: '<?php echo $disp_dinvoiceno; ?>'
      };
      GetProvisionaOrderData(filterby, filterbyvalue, function (res) {
        console.log(res);
        // Filter out cancelled orders and fully settled orders (balance <= 0)
        const pendingOrders = (res || []).filter(function (item) {
          const st = String(item.status || '').toLowerCase().trim();
          if (st === 'cancelled' || st === 'cancel' || st === 'deleted' || st === 'removed') {
            return false;
          }
          if (item.items && item.items.length > 0) {
            const hasActiveItems = item.items.some(function(it) {
              const itSt = String(it.status || '').toLowerCase().trim();
              return itSt !== 'cancelled' && itSt !== 'cancel' && itSt !== 'deleted' && itSt !== 'removed';
            });
            if (!hasActiveItems) {
              return false;
            }
          }
          const bal = parseFloat(item.total_amount || 0) - parseFloat(item.paid_amount || 0);
          return bal > 0.009;
        });

        if (pendingOrders.length > 0) {
          $('#pamotherModal').modal("show");
          const totalToAllocate = parseFloat($("#amount").val()) || 0;
          const isCn = (sourceofamount === 'creditnote');
          const hasDispatchedOrders = pendingOrders.some(function(it) { return !!it.is_dispatch_invoice; });
          let modalTitle = hasDispatchedOrders 
            ? `Receive Amount Against Dispatched Invoice - ${clientname}` 
            : `Receive Amount Against Provisional Order - ${clientname}`;
          if (isCn) {
            modalTitle = `Apply Credit Note (${$("#creditnote_no").val()}) to Fulfill Order - ${clientname}`;
          }
          $('#pamotherModalheader').html(`
            <div class="d-flex justify-content-between align-items-center w-100 mr-4">
              <span><i class="fa fa-file-invoice mr-2"></i>${modalTitle}</span>
              <span class="badge ${isCn ? 'badge-primary' : 'badge-warning'} p-2" style="font-size: 0.9rem; ${isCn ? 'background-color: #7c3aed;' : ''}">
                <i class="fa fa-wallet mr-1"></i> Total to Allocate: ₹${totalToAllocate.toFixed(2)}
              </span>
            </div>
          `);
          let alertNotice = isCn 
            ? 'Select the provisional order to apply Credit Note ₹' + totalToAllocate.toFixed(2) + ' for fulfillment and confirmation.' 
            : 'Select orders and enter the amount for each. The sum cannot exceed ₹' + totalToAllocate.toFixed(2);
          let content = '<div class="alert alert-info border-left-primary py-2 mb-3">' + alertNotice + '</div>';

          content += '<div class="list-group">';

          for (let i = 0; i < pendingOrders.length; i++) {
            const item = pendingOrders[i];
            const isEmergencyPending = (
              item.status === 'Emergency Sowing' ||
              item.status === 'Pending Approval for Emergency Sowing' ||
              (item.status && String(item.status).toLowerCase().includes('emergency'))
            );

            let statusBadge = '';
            if (isEmergencyPending) {
              statusBadge = '<span class="badge badge-danger text-white"><i class="fa fa-exclamation-triangle mr-1"></i>Emergency Sowing Approval Pending</span>';
            } else if (String(item.status || '').toLowerCase() === 'confirmed') {
              statusBadge = '<span class="badge badge-success">Confirmed</span>';
            } else if (String(item.status || '').toLowerCase().includes('cancel')) {
              statusBadge = '<span class="badge badge-danger">Cancelled</span>';
            } else {
              statusBadge = '<span class="badge badge-warning">Draft</span>';
            }

            let boxPackingAmount = parseFloat(item.box_packing_amount || 0);
            let itemsPlantAmount = parseFloat(item.items_amount || 0);
            if (itemsPlantAmount <= 0) {
              itemsPlantAmount = Math.max(0, parseFloat(item.total_amount || 0) - boxPackingAmount - parseFloat(item.freight_amount || 0));
            }

            let correctTotalAmount = parseFloat(item.total_amount || 0);
            if (boxPackingAmount > 0 && correctTotalAmount < (itemsPlantAmount + boxPackingAmount)) {
              correctTotalAmount = Math.round((itemsPlantAmount + boxPackingAmount + parseFloat(item.freight_amount || 0)) * 100) / 100;
              item.total_amount = correctTotalAmount;
            }

            const netBalance = Math.max(0, correctTotalAmount - parseFloat(item.paid_amount || 0));

            let productDetailsHtml = '';
            if (item.items && item.items.length > 0) {
              const activeItems = item.items.filter(function(it) {
                const itSt = String(it.status || '').toLowerCase().trim();
                return itSt !== 'cancelled' && itSt !== 'cancel' && itSt !== 'deleted' && itSt !== 'removed';
              });
              if (activeItems.length > 0) {
                productDetailsHtml = '<div class="mt-2 bg-light p-2 rounded border" style="background-color: #f8fafc !important;">';
                activeItems.forEach(function(it, idx) {
                  productDetailsHtml += `
                    <div class="small text-dark ${idx > 0 ? 'border-top pt-1 mt-1' : ''}">
                      <div class="d-flex justify-content-between align-items-center">
                        <span class="font-weight-bold text-dark"><i class="fa fa-cube text-primary mr-1"></i>${it.product_name}</span>
                        <span class="badge badge-info px-2 py-1">Qty: ${it.quantity}</span>
                      </div>
                      <div class="d-flex justify-content-between text-muted mt-1" style="font-size: 0.82rem;">
                        <span>Rate: ₹${parseFloat(it.rate || 0).toFixed(2)} | Amount: <b class="text-dark">₹${parseFloat(it.amount || 0).toFixed(2)}</b></span>
                        <span><i class="fa fa-calendar-day mr-1 text-secondary"></i>Delivery: <b>${changeDateFormatYmd_dMY(it.expecteddeliverydate)}</b></span>
                      </div>
                    </div>`;
                });
              }
              if (parseFloat(item.freight_amount || 0) > 0) {
                productDetailsHtml += `
                  <div class="small text-muted border-top pt-1 mt-1 d-flex justify-content-between align-items-center" style="font-size: 0.82rem;">
                    <span><i class="fa fa-truck text-secondary mr-1"></i><b>Freight Charges:</b></span>
                    <span class="font-weight-bold text-dark">₹${parseFloat(item.freight_amount).toFixed(2)}</span>
                  </div>`;
              }
              if (parseFloat(item.box_packing_amount || 0) > 0) {
                let boxDesc = '';
                if (item.box_packing_details && item.box_packing_details.noofbox) {
                  boxDesc = ` (${item.box_packing_details.noofbox} Box @ ₹${parseFloat(item.box_packing_details.chargesperbox || 0).toFixed(2)}/box${item.box_packing_details.pickuppoint ? ' via ' + item.box_packing_details.pickuppoint : ''})`;
                }
                productDetailsHtml += `
                  <div class="small border-top pt-1 mt-1 d-flex justify-content-between align-items-center rounded p-1" style="font-size: 0.82rem; background: #fffdf0; border: 1px dashed #fde047 !important;">
                    <span class="text-dark"><i class="fa fa-box-open text-warning mr-1"></i><b>Box Packing Charges:</b><small class="text-muted">${boxDesc}</small></span>
                    <span class="font-weight-bold text-dark">₹${parseFloat(item.box_packing_amount).toFixed(2)}</span>
                  </div>`;
              }
              productDetailsHtml += '</div>';
            } else {
              productDetailsHtml = `
                <div class="mt-2">
                    <small class="text-dark d-block"><b>Product:</b> ${item.product_name} (Qty: ${item.quantity})</small>
                    <small class="text-dark d-block"><b>Expected Delivery:</b> ${changeDateFormatYmd_dMY(item.expecteddeliverydate)}</small>
                </div>`;
            }

            let emergencyNoticeHtml = '';
            let disabledAttr = '';
            if (isEmergencyPending) {
              disabledAttr = 'disabled';
              emergencyNoticeHtml = `
                <div class="alert alert-danger py-2 px-3 mt-2 mb-0 d-flex align-items-center" style="font-size: 0.83rem; border-left: 4px solid #dc3545; background-color: #fff5f5;">
                  <i class="fa fa-exclamation-triangle text-danger mr-2 fa-lg"></i>
                  <div>
                    <strong class="text-danger">Payment Blocked:</strong> This provisional order is in <strong>Emergency Sowing Approval Pending</strong>. You cannot proceed with payment until emergency sowing is approved.
                  </div>
                </div>`;
            }

            if (itemsPlantAmount < 0) itemsPlantAmount = 0;

            if (boxPackingAmount > 0) {
              $('#bpc_display_boxamt').text(boxPackingAmount.toFixed(2));
              $('#bpc_display_boxamt_sub').text(boxPackingAmount.toFixed(2));
              $('#bpc_display_plantamt').text(itemsPlantAmount.toFixed(2));
              $('#bpc_display_totalamt').text(correctTotalAmount.toFixed(2));
              $('.bpc-btn-full-amt').text(correctTotalAmount.toFixed(2));
              $('.bpc-btn-plant-amt').text(itemsPlantAmount.toFixed(2));
              $('.bpc-btn-box-amt').text(boxPackingAmount.toFixed(2));
              $('.bpc-preset-btn[data-type="full"]').data('amt', correctTotalAmount.toFixed(2));
              $('.bpc-preset-btn[data-type="plant"]').data('amt', itemsPlantAmount.toFixed(2));
              $('.bpc-preset-btn[data-type="box"]').data('amt', boxPackingAmount.toFixed(2));
              $('#boxPackingChargeNotice').removeClass('d-none');
            }

            let boxPackingControlHtml = '';
            if (boxPackingAmount > 0) {
              boxPackingControlHtml = `
                <div class="modal-bpc-container mt-2 p-2 rounded border" style="background: #fffdf5; border-color: #fef08a !important; border-left: 3px solid #eab308 !important;">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="small font-weight-bold text-dark" style="font-size: 0.78rem;">
                      <i class="fa fa-sliders-h text-warning mr-1"></i> Receive Option:
                    </span>
                    <div class="btn-group btn-group-sm">
                      <button type="button" class="btn btn-xs btn-outline-success modal-bpc-quick-btn active" data-index="${i}" data-mode="full" data-plant="${itemsPlantAmount.toFixed(2)}" data-box="${boxPackingAmount.toFixed(2)}" data-net="${netBalance.toFixed(2)}" style="font-size: 0.70rem; padding: 1px 5px;">Full (₹${netBalance.toFixed(2)})</button>
                      <button type="button" class="btn btn-xs btn-outline-primary modal-bpc-quick-btn" data-index="${i}" data-mode="plant" data-plant="${itemsPlantAmount.toFixed(2)}" data-box="${boxPackingAmount.toFixed(2)}" data-net="${netBalance.toFixed(2)}" style="font-size: 0.70rem; padding: 1px 5px;">Plant Only (₹${itemsPlantAmount.toFixed(2)})</button>
                      <button type="button" class="btn btn-xs btn-outline-warning modal-bpc-quick-btn text-dark" data-index="${i}" data-mode="box" data-plant="${itemsPlantAmount.toFixed(2)}" data-box="${boxPackingAmount.toFixed(2)}" data-net="${netBalance.toFixed(2)}" style="font-size: 0.70rem; padding: 1px 5px;">Box Only (₹${boxPackingAmount.toFixed(2)})</button>
                    </div>
                  </div>
                  <div class="d-flex justify-content-between align-items-center pt-1 border-top" style="border-color: #fef08a !important; font-size: 0.76rem;">
                    <div class="custom-control custom-checkbox custom-control-inline m-0">
                      <input type="checkbox" class="custom-control-input modal-bpc-checkbox" id="check_plant_${i}" data-index="${i}" data-type="plant" data-val="${itemsPlantAmount.toFixed(2)}" checked>
                      <label class="custom-control-label text-dark" for="check_plant_${i}">Plant: <b>₹${itemsPlantAmount.toFixed(2)}</b></label>
                    </div>
                    <div class="custom-control custom-checkbox custom-control-inline m-0">
                      <input type="checkbox" class="custom-control-input modal-bpc-checkbox" id="check_box_${i}" data-index="${i}" data-type="box" data-val="${boxPackingAmount.toFixed(2)}" checked>
                      <label class="custom-control-label text-dark" for="check_box_${i}">Box Packing: <b>₹${boxPackingAmount.toFixed(2)}</b></label>
                    </div>
                  </div>
                </div>
              `;
            }

            const isDisp = !!item.is_dispatch_invoice;
            let orderLabel = `Order No: ${item.psono}`;
            if (isDisp) {
              if (item.dinvoiceno) {
                orderLabel += ` <span class="badge badge-success ml-2 px-2 py-1"><i class="fa fa-receipt mr-1"></i>Inv: ${item.dinvoiceno}</span>`;
              } else if (item.sono) {
                orderLabel += ` <span class="badge badge-primary ml-2 px-2 py-1"><i class="fa fa-file-invoice mr-1"></i>SO: ${item.sono}</span>`;
              }
              orderLabel += ` <span class="badge badge-info ml-1 px-2 py-1"><i class="fa fa-truck mr-1"></i>Dispatched Invoice</span>`;
            }

            content += `
                <div class="list-group-item list-group-item-action flex-column align-items-start border-bottom ${isEmergencyPending ? 'bg-light opacity-90' : ''}">
                    <div class="d-flex w-100 justify-content-between align-items-start">
                        <div style="flex: 1;">
                            <div class="custom-control custom-checkbox">
                                 <input type="checkbox" id="provisionalordercheck_${i}" name="provisionalordercheck" class="custom-control-input order-checkbox"
                                    ${disabledAttr}
                                    data-psono="${item.psono}"
                                    data-sono="${item.sono || ''}"
                                    data-dinvoiceno="${item.dinvoiceno || ''}"
                                    data-psodbsno="${item.id}"
                                    data-username="${item.clientname}"
                                    data-total="${item.total_amount}"
                                    data-paid="${item.paid_amount || 0}">
                                 <label class="custom-control-label font-weight-bold h6 mb-0 ${isEmergencyPending ? 'text-muted' : ''}" for="provisionalordercheck_${i}">${orderLabel}</label>
                            </div>
                            ${productDetailsHtml}
                            ${emergencyNoticeHtml}
                        </div>
                        <div class="text-right text-end ml-3" style="min-width: 200px;">
                            <div class="small text-dark mb-1"><b>${isDisp ? 'Dispatched Invoice Amount:' : 'Order Amount:'}</b> <span class="text-primary font-weight-bold">₹${parseFloat(item.total_amount).toFixed(2)}</span></div>
                            <div class="small text-dark mb-1"><b>Already Paid Amount:</b> <span class="text-success font-weight-bold">₹${parseFloat(item.paid_amount || 0).toFixed(2)}</span></div>
                            <div class="small text-dark"><b>Balance Amount:</b> <span class="text-danger font-weight-bold">₹${netBalance.toFixed(2)}</span></div>
                        </div>
                    </div>
                    <div class="mt-3 order-amount-input-div d-none" id="amount_div_${i}">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                          <label class="small font-weight-bold text-success mb-0">Enter Received Amount for this Order:</label>
                          ${boxPackingAmount > 0 ? '<span class="badge badge-warning text-dark font-weight-bold" style="font-size: 0.70rem;"><i class="fa fa-box mr-1"></i>Box Packing Included</span>' : ''}
                        </div>
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend">
                                <span class="input-group-text">₹</span>
                            </div>
                            <input type="number" class="form-control order-received-amount" 
                                   id="order_amount_${i}" 
                                   placeholder="Enter amount" 
                                   value="0.00"
                                   max="${netBalance.toFixed(2)}"
                                   step="0.01"
                                   data-plant="${itemsPlantAmount.toFixed(2)}"
                                   data-box="${boxPackingAmount.toFixed(2)}">
                        </div>
                        ${boxPackingControlHtml}
                    </div>
                    <div class="d-flex w-100 justify-content-between mt-2 align-items-center">
                        <small class="text-dark font-weight-bold"><i class="fa fa-calendar-alt"></i> ${item.created_at}</small>
                        ${statusBadge}
                    </div>
                </div>`;
          }
          content += '</div>';
          content += `
            <div class="modal-footer bg-light mt-3">
              <div class="w-100 d-flex justify-content-between align-items-center">
                <div class="font-weight-bold">
                   <span class="text-dark mr-2">Allocated:</span>
                   <span id="selected_total_display" class="h5 mb-0 text-success">₹0.00</span>
                </div>
                <button type="button" class="btn btn-primary" id="confirmOrderSelection">
                  <i class="fa fa-check-circle mr-1"></i> Confirm Selection
                </button>
              </div>
            </div>`;

          $('#pamotherModalContent').html(content);

          // Auto-select target provisional order if specified, or auto-select if only 1 pending order
          const targetPsono = '<?php echo $psono ?? ""; ?>' || $("#invoiceno").val() || "";
          if (targetPsono && $(`.order-checkbox[data-psono="${targetPsono}"]`).length > 0) {
            $(`.order-checkbox[data-psono="${targetPsono}"]`).prop('checked', true).trigger('change');
          } else {
            const availableCheckboxes = $('.order-checkbox').filter(function() {
              const tot = parseFloat($(this).data('total')) || 0;
              const pd = parseFloat($(this).data('paid')) || 0;
              return (tot - pd) > 0 && !$(this).is(':disabled');
            });
            if (availableCheckboxes.length === 1) {
              availableCheckboxes.first().prop('checked', true).trigger('change');
            }
          }
        } else {
          var purposeOfAmount = $("input[name='purposeofamount']:checked").val();
          if (purposeOfAmount === "advancepayment" && typeof checkClientPendingOrdersAndPrompt === "function") {
            const hasPrompted = checkClientPendingOrdersAndPrompt(amount, function() {
              // User chose Save as Future Advance
              window.orderSelectionConfirmed = true;
              $("#savepaymentdatabtn").show();
              $("#fetchthedata").hide();
              $('#returnmsgdiv').html('<div class="text-center p-2 text-success font-weight-bold"><i class="fa fa-check-circle mr-1"></i> Confirmed: Recording as Future Advance Deposit. Click "Save Payment Info" to complete.</div>');
            }, function() {
              // User chose Against Invoice -> Switch purpose and fetch orders
              $("#purpose_invoice").prop("checked", true).trigger("change");
              getProvisionalOrderhtmlwithModal();
            });
            if (hasPrompted) return;
          }

          Swal.fire({
            title: "No Pending Orders Found",
            text: "No provisional orders with pending balance were found for this client. Would you like to record this payment as a Future Advance Deposit?",
            icon: "info",
            showCancelButton: true,
            confirmButtonText: "Yes, Save as Advance",
            cancelButtonText: "Cancel"
          }).then((confirmRes) => {
            if (confirmRes.isConfirmed) {
              window.orderSelectionConfirmed = true;
              $("#purpose_advance").prop("checked", true).trigger("change");
              $("#advancePaymentForm").submit();
            } else {
              $("#savepaymentdatabtn").show();
              $("#fetchthedata").hide();
              $('#returnmsgdiv').html('<div class="text-center p-4 text-muted"><i class="fa fa-folder-open fa-3x mb-3"></i><br>No provisional orders found for this client, Click to "Save Payment Info" As Advance</div>');
            }
          });
        }
      });
    }
  }
</script>