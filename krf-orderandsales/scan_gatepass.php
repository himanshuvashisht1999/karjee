<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Kolkata');

// -------------------------------------------------------------
// Check Session & Auth
// -------------------------------------------------------------
if (!isset($_SESSION['sessionid']) || $_SESSION['sessionid'] !== session_id()) {
    $protocol = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https' : 'http';
    $rooturl = $protocol . '://' . $_SERVER['HTTP_HOST'];
    $prefix = ($_SERVER['HTTP_HOST'] == "localhost") ? $rooturl . "/krf_amanaccounting/" : $rooturl . "/";
    $requested_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    echo "<script>
        try {
            if (" . json_encode($requested_url) . " && !" . json_encode($requested_url) . ".includes('loginCell') && !" . json_encode($requested_url) . ".includes('logout')) {
                localStorage.setItem('last_active_module_url', " . json_encode($requested_url) . ");
            }
        } catch(e){}
        top.location.href = '" . $prefix . "loginCell/index.php';
    </script>";
    exit;
}

if (isset($_GET['dz'])) {
    require('../connection.php');
} else {
    require('../header.php');
}

$loginusername = $_SESSION['username'] ?? 'User';
$loginroleid   = isset($loginroleid) ? (int)$loginroleid : (int)($_SESSION['loginroleid'] ?? 4);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Gate Pass QR Scanner & Verification Hub | Karjee Farm</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

  <!-- Google Fonts (Inter & JetBrains Mono) & Font Awesome 6 Icons -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  
  <!-- jQuery, SweetAlert2 & HTML5 QR Scanner -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

  <style>
    :root {
      --gp-font-sans: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
      --gp-font-mono: 'JetBrains Mono', SFMono-Regular, Menlo, Monaco, Consolas, monospace;
      --gp-primary: #2563eb;
      --gp-primary-dark: #1d4ed8;
      --gp-primary-light: #eff6ff;
      --gp-emerald: #059669;
      --gp-emerald-dark: #047857;
      --gp-emerald-light: #ecfdf5;
      --gp-amber: #d97706;
      --gp-amber-light: #fffbeb;
      --gp-slate-50: #f8fafc;
      --gp-slate-100: #f1f5f9;
      --gp-slate-200: #e2e8f0;
      --gp-slate-300: #cbd5e1;
      --gp-slate-500: #64748b;
      --gp-slate-700: #334155;
      --gp-slate-800: #1e293b;
      --gp-slate-900: #0f172a;
    }

    body {
      font-family: var(--gp-font-sans) !important;
      background-color: #f8fafc !important;
      color: var(--gp-slate-900) !important;
      margin: 0;
      padding: 0;
    }

    /* Force FontAwesome 6 Icons to render properly even when FA4 is included in header */
    .fa, .fas, .far, .fa-solid, .fa-regular, .fa-brands, .fab {
      font-family: "Font Awesome 6 Free", "FontAwesome" !important;
      font-weight: 900 !important;
      -webkit-font-smoothing: antialiased;
      display: inline-block;
      font-style: normal;
      font-variant: normal;
      text-rendering: auto;
      line-height: 1;
    }
    .fa-regular, .far {
      font-weight: 400 !important;
    }

    .gp-scan-wrapper {
      max-width: 1400px;
      margin: 1.25rem auto 3.5rem auto;
      padding: 0 1.25rem;
    }

    /* Top Executive Banner */
    .gp-top-hero {
      background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #064e3b 100%);
      border-radius: 1.25rem;
      padding: 1.6rem 2.25rem;
      color: #ffffff;
      box-shadow: 0 12px 30px -8px rgba(15, 23, 42, 0.25);
      margin-bottom: 1.5rem;
      position: relative;
      overflow: hidden;
      border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .gp-top-hero::after {
      content: '';
      position: absolute;
      right: -30px;
      top: -30px;
      width: 240px;
      height: 240px;
      background: radial-gradient(circle, rgba(16, 185, 129, 0.2) 0%, transparent 70%);
      border-radius: 50%;
      pointer-events: none;
    }

    /* Cards */
    .gp-card {
      background: #ffffff;
      border: 1px solid var(--gp-slate-200);
      border-radius: 1rem;
      box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
      margin-bottom: 1.5rem;
      overflow: hidden;
      transition: box-shadow 0.2s ease, border-color 0.2s ease;
    }
    .gp-card:hover {
      box-shadow: 0 8px 22px rgba(15, 23, 42, 0.07);
    }

    .gp-card-header {
      background: #ffffff;
      padding: 1rem 1.35rem;
      border-bottom: 1px solid var(--gp-slate-200);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    /* Scanner Viewport */
    .scanner-box-wrapper {
      position: relative;
      background: #090d16;
      border-radius: 0.85rem;
      overflow: hidden;
      min-height: 290px;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 2px dashed #334155;
      box-shadow: inset 0 2px 8px rgba(0,0,0,0.4);
    }
    #qr-reader {
      width: 100% !important;
      border: none !important;
    }
    #qr-reader video {
      border-radius: 0.85rem;
      object-fit: cover !important;
    }
    .scanner-laser-line {
      position: absolute;
      top: 0;
      left: 8%;
      right: 8%;
      height: 3px;
      background: linear-gradient(90deg, transparent, #10b981, #34d399, transparent);
      box-shadow: 0 0 16px #10b981;
      animation: scanLaserAnim 2s infinite ease-in-out;
      z-index: 10;
      pointer-events: none;
      display: none;
    }
    @keyframes scanLaserAnim {
      0% { top: 12%; opacity: 0.2; }
      50% { top: 85%; opacity: 1; }
      100% { top: 12%; opacity: 0.2; }
    }

    /* Status Badges */
    .gp-status-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.45rem;
      padding: 0.4rem 0.9rem;
      border-radius: 9999px;
      font-size: 0.78rem;
      font-weight: 700;
      letter-spacing: 0.04em;
      text-transform: uppercase;
      white-space: nowrap;
    }
    .status-badge-cleared {
      background-color: #dcfce7 !important;
      color: #15803d !important;
      border: 1px solid #86efac !important;
    }
    .status-badge-pending {
      background-color: #fef3c7 !important;
      color: #b45309 !important;
      border: 1px solid #fcd34d !important;
    }
    .badge.bg-success, span.badge.bg-success {
      background-color: #059669 !important;
      color: #ffffff !important;
    }
    .badge.bg-primary, span.badge.bg-primary {
      background-color: #2563eb !important;
      color: #ffffff !important;
    }

    /* KPI Highlights */
    .gp-kpi-block {
      background: #ffffff;
      border: 1px solid var(--gp-slate-200);
      border-radius: 0.85rem;
      padding: 1.1rem 1.25rem;
      display: flex;
      align-items: center;
      gap: 1rem;
      height: 100%;
      box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .gp-kpi-icon {
      width: 52px;
      height: 52px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.4rem;
      flex-shrink: 0;
    }
    .kpi-icon-plants {
      background-color: #ecfdf5 !important;
      color: #059669 !important;
      border: 1px solid #a7f3d0 !important;
    }
    .kpi-icon-trays {
      background-color: #eff6ff !important;
      color: #2563eb !important;
      border: 1px solid #bfdbfe !important;
    }
    .kpi-icon-vehicle {
      background-color: #fffbeb !important;
      color: #d97706 !important;
      border: 1px solid #fde68a !important;
    }

    .kpi-title {
      font-size: 0.72rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: #64748b;
      margin-bottom: 0.25rem;
    }
    .kpi-stat-number {
      font-size: 1.45rem;
      font-weight: 800;
      color: #0f172a;
      line-height: 1.15;
    }

    /* Freight Highlights Container */
    .freight-overview-card {
      background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
      border: 1.5px solid #a7f3d0;
      border-radius: 0.85rem;
      padding: 1.25rem 1.5rem;
    }
    .financial-pill-badge {
      background-color: #dcfce7 !important;
      color: #15803d !important;
      border: 1px solid #86efac !important;
      font-weight: 700;
      font-size: 0.75rem;
      padding: 0.35rem 0.75rem;
      border-radius: 9999px;
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
    }

    /* Clean Modern Table */
    .gp-modern-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      font-size: 0.86rem;
    }
    .gp-modern-table th {
      background: #f1f5f9;
      color: #475569;
      font-family: var(--gp-font-sans) !important;
      font-size: 0.74rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      padding: 0.85rem 1rem;
      border-bottom: 2px solid #e2e8f0;
      white-space: nowrap;
    }
    .gp-modern-table td {
      padding: 0.85rem 1rem;
      border-bottom: 1px solid #f1f5f9;
      vertical-align: middle;
      font-family: var(--gp-font-sans);
    }
    .gp-modern-table tr:hover td {
      background-color: #f8fafc;
    }
    .gp-modern-table tr:last-child td {
      border-bottom: none;
    }

    /* Pulse dot */
    .live-dot {
      width: 9px;
      height: 9px;
      border-radius: 50%;
      background-color: #10b981;
      display: inline-block;
      box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
      animation: pulseAnim 2s infinite;
    }
    @keyframes pulseAnim {
      0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
      70% { transform: scale(1); box-shadow: 0 0 0 7px rgba(16, 185, 129, 0); }
      100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }

    .font-mono {
      font-family: var(--gp-font-mono) !important;
    }

    /* Smooth Touch-Scrolling Tables */
    .table-responsive {
      -webkit-overflow-scrolling: touch;
      scrollbar-width: thin;
      scrollbar-color: #cbd5e1 transparent;
    }
    .table-responsive::-webkit-scrollbar {
      height: 6px;
    }
    .table-responsive::-webkit-scrollbar-thumb {
      background: #cbd5e1;
      border-radius: 4px;
    }

    /* Mobile Responsive Optimizations */
    @media (max-width: 991.98px) {
      .gp-scan-wrapper {
        margin: 0.75rem auto 2.5rem auto;
        padding: 0 0.75rem;
      }
      .gp-top-hero {
        padding: 1.25rem 1.15rem;
        border-radius: 1rem;
        margin-bottom: 1rem;
      }
      .gp-top-hero h2 {
        font-size: 1.35rem;
      }
      .hero-actions {
        width: 100%;
        display: flex;
        gap: 0.5rem;
      }
      .hero-actions .btn {
        flex: 1 1 50%;
        justify-content: center;
        text-align: center;
        padding: 0.55rem 0.65rem;
        font-size: 0.85rem;
      }
      .scanner-box-wrapper {
        min-height: 240px;
      }
    }

    @media (max-width: 575.98px) {
      .gp-top-hero h2 {
        font-size: 1.18rem;
      }
      .gp-top-hero p {
        font-size: 0.8rem !important;
      }
      .scanner-box-wrapper {
        min-height: 210px;
      }
      
      /* Compact 3-in-a-row KPI blocks on small phones */
      .kpi-row-compact .col-4 {
        padding-left: 3px;
        padding-right: 3px;
      }
      .gp-kpi-block {
        padding: 0.65rem 0.35rem;
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 0.35rem;
        border-radius: 0.65rem;
      }
      .gp-kpi-icon {
        width: 36px;
        height: 36px;
        font-size: 1rem;
        border-radius: 8px;
      }
      .kpi-title {
        font-size: 0.6rem;
        letter-spacing: 0.02em;
        margin-bottom: 0.1rem;
      }
      .kpi-stat-number {
        font-size: 1.05rem;
      }

      /* Touch-friendly Action Buttons */
      .res-action-buttons {
        width: 100%;
        display: flex;
        gap: 0.5rem;
        margin-top: 0.35rem;
      }
      .res-action-buttons .btn {
        flex: 1 1 50%;
        font-size: 0.8rem;
        padding: 0.45rem 0.5rem;
      }
      
      /* Full-width responsive approval button */
      #btnApproveGateExit {
        width: 100% !important;
        padding: 0.75rem 1rem !important;
        font-size: 1rem !important;
      }
      
      /* Minimum 16px input font to prevent iOS Safari auto-zoom */
      input.form-control, textarea.form-control {
        font-size: 16px !important;
      }
    }
  </style>
