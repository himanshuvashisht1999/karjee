<?php
include('../header.php');
include('functions/product_functions.php');
include('functions/vendor_functions.php');
include('functions/seed_functions.php');
date_default_timezone_set("Asia/Calcutta");

$mdid = "";
if (isset($_GET['mdid'])) {
    $mdid = $_GET['mdid'];
}
$dddr = loginmodulesdetails_frommaster($authconn, $companyid, $subcompanyid, $projectid, "moduleid", $mdid);
$groupid = "";
if (count($dddr) > 0) {
    $groupid = $dddr[0]['groupid'];
}

// Query to count today's received germination boxes at Polyhouse
$today_received_count = 0;
$today_received_trays = 0;
$todayQuery = "SELECT COUNT(*) as box_count, COALESCE(SUM(traycountinbox), 0) as tray_count 
               FROM krf_polyhouse_germinated_boxes 
               WHERE status = 'ReceivedAtPolyhouse' 
                 AND projectid = ? 
                 AND DATE(received_at) = CURDATE()";
if (isset($krfconn) && $stmtToday = $krfconn->prepare($todayQuery)) {
    $stmtToday->bind_param("i", $projectid);
    $stmtToday->execute();
    $resToday = $stmtToday->get_result()->fetch_assoc();
    $today_received_count = (int)($resToday['box_count'] ?? 0);
    $today_received_trays = (int)($resToday['tray_count'] ?? 0);
    $stmtToday->close();
}
?>
<!-- Google Fonts: Outfit & Plus Jakarta Sans -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome & Bootstrap Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

<!-- QR Code Scanner Library -->
<script src="https://unpkg.com/html5-qrcode@2.3.7/html5-qrcode.min.js"></script>

