<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$loginid = "";
$loginroleid = "";
$companyid = "";
$subcompanyid = "";
$projectid = "";
$loginname = "";
$key = $_GET['k'] ?? '';
$mdid = $_GET['mdid'] ?? '';
$dz = $_GET['dz'] ?? '';

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443 ? "https://" : "http://";
$url = basename($_SERVER["PHP_SELF"]);
$rooturl = $protocol . $_SERVER['HTTP_HOST'];
if ($_SERVER['HTTP_HOST'] == "localhost" || $_SERVER['HTTP_HOST'] == "192.168.1.65") {
  $prefix = $rooturl . "/krf_amanaccounting/";
} else {
  $prefix = $rooturl . "/";
}
$rooturl = $prefix;

$use_standalone_layout = false;

// If explicitly dz, or direct access where k is missing
if (isset($_GET['dz']) || empty($key)) {
  $use_standalone_layout = true;
  if (file_exists(dirname(__DIR__) . '/connection.php')) {
    require_once(dirname(__DIR__) . '/connection.php');
  }
  if (file_exists(dirname(__DIR__) . '/functions/function_master.php')) {
    include_once(dirname(__DIR__) . '/functions/function_master.php');
  }
  if (file_exists(__DIR__ . '/functions/orders_functions.php')) {
    include_once(__DIR__ . '/functions/orders_functions.php');
  }

  if (!empty($key)) {
    $key_decoded = base64url_decode($key);
    $key_parts = explode("|", $key_decoded);
    $checkkey = base64url_decode($key_parts[count($key_parts) - 1] ?? '');
    $actkey = "";
    for ($i = 0; $i < count($key_parts) - 1; $i++) {
      $actkey = $actkey ? $actkey . "|" . $key_parts[$i] : $key_parts[$i];
    }
    if ($actkey == $checkkey) {
      $loginid = $key_parts[0] ?? '';
      $loginroleid = $key_parts[1] ?? '';
      $companyid = $key_parts[2] ?? '';
      $subcompanyid = $key_parts[3] ?? '';
      $projectid = $key_parts[4] ?? '';
    }
  }

  if (empty($loginid) && !empty($_SESSION['loginid'])) $loginid = $_SESSION['loginid'];
  if (empty($loginroleid) && !empty($_SESSION['loginroleid'])) $loginroleid = $_SESSION['loginroleid'];
  if (empty($companyid) && !empty($_SESSION['companyid'])) $companyid = $_SESSION['companyid'];
  if (empty($subcompanyid) && !empty($_SESSION['subcompanyid'])) $subcompanyid = $_SESSION['subcompanyid'];
  if (empty($projectid) && !empty($_SESSION['projectid'])) $projectid = $_SESSION['projectid'];
  if (empty($loginname) && !empty($_SESSION['loginname'])) $loginname = $_SESSION['loginname'];

  if (empty($loginname) && !empty($loginid) && isset($authconn)) {
    $logininfo = loginuserdetailsfrommaster($authconn, $loginid, "loginid", $loginid);
    if (count($logininfo) > 0) {
      foreach ($logininfo as $lgkey) {
        $loginname = $lgkey['loginname'];
        if (empty($loginroleid)) $loginroleid = $lgkey['loginroleid'];
      }
    }
  }
} else {
  // Standard ERP access with key
  $hide_heading = true;
  require('../header.php');
  if (file_exists(__DIR__ . '/functions/orders_functions.php')) {
    include_once(__DIR__ . '/functions/orders_functions.php');
  }
}

if (empty($loginname)) {
  $loginname = $_SESSION['loginname'] ?? 'Admin User';
}
if (empty($projectid)) {
  $projectid = $_SESSION['projectid'] ?? 17;
}
if (empty($companyid)) {
  $companyid = $_SESSION['companyid'] ?? 19;
}
if (empty($subcompanyid)) {
  $subcompanyid = $_SESSION['subcompanyid'] ?? 12;
}

// Compute default month range:
// Default is current month. If current month has 0 unverified entries, fallback to previous month!
$curMonthStart = date('Y-m-01');
$curMonthEnd = date('Y-m-t');
$prevMonthStart = date('Y-m-01', strtotime('first day of last month'));
$prevMonthEnd = date('Y-m-t', strtotime('last day of last month'));

$pIdEsc = (!empty($projectid) && isset($krfconn) && $krfconn) ? $krfconn->real_escape_string($projectid) : '';
$projCond = !empty($pIdEsc) ? "AND (apt.projectid = '{$pIdEsc}' OR apt.projectid IS NULL OR apt.projectid = 0)" : "";

$curUnvCount = 0;
if (isset($krfconn) && $krfconn) {
    $curUnvSql = "SELECT COUNT(*) as c FROM advance_payment_transactions apt 
                  WHERE (apt.tedit IS NULL OR apt.tedit = '') 
                  AND apt.paymentdate >= '{$curMonthStart}' AND apt.paymentdate <= '{$curMonthEnd}' 
                  AND (apt.verification = 'unverified' OR apt.verification IS NULL OR apt.verification = '') 
                  {$projCond}";
    $curRes = $krfconn->query($curUnvSql);
    $curUnvCount = ($curRes && $r = $curRes->fetch_assoc()) ? intval($r['c']) : 0;
}

if ($curUnvCount > 0) {
    $defaultDateFrom = $curMonthStart;
    $defaultDateTo = $curMonthEnd;
    $defaultPreset = 'this_month';
} else {
    // Current month has 0 unverified entries, fallback to previous month
    $defaultDateFrom = $prevMonthStart;
    $defaultDateTo = $prevMonthEnd;
    $defaultPreset = 'prev_month';
}
?>

<?php if ($use_standalone_layout): ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Received Payment Verification | Karjee Farm</title>

  <!-- Google Fonts: Inter & Outfit -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- FontAwesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Select2 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <!-- SweetAlert2 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <!-- iziToast CSS -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/css/iziToast.min.css" />
<?php else: ?>
  <!-- Google Fonts: Inter & Outfit -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <!-- FontAwesome 6 (with icons for verification) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<?php endif; ?>

  <script>
    if (window.self !== window.top) {
      document.documentElement.classList.add('is-iframe');
      if (document.body) document.body.classList.add('is-iframe');
    }
  </script>

  <style>
    :root {
      --primary-color: #3b82f6;
      --primary-dark: #1d4ed8;
      --success-color: #10b981;
      --warning-color: #f59e0b;
      --danger-color: #ef4444;
      --dark-color: #1e293b;
      --card-border-radius: 16px;
      --bg-gradient: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    }

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
    .is-iframe .company-header-strip {
      display: none !important;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: var(--bg-gradient);
      color: #334155;
      min-height: 100vh;
      padding-bottom: 80px;
    }

    h1, h2, h3, h4, h5, h6, .font-heading {
      font-family: 'Outfit', sans-serif;
    }

    /* Modern Glass Card */
    .glass-card {
      background: rgba(255, 255, 255, 0.98);
      backdrop-filter: blur(12px);
      border: 1px solid #e2e8f0;
      border-radius: var(--card-border-radius);
      box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05);
    }

    /* Metric Cards in Summary */
    .metric-item {
      padding: 1rem 1.25rem;
    }

    .amount-display {
      font-family: 'Outfit', sans-serif;
      font-size: 1.45rem;
      font-weight: 700;
      line-height: 1.2;
    }

    .icon-bubble {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.35rem;
    }

    /* Checkbox Reset & Standard Styling */
    input[type="checkbox"].payment-select-chk,
    input[type="checkbox"]#selectAllCheckbox {
      -webkit-transform: none !important;
      -ms-transform: none !important;
      transform: none !important;
      width: 18px !important;
      height: 18px !important;
      min-width: 18px !important;
      min-height: 18px !important;
      margin: 0 6px 0 0 !important;
      cursor: pointer !important;
      accent-color: #10b981 !important;
      vertical-align: middle !important;
      display: inline-block !important;
    }

    .chk-wrapper {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 32px;
      height: 32px;
      border-radius: 8px;
      background: #f8fafc;
      border: 1px solid #cbd5e1;
      margin-right: 8px;
      flex-shrink: 0;
      transition: all 0.2s ease;
    }
    .chk-wrapper:hover {
      border-color: #10b981;
      background: #f0fdf4;
    }

    /* Single-Row Payment Strip Card Layout */
    .payment-row-card {
      border-radius: 14px;
      border: 1px solid #e2e8f0;
      background: #ffffff;
      transition: all 0.2s ease-in-out;
      padding: 1.25rem 1.5rem;
      margin-bottom: 1.25rem;
      position: relative;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
    }
    .payment-row-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 24px rgba(0, 0, 0, 0.06);
      border-color: #cbd5e1;
    }
    .payment-row-card.unverified-row {
      border-left: 6px solid #f59e0b;
    }
    .payment-row-card.verified-row {
      border-left: 6px solid #10b981;
      background: #fcfdfd;
    }
    .payment-row-card.rejected-row {
      border-left: 6px solid #ef4444;
      background: #fffdfd;
    }

    /* Status Badges */
    .status-badge-unverified {
      background: #fef3c7;
      color: #b45309;
      border: 1px solid #fde68a;
      font-weight: 600;
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 0.8rem;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    .status-badge-verified {
      background: #d1fae5;
      color: #047857;
      border: 1px solid #a7f3d0;
      font-weight: 600;
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 0.8rem;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    .status-badge-rejected {
      background: #fee2e2;
      color: #b91c1c;
      border: 1px solid #fecaca;
      font-weight: 600;
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 0.8rem;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .pulse-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: #f59e0b;
      animation: pulse 1.5s infinite;
    }
    @keyframes pulse {
      0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7); }
      70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(245, 158, 11, 0); }
      100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
    }

    /* Filter Nav Pills */
    .nav-tabs-custom {
      border-bottom: 2px solid #e2e8f0;
      gap: 10px;
    }
    .nav-tabs-custom .nav-link {
      border: none;
      font-weight: 600;
      color: #64748b;
      padding: 10px 20px;
      border-radius: 8px 8px 0 0;
      transition: all 0.2s ease;
      font-size: 0.92rem;
    }
    .nav-tabs-custom .nav-link:hover {
      background: #f1f5f9;
      color: #1e293b;
    }
    .nav-tabs-custom .nav-link.active {
      color: var(--primary-dark);
      background: #ffffff;
      border-bottom: 3px solid var(--primary-color);
      box-shadow: 0 -2px 6px rgba(0,0,0,0.03);
    }

    /* Thumbnail Receipt */
    .receipt-thumb {
      width: 68px;
      height: 68px;
      object-fit: cover;
      border-radius: 10px;
      border: 1px solid #cbd5e1;
      cursor: pointer;
      transition: transform 0.2s ease;
      background: #ffffff;
    }
    .receipt-thumb:hover {
      transform: scale(1.08);
      box-shadow: 0 4px 10px rgba(0,0,0,0.12);
    }
    .pdf-preview-box {
      width: 68px;
      height: 68px;
      border-radius: 10px;
      background: #fee2e2;
      border: 1px solid #fecaca;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      color: #ef4444;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.2s;
    }
    .pdf-preview-box:hover {
      background: #fca5a5;
      color: #b91c1c;
      transform: scale(1.08);
    }

    /* Select2 custom tweak */
    .select2-container .select2-selection--single {
      height: 40px !important;
      border: 1px solid #cbd5e1 !important;
      border-radius: 8px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
      line-height: 38px !important;
      padding-left: 12px !important;
      font-size: 0.9rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
      height: 38px !important;
    }

    /* Filter Spacing Classes */
    .filter-label {
      display: flex !important;
      align-items: center !important;
      margin-bottom: 8px !important;
      font-size: 0.85rem !important;
      font-weight: 600 !important;
      color: #475569 !important;
      line-height: 1.2;
    }
    .filter-label i {
      margin-right: 8px !important;
      font-size: 0.95rem;
      flex-shrink: 0;
    }
    .filter-inputs-row {
      margin-bottom: 16px !important;
    }
    .filter-actions-group {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 10px;
    }
    .filter-actions-group .btn {
      margin-right: 6px;
    }
    .filter-actions-group .btn:last-child {
      margin-right: 0;
    }

    .btn-action-verify {
      background-color: #10b981;
      border-color: #10b981;
      color: #ffffff;
      font-weight: 600;
      padding: 6px 18px;
      border-radius: 8px;
      transition: all 0.2s;
    }
    .btn-action-verify:hover {
      background-color: #059669;
      border-color: #059669;
      color: #ffffff;
      transform: translateY(-1px);
    }

    .btn-action-edit {
      border: 1px solid #cbd5e1;
      background: #ffffff;
      color: #3b82f6;
      font-weight: 600;
      padding: 6px 16px;
      border-radius: 8px;
      transition: all 0.2s;
    }
    .btn-action-edit:hover {
      background: #eff6ff;
      border-color: #93c5fd;
      color: #1d4ed8;
      transform: translateY(-1px);
    }

    .btn-action-revert {
      border: 1px solid #fecaca;
      background: #ffffff;
      color: #ef4444;
      font-weight: 600;
      padding: 6px 16px;
      border-radius: 8px;
      transition: all 0.2s;
    }
    .btn-action-revert:hover {
      background: #fee2e2;
      border-color: #f87171;
      color: #b91c1c;
      transform: translateY(-1px);
    }

    .btn-action-reject {
      background-color: #ef4444;
      border-color: #ef4444;
      color: #ffffff;
      font-weight: 600;
      padding: 6px 16px;
      border-radius: 8px;
      transition: all 0.2s;
    }
    .btn-action-reject:hover {
      background-color: #dc2626;
      border-color: #dc2626;
      color: #ffffff;
      transform: translateY(-1px);
    }

    .rejected-remark-box {
      background-color: #fef2f2;
      border: 1px solid #fecaca;
      border-radius: 8px;
      padding: 8px 12px;
      font-size: 0.78rem;
      color: #991b1b;
    }

    @media (min-width: 992px) {
      .border-end-lg {
        border-right: 1px solid #e2e8f0 !important;
      }
    }

    .shadow-2xs {
      box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }

    /* Modal Sizing & Clean Stacking */
    #paymentVerificationModal {
      z-index: 1055 !important;
    }

    #paymentVerificationModal .modal-dialog {
      max-width: 1260px;
      width: 94vw;
      margin: 1.5rem auto;
    }

    #receiptModal {
      z-index: 1080 !important;
    }

    #receiptModal .modal-dialog {
      max-width: 1000px;
      width: 90vw;
    }

    /* UTR Badge & Modal Styling */
    .utr-badge-pill {
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      transition: all 0.2s ease-in-out;
      border: 1px solid #cbd5e1;
      background: #f8fafc;
      text-decoration: none;
    }
    .utr-badge-pill:hover {
      background: #e0f2fe;
      border-color: #38bdf8;
      color: #0369a1 !important;
      box-shadow: 0 2px 6px rgba(14, 165, 233, 0.2);
      transform: translateY(-1px);
    }
    .utr-badge-pill:active {
      transform: translateY(0);
    }

    #utrDetailsModal {
      z-index: 1060 !important;
    }
    #utrDetailsModal .modal-dialog {
      max-width: 1100px;
      width: 95vw;
      margin: 1.5rem auto;
    }

    /* =========================================================
       Mobile Responsive Styles for Modals (Payment Verification, Zoom, UTR)
       ========================================================= */
    @media (max-width: 991.98px) {
      #paymentVerificationModal .modal-dialog {
        max-width: 98vw !important;
        width: 98vw !important;
        margin: 0.5rem auto !important;
      }
      #paymentVerificationModal .col-lg-5 .card {
        height: auto !important;
        margin-top: 0.75rem;
      }
      #modal_receipt_preview_area {
        min-height: 220px !important;
        max-height: 320px !important;
      }
      #modal_receipt_preview_area img {
        max-height: 280px !important;
        max-width: 100% !important;
        object-fit: contain !important;
      }
    }

    @media (max-width: 767.98px) {
      /* 1. Modal Dialog & Container */
      #paymentVerificationModal .modal-dialog {
        max-width: 100vw !important;
        width: 98vw !important;
        margin: 0.25rem auto !important;
      }
      #paymentVerificationModal .modal-content {
        border-radius: 12px !important;
        max-height: calc(100dvh - 0.5rem) !important;
        display: flex !important;
        flex-direction: column !important;
      }

      /* 2. Modal Header */
      #paymentVerificationModal .modal-header {
        padding: 0.75rem 1rem !important;
        flex-wrap: nowrap !important;
        gap: 0.5rem !important;
        align-items: flex-start !important;
      }
      #paymentVerificationModal .modal-header .mr-2 {
        margin-right: 0.4rem !important;
      }
      #paymentVerificationModal .modal-title {
        font-size: 1.02rem !important;
        line-height: 1.25 !important;
      }
      #paymentVerificationModal .modal-header small {
        font-size: 0.72rem !important;
        line-height: 1.25 !important;
        display: block !important;
        margin-top: 2px !important;
      }

      /* 3. Modal Body & Scroll Area */
      #paymentVerificationModal .modal-body {
        padding: 0.75rem 0.65rem !important;
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch !important;
        flex: 1 1 auto !important;
      }

      /* 4. Client / Order Information Strip (2x2 Grid) */
      .modal-client-info-strip {
        display: grid !important;
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 0.65rem 0.5rem !important;
      }
      .modal-client-info-strip h6,
      .modal-client-info-strip div {
        font-size: 0.85rem !important;
        word-break: break-word !important;
      }

      /* 5. Matching Summary Card */
      #modal_pso_match_card .card-body {
        padding: 0.65rem !important;
      }
      #modal_pso_match_card .pso-match-header {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 0.35rem !important;
      }
      #modal_match_badge {
        white-space: normal !important;
        text-align: left !important;
        font-size: 0.72rem !important;
        line-height: 1.3 !important;
      }
      #modal_pso_match_card strong {
        font-size: 0.82rem !important;
        word-break: break-word !important;
      }

      /* 6. Form Fields & Controls */
      #paymentVerificationModal .form-control,
      #paymentVerificationModal .form-select {
        font-size: 14px !important;
        border-radius: 8px !important;
      }
      #paymentVerificationModal label.form-label {
        font-size: 0.8rem !important;
        margin-bottom: 0.25rem !important;
      }
      #modal_cash_details_row {
        margin-left: 0 !important;
        margin-right: 0 !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
      }

      /* 7. Document Upload Box */
      #paymentVerificationModal .bg-white.rounded-3.border.mb-3.shadow-2xs.p-3 {
        padding: 0.65rem !important;
      }

      /* 8. Live Receipt Preview Card */
      #paymentVerificationModal .col-lg-5 .card {
        margin-top: 0.5rem !important;
      }
      #modal_receipt_preview_area {
        min-height: 180px !important;
        max-height: 250px !important;
        padding: 0.5rem !important;
      }
      #modal_receipt_preview_area img {
        max-height: 220px !important;
        max-width: 100% !important;
        object-fit: contain !important;
      }

      /* 9. Modal Footer & Action Buttons */
      #paymentVerificationModal .modal-footer {
        flex-direction: column-reverse !important;
        align-items: stretch !important;
        gap: 0.5rem !important;
        padding: 0.75rem 1rem !important;
        padding-bottom: max(24px, calc(16px + env(safe-area-inset-bottom))) !important;
        border-top: 1px solid #e2e8f0 !important;
      }
      #paymentVerificationModal .modal-footer > div {
        width: 100% !important;
        display: flex !important;
        flex-direction: column !important;
        gap: 0.5rem !important;
      }
      #paymentVerificationModal .modal-footer .btn {
        width: 100% !important;
        padding: 0.65rem 1rem !important;
        font-size: 0.92rem !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
      }
      #paymentVerificationModal .modal-footer .btn#btnModalVerify {
        order: -1 !important; /* Prominent top placement for Confirm & Verify */
      }

      /* 10. Zoom / Receipt Modal & UTR Modal Mobile Rules */
      #receiptModal .modal-dialog,
      #utrDetailsModal .modal-dialog {
        max-width: 98vw !important;
        width: 98vw !important;
        margin: 0.35rem auto !important;
      }
      #receiptModal .modal-body {
        padding: 0.5rem !important;
        min-height: 240px !important;
      }
      #receiptModal .modal-body img {
        max-height: 60vh !important;
      }
      #receiptModal .modal-footer,
      #utrDetailsModal .modal-footer {
        flex-direction: column-reverse !important;
        align-items: stretch !important;
        padding: 0.75rem 1rem !important;
        padding-bottom: max(20px, calc(14px + env(safe-area-inset-bottom))) !important;
        gap: 0.5rem !important;
      }
      #receiptModal .modal-footer .btn,
      #utrDetailsModal .modal-footer .btn {
        width: 100% !important;
      }
    }

    @media (max-width: 400px) {
      /* Extra Narrow Screens (iPhone SE, Small Android devices) */
      .modal-client-info-strip {
        grid-template-columns: 1fr !important;
        gap: 0.45rem !important;
      }
      #paymentVerificationModal .modal-header {
        padding: 0.6rem 0.75rem !important;
      }
      #paymentVerificationModal .modal-body {
        padding: 0.5rem 0.4rem !important;
      }
      #modal_pso_match_card .row.g-2 > .col-6 {
        width: 100% !important;
      }
    }
  </style>