</head>
<body>

<div class="gp-scan-wrapper">

  <!-- Top Hero Header -->
  <div class="gp-top-hero d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1.5">
        <span class="badge" style="background: rgba(255,255,255,0.15); color: #ffffff; border: 1px solid rgba(255,255,255,0.3); font-weight: 600; padding: 0.4rem 0.75rem; border-radius: 6px;">
          <i class="fa-solid fa-shield-halved me-1.5 text-success"></i> Security & Dispatch Clearance
        </span>
        <span class="live-dot"></span>
        <small class="text-white-50 fw-semibold">Scanner Active</small>
      </div>
      <h2 class="fw-bold mb-1" style="font-size: 1.65rem; letter-spacing: -0.01em;">
        Gate Pass QR Verification Hub
      </h2>
      <p class="mb-0 text-white-50" style="font-size: 0.88rem;">
        Scan top QR on printed Gate Pass or enter Gate Pass / PSO number to verify vehicle, items, tray cavity, and freight charges.
      </p>
    </div>
    <div class="d-flex flex-wrap gap-2 hero-actions">
      <a href="gatepass_generator.php" class="btn btn-light fw-bold px-3 py-2 text-dark shadow-sm">
        <i class="fa-solid fa-arrow-left me-1.5 text-primary"></i> Gate Pass Generator
      </a>
      <button class="btn btn-outline-light fw-bold px-3 py-2" onclick="refreshRecentList()">
        <i class="fa-solid fa-rotate me-1.5"></i> Refresh History
      </button>
    </div>
  </div>

  <div class="row g-4">
    
    <!-- LEFT PANEL: SCANNER & SEARCH CONTROLS -->
    <div class="col-lg-5 col-xl-4">
      
      <!-- Scanner Card -->
      <div class="gp-card p-3 p-md-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h6 class="fw-bold mb-0 text-slate-800 d-flex align-items-center gap-2">
            <i class="fa-solid fa-qrcode text-primary"></i> Camera QR Scanner
          </h6>
          <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" id="autoBeepSwitch" checked>
            <label class="form-check-label text-muted small fw-semibold" for="autoBeepSwitch">Audio Beep</label>
          </div>
        </div>

        <!-- Camera Box -->
        <div class="scanner-box-wrapper mb-3">
          <div id="qr-reader"></div>
          <div class="scanner-laser-line" id="scannerLaser"></div>
          <div id="scannerPlaceholder" class="text-center p-4 text-white-50">
            <div class="mb-2">
              <span class="d-inline-flex align-items-center justify-content-center bg-dark rounded-circle p-3 text-slate-400 border border-secondary" style="width: 60px; height: 60px;">
                <i class="fa-solid fa-camera fa-2x"></i>
              </span>
            </div>
            <div class="fw-bold text-white mb-1">Camera Scanner Standby</div>
            <small class="text-slate-400">Click button below to scan with phone or webcam</small>
          </div>
        </div>

        <!-- Scanner Control Buttons -->
        <div class="d-flex gap-2 mb-3">
          <button id="btnStartCamera" class="btn btn-primary flex-grow-1 fw-bold py-2 shadow-sm" onclick="startCameraScanner()">
            <i class="fa-solid fa-video me-1.5"></i> Start Camera Scan
          </button>
          <button id="btnStopCamera" class="btn btn-outline-danger fw-bold py-2 d-none flex-grow-1" onclick="stopCameraScanner()">
            <i class="fa-solid fa-stop me-1.5"></i> Stop Camera
          </button>
          <button id="btnSwitchCamera" class="btn btn-outline-secondary fw-bold py-2 d-none px-3" onclick="switchCamera()" title="Switch Camera">
            <i class="fa-solid fa-camera-rotate"></i>
          </button>
        </div>

        <hr class="my-3 text-slate-200">

        <!-- Manual Lookup Form -->
        <div>
          <label class="form-label fw-bold text-slate-700 small mb-1.5 d-flex align-items-center justify-content-between">
            <span><i class="fa-solid fa-barcode me-1.5 text-primary"></i> Manual Input / Barcode Gun</span>
            <span class="text-muted" style="font-size: 0.72rem;">Press Enter to search</span>
          </label>
          <div class="input-group mb-2">
            <input type="text" id="manualScanInput" class="form-control form-control-lg font-mono fw-bold" 
                   placeholder="GP-XXXX / KF-PS-XXXX" autofocus>
            <button class="btn btn-success fw-bold px-3" type="button" onclick="handleManualSearch()">
              <i class="fa-solid fa-magnifying-glass me-1"></i> Find
            </button>
          </div>
          <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted" style="font-size: 0.75rem;">Works with barcode guns & pasted QR data</small>
            <button class="btn btn-link btn-sm p-0 text-danger text-decoration-none fw-semibold" onclick="clearResults()" style="font-size: 0.78rem;">
              <i class="fa-solid fa-circle-xmark me-1"></i> Reset Form
            </button>
          </div>
        </div>

      </div>

      <!-- Quick Tips Box -->
      <div class="gp-card p-3 bg-light border-0">
        <div class="d-flex gap-2.5 align-items-start">
          <i class="fa-solid fa-shield-halved text-primary mt-1 fs-6"></i>
          <small class="text-slate-600" style="font-size: 0.8rem; line-height: 1.45;">
            <strong>Gate Security Rule:</strong> Ensure vehicle number matches physical vehicle and driver phone number is verified before pressing <b>Mark Gate Exit & Verified</b>.
          </small>
        </div>
      </div>

    </div>

    <!-- RIGHT PANEL: VERIFIED GATE PASS DETAILS -->
    <div class="col-lg-7 col-xl-8">
      
      <!-- Empty State View -->
      <div id="emptyResultPlaceholder" class="gp-card p-5 text-center text-muted">
        <div class="mb-3">
          <span class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle" style="width: 76px; height: 76px; background-color: #eff6ff !important; color: #2563eb !important;">
            <i class="fa-solid fa-qrcode fa-2x"></i>
          </span>
        </div>
        <h4 class="fw-bold text-slate-800 mb-1" style="font-size: 1.25rem;">Awaiting Gate Pass Scan</h4>
        <p class="text-slate-500 mb-0" style="max-width: 440px; margin: 0 auto; font-size: 0.88rem;">
          Point the camera to the top QR Code on the Gate Pass PDF, or type the Gate Pass No. / PSO No. to review plants, tray cavity, vehicle, and freight charges.
        </p>
      </div>

      <!-- Result Card (Populated dynamically upon scan) -->
      <div id="resultCard" class="gp-card d-none">
        
        <!-- Header Banner with Clearance Status -->
        <div class="p-3 p-md-4 border-bottom bg-slate-50 d-flex flex-wrap justify-content-between align-items-center gap-2">
          <div>
            <div class="d-flex align-items-center gap-2 mb-1.5">
              <span id="resGateStatusBadge" class="gp-status-badge status-badge-pending">
                <i class="fa-solid fa-clock"></i> PENDING GATE EXIT
              </span>
              <span id="resGatepassNoBadge" class="badge font-mono px-2.5 py-1.5 text-white" style="font-size: 0.85rem; background-color: #0f172a !important;"></span>
            </div>
            <div class="text-muted small" style="font-size: 0.82rem;">
              Order: <strong id="resPsoNo" class="text-slate-900 font-mono"></strong>
              <span id="resInvoiceContainer" class="ms-1.5 d-none">&bull; Invoice: <strong id="resInvoiceNo" class="text-primary font-mono"></strong></span>
              &bull; Generated: <span id="resCreatedAt" class="text-slate-700 fw-semibold"></span>
            </div>
          </div>
          <div class="d-flex flex-wrap gap-2 res-action-buttons">
            <button id="resBtnPdf" class="btn btn-outline-primary btn-sm fw-bold px-3 py-1.5 shadow-sm">
              <i class="fa-solid fa-file-pdf me-1.5 text-danger"></i> View PDF
            </button>
            <button class="btn btn-outline-secondary btn-sm fw-bold px-3 py-1.5" onclick="openEditGateModal()">
              <i class="fa-solid fa-pen-to-square me-1.5 text-primary"></i> Edit Transport / Freight
            </button>
          </div>
        </div>

        <!-- Details Body -->
        <div class="p-3 p-md-4">
          
          <!-- KPI Summary Row -->
          <div class="row g-2 g-md-3 mb-4 kpi-row-compact">
            <div class="col-4">
              <div class="gp-kpi-block">
                <div class="gp-kpi-icon kpi-icon-plants">
                  <i class="fa-solid fa-seedling"></i>
                </div>
                <div>
                  <div class="kpi-title">Total Plants</div>
                  <div class="kpi-stat-number text-success" id="resTotalPlants">0</div>
                </div>
              </div>
            </div>
            <div class="col-4">
              <div class="gp-kpi-block">
                <div class="gp-kpi-icon kpi-icon-trays">
                  <i class="fa-solid fa-layer-group"></i>
                </div>
                <div>
                  <div class="kpi-title">Total Trays</div>
                  <div class="kpi-stat-number text-primary" id="resTotalTrays">0</div>
                </div>
              </div>
            </div>
            <div class="col-4">
              <div class="gp-kpi-block">
                <div class="gp-kpi-icon kpi-icon-vehicle">
                  <i class="fa-solid fa-truck-fast"></i>
                </div>
                <div>
                  <div class="kpi-title">Vehicle</div>
                  <div class="kpi-stat-number font-mono text-dark" style="font-size: 1.15rem;" id="resVehicleNoTop">-</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Customer & Vehicle Columns -->
          <div class="row g-3 mb-4">
            <!-- Customer -->
            <div class="col-md-6">
              <div class="p-3 bg-slate-50 border rounded-3 h-100">
                <div class="text-uppercase text-muted fw-bold small mb-2 d-flex align-items-center" style="font-size: 0.72rem; letter-spacing: 0.04em;">
                  <i class="fa-solid fa-circle-user text-primary me-2" style="font-size: 0.95rem;"></i> Customer & Delivery Destination
                </div>
                <div class="fw-bold text-slate-900 fs-6" id="resClientName">-</div>
                <div class="text-muted small mt-1.5" id="resClientPhoneContainer">
                  <i class="fa-solid fa-phone me-1.5 text-success"></i> <span id="resClientPhone" class="fw-semibold text-slate-700">-</span>
                </div>
                <div class="text-muted small mt-1">
                  <i class="fa-solid fa-location-dot me-1.5 text-danger"></i> <span id="resClientAddress">-</span>
                </div>
              </div>
            </div>

            <!-- Transport -->
            <div class="col-md-6">
              <div class="p-3 bg-slate-50 border rounded-3 h-100">
                <div class="text-uppercase text-muted fw-bold small mb-2 d-flex align-items-center" style="font-size: 0.72rem; letter-spacing: 0.04em;">
                  <i class="fa-solid fa-truck-moving text-primary me-2" style="font-size: 0.95rem;"></i> Transport & Driver Information
                </div>
                <div class="fw-bold text-slate-900">
                  <span id="resVehicleNo" class="badge font-mono px-2.5 py-1 text-white" style="background-color: #2563eb !important;"></span>
                  <span id="resTransporter" class="ms-1.5 text-slate-700 font-mono small"></span>
                </div>
                <div class="text-muted small mt-2" id="resDriverLine">
                  <strong>Driver:</strong> <span id="resDriverInfoLine" class="text-slate-800 fw-semibold">-</span>
                </div>
                <div class="text-muted small mt-1" id="resRemarksContainer">
                  <strong>Remarks:</strong> <span id="resRemarks" class="fst-italic text-slate-600">-</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Freight & Financial Breakdown Container -->
          <div class="freight-overview-card mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2.5">
              <div class="fw-bold text-slate-800 d-flex align-items-center" style="font-size: 0.92rem;">
                <i class="fa-solid fa-file-invoice-dollar text-success me-2" style="font-size: 1.05rem;"></i> Freight Charges & Order Summary
              </div>
              <span class="financial-pill-badge">
                <i class="fa-solid fa-calculator"></i> Financial Breakdown
              </span>
            </div>
            <div class="row g-2 text-slate-700">
              <div class="col-6 col-sm-3">
                <span class="text-muted d-block small">Freight Charges:</span>
                <strong class="text-success fs-6 font-mono" id="resFreightCharges">₹ 0.00</strong>
              </div>
              <div class="col-6 col-sm-3">
                <span class="text-muted d-block small">Plants Value:</span>
                <strong class="text-slate-900 font-mono" id="resPlantsValue">₹ 0.00</strong>
              </div>
              <div class="col-6 col-sm-3">
                <span class="text-muted d-block small">GST Tax:</span>
                <strong class="text-slate-900 font-mono" id="resTaxAmount">₹ 0.00</strong>
              </div>
              <div class="col-6 col-sm-3">
                <span class="text-muted d-block small">Total Invoice Value:</span>
                <strong class="text-primary fs-6 font-mono" id="resGrandTotal">₹ 0.00</strong>
              </div>
            </div>
          </div>

          <!-- Items Table -->
          <div class="mb-4">
            <div class="fw-bold text-slate-800 mb-2.5 d-flex align-items-center justify-content-between">
              <span class="d-flex align-items-center" style="font-size: 0.92rem;">
                <i class="fa-solid fa-boxes-stacked text-primary me-2" style="font-size: 1rem;"></i> Dispatched Plants & Tray Cavity Breakdown
              </span>
              <span class="badge" style="background-color: #e2e8f0; color: #334155; font-weight: 700;" id="resItemCountBadge">0 Items</span>
            </div>
            <div class="table-responsive border rounded-3 overflow-hidden shadow-sm">
              <table class="gp-modern-table mb-0" style="min-width: 520px;">
                <thead>
                  <tr>
                    <th style="width: 45px;">#</th>
                    <th>Product & Variety</th>
                    <th>Stock Order</th>
                    <th class="text-center">Trays Dispatched & Cavity</th>
                    <th class="text-end">Plants Quantity</th>
                  </tr>
                </thead>
                <tbody id="resItemsTbody">
                  <!-- Injected via JavaScript -->
                </tbody>
              </table>
            </div>
          </div>

          <!-- Gate Clearance Footer Actions -->
          <div class="p-3 bg-light border rounded-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div id="resGateVerifiedInfo" class="small text-muted">
              <!-- Injected dynamically -->
            </div>
            <div class="d-flex gap-2 ms-auto w-100-mobile">
              <button id="btnApproveGateExit" class="btn btn-success fw-bold px-4 py-2 shadow-sm" onclick="approveGateExit()">
                <i class="fa-solid fa-circle-check me-1.5"></i> Mark Gate Exit & Verified
              </button>
            </div>
          </div>

        </div>

      </div>

    </div>

  </div>

  <!-- RECENT GATE PASS SCANS HISTORY LOG -->
  <div class="gp-card p-3 p-md-4 mt-2">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3.5 gap-2">
      <div>
        <h5 class="fw-bold text-slate-800 mb-0 d-flex align-items-center">
          <i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> Recent Gate Passes & Verification Log
        </h5>
        <small class="text-muted">Real-time log of cleared gate exits and verification history</small>
      </div>
      <div class="d-flex align-items-center gap-2">
        <span class="badge d-none d-sm-inline-flex align-items-center gap-1" style="background-color: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; font-size: 0.75rem; padding: 0.35rem 0.65rem;">
          <i class="fa-solid fa-shield-check"></i> Verified Exits Only
        </span>
        <button class="btn btn-outline-secondary btn-sm fw-bold px-3 py-1.5" onclick="refreshRecentList()">
          <i class="fa-solid fa-rotate-right me-1.5"></i> Refresh Log
        </button>
      </div>
    </div>

    <div class="table-responsive border rounded-3 overflow-hidden shadow-sm">
      <table class="gp-modern-table mb-0" style="min-width: 860px;">
        <thead>
          <tr>
            <th style="min-width: 140px;">Gate Pass No.</th>
            <th style="min-width: 125px;">Order No.</th>
            <th style="min-width: 155px;">Customer</th>
            <th style="min-width: 155px;">Vehicle & Driver</th>
            <th class="text-center" style="min-width: 75px;">Trays</th>
            <th class="text-center" style="min-width: 85px;">Plants</th>
            <th class="text-center" style="min-width: 95px;">Freight</th>
            <th class="text-center" style="min-width: 125px;">Gate Status</th>
            <th class="text-end" style="min-width: 85px;">Actions</th>
          </tr>
        </thead>
        <tbody id="recentScansTbody">
          <tr>
            <td colspan="9" class="text-center py-4 text-muted">Loading recent history...</td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="d-md-none text-muted small text-center mt-2.5" style="font-size: 0.75rem;">
      <i class="fa-solid fa-arrows-left-right text-primary me-1"></i> Swipe table horizontally to see full dispatch details
    </div>
  </div>