<style>
    :root {
        --app-primary: #0284c7;
        --app-primary-dark: #0369a1;
        --app-primary-light: #e0f2fe;
        --app-accent: #0ea5e9;
        --app-success: #10b981;
        --app-success-light: #d1fae5;
        --app-warning: #f59e0b;
        --app-warning-light: #fef3c7;
        --app-danger: #ef4444;
        --app-danger-light: #fee2e2;
        --app-card-bg: #ffffff;
        --app-bg: #f1f5f9;
        --app-text-main: #0f172a;
        --app-text-muted: #64748b;
        --app-border: #e2e8f0;
        --app-radius-lg: 20px;
        --app-radius-md: 14px;
        --app-radius-sm: 10px;
        --app-shadow-sm: 0 2px 8px rgba(15, 23, 42, 0.04);
        --app-shadow: 0 8px 24px -4px rgba(15, 23, 42, 0.08), 0 2px 6px -1px rgba(15, 23, 42, 0.04);
        --app-shadow-lg: 0 16px 36px -6px rgba(14, 165, 233, 0.18);
    }

    body {
        font-family: 'Plus Jakarta Sans', 'Outfit', sans-serif;
        background-color: var(--app-bg);
        color: var(--app-text-main);
        -webkit-tap-highlight-color: transparent;
    }

    /* Mobile App Frame Container (Mobile default, Desktop/Laptop 98% wide) */
    .app-mobile-container {
        width: 100%;
        max-width: 680px;
        margin: 0 auto;
        padding: 12px 14px 120px;
        transition: max-width 0.2s ease, width 0.2s ease;
    }

    /* Desktop and Laptop: Expand to 98% width */
    @media (min-width: 992px) {
        .app-mobile-container {
            width: 98% !important;
            max-width: 98% !important;
            margin: 0 auto !important;
            padding: 18px 16px 120px !important;
        }

        .kpi-row {
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
        }

        .kpi-card {
            padding: 1.15rem 1.5rem;
        }

        .kpi-value {
            font-size: 1.65rem;
        }

        .scanner-mode-tabs {
            max-width: 480px;
            margin-left: auto;
            margin-right: auto;
        }

        .manual-input-wrap {
            max-width: 840px;
            margin-left: auto;
            margin-right: auto;
        }

        #verifyStatusAlert {
            max-width: 840px;
            margin-left: auto;
            margin-right: auto;
        }

        .scanned-list-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
            gap: 1rem;
        }

        .scanned-empty-state {
            grid-column: 1 / -1;
        }

        .bed-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
            gap: 1rem;
        }
    }

    /* --- App Header Card --- */
    .mobile-app-header {
        background: linear-gradient(135deg, #0284c7 0%, #0ea5e9 100%);
        border-radius: var(--app-radius-lg);
        padding: 1rem 1.25rem;
        color: white;
        margin-bottom: 1rem;
        box-shadow: 0 10px 25px rgba(2, 132, 199, 0.25);
        display: flex;
        justify-content: space-between;
        align-items: center;
        border: none;
        position: relative;
        overflow: hidden;
    }

    .mobile-app-header::after {
        content: '';
        position: absolute;
        top: -30px;
        right: -30px;
        width: 110px;
        height: 110px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.12);
        pointer-events: none;
    }

    .header-icon-circle {
        width: 44px;
        height: 44px;
        min-width: 44px;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        color: #ffffff;
    }

    .header-text-block h1 {
        font-size: 1.05rem;
        font-weight: 800;
        margin: 0;
        letter-spacing: -0.2px;
        line-height: 1.2;
        text-transform: uppercase;
    }

    .header-text-block p {
        font-size: 0.72rem;
        font-weight: 500;
        margin: 2px 0 0;
        opacity: 0.9;
        letter-spacing: 0.3px;
    }

    .btn-app-refresh {
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.35);
        color: white;
        width: 38px;
        height: 38px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.25s ease;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
    }

    .btn-app-refresh:hover, .btn-app-refresh:active {
        background: white;
        color: var(--app-primary);
        transform: rotate(180deg) scale(1.05);
    }

    /* --- Today's KPI Metric Cards --- */
    .kpi-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
        margin-bottom: 1rem;
    }

    .kpi-card {
        background: var(--app-card-bg);
        border-radius: var(--app-radius-md);
        padding: 0.85rem 1rem;
        border: 1px solid var(--app-border);
        box-shadow: var(--app-shadow-sm);
        display: flex;
        align-items: center;
        gap: 0.75rem;
        transition: transform 0.2s ease;
    }

    .kpi-card:hover {
        transform: translateY(-2px);
    }

    .kpi-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
    }

    .kpi-icon.blue {
        background: var(--app-primary-light);
        color: var(--app-primary);
    }

    .kpi-icon.green {
        background: var(--app-success-light);
        color: var(--app-success);
    }

    .kpi-label {
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--app-text-muted);
        letter-spacing: 0.4px;
        line-height: 1.2;
    }

    .kpi-value {
        font-size: 1.15rem;
        font-weight: 800;
        color: var(--app-text-main);
        line-height: 1.2;
        margin-top: 2px;
    }

    /* --- App Cards --- */
    .app-card {
        background: var(--app-card-bg);
        border-radius: var(--app-radius-lg);
        padding: 1.25rem;
        border: 1px solid var(--app-border);
        box-shadow: var(--app-shadow);
        margin-bottom: 1rem;
    }

    .app-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #f1f5f9;
    }

    .app-card-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--app-text-main);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin: 0;
    }

    .app-card-title i {
        color: var(--app-primary);
        font-size: 1.15rem;
    }

    /* --- Segmented Mode Tabs (Manual default, Camera alternative) --- */
    .scanner-mode-tabs {
        display: grid;
        grid-template-columns: 1fr 1fr;
        background: #f1f5f9;
        border-radius: 14px;
        padding: 4px;
        gap: 4px;
        margin-bottom: 1rem;
    }

    .mode-tab-btn {
        border: none;
        background: transparent;
        color: var(--app-text-muted);
        padding: 0.6rem 0.5rem;
        font-size: 0.82rem;
        font-weight: 700;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
    }

    .mode-tab-btn.active {
        background: white;
        color: var(--app-primary);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    }

    /* --- Scanner Idle Placeholder --- */
    .scanner-idle-box {
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        border: 2px dashed #cbd5e1;
        border-radius: 18px;
        padding: 1.75rem 1rem;
        text-align: center;
        transition: all 0.3s ease;
    }

    .scanner-idle-icon {
        width: 64px;
        height: 64px;
        margin: 0 auto 0.75rem;
        background: var(--app-primary-light);
        color: var(--app-primary);
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.85rem;
        box-shadow: 0 8px 20px rgba(14, 165, 233, 0.15);
    }

    .scanner-idle-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--app-text-main);
        margin-bottom: 0.25rem;
    }

    .scanner-idle-subtitle {
        font-size: 0.75rem;
        color: var(--app-text-muted);
        margin-bottom: 1rem;
    }

    /* --- Live Camera Viewport --- */
    .camera-live-wrapper {
        border-radius: 20px;
        overflow: hidden;
        border: 2px solid var(--app-primary);
        background: #0f172a;
        position: relative;
        margin: 0 auto;
        width: 100%;
        max-width: 360px;
        min-height: 280px;
        height: 280px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .camera-loading-overlay {
        position: absolute;
        inset: 0;
        background: #0f172a;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        z-index: 6;
        color: white;
    }

    #reader {
        width: 100% !important;
        height: 100% !important;
        min-height: 280px;
        margin: 0 auto;
        overflow: hidden;
        border: none !important;
        position: relative;
    }

    #reader video {
        width: 100% !important;
        height: 100% !important;
        min-height: 280px;
        object-fit: cover !important;
        border-radius: 18px;
        display: block;
    }

    #reader > div {
        width: 100% !important;
        height: 100% !important;
    }

    .scanner-overlay {
        position: absolute;
        inset: 0;
        z-index: 5;
        pointer-events: none;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .scan-region-highlight {
        position: absolute;
        width: 220px;
        height: 220px;
        border: 2px solid rgba(255, 255, 255, 0.4);
        border-radius: 18px;
        box-shadow: 0 0 0 4000px rgba(0, 0, 0, 0.45);
    }

    .scan-line {
        position: absolute;
        width: 100%;
        height: 3px;
        background: linear-gradient(to right, transparent, #0ea5e9, transparent);
        box-shadow: 0 0 12px #0ea5e9;
        top: 0;
        z-index: 10;
        animation: scanMove 2.2s ease-in-out infinite;
    }

    @keyframes scanMove {
        0% { top: 8%; opacity: 0; }
        20% { opacity: 1; }
        80% { opacity: 1; }
        100% { top: 92%; opacity: 0; }
    }

    .scanner-corner {
        position: absolute;
        width: 22px;
        height: 22px;
        border: 3px solid #0ea5e9;
        z-index: 11;
    }

    .corner-tl { top: 0; left: 0; border-right: none; border-bottom: none; border-top-left-radius: 6px; }
    .corner-tr { top: 0; right: 0; border-left: none; border-bottom: none; border-top-right-radius: 6px; }
    .corner-bl { bottom: 0; left: 0; border-right: none; border-top: none; border-bottom-left-radius: 6px; }
    .corner-br { bottom: 0; right: 0; border-left: none; border-top: none; border-bottom-right-radius: 6px; }

    .scan-success-flash {
        position: absolute;
        inset: 0;
        background: rgba(16, 185, 129, 0.35);
        z-index: 20;
        display: none;
        animation: flashAnim 0.4s ease-out;
    }

    @keyframes flashAnim {
        0% { opacity: 1; }
        100% { opacity: 0; }
    }

    /* Pulse animation */
    @keyframes pulse-dot {
        0% { transform: scale(0.95); opacity: 0.8; }
        50% { transform: scale(1.2); opacity: 1; }
        100% { transform: scale(0.95); opacity: 0.8; }
    }
    .pulse-dot {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background-color: #ef4444;
        animation: pulse-dot 1.4s infinite;
        vertical-align: middle;
        margin-right: 4px;
    }

    /* --- Action Controls --- */
    .scanner-controls-grid {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-top: 1rem;
        flex-wrap: wrap;
    }

    .btn-mobile-action {
        border-radius: 12px;
        padding: 0.55rem 1.15rem;
        font-size: 0.85rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s ease;
        border: none;
    }

    .btn-mobile-action:active {
        transform: scale(0.96);
    }

    /* --- Manual Input Styling --- */
    .manual-input-wrap {
        background: #f8fafc;
        border-radius: 16px;
        padding: 1.25rem;
        border: 1px solid var(--app-border);
    }

    .app-input-group {
        display: flex;
        align-items: center;
        background: white;
        border: 2px solid var(--app-border);
        border-radius: 14px;
        padding: 2px 8px;
        margin-bottom: 0.75rem;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .app-input-group:focus-within {
        border-color: var(--app-primary);
        box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.12);
    }

    .app-input-icon {
        font-size: 1.2rem;
        color: var(--app-text-muted);
        padding: 0 8px;
    }

    .app-input-field {
        border: none;
        outline: none;
        width: 100%;
        padding: 0.75rem 0.5rem;
        font-size: 0.95rem;
        font-weight: 600;
        color: var(--app-text-main);
        background: transparent;
    }

    /* --- Scanned Box Cards List (Mobile Optimized) --- */
    .scanned-list-container {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .scanned-box-card {
        background: white;
        border: 1px solid var(--app-border);
        border-radius: 16px;
        padding: 1rem;
        box-shadow: var(--app-shadow-sm);
        position: relative;
        transition: all 0.2s ease;
    }

    .scanned-box-card:hover {
        border-color: var(--app-primary-light);
        box-shadow: 0 4px 14px rgba(2, 132, 199, 0.1);
    }

    .box-card-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.5rem;
        flex-wrap: wrap;
        gap: 6px;
    }

    .box-id-badge {
        background: #f1f5f9;
        color: var(--app-text-main);
        font-weight: 800;
        font-size: 0.8rem;
        padding: 4px 10px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        letter-spacing: 0.2px;
    }

    .box-tray-badge {
        background: var(--app-success-light);
        color: #065f46;
        font-weight: 800;
        font-size: 0.75rem;
        padding: 4px 10px;
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .box-product-title {
        font-size: 0.95rem;
        font-weight: 800;
        color: var(--app-text-main);
        margin: 0 0 2px 0;
        line-height: 1.3;
    }

    .box-order-sub {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--app-primary);
    }

    .box-meta-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 6px 10px;
        background: #f8fafc;
        padding: 10px;
        border-radius: 12px;
        font-size: 0.74rem;
        margin-top: 6px;
    }

    .box-meta-item {
        display: flex;
        flex-direction: column;
    }

    .box-meta-label {
        font-size: 0.65rem;
        color: var(--app-text-muted);
        text-transform: uppercase;
        font-weight: 700;
    }

    .box-meta-val {
        font-weight: 700;
        color: var(--app-text-main);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .btn-remove-box {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: 1px solid var(--app-danger-light);
        background: #fff;
        color: var(--app-danger);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-remove-box:hover {
        background: var(--app-danger);
        color: white;
    }

    /* Scanned Empty State */
    .scanned-empty-state {
        text-align: center;
        padding: 2rem 1rem;
        background: #f8fafc;
        border-radius: 16px;
        border: 1px dashed var(--app-border);
    }

    .scanned-empty-icon {
        font-size: 2.2rem;
        color: #94a3b8;
        margin-bottom: 0.5rem;
    }

    /* Total Summary Strip */
    .scanned-summary-strip {
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        border: 1px solid #bae6fd;
        border-radius: 14px;
        padding: 0.75rem 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 0.75rem;
    }

    /* Target Polyhouse & Bed Section */
    .polyhouse-target-card {
        background: #f8fafc;
        border-radius: 16px;
        padding: 1.25rem;
        border: 1px solid var(--app-border);
        margin-top: 1rem;
    }

    /* Bed Capacity Cards */
    .bed-card {
        background: white;
        border: 1px solid var(--app-border);
        border-radius: 14px;
        padding: 0.85rem 1rem;
        margin-bottom: 0.65rem;
        box-shadow: 0 2px 6px rgba(0,0,0,0.03);
    }

    .bed-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.4rem;
    }

    .bed-card-title {
        font-size: 0.9rem;
        font-weight: 800;
        color: var(--app-text-main);
    }

    .bed-card-space {
        font-size: 0.78rem;
        font-weight: 700;
    }

    .bed-progress {
        height: 6px;
        border-radius: 3px;
        background: #e2e8f0;
        margin: 6px 0 8px;
        overflow: hidden;
    }

    .bed-progress-bar {
        height: 100%;
        background: var(--app-primary);
        border-radius: 3px;
    }

    /* --- Modal Dialog Custom Sizing --- */
    #allocateToBedModal .modal-dialog {
        max-width: 860px !important;
        width: 95% !important;
        margin: 1.75rem auto;
    }

    #viewBedDetailsModal .modal-dialog {
        max-width: 960px !important;
        width: 95% !important;
        margin: 1.75rem auto;
    }

    #allocBoxList {
        max-height: 420px;
        overflow-y: auto;
        padding: 4px;
    }

    @media (min-width: 650px) {
        #allocBoxList {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
            gap: 0.85rem;
        }
    }

    .box-alloc-card {
        background: #ffffff;
        border: 1.5px solid #e2e8f0;
        border-radius: 14px;
        padding: 0.9rem 1rem;
        box-shadow: 0 2px 6px rgba(0,0,0,0.02);
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .box-alloc-card:hover {
        border-color: var(--app-primary);
        box-shadow: 0 4px 14px rgba(2, 132, 199, 0.1);
    }

    .box-alloc-card.selected {
        border-color: var(--app-primary);
        background: #f0f9ff;
    }

    /* --- Modal Bottom Sheet / Mobile Redesign --- */
    .modal-content.premium-modal {
        border-radius: 24px !important;
        border: none !important;
        padding: 1.25rem 1.5rem !important;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2) !important;
    }

    .modal-header.no-border {
        border: none !important;
        padding: 0.5rem 0.5rem 0 !important;
        position: relative;
    }

    .modal-title-custom {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 800;
        font-size: 1.1rem;
        color: var(--app-text-main);
    }

    .btn-close-custom {
        background-color: #f1f5f9 !important;
        border: none !important;
        width: 32px !important;
        height: 32px !important;
        border-radius: 50% !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 0.9rem !important;
        color: #64748b !important;
        transition: all 0.2s ease !important;
        position: absolute !important;
        right: 0 !important;
        top: 0 !important;
        z-index: 1060 !important;
    }

    .btn-close-custom:hover {
        transform: scale(1.1) rotate(90deg);
    }

    .position-radio-group {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        background: #f1f5f9;
        padding: 6px;
        border-radius: 14px;
        gap: 6px;
    }

    .position-radio-item input {
        display: none;
    }

    .position-radio-item label {
        display: block;
        text-align: center;
        padding: 12px 8px;
        border-radius: 10px;
        cursor: pointer;
        font-weight: 700;
        font-size: 0.9rem;
        color: #64748b;
        transition: all 0.2s ease;
        margin-bottom: 0;
    }

    .position-radio-item input#posStart:checked+label {
        background: #ffc107 !important;
        color: #000 !important;
        box-shadow: 0 4px 10px rgba(255, 193, 7, 0.35);
    }

    .position-radio-item input#posMiddle:checked+label {
        background: #0284c7 !important;
        color: #fff !important;
        box-shadow: 0 4px 10px rgba(2, 132, 199, 0.35);
    }

    .position-radio-item input#posEnd:checked+label {
        background: #10b981 !important;
        color: #fff !important;
        box-shadow: 0 4px 10px rgba(16, 185, 129, 0.35);
    }

    .confirm-btn-premium {
        background: linear-gradient(135deg, #0284c7 0%, #0ea5e9 100%) !important;
        color: white !important;
        border: none !important;
        border-radius: 14px !important;
        padding: 0.85rem 1.5rem !important;
        font-weight: 800 !important;
        font-size: 0.95rem !important;
        box-shadow: 0 6px 18px rgba(2, 132, 199, 0.25) !important;
        transition: all 0.2s ease !important;
    }

    .confirm-btn-premium:active {
        transform: scale(0.98);
    }
</style>

<div class="app-mobile-container">
    <div id="printparameterdiv"></div>

    <!-- Mobile App Top Bar Header -->
    <div class="mobile-app-header">
        <div class="d-flex align-items-center gap-3">
            <div class="header-icon-circle">
                <i class="bi bi-box-arrow-in-down"></i>
            </div>
            <div class="header-text-block">
                <h1><?php echo _t('Germination box Received at Polyhouse'); ?></h1>
                <p>Inward Verification & Bed Allocation</p>
            </div>
        </div>
        <button class="btn-app-refresh" onclick="window.location.reload();" title="Refresh Page">
            <i class="fa fa-sync"></i>
        </button>
    </div>

    <!-- Polyhouse Today's KPI Metric Cards -->
    <div class="kpi-row">
        <div class="kpi-card">
            <div class="kpi-icon blue">
                <i class="bi bi-box-seam-fill"></i>
            </div>
            <div>
                <div class="kpi-label">Today Received</div>
                <div class="kpi-value"><?php echo $today_received_count; ?> <span style="font-size:0.75rem; font-weight:600; color:var(--app-text-muted);">Boxes</span></div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon green">
                <i class="bi bi-grid-3x3-gap-fill"></i>
            </div>
            <div>
                <div class="kpi-label">Today Trays</div>
                <div class="kpi-value"><?php echo $today_received_trays; ?> <span style="font-size:0.75rem; font-weight:600; color:var(--app-text-muted);">Trays</span></div>
            </div>
        </div>
    </div>

    <!-- Scanner & Verification Card -->
    <div class="app-card">
        <div class="app-card-header">
            <h2 class="app-card-title">
                <i class="bi bi-qr-code-scan"></i>
                <?php echo _t('box_verification'); ?>
            </h2>
            <span class="badge bg-light text-secondary border px-2 py-1" style="font-size:0.72rem; border-radius:8px;">
                <i class="bi bi-shield-check text-success me-1"></i> Polyhouse Ready
            </span>
        </div>

        <!-- Segmented Mode Switcher Tabs (Manual / Gun is DEFAULT) -->
        <div class="scanner-mode-tabs">
            <button type="button" class="mode-tab-btn" id="tabCameraScan">
                <i class="bi bi-camera"></i> Camera Scanner
            </button>
            <button type="button" class="mode-tab-btn active" id="tabManualInput">
                <i class="bi bi-keyboard"></i> Manual / Gun Input
            </button>
        </div>

        <!-- Mode 1: Manual / Barcode Gun Input View (DEFAULT ACTIVE) -->
        <div id="manualQrCodeSearchDiv">
            <div class="manual-input-wrap">
                <label class="filter-label" style="font-size:0.75rem; font-weight:700; color:var(--app-text-muted); margin-bottom:0.4rem; text-transform:uppercase;">
                    <i class="bi bi-upc-scan me-1 text-primary"></i> Scan Barcode or Type Code
                </label>
                <div class="app-input-group">
                    <span class="app-input-icon"><i class="bi bi-qr-code"></i></span>
                    <input type="text" id="manualQrCode" autocomplete="off" class="app-input-field" placeholder="Enter Box QR / Barcode..." autofocus>
                    <button type="button" class="btn btn-sm btn-link text-muted p-1" onclick="$('#manualQrCode').val('').focus();" title="Clear">
                        <i class="bi bi-x-circle-fill"></i>
                    </button>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" id="fetchManual" class="btn btn-primary w-100 py-2-5 fw-bold" style="border-radius: 12px; font-size: 0.9rem;">
                        <i class="bi bi-check2-circle me-1"></i> <?php echo _t('verify'); ?> & Add Box
                    </button>
                </div>
                <!-- Warning / Alert Message Box directly after verify button -->
                <div id="verifyStatusAlert" class="alert alert-warning mt-2-5 mb-2 d-flex align-items-center justify-content-between shadow-sm" style="display:none; border-radius:12px; font-size:0.85rem; font-weight:600; padding:10px 14px; border:1px solid #fed7aa; background-color:#fff7ed; color:#9a3412;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-exclamation-triangle-fill fs-5 text-warning flex-shrink-0"></i>
                        <span id="verifyStatusMsg"></span>
                    </div>
                    <button type="button" class="btn-close" style="font-size:0.7rem; flex-shrink:0;" onclick="$('#verifyStatusAlert').slideUp(200);" aria-label="Close"></button>
                </div>
                <small class="text-muted d-block mt-2 text-center" style="font-size:0.72rem;">
                    <i class="bi bi-info-circle me-1"></i> Barcode scanners automatically verify on Enter key.
                </small>
            </div>
        </div>

        <!-- Mode 2: Camera Scanner View (Hidden by default) -->
        <div id="qrcodescannerdiv" style="display:none;">
            <!-- Active Camera Viewport (Always properly sized) -->
            <div id="scannerActiveBox">
                <div class="camera-live-wrapper">
                    <!-- Loading Spinner Overlay -->
                    <div id="cameraLoadingSpinner" class="camera-loading-overlay" style="display:none;">
                        <div class="spinner-border text-primary" role="status" style="width:2.2rem; height:2.2rem;"></div>
                        <div class="mt-2 text-white small fw-bold">Connecting Camera...</div>
                    </div>

                    <div id="reader"></div>

                    <div class="scanner-overlay" id="scannerOverlay" style="display:none;">
                        <div class="scan-region-highlight">
                            <div class="scan-line"></div>
                            <div class="scanner-corner corner-tl"></div>
                            <div class="scanner-corner corner-tr"></div>
                            <div class="scanner-corner corner-bl"></div>
                            <div class="scanner-corner corner-br"></div>
                        </div>
                        <div class="scan-success-flash" id="successFlash"></div>
                    </div>
                </div>

                <!-- Camera Selection Dropdown (if multi-camera device) -->
                <div id="cameraSelectDiv" class="mt-2 text-center" style="display: none; max-width: 320px; margin-left:auto; margin-right:auto;">
                    <select id="cameraSelection" class="form-select form-select-sm border-primary-subtle shadow-sm" style="border-radius: 10px; font-size: 0.8rem;"></select>
                </div>

                <!-- Real-time Scanner Status Pill -->
                <div id="scannerStatusDiv" class="mt-2 text-center">
                    <span id="scannerStatus" class="badge bg-light text-secondary border shadow-sm py-2 px-3" style="border-radius: 20px; font-size: 0.78rem; font-weight: 600;">
                        <span class="pulse-dot"></span><span>Scanning for Box QR Code...</span>
                    </span>
                </div>

                <!-- Active Scanner Toolbar -->
                <div class="scanner-controls-grid">
                    <button type="button" id="startScan" class="btn btn-primary btn-mobile-action" style="display:none;">
                        <i class="bi bi-camera-fill"></i> Start Camera
                    </button>
                    <button type="button" id="stopScan" class="btn btn-danger btn-mobile-action">
                        <i class="bi bi-stop-circle-fill"></i> Stop Camera
                    </button>
                    <button type="button" id="toggleFlash" class="btn btn-warning btn-mobile-action text-dark">
                        <i class="bi bi-lightning-fill"></i> Torch
                    </button>
                </div>
            </div>
        </div>

        <div id="noResult" class="alert alert-danger mt-3 text-center" style="display:none; border-radius:12px; font-size:0.85rem;">
            <i class="bi bi-exclamation-triangle me-1"></i> <?php echo _t('no_matching_box'); ?>
        </div>
    </div>

    <!-- Scanned Boxes Section -->
    <div id="BoxDetailsInfo" class="app-card">
        <div class="app-card-header">
            <h2 class="app-card-title">
                <i class="bi bi-list-check"></i> Scanned Boxes Details
            </h2>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary rounded-pill px-2 py-1" id="scannedBoxCountBadge" style="font-size:0.75rem;">0 Boxes</span>
                <button type="button" class="btn btn-xs btn-outline-danger" id="clearScannedBoxes" style="font-size:0.72rem; padding:2px 8px; border-radius:6px; display:none;">
                    <i class="bi bi-trash me-1"></i> Clear
                </button>
            </div>
        </div>

        <!-- Scanned Box Cards Container -->
        <div id="scannedBoxesList" class="scanned-list-container">
            <!-- Empty state -->
            <div class="scanned-empty-state">
                <div class="scanned-empty-icon">
                    <i class="bi bi-box-seam"></i>
                </div>
                <h6 class="fw-bold text-dark mb-1" style="font-size:0.9rem;">No Boxes Scanned Yet</h6>
                <p class="text-muted mb-0" style="font-size:0.75rem;">Scan box QR code using barcode gun or camera above.</p>
            </div>
        </div>

        <!-- Hidden table to preserve any legacy element bindings -->
        <table class="scanned-boxes-table d-none" id="scannedBoxesTable">
            <thead>
                <tr>
                    <th>Product / Stock Order</th>
                    <th>Root Type / Seed Name</th>
                    <th>Sowing Internal Date</th>
                    <th>Box ID</th>
                    <th>Trays</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">No boxes scanned yet.</td>
                </tr>
            </tbody>
            <tfoot>
                <tr class="total-row" style="display:none;">
                    <td colspan="4" class="text-end">Total Trays Scanned:</td>
                    <td id="totalTraysSum">0</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <!-- Live Total Summary Strip -->
        <div id="scannedSummaryStrip" class="scanned-summary-strip" style="display:none;">
            <div>
                <small class="text-muted text-uppercase fw-bold d-block" style="font-size:0.65rem;">Total Trays in Batch</small>
                <span class="fw-800 text-primary fs-5" id="totalTraysSummaryText">0 Trays</span>
            </div>
            <div class="text-end">
                <small class="text-muted text-uppercase fw-bold d-block" style="font-size:0.65rem;">Total Boxes</small>
                <span class="fw-800 text-dark fs-5" id="totalBoxesSummaryText">0 Boxes</span>
            </div>
        </div>

        <!-- Target Polyhouse Plan & Bed Allocation Section (Auto-shown after scan) -->
        <div id="batchActionDiv" class="mt-3" style="display:none;">
            <button type="button" class="btn btn-primary w-100 py-3 rounded-4 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2" id="viewCapacityBtn" style="font-size:0.92rem; background:linear-gradient(135deg, #0284c7 0%, #0ea5e9 100%); border:none;">
                <i class="bi bi-building-check fs-5"></i>
                <span id="capacityBtnText">TARGET POLYHOUSE BED PLAN & CAPACITY</span>
            </button>

            <!-- Target Selection & Capacity View -->
            <div id="polyhouseCapacitySection" class="polyhouse-target-card mt-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0 d-flex align-items-center gap-2" style="font-size:0.9rem; color:var(--app-text-main);">
                        <i class="bi bi-geo-alt-fill text-primary"></i> Target Polyhouse & Bed Plan
                    </h6>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1" style="font-size:0.7rem;">
                        <i class="bi bi-check2-circle me-1"></i> Auto Selected from Box Plan
                    </span>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-12 col-sm-6">
                        <label class="filter-label" style="font-size:0.72rem; font-weight:700; color:var(--app-text-muted); margin-bottom:4px; text-transform:uppercase;">
                            Sowing Location
                        </label>
                        <select id="targetLocationList" class="form-select form-select-sm border-primary-subtle" style="border-radius:10px; height:38px; font-size:0.85rem; font-weight:600;"></select>
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="filter-label" style="font-size:0.72rem; font-weight:700; color:var(--app-text-muted); margin-bottom:4px; text-transform:uppercase;">
                            Target Polyhouse
                        </label>
                        <select id="targetPolyhouseList" class="form-select form-select-sm border-primary-subtle" style="border-radius:10px; height:38px; font-size:0.85rem; font-weight:600;">
                            <option value="">Select Polyhouse</option>
                        </select>
                    </div>
                </div>

                <div id="capacityGridDiv" class="mt-2">
                    <!-- Bed wise cards injected here -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: View Bed Details -->
<div class="modal fade" id="viewBedDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content premium-modal">
            <button type="button" class="btn-close-custom" data-bs-dismiss="modal" aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
            <div class="modal-header no-border">
                <h5 class="modal-title-custom">
                    <i class="bi bi-info-circle text-primary"></i>
                    Bed Details: <span id="modalBedNameLbl"></span>
                </h5>
            </div>
            <div class="modal-body p-2 pt-3">
                <div id="bedDetailsContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary"></div>
                        <p class="mt-2 text-muted small">Loading plant details...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Allocate to Bed (Enlarged for Desktop & Touch-Optimized) -->
<div class="modal fade" id="allocateToBedModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content premium-modal">
            <button type="button" class="btn-close-custom" data-bs-dismiss="modal" aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
            <div class="modal-header no-border">
                <h5 class="modal-title-custom">
                    <i class="bi bi-plus-circle text-primary"></i>
                    Allocate Boxes to <span id="allocBedNameLbl" class="badge bg-primary text-white ms-2 px-3 py-1 rounded-pill"></span>
                </h5>
            </div>
            <div class="modal-body p-2 pt-2">
                <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                    <span class="text-muted small fw-bold text-uppercase" style="letter-spacing:0.3px;">Select boxes to allocate into this bed:</span>
                    <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 fw-bold" id="toggleSelectAllAllocBoxes" style="font-size:0.8rem; color:var(--app-primary);">
                        <i class="bi bi-check2-all me-1"></i> Deselect All
                    </button>
                </div>

                <div id="allocBoxList" class="mb-3">
                    <!-- Dynamic list items injected by JS -->
                </div>

                <div class="mb-4">
                    <label class="filter-label mb-2 ms-1" style="font-size:0.75rem; font-weight:700; color:var(--app-text-muted); text-transform:uppercase;">
                        Position in Bed <span class="text-danger">*</span>
                    </label>
                    <div class="position-radio-group">
                        <div class="position-radio-item">
                            <input type="radio" name="trayPosition" id="posStart" value="Start">
                            <label for="posStart"><i class="bi bi-arrow-left me-1"></i> Start Position</label>
                        </div>
                        <div class="position-radio-item">
                            <input type="radio" name="trayPosition" id="posMiddle" value="Middle">
                            <label for="posMiddle"><i class="bi bi-record-circle me-1"></i> Middle Position</label>
                        </div>
                        <div class="position-radio-item">
                            <input type="radio" name="trayPosition" id="posEnd" value="End">
                            <label for="posEnd"><i class="bi bi-arrow-right me-1"></i> End Position</label>
                        </div>
                    </div>
                </div>

                <button class="btn confirm-btn-premium w-100 py-3 shadow-sm d-flex align-items-center justify-content-center gap-2" id="confirmBedAllocation" style="font-size:1rem; letter-spacing:0.3px;">
                    <i class="bi bi-check2-circle fs-5"></i> CONFIRM & SAVE ALLOCATION TO BED
                </button>
            </div>
        </div>
    </div>
</div>

<?php include('../footer.php'); ?>
<script type="text/javascript" src="<?php echo $prefix; ?>krf-orderandsales/js/pjs_function.js"></script>

<script>
    let qrReader = null;
    let isScanning = false;
    let torchOn = false;
    let scannedBoxes = [];
    let lastScannedCode = null;
    let scanLockTimeout = null;
    let activeBedNo = null;
    let activePhId = null;
    let isCameraListLoaded = false;
    let globalAudioCtx = null;
    let verifyAlertTimer = null;

    function showVerifyWarning(rawMessage, type = 'warning') {
        clearTimeout(verifyAlertTimer);

        let cleanMsg = rawMessage || 'Invalid QR Code.';
        // Translate technical status to clear user-friendly explanation
        if (cleanMsg.includes('ReceivedAfterSowing')) {
            cleanMsg = 'Box status is ReceivedAfterSowing. This box is currently at the Germination Room and must be allocated to Polyhouse first.';
        } else if (cleanMsg.includes('PendingAllocation')) {
            cleanMsg = 'Box allocation is not defined yet from Germination Room.';
        } else if (cleanMsg.includes('ReceivedAtPolyhouse')) {
            cleanMsg = 'This box is already received and verified at Polyhouse.';
        } else if (cleanMsg.includes('Invalid box QR')) {
            cleanMsg = 'Invalid box QR code. No matching germination box found.';
        } else if (cleanMsg.includes('Duplicate') || cleanMsg.includes('already in list')) {
            cleanMsg = 'This box is already in the scanned list below.';
        }

        const isError = (type === 'error' || type === 'danger');
        const borderStyle = isError ? '1px solid #fecaca' : '1px solid #fed7aa';
        const bgStyle = isError ? '#fef2f2' : '#fff7ed';
        const textStyle = isError ? '#991b1b' : '#9a3412';
        const iconClass = isError ? 'bi-x-circle-fill text-danger' : 'bi-exclamation-triangle-fill text-warning';

        $("#verifyStatusAlert")
            .css({
                'border': borderStyle,
                'background-color': bgStyle,
                'color': textStyle
            });
        $("#verifyStatusAlert i").attr("class", `bi ${iconClass} fs-5 flex-shrink-0`);
        $("#verifyStatusMsg").text(cleanMsg);

        // Slide down warning box directly after verify button
        $("#verifyStatusAlert").stop(true, true).slideDown(250);

        if (navigator.vibrate) {
            navigator.vibrate([100, 50, 100]);
        }

        if (isError) {
            iziToast.error({ title: 'Attention', message: cleanMsg, position: 'topRight', timeout: 5000 });
        } else {
            iziToast.warning({ title: 'Warning', message: cleanMsg, position: 'topRight', timeout: 5000 });
        }

        // Automatically hide after 5 seconds
        verifyAlertTimer = setTimeout(() => {
            $("#verifyStatusAlert").slideUp(300);
        }, 5000);
    }

    $(document).ready(function () {
        const projectid = $("body").data("projectid");

        // Focus manual barcode input by default
        setTimeout(() => {
            $("#manualQrCode").focus();
        }, 150);

        // Explicit Close Handler for Custom Close Buttons
        $(document).on("click", ".btn-close-custom", function () {
            $(this).closest(".modal").modal("hide");
        });

        // Tab Mode Switching: Camera Scanner vs Manual / Gun Input
        $("#tabCameraScan").on("click", function () {
            $("#tabCameraScan").addClass("active");
            $("#tabManualInput").removeClass("active");
            $("#manualQrCodeSearchDiv").hide();
            $("#qrcodescannerdiv").show();
            initScanner();
        });

        $("#tabManualInput").on("click", function () {
            $("#tabManualInput").addClass("active");
            $("#tabCameraScan").removeClass("active");
            stopScanner();
            $("#qrcodescannerdiv").hide();
            $("#manualQrCodeSearchDiv").show();
            setTimeout(() => { $("#manualQrCode").focus(); }, 100);
        });

        LoadProductionLocations(projectid, '', 'targetLocationList');

        function LoadProductionLocations(projectid, selectedId, dropdownid) {
            let companyid = $("body").data("companyid");
            let subcompanyid = $("body").data("subcompanyid");
            let routurl = $("body").data("routurl");
            $.ajax({
                url: routurl + "krf-orderandsales/modal/product_modal.php?action=GetProductionLocationList",
                type: "POST",
                data: { projectid: projectid, companyid: companyid, subcompanyid: subcompanyid },
                dataType: "json",
                success: function (res) {
                    let options = '<option value="">All Locations</option>';
                    if (Array.isArray(res)) {
                        res.forEach(function (loc) {
                            options += `<option value="${loc.LocationId}">${loc.LocationName}</option>`;
                        });
                    }
                    $("#" + dropdownid).html(options);
                    if (selectedId) $("#" + dropdownid).val(selectedId).trigger("change");
                }
            });
        }

        /* --- Capacity & Target Selection Logic --- */
        $("#viewCapacityBtn").on("click", function () {
            $("#polyhouseCapacitySection").slideToggle();
        });

        $(document).on("change", "#targetLocationList", function () {
            const locId = $(this).val();
            if (!locId) {
                $("#targetPolyhouseList").html('<option value="">Select Polyhouse</option>');
                $("#capacityGridDiv").empty();
                return;
            }
            GetPolihouseDetailsList('locationid', locId, '', 'targetPolyhouseList');
        });

        $(document).on("change", "#targetPolyhouseList", function () {
            const phId = $(this).val();
            const locId = $("#targetLocationList").val();
            if (!phId) {
                $("#capacityGridDiv").empty();
                return;
            }
            fetchPolyhouseCapacity(phId, locId);
        });

        function fetchPolyhouseCapacity(phId, locId) {
            const routurl = $("body").data("routurl");
            const projectid = $("body").data("projectid");

            $("#capacityGridDiv").html('<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div><span class="ms-2 text-muted small">Loading bed capacity...</span></div>');

            $.ajax({
                url: routurl + "krf-orderandsales/modal/polihouse_modal.php?action=PolyhouseCapacitydetails",
                type: "POST",
                data: { projectid, locationid: locId, polyhouseid: phId },
                dataType: "json",
                success: function (res) {
                    if (!res || !res.bed_wise_details) {
                        $("#capacityGridDiv").html('<p class="text-muted small text-center my-2">No bed capacity records found.</p>');
                        return;
                    }

                    let cardsHtml = '';
                    res.bed_wise_details.forEach(bed => {
                        let total = parseInt(bed.total_capacity) || 0;
                        let used = parseInt(bed.used_trays) || 0;
                        let rem = parseInt(bed.remaining_trays) || 0;
                        let percent = total > 0 ? Math.min(100, Math.round((used / total) * 100)) : 0;

                        let productsHtml = '';
                        if (bed.products && bed.products.length > 0) {
                            productsHtml = `<div class="mt-1 pt-1 border-top" style="font-size:0.7rem; color:var(--app-text-muted);">`;
                            bed.products.forEach(p => {
                                productsHtml += `<div>${p.productname}: <b>${p.count} trays</b></div>`;
                            });
                            productsHtml += `</div>`;
                        }

                        cardsHtml += `
                            <div class="bed-card">
                                <div class="bed-card-header">
                                    <div class="bed-card-title">
                                        <i class="bi bi-grid-fill text-primary me-1"></i> Bed ${bed.bed_no}
                                    </div>
                                    <div class="bed-card-space text-success">
                                        ${rem} Trays Avail
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center" style="font-size:0.72rem; color:var(--app-text-muted);">
                                    <span>Used: <b class="text-danger">${used}</b></span>
                                    <span>Total: <b>${total}</b></span>
                                </div>
                                <div class="bed-progress">
                                    <div class="bed-progress-bar" style="width: ${percent}%;"></div>
                                </div>
                                ${productsHtml}
                                <div class="d-flex gap-2 mt-2 pt-1">
                                    <button class="btn btn-sm btn-primary flex-grow-1 allocate-to-bed-btn" style="border-radius:10px; font-weight:700; font-size:0.78rem; padding:5px 10px;"
                                        data-phid="${phId}" data-bedno="${bed.bed_no}" data-rem="${rem}">
                                        <i class="bi bi-plus-circle me-1"></i> Allocate
                                    </button>
                                    <button class="btn btn-sm btn-outline-info view-bed-details-btn" style="border-radius:10px; font-weight:700; font-size:0.78rem; padding:5px 10px;"
                                        data-phid="${phId}" data-bedno="${bed.bed_no}" title="View bed details">
                                        <i class="bi bi-eye"></i> Details
                                    </button>
                                </div>
                            </div>
                        `;
                    });

                    let totalSummaryHtml = `
                        <div class="p-2 mb-2 rounded-3 bg-white border d-flex justify-content-between align-items-center" style="font-size:0.8rem; font-weight:700;">
                            <span>Capacity: ${res.total_tray_capacity}</span>
                            <span class="text-danger">Used: ${res.total_tray_exists}</span>
                            <span class="text-success">Available: ${res.remaining_total_tray}</span>
                        </div>
                    `;

                    $("#capacityGridDiv").html(totalSummaryHtml + '<div class="bed-cards-grid mt-2">' + cardsHtml + '</div>');
                },
                error: function () {
                    $("#capacityGridDiv").html('<p class="text-danger small text-center my-2">Failed to load bed capacity.</p>');
                }
            });
        }

        /* --- Bed Action Handlers --- */
        $(document).on("click", ".view-bed-details-btn", function () {
            const phId = $(this).data("phid");
            const bedNo = $(this).data("bedno");
            const routurl = $("body").data("routurl");
            const projectid = $("body").data("projectid");

            $("#modalBedNameLbl").text(bedNo);
            $("#viewBedDetailsModal").modal("show");
            $("#bedDetailsContent").html('<div class="text-center py-4"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted small">Loading plant details...</p></div>');

            $.ajax({
                url: routurl + "krf-orderandsales/modal/polihouse_modal.php?action=GetBedWisePlantDetails",
                type: "POST",
                data: { projectid, polyhouseid: phId, bed_no: bedNo },
                dataType: "json",
                success: function (res) {
                    if (res.status === "success" && res.data.length > 0) {
                        let listHtml = `<div class="d-flex flex-column gap-2">`;
                        res.data.forEach(d => {
                            let date = d.planted_date ? new Date(d.planted_date).toLocaleDateString() : '-';
                            listHtml += `
                                <div class="p-2 bg-light rounded-3 border d-flex justify-content-between align-items-center" style="font-size:0.82rem;">
                                    <div>
                                        <div class="fw-bold text-dark">${d.productname}</div>
                                        <small class="text-muted">#${d.stockorder_no} | ${date}</small>
                                    </div>
                                    <span class="badge bg-primary rounded-pill px-2 py-1">${d.tray_count} Trays</span>
                                </div>
                            `;
                        });
                        listHtml += `</div>`;
                        $("#bedDetailsContent").html(listHtml);
                    } else {
                        $("#bedDetailsContent").html('<p class="text-center text-muted my-3 small">No plant data found for this bed.</p>');
                    }
                }
            });
        });

        $(document).on("click", ".allocate-to-bed-btn", function () {
            if (scannedBoxes.length === 0) {
                iziToast.warning({ title: 'No Scanned Box', message: 'Please scan a box first.', position: 'topRight' });
                return;
            }

            activeBedNo = $(this).data("bedno");
            activePhId = $(this).data("phid");

            $("#allocBedNameLbl").text(`Bed-${activeBedNo}`);
            $("#allocBoxList").empty();
            $("input[name='trayPosition']").prop("checked", false);
            $("#toggleSelectAllAllocBoxes").html('<i class="bi bi-check2-all me-1"></i> Deselect All');

            scannedBoxes.forEach((box, idx) => {
                $("#allocBoxList").append(`
                    <div class="box-alloc-card selected" id="alloc_card_${idx}">
                        <div class="d-flex align-items-start gap-2">
                            <input class="form-check-input mt-1 box-alloc-checkbox" type="checkbox" name="boxToAlloc" id="box_check_${idx}" value="${idx}" checked style="width:22px; height:22px; cursor:pointer; flex-shrink:0;">
                            <label class="cursor-pointer d-flex flex-column flex-grow-1" for="box_check_${idx}" style="cursor:pointer; margin-bottom:0;">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="badge bg-primary-subtle text-primary fw-bold" style="font-size:0.78rem;">Box #${box.boxid}</span>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill fw-bold" style="font-size: 0.78rem; padding: 4px 12px;">${box.traycountinbox} Trays</span>
                                </div>
                                <span class="fw-bold text-dark" style="font-size:0.92rem; line-height:1.3;">${box.productname}</span>
                                <div class="d-flex justify-content-between align-items-center mt-2 text-muted flex-wrap gap-1" style="font-size:0.75rem;">
                                    <span>Order: <b>#${box.stockorderno || 'N/A'}</b></span>
                                    <span>Lot: <b>${box.sowinglotno_internal || box.lotno || 'N/A'}</b></span>
                                    ${box.seedname ? `<span>Seed: <b>${box.seedname}</b></span>` : ''}
                                </div>
                            </label>
                        </div>
                    </div>
                `);
            });

            $("#allocateToBedModal").modal("show");
        });

        $(document).on("change", ".box-alloc-checkbox", function () {
            const idx = $(this).val();
            $(`#alloc_card_${idx}`).toggleClass("selected", $(this).is(":checked"));
            const total = $("input[name='boxToAlloc']").length;
            const checked = $("input[name='boxToAlloc']:checked").length;
            if (checked === total) {
                $("#toggleSelectAllAllocBoxes").html('<i class="bi bi-check2-all me-1"></i> Deselect All');
            } else {
                $("#toggleSelectAllAllocBoxes").html('<i class="bi bi-check2-square me-1"></i> Select All');
            }
        });

        $(document).on("click", "#toggleSelectAllAllocBoxes", function () {
            const $checkboxes = $("input[name='boxToAlloc']");
            const allChecked = $checkboxes.length === $checkboxes.filter(":checked").length;
            $checkboxes.prop("checked", !allChecked);
            $(".box-alloc-card").toggleClass("selected", !allChecked);
            $(this).html(!allChecked ? '<i class="bi bi-check2-all me-1"></i> Deselect All' : '<i class="bi bi-check2-square me-1"></i> Select All');
        });

        $("#confirmBedAllocation").on("click", async function () {
            const selectedIndices = [];
            $("input[name='boxToAlloc']:checked").each(function () {
                selectedIndices.push($(this).val());
            });

            if (selectedIndices.length === 0) {
                iziToast.warning({ title: 'Selection Required', message: 'Please select at least one box.', position: 'topRight' });
                return;
            }

            const routurl = $("body").data("routurl");
            const companyid = $("body").data("companyid");
            const subcompanyid = $("body").data("subcompanyid");
            const projectid = $("body").data("projectid");
            const loginid = $("body").data("loginid");

            const boxIds = selectedIndices.map(idx => scannedBoxes[idx].boxid);
            const trayPosition = $("input[name='trayPosition']:checked").val();

            if (!trayPosition) {
                iziToast.error({ title: 'Position Missing', message: 'Please select a Position (Start, Middle, or End).', position: 'topRight' });
                return false;
            }

            const fd = new FormData();
            fd.append("companyid", companyid);
            fd.append("subcompanyid", subcompanyid);
            fd.append("projectid", projectid);
            fd.append("loginid", loginid);
            fd.append("boxids", JSON.stringify(boxIds));
            fd.append("polyhouseid", activePhId);
            fd.append("bedrackname", activeBedNo);
            fd.append("placeofrow", trayPosition);
            fd.append("gfailurestatus", "Healthy");

            const $btn = $(this);
            $btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-2"></span>Allocating...');

            try {
                const response = await fetch(routurl + "krf-orderandsales/modal/polihouse_modal.php?action=UpdateTrayAllocationToPolyhouse", {
                    method: 'POST',
                    body: fd
                });
                const res = await response.json();
                $btn.prop("disabled", false).html('<i class="bi bi-check-lg me-1"></i> CONFIRM ALLOCATION');

                if (res.status === "success") {
                    iziToast.success({ title: 'Success', message: res.message, position: 'topRight' });
                    // Remove allocated boxes from list
                    boxIds.forEach(id => {
                        const idx = scannedBoxes.findIndex(b => b.boxid == id);
                        if (idx !== -1) scannedBoxes.splice(idx, 1);
                    });
                    renderScannedBoxes();
                    $("#allocateToBedModal").modal("hide");
                    // Refresh capacity grid
                    const locId = $("#targetLocationList").val();
                    if (activePhId && locId) {
                        fetchPolyhouseCapacity(activePhId, locId);
                    }
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            } catch (err) {
                $btn.prop("disabled", false).html('<i class="bi bi-check-lg me-1"></i> CONFIRM ALLOCATION');
                Swal.fire('Error', 'Failed to communicate with server', 'error');
            }
        });

        /* --- Audio & Haptics Feedback --- */
        function playBeep() {
            try {
                if (!globalAudioCtx) {
                    globalAudioCtx = new (window.AudioContext || window.webkitAudioContext)();
                }
                if (globalAudioCtx.state === 'suspended') {
                    globalAudioCtx.resume();
                }
                const osc = globalAudioCtx.createOscillator();
                const gain = globalAudioCtx.createGain();
                osc.connect(gain);
                gain.connect(globalAudioCtx.destination);
                osc.type = 'sine';
                osc.frequency.setValueAtTime(880, globalAudioCtx.currentTime);
                gain.gain.setValueAtTime(0.12, globalAudioCtx.currentTime);
                osc.start();
                osc.stop(globalAudioCtx.currentTime + 0.12);
            } catch (e) {
                console.warn("Audio feedback error:", e);
            }
        }

        // Camera detection
        function loadCameras(activeTrackLabel = null) {
            if (isCameraListLoaded) {
                if (activeTrackLabel) {
                    Html5Qrcode.getCameras().then(cameras => {
                        const activeCam = cameras.find(c => c.label === activeTrackLabel);
                        if (activeCam) $("#cameraSelection").val(activeCam.id);
                    }).catch(() => {});
                }
                return Promise.resolve();
            }

            return new Promise((resolve) => {
                if (typeof Html5Qrcode === "undefined") {
                    resolve([]);
                    return;
                }
                Html5Qrcode.getCameras().then(cameras => {
                    if (cameras && cameras.length > 0) {
                        let options = '';
                        cameras.forEach((camera, index) => {
                            let label = camera.label || `Camera ${index + 1}`;
                            if (label.toLowerCase().includes('back') || label.toLowerCase().includes('environment') || label.toLowerCase().includes('rear')) {
                                label = `Rear Camera ${index + 1} 📷`;
                            } else if (label.toLowerCase().includes('front') || label.toLowerCase().includes('user')) {
                                label = `Front Camera ${index + 1} 🤳`;
                            }
                            options += `<option value="${camera.id}">${label}</option>`;
                        });
                        $("#cameraSelection").html(options);
                        isCameraListLoaded = true;

                        if (cameras.length > 1) {
                            $("#cameraSelectDiv").slideDown();
                        }

                        let activeCam = cameras.find(c => {
                            const lbl = (c.label || '').toLowerCase();
                            return lbl.includes('back') || lbl.includes('environment') || lbl.includes('rear');
                        });
                        if (!activeCam && cameras.length > 0) activeCam = cameras[0];
                        if (activeCam) $("#cameraSelection").val(activeCam.id);
                        resolve(cameras);
                    } else {
                        resolve([]);
                    }
                }).catch(err => {
                    console.warn("Camera query error:", err);
                    resolve([]);
                });
            });
        }

        function initScanner(cameraId = null) {
            if (typeof Html5Qrcode === "undefined") {
                Swal.fire('Scanner Library', 'Html5Qrcode library is still loading or unavailable.', 'error');
                return;
            }
            if (isScanning) return;

            $("#cameraLoadingSpinner").show();
            $("#scannerOverlay").hide();
            $("#startScan").hide();
            $("#stopScan").show();
            $("#scannerStatus")
                .html('<span class="pulse-dot"></span><span>Connecting camera...</span>')
                .removeClass('bg-success text-white bg-warning text-dark bg-danger text-white')
                .addClass('bg-light text-secondary');

            if (!qrReader) {
                qrReader = new Html5Qrcode("reader");
            }
            isScanning = true;

            const selectedCameraId = cameraId || $("#cameraSelection").val();
            let cameraTarget = selectedCameraId ? selectedCameraId : { facingMode: "environment" };

            const config = {
                fps: 15,
                qrbox: { width: 220, height: 220 },
                formatsToSupport: [
                    typeof Html5QrcodeSupportedFormats !== "undefined"
                        ? Html5QrcodeSupportedFormats.QR_CODE
                        : 0
                ],
                experimentalFeatures: {
                    useBarCodeDetectorIfSupported: true
                }
            };

            function startCameraStream(target, attempt = 1) {
                qrReader.start(
                    target,
                    config,
                    onScanSuccess,
                    onScanFailure
                ).then(() => {
                    $("#cameraLoadingSpinner").fadeOut(200);
                    $("#scannerOverlay").fadeIn();
                    $("#scannerStatus")
                        .html('<span class="pulse-dot"></span><span>Scanning for Box QR Code...</span>')
                        .removeClass('bg-success text-white bg-warning text-dark bg-danger text-white')
                        .addClass('bg-light text-secondary');

                    let activeTrackLabel = null;
                    try {
                        const track = qrReader.getRunningTrack();
                        if (track) {
                            activeTrackLabel = track.label;
                            const capabilities = track.getCapabilities ? track.getCapabilities() : {};
                            const constraints = { advanced: [] };
                            let appliedAny = false;

                            if (capabilities.focusMode && capabilities.focusMode.includes("continuous")) {
                                constraints.advanced.push({ focusMode: "continuous" });
                                appliedAny = true;
                            }

                            if (capabilities.zoom) {
                                let minZoom = capabilities.zoom.min || 1;
                                let maxZoom = capabilities.zoom.max || 1;
                                let idealZoom = Math.min(Math.max(1.5, minZoom), maxZoom);
                                constraints.advanced.push({ zoom: idealZoom });
                                appliedAny = true;
                            }

                            if (appliedAny && track.applyConstraints) {
                                track.applyConstraints(constraints).catch(() => {});
                            }
                        }
                    } catch (e) {
                        console.warn("Track constraints error:", e);
                    }

                    loadCameras(activeTrackLabel);

                }).catch(err => {
                    console.warn(`Camera attempt ${attempt} failed:`, err);
                    if (attempt === 1) {
                        // Fallback 1: Try front / user-facing webcam
                        startCameraStream({ facingMode: "user" }, 2);
                    } else if (attempt === 2) {
                        // Fallback 2: Try first enumerated camera device
                        Html5Qrcode.getCameras().then(cameras => {
                            if (cameras && cameras.length > 0) {
                                startCameraStream(cameras[0].id, 3);
                            } else {
                                handleCameraFail(err);
                            }
                        }).catch(() => {
                            handleCameraFail(err);
                        });
                    } else {
                        handleCameraFail(err);
                    }
                });
            }

            function handleCameraFail(err) {
                isScanning = false;
                $("#cameraLoadingSpinner").hide();
                $("#startScan").show();
                $("#stopScan").hide();
                let errorMsg = 'Could not access camera stream. Please check camera permissions in your browser.';
                if (window.location.protocol !== 'https:' && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
                    errorMsg += ' (Note: Browsers require HTTPS or localhost for webcam access)';
                }
                Swal.fire({
                    icon: 'warning',
                    title: 'Camera Access Needed',
                    text: errorMsg,
                    confirmButtonText: 'OK'
                });
            }

            startCameraStream(cameraTarget, 1);
        }

        window.stopScanner = function () {
            if (qrReader && isScanning) {
                qrReader.stop().then(() => {
                    isScanning = false;
                    $("#cameraLoadingSpinner").hide();
                    $("#scannerOverlay").hide();
                    $("#startScan").show();
                    $("#stopScan").hide();
                    $("#scannerStatus")
                        .html('<i class="bi bi-pause-circle text-muted me-1"></i><span>Camera paused</span>')
                        .removeClass('bg-success text-white bg-danger text-white')
                        .addClass('bg-light text-secondary');
                }).catch(err => {
                    console.warn("Stop failed", err);
                    isScanning = false;
                    $("#cameraLoadingSpinner").hide();
                    $("#startScan").show();
                    $("#stopScan").hide();
                });
            } else {
                isScanning = false;
                $("#cameraLoadingSpinner").hide();
                $("#startScan").show();
                $("#stopScan").hide();
            }
        };

        function onScanSuccess(decodedText) {
            if (decodedText === lastScannedCode) return;
            lastScannedCode = decodedText;
            clearTimeout(scanLockTimeout);
            scanLockTimeout = setTimeout(() => { lastScannedCode = null; }, 3000);

            $("#scannerStatus")
                .html('<i class="bi bi-check-circle-fill text-white me-1"></i><span>QR Read: ' + decodedText + '</span>')
                .removeClass('bg-light text-secondary bg-warning text-dark bg-danger text-white')
                .addClass('bg-success text-white');

            playBeep();
            if (navigator.vibrate) navigator.vibrate([80, 40, 80]);
            $("#successFlash").show().fadeOut(350);

            setTimeout(() => {
                if (isScanning) {
                    $("#scannerStatus")
                        .html('<span class="pulse-dot"></span><span>Scanning for Box QR Code...</span>')
                        .removeClass('bg-success text-white')
                        .addClass('bg-light text-secondary');
                }
            }, 1600);

            fetchGerminatedBoxreceivedbyph(decodedText);
        }

        function onScanFailure(error) { }

        $("#cameraSelection").on("change", function () {
            const selectedCameraId = $(this).val();
            if (qrReader && isScanning) {
                qrReader.stop().then(() => {
                    isScanning = false;
                    initScanner(selectedCameraId);
                });
            }
        });

        $("#toggleFlash").on("click", async function () {
            if (!qrReader || !isScanning) return;
            try {
                const track = qrReader.getRunningTrack();
                if (track) {
                    torchOn = !torchOn;
                    await track.applyConstraints({ advanced: [{ torch: torchOn }] });
                    $(this).toggleClass("btn-warning btn-outline-warning");
                }
            } catch (err) {
                console.warn("Torch error:", err);
            }
        });

        function fetchGerminatedBoxreceivedbyph(qrCodeValue) {
            qrCodeValue = qrCodeValue.trim();
            if (!qrCodeValue) return false;

            const routurl = $("body").data("routurl");
            const companyid = $("body").data("companyid");
            const subcompanyid = $("body").data("subcompanyid");
            const projectid = $("body").data("projectid");

            if (scannedBoxes.some(b => b.qrcode === qrCodeValue)) {
                $("#manualQrCode").val("").focus();
                showVerifyWarning('Box already in list.', 'warning');
                return;
            }

            $.ajax({
                url: routurl + "krf-orderandsales/modal/polihouse_modal.php?action=fetchallocatedGBoxDataWithQRCode",
                type: "POST",
                dataType: "json",
                data: { qrcode: qrCodeValue, companyid, subcompanyid, projectid },
                success: function (res) {
                    if (!res || res.status === "error" || !res.boxid) {
                        $("#manualQrCode").val("").focus();
                        const errMsg = (res && res.message) ? res.message : 'Invalid box QR.';
                        showVerifyWarning(errMsg, 'warning');
                        return;
                    }

                    if (res.receivedstatus === 'ReceivedAtPolyhouse') {
                        $("#manualQrCode").val("").focus();
                        showVerifyWarning('This box is already verified at polyhouse.', 'warning');
                        return;
                    }

                    // On success, hide any warning message
                    clearTimeout(verifyAlertTimer);
                    $("#verifyStatusAlert").slideUp(150);

                    res.qrcode = qrCodeValue;
                    scannedBoxes.push(res);
                    renderScannedBoxes();
                    $("#manualQrCode").val("").focus();

                    /* --- Automatically Fetch and Show Polyhouse Bed Capacity & Plan --- */
                    const targetLocId = res.polyhouse_locationid || $("#targetLocationList").val();
                    const targetPhId = res.polyhouseid || '';

                    if (targetLocId) {
                        $("#targetLocationList").val(targetLocId);
                        GetPolihouseDetailsList('locationid', targetLocId, targetPhId, 'targetPolyhouseList');
                    }

                    if (targetPhId && targetLocId) {
                        fetchPolyhouseCapacity(targetPhId, targetLocId);
                    }

                    // Auto open target selection & bed plan section
                    $("#batchActionDiv").show();
                    $("#polyhouseCapacitySection").slideDown();

                    iziToast.success({
                        title: 'Box Verified',
                        message: `Box #${res.boxid} verified. Target: ${res.polyhousename || 'Polyhouse'}`,
                        position: 'topRight'
                    });
                },
                error: function () {
                    $("#manualQrCode").val("").focus();
                    showVerifyWarning('Failed to communicate with server. Please try again.', 'error');
                }
            });
        }

        function renderScannedBoxes() {
            const $list = $("#scannedBoxesList").empty();
            const $tbody = $("#scannedBoxesTable tbody").empty();
            let totalTrays = 0;

            if (scannedBoxes.length === 0) {
                $list.html(`
                    <div class="scanned-empty-state">
                        <div class="scanned-empty-icon">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-1" style="font-size:0.9rem;">No Boxes Scanned Yet</h6>
                        <p class="text-muted mb-0" style="font-size:0.75rem;">Scan box QR code using barcode gun or manual input above.</p>
                    </div>
                `);
                $tbody.append('<tr><td colspan="6" class="text-center py-4 text-muted">No boxes scanned yet.</td></tr>');
                $("#batchActionDiv").hide();
                $(".total-row").hide();
                $("#scannedSummaryStrip").hide();
                $("#clearScannedBoxes").hide();
                $("#scannedBoxCountBadge").text("0 Boxes");
                return;
            }

            scannedBoxes.forEach((box, index) => {
                const trays = parseInt(box.traycountinbox) || 0;
                totalTrays += trays;

                // Render Mobile Card Item with full Plan Information
                $list.append(`
                    <div class="scanned-box-card">
                        <div class="box-card-top">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="box-id-badge">
                                    <i class="bi bi-box-seam text-primary"></i> #${box.boxid}
                                </span>
                                <span class="box-tray-badge">
                                    <i class="bi bi-grid-3x3"></i> ${trays} Trays
                                </span>
                                ${box.polyhousename ? `<span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:0.72rem; border-radius:6px;"><i class="bi bi-building me-1"></i>${box.polyhousename}</span>` : ''}
                            </div>
                            <button type="button" class="btn-remove-box remove-box" data-index="${index}" title="Remove Box">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </div>
                        <h4 class="box-product-title">${box.productname}</h4>
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-1 mb-1">
                            <span class="box-order-sub mb-0">#${box.stockorderno}</span>
                            ${box.totalbox ? `<span class="badge bg-light text-secondary border" style="font-size:0.7rem;">Box ${box.allocatedbox || 1} of ${box.totalbox} (${box.totaltrayinorder || (box.totalbox * trays)} Trays Total)</span>` : ''}
                        </div>
                        <div class="box-meta-grid">
                            <div class="box-meta-item">
                                <span class="box-meta-label">Root Type</span>
                                <span class="box-meta-val">${box.roottype || 'N/A'}</span>
                            </div>
                            <div class="box-meta-item">
                                <span class="box-meta-label">Seed Name</span>
                                <span class="box-meta-val">${box.seedname || 'N/A'}</span>
                            </div>
                            <div class="box-meta-item">
                                <span class="box-meta-label">Sowing Date</span>
                                <span class="box-meta-val">${box.sowingdate || box.boxpackingdatedsp || 'N/A'}</span>
                            </div>
                            <div class="box-meta-item">
                                <span class="box-meta-label">Delivery Date</span>
                                <span class="box-meta-val">${box.deliverydate || 'N/A'}</span>
                            </div>
                            <div class="box-meta-item">
                                <span class="box-meta-label">Internal Lot</span>
                                <span class="box-meta-val">${box.sowinglotno_internal || box.lotno || 'N/A'}</span>
                            </div>
                            <div class="box-meta-item">
                                <span class="box-meta-label">Planned Polyhouse</span>
                                <span class="box-meta-val text-primary fw-bold">${box.polyhousename || 'Not Assigned'}</span>
                            </div>
                        </div>
                    </div>
                `);

                // Also populate hidden table for compatibility
                $tbody.append(`
                    <tr>
                        <td>
                            <div class="fw-bold text-primary">${box.productname}</div>
                            <div class="small text-muted">#${box.stockorderno}</div>
                        </td>
                        <td>
                            <div class="fw-bold text-dark">${box.roottype || 'N/A'}</div>
                            <div class="small text-muted">${box.seedname || 'N/A'}</div>
                        </td>
                        <td>${box.boxpackingdatedsp || 'N/A'}</td>
                        <td>#${box.boxid}</td>
                        <td>${box.traycountinbox}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-danger remove-box" data-index="${index}"><i class="bi bi-x"></i></button>
                        </td>
                    </tr>
                `);
            });

            $("#totalTraysSum").text(totalTrays);
            $("#totalTraysSummaryText").text(`${totalTrays} Trays`);
            $("#totalBoxesSummaryText").text(`${scannedBoxes.length} Boxes`);
            $("#scannedBoxCountBadge").text(`${scannedBoxes.length} Boxes`);

            $(".total-row").show();
            $("#scannedSummaryStrip").show();
            $("#clearScannedBoxes").show();
            $("#batchActionDiv").show();
        }

        $(document).on("click", ".remove-box", function () {
            scannedBoxes.splice($(this).data("index"), 1);
            renderScannedBoxes();
        });

        $("#clearScannedBoxes").on("click", function () {
            scannedBoxes = [];
            renderScannedBoxes();
        });

        $("#fetchManual").on("click", function () {
            const qrValue = $("#manualQrCode").val();
            if (qrValue) fetchGerminatedBoxreceivedbyph(qrValue);
        });

        $("#manualQrCode").on("keypress", function (e) {
            if (e.which === 13) {
                e.preventDefault();
                fetchGerminatedBoxreceivedbyph($(this).val());
            }
        });

        $("#startScan").on("click", () => initScanner());
        $("#stopScan").on("click", stopScanner);

        $("#ManualStartScan").on("click", function () {
            $("#tabManualInput").trigger("click");
        });
        $("#QRcodeStartScan").on("click", function () {
            $("#tabCameraScan").trigger("click");
            initScanner();
        });

        /* --- Batch Fallback Submission Handler --- */
        $("#submitVerificationBatch").on("click", async function () {
            const polyhouseid = $("#targetPolyhouseList").val();
            if (!polyhouseid) {
                iziToast.error({ title: 'Select Polyhouse', message: 'Target polyhouse is required.', position: 'topRight' });
                $("#polyhouseCapacitySection").slideDown();
                return;
            }

            const confirm = await Swal.fire({
                title: 'Verify Batch?',
                text: `Process ${scannedBoxes.length} boxes into the selected polyhouse?`,
                icon: 'question',
                showCancelButton: true
            });

            if (!confirm.isConfirmed) return;

            const routurl = $("body").data("routurl");
            const companyid = $("body").data("companyid");
            const subcompanyid = $("body").data("subcompanyid");
            const projectid = $("body").data("projectid");
            const loginid = $("body").data("loginid");

            Swal.fire({ title: 'Processing...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

            let successCount = 0;
            for (const box of scannedBoxes) {
                const fd = new FormData();
                fd.append("companyid", companyid);
                fd.append("subcompanyid", subcompanyid);
                fd.append("projectid", projectid);
                fd.append("loginid", loginid);
                fd.append("boxid", box.boxid);
                fd.append("stockorderno", box.stockorderno);
                fd.append("polyhouseid", polyhouseid);
                fd.append("allocation_mode", "single");

                try {
                    const response = await fetch(routurl + "krf-orderandsales/modal/polihouse_modal.php?action=SubmitGerminationBoxVerificationForPolyhouse", {
                        method: 'POST',
                        body: fd
                    });
                    const res = await response.json();
                    if (res.status === "success") successCount++;
                } catch (err) { }
            }

            Swal.close();
            Swal.fire({ title: 'Complete', text: `Verified ${successCount} boxes.`, icon: 'success' }).then(() => {
                scannedBoxes = [];
                renderScannedBoxes();
                $("#polyhouseCapacitySection").slideUp();
                $("#capacityGridDiv").empty();
            });
        });
    });
</script>
