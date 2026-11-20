<?php
if (isset($_GET['dz'])) {
  require('../connection.php');
  require('../functions/function_master.php');
  include('functions/vendor_functions.php');
  include('functions/product_functions.php');
  
  $key = $_GET['k'] ?? '';
  $key_decoded = base64url_decode($key);
  $key_parts = explode("|", $key_decoded);
  $checkkey = base64url_decode($key_parts[count($key_parts) - 1] ?? '');
  $actkey = "";
  for ($i = 0; $i < count($key_parts) - 1; $i++) {
    $actkey = $actkey ? $actkey . "|" . $key_parts[$i] : $key_parts[$i];
  }
  if ($actkey != $checkkey) {
    echo "<div class=\"col-md-12 text-center black\"><h3>OOPS!! Something went wrong. Please Try after sometime.</h3></div>";
    die;
  }
  $loginid = $key_parts[0];
  $loginroleid = $key_parts[1];
  $companyid = $key_parts[2];
  $subcompanyid = $key_parts[3];
  $projectid = $key_parts[4];
  $logintype = $key_parts[5];
  
  $protocol = (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS'])) ? 'https' : 'http';
  $url = basename($_SERVER["PHP_SELF"]);
  $rooturl = $protocol . '://' . $_SERVER['HTTP_HOST'];
  $baseurl = $rooturl . str_replace($url, "", $_SERVER["PHP_SELF"]);
  if ($_SERVER['HTTP_HOST'] == "localhost") {
    $prefix = $rooturl . "/krf_amanaccounting/";
  } else {
    $prefix = $rooturl . "/";
  }
} else {
  require('../header.php');
  include('functions/product_functions.php');
  include('functions/tray_functions.php');
}

$companyid = $companyid ?? $_SESSION['companyid'] ?? '';
$subcompanyid = $subcompanyid ?? $_SESSION['subcompanyid'] ?? '';
$projectid = $projectid ?? $_SESSION['projectid'] ?? '';
$loginid = $loginid ?? $_SESSION['loginid'] ?? 'Admin';
if (!isset($prefix) || empty($prefix)) {
  $protocol = (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS'])) ? 'https' : 'http';
  $rooturl = $protocol . '://' . $_SERVER['HTTP_HOST'];
  $prefix = ($_SERVER['HTTP_HOST'] == "localhost") ? $rooturl . "/krf_amanaccounting/" : $rooturl . "/";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Physical Tray Verification | Karjee Farm</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">

  <!-- Fonts & Icons -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

  <!-- Safe jQuery & SweetAlert2 & HTML5-QRCode -->
  <script>
    if (typeof jQuery === 'undefined') {
      document.write('<script src="https://code.jquery.com/jquery-3.6.0.min.js"><\/script>');
    }
  </script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

  <style>
    :root {
      --primary-color: #4f46e5;
      --primary-dark: #3730a3;
      --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
      --bg-body: #f8fafc;
      --surface: #ffffff;
      --text-main: #0f172a;
      --text-muted: #64748b;
      --border-color: #e2e8f0;
      --success-color: #10b981;
      --warning-color: #f59e0b;
      --danger-color: #ef4444;
      --card-radius: 16px;
      --shadow-sm: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
      --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.07), 0 2px 4px -2px rgba(0,0,0,0.05);
      --shadow-lg: 0 10px 25px -3px rgba(0,0,0,0.08), 0 4px 6px -4px rgba(0,0,0,0.04);
    }

    body {
      font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
      background-color: var(--bg-body);
      color: var(--text-main);
      -webkit-tap-highlight-color: transparent;
      padding-bottom: 90px; /* Space for sticky bottom bar */
    }

    /* App Wrapper Container */
    .app-mobile-container {
      max-width: 980px;
      margin: 0 auto;
      padding: 12px 14px;
    }

    /* Top App Bar Header */
    .app-header-card {
      background: var(--primary-gradient);
      color: #ffffff;
      border-radius: var(--card-radius);
      padding: 16px 20px;
      box-shadow: var(--shadow-md);
      margin-bottom: 14px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 12px;
    }

    .app-header-title {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .app-header-icon {
      width: 44px;
      height: 44px;
      background: rgba(255, 255, 255, 0.18);
      backdrop-filter: blur(8px);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 22px;
    }

    .app-header-card h1 {
      font-size: 1.25rem;
      font-weight: 800;
      margin: 0;
      letter-spacing: -0.02em;
    }

    .app-header-card p {
      font-size: 0.8rem;
      margin: 0;
      opacity: 0.88;
    }

    .app-header-actions {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .btn-header-action {
      background: rgba(255, 255, 255, 0.2);
      color: #ffffff;
      border: 1px solid rgba(255, 255, 255, 0.3);
      border-radius: 20px;
      padding: 6px 14px;
      font-size: 0.8rem;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all 0.2s;
      cursor: pointer;
      text-decoration: none;
    }

    .btn-header-action:hover, .btn-header-action:active {
      background: #ffffff;
      color: var(--primary-dark);
    }

    /* Standard App Card */
    .app-card {
      background: var(--surface);
      border-radius: var(--card-radius);
      border: 1px solid var(--border-color);
      box-shadow: var(--shadow-sm);
      margin-bottom: 14px;
      overflow: hidden;
    }

    .app-card-header {
      padding: 12px 18px;
      background: #ffffff;
      border-bottom: 1px solid var(--border-color);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
    }

    .app-card-header h2 {
      font-size: 0.98rem;
      font-weight: 700;
      margin: 0;
      display: flex;
      align-items: center;
      gap: 8px;
      color: var(--text-main);
    }

    .app-card-body {
      padding: 16px 18px;
    }

    /* Target Selector Controls */
    .form-group-custom {
      margin-bottom: 12px;
    }

    .form-group-custom label {
      display: block;
      font-size: 0.82rem;
      font-weight: 700;
      color: #334155;
      margin-bottom: 6px;
    }

    .select-custom {
      width: 100%;
      height: 44px;
      border-radius: 10px;
      border: 1.5px solid #cbd5e1;
      padding: 0 12px;
      font-size: 0.9rem;
      font-weight: 600;
      background-color: #ffffff;
      color: #1e293b;
      transition: all 0.2s;
    }

    .select-custom:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
    }

    /* Collapsible Compact Target Badge */
    .target-pill-strip {
      background: #eef2ff;
      border: 1px solid #c7d2fe;
      border-radius: 12px;
      padding: 10px 14px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 8px;
    }

    .target-pill-info {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 0.88rem;
      font-weight: 700;
      color: #3730a3;
    }

    /* Live Summary Counters Strip */
    .summary-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 10px;
      margin-bottom: 14px;
    }

    .summary-tile {
      background: #ffffff;
      border-radius: 12px;
      padding: 12px 10px;
      text-align: center;
      border: 1px solid var(--border-color);
      box-shadow: var(--shadow-sm);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
    }

    .summary-tile.total {
      border-top: 4px solid #64748b;
    }

    .summary-tile.verified {
      border-top: 4px solid var(--success-color);
    }

    .summary-tile.pending {
      border-top: 4px solid var(--warning-color);
    }

    .summary-tile-label {
      font-size: 0.72rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      color: var(--text-muted);
      margin-bottom: 2px;
    }

    .summary-tile-val {
      font-size: 1.4rem;
      font-weight: 800;
      color: var(--text-main);
      line-height: 1.1;
      font-family: 'JetBrains Mono', monospace;
    }

    .summary-tile.verified .summary-tile-val {
      color: var(--success-color);
    }

    .summary-tile.pending .summary-tile-val {
      color: #d97706;
    }

    .summary-tile-sub {
      font-size: 0.7rem;
      font-weight: 600;
      color: var(--text-muted);
      margin-top: 2px;
    }

    /* Product Batch Info Tag Strip */
    .product-info-pill {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      padding: 10px 14px;
      margin-bottom: 12px;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .product-info-icon {
      width: 40px;
      height: 40px;
      min-width: 40px;
      background: #e0e7ff;
      color: var(--primary-color);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
    }

    /* Mode Tab Switcher */
    .scanner-mode-tabs {
      display: flex;
      background: #e2e8f0;
      padding: 4px;
      border-radius: 12px;
      margin-bottom: 14px;
      gap: 4px;
    }

    .scanner-mode-tab {
      flex: 1;
      text-align: center;
      padding: 8px 12px;
      border-radius: 8px;
      font-size: 0.85rem;
      font-weight: 700;
      color: #64748b;
      cursor: pointer;
      border: none;
      background: transparent;
      transition: all 0.2s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
    }

    .scanner-mode-tab.active {
      background: #ffffff;
      color: var(--primary-color);
      box-shadow: 0 2px 4px rgba(0,0,0,0.06);
    }

    /* Camera Viewport Wrapper */
    .camera-viewport-card {
      position: relative;
      background: #0f172a;
      border-radius: 14px;
      overflow: hidden;
      margin-bottom: 12px;
      min-height: 260px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    #qrCameraViewport {
      width: 100% !important;
      max-width: 100% !important;
      border: none !important;
      overflow: hidden;
    }

    #qrCameraViewport video {
      width: 100% !important;
      height: auto !important;
      max-height: 380px !important;
      object-fit: cover !important;
      border-radius: 14px;
    }

    /* Laser Scanning Line Animation */
    .camera-laser {
      position: absolute;
      top: 15%;
      left: 10%;
      width: 80%;
      height: 3px;
      background: #10b981;
      box-shadow: 0 0 12px #10b981, 0 0 20px #10b981;
      border-radius: 2px;
      pointer-events: none;
      z-index: 10;
      animation: laserScan 2s infinite ease-in-out;
    }

    @keyframes laserScan {
      0% { top: 15%; opacity: 0.9; }
      50% { top: 85%; opacity: 0.9; }
      100% { top: 15%; opacity: 0.9; }
    }

    /* Reticle Corners */
    .reticle-corner {
      position: absolute;
      width: 24px;
      height: 24px;
      border-color: #10b981;
      border-style: solid;
      pointer-events: none;
      z-index: 9;
    }

    .reticle-tl { top: 15%; left: 12%; border-width: 3px 0 0 3px; border-top-left-radius: 6px; }
    .reticle-tr { top: 15%; right: 12%; border-width: 3px 3px 0 0; border-top-right-radius: 6px; }
    .reticle-bl { bottom: 15%; left: 12%; border-width: 0 0 3px 3px; border-bottom-left-radius: 6px; }
    .reticle-br { bottom: 15%; right: 12%; border-width: 0 3px 3px 0; border-bottom-right-radius: 6px; }

    /* Camera Action Controls Bar */
    .camera-ctrl-bar {
      display: flex;
      align-items: center;
      justify-content: center;
      flex-wrap: wrap;
      gap: 8px;
      margin-bottom: 10px;
    }

    .btn-cam-action {
      border: none;
      border-radius: 20px;
      padding: 8px 16px;
      font-size: 0.85rem;
      font-weight: 700;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all 0.2s;
    }

    .btn-cam-start {
      background: var(--primary-gradient);
      color: #ffffff;
    }

    .btn-cam-stop {
      background: #ef4444;
      color: #ffffff;
    }

    .btn-cam-sub {
      background: #f1f5f9;
      color: #334155;
      border: 1px solid #cbd5e1;
    }

    .btn-cam-sub:hover, .btn-cam-sub:active {
      background: #e2e8f0;
    }

    /* Auto-Verify Switch Toggle */
    .auto-verify-toggle-wrap {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      background: #f8fafc;
      border: 1.5px solid #cbd5e1;
      border-radius: 20px;
      padding: 5px 12px;
      font-size: 0.82rem;
      font-weight: 700;
      color: #1e293b;
      user-select: none;
      cursor: pointer;
      line-height: 1;
      vertical-align: middle;
      transition: all 0.2s ease;
      position: relative;
    }

    .auto-verify-toggle-wrap:hover {
      background: #f1f5f9;
      border-color: #94a3b8;
    }

    .auto-verify-toggle-wrap input[type="checkbox"] {
      position: absolute;
      opacity: 0;
      width: 0;
      height: 0;
      pointer-events: none;
      margin: 0;
    }

    .custom-check-icon {
      width: 16px;
      height: 16px;
      min-width: 16px;
      max-width: 16px;
      min-height: 16px;
      max-height: 16px;
      border-radius: 4px;
      border: 1.5px solid #cbd5e1;
      background: #ffffff;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 10px;
      color: transparent;
      transition: all 0.2s ease;
      flex-shrink: 0;
      box-sizing: border-box;
    }

    .auto-verify-toggle-wrap input[type="checkbox"]:checked + .custom-check-icon {
      background: var(--primary-color, #4f46e5);
      border-color: var(--primary-color, #4f46e5);
      color: #ffffff;
    }

    .auto-verify-toggle-wrap input[type="checkbox"]:focus + .custom-check-icon {
      box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.25);
    }

    /* Barcode Gun / Manual Search */
    .manual-input-group {
      display: flex;
      gap: 6px;
    }

    .manual-input-box {
      flex: 1;
      height: 48px;
      border-radius: 12px;
      border: 2px solid #cbd5e1;
      padding: 0 14px;
      font-size: 1rem;
      font-weight: 600;
      font-family: 'JetBrains Mono', monospace;
      outline: none;
    }

    .manual-input-box:focus {
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
    }

    .btn-manual-add {
      height: 48px;
      padding: 0 20px;
      border-radius: 12px;
      background: var(--primary-gradient);
      color: #ffffff;
      border: none;
      font-weight: 700;
      font-size: 0.9rem;
      display: flex;
      align-items: center;
      gap: 6px;
      cursor: pointer;
    }

    /* Status Badges */
    .badge-status {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 0.78rem;
      font-weight: 700;
      white-space: nowrap;
    }

    .badge-status-pending {
      background: #fef3c7;
      color: #b45309;
      border: 1px solid #fde68a;
    }

    .badge-status-verifying {
      background: #e0e7ff;
      color: #4338ca;
      border: 1px solid #c7d2fe;
    }

    .badge-status-verified {
      background: #dcfce7;
      color: #15803d;
      border: 1px solid #bbf7d0;
    }

    .badge-status-failed {
      background: #fee2e2;
      color: #b91c1c;
      border: 1px solid #fecaca;
    }

    .spin-icon {
      animation: spin 1s infinite linear;
    }

    @keyframes spin {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }

    /* Grid Table Styles with Smooth Horizontal Scroll */
    .table-responsive,
    .grid-table-responsive {
      width: 100%;
      overflow-x: auto !important;
      -webkit-overflow-scrolling: touch;
      border-radius: 0 0 var(--card-radius) var(--card-radius);
    }

    /* Custom Sleek Horizontal Scrollbar */
    .table-responsive::-webkit-scrollbar,
    .grid-table-responsive::-webkit-scrollbar {
      height: 6px;
    }
    .table-responsive::-webkit-scrollbar-track,
    .grid-table-responsive::-webkit-scrollbar-track {
      background: #f1f5f9;
      border-radius: 4px;
    }
    .table-responsive::-webkit-scrollbar-thumb,
    .grid-table-responsive::-webkit-scrollbar-thumb {
      background: #cbd5e1;
      border-radius: 4px;
    }
    .table-responsive::-webkit-scrollbar-thumb:hover,
    .grid-table-responsive::-webkit-scrollbar-thumb:hover {
      background: #94a3b8;
    }

    .table-container-app {
      width: 100%;
      min-width: 760px; /* Keeps each row in one line with horizontal scroll on mobile */
      border-collapse: separate;
      border-spacing: 0;
    }

    .table-container-app th {
      background: #f8fafc;
      color: #475569;
      font-size: 0.76rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      padding: 11px 14px;
      border-bottom: 2px solid #e2e8f0;
      white-space: nowrap;
    }

    .table-container-app td {
      padding: 10px 14px;
      border-bottom: 1px solid #f1f5f9;
      font-size: 0.85rem;
      vertical-align: middle;
      white-space: nowrap; /* Each row in one single line */
    }

    .table-container-app tbody tr:hover {
      background-color: #f8fafc;
    }

    .barcode-cell-text {
      font-family: 'JetBrains Mono', monospace;
      font-weight: 700;
      color: var(--text-main);
      font-size: 0.92rem;
      white-space: nowrap;
    }

    @media (max-width: 768px) {
      .app-mobile-container {
        padding: 8px 10px;
      }
      .table-container-app {
        min-width: 740px !important;
      }
    }

    /* Sticky Bottom Mobile Bar */
    .mobile-bottom-dock {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      background: rgba(255, 255, 255, 0.92);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border-top: 1px solid rgba(226, 232, 240, 0.8);
      padding: 10px 16px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      z-index: 1000;
      box-shadow: 0 -4px 15px rgba(0, 0, 0, 0.06);
    }

    .bottom-dock-inner {
      max-width: 980px;
      margin: 0 auto;
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
    }

    .btn-submit-pending {
      flex: 1;
      max-width: 400px;
      background: var(--primary-gradient);
      color: #ffffff;
      border: none;
      border-radius: 12px;
      padding: 12px 20px;
      font-size: 0.92rem;
      font-weight: 800;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3);
      cursor: pointer;
      transition: all 0.2s;
    }

    .btn-submit-pending:disabled {
      background: #94a3b8;
      box-shadow: none;
      cursor: not-allowed;
      opacity: 0.7;
    }

    /* Toast styling */
    .scanner-toast-msg {
      padding: 6px 12px;
      border-radius: 8px;
      font-size: 0.82rem;
      font-weight: 600;
      margin-top: 8px;
      text-align: center;
    }
  </style>
</head>
<body>

<div class="app-mobile-container" id="trayVerificationApp"
     data-companyid="<?php echo htmlspecialchars($companyid); ?>"
     data-subcompanyid="<?php echo htmlspecialchars($subcompanyid); ?>"
     data-projectid="<?php echo htmlspecialchars($projectid); ?>"
     data-loginid="<?php echo htmlspecialchars($loginid); ?>"
     data-prefix="<?php echo htmlspecialchars($prefix); ?>">

  <!-- 1. Top App Header -->
  <div class="app-header-card">
    <div class="app-header-title">
      <div class="app-header-icon">
        <i class="fa fa-qrcode"></i>
      </div>
      <div>
        <h1>Physical Tray Verification</h1>
        <p>Real-time Mobile QR Scanner & Instant Bed Verification</p>
      </div>
    </div>
    <div class="app-header-actions">
      <button type="button" class="btn-header-action" id="btnAppReset" onclick="resetEntireSession();">
        <i class="fa fa-rotate-left"></i> <span>Reset</span>
      </button>
    </div>
  </div>

  <!-- 2. Target Bed & Location Selector Card -->
  <div class="app-card" id="selectionSectionCard">
    <div class="app-card-header">
      <h2><i class="fa fa-map-marker-alt text-primary"></i> 1. Select Target Bed</h2>
      <button type="button" class="btn btn-sm btn-link text-decoration-none text-muted p-0" id="btnToggleSelectionCard" style="display:none;" onclick="toggleSelectionCollapse();">
        <i class="fa fa-pen me-1"></i> Edit
      </button>
    </div>
    <div class="app-card-body" id="selectionCardBody">
      <div class="row g-2">
        <div class="col-12 col-md-4">
          <div class="form-group-custom">
            <label><i class="fa fa-building text-muted me-1"></i> Location <span class="text-danger">*</span></label>
            <select id="locationSelect" class="select-custom">
              <option value="" selected disabled>-- Loading Locations... --</option>
            </select>
          </div>
        </div>
        <div class="col-12 col-md-4">
          <div class="form-group-custom">
            <label><i class="fa fa-warehouse text-muted me-1"></i> Polyhouse <span class="text-danger">*</span></label>
            <select id="polyhouseSelect" class="select-custom" disabled>
              <option value="" selected disabled>-- Select Location First --</option>
            </select>
          </div>
        </div>
        <div class="col-12 col-md-4">
          <div class="form-group-custom">
            <label><i class="fa fa-layer-group text-muted me-1"></i> Target Bed <span class="text-danger">*</span></label>
            <select id="bedSelect" class="select-custom" disabled>
              <option value="" selected disabled>-- Select Polyhouse First --</option>
            </select>
          </div>
        </div>
      </div>
    </div>
    <!-- Compact Target Pill (Visible when destination selected) -->
    <div id="targetSummaryPill" class="px-3 pb-3" style="display:none;">
      <div class="target-pill-strip">
        <div class="target-pill-info">
          <i class="fa fa-check-circle text-success"></i>
          <span>Target Bed:</span>
          <span class="badge bg-primary text-white" id="pillTargetBedDisplay">-</span>
          <span class="text-muted fw-normal" style="font-size:0.8rem;" id="pillTargetPolyDisplay">(-)</span>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill py-1 px-3" onclick="toggleSelectionCollapse(true);">
          <i class="fa fa-sliders me-1"></i> Change Bed
        </button>
      </div>
    </div>
  </div>

  <!-- 3. Bed Live Summary Strip -->
  <div id="bedSummarySection" style="display: none;">
    <!-- Live Tiles -->
    <div class="summary-grid">
      <div class="summary-tile total">
        <div class="summary-tile-label">Total Trays</div>
        <div class="summary-tile-val" id="summaryTotalTrays">0</div>
        <div class="summary-tile-sub">In Target Bed</div>
      </div>
      <div class="summary-tile verified">
        <div class="summary-tile-label">Verified</div>
        <div class="summary-tile-val" id="summaryVerifiedTrays">0</div>
        <div class="summary-tile-sub" id="summaryVerifiedPercent">(0%)</div>
      </div>
      <div class="summary-tile pending">
        <div class="summary-tile-label">Pending</div>
        <div class="summary-tile-val" id="summaryPendingTrays">0</div>
        <div class="summary-tile-sub" id="summaryPendingPercent">(0%)</div>
      </div>
    </div>

    <!-- Product Details Strip -->
    <div class="product-info-pill" id="productBatchInfoStrip">
      <div class="product-info-icon">
        <i class="fa fa-seedling"></i>
      </div>
      <div class="flex-grow-1" id="productBatchDetailsContainer">
        <div class="fw-bold text-dark" style="font-size: 0.95rem;">Loading Bed Details...</div>
      </div>
      <button class="btn btn-sm btn-outline-secondary rounded-circle" id="btnRefreshBedData" title="Refresh Live Data" style="width:34px; height:34px;">
        <i class="fa fa-sync-alt"></i>
      </button>
    </div>
  </div>

  <!-- 4. QR Code & Barcode Scanner Card -->
  <div class="app-card" id="scannerCard" style="display: none;">
    <div class="app-card-header">
      <h2><i class="fa fa-camera text-primary"></i> 2. Scan Trays</h2>
      <div class="d-flex align-items-center gap-2">
        <!-- Auto-Verify Switch -->
        <label class="auto-verify-toggle-wrap" title="Auto-verify tray as soon as it is scanned">
          <input type="checkbox" id="chkAutoVerify" checked>
          <span class="custom-check-icon"><i class="fa fa-check"></i></span>
          <span><i class="fa fa-bolt text-warning me-1"></i>Auto-Verify</span>
        </label>
      </div>
    </div>
    <div class="app-card-body">

      <!-- Scanner Mode Tabs -->
      <div class="scanner-mode-tabs">
        <button type="button" class="scanner-mode-tab active" id="tabModeCamera" onclick="switchScannerMode('camera');">
          <i class="fa fa-camera"></i> <span>Mobile Camera</span>
        </button>
        <button type="button" class="scanner-mode-tab" id="tabModeManual" onclick="switchScannerMode('manual');">
          <i class="fa fa-barcode"></i> <span>Barcode Gun / Manual</span>
        </button>
      </div>

      <!-- Mode A: Camera Scanner Viewport -->
      <div id="modeCameraContainer">
        <div class="camera-viewport-card" id="cameraViewportWrap">
          <div id="qrCameraViewport"></div>
          <div class="camera-laser" id="scannerLaserLine" style="display: none;"></div>
          <div class="reticle-corner reticle-tl"></div>
          <div class="reticle-corner reticle-tr"></div>
          <div class="reticle-corner reticle-bl"></div>
          <div class="reticle-corner reticle-br"></div>
        </div>

        <!-- Camera Controls -->
        <div class="camera-ctrl-bar">
          <button type="button" class="btn-cam-action btn-cam-start" id="btnStartCamera" onclick="startCameraScanner();">
            <i class="fa fa-play"></i> <span>Start Camera</span>
          </button>
          <button type="button" class="btn-cam-action btn-cam-stop" id="btnStopCamera" style="display: none;" onclick="stopCameraScanner();">
            <i class="fa fa-stop"></i> <span>Stop Camera</span>
          </button>
          <button type="button" class="btn-cam-action btn-cam-sub" id="btnFlipCamera" onclick="flipCamera();">
            <i class="fa fa-camera-rotate"></i> <span>Flip Camera</span>
          </button>
          <button type="button" class="btn-cam-action btn-cam-sub" id="btnToggleTorch" style="display: none;" onclick="toggleCameraTorch();">
            <i class="fa fa-lightbulb"></i> <span>Torch</span>
          </button>
        </div>

        <div id="cameraStatusMsg" class="text-center text-muted small mt-1">
          <i class="fa fa-info-circle text-primary me-1"></i> Point your mobile camera steadily at the Tray QR code.
        </div>
      </div>

      <!-- Mode B: Manual Barcode Gun Input -->
      <div id="modeManualContainer" style="display: none;">
        <form onsubmit="event.preventDefault(); submitManualBarcode();">
          <div class="manual-input-group">
            <input type="text" id="manualBarcodeInput" class="manual-input-box" placeholder="Scan or type Tray Barcode..." autocomplete="off">
            <button type="submit" class="btn-manual-add">
              <i class="fa fa-plus"></i> <span>Add</span>
            </button>
          </div>
        </form>
        <div class="text-muted small mt-2">
          <i class="fa fa-keyboard text-primary me-1"></i> Press <b>Enter</b> after scanning or typing barcode.
        </div>
      </div>

      <div id="scannerFeedbackMsg" class="scanner-toast-msg" style="display: none;"></div>

    </div>
  </div>

  <!-- 5. Scanned Trays Table Grid Card -->
  <div class="app-card" id="scannedGridCard" style="display: none;">
    <div class="app-card-header">
      <h2>
        <i class="fa fa-list-check text-primary"></i> 3. Verification Activity Grid
      </h2>
      <div class="d-flex align-items-center gap-2">
        <span class="badge bg-secondary rounded-pill px-2.5 py-1.5" id="badgeScannedTotal">0 Scanned</span>
        <span class="badge bg-success rounded-pill px-2.5 py-1.5" id="badgeVerifiedSession">0 Verified</span>
      </div>
    </div>
    <div class="app-card-body p-0">
      <div class="table-responsive">
        <table class="table-container-app" id="scannedTraysTable">
          <thead>
            <tr>
              <th style="width: 50px;">#</th>
              <th>Tray Barcode</th>
              <th>Product & Batch</th>
              <th>Current Bed</th>
              <th>Target Bed</th>
              <th>Status</th>
              <th style="width: 80px;" class="text-center">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr id="emptyTraysRow">
              <td colspan="7" class="text-center py-5 text-muted">
                <i class="fa fa-qrcode fa-2x mb-2 text-secondary opacity-50 d-block"></i>
                No trays scanned yet. Aim camera at a QR code to begin.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<!-- 6. Sticky Mobile Bottom Dock -->
<div class="mobile-bottom-dock" id="mobileBottomDock" style="display: none;">
  <div class="bottom-dock-inner">
    <div>
      <div style="font-size: 0.75rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Pending Queue</div>
      <div class="fw-bold" style="font-size: 1rem; color: #b45309;">
        <span id="dockPendingCount">0</span> Trays Pending
      </div>
    </div>
    <button type="button" class="btn-submit-pending" id="btnSubmitPendingTrays" onclick="submitPendingTraysManually();" disabled>
      <i class="fa fa-circle-check"></i>
      <span id="btnSubmitPendingText">Verify Pending (0)</span>
    </button>
  </div>
</div>

<script>
$(document).ready(function() {
  const companyid = $('#trayVerificationApp').data('companyid');
  const subcompanyid = $('#trayVerificationApp').data('subcompanyid');
  const projectid = $('#trayVerificationApp').data('projectid');
  const loginid = $('#trayVerificationApp').data('loginid');
  const prefix = $('#trayVerificationApp').data('prefix');

  const backendUrl = prefix + 'krf-orderandsales/physical_tray_verification_backend.php';

  // State Management
  let html5QrcodeScanner = null;
  let isCameraScanning = false;
  let currentFacingMode = "environment";
  let torchTrack = null;
  let isTorchOn = false;

  let scannedTraysMap = new Map(); // barcode => { rowNum, barcode, productname, batchno, currentLoc, targetBed, status: 'pending'|'verifying'|'verified'|'failed', verifiedAt }
  let verificationQueue = [];
  let isQueueWorkerRunning = false;

  let dbTotalTrays = 0;
  let dbVerifiedTrays = 0;
  let sessionVerifiedCount = 0;

  let lastScannedBarcode = '';
  let lastScannedTimestamp = 0;
  const SCAN_COOLDOWN_MS = 1400;

  // 1. Load Locations on Init
  function loadLocations() {
    $.ajax({
      url: backendUrl + '?action=getLocations',
      type: 'POST',
      data: { companyid, subcompanyid, projectid },
      dataType: 'json',
      success: function(res) {
        if (res.status === 'success') {
          let options = '<option value="" selected disabled>-- Choose Location --</option>';
          res.data.forEach(function(loc) {
            options += `<option value="${loc.LocationId}">${loc.LocationName}</option>`;
          });
          $('#locationSelect').html(options);
        } else {
          showAppAlert('Error!', 'Failed to load locations: ' + res.message, 'error');
        }
      },
      error: function() {
        showAppAlert('Error!', 'Network error loading locations.', 'error');
      }
    });
  }

  loadLocations();

  // On Location Change
  $('#locationSelect').change(function() {
    const locId = $(this).val();
    $('#polyhouseSelect').prop('disabled', true).html('<option value="" selected disabled>Loading polyhouses...</option>');
    $('#bedSelect').prop('disabled', true).html('<option value="" selected disabled>-- Select Polyhouse First --</option>');
    hideWorkflowSections();

    $.ajax({
      url: backendUrl + '?action=getPolyhouses',
      type: 'POST',
      data: { projectid, locationid: locId },
      dataType: 'json',
      success: function(res) {
        if (res.status === 'success') {
          let options = '<option value="" selected disabled>-- Choose Polyhouse --</option>';
          if (res.data && res.data.length > 0) {
            res.data.forEach(function(ph) {
              options += `<option value="${ph.polyhouseid}">${ph.polyhousename}</option>`;
            });
            $('#polyhouseSelect').prop('disabled', false).html(options);
          } else {
            $('#polyhouseSelect').html('<option value="" selected disabled>No polyhouses found</option>');
          }
        }
      }
    });
  });

  // On Polyhouse Change
  $('#polyhouseSelect').change(function() {
    const polyId = $(this).val();
    $('#bedSelect').prop('disabled', true).html('<option value="" selected disabled>Loading beds...</option>');
    hideWorkflowSections();

    $.ajax({
      url: backendUrl + '?action=getBeds',
      type: 'POST',
      data: { projectid, polyhouseid: polyId },
      dataType: 'json',
      success: function(res) {
        if (res.status === 'success') {
          let options = '<option value="" selected disabled>-- Choose Bed --</option>';
          if (res.data && res.data.length > 0) {
            res.data.forEach(function(bed) {
              options += `<option value="${bed.bedname}">${bed.bedname}</option>`;
            });
            $('#bedSelect').prop('disabled', false).html(options);
          } else {
            $('#bedSelect').html('<option value="" selected disabled>No beds available</option>');
          }
        }
      }
    });
  });

  // On Bed Change
  $('#bedSelect').change(function() {
    const targetBed = $(this).val();
    const polyName = $("#polyhouseSelect option:selected").text();

    $('#pillTargetBedDisplay').text(targetBed);
    $('#pillTargetPolyDisplay').text(`(${polyName})`);
    $('#targetSummaryPill').show();
    $('#btnToggleSelectionCard').show();

    // Auto-collapse selection on mobile to save vertical space
    if ($(window).width() <= 768) {
      $('#selectionCardBody').slideUp(200);
    }

    fetchBedSummary();
    showWorkflowSections();
  });

  window.toggleSelectionCollapse = function(forceOpen = false) {
    if (forceOpen || $('#selectionCardBody').is(':hidden')) {
      $('#selectionCardBody').slideDown(200);
    } else {
      $('#selectionCardBody').slideUp(200);
    }
  };

  // Fetch Bed Live Summary
  function fetchBedSummary() {
    const locationId = $('#locationSelect').val();
    const polyId = $('#polyhouseSelect').val();
    const bedName = $('#bedSelect').val();
    if (!polyId || !bedName) return;

    $('#productBatchDetailsContainer').html('<div class="text-muted"><i class="fa fa-spinner fa-spin me-1"></i> Refreshing live bed stats...</div>');

    $.ajax({
      url: backendUrl + '?action=getBedSummary',
      type: 'POST',
      data: { projectid, polyhouseid: polyId, bed_no: bedName, locationid: locationId },
      dataType: 'json',
      success: function(res) {
        if (res.status === 'success') {
          const container = $('#productBatchDetailsContainer');
          container.empty();

          if (res.data.groups && res.data.groups.length > 0) {
            let groupsHtml = '';
            res.data.groups.forEach(function(g) {
              groupsHtml += `
                <div class="mb-1">
                  <div class="fw-bold text-dark" style="font-size:0.95rem;">
                    ${g.productname}
                    <span class="badge bg-primary text-white ms-1" style="font-size:0.72rem;">SO: ${g.stockorder_no}</span>
                    <span class="badge bg-secondary text-white ms-1" style="font-size:0.72rem;">Batch: ${g.batchno}</span>
                  </div>
                  <div class="text-muted" style="font-size:0.78rem;">
                    <span><i class="fa fa-calendar-day me-1"></i>Sown: ${g.sowingdate}</span>
                    <span class="ms-2"><i class="fa fa-boxes-stacked me-1"></i>Bed Total: <b>${g.cnt} Trays</b></span>
                  </div>
                </div>
              `;
            });
            container.html(groupsHtml);
          } else {
            container.html(`
              <div class="fw-bold text-dark" style="font-size:0.95rem;">No Active Trays in Polyhouse Status</div>
              <div class="text-muted" style="font-size:0.78rem;">This bed currently has 0 registered trays.</div>
            `);
          }

          dbTotalTrays = parseInt(res.data.total_trays) || 0;
          dbVerifiedTrays = parseInt(res.data.total_verified) || 0;
          updateLiveCounters();
        }
      }
    });
  }

  $('#btnRefreshBedData').click(function() {
    fetchBedSummary();
    showToastFeedback('Bed data refreshed', 'success');
  });

  function showWorkflowSections() {
    $('#bedSummarySection').slideDown(200);
    $('#scannerCard').slideDown(200);
    $('#scannedGridCard').slideDown(200);
    $('#mobileBottomDock').slideDown(200);
  }

  function hideWorkflowSections() {
    $('#targetSummaryPill').hide();
    $('#btnToggleSelectionCard').hide();
    $('#selectionCardBody').show();
    $('#bedSummarySection').slideUp(200);
    $('#scannerCard').slideUp(200);
    $('#scannedGridCard').slideUp(200);
    $('#mobileBottomDock').slideUp(200);
    stopCameraScanner();
  }

  // Scanner Mode Switcher
  window.switchScannerMode = function(mode) {
    if (mode === 'camera') {
      $('#tabModeCamera').addClass('active');
      $('#tabModeManual').removeClass('active');
      $('#modeCameraContainer').show();
      $('#modeManualContainer').hide();
      startCameraScanner();
    } else {
      $('#tabModeManual').addClass('active');
      $('#tabModeCamera').removeClass('active');
      $('#modeManualContainer').show();
      $('#modeCameraContainer').hide();
      stopCameraScanner();
      setTimeout(() => $('#manualBarcodeInput').focus(), 150);
    }
  };

  // 2. Camera Controls & Html5Qrcode
  window.startCameraScanner = async function() {
    if (isCameraScanning) return;

    if (typeof Html5Qrcode === 'undefined') {
      showAppAlert('Error', 'QR Scanner library not loaded. Please refresh.', 'error');
      return;
    }

    $('#cameraStatusMsg').html('<i class="fa fa-spinner fa-spin text-primary me-1"></i> Accessing camera...');
    $('#btnStartCamera').hide();
    $('#btnStopCamera').show();
    $('#scannerLaserLine').show();

    try {
      if (html5QrcodeScanner) {
        try {
          if (html5QrcodeScanner.isScanning) {
            await html5QrcodeScanner.stop();
          }
          html5QrcodeScanner.clear();
        } catch (e) {}
        html5QrcodeScanner = null;
      }

      html5QrcodeScanner = new Html5Qrcode("qrCameraViewport");

      const scanConfig = {
        fps: 12,
        qrbox: (viewfinderWidth, viewfinderHeight) => {
          const w = Math.min(280, Math.max(180, Math.floor(viewfinderWidth * 0.72)));
          const h = Math.min(200, Math.max(130, Math.floor(viewfinderHeight * 0.58)));
          return { width: w, height: h };
        },
        aspectRatio: 1.333333
      };

      await html5QrcodeScanner.start(
        { facingMode: currentFacingMode },
        scanConfig,
        onQrCodeScanned,
        () => {} // Silent frame errors
      );

      isCameraScanning = true;
      $('#cameraStatusMsg').html('<span class="text-success"><i class="fa fa-check-circle me-1"></i> Scanner ready. Point at Tray QR code.</span>');

      // Detect hardware torch support
      setTimeout(() => {
        try {
          const video = document.querySelector("#qrCameraViewport video");
          if (video && video.srcObject) {
            const tracks = video.srcObject.getVideoTracks();
            if (tracks.length > 0) {
              torchTrack = tracks[0];
              const caps = (typeof torchTrack.getCapabilities === 'function') ? torchTrack.getCapabilities() : {};
              if (caps && caps.torch) {
                $('#btnToggleTorch').show();
              } else {
                $('#btnToggleTorch').hide();
              }
            }
          }
        } catch (e) {}
      }, 500);

    } catch (err) {
      isCameraScanning = false;
      $('#scannerLaserLine').hide();
      $('#btnStartCamera').show();
      $('#btnStopCamera').hide();
      $('#cameraStatusMsg').html('<span class="text-danger"><i class="fa fa-exclamation-triangle me-1"></i> Camera error: ' + (err.message || err) + '</span>');
    }
  };

  window.stopCameraScanner = async function() {
    if (!html5QrcodeScanner) return;
    try {
      if (html5QrcodeScanner.isScanning) {
        await html5QrcodeScanner.stop();
      }
      html5QrcodeScanner.clear();
    } catch (e) {}
    html5QrcodeScanner = null;
    isCameraScanning = false;
    $('#scannerLaserLine').hide();

    if (torchTrack && isTorchOn) {
      try { torchTrack.applyConstraints({ advanced: [{ torch: false }] }); } catch (e) {}
      torchTrack = null;
    }
    isTorchOn = false;
    $('#btnToggleTorch').hide();

    $('#btnStartCamera').show();
    $('#btnStopCamera').hide();
    $('#cameraStatusMsg').html('<span class="text-muted"><i class="fa fa-video-slash me-1"></i> Camera stopped.</span>');
  };

  window.flipCamera = async function() {
    currentFacingMode = (currentFacingMode === "environment") ? "user" : "environment";
    if (isCameraScanning) {
      await stopCameraScanner();
      await startCameraScanner();
    }
  };

  window.toggleCameraTorch = function() {
    if (!torchTrack) return;
    try {
      isTorchOn = !isTorchOn;
      torchTrack.applyConstraints({ advanced: [{ torch: isTorchOn }] });
      if (isTorchOn) {
        $('#btnToggleTorch').addClass('btn-warning').removeClass('btn-cam-sub');
      } else {
        $('#btnToggleTorch').removeClass('btn-warning').addClass('btn-cam-sub');
      }
    } catch (e) {
      console.warn('Torch toggle error:', e);
    }
  };

  // Helper to clean QR payload
  function cleanQrPayload(str) {
    if (!str) return '';
    str = String(str).trim();
    try {
      if ((str.startsWith('{') && str.endsWith('}')) || (str.startsWith('[') && str.endsWith(']'))) {
        const obj = JSON.parse(str);
        if (typeof obj === 'object' && obj !== null) {
          return obj.barcode || obj.bedqrcode || obj.stockorder_no || obj.code || str;
        }
      }
    } catch (e) {}

    if (str.indexOf('http://') === 0 || str.indexOf('https://') === 0 || str.indexOf('://') !== -1) {
      try {
        const url = new URL(str, window.location.origin);
        return url.searchParams.get('barcode') || url.searchParams.get('code') || str;
      } catch (e) {}
    }

    if (str.includes('\n')) {
      const lines = str.split(/\r?\n/).map(l => l.trim()).filter(Boolean);
      for (const line of lines) {
        const m = line.match(/^(?:tray(?:\s*(?:no|barcode|id))?|barcode)\s*[:=]\s*(.+)$/i);
        if (m && m[1]) return m[1].trim();
      }
      if (lines.length > 0) return lines[0];
    }

    return str.replace(/[\x00-\x1F\x7F\xC2\xA0]/g, '').trim();
  }

  // QR Scan Handler
  function onQrCodeScanned(decodedText) {
    const cleanCode = cleanQrPayload(decodedText);
    if (!cleanCode) return;

    const now = Date.now();
    // Anti-burst protection
    if (cleanCode === lastScannedBarcode && (now - lastScannedTimestamp < SCAN_COOLDOWN_MS * 2)) {
      return;
    }
    if (now - lastScannedTimestamp < SCAN_COOLDOWN_MS) {
      return;
    }

    lastScannedBarcode = cleanCode;
    lastScannedTimestamp = now;

    playScanBeep();
    triggerHaptic('success');
    handleScannedBarcode(cleanCode);
  }

  // Manual input form submission
  window.submitManualBarcode = function() {
    const rawCode = $('#manualBarcodeInput').val().trim();
    if (!rawCode) return;
    const cleanCode = cleanQrPayload(rawCode);
    $('#manualBarcodeInput').val('');
    handleScannedBarcode(cleanCode);
  };

  // Main Barcode Processing Pipeline
  function handleScannedBarcode(barcode) {
    const targetBed = ($('#bedSelect').val() || '').trim();
    const targetPolyId = $('#polyhouseSelect').val();

    if (!targetBed || !targetPolyId) {
      showAppAlert('Select Target Bed', 'Please select Location, Polyhouse and Bed before scanning trays.', 'warning');
      return;
    }

    // 1. Check if already in current session's scanned list
    if (scannedTraysMap.has(barcode)) {
      const existing = scannedTraysMap.get(barcode);
      highlightRow(barcode);
      triggerHaptic('duplicate');
      showToastFeedback(`Tray "${barcode}" is already in grid (${existing.status.toUpperCase()}).`, 'warning');
      return;
    }

    // Temporary row insertion while fetching details
    showToastFeedback(`Fetching details for Tray: ${barcode}...`, 'info');

    $.ajax({
      url: backendUrl + '?action=getTrayDetails',
      type: 'POST',
      data: { projectid, barcode: barcode },
      dataType: 'json',
      success: function(res) {
        let pName = 'N/A';
        let batch = 'N/A';
        let currentLoc = 'Not Set';
        let origBed = '';
        let isAlreadyVerifiedInDb = false;

        if (res.status === 'success') {
          pName = res.data.productname || 'N/A';
          batch = res.data.batchno || 'N/A';
          currentLoc = (res.data.polyhousename || 'No PH') + ' / ' + (res.data.bed_no || 'No Bed');
          origBed = (res.data.bed_no || '').trim();

          const bedverifystatus = (res.data.bed_verify || '').trim();
          if (bedverifystatus !== '' && bedverifystatus !== '0000-00-00 00:00:00') {
            isAlreadyVerifiedInDb = true;
          }
        } else if (res.status === 'not_found_moved') {
          pName = res.data ? res.data.productname : 'Not Found in Moved';
          batch = res.data ? res.data.batchno : 'N/A';
          currentLoc = 'Pending Move';
        }

        // If tray is already verified in DB on THIS target bed
        if (isAlreadyVerifiedInDb && origBed === targetBed) {
          triggerHaptic('duplicate');
          showToastFeedback(`Tray "${barcode}" is ALREADY verified on Bed: ${targetBed}`, 'info');
          // Add as already verified
          addTrayToGrid(barcode, pName, batch, currentLoc, targetBed, origBed, 'verified', res.data.bed_verify || 'Verified');
          return;
        }

        // Otherwise append as PENDING and queue for auto-verification if enabled
        addTrayToGrid(barcode, pName, batch, currentLoc, targetBed, origBed, 'pending');

        if ($('#chkAutoVerify').is(':checked')) {
          enqueueTrayForVerification(barcode);
        }
      },
      error: function() {
        showToastFeedback('Network error checking barcode. Added as Pending.', 'warning');
        addTrayToGrid(barcode, 'N/A', 'N/A', 'Unknown', targetBed, '', 'pending');
        if ($('#chkAutoVerify').is(':checked')) {
          enqueueTrayForVerification(barcode);
        }
      }
    });
  }

  // Add Tray to Grid Table
  function addTrayToGrid(barcode, productname, batchno, currentLoc, targetBed, origBed, status = 'pending', verifiedAt = '') {
    $('#emptyTraysRow').remove();

    const rowNum = scannedTraysMap.size + 1;
    scannedTraysMap.set(barcode, {
      rowNum,
      barcode,
      productname,
      batchno,
      currentLoc,
      targetBed,
      origBed,
      status,
      verifiedAt
    });

    let statusBadgeHtml = getStatusBadgeHtml(status, verifiedAt);

    const trHtml = `
      <tr id="row_${barcode}" class="tray-row-item" data-barcode="${barcode}" data-status="${status}" data-orig-bed="${origBed}">
        <td class="row-num-cell" data-label="#">${rowNum}</td>
        <td class="row-barcode-cell" data-label="Barcode">
          <div class="barcode-cell-text">${barcode}</div>
        </td>
        <td data-label="Product">
          <div class="fw-bold text-dark">${productname}</div>
          <div class="small text-muted">Batch: ${batchno}</div>
        </td>
        <td data-label="Current Bed" class="text-muted">${currentLoc}</td>
        <td data-label="Target Bed" class="fw-semibold text-primary">${targetBed}</td>
        <td data-label="Status" class="status-cell">
          ${statusBadgeHtml}
        </td>
        <td data-label="Action" class="text-center action-cell">
          <button type="button" class="btn btn-sm btn-outline-danger btn-remove-tray py-1 px-2" data-barcode="${barcode}" title="Remove Tray">
            <i class="fa fa-trash"></i>
          </button>
        </td>
      </tr>
    `;

    $('#scannedTraysTable tbody').prepend(trHtml);
    highlightRow(barcode);
    updateLiveCounters();
    updatePendingBadgeCount();
  }

  // Render Status Badge
  function getStatusBadgeHtml(status, timeInfo = '') {
    if (status === 'verified') {
      return `<span class="badge-status badge-status-verified"><i class="fa fa-check-circle"></i> Verified ${timeInfo ? '<small class="opacity-75">' + timeInfo + '</small>' : ''}</span>`;
    } else if (status === 'verifying') {
      return `<span class="badge-status badge-status-verifying"><i class="fa fa-circle-notch spin-icon"></i> Verifying...</span>`;
    } else if (status === 'failed') {
      return `<span class="badge-status badge-status-failed"><i class="fa fa-circle-exclamation"></i> Failed</span>`;
    } else {
      return `<span class="badge-status badge-status-pending"><i class="fa fa-clock"></i> Pending</span>`;
    }
  }

  // Update specific row status in HTML
  function updateRowStatusUI(barcode, status, timeOrMsg = '') {
    const row = $(`#row_${barcode}`);
    if (!row.length) return;

    row.attr('data-status', status);
    const badgeHtml = getStatusBadgeHtml(status, timeOrMsg);
    row.find('.status-cell').html(badgeHtml);

    if (scannedTraysMap.has(barcode)) {
      const obj = scannedTraysMap.get(barcode);
      obj.status = status;
      obj.verifiedAt = timeOrMsg;
      scannedTraysMap.set(barcode, obj);
    }

    // If failed, show a retry button in action cell
    if (status === 'failed') {
      row.find('.action-cell').html(`
        <button type="button" class="btn btn-sm btn-outline-primary btn-retry-tray py-1 px-2 me-1" data-barcode="${barcode}" title="Retry">
          <i class="fa fa-rotate-right"></i>
        </button>
        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-tray py-1 px-2" data-barcode="${barcode}" title="Remove">
          <i class="fa fa-trash"></i>
        </button>
      `);
    } else if (status === 'verified') {
      row.find('.action-cell').html(`
        <span class="text-success"><i class="fa fa-check"></i></span>
      `);
    }

    updateLiveCounters();
    updatePendingBadgeCount();
  }

  // 3. Sequential Verification Worker Queue
  function enqueueTrayForVerification(barcode) {
    if (!verificationQueue.includes(barcode)) {
      verificationQueue.push(barcode);
    }
    updatePendingBadgeCount();
    processVerificationQueue();
  }

  function processVerificationQueue() {
    if (isQueueWorkerRunning || verificationQueue.length === 0) {
      updatePendingBadgeCount();
      return;
    }

    const targetPolyId = $('#polyhouseSelect').val();
    const targetBed = $('#bedSelect').val();
    if (!targetPolyId || !targetBed) return;

    const barcode = verificationQueue.shift();
    isQueueWorkerRunning = true;
    updatePendingBadgeCount();

    // Mark row as Verifying...
    updateRowStatusUI(barcode, 'verifying');

    $.ajax({
      url: backendUrl + '?action=updateTrays',
      type: 'POST',
      data: {
        companyid,
        subcompanyid,
        projectid,
        loginid,
        barcodes: [barcode],
        target_polyhouseid: targetPolyId,
        target_bed_no: targetBed
      },
      dataType: 'json',
      success: function(res) {
        if (res.status === 'success') {
          const verifiedTime = res.verified_at || new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
          updateRowStatusUI(barcode, 'verified', verifiedTime);
          sessionVerifiedCount++;
          playSuccessChime();
          triggerHaptic('success');
        } else {
          updateRowStatusUI(barcode, 'failed', res.message || 'Error');
          triggerHaptic('warning');
          showToastFeedback(`Tray ${barcode} failed: ${res.message}`, 'warning');
        }
      },
      error: function() {
        updateRowStatusUI(barcode, 'failed', 'Network Error');
        triggerHaptic('warning');
        showToastFeedback(`Network error verifying Tray ${barcode}`, 'warning');
      },
      complete: function() {
        isQueueWorkerRunning = false;
        updatePendingBadgeCount();
        // Continue to next pending tray with a small delay for smooth animation
        setTimeout(processVerificationQueue, 220);
      }
    });
  }

  // 4. Manual Verification Trigger
  window.submitPendingTraysManually = function() {
    const targetBed = ($('#bedSelect').val() || '').trim();
    if (!targetBed) {
      showAppAlert('Select Target Bed', 'Please choose a target polyhouse and bed first.', 'warning');
      return;
    }

    const pendingList = [];
    $('#scannedTraysTable tbody tr.tray-row-item').each(function() {
      const st = $(this).attr('data-status');
      const bc = $(this).attr('data-barcode');
      if (st === 'pending' || st === 'failed') {
        if (!verificationQueue.includes(bc)) {
          pendingList.push(bc);
        }
      }
    });

    if (pendingList.length === 0) {
      if (isQueueWorkerRunning) {
        showToastFeedback('Verification is already in progress...', 'info');
      } else {
        showToastFeedback('No pending trays found to verify.', 'info');
      }
      return;
    }

    pendingList.forEach(bc => verificationQueue.push(bc));
    updatePendingBadgeCount();
    processVerificationQueue();
  };

  // Retry button click handler
  $(document).on('click', '.btn-retry-tray', function() {
    const barcode = $(this).data('barcode');
    if (barcode) {
      updateRowStatusUI(barcode, 'pending');
      enqueueTrayForVerification(barcode);
    }
  });

  // Remove tray button click handler
  $(document).on('click', '.btn-remove-tray', function() {
    const barcode = $(this).data('barcode');
    verificationQueue = verificationQueue.filter(b => b !== barcode);
    scannedTraysMap.delete(barcode);
    $(`#row_${barcode}`).fadeOut(200, function() {
      $(this).remove();
      if (scannedTraysMap.size === 0) {
        $('#scannedTraysTable tbody').html(`
          <tr id="emptyTraysRow">
            <td colspan="7" class="text-center py-5 text-muted">
              <i class="fa fa-qrcode fa-2x mb-2 text-secondary opacity-50 d-block"></i>
              No trays scanned yet. Aim camera at a QR code to begin.
            </td>
          </tr>
        `);
      }
      updateLiveCounters();
      updatePendingBadgeCount();
    });
  });

  // Highlight specific row
  function highlightRow(barcode) {
    const row = $(`#row_${barcode}`);
    if (row.length) {
      row.css({ 'background-color': '#fef9c3', 'transition': 'background-color 0.4s' });
      setTimeout(() => {
        row.css({ 'background-color': '', 'transition': 'background-color 0.8s' });
      }, 1200);
    }
  }

  // Update Live Counter Numbers
  function updateLiveCounters() {
    let sessionPending = 0;
    let sessionVerified = 0;

    scannedTraysMap.forEach((val) => {
      if (val.status === 'verified') {
        sessionVerified++;
      } else if (val.status === 'pending' || val.status === 'failed') {
        sessionPending++;
      }
    });

    $('#badgeScannedTotal').text(scannedTraysMap.size + ' Scanned');
    $('#badgeVerifiedSession').text(sessionVerified + ' Verified');

    // Total and Verified calculations
    const displayTotal = Math.max(dbTotalTrays, dbTotalTrays + sessionVerified);
    const displayVerified = dbVerifiedTrays + sessionVerified;
    const displayPending = Math.max(0, displayTotal - displayVerified);

    $('#summaryTotalTrays').text(displayTotal);
    $('#summaryVerifiedTrays').text(displayVerified);
    $('#summaryPendingTrays').text(displayPending);

    const verifiedPercent = displayTotal > 0 ? Math.round((displayVerified / displayTotal) * 100) : 0;
    const pendingPercent = displayTotal > 0 ? Math.round((displayPending / displayTotal) * 100) : 0;

    $('#summaryVerifiedPercent').text(`(${verifiedPercent}%)`);
    $('#summaryPendingPercent').text(`(${pendingPercent}%)`);
  }

  // Update pending badge & dock button
  function updatePendingBadgeCount() {
    let pendingCount = 0;
    scannedTraysMap.forEach((val) => {
      if (val.status === 'pending' || val.status === 'failed') {
        pendingCount++;
      }
    });

    $('#dockPendingCount').text(pendingCount);
    $('#btnSubmitPendingText').text(`Verify Pending (${pendingCount})`);

    if (pendingCount > 0 && !isQueueWorkerRunning) {
      $('#btnSubmitPendingTrays').prop('disabled', false);
    } else if (isQueueWorkerRunning) {
      $('#btnSubmitPendingTrays').prop('disabled', true);
      $('#btnSubmitPendingText').text(`Verifying in progress...`);
    } else {
      $('#btnSubmitPendingTrays').prop('disabled', true);
    }
  }

  // Reset entire session
  window.resetEntireSession = function() {
    Swal.fire({
      title: 'Reset Verification Session?',
      text: 'This will clear all currently scanned items from the screen and refresh the page.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#4f46e5',
      cancelButtonColor: '#94a3b8',
      confirmButtonText: 'Yes, Reset'
    }).then((res) => {
      if (res.isConfirmed) {
        window.location.reload();
      }
    });
  };

  // Toast Helper
  function showToastFeedback(msg, type = 'info') {
    const el = $('#scannerFeedbackMsg');
    el.removeClass('text-danger text-success text-warning text-info');
    if (type === 'warning') el.addClass('text-warning');
    else if (type === 'success') el.addClass('text-success');
    else if (type === 'error') el.addClass('text-danger');
    else el.addClass('text-info');

    el.html(`<i class="fa fa-info-circle me-1"></i> ${msg}`).fadeIn(150).delay(2500).fadeOut(300);
  }

  function showAppAlert(title, text, icon = 'info') {
    Swal.fire({
      title: title,
      text: text,
      icon: icon,
      confirmButtonColor: '#4f46e5'
    });
  }

  // Audio & Haptic Feedback
  function triggerHaptic(type = 'success') {
    if (navigator.vibrate) {
      try {
        if (type === 'success') navigator.vibrate([50]);
        else if (type === 'duplicate') navigator.vibrate([40, 50, 40]);
        else if (type === 'warning') navigator.vibrate([100, 50, 100]);
      } catch (e) {}
    }
  }

  function playScanBeep() {
    try {
      const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
      const osc = audioCtx.createOscillator();
      const gain = audioCtx.createGain();
      osc.connect(gain);
      gain.connect(audioCtx.destination);
      osc.type = "sine";
      osc.frequency.setValueAtTime(880, audioCtx.currentTime);
      gain.gain.setValueAtTime(0.18, audioCtx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.00001, audioCtx.currentTime + 0.12);
      osc.start();
      osc.stop(audioCtx.currentTime + 0.12);
    } catch (e) {}
  }

  function playSuccessChime() {
    try {
      const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
      const now = audioCtx.currentTime;
      const osc = audioCtx.createOscillator();
      const gain = audioCtx.createGain();
      osc.connect(gain);
      gain.connect(audioCtx.destination);
      osc.type = "sine";
      osc.frequency.setValueAtTime(587.33, now); // D5
      osc.frequency.setValueAtTime(880, now + 0.08); // A5
      gain.gain.setValueAtTime(0.15, now);
      gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.25);
      osc.start(now);
      osc.stop(now + 0.25);
    } catch (e) {}
  }

});
</script>

</body>
</html>