</div>

<!-- EDIT TRANSPORT & FREIGHT MODAL -->
<div class="modal fade" id="editGateModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header bg-slate-900 text-white border-0 py-3">
        <h5 class="modal-title fw-bold">
          <i class="fa fa-edit text-warning me-2"></i>Edit Transport & Freight Details
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <form id="editGateForm">
          <input type="hidden" id="editGpNo" name="gatepass_no">

          <div class="mb-3">
            <label class="form-label fw-bold small text-slate-700">Vehicle Number <span class="text-danger">*</span></label>
            <input type="text" id="editVehicleno" name="vehicleno" class="form-control text-uppercase font-mono fw-bold" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold small text-slate-700">Transporter Name</label>
            <input type="text" id="editTransporter" name="transporter_name" class="form-control" placeholder="e.g. Karjee Transport / Self">
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label fw-bold small text-slate-700">Driver Name</label>
              <input type="text" id="editDriverName" name="drivername" class="form-control">
            </div>
            <div class="col-6">
              <label class="form-label fw-bold small text-slate-700">Driver Mobile</label>
              <input type="text" id="editDriverMobile" name="drivermobile" class="form-control font-mono">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold small text-slate-700">Freight Charges (₹)</label>
            <div class="input-group">
              <span class="input-group-text">₹</span>
              <input type="number" step="0.01" id="editFreightCharges" name="freight_charges" class="form-control font-mono fw-bold">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold small text-slate-700">Security / Gate Remarks</label>
            <textarea id="editRemarks" name="remarks" class="form-control" rows="2" placeholder="e.g. Odometer reading, gate exit notes"></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer bg-light border-0 py-3">
        <button type="button" class="btn btn-secondary fw-bold px-3" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary fw-bold px-4" onclick="saveGateDetails()">
          <i class="fa fa-save me-1"></i> Save Changes
        </button>
      </div>
    </div>
  </div>