<?php if ($use_standalone_layout): ?>
</head>
<body data-projectid="<?php echo $projectid; ?>" data-companyid="<?php echo $companyid; ?>" data-subcompanyid="<?php echo $subcompanyid; ?>" data-loginname="<?php echo htmlspecialchars($loginname); ?>" data-rooturl="<?php echo $rooturl; ?>">
<?php else: ?>
<script>
  $(document).ready(function() {
    $('body').attr({
      'data-projectid': '<?php echo $projectid; ?>',
      'data-companyid': '<?php echo $companyid; ?>',
      'data-subcompanyid': '<?php echo $subcompanyid; ?>',
      'data-loginname': '<?php echo htmlspecialchars($loginname); ?>',
      'data-rooturl': '<?php echo $rooturl; ?>',
      'data-key': '<?php echo $key; ?>'
    });
  });
</script>
<?php endif; ?>

  <div class="container-fluid px-3 px-md-4 py-4" style="max-width: 1440px; margin: 0 auto;">
    
    <!-- Top Header & Breadcrumb -->
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 pb-3 border-bottom">
      <div class="mb-2 mb-md-0">
        <div class="d-flex align-items-center gap-2 mb-1">
          <span class="badge badge-primary bg-primary px-2.5 py-1.5 me-3 mr-3" style="border-radius: 6px; font-size: 0.75rem; margin-right: 14px; display: inline-flex; align-items: center;"><i class="fa fa-shield-alt me-1 mr-1" style="margin-right: 5px;"></i> VERIFICATION CELL</span>
          <span class="text-muted small ms-1 ml-1" style="margin-left: 6px; font-weight: 500;">Project ID: #<?php echo $projectid; ?></span>
        </div>
        <h3 class="fw-bold font-weight-bold text-dark mb-1">Received Payment Verification</h3>
        <p class="text-muted small mb-0">Review, verify and manage advance payment receipts logged for provisional sales orders.</p>
      </div>

      <?php
        $nav_extra = (!empty($dz) ? '&dz=' . urlencode($dz) : '') . (isset($_GET['iframe']) ? '&iframe=true' : '');
      ?>
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="paymentsummary.php?mdid=<?php echo urlencode($mdid); ?>&k=<?php echo urlencode($key); ?><?php echo $nav_extra; ?>" class="btn btn-outline-success btn-sm rounded px-3 py-1.5 shadow-sm">
          <i class="fa fa-chart-pie mr-1 me-1"></i> Payment Summary
        </a>
        <a href="paymenthistory_review.php?mdid=<?php echo urlencode($mdid); ?>&k=<?php echo urlencode($key); ?><?php echo $nav_extra; ?>" class="btn btn-outline-info btn-sm rounded px-3 py-1.5 shadow-sm">
          <i class="fa fa-clock-rotate-left mr-1 me-1"></i> Receipt History
        </a>
        <button type="button" class="btn btn-outline-secondary btn-sm rounded px-3 py-1.5 shadow-sm" onclick="resetFilters()">
          <i class="fa fa-undo mr-1 me-1"></i> Reset Filters
        </button>
        <button type="button" class="btn btn-primary btn-sm rounded px-3.5 py-1.5 shadow-sm" onclick="loadPayments()">
          <i class="fa fa-sync mr-1 me-1"></i> Refresh
        </button>
      </div>
    </div>

    <!-- 1. Top Summary Metric: Single Unified Card -->
    <div class="card glass-card shadow-sm border-0 mb-4 overflow-hidden">
      <div class="card-body p-2 p-md-3">
        <div class="row g-0 align-items-center">
          
          <!-- Metric 1: Total Received -->
          <div class="col-12 col-sm-6 col-lg-3 pe-lg-3 border-end-lg">
            <div class="metric-item d-flex align-items-center justify-content-between">
              <div>
                <span class="text-muted small text-uppercase font-weight-bold fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                  <i class="fa fa-circle text-primary mr-1 me-1" style="font-size: 0.5rem;"></i> TOTAL RECEIVED
                </span>
                <h4 class="amount-display text-primary mb-0 mt-1" id="stat_total_amt">&#8377;0.00</h4>
                <small class="text-muted fw-semibold font-weight-bold" id="stat_total_cnt" style="font-size: 0.8rem;">0 Transactions</small>
              </div>
              <div class="icon-bubble bg-light text-primary ms-2 ml-2 shadow-2xs">
                <i class="fa fa-wallet"></i>
              </div>
            </div>
          </div>

          <!-- Metric 2: Pending Verification -->
          <div class="col-12 col-sm-6 col-lg-3 px-lg-3 border-end-lg">
            <div class="metric-item d-flex align-items-center justify-content-between">
              <div>
                <span class="text-muted small text-uppercase font-weight-bold fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                  <i class="fa fa-circle text-warning mr-1 me-1" style="font-size: 0.5rem;"></i> PENDING VERIFICATION
                </span>
                <h4 class="amount-display text-warning mb-0 mt-1" id="stat_unverified_amt">&#8377;0.00</h4>
                <small class="text-warning font-weight-bold fw-bold" id="stat_unverified_cnt" style="font-size: 0.8rem;">0 Pending Verification</small>
              </div>
              <div class="icon-bubble bg-light text-warning ms-2 ml-2 shadow-2xs">
                <i class="fa fa-hourglass-half"></i>
              </div>
            </div>
          </div>

          <!-- Metric 3: Verified Payments -->
          <div class="col-12 col-sm-6 col-lg-3 px-lg-3 border-end-lg">
            <div class="metric-item d-flex align-items-center justify-content-between">
              <div>
                <span class="text-muted small text-uppercase font-weight-bold fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                  <i class="fa fa-circle text-success mr-1 me-1" style="font-size: 0.5rem;"></i> VERIFIED PAYMENTS
                </span>
                <h4 class="amount-display text-success mb-0 mt-1" id="stat_verified_amt">&#8377;0.00</h4>
                <small class="text-success font-weight-bold fw-bold" id="stat_verified_cnt" style="font-size: 0.8rem;">0 Verified</small>
              </div>
              <div class="icon-bubble bg-light text-success ms-2 ml-2 shadow-2xs">
                <i class="fa fa-check-double"></i>
              </div>
            </div>
          </div>

          <!-- Metric 4: Today's Collection -->
          <div class="col-12 col-sm-6 col-lg-3 ps-lg-3">
            <div class="metric-item d-flex align-items-center justify-content-between">
              <div>
                <span class="text-muted small text-uppercase font-weight-bold fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                  <i class="fa fa-circle text-info mr-1 me-1" style="font-size: 0.5rem;"></i> TODAY'S COLLECTION
                </span>
                <h4 class="amount-display text-info mb-0 mt-1" id="stat_today_amt">&#8377;0.00</h4>
                <small class="text-info font-weight-bold fw-bold" id="stat_today_cnt" style="font-size: 0.8rem;">0 Received Today</small>
              </div>
              <div class="icon-bubble bg-light text-info ms-2 ml-2 shadow-2xs">
                <i class="fa fa-calendar-day"></i>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- 2. Filter Bar & Quick Controls -->
    <div class="card glass-card shadow-sm border-0 mb-4 p-3 p-md-4">
      <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 pb-3 border-bottom">
        <h6 class="font-heading fw-bold font-weight-bold text-dark mb-2 mb-sm-0 d-flex align-items-center">
          <i class="fa fa-filter text-primary mr-2 me-2" style="margin-right: 8px;"></i> Filter Payments
        </h6>
        <!-- Quick Preset Range Pills -->
        <div class="d-flex align-items-center">
          <span class="text-muted small fw-semibold d-none d-md-inline mr-2 me-2" style="font-size: 0.82rem; margin-right: 8px;">Quick Range:</span>
          <div class="btn-group btn-group-sm" role="group" aria-label="Date Presets">
            <button type="button" class="btn btn-outline-secondary px-2.5" onclick="setDatePreset('today', this)">Today</button>
            <button type="button" class="btn btn-outline-secondary px-2.5" onclick="setDatePreset('this_week', this)">This Week</button>
            <button type="button" class="btn btn-outline-secondary px-2.5 <?php echo ($defaultPreset === 'this_month') ? 'active' : ''; ?>" id="btn_preset_this_month" onclick="setDatePreset('this_month', this)">This Month</button>
            <button type="button" class="btn btn-outline-secondary px-2.5 <?php echo ($defaultPreset === 'prev_month') ? 'active' : ''; ?>" id="btn_preset_prev_month" onclick="setDatePreset('prev_month', this)" title="Show Previous Month (1st to end of last month)">Previous Month</button>
            <button type="button" class="btn btn-outline-secondary px-2.5" id="btn_preset_all" onclick="setDatePreset('all', this)">All Time</button>
          </div>
        </div>
      </div>

      <!-- Filters Grid: Row 1 (Inputs) -->
      <div class="row filter-inputs-row mb-3">
        <!-- 1. Product Filter -->
        <div class="col-12 col-sm-6 col-lg-3 mb-3 mb-lg-0">
          <label class="filter-label">
            <i class="fa fa-seedling text-success"></i> <span>Product Name</span>
          </label>
          <select id="filter_product" class="form-select form-control select2" style="width: 100%;">
            <option value="">All Products</option>
          </select>
        </div>

        <!-- 2. Client Filter -->
        <div class="col-12 col-sm-6 col-lg-3 mb-3 mb-lg-0">
          <label class="filter-label">
            <i class="fa fa-user text-primary"></i> <span>Client</span>
          </label>
          <select id="filter_client" class="form-select form-control select2" style="width: 100%;">
            <option value="">All Clients</option>
          </select>
        </div>

        <!-- 3. Provisional Order Date Filter -->
        <div class="col-12 col-sm-6 col-lg-3 mb-3 mb-lg-0">
          <label class="filter-label">
            <i class="fa fa-file-invoice text-info"></i> <span>Provisional Order Date</span>
          </label>
          <input type="date" id="filter_psodate" class="form-control" style="height: 40px; border-radius: 8px; border: 1px solid #cbd5e1;">
        </div>

        <!-- 4. Date Range: Payment Date From & To -->
        <div class="col-12 col-sm-6 col-lg-3 mb-2 mb-lg-0">
          <label class="filter-label">
            <i class="fa fa-calendar-alt text-warning"></i> <span>Payment Date Range</span>
          </label>
          <div class="input-group">
            <input type="date" id="filter_date_from" class="form-control" value="<?php echo htmlspecialchars($defaultDateFrom); ?>" style="height: 40px; border-radius: 8px 0 0 8px; border: 1px solid #cbd5e1;" title="From Date">
            <span class="input-group-text bg-light text-muted px-2.5 small border-start-0 border-end-0" style="border-top: 1px solid #cbd5e1; border-bottom: 1px solid #cbd5e1;">to</span>
            <input type="date" id="filter_date_to" class="form-control" value="<?php echo htmlspecialchars($defaultDateTo); ?>" style="height: 40px; border-radius: 0 8px 8px 0; border: 1px solid #cbd5e1;" title="To Date">
          </div>
        </div>
      </div>

      <!-- Filters Grid: Row 2 (Search Bar & Action Buttons) -->
      <div class="row pt-2 align-items-center">
        <!-- Search Bar (Left) -->
        <div class="col-12 col-lg-7 col-xl-7 mb-3 mb-lg-0">
          <div class="input-group">
            <span class="input-group-text bg-white border-end-0 text-muted px-3" style="border-radius: 8px 0 0 8px; border: 1px solid #cbd5e1; border-right: none;">
              <i class="fa fa-search text-secondary"></i>
            </span>
            <input type="text" id="filter_search" class="form-control border-start-0 ps-2" style="height: 40px; border-radius: 0 8px 8px 0; border: 1px solid #cbd5e1; border-left: none;" placeholder="Search by PSO #, UTR Number, Client Name, Received By, Remarks...">
          </div>
        </div>

        <!-- Action Buttons (Right) -->
        <div class="col-12 col-lg-5 col-xl-5 d-flex flex-wrap align-items-center justify-content-lg-end">
          <button type="button" class="btn btn-outline-primary font-weight-bold fw-semibold shadow-sm px-3 flex-grow-1 flex-lg-grow-0" onclick="syncAllVerifiedOrders()" style="height: 40px; border-radius: 8px; min-width: 150px; margin-right: 8px; margin-bottom: 4px;" title="Scan and sync all verified payments with provisional orders & stock orders">
            <i class="fa fa-sync-alt mr-1.5 me-1.5" style="margin-right: 6px;"></i> Re-Verify & Sync All
          </button>
          <button type="button" class="btn btn-primary font-weight-bold fw-semibold shadow-sm px-3.5 flex-grow-1 flex-lg-grow-0" onclick="loadPayments()" style="height: 40px; border-radius: 8px; min-width: 125px; margin-right: 8px; margin-bottom: 4px;">
            <i class="fa fa-filter mr-1.5 me-1.5" style="margin-right: 6px;"></i> Apply
          </button>
          <button type="button" class="btn btn-outline-secondary font-weight-bold fw-semibold px-3 flex-grow-1 flex-lg-grow-0" onclick="resetFilters()" style="height: 40px; border-radius: 8px; min-width: 85px; margin-right: 8px; margin-bottom: 4px;" title="Reset all filters">
            <i class="fa fa-undo mr-1.5 me-1.5" style="margin-right: 6px;"></i> Reset
          </button>
          <button type="button" class="btn btn-success font-weight-bold fw-semibold shadow-sm px-3.5 flex-grow-1 flex-lg-grow-0 d-inline-flex align-items-center justify-content-center text-nowrap" onclick="exportPaymentsToExcel()" style="height: 40px; border-radius: 8px; min-width: 130px; margin-bottom: 4px;" title="Export all payments to Excel with separate columns">
            <i class="fa fa-file-excel mr-1.5 me-1.5" style="margin-right: 6px;"></i> Export Excel
          </button>
        </div>
      </div>
    </div>

    <!-- 3. Tab Switcher & Batch Action Toolbar -->
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 pb-2 border-bottom gap-3">
      <ul class="nav nav-tabs nav-tabs-custom mb-0" id="paymentTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="tab-unverified" data-status="unverified" onclick="switchStatusTab('unverified')">
            <i class="fa fa-clock mr-1 me-1 text-warning"></i> Unverified Payments 
            <span class="badge badge-warning bg-warning text-dark ms-1 ml-1" id="badge_tab_unverified">0</span>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="tab-verified" data-status="verified" onclick="switchStatusTab('verified')">
            <i class="fa fa-check-circle mr-1 me-1 text-success"></i> Verified Payments 
            <span class="badge badge-success bg-success ms-1 ml-1" id="badge_tab_verified">0</span>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="tab-rejected" data-status="rejected" onclick="switchStatusTab('rejected')">
            <i class="fa fa-times-circle mr-1 me-1 text-danger"></i> Rejected Payments 
            <span class="badge badge-danger bg-danger ms-1 ml-1" id="badge_tab_rejected">0</span>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="tab-all" data-status="all" onclick="switchStatusTab('all')">
            <i class="fa fa-layer-group mr-1 me-1 text-primary"></i> All Transactions
            <span class="badge badge-secondary bg-secondary ms-1 ml-1" id="badge_tab_all">0</span>
          </button>
        </li>
      </ul>

      <!-- Right Action Group: Batch Toolbar + Excel Export Button -->
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <!-- Batch Verification Toolbar -->
        <div id="batchActionToolbar" class="d-none d-flex align-items-center gap-3 bg-white px-3 py-1.5 rounded shadow-sm border">
          <div class="d-flex align-items-center mr-2">
            <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)">
            <label for="selectAllCheckbox" class="small font-weight-bold fw-semibold text-secondary mb-0 user-select-none" style="cursor: pointer;">Select All</label>
          </div>
          <button type="button" class="btn btn-success btn-sm rounded px-3 py-1 shadow-sm d-flex align-items-center gap-1.5 font-weight-bold fw-semibold" onclick="batchVerifySelected()">
            <i class="fa fa-check-double mr-1 me-1"></i> <span>Verify Selected (<strong id="selectedCount">0</strong>)</span>
          </button>
        </div>

        <button type="button" class="btn btn-outline-success btn-sm rounded shadow-sm font-weight-bold fw-semibold px-3 py-1.5 d-flex align-items-center gap-1.5 bg-white" onclick="exportPaymentsToExcel()" title="Export payments to Excel with separate columns">
          <i class="fa fa-file-excel text-success mr-1 me-1"></i> <span>Export Excel</span>
        </button>
      </div>
    </div>

    <!-- 4. Dynamic Single-Row Strip Container -->
    <div id="loadingSpinner" class="text-center py-5 d-none">
      <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
        <span class="sr-only visually-hidden">Loading...</span>
      </div>
      <p class="text-muted mt-2 font-weight-bold fw-semibold">Fetching payment transactions...</p>
    </div>

    <div id="paymentCardsGrid">
      <!-- Dynamic Single-Row Payment Strips will be injected here via JavaScript -->
    </div>

    <!-- Empty State -->
    <div id="emptyStateContainer" class="glass-card text-center py-5 d-none my-4">
      <div class="text-muted mb-3">
        <i class="fa fa-receipt fa-4x text-secondary opacity-50"></i>
      </div>
      <h5 class="font-weight-bold fw-bold text-dark">No Payment Records Found</h5>
      <p class="text-secondary text-muted small max-w-md mx-auto mb-3">No advance payments match the current filter criteria. Try adjusting the date range or clearing your search filters.</p>
      <button type="button" class="btn btn-outline-primary btn-sm px-4 py-1.5 rounded" onclick="resetFilters()">
        <i class="fa fa-undo mr-1 me-1"></i> Reset Filters
      </button>
    </div>

  </div>

  <!-- Fullscreen / Zoom Receipt Modal -->
  <div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content rounded-4 border-0 shadow-lg">
        <div class="modal-header bg-dark text-white border-0 py-3 px-4">
          <h5 class="modal-title font-heading font-weight-bold fw-bold" id="receiptModalLabel">
            <i class="fa fa-receipt mr-2 me-2 text-warning"></i> Payment Receipt Attachment
          </h5>
          <button type="button" class="close text-white btn-close btn-close-white" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" onclick="closeBootstrapModal('receiptModal')">&times;</button>
        </div>
        <div class="modal-body p-3 text-center bg-light" style="min-height: 400px;">
          <div id="receiptModalContent" class="d-flex align-items-center justify-content-center h-100">
            <!-- Image or PDF Embed injected dynamically -->
          </div>
        </div>
        <div class="modal-footer bg-white border-0 py-2.5 px-4 d-flex justify-content-between">
          <a id="receiptDownloadBtn" href="#" target="_blank" download class="btn btn-outline-primary btn-sm px-3 rounded">
            <i class="fa fa-download mr-1 me-1"></i> Download File
          </a>
          <button type="button" class="btn btn-secondary btn-sm px-4 rounded" data-dismiss="modal" data-bs-dismiss="modal" onclick="closeBootstrapModal('receiptModal')">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Unified Payment Edit & Verification Modal -->
  <div class="modal fade" id="paymentVerificationModal" tabindex="-1" aria-labelledby="paymentVerificationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content border-0 shadow-lg" style="border-radius: 18px; overflow: hidden;">
        
        <!-- Header -->
        <div class="modal-header bg-dark text-white border-0 py-3 px-4">
          <div class="d-flex align-items-center gap-2">
            <div class="p-2 bg-primary rounded-3 text-white mr-2">
              <i class="fa fa-shield-alt fs-5"></i>
            </div>
            <div>
              <h5 class="modal-title font-heading fw-bold font-weight-bold mb-0" id="paymentVerificationModalLabel">
                Verify & Manage Payment Receipt
              </h5>
              <small class="text-secondary opacity-75" style="font-size: 0.8rem;">
                Review payment details, transaction reference, amount & verified status.
              </small>
            </div>
          </div>
          <button type="button" class="close text-white btn-close btn-close-white" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" onclick="closeBootstrapModal('paymentVerificationModal')">&times;</button>
        </div>

        <div class="modal-body p-4 bg-light">
          <div class="row g-4">
            
            <!-- Left Column: Form & Edit Details -->
            <div class="col-12 col-lg-7">
              <input type="hidden" id="modal_payment_sno">
              <input type="hidden" id="modal_payment_psono">

              <!-- Order / Client Info Strip -->
              <div class="bg-white p-3 rounded-3 border mb-3 shadow-2xs">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 modal-client-info-strip">
                  <div>
                    <span class="text-muted small text-uppercase font-weight-bold fw-bold" style="font-size: 0.72rem;">Client Name</span>
                    <h6 class="fw-bold font-weight-bold text-dark mb-0 mt-0.5" id="modal_clientname">-</h6>
                  </div>
                  <div>
                    <span class="text-muted small text-uppercase font-weight-bold fw-bold" style="font-size: 0.72rem;">Provisional Order</span>
                    <h6 class="font-monospace text-primary fw-bold font-weight-bold mb-0 mt-0.5" id="modal_orderno">-</h6>
                  </div>
                  <div>
                    <span class="text-muted small text-uppercase font-weight-bold fw-bold" style="font-size: 0.72rem;">Generated By & Date</span>
                    <div class="small fw-semibold text-dark mt-0.5" id="modal_generated_info">-</div>
                  </div>
                  <div>
                    <span class="text-muted small text-uppercase font-weight-bold fw-bold" style="font-size: 0.72rem;">Current Status</span>
                    <div id="modal_current_status_badge" class="mt-0.5">-</div>
                  </div>
                </div>
              </div>

              <!-- Linked Provisional Order & Verification Match Summary Box -->
              <div id="modal_pso_match_card" class="card border rounded-3 mb-3 shadow-2xs" style="background: #f8fafc; border-color: #cbd5e1 !important;">
                <div class="card-body p-3">
                  <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-1 pso-match-header">
                    <span class="small font-weight-bold fw-bold text-dark d-flex align-items-center">
                      <i class="fa fa-file-invoice-dollar text-primary mr-1.5 me-1.5"></i> Linked Order & Payment Matching Summary
                    </span>
                    <span id="modal_match_badge" class="badge rounded-pill bg-success text-white py-1 px-2.5 font-weight-semibold fw-semibold" style="font-size: 0.72rem;">
                      <i class="fa fa-check-double mr-1 me-1"></i> Order Matched
                    </span>
                  </div>
                  <div class="row g-2 text-dark small" style="font-size: 0.8rem;">
                    <div class="col-6 col-sm-3">
                      <span class="text-muted d-block" style="font-size: 0.7rem;">Order Total Value</span>
                      <strong class="text-dark" id="modal_match_ordertotal">₹0.00</strong>
                    </div>
                    <div class="col-6 col-sm-3">
                      <span class="text-muted d-block" style="font-size: 0.7rem;">Total Paid Amount</span>
                      <strong class="text-success" id="modal_match_paidamount">₹0.00</strong>
                    </div>
                    <div class="col-6 col-sm-3">
                      <span class="text-muted d-block" style="font-size: 0.7rem;">Pending Balance</span>
                      <strong class="text-danger" id="modal_match_pendingbalance">₹0.00</strong>
                    </div>
                    <div class="col-6 col-sm-3">
                      <span class="text-muted d-block" style="font-size: 0.7rem;">Total Plants</span>
                      <strong class="text-primary" id="modal_match_totalplants">0 Plants</strong>
                    </div>
                    <div class="col-12 mt-1.5 pt-1.5 border-top border-light-subtle">
                      <span class="text-muted d-block" style="font-size: 0.7rem;">Crop / Variety Details</span>
                      <span class="text-dark fw-semibold" id="modal_match_cropnames">-</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Payment Form Fields -->
              <div class="row g-3 mb-3">
                
                <!-- Amount Received -->
                <div class="col-12 col-sm-6 mb-2">
                  <label class="form-label fw-bold font-weight-bold small text-dark mb-1.5 d-block">
                    <i class="fa fa-indian-rupee-sign text-primary mr-1 me-1"></i> Amount Received (&#8377;) <span class="text-danger">*</span>
                  </label>
                  <input type="number" step="0.01" id="modal_advanceamount" class="form-control fw-bold font-weight-bold text-success" placeholder="0.00">
                </div>

                <!-- Mode of Payment / Transaction Type -->
                <div class="col-12 col-sm-6 mb-2">
                  <label class="form-label fw-bold font-weight-bold small text-dark mb-1.5 d-block">
                    <i class="fa fa-money-bill-wave text-success mr-1 me-1"></i> Mode of Payment <span class="text-danger">*</span>
                  </label>
                  <select id="modal_sourceofpayment" class="form-select form-control font-heading fw-semibold" onchange="handlePaymentModeChange()">
                    <option value="UPI">UPI</option>
                    <option value="Cash">Cash</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="NEFT/RTGS">NEFT / RTGS</option>
                    <option value="Cheque">Cheque</option>
                    <option value="Card">Card / POS</option>
                    <option value="Other">Other</option>
                  </select>
                </div>

                <!-- Txn No / UTR No -->
                <div class="col-12 col-sm-6 mb-2" id="modal_utr_wrapper">
                  <label class="form-label fw-bold font-weight-bold small text-dark mb-1.5 d-block">
                    <i class="fa fa-hashtag text-primary mr-1 me-1"></i> Txn. No / UTR No <span class="text-danger" id="modal_utr_required_star">*</span>
                  </label>
                  <input type="text" id="modal_utrnumber" class="form-control font-monospace" placeholder="e.g. 023321814862">
                </div>

                <!-- Transaction Date -->
                <div class="col-12 col-sm-6 mb-2">
                  <label class="form-label fw-bold font-weight-bold small text-dark mb-1.5 d-block">
                    <i class="fa fa-calendar-alt text-info mr-1 me-1"></i> Transaction Date <span class="text-danger">*</span>
                  </label>
                  <input type="date" id="modal_paymentdate" class="form-control">
                </div>

                <!-- Cash Specific Details (Received By & Location) -->
                <div id="modal_cash_details_row" class="col-12 row g-3 mb-1 px-0 mx-0 d-none">
                  <div class="col-12 col-sm-6 mb-2">
                    <label class="form-label fw-bold font-weight-bold small text-dark mb-1.5 d-block">
                      <i class="fa fa-user-check text-success mr-1 me-1"></i> Received By (Person Name) <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="modal_receivedby" class="form-control" placeholder="e.g. Agent / Staff Name" autocomplete="off">
                  </div>
                  <div class="col-12 col-sm-6 mb-2">
                    <label class="form-label fw-bold font-weight-bold small text-dark mb-1.5 d-block">
                      <i class="fa fa-map-marker-alt text-danger mr-1 me-1"></i> Received Location <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="modal_receivedlocation" class="form-control" placeholder="e.g. Farm / Office / Site" autocomplete="off">
                  </div>
                </div>
              </div>

              <!-- Upload / Replace Attachment Section -->
              <div class="bg-white rounded-3 border mb-3 shadow-2xs p-3">
                <label class="form-label fw-bold font-weight-bold small text-dark mb-2 d-flex justify-content-between align-items-center">
                  <span><i class="fa fa-cloud-upload-alt text-primary mr-2 me-2"></i> Payment Proof / Document <span class="text-danger">*</span></span>
                  <span class="text-muted fw-normal small" id="modal_receipt_subtext">(Mandatory document proof)</span>
                </label>
                <input type="file" id="modal_receipt_file" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.webp,.pdf" onchange="previewModalReceipt(this)">
                <div class="d-flex justify-content-between align-items-center mt-2">
                  <small class="text-muted" style="font-size: 0.75rem;">JPG, PNG, WEBP, PDF (Max 10MB)</small>
                  <small id="modal_upload_file_name" class="text-primary fw-semibold" style="font-size: 0.75rem;"></small>
                </div>
              </div>

              <!-- Validation Warning Box -->
              <div id="modal_validation_alert" class="alert alert-danger p-2.5 px-3 mt-3 mb-0 small d-none rounded shadow-2xs">
                <div class="d-flex align-items-start gap-2">
                  <i class="fa fa-exclamation-triangle fs-6 mt-0.5 text-danger mr-1 me-1"></i>
                  <div>
                    <strong>Verification Blocked:</strong>
                    <div id="modal_validation_msg"></div>
                  </div>
                </div>
              </div>

            </div>

            <!-- Right Column: Live Receipt Attachment Preview Screen -->
            <div class="col-12 col-lg-5">
              <div class="card h-100 border rounded-3 bg-white shadow-2xs d-flex flex-column">
                <div class="card-header bg-light py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                  <span class="fw-bold font-weight-bold small text-dark">
                    <i class="fa fa-file-invoice text-primary mr-1.5 me-1.5"></i> Receipt Document Preview
                  </span>
                  <button type="button" class="btn btn-outline-secondary btn-sm rounded px-2.5 py-0.5" id="btnReceiptZoom" onclick="zoomCurrentReceipt()" title="Open Fullscreen Preview">
                    <i class="fa fa-expand mr-1 me-1"></i> Zoom
                  </button>
                </div>
                <div class="card-body p-3 d-flex align-items-center justify-content-center text-center overflow-auto" id="modal_receipt_preview_area" style="min-height: 280px; max-height: 380px; background: #f8fafc;">
                  <!-- Dynamic preview loaded here -->
                </div>
                <div class="card-footer bg-white py-1.5 px-3 border-top text-center" id="modal_receipt_preview_footer">
                  <small class="text-muted" id="modal_receipt_preview_status">-</small>
                </div>
              </div>
            </div>

          </div>
        </div>

        <div class="modal-footer bg-white border-0 py-3 px-4 d-flex flex-wrap align-items-center justify-content-between gap-2">
          <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm px-3.5 rounded" data-dismiss="modal" data-bs-dismiss="modal" onclick="closeBootstrapModal('paymentVerificationModal')">Cancel</button>
            <button type="button" class="btn btn-outline-danger btn-sm px-3 rounded shadow-sm font-weight-bold fw-semibold" id="btnModalReject" onclick="openRejectPaymentFromModal()">
              <i class="fa fa-times-circle mr-1 me-1"></i> Reject Payment
            </button>
          </div>
          
          <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-primary btn-sm px-3.5 rounded shadow-sm font-weight-bold fw-semibold" id="btnModalUpdateOnly" onclick="submitPaymentModal(false)">
              <i class="fa fa-save mr-1 me-1"></i> Update Details Only
            </button>
            <button type="button" class="btn btn-success btn-sm px-4 rounded shadow-sm font-weight-bold fw-semibold" id="btnModalVerify" onclick="submitPaymentModal(true)">
              <i class="fa fa-check-circle mr-1 me-1"></i> Confirm & Verify
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- UTR Details & Linked Orders Modal -->
  <div class="modal fade" id="utrDetailsModal" tabindex="-1" aria-labelledby="utrDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content shadow-lg border-0 rounded-4 overflow-hidden">
        <div class="modal-header bg-dark text-white py-3 px-4 d-flex justify-content-between align-items-center">
          <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle bg-primary bg-opacity-25 p-2 text-primary d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
              <i class="fa fa-receipt fs-6 text-white"></i>
            </div>
            <div>
              <h5 class="modal-title font-heading fw-bold mb-0 text-white" id="utrDetailsModalLabel" style="font-size: 1.15rem;">
                UTR Transaction Details
              </h5>
              <div class="small text-white-50" id="utrModalSubHeader">Loading transaction entries...</div>
            </div>
          </div>
          <button type="button" class="btn-close btn-close-white" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" onclick="closeBootstrapModal('utrDetailsModal')"></button>
        </div>

        <div class="modal-body p-3 p-md-4 bg-light" id="utrModalBody">
          <!-- Loading Spinner -->
          <div class="text-center py-5" id="utrModalSpinner">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted fw-semibold">Fetching transactions and orders for UTR...</p>
          </div>
          <!-- Dynamic Content -->
          <div id="utrModalContent" class="d-none"></div>
        </div>

        <div class="modal-footer bg-white border-0 py-2.5 px-4 d-flex justify-content-between align-items-center">
          <span class="small text-muted" id="utrModalFooterInfo"></span>
          <button type="button" class="btn btn-secondary btn-sm px-4 rounded" data-dismiss="modal" data-bs-dismiss="modal" onclick="closeBootstrapModal('utrDetailsModal')">
            Close
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Reject Payment Modal -->
  <div class="modal fade" id="rejectPaymentModal" tabindex="-1" aria-labelledby="rejectPaymentModalLabel" aria-hidden="true" style="z-index: 1065;">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-4 border-0 shadow-lg" style="overflow: hidden;">
        <div class="modal-header bg-danger text-white border-0 py-3 px-4">
          <div class="d-flex align-items-center gap-2">
            <div class="p-2 bg-white bg-opacity-25 rounded-3 text-white mr-2 me-2">
              <i class="fa fa-ban fs-5"></i>
            </div>
            <div>
              <h5 class="modal-title font-heading fw-bold mb-0 text-white" id="rejectPaymentModalLabel">Reject Payment Record</h5>
              <small class="text-white-50" style="font-size: 0.8rem;">Provide a rejection reason and remarks</small>
            </div>
          </div>
          <button type="button" class="close text-white btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close" onclick="closeBootstrapModal('rejectPaymentModal')">&times;</button>
        </div>
        <div class="modal-body p-4 bg-light">
          <input type="hidden" id="reject_payment_sno">
          
          <!-- Payment Details Strip -->
          <div class="bg-white p-3 rounded-3 border mb-3 shadow-2xs">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
              <div>
                <span class="text-muted small text-uppercase font-weight-bold fw-bold" style="font-size: 0.7rem;">Order No</span>
                <h6 class="font-monospace text-dark fw-bold mb-0 mt-0.5" id="reject_order_no">-</h6>
              </div>
              <div>
                <span class="text-muted small text-uppercase font-weight-bold fw-bold" style="font-size: 0.7rem;">Client Name</span>
                <h6 class="text-dark fw-bold mb-0 mt-0.5" id="reject_client_name">-</h6>
              </div>
              <div class="text-end">
                <span class="text-muted small text-uppercase font-weight-bold fw-bold" style="font-size: 0.7rem;">Amount</span>
                <h6 class="text-danger fw-bold mb-0 mt-0.5" id="reject_amount">₹0.00</h6>
              </div>
            </div>
          </div>

          <!-- Common Quick Reason Select -->
          <div class="mb-3">
            <label class="form-label fw-bold small text-dark mb-1">
              <i class="fa fa-list text-primary mr-1 me-1"></i> Quick Reason (Optional)
            </label>
            <select id="reject_reason_preset" class="form-select form-control" onchange="handleRejectReasonPresetChange()">
              <option value="">-- Select a common reason or type below --</option>
              <option value="Invalid / Fake Transaction Slip">Invalid / Fake Transaction Slip</option>
              <option value="Payment not credited in bank account">Payment not credited in bank account</option>
              <option value="Amount mismatch with bank credit">Amount mismatch with bank credit</option>
              <option value="Duplicate payment entry">Duplicate payment entry</option>
              <option value="Cheque bounced / returned">Cheque bounced / returned</option>
              <option value="Incorrect UTR / Reference number">Incorrect UTR / Reference number</option>
              <option value="Order cancelled / Void payment">Order cancelled / Void payment</option>
              <option value="Other">Other reason</option>
            </select>
          </div>

          <!-- Remark Textarea (Compulsory) -->
          <div class="mb-2">
            <label class="form-label fw-bold small text-dark mb-1">
              <i class="fa fa-comment-dots text-danger mr-1 me-1"></i> Rejection Remark <span class="text-danger">*</span>
            </label>
            <textarea id="reject_remark_text" class="form-control" rows="3" placeholder="Enter detailed reason for rejecting this payment..."></textarea>
            <small class="text-muted" style="font-size: 0.75rem;">This remark will be permanently recorded and displayed on this payment card.</small>
          </div>

          <div id="reject_modal_alert" class="alert alert-danger p-2 px-3 mt-2 mb-0 small d-none rounded">
            <i class="fa fa-exclamation-circle mr-1 me-1"></i> <span id="reject_modal_alert_msg">Please enter a rejection remark.</span>
          </div>
        </div>
        <div class="modal-footer bg-white border-0 py-3 px-4 d-flex justify-content-between">
          <button type="button" class="btn btn-outline-secondary btn-sm px-3.5 rounded" data-dismiss="modal" data-bs-dismiss="modal" onclick="closeBootstrapModal('rejectPaymentModal')">Cancel</button>
          <button type="button" class="btn btn-danger btn-sm px-4 rounded shadow-sm font-weight-bold fw-semibold" id="btnConfirmRejectSubmit" onclick="submitRejectPayment()">
            <i class="fa fa-times-circle mr-1 me-1"></i> Confirm Reject
          </button>
        </div>
      </div>
    </div>
  </div>

<?php if ($use_standalone_layout): ?>
  <!-- Scripts for Standalone Mode -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<?php else: ?>
  <!-- Scripts for Standard ERP Mode -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<?php endif; ?>

  <script>
    var currentStatusTab = 'unverified';
    var allPaymentsData = [];
    var pageProjectId = $('body').data('projectid') || <?php echo json_encode($projectid); ?> || 17;
    var pageLoginName = $('body').data('loginname') || <?php echo json_encode($loginname); ?> || 'Admin';
    var pageRootUrl = $('body').data('rooturl') || <?php echo json_encode($rooturl); ?> || '';

    $(document).ready(function() {
      // Ensure modals are top-level children of body to avoid stacking context issues
      $('#paymentVerificationModal, #receiptModal, #utrDetailsModal, #rejectPaymentModal').appendTo('body');

      // Initialize Select2 dropdowns if available
      if (typeof $.fn.select2 !== 'undefined') {
        $('.select2').select2({
          placeholder: "Choose an option",
          allowClear: true
        });
      }

      // Fetch filter metadata (products & clients)
      loadFilterMetadata();

      // Initial Payment Load
      loadPayments();

      // Live search typing listener with debounce
      let searchTimeout;
      $("#filter_search").on("keyup input", function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
          loadPayments();
        }, 400);
      });
    });

    // 1. Fetch Products & Clients for Filter Dropdowns
    function loadFilterMetadata() {
      $.ajax({
        url: "modal/payment_verification_modal.php?action=get_filter_data",
        type: "POST",
        data: { projectid: pageProjectId },
        dataType: "json",
        success: function(res) {
          if (res.status === 'success') {
            // Populate Products
            let prodOptions = '<option value="">All Products</option>';
            if (res.products && res.products.length) {
              res.products.forEach(p => {
                prodOptions += `<option value="${p.productid}">${p.productname} ${p.isgrafted ? '(' + p.isgrafted + ')' : ''}</option>`;
              });
            }
            $('#filter_product').html(prodOptions);
            if (typeof $.fn.select2 !== 'undefined') {
              $('#filter_product').trigger('change.select2');
            }

            // Populate Clients
            let clientOptions = '<option value="">All Clients</option>';
            if (res.clients && res.clients.length) {
              res.clients.forEach(c => {
                clientOptions += `<option value="${c.clientid}">${c.clientname} ${c.mobileno ? '(' + c.mobileno + ')' : ''}</option>`;
              });
            }
            $('#filter_client').html(clientOptions);
            if (typeof $.fn.select2 !== 'undefined') {
              $('#filter_client').trigger('change.select2');
            }
          }
        }
      });
    }

    // 2. Fetch & Render Payment Records
    function loadPayments() {
      const productid = $('#filter_product').val() || '';
      const clientid = $('#filter_client').val() || '';
      const psodate = $('#filter_psodate').val() || '';
      const date_from = $('#filter_date_from').val() || '';
      const date_to = $('#filter_date_to').val() || '';
      const search = $('#filter_search').val() || '';

      $('#loadingSpinner').removeClass('d-none');
      $('#paymentCardsGrid').empty();
      $('#emptyStateContainer').addClass('d-none');

      $.ajax({
        url: "modal/payment_verification_modal.php?action=fetch_payments",
        type: "POST",
        data: {
          projectid: pageProjectId,
          productid: productid,
          clientid: clientid,
          psodate: psodate,
          date_from: date_from,
          date_to: date_to,
          is_all_time: $('#btn_preset_all').hasClass('active') ? 1 : 0,
          verification_status: currentStatusTab,
          search: search
        },
        dataType: "json",
        success: function(res) {
          $('#loadingSpinner').addClass('d-none');
          if (res.status === 'success') {
            if (res.applied_date_from && !$('#filter_date_from').val()) {
              $('#filter_date_from').val(res.applied_date_from);
            }
            if (res.applied_date_to && !$('#filter_date_to').val()) {
              $('#filter_date_to').val(res.applied_date_to);
            }
            if (res.applied_preset && !$('#btn_preset_all').hasClass('active')) {
              $('.btn-group button').removeClass('active');
              if (res.applied_preset === 'this_month') {
                $('#btn_preset_this_month').addClass('active');
              } else if (res.applied_preset === 'prev_month') {
                $('#btn_preset_prev_month').addClass('active');
              }
            }
            allPaymentsData = res.data || [];
            updateSummaryStats(res.stats);
            renderPaymentRows(allPaymentsData);
          } else {
            if (typeof Swal !== 'undefined') {
              Swal.fire('Error', res.message || 'Failed to fetch payments', 'error');
            } else {
              alert(res.message || 'Failed to fetch payments');
            }
          }
        },
        error: function(xhr, status, error) {
          $('#loadingSpinner').addClass('d-none');
          console.error('loadPayments AJAX error:', status, error, xhr.responseText);
          let errMsg = 'Server communication failure while loading payments.';
          if (xhr.responseJSON && xhr.responseJSON.message) {
            errMsg = xhr.responseJSON.message;
          } else if (xhr.status) {
            errMsg = `Server communication failure (Status: ${xhr.status} ${error || ''}).`;
          }
          if (typeof Swal !== 'undefined') {
            Swal.fire('Error', errMsg, 'error');
          } else {
            alert(errMsg);
          }
        }
      });
    }

    // 3. Render Metric Cards
    function updateSummaryStats(stats) {
      if (!stats) return;
      $('#stat_total_amt').text(stats.total_amount_formatted);
      $('#stat_total_cnt').text(`${stats.total_count} Transactions`);

      $('#stat_unverified_amt').text(stats.unverified_amount_formatted);
      $('#stat_unverified_cnt').text(`${stats.unverified_count} Pending Verification`);
      $('#badge_tab_unverified').text(stats.unverified_count);

      $('#stat_verified_amt').text(stats.verified_amount_formatted);
      $('#stat_verified_cnt').text(`${stats.verified_count} Verified`);
      $('#badge_tab_verified').text(stats.verified_count);

      $('#badge_tab_rejected').text(stats.rejected_count || 0);

      $('#stat_today_amt').text(stats.today_amount_formatted);
      $('#stat_today_cnt').text(`${stats.today_count} Received Today`);

      $('#badge_tab_all').text(stats.total_count);

      if (currentStatusTab === 'unverified' && stats.unverified_count > 0) {
        $('#batchActionToolbar').removeClass('d-none');
      } else {
        $('#batchActionToolbar').addClass('d-none');
      }
    }

    // 4. Render Single-Row Full Width Payment Strips
    function renderPaymentRows(payments) {
      const $container = $('#paymentCardsGrid');
      $container.empty();
      $('#selectAllCheckbox').prop('checked', false);
      updateSelectedCount();

      if (!payments || payments.length === 0) {
        $('#emptyStateContainer').removeClass('d-none');
        return;
      }

      payments.forEach(p => {
        const isVerified = (p.verification || '').toLowerCase() === 'verified';
        const isRejected = (p.verification || '').toLowerCase() === 'rejected';
        const rowBorderClass = isVerified ? 'verified-row' : (isRejected ? 'rejected-row' : 'unverified-row');
        const paymentMode = p.sourceofpayment || 'Cash';

        // Receipt Attachment Box
        let receiptHtml = '';
        if (p.receiptpath) {
          const fullReceiptUrl = pageRootUrl + p.receiptpath;
          const isPdf = p.receiptpath.toLowerCase().endsWith('.pdf');

          if (isPdf) {
            receiptHtml = `
              <div class="pdf-preview-box shadow-2xs" onclick="openReceiptModal('${fullReceiptUrl}', 'pdf')" title="Click to view PDF receipt">
                <i class="fa fa-file-pdf fs-4"></i>
                <span style="font-size: 0.65rem; font-weight: 700;">PDF</span>
              </div>
            `;
          } else {
            receiptHtml = `
              <img src="${fullReceiptUrl}" class="receipt-thumb shadow-2xs" onclick="openReceiptModal('${fullReceiptUrl}', 'image')" alt="Receipt" title="Click to enlarge image" onerror="this.onerror=null; this.src='data:image/svg+xml;utf8,<svg xmlns=\\'http://www.w3.org/2000/svg\\' width=\\'68\\' height=\\'68\\' viewBox=\\'0 0 68 68\\' fill=\\'%23f1f5f9\\'><rect width=\\'68\\' height=\\'68\\' rx=\\'10\\' fill=\\'%23f8fafc\\' stroke=\\'%23cbd5e1\\' stroke-width=\\'1\\'/><text x=\\'50%\\' y=\\'45%\\' dominant-baseline=\\'middle\\' text-anchor=\\'middle\\' font-size=\\'11\\' fill=\\'%2394a3b8\\' font-family=\\'sans-serif\\'>File</text><text x=\\'50%\\' y=\\'65%\\' dominant-baseline=\\'middle\\' text-anchor=\\'middle\\' font-size=\\'9\\' fill=\\'%2394a3b8\\' font-family=\\'sans-serif\\'>Missing</text></svg>';">
            `;
          }
        } else {
          receiptHtml = `
            <div class="receipt-thumb d-flex flex-column align-items-center justify-content-center bg-light text-muted border shadow-2xs" style="cursor: default;" title="No receipt uploaded">
              <i class="fa fa-image-slash fs-5 opacity-50 mb-1"></i>
              <span style="font-size: 0.65rem; font-weight: 500;">No File</span>
            </div>
          `;
        }

        // Verification details badge & Mismatch Warning
        let verStatusBadge = '';
        let mismatchWarning = '';
        const orderStatus = (p.pso_status || '').trim();

        if (isVerified) {
          verStatusBadge = `
            <span class="status-badge-verified shadow-2xs mb-1">
              <i class="fa fa-check-circle"></i> Verified
            </span>
          `;
          if (p.invoiceno && orderStatus && orderStatus !== 'Confirmed' && orderStatus !== 'Cancelled') {
            mismatchWarning = `
              <div class="mb-1 text-end">
                <span class="badge bg-warning text-dark border px-2 py-1 shadow-2xs text-wrap" style="font-size: 0.7rem; cursor: pointer;" onclick="reverifyAndSyncOrder(${p.sno}, '${p.invoiceno}')" title="Payment is Verified but Order is currently '${orderStatus}'. Click to Re-Verify & Sync to Confirmed.">
                  <i class="fa fa-exclamation-triangle mr-1"></i> Order: ${orderStatus} (Click to Sync)
                </span>
              </div>
            `;
          }
        } else if (isRejected) {
          verStatusBadge = `
            <span class="status-badge-rejected shadow-2xs mb-1">
              <i class="fa fa-times-circle"></i> Rejected
            </span>
          `;
        } else {
          verStatusBadge = `
            <span class="status-badge-unverified shadow-2xs mb-1">
              <span class="pulse-dot"></span> Unverified
            </span>
          `;
        }

        // Action Buttons
        let actionButtonsHtml = '';
        const safeOrderNo = escapeJsString(p.invoiceno || '');
        const safeClientName = escapeJsString(p.clientname || '');
        const safeAmount = parseFloat(p.advanceamount || 0);

        if (isVerified) {
          actionButtonsHtml = `
            <div class="d-flex align-items-center gap-1.5 flex-wrap justify-content-end">
              <button type="button" class="btn btn-action-edit btn-sm shadow-2xs" onclick="openPaymentModal(${p.sno}, false)" title="Edit Payment Receipt Details">
                <i class="fa fa-edit mr-1 me-1"></i> Edit
              </button>
              <button type="button" class="btn btn-outline-success btn-sm shadow-2xs font-weight-bold" onclick="reverifyAndSyncOrder(${p.sno}, '${p.invoiceno || ''}')" title="Re-Verify & Sync Provisional Order & Production Stock Order to Confirmed">
                <i class="fa fa-sync-alt mr-1 me-1"></i> Re-Verify
              </button>
              <button type="button" class="btn btn-action-revert btn-sm shadow-2xs" onclick="revertPaymentVerification(${p.sno})" title="Revert to Unverified">
                <i class="fa fa-undo mr-1 me-1"></i> Revert
              </button>
              <button type="button" class="btn btn-action-reject btn-sm shadow-2xs" onclick="openRejectPaymentModal(${p.sno}, '${safeOrderNo}', '${safeClientName}', ${safeAmount})" title="Reject this payment with remark">
                <i class="fa fa-times-circle mr-1 me-1"></i> Reject
              </button>
            </div>
          `;
        } else if (isRejected) {
          actionButtonsHtml = `
            <div class="d-flex align-items-center gap-1.5 flex-wrap justify-content-end">
              <button type="button" class="btn btn-action-edit btn-sm shadow-2xs" onclick="openPaymentModal(${p.sno}, false)" title="Edit Transaction Details">
                <i class="fa fa-edit mr-1 me-1"></i> Edit
              </button>
              <button type="button" class="btn btn-action-verify btn-sm shadow-sm" onclick="openPaymentModal(${p.sno}, true)" title="Verify this payment now">
                <i class="fa fa-check-circle mr-1 me-1"></i> Verify
              </button>
              <button type="button" class="btn btn-action-revert btn-sm shadow-2xs" onclick="revertPaymentVerification(${p.sno})" title="Revert to Unverified">
                <i class="fa fa-undo mr-1 me-1"></i> Revert
              </button>
            </div>
          `;
        } else {
          actionButtonsHtml = `
            <div class="d-flex align-items-center gap-1.5 flex-wrap justify-content-end">
              <button type="button" class="btn btn-action-edit btn-sm shadow-2xs" onclick="openPaymentModal(${p.sno}, false)" title="Edit Transaction Details">
                <i class="fa fa-edit mr-1 me-1"></i> Edit
              </button>
              <button type="button" class="btn btn-action-verify btn-sm shadow-sm" onclick="openPaymentModal(${p.sno}, true)" title="Verify this payment now">
                <i class="fa fa-check-circle mr-1 me-1"></i> Verify
              </button>
              <button type="button" class="btn btn-action-reject btn-sm shadow-sm" onclick="openRejectPaymentModal(${p.sno}, '${safeOrderNo}', '${safeClientName}', ${safeAmount})" title="Reject this payment with remark">
                <i class="fa fa-times-circle mr-1 me-1"></i> Reject
              </button>
            </div>
          `;
        }

        // Ordered Products Line
        let productsSnippet = '';
        if (p.product_names) {
          const cleanProds = p.product_names.replace(/^(NonGrafted|Non-Grafted)\s*[-:]?\s*/gi, '');
          const prodList = cleanProds.split('|').map(item => `<span class="badge badge-light bg-light text-dark border px-2 py-1 mr-1 me-1 mb-1 font-weight-normal">${item.trim()}</span>`).join(' ');
          productsSnippet = `
            <div class="mt-2.5 pt-2 border-top d-flex flex-wrap align-items-center gap-1.5 small">
              <span class="text-muted mr-1 me-1 font-weight-bold fw-bold" style="font-size: 0.78rem;">
                <i class="fa fa-seedling text-success mr-1 me-1"></i> Ordered Items:
              </span>
              ${prodList}
            </div>
          `;
        }

        // Rejection Remark Banner if rejected or has remark
        let rejectionRemarkHtml = '';
        if (isRejected || (p.rejection_remark && p.rejection_remark.trim() !== '')) {
          rejectionRemarkHtml = `
            <div class="mt-2.5 pt-2 border-top">
              <div class="rejected-remark-box d-flex align-items-start gap-2">
                <i class="fa fa-ban text-danger mt-1 fs-6 flex-shrink-0"></i>
                <div class="w-100">
                  <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                    <strong class="text-danger"><i class="fa fa-exclamation-circle mr-1 me-1"></i> Payment Rejected:</strong>
                    ${p.formatted_rejectedat ? `<span class="text-muted" style="font-size: 0.72rem;"><i class="fa fa-clock mr-1 me-1"></i> ${p.formatted_rejectedat} by <strong>${escapeHtml(p.rejectedby || 'Admin')}</strong></span>` : ''}
                  </div>
                  <div class="text-dark mt-1 font-monospace" style="font-size: 0.8rem; background: #ffffff; padding: 4px 8px; border-radius: 4px; border: 1px dashed #fca5a5;">
                    ${escapeHtml(p.rejection_remark || 'No remark specified')}
                  </div>
                </div>
              </div>
            </div>
          `;
        }

        // Verification metadata (who verified & when)
        let verifiedMetaHtml = '';
        if (isVerified && p.formatted_verificationat) {
          verifiedMetaHtml = `
            <div class="small text-muted mt-1 text-right text-end" style="font-size: 0.76rem;">
              <i class="fa fa-user-check text-success mr-1 me-1"></i> Verified by <strong>${p.verificationby || 'Admin'}</strong><br>${p.formatted_verificationat}
            </div>
          `;
        }

        const rowHtml = `
          <div class="payment-row-card ${rowBorderClass} mb-3">
            <div class="row align-items-center g-3">
              
              <!-- Column 1: Checkbox, Order No, Generated By & Generated At -->
              <div class="col-12 col-md-3 col-xl-2 border-end-lg pe-md-3">
                <div class="d-flex align-items-start gap-2">
                  ${(!isVerified && !isRejected) ? `
                    <span class="chk-wrapper shadow-2xs" title="Select for batch verification">
                      <input type="checkbox" class="payment-select-chk" value="${p.sno}" onchange="updateSelectedCount()">
                    </span>
                  ` : ''}
                  <div class="w-100">
                    <span class="badge badge-dark bg-dark text-white font-monospace px-2 py-1" style="font-size: 0.85rem; letter-spacing: 0.5px;">
                      ${p.invoiceno ? '#' + p.invoiceno : 'Direct Advance'}
                    </span>
                    <div class="mt-1.5 text-muted small" style="font-size: 0.76rem; line-height: 1.45;">
                      <div class="text-dark font-weight-bold fw-bold text-truncate" title="Order Generated By">
                        <i class="fa fa-user-edit text-primary mr-1 me-1"></i> <span class="text-muted font-weight-normal fw-normal">By:</span> ${p.pso_created_by_name || 'Admin'}
                      </div>
                      <div class="text-muted text-nowrap" title="Order Generated At">
                        <i class="fa fa-calendar-alt text-secondary mr-1 me-1"></i> ${p.formatted_pso_created_at || 'N/A'}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Column 2: Client & Contact Info (Width ~30%) -->
              <div class="col-12 col-md-4 col-xl-3 border-end-lg px-md-3">
                <div class="mb-1">
                  <h6 class="font-weight-bold fw-bold text-dark mb-1 d-flex align-items-center gap-1.5">
                    <i class="fa fa-user-circle text-primary mr-1.5 me-1.5"></i> ${p.clientname || 'Unknown Client'}
                  </h6>
                  <div class="d-flex flex-wrap gap-2 text-muted small" style="font-size: 0.78rem;">
                    ${p.pso_mobileno ? `<span class="mr-2 me-2"><i class="fa fa-phone text-success mr-1 me-1"></i> ${p.pso_mobileno}</span>` : ''}
                    ${p.pso_districtname ? `<span><i class="fa fa-map-marker-alt text-danger mr-1 me-1"></i> ${p.pso_districtname}${p.pso_statename ? ', ' + p.pso_statename : ''}</span>` : ''}
                  </div>
                </div>
              </div>

              <!-- Column 3: Amount Received & Transaction Details (Width ~26%) -->
              <div class="col-12 col-md-5 col-xl-4 border-end-lg px-md-3">
                <div class="d-flex align-items-center justify-content-between">
                  <div>
                    <div class="text-muted small text-uppercase font-weight-bold fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">Amount Received</div>
                    <div class="amount-display text-success mt-0.5" style="font-size: 1.35rem;">
                      &#8377;${parseFloat(p.advanceamount || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 })}
                    </div>
                    <div class="mt-1 d-flex flex-wrap align-items-center gap-2">
                      <span class="badge badge-info bg-light text-primary border font-weight-bold fw-bold px-2 py-0.5" style="font-size: 0.74rem;">
                        <i class="fa fa-tag mr-1 me-1"></i> ${paymentMode}
                      </span>
                      ${p.utrnumber ? `
                        <span class="font-monospace text-dark small bg-light px-2 py-0.5 rounded border utr-badge-pill" 
                              style="font-size: 0.74rem; cursor: pointer;" 
                              onclick="showUtrDetailsModal('${p.utrnumber}')" 
                              title="Click to view all ${p.utr_entry_count || 1} transaction entries and order details for UTR #${p.utrnumber}">
                          <i class="fa fa-hashtag text-secondary"></i> <strong>${p.utrnumber}</strong>
                          ${(p.utr_entry_count > 1) ? `
                            <span class="badge bg-primary text-white ms-1.5 ml-1.5 px-1.5 py-0.5 rounded-pill" style="font-size: 0.65rem; font-weight: 600;">
                              <i class="fa fa-layer-group me-0.5"></i> ${p.utr_entry_count} Orders
                            </span>
                          ` : `
                            <span class="badge bg-light text-primary border ms-1 ml-1 px-1 py-0.5 rounded" style="font-size: 0.62rem;" title="View UTR Details">
                              <i class="fa fa-info-circle"></i>
                            </span>
                          `}
                        </span>
                      ` : ''}
                    </div>
                  </div>

                  <div class="text-muted small text-right text-end ms-2 ml-2" style="font-size: 0.76rem;">
                    <div><i class="fa fa-calendar-check text-info mr-1 me-1"></i> Txn: <strong>${p.formatted_paymentdate || 'N/A'}</strong></div>
                    ${(p.receivedby || p.receivedlocation || (p.sourceofpayment || '').toLowerCase() === 'cash') ? `
                      <div class="mt-1">
                        <i class="fa fa-user-tag text-secondary mr-1 me-1"></i> Recv: <strong>${p.receivedby || 'N/A'}</strong>
                        ${p.receivedlocation ? `<span class="text-muted font-italic"> (${p.receivedlocation})</span>` : ''}
                      </div>
                    ` : ''}
                  </div>
                </div>
              </div>

              <!-- Column 4: Receipt Thumbnail & Actions (Width ~22%) -->
              <div class="col-12 col-md-12 col-xl-3 ps-md-3">
                <div class="d-flex align-items-center justify-content-between justify-content-xl-end gap-3 flex-wrap">
                  <!-- Thumbnail -->
                  <div class="flex-shrink-0" title="Receipt Attachment">
                    ${receiptHtml}
                  </div>

                  <!-- Status & Buttons -->
                  <div class="d-flex flex-column align-items-end justify-content-center">
                    ${verStatusBadge}
                    ${actionButtonsHtml}
                    ${verifiedMetaHtml}
                  </div>
                </div>
              </div>

            </div>

            <!-- Ordered Products Details (Bottom strip if exists) -->
            ${productsSnippet}

            <!-- Rejection Remark Strip if rejected -->
            ${rejectionRemarkHtml}

          </div>
        `;

        $container.append(rowHtml);
      });
    }

    // 5. Single / Batch Verification Handlers
    function revertPaymentVerification(sno) {
      if (typeof Swal === 'undefined') {
        if (confirm('Are you sure you want to revert this payment to Unverified status?')) {
          doRevertPayment(sno);
        }
        return;
      }
      Swal.fire({
        title: 'Revert Verification?',
        text: 'Are you sure you want to revert this payment to Unverified status?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa fa-undo mr-1 me-1"></i> Yes, Revert'
      }).then((result) => {
        if (result.isConfirmed) {
          doRevertPayment(sno);
        }
      });
    }

    function doRevertPayment(sno) {
      $.ajax({
        url: "modal/payment_verification_modal.php?action=unverify_payment",
        type: "POST",
        data: { sno: sno },
        dataType: "json",
        success: function(res) {
          if (res.status === 'success') {
            if (typeof iziToast !== 'undefined') {
              iziToast.warning({
                title: 'Reverted!',
                message: res.message,
                position: 'topRight'
              });
            } else {
              alert(res.message);
            }
            loadPayments();
          } else {
            if (typeof Swal !== 'undefined') {
              Swal.fire('Error', res.message, 'error');
            } else {
              alert(res.message);
            }
          }
        }
      });
    }

    function batchVerifySelected() {
      const selectedIds = [];
      $('.payment-select-chk:checked').each(function() {
        selectedIds.push($(this).val());
      });

      if (selectedIds.length === 0) {
        if (typeof Swal !== 'undefined') {
          Swal.fire('Selection Required', 'Please select at least one unverified payment row.', 'info');
        } else {
          alert('Please select at least one unverified payment row.');
        }
        return;
      }

      if (typeof Swal === 'undefined') {
        if (confirm(`You are about to mark ${selectedIds.length} selected transaction(s) as Verified. Proceed?`)) {
          doBatchVerify(selectedIds);
        }
        return;
      }

      Swal.fire({
        title: `Verify ${selectedIds.length} Payment(s)?`,
        text: `You are about to mark ${selectedIds.length} selected transaction(s) as Verified.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#64748b',
        confirmButtonText: `<i class="fa fa-check-double mr-1 me-1"></i> Verify All (${selectedIds.length})`
      }).then((result) => {
        if (result.isConfirmed) {
          doBatchVerify(selectedIds);
        }
      });
    }

    function doBatchVerify(selectedIds) {
      $.ajax({
        url: "modal/payment_verification_modal.php?action=batch_verify",
        type: "POST",
        data: { snos: selectedIds, loginname: pageLoginName },
        dataType: "json",
        success: function(res) {
          if (res.status === 'success') {
            if (typeof iziToast !== 'undefined') {
              iziToast.success({
                title: 'Batch Completed!',
                message: res.message,
                position: 'topRight'
              });
            } else {
              alert(res.message);
            }
            loadPayments();
          } else {
            if (typeof Swal !== 'undefined') {
              Swal.fire('Error', res.message, 'error');
            } else {
              alert(res.message);
            }
          }
        }
      });
    }

    // 6. Selection Helpers
    function toggleSelectAll(masterChk) {
      const isChecked = $(masterChk).is(':checked');
      $('.payment-select-chk').prop('checked', isChecked);
      updateSelectedCount();
    }

    function updateSelectedCount() {
      const cnt = $('.payment-select-chk:checked').length;
      $('#selectedCount').text(cnt);
    }

    // 7. Tab Switching
    function switchStatusTab(status) {
      currentStatusTab = status;
      $('.nav-tabs-custom .nav-link').removeClass('active');
      $(`#tab-${status}`).addClass('active');
      loadPayments();
    }

    // 8. Quick Date Preset Helper
    function setDatePreset(preset, btn) {
      $('.btn-group button').removeClass('active');
      if (btn) {
        $(btn).addClass('active');
      } else if (typeof window !== 'undefined' && window.event && window.event.target) {
        $(window.event.target).addClass('active');
      }

      const today = new Date();
      const formatDate = (d) => {
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
      };

      if (preset === 'today') {
        const tStr = formatDate(today);
        $('#filter_date_from').val(tStr);
        $('#filter_date_to').val(tStr);
      } else if (preset === 'this_week') {
        const curr = new Date();
        const first = curr.getDate() - curr.getDay() + (curr.getDay() === 0 ? -6 : 1);
        const firstday = new Date(curr.setDate(first));
        const lastday = new Date(curr.setDate(firstday.getDate() + 6));
        $('#filter_date_from').val(formatDate(firstday));
        $('#filter_date_to').val(formatDate(lastday));
      } else if (preset === 'this_month') {
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        $('#filter_date_from').val(formatDate(firstDay));
        $('#filter_date_to').val(formatDate(lastDay));
      } else if (preset === 'prev_month') {
        const firstDay = new Date(today.getFullYear(), today.getMonth() - 1, 1);
        const lastDay = new Date(today.getFullYear(), today.getMonth(), 0);
        $('#filter_date_from').val(formatDate(firstDay));
        $('#filter_date_to').val(formatDate(lastDay));
      } else {
        // all time
        $('#filter_date_from').val('');
        $('#filter_date_to').val('');
      }
      loadPayments();
    }

    function resetFilters() {
      $('#filter_product').val('');
      if (typeof $.fn.select2 !== 'undefined') $('#filter_product').trigger('change.select2');
      $('#filter_client').val('');
      if (typeof $.fn.select2 !== 'undefined') $('#filter_client').trigger('change.select2');
      $('#filter_psodate').val('');
      $('#filter_search').val('');

      const defaultPreset = <?php echo json_encode($defaultPreset); ?> || 'prev_month';
      const targetBtn = (defaultPreset === 'this_month') ? '#btn_preset_this_month' : '#btn_preset_prev_month';
      setDatePreset(defaultPreset, $(targetBtn)[0]);
    }

    // 9. Excel Export Handler (Separate Column for Every Field)
    function exportPaymentsToExcel() {
      if (!allPaymentsData || allPaymentsData.length === 0) {
        if (typeof Swal !== 'undefined') {
          Swal.fire('No Data to Export', 'There are no payment records matching your current filter criteria.', 'info');
        } else {
          alert('There are no payment records matching your current filter criteria.');
        }
        return;
      }

      // Convert payments array to structured table data with separate columns
      const exportRows = allPaymentsData.map((p, index) => {
        const isVerified = (p.verification || '').toLowerCase() === 'verified';
        const cleanProducts = p.product_names ? p.product_names.replace(/^(NonGrafted|Non-Grafted)\s*[-:]?\s*/gi, '').replace(/\|/g, ', ') : 'General Advance';

        return {
          "Sr No": index + 1,
          "Provisional Order No": p.invoiceno ? '#' + p.invoiceno : 'Direct / General Advance',
          "Generated By": p.pso_created_by_name || 'Admin',
          "Order Date & Time": p.formatted_pso_created_at || 'N/A',
          "Client ID": p.clientid || '',
          "Client Name": p.clientname || 'Unknown Client',
          "Client Mobile No": p.pso_mobileno || '',
          "District": p.pso_districtname || '',
          "State": p.pso_statename || '',
          "Amount Received (INR)": parseFloat(p.advanceamount || 0),
          "Mode of Payment": p.sourceofpayment || 'Cash',
          "UTR / Ref / Txn No": p.utrnumber || p.paymentref_no || '',
          "Transaction Date": p.formatted_paymentdate || '',
          "Received By": p.receivedby || '',
          "Received Location": p.receivedlocation || '',
          "Purpose of Payment": p.purposeofpayment || 'Advance Payment',
          "Order Total Amount (INR)": parseFloat(p.pso_total_amount || 0),
          "Total Order Plants": p.total_plants || 0,
          "Ordered Products List": cleanProducts,
          "Remarks / Notes": p.remarks || '',
          "Verification Status": isVerified ? "Verified" : "Unverified",
          "Verified By": p.verificationby || 'N/A',
          "Verification Date & Time": p.formatted_verificationat || 'N/A'
        };
      });

      const tabTitle = currentStatusTab === 'unverified' ? 'Unverified_Payments' : (currentStatusTab === 'verified' ? 'Verified_Payments' : 'All_Transactions');
      const nowStr = new Date().toISOString().slice(0, 10);
      const filename = `KRF_Received_${tabTitle}_${nowStr}.xlsx`;

      if (typeof XLSX !== 'undefined') {
        const ws = XLSX.utils.json_to_sheet(exportRows);

        // Auto-fit column widths based on cell content lengths
        const colWidths = [];
        const headerKeys = Object.keys(exportRows[0]);
        headerKeys.forEach((key) => {
          let maxLen = key.length;
          exportRows.forEach(row => {
            const val = row[key] !== null && row[key] !== undefined ? String(row[key]) : '';
            if (val.length > maxLen) {
              maxLen = Math.min(val.length, 50);
            }
          });
          colWidths.push({ wch: Math.max(maxLen + 3, 12) });
        });
        ws['!cols'] = colWidths;

        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Payments");
        XLSX.writeFile(wb, filename);

        if (typeof iziToast !== 'undefined') {
          iziToast.success({
            title: 'Excel Export Complete!',
            message: `${exportRows.length} payment record(s) exported to Excel file.`,
            position: 'topRight'
          });
        }
      } else {
        // Fallback: UTF-8 BOM CSV Export
        exportFallbackCSV(exportRows, `KRF_Received_${tabTitle}_${nowStr}.csv`);
      }
    }

    function exportFallbackCSV(rows, filename) {
      if (!rows || !rows.length) return;
      const headers = Object.keys(rows[0]);
      let csvContent = "\uFEFF"; // UTF-8 BOM
      csvContent += headers.map(h => `"${h.replace(/"/g, '""')}"`).join(",") + "\r\n";

      rows.forEach(row => {
        const rowStr = headers.map(h => {
          const val = row[h] !== null && row[h] !== undefined ? String(row[h]) : '';
          return `"${val.replace(/"/g, '""')}"`;
        }).join(",");
        csvContent += rowStr + "\r\n";
      });

      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement("a");
      const url = URL.createObjectURL(blob);
      link.setAttribute("href", url);
      link.setAttribute("download", filename);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);

      if (typeof iziToast !== 'undefined') {
        iziToast.success({
          title: 'Export Complete!',
          message: `${rows.length} payment records exported to file.`,
          position: 'topRight'
        });
      }
    }

    // Safe Cross-Version Modal Helper (Bootstrap 4 & 5 Compatible without console errors)
    function openBootstrapModal(modalId) {
      const el = document.getElementById(modalId);
      if (!el) return;
      try {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
          if (typeof bootstrap.Modal.getOrCreateInstance === 'function') {
            bootstrap.Modal.getOrCreateInstance(el).show();
            return;
          }
          let inst = (typeof bootstrap.Modal.getInstance === 'function') ? bootstrap.Modal.getInstance(el) : null;
          if (!inst) inst = new bootstrap.Modal(el);
          inst.show();
          return;
        }
      } catch (e) {
        console.warn('Bootstrap modal open warning:', e);
      }
      if (typeof $ !== 'undefined' && typeof $(el).modal === 'function') {
        $(el).modal('show');
      }
    }

    function closeBootstrapModal(modalId) {
      const el = document.getElementById(modalId);
      const $el = $('#' + modalId);
      if (!el) return;
      try {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
          let inst = null;
          if (typeof bootstrap.Modal.getInstance === 'function') {
            inst = bootstrap.Modal.getInstance(el);
          }
          if (!inst && typeof bootstrap.Modal.getOrCreateInstance === 'function') {
            inst = bootstrap.Modal.getOrCreateInstance(el);
          }
          if (inst && typeof inst.hide === 'function') {
            inst.hide();
          }
        }
      } catch (e) {
        console.warn('Bootstrap modal close warning:', e);
      }
      if (typeof $ !== 'undefined' && typeof $el.modal === 'function') {
        try {
          $el.modal('hide');
        } catch(e) {}
      }
      $el.removeClass('show').attr('aria-hidden', 'true');
      setTimeout(function() {
        $el.hide();
        if (!$('.modal.show').length && !$('.modal:visible').not($el).length) {
          $('.modal-backdrop').remove();
          $('body').removeClass('modal-open').css({ 'overflow': '', 'padding-right': '' });
        }
      }, 150);
    }

    // 10. Receipt Modal Zoom & View
    function openReceiptModal(url, type) {
      const $content = $('#receiptModalContent');
      $content.empty();
      $('#receiptDownloadBtn').attr('href', url);

      if (type === 'image') {
        $content.html(`<img src="${url}" class="img-fluid rounded shadow" style="max-height: 70vh; object-fit: contain;" alt="Receipt">`);
      } else if (type === 'pdf') {
        $content.html(`<iframe src="${url}" style="width: 100%; height: 70vh; border: none; border-radius: 8px;"></iframe>`);
      }
      
      openBootstrapModal('receiptModal');
    }

    // 11. Unified Verification & Details Modal Controller
    let currentModalReceiptUrl = '';
    let currentModalReceiptType = '';

    function openPaymentModal(sno, isForVerify = false) {
      const p = allPaymentsData.find(item => item.sno == sno);
      if (!p) {
        if (typeof Swal !== 'undefined') Swal.fire('Error', 'Payment record not found.', 'error');
        else alert('Payment record not found.');
        return;
      }

      $('#modal_payment_sno').val(p.sno);
      $('#modal_payment_psono').val(p.invoiceno || '');
      $('#modal_clientname').text(p.clientname || 'Unknown Client');
      $('#modal_orderno').text(p.invoiceno ? '#' + p.invoiceno : 'Direct / General Advance');
      $('#modal_generated_info').html(
        `<span class="text-dark font-weight-bold fw-bold"><i class="fa fa-user-edit text-primary mr-1 me-1"></i> ${p.pso_created_by_name || 'Admin'}</span> <span class="text-muted" style="font-size: 0.78rem;">(${p.formatted_pso_created_at || 'N/A'})</span>`
      );
      
      const isVerified = (p.verification || '').toLowerCase() === 'verified';
      const isRejected = (p.verification || '').toLowerCase() === 'rejected';
      $('#modal_current_status_badge').html(
        isVerified 
          ? '<span class="status-badge-verified py-0.5"><i class="fa fa-check-circle"></i> Verified</span>' 
          : (isRejected
              ? '<span class="status-badge-rejected py-0.5"><i class="fa fa-times-circle"></i> Rejected</span>'
              : '<span class="status-badge-unverified py-0.5"><span class="pulse-dot"></span> Unverified</span>')
      );

      if (isRejected && p.rejection_remark) {
        $('#modal_validation_alert').removeClass('d-none').removeClass('alert-danger').addClass('alert-warning');
        $('#modal_validation_msg').html(`<strong>Payment is currently Rejected:</strong> ${escapeHtml(p.rejection_remark)} <br><small class="text-muted">Rejected by ${escapeHtml(p.rejectedby || 'Admin')} ${p.formatted_rejectedat ? 'on ' + p.formatted_rejectedat : ''}</small>`);
      } else {
        $('#modal_validation_alert').addClass('d-none').removeClass('alert-warning').addClass('alert-danger');
      }

      if (isRejected) {
        $('#btnModalReject').html('<i class="fa fa-edit mr-1 me-1"></i> Update Rejection Remark');
      } else {
        $('#btnModalReject').html('<i class="fa fa-times-circle mr-1 me-1"></i> Reject Payment');
      }

      // Populate Linked Order Matching Card
      const psoTotal = parseFloat(p.pso_total_amount) || 0;
      const totalPaid = parseFloat(p.paid_amount || p.advanceamount) || 0;
      const pendingBal = Math.max(0, psoTotal - totalPaid);
      const totalPlants = parseInt(p.total_plants) || 0;
      const cropNames = p.product_names || 'N/A';

      if (p.invoiceno && p.invoiceno.trim() !== '') {
        $('#modal_match_ordertotal').text('₹' + psoTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        $('#modal_match_paidamount').text('₹' + totalPaid.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        $('#modal_match_pendingbalance').text('₹' + pendingBal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        $('#modal_match_totalplants').text(totalPlants > 0 ? (totalPlants.toLocaleString('en-IN') + ' Plants') : 'N/A');
        $('#modal_match_cropnames').text(cropNames);
        $('#modal_match_badge').html('<i class="fa fa-check-double mr-1 me-1"></i> Order & Payment Details Matched (#' + p.invoiceno + ')').removeClass('bg-warning text-dark').addClass('bg-success text-white');
        $('#modal_pso_match_card').show();
      } else {
        $('#modal_match_ordertotal').text('N/A (General Advance)');
        $('#modal_match_paidamount').text('₹' + (parseFloat(p.advanceamount) || 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        $('#modal_match_pendingbalance').text('₹0.00');
        $('#modal_match_totalplants').text('N/A');
        $('#modal_match_cropnames').text('Direct / General Advance Payment');
        $('#modal_match_badge').html('<i class="fa fa-info-circle mr-1 me-1"></i> General / Unlinked Payment').removeClass('bg-success text-white').addClass('bg-warning text-dark');
        $('#modal_pso_match_card').show();
      }

      // Amount
      const rawAmt = parseFloat(p.advanceamount) || 0;
      $('#modal_advanceamount').val(rawAmt > 0 ? rawAmt : '');

      // Payment Mode
      let mode = (p.sourceofpayment || '').trim();
      if (mode.toLowerCase().includes('upi')) mode = 'UPI';
      else if (mode.toLowerCase().includes('cash')) mode = 'Cash';
      else if (mode.toLowerCase().includes('neft') || mode.toLowerCase().includes('rtgs')) mode = 'NEFT/RTGS';
      else if (mode.toLowerCase().includes('cheque')) mode = 'Cheque';
      else if (mode.toLowerCase().includes('card') || mode.toLowerCase().includes('pos')) mode = 'Card';
      else if (mode.toLowerCase().includes('bank')) mode = 'Bank Transfer';
      else if (!mode) mode = 'UPI';
      $('#modal_sourceofpayment').val(mode);

      // UTR & Date
      const cleanUtr = (p.utrnumber && p.utrnumber !== 'N/A') ? p.utrnumber : (p.paymentref_no && p.paymentref_no !== 'N/A' ? p.paymentref_no : '');
      $('#modal_utrnumber').val(cleanUtr);
      $('#modal_paymentdate').val(p.paymentdate || '');

      // Cash Fields (Received By & Location)
      $('#modal_receivedby').val(p.receivedby || '');
      $('#modal_receivedlocation').val(p.receivedlocation || '');

      // Reset file input & label
      $('#modal_receipt_file').val('');
      $('#modal_upload_file_name').text('');

      // Setup Live Preview in Modal
      if (p.receiptpath) {
        currentModalReceiptUrl = pageRootUrl + p.receiptpath;
        currentModalReceiptType = p.receiptpath.toLowerCase().endsWith('.pdf') ? 'pdf' : 'image';
        renderModalPreview(currentModalReceiptUrl, currentModalReceiptType, 'Existing Receipt Attached');
        $('#btnReceiptZoom').show();
        $('#modal_receipt_subtext').text('(Optional to replace existing file)');
      } else {
        currentModalReceiptUrl = '';
        currentModalReceiptType = '';
        renderModalPreview('', '', 'No Attachment Uploaded');
        $('#btnReceiptZoom').hide();
        $('#modal_receipt_subtext').text('(Mandatory document proof)');
      }

      handlePaymentModeChange();
      $('#modal_validation_alert').addClass('d-none');

      // If user clicked direct Verify button, highlight verification button
      if (isForVerify) {
        $('#btnModalVerify').addClass('btn-pulse');
      } else {
        $('#btnModalVerify').removeClass('btn-pulse');
      }

      openBootstrapModal('paymentVerificationModal');
    }

    function handlePaymentModeChange() {
      const mode = ($('#modal_sourceofpayment').val() || '').toLowerCase();
      if (mode === 'cash') {
        // Hide Transaction No / UTR for Cash, Show Cash Received By & Location
        $('#modal_utr_wrapper').addClass('d-none');
        $('#modal_cash_details_row').removeClass('d-none');
        $('#modal_utr_required_star').addClass('d-none');
        $('#modal_utrnumber').val('');
      } else {
        // Show Transaction No / UTR for other payment modes, Hide Cash fields
        $('#modal_utr_wrapper').removeClass('d-none');
        $('#modal_cash_details_row').addClass('d-none');
        $('#modal_utr_required_star').removeClass('d-none');
        $('#modal_utrnumber').attr('placeholder', 'e.g. 023321814862 (Required for ' + $('#modal_sourceofpayment').val() + ')');
      }
    }

    function previewModalReceipt(input) {
      if (input.files && input.files[0]) {
        const file = input.files[0];
        $('#modal_upload_file_name').text(file.name + ` (${(file.size / 1024).toFixed(1)} KB)`);

        const isPdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
        const objectUrl = URL.createObjectURL(file);

        currentModalReceiptUrl = objectUrl;
        currentModalReceiptType = isPdf ? 'pdf' : 'image';
        renderModalPreview(objectUrl, currentModalReceiptType, 'New File Selected: ' + file.name);
        $('#btnReceiptZoom').show();
      }
    }

    function renderModalPreview(url, type, statusText) {
      const $area = $('#modal_receipt_preview_area');
      $area.empty();
      $('#modal_receipt_preview_status').text(statusText || '-');

      if (!url) {
        $area.html(`
          <div class="text-muted p-4">
            <i class="fa fa-file-invoice fa-3x text-secondary opacity-50 mb-2"></i>
            <p class="small mb-0">No document attached for this payment.</p>
            <span class="text-danger small" style="font-size: 0.72rem; font-weight: 600;">Document is compulsory. Upload an image or PDF from the left form</span>
          </div>
        `);
        return;
      }

      if (type === 'pdf') {
        $area.html(`
          <div class="w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3">
            <i class="fa fa-file-pdf fa-4x text-danger mb-2"></i>
            <h6 class="fw-bold text-dark mb-1">PDF Document Attached</h6>
            <a href="${url}" target="_blank" class="btn btn-outline-danger btn-sm px-3 rounded mt-2">
              <i class="fa fa-external-link-alt mr-1 me-1"></i> Open PDF in New Tab
            </a>
          </div>
        `);
      } else {
        $area.html(`
          <img src="${url}" class="img-fluid rounded shadow-sm" style="max-height: 270px; object-fit: contain; cursor: pointer;" onclick="zoomCurrentReceipt()" alt="Receipt Preview" title="Click to zoom" onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\\'text-warning p-4\\'><i class=\\'fa fa-exclamation-circle fa-3x mb-2\\'></i><p class=\\'small mb-0\\'>The attached file could not be found on server.</p><span class=\\'text-muted small\\'>Please upload a replacement document using the form.</span></div>';">
        `);
      }
    }

    function zoomCurrentReceipt() {
      if (currentModalReceiptUrl) {
        openReceiptModal(currentModalReceiptUrl, currentModalReceiptType);
      }
    }

    function escapeHtml(text) {
      if (text === null || text === undefined) return '';
      return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function escapeJsString(str) {
      if (str === null || str === undefined) return '';
      return String(str)
        .replace(/\\/g, '\\\\')
        .replace(/'/g, "\\'")
        .replace(/"/g, '&quot;')
        .replace(/[\r\n]+/g, ' ');
    }

    // Reject Payment Handlers
    function openRejectPaymentModal(sno, orderNo = '', clientName = '', amount = 0) {
      if (!sno) return;
      
      const p = (typeof allPaymentsData !== 'undefined' && Array.isArray(allPaymentsData))
        ? allPaymentsData.find(item => item.sno == sno)
        : null;

      const resolvedOrderNo = orderNo || (p && p.invoiceno ? '#' + p.invoiceno : 'Direct Advance');
      const resolvedClientName = clientName || (p && p.clientname ? p.clientname : 'Unknown Client');
      const resolvedAmount = (amount > 0) ? amount : (p ? parseFloat(p.advanceamount || 0) : 0);
      const existingRemark = (p && p.rejection_remark) ? p.rejection_remark : '';

      $('#reject_payment_sno').val(sno);
      $('#reject_order_no').text(resolvedOrderNo.startsWith('#') ? resolvedOrderNo : ('#' + resolvedOrderNo));
      $('#reject_client_name').text(resolvedClientName);
      $('#reject_amount').text('₹' + parseFloat(resolvedAmount).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
      $('#reject_reason_preset').val('');
      $('#reject_remark_text').val(existingRemark);
      $('#reject_modal_alert').addClass('d-none');
      $('#btnConfirmRejectSubmit').prop('disabled', false).html('<i class="fa fa-times-circle mr-1 me-1"></i> Confirm Reject');

      openBootstrapModal('rejectPaymentModal');
    }

    function handleRejectReasonPresetChange() {
      const preset = $('#reject_reason_preset').val();
      if (!preset || preset === 'Other') return;
      const current = $('#reject_remark_text').val().trim();
      if (!current) {
        $('#reject_remark_text').val(preset);
      } else if (!current.includes(preset)) {
        $('#reject_remark_text').val(preset + ' - ' + current);
      }
    }

    function openRejectPaymentFromModal() {
      const sno = $('#modal_payment_sno').val();
      if (!sno) return;
      const p = (typeof allPaymentsData !== 'undefined' && Array.isArray(allPaymentsData))
        ? allPaymentsData.find(item => item.sno == sno)
        : null;
      const orderNo = p && p.invoiceno ? '#' + p.invoiceno : ($('#modal_orderno').text() || 'Direct Advance');
      const clientName = p && p.clientname ? p.clientname : ($('#modal_clientname').text() || 'Unknown Client');
      const amount = parseFloat($('#modal_advanceamount').val() || (p ? p.advanceamount : 0));
      openRejectPaymentModal(sno, orderNo, clientName, amount);
    }

    function submitRejectPayment() {
      const sno = $('#reject_payment_sno').val();
      const remark = $('#reject_remark_text').val().trim();

      if (!sno) {
        $('#reject_modal_alert_msg').text('Invalid payment record ID.');
        $('#reject_modal_alert').removeClass('d-none');
        return;
      }

      if (!remark) {
        $('#reject_modal_alert_msg').text('Please enter a rejection remark explaining why this payment is rejected.');
        $('#reject_modal_alert').removeClass('d-none');
        $('#reject_remark_text').addClass('is-invalid').focus();
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'warning',
            title: 'Rejection Remark Required',
            text: 'Please enter a detailed reason or remark for rejecting this payment.',
            confirmButtonColor: '#ef4444'
          });
        }
        return;
      }
      $('#reject_remark_text').removeClass('is-invalid');

      $('#reject_modal_alert').addClass('d-none');
      const $btn = $('#btnConfirmRejectSubmit');
      $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-1 me-1" role="status" aria-hidden="true"></span> Rejecting...');

      $.ajax({
        url: "modal/payment_verification_modal.php?action=reject_payment",
        type: "POST",
        data: {
          sno: sno,
          rejection_remark: remark,
          loginname: pageLoginName
        },
        dataType: "json",
        success: function(res) {
          $btn.prop('disabled', false).html('<i class="fa fa-times-circle mr-1 me-1"></i> Confirm Reject');
          if (res && res.status === 'success') {
            // Close modals cleanly
            closeBootstrapModal('rejectPaymentModal');
            closeBootstrapModal('paymentVerificationModal');
            $('#rejectPaymentModal, #paymentVerificationModal').removeClass('show').hide().attr('aria-hidden', 'true');
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open').css({ 'overflow': '', 'padding-right': '' });

            // Clear inputs
            $('#reject_remark_text').val('');
            $('#reject_reason_preset').val('');
            $('#reject_modal_alert').addClass('d-none');

            // Prompt Success Message
            const successMsg = res.message || 'Payment has been rejected with remark successfully.';
            if (typeof Swal !== 'undefined') {
              Swal.fire({
                icon: 'success',
                title: 'Payment Rejected!',
                text: successMsg,
                timer: 3000,
                showConfirmButton: true,
                confirmButtonColor: '#10b981',
                confirmButtonText: 'OK'
              });
            } else if (typeof iziToast !== 'undefined') {
              iziToast.success({
                title: 'Rejected Successfully',
                message: successMsg,
                position: 'topRight',
                timeout: 3000
              });
            } else {
              alert(successMsg);
            }

            loadPayments();
          } else {
            const errorMsg = (res && res.message) ? res.message : 'Failed to reject payment.';
            $('#reject_modal_alert_msg').text(errorMsg);
            $('#reject_modal_alert').removeClass('d-none');
            if (typeof Swal !== 'undefined') {
              Swal.fire({
                icon: 'error',
                title: 'Rejection Failed',
                text: errorMsg,
                confirmButtonColor: '#ef4444'
              });
            }
          }
        },
        error: function(xhr, status, error) {
          $btn.prop('disabled', false).html('<i class="fa fa-times-circle mr-1 me-1"></i> Confirm Reject');
          const errorMsg = (xhr.responseJSON && xhr.responseJSON.message) 
            ? xhr.responseJSON.message 
            : ('Server error: ' + (error || 'Failed to communicate with server.'));
          $('#reject_modal_alert_msg').text(errorMsg);
          $('#reject_modal_alert').removeClass('d-none');
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'error',
              title: 'Communication Error',
              text: errorMsg,
              confirmButtonColor: '#ef4444'
            });
          }
        }
      });
    }

    function submitPaymentModal(verifyNow = false) {
      debugger;
      const sno = $('#modal_payment_sno').val();
      const psono = $('#modal_payment_psono').val();
      const advanceamount = parseFloat($('#modal_advanceamount').val());
      const sourceofpayment = $('#modal_sourceofpayment').val();
      const isCash = (sourceofpayment || '').toLowerCase() === 'cash';
      const utrnumber = isCash ? '' : ($('#modal_utrnumber').val() || '').trim();
      const paymentdate = ($('#modal_paymentdate').val() || '').trim();
      const receivedby = isCash ? ($('#modal_receivedby').val() || '').trim() : '';
      const receivedlocation = isCash ? ($('#modal_receivedlocation').val() || '').trim() : '';
      const fileInput = document.getElementById('modal_receipt_file');

      $('#modal_validation_alert').addClass('d-none');

      // Comprehensive validation of all compulsory fields
      if (!sno) {
        showModalValidation('Payment record identifier is missing.');
        return;
      }

      if (isNaN(advanceamount) || advanceamount <= 0) {
        showModalValidation('Please enter a valid Amount Received greater than 0.');
        $('#modal_advanceamount').focus();
        return;
      }

      if (!sourceofpayment) {
        showModalValidation('Please select a Mode of Payment.');
        $('#modal_sourceofpayment').focus();
        return;
      }

      if (!paymentdate) {
        showModalValidation('Please select a valid Transaction Date.');
        $('#modal_paymentdate').focus();
        return;
      }

      if (isCash) {
        if (!receivedby) {
          showModalValidation('Received By (Person Name) is compulsory for Cash payment.');
          $('#modal_receivedby').focus();
          return;
        }
        if (!receivedlocation) {
          showModalValidation('Received Location is compulsory for Cash payment.');
          $('#modal_receivedlocation').focus();
          return;
        }
      } else {
        if (!utrnumber) {
          showModalValidation(`Txn. No / UTR No is compulsory for ${sourceofpayment} payment.`);
          $('#modal_utrnumber').focus();
          return;
        }
      }

      // Mandatory Document Attachment Check
      const hasNewFile = fileInput.files && fileInput.files.length > 0;
      const hasExistingDoc = !!currentModalReceiptUrl;
      if (!hasNewFile && !hasExistingDoc) {
        showModalValidation('Payment proof / receipt document is compulsory. Please choose and upload a document.');
        $('#modal_receipt_file').focus();
        return;
      }

      const doAjaxSubmit = () => {
        const formData = new FormData();
        formData.append('action', 'update_receipt');
        formData.append('sno', sno);
        formData.append('psono', psono);
        formData.append('projectid', pageProjectId);
        formData.append('loginname', pageLoginName);
        formData.append('advanceamount', advanceamount);
        formData.append('sourceofpayment', sourceofpayment);
        formData.append('utrnumber', utrnumber);
        formData.append('receivedby', receivedby);
        formData.append('receivedlocation', receivedlocation);
        formData.append('paymentdate', paymentdate);
        formData.append('verify_now', verifyNow ? 'yes' : 'no');

        if (fileInput.files && fileInput.files.length > 0) {
          formData.append('receiptfile', fileInput.files[0]);
        }

        const btnId = verifyNow ? '#btnModalVerify' : '#btnModalUpdateOnly';
        const originalBtnText = $(btnId).html();
        $(btnId).prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1 me-1"></i> Processing...');

        $.ajax({
          url: "modal/payment_verification_modal.php?action=update_receipt",
          type: "POST",
          data: formData,
          contentType: false,
          processData: false,
          dataType: "json",
          success: function(res) {
            $(btnId).prop('disabled', false).html(originalBtnText);
            if (res.status === 'success') {
              // Close the modal cleanly across all Bootstrap versions
              closeBootstrapModal('paymentVerificationModal');

              // Show SweetAlert success popup
              if (typeof Swal !== 'undefined') {
                Swal.fire({
                  icon: 'success',
                  title: verifyNow ? 'Payment Verified Successfully!' : 'Payment Details Updated Successfully!',
                  text: res.message,
                  timer: 2500,
                  showConfirmButton: true,
                  confirmButtonColor: '#10b981',
                  confirmButtonText: 'OK'
                });
              } else if (typeof iziToast !== 'undefined') {
                iziToast.success({
                  title: verifyNow ? 'Verified!' : 'Updated!',
                  message: res.message,
                  position: 'topRight'
                });
              } else {
                alert(res.message);
              }

              // Reload data in-place without page load
              loadPayments();
            } else {
              showModalValidation(res.message || 'Failed to update transaction.');
            }
          },
          error: function(xhr, status, error) {
            $(btnId).prop('disabled', false).html(originalBtnText);
            let errorMsg = 'Failed to process request on server.';
            try {
              if (xhr.responseText) {
                const json = JSON.parse(xhr.responseText);
                if (json && json.message) errorMsg = json.message;
              }
            } catch(e) {
              errorMsg = (error && error.toString().length > 0 && error.toString() !== 'Error') ? ('Server error: ' + error) : 'Server communication error. Please try again.';
            }
            showModalValidation(errorMsg, 'Verification Error');
          }
        });
      };

      if (verifyNow && typeof Swal !== 'undefined') {
        const clientName = $('#modal_clientname').text();
        const orderNo = $('#modal_payment_psono').val() || $('#modal_orderno').text().replace('#', '') || 'Direct Payment';
        const formattedAmt = '₹' + parseFloat(advanceamount).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        
        const confirmHtml = `
          <div class="text-start small p-3 rounded" style="background:#f8fafc; border:1px solid #e2e8f0; font-size: 0.85rem; line-height: 1.6;">
            <div class="mb-1.5"><span class="text-muted">Client Name:</span> <strong class="text-dark">${escapeHtml(clientName)}</strong></div>
            <div class="mb-1.5"><span class="text-muted">Provisional Order / Invoice:</span> <strong class="text-primary font-monospace">#${escapeHtml(orderNo)}</strong></div>
            <div class="mb-1.5"><span class="text-muted">Payment Amount:</span> <strong class="text-success">${formattedAmt}</strong></div>
            <div class="mb-1.5"><span class="text-muted">Payment Mode:</span> <strong class="text-dark">${escapeHtml(sourceofpayment)}</strong></div>
            ${isCash ? `<div><span class="text-muted">Received By & Place:</span> <strong class="text-dark">${escapeHtml(receivedby)} (${escapeHtml(receivedlocation)})</strong></div>` : `<div><span class="text-muted">Txn. / UTR No:</span> <strong class="text-dark font-monospace">${escapeHtml(utrnumber)}</strong></div>`}
          </div>
          <div class="mt-2 text-muted small text-start">
            <i class="fa fa-info-circle text-info mr-1 me-1"></i> Please confirm that the invoice no and payment details match this provisional order before verifying.
          </div>
        `;

        Swal.fire({
          title: 'Confirm Payment Verification',
          html: confirmHtml,
          icon: 'question',
          showCancelButton: true,
          confirmButtonColor: '#10b981',
          cancelButtonColor: '#64748b',
          confirmButtonText: '<i class="fa fa-check-circle mr-1 me-1"></i> Yes, Confirm & Verify',
          cancelButtonText: 'Cancel'
        }).then((result) => {
          if (result.isConfirmed) {
            doAjaxSubmit();
          }
        });
      } else {
        doAjaxSubmit();
      }
    }

    function showModalValidation(msg, title = 'Missing Required Information') {
      $('#modal_validation_msg').text(msg);
      $('#modal_validation_alert').removeClass('d-none');
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'warning',
          title: title,
          text: msg,
          confirmButtonColor: '#2563eb',
          confirmButtonText: 'OK'
        });
      }
    }

    // 12. Revert Verification Handler
    function revertPaymentVerification(sno) {
      if (!sno) return;
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: 'Revert to Unverified?',
          text: 'This will mark the payment transaction as Unverified and update order status.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#f59e0b',
          cancelButtonColor: '#64748b',
          confirmButtonText: 'Yes, Revert'
        }).then((res) => {
          if (res.isConfirmed) {
            doRevertPayment(sno);
          }
        });
      } else {
        if (confirm('Revert payment to unverified?')) {
          doRevertPayment(sno);
        }
      }
    }

    function doRevertPayment(sno) {
      $.ajax({
        url: "modal/payment_verification_modal.php?action=unverify_payment",
        type: "POST",
        data: { sno: sno },
        dataType: "json",
        success: function(res) {
          if (res.status === 'success') {
            if (typeof iziToast !== 'undefined') {
              iziToast.warning({ title: 'Reverted', message: res.message, position: 'topRight' });
            } else {
              alert(res.message);
            }
            loadPayments();
          } else {
            if (typeof Swal !== 'undefined') Swal.fire('Error', res.message, 'error');
            else alert(res.message);
          }
        }
      });
    }

    // 13. Re-Verify & Sync Single Order Handler
    function reverifyAndSyncOrder(sno, psono) {
      if (!psono && !sno) return;
      const confirmMsg = psono ? `Re-Verify and sync Provisional Order #${psono}, Production Stock Order & Consumed Orders to 'Confirmed'?` : 'Re-verify and sync order status to Confirmed?';
      
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: 'Re-Verify & Sync Order?',
          text: confirmMsg,
          icon: 'question',
          showCancelButton: true,
          confirmButtonColor: '#10b981',
          cancelButtonColor: '#64748b',
          confirmButtonText: '<i class="fa fa-sync-alt mr-1 me-1"></i> Re-Verify & Sync'
        }).then((res) => {
          if (res.isConfirmed) {
            doReverifyAndSync(sno, psono);
          }
        });
      } else {
        if (confirm(confirmMsg)) {
          doReverifyAndSync(sno, psono);
        }
      }
    }

    function doReverifyAndSync(sno, psono) {
      $.ajax({
        url: "modal/payment_verification_modal.php?action=sync_order_status",
        type: "POST",
        data: { sno: sno, psono: psono, loginname: pageLoginName },
        dataType: "json",
        success: function(res) {
          if (res.status === 'success') {
            if (typeof Swal !== 'undefined') {
              Swal.fire({
                icon: 'success',
                title: 'Order Status Synced!',
                text: res.message,
                timer: 2500,
                confirmButtonColor: '#10b981'
              });
            } else if (typeof iziToast !== 'undefined') {
              iziToast.success({ title: 'Synced!', message: res.message, position: 'topRight' });
            } else {
              alert(res.message);
            }
            loadPayments();
          } else {
            if (typeof Swal !== 'undefined') Swal.fire('Error', res.message, 'error');
            else alert(res.message);
          }
        },
        error: function(xhr, status, error) {
          if (typeof Swal !== 'undefined') Swal.fire('Error', 'Server Error: ' + error, 'error');
          else alert('Server Error: ' + error);
        }
      });
    }

    // 14. Sync All Verified Orders (Batch Scan & Sync)
    function syncAllVerifiedOrders() {
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: 'Re-Verify & Sync All Orders?',
          text: 'This will scan all verified payments and ensure every linked Provisional Order and Production Stock Order is updated to "Confirmed".',
          icon: 'info',
          showCancelButton: true,
          confirmButtonColor: '#2563eb',
          cancelButtonColor: '#64748b',
          confirmButtonText: '<i class="fa fa-sync-alt mr-1 me-1"></i> Sync All Verified Orders'
        }).then((res) => {
          if (res.isConfirmed) {
            doSyncAllVerifiedOrders();
          }
        });
      } else {
        if (confirm('Sync all verified payments with provisional orders and production stock orders?')) {
          doSyncAllVerifiedOrders();
        }
      }
    }

    function doSyncAllVerifiedOrders() {
      $.ajax({
        url: "modal/payment_verification_modal.php?action=sync_all_verified_orders",
        type: "POST",
        data: { loginname: pageLoginName },
        dataType: "json",
        success: function(res) {
          if (res.status === 'success') {
            if (typeof Swal !== 'undefined') {
              Swal.fire({
                icon: 'success',
                title: 'All Orders Synced!',
                text: res.message,
                confirmButtonColor: '#10b981'
              });
            } else if (typeof iziToast !== 'undefined') {
              iziToast.success({ title: 'Synced!', message: res.message, position: 'topRight' });
            } else {
              alert(res.message);
            }
            loadPayments();
          } else {
            if (typeof Swal !== 'undefined') Swal.fire('Error', res.message, 'error');
            else alert(res.message);
          }
        },
        error: function(xhr, status, error) {
          if (typeof Swal !== 'undefined') Swal.fire('Error', 'Server Error: ' + error, 'error');
          else alert('Server Error: ' + error);
        }
      });
    }

    // Modal stacking fix: keep modal-open class and manage dual backdrop z-index when opening/closing zoom modal
    $(document).on('show.bs.modal', '#receiptModal', function () {
      setTimeout(function () {
        if ($('#paymentVerificationModal').hasClass('show') || $('#paymentVerificationModal').is(':visible')) {
          $('.modal-backdrop').not('.receipt-backdrop').last().addClass('receipt-backdrop').css('z-index', 1075);
        }
      }, 10);
    });

    $(document).on('hidden.bs.modal', '#receiptModal', function () {
      $('.receipt-backdrop').remove();
      if ($('#paymentVerificationModal').hasClass('show') || $('#paymentVerificationModal').is(':visible') || $('#utrDetailsModal').hasClass('show') || $('#utrDetailsModal').is(':visible')) {
        $('body').addClass('modal-open');
      }
    });

    /**
     * JS API: Get transaction details of a UTR number including linked order numbers and products
     * @param {string} utrNumber - The UTR / Reference number
     * @param {function} [callback] - Optional callback function(err, data)
     * @returns {jqXHR} - jQuery AJAX Promise
     */
    window.getUtrTransactionDetails = function(utrNumber, callback) {
      if (!utrNumber) {
        if (typeof callback === 'function') callback(new Error('UTR number is required'), null);
        return;
      }
      return $.ajax({
        url: "modal/payment_verification_modal.php?action=get_utr_details",
        type: "POST",
        data: { 
          utrnumber: utrNumber, 
          projectid: pageProjectId 
        },
        dataType: "json"
      }).done(function(res) {
        if (typeof callback === 'function') {
          if (res && res.status === 'success') {
            callback(null, res);
          } else {
            callback(new Error(res ? res.message : 'Failed to fetch UTR details'), res);
          }
        }
      }).fail(function(xhr, status, error) {
        if (typeof callback === 'function') {
          callback(new Error(error || 'Network error'), null);
        }
      });
    };

    /**
     * Display the UTR Details & Linked Orders Modal with full item-level breakdowns
     * @param {string} utrNumber - The UTR number to inspect
     */
    window.showUtrDetailsModal = function(utrNumber) {
      if (!utrNumber) return;

      $('#utrDetailsModalLabel').html(`UTR Details: <span class="font-monospace text-warning">#${utrNumber}</span>`);
      $('#utrModalSubHeader').text('Loading transaction and linked order data...');
      $('#utrModalSpinner').removeClass('d-none');
      $('#utrModalContent').addClass('d-none').empty();
      $('#utrModalFooterInfo').text('');

      openBootstrapModal('utrDetailsModal');

      getUtrTransactionDetails(utrNumber, function(err, res) {
        $('#utrModalSpinner').addClass('d-none');
        $('#utrModalContent').removeClass('d-none');

        if (err || !res || res.status !== 'success') {
          const errMsg = err ? err.message : (res ? res.message : 'Could not retrieve transaction details.');
          $('#utrModalContent').html(`
            <div class="alert alert-danger d-flex align-items-center gap-2">
              <i class="fa fa-exclamation-circle fs-5"></i>
              <div><strong>Error:</strong> ${errMsg}</div>
            </div>
          `);
          $('#utrModalSubHeader').text('Error fetching data');
          return;
        }

        const txns = res.transactions || [];
        const totalEntries = res.total_entries || txns.length;
        const totalAmountFormatted = res.total_amount_formatted || ('₹' + (res.total_amount || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 }));
        const ordersCount = res.orders_count || 0;

        $('#utrModalSubHeader').text(`${totalEntries} transaction entry(s) across ${ordersCount} order(s)`);
        $('#utrModalFooterInfo').html(`<strong>Total UTR Amount:</strong> <span class="text-success font-weight-bold fw-bold">${totalAmountFormatted}</span>`);

        let html = `
          <!-- Summary Metrics Header -->
          <div class="row g-3 mb-3">
            <div class="col-12 col-md-4">
              <div class="p-3 bg-white border rounded-3 shadow-2xs">
                <div class="text-muted small text-uppercase font-weight-bold fw-bold" style="font-size: 0.68rem;">Total UTR Amount</div>
                <div class="fs-4 font-weight-bold fw-bold text-success mt-0.5">${totalAmountFormatted}</div>
              </div>
            </div>
            <div class="col-12 col-md-4">
              <div class="p-3 bg-white border rounded-3 shadow-2xs">
                <div class="text-muted small text-uppercase font-weight-bold fw-bold" style="font-size: 0.68rem;">Total Linked Orders</div>
                <div class="fs-4 font-weight-bold fw-bold text-primary mt-0.5">${ordersCount} Orders</div>
              </div>
            </div>
            <div class="col-12 col-md-4">
              <div class="p-3 bg-white border rounded-3 shadow-2xs">
                <div class="text-muted small text-uppercase font-weight-bold fw-bold" style="font-size: 0.68rem;">Total Transaction Entries</div>
                <div class="fs-4 font-weight-bold fw-bold text-dark mt-0.5">${totalEntries} Entries</div>
              </div>
            </div>
          </div>
        `;

        if (txns.length === 0) {
          html += `
            <div class="p-4 text-center bg-white rounded-3 border text-muted">
              <i class="fa fa-info-circle fs-3 text-secondary mb-2"></i>
              <div>No transactions found recorded against UTR #${utrNumber}.</div>
            </div>
          `;
        } else {
          html += `<div class="d-flex flex-column gap-3">`;

          txns.forEach((txn, idx) => {
            const isVerified = (txn.verification || '').toLowerCase() === 'verified';
            const isRejected = (txn.verification || '').toLowerCase() === 'rejected';
            const verBadge = isVerified 
              ? `<span class="status-badge-verified py-0.5 px-2" style="font-size: 0.72rem;"><i class="fa fa-check-circle"></i> Verified</span>`
              : (isRejected
                  ? `<span class="status-badge-rejected py-0.5 px-2" style="font-size: 0.72rem;"><i class="fa fa-times-circle"></i> Rejected</span>`
                  : `<span class="status-badge-unverified py-0.5 px-2" style="font-size: 0.72rem;"><span class="pulse-dot"></span> Unverified</span>`);
            
            const psoNo = txn.invoiceno ? '#' + txn.invoiceno : 'Direct / General Advance';
            const orderDate = txn.formatted_pso_created_at || 'N/A';
            const txnDate = txn.formatted_paymentdate || 'N/A';
            const createdBy = txn.pso_created_by_name || 'Admin';
            const clientName = txn.clientname || 'Unknown Client';
            const mobile = txn.pso_mobileno || '';
            const location = [txn.pso_districtname, txn.pso_statename].filter(Boolean).join(', ');

            // Items breakdown
            const items = txn.items || [];
            let itemsHtml = '';
            if (items.length > 0) {
              itemsHtml = `
                <div class="table-responsive mt-2">
                  <table class="table table-sm table-bordered table-hover mb-0 bg-white" style="font-size: 0.78rem;">
                    <thead class="table-light text-muted">
                      <tr>
                        <th class="py-1">#</th>
                        <th class="py-1">Product Name</th>
                        <th class="py-1 text-center">Expected Delivery</th>
                        <th class="py-1 text-end text-right">Quantity</th>
                        <th class="py-1 text-end text-right">Rate (₹)</th>
                        <th class="py-1 text-end text-right">Amount (₹)</th>
                      </tr>
                    </thead>
                    <tbody>
                      ${items.map((it, itIdx) => `
                        <tr>
                          <td class="py-1 text-muted">${itIdx + 1}</td>
                          <td class="py-1 font-weight-bold fw-semibold text-dark">${it.product_name}</td>
                          <td class="py-1 text-center text-muted">${it.expecteddeliverydate || 'N/A'}</td>
                          <td class="py-1 text-end text-right">${parseFloat(it.quantity || 0).toLocaleString('en-IN')}</td>
                          <td class="py-1 text-end text-right">₹${parseFloat(it.rate || 0).toFixed(2)}</td>
                          <td class="py-1 text-end text-right font-weight-bold fw-bold">₹${parseFloat(it.amount || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 })}</td>
                        </tr>
                      `).join('')}
                    </tbody>
                  </table>
                </div>
              `;
            } else {
              itemsHtml = `<div class="text-muted small font-italic mt-1" style="font-size: 0.75rem;">No item-level breakdown recorded for this entry.</div>`;
            }

            // Receipt preview
            let receiptBox = '';
            if (txn.receiptpath) {
              const rUrl = pageRootUrl + txn.receiptpath;
              const isPdf = txn.receiptpath.toLowerCase().endsWith('.pdf');
              receiptBox = `
                <div class="d-flex align-items-center gap-2 mt-2 p-2 bg-light border rounded">
                  <div class="flex-shrink-0">
                    ${isPdf ? `
                      <div class="pdf-preview-box" style="width: 42px; height: 42px;" onclick="openReceiptModal('${rUrl}', 'pdf')">
                        <i class="fa fa-file-pdf fs-5"></i>
                      </div>
                    ` : `
                      <img src="${rUrl}" class="receipt-thumb" style="width: 42px; height: 42px; object-fit: cover;" onclick="openReceiptModal('${rUrl}', 'image')" alt="Proof">
                    `}
                  </div>
                  <div class="flex-grow-1 small">
                    <div class="fw-semibold text-dark">Payment Proof Attached</div>
                    <a href="${rUrl}" target="_blank" class="text-primary small text-decoration-none"><i class="fa fa-external-link-alt"></i> View Receipt</a>
                  </div>
                </div>
              `;
            }

            html += `
              <div class="card border rounded-3 bg-white shadow-2xs overflow-hidden">
                <div class="card-header bg-white py-2.5 px-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
                  <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-dark text-white px-2 py-1 font-monospace" style="font-size: 0.8rem;">
                      ${psoNo}
                    </span>
                    <span class="fw-bold font-weight-bold text-dark" style="font-size: 0.95rem;">
                      <i class="fa fa-user-circle text-primary mr-1 me-1"></i> ${clientName}
                    </span>
                    ${mobile ? `<span class="text-muted small"><i class="fa fa-phone text-success"></i> ${mobile}</span>` : ''}
                  </div>
                  <div class="d-flex align-items-center gap-2">
                    ${verBadge}
                    <button type="button" class="btn btn-outline-primary btn-sm py-0.5 px-2 rounded" style="font-size: 0.74rem;" onclick="closeBootstrapModal('utrDetailsModal'); openPaymentModal(${txn.sno}, false);" title="Edit / Verify This Payment Entry">
                      <i class="fa fa-edit"></i> Edit / Verify
                    </button>
                  </div>
                </div>

                <div class="card-body p-3">
                  <div class="row g-2 mb-2">
                    <div class="col-12 col-md-3">
                      <div class="text-muted small text-uppercase" style="font-size: 0.65rem;">Amount Received</div>
                      <div class="text-success font-weight-bold fw-bold fs-5">${txn.advanceamount_formatted || ('₹' + parseFloat(txn.advanceamount || 0).toFixed(2))}</div>
                      <div class="badge bg-light text-primary border" style="font-size: 0.68rem;">${txn.sourceofpayment || 'UPI'}</div>
                    </div>
                    <div class="col-12 col-md-3">
                      <div class="text-muted small text-uppercase" style="font-size: 0.65rem;">Payment Date</div>
                      <div class="fw-semibold text-dark small mt-0.5"><i class="fa fa-calendar-alt text-info mr-1 me-1"></i> ${txnDate}</div>
                      ${txn.receivedby ? `<div class="text-muted small" style="font-size: 0.72rem;">Recv by: ${txn.receivedby}</div>` : ''}
                    </div>
                    <div class="col-12 col-md-3">
                      <div class="text-muted small text-uppercase" style="font-size: 0.65rem;">Order Generated</div>
                      <div class="fw-semibold text-dark small mt-0.5"><i class="fa fa-user-edit text-secondary mr-1 me-1"></i> ${createdBy}</div>
                      <div class="text-muted small" style="font-size: 0.72rem;">${orderDate}</div>
                    </div>
                    <div class="col-12 col-md-3">
                      <div class="text-muted small text-uppercase" style="font-size: 0.65rem;">Verification Info</div>
                      <div class="fw-semibold text-dark small mt-0.5">
                        ${isVerified 
                            ? `<span class="text-success"><i class="fa fa-check"></i> ${txn.verificationby || 'Admin'}</span>` 
                            : (isRejected
                                ? `<span class="text-danger"><i class="fa fa-ban"></i> Rejected by ${txn.rejectedby || 'Admin'}</span>`
                                : '<span class="text-warning">Pending Verification</span>')}
                      </div>
                      ${isVerified && txn.formatted_verificationat ? `<div class="text-muted small" style="font-size: 0.72rem;">${txn.formatted_verificationat}</div>` : ''}
                      ${isRejected && txn.formatted_rejectedat ? `<div class="text-muted small" style="font-size: 0.72rem;">${txn.formatted_rejectedat}</div>` : ''}
                    </div>
                  </div>

                  ${location ? `<div class="text-muted small mb-2" style="font-size: 0.75rem;"><i class="fa fa-map-marker-alt text-danger mr-1 me-1"></i> ${location}</div>` : ''}

                  <!-- Products Breakdown -->
                  <div class="mt-2 pt-2 border-top">
                    <div class="d-flex align-items-center justify-content-between">
                      <span class="text-muted font-weight-bold fw-semibold small" style="font-size: 0.74rem;">
                        <i class="fa fa-seedling text-success mr-1 me-1"></i> Ordered Items in Order ${psoNo}
                      </span>
                    </div>
                    ${itemsHtml}
                  </div>

                  ${txn.remarks ? `
                    <div class="mt-2 p-1.5 px-2 bg-light border rounded small text-muted" style="font-size: 0.74rem;">
                      <strong>Remarks:</strong> ${txn.remarks}
                    </div>
                  ` : ''}

                  ${isRejected && txn.rejection_remark ? `
                    <div class="mt-2 p-1.5 px-2 bg-danger-subtle border border-danger-subtle rounded small text-danger" style="font-size: 0.74rem;">
                      <strong>Rejection Remark:</strong> ${txn.rejection_remark}
                    </div>
                  ` : ''}

                  ${receiptBox}
                </div>
              </div>
            `;
          });

          html += `</div>`;
        }

        $('#utrModalContent').html(html);
      });
    };
  </script>

<?php
if ($use_standalone_layout) {
  echo "</body></html>";
} else {
  if (file_exists('../footer.php')) {
    include('../footer.php');
  }
}
?>
