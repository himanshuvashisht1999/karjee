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
$loginroleid = isset($loginroleid) ? (int)$loginroleid : (int)($_SESSION['loginroleid'] ?? 4);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Plant QR Movement (Ready & Open Stock) | Karjee Farm</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

  <!-- Fonts & Icons -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  
  <!-- Select2, SweetAlert2, HTML5 QR Code & jsQR Scanner -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

  <style>
    :root {
      --font-main: 'Plus Jakarta Sans', sans-serif;
      --font-mono: 'JetBrains Mono', monospace;
      --primary: #4f46e5;
      --primary-dark: #3730a3;
      --primary-light: #eef2ff;
      --emerald: #10b981;
      --emerald-light: #ecfdf5;
      --amber: #f59e0b;
      --amber-light: #fffbeb;
      --rose: #ef4444;
      --rose-light: #fef2f2;
      --slate-900: #0f172a;
      --slate-800: #1e293b;
      --slate-700: #334155;
      --slate-600: #475569;
      --slate-500: #64748b;
      --slate-200: #e2e8f0;
      --slate-100: #f1f5f9;
      --slate-50: #f8fafc;
      --card-radius: 14px;
      --shadow-soft: 0 4px 20px -2px rgba(15, 23, 42, 0.07), 0 2px 6px -2px rgba(15, 23, 42, 0.04);
    }

    body {
      font-family: var(--font-main);
      background-color: #f1f5f9;
      color: var(--slate-800);
      margin: 0;
      padding: 0;
      -webkit-font-smoothing: antialiased;
    }

    .move-page-wrap {
      max-width: 1100px;
      margin: 1.5rem auto 5rem auto;
      padding: 0 1rem;
    }

    /* Hero Header */
    .move-hero {
      background: linear-gradient(135deg, #1e1b4b 0%, #312e81 40%, #4338ca 100%);
      color: #ffffff;
      border-radius: var(--card-radius);
      padding: 1.5rem 1.75rem;
      margin-bottom: 1.5rem;
      box-shadow: 0 10px 25px -5px rgba(49, 46, 129, 0.35);
      position: relative;
      overflow: hidden;
    }
    .move-hero::after {
      content: "";
      position: absolute;
      top: -30%;
      right: -10%;
      width: 300px;
      height: 300px;
      background: radial-gradient(circle, rgba(99, 102, 241, 0.25) 0%, rgba(255,255,255,0) 70%);
      pointer-events: none;
    }
    .move-hero h1 {
      font-size: 1.45rem;
      font-weight: 800;
      margin: 0 0 0.35rem 0;
      display: flex;
      align-items: center;
      gap: 0.6rem;
    }
    .move-hero p {
      margin: 0;
      font-size: 0.88rem;
      color: #c7d2fe;
      max-width: 700px;
    }

    /* Card Panels */
    .move-card {
      background: #ffffff;
      border-radius: var(--card-radius);
      border: 1px solid var(--slate-200);
      box-shadow: var(--shadow-soft);
      padding: 1.4rem;
      margin-bottom: 1.5rem;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .card-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid var(--slate-100);
      padding-bottom: 0.85rem;
      margin-bottom: 1.15rem;
      flex-wrap: wrap;
      gap: 0.5rem;
    }
    .card-head h2 {
      font-size: 1.1rem;
      font-weight: 700;
      color: var(--slate-900);
      margin: 0;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    /* Scanner Viewport */
    .scanner-tabs {
      display: flex;
      gap: 0.5rem;
      background: var(--slate-100);
      padding: 4px;
      border-radius: 10px;
      margin-bottom: 1rem;
    }
    .scanner-tab-btn {
      flex: 1;
      border: none;
      background: transparent;
      padding: 8px 14px;
      font-size: 0.88rem;
      font-weight: 600;
      color: var(--slate-600);
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.15s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.45rem;
    }
    .scanner-tab-btn.active {
      background: #ffffff;
      color: var(--primary);
      box-shadow: 0 2px 6px rgba(0,0,0,0.06);
    }

    #qrCameraViewport {
      width: 100%;
      min-height: 250px;
      background: #0f172a;
      border-radius: 12px;
      overflow: hidden;
      position: relative;
    }

    .camera-ctrl-bar {
      display: flex;
      gap: 0.5rem;
      margin-top: 0.75rem;
      flex-wrap: wrap;
    }

    /* Manual Input Bar */
    .search-input-group {
      display: flex;
      gap: 0.5rem;
      width: 100%;
      box-sizing: border-box;
      align-items: stretch;
    }
    .scan-input {
      flex: 1;
      width: 100%;
      min-height: 52px !important;
      height: 52px !important;
      line-height: normal !important;
      border: 2px solid var(--slate-200) !important;
      border-radius: 10px !important;
      padding: 10px 16px !important;
      font-size: 1.05rem !important;
      font-family: var(--font-mono) !important;
      font-weight: 700 !important;
      outline: none;
      box-sizing: border-box !important;
      display: block !important;
      background: #ffffff !important;
      color: var(--slate-900) !important;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    .scan-input:focus {
      border-color: var(--primary) !important;
      box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15) !important;
    }
    .btn-scan-action {
      min-height: 52px !important;
      height: 52px !important;
      padding: 0 1.5rem !important;
      border: none !important;
      border-radius: 10px !important;
      font-weight: 700 !important;
      font-size: 1rem !important;
      cursor: pointer;
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
      gap: 0.45rem !important;
      box-sizing: border-box !important;
      transition: all 0.2s ease;
      background: var(--primary) !important;
      color: #ffffff !important;
      white-space: nowrap !important;
    }
    .btn-scan-action:hover {
      background: var(--primary-dark) !important;
      transform: translateY(-1px);
    }

    /* Details Grid */
    .details-box {
      background: #f8fafc;
      border: 1.5px solid #e2e8f0;
      border-radius: 12px;
      padding: 1.25rem;
      margin-bottom: 1.25rem;
    }
    .details-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1rem;
    }
    .detail-item {
      display: flex;
      flex-direction: column;
    }
    .detail-label {
      font-size: 0.76rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: var(--slate-500);
      margin-bottom: 0.2rem;
    }
    .detail-val {
      font-size: 0.96rem;
      font-weight: 700;
      color: var(--slate-900);
    }
    .detail-val.mono {
      font-family: var(--font-mono);
      color: var(--primary);
    }

    /* Status Badges */
    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 0.78rem;
      font-weight: 800;
    }
    .status-badge.ready {
      background: var(--emerald-light);
      color: #065f46;
      border: 1px solid #a7f3d0;
    }
    .status-badge.open {
      background: #e0f2fe;
      color: #0369a1;
      border: 1px solid #bae6fd;
    }

    /* Available Trays & Occupancy Dual KPI Cards */
    .kpi-row-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 0.85rem;
      margin-bottom: 1.25rem;
    }
    .kpi-dual-card {
      border-radius: 12px;
      padding: 1rem 1.25rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      transition: all 0.2s ease;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .kpi-dual-card.card-occupied {
      background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
      border: 1.5px solid #86efac;
    }
    .kpi-dual-card.card-ready {
      background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
      border: 1.5px solid #93c5fd;
    }
    .kpi-dual-card .kpi-info-title {
      font-size: 0.8rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.4px;
      margin-bottom: 0.2rem;
    }
    .card-occupied .kpi-info-title { color: #166534; }
    .card-ready .kpi-info-title { color: #1e40af; }
    .kpi-dual-card .kpi-info-sub {
      font-size: 0.76rem;
      font-weight: 500;
      display: block;
    }
    .card-occupied .kpi-info-sub { color: #15803d; }
    .card-ready .kpi-info-sub { color: #2563eb; }
    .kpi-dual-card .kpi-stat-num {
      font-size: 1.7rem;
      font-weight: 800;
      font-family: var(--font-mono);
      line-height: 1;
    }
    .card-occupied .kpi-stat-num { color: #15803d; }
    .card-ready .kpi-stat-num { color: #1d4ed8; }

    .avail-kpi-card {
      background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
      border: 1.5px solid #a7f3d0;
      border-radius: 12px;
      padding: 1rem 1.25rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 1.25rem;
    }
    .avail-kpi-title {
      font-size: 0.85rem;
      font-weight: 700;
      color: #065f46;
    }
    .avail-kpi-num {
      font-size: 1.65rem;
      font-weight: 800;
      color: #047857;
      font-family: var(--font-mono);
    }

    /* Action Banner for Ready to Sell */
    .banner-mark-ready {
      background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
      border: 1.5px solid #fde68a;
      border-radius: 14px;
      padding: 1rem 1.25rem;
      margin-bottom: 1.25rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      box-shadow: 0 2px 6px rgba(245, 158, 11, 0.08);
      flex-wrap: wrap;
    }
    .banner-mark-ready-icon {
      width: 44px;
      height: 44px;
      border-radius: 12px;
      background: #fef3c7;
      border: 1.5px solid #fcd34d;
      color: #d97706;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.25rem;
      flex-shrink: 0;
    }
    .banner-mark-ready-title {
      font-weight: 800;
      color: #92400e;
      font-size: 0.95rem;
      line-height: 1.35;
    }
    .banner-mark-ready-sub {
      font-size: 0.82rem;
      color: #b45309;
      margin-top: 2px;
    }
    .btn-action-mark-ready {
      background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
      color: #ffffff !important;
      border: none;
      border-radius: 10px;
      padding: 0.65rem 1.25rem;
      font-size: 0.88rem;
      font-weight: 700;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      white-space: nowrap;
      box-shadow: 0 3px 6px rgba(217, 119, 6, 0.25);
      transition: all 0.2s ease;
    }
    .btn-action-mark-ready:hover {
      box-shadow: 0 6px 12px rgba(217, 119, 6, 0.35);
      transform: translateY(-1px);
      color: #ffffff !important;
    }

    /* ============================================================
       Ready to Sell SweetAlert Custom Modal Styling
       ============================================================ */
    .swal2-rts-popup {
      border-radius: 20px !important;
      padding: 1.5rem !important;
      box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25) !important;
      border: 1px solid rgba(226, 232, 240, 0.9) !important;
      font-family: inherit !important;
      overflow: hidden !important;
      box-sizing: border-box !important;
    }
    .swal2-rts-container {
      margin: 0 !important;
      padding: 0 !important;
      overflow-x: hidden !important;
      overflow-y: visible !important;
      max-width: 100% !important;
      width: 100% !important;
      box-sizing: border-box !important;
    }
    .swal2-rts-container::-webkit-scrollbar {
      display: none !important;
    }
    
    /* ============================================================
       Re-Confirm Movement SweetAlert Responsive Modal Styling
       ============================================================ */
    .swal2-reconfirm-popup {
      max-height: 94vh !important;
      max-height: 94dvh !important;
      display: flex !important;
      flex-direction: column !important;
      padding: 0.85rem 1rem 0.65rem 1rem !important;
      border-radius: 16px !important;
      box-sizing: border-box !important;
      overflow: hidden !important;
      width: min(540px, 96vw) !important;
      box-shadow: 0 20px 40px -10px rgba(15, 23, 42, 0.3) !important;
    }

    .swal2-reconfirm-popup .swal2-icon {
      display: none !important; /* Hide giant circle icon to conserve vertical space across all screens */
    }

    .swal2-reconfirm-popup .swal2-title {
      font-size: 1.15rem !important;
      padding: 0 0.25rem !important;
      margin: 0.1rem 0 0.35rem 0 !important;
      line-height: 1.3 !important;
      flex-shrink: 0 !important;
      color: #0f172a !important;
      font-weight: 800 !important;
      text-align: left !important;
    }

    .swal2-reconfirm-container {
      overflow-y: auto !important;
      -webkit-overflow-scrolling: touch !important;
      flex: 1 1 auto !important;
      margin: 0 !important;
      padding: 0.15rem 0.15rem 0.4rem 0.15rem !important;
      box-sizing: border-box !important;
      text-align: left !important;
    }

    .swal2-reconfirm-actions {
      flex-shrink: 0 !important;
      width: 100% !important;
      margin: 0.35rem 0 0 0 !important;
      padding: 0.45rem 0 0 0 !important;
      border-top: 1px solid #e2e8f0 !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      gap: 0.65rem !important;
      box-sizing: border-box !important;
      background: #ffffff !important;
      z-index: 10 !important;
    }

    .swal2-reconfirm-actions .swal2-confirm,
    .swal2-reconfirm-actions .swal2-cancel {
      font-weight: 700 !important;
      font-size: 0.92rem !important;
      padding: 0.6rem 1.15rem !important;
      border-radius: 8px !important;
      margin: 0 !important;
    }

    .swal-app-top-action-bar {
      display: block !important;
    }

    @media (max-width: 768px) {
      .swal2-container.swal2-backdrop-show,
      .swal2-container.swal2-top,
      .swal2-container {
        align-items: flex-start !important;
        justify-content: center !important;
        padding-top: 6px !important;
        padding-bottom: 6px !important;
      }
      .swal2-reconfirm-popup {
        padding: 0.6rem 0.65rem 0.5rem 0.65rem !important;
        width: 96vw !important;
        max-width: 460px !important;
        max-height: 96vh !important;
        max-height: 96dvh !important;
        border-radius: 14px !important;
        margin: 2px auto !important;
      }
      .swal2-reconfirm-popup .swal2-title {
        font-size: 1.02rem !important;
        margin: 0.1rem 0 0.3rem 0 !important;
        padding: 0 !important;
        font-weight: 800 !important;
        line-height: 1.25 !important;
      }
      .swal2-reconfirm-container {
        padding: 0.1rem 0.1rem !important;
        max-height: calc(96vh - 110px) !important;
      }
      .swal2-reconfirm-actions {
        flex-direction: row !important;
        gap: 0.5rem !important;
        padding-top: 0.4rem !important;
        margin-top: 0.3rem !important;
      }
      .swal2-reconfirm-actions .swal2-confirm {
        flex: 2 !important;
        width: auto !important;
        padding: 0.6rem 0.4rem !important;
        font-size: 0.86rem !important;
        border-radius: 8px !important;
      }
      .swal2-reconfirm-actions .swal2-cancel {
        flex: 1 !important;
        width: auto !important;
        padding: 0.6rem 0.4rem !important;
        font-size: 0.86rem !important;
        border-radius: 8px !important;
      }
    }
    
    .rts-modal-wrap {
      text-align: left;
      color: var(--slate-800);
      width: 100%;
      box-sizing: border-box;
      overflow: hidden;
    }
    .rts-header {
      display: flex;
      align-items: center;
      gap: 0.9rem;
      margin-bottom: 1.15rem;
      padding-bottom: 0.85rem;
      border-bottom: 1px solid #f1f5f9;
    }
    .rts-icon-pill {
      width: 46px;
      height: 46px;
      border-radius: 12px;
      background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
      color: #059669;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.3rem;
      border: 1px solid #a7f3d0;
      flex-shrink: 0;
    }
    .rts-title {
      font-size: 1.2rem;
      font-weight: 800;
      color: #0f172a;
      margin: 0;
      line-height: 1.25;
    }
    .rts-subtitle {
      font-size: 0.78rem;
      color: #64748b;
      margin: 3px 0 0 0;
      font-weight: 500;
    }

    /* Meta Info Card */
    .rts-meta-card {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 0.85rem 1rem;
      margin-bottom: 1rem;
      box-sizing: border-box;
    }
    .rts-meta-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 0.65rem;
      margin-bottom: 0.5rem;
    }
    .rts-meta-item {
      display: flex;
      flex-direction: column;
    }
    .rts-meta-tag {
      font-size: 0.68rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: #64748b;
      margin-bottom: 2px;
    }
    .rts-badge-bed {
      display: inline-flex;
      align-items: center;
      width: fit-content;
      background: #0f172a;
      color: #ffffff;
      font-weight: 700;
      font-size: 0.82rem;
      padding: 2px 8px;
      border-radius: 6px;
    }
    .rts-mono-order {
      font-family: var(--font-mono);
      font-weight: 700;
      font-size: 0.9rem;
      color: #2563eb;
    }
    .rts-meta-product {
      border-top: 1px dashed #e2e8f0;
      padding-top: 0.5rem;
    }
    .rts-product-name {
      font-weight: 700;
      font-size: 0.88rem;
      color: #1e293b;
      word-break: break-word;
      line-height: 1.3;
    }

    /* 3-Pill Stats Grid */
    .rts-stats-row {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 0.6rem;
      margin-bottom: 1.15rem;
      box-sizing: border-box;
    }
    .rts-stat-chip {
      padding: 0.65rem 0.4rem;
      border-radius: 10px;
      text-align: center;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      box-sizing: border-box;
    }
    .rts-stat-chip.stat-occ {
      background: #f1f5f9;
      border: 1px solid #e2e8f0;
    }
    .rts-stat-chip.stat-ready {
      background: #eff6ff;
      border: 1px solid #bfdbfe;
    }
    .rts-stat-chip.stat-avail {
      background: #ecfdf5;
      border: 1.5px solid #a7f3d0;
    }
    .rts-chip-label {
      font-size: 0.68rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.3px;
      margin-bottom: 2px;
      white-space: nowrap;
    }
    .stat-occ .rts-chip-label { color: #475569; }
    .stat-ready .rts-chip-label { color: #1e40af; }
    .stat-avail .rts-chip-label { color: #065f46; }
    .rts-chip-val {
      font-size: 1.35rem;
      font-weight: 800;
      font-family: var(--font-mono);
      line-height: 1.1;
    }
    .stat-occ .rts-chip-val { color: #1e293b; }
    .stat-ready .rts-chip-val { color: #2563eb; }
    .stat-avail .rts-chip-val { color: #059669; }
    .rts-chip-unit {
      font-size: 0.68rem;
      font-weight: 600;
      color: #64748b;
    }

    /* Stepper & Input Section */
    .rts-input-box {
      margin-bottom: 0.5rem;
      box-sizing: border-box;
    }
    .rts-label-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 0.4rem;
    }
    .rts-field-label {
      font-size: 0.84rem;
      font-weight: 700;
      color: #1e293b;
    }
    .rts-btn-setmax {
      font-size: 0.72rem;
      font-weight: 700;
      color: #059669;
      background: #ecfdf5;
      border: 1px solid #a7f3d0;
      border-radius: 6px;
      padding: 2px 8px;
      cursor: pointer;
      transition: all 0.15s ease;
    }
    .rts-btn-setmax:hover {
      background: #d1fae5;
    }
    .rts-stepper-container {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      width: 100%;
      box-sizing: border-box;
    }
    .rts-stepper-btn {
      width: 44px;
      height: 44px;
      border-radius: 10px;
      border: 1.5px solid #cbd5e1;
      background: #ffffff;
      color: #334155;
      font-size: 1.15rem;
      font-weight: 700;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      transition: all 0.15s ease;
    }
    .rts-stepper-btn:hover {
      background: #f8fafc;
      border-color: #94a3b8;
    }
    .rts-custom-input {
      flex: 1;
      height: 44px;
      border: 1.5px solid #cbd5e1;
      border-radius: 10px;
      font-family: var(--font-mono);
      font-size: 1.35rem;
      font-weight: 800;
      text-align: center;
      color: #0f172a;
      outline: none;
      box-sizing: border-box;
      width: 100%;
      margin: 0 !important;
      padding: 0 0.5rem;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    .rts-custom-input:focus {
      border-color: #10b981;
      box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.18);
    }
    .rts-calc-hint {
      margin-top: 0.5rem;
      font-size: 0.78rem;
      color: #64748b;
      display: flex;
      align-items: center;
      gap: 0.35rem;
    }
    .rts-calc-hint b {
      color: #059669;
    }

    /* Modal Button Styles */
    .rts-actions-row {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      gap: 0.65rem;
      margin-top: 1.25rem;
      padding-top: 1rem;
      border-top: 1px solid #f1f5f9;
      width: 100%;
      box-sizing: border-box;
    }
    .rts-btn-cancel {
      height: 42px;
      padding: 0 1.25rem;
      border-radius: 10px;
      border: 1.5px solid #e2e8f0;
      background: #ffffff;
      color: #475569;
      font-weight: 700;
      font-size: 0.88rem;
      cursor: pointer;
      transition: all 0.15s ease;
    }
    .rts-btn-cancel:hover {
      background: #f1f5f9;
      color: #1e293b;
    }
    .rts-btn-submit {
      height: 42px;
      padding: 0 1.5rem;
      border-radius: 10px;
      border: none;
      background: linear-gradient(135deg, #059669 0%, #10b981 100%);
      color: #ffffff;
      font-weight: 700;
      font-size: 0.88rem;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 0.45rem;
      box-shadow: 0 2px 4px rgba(16, 185, 129, 0.25);
      transition: all 0.15s ease;
    }
    .rts-btn-submit:hover {
      box-shadow: 0 4px 8px rgba(16, 185, 129, 0.35);
      transform: translateY(-1px);
    }

    /* Form Stepper & Inputs */
    .form-label-custom {
      font-size: 0.88rem;
      font-weight: 700;
      color: var(--slate-800);
      margin-bottom: 0.4rem;
      display: block;
    }

    .stepper-wrap {
      display: flex;
      align-items: center;
      gap: 0.4rem;
    }
    .stepper-btn {
      width: 44px;
      height: 44px;
      border: 1.5px solid var(--slate-200);
      background: #ffffff;
      border-radius: 8px;
      font-size: 1.2rem;
      font-weight: 700;
      color: var(--slate-700);
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.15s ease;
      touch-action: manipulation;
    }
    .stepper-btn:hover {
      background: var(--slate-100);
      border-color: var(--slate-300);
    }
    .stepper-input {
      flex: 1;
      height: 44px;
      border: 1.5px solid var(--slate-200);
      border-radius: 8px;
      padding: 0 0.75rem;
      font-size: 1.15rem;
      font-family: var(--font-mono);
      font-weight: 800;
      text-align: center;
      color: var(--slate-900);
      outline: none;
    }
    .stepper-input:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
    }
    .btn-max-trays {
      background: #eef2ff;
      color: #4338ca;
      border: 1px solid #c7d2fe;
      padding: 0 12px;
      height: 44px;
      border-radius: 8px;
      font-weight: 700;
      font-size: 0.85rem;
      cursor: pointer;
      white-space: nowrap;
    }
    .btn-max-trays:hover {
      background: #e0e7ff;
    }

    .form-select-custom {
      width: 100%;
      height: 46px;
      border: 1.5px solid var(--slate-200);
      border-radius: 8px;
      padding: 0 0.85rem;
      font-size: 0.95rem;
      font-weight: 600;
      color: var(--slate-800);
      background-color: #ffffff;
      outline: none;
      transition: border-color 0.2s;
    }
    .form-select-custom:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
    }

    .form-textarea-custom {
      width: 100%;
      border: 1.5px solid var(--slate-200);
      border-radius: 8px;
      padding: 0.65rem 0.85rem;
      font-size: 0.9rem;
      color: var(--slate-800);
      outline: none;
      min-height: 70px;
      resize: vertical;
      font-family: inherit;
      box-sizing: border-box;
    }
    .form-textarea-custom:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
    }

    .btn-submit-movement {
      width: 100%;
      height: 50px;
      background: linear-gradient(135deg, #059669 0%, #10b981 100%);
      color: #ffffff;
      border: none;
      border-radius: 10px;
      font-size: 1.05rem;
      font-weight: 800;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
      transition: all 0.2s ease;
      margin-top: 1rem;
    }
    .btn-submit-movement:hover {
      background: linear-gradient(135deg, #047857 0%, #059669 100%);
      transform: translateY(-1px);
      box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
    }

    /* History Table */
    .history-table-wrap {
      overflow-x: auto;
      border-radius: 10px;
      border: 1px solid var(--slate-200);
    }
    .table-history {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.86rem;
      background: #ffffff;
      text-align: left;
    }
    .table-history th {
      background: #f8fafc;
      padding: 10px 12px;
      font-weight: 700;
      color: var(--slate-600);
      border-bottom: 1px solid var(--slate-200);
      white-space: nowrap;
    }
    .table-history td {
      padding: 10px 12px;
      border-bottom: 1px solid #f1f5f9;
      color: var(--slate-800);
    }
    .table-history tr:last-child td {
      border-bottom: none;
    }

    /* Responsive */
    @media (max-width: 767px) {
      .move-page-wrap {
        margin: 1rem auto 6rem auto;
        padding: 0 0.65rem;
      }
      .move-hero {
        padding: 1.15rem 1rem;
      }
      .move-hero h1 {
        font-size: 1.2rem;
      }
      .move-card {
        padding: 1rem;
      }
      .search-input-group {
        display: flex !important;
        flex-direction: column !important;
        gap: 0.75rem !important;
        width: 100% !important;
      }
      .scan-input {
        width: 100% !important;
        min-height: 54px !important;
        height: 54px !important;
        font-size: 1.1rem !important;
        padding: 12px 16px !important;
        border-radius: 12px !important;
        box-sizing: border-box !important;
        display: block !important;
      }
      .btn-scan-action, #btnClearScan {
        width: 100% !important;
        min-height: 52px !important;
        height: 52px !important;
        font-size: 1.05rem !important;
        border-radius: 12px !important;
        justify-content: center !important;
        box-sizing: border-box !important;
      }
      .avail-kpi-card {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
      }
    /* Custom styling for Filter Dropdowns and Select2 */
    .filter-input-custom {
      height: 40px !important;
      border: 1.5px solid var(--slate-200) !important;
      border-radius: 8px !important;
      padding: 6px 12px !important;
      font-size: 0.88rem !important;
      color: var(--slate-800) !important;
      background-color: #ffffff !important;
      outline: none !important;
      box-shadow: none !important;
    }
    .filter-input-custom:focus {
      border-color: var(--primary) !important;
      box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12) !important;
    }
    .select2-container--default .select2-selection--single {
      height: 40px !important;
      border: 1.5px solid var(--slate-200) !important;
      border-radius: 8px !important;
      padding: 5px 8px !important;
      display: flex !important;
      align-items: center !important;
      background-color: #ffffff !important;
    }
    .select2-container--default .select2-selection--single:focus,
    .select2-container--default.select2-container--open .select2-selection--single {
      border-color: var(--primary) !important;
      box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12) !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
      color: var(--slate-800) !important;
      font-size: 0.88rem !important;
      font-weight: 600 !important;
      line-height: 28px !important;
      padding-left: 4px !important;
      padding-right: 20px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
      height: 38px !important;
      right: 8px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__clear {
      margin-right: 18px !important;
      font-size: 1.1rem !important;
      color: #94a3b8 !important;
    }
    .select2-dropdown {
      border: 1.5px solid var(--slate-200) !important;
      border-radius: 8px !important;
      box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.15) !important;
      font-size: 0.88rem !important;
      z-index: 1055 !important;
    }
    .select2-search--dropdown {
      padding: 8px !important;
    }
    .select2-search--dropdown .select2-search__field {
      border: 1.5px solid var(--slate-200) !important;
      border-radius: 6px !important;
      padding: 6px 10px !important;
      font-size: 0.86rem !important;
      outline: none !important;
    }
    .select2-results__option {
      padding: 8px 12px !important;
    }
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
      background-color: var(--primary) !important;
      color: #ffffff !important;
    }
  </style>
</head>
<body>

<div class="move-page-wrap">
  
  <!-- Hero Section -->
  <div class="move-hero d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h1><i class="fa fa-qrcode"></i> Plant Movement QR Scanner</h1>
      <p>Scan QR code or enter Order No. of <b>Ready to Sale</b> or <b>Open Stock</b> plants to view bed details and record plant movements across polyhouse stages.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
      <a href="opensale_stock_review.php" class="btn btn-sm text-white" style="background:#10b981; font-weight:700; border-radius:8px; padding:8px 14px; text-decoration:none; box-shadow:0 2px 6px rgba(0,0,0,0.2);">
        <i class="fa fa-boxes-stacked mr-1"></i> Open Sale Stock Report
      </a>
    </div>
  </div>

  <div class="row" style="row-gap: 1.5rem;">
    
    <!-- Row 1: Scanner & Search (Full Width) -->
    <div class="col-12">
      <div class="move-card">
        <div class="card-head">
          <h2><i class="fa fa-qrcode text-primary"></i> Scan Plant Order QR</h2>
          <span class="badge" style="background:#e0e7ff; color:#3730a3; font-weight:700; font-size:0.75rem; border-radius:6px; padding:4px 8px;">Ready & Open Stock</span>
        </div>

        <!-- Scanner Mode Tabs -->
        <div class="scanner-tabs" style="max-width: 440px;">
          <button type="button" class="scanner-tab-btn active" id="tabManual">
            <i class="fa fa-barcode"></i> Barcode Gun / Text
          </button>
          <button type="button" class="scanner-tab-btn" id="tabCamera">
            <i class="fa fa-video"></i> Camera Scanner
          </button>
        </div>

        <!-- Mode 1: Manual / Barcode Gun Input -->
        <div id="sectionManual">
          <form id="formManualScan" onsubmit="event.preventDefault(); triggerScanLookup();">
            <div class="search-input-group mb-2">
              <input type="text" id="inputBarcodeQuery" class="scan-input" placeholder="Scan or enter RSO Order QR / Code (e.g. RSO-KFP2535, KFP2535, RSO-090800)..." autocomplete="off" autofocus>
              <button type="submit" class="btn-scan-action" id="btnSearchBarcode">
                <i class="fa fa-search"></i> Search
              </button>
              <button type="button" class="btn btn-outline-danger" id="btnClearScan" style="min-height:52px; padding:0 1.25rem; font-weight:700; border-radius:10px; display:inline-flex; align-items:center; justify-content:center; gap:0.45rem; border:1.5px solid #ef4444; color:#ef4444; background:#ffffff;">
                <i class="fa fa-redo"></i> Reset / Clear
              </button>
            </div>
          </form>
          <small class="text-muted d-block" style="font-size: 0.78rem;">
            <i class="fa fa-info-circle mr-1"></i> Scan or enter RSO / OS Order QR code (or Bed QR code printed on order slip). <span class="text-primary font-weight-bold">Only RSO Order QR codes are processed on this page.</span>
          </small>
        </div>

        <!-- Mode 2: Camera Scanner Viewport -->
        <div id="sectionCamera" style="display:none;">
          <div id="qrCameraViewport" style="width:100%; max-width:640px; margin:0 auto; min-height:260px; background:#0f172a; border-radius:12px; overflow:hidden; position:relative;"></div>
          <div class="camera-ctrl-bar d-flex justify-content-center gap-2 flex-wrap mt-2">
            <button type="button" class="btn btn-sm btn-primary" id="btnToggleCamera">
              <i class="fa fa-play mr-1"></i> Start Camera
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnSwitchCamera">
              <i class="fa fa-sync-alt mr-1"></i> Flip Camera
            </button>
            <button type="button" class="btn btn-sm btn-outline-warning" id="btnToggleTorch" style="display:none;">
              <i class="fa fa-lightbulb mr-1"></i> Torch
            </button>
            <button type="button" class="btn btn-sm btn-outline-info" onclick="openPhotoCapture('environment')" title="Take a live photo with phone camera">
              <i class="fa fa-camera mr-1"></i> Snap Photo
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="openPhotoCapture('')" title="Upload QR image or barcode photo from gallery">
              <i class="fa fa-image mr-1"></i> Upload Image
            </button>
            <input type="file" id="inputScanImageFile" accept="image/*" capture="environment" style="position:fixed; top:-2000px; left:-2000px; opacity:0; pointer-events:none;" onchange="handleQrImageUpload(this)">
          </div>
          <div id="cameraStatusMsg" class="small text-muted mt-2 text-center" style="font-size:0.8rem;">
            <i class="fa fa-info-circle text-primary mr-1"></i> Point your phone camera directly at the Order QR Code tag. <span class="text-danger font-weight-bold">(Tray barcodes disabled)</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Row 2: Bed Details & Movement Form (Full Width) -->
    <div class="col-12">
      
      <!-- Empty / Standby Placeholder -->
      <div class="move-card text-center py-5" id="panelStandby">
        <i class="fa fa-qrcode fa-3x text-muted mb-3" style="opacity: 0.4;"></i>
        <h3 class="font-weight-bold text-dark" style="font-size: 1.15rem;">Ready to Scan</h3>
        <p class="text-muted mx-auto" style="max-width: 460px; font-size: 0.88rem;">
          Scan a QR code or enter Order No. above to load Bed details and execute plant tray movement.
        </p>
      </div>

      <!-- Scanned Details & Movement Container -->
      <div class="move-card" id="panelMovement" style="display:none;">
        <div class="card-head">
          <h2><i class="fa fa-leaf text-success"></i> Plant & Bed Details</h2>
          <div id="badgeStockStatus"></div>
        </div>

        <!-- Status Breakdown / No Active Trays Alert Box -->
        <div id="boxNoActiveTraysWarning" style="display:none;"></div>
        <div id="boxActiveBreakdown" style="display:none;"></div>

        <!-- Bed Occupancy KPI Card -->
        <div class="kpi-row-grid">
          <div class="kpi-dual-card card-occupied">
            <div>
              <div class="kpi-info-title"><i class="fa fa-th mr-1"></i> Occupied Trays in Bed</div>
              <small class="kpi-info-sub" id="lblOccupiedPlantsMeta">Total active trays for this order</small>
            </div>
            <div class="kpi-stat-num" id="lblOccupiedTrays">0</div>
          </div>
        </div>

        <!-- Details Grid -->
        <div class="details-box">
          <div class="details-grid">
            <div class="detail-item">
              <span class="detail-label">Bed No</span>
              <span class="detail-val" id="valBedNo">-</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Polyhouse</span>
              <span class="detail-val" id="valPolyhouse">-</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Location</span>
              <span class="detail-val" id="valLocation">-</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Product Name</span>
              <span class="detail-val text-primary" id="valProductName">-</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Stock Order No</span>
              <span class="detail-val mono" id="valStockOrder">-</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">PSO No</span>
              <span class="detail-val mono" id="valPsoNo">-</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Occupied in Bed</span>
              <span class="detail-val text-dark font-weight-bold" id="valOccupiedTrays">-</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Marked Ready to Sell</span>
              <span class="detail-val text-success font-weight-bold" id="valReadyTrays">-</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Growth Stage</span>
              <span class="detail-val text-info font-weight-bold" id="valGrowthStage">-</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Seed Lot / Batch No</span>
              <span class="detail-val" id="valSeedLot">-</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Tray Cavity</span>
              <span class="detail-val" id="valTrayInfo">-</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Sowing Date</span>
              <span class="detail-val" id="valSowingDate">-</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Dispatched Date</span>
              <span class="detail-val" id="valDispatchDate">-</span>
            </div>
          </div>
        </div>
        <!-- Movement Form -->
        <form id="formPlantMovement" onsubmit="event.preventDefault(); submitMovement();">
          <!-- Hidden Context Inputs -->
          <input type="hidden" id="moveBedNo" name="bed_no">
          <input type="hidden" id="moveLocationId" name="locationid">
          <input type="hidden" id="movePolyhouseId" name="polyhouseid">
          <input type="hidden" id="moveStockOrderNo" name="stockorder_no">
          <input type="hidden" id="moveProductId" name="productid">
          <input type="hidden" id="moveStockStatus" name="stock_status">
          <input type="hidden" id="moveMaxTrays" value="0">

          <div class="row" style="row-gap: 1rem;">
            
            <!-- 1. How many trays to move? -->
            <div class="col-md-6 col-12">
              <label class="form-label-custom" id="lblTraysCountTitle">
                How many trays to move? <span class="text-danger">*</span>
              </label>
              <div class="stepper-wrap">
                <button type="button" class="stepper-btn" onclick="stepTrays(-1)"><i class="fa fa-minus"></i></button>
                <input type="number" id="inputTraysToMove" name="trays_to_move" class="stepper-input" min="1" value="1" required oninput="validateTrayNumber(this)">
                <button type="button" class="stepper-btn" onclick="stepTrays(1)"><i class="fa fa-plus"></i></button>
                <button type="button" class="btn-max-trays" id="btnMoveAllTrays" onclick="setMoveAllTrays()">Move All</button>
              </div>
              <small class="text-muted mt-1 d-block" id="lblTraysHint">Enter numeric count only.</small>
            </div>

            <!-- 2. Why are you moved entered tray? (Reason Dropdown) -->
            <div class="col-md-6 col-12">
              <label class="form-label-custom">
                Why are you moved entered tray? <span class="text-danger">*</span>
              </label>
              <select id="selectMovementReason" name="movement_reason" class="form-select-custom" required>
                <option value="">-- Select Movement Reason --</option>
                <option value="Bed Shifting">Bed Shifting</option>
                <option value="Polyhouse Shifting">Polyhouse Shifting</option>
                <option value="Move for Grafting">Move for Grafting</option>
                <option value="Healing">Healing</option>
                <option value="Hardening">Hardening</option>
                <option value="Clip Stick Removal">Clip Stick Removal</option>
                <option value="Empty After Grafting">Empty After Grafting</option>
                <option value="Empty After Shifting">Empty After Shifting</option>
                <option value="Move for Opensale">Move for Opensale</option>
                <option value="Remove After Over Age">Remove After Over Age</option>
                <?php if ($loginroleid < 3): ?>
                <option value="Removed">Removed</option>
                <option value="Add Trays">Add Trays</option>
                <?php endif; ?>
                <option value="Grafted">Grafted</option>
              </select>
            </div>

            <!-- Destination Bed Selection (for Bed Shifting - Same Polyhouse) -->
            <div class="col-md-6" id="boxTargetBed" style="display:none;">
              <label class="form-label-custom">
                Target Bed No (Same Polyhouse) <span class="text-danger">*</span>
              </label>
              <select id="selectTargetBed" name="target_bed_no" class="form-select-custom">
                <option value="">-- Select Destination Bed --</option>
              </select>
              <small class="text-muted mt-1 d-block" id="lblTargetBedHint">Select destination bed in this polyhouse to transfer trays.</small>
            </div>

            <!-- Destination Polyhouse Selection (for Polyhouse Shifting) -->
            <div class="col-md-6" id="boxTargetPolyhouse" style="display:none;">
              <label class="form-label-custom">
                Target Polyhouse <span class="text-danger">*</span>
              </label>
              <select id="selectTargetPolyhouse" name="target_polyhouseid" class="form-select-custom">
                <option value="">-- Select Destination Polyhouse --</option>
              </select>
            </div>

            <!-- Destination Bed for Polyhouse Shifting -->
            <div class="col-md-6" id="boxTargetPolyhouseBed" style="display:none;">
              <label class="form-label-custom">
                Target Bed in Destination Polyhouse <span class="text-danger">*</span>
              </label>
              <select id="selectTargetPolyhouseBed" name="target_polyhouse_bed_no" class="form-select-custom">
                <option value="">-- Select Destination Bed --</option>
              </select>
            </div>

            <!-- Target Open Sale Location (for Move for Opensale) -->
            <div class="col-md-6 col-12" id="boxOpenSaleLocation" style="display:none;">
              <label class="form-label-custom">
                <i class="fa fa-shopping-cart text-success mr-1"></i> Which Open Sale Location? <span class="text-danger">*</span>
              </label>
              <select id="selectOpenSaleLocation" name="open_sale_location" class="form-select-custom">
                <option value="">-- Select Open Sale Location --</option>
              </select>
              <small class="text-muted mt-1 d-block" id="lblOpenSaleLocHint">Select the open sale center where these trays are being placed.</small>
            </div>

            <!-- 3. Movement Narration / Notes -->
            <div class="col-12">
              <label class="form-label-custom" id="lblMovementNarration">
                Movement Narration / Remark <span class="text-muted font-weight-normal" id="lblNarrationReqStatus">(Optional)</span>
              </label>

              <!-- Dynamic Notification & Guidance Notice Box -->
              <div id="narrationNoticeBox" style="display:none; border-radius:8px; padding:10px 14px; margin-bottom:8px;">
                <div class="d-flex align-items-start" style="gap: 10px;">
                  <i id="narrationNoticeIcon" class="fa fa-info-circle mt-0.5" style="font-size: 1.15rem;"></i>
                  <div style="font-size: 0.86rem; line-height: 1.45;">
                    <strong id="narrationNoticeTitle" class="d-block mb-0.5"></strong>
                    <span id="narrationNoticeText"></span>
                  </div>
                </div>
              </div>

              <textarea id="inputCustomNarration" name="custom_narration" class="form-textarea-custom" placeholder="Optional notes for movement record..."></textarea>
              <small class="text-muted d-block mt-1" style="font-size: 0.78rem;">
                <i class="fa fa-check-circle text-success mr-1"></i> System will automatically record your username and local timestamp in column <code>movementremark</code>.
              </small>
            </div>

            <!-- Submit Button -->
            <div class="col-12">
              <button type="submit" class="btn-submit-movement" id="btnSubmitMovement">
                <i class="fa fa-truck-moving"></i> Confirm & Execute Plant Movement
              </button>
            </div>

          </div>
        </form>

      </div>

    </div>

    <!-- Full Width: Recent Movements Activity Log -->
    <div class="col-12">
      <div class="move-card">
        <div class="card-head d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
          <div>
            <h2 class="mb-0"><i class="fa fa-history text-primary"></i> Recent Plant Movements Log</h2>
            <small class="text-muted">Date & Production Order No. wise summary of all tray movements</small>
          </div>
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark border px-2.5 py-1.5" id="logSummaryBadge" style="font-size:0.85rem;">
              <i class="fa fa-layer-group text-primary mr-1"></i> <b id="logTotalTrays">0</b> Trays (<span id="logTotalPlants">0</span> Plants)
            </span>
            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="loadRecentMovements()">
              <i class="fa fa-sync-alt mr-1"></i> Refresh Log
            </button>
          </div>
        </div>

        <!-- Filter Toolbar -->
        <div class="p-3 mb-3 rounded-3 border" style="background:#f8fafc; border-color:#e2e8f0;">
          <div class="row g-2 align-items-end">
            <!-- Order No Filter -->
            <div class="col-lg-3 col-md-4 col-sm-6">
              <label class="form-label mb-1 font-weight-bold" style="font-size:0.8rem; color:#475569;">
                <i class="fa fa-search text-primary mr-1"></i> Order No / PSO
              </label>
              <input type="text" id="filterLogOrderNo" class="form-control filter-input-custom" placeholder="Search Order No / PSO...">
            </div>

            <!-- Product Name Filter -->
            <div class="col-lg-3 col-md-4 col-sm-6">
              <label class="form-label mb-1 font-weight-bold" style="font-size:0.8rem; color:#475569;">
                <i class="fa fa-seedling text-success mr-1"></i> Product Name
              </label>
              <select id="filterLogProduct" class="select2-filter-product" style="width: 100%;">
                <option value="">-- All Products --</option>
              </select>
            </div>

            <!-- Destination / Reason Filter -->
            <div class="col-lg-2 col-md-4 col-sm-6">
              <label class="form-label mb-1 font-weight-bold" style="font-size:0.8rem; color:#475569;">
                <i class="fa fa-tags text-warning mr-1"></i> Destination / Reason
              </label>
              <select id="filterLogReason" class="select2-filter-reason" style="width: 100%;">
                <option value="">-- All Reasons --</option>
              </select>
            </div>

            <!-- Moved By Filter -->
            <div class="col-lg-2 col-md-4 col-sm-6">
              <label class="form-label mb-1 font-weight-bold" style="font-size:0.8rem; color:#475569;">
                <i class="fa fa-user text-secondary mr-1"></i> Movement By
              </label>
              <select id="filterLogMovedBy" class="select2-filter-movedby" style="width: 100%;">
                <option value="">-- All Staff / Users --</option>
              </select>
            </div>

            <!-- Movement Date Filter -->
            <div class="col-lg-2 col-md-4 col-sm-6">
              <label class="form-label mb-1 font-weight-bold" style="font-size:0.8rem; color:#475569;">
                <i class="fa fa-calendar-alt text-info mr-1"></i> Movement Date
              </label>
              <input type="date" id="filterLogDateFrom" class="form-control filter-input-custom">
            </div>

            <!-- Filter Buttons -->
            <div class="col-12 d-flex justify-content-end gap-2 mt-2 pt-2 border-top">
              <button type="button" class="btn btn-sm btn-primary px-3 py-1.5 font-weight-bold d-inline-flex align-items-center gap-1.5" onclick="loadRecentMovements()" style="border-radius:7px; font-size:0.88rem;">
                <i class="fa fa-filter"></i> Filter
              </button>
              <button type="button" class="btn btn-sm btn-outline-secondary px-3 py-1.5 font-weight-bold d-inline-flex align-items-center gap-1.5" onclick="resetLogFilters()" style="border-radius:7px; font-size:0.88rem;" title="Reset Filters">
                <i class="fa fa-undo"></i> Reset
              </button>
            </div>
          </div>
        </div>

        <div class="history-table-wrap">
          <table class="table-history">
            <thead>
              <tr>
                <th style="width:145px;">Date & Time</th>
                <th>Bed No</th>
                <th>Order / PSO</th>
                <th>Product</th>
                <th class="text-center" style="width:130px;">Moved Qty</th>
                <th>Destination / Reason</th>
                <th>Movement Narration</th>
                <th>Moved By</th>
              </tr>
            </thead>
            <tbody id="tblRecentMovementsBody">
              <tr>
                <td colspan="8" class="text-center py-4 text-muted">
                  <i class="fa fa-spinner fa-spin mr-1"></i> Loading movement history...
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>

</div>

<?php 
if (!isset($_GET['dz'])) {
    require('../footer.php'); 
}
?>

<script>
$(function() {
  let activePlantData = null;
  let html5QrCode = null;
  let isCameraScanning = false;
  let currentFacingMode = "environment"; // Always default to back/rear camera on mobile
  let currentCameraId = null;
  let availableCameras = [];

  // Helper to extract clean ID from raw QR/Barcode string, URL, or JSON
  function cleanQrPayload(str) {
    if (!str) return '';
    str = String(str).trim();
    try {
      if ((str.startsWith('{') && str.endsWith('}')) || (str.startsWith('[') && str.endsWith(']'))) {
        const obj = JSON.parse(str);
        if (typeof obj === 'object' && obj !== null) {
          return obj.barcode || obj.bedqrcode || obj.stockorder_no || obj.psono || obj.code || obj.qr_text || obj.bed_no || str;
        }
      }
    } catch(e) {}
    
    if (str.indexOf('http://') === 0 || str.indexOf('https://') === 0 || str.indexOf('://') !== -1) {
      try {
        const url = new URL(str, window.location.origin);
        const params = url.searchParams;
        return params.get('barcode') || params.get('stockorder_no') || params.get('stockorder') || params.get('psono') || params.get('bedqrcode') || params.get('code') || params.get('bed_no') || str;
      } catch(e) {}
    }
    return str.replace(/[\x00-\x1F\x7F\xC2\xA0]/g, '').trim();
  }

  // 1. Tab Switching (Barcode Gun vs Camera)
  $('#tabManual').on('click', function() {
    $(this).addClass('active');
    $('#tabCamera').removeClass('active');
    $('#sectionManual').show();
    $('#sectionCamera').hide();
    stopCamera();
    $('#inputBarcodeQuery').focus();
  });

  $('#tabCamera').on('click', function() {
    $(this).addClass('active');
    $('#tabManual').removeClass('active');
    $('#sectionManual').hide();
    $('#sectionCamera').show();
    currentFacingMode = "environment"; // Enforce back camera when opening camera tab
    currentCameraId = null;
    startCamera();
  });

  // 2. Camera QR Scanner Setup for Mobile & Desktop (Defaults to Back Camera)
  async function startCamera(preferredId = null) {
    if (isCameraScanning) return;
    
    if (typeof Html5Qrcode === 'undefined') {
      Swal.fire('Error', 'QR Scanner library not loaded. Please check your internet connection.', 'error');
      return;
    }

    $('#cameraStatusMsg').html('<i class="fa fa-spinner fa-spin mr-1"></i> Opening back camera...');
    $('#btnToggleCamera').prop('disabled', true);

    try {
      if (html5QrCode) {
        try {
          if (html5QrCode.isScanning) {
            await html5QrCode.stop();
          }
          html5QrCode.clear();
        } catch(e) {}
        html5QrCode = null;
      }

      html5QrCode = new Html5Qrcode("qrCameraViewport");

      const scanConfig = {
        fps: 15,
        qrbox: (viewfinderWidth, viewfinderHeight) => {
          const w = Math.floor(viewfinderWidth * 0.85);
          const h = Math.floor(viewfinderHeight * 0.75);
          return {
            width: Math.max(180, Math.min(320, w)),
            height: Math.max(180, Math.min(320, h))
          };
        },
        aspectRatio: 1.333333
      };

      const onScanSuccess = (decodedText) => {
        playScanBeep();
        stopCamera();
        const cleanPayload = cleanQrPayload(decodedText);
        $('#tabManual').click();
        $('#inputBarcodeQuery').val(cleanPayload);
        lookupQrCode(cleanPayload);
      };

      // 1. Primary Strategy: If specific cameraId chosen use it, otherwise use native { facingMode: "environment" } for back camera
      let cameraConfig = preferredId ? preferredId : { facingMode: "environment" };

      try {
        await html5QrCode.start(cameraConfig, scanConfig, onScanSuccess, () => {});
      } catch (firstErr) {
        console.warn("Direct facingMode: environment failed, querying device list:", firstErr);
        
        // 2. Fallback Strategy: Query cameras list and find the back/rear camera
        const devices = await Html5Qrcode.getCameras();
        if (devices && devices.length > 0) {
          availableCameras = devices;
          
          // Filter specifically for back / rear / environment cameras
          let backCam = devices.find(d => /back|rear|environment|0/i.test(d.label));
          if (!backCam) {
            // Remove front/selfie cameras
            const nonFront = devices.filter(d => !/front|selfie|user/i.test(d.label));
            backCam = nonFront.length > 0 ? nonFront[nonFront.length - 1] : devices[devices.length - 1];
          }
          
          const chosenId = backCam ? backCam.id : devices[0].id;
          currentCameraId = chosenId;
          await html5QrCode.start(chosenId, scanConfig, onScanSuccess, () => {});
        } else {
          throw firstErr;
        }
      }

      isCameraScanning = true;
      $('#btnToggleCamera').prop('disabled', false).html('<i class="fa fa-stop mr-1"></i> Stop Camera').removeClass('btn-primary').addClass('btn-danger');
      $('#cameraStatusMsg').html('<i class="fa fa-check-circle text-success mr-1"></i> Back camera active. Align QR code or Barcode inside viewfinder.');

      // Check available cameras to show/hide flip button
      try {
        const devs = await Html5Qrcode.getCameras();
        if (devs && devs.length > 1) {
          availableCameras = devs;
          $('#btnSwitchCamera').show();
        }
      } catch(e){}

      // Check Torch capability
      try {
        const caps = html5QrCode.getRunningTrackCapabilities();
        if (caps && caps.torch) {
          $('#btnToggleTorch').show();
        } else {
          $('#btnToggleTorch').hide();
        }
      } catch(e) {
        $('#btnToggleTorch').hide();
      }

    } catch (err) {
      console.error("Camera start failed:", err);
      isCameraScanning = false;
      if (html5QrCode) {
        try { html5QrCode.clear(); } catch(e){}
        html5QrCode = null;
      }
      $('#btnToggleCamera').prop('disabled', false).html('<i class="fa fa-play mr-1"></i> Start Camera').removeClass('btn-danger').addClass('btn-primary');
      $('#cameraStatusMsg').html('<span class="text-danger"><i class="fa fa-exclamation-triangle mr-1"></i> Live camera failed. Tap "Snap Photo / Upload" to scan directly.</span>');

      let errMsg = 'Could not access the mobile back camera stream. Please check browser camera permissions or use the "Snap Photo / Upload" button below.';
      if (!window.isSecureContext && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
        errMsg = 'Mobile browsers require HTTPS for live camera video streaming. Please use the "Snap Photo / Upload" button to take a photo directly with your camera.';
      }

      Swal.fire({
        icon: 'info',
        title: 'Camera Access',
        text: errMsg,
        confirmButtonText: 'OK'
      });
    }
  }

  async function stopCamera() {
    if (html5QrCode && isCameraScanning) {
      try {
        if (html5QrCode.isScanning) {
          await html5QrCode.stop();
        }
        html5QrCode.clear();
      } catch (err) {
        console.warn("Camera stop error:", err);
      }
    }
    html5QrCode = null;
    isCameraScanning = false;
    $('#btnToggleCamera').prop('disabled', false).html('<i class="fa fa-play mr-1"></i> Start Camera').removeClass('btn-danger').addClass('btn-primary');
    $('#btnToggleTorch').hide();
    $('#cameraStatusMsg').html('<i class="fa fa-info-circle text-primary mr-1"></i> Point your phone camera directly at the QR Code tag.');
  }

  $('#btnToggleCamera').on('click', function() {
    if (isCameraScanning) {
      stopCamera();
    } else {
      currentFacingMode = "environment";
      startCamera();
    }
  });

  $('#btnSwitchCamera').on('click', async function() {
    if (availableCameras && availableCameras.length > 1) {
      let currIdx = availableCameras.findIndex(c => c.id === currentCameraId);
      let nextIdx = (currIdx + 1) % availableCameras.length;
      currentCameraId = availableCameras[nextIdx].id;
      if (isCameraScanning) {
        await stopCamera();
        await startCamera(currentCameraId);
      }
    } else {
      currentFacingMode = (currentFacingMode === "environment") ? "user" : "environment";
      currentCameraId = null;
      if (isCameraScanning) {
        await stopCamera();
        await startCamera();
      }
    }
  });

  // Torch Toggle Support
  $('#btnToggleTorch').on('click', function() {
    if (!html5QrCode || !isCameraScanning) return;
    isTorchOn = !isTorchOn;
    try {
      html5QrCode.applyVideoConstraints({
        advanced: [{ torch: isTorchOn }]
      }).then(() => {
        $(this).toggleClass('btn-warning btn-outline-warning');
      }).catch(e => {
        console.warn("Torch toggle not supported:", e);
      });
    } catch(e) {}
  });

  // Trigger native photo capture or gallery picker
  window.openPhotoCapture = function(mode) {
    const inp = document.getElementById('inputScanImageFile');
    if (!inp) return;
    if (mode === 'environment') {
      inp.setAttribute('capture', 'environment');
    } else {
      inp.removeAttribute('capture');
    }
    inp.value = '';
    inp.click();
  };

  function readFileAsDataURL(file) {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onload = () => resolve(reader.result);
      reader.onerror = reject;
      reader.readAsDataURL(file);
    });
  }

  function loadImageElement(src) {
    return new Promise((resolve, reject) => {
      const img = new Image();
      img.onload = () => resolve(img);
      img.onerror = reject;
      img.src = src;
    });
  }

  // Multi-engine image decoder (Native BarcodeDetector + jsQR + Html5Qrcode)
  async function decodePhotoFile(file) {
    // Stage 1: Hardware-Accelerated Native BarcodeDetector (Chrome Android / Safari 17+)
    if ('BarcodeDetector' in window) {
      try {
        const formats = ['qr_code', 'code_128', 'code_39', 'code_93', 'ean_13', 'ean_8', 'upc_a', 'upc_e', 'data_matrix', 'itf'];
        const detector = new BarcodeDetector({ formats: formats });
        const imgBitmap = await createImageBitmap(file);
        const results = await detector.detect(imgBitmap);
        if (results && results.length > 0 && results[0].rawValue) {
          console.log("Decoded via native BarcodeDetector:", results[0].rawValue);
          return results[0].rawValue;
        }
      } catch (bdErr) {
        console.warn("BarcodeDetector decode attempt:", bdErr);
      }
    }

    // Stage 2: Canvas Image Processing + Multi-pass jsQR
    try {
      const dataUrl = await readFileAsDataURL(file);
      const img = await loadImageElement(dataUrl);

      const maxDim = 1280;
      let w = img.width;
      let h = img.height;
      if (w > maxDim || h > maxDim) {
        if (w > h) {
          h = Math.round((h * maxDim) / w);
          w = maxDim;
        } else {
          w = Math.round((w * maxDim) / h);
          h = maxDim;
        }
      }

      const canvas = document.createElement('canvas');
      canvas.width = w;
      canvas.height = h;
      const ctx = canvas.getContext('2d');
      ctx.drawImage(img, 0, 0, w, h);

      if (typeof jsQR !== 'undefined') {
        // Pass 2A: Full Image Standard
        const imgData = ctx.getImageData(0, 0, w, h);
        let code = jsQR(imgData.data, w, h, { inversionAttempts: "dontInvert" }) ||
                   jsQR(imgData.data, w, h, { inversionAttempts: "attemptBoth" });
        if (code && code.data) {
          console.log("Decoded via jsQR (full):", code.data);
          return code.data;
        }

        // Pass 2B: Center Crop Focus Area (70% center region)
        const cropW = Math.round(w * 0.70);
        const cropH = Math.round(h * 0.70);
        const cropX = Math.round((w - cropW) / 2);
        const cropY = Math.round((h - cropH) / 2);
        const cropData = ctx.getImageData(cropX, cropY, cropW, cropH);
        let cropCode = jsQR(cropData.data, cropW, cropH, { inversionAttempts: "attemptBoth" });
        if (cropCode && cropCode.data) {
          console.log("Decoded via jsQR (crop):", cropCode.data);
          return cropCode.data;
        }

        // Pass 2C: High-Contrast Grayscale Thresholding
        const grayData = ctx.getImageData(0, 0, w, h);
        const d = grayData.data;
        for (let i = 0; i < d.length; i += 4) {
          const lum = 0.299 * d[i] + 0.587 * d[i + 1] + 0.114 * d[i + 2];
          const val = lum > 120 ? 255 : 0;
          d[i] = val;
          d[i + 1] = val;
          d[i + 2] = val;
        }
        let grayCode = jsQR(grayData.data, w, h, { inversionAttempts: "attemptBoth" });
        if (grayCode && grayCode.data) {
          console.log("Decoded via jsQR (high-contrast):", grayCode.data);
          return grayCode.data;
        }
      }
    } catch (cvErr) {
      console.warn("Canvas / jsQR decode error:", cvErr);
    }

    // Stage 3: Html5Qrcode scanFile with original File object
    if (typeof Html5Qrcode !== 'undefined') {
      try {
        if (!html5QrCode) {
          html5QrCode = new Html5Qrcode("qrCameraViewport");
        }
        const resText = await html5QrCode.scanFile(file, false);
        if (resText) {
          console.log("Decoded via Html5Qrcode scanFile:", resText);
          return resText;
        }
      } catch (h5Err) {
        console.warn("Html5Qrcode scanFile error:", h5Err);
      }
    }

    return null;
  }

  // Handle Photo Snap / File Upload QR Scanning
  window.handleQrImageUpload = async function(input) {
    if (!input.files || input.files.length === 0) return;
    const file = input.files[0];

    Swal.fire({
      title: 'Scanning Photo...',
      text: 'Decoding QR code / Barcode from image...',
      allowOutsideClick: false,
      didOpen: () => { Swal.showLoading(); }
    });

    try {
      const decodedText = await decodePhotoFile(file);
      if (decodedText) {
        Swal.close();
        playScanBeep();
        const cleanPayload = cleanQrPayload(decodedText);
        $('#tabManual').click();
        $('#inputBarcodeQuery').val(cleanPayload);
        lookupQrCode(cleanPayload);
      } else {
        Swal.close();
        Swal.fire({
          icon: 'error',
          title: 'No Code Detected',
          text: 'Could not detect a clear QR Code or Barcode in the photo. Please hold your camera closer to the tag, ensure good lighting, and take another photo.'
        });
      }
    } catch (err) {
      Swal.close();
      console.error("Photo scan error:", err);
      Swal.fire({
        icon: 'error',
        title: 'Scan Error',
        text: 'An error occurred while processing the photo: ' + (err.message || err)
      });
    } finally {
      input.value = '';
    }
  };

  // Audio Beep for Scan Feedback
  function playScanBeep() {
    try {
      const ctx = new (window.AudioContext || window.webkitAudioContext)();
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.type = "sine";
      osc.frequency.setValueAtTime(800, ctx.currentTime);
      gain.gain.setValueAtTime(0.2, ctx.currentTime);
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.start();
      osc.stop(ctx.currentTime + 0.12);
    } catch(e) {}
  }

  // 3. Scan & Lookup Barcode Handler
  window.triggerScanLookup = function() {
    const q = $('#inputBarcodeQuery').val().trim();
    if (!q) {
      Swal.fire('Input Required', 'Please scan or type a barcode / QR code.', 'info');
      $('#inputBarcodeQuery').focus();
      return;
    }
    lookupQrCode(q);
  };

  function lookupQrCode(query) {
    const cleanQuery = cleanQrPayload(query);
    if (!cleanQuery) return;

    const $btn = $('#btnSearchBarcode');
    const origBtn = $btn.html();
    $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
    $('#inputBarcodeQuery').val(cleanQuery);

    $.post('modal/movement_modal.php?action=scan_qr', { query: cleanQuery, raw_query: query }, function(res) {
      $btn.prop('disabled', false).html(origBtn);
      
      if (res.status === 'success' && res.data) {
        if (res.data.stockorder_no && res.data.stockorder_no !== 'N/A') {
          $('#inputBarcodeQuery').val(res.data.stockorder_no);
        }
        renderPlantDetails(res.data);
      } else {
        $('#inputBarcodeQuery').val('').focus();
        Swal.fire({
          icon: 'error',
          title: 'Search Result',
          text: res.message || 'No plant details found for scanned QR / Barcode.'
        });
        resetFormPanel();
      }
    }, 'json').fail(function(xhr, status, error) {
      $btn.prop('disabled', false).html(origBtn);
      Swal.fire('Network Error', 'Unable to connect to server for QR lookup. ' + (error || ''), 'error');
    });
  }

  // 4. Render Scanned Details
  function renderPlantDetails(d) {
    activePlantData = d;

    const occupiedTrays = Number(d.occupied_trays) || 0;
    const readyTrays = Number(d.ready_to_sale_trays) || 0;
    const openTrays = Number(d.available_open_trays) || 0;
    const polyTrays = Number(d.polyhouse_trays) || 0;
    const cavityCount = Number(d.cavity_count) || 126;
    const totalPlants = Number(d.available_plants) || (occupiedTrays * cavityCount);
    const readyPlants = Number(d.ready_plants) || (readyTrays * cavityCount);

    // Status Badges
    let badgeHtml = '';
    if (readyTrays > 0) {
      badgeHtml += `<span class="status-badge ready mr-1" style="margin-right:6px;"><i class="fa fa-boxes mr-1" style="margin-right:5px;"></i> ${readyTrays} Ready to Sell</span> `;
    }
    if (openTrays > 0) {
      badgeHtml += `<span class="status-badge open mr-1" style="margin-right:6px;"><i class="fa fa-seedling mr-1" style="margin-right:5px;"></i> ${openTrays} Open Stock</span> `;
    }
    if (polyTrays > 0 || (occupiedTrays > 0 && readyTrays === 0 && openTrays === 0)) {
      const pCount = polyTrays > 0 ? polyTrays : Math.max(0, occupiedTrays - readyTrays);
      badgeHtml += `<span class="status-badge" style="background:#fef3c7; color:#92400e; border:1px solid #fde68a; margin-right:6px;"><i class="fa fa-layer-group mr-1" style="margin-right:5px;"></i> ${pCount} ${esc(d.growth_stage || 'Polyhouse')}</span> `;
    }
    if (occupiedTrays === 0 && !d.has_active_trays) {
      badgeHtml = `<span class="status-badge" style="background:#fee2e2; color:#b91c1c; border:1px solid #fca5a5;"><i class="fa fa-ban mr-1" style="margin-right:5px;"></i> No Active Trays</span>`;
    }
    $('#badgeStockStatus').html(badgeHtml || `<span class="status-badge ready"><i class="fa fa-boxes mr-1" style="margin-right:5px;"></i> Active</span>`);

    // Dual KPI Cards
    $('#lblOccupiedTrays').text(occupiedTrays.toLocaleString());
    $('#lblOccupiedPlantsMeta').text(occupiedTrays > 0 ? `(${totalPlants.toLocaleString()} total plants at ${cavityCount} cavity/tray)` : 'No active trays remaining in bed');
    $('#lblReadyTrays').text(readyTrays.toLocaleString());
    $('#lblReadyPlantsMeta').text(`(${readyPlants.toLocaleString()} plants marked Ready to Sell)`);

    // Detail Fields
    $('#valBedNo').html(`<span class="badge bg-dark text-white px-2 py-1" style="font-size:0.9rem;">${esc(d.bed_no)}</span>`);
    $('#valPolyhouse').text(d.polyhousename || 'N/A');
    $('#valLocation').text(d.locationname || 'N/A');
    $('#valProductName').text(d.productname || 'Plant');
    $('#valStockOrder').text(d.stockorder_no || 'N/A');
    $('#valPsoNo').text(d.psono || 'N/A');
    $('#valOccupiedTrays').text(`${occupiedTrays.toLocaleString()} Tray(s) (${totalPlants.toLocaleString()} Plants)`);
    $('#valReadyTrays').text(`${readyTrays.toLocaleString()} Tray(s) (${readyPlants.toLocaleString()} Plants)`);
    $('#valGrowthStage').text(d.growth_stage || d.raw_status || 'Polyhouse');
    $('#valSeedLot').text(d.seed_lot_no || '-');
    $('#valTrayInfo').text(`${d.tray_name} (${cavityCount} Cavity)`);
    $('#valSowingDate').text(d.sowing_date || '-');
    $('#valDispatchDate').text(d.dispatch_date || '-');

    // Build Status Breakdown badges
    const sb = d.status_breakdown || {};
    let sbBadgesHtml = '';
    const sbKeys = Object.keys(sb);
    if (sbKeys.length > 0) {
      sbBadgesHtml += '<div class="d-flex flex-wrap align-items-center" style="gap: 10px; margin-top: 6px;">';
      sbKeys.forEach(k => {
        const count = sb[k];
        let badgeStyle = 'background:#475569; color:#ffffff;';
        let icon = 'fa-tag';
        const kLower = k.toLowerCase();
        if (kLower === 'readytosale' || kLower === 'ready to sale') {
          badgeStyle = 'background:#059669; color:#ffffff;';
          icon = 'fa-boxes';
        } else if (kLower === 'openstock' || kLower === 'open stock') {
          badgeStyle = 'background:#0284c7; color:#ffffff;';
          icon = 'fa-seedling';
        } else if (kLower === 'polyhouse') {
          badgeStyle = 'background:#d97706; color:#ffffff;';
          icon = 'fa-layer-group';
        } else if (kLower === 'removed' || kLower.includes('over age')) {
          badgeStyle = 'background:#dc2626; color:#ffffff;';
          icon = 'fa-trash-alt';
        } else if (kLower === 'final dispatch') {
          badgeStyle = 'background:#2563eb; color:#ffffff;';
          icon = 'fa-truck';
        } else if (kLower.includes('shift')) {
          badgeStyle = 'background:#0f172a; color:#ffffff;';
          icon = 'fa-exchange-alt';
        }
        sbBadgesHtml += `<span class="badge shadow-sm d-inline-flex align-items-center" style="${badgeStyle} font-size:0.86rem; border-radius:6px; font-weight:700; padding: 6px 12px; margin-right: 8px; margin-bottom: 8px;">
          <i class="fa ${icon} mr-1" style="margin-right: 7px;"></i> ${esc(k)}:&nbsp;<b>${count.toLocaleString()} Tray(s)</b>
        </span>`;
      });
      sbBadgesHtml += '</div>';
    }

    if (!d.has_active_trays || occupiedTrays === 0) {
      $('#boxNoActiveTraysWarning').html(`
        <div class="alert alert-warning border-2 p-3 mb-3 shadow-sm" style="border-radius:12px; background:#fffbeb; border: 2px solid #f59e0b;">
          <div class="d-flex align-items-center mb-1">
            <i class="fa fa-exclamation-triangle text-warning mr-2" style="font-size:1.3rem; margin-right:8px;"></i>
            <strong style="color:#92400e; font-size:1.02rem;">कोई सक्रिय ट्रे उपलब्ध नहीं है (No Active Trays for Movement)</strong>
          </div>
          <p class="mb-2" style="font-size:0.88rem; color:#78350f;">
            इस बेड (<b>${esc(d.bed_no)}</b>) / ऑर्डर (<b>${esc(d.stockorder_no)}</b>) में <b>Polyhouse</b> या <b>Readytosale</b> स्थिति की कोई सक्रिय ट्रे नहीं बची है। स्थिति अनुसार सभी ट्रे का विवरण (Full Tray Count by Status):
          </p>
          ${sbBadgesHtml}
        </div>
      `).show();
      $('#boxActiveBreakdown').empty().hide();
      $('#formPlantMovement').hide();
    } else {
      $('#boxNoActiveTraysWarning').empty().hide();
      $('#boxActiveBreakdown').html(`
        <div class="mb-3 rounded-3" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding: 12px 14px;">
          <div style="font-size:0.78rem; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:0.5px; margin-bottom: 10px;">
            <i class="fa fa-chart-pie text-primary mr-1" style="margin-right: 6px;"></i> All Trays Status Breakdown in Bed
          </div>
          ${sbBadgesHtml}
        </div>
      `).show();
      $('#formPlantMovement').show();
    }

    // Populate Hidden Movement Context
    $('#moveBedNo').val(d.bed_no);
    $('#moveLocationId').val(d.locationid);
    $('#movePolyhouseId').val(d.polyhouseid);
    $('#moveStockOrderNo').val(d.stockorder_no);
    $('#moveProductId').val(d.productid);
    $('#moveStockStatus').val(d.status);

    // Setup Trays To Move input & Movement Button
    const availForMovement = Number(d.available_trays) > 0 ? Number(d.available_trays) : occupiedTrays;
    $('#moveMaxTrays').val(availForMovement);

    if (availForMovement > 0) {
      $('#inputTraysToMove').attr('max', availForMovement).val(1).prop('disabled', false);
      $('#btnSubmitMovement').prop('disabled', false).html('<i class="fa fa-truck-moving mr-1"></i> Confirm & Execute Plant Movement');
      $('#lblTraysHint').html(`Max available: <b>${availForMovement}</b> active tray(s) in ${esc(d.bed_no)}.`);
      $('#btnMoveAllTrays').prop('disabled', false);
    } else {
      $('#inputTraysToMove').attr('max', 0).val(0).prop('disabled', true);
      $('#btnSubmitMovement').prop('disabled', true).html('<i class="fa fa-ban mr-1"></i> No Trays Available for Movement');
      $('#lblTraysHint').html('<span class="text-danger font-weight-bold"><i class="fa fa-exclamation-triangle mr-1"></i> No active trays available in this bed for movement.</span>');
      $('#btnMoveAllTrays').prop('disabled', true);
    }

    // Reset Reason & Narration & Target Selector Boxes
    updateReasonDropdown();
    $('#selectMovementReason').val('');
    $('#boxTargetBed').hide();
    $('#boxTargetPolyhouse').hide();
    $('#boxTargetPolyhouseBed').hide();
    $('#boxOpenSaleLocation').hide();
    $('#selectOpenSaleLocation').val('').prop('required', false);
    $('#selectTargetBed').empty().append('<option value="">-- Select Destination Bed --</option>');
    $('#selectTargetPolyhouse').empty().append('<option value="">-- Select Destination Polyhouse --</option>');
    $('#selectTargetPolyhouseBed').empty().append('<option value="">-- Select Destination Bed --</option>');
    $('#inputCustomNarration').val('').attr('placeholder', 'Optional notes for movement record...');
    $('#lblNarrationReqStatus').html('<span class="text-muted font-weight-normal">(Optional)</span>');
    $('#narrationNoticeBox').hide();

    // Switch view
    $('#panelStandby').hide();
    $('#panelMovement').slideDown(200);

    // Scroll to movement form on mobile
    if (window.innerWidth < 768) {
      $('html, body').animate({
        scrollTop: $('#panelMovement').offset().top - 70
      }, 300);
    }
  }

  function resetFormPanel() {
    activePlantData = null;
    $('#panelMovement').hide();
    $('#panelStandby').slideDown(200);
    $('#formPlantMovement')[0].reset();
    $('#formPlantMovement').show();
    $('#boxNoActiveTraysWarning').empty().hide();
    $('#boxActiveBreakdown').empty().hide();
    $('#lblOccupiedTrays').text('0');
    $('#lblReadyTrays').text('0');
    $('#valOccupiedTrays').text('-');
    $('#valReadyTrays').text('-');
    $('#valGrowthStage').text('-');
    $('#boxTargetBed').hide();
    $('#boxTargetPolyhouse').hide();
    $('#boxTargetPolyhouseBed').hide();
    $('#boxOpenSaleLocation').hide();
    $('#selectOpenSaleLocation').val('').prop('required', false);
    $('#selectTargetBed').empty().append('<option value="">-- Select Destination Bed --</option>');
    $('#selectTargetPolyhouse').empty().append('<option value="">-- Select Destination Polyhouse --</option>');
    $('#selectTargetPolyhouseBed').empty().append('<option value="">-- Select Destination Bed --</option>');
    $('#lblNarrationReqStatus').html('<span class="text-muted font-weight-normal">(Optional)</span>');
    $('#narrationNoticeBox').hide();
    $('#inputCustomNarration').attr('placeholder', 'Optional notes for movement record...');
    $('#badgeStockStatus').empty();
  }

  // 4.5 Mark Trays as Ready to Sell Actions (Premium UI)
  window.rtsStep = function(delta) {
    const inp = document.getElementById('swalMarkTraysInput');
    if (!inp) return;
    let v = parseInt(inp.value, 10) || 1;
    v += delta;
    if (v < 1) v = 1;
    if (v > (window.rtsMaxMarkable || 1)) v = window.rtsMaxMarkable;
    inp.value = v;
    window.rtsUpdateCalc(v);
  };

  window.rtsSetMax = function() {
    const inp = document.getElementById('swalMarkTraysInput');
    if (!inp) return;
    inp.value = window.rtsMaxMarkable;
    window.rtsUpdateCalc(window.rtsMaxMarkable);
  };

  window.rtsUpdateCalc = function(val) {
    const v = parseInt(val, 10) || 0;
    const calcEl = document.getElementById('rtsDynamicCalc');
    if (calcEl) {
      calcEl.textContent = (v * (window.rtsCavity || 126)).toLocaleString();
    }
    const errEl = document.getElementById('rtsErrorMsg');
    if (errEl) {
      if (v > window.rtsMaxMarkable) {
        errEl.textContent = `Cannot exceed ${window.rtsMaxMarkable} available trays.`;
        errEl.style.display = 'block';
      } else if (v < 1) {
        errEl.textContent = 'Please enter at least 1 tray.';
        errEl.style.display = 'block';
      } else {
        errEl.style.display = 'none';
      }
    }
  };

  window.rtsSubmitConfirm = function() {
    const inp = document.getElementById('swalMarkTraysInput');
    const errEl = document.getElementById('rtsErrorMsg');
    if (!inp) return;
    const v = parseInt(inp.value, 10);
    if (isNaN(v) || v <= 0) {
      if (errEl) {
        errEl.textContent = 'Please enter a valid number of trays (at least 1).';
        errEl.style.display = 'block';
      }
      inp.focus();
      return;
    }
    if (v > window.rtsMaxMarkable) {
      if (errEl) {
        errEl.textContent = `You can only mark up to ${window.rtsMaxMarkable} trays in this bed.`;
        errEl.style.display = 'block';
      }
      inp.focus();
      return;
    }
    Swal.close();
    executeMarkReadyToSale(v);
  };

  window.promptMarkReadyToSale = function() {
    if (!activePlantData) return;
    const occupied = Number(activePlantData.occupied_trays) || 0;
    const ready = Number(activePlantData.ready_to_sale_trays) || 0;
    const poly = Number(activePlantData.polyhouse_trays) || 0;
    const maxMarkable = poly > 0 ? poly : Math.max(0, occupied - ready);
    const cavity = Number(activePlantData.cavity_count) || 126;

    if (maxMarkable <= 0) {
      Swal.fire({
        icon: 'info',
        title: 'All Trays Ready',
        text: 'All occupied trays in this bed are already marked as Ready to Sell!',
        confirmButtonColor: '#10b981'
      });
      return;
    }

    const initialPlants = (maxMarkable * cavity).toLocaleString();

    Swal.fire({
      width: '520px',
      showConfirmButton: false,
      showCancelButton: false,
      customClass: {
        popup: 'swal2-rts-popup',
        htmlContainer: 'swal2-rts-container'
      },
      html: `
        <div class="rts-modal-wrap">
          <div class="rts-header">
            <div class="rts-icon-pill">
              <i class="fa fa-boxes"></i>
            </div>
            <div>
              <h3 class="rts-title">Mark Trays as Ready to Sell</h3>
              <p class="rts-subtitle">Allocate occupied trays to Ready to Sell status for sales orders & dispatch</p>
            </div>
          </div>

          <div class="rts-meta-card">
            <div class="rts-meta-grid">
              <div class="rts-meta-item">
                <span class="rts-meta-tag">Bed</span>
                <span class="rts-badge-bed">${esc(activePlantData.bed_no)}</span>
              </div>
              <div class="rts-meta-item">
                <span class="rts-meta-tag">Order / PSO</span>
                <span class="rts-mono-order">${esc(activePlantData.stockorder_no || activePlantData.psono)}</span>
              </div>
            </div>
            <div class="rts-meta-product">
              <span class="rts-meta-tag">Product</span>
              <div class="rts-product-name">${esc(activePlantData.productname)}</div>
            </div>
          </div>

          <div class="rts-stats-row">
            <div class="rts-stat-chip stat-occ">
              <span class="rts-chip-label">Occupied</span>
              <span class="rts-chip-val">${occupied}</span>
              <span class="rts-chip-unit">trays in bed</span>
            </div>
            <div class="rts-stat-chip stat-ready">
              <span class="rts-chip-label">Ready to Sell</span>
              <span class="rts-chip-val">${ready}</span>
              <span class="rts-chip-unit">already marked</span>
            </div>
            <div class="rts-stat-chip stat-avail">
              <span class="rts-chip-label">Can Mark</span>
              <span class="rts-chip-val">${maxMarkable}</span>
              <span class="rts-chip-unit">available now</span>
            </div>
          </div>

          <div class="rts-input-box">
            <div class="rts-label-row">
              <label class="rts-field-label" for="swalMarkTraysInput">Enter number of trays to mark:</label>
              <button type="button" class="rts-btn-setmax" onclick="window.rtsSetMax()">
                <i class="fa fa-arrow-up mr-1"></i>Set Max (${maxMarkable})
              </button>
            </div>
            <div class="rts-stepper-container">
              <button type="button" class="rts-stepper-btn" onclick="window.rtsStep(-1)"><i class="fa fa-minus"></i></button>
              <input type="number" id="swalMarkTraysInput" class="rts-custom-input" min="1" max="${maxMarkable}" value="${maxMarkable}" oninput="window.rtsUpdateCalc(this.value)" onkeydown="if(event.key==='Enter'){event.preventDefault(); window.rtsSubmitConfirm();}">
              <button type="button" class="rts-stepper-btn" onclick="window.rtsStep(1)"><i class="fa fa-plus"></i></button>
            </div>
            <div class="rts-calc-hint">
              <i class="fa fa-info-circle text-success"></i> Marking <b id="rtsDynamicCalc">${initialPlants}</b> plants (${cavity} cavity/tray)
            </div>
            <div id="rtsErrorMsg" style="color:#ef4444; font-size:0.8rem; font-weight:700; margin-top:4px; display:none;"></div>
          </div>

          <div class="rts-actions-row">
            <button type="button" class="rts-btn-cancel" onclick="Swal.close()">Cancel</button>
            <button type="button" class="rts-btn-submit" onclick="window.rtsSubmitConfirm()">
              <i class="fa fa-check-circle"></i> Confirm & Mark Ready
            </button>
          </div>
        </div>
      `,
      didOpen: () => {
        window.rtsMaxMarkable = maxMarkable;
        window.rtsCavity = cavity;
        const inp = document.getElementById('swalMarkTraysInput');
        if (inp) {
          inp.focus();
          inp.select();
        }
      }
    });
  };

  function executeMarkReadyToSale(traysToMark) {
    if (!activePlantData) return;

    Swal.fire({
      title: 'Updating Trays...',
      text: 'Marking trays as Ready to Sell in database...',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });

    $.post('modal/movement_modal.php?action=mark_ready_to_sale', {
      bed_no: activePlantData.bed_no,
      locationid: activePlantData.locationid,
      polyhouseid: activePlantData.polyhouseid,
      stockorder_no: activePlantData.stockorder_no,
      psono: activePlantData.psono,
      productid: activePlantData.productid,
      trays_to_mark: traysToMark
    }, function(res) {
      if (res && res.status === 'success') {
        Swal.fire({
          icon: 'success',
          title: 'Marked Ready to Sell!',
          text: res.message || `${traysToMark} tray(s) successfully marked as Ready to Sell.`,
          timer: 2000,
          showConfirmButton: true
        }).then(() => {
          const query = activePlantData.barcode || activePlantData.bed_no || $('#inputBarcodeQuery').val();
          if (query) {
            lookupQrCode(query);
          }
        });
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Failed to Mark Trays',
          text: (res && res.message) ? res.message : 'An error occurred while marking trays.'
        });
      }
    }, 'json').fail(function(xhr, status, error) {
      Swal.fire('Network Error', 'Server communication error: ' + (error || ''), 'error');
    });
  }

  // Load Open Sale Locations Dropdown
  function loadOpenSaleLocationsDropdown(callback) {
    const $sel = $('#selectOpenSaleLocation');
    if ($sel.children('option').length > 1) {
      if (typeof callback === 'function') callback();
      return;
    }
    $.getJSON('modal/movement_modal.php?action=get_opensale_locations', function(res) {
      if (res && res.status === 'success' && res.data && res.data.length) {
        let opts = '<option value="">-- Select Open Sale Location --</option>';
        res.data.forEach(function(item) {
          opts += `<option value="${esc(item.polyhousename)}" data-polyhouseid="${esc(item.polyhouseid)}" data-locationid="${esc(item.locationid)}" data-enum="${esc(item.enum_location)}">${esc(item.polyhousename)}</option>`;
        });
        $sel.html(opts);
      } else {
        $sel.html('<option value="">-- No Open Sale Location Found --</option>');
      }
      if (typeof callback === 'function') callback();
    }).fail(function() {
      $sel.html('<option value="">-- Error Loading Open Sale Locations --</option>');
    });
  }

  // Dynamic Narration Guidance on Movement Reason Change
  $('#selectMovementReason').on('change', function() {
    const reason = $(this).val();
    const $lblReq = $('#lblNarrationReqStatus');
    const $notice = $('#narrationNoticeBox');
    const $title = $('#narrationNoticeTitle');
    const $text = $('#narrationNoticeText');
    const $icon = $('#narrationNoticeIcon');
    const $textarea = $('#inputCustomNarration');

    // Hide target selectors initially
    $('#boxTargetBed').hide();
    $('#boxTargetPolyhouse').hide();
    $('#boxTargetPolyhouseBed').hide();
    $('#boxOpenSaleLocation').hide();
    $('#selectOpenSaleLocation').prop('required', false);

    if (reason === 'Internal Dispatch') {
      $lblReq.html('<span class="text-danger font-weight-bold" style="color:#b45309!important;">* (Required for Internal Dispatch)</span>');
      $notice.css({
        'background': '#fffbeb',
        'border': '1.5px solid #fcd34d',
        'color': '#92400e',
        'display': 'block'
      });
      $icon.attr('class', 'fa fa-exclamation-circle text-warning mt-0.5');
      $title.html('<i class="fa fa-map-marker-alt mr-1"></i> Internal Dispatch Details Required:');
      $text.html('Please specify <b>where to move the plants</b> (destination polyhouse / bed / chamber) and <b>why you are moving</b> the entered trays.');
      $textarea.attr('placeholder', 'e.g. Moving to Polyhouse 04 / Bed 02 for hardening & observation due to weather shift...').focus();
    } else if (reason === 'Final Dispatch') {
      $lblReq.html('<span class="text-danger font-weight-bold" style="color:#1d4ed8!important;">* (Required for Final Dispatch)</span>');
      $notice.css({
        'background': '#eff6ff',
        'border': '1.5px solid #93c5fd',
        'color': '#1e40af',
        'display': 'block'
      });
      $icon.attr('class', 'fa fa-truck text-primary mt-0.5');
      $title.html('<i class="fa fa-file-invoice-dollar mr-1"></i> Final Dispatch Client & Order Details Required:');
      $text.html('Please write the <b>Client Full Details</b> with <b>Order No.</b>, delivery vehicle / transport info, and <b>Payment Clearance</b> confirmation.');
      $textarea.attr('placeholder', 'e.g. Client: Ramesh Patel (Farmer ID: FP-102), Order No: PO-8842, Transport: GJ-01-XX-1234, Payment Clearance: 100% Paid / Cash Receipt #4459...').focus();
    } else if (reason === 'Bed Shifting') {
      $lblReq.html('<span class="text-muted font-weight-normal">(Optional)</span>');
      $notice.css({
        'background': '#f0fdf4',
        'border': '1.5px solid #86efac',
        'color': '#166534',
        'display': 'block'
      });
      $icon.attr('class', 'fa fa-exchange-alt text-success mt-0.5');
      $title.html('<i class="fa fa-arrows-alt mr-1"></i> Bed Shifting within Same Polyhouse:');
      $text.html('Select the <b>destination Bed No.</b> below. The system will update the bed location for the selected trays in <b>' + esc(activePlantData ? (activePlantData.polyhousename || 'Polyhouse') : '') + '</b>.');
      $textarea.attr('placeholder', 'Optional notes for bed transfer (e.g. shifted for better sunlight exposure / spacing)...');
      
      // Populate target bed dropdown
      const $selBed = $('#selectTargetBed');
      $selBed.empty().append('<option value="">-- Select Destination Bed --</option>');
      const currBed = (activePlantData ? activePlantData.bed_no : '') || '';
      const beds = (activePlantData && activePlantData.polyhouse_beds) ? activePlantData.polyhouse_beds : [];
      
      let addedCount = 0;
      beds.forEach(function(b) {
        if (b && String(b).trim().toLowerCase() !== String(currBed).trim().toLowerCase()) {
          $selBed.append($('<option>', { value: b, text: b }));
          addedCount++;
        }
      });
      
      // Fallback if no beds preloaded
      if (addedCount === 0) {
        for (let i = 1; i <= 30; i++) {
          const fallbackBed = 'Bed-' + i;
          if (fallbackBed.toLowerCase() !== String(currBed).toLowerCase()) {
            $selBed.append($('<option>', { value: fallbackBed, text: fallbackBed }));
          }
        }
      }
      $('#boxTargetBed').slideDown(150);
    } else if (reason === 'Polyhouse Shifting' || reason === 'Hardening') {
      const isHardening = (reason === 'Hardening');
      $lblReq.html('<span class="text-muted font-weight-normal">(Optional)</span>');
      $notice.css({
        'background': isHardening ? '#f0fdf4' : '#f5f3ff',
        'border': isHardening ? '1.5px solid #86efac' : '1.5px solid #c4b5fd',
        'color': isHardening ? '#166534' : '#5b21b6',
        'display': 'block'
      });
      $icon.attr('class', isHardening ? 'fa fa-seedling text-success mt-0.5' : 'fa fa-warehouse text-primary mt-0.5');
      $title.html(isHardening ? '<i class="fa fa-seedling mr-1"></i> Hardening Transfer:' : '<i class="fa fa-building mr-1"></i> Polyhouse Shifting:');
      $text.html(isHardening 
        ? 'Select the <b>target Polyhouse</b> and <b>destination Bed No.</b> to transfer trays for Hardening.' 
        : 'Select the <b>target Polyhouse</b> and <b>destination Bed No.</b> to shift the trays.');
      $textarea.attr('placeholder', isHardening ? 'Optional notes for hardening transfer...' : 'Optional notes for polyhouse transfer...');

      // Populate target polyhouse dropdown
      const $selPh = $('#selectTargetPolyhouse');
      $selPh.empty().append('<option value="">-- Select Destination Polyhouse --</option>');
      const currPhId = (activePlantData ? activePlantData.polyhouseid : '') || '';
      const polyhouses = (activePlantData && activePlantData.all_polyhouses) ? activePlantData.all_polyhouses : [];

      polyhouses.forEach(function(ph) {
        if (isHardening) {
          const isCurrent = (String(ph.polyhouseid) === String(currPhId)) ? ' (Current)' : '';
          $selPh.append($('<option>', { value: ph.polyhouseid, text: ph.polyhousename + isCurrent }));
        } else {
          if (String(ph.polyhouseid) !== String(currPhId)) {
            $selPh.append($('<option>', { value: ph.polyhouseid, text: ph.polyhousename }));
          }
        }
      });
      $('#selectTargetPolyhouseBed').empty().append('<option value="">-- Select Destination Bed --</option>');
      $('#boxTargetPolyhouse').slideDown(150);
      $('#boxTargetPolyhouseBed').slideDown(150);
    } else if (reason === 'Empty After Grafting' || reason === 'Destroy After grafted') {
      $lblReq.html('<span class="text-muted font-weight-normal">(Optional)</span>');
      $notice.css({
        'background': '#fef2f2',
        'border': '1.5px solid #fca5a5',
        'color': '#991b1b',
        'display': 'block'
      });
      $icon.attr('class', 'fa fa-ban text-danger mt-0.5');
      $title.html('<i class="fa fa-ban mr-1"></i> Empty After Grafting:');
      $text.html('Selected trays will be marked as <b>Empty After Grafting</b> in plant inventory records.');
      $textarea.attr('placeholder', 'Optional remarks / loss reason...');
    } else if (reason === 'Empty After Shifting' || reason === 'Tray Free After Shifting') {
      $lblReq.html('<span class="text-muted font-weight-normal">(Optional)</span>');
      $notice.css({
        'background': '#f0fdfa',
        'border': '1.5px solid #5eead4',
        'color': '#115e59',
        'display': 'block'
      });
      $icon.attr('class', 'fa fa-check-circle text-success mt-0.5');
      $title.html('<i class="fa fa-boxes mr-1"></i> Empty After Shifting:');
      $text.html('Selected trays will be marked as <b>Empty After Shifting</b> and vacated.');
      $textarea.attr('placeholder', 'Optional remarks...');
    } else if (reason === 'Move for Opensale') {
      $lblReq.html('<span class="text-muted font-weight-normal">(Optional)</span>');
      $notice.css({
        'background': '#f0fdf4',
        'border': '1.5px solid #86efac',
        'color': '#166534',
        'display': 'block'
      });
      $icon.attr('class', 'fa fa-store text-success mt-0.5');
      $title.html('<i class="fa fa-store mr-1"></i> Move for Opensale:');
      $text.html('Selected trays will be marked as <b>Move for Opensale</b> and synchronized into <b>Open Sale Stocks</b> inventory for direct sales.');
      $textarea.attr('placeholder', 'Optional notes for open sale transfer...');
      $('#boxOpenSaleLocation').slideDown(150);
      $('#selectOpenSaleLocation').prop('required', true);
      loadOpenSaleLocationsDropdown();
    } else if (reason === 'Remove After Over Age') {
      $lblReq.html('<span class="text-muted font-weight-normal">(Optional)</span>');
      $notice.css({
        'background': '#fff1f2',
        'border': '1.5px solid #fecdd3',
        'color': '#9f1239',
        'display': 'block'
      });
      $icon.attr('class', 'fa fa-hourglass-end text-danger mt-0.5');
      $title.html('<i class="fa fa-hourglass-end mr-1"></i> Remove After Over Age:');
      $text.html('Selected trays will be marked as <b>Remove After Over Age</b>. Production order quantity will be reduced and active ready stock records will be removed.');
      $textarea.attr('placeholder', 'Optional notes / reason for discarding over-age plants...');
    } else if (reason === 'Removed') {
      $lblReq.html('<span class="text-muted font-weight-normal">(Optional)</span>');
      $notice.css({
        'background': '#fef2f2',
        'border': '1.5px solid #fca5a5',
        'color': '#991b1b',
        'display': 'block'
      });
      $icon.attr('class', 'fa fa-trash-alt text-danger mt-0.5');
      $title.html('<i class="fa fa-trash-alt mr-1"></i> Removed (Deduct Order Quantity):');
      $text.html('Selected trays will be permanently marked as <b>Removed</b>. Production order quantity will be deducted and corresponding ready stock deleted.');
      $textarea.attr('placeholder', 'Reason for removing plants from inventory...');
    } else if (reason === 'Add Trays') {
      $lblReq.html('<span class="text-muted font-weight-normal">(Optional)</span>');
      $notice.css({
        'background': '#f0fdf4',
        'border': '1.5px solid #86efac',
        'color': '#166534',
        'display': 'block'
      });
      $icon.attr('class', 'fa fa-plus-circle text-success mt-0.5');
      $title.html('<i class="fa fa-plus-circle mr-1"></i> Add Trays (Manual Stock Increment):');
      $text.html('Enter how many new trays to add. The system will <b>increase quantity in Production Stock Order</b>, <b>insert new tray rows with barcodes in Plant Details</b>, and <b>insert into Ready Stock</b> (if applicable).');
      $textarea.attr('placeholder', 'Optional notes for adding trays (e.g. extra trays found, count correction)...');
    } else {
      $lblReq.html('<span class="text-muted font-weight-normal">(Optional)</span>');
      $notice.hide();
      $textarea.attr('placeholder', 'Optional notes for movement record...');
    }

    // Dynamic UI adjust for Add Trays
    const isAddTrays = (reason === 'Add Trays');
    if (isAddTrays) {
      $('#lblTraysCountTitle').html('How many trays to add? <span class="text-danger">*</span>');
      $('#lblTraysHint').text('Enter numeric count of new trays to add.');
      $('#btnMoveAllTrays').hide();
      $('#btnSubmitMovement').html('<i class="fa fa-plus-circle mr-1"></i> Confirm & Add Trays');
    } else {
      $('#lblTraysCountTitle').html('How many trays to move? <span class="text-danger">*</span>');
      const maxAvail = parseInt($('#moveMaxTrays').val(), 10) || 0;
      const curBed = $('#moveBedNo').val() || '';
      $('#lblTraysHint').html(`Max available: <b>${maxAvail}</b> tray(s) in ${esc(curBed)}.`);
      $('#btnMoveAllTrays').show();
      $('#btnSubmitMovement').html('<i class="fa fa-truck-moving mr-1"></i> Confirm & Execute Plant Movement');
    }
  });

  // When Destination Polyhouse changes in Polyhouse Shifting, fetch its beds
  $('#selectTargetPolyhouse').on('change', function() {
    const phId = $(this).val();
    const $selBed = $('#selectTargetPolyhouseBed');
    $selBed.empty().append('<option value="">-- Select Destination Bed --</option>');
    if (!phId) return;

    $selBed.prop('disabled', true);
    $.post('modal/movement_modal.php?action=get_polyhouse_beds', { polyhouseid: phId }, function(res) {
      $selBed.prop('disabled', false);
      if (res.status === 'success' && res.beds && res.beds.length) {
        res.beds.forEach(function(b) {
          $selBed.append($('<option>', { value: b, text: b }));
        });
      } else {
        for (let i = 1; i <= 30; i++) {
          $selBed.append($('<option>', { value: 'Bed-' + i, text: 'Bed-' + i }));
        }
      }
    }, 'json').fail(function() {
      $selBed.prop('disabled', false);
      for (let i = 1; i <= 30; i++) {
        $selBed.append($('<option>', { value: 'Bed-' + i, text: 'Bed-' + i }));
      }
    });
  });

  $('#btnClearScan').on('click', function() {
    $('#inputBarcodeQuery').val('').focus();
    resetFormPanel();
  });

  // Dynamic Reason Dropdown filter based on Move All vs partial
  window.updateReasonDropdown = function() {
    const max = parseInt($('#moveMaxTrays').val(), 10) || 0;
    const count = parseInt($('#inputTraysToMove').val(), 10) || 1;
    const isMoveAll = (max > 0 && count >= max);
    const $sel = $('#selectMovementReason');
    const curVal = $sel.val();

    let opts = '<option value="">-- Select Movement Reason --</option>';
    const reasons = [
      'Bed Shifting',
      'Polyhouse Shifting',
      'Move for Grafting',
      'Healing',
      'Hardening',
      'Clip Stick Removal',
      'Empty After Grafting',
      'Empty After Shifting',
      'Move for Opensale',
      'Remove After Over Age'
    ];
    <?php if ($loginroleid < 3): ?>
    reasons.push('Removed', 'Add Trays');
    <?php endif; ?>
    reasons.push('Grafted');

    reasons.forEach(r => {
      opts += `<option value="${r}">${r}</option>`;
    });

    $sel.html(opts);
    if (reasons.includes(curVal)) {
      $sel.val(curVal);
    } else {
      $sel.val('');
    }
    $sel.trigger('change');
  };

  // 5. Tray Stepper Helpers
  window.stepTrays = function(delta) {
    const input = document.getElementById('inputTraysToMove');
    const isAdd = ($('#selectMovementReason').val() === 'Add Trays');
    const max = isAdd ? 99999 : (parseInt($('#moveMaxTrays').val(), 10) || 1);
    let curr = parseInt(input.value, 10) || 1;
    curr += delta;
    if (curr < 1) curr = 1;
    if (curr > max) curr = max;
    input.value = curr;
    updateReasonDropdown();
  };

  window.setMoveAllTrays = function() {
    const max = parseInt($('#moveMaxTrays').val(), 10) || 1;
    $('#inputTraysToMove').val(max);
    updateReasonDropdown();
  };

  window.validateTrayNumber = function(el) {
    const isAdd = ($('#selectMovementReason').val() === 'Add Trays');
    const max = isAdd ? 99999 : (parseInt($('#moveMaxTrays').val(), 10) || 1);
    let v = parseInt(el.value, 10);
    if (isNaN(v) || v < 1) el.value = 1;
    else if (v > max) el.value = max;
    updateReasonDropdown();
  };

  // 6. Submit Plant Movement Handler
  window.submitMovement = function() {
    const bedNo = $('#moveBedNo').val();
    const locationId = $('#moveLocationId').val();
    const polyhouseId = $('#movePolyhouseId').val();
    const stockOrderNo = $('#moveStockOrderNo').val();
    const productId = $('#moveProductId').val();
    const stockStatus = $('#moveStockStatus').val();
    const traysToMove = parseInt($('#inputTraysToMove').val(), 10) || 0;
    const maxTrays = parseInt($('#moveMaxTrays').val(), 10) || 0;
    const movementReason = $('#selectMovementReason').val();
    const customNarration = $('#inputCustomNarration').val().trim();

    if (traysToMove <= 0) {
      Swal.fire('Validation', 'Please enter a valid number of trays (min: 1).', 'warning');
      $('#inputTraysToMove').focus();
      return;
    }
    if (movementReason !== 'Add Trays' && traysToMove > maxTrays) {
      Swal.fire('Validation', `Cannot move ${traysToMove} trays. Only ${maxTrays} available in this bed.`, 'warning');
      $('#inputTraysToMove').focus();
      return;
    }
    if (!movementReason) {
      Swal.fire('Validation', 'Please select why you are moving/adjusting the entered trays.', 'warning');
      $('#selectMovementReason').focus();
      return;
    }

    let targetBedNo = '';
    let targetPolyhouseId = '';

    if (movementReason === 'Bed Shifting') {
      targetBedNo = $('#selectTargetBed').val();
      if (!targetBedNo) {
        Swal.fire('Validation', 'Please select the destination Bed No in this polyhouse to shift trays.', 'warning');
        $('#selectTargetBed').focus();
        return;
      }
      if (targetBedNo.toLowerCase() === (bedNo || '').toLowerCase()) {
        Swal.fire('Validation', 'Destination Bed cannot be the same as current Bed.', 'warning');
        $('#selectTargetBed').focus();
        return;
      }
    } else if (movementReason === 'Polyhouse Shifting' || movementReason === 'Hardening') {
      targetPolyhouseId = $('#selectTargetPolyhouse').val();
      targetBedNo = $('#selectTargetPolyhouseBed').val();
      if (!targetPolyhouseId) {
        Swal.fire('Validation', 'Please select the destination Polyhouse' + (movementReason === 'Hardening' ? ' for Hardening.' : '.'), 'warning');
        $('#selectTargetPolyhouse').focus();
        return;
      }
      if (!targetBedNo) {
        Swal.fire('Validation', 'Please select the destination Bed No in the target polyhouse' + (movementReason === 'Hardening' ? ' for Hardening.' : '.'), 'warning');
        $('#selectTargetPolyhouseBed').focus();
        return;
      }
    }

    let openSaleLocation = '';
    let targetOpenSalePolyhouseId = '';
    let targetOpenSaleLocationId = '';
    let targetOpenSaleEnum = '';
    if (movementReason === 'Move for Opensale') {
      openSaleLocation = $('#selectOpenSaleLocation').val();
      if (!openSaleLocation) {
        Swal.fire('Validation', 'Please select Which Open Sale Location to move trays.', 'warning');
        $('#selectOpenSaleLocation').focus();
        return;
      }
      const $opt = $('#selectOpenSaleLocation option:selected');
      targetOpenSalePolyhouseId = $opt.data('polyhouseid') || '';
      targetOpenSaleLocationId = $opt.data('locationid') || '';
      targetOpenSaleEnum = $opt.data('enum') || '';
    }

    if (movementReason === 'Internal Dispatch' && !customNarration) {
      Swal.fire({
        icon: 'warning',
        title: 'Movement Narration Required',
        html: 'For <b>Internal Dispatch</b>, you must enter full description in <b>Movement Narration</b>.<br><br><span class="text-muted" style="font-size:0.9rem;">Please specify <b>where to move the plants</b> (destination polyhouse / bed) and <b>why you are moving</b> them.</span>',
        confirmButtonColor: '#4f46e5'
      });
      $('#inputCustomNarration').focus();
      return;
    }

    if (movementReason === 'Final Dispatch' && !customNarration) {
      Swal.fire({
        icon: 'warning',
        title: 'Movement Narration Required',
        html: 'For <b>Final Dispatch</b>, you must enter full description in <b>Movement Narration</b>.<br><br><span class="text-muted" style="font-size:0.9rem;">Please write the <b>Client Full Details</b> with <b>Order No.</b> and <b>Payment Clearance</b> details.</span>',
        confirmButtonColor: '#4f46e5'
      });
      $('#inputCustomNarration').focus();
      return;
    }

    const cavityCount = Number(activePlantData ? (activePlantData.cavity_count || activePlantData.qtypertray) : 126) || 126;
    const totalPlantsCount = (traysToMove * cavityCount).toLocaleString();
    const prodName = activePlantData ? (activePlantData.productname || 'Plant') : 'Plant';
    const curPhName = activePlantData ? (activePlantData.polyhousename || 'Polyhouse') : 'Polyhouse';

    let reasonHindi = '';
    let reasonDescHindi = '';
    let warnTheme = {
      bg: '#fffbeb',
      border: '#f59e0b',
      text: '#92400e',
      icon: 'fa-exclamation-triangle',
      badgeBg: '#fef3c7',
      badgeText: '#b45309'
    };

    if (movementReason === 'Remove After Over Age') {
      warnTheme = {
        bg: '#fef2f2',
        border: '#ef4444',
        text: '#991b1b',
        icon: 'fa-hourglass-end',
        badgeBg: '#fee2e2',
        badgeText: '#b91c1c'
      };
      reasonHindi = '⚠️ अधिक उम्र के कारण पौधे हटाना (Remove After Over Age)';
      reasonDescHindi = 'चेतावनी: यह पौधे अपनी निश्चित समयावधि (Over Age) पार कर चुके हैं। इन्हें बेड से हटाया / नष्ट किया जा रहा है। प्रोडक्शन ऑर्डर से कुल संख्या कम हो जाएगी और रेडी स्टॉक से भी यह रिकॉर्ड हटा दिए जाएंगे।';
    } else if (movementReason === 'Removed') {
      warnTheme = {
        bg: '#fef2f2',
        border: '#ef4444',
        text: '#991b1b',
        icon: 'fa-trash-alt',
        badgeBg: '#fee2e2',
        badgeText: '#b91c1c'
      };
      reasonHindi = '⚠️ स्टॉक से पौधे हटाना (Removed)';
      reasonDescHindi = 'चेतावनी: इन पौधों को इन्वेंटरी से स्थायी रूप से हटाया जा रहा है। प्रोडक्शन स्टॉक ऑर्डर से इनकी संख्या कम कर दी जाएगी और रेडी स्टॉक हटा दिया जाएगा।';
    } else if (movementReason === 'Move for Opensale') {
      warnTheme = {
        bg: '#f0fdf4',
        border: '#10b981',
        text: '#065f46',
        icon: 'fa-store',
        badgeBg: '#d1fae5',
        badgeText: '#047857'
      };
      reasonHindi = '🛒 ओपन सेल में स्थानांतरण (Move for Opensale)';
      reasonDescHindi = 'यह पौधे सीधे बिक्री / ओपन सेल (Open Sale) के लिए उपलब्ध कराए जा रहे हैं। यह ओपन सेल स्टॉक रिकॉर्ड में तुरंत जुड़ जाएंगे।';
    } else if (movementReason === 'Final Dispatch') {
      warnTheme = {
        bg: '#eff6ff',
        border: '#3b82f6',
        text: '#1e40af',
        icon: 'fa-truck',
        badgeBg: '#dbeafe',
        badgeText: '#1d4ed8'
      };
      reasonHindi = '🚚 अंतिम डिलीवरी (Final Dispatch)';
      reasonDescHindi = 'यह पौधे किसान / ग्राहक को अंतिम डिलीवरी के लिए डिस्पैच किए जा रहे हैं। कृपया सुनिश्चित करें कि डिलीवरी वाहन और पेमेंट कन्फर्म है।';
    } else if (movementReason === 'Internal Dispatch') {
      warnTheme = {
        bg: '#fffbeb',
        border: '#f59e0b',
        text: '#92400e',
        icon: 'fa-box',
        badgeBg: '#fef3c7',
        badgeText: '#b45309'
      };
      reasonHindi = '📦 आंतरिक स्थानांतरण (Internal Dispatch)';
      reasonDescHindi = 'यह पौधे फार्म के आंतरिक उपयोग या अन्य विशिष्ट यूनिट के लिए भेजे जा रहे हैं।';
    } else if (movementReason === 'Bed Shifting') {
      warnTheme = {
        bg: '#f0fdf4',
        border: '#10b981',
        text: '#166534',
        icon: 'fa-exchange-alt',
        badgeBg: '#dcfce7',
        badgeText: '#15803d'
      };
      reasonHindi = '🔄 बेड बदलना (Bed Shifting)';
      reasonDescHindi = `यह पौधे उसी पॉलीहाउस में वर्तमान बेड (${esc(bedNo)}) से नए बेड (${esc(targetBedNo)}) में शिफ्ट किए जा रहे हैं।`;
    } else if (movementReason === 'Polyhouse Shifting') {
      const phText = $('#selectTargetPolyhouse option:selected').text() || ('Polyhouse ' + targetPolyhouseId);
      warnTheme = {
        bg: '#f5f3ff',
        border: '#8b5cf6',
        text: '#5b21b6',
        icon: 'fa-warehouse',
        badgeBg: '#ede9fe',
        badgeText: '#6d28d9'
      };
      reasonHindi = '🏢 पॉलीहाउस बदलना (Polyhouse Shifting)';
      reasonDescHindi = `यह पौधे दूसरे पॉलीहाउस (${esc(phText)}) के बेड (${esc(targetBedNo)}) में स्थानांतरित किए जा रहे हैं।`;
    } else if (movementReason === 'Hardening') {
      const phText = $('#selectTargetPolyhouse option:selected').text() || ('Polyhouse ' + targetPolyhouseId);
      warnTheme = {
        bg: '#f0fdf4',
        border: '#10b981',
        text: '#166534',
        icon: 'fa-seedling',
        badgeBg: '#dcfce7',
        badgeText: '#15803d'
      };
      reasonHindi = '🌿 हार्डनिंग के लिए स्थानांतरण (Hardening)';
      reasonDescHindi = `यह पौधे हार्डनिंग प्रक्रिया के लिए ${esc(phText)} (बेड ${esc(targetBedNo)}) में भेजे जा रहे हैं।`;
    } else if (movementReason === 'Move for Grafting') {
      warnTheme = {
        bg: '#faf5ff',
        border: '#a855f7',
        text: '#6b21a8',
        icon: 'fa-cut',
        badgeBg: '#f3e8ff',
        badgeText: '#7e22ce'
      };
      reasonHindi = '🌱 ग्राफ्टिंग के लिए स्थानांतरण (Move for Grafting)';
      reasonDescHindi = 'यह पौधे ग्राफ्टिंग यूनिट में कलम लगाने की प्रक्रिया के लिए भेजे जा रहे हैं।';
    } else if (movementReason === 'Healing') {
      warnTheme = {
        bg: '#fffbeb',
        border: '#f59e0b',
        text: '#92400e',
        icon: 'fa-first-aid',
        badgeBg: '#fef3c7',
        badgeText: '#b45309'
      };
      reasonHindi = '🏥 हीलिंग कक्ष में स्थानांतरण (Healing)';
      reasonDescHindi = 'ग्राफ्टेड पौधों को उचित वातावरण और नमी में सुधार (Healing) के लिए भेजा जा रहा है।';
    } else if (movementReason === 'Clip Stick Removal') {
      warnTheme = {
        bg: '#fffbeb',
        border: '#f59e0b',
        text: '#92400e',
        icon: 'fa-paperclip',
        badgeBg: '#fef3c7',
        badgeText: '#b45309'
      };
      reasonHindi = '✂️ क्लिप / स्टिक हटाना (Clip Stick Removal)';
      reasonDescHindi = 'पौधों के जुड़ने के बाद सपोर्ट क्लिप और स्टिक को अलग करने के लिए भेजा जा रहा है।';
    } else if (movementReason === 'Empty After Grafting') {
      warnTheme = {
        bg: '#fef2f2',
        border: '#ef4444',
        text: '#991b1b',
        icon: 'fa-ban',
        badgeBg: '#fee2e2',
        badgeText: '#b91c1c'
      };
      reasonHindi = '🗑️ ग्राफ्टिंग के बाद खाली (Empty After Grafting)';
      reasonDescHindi = 'ग्राफ्टिंग के बाद बची हुई खाली ट्रे को इन्वेंटरी से खाली दर्ज किया जा रहा है।';
    } else if (movementReason === 'Empty After Shifting') {
      warnTheme = {
        bg: '#f0fdfa',
        border: '#14b8a6',
        text: '#115e59',
        icon: 'fa-boxes',
        badgeBg: '#ccfbf1',
        badgeText: '#0f766e'
      };
      reasonHindi = '📦 शिफ्टिंग के बाद खाली (Empty After Shifting)';
      reasonDescHindi = 'शिफ्टिंग पूरी होने के बाद खाली ट्रे को बेड से खाली दर्ज किया जा रहा है।';
    } else if (movementReason === 'Add Trays') {
      warnTheme = {
        bg: '#f0fdf4',
        border: '#10b981',
        text: '#166534',
        icon: 'fa-plus-circle',
        badgeBg: '#dcfce7',
        badgeText: '#15803d'
      };
      reasonHindi = '➕ नए ट्रे जोड़ना (Add Trays)';
      reasonDescHindi = 'इस ऑर्डर में नए ट्रे जोड़कर स्टॉक की मात्रा बढ़ाई जा रही है।';
    } else {
      reasonHindi = `स्थानांतरण: ${esc(movementReason)}`;
      reasonDescHindi = `यह पौधे "${esc(movementReason)}" के अंतर्गत स्थानांतरित किए जा रहे हैं।`;
    }

    const isDangerousMove = (movementReason === 'Remove After Over Age' || movementReason === 'Removed');
    const confirmThemeColor = isDangerousMove ? '#dc2626' : '#059669';

    const modalHtml = `
      <div style="text-align: left; font-family: var(--font-main, sans-serif);">
        <!-- Top Sticky Action Bar (Mobile App Preview Style) -->
        <div class="swal-app-top-action-bar" style="position: sticky; top: -4px; z-index: 30; background: #ffffff; padding: 2px 0 6px 0; margin-bottom: 8px; border-bottom: 1.5px solid #e2e8f0;">
          <!-- Re-confirm Checkbox (Placed right at the very top for 100% instant visibility) -->
          <div id="reconfirmBoxWrap" style="background: #ffffff; border: 2px dashed ${confirmThemeColor}; border-radius: 9px; padding: 8px 10px; margin-bottom: 6px; transition: all 0.25s ease;">
            <label style="display: flex; align-items: flex-start; gap: 9px; cursor: pointer; margin: 0; font-size: 0.86rem; font-weight: 700; color: #0f172a; line-height: 1.35;">
              <input type="checkbox" id="chkReconfirmMovement" onchange="window.toggleReconfirmBtn(this.checked)" style="width: 22px; height: 22px; min-width: 22px; margin-top: 1px; accent-color: ${confirmThemeColor}; cursor: pointer;">
              <span>हाँ, मैं पुनः पुष्टि करता/करती हूँ कि दर्ज संख्या (<b style="color:#2563eb;">${traysToMove} ट्रे / ${totalPlantsCount} पौधे</b>) और यह स्थानांतरण कारण सही है।</span>
            </label>
          </div>

          <!-- Top Confirm Action Button (Sticky at the top of the screen) -->
          <button type="button" id="btnTopSwalConfirm" onclick="window.triggerSwalConfirm()" style="width: 100%; padding: 10px 12px; font-size: 0.94rem; font-weight: 800; border-radius: 9px; border: none; background: #94a3b8; color: #ffffff; cursor: not-allowed; transition: all 0.25s ease; box-shadow: 0 2px 6px rgba(0,0,0,0.08); display: flex; align-items: center; justify-content: center; gap: 8px;" disabled>
            <i class="fa fa-lock" id="iconTopSwalLock"></i> <span id="lblTopSwalText">पुष्टि करें (ऊपर चेकबॉक्स टिक करें)</span>
          </button>
        </div>

        <!-- Mobile App Preview Card (Product & Movement Info) -->
        <div class="swal-app-preview-card" style="background: #ffffff; border: 1.5px solid #cbd5e1; border-radius: 12px; overflow: hidden; box-shadow: 0 3px 10px rgba(15, 23, 42, 0.05); margin-bottom: 6px;">
          <!-- App Preview Header Pill -->
          <div style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); padding: 6px 10px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between;">
            <span style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; color: #475569;">
              <i class="fa fa-mobile-screen-button text-primary mr-1"></i> ऐप पूर्वावलोकन (App Preview)
            </span>
            <span style="font-size: 0.72rem; font-weight: 700; color: #059669; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 4px; padding: 1px 6px;">
              <i class="fa fa-circle text-success" style="font-size: 0.5rem; vertical-align: middle;"></i> Live Ready
            </span>
          </div>

          <div style="padding: 8px 10px;">
            <!-- Product & Current Bed Info -->
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; margin-bottom: 6px;">
              <div style="flex: 1;">
                <small style="color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.68rem; letter-spacing: 0.4px;">Product / Variety</small>
                <div style="font-weight: 800; color: #0f172a; font-size: 0.92rem; line-height: 1.25;">${esc(prodName)}</div>
              </div>
              <div style="text-align: right; flex-shrink: 0;">
                <small style="color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.68rem; letter-spacing: 0.4px;">Location / Bed</small>
                <div style="margin-top: 2px;">
                  <span class="badge bg-secondary text-white" style="font-size:0.75rem; padding: 2px 6px;">${esc(curPhName)}</span>
                  <span class="badge bg-dark text-white" style="font-size:0.78rem; padding: 2px 7px; margin-left:2px;">${esc(bedNo)}</span>
                </div>
              </div>
            </div>
            
            <!-- Count Badges -->
            <div style="padding-top: 6px; border-top: 1px dashed #e2e8f0; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 6px;">
              <div>
                <span style="font-size: 0.78rem; color: #475569; font-weight: 700;">पौधों की कुल संख्या:</span>
              </div>
              <div style="font-size: 0.92rem; font-weight: 800;">
                <span style="color: #2563eb; background:#eff6ff; border:1px solid #bfdbfe; border-radius:6px; padding:2px 7px;">📦 ${traysToMove} Tray(s)</span>
                <span style="color: #059669; background:#ecfdf5; border:1px solid #a7f3d0; border-radius:6px; padding:2px 7px; margin-left: 4px;">🌱 ${totalPlantsCount} Plants</span>
              </div>
            </div>

            <!-- Target Open Sale Center (if Move for Opensale) -->
            ${(movementReason === 'Move for Opensale' && openSaleLocation) ? `
            <div style="margin-top: 6px; padding-top: 6px; border-top: 1px dashed #e2e8f0; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 6px;">
              <div>
                <span style="font-size: 0.78rem; color: #475569; font-weight: 700;">ओपन सेल केंद्र (Target):</span>
              </div>
              <div>
                <span style="color: #059669; font-weight: 800; font-size: 0.88rem; background:#ecfdf5; border:1px solid #a7f3d0; border-radius:6px; padding:2px 8px;"><i class="fa fa-shopping-cart mr-1"></i>${esc(openSaleLocation)}</span>
              </div>
            </div>
            ` : ''}

            <!-- Reason & Hindi Explanation Note -->
            <div style="margin-top: 6px; background: ${warnTheme.bg}; border: 1px solid ${warnTheme.border}; border-radius: 7px; padding: 6px 8px; color: ${warnTheme.text};">
              <div style="display: flex; align-items: flex-start; gap: 6px;">
                <i class="fa ${warnTheme.icon}" style="font-size: 1rem; margin-top: 2px;"></i>
                <div>
                  <div style="font-weight: 800; font-size: 0.86rem; margin-bottom: 1px;">
                    ${reasonHindi}
                  </div>
                  <div style="font-size: 0.78rem; line-height: 1.3;">
                    ${reasonDescHindi}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;

    Swal.fire({
      position: (window.innerWidth <= 768 ? 'top' : 'center'),
      title: '<i class="fa fa-clipboard-check text-success mr-1"></i> पौधे स्थानांतरण पुनः पुष्टि (Re-Confirm Movement)',
      html: modalHtml,
      width: (window.innerWidth <= 576 ? '96%' : '540px'),
      showCloseButton: true,
      customClass: {
        popup: 'swal2-reconfirm-popup',
        htmlContainer: 'swal2-reconfirm-container',
        actions: 'swal2-reconfirm-actions'
      },
      showCancelButton: true,
      confirmButtonColor: confirmThemeColor,
      cancelButtonColor: '#64748b',
      confirmButtonText: '<i class="fa fa-check mr-1"></i> पुष्टि करें (Confirm & Move)',
      cancelButtonText: 'रद्द करें (Cancel)',
      didOpen: () => {
        const confirmBtn = Swal.getConfirmButton();
        if (confirmBtn) {
          confirmBtn.disabled = true;
          confirmBtn.style.opacity = '0.4';
          confirmBtn.style.cursor = 'not-allowed';
        }

        window.triggerSwalConfirm = function() {
          const chk = document.getElementById('chkReconfirmMovement');
          if (chk && !chk.checked) {
            chk.focus();
            const wrap = document.getElementById('reconfirmBoxWrap');
            if (wrap) {
              wrap.style.borderColor = '#dc2626';
              setTimeout(() => { if (wrap) wrap.style.borderColor = confirmThemeColor; }, 1000);
            }
            return;
          }
          Swal.clickConfirm();
        };

        window.toggleReconfirmBtn = function(isChecked) {
          const btn = Swal.getConfirmButton();
          const topBtn = document.getElementById('btnTopSwalConfirm');
          const lockIcon = document.getElementById('iconTopSwalLock');
          const topText = document.getElementById('lblTopSwalText');
          const wrap = document.getElementById('reconfirmBoxWrap');

          if (wrap) {
            wrap.style.background = isChecked ? '#ecfdf5' : '#ffffff';
            wrap.style.borderColor = confirmThemeColor;
            wrap.style.borderStyle = isChecked ? 'solid' : 'dashed';
          }
          if (btn) {
            btn.disabled = !isChecked;
            btn.style.opacity = isChecked ? '1' : '0.4';
            btn.style.cursor = isChecked ? 'pointer' : 'not-allowed';
            btn.style.boxShadow = isChecked ? `0 0 0 3px rgba(5, 150, 105, 0.4)` : 'none';
          }

          if (topBtn) {
            topBtn.disabled = !isChecked;
            if (isChecked) {
              topBtn.style.background = confirmThemeColor;
              topBtn.style.color = '#ffffff';
              topBtn.style.cursor = 'pointer';
              topBtn.style.boxShadow = '0 4px 14px rgba(5, 150, 105, 0.45)';
              if (lockIcon) lockIcon.className = 'fa fa-check-circle mr-1 text-white';
              if (topText) topText.innerHTML = 'पुष्टि करें एवं निष्पादित करें (Confirm & Move)';

              // Scroll smoothly to top so the confirm button is immediately at the top of the mobile screen
              try {
                const popup = Swal.getPopup();
                if (popup) popup.scrollTo({ top: 0, behavior: 'smooth' });
                const container = document.querySelector('.swal2-reconfirm-container');
                if (container) container.scrollTo({ top: 0, behavior: 'smooth' });
              } catch(e){}
            } else {
              topBtn.style.background = '#94a3b8';
              topBtn.style.color = '#ffffff';
              topBtn.style.cursor = 'not-allowed';
              topBtn.style.boxShadow = 'none';
              if (lockIcon) lockIcon.className = 'fa fa-lock mr-1';
              if (topText) topText.innerHTML = 'पुष्टि करें (ऊपर चेकबॉक्स टिक करें)';
            }
          }
        };
      }
    }).then((res) => {
      if (!res.isConfirmed) return;

      const $btn = $('#btnSubmitMovement');
      const origBtn = $btn.html();
      $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i> Processing Movement...');

      const postData = {
        bed_no: bedNo,
        locationid: locationId,
        polyhouseid: polyhouseId,
        stockorder_no: stockOrderNo,
        productid: productId,
        stock_status: stockStatus,
        trays_to_move: traysToMove,
        movement_reason: movementReason,
        target_bed_no: targetBedNo,
        target_polyhouseid: targetPolyhouseId,
        open_sale_location: openSaleLocation,
        target_opensale_polyhouseid: targetOpenSalePolyhouseId,
        target_opensale_locationid: targetOpenSaleLocationId,
        open_sale_enum: targetOpenSaleEnum,
        custom_narration: customNarration
      };

      $.post('modal/movement_modal.php?action=move_trays', postData, function(response) {
        $btn.prop('disabled', false).html(origBtn);

        if (response.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: 'Movement Recorded!',
            text: response.message,
            timer: 2500,
            showConfirmButton: false
          });

          // Reset scan query and refresh recent logs
          $('#inputBarcodeQuery').val('').focus();
          resetFormPanel();
          loadRecentMovements();
        } else {
          Swal.fire('Error', response.message || 'Failed to execute movement.', 'error');
        }
      }, 'json').fail(function() {
        $btn.prop('disabled', false).html(origBtn);
        Swal.fire('Server Error', 'Failed to communicate with server during movement execution.', 'error');
      });
    });
  };

  // Initialize Filter Select2
  function initFilterSelect2() {
    if ($.fn.select2) {
      $('#filterLogProduct').select2({
        placeholder: '-- All Products --',
        allowClear: true,
        width: '100%'
      });
      $('#filterLogReason').select2({
        placeholder: '-- All Reasons --',
        allowClear: true,
        width: '100%'
      });
      $('#filterLogMovedBy').select2({
        placeholder: '-- All Staff / Users --',
        allowClear: true,
        width: '100%'
      });
    }
  }

  // 7. Recent Movements History Table (Grouped Date & Order wise + Filterable)
  window.loadRecentMovements = function() {
    const postData = {
      limit: 150,
      order_no: $.trim($('#filterLogOrderNo').val() || ''),
      product_name: $.trim($('#filterLogProduct').val() || ''),
      destination_reason: $.trim($('#filterLogReason').val() || ''),
      moved_by: $.trim($('#filterLogMovedBy').val() || ''),
      from_date: $.trim($('#filterLogDateFrom').val() || '')
    };

    $('#tblRecentMovementsBody').html('<tr><td colspan="8" class="text-center py-4 text-muted"><i class="fa fa-spinner fa-spin mr-1"></i> Loading movement logs...</td></tr>');

    $.post('modal/movement_modal.php?action=recent_movements', postData, function(res) {
      // Dynamically populate filter dropdowns if not yet populated
      if (res.filter_options) {
        if (res.filter_options.products && $('#filterLogProduct option').length <= 1) {
          const curVal = $('#filterLogProduct').val();
          let pOpts = '<option value="">-- All Products --</option>';
          res.filter_options.products.forEach(p => {
            pOpts += `<option value="${esc(p)}">${esc(p)}</option>`;
          });
          $('#filterLogProduct').html(pOpts).val(curVal);
          if ($.fn.select2) $('#filterLogProduct').trigger('change.select2');
        }
        if (res.filter_options.reasons && $('#filterLogReason option').length <= 1) {
          const curReason = $('#filterLogReason').val();
          let rOpts = '<option value="">-- All Reasons --</option>';
          res.filter_options.reasons.forEach(r => {
            rOpts += `<option value="${esc(r)}">${esc(r)}</option>`;
          });
          $('#filterLogReason').html(rOpts).val(curReason);
          if ($.fn.select2) $('#filterLogReason').trigger('change.select2');
        }
        if (res.filter_options.users && $('#filterLogMovedBy option').length <= 1) {
          const curUser = $('#filterLogMovedBy').val();
          let uOpts = '<option value="">-- All Staff / Users --</option>';
          res.filter_options.users.forEach(u => {
            uOpts += `<option value="${esc(u)}">${esc(u)}</option>`;
          });
          $('#filterLogMovedBy').html(uOpts).val(curUser);
          if ($.fn.select2) $('#filterLogMovedBy').trigger('change.select2');
        }
      }

      // Update summary KPI
      if (res.summary) {
        $('#logTotalTrays').text(Number(res.summary.total_trays || 0).toLocaleString());
        $('#logTotalPlants').text(Number(res.summary.total_plants || 0).toLocaleString());
      }

      let h = '';
      if (res.status === 'success' && res.data && res.data.length) {
        res.data.forEach(r => {
          const soDisplay = r.stockorder_no ? esc(r.stockorder_no) : (r.psono ? esc(r.psono) : '-');
          let reasonClass = 'bg-primary';
          const st = (r.status || '').toLowerCase();
          if (st.includes('bed shift')) reasonClass = 'bg-info text-white';
          else if (st.includes('polyhouse shift')) reasonClass = 'bg-primary text-white';
          else if (st.includes('final dispatch')) reasonClass = 'bg-dark text-white';
          else if (st.includes('internal dispatch')) reasonClass = 'bg-secondary text-white';
          else if (st.includes('ready')) reasonClass = 'bg-success text-white';
          else if (st.includes('removed')) reasonClass = 'bg-danger text-white';

          const reasonBadge = `<span class="badge ${reasonClass} px-2 py-1" style="font-size:0.8rem; font-weight:600;">${esc(r.status || 'Movement')}</span>`;
          const trays = Number(r.tray_count || 1);
          const plants = Number(r.plant_count || 0);

          h += `
            <tr>
              <td style="font-size:0.8rem; color:#475569; white-space:nowrap; font-weight:600;">
                <i class="fa fa-calendar-alt text-muted mr-1"></i>${esc(r.updated_at || '-')}
              </td>
              <td><span class="badge bg-dark text-white font-monospace">${esc(r.bed_no || '-')}</span></td>
              <td class="font-monospace" style="color:#4f46e5; font-weight:800; font-size:0.88rem;">${soDisplay}</td>
              <td style="font-weight:700; color:#0f172a;">${esc(r.productname || 'Plant')}</td>
              <td class="text-center" style="white-space:nowrap;">
                <span class="badge text-white px-2 py-1" style="font-size:0.83rem; background:#4f46e5 !important; font-weight:700;">${trays} ${trays === 1 ? 'Tray' : 'Trays'}</span>
                ${plants > 0 ? `<div class="text-muted" style="font-size:0.75rem; font-weight:600; margin-top:2px;">${plants.toLocaleString()} Plants</div>` : ''}
              </td>
              <td>${reasonBadge}</td>
              <td style="max-width:320px; font-size:0.82rem; color:#334155; white-space:pre-line; line-height:1.35;">${esc(r.movementremark || '-')}</td>
              <td style="white-space:nowrap;"><span class="badge bg-light text-dark border px-2 py-1" style="font-size:0.8rem;"><i class="fa fa-user-circle mr-1 text-primary"></i>${esc(r.updated_by || 'Admin')}</span></td>
            </tr>
          `;
        });
      } else {
        h = '<tr><td colspan="8" class="text-center py-4 text-muted"><i class="fa fa-info-circle mr-1"></i>No movement logs found matching selected filters.</td></tr>';
      }
      $('#tblRecentMovementsBody').html(h);
    }, 'json').fail(function() {
      $('#tblRecentMovementsBody').html('<tr><td colspan="8" class="text-center py-3 text-danger">Unable to load movement history.</td></tr>');
    });
  };

  window.resetLogFilters = function() {
    $('#filterLogOrderNo').val('');
    $('#filterLogProduct').val('');
    $('#filterLogReason').val('');
    $('#filterLogMovedBy').val('');
    $('#filterLogDateFrom').val('');
    if ($.fn.select2) {
      $('#filterLogProduct').trigger('change.select2');
      $('#filterLogReason').trigger('change.select2');
      $('#filterLogMovedBy').trigger('change.select2');
    }
    loadRecentMovements();
  };

  // Bind live filter triggers on enter / change (with debounce)
  let filterDebounce = null;
  $('#filterLogOrderNo').on('keyup', function(e) {
    if (e.key === 'Enter') {
      loadRecentMovements();
    } else {
      clearTimeout(filterDebounce);
      filterDebounce = setTimeout(loadRecentMovements, 400);
    }
  });
  $('#filterLogProduct, #filterLogReason, #filterLogMovedBy, #filterLogDateFrom').on('change', function() {
    loadRecentMovements();
  });

  function esc(s) {
    if (s == null) return '';
    return $('<div>').text(s).html();
  }

  // Initial Load
  initFilterSelect2();
  loadRecentMovements();
});
</script>
</body>
</html>