</div>

<script>
let html5QrScanner = null;
let currentCameraId = null;
let availableCameras = [];
let isScanning = false;
let currentLoadedGp = null;

// Audio Beep generator
const audioCtx = (window.AudioContext || window.webkitAudioContext) ? new (window.AudioContext || window.webkitAudioContext)() : null;
function playBeep() {
  if (!$('#autoBeepSwitch').is(':checked') || !audioCtx) return;
  try {
    const osc = audioCtx.createOscillator();
    const gain = audioCtx.createGain();
    osc.connect(gain);
    gain.connect(audioCtx.destination);
    osc.type = 'sine';
    osc.frequency.setValueAtTime(880, audioCtx.currentTime);
    gain.gain.setValueAtTime(0.2, audioCtx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.00001, audioCtx.currentTime + 0.15);
    osc.start();
    osc.stop(audioCtx.currentTime + 0.15);
  } catch(e) {}
}

$(document).ready(function() {
  refreshRecentList();

  // Manual input enter key
  $('#manualScanInput').on('keypress', function(e) {
    if (e.which === 13) {
      e.preventDefault();
      handleManualSearch();
    }
  });

  // Check URL query parameters for direct scan
  const urlParams = new URLSearchParams(window.location.search);
  const gpParam = urlParams.get('gp') || urlParams.get('gatepass_no') || urlParams.get('pso');
  if (gpParam) {
    $('#manualScanInput').val(gpParam);
    performLookup(gpParam);
  }
});

// START CAMERA SCANNER
function startCameraScanner() {
  if (isScanning) return;

  $('#scannerPlaceholder').addClass('d-none');
  $('#scannerLaser').show();
  $('#btnStartCamera').addClass('d-none');
  $('#btnStopCamera').removeClass('d-none');

  Html5Qrcode.getCameras().then(cameras => {
    if (cameras && cameras.length > 0) {
      availableCameras = cameras;
      let backCam = cameras.find(c => c.label.toLowerCase().includes('back') || c.label.toLowerCase().includes('environment'));
      currentCameraId = backCam ? backCam.id : cameras[0].id;

      if (cameras.length > 1) {
        $('#btnSwitchCamera').removeClass('d-none');
      }

      html5QrScanner = new Html5Qrcode("qr-reader");
      const config = { fps: 12, qrbox: { width: 250, height: 250 } };

      html5QrScanner.start(
        currentCameraId,
        config,
        (decodedText) => {
          playBeep();
          handleScannedData(decodedText);
        },
        (errorMessage) => {}
      ).then(() => {
        isScanning = true;
      }).catch(err => {
        $('#scannerLaser').hide();
        $('#scannerPlaceholder').removeClass('d-none');
        Swal.fire('Camera Error', 'Could not open camera: ' + err, 'error');
        stopCameraScanner();
      });
    } else {
      Swal.fire('No Cameras', 'No camera detected on this device.', 'warning');
      stopCameraScanner();
    }
  }).catch(err => {
    Swal.fire('Camera Permission', 'Please allow camera access to scan QR codes.', 'error');
    stopCameraScanner();
  });
}

// STOP CAMERA SCANNER
function stopCameraScanner() {
  if (html5QrScanner && isScanning) {
    html5QrScanner.stop().then(() => {
      html5QrScanner.clear();
      isScanning = false;
      $('#scannerLaser').hide();
      $('#scannerPlaceholder').removeClass('d-none');
      $('#btnStartCamera').removeClass('d-none');
      $('#btnStopCamera').addClass('d-none');
      $('#btnSwitchCamera').addClass('d-none');
    }).catch(err => {
      isScanning = false;
    });
  } else {
    $('#scannerLaser').hide();
    $('#scannerPlaceholder').removeClass('d-none');
    $('#btnStartCamera').removeClass('d-none');
    $('#btnStopCamera').addClass('d-none');
    $('#btnSwitchCamera').addClass('d-none');
  }
}

// SWITCH CAMERA
function switchCamera() {
  if (availableCameras.length <= 1) return;
  let currentIndex = availableCameras.findIndex(c => c.id === currentCameraId);
  let nextIndex = (currentIndex + 1) % availableCameras.length;
  currentCameraId = availableCameras[nextIndex].id;
  stopCameraScanner();
  setTimeout(() => { startCameraScanner(); }, 300);
}

// HANDLE SCANNED QR STRING
function handleScannedData(rawText) {
  $('#manualScanInput').val(rawText);
  performLookup(rawText);
}

// MANUAL SEARCH
function handleManualSearch() {
  let val = $('#manualScanInput').val().trim();
  if (!val) {
    Swal.fire('Input Required', 'Please enter or scan a Gate Pass or PSO Number.', 'warning');
    return;
  }
  performLookup(val);
}

// PERFORM AJAX LOOKUP
function performLookup(searchKey) {
  Swal.showLoading();

  $.ajax({
    url: 'modal/gatepass_modal.php?action=scan_lookup',
    type: 'POST',
    data: { qr_data: searchKey },
    dataType: 'json',
    success: function(res) {
      Swal.close();
      if (res.status === 'success') {
        currentLoadedGp = res;
        renderGatePassDetails(res);
      } else {
        Swal.fire('Not Found', res.message || 'No matching gate pass found.', 'error');
      }
    },
    error: function() {
      Swal.close();
      Swal.fire('Connection Error', 'Failed to communicate with server.', 'error');
    }
  });
}

// RENDER GATE PASS DETAILS
function renderGatePassDetails(data) {
  let gp = data.gatepass;
  let client = data.client;
  let items = data.items;
  let fin = data.financials;

  $('#emptyResultPlaceholder').addClass('d-none');
  $('#resultCard').removeClass('d-none');

  // Top Status & Badges
  $('#resGatepassNoBadge').text(gp.gatepass_no);
  $('#resPsoNo').text(gp.psono + (gp.sono ? ' / ' + gp.sono : ''));
  $('#resCreatedAt').text(gp.created_at);

  if (gp.invoiceno) {
    $('#resInvoiceContainer').removeClass('d-none');
    $('#resInvoiceNo').text(gp.invoiceno);
  } else {
    $('#resInvoiceContainer').addClass('d-none');
  }

  // Top Status & Badges
  $('#resGatepassNoBadge').text(gp.gatepass_no);
  $('#resPsoNo').text(gp.psono + (gp.sono ? ' / ' + gp.sono : ''));
  $('#resCreatedAt').text(gp.created_at);

  if (gp.gate_status === 'passed_gate') {
    $('#resGateStatusBadge')
      .removeClass('status-badge-pending')
      .addClass('status-badge-cleared')
      .html('<i class="fa-solid fa-circle-check me-1.5"></i> GATE CLEARED &amp; VERIFIED');
    $('#btnApproveGateExit').addClass('d-none');
    let tripStatusText = escapeHtml(gp.trip_status || 'Dispatched');
    let tripBadgeHtml = `
      <span class="badge ms-2" style="background-color: #059669 !important; color: #ffffff !important; font-size: 0.78rem; font-weight: 700; padding: 0.35rem 0.75rem; border-radius: 6px; display: inline-flex; align-items: center; gap: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.12); vertical-align: middle;">
        <i class="fa-solid fa-truck-fast text-white" style="font-size: 0.82rem; color: #ffffff !important;"></i>
        <span style="color: #ffffff !important;">Trip: ${tripStatusText}</span>
      </span>
    `;

    let dispatchInfo = gp.dispatch_time 
      ? ` &bull; <span style="color: #059669;">Dispatched:</span> <span class="text-slate-700 fw-semibold">${escapeHtml(gp.dispatch_time)}</span>` 
      : '';

    $('#resGateVerifiedInfo').html(`
      <div class="d-flex flex-wrap align-items-center fw-bold gap-1" style="font-size: 0.88rem;">
        <i class="fa-solid fa-circle-check text-success me-1.5" style="font-size: 1.15rem;"></i>
        <span style="color: #15803d;">Passed Gate: <span class="text-slate-700 fw-semibold">${escapeHtml(gp.gate_verified_at || 'Recently')}</span> by <strong class="text-slate-800">${escapeHtml(gp.gate_verified_by || 'Security Officer')}</strong>${dispatchInfo}</span>
        ${tripBadgeHtml}
      </div>
    `);
  } else {
    $('#resGateStatusBadge')
      .removeClass('status-badge-cleared')
      .addClass('status-badge-pending')
      .html('<i class="fa-solid fa-clock me-1.5"></i> PENDING GATE EXIT');
    $('#btnApproveGateExit').removeClass('d-none');
    $('#resGateVerifiedInfo').html(`
      <div class="d-flex align-items-center text-amber fw-bold" style="color: #b45309; font-size: 0.88rem;">
        <i class="fa-solid fa-triangle-exclamation me-2" style="font-size: 1.15rem;"></i>
        <span>Awaiting physical gate clearance inspection.</span>
      </div>
    `);
  }

  // PDF Action
  $('#resBtnPdf').attr('onclick', `previewGatePassPdf('${escapeAttr(gp.pdf_url)}', '${escapeAttr(gp.gatepass_no)}')`);

  // KPI
  $('#resTotalPlants').text(Number(gp.total_plants).toLocaleString());
  $('#resTotalTrays').text(Number(gp.total_trays).toLocaleString());
  $('#resVehicleNoTop').text(gp.vehicleno || '-');

  // Customer
  $('#resClientName').text(client.clientname || 'Customer');
  if (client.mobileno && client.mobileno.trim() !== '' && client.mobileno !== '-') {
    $('#resClientPhoneContainer').removeClass('d-none');
    $('#resClientPhone').html(`<a href="tel:${escapeAttr(client.mobileno)}" class="text-decoration-none text-slate-800 fw-bold">${escapeHtml(client.mobileno)}</a>`);
  } else {
    $('#resClientPhoneContainer').addClass('d-none');
  }
  $('#resClientAddress').text(client.delivery_address || 'Destination');

  // Vehicle & Driver
  $('#resVehicleNo').text(gp.vehicleno || 'No Vehicle');
  $('#resTransporter').text(gp.transporter_name ? `• ${gp.transporter_name}` : '');
  
  let driverText = escapeHtml(gp.drivername || 'Self / Transporter Driver');
  if (gp.drivermobile && gp.drivermobile.trim() !== '' && gp.drivermobile !== '-') {
    driverText += ` <span class="text-muted ms-1">(<i class="fa-solid fa-phone text-success mx-0.5" style="font-size: 0.72rem;"></i> <a href="tel:${escapeAttr(gp.drivermobile)}" class="text-decoration-none text-slate-700 fw-semibold">${escapeHtml(gp.drivermobile)}</a>)</span>`;
  }
  $('#resDriverInfoLine').html(driverText);

  if (gp.remarks && gp.remarks.trim() !== '' && gp.remarks !== 'None' && gp.remarks !== '-') {
    $('#resRemarksContainer').removeClass('d-none');
    $('#resRemarks').text(gp.remarks);
  } else {
    $('#resRemarksContainer').addClass('d-none');
  }

  // Financials
  $('#resFreightCharges').text('₹ ' + Number(fin.freight_charges || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 }));
  $('#resPlantsValue').text('₹ ' + Number(fin.plants_value || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 }));
  $('#resTaxAmount').text('₹ ' + Number(fin.tax_amount || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 }));
  $('#resGrandTotal').text('₹ ' + Number(fin.grand_total || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 }));

  // Items Table
  $('#resItemCountBadge').text(items.length + ' Items');
  let itemsHtml = '';
  items.forEach((it, idx) => {
    itemsHtml += `
      <tr>
        <td class="text-muted fw-bold">${idx + 1}</td>
        <td>
          <div class="fw-bold text-slate-800">${escapeHtml(it.product_name)}</div>
        </td>
        <td>
          <span class="badge font-mono" style="background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; padding: 0.35rem 0.6rem;">${escapeHtml(it.stockorderno || '-')}</span>
        </td>
        <td class="text-center">
          <strong class="text-primary font-mono">${Number(it.trays).toLocaleString()} Trays</strong>
          <span class="badge ms-1.5" style="background: #f8fafc; color: #475569; border: 1px solid #e2e8f0; padding: 0.3rem 0.55rem;">${escapeHtml(it.tray_cavity || '126 Cavity')}</span>
        </td>
        <td class="text-end">
          <strong class="text-success font-mono">${Number(it.plants).toLocaleString()} Plants</strong>
        </td>
      </tr>
    `;
  });
  $('#resItemsTbody').html(itemsHtml);

  // Scroll to details on mobile
  if (window.innerWidth < 992) {
    $('html, body').animate({
      scrollTop: $("#resultCard").offset().top - 20
    }, 400);
  }
}

// APPROVE & MARK GATE EXIT
function approveGateExit() {
  if (!currentLoadedGp || !currentLoadedGp.gatepass) return;
  let gpNo = currentLoadedGp.gatepass.gatepass_no;

  Swal.fire({
    title: 'Verify & Clear Gate Exit?',
    html: `Are you sure you want to approve Gate Exit for Gate Pass <strong>${escapeHtml(gpNo)}</strong> with Vehicle <strong>${escapeHtml(currentLoadedGp.gatepass.vehicleno)}</strong>?`,
    icon: 'question',
    input: 'text',
    inputPlaceholder: 'Optional gate remarks / odometer reading...',
    showCancelButton: true,
    confirmButtonColor: '#059669',
    cancelButtonColor: '#64748b',
    confirmButtonText: '<i class="fa-solid fa-check me-1"></i> Approve Gate Exit'
  }).then((res) => {
    if (res.isConfirmed) {
      Swal.showLoading();
      $.ajax({
        url: 'modal/gatepass_modal.php?action=mark_gate_verified',
        type: 'POST',
        data: {
          gatepass_no: gpNo,
          remarks: res.value || ''
        },
        dataType: 'json',
        success: function(resp) {
          Swal.close();
          if (resp.status === 'success') {
            Swal.fire({
              title: 'Gate Cleared & Dispatched!',
              html: `
                <div class="text-start p-2">
                  <div class="text-success fw-bold mb-2"><i class="fa-solid fa-circle-check me-1"></i> ${escapeHtml(resp.message)}</div>
                  <div class="p-2.5 rounded bg-light border small">
                    <div><strong>Gate Pass ID:</strong> ${escapeHtml(resp.gatepass_id || '')}</div>
                    <div><strong>Trip Status:</strong> <span class="badge" style="background-color: #059669 !important; color: #ffffff !important; font-size: 0.8rem; padding: 0.3rem 0.6rem;">${escapeHtml(resp.trip_status || 'Dispatched')}</span></div>
                    <div><strong>Dispatch Time:</strong> ${escapeHtml(resp.dispatch_time || 'Just now')}</div>
                    <div><strong>Verified By:</strong> ${escapeHtml(resp.verified_by || 'Security Officer')}</div>
                  </div>
                </div>
              `,
              icon: 'success',
              confirmButtonColor: '#059669'
            });
            performLookup(gpNo);
            refreshRecentList();
          } else {
            Swal.fire('Error', resp.message || 'Failed to verify.', 'error');
          }
        },
        error: function() {
          Swal.close();
          Swal.fire('Error', 'Server communication error.', 'error');
        }
      });
    }
  });
}

// OPEN EDIT GATE MODAL
function openEditGateModal() {
  if (!currentLoadedGp || !currentLoadedGp.gatepass) return;
  let gp = currentLoadedGp.gatepass;
  let fin = currentLoadedGp.financials;

  $('#editGpNo').val(gp.gatepass_no);
  $('#editVehicleno').val(gp.vehicleno || '');
  $('#editTransporter').val(gp.transporter_name || '');
  $('#editDriverName').val(gp.drivername || '');
  $('#editDriverMobile').val(gp.drivermobile || '');
  $('#editFreightCharges').val(fin.freight_charges || 0);
  $('#editRemarks').val(gp.remarks || '');

  $('#editGateModal').modal('show');
}

// SAVE GATE DETAILS
function saveGateDetails() {
  let gpNo = $('#editGpNo').val();
  let vNo = $('#editVehicleno').val().trim();
  if (!vNo) {
    Swal.fire('Required', 'Vehicle Number is required.', 'warning');
    return;
  }

  let formData = $('#editGateForm').serialize();
  Swal.showLoading();

  $.ajax({
    url: 'modal/gatepass_modal.php?action=update_gate_details',
    type: 'POST',
    data: formData,
    dataType: 'json',
    success: function(res) {
      Swal.close();
      if (res.status === 'success') {
        $('#editGateModal').modal('hide');
        Swal.fire('Saved!', res.message, 'success');
        performLookup(gpNo);
        refreshRecentList();
      } else {
        Swal.fire('Error', res.message || 'Failed to update.', 'error');
      }
    },
    error: function() {
      Swal.close();
      Swal.fire('Error', 'Server connection error.', 'error');
    }
  });
}

// LOAD & SCROLL TO GATE PASS
function loadAndScrollToPass(gpNo) {
  if (!gpNo) return;
  $('#manualScanInput').val(gpNo);
  performLookup(gpNo);
  setTimeout(() => {
    let target = $('#resultCard');
    if (target.length) {
      $('html, body').animate({
        scrollTop: target.offset().top - 20
      }, 350);
    }
  }, 250);
}

// REFRESH RECENT SCANS LOG
function refreshRecentList() {
  $.ajax({
    url: 'modal/gatepass_modal.php?action=get_recent_scans',
    type: 'GET',
    dataType: 'json',
    success: function(res) {
      if (res.status === 'success') {
        let rows = '';
        if (res.list && res.list.length > 0) {
          res.list.forEach(r => {
            let statusBadge = `<span class="gp-status-badge status-badge-cleared"><i class="fa-solid fa-truck-fast me-1"></i> Dispatched</span>`;

            let invInfo = r.invoiceno ? `<div class="small text-primary font-mono"><i class="fa-solid fa-file-invoice me-1"></i>${escapeHtml(r.invoiceno)}</div>` : '';

            let timeBadge = (r.verified_at_fmt && r.verified_at_fmt !== '-')
              ? `<div class="text-success small fw-semibold" style="font-size: 0.72rem;"><i class="fa-solid fa-circle-check me-1"></i>${escapeHtml(r.verified_at_fmt)}</div>`
              : `<div class="text-muted small" style="font-size: 0.72rem;"><i class="fa-regular fa-clock me-1"></i>${escapeHtml(r.created_at_fmt)}</div>`;

            let phoneHtml = (r.clientphone && r.clientphone !== '-' && r.clientphone.trim() !== '')
              ? `<div class="small"><a href="tel:${escapeAttr(r.clientphone)}" class="text-muted text-decoration-none"><i class="fa-solid fa-phone text-success me-1" style="font-size: 0.7rem;"></i>${escapeHtml(r.clientphone)}</a></div>`
              : '';

            let driverHtml = (r.drivername && r.drivername !== '-' && r.drivername.trim() !== '')
              ? `<div class="small text-slate-600"><i class="fa-solid fa-id-card text-slate-400 me-1"></i>${escapeHtml(r.drivername)}</div>`
              : '';

            rows += `
              <tr>
                <td>
                  <a href="javascript:void(0)" onclick="loadAndScrollToPass('${escapeAttr(r.gatepass_no)}')" class="fw-bold text-primary font-mono text-decoration-none fs-6">
                    ${escapeHtml(r.gatepass_no)}
                  </a>
                  ${timeBadge}
                </td>
                <td>
                  <div class="font-mono fw-bold text-slate-800">${escapeHtml(r.psono)}</div>
                  ${invInfo}
                </td>
                <td>
                  <div class="fw-bold text-slate-800">${escapeHtml(r.clientname || 'Customer')}</div>
                  ${phoneHtml}
                </td>
                <td>
                  <span class="badge font-mono px-2 py-1 text-white" style="background-color: #2563eb !important; font-size: 0.76rem;">${escapeHtml(r.vehicleno || '-')}</span>
                  ${driverHtml}
                </td>
                <td class="text-center font-mono fw-semibold">${Number(r.total_trays || 0).toLocaleString()}</td>
                <td class="text-center font-mono fw-bold text-success">${Number(r.total_plants || 0).toLocaleString()}</td>
                <td class="text-center font-mono text-slate-800 fw-bold">₹ ${Number(r.freight_charges || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 })}</td>
                <td class="text-center">${statusBadge}</td>
                <td class="text-end">
                  <button class="btn btn-sm btn-outline-primary fw-bold px-2.5 py-1" onclick="loadAndScrollToPass('${escapeAttr(r.gatepass_no)}')">
                    <i class="fa-solid fa-eye me-1"></i> View
                  </button>
                </td>
              </tr>
            `;
          });
        } else {
          rows = `
            <tr>
              <td colspan="9" class="text-center py-5 text-muted">
                <div class="mb-2 text-slate-400"><i class="fa-solid fa-clipboard-check fa-2x"></i></div>
                <div class="fw-bold text-slate-700">No Dispatched Gate Passes Yet</div>
                <small class="text-muted">Gate passes will appear here automatically once gate exit is verified and cleared.</small>
              </td>
            </tr>
          `;
        }
        $('#recentScansTbody').html(rows);
      }
    }
  });
}

// PREVIEW PDF
function previewGatePassPdf(pdfUrl, gpNo) {
  let viewUrl = '';
  if (gpNo) {
    viewUrl = `modal/gatepass_modal.php?action=view_pdf&gatepass_no=${encodeURIComponent(gpNo)}&t=${Date.now()}`;
  } else if (pdfUrl) {
    viewUrl = pdfUrl + (pdfUrl.indexOf('?') === -1 ? '?' : '&') + 't=' + Date.now();
  }
  if (!viewUrl) return;
  window.open(viewUrl, '_blank');
}

// CLEAR RESULTS
function clearResults() {
  $('#manualScanInput').val('');
  $('#emptyResultPlaceholder').removeClass('d-none');
  $('#resultCard').addClass('d-none');
  currentLoadedGp = null;
}

// Helper escape functions
function escapeHtml(str) {
  if (!str) return '';
  return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}
function escapeAttr(str) {
  if (!str) return '';
  return String(str).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}
</script>

<?php
if (!isset($_GET['dz'])) {
    require('../footer.php');
}
?>
</body>
</html>
