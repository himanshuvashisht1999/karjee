<?php
include('../header.php');
date_default_timezone_set("Asia/Calcutta");

$logoPath = __DIR__ . '/../img/logo/logo.png';
$krfLogoBase64 = '';
if (file_exists($logoPath)) {
    $logoData = file_get_contents($logoPath);
    $krfLogoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
}
?>
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- html2pdf.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
    window.KRF_COMPANY_LOGO = <?php echo json_encode($krfLogoBase64 ?? ''); ?>;
</script>

<style>
    /* ==========================================================================
       Ensure SweetAlert2 popups & backdrops always appear on top of all modals
       (Fixes confirmation dialog hiding behind #closePendingQtyModal)
       ========================================================================== */
    body .swal2-container,
    div.swal2-container,
    .swal2-container {
        z-index: 99999999 !important;
    }

    /* ==========================================================================
       Fix for #orderGrowthStatusModal: Ensure modal & footer buttons are 100% visible
       ========================================================================== */
    #orderGrowthStatusModal {
        overflow: hidden !important;
    }

    #orderGrowthStatusModal .modal-dialog {
        width: 100vw !important;
        max-width: 100vw !important;
        height: 100vh !important;
        max-height: 100vh !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    #orderGrowthStatusModal .modal-content {
        height: 100vh !important;
        max-height: 100vh !important;
        display: flex !important;
        flex-direction: column !important;
        border-radius: 0 !important;
        overflow: hidden !important;
    }

    #orderGrowthStatusModal .modal-header {
        flex-shrink: 0 !important;
        padding-top: 0.65rem !important;
        padding-bottom: 0.65rem !important;
    }

    #orderGrowthStatusModal .modal-body {
        flex: 1 1 auto !important;
        min-height: 0 !important;        /* CRITICAL: Allows flex child to shrink properly */
        overflow-y: auto !important;      /* Only the body scrolls, never the whole window */
        overflow-x: hidden !important;
        -webkit-overflow-scrolling: touch;
    }

    #orderGrowthStatusModal .modal-footer {
        flex-shrink: 0 !important;        /* CRITICAL: Footer will NEVER be compressed or pushed off */
        position: sticky !important;
        bottom: 0 !important;
        background-color: #ffffff !important;
        z-index: 1055 !important;
        box-shadow: 0 -3px 12px rgba(0, 0, 0, 0.08) !important;
        padding: 8px 16px !important;
        padding-bottom: max(10px, env(safe-area-inset-bottom)) !important;
    }

    #orderGrowthStatusModal #orderGrowthModalFooterNotice {
        flex: 1 1 320px !important;
        max-width: calc(100% - 360px);
    }

    @media (max-width: 991.98px) {
        #orderGrowthStatusModal #orderGrowthModalFooterNotice {
            max-width: 100% !important;
            font-size: 11px !important;
        }
    }

    @media (max-width: 767.98px) {
        #orderGrowthStatusModal #orderGrowthModalFooterNotice {
            display: none !important;
        }
        #orderGrowthStatusModal .modal-footer {
            justify-content: flex-end !important;
        }
        #orderGrowthStatusModal .modal-footer > div:last-child {
            width: 100% !important;
            display: flex !important;
            justify-content: flex-end !important;
            gap: 8px !important;
        }
    }

    /* Shifted Plants YES/NO Toggle Button with Green Active State & Glowing Border Animation */
    @keyframes pulseGreenGlow {
        0% {
            box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.7);
            border-color: #198754;
        }
        50% {
            box-shadow: 0 0 0 6px rgba(25, 135, 84, 0.25);
            border-color: #198754;
        }
        100% {
            box-shadow: 0 0 0 0 rgba(25, 135, 84, 0);
            border-color: #198754;
        }
    }

    .shifted-toggle-btn {
        transition: all 0.2s ease-in-out;
        font-weight: bold !important;
        font-size: 13px !important;
        cursor: pointer;
    }

    .shifted-toggle-radio:checked + .shifted-toggle-btn {
        background-color: #198754 !important;
        border-color: #198754 !important;
        color: #ffffff !important;
        animation: pulseGreenGlow 1.5s infinite !important;
        position: relative;
        z-index: 2;
    }

    .shifted-toggle-radio:not(:checked) + .shifted-toggle-btn {
        background-color: #5c636a !important;
        border-color: #5c636a !important;
        color: #ffffff !important;
        opacity: 0.9;
    }
    .shifted-toggle-radio:not(:checked) + .shifted-toggle-btn:hover {
        opacity: 1;
        background-color: #495057 !important;
    }

    /* Stock Order Selection Grid Table */
    #modalStockOrderGridTable {
        width: 100% !important;
        border-collapse: collapse !important;
    }
    #modalStockOrderGridTable thead th {
        background-color: #f1f3f5 !important;
        position: sticky;
        top: 0;
        z-index: 2;
        border-bottom: 2px solid #ced4da;
        vertical-align: middle !important;
    }
    #modalStockOrderGridTable th:first-child,
    #modalStockOrderGridTable td:first-child {
        width: 55px !important;
        min-width: 55px !important;
        max-width: 55px !important;
        text-align: center !important;
        vertical-align: middle !important;
        padding: 6px 4px !important;
    }
    #modalStockOrderGridTable .stockorder-table-checkbox,
    #modalStockOrderGridTable .stockorder-table-radio {
        position: static !important;
        margin: 0 auto !important;
        display: block !important;
        width: 18px !important;
        height: 18px !important;
        min-width: 18px !important;
        min-height: 18px !important;
        max-width: 18px !important;
        max-height: 18px !important;
        cursor: pointer !important;
        accent-color: #0d6efd !important;
        transform: none !important;
        vertical-align: middle !important;
        opacity: 1 !important;
        visibility: visible !important;
        float: none !important;
        box-sizing: border-box !important;
    }
    /* Fix for Bed Selection Pills & Checkboxes - Prevent Bootstrap 4 absolute displacement */
    .batch-bed-checkbox,
    #modalStockOrderGridTable .batch-bed-checkbox,
    .bed-select-pill input[type="checkbox"] {
        position: static !important;
        float: none !important;
        margin: 0 !important;
        margin-right: 4px !important;
        padding: 0 !important;
        display: inline-block !important;
        width: 14px !important;
        height: 14px !important;
        min-width: 14px !important;
        min-height: 14px !important;
        max-width: 14px !important;
        max-height: 14px !important;
        cursor: pointer !important;
        accent-color: #10b981 !important;
        vertical-align: middle !important;
        box-sizing: border-box !important;
        opacity: 1 !important;
        visibility: visible !important;
        transform: none !important;
    }
    .bed-select-pill {
        position: relative !important;
        display: inline-flex !important;
        align-items: center !important;
        vertical-align: middle !important;
        gap: 3px !important;
        margin: 2px 3px 2px 0 !important;
        padding: 3px 8px !important;
        border-radius: 6px !important;
        font-size: 11px !important;
        font-weight: 500 !important;
        cursor: pointer !important;
        user-select: none !important;
        background-color: #f8fafc !important;
        border: 1px solid #cbd5e1 !important;
        color: #1e293b !important;
        transition: all 0.15s ease-in-out !important;
        line-height: 1.2 !important;
    }
    .bed-select-pill:hover {
        background-color: #f1f5f9 !important;
        border-color: #94a3b8 !important;
    }
    .bed-select-pill.bed-checked {
        background-color: #ecfdf5 !important;
        border-color: #10b981 !important;
        color: #065f46 !important;
        box-shadow: 0 1px 2px rgba(16, 185, 129, 0.15) !important;
    }
    #modalStockOrderGridTable tbody tr {
        cursor: pointer;
        transition: background-color 0.15s ease-in-out;
    }
    #modalStockOrderGridTable tbody tr:hover {
        background-color: #f8f9fa;
    }
    #modalStockOrderGridTable tbody tr.stockorder-row-selected {
        background-color: #d1e7dd !important;
    }
    #modalStockOrderGridTable tbody tr.stockorder-row-selected td {
        background-color: #d1e7dd !important;
        font-weight: 600;
    }
    #modalStockOrderGridTable td {
        vertical-align: middle !important;
    }

    /* Bulletproof custom variety switch slider */
    .custom-variety-toggle {
        display: inline-flex !important;
        align-items: center !important;
        gap: 8px !important;
        margin-left: 10px !important;
        padding: 3px 10px 3px 6px !important;
        background-color: #f1f3f5 !important;
        border: 1px solid #ced4da !important;
        border-radius: 30px !important;
        cursor: pointer !important;
        user-select: none !important;
        vertical-align: middle !important;
    }

    .custom-variety-input {
        position: absolute !important;
        opacity: 0 !important;
        width: 0 !important;
        height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        pointer-events: none !important;
    }

    .custom-variety-slider {
        position: relative !important;
        display: inline-block !important;
        width: 32px !important;
        height: 18px !important;
        background-color: #adb5bd !important;
        border-radius: 20px !important;
        transition: background-color 0.25s ease-in-out !important;
        flex-shrink: 0 !important;
    }

    .custom-variety-slider::after {
        content: "" !important;
        position: absolute !important;
        top: 2px !important;
        left: 2px !important;
        width: 14px !important;
        height: 14px !important;
        background-color: #ffffff !important;
        border-radius: 50% !important;
        transition: transform 0.25s ease-in-out !important;
        box-shadow: 0 1px 3px rgba(0,0,0,0.3) !important;
    }

    .custom-variety-input:checked + .custom-variety-slider {
        background-color: #198754 !important;
    }

    .custom-variety-input:checked + .custom-variety-slider::after {
        transform: translateX(14px) !important;
    }

    .custom-variety-label {
        font-size: 11px !important;
        font-weight: 700 !important;
        color: #495057 !important;
        line-height: 1 !important;
        margin: 0 !important;
    }

    /* Custom Modern Toggle Switch (Fix for clipped knob / alignment bug in modal) */
    .dinv-toggle-switch {
        position: relative !important;
        display: inline-block !important;
        width: 44px !important;
        height: 24px !important;
        margin: 0 !important;
        padding: 0 !important;
        cursor: pointer !important;
        vertical-align: middle !important;
        user-select: none !important;
        flex-shrink: 0 !important;
    }
    .dinv-toggle-switch input {
        position: absolute !important;
        opacity: 0 !important;
        width: 0 !important;
        height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        pointer-events: none !important;
    }
    .dinv-toggle-switch .dinv-toggle-track {
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        background-color: #cbd5e1 !important;
        border: 1.5px solid #94a3b8 !important;
        border-radius: 24px !important;
        transition: background-color 0.25s ease, border-color 0.25s ease !important;
        box-sizing: border-box !important;
        display: block !important;
    }
    .dinv-toggle-switch .dinv-toggle-thumb {
        position: absolute !important;
        height: 16px !important;
        width: 16px !important;
        left: 2.5px !important;
        top: 2.5px !important;
        background-color: #ffffff !important;
        border-radius: 50% !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.28) !important;
        transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
        box-sizing: border-box !important;
        display: block !important;
    }
    .dinv-toggle-switch input:checked + .dinv-toggle-track {
        background-color: #059669 !important;
        border-color: #047857 !important;
    }
    .dinv-toggle-switch input:focus + .dinv-toggle-track {
        box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.25) !important;
    }
    .dinv-toggle-switch input:checked + .dinv-toggle-track .dinv-toggle-thumb {
        transform: translateX(20px) !important;
    }

    /* Select2 custom adjustments */
    .select2-container--bootstrap-5 .select2-selection,
    .select2-container--default .select2-selection--single {
        height: 38px !important;
        border: 1px solid #ced4da !important;
        border-radius: 6px !important;
        display: flex !important;
        align-items: center !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px !important;
        color: #212529 !important;
        font-weight: 500 !important;
        font-size: 13px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
    }
    .select2-dropdown {
        font-size: 13px !important;
        border-radius: 6px !important;
        border: 1px solid #ced4da !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
    }
    .select2-search__field {
        border-radius: 4px !important;
        padding: 6px 10px !important;
    }
    /* Assign Vehicle, Transporter & Driver Modal sizing & Select2 adjustments */
    #assignVehicleDriverModal .modal-dialog {
        max-width: 980px !important;
        width: 95vw !important;
        margin: 15px auto !important;
    }
    #assignVehicleDriverModal .select2-container {
        width: 100% !important;
    }
    #assignVehicleDriverModal .select2-container .select2-selection--single {
        height: 34px !important;
        border: 1px solid #ced4da !important;
        border-radius: 6px !important;
        display: flex !important;
        align-items: center !important;
    }
    #assignVehicleDriverModal .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 32px !important;
        font-size: 13px !important;
        font-weight: 600 !important;
        color: #212529 !important;
        padding-left: 8px !important;
    }
    #assignVehicleDriverModal .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 32px !important;
    }
    .select2-container--open {
        z-index: 99999999 !important;
    }
    .select2-dropdown {
        z-index: 99999999 !important;
    }
    /* Table Header styling - Sticky on top of scrollable container */
    #dispatchTableHead th {
        background-color: #23b185 !important;
        color: #ffffff !important;
        text-align: left;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 0.5px;
        vertical-align: middle !important;
        border: 1px solid rgba(255, 255, 255, 0.25) !important;
        padding: 10px 10px !important;
        white-space: nowrap;
        position: sticky !important;
        top: 0 !important;
        z-index: 10 !important;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
    }

    #finalDispatchTable {
        display: table !important;
        width: 100% !important;
        min-width: 1270px !important;
        border-collapse: collapse !important;
        table-layout: fixed !important;
        margin-bottom: 0 !important;
        background-color: #ffffff !important;
    }
    #finalDispatchTable thead {
        display: table-header-group !important;
    }
    #finalDispatchTable tbody {
        display: table-row-group !important;
    }
    #finalDispatchTable tr {
        display: table-row !important;
    }
    #finalDispatchTable th,
    #finalDispatchTable td {
        display: table-cell !important;
        vertical-align: top !important;
        font-size: 13px !important;
        padding: 10px 10px !important;
        border: 1px solid #dee2e6 !important;
        word-wrap: break-word !important;
        overflow-wrap: break-word !important;
        box-sizing: border-box !important;
    }
    #finalDispatchTable .btn {
        font-size: 11.5px !important;
        padding: 5px 6px !important;
        white-space: normal !important;
        word-wrap: break-word !important;
        overflow-wrap: break-word !important;
        line-height: 1.3 !important;
        text-align: center !important;
        box-sizing: border-box !important;
    }
    .btn-teal {
        background-color: #0d9488 !important;
        border-color: #0d9488 !important;
        color: #ffffff !important;
    }
    .btn-teal:hover {
        background-color: #0f766e !important;
        border-color: #0f766e !important;
        color: #ffffff !important;
    }
    #finalDispatchTable tr.order-row-first td {
        border-top: 2px solid #ced4da !important;
    }
    #finalDispatchTable tr.item-row-subsequent td {
        border-top: 1px dashed #dee2e6 !important;
    }
    
    .table-responsive {
        border-radius: 8px;
        overflow-x: auto;
        border: 1px solid #dee2e6;
    }

    .dispatch-table-responsive {
        max-height: calc(100vh - 215px) !important;
        min-height: 400px;
        overflow-y: auto !important;
        overflow-x: auto !important;
        border-radius: 8px;
        border: 1px solid #dee2e6;
        scrollbar-width: thin;
        scrollbar-color: #23b185 #f1f3f5;
        background: #ffffff;
        position: relative;
    }
    .dispatch-table-responsive::-webkit-scrollbar {
        width: 8px;
        height: 10px;
    }
    .dispatch-table-responsive::-webkit-scrollbar-thumb {
        background-color: #23b185;
        border-radius: 4px;
    }
    .dispatch-table-responsive::-webkit-scrollbar-track {
        background: #f1f3f5;
    }
    
    .table-hover tbody tr:hover {
        background-color: rgba(35, 177, 133, 0.05) !important;
    }

    /* Week navigator buttons */
    #dispatchPrevWeekBtn {
        background-color: #f3a614 !important;
        color: black !important;
        border: none;
        padding: 8px 16px;
        font-weight: 600;
        border-radius: 6px;
        transition: background-color 0.2s;
    }
    #dispatchPrevWeekBtn:hover {
        background-color: #d9910d !important;
    }

    #dispatchNextWeekBtn {
        background-color: #0c8c5e !important;
        color: white !important;
        border: none;
        padding: 8px 16px;
        font-weight: 600;
        border-radius: 6px;
        transition: background-color 0.2s;
    }
    #dispatchNextWeekBtn:hover {
        background-color: #096d49 !important;
    }

    /* Select controls */
    .form-control, .form-select {
        border-radius: 6px;
        border: 1px solid #ced4da;
        padding: 8px 12px;
        font-weight: 500;
    }

    .date-highlight {
        font-weight: 600;
    }

    /* Responsive title sizing */
    @media (max-width: 768px) {
        h3.page-title {
            font-size: 18px !important;
        }
    }

    /* Row highlight colors */
    #finalDispatchTable tr.row-pending-alert > td,
    .row-pending-alert {
        background-color: #fde8e8 !important;
    }
    #finalDispatchTable tr.row-pending-alert:hover > td {
        background-color: #fcd4d4 !important;
    }
    .badge-pending-alert {
        background-color: #f8d7da !important;
        color: #842029 !important;
        border: 1px solid #f5c2c7 !important;
    }
    .row-past-due {
        background-color: #fff4f4 !important;
    }
    .row-dispatched {
        background-color: #f0fdf4 !important;
    }
    .row-ready {
        background-color: #f0fdfa !important;
    }

    /* Summary Card Base Styles */
    .status-filter-card {
        border-radius: 12px;
        padding: 14px 18px;
        cursor: pointer;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        border: 2px solid transparent;
        box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        position: relative;
        overflow: hidden;
        user-select: none;
        height: 100%;
    }
    .status-filter-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 14px rgba(0,0,0,0.08);
    }
    .status-filter-card .card-status-title {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        margin-bottom: 2px;
        display: block;
    }
    .status-filter-card .card-status-value {
        font-size: 24px;
        font-weight: 800;
        line-height: 1.1;
    }
    .status-filter-card .card-status-sub {
        font-size: 11.5px;
        font-weight: 500;
        margin-top: 2px;
        display: block;
    }
    .status-filter-card .card-status-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }

    /* Card Themes */
    /* 1. Total Card */
    .status-filter-card.card-total {
        background-color: #eff6ff;
        border-color: #bfdbfe;
    }
    .status-filter-card.card-total .card-status-title { color: #1e40af; }
    .status-filter-card.card-total .card-status-value { color: #1d4ed8; }
    .status-filter-card.card-total .card-status-sub { color: #3b82f6; }
    .status-filter-card.card-total .card-status-icon {
        background-color: #dbeafe;
        color: #1d4ed8;
    }
    .status-filter-card.card-total.active {
        border-color: #2563eb !important;
        background-color: #e0e7ff !important;
        animation: pulseTotal 2s infinite ease-in-out;
    }

    /* 2. Allocated Card */
    .status-filter-card.card-allocated {
        background-color: #ecfdf5;
        border-color: #d1fae5;
    }
    .status-filter-card.card-allocated .card-status-title { color: #065f46; }
    .status-filter-card.card-allocated .card-status-value { color: #047857; }
    .status-filter-card.card-allocated .card-status-sub { color: #059669; }
    .status-filter-card.card-allocated .card-status-icon {
        background-color: #d1fae5;
        color: #047857;
    }
    .status-filter-card.card-allocated.active {
        border-color: #059669 !important;
        background-color: #dcfce7 !important;
        animation: pulseAllocated 2s infinite ease-in-out;
    }

    /* 3. Ready for Dispatch Card */
    .status-filter-card.card-ready {
        background-color: #f0fdfa;
        border-color: #ccfbf1;
    }
    .status-filter-card.card-ready .card-status-title { color: #0f766e; }
    .status-filter-card.card-ready .card-status-value { color: #0891b2; }
    .status-filter-card.card-ready .card-status-sub { color: #0e7490; }
    .status-filter-card.card-ready .card-status-icon {
        background-color: #ccfbf1;
        color: #0891b2;
    }
    .status-filter-card.card-ready.active {
        border-color: #0891b2 !important;
        background-color: #cffafe !important;
        animation: pulseReady 2s infinite ease-in-out;
    }

    /* 4. Fully Dispatched Card */
    .status-filter-card.card-dispatched {
        background-color: #f8fafc;
        border-color: #e2e8f0;
    }
    .status-filter-card.card-dispatched .card-status-title { color: #334155; }
    .status-filter-card.card-dispatched .card-status-value { color: #0f172a; }
    .status-filter-card.card-dispatched .card-status-sub { color: #64748b; }
    .status-filter-card.card-dispatched .card-status-icon {
        background-color: #e2e8f0;
        color: #334155;
    }
    .status-filter-card.card-dispatched.active {
        border-color: #475569 !important;
        background-color: #f1f5f9 !important;
        animation: pulseDispatched 2s infinite ease-in-out;
    }

    /* 2.5 Ready to Loading Card */
    .status-filter-card.card-readytoload {
        background-color: #fffbeb;
        border-color: #fef3c7;
    }
    .status-filter-card.card-readytoload .card-status-title { color: #92400e; }
    .status-filter-card.card-readytoload .card-status-value { color: #d97706; }
    .status-filter-card.card-readytoload .card-status-sub { color: #b45309; }
    .status-filter-card.card-readytoload .card-status-icon {
        background-color: #fef3c7;
        color: #d97706;
    }
    .status-filter-card.card-readytoload.active {
        border-color: #d97706 !important;
        background-color: #fef3c7 !important;
        animation: pulseReadyToLoad 2s infinite ease-in-out;
    }

    /* 5. Pending Card */
    .status-filter-card.card-pending {
        background-color: #fef2f2;
        border-color: #fee2e2;
    }
    .status-filter-card.card-pending .card-status-title { color: #991b1b; }
    .status-filter-card.card-pending .card-status-value { color: #dc2626; }
    .status-filter-card.card-pending .card-status-sub { color: #b91c1c; }
    .status-filter-card.card-pending .card-status-icon {
        background-color: #fee2e2;
        color: #dc2626;
    }
    .status-filter-card.card-pending.active {
        border-color: #dc2626 !important;
        background-color: #fde8e8 !important;
        animation: pulsePending 2s infinite ease-in-out;
    }

    /* 6. Old Pending Card */
    .status-filter-card.card-oldpending {
        background-color: #faf5ff;
        border-color: #f3e8ff;
    }
    .status-filter-card.card-oldpending .card-status-title { color: #6b21a8; }
    .status-filter-card.card-oldpending .card-status-value { color: #7e22ce; }
    .status-filter-card.card-oldpending .card-status-sub { color: #9333ea; }
    .status-filter-card.card-oldpending .card-status-icon {
        background-color: #f3e8ff;
        color: #7e22ce;
    }
    .status-filter-card.card-oldpending.active {
        border-color: #7e22ce !important;
        background-color: #f5f3ff !important;
        animation: pulseOldPending 2s infinite ease-in-out;
    }

    /* Animations */
    @keyframes pulseTotal {
        0%, 100% { box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.35), 0 4px 10px rgba(37, 99, 235, 0.15); }
        50% { box-shadow: 0 0 0 6px rgba(37, 99, 235, 0.65), 0 6px 16px rgba(37, 99, 235, 0.25); }
    }
    @keyframes pulseAllocated {
        0%, 100% { box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.35), 0 4px 10px rgba(5, 150, 105, 0.15); }
        50% { box-shadow: 0 0 0 6px rgba(5, 150, 105, 0.65), 0 6px 16px rgba(5, 150, 105, 0.25); }
    }
    @keyframes pulseReadyToLoad {
        0%, 100% { box-shadow: 0 0 0 3px rgba(217, 119, 6, 0.35), 0 4px 10px rgba(217, 119, 6, 0.15); }
        50% { box-shadow: 0 0 0 6px rgba(217, 119, 6, 0.65), 0 6px 16px rgba(217, 119, 6, 0.25); }
    }
    @keyframes pulseReady {
        0%, 100% { box-shadow: 0 0 0 3px rgba(8, 145, 178, 0.35), 0 4px 10px rgba(8, 145, 178, 0.15); }
        50% { box-shadow: 0 0 0 6px rgba(8, 145, 178, 0.65), 0 6px 16px rgba(8, 145, 178, 0.25); }
    }
    @keyframes pulseDispatched {
        0%, 100% { box-shadow: 0 0 0 3px rgba(71, 85, 105, 0.35), 0 4px 10px rgba(71, 85, 105, 0.15); }
        50% { box-shadow: 0 0 0 6px rgba(71, 85, 105, 0.65), 0 6px 16px rgba(71, 85, 105, 0.25); }
    }
    @keyframes pulsePending {
        0%, 100% { box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.35), 0 4px 10px rgba(220, 38, 38, 0.15); }
        50% { box-shadow: 0 0 0 6px rgba(220, 38, 38, 0.65), 0 6px 16px rgba(220, 38, 38, 0.25); }
    }
    @keyframes pulseOldPending {
        0%, 100% { box-shadow: 0 0 0 3px rgba(126, 34, 206, 0.35), 0 4px 10px rgba(126, 34, 206, 0.15); }
        50% { box-shadow: 0 0 0 6px rgba(126, 34, 206, 0.65), 0 6px 16px rgba(126, 34, 206, 0.25); }
    }

    /* Active Filter Highlight - Soft light background and highlighted border */
    .filter-applied,
    .filter-applied .select2-selection,
    .select2-container.filter-applied .select2-selection,
    .select2-container.filter-applied .select2-selection--single,
    .form-select.filter-applied {
        background-color: #e8f5e9 !important;
        border-color: #0c8c5e !important;
        color: #065f46 !important;
        font-weight: 600 !important;
        box-shadow: 0 0 0 0.15rem rgba(12, 140, 94, 0.18) !important;
    }
    .select2-container.filter-applied .select2-selection__rendered {
        color: #065f46 !important;
        font-weight: 600 !important;
    }
    .input-group.filter-applied {
        box-shadow: 0 0 0 0.15rem rgba(12, 140, 94, 0.18) !important;
        border-radius: 6px;
    }
    .input-group.filter-applied .form-control,
    .input-group.filter-applied .input-group-text {
        background-color: #e8f5e9 !important;
        border-color: #0c8c5e !important;
        color: #065f46 !important;
        font-weight: 600 !important;
    }
    .input-group.filter-applied .form-control::placeholder {
        color: #065f46 !important;
        opacity: 0.7;
    }

    /* Dispatch Invoice Modal Styling */
    .dinv-product-card {
        border-color: #cbd5e1 !important;
        transition: all 0.2s ease-in-out;
    }
    .dinv-product-card:hover {
        border-color: #94a3b8 !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06) !important;
    }
    .btn-check:checked + label.btn-outline-success {
        background-color: #059669 !important;
        color: #ffffff !important;
        border-color: #059669 !important;
        box-shadow: 0 2px 6px rgba(5, 150, 105, 0.3) !important;
    }
    .btn-check:checked + label.btn-outline-secondary {
        background-color: #d97706 !important;
        color: #ffffff !important;
        border-color: #d97706 !important;
        box-shadow: 0 2px 6px rgba(217, 119, 6, 0.3) !important;
    }
    .dinv-include-wrapper {
        display: inline-flex !important;
        align-items: center !important;
        padding: 4px 10px !important;
        margin-right: 10px !important;
        background-color: #ffffff !important;
        border: 1px solid #ced4da !important;
        border-radius: 6px !important;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05) !important;
        gap: 8px !important;
    }
    .dinv-item-include-checkbox {
        width: 15px !important;
        height: 15px !important;
        min-width: 15px !important;
        min-height: 15px !important;
        max-width: 15px !important;
        max-height: 15px !important;
        margin: 0 8px 0 0 !important;
        margin-right: 8px !important;
        cursor: pointer !important;
        accent-color: #059669 !important;
        flex-shrink: 0 !important;
        position: static !important;
        display: inline-block !important;
        vertical-align: middle !important;
        box-sizing: border-box !important;
        transform: none !important;
    }
    .dinv-include-label {
        font-size: 12px !important;
        font-weight: 700 !important;
        color: #1e293b !important;
        cursor: pointer !important;
        line-height: 1.2 !important;
        white-space: nowrap !important;
        margin: 0 !important;
        padding: 0 !important;
        user-select: none !important;
        display: inline-block !important;
        vertical-align: middle !important;
    }

    /* =========================================================
       Responsive Styles for Prepared Dispatch Invoice Modal
       ========================================================= */
    #prepareDispatchInvoiceModal .modal-dialog {
        max-width: 96vw;
        width: 96vw;
        margin: 0.75rem auto;
    }

    @media (max-width: 991.98px) {
        #prepareDispatchInvoiceModal .modal-dialog {
            max-width: 98vw !important;
            width: 98vw !important;
            margin: 0.5rem auto !important;
        }
        #prepareDispatchInvoiceModal .modal-body {
            padding: 0.75rem !important;
        }
    }

    @media (max-width: 767.98px) {
        #prepareDispatchInvoiceModal .modal-dialog {
            max-width: 99vw !important;
            width: 99vw !important;
            margin: 0.25rem auto !important;
        }
        #prepareDispatchInvoiceModal .modal-content {
            border-radius: 8px !important;
        }
        #prepareDispatchInvoiceModal .modal-header {
            padding: 0.75rem 1rem !important;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        #prepareDispatchInvoiceModal .modal-title {
            font-size: 1.1rem !important;
        }
        #dinvModalSubTitle {
            font-size: 0.75rem !important;
        }
        #prepareDispatchInvoiceModal .modal-body {
            padding: 0.5rem !important;
        }

        /* Sticky Mobile Top Action Bar for Prepare Dispatch Invoice Modal */
        .dinv-mobile-top-bar {
            position: sticky !important;
            top: -0.5rem !important;
            z-index: 1025 !important;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08) !important;
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 10px !important;
            padding: 0.5rem 0.65rem !important;
        }
        .dinv-mobile-top-bar .btn {
            font-size: 0.85rem !important;
            padding: 0.45rem 0.75rem !important;
            border-radius: 6px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 6px !important;
            white-space: nowrap !important;
            line-height: 1.25 !important;
        }
        .dinv-top-step-btn {
            display: none !important;
            width: 100% !important;
            margin: 0 !important;
        }
        .dinv-top-step-btn.active {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            gap: 8px !important;
            width: 100% !important;
        }

        /* 1. Client & Invoice Info Card */
        #prepareDispatchInvoiceModal .card-body {
            padding: 0.75rem !important;
        }
        #dinv_lbl_clientname {
            font-size: 0.95rem !important;
        }

        /* 2. Product Items */
        .dinv-product-card .card-header {
            padding: 0.6rem 0.75rem !important;
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 0.5rem !important;
        }
        .dinv-product-card .card-header > div {
            width: 100%;
        }
        .dinv-product-card .card-body {
            padding: 0.65rem !important;
        }
        .dinv-include-wrapper {
            padding: 3px 6px !important;
            margin-right: 4px !important;
        }

        /* Shifting Radio Button Group */
        .dinv-item-shifted-radio + label {
            font-size: 11px !important;
            padding: 0.35rem 0.4rem !important;
            white-space: normal !important;
            text-align: center !important;
        }

        /* 3. Combined Summary Bar (2 columns on mobile) */
        #prepareDispatchInvoiceModal .card.border-0.shadow-sm.rounded-3[style*="background: #f8fafc"] .col-6 {
            border-right: none !important;
            border-bottom: 1px solid #e2e8f0;
            padding-top: 0.4rem;
            padding-bottom: 0.4rem;
        }
        #prepareDispatchInvoiceModal .card.border-0.shadow-sm.rounded-3[style*="background: #f8fafc"] .col-6:nth-child(odd) {
            border-right: 1px solid #e2e8f0 !important;
        }
        #prepareDispatchInvoiceModal .card.border-0.shadow-sm.rounded-3[style*="background: #f8fafc"] .col-6:nth-last-child(-n+2) {
            border-bottom: none;
        }

        /* 4. Freight Section */
        #dinv_delivery_type_radios {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        #dinv_delivery_type_radios label.btn {
            width: 100%;
            text-align: left;
            font-size: 12px !important;
        }

        /* 5. Grand Total Highlight Banner */
        #dinv_grand_total_banner {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 0.5rem !important;
        }
        #dinv_grand_total_banner .text-end {
            text-align: left !important;
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px dashed #86efac;
            padding-top: 0.5rem;
        }
        #dinv_grand_total_display {
            font-size: 1.6rem !important;
        }

        /* 6. Payment & Advance Reconciliation Card */
        #dinv_payment_summary_card .card-header {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 0.5rem !important;
        }
        #dinv_payment_summary_card .card-header > div:last-child {
            width: 100%;
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
        }
        #dinv_payment_summary_card .card-header .btn {
            flex: 1 1 auto;
            font-size: 11.5px !important;
            text-align: center;
            padding: 0.35rem 0.5rem !important;
        }

        /* Modal Footer */
        #prepareDispatchInvoiceModal .modal-footer {
            flex-direction: column-reverse !important;
            align-items: stretch !important;
            gap: 0.5rem !important;
            padding: 0.75rem 1rem !important;
            padding-bottom: max(30px, env(safe-area-inset-bottom)) !important;
        }
        #prepareDispatchInvoiceModal .modal-footer > div {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        #prepareDispatchInvoiceModal .modal-footer .btn {
            width: 100%;
            padding: 0.55rem 1rem !important;
            font-size: 0.9rem !important;
        }
        #dinv_footer_balance_status {
            width: 100%;
            text-align: center;
        }
        #dinv_footer_balance_status .badge {
            width: 100%;
            white-space: normal !important;
            text-align: center;
            display: block;
            padding: 0.5rem !important;
            font-size: 0.75rem !important;
        }
    }

    /* 3-Step Wizard Stepper Styles for Prepare Dispatch Invoice */
    .dinv-wizard-header {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 0.65rem 1.25rem;
        margin-bottom: 1.25rem;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
    }
    .dinv-step-item {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        cursor: pointer;
        user-select: none;
        transition: all 0.2s ease;
        padding: 0.35rem 0.75rem;
        border-radius: 8px;
    }
    .dinv-step-item:hover {
        background: #f1f5f9;
    }
    .dinv-step-circle {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: #f1f5f9;
        border: 2px solid #cbd5e1;
        color: #64748b;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.9rem;
        transition: all 0.25s ease;
    }
    .dinv-step-item.active .dinv-step-circle {
        background: #2563eb;
        border-color: #2563eb;
        color: #ffffff;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.2);
    }
    .dinv-step-item.completed .dinv-step-circle {
        background: #10b981;
        border-color: #10b981;
        color: #ffffff;
    }
    .dinv-step-label {
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        color: #94a3b8;
        letter-spacing: 0.5px;
        line-height: 1;
    }
    .dinv-step-item.active .dinv-step-label {
        color: #2563eb;
    }
    .dinv-step-item.completed .dinv-step-label {
        color: #10b981;
    }
    .dinv-step-name {
        font-size: 0.88rem;
        font-weight: 700;
        color: #475569;
        line-height: 1.2;
    }
    .dinv-step-item.active .dinv-step-name {
        color: #0f172a;
    }
    .dinv-step-line {
        height: 2px;
        background: #e2e8f0;
        transition: background 0.25s ease;
    }
    .dinv-step-line.completed {
        background: #10b981;
    }
    @media (max-width: 767.98px) {
        .dinv-wizard-header {
            padding: 0.5rem 0.5rem !important;
            margin-bottom: 0.75rem !important;
        }
        .dinv-step-item {
            padding: 0.2rem 0.35rem !important;
            gap: 0.35rem !important;
        }
        .dinv-step-circle {
            width: 28px !important;
            height: 28px !important;
            font-size: 0.8rem !important;
        }
        .dinv-step-name {
            font-size: 0.75rem !important;
        }
        .dinv-step-line {
            margin: 0 0.35rem !important;
        }
    }

    /* Receive Payment Modal Mobile Responsive */
    #dinvReceivePaymentModal .modal-dialog {
        max-width: 95vw !important;
        width: 95vw !important;
        margin: 0.75rem auto;
    }
    @media (max-width: 767.98px) {
        #dinvReceivePaymentModal .modal-dialog {
            max-width: 99vw !important;
            width: 99vw !important;
            margin: 0.25rem auto !important;
        }
        #dinvReceivePaymentModal .modal-body {
            padding: 0 !important;
        }
    }
</style>

<div class="container-fluid my-4">
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h3 class="mb-0 text-dark font-weight-bold page-title" style="font-size: 22px; white-space: nowrap;">
                    <i class="fa fa-truck text-success me-2"></i> Weekly Farmer Order Allocation & Dispatch Schedule
                </h3>
                <div class="d-flex gap-2">
                    <!-- <button class="btn btn-sm btn-success" id="exportExcelBtnTop">
                        <i class="fa fa-file-excel me-1"></i> Export Excel
                    </button> -->
                    <button class="btn btn-sm btn-outline-secondary" onclick="window.location.reload();">
                        <i class="fa fa-sync"></i> Refresh
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body">
            <!-- Filter Bar: Year, Month, Week, Navigation & Export -->
            <div class="d-flex flex-wrap align-items-end gap-3 mb-3 p-3 bg-light rounded-3 shadow-sm border">
                <div style="min-width: 140px; flex: 0.8;">
                    <label class="form-label fw-bold text-secondary small text-uppercase mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Select Year</label>
                    <select id="dispatchYearFilter" class="form-select" style="height: 42px;"></select>
                </div>
                <div style="min-width: 180px; flex: 1;">
                    <label class="form-label fw-bold text-secondary small text-uppercase mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Select Month</label>
                    <select id="dispatchMonthFilter" class="form-select" style="height: 42px;"></select>
                </div>
                <div style="min-width: 250px; flex: 1.5;">
                    <label class="form-label fw-bold text-secondary small text-uppercase mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Select Week</label>
                    <select id="dispatchWeekSelector" class="form-select" style="height: 42px;"></select>
                </div>
                <div class="d-flex gap-2 flex-wrap ms-md-auto align-items-center mt-2 mt-md-0">
                    <button class="btn" id="dispatchPrevWeekBtn" style="height: 42px;">← Previous Week</button>
                    <button class="btn" id="dispatchNextWeekBtn" style="height: 42px;">Next Week →</button>
                    <button class="btn btn-success fw-bold" id="btnExportExcelSchedule" style="height: 42px; background-color: #107c41; border-color: #107c41; display: inline-flex; align-items: center;" title="Export schedule to Excel">
                        <i class="fa fa-file-excel text-white me-1"></i> Excel
                    </button>
                    <button class="btn btn-danger fw-bold" id="btnExportPdfSchedule" style="height: 42px; background-color: #dc2626; border-color: #dc2626; display: inline-flex; align-items: center;" title="Open printable PDF preview">
                        <i class="fa fa-file-pdf text-white me-1"></i> Print / PDF
                    </button>
                </div>
            </div>

            <!-- KPI & Status Filter Cards -->
            <div class="row g-2 mb-3" id="dispatchStatusCardsRow">
                <!-- 1. Pending Allocation -->
                <div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl" style="min-width: 170px;">
                    <div class="status-filter-card card-pending" data-filter="pending">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="card-status-title">Pending Allocation</span>
                                <h3 class="card-status-value mb-0" id="cardPendingOrders">0</h3>
                                <span class="card-status-sub" id="cardPendingQty">Awaiting Stock Order</span>
                            </div>
                            <div class="card-status-icon">
                                <i class="fa fa-clock"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- 2. Allocated (For Prepared Invoice) -->
                <div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl" style="min-width: 170px;">
                    <div class="status-filter-card card-allocated" data-filter="allocated">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="card-status-title">Allocated (For Prepared Invoice)</span>
                                <h3 class="card-status-value mb-0" id="cardAllocatedOrders">0</h3>
                                <span class="card-status-sub" id="cardAllocatedQty">0 Allocated</span>
                            </div>
                            <div class="card-status-icon">
                                <i class="fa fa-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- 3. Ready to Loading -->
                <div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl" style="min-width: 170px;">
                    <div class="status-filter-card card-readytoload" data-filter="readytoload">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="card-status-title">Ready to Loading</span>
                                <h3 class="card-status-value mb-0" id="cardReadyToLoadOrders">0</h3>
                                <span class="card-status-sub" id="cardReadyToLoadQty">0 Ready to Load</span>
                            </div>
                            <div class="card-status-icon">
                                <i class="fa fa-boxes-packing"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- 5. Final Dispatched -->
                <div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl" style="min-width: 170px;">
                    <div class="status-filter-card card-dispatched" data-filter="dispatched">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="card-status-title">Final Dispatched</span>
                                <h3 class="card-status-value mb-0" id="cardDispatchedOrders">0</h3>
                                <span class="card-status-sub" id="cardDispatchedQty">0 Completed</span>
                            </div>
                            <div class="card-status-icon">
                                <i class="fa fa-truck-moving"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- 6. Total Orders -->
                <div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl" style="min-width: 170px;">
                    <div class="status-filter-card card-total active" data-filter="all">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="card-status-title">Total Orders</span>
                                <h3 class="card-status-value mb-0" id="cardTotalOrders">0</h3>
                                <span class="card-status-sub" id="cardTotalQty">0 Plants Qty</span>
                            </div>
                            <div class="card-status-icon">
                                <i class="fa fa-shopping-cart"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- 7. Old Pending Allocation -->
                <div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl" style="min-width: 170px;">
                    <div class="status-filter-card card-oldpending" data-filter="oldpending">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="card-status-title">Old Pending Allocation</span>
                                <h3 class="card-status-value mb-0" id="cardOldPendingOrders">0</h3>
                                <span class="card-status-sub" id="cardOldPendingQty">Prior Weeks</span>
                            </div>
                            <div class="card-status-icon">
                                <i class="fa fa-history"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- In-Table Secondary Dynamic Filters -->
            <div class="card mb-3 border bg-white shadow-sm" style="border-radius: 10px;">
                <div class="card-body p-3">
                    <div class="row g-2 align-items-end">
                        <!-- 1. Unique Delivery Date Filter (Only dates from loaded screen data) -->
                        <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                            <label class="form-label fw-bold text-secondary small text-uppercase mb-1" style="font-size: 11px;">
                                <i class="fa fa-calendar-day text-success me-1"></i> Delivery Date
                            </label>
                            <select id="tableDeliveryDateFilter" class="form-select select2-filter" style="width: 100%;">
                                <option value="all">All Dates</option>
                            </select>
                        </div>

                        <!-- 2. Client Name / Final Sales Order No (Searchable) -->
                        <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                            <label class="form-label fw-bold text-secondary small text-uppercase mb-1" style="font-size: 11px;">
                                <i class="fa fa-user text-primary me-1"></i> Client / Sales Order
                            </label>
                            <select id="tableClientFilter" class="form-select select2-filter" style="width: 100%;">
                                <option value="all">All Clients & Orders</option>
                            </select>
                        </div>

                        <!-- 3. Grafted / Non-Grafted Filter -->
                        <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                            <label class="form-label fw-bold text-secondary small text-uppercase mb-1" style="font-size: 11px;">
                                <i class="fa fa-dna text-danger me-1"></i> Type
                            </label>
                            <select id="tableGraftedTypeFilter" class="form-select select2-filter" style="width: 100%;">
                                <option value="all">All Types</option>
                                <option value="grafted">Grafted</option>
                                <option value="nongrafted">Non-Grafted</option>
                            </select>
                        </div>

                        <!-- 4. Product Name (Searchable) -->
                        <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                            <label class="form-label fw-bold text-secondary small text-uppercase mb-1" style="font-size: 11px;">
                                <i class="fa fa-seedling text-teal me-1" style="color: #0d9488;"></i> Product Name
                            </label>
                            <select id="tableProductFilter" class="form-select select2-filter" style="width: 100%;">
                                <option value="all">All Products</option>
                            </select>
                        </div>

                        <!-- 5. Polyhouse Filter -->
                        <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                            <label class="form-label fw-bold text-secondary small text-uppercase mb-1" style="font-size: 11px;">
                                <i class="fa fa-warehouse text-info me-1"></i> Polyhouse
                            </label>
                            <select id="tablePolyhouseFilter" class="form-select select2-filter" style="width: 100%;">
                                <option value="all">All Polyhouses</option>
                            </select>
                        </div>

                        <!-- 6. Status Filter -->
                        <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                            <label class="form-label fw-bold text-secondary small text-uppercase mb-1" style="font-size: 11px;">
                                <i class="fa fa-tasks text-warning me-1"></i> Status
                            </label>
                            <select id="tableStatusFilter" class="form-select select2-filter" style="width: 100%;">
                                <option value="all" selected>All Statuses</option>
                                <option value="pending">Pending Allocation</option>
                                <option value="allocated">Allocated (For Prepared Invoice)</option>
                                <option value="readytoload">Ready to Loading</option>
                                <option value="dispatched">Final Dispatched</option>
                                <option value="cancelled">Cancelled Orders</option>
                            </select>
                        </div>
                    </div>

                    <!-- Bottom row: Row counter, quick search input, and Reset button -->
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-3 pt-2 border-top">
                        <div class="text-muted small">
                            <i class="fa fa-list-check text-success me-1"></i> Showing <span class="badge bg-light text-dark border fw-bold px-2 py-1" id="filteredRowCountDisplay">0</span> of <span class="fw-bold" id="totalRowCountDisplay">0</span> orders
                        </div>
                        <div class="flex-grow-1" style="min-width: 320px; max-width: 620px;">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 px-3"><i class="fa fa-search text-muted"></i></span>
                                <input type="text" id="tableSearchInput" class="form-control border-start-0" style="height: 40px; font-size: 13.5px;" placeholder="Search by client, SO/PSONO, product, polyhouse, phone, city...">
                            </div>
                        </div>
                        <div>
                            <button class="btn btn-success" id="exportExcelBtn" style="height: 42px;">
                                <i class="fa fa-file-excel text-white me-1"></i> Export Excel
                            </button>
                            <button type="button" class="btn btn-outline-danger" id="resetTableFiltersBtn" style="height: 40px; padding: 0 18px;">
                                <i class="fa fa-rotate-left me-1"></i> Reset Filters
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Schedule Table Grid -->
            <div class="row mt-2">
                <div class="col-md-12">
                    <div class="table-responsive dispatch-table-responsive shadow-sm" style="border-radius: 8px; border: 1px solid #dee2e6; background: #ffffff; width: 100%;">
                        <table class="table table-bordered table-hover mb-0" id="finalDispatchTable">
                            <colgroup>
                                <col style="width: 50px;">
                                <col style="width: 140px;">
                                <col style="width: 260px;">
                                <col style="width: 220px;">
                                <col style="width: 340px;">
                                <col style="width: 180px;">
                            </colgroup>
                            <thead id="dispatchTableHead">
                                <tr>
                                    <th class="text-center" style="width: 50px;">S.NO</th>
                                    <th style="width: 140px;">DELIVERY DATE</th>
                                    <th style="width: 260px;">SALES ORDER & CLIENT DETAILS</th>
                                    <th style="width: 220px;">PRODUCT NAME</th>
                                    <th style="width: 340px;">PRO. ORDER & QUANTITY</th>
                                    <th class="text-center" style="width: 180px;">ACTION</th>
                                </tr>
                            </thead>
                            <tbody id="finalDispatchTableBody">
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="fa fa-spinner fa-spin me-2"></i> Loading schedule...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: 100% Width Order Growth & Polyhouse Tray Details -->
<div class="modal fade" id="orderGrowthStatusModal" tabindex="-1" aria-labelledby="orderGrowthStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
        <div class="modal-content border-0">
            <div class="modal-header py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom" style="background-color: #e0f2fe; border-bottom: 2px solid #bae6fd !important;">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-success rounded-3 p-2 d-flex align-items-center justify-content-center text-white shadow-sm" style="width: 44px; height: 44px;">
                        <i class="fa fa-seedling fs-5"></i>
                    </div>
                    <div>
                        <h4 class="modal-title fw-bold mb-0 text-dark" id="orderGrowthStatusModalLabel">
                            Allocated Production Order, Growing & Polyhouse Trays Details
                        </h4>
                        <span class="text-secondary small fw-medium" id="growthModalSubtitle">Complete Production Stock Order Details, Live Growth Status & Tray Location Breakdown</span>
                    </div>
                </div>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <span id="orderGrowthModalHeaderDispatchedBadge" class="badge bg-success py-2 px-3 fs-6 d-none shadow-sm">
                        <i class="fa fa-check-double me-1"></i> Fully Dispatched
                    </span>
                    <button type="button" id="orderGrowthModalHeaderInvoicePdfBtn" class="btn btn-success btn-sm fw-bold px-3 shadow-sm view-download-invoice-pdf-btn d-none">
                        <i class="fa fa-file-pdf me-1"></i> Download Dispatched Invoice (PDF)
                    </button>
                    <button type="button" class="btn btn-danger btn-sm px-3 shadow-sm close-modal-btn" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fa fa-times me-1"></i> Close
                    </button>
                </div>
            </div>
            <div class="modal-body bg-light p-4" id="orderGrowthModalBody">
                <div class="text-center py-5 text-muted">
                    <i class="fa fa-spinner fa-spin fa-2x me-2 text-success"></i>
                    <span class="fs-5">Loading order details and polyhouse tray breakdown...</span>
                </div>
            </div>
            <div class="modal-footer bg-white py-2 px-4 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="text-muted small" id="orderGrowthModalFooterNotice">
                    <i class="fa fa-info-circle text-primary me-1"></i> Allocate from available production stock order batches and beds above.
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <button type="button" id="orderGrowthModalFooterInvoicePdfBtn" class="btn btn-success btn-sm fw-bold px-3 shadow-sm view-download-invoice-pdf-btn d-none">
                        <i class="fa fa-file-pdf me-1"></i> Download Dispatched Invoice (PDF)
                    </button>
                    <button type="button" id="growthModalFooterSaveAllocationBtn" class="btn btn-success fw-bold px-3 shadow-sm growth-modal-save-btn">
                        <i class="fa fa-save me-1"></i> Save Allocation
                    </button>
                    <button type="button" id="growthModalFooterSaveAndInvoiceBtn" class="btn btn-primary fw-bold px-3 shadow-sm growth-modal-invoice-btn">
                        <i class="fa fa-file-invoice me-1"></i> Confirm &amp; Prepare Invoice <i class="fa fa-arrow-right ms-1"></i>
                    </button>
                    <button type="button" class="btn btn-secondary px-4 close-modal-btn" data-dismiss="modal" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Update / Re-Schedule Delivery Date -->
<div class="modal fade" id="rescheduleDeliveryDateModal" tabindex="-1" aria-labelledby="rescheduleDeliveryDateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header py-3 px-4 d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: #ffffff;">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa fa-calendar-alt fs-5"></i>
                    <h5 class="modal-title fw-bold text-white mb-0" id="rescheduleDeliveryDateModalLabel">
                        Re-Schedule Delivery Date
                    </h5>
                </div>
                <button type="button" class="close text-white close-modal-btn" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" style="background: rgba(255,255,255,0.2); border: none; font-size: 1.4rem; line-height: 1; border-radius: 6px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; color: #ffffff !important; opacity: 1;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4 bg-light">
                <form id="rescheduleDeliveryDateForm" onsubmit="return false;">
                    <input type="hidden" id="reschedule_soi_id" name="soi_id">
                    <input type="hidden" id="reschedule_parent_soi_id" name="parent_soi_id">
                    <input type="hidden" id="reschedule_is_partial" name="is_partial_pending">
                    <input type="hidden" id="reschedule_pending_qty" name="pending_qty">
                    <input type="hidden" id="reschedule_psono" name="psono">
                    <input type="hidden" id="reschedule_sono" name="sono">
                    <input type="hidden" id="reschedule_stockorder_no" name="stockorder_no">
                    <input type="hidden" id="reschedule_projectid" name="projectid">

                    <!-- Product & Order Summary Card -->
                    <div class="card shadow-sm border-0 mb-3 bg-white">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2 border-bottom pb-2">
                                <div>
                                    <div class="fw-bold text-dark fs-6" id="reschedule_modal_product_name">-</div>
                                    <div class="text-muted small" id="reschedule_modal_so_info">-</div>
                                </div>
                                <span class="badge bg-primary px-2 py-1 fs-7" id="reschedule_modal_order_qty">0 Plants</span>
                            </div>
                            <div class="row g-2 small">
                                <div class="col-6">
                                    <span class="text-secondary fw-semibold">Client:</span>
                                    <div class="text-dark fw-bold" id="reschedule_modal_client_name">-</div>
                                </div>
                                <div class="col-6">
                                    <span class="text-secondary fw-semibold">Current Delivery Date:</span>
                                    <div class="text-primary fw-bold" id="reschedule_modal_current_date">-</div>
                                </div>
                                <div class="col-6">
                                    <span class="text-secondary fw-semibold">Stock Order:</span>
                                    <div class="text-dark fw-semibold" id="reschedule_modal_stock_order">-</div>
                                </div>
                                <div class="col-6">
                                    <span class="text-secondary fw-semibold">Order Type:</span>
                                    <div id="reschedule_modal_client_type">-</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Previous Remark Log (If any) -->
                    <div id="reschedule_previous_remark_container" class="mb-3 d-none">
                        <label class="form-label text-secondary small fw-bold mb-1">
                            <i class="fa fa-history text-muted me-1"></i> Previous Re-Schedule History / Remarks:
                        </label>
                        <div class="p-2 rounded border bg-white small text-muted font-monospace" id="reschedule_previous_remark_text" style="max-height: 80px; overflow-y: auto; font-size: 11.5px; white-space: pre-wrap;">
                        </div>
                    </div>

                    <!-- New Delivery Date Input (Mandatory) -->
                    <div class="mb-3">
                        <label for="reschedule_new_delivery_date" class="form-label fw-bold text-dark">
                            New Re-Scheduled Delivery Date <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="fa fa-calendar-alt text-primary"></i></span>
                            <input type="date" class="form-control form-control-lg fw-bold" id="reschedule_new_delivery_date" name="new_delivery_date" required>
                        </div>
                        <div class="form-text small text-muted">Select the updated or confirmed delivery date.</div>
                    </div>

                    <!-- Remark / Reason Input (Mandatory) -->
                    <div class="mb-3">
                        <label for="reschedule_remark" class="form-label fw-bold text-dark">
                            Re-Schedule Reason / Remark <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="reschedule_remark" name="remark" rows="3" placeholder="Enter reason for updating delivery date (e.g., Client requested delay, weather conditions, batch readiness)..." required></textarea>
                        <div class="form-text small text-muted">
                            <i class="fa fa-user-tag text-info me-1"></i> Will be saved with login: <strong id="reschedule_modal_loginname_display">-</strong> and local timestamp.
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-white py-2 px-4 border-top d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-secondary px-3 close-modal-btn" data-dismiss="modal" data-bs-dismiss="modal">
                    <i class="fa fa-times me-1"></i> Cancel
                </button>
                <button type="button" class="btn btn-success px-4 fw-bold shadow-sm" id="btn_save_reschedule_delivery_date">
                    <i class="fa fa-save me-1"></i> Update & Re-Schedule Date
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Close / Revise Pending Order Quantity -->
<div class="modal fade" id="closePendingQtyModal" tabindex="-1" aria-labelledby="closePendingQtyModalLabel" aria-hidden="true" style="z-index: 1065;">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header py-3 px-4 d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #e11d48 0%, #be123c 100%); color: #ffffff;">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa fa-clipboard-check fs-5"></i>
                    <h5 class="modal-title fw-bold text-white mb-0" id="closePendingQtyModalLabel">
                        Pending Order Quantity Closure & Remark
                    </h5>
                </div>
                <button type="button" class="close text-white close-modal-btn" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" style="background: rgba(255,255,255,0.2); border: none; font-size: 1.4rem; line-height: 1; border-radius: 6px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; color: #ffffff !important; opacity: 1;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4 bg-light">
                <form id="closePendingQtyForm" onsubmit="return false;">
                    <input type="hidden" id="cpq_soi_id" name="soi_id">
                    <input type="hidden" id="cpq_psono" name="psono">
                    <input type="hidden" id="cpq_sono" name="sono">
                    <input type="hidden" id="cpq_product_id" name="product_id">
                    <input type="hidden" id="cpq_projectid" name="projectid">
                    <input type="hidden" id="cpq_fulfilled_qty" name="fulfilled_qty">
                    <input type="hidden" id="cpq_pending_qty" name="pending_qty">

                    <!-- Order Details Summary Card -->
                    <div class="card shadow-sm border-0 mb-3 bg-white">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2 border-bottom pb-2">
                                <div>
                                    <div class="fw-bold text-dark fs-6" id="cpq_modal_product_name">-</div>
                                    <div class="text-muted small" id="cpq_modal_so_info">-</div>
                                </div>
                                <span class="badge bg-danger px-2.5 py-1 fs-7" id="cpq_modal_pending_badge">Pending: 0 Plants</span>
                            </div>
                            <div class="row g-2 small">
                                <div class="col-md-6">
                                    <span class="text-secondary fw-semibold">Client Name:</span>
                                    <div class="text-dark fw-bold" id="cpq_modal_client_name">-</div>
                                </div>
                                <div class="col-md-6">
                                    <span class="text-secondary fw-semibold">Delivery Date:</span>
                                    <div class="text-primary fw-bold" id="cpq_modal_delivery_date">-</div>
                                </div>
                            </div>

                            <!-- 4-Part Metric Strip in Modal -->
                            <div class="mt-3 p-2 rounded bg-light border d-flex justify-content-around text-center flex-wrap gap-2">
                                <div>
                                    <small class="text-secondary fw-semibold d-block">Total Order Qty</small>
                                    <span class="badge bg-dark fs-6 px-2.5 py-1" id="cpq_modal_order_qty">0</span>
                                </div>
                                <div>
                                    <small class="text-secondary fw-semibold d-block">Allocated (Not Disp.)</small>
                                    <span class="badge bg-success fs-6 px-2.5 py-1" id="cpq_modal_alloc_qty">0</span>
                                </div>
                                <div>
                                    <small class="text-secondary fw-semibold d-block">Final Dispatched</small>
                                    <span class="badge bg-primary fs-6 px-2.5 py-1" id="cpq_modal_disp_qty">0</span>
                                </div>
                                <div>
                                    <small class="text-danger fw-bold d-block">Remaining Pending</small>
                                    <span class="badge bg-danger fs-6 px-2.5 py-1" id="cpq_modal_pending_qty">0</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Closure Action Options -->
                    <div class="card shadow-sm border-0 mb-3 bg-white">
                        <div class="card-body p-3">
                            <label class="form-label fw-bold text-dark mb-2">
                                <i class="fa fa-tasks text-primary me-1"></i> Select Order Closure / Revision Action:
                            </label>
                            
                            <div class="form-check mb-2 p-2.5 rounded border bg-light-subtle">
                                <input class="form-check-input ms-1" type="radio" name="cpq_closure_action" id="cpq_action_revise" value="revise_order_qty" checked>
                                <label class="form-check-label ms-2 w-100" for="cpq_action_revise">
                                    <strong class="text-dark">Revise Total Order Quantity</strong>
                                    <div class="mt-2 d-flex align-items-center flex-wrap gap-2">
                                        <span class="small fw-semibold text-secondary">New Order Quantity:</span>
                                        <input type="number" id="cpq_custom_new_qty" class="form-control form-control-sm text-center fw-bold" style="width: 140px; border: 1.5px solid #2563eb;" min="0" value="0">
                                        <span class="small text-muted">Plants</span>
                                        <button type="button" class="btn btn-outline-secondary btn-sm py-0.5 px-2" id="cpq_btn_set_fulfilled" title="Set to already fulfilled / dispatched quantity">
                                            <i class="fa fa-check me-1"></i>Set to Fulfilled (<span id="cpq_btn_fulfilled_val">0</span>)
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm py-0.5 px-2" id="cpq_btn_set_zero" title="Cancel the entire remaining order">
                                            <i class="fa fa-ban me-1"></i>Cancel All (0)
                                        </button>
                                    </div>
                                    <div class="small mt-2 p-1.5 rounded bg-white border text-muted" id="cpq_calc_summary">
                                        Booked Order: <b id="cpq_summary_orig" class="text-dark">0</b> | 
                                        Revised Order: <b id="cpq_summary_revised" class="text-primary">0</b> | 
                                        Dropped / Cancelled: <b id="cpq_summary_dropped" class="text-danger">0</b> Plants
                                    </div>
                                    <div class="text-muted small mt-1" style="font-size: 11px;">
                                        Updates the sales order line item to the revised quantity. Dropped plants are removed, order amounts recalculate, and pending balance adjusts accordingly.
                                    </div>
                                </label>
                            </div>

                            <div class="form-check mb-2 p-2 rounded border bg-light-subtle">
                                <input class="form-check-input ms-1" type="radio" name="cpq_closure_action" id="cpq_action_cancel" value="cancel_remainder">
                                <label class="form-check-label ms-2" for="cpq_action_cancel">
                                    <strong class="text-dark">Cancel Remaining Balance (<span id="cpq_pending_label2">0</span> Plants) with Remark</strong>
                                    <div class="text-muted small">
                                        Retains the original booked order quantity, but marks the unfulfilled pending balance as cancelled/closed with audit remarks.
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Remark / Reason Input (Mandatory) -->
                    <div class="mb-3">
                        <label for="cpq_remark" class="form-label fw-bold text-dark">
                            Mandatory Remark / Reason for Order Closure <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="cpq_remark" name="remark" rows="3" placeholder="Enter reason why remaining pending plants are not required to be delivered (e.g., Client requested order closure, crop season ended, short delivery accepted by customer)..." required style="border: 1.5px solid #fca5a5; font-size: 13px;"></textarea>
                        <div id="cpq_remark_error" class="text-danger small fw-bold mt-1" style="display: none;">
                            <i class="fa fa-exclamation-triangle me-1"></i> Remark is mandatory! Cannot close or update order without providing a reason.
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-white py-2 px-4 border-top d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-secondary px-3 close-modal-btn" data-dismiss="modal" data-bs-dismiss="modal">
                    <i class="fa fa-times me-1"></i> Cancel
                </button>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-secondary px-3 small" id="cpq_open_full_so_btn" title="Open full provisional order in edit page">
                        <i class="fa fa-external-link-alt me-1"></i> Open Full SO Editor
                    </button>
                    <button type="button" class="btn btn-danger px-4 fw-bold shadow-sm" id="btn_save_close_pending_qty">
                        <i class="fa fa-check-circle me-1"></i> Update & Close Pending Qty
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Allocate / Re-allocate Production Stock Order -->
<div class="modal fade" id="allocateStockOrderModal" tabindex="-1" aria-labelledby="allocateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="max-width: 95vw; width: 95vw; margin: 0.75rem auto;">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-light btn-sm text-success fw-bold shadow-xs back-to-prev-modal-btn px-2.5 py-1" id="modal_back_to_prev_header_btn" style="border-radius: 6px; font-size: 13px;" title="Go back to previous modal">
                        <i class="fa fa-arrow-left me-1"></i> Back
                    </button>
                    <h5 class="modal-title fw-bold text-white mb-0" id="allocateModalLabel">
                        <i class="fa fa-clipboard-check me-2"></i> Allocate Ready Plants / Production Order
                    </h5>
                </div>
                <button type="button" class="close text-white close-modal-btn" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" style="background: rgba(255,255,255,0.25); border: none; font-size: 1.4rem; line-height: 1; border-radius: 6px; width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; color: #ffffff !important; opacity: 1;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4">
                <form id="allocateStockOrderForm">
                    <input type="hidden" id="modal_soi_id" name="soi_id">
                    <input type="hidden" id="modal_psono" name="psono">
                    <input type="hidden" id="modal_sono" name="sono">
                    <input type="hidden" id="modal_client_id" name="client_id">
                    <input type="hidden" id="modal_product_id" name="product_id">
                    <input type="hidden" id="modal_product_name" name="product_name">
                    <input type="hidden" id="modal_stockorder_no" name="stockorder_no">
                    <input type="hidden" id="modal_allocated_psono" name="allocated_psono">
                    <input type="hidden" id="modal_client_order_qty" value="0">
                    <input type="hidden" id="modal_selected_ready_plants" value="0">
                    <input type="hidden" id="modal_selected_ready_trays" value="0">
                    <input type="hidden" id="modal_healthy_plant" name="healthyplant" value="0">
                    <input type="hidden" id="modal_unhealthy_plant" name="unhealthyplant" value="0">

                    <!-- Sales Order Item Summary Banner -->
                    <div class="bg-light p-3 rounded-3 mb-3 border">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <span class="text-secondary small fw-bold">Client:</span>
                                <div class="fw-bold text-dark fs-6" id="modal_client_name">-</div>
                            </div>
                            <div class="col-md-6">
                                <span class="text-secondary small fw-bold">Sales Order:</span>
                                <div class="fw-bold text-dark" id="modal_so_display">-</div>
                            </div>
                            <div class="col-md-6">
                                <span class="text-secondary small fw-bold">Product:</span>
                                <div class="fw-bold text-success" id="modal_product_display">-</div>
                            </div>
                            <div class="col-md-6">
                                <span class="text-secondary small fw-bold">Order Qty / Delivery Date:</span>
                                <div class="fw-bold text-dark" id="modal_qty_date_display">-</div>
                            </div>
                            <div class="col-md-6 mt-2">
                                <span class="text-secondary small fw-bold">Production Stock Order:</span>
                                <div class="d-flex align-items-center flex-wrap gap-1">
                                    <span class="badge bg-primary fs-6" id="modal_stockorder_badge">-</span>
                                    <span id="modal_stockorder_type_badge"></span>
                                </div>
                            </div>
                            <div class="col-md-6 mt-2">
                                <span class="text-secondary small fw-bold">Total Trays & Plants Ready:</span>
                                <div><span class="badge bg-success fs-6" id="modal_ready_summary_badge">0 Trays | 0 Plants Ready</span></div>
                            </div>
                        </div>

                        <!-- Cross-Allocation Details (Shown if allocated other production order) -->
                        <div id="modal_cross_allocation_section" class="mt-2.5 pt-2 border-top d-none">
                            <div class="p-2.5 rounded-2 border bg-white shadow-sm">
                                <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom flex-wrap gap-1">
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning fw-bold px-2 py-1" style="font-size: 11px;">
                                        <i class="fa fa-exchange-alt me-1"></i> Re-Allocated from Other Production Order
                                    </span>
                                    <span class="text-muted small" style="font-size: 11px;">
                                        <i class="fa fa-sync-alt me-1 text-primary"></i> Mutual allocation details
                                    </span>
                                </div>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <div class="p-2 rounded bg-primary-subtle border border-primary-subtle h-100">
                                            <div class="d-flex align-items-center justify-content-between mb-1">
                                                <span class="small fw-bold text-primary"><i class="fa fa-check-circle me-1"></i> Allocated Production Order:</span>
                                                <span class="badge bg-primary fs-6" id="modal_alloc_batch_badge">-</span>
                                            </div>
                                            <div class="small text-dark" style="font-size: 11.5px; line-height: 1.45;">
                                                <div><b>Client Name:</b> <span id="modal_alloc_client_name" class="fw-semibold">-</span></div>
                                                <div><b>Provisional Order (PSONO):</b> <span id="modal_alloc_psono" class="font-monospace fw-semibold">-</span></div>
                                                <div><b>Sales Order (SO):</b> <span id="modal_alloc_sono" class="font-monospace">-</span></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-2 rounded bg-secondary-subtle border border-secondary-subtle h-100">
                                            <div class="d-flex align-items-center justify-content-between mb-1">
                                                <span class="small fw-bold text-secondary"><i class="fa fa-seedling me-1"></i> Original Sown Order:</span>
                                                <span class="badge bg-secondary fs-6" id="modal_orig_batch_badge">-</span>
                                            </div>
                                            <div class="small text-dark" style="font-size: 11.5px; line-height: 1.45;">
                                                <div><b>Client Name:</b> <span id="modal_orig_client_name" class="fw-semibold">-</span></div>
                                                <div><b>Provisional Order (PSONO):</b> <span id="modal_orig_psono" class="font-monospace fw-semibold">-</span></div>
                                                <div><b>Sales Order (SO):</b> <span id="modal_orig_sono" class="font-monospace">-</span></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Do you allocate shifted plants? Card -->
                    <div class="card border border-primary-subtle bg-light p-3 rounded-3 mb-3 shadow-sm" id="shiftedPlantsQuestionCard">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                            <div>
                                <div class="fw-bold text-dark"><i class="fa fa-question-circle text-primary me-1"></i> Do you allocate shifted plants?</div>
                                <div class="text-muted small" style="font-size: 11px;">Select YES for shifted plants (exact order qty), or NO to specify extra plants for non-shifted trays.</div>
                            </div>
                            <div class="btn-group" role="group" aria-label="Allocate shifted plants">
                                <input type="radio" class="btn-check shifted-toggle-radio" name="allocate_shifted_plants" id="shifted_yes" value="yes" checked>
                                <label class="btn shifted-toggle-btn btn-sm px-3 fw-bold" for="shifted_yes"><i class="fa fa-check me-1"></i> YES</label>
                                
                                <input type="radio" class="btn-check shifted-toggle-radio" name="allocate_shifted_plants" id="shifted_no" value="no">
                                <label class="btn shifted-toggle-btn btn-sm px-3 fw-bold" for="shifted_no"><i class="fa fa-times me-1"></i> NO</label>
                            </div>
                        </div>

                        <!-- Extra Plant Calculation Details (shown only when NO) -->
                        <div id="shiftedPlantsCalcSection" class="bg-white p-3 rounded-2 border mt-2 d-none">
                            <div class="row g-3 align-items-center">
                                <div class="col-12 col-md-4">
                                    <span class="text-muted small d-block">Client Order Qty:</span>
                                    <span class="fw-bold text-dark fs-6" id="calc_order_qty">0 Plants</span>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="text-muted small d-block mb-0" for="calc_extra_percent">% of Extra Plant: <span class="text-danger fw-bold">*</span></label>
                                    <div class="input-group input-group-sm mt-1">
                                        <input type="number" class="form-control fw-bold text-primary" id="calc_extra_percent" step="0.01" min="0" placeholder="e.g. 5">
                                        <span class="input-group-text bg-light fw-bold">%</span>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4">
                                    <span class="text-muted small d-block">Extra Plants (Non-Shifted Trays):</span>
                                    <span class="fw-bold text-warning-emphasis fs-6" id="calc_extra_plants">+0 Plants</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stock Order Selection Table (For Variety Replacement / Re-allocation / Swapping with other clients) -->
                    <div class="mb-3 d-none" id="modalStockOrderSelectContainer">
                        <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-2">
                            <label class="form-label fw-bold text-dark mb-0">
                                Select Production Stock Order <span class="text-danger">*</span>
                            </label>
                            <div class="d-flex align-items-center gap-2">
                                <span id="modal_stockorder_grid_count" class="badge bg-secondary px-2 py-1">0 Batches</span>
                                <label class="custom-variety-toggle mb-0" for="modal_allow_all_products_toggle">
                                    <input type="checkbox" id="modal_allow_all_products_toggle" class="custom-variety-input">
                                    <span class="custom-variety-slider"></span>
                                    <span class="custom-variety-label">All Varieties</span>
                                </label>
                            </div>
                        </div>

                        <!-- Search input and Quick Auto-Allocate Actions -->
                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                            <div class="input-group input-group-sm shadow-sm flex-grow-1" style="max-width: 580px;">
                                <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa fa-search"></i></span>
                                <input type="text" id="modal_stockorder_grid_search" class="form-control border-start-0" placeholder="Search batch no, product, allocated client, polyhouse, dates...">
                                <button class="btn btn-outline-secondary" type="button" id="modal_clear_stockorder_search" title="Clear search"><i class="fa fa-times"></i></button>
                            </div>
                            <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                <button type="button" class="btn btn-sm btn-outline-success fw-bold shadow-xs py-1 px-2.5" id="btnAutoAllocateByBalance" title="Automatically allocate required order quantity from available stock order batches by balance">
                                    <i class="fa fa-magic me-1"></i> Auto-Allocate by Balance
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary fw-semibold shadow-xs py-1 px-2" id="btnResetAllocation" title="Reset all selected batches">
                                    <i class="fa fa-undo me-1"></i> Reset
                                </button>
                            </div>
                        </div>

                        <!-- Live Multi-Batch Allocation Summary Bar -->
                        <div id="modalMultiBatchAllocationSummary" class="card border border-primary-subtle p-2.5 rounded-3 mb-2 shadow-xs" style="background: linear-gradient(135deg, #f0fdf4 0%, #f8fafc 100%);">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-3 flex-wrap">
                                    <div>
                                        <span class="text-secondary small fw-bold text-uppercase" style="font-size: 11px;"><i class="fa fa-clipboard-list text-primary me-1"></i> Target Order Qty:</span>
                                        <span class="fw-bold text-dark fs-6 ms-1 font-monospace" id="multi_alloc_target_qty">0 Plants</span>
                                    </div>
                                    <div class="border-start ps-3">
                                        <span class="text-secondary small fw-bold text-uppercase" style="font-size: 11px;"><i class="fa fa-check-double text-success me-1"></i> Total Allocated:</span>
                                        <span class="fw-bold fs-6 ms-1 font-monospace text-success" id="multi_alloc_total_qty">0 Plants</span>
                                    </div>
                                    <div class="border-start ps-3">
                                        <span class="text-secondary small fw-bold text-uppercase" style="font-size: 11px;"><i class="fa fa-balance-scale text-warning-emphasis me-1"></i> Remaining Needed:</span>
                                        <span class="fw-bold fs-6 ms-1 font-monospace" id="multi_alloc_diff_qty">0 Plants</span>
                                    </div>
                                </div>
                                <div id="multi_alloc_status_badge">
                                    <span class="badge bg-secondary px-2.5 py-1.5"><i class="fa fa-info-circle me-1"></i> Please allocate batches</span>
                                </div>
                            </div>
                            <div id="multi_alloc_batches_chips" class="mt-2 pt-2 border-top d-flex align-items-center flex-wrap gap-1.5 d-none">
                                <!-- Populated dynamically with badges for each allocated batch -->
                            </div>
                        </div>

                        <!-- Table Grid with max-height: 320px -->
                        <div class="table-responsive rounded-2 border bg-white shadow-sm" style="max-height: 320px; overflow-y: auto;">
                            <table class="table table-hover table-sm align-middle mb-0 text-nowrap" id="modalStockOrderGridTable" style="font-size: 12px;">
                                <thead>
                                    <tr class="text-uppercase text-secondary" style="font-size: 11px;">
                                        <th class="text-center align-middle" style="width: 55px; min-width: 55px; text-align: center;">Select</th>
                                        <th>Stock Order / Allocation</th>
                                        <th>Product Name</th>
                                        <th class="text-end" style="min-width: 105px;" title="Order Qty & Plants in Polyhouse">QTY</th>
                                        <th class="text-end" style="min-width: 90px;" title="Assigned / Confirmed Allocations from krf_productionorder_consumed">Assigned Qty</th>
                                        <th class="text-end" style="min-width: 105px;" title="Plants Consumed or Removed from Polyhouse">Consumed/Removed</th>
                                        <th class="text-end" style="min-width: 95px;" title="Ready to Allocate Available Balance">Avail Bal</th>
                                        <th class="text-center" style="width: 140px; min-width: 130px; text-align: center;">Allocating Qty</th>
                                        <th class="text-center" style="width: 125px; min-width: 115px; text-align: center;">Extra Plant</th>
                                        <th style="min-width: 110px;">Polyhouse &amp; Bed</th>
                                        <th style="min-width: 220px;"><i class="fa fa-th-large text-success me-1"></i>Allocated Bed</th>
                                        <th>Delivery Due</th>
                                    </tr>
                                </thead>
                                <tbody id="modalStockOrderGridBody">
                                    <tr>
                                        <td colspan="12" class="text-center py-4 text-muted">
                                            <i class="fa fa-spinner fa-spin me-2 text-primary"></i> Loading available production stock orders...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="form-text text-muted mt-1" id="modal_stockorder_help">
                            Select one or more production stock order batches above and allocate quantities according to available balance to fulfill client order.
                        </div>
                    </div>

                    <!-- Bed Ready Plants vs Allocated Qty Validation Alert -->
                    <div id="modalReadyPlantsValidationAlert" class="mb-3 d-none"></div>

                    <!-- Order Qty, Extra Qty & Remarks -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-dark">
                                Order Qty <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control fw-bold text-success fs-5 bg-light" id="modal_allocated_qty" min="1" step="any" readonly required>
                            <div class="form-text text-muted" id="modal_alloc_qty_help">Base order plant quantity (Read Only).</div>
                        </div>
                        <div class="col-md-4" id="modalExtraQtyContainer">
                            <label class="form-label fw-bold text-dark">
                                Extra Qty (Plants)
                            </label>
                            <input type="number" class="form-control fw-bold text-warning-emphasis fs-5 bg-light" id="modal_extra_qty" value="0">
                            <div class="form-text text-muted" id="modal_extra_qty_help">Calculated from % extra plant (Read Only).</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-dark">Allocation / Substitution Remark</label>
                            <input type="text" class="form-control" id="modal_allocation_remark" placeholder="e.g. Allocated ready trays with shifted plants">
                        </div>
                    </div>

                    <!-- Selected Stock Order Details Box -->
                    <div id="modalStockOrderInfo" class="card border-0 shadow-sm d-none mb-3" style="background: #ffffff; border: 1.5px solid #94a3b8 !important; border-radius: 10px; overflow: hidden;">
                        <div class="card-header py-2 px-3 bg-light border-bottom d-flex align-items-center justify-content-between">
                            <div class="fw-bold text-dark fs-6 d-flex align-items-center gap-2">
                                <i class="fa fa-cubes text-primary"></i>
                                <span>Selected Stock Order Details</span>
                            </div>
                        </div>
                        <div class="card-body p-3">
                            <div id="modalStockOrderInfoContent"></div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-dark fw-bold px-3 back-to-prev-modal-btn shadow-xs" id="modal_back_to_prev_footer_btn">
                        <i class="fa fa-arrow-left me-1"></i> Back to Previous Modal
                    </button>
                    <button type="button" class="btn btn-secondary close-modal-btn" data-dismiss="modal" data-bs-dismiss="modal">Cancel</button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-success fw-bold px-3" id="saveAllocationBtn">
                        <i class="fa fa-save me-1"></i> Save Allocation
                    </button>
                    <button type="button" class="btn btn-primary fw-bold px-3 shadow-sm" id="confirmAndPrepareInvoiceBtn">
                        <i class="fa fa-file-invoice me-1"></i> Confirm &amp; Prepare Invoice <i class="fa fa-arrow-right ms-1"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Prepare & Generate Dispatch Invoice -->
<div class="modal fade" id="prepareDispatchInvoiceModal" tabindex="-1" aria-labelledby="prepareDispatchInvoiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="max-width: 96vw; width: 96vw; margin: 0.75rem auto;">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white py-3 d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%) !important;">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px;">
                        <i class="fa fa-file-invoice fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0" id="prepareDispatchInvoiceModalLabel">
                            Prepared Dispatch Invoice
                        </h5>
                        <div class="text-white-50 small" id="dinvModalSubTitle">Verify order quantities, shifting plants, freight charges & financial breakdown to generate dispatch invoice</div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fw-bold fs-6" id="dinvStatusBadge">
                        <i class="fa fa-check-circle me-1"></i> Allocated Order
                    </span>
                    <button type="button" class="close text-white close-modal-btn" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" style="background: rgba(255,255,255,0.15); border: none; font-size: 1.4rem; line-height: 1; border-radius: 6px; width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; color: #ffffff !important; opacity: 1;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>
            <div class="modal-body p-4 bg-light" id="prepareDispatchInvoiceModalBody">
                <!-- 3-Step Wizard Stepper Header -->
                <div class="dinv-wizard-header bg-white border shadow-2xs rounded-3 mb-3 px-3 py-2">
                    <div class="d-flex align-items-center justify-content-between position-relative">
                        <!-- Step 1 Tab -->
                        <div class="dinv-step-item active" data-step="1" title="Click to view Step 1: Client & Products">
                            <div class="dinv-step-circle">1</div>
                            <div>
                                <div class="dinv-step-label">Step 1</div>
                                <div class="dinv-step-name">Client &amp; Products</div>
                            </div>
                        </div>
                        
                        <div class="dinv-step-line flex-grow-1 mx-3" data-step-line="2"></div>

                        <!-- Step 2 Tab -->
                        <div class="dinv-step-item" data-step="2" title="Click to view Step 2: Freight & Delivery">
                            <div class="dinv-step-circle">2</div>
                            <div>
                                <div class="dinv-step-label">Step 2</div>
                                <div class="dinv-step-name">Freight &amp; Delivery</div>
                            </div>
                        </div>

                        <div class="dinv-step-line flex-grow-1 mx-3" data-step-line="3"></div>

                        <!-- Step 3 Tab -->
                        <div class="dinv-step-item" data-step="3" title="Click to view Step 3: Payments & Final Invoice">
                            <div class="dinv-step-circle">3</div>
                            <div>
                                <div class="dinv-step-label">Step 3</div>
                                <div class="dinv-step-name">Payment &amp; Finalize</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mobile Top Sticky Navigation Bar (Visible only on mobile screens < 768px) -->
                <div class="dinv-mobile-top-bar d-md-none bg-white border shadow-sm rounded-3 p-2 mb-3 sticky-top">
                    <!-- Step 1 Actions -->
                    <div class="dinv-top-step-btn dinv-top-step-1 active">
                        <button type="button" class="btn btn-outline-secondary btn-sm px-3 close-modal-btn" data-dismiss="modal" data-bs-dismiss="modal">
                            <i class="fa fa-times me-1"></i> Cancel
                        </button>
                        <button type="button" class="btn btn-primary btn-sm fw-bold px-3 py-2 flex-grow-1 shadow-sm dinv-top-btn-next-to-2">
                            Next: Freight Details <i class="fa fa-arrow-right ms-1"></i>
                        </button>
                    </div>
                    <!-- Step 2 Actions -->
                    <div class="dinv-top-step-btn dinv-top-step-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm px-3 dinv-top-btn-back-to-1">
                            <i class="fa fa-arrow-left me-1"></i> Back
                        </button>
                        <button type="button" class="btn btn-primary btn-sm fw-bold px-3 py-2 flex-grow-1 shadow-sm dinv-top-btn-next-to-3">
                            Next: Payment &amp; Finalize <i class="fa fa-arrow-right ms-1"></i>
                        </button>
                    </div>
                    <!-- Step 3 Actions -->
                    <div class="dinv-top-step-btn dinv-top-step-3">
                        <button type="button" class="btn btn-outline-secondary btn-sm px-3 dinv-top-btn-back-to-2">
                            <i class="fa fa-arrow-left me-1"></i> Back
                        </button>
                        <button type="button" class="btn btn-info text-white btn-sm fw-bold px-3 py-2 flex-grow-1 shadow-sm dinv-top-btn-preview-pdf" style="display: none;">
                            <i class="fa fa-file-pdf me-1"></i> Preview PDF
                        </button>
                        <button type="button" class="btn btn-success btn-sm fw-bold px-3 py-2 flex-grow-1 shadow-sm dinv-top-btn-confirm-save" style="display: none;">
                            <i class="fa fa-check-circle me-1"></i> Generate Invoice
                        </button>
                    </div>
                </div>

                <form id="prepareDispatchInvoiceForm">
                    <input type="hidden" id="dinv_soi_id" name="soi_id">
                    <input type="hidden" id="dinv_sono" name="sono">
                    <input type="hidden" id="dinv_psono" name="psono">
                    <input type="hidden" id="dinv_edit_invoiceno" name="edit_invoiceno">
                    <input type="hidden" id="dinv_clientid" name="clientid">
                    <input type="hidden" id="dinv_companyid" name="companyid">
                    <input type="hidden" id="dinv_projectid" name="projectid">

                    <!-- ============================================== -->
                    <!-- STEP 1: CLIENT & PRODUCT ITEMS ALLOCATION     -->
                    <!-- ============================================== -->
                    <div class="dinv-step-pane" id="dinv_step_1">
                        <!-- 1. Executive Invoice & Client Master Information Header -->
                        <div class="card border-0 shadow-sm rounded-3 mb-3 bg-white">
                            <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar-xs bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                        <i class="fa fa-file-invoice text-primary"></i>
                                    </span>
                                    <span class="fw-bold text-dark fs-6">Client & Invoice Information</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 font-monospace" id="dinv_sono_badge">SO: - | PSONO: -</span>
                                    <span class="badge bg-secondary-subtle text-dark border px-2.5 py-1" id="dinv_cust_type_badge">Customer: Farmer</span>
                                </div>
                            </div>
                            <div class="card-body p-3">
                                <div class="row g-3">
                                    <!-- Left Column: Billed Customer Profile (Client Master) -->
                                    <div class="col-lg-7 col-md-7 col-12">
                                        <div class="h-100 p-3 rounded-3 border bg-light d-flex flex-column justify-content-between" style="border-color: #e2e8f0 !important;">
                                            <!-- Customer Name & Badge Banner -->
                                            <div class="d-flex align-items-center justify-content-between mb-2.5 pb-2 border-bottom" style="border-color: #e2e8f0 !important;">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="bg-white text-primary rounded-circle shadow-2xs d-flex align-items-center justify-content-center border" style="width: 36px; height: 36px; min-width: 36px;">
                                                        <i class="fa fa-user-tie fs-6"></i>
                                                    </div>
                                                    <div>
                                                        <div class="text-muted text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">Billed Customer (Client Master)</div>
                                                        <div class="fw-bold text-dark fs-6 lh-1 mt-0.5" id="dinv_lbl_clientname">-</div>
                                                        <input type="hidden" id="dinv_clientname" name="clientname">
                                                    </div>
                                                </div>
                                                <span class="badge bg-white text-dark border shadow-2xs px-2.5 py-1 fw-bold" id="dinv_lbl_cust_type_pill" style="font-size: 11px;">Farmer</span>
                                            </div>

                                            <!-- Customer Contact & Address Info Grid -->
                                            <div class="row g-2" style="font-size: 0.85rem;">
                                                <div class="col-sm-6 col-12">
                                                    <div class="p-2 rounded-2 bg-white border h-100 shadow-2xs">
                                                        <div class="text-muted small fw-semibold mb-0.5"><i class="fa fa-phone me-1.5 text-primary"></i> Contact / Mobile</div>
                                                        <div class="fw-bold text-dark font-monospace" id="dinv_lbl_mobileno">-</div>
                                                        <input type="hidden" id="dinv_mobileno" name="mobileno">
                                                    </div>
                                                </div>
                                                <div class="col-sm-6 col-12">
                                                    <div class="p-2 rounded-2 bg-white border h-100 shadow-2xs">
                                                        <div class="text-muted small fw-semibold mb-0.5"><i class="fa fa-envelope me-1.5 text-primary"></i> Email Address</div>
                                                        <div class="text-dark fw-medium text-truncate" id="dinv_lbl_emailid">N/A</div>
                                                        <input type="hidden" id="dinv_emailid" name="emailid">
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <div class="p-2 rounded-2 bg-white border shadow-2xs">
                                                        <div class="text-muted small fw-semibold mb-0.5"><i class="fa fa-map-marker-alt me-1.5 text-danger"></i> Invoicing Address (Client Master)</div>
                                                        <div class="text-dark fw-medium" id="dinv_lbl_invoice_address">-</div>
                                                        <input type="hidden" id="dinv_address" name="address">
                                                        <input type="hidden" id="dinv_invoicing_address" name="invoicing_address">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Hidden Delivery Address & Location fields for form submission -->
                                        <input type="hidden" id="dinv_delivery_address" name="delivery_address">
                                        <input type="hidden" id="dinv_districtname" name="districtname">
                                        <input type="hidden" id="dinv_statename" name="statename">
                                    </div>

                                    <!-- Right Column: Dispatch Invoice & Order Meta (Invoice Format) -->
                                    <div class="col-lg-5 col-md-5 col-12">
                                        <div class="h-100 p-3 rounded-3 border d-flex flex-column justify-content-between" style="background: #f8fafc; border-color: #cbd5e1 !important;">
                                            <!-- Invoice Number & Date Controls -->
                                            <div class="mb-2 pb-2 border-bottom" style="border-color: #e2e8f0 !important;">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">
                                                        <i class="fa fa-receipt text-primary me-1"></i> Invoice No:
                                                    </span>
                                                    <div class="d-flex align-items-center gap-1">
                                                        <span class="badge bg-primary text-white font-monospace fs-6 px-2.5 py-1 shadow-2xs" id="dinv_invoiceno_badge">KF/26-27/--</span>
                                                        <input type="hidden" id="dinv_invoiceno" name="invoiceno">
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">
                                                        <i class="fa fa-calendar-alt text-primary me-1"></i> Invoice Date:
                                                    </span>
                                                    <div style="max-width: 155px;">
                                                        <input type="date" class="form-control form-control-sm fw-bold text-end border-primary-subtle bg-white shadow-2xs" id="dinv_invoicedate" name="invoicedate" required style="font-size: 12.5px; height: 30px;">
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Order Details Right-Aligned List -->
                                            <div class="d-flex flex-column gap-1.5" style="font-size: 0.83rem;">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="text-muted fw-semibold"><i class="fa fa-hashtag me-1 text-secondary"></i> Order No:</span>
                                                    <span class="fw-bold text-dark font-monospace" id="dinv_lbl_orderno">-</span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="text-muted fw-semibold"><i class="fa fa-clock me-1 text-secondary"></i> Order Date:</span>
                                                    <span class="text-dark fw-medium" id="dinv_lbl_orderdate">-</span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="text-muted fw-semibold"><i class="fa fa-check-circle me-1 text-success"></i> Status:</span>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5" id="dinv_lbl_orderstatus">Confirmed</span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="text-muted fw-semibold"><i class="fa fa-user-shield me-1 text-secondary"></i> Created By:</span>
                                                    <span class="text-dark fw-bold text-uppercase" id="dinv_lbl_createdby">Admin</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 1.9 Past Generated Invoices Container (Rendered on top of Product Items) -->
                        <div id="dinvPastInvoicesContainer" class="mb-3" style="display: none;"></div>

                        <!-- 2. Product Items & Shifting Allocation Container (Whole Provisional Order) -->
                        <div class="card border-0 shadow-sm rounded-3 mb-3">
                            <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fw-bold text-dark"><i class="fa fa-boxes text-success me-2"></i> Product Items & Shifting Allocation</span>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-0.5 small" id="dinv_provisional_order_tag">Whole Provisional Order</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 fw-bold" id="dinv_product_count_badge">0 Selected</span>
                                </div>
                            </div>
                            <div class="card-body p-3 bg-light" id="dinvProductsContainer">
                                <!-- Dynamic Product Items Rendered by JavaScript -->
                            </div>
                        </div>

                        <!-- 2.5 Box Packing Charges Section (Billable Line Item) -->
                        <div class="card border-0 shadow-sm rounded-3 mb-3" style="background: #ffffff; border: 1px solid #e2e8f0 !important;">
                            <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="d-inline-flex align-items-center">
                                        <label class="dinv-toggle-switch me-2 mb-0" for="dinv_enable_box_packing" title="Toggle Box Packing Charges in Bill">
                                            <input type="checkbox" id="dinv_enable_box_packing">
                                            <span class="dinv-toggle-track">
                                                <span class="dinv-toggle-thumb"></span>
                                            </span>
                                        </label>
                                        <label class="fw-bold text-dark mb-0 cursor-pointer user-select-none" for="dinv_enable_box_packing" style="cursor: pointer; font-size: 13px;">
                                            <i class="fa fa-box-open text-primary me-1"></i> Add Per Box Charge
                                        </label>
                                    </div>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-0.5 small" id="dinv_box_rate_badge">₹ 100.00 / Box</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary py-0.5 px-2" id="dinv_auto_calc_boxes_btn" title="Auto calculate boxes from Total Dispatch Plants (500 Plants/Box)">
                                        <i class="fa fa-calculator me-1"></i> Auto Calculate Boxes
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-3" id="dinv_box_packing_body" style="display: none; background: #fbfcfe;">
                                <div class="row g-3 align-items-center">
                                    <div class="col-md-3 col-6">
                                        <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="font-size: 11px;">
                                            Total Dispatch Plants
                                        </label>
                                        <div class="input-group input-group-sm">
                                            <input type="text" class="form-control text-center fw-bold bg-light" id="dinv_box_dispatch_plants_display" value="0 Plants" readonly>
                                        </div>
                                        <div class="form-text text-muted" id="dinv_box_capacity_hint" style="font-size: 10px;">Capacity: 500 plants / box</div>
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <label class="form-label small fw-bold text-dark text-uppercase mb-1" style="font-size: 11px;">
                                            No. of Boxes <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-white"><i class="fa fa-boxes text-muted"></i></span>
                                            <input type="number" class="form-control text-center fw-bold fs-6 text-primary" id="dinv_box_count" min="0" step="1" value="0" placeholder="0">
                                            <span class="input-group-text bg-light small">Boxes</span>
                                        </div>
                                        <div class="form-text text-muted" style="font-size: 10px;">Enter or auto-calculate boxes</div>
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="font-size: 11px;">
                                            Packing Rate / Box
                                        </label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-white">₹</span>
                                            <input type="number" class="form-control text-center fw-bold" id="dinv_box_rate" min="0" step="any" value="100.00" placeholder="100.00">
                                            <span class="input-group-text bg-light small">/ Box</span>
                                        </div>
                                        <div class="form-text text-muted" style="font-size: 10px;" id="dinv_box_rate_hint">Rate per box (editable)</div>
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <label class="form-label small fw-bold text-success text-uppercase mb-1" style="font-size: 11px;">
                                            Total Box Packing Charges
                                        </label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-success-subtle text-success fw-bold">₹</span>
                                            <input type="text" class="form-control text-end fw-bold fs-6 text-success bg-white border-success-subtle" id="dinv_box_total_charge" value="0.00" readonly>
                                        </div>
                                        <div class="form-text text-success" style="font-size: 10px;" id="dinv_box_calc_formula_text">0 boxes × ₹ 100.00 = ₹ 0.00</div>
                                    </div>
                                </div>
                                <div class="alert alert-light border border-info-subtle py-1.5 px-2.5 mt-2 mb-0 rounded-2 small text-muted d-flex align-items-center gap-2" style="font-size: 11.5px;">
                                    <i class="fa fa-info-circle text-primary"></i>
                                    <div>When enabled, box packing charge is added as a billable item into the dispatched invoice (<code>krf_sales_order_items</code>) and automatically included in taxable value and grand total.</div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Combined Order Quantities & Financial Summary Bar -->
                        <div class="card border-0 shadow-sm rounded-3 mb-3" style="background: #f8fafc;">
                            <div class="card-body p-3">
                                <div class="row g-2 text-center align-items-center">
                                    <div class="col-md-2 col-6 border-end">
                                        <div class="text-muted small fw-bold text-uppercase" style="font-size: 10px;">Total Order Qty</div>
                                        <div class="fs-6 fw-bold text-dark" id="dinv_sum_order_qty">0 Plants</div>
                                    </div>
                                    <div class="col-md-2 col-6 border-end">
                                        <div class="text-success small fw-bold text-uppercase" style="font-size: 10px;">Invoiced / Billable Qty</div>
                                        <div class="fs-6 fw-bold text-success" id="dinv_sum_alloc_qty">0 Plants</div>
                                    </div>
                                    <div class="col-md-2 col-6 border-end">
                                        <div class="text-warning-emphasis small fw-bold text-uppercase" style="font-size: 10px;">Free Shifting Buffer</div>
                                        <div class="fs-6 fw-bold text-warning-emphasis" id="dinv_sum_shifting_qty">0 Plants</div>
                                    </div>
                                    <div class="col-md-2 col-6 border-end">
                                        <div class="text-primary small fw-bold text-uppercase" style="font-size: 10px;">Total Dispatch Plants</div>
                                        <div class="fs-6 fw-bold text-primary" id="dinv_sum_dispatch_qty">0 Plants</div>
                                    </div>
                                    <div class="col-md-2 col-6 border-end">
                                        <div class="text-muted small fw-bold text-uppercase" style="font-size: 10px;">Total Taxable Value</div>
                                        <div class="fs-6 fw-bold text-dark" id="dinv_sum_taxable_amt">₹ 0.00</div>
                                    </div>
                                    <div class="col-md-2 col-6">
                                        <div class="text-muted small fw-bold text-uppercase" style="font-size: 10px;">Total Tax (GST)</div>
                                        <div class="fs-6 fw-bold text-dark" id="dinv_sum_tax_amt">₹ 0.00</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div><!-- End Step 1 -->

                    <!-- ============================================== -->
                    <!-- STEP 2: FREIGHT CHARGE DETAILS & REMARKS       -->
                    <!-- ============================================== -->
                    <div class="dinv-step-pane" id="dinv_step_2" style="display: none;">
                        <!-- 4. Freight Charge Details (Grouped by Address & Date) -->
                        <div class="card border-0 shadow-sm rounded-3 mb-3">
                            <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fw-bold text-dark"><i class="fa fa-hand-holding-usd text-success me-2"></i> Freight Charge Details (Grouped by Address & Date)</span>
                                    <button type="button" class="btn btn-sm btn-outline-primary fw-bold px-2.5 py-1 rounded-2 shadow-2xs dinv-reload-freight-btn" id="dinv_btn_reload_freight" title="Reload Freight & Vehicle Allocation from database">
                                        <i class="fa fa-sync-alt me-1"></i> Reload Freight
                                    </button>
                                </div>
                                <div class="d-flex align-items-center ms-auto flex-wrap gap-2">
                                    <label class="form-label mb-0 small fw-bold text-secondary text-nowrap"><i class="fa fa-truck text-primary me-1"></i>Delivery Type:</label>
                                    <div class="d-flex align-items-center flex-wrap gap-2" id="dinv_delivery_type_radios">
                                        <input type="radio" class="btn-check dinv-delivery-type-radio" name="dinv_delivery_type" id="dinv_deltype_pickup" value="pickup" autocomplete="off">
                                        <label class="btn btn-outline-secondary fw-bold px-3 py-1 rounded-2 shadow-2xs" for="dinv_deltype_pickup" title="1. Self Pickup By Farmer/Client">
                                            <i class="fa fa-user-check me-1"></i> 1. Self Pickup By Farmer/Client
                                        </label>

                                        <input type="radio" class="btn-check dinv-delivery-type-radio" name="dinv_delivery_type" id="dinv_deltype_delivered" value="delivered" autocomplete="off" checked>
                                        <label class="btn btn-outline-primary fw-bold px-3 py-1 rounded-2 shadow-2xs" for="dinv_deltype_delivered" title="2. Farm Delivery">
                                            <i class="fa fa-truck me-1"></i> 2. Farm Delivery
                                        </label>

                                        <input type="radio" class="btn-check dinv-delivery-type-radio" name="dinv_delivery_type" id="dinv_deltype_boxpacking" value="boxpacking" autocomplete="off">
                                        <label class="btn btn-outline-warning fw-bold px-3 py-1 text-dark rounded-2 shadow-2xs" for="dinv_deltype_boxpacking" title="3. Box Packing">
                                            <i class="fa fa-box me-1"></i> 3. Box Packing
                                        </label>
                                    </div>
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-3 py-1.5 rounded-2 ms-1" id="dinv_delivery_mode_badge">Mode: 2. Farm Delivery</span>
                                </div>
                            </div>
                            <div class="card-body p-3">
                                <!-- Unified / Single Delivery Date Option Section -->
                                <div id="dinvUnifiedDeliveryDateSection" class="mb-3"></div>

                                <!-- Freight Eligibility Rules Banner Container -->
                                <div id="dinvFreeFreightNotification" class="mb-3"></div>

                                <!-- Grouped Freight Table -->
                                <div class="table-responsive rounded-3 border bg-white mb-3">
                                    <table class="table table-hover table-bordered align-middle mb-0" id="dinvFreightGroupTable" style="font-size: 0.85rem;">
                                        <thead class="bg-light text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                            <tr class="text-dark">
                                                <th style="min-width: 230px;">CROP NAME / DESCRIPTION</th>
                                                <th style="width: 140px; min-width: 130px;" class="text-center">DELIVERY DATE</th>
                                                <th style="min-width: 230px;">DELIVERY LOCATION</th>
                                                <th style="width: 110px;" class="text-center">Distance (KM)</th>
                                                <th style="min-width: 140px; width: 150px;">FREIGHT AMOUNT</th>
                                                <th style="min-width: 220px; width: 240px;">DISCOUNT (% &amp; AMOUNT)</th>
                                                <th style="min-width: 140px; width: 150px;">PAYABLE FREIGHT</th>
                                                <th style="width: 130px;" class="text-center">VEHICLE</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Dynamically Rendered by JavaScript -->
                                        </tbody>
                                        <tfoot class="bg-light fw-bold fs-6">
                                            <tr>
                                                <td colspan="4" class="text-end pe-3">
                                                    <span class="fw-bold">Grand Total Freight:</span>
                                                </td>
                                                <td id="dinv_tbl_base_freight_display" class="fw-bold font-monospace">0.00</td>
                                                <td id="dinv_tbl_disc_freight_display" class="text-success font-monospace text-center">0.00</td>
                                                <td id="dinv_tbl_payable_freight_display" class="text-primary font-monospace fw-bold">0.00</td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                                <!-- Remarks Section -->
                                <div class="mt-3">
                                    <label class="form-label text-dark small fw-bold mb-1" for="dinv_remarks">
                                        <i class="fa fa-comment-dots text-muted me-1"></i> Remarks
                                    </label>
                                    <textarea class="form-control form-control-sm bg-white" id="dinv_remarks" name="remarks" rows="2" placeholder="Freight discount and dispatch remarks..."></textarea>
                                </div>

                                <input type="hidden" id="dinv_scheduled_dispatch_date" name="scheduled_dispatch_date" value="">
                                <input type="hidden" id="dinv_freight_charges" name="freight_charges" value="0.00">
                                <input type="hidden" id="dinv_tax_amount" name="tax_amount" value="0.00">
                                <input type="hidden" id="dinv_total_amount" name="total_amount" value="0.00">
                            </div>
                        </div>
                    </div><!-- End Step 2 -->

                    <!-- ============================================== -->
                    <!-- STEP 3: GRAND TOTAL, PAYMENTS & ADVANCE HISTORY -->
                    <!-- ============================================== -->
                    <div class="dinv-step-pane" id="dinv_step_3" style="display: none;">
                        <!-- 5. Grand Total Highlight Banner -->
                        <div class="d-flex justify-content-between align-items-center p-3 rounded-3 shadow-sm border mb-3" id="dinv_grand_total_banner" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-color: #86efac !important;">
                            <div>
                                <div class="text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px; color: #166534;">
                                    <i class="fa fa-receipt me-1 text-success"></i> Dispatch Invoice Final Grand Total
                                </div>
                                <div class="small fw-semibold mt-0.5" id="dinv_grand_total_breakdown" style="color: #14532d; font-size: 12px;">
                                    Includes Taxable Value + Applicable Taxes + Freight Charges
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="fs-2 fw-bold font-monospace" id="dinv_grand_total_display" style="color: #15803d;">₹ 0.00</div>
                                <input type="hidden" id="dinv_grand_total" name="grand_total" value="0.00">
                            </div>
                        </div>

                        <!-- 6. Payment & Advance Reconciliation Card -->
                        <div class="card border-0 shadow-sm rounded-3 mb-2 overflow-hidden" id="dinv_payment_summary_card" style="border: 1px solid #e2e8f0;">
                            <div class="card-header bg-white py-2.5 px-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fw-bold text-dark"><i class="fa fa-receipt text-primary me-2"></i> Payment Details & Advance History (Provisional Order: <span id="dinv_pay_psono_badge" class="font-monospace text-primary"></span>)</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary px-2.5 py-1" id="dinv_btn_refresh_payments" title="Refresh Payment History">
                                        <i class="fa fa-sync-alt me-1"></i> Refresh Payments
                                    </button>
                                    <?php 
                                    $currUserRoleId = isset($loginroleid) && $loginroleid !== '' ? intval($loginroleid) : (isset($_SESSION['loginroleid']) && $_SESSION['loginroleid'] !== '' ? intval($_SESSION['loginroleid']) : 99);
                                    if ($currUserRoleId < 3): 
                                    ?>
                                    <button type="button" class="btn btn-sm btn-outline-success fw-bold px-2.5 py-1 shadow-xs" id="dinv_btn_autofill_cash" title="Auto-fill Pending Balance as Cash by adminsystem">
                                        <i class="fa fa-money-bill-wave me-1"></i> Auto-fill Cash (adminsystem)
                                    </button>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-sm btn-primary fw-bold px-3 py-1 shadow-xs" id="dinv_btn_open_receive_payment">
                                        <i class="fa fa-hand-holding-usd me-1"></i> Receive Balance Amount
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-3">
                                <!-- Unverified Payment Alert Box (shown when any unverified payment exists) -->
                                <div id="dinv_unverified_alert_container" class="mb-3" style="display: none;"></div>

                                <!-- 3-Pillar Financial Summary Metrics -->
                                <div class="row g-2 mb-3">
                                    <div class="col-md-4 col-sm-12">
                                        <div class="p-2.5 rounded-3 border bg-light text-center">
                                            <div class="text-muted small fw-bold text-uppercase" style="font-size: 11px;">Invoice Grand Total</div>
                                            <div class="fs-5 fw-bold text-dark font-monospace" id="dinv_metric_grand_total">₹ 0.00</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="p-2.5 rounded-3 border bg-success-subtle border-success-subtle text-center">
                                            <div class="text-success small fw-bold text-uppercase" style="font-size: 11px;">Total Received (Advance)</div>
                                            <div class="fs-5 fw-bold text-success font-monospace" id="dinv_metric_total_received">₹ 0.00</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="p-2.5 rounded-3 border bg-danger-subtle border-danger-subtle text-center" id="dinv_balance_card_container">
                                            <div class="text-danger small fw-bold text-uppercase" style="font-size: 11px;" id="dinv_balance_label">Balance Amount Due</div>
                                            <div class="fs-5 fw-bold text-danger font-monospace" id="dinv_metric_balance_amount">₹ 0.00</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment Received History Table -->
                                <div class="table-responsive rounded-3 border bg-white">
                                    <table class="table table-hover table-bordered align-middle mb-0" id="dinvPaymentHistoryTable" style="font-size: 0.83rem;">
                                        <thead class="bg-light text-uppercase fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                                            <tr class="text-dark">
                                                <th style="width: 45px;" class="text-center">#</th>
                                                <th style="width: 110px;" class="text-center">DATE</th>
                                                <th style="width: 130px;" class="text-end">AMOUNT</th>
                                                <th style="width: 120px;" class="text-center">SOURCE / MODE</th>
                                                <th style="min-width: 140px;">UTR / REF NO</th>
                                                <th style="min-width: 160px;">RECEIVED BY & LOCATION</th>
                                                <th style="width: 90px;" class="text-center">RECEIPT</th>
                                                <th style="width: 110px;" class="text-center">STATUS</th>
                                                <th style="width: 85px;" class="text-center">ACTION</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Dynamically Rendered by JS -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div><!-- End Step 3 -->
                </form>
            </div>
            <div class="modal-footer bg-white py-2 px-4 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-secondary px-4 close-modal-btn" data-dismiss="modal" data-bs-dismiss="modal">
                        <i class="fa fa-times me-1"></i> Cancel
                    </button>
                    <!-- Back Buttons for Steps 2 & 3 -->
                    <button type="button" class="btn btn-outline-secondary px-3 dinv-footer-step-btn dinv-footer-step-2" id="dinv_btn_back_to_1" style="display: none;">
                        <i class="fa fa-arrow-left me-1"></i> Back: Products
                    </button>
                    <button type="button" class="btn btn-outline-secondary px-3 dinv-footer-step-btn dinv-footer-step-3" id="dinv_btn_back_to_2" style="display: none;">
                        <i class="fa fa-arrow-left me-1"></i> Back: Freight Details
                    </button>
                    <div id="dinv_footer_balance_status" class="dinv-footer-step-btn dinv-footer-step-3" style="display: none;"></div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <!-- Step 1 Next Button -->
                    <button type="button" class="btn btn-primary fw-bold px-4 py-2 shadow-sm dinv-footer-step-btn dinv-footer-step-1" id="dinv_btn_next_to_2">
                        Next: Freight Details <i class="fa fa-arrow-right ms-1"></i>
                    </button>
                    <!-- Step 2 Next Button -->
                    <button type="button" class="btn btn-primary fw-bold px-4 py-2 shadow-sm dinv-footer-step-btn dinv-footer-step-2" id="dinv_btn_next_to_3" style="display: none;">
                        Next: Payment & Finalize <i class="fa fa-arrow-right ms-1"></i>
                    </button>
                    <!-- Step 3 Action Buttons -->
                    <button type="button" class="btn btn-info text-white fw-bold px-4 py-2 shadow-sm dinv-footer-step-btn dinv-footer-step-3" id="previewDispatchInvoicePdfBtn" style="display: none;">
                        <i class="fa fa-file-pdf me-1"></i> Preview Invoice (PDF)
                    </button>
                    <button type="button" class="btn btn-success fw-bold px-4 py-2 shadow-sm dinv-footer-step-btn dinv-footer-step-3" id="confirmGenerateDispatchInvoiceBtn" style="display: none;">
                        <i class="fa fa-check-circle me-1"></i> Generate & Save Dispatch Invoice
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Payment Advice Manual (Collect Balance Payment Against Invoice) -->
<div class="modal fade" id="dinvReceivePaymentModal" tabindex="-1" role="dialog" aria-labelledby="dinvReceivePaymentModalLabel" aria-hidden="true" style="z-index: 1070;">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 95vw; width: 95vw; margin: 15px auto;">
    <div class="modal-content shadow-lg border-0" style="border-radius: 14px; overflow: hidden; height: 90vh; display: flex; flex-direction: column;">
      <div class="modal-header py-2.5 px-3 text-white d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); flex-shrink: 0;">
        <div class="d-flex align-items-center gap-2">
          <i class="fa fa-receipt text-warning fs-5"></i>
          <div>
            <h5 class="modal-title fw-bold text-white mb-0 fs-6 d-flex align-items-center">
              Payment Advice Manual
              <span class="badge bg-white text-primary ms-2 px-2 py-0.5" id="dinv_pay_modal_ord_badge" style="font-size: 11px;"></span>
            </h5>
            <small class="text-white-50" style="font-size: 11px;">Securely record and verify client payments via Payment Advice Manual</small>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <button type="button" class="btn btn-sm btn-outline-light px-2.5 py-1" id="dinv_btn_refresh_iframe_pam" title="Reload Payment Advice Page">
            <i class="fa fa-sync-alt me-1"></i> Reload Form
          </button>
          <button type="button" class="btn btn-sm btn-outline-light close-modal-btn d-flex align-items-center justify-content-center" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close" style="width: 32px; height: 32px; border-radius: 6px; padding: 0; background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.4); color: #ffffff;" title="Close Modal">
            <i class="fa fa-times" style="font-size: 16px; color: #ffffff;"></i>
          </button>
        </div>
      </div>
      <div class="modal-body p-0 bg-light" style="flex: 1 1 auto; overflow: hidden; position: relative;">
        <iframe id="dinvPaymentAdviceIframe" src="" style="width: 100%; height: 100%; border: none; display: block;" frameborder="0"></iframe>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Preview Invoice as PDF in Modal (No new tab) -->
<div class="modal fade" id="dinvInvoicePdfPreviewModal" tabindex="-1" role="dialog" aria-labelledby="dinvInvoicePdfPreviewModalLabel" aria-hidden="true" style="z-index: 1080;">
  <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 92vw; margin: 10px auto;">
    <div class="modal-content shadow-lg border-0" style="border-radius: 14px; overflow: hidden; height: 90vh;">
      <div class="modal-header py-2 px-3 text-white d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);">
        <div class="d-flex align-items-center gap-2">
          <i class="fa fa-file-pdf text-white fs-5"></i>
          <h5 class="modal-title fw-bold text-white mb-0 fs-6" id="dinvInvoicePdfPreviewModalLabel">
            Invoice PDF Preview
          </h5>
          <span class="badge bg-white text-primary border ms-2" id="dinv_preview_psono_badge" style="font-size: 11px;"></span>
        </div>
        <div class="d-flex align-items-center gap-2">
          <a href="#" id="dinv_pdf_download_link" download class="btn btn-sm btn-light text-primary fw-bold px-3 py-1 shadow-2xs" target="_blank" title="Download PDF copy">
            <i class="fa fa-download me-1"></i> Download
          </a>
          <button type="button" class="btn btn-sm btn-outline-light close-modal-btn d-flex align-items-center justify-content-center" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close" style="width: 32px; height: 32px; border-radius: 6px; padding: 0; background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.4); color: #ffffff;" title="Close Modal">
            <i class="fa fa-times" style="font-size: 16px; color: #ffffff;"></i>
          </button>
        </div>
      </div>
      <div class="modal-body p-0 bg-dark position-relative" style="height: calc(90vh - 52px); overflow: hidden;">
        <div id="dinv_pdf_iframe_container" class="w-100 h-100 d-flex justify-content-center align-items-center">
          <div class="text-center text-white py-5">
            <div class="spinner-border text-light" role="status"></div>
            <p class="mt-2 small">Generating Invoice PDF Preview...</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Download Invoices Popup for Order -->
<div class="modal fade" id="orderInvoicesDownloadModal" tabindex="-1" role="dialog" aria-labelledby="orderInvoicesDownloadModalLabel" aria-hidden="true" style="z-index: 1060;">
  <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width: 860px;">
    <div class="modal-content shadow-lg border-0" style="border-radius: 14px; overflow: hidden;">
      <div class="modal-header py-2.5 px-3 text-white d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #059669 0%, #047857 100%);">
        <div class="d-flex align-items-center gap-2">
          <i class="fa fa-file-invoice text-white fs-5"></i>
          <div>
            <h5 class="modal-title fw-bold text-white mb-0 fs-6" id="orderInvoicesDownloadModalLabel">
              Download Invoices for Order
            </h5>
            <small class="text-white-50" id="orderInvoicesModalSubTitle">All generated dispatch invoices for this order</small>
          </div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-light close-modal-btn d-flex align-items-center justify-content-center" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close" style="width: 32px; height: 32px; border-radius: 6px; padding: 0; background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.4); color: #ffffff;" title="Close Modal">
          <i class="fa fa-times" style="font-size: 16px; color: #ffffff;"></i>
        </button>
      </div>
      <div class="modal-body p-3 bg-light">
        <!-- Order & Client Info Summary Header -->
        <div class="card border border-success-subtle mb-3 shadow-2xs" style="background: #f0fdf4;">
          <div class="card-body p-2.5 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
              <span class="text-muted small">Client:</span>
              <strong class="text-dark ms-1" id="orderInvModalClientName">-</strong>
              <span class="text-muted small ms-3">Mobile:</span>
              <strong class="text-dark ms-1" id="orderInvModalMobile">-</strong>
            </div>
            <div>
              <span class="badge bg-success-subtle text-success border border-success font-monospace" id="orderInvModalPsonoBadge">PSONO: -</span>
              <span class="badge bg-secondary-subtle text-dark border font-monospace ms-1" id="orderInvModalSonoBadge">SO: -</span>
            </div>
          </div>
        </div>

        <!-- Invoices List Table -->
        <div class="table-responsive bg-white rounded border shadow-2xs">
          <table class="table table-hover table-striped align-middle mb-0" style="font-size: 12.5px;">
            <thead class="table-light text-secondary border-bottom">
              <tr>
                <th class="text-center" style="width: 40px;">#</th>
                <th>Invoice No</th>
                <th>Date</th>
                <th class="text-center">Plants</th>
                <th class="text-end">Amount</th>
                <th>Delivery / Vehicle</th>
                <th class="text-center">Status</th>
                <th class="text-center" style="width: 140px;">Action</th>
              </tr>
            </thead>
            <tbody id="orderInvoicesTableBody">
              <!-- Dynamically populated via AJAX -->
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer py-2 px-3 bg-white border-top d-flex justify-content-between">
        <span class="text-muted small" id="orderInvoicesSummaryCount"></span>
        <button type="button" class="btn btn-secondary btn-sm px-3 fw-semibold" data-bs-dismiss="modal" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Update Delivery Address & Calculate Road Distance Map Locator -->
<div class="modal fade" id="adddeliveryaddressModal" tabindex="-1" role="dialog" aria-labelledby="adddeliveryaddressModalLabel" aria-hidden="true" style="z-index: 1065;">
  <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 95vw; margin: 10px auto;">
    <div class="modal-content shadow-lg border-0" style="border-radius: 16px; overflow: hidden;">
      <div class="modal-header bg-primary text-white py-2 px-3 d-flex justify-content-between align-items-center">
        <h5 class="modal-title fw-bold fs-15 text-white mb-0" id="adddeliveryaddressModalLabel">
          <i class="fa fa-map-marker-alt me-2"></i> Update Delivery Address & Calculate Road Distance
        </h5>
        <button type="button" class="btn btn-sm btn-outline-light close-modal-btn d-flex align-items-center justify-content-center" onclick="$('#adddeliveryaddressModal').modal('hide')" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close" style="width: 32px; height: 32px; border-radius: 6px; padding: 0; background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.4); color: #ffffff;" title="Close Modal">
          <i class="fa fa-times" style="font-size: 16px; color: #ffffff;"></i>
        </button>
      </div>
      <div class="modal-body p-2" style="min-height: 640px; overflow: hidden;">
        <div class="card p-2 mb-2 border-0 bg-light shadow-sm">
          <div class="row g-2 align-items-center">
            <div class="col-md-6">
              <label class="form-label fw-bold mb-1 small text-dark"><i class="fa fa-crosshairs text-primary me-1"></i>Coordinates (Latitude, Longitude):</label>
              <input type="text" class="form-control form-control-sm border-primary-subtle shadow-sm" name="latlong" id="latlonginput" placeholder="e.g. 23.122173, 83.199275"
                oninput="if(typeof initLatLongSplit === 'function') initLatLongSplit('latlonginput', 'latinput', 'longinput');" />
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold mb-1 small text-muted">Latitude:</label>
              <input type="text" class="form-control form-control-sm bg-white" name="latinput" id="latinput" placeholder="Lat" />
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold mb-1 small text-muted">Longitude:</label>
              <input type="text" class="form-control form-control-sm bg-white" name="longinput" id="longinput" placeholder="Long" />
            </div>
          </div>
        </div>

        <div id="appenddeliveryaddressdiv" style="min-height: 620px; overflow: hidden;"></div> 

      </div>
    </div>
  </div>
</div>

<!-- Modal: Manual Delivery Address & Distance Entry -->
<div class="modal fade" id="manualDeliveryAddressModal" tabindex="-1" role="dialog" aria-labelledby="manualDeliveryAddressModalLabel" aria-hidden="true" style="z-index: 1070;">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 580px; margin: 20px auto;">
    <div class="modal-content shadow-lg border-0" style="border-radius: 14px; overflow: hidden;">
      <div class="modal-header bg-primary text-white py-2.5 px-3 d-flex justify-content-between align-items-center">
        <h5 class="modal-title fw-bold fs-15 text-white mb-0" id="manualDeliveryAddressModalLabel">
          <i class="fa fa-map-marked-alt me-2"></i> Update Manual Delivery Address & Distance
        </h5>
        <button type="button" class="btn btn-sm btn-outline-light close-modal-btn d-flex align-items-center justify-content-center" onclick="$('#manualDeliveryAddressModal').modal('hide')" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close" style="width: 30px; height: 30px; border-radius: 6px; padding: 0; background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.4); color: #ffffff;" title="Close Modal">
          <i class="fa fa-times" style="font-size: 15px; color: #ffffff;"></i>
        </button>
      </div>
      <form id="manualDeliveryAddressForm" autocomplete="off">
        <div class="modal-body p-3 bg-light">
          <input type="hidden" id="manual_addr_group_index" value="0">
          <input type="hidden" id="manual_addr_psono" value="">
          <input type="hidden" id="manual_addr_clientid" value="">
          <input type="hidden" id="manual_addr_projectid" value="">

          <div class="alert alert-info py-2 px-3 mb-2.5 small d-flex align-items-center gap-2 border-0 shadow-xs" style="border-radius: 8px;">
            <i class="fa fa-info-circle fs-6 text-primary"></i>
            <div>Fill in State, District, Village, and Approximate Distance to update <b>krf_deliveryaddress_location</b> and calculate freight.</div>
          </div>

          <div class="card border-0 shadow-sm p-3 mb-0" style="border-radius: 10px;">
            <div class="row g-2.5">
              <!-- 1. State (Master) -->
              <div class="col-md-6">
                <label class="form-label fw-bold small text-dark mb-1">State <span class="text-danger">*</span></label>
                <select class="form-select form-select-sm fw-bold border-secondary-subtle" id="manual_addr_state" name="state" required>
                  <option value="">-- Loading States... --</option>
                </select>
              </div>

              <!-- 2. District (Master) -->
              <div class="col-md-6">
                <label class="form-label fw-bold small text-dark mb-1">District <span class="text-danger">*</span></label>
                <select class="form-select form-select-sm fw-bold border-secondary-subtle" id="manual_addr_district" name="district" required>
                  <option value="">-- Select District --</option>
                </select>
              </div>

              <!-- 3. Block / Tahsil -->
              <div class="col-md-6">
                <label class="form-label fw-bold small text-dark mb-1">Block / Tahsil</label>
                <input type="text" class="form-control form-control-sm" id="manual_addr_block" name="block" placeholder="Enter Block or Tahsil">
              </div>

              <!-- 4. City / Village -->
              <div class="col-md-6">
                <label class="form-label fw-bold small text-dark mb-1">City / Village <span class="text-danger">*</span></label>
                <input type="text" class="form-control form-control-sm fw-bold" id="manual_addr_city" name="city" placeholder="Enter City or Village" required>
              </div>

              <!-- 5. Approx Distance (KM) -->
              <div class="col-md-7">
                <label class="form-label fw-bold small text-dark mb-1">Approx Distance (KM) <span class="text-danger">*</span></label>
                <div class="input-group input-group-sm">
                  <input type="number" step="0.1" min="0" class="form-control form-control-sm fw-bold text-primary fs-6" id="manual_addr_distance" name="distance_km" placeholder="e.g. 85" required>
                  <span class="input-group-text bg-white fw-bold text-muted">KM</span>
                </div>
              </div>

              <!-- Pincode (Optional) -->
              <div class="col-md-5">
                <label class="form-label fw-bold small text-dark mb-1">Pincode</label>
                <input type="text" class="form-control form-control-sm" id="manual_addr_pincode" name="pincode" placeholder="e.g. 492001" maxlength="6">
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer py-2 px-3 bg-white border-top d-flex justify-content-between">
          <button type="button" class="btn btn-sm btn-secondary px-3" onclick="$('#manualDeliveryAddressModal').modal('hide')" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-sm btn-success fw-bold px-3 shadow-xs" id="btn_save_manual_address">
            <i class="fa fa-check-circle me-1"></i> Save & Refresh Distance
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Box Packing Delivery & Freight Calculator -->
<div class="modal fade" id="boxPackingModal" tabindex="-1" role="dialog" aria-labelledby="boxPackingModalLabel" aria-hidden="true" style="z-index: 1075;">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 620px; margin: 20px auto;">
    <div class="modal-content shadow-lg border-0" style="border-radius: 14px; overflow: hidden;">
      <div class="modal-header bg-warning text-dark py-2.5 px-3 d-flex justify-content-between align-items-center">
        <h5 class="modal-title fw-bold fs-15 mb-0 text-dark" id="boxPackingModalLabel">
          <i class="fa fa-box-open me-2 text-dark"></i> 3. Box Packing Delivery & Freight Calculator
        </h5>
        <button type="button" class="btn btn-sm btn-outline-dark close-modal-btn d-flex align-items-center justify-content-center" onclick="$('#boxPackingModal').modal('hide')" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close" style="width: 30px; height: 30px; border-radius: 6px; padding: 0; background: rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.2); color: #000000;" title="Close Modal">
          <i class="fa fa-times" style="font-size: 15px;"></i>
        </button>
      </div>
      <form id="boxPackingDeliveryForm" autocomplete="off">
        <div class="modal-body p-3 bg-light">
          <input type="hidden" id="bp_group_index" value="0">
          <input type="hidden" id="bp_psono" value="">
          <input type="hidden" id="bp_clientid" value="">
          <input type="hidden" id="bp_projectid" value="">

          <div class="alert alert-warning py-2 px-3 mb-2.5 small d-flex align-items-center gap-2 border-0 shadow-xs text-dark" style="border-radius: 8px; background-color: #fff3cd;">
            <i class="fa fa-info-circle fs-6 text-warning"></i>
            <div>Configure destination and box rates. Total freight is automatically calculated as: <b>Total Boxes × Rate Per Box</b>.</div>
          </div>

          <div class="card border-0 shadow-sm p-3 mb-0" style="border-radius: 10px;">
            <div class="row g-2.5">
              <!-- 1. State -->
              <div class="col-md-6">
                <label class="form-label fw-bold small text-dark mb-1">Destination State <span class="text-danger">*</span></label>
                <select class="form-select form-select-sm fw-bold border-secondary-subtle" id="bp_state" name="state" required>
                  <option value="">-- Select State --</option>
                </select>
              </div>

              <!-- 2. District -->
              <div class="col-md-6">
                <label class="form-label fw-bold small text-dark mb-1">Destination District <span class="text-danger">*</span></label>
                <select class="form-select form-select-sm fw-bold border-secondary-subtle" id="bp_district" name="district" required>
                  <option value="">-- Select District --</option>
                </select>
              </div>

              <!-- 3. Destination City / Location -->
              <div class="col-md-6">
                <label class="form-label fw-bold small text-dark mb-1">Destination City / Station <span class="text-danger">*</span></label>
                <input type="text" class="form-control form-control-sm fw-bold" id="bp_city" name="city" placeholder="Enter City or select" required list="bp_location_datalist">
                <datalist id="bp_location_datalist"></datalist>
              </div>

              <!-- 4. Transport Type -->
              <div class="col-md-6">
                <label class="form-label fw-bold small text-dark mb-1">Transport Service Type <span class="text-danger">*</span></label>
                <select class="form-select form-select-sm fw-bold border-secondary-subtle" id="bp_transport_type" name="transport_type" required>
                  <option value="Bus" selected>Bus Transport</option>
                  <option value="Airport">Air Cargo / Airport</option>
                  <option value="Railway">Railway Cargo</option>
                  <option value="Standard">Standard Box Transport</option>
                </select>
              </div>

              <!-- 5. Total Quantity (Plants) -->
              <div class="col-md-4">
                <label class="form-label fw-bold small text-dark mb-1">Dispatch Plants</label>
                <input type="number" class="form-control form-control-sm bg-light fw-bold text-dark" id="bp_total_qty" readonly value="0">
              </div>

              <!-- 6. Plants Per Box -->
              <div class="col-md-4">
                <label class="form-label fw-bold small text-dark mb-1">Plants Per Box <span class="text-danger">*</span></label>
                <input type="number" min="1" step="1" class="form-control form-control-sm fw-bold" id="bp_plants_per_box" name="plants_per_box" value="1000" required>
              </div>

              <!-- 7. Calculated Boxes -->
              <div class="col-md-4">
                <label class="form-label fw-bold small text-dark mb-1">Calculated Boxes</label>
                <div class="input-group input-group-sm">
                  <input type="number" min="1" step="1" class="form-control form-control-sm fw-bold text-primary fs-6" id="bp_box_count" name="boxes" value="1" required>
                  <span class="input-group-text bg-white fw-bold text-muted">Box</span>
                </div>
              </div>

              <!-- 8. Charge Per Box -->
              <div class="col-md-6">
                <label class="form-label fw-bold small text-dark mb-1">Charge Per Box (₹) <span class="text-danger">*</span></label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text bg-white fw-bold">₹</span>
                  <input type="number" step="0.01" min="0" class="form-control form-control-sm fw-bold text-dark fs-6" id="bp_charge_per_box" name="charges_per_box" value="70.00" required>
                </div>
              </div>

              <!-- 9. Total Box Freight -->
              <div class="col-md-6">
                <label class="form-label fw-bold small text-dark mb-1">Total Box Freight (₹)</label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text bg-light fw-bold">₹</span>
                  <input type="number" step="0.01" min="0" class="form-control form-control-sm bg-light fw-bold text-success fs-6" id="bp_total_freight" name="total_freight" value="70.00" readonly>
                </div>
              </div>

              <!-- 10. Remarks / Counter Details -->
              <div class="col-12">
                <label class="form-label fw-bold small text-dark mb-1">Delivery / Booking Remarks</label>
                <input type="text" class="form-control form-control-sm" id="bp_remark" name="remark" placeholder="e.g. Royal Travels Counter / Bus Contact Info">
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer py-2 px-3 bg-white border-top d-flex justify-content-between">
          <button type="button" class="btn btn-sm btn-secondary px-3" onclick="$('#boxPackingModal').modal('hide')" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-sm btn-warning text-dark fw-bold px-3 shadow-xs" id="btn_save_box_packing">
            <i class="fa fa-check-circle me-1"></i> Apply & Refresh Box Freight
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Assign Vehicle, Transporter & Driver Details with Live Capacity & Trip ID -->
<div class="modal fade" id="assignVehicleDriverModal" tabindex="-1" role="dialog" aria-labelledby="assignVehicleDriverModalLabel" aria-hidden="true" style="z-index: 1075;">
  <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 980px; width: 95vw; margin: 15px auto;">
    <div class="modal-content shadow-lg border-0" style="border-radius: 14px; overflow: hidden;">
      <div class="modal-header py-2.5 px-3 text-white d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
        <div class="d-flex align-items-center gap-2">
          <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 34px; height: 34px;">
            <i class="fa fa-truck fs-6"></i>
          </div>
          <div>
            <h5 class="modal-title fw-bold text-white mb-0 fs-6" id="assignVehicleDriverModalLabel">
              Assign Vehicle, Transporter &amp; Driver Details
            </h5>
            <div class="text-white-50 small" style="font-size: 11px;">Select vehicle from master, check capacity &amp; assign multi-location trip</div>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span class="badge bg-primary text-white font-monospace px-2.5 py-1" id="vmodal_trip_id_header_badge">Trip ID: Auto</span>
          <button type="button" class="btn btn-sm btn-outline-light close-modal-btn d-flex align-items-center justify-content-center" onclick="$('#assignVehicleDriverModal').modal('hide')" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close" style="width: 30px; height: 30px; border-radius: 6px; padding: 0; background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.4); color: #ffffff;" title="Close Modal">
            <i class="fa fa-times" style="font-size: 15px; color: #ffffff;"></i>
          </button>
        </div>
      </div>
      <form id="assignVehicleDriverForm" autocomplete="off">
        <div class="modal-body p-3 bg-light">
          <input type="hidden" id="vmodal_group_index" value="0">
          <input type="hidden" id="vmodal_psono" value="">
          <input type="hidden" id="vmodal_clientid" value="">
          <input type="hidden" id="vmodal_projectid" value="">
          <input type="hidden" id="vmodal_deliverydate" value="">
          <input type="hidden" id="vmodal_order_plants" value="0">
          <input type="hidden" id="vmodal_distance" value="0">
          <input type="hidden" id="vmodal_freightamt" value="0">
          <input type="hidden" id="vmodal_trip_id" name="trip_id" value="">
          <input type="hidden" id="vmodal_driver_id" name="driver_id" value="">

          <!-- Trip Capacity & Multi-Location Delivery Status Card -->
          <div class="card border-0 shadow-sm p-3 mb-3" style="border-radius: 10px; background: #ffffff;">
            <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
              <div class="d-flex align-items-center gap-2">
                <i class="fa fa-route text-primary fs-5"></i>
                <div>
                  <span class="fw-bold text-dark fs-6">Trip ID:</span>
                  <span class="fw-bold text-primary font-monospace fs-6 ms-1" id="vmodal_trip_id_text">TRIP-AUTO</span>
                </div>
              </div>
              <div id="vmodal_trip_badge_container">
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 fw-bold" id="vmodal_trip_status_badge">
                  <i class="fa fa-check-circle me-1"></i> Multi-Location Trip
                </span>
              </div>
            </div>

            <!-- Capacity Progress & Metrics -->
            <div class="row g-2 mb-2 text-center" style="font-size: 0.8rem;">
              <div class="col-3">
                <div class="p-2 rounded-2 bg-light border">
                  <div class="text-muted small fw-bold text-uppercase" style="font-size: 10px;">Vehicle Capacity</div>
                  <div class="fw-bold text-dark fs-6" id="vmodal_cap_total">0 Plants</div>
                </div>
              </div>
              <div class="col-3">
                <div class="p-2 rounded-2 bg-light border">
                  <div class="text-muted small fw-bold text-uppercase" style="font-size: 10px;">Active Trip Load</div>
                  <div class="fw-bold text-secondary fs-6" id="vmodal_cap_active">0 Plants</div>
                </div>
              </div>
              <div class="col-3">
                <div class="p-2 rounded-2 bg-primary-subtle border border-primary-subtle">
                  <div class="text-primary small fw-bold text-uppercase" style="font-size: 10px;">This Order Qty</div>
                  <div class="fw-bold text-primary fs-6" id="vmodal_cap_this_order">0 Plants</div>
                </div>
              </div>
              <div class="col-3">
                <div class="p-2 rounded-2 bg-success-subtle border border-success-subtle" id="vmodal_cap_remaining_box">
                  <div class="text-success small fw-bold text-uppercase" style="font-size: 10px;">Remaining Capacity</div>
                  <div class="fw-bold text-success fs-6" id="vmodal_cap_remaining">0 Plants</div>
                </div>
              </div>
            </div>

            <!-- Progress Bar -->
            <div class="mb-1">
              <div class="d-flex justify-content-between small text-muted mb-1" style="font-size: 11px;">
                <span id="vmodal_progress_label">Vehicle Capacity Utilization</span>
                <span class="fw-bold font-monospace" id="vmodal_progress_percent">0%</span>
              </div>
              <div class="progress" style="height: 10px; border-radius: 6px;">
                <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" id="vmodal_capacity_bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
              </div>
            </div>

            <!-- Trip Multi-location Notice Banner -->
            <div class="alert alert-info py-2 px-3 mt-2 mb-0 small d-flex align-items-start gap-2 border-0 shadow-2xs" id="vmodal_trip_notice" style="border-radius: 8px;">
              <i class="fa fa-info-circle fs-6 text-primary mt-0.5"></i>
              <div id="vmodal_trip_notice_text">
                1 Trip ID can deliver to multiple locations within vehicle capacity.
              </div>
            </div>
          </div>

          <!-- Vehicle Master & Transporter Details Card -->
          <div class="card border-0 shadow-sm p-3 mb-3" style="border-radius: 10px; background: #ffffff;">
            <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
              <div class="d-flex align-items-center gap-2">
                <i class="fa fa-truck-moving text-primary"></i>
                <span class="fw-bold text-dark small">1. Vehicle &amp; Transporter Information (krf_vehicle_master)</span>
              </div>
              <div id="vmodal_transporter_missing_badge" style="display: none;">
                <span class="badge bg-warning text-dark border border-warning px-2.5 py-1 fw-bold">
                  <i class="fa fa-exclamation-triangle me-1"></i> Transporter Missing in Master - Add Details
                </span>
              </div>
            </div>
            <div class="row g-2.5">
              <!-- Vehicle Select Dropdown (with Search) -->
              <div class="col-md-6">
                <label class="form-label fw-bold small text-dark mb-1">Select Vehicle from Master <span class="text-danger">*</span></label>
                <select class="form-select form-select-sm fw-bold border-primary-subtle" id="vmodal_vehicle_select" style="width: 100%;" required>
                  <option value="">-- Choose Vehicle from Master --</option>
                </select>
              </div>

              <!-- Vehicle Name -->
              <div class="col-md-6">
                <label class="form-label fw-bold small text-dark mb-1">Vehicle Name / Type <span class="text-danger">*</span></label>
                <input type="text" class="form-control form-control-sm fw-bold" id="vmodal_vehiclename" name="vehiclename" placeholder="e.g. Mahindra Bolero Pickup, E-Auto" required>
              </div>

              <!-- Vehicle Number / Plate -->
              <div class="col-md-6">
                <label class="form-label fw-bold small text-dark mb-1">Vehicle Number (Plate No) <span class="text-danger">*</span></label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text bg-white"><i class="fa fa-car"></i></span>
                  <input type="text" class="form-control form-control-sm fw-bold text-uppercase font-monospace" id="vmodal_vehicleno" name="vehicleno" placeholder="e.g. CG-15-AB-1234" required>
                </div>
              </div>

              <!-- Transporter Name (Vehicle Owner) -->
              <div class="col-md-6">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label class="form-label fw-bold small text-dark mb-0">Transporter / Vehicle Owner Name <span class="text-danger">*</span></label>
                </div>
                <div class="row g-1">
                  <div class="col-12 mb-1">
                    <select class="form-select form-select-sm" id="vmodal_transporter_select" style="width: 100%;">
                      <option value="">-- Choose Transporter or Type New Below --</option>
                    </select>
                  </div>
                  <div class="col-12">
                    <div class="input-group input-group-sm">
                      <span class="input-group-text bg-white"><i class="fa fa-building text-primary"></i></span>
                      <input type="text" class="form-control form-control-sm fw-bold" id="vmodal_transporter_name" name="transporter_name" placeholder="e.g. Aman Roadways / KRF Logistics" required>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Driver Information Card -->
          <div class="card border-0 shadow-sm p-3 mb-0" style="border-radius: 10px; background: #ffffff;">
            <div class="d-flex align-items-center gap-2 mb-2 pb-1 border-bottom">
              <i class="fa fa-id-card text-primary"></i>
              <span class="fw-bold text-dark small">2. Driver Details (krf_vehicle_driver_master)</span>
            </div>
            <div class="row g-2.5">
              <!-- Driver Select Dropdown (with Search) -->
              <div class="col-md-4">
                <label class="form-label fw-bold small text-dark mb-1">Existing Driver List</label>
                <select class="form-select form-select-sm border-secondary-subtle" id="vmodal_driver_select" style="width: 100%;">
                  <option value="">-- Select Existing Driver --</option>
                </select>
              </div>

              <!-- Driver Name -->
              <div class="col-md-4">
                <label class="form-label fw-bold small text-dark mb-1">Driver Name <span class="text-danger">*</span></label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text bg-white"><i class="fa fa-user text-secondary"></i></span>
                  <input type="text" class="form-control form-control-sm fw-bold" id="vmodal_driver_name" name="driver_name" placeholder="e.g. Ramesh Kumar" required>
                </div>
              </div>

              <!-- Driver Mobile No -->
              <div class="col-md-4">
                <label class="form-label fw-bold small text-dark mb-1">Driver Mobile Number <span class="text-danger">*</span></label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text bg-white"><i class="fa fa-phone text-success"></i></span>
                  <input type="tel" class="form-control form-control-sm fw-bold font-monospace" id="vmodal_driver_mobile" name="driver_mobile" placeholder="e.g. 9876543210" maxlength="15" required>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer py-2 px-3 bg-white border-top d-flex justify-content-between">
          <button type="button" class="btn btn-sm btn-secondary px-3" onclick="$('#assignVehicleDriverModal').modal('hide')" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-sm btn-primary fw-bold px-4 shadow-sm" id="btn_save_vehicle_driver">
            <i class="fa fa-check-circle me-1"></i> Save &amp; Confirm Vehicle &amp; Trip
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: PDF & Print Preview for Final Dispatch Schedule -->
<div class="modal fade" id="dispatchPdfPreviewModal" tabindex="-1" role="dialog" aria-labelledby="dispatchPdfPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document" style="max-width: 95vw;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header py-2.5 px-4 d-flex justify-content-between align-items-center bg-dark text-white">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa fa-file-pdf text-danger fs-5"></i>
                    <div>
                        <h6 class="modal-title fw-bold mb-0 text-white" id="dispatchPdfPreviewModalLabel">Final Dispatch Timetable Schedule Report</h6>
                        <span class="text-white-50 small" id="dispatchPdfPreviewSubtitle" style="font-size: 11.5px;">A4 Landscape Print / PDF Document</span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-primary px-3 fw-bold" id="btnDispatchPdfModalPrint">
                        <i class="fa fa-print me-1"></i> Print
                    </button>
                    <button type="button" class="btn btn-sm btn-danger px-3 fw-bold" id="btnDispatchPdfModalDownload">
                        <i class="fa fa-download me-1"></i> Download PDF
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-light px-2.5" data-bs-dismiss="modal" data-dismiss="modal">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="modal-body p-2 p-md-3" style="background-color: #525659; overflow-y: auto; max-height: 80vh;">
                <!-- Printable Paper Container (Full-width A4 Landscape) -->
                <div id="dispatchPdfPrintableDocument" class="bg-white shadow-lg mx-auto p-3 p-md-4" style="width: 100%; max-width: 1080px; min-height: 300px; color: #1e293b; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; border-radius: 4px; box-sizing: border-box;">
                    <!-- Dynamically populated via JS -->
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-4 border-top d-flex justify-content-between align-items-center">
                <span class="text-muted small" id="dispatchPdfFooterCount">Total orders in report: 0</span>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary btn-sm px-3 fw-bold" id="btnDispatchPdfModalPrintBottom">
                        <i class="fa fa-print me-1"></i> Print Report
                    </button>
                    <button type="button" class="btn btn-danger btn-sm px-3 fw-bold" id="btnDispatchPdfModalDownloadBottom">
                        <i class="fa fa-download me-1"></i> Download PDF
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('../footer.php'); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script type="text/javascript" src="<?php echo $prefix; ?>krf-orderandsales/js/pjs_function.js"></script>
<script type="text/javascript" src="<?php echo $prefix; ?>krf-orderandsales/js/vehicle_calculator.js?v=<?php echo time(); ?>"></script>

<script>
const CURRENT_APP_COMPANYID = "<?php echo htmlspecialchars($companyid ?? '', ENT_QUOTES); ?>";
const CURRENT_APP_SUBCOMPANYID = "<?php echo htmlspecialchars($subcompanyid ?? '', ENT_QUOTES); ?>";
const CURRENT_APP_PROJECTID = "<?php echo htmlspecialchars($projectid ?? '', ENT_QUOTES); ?>";
const CURRENT_APP_LOGINROLEID = "<?php echo (isset($loginroleid) && $loginroleid !== '' ? (string)$loginroleid : (isset($_SESSION['loginroleid']) && $_SESSION['loginroleid'] !== '' ? (string)$_SESSION['loginroleid'] : '')); ?>";
const CURRENT_APP_LOGINID = "<?php echo htmlspecialchars($loginid ?? $_SESSION['loginid'] ?? '', ENT_QUOTES); ?>";
const CURRENT_APP_LOGINNAME = "<?php echo htmlspecialchars($loginname ?? $_SESSION['loginname'] ?? $_SESSION['username'] ?? '', ENT_QUOTES); ?>";
const CURRENT_ROOTURL = "<?php echo htmlspecialchars($rooturl ?? $prefix ?? '', ENT_QUOTES); ?>";
const CURRENT_APP_KEY = "<?php echo htmlspecialchars($_GET['k'] ?? $key ?? '', ENT_QUOTES); ?>";
const CURRENT_APP_MDID = "<?php echo htmlspecialchars($_GET['mdid'] ?? $mdid ?? '', ENT_QUOTES); ?>";
const CURRENT_APP_DZ = "<?php echo htmlspecialchars($_GET['dz'] ?? '', ENT_QUOTES); ?>";

function canUserMarkDispatch() {
    let roleStr = CURRENT_APP_LOGINROLEID;
    if (roleStr === "" || roleStr === null || roleStr === undefined) {
        roleStr = $("body").data("loginroleid");
    }
    if (roleStr === undefined || roleStr === null || roleStr === "") {
        return false;
    }
    let roleId = parseInt(roleStr, 10);
    // 'Mark Dispatch' and 'Mark-Dispatched-Without Plant Deduction' buttons display ONLY if loginroleid < 3 (i.e. role 0, 1, 2)
    return (!isNaN(roleId) && roleId >= 0 && roleId < 3);
}

$(document).ready(function() {
    // Universal Modal Dismiss Handler (works for Bootstrap 4, Bootstrap 5, and custom modals)
    $(document).on("click", '[data-bs-dismiss="modal"], [data-dismiss="modal"], .close-modal-btn', function(e) {
        e.preventDefault();
        let $modal = $(this).closest(".modal");
        $modal.modal("hide");
        setTimeout(function() {
            if ($(".modal.show").filter(":visible").length > 0) {
                $("body").addClass("modal-open");
            } else {
                $(".modal-backdrop").remove();
                $("body").removeClass("modal-open").css("padding-right", "");
            }
        }, 200);
    });

    let globalWeekMap = {};
    let globalSortedWeeks = [];
    let globalMonths = [];
    let currentRawData = [];
    let currentRawDataIsOldPending = false;
    let currentWeekRange = "";
    let lastRegularWeekRange = "";
    let currentFilter = "all";
    let cachedAvailableStockOrders = [];

    loadActiveFinalDispatchDates();

    function loadActiveFinalDispatchDates() {
        let projectid = $("body").data("projectid") || 17;

        $.ajax({
            url: "modal/finaldispatch_modal.php?action=GetActiveFinalDispatchDates",
            type: "POST",
            data: { projectid: projectid },
            dataType: "json",
            success: function(dates) {
                if (!Array.isArray(dates)) dates = [];

                let weekMap = {};
                dates.forEach(item => {
                    if (!item.dispatch_date) return;
                    let d = new Date(item.dispatch_date);
                    if (isNaN(d.getTime())) return;
                    let day = d.getDay();
                    let diffToMonday = (day === 0) ? -6 : (1 - day);
                    let monday = new Date(d);
                    monday.setDate(d.getDate() + diffToMonday);
                    monday.setHours(0, 0, 0, 0);

                    let sunday = new Date(monday);
                    sunday.setDate(monday.getDate() + 6);
                    sunday.setHours(23, 59, 59, 999);

                    let ws = monday.toISOString().split("T")[0];
                    let we = sunday.toISOString().split("T")[0];
                    weekMap[ws] = we;
                });

                let today = new Date();
                let curYearStr = today.getFullYear().toString();
                let curMonthStr = today.toISOString().substring(0, 7);

                let tDay = today.getDay();
                let tDiff = (tDay === 0) ? -6 : (1 - tDay);
                let tMon = new Date(today);
                tMon.setDate(today.getDate() + tDiff);
                let tSun = new Date(tMon);
                tSun.setDate(tMon.getDate() + 6);
                let tWS = tMon.toISOString().split("T")[0];
                let tWE = tSun.toISOString().split("T")[0];
                let currentWeekRangeStr = `${tWS} to ${tWE}`;

                if (!weekMap[tWS]) {
                    weekMap[tWS] = tWE;
                }

                let sortedWeeks = Object.keys(weekMap).sort();
                globalWeekMap = weekMap;
                globalSortedWeeks = sortedWeeks;

                // 1. Extract Unique Years
                let years = new Set();
                sortedWeeks.forEach(ws => years.add(ws.substring(0, 4)));
                years.add(curYearStr);

                $("#dispatchYearFilter").html("");
                Array.from(years).sort().forEach(y => {
                    $("#dispatchYearFilter").append(`<option value="${y}">${y}</option>`);
                });
                $("#dispatchYearFilter").val(curYearStr);

                // 2. Extract Unique Months
                let months = new Set();
                sortedWeeks.forEach(ws => months.add(ws.substring(0, 7)));
                months.add(curMonthStr);
                globalMonths = Array.from(months).sort();

                populateMonths(curYearStr, curMonthStr, currentWeekRangeStr);

                // Year change
                $("#dispatchYearFilter").off("change").on("change", function() {
                    let selYear = $(this).val();
                    let todayM = new Date().toISOString().substring(0, 7);
                    let defMonth = (selYear === curYearStr) ? todayM : "first";
                    populateMonths(selYear, defMonth, "all_week");
                });

                // Month change
                $("#dispatchMonthFilter").off("change").on("change", function() {
                    populateWeeks(globalWeekMap, globalSortedWeeks, "all_week");
                });
            }
        });
    }

    function populateMonths(selectedYear, defaultMonth, defaultWeek) {
        let monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
        let filteredMonths = globalMonths.filter(m => m.startsWith(selectedYear));

        if (filteredMonths.length === 0) {
            for (let i = 1; i <= 12; i++) {
                let miStr = (i < 10 ? '0' + i : '' + i);
                filteredMonths.push(`${selectedYear}-${miStr}`);
            }
        }

        $("#dispatchMonthFilter").html("");
        $("#dispatchMonthFilter").append(`<option value="all">All Month</option>`);
        filteredMonths.forEach(m => {
            let [y, mi] = m.split("-");
            let mName = monthNames[parseInt(mi) - 1];
            let display = `${mName}-${y}`;
            $("#dispatchMonthFilter").append(`<option value="${m}">${display}</option>`);
        });

        let selMonth = defaultMonth;
        if (selMonth === "all") {
            // Keep "all"
        } else if (selMonth === "first" && filteredMonths.length > 0) {
            selMonth = filteredMonths[0];
        } else if (selMonth === "last" && filteredMonths.length > 0) {
            selMonth = filteredMonths[filteredMonths.length - 1];
        } else if ($("#dispatchMonthFilter").find(`option[value="${selMonth}"]`).length === 0) {
            selMonth = filteredMonths[0];
        }

        $("#dispatchMonthFilter").val(selMonth);
        populateWeeks(globalWeekMap, globalSortedWeeks, defaultWeek);
    }

    function populateWeeks(weekMap, sortedWeeks, defaultWeek, skipLoadSchedule = false) {
        let selectedYear = $("#dispatchYearFilter").val() || (new Date().getFullYear().toString());
        let selectedMonth = $("#dispatchMonthFilter").val();
        let displayWeeks = sortedWeeks.filter(ws => {
            if (selectedMonth === "all") {
                return selectedYear ? ws.startsWith(selectedYear) : true;
            }
            return ws.substring(0, 7) === selectedMonth;
        });

        // Calculate All Week range (1st day of selected month/year to last day)
        let allWeeksRange = "";
        let allWeeksLabel = "All Week";
        if (selectedMonth && selectedMonth !== "all") {
            let parts = selectedMonth.split("-").map(Number);
            let yr = parts[0];
            let mo = parts[1];
            let firstDay = `${yr}-${String(mo).padStart(2, '0')}-01`;
            let lastDateObj = new Date(yr, mo, 0);
            let lastDay = `${yr}-${String(mo).padStart(2, '0')}-${String(lastDateObj.getDate()).padStart(2, '0')}`;
            allWeeksRange = `${firstDay} to ${lastDay}`;
            allWeeksLabel = `All Week (${firstDay} to ${lastDay})`;
        } else if (selectedMonth === "all") {
            let yr = selectedYear;
            let firstDay = `${yr}-01-01`;
            let lastDay = `${yr}-12-31`;
            allWeeksRange = `${firstDay} to ${lastDay}`;
            allWeeksLabel = `All Week of All Month (${firstDay} to ${lastDay})`;
        }

        let $week = $("#dispatchWeekSelector");
        $week.html("");

        if (allWeeksRange) {
            $week.append(`<option value="${allWeeksRange}">${allWeeksLabel}</option>`);
        }

        displayWeeks.forEach(ws => {
            let range = `${ws} to ${weekMap[ws]}`;
            $week.append(`<option value="${range}">${range}</option>`);
        });

        let selectedWeek = defaultWeek;
        if (selectedWeek === "all_week" || selectedWeek === "all") {
            if (allWeeksRange) {
                selectedWeek = allWeeksRange;
            } else if (displayWeeks.length > 0) {
                selectedWeek = `${displayWeeks[0]} to ${weekMap[displayWeeks[0]]}`;
            }
        } else if (selectedWeek === "current" || selectedWeek === "first" || selectedWeek === "last") {
            let today = new Date();
            let tDay = today.getDay();
            let tDiff = (tDay === 0) ? -6 : (1 - tDay);
            let tMon = new Date(today);
            tMon.setDate(today.getDate() + tDiff);
            let tSun = new Date(tMon);
            tSun.setDate(tMon.getDate() + 6);
            let tWS = tMon.toISOString().split("T")[0];
            let tWE = tSun.toISOString().split("T")[0];
            let currentWeekRange = `${tWS} to ${tWE}`;

            if (selectedWeek === "current") {
                selectedWeek = currentWeekRange;
            } else if (selectedWeek === "first" && displayWeeks.length > 0) {
                selectedWeek = `${displayWeeks[0]} to ${weekMap[displayWeeks[0]]}`;
            } else if (selectedWeek === "last" && displayWeeks.length > 0) {
                let lastIdx = displayWeeks.length - 1;
                selectedWeek = `${displayWeeks[lastIdx]} to ${weekMap[displayWeeks[lastIdx]]}`;
            }
        }

        if ($week.find(`option[value="${selectedWeek}"]`).length === 0) {
            if (allWeeksRange) {
                selectedWeek = allWeeksRange;
            } else if (displayWeeks.length > 0) {
                selectedWeek = `${displayWeeks[0]} to ${weekMap[displayWeeks[0]]}`;
            } else {
                let today = new Date();
                let tDay = today.getDay();
                let tDiff = (tDay === 0) ? -6 : (1 - tDay);
                let tMon = new Date(today);
                tMon.setDate(today.getDate() + tDiff);
                let tSun = new Date(tMon);
                tSun.setDate(tMon.getDate() + 6);
                let tWS = tMon.toISOString().split("T")[0];
                let tWE = tSun.toISOString().split("T")[0];
                let currentWeekRange = `${tWS} to ${tWE}`;

                $week.append(`<option value="${currentWeekRange}">${currentWeekRange}</option>`);
                selectedWeek = currentWeekRange;
            }
        }

        $week.val(selectedWeek);
        if (!skipLoadSchedule) {
            loadFinalDispatchSchedule(selectedWeek);
        }
    }

    // Previous Week Navigation
    $("#dispatchPrevWeekBtn").off("click").on("click", function() {
        let select = document.getElementById("dispatchWeekSelector");
        if (select && select.selectedIndex > 0) {
            select.selectedIndex = select.selectedIndex - 1;
            $(select).trigger("change");
        } else {
            let monthSelect = document.getElementById("dispatchMonthFilter");
            if (monthSelect && monthSelect.selectedIndex > 0) {
                monthSelect.selectedIndex = monthSelect.selectedIndex - 1;
                populateWeeks(globalWeekMap, globalSortedWeeks, "last");
            } else {
                let yearSelect = document.getElementById("dispatchYearFilter");
                if (yearSelect && yearSelect.selectedIndex > 0) {
                    yearSelect.selectedIndex = yearSelect.selectedIndex - 1;
                    populateMonths($(yearSelect).val(), "last", "last");
                }
            }
        }
    });

    // Next Week Navigation
    $("#dispatchNextWeekBtn").off("click").on("click", function() {
        let select = document.getElementById("dispatchWeekSelector");
        if (select && select.selectedIndex < select.options.length - 1) {
            select.selectedIndex = select.selectedIndex + 1;
            $(select).trigger("change");
        } else {
            let monthSelect = document.getElementById("dispatchMonthFilter");
            if (monthSelect && monthSelect.selectedIndex < monthSelect.options.length - 1) {
                monthSelect.selectedIndex = monthSelect.selectedIndex + 1;
                populateWeeks(globalWeekMap, globalSortedWeeks, "first");
            } else {
                let yearSelect = document.getElementById("dispatchYearFilter");
                if (yearSelect && yearSelect.selectedIndex < yearSelect.options.length - 1) {
                    yearSelect.selectedIndex = yearSelect.selectedIndex + 1;
                    populateMonths($(yearSelect).val(), "first", "first");
                }
            }
        }
    });

    $("#dispatchWeekSelector").off("change").on("change", function() {
        loadFinalDispatchSchedule($(this).val());
    });

    let isUpdatingFilters = false;

    function formatProductNameDisplay(rawName) {
        if (!rawName) return '';
        return rawName.replace(/^(NonGrafted|Non-Grafted)\s*[-:]?\s*/i, '');
    }

    function isProductGrafted(rawName) {
        if (!rawName) return false;
        return (/grafted/i.test(rawName) && !/nongrafted|non-grafted/i.test(rawName));
    }

    // Format Delivery / Transport Location details based on pickuptype rules:
    // 1. pickup: Self Pickup By Farmer
    // 2. delivered: Delivered by Farm + totaldistance, freedistance, distance, freightamt, remark
    // 3. boxpacking: Box Packing + dest_state, dest_district, dest_city, dest_pincode, boxperpack, chargesperbox, cityid, deliveryaddress, remark
    function formatDeliveryLocationDisplay(row) {
        if (!row) return '';
        let rawType = (row.dl_pickuptype || '').trim();
        let normType = rawType.toLowerCase().replace(/[-_ ]/g, '');

        // 1. Pickup: Self Pickup By Farmer
        if (normType === 'pickup' || normType === 'pickbyclient' || normType === 'selfpickup') {
            let pickupPointHtml = row.dl_pickuppoint ? `<div class="text-muted" style="font-size: 11px;"><i class="fa fa-map-pin text-secondary me-1"></i><b>Pickup Point:</b> ${row.dl_pickuppoint}</div>` : '';
            return `
                <div class="mt-1">
                    <span class="badge bg-secondary text-white" style="font-size: 11px;"><i class="fa fa-user-check me-1"></i>Self Pickup By Farmer</span>
                    ${pickupPointHtml}
                </div>
            `;
        }

        // 2. Delivered: Delivered by Farm as per mentioned address
        // Show totaldistance, freedistance, distance, freightamt, remark
        if (normType === 'delivered' || normType === 'companytransport' || normType === 'deliver') {
            let addr = row.dl_deliveryaddress || row.delivery_address || '';
            let totalDist = parseFloat(row.dl_totaldistance || 0);
            let freeDist = parseFloat(row.dl_freedistance || 0);
            let netDist = parseFloat(row.dl_distance || 0);
            let freightAmt = parseFloat(row.dl_freightamt || 0);
            let remark = row.dl_remark || '';

            return `
                <div class="mt-1 pt-1 border-top">
                    <div class="d-flex align-items-center gap-1 mb-1">
                        <span class="badge bg-info text-dark" style="font-size: 11px;"><i class="fa fa-truck me-1"></i>Delivered by Farm</span>
                    </div>
                    ${addr ? `<div class="text-muted" style="font-size: 11px;"><i class="fa fa-map-marker-alt text-danger me-1"></i>${addr}</div>` : ''}
                    <div class="text-secondary small" style="font-size: 11px;">
                        <b>Total Dist:</b> ${totalDist} km | <b>Free:</b> ${freeDist} km | <b>Distance:</b> ${netDist} km | <b>Freight:</b> ₹${freightAmt.toLocaleString()}
                    </div>
                    ${remark ? `<div class="text-muted fst-italic" style="font-size: 10.5px;"><i class="fa fa-comment-dots text-primary me-1"></i><b>Remark:</b> ${remark}</div>` : ''}
                </div>
            `;
        }

        // 3. Box Packing: dest_state, dest_district, dest_city, dest_pincode, boxperpack, chargesperbox, cityid, deliveryaddress, remark
        if (normType === 'boxpacking' || normType === 'box') {
            let addr = row.dl_deliveryaddress || row.delivery_address || '';
            let destCity = row.dl_dest_city || row.dl_cityid || '';
            let destArr = [destCity, row.dl_dest_district, row.dl_dest_state, row.dl_dest_pincode ? `PIN: ${row.dl_dest_pincode}` : ''].filter(Boolean);
            let destStr = destArr.join(', ');
            let boxPerPack = row.dl_boxperpack ? parseInt(row.dl_boxperpack) : '-';
            let chargesPerBox = row.dl_chargesperbox ? parseFloat(row.dl_chargesperbox) : 0;
            let remark = row.dl_remark || '';

            return `
                <div class="mt-1 pt-1 border-top">
                    <div class="d-flex align-items-center gap-1 mb-1">
                        <span class="badge bg-warning text-dark" style="font-size: 11px;"><i class="fa fa-box me-1"></i>Box Packing</span>
                    </div>
                    ${destStr ? `<div class="text-secondary small" style="font-size: 11px;"><i class="fa fa-map-marker-alt text-danger me-1"></i><b>Dest:</b> ${destStr}</div>` : ''}
                    ${addr ? `<div class="text-muted" style="font-size: 11px;"><i class="fa fa-location-arrow text-secondary me-1"></i>${addr}</div>` : ''}
                    <div class="text-secondary small" style="font-size: 11px;">
                        <b>Box/Pack:</b> ${boxPerPack} | <b>Charges/Box:</b> ₹${chargesPerBox.toLocaleString()}
                        ${row.dl_noofbox ? ` | <b>Boxes:</b> ${row.dl_noofbox}` : ''}
                        ${row.dl_totalamount ? ` | <b>Total:</b> ₹${parseFloat(row.dl_totalamount).toLocaleString()}` : ''}
                    </div>
                    ${remark ? `<div class="text-muted fst-italic" style="font-size: 10.5px;"><i class="fa fa-comment-dots text-warning me-1"></i><b>Remark:</b> ${remark}</div>` : ''}
                </div>
            `;
        }

        // 4. Default / Fallback: delivery address from sales order if available
        if (row.delivery_address) {
            return `<div class="text-muted" style="font-size: 11px;"><i class="fa fa-map-marker-alt text-danger me-1"></i>${row.delivery_address}</div>`;
        }
        return '';
    }

    // Update active visual styles (light background & highlighted border) on filter controls
    function updateFilterActiveVisuals() {
        const filters = [
            '#tableDeliveryDateFilter',
            '#tableClientFilter',
            '#tableGraftedTypeFilter',
            '#tableProductFilter',
            '#tablePolyhouseFilter',
            '#tableStatusFilter'
        ];

        filters.forEach(selId => {
            let $el = $(selId);
            let val = $el.val();
            let isActive = val && val !== 'all' && val !== '';
            let $select2Container = $el.next('.select2-container');
            let $selection = $select2Container.find('.select2-selection');

            if (isActive) {
                $el.addClass('filter-applied');
                $select2Container.addClass('filter-applied');
                $selection.addClass('filter-applied');
            } else {
                $el.removeClass('filter-applied');
                $select2Container.removeClass('filter-applied');
                $selection.removeClass('filter-applied');
            }
        });

        // Search Input
        let searchVal = ($('#tableSearchInput').val() || '').trim();
        let $searchGroup = $('#tableSearchInput').closest('.input-group');
        if (searchVal !== '') {
            $searchGroup.addClass('filter-applied');
        } else {
            $searchGroup.removeClass('filter-applied');
        }
    }

    // Initialize Select2 on dropdowns
    function initSelect2Filters() {
        $(".select2-filter").each(function() {
            if ($(this).hasClass("select2-hidden-accessible")) {
                $(this).select2("destroy");
            }
            $(this).select2({
                theme: "bootstrap-5",
                width: "100%",
                placeholder: $(this).find("option:first").text()
            });
        });
        updateFilterActiveVisuals();
    }

    function getFilteredDataset(excludeFilterType = null) {
        let list = currentRawData;

        // 1. Status / KPI Card
        if (excludeFilterType !== 'status') {
            let statusVal = $("#tableStatusFilter").val();
            let effectiveStatus = (currentFilter && currentFilter !== "oldpending") ? currentFilter : (statusVal || "all");
            if (effectiveStatus !== "all" && effectiveStatus !== "oldpending") {
                list = list.filter(r => {
                    let isRowCancelled = Boolean(
                        r.is_cancelled == 1 ||
                        r.dispatch_status_code === 'cancelled' ||
                        (r.item_status && r.item_status.toLowerCase() === 'cancelled') ||
                        (r.soi_status && r.soi_status.toLowerCase() === 'cancelled') ||
                        (r.sales_order_status && r.sales_order_status.toLowerCase() === 'cancelled') ||
                        (r.pso_status && r.pso_status.toLowerCase() === 'cancelled')
                    );

                    if (effectiveStatus === 'cancelled') {
                        return isRowCancelled;
                    }
                    if (isRowCancelled) {
                        return false;
                    }

                    let isPartialPending = Boolean(r.is_partial_pending == 1);
                    let isPhysDispatched = Boolean(r.is_physically_dispatched || r.dispatch_status_code === 'dispatched');
                    let allocQty = parseInt(r.allocated_qty || r.allocate_ordqty || r.reserved_qty || 0);
                    let invDispatched = parseInt(r.dispatched_plant_qty || 0);
                    let hasPreparedInvoice = Boolean(r.has_prepared_invoice || (allocQty > 0 && r.dinvoiceno && r.remaining_uninvoiced_alloc_qty <= 0) || r.dispatch_status_code === 'readytoload');

                    if (effectiveStatus === 'dispatched') {
                        return (r.dispatch_status_code === 'dispatched' || (isPhysDispatched && !isPartialPending));
                    }
                    if (isPhysDispatched && !isPartialPending && r.dispatch_status_code === 'dispatched') {
                        return false;
                    }

                    if (effectiveStatus === 'allocated') {
                        if (r.dispatch_status_code === 'readytoload' || r.dispatch_status_code === 'dispatched') {
                            return false;
                        }
                        // Order item is partially or fully allocated (allocQty > 0 or status is allocated) AND invoice is NOT prepared yet for this allocation:
                        return (r.dispatch_status_code === 'allocated' || (allocQty > 0 && r.dispatch_status_code !== 'readytoload'));
                    }

                    if (effectiveStatus === 'readytoload') {
                        if (r.dispatch_status_code === 'dispatched' || r.dispatch_status_code === 'allocated') {
                            return false;
                        }
                        // Shows in Ready to Loading if status is readytoload
                        return (r.dispatch_status_code === 'readytoload');
                    }

                    if (effectiveStatus === 'pending') {
                        // User Rule: If order is partially allocated or full allocated (allocQty > 0),
                        // this order MUST SHOW IN ALLOCATED (FOR PREPARED INVOICE) TAB, NOT IN PENDING ALLOCATION!
                        if (r.dispatch_status_code === 'allocated' || r.dispatch_status_code === 'readytoload' || r.dispatch_status_code === 'dispatched' || allocQty > 0) {
                            return false;
                        }
                        return (r.dispatch_status_code === 'pending' || isPartialPending || (allocQty === 0 && !isPhysDispatched));
                    }

                    return r.dispatch_status_code === effectiveStatus;
                });
            }
        }

        // 2. Delivery Date filter
        if (excludeFilterType !== 'delivery_date') {
            let selDate = $("#tableDeliveryDateFilter").val();
            if (selDate && selDate !== "all") {
                list = list.filter(r => r.delivery_date === selDate || r.formatted_delivery_date === selDate);
            }
        }

        // 3. Client Name / SO filter
        if (excludeFilterType !== 'client') {
            let selClient = $("#tableClientFilter").val();
            if (selClient && selClient !== "all") {
                let target = selClient.trim().toLowerCase();
                list = list.filter(r => {
                    let clientName = (r.clientname || '').trim().toLowerCase();
                    let sono = (r.sono || '').trim().toLowerCase();
                    let psono = (r.psono || '').trim().toLowerCase();
                    return clientName === target || clientName.includes(target) || sono.includes(target) || psono.includes(target);
                });
            }
        }

        // 4. Type (Grafted / Non-Grafted) filter
        if (excludeFilterType !== 'grafted_type') {
            let selType = $("#tableGraftedTypeFilter").val();
            if (selType && selType !== "all") {
                if (selType === "grafted") {
                    list = list.filter(r => isProductGrafted(r.allocated_product_name || r.product_name));
                } else if (selType === "nongrafted") {
                    list = list.filter(r => !isProductGrafted(r.allocated_product_name || r.product_name));
                }
            }
        }

        // 5. Product filter
        if (excludeFilterType !== 'product') {
            let selProduct = $("#tableProductFilter").val();
            if (selProduct && selProduct !== "all") {
                let target = selProduct.trim().toLowerCase();
                list = list.filter(r => {
                    let effP = (r.allocated_product_name || r.product_name || '').trim().toLowerCase();
                    let bookedP = (r.product_name || '').trim().toLowerCase();
                    return effP === target || bookedP === target;
                });
            }
        }

        // 6. Polyhouse filter
        if (excludeFilterType !== 'polyhouse') {
            let selPolyhouse = $("#tablePolyhouseFilter").val();
            if (selPolyhouse && selPolyhouse !== "all") {
                list = list.filter(r => (r.polyhouse_names || '').includes(selPolyhouse));
            }
        }

        // 7. Global Search keyword
        if (excludeFilterType !== 'search') {
            let searchKeyword = ($("#tableSearchInput").val() || "").trim().toLowerCase();
            if (searchKeyword !== "") {
                list = list.filter(r => {
                    let cleanProd = formatProductNameDisplay(r.product_name || '');
                    let cleanAllocProd = formatProductNameDisplay(r.allocated_product_name || '');
                    let dlText = `${r.dl_pickuptype || ''} ${r.dl_deliveryaddress || ''} ${r.dl_dest_state || ''} ${r.dl_dest_district || ''} ${r.dl_dest_city || ''} ${r.dl_dest_pincode || ''} ${r.dl_remark || ''} ${r.dl_pickuppoint || ''}`;
                    let haystack = `${r.clientname || ''} ${r.sono || ''} ${r.psono || ''} ${r.product_name || ''} ${r.allocated_product_name || ''} ${cleanProd} ${cleanAllocProd} ${r.effective_stockorderno || ''} ${r.polyhouse_names || ''} ${r.bed_numbers || ''} ${r.mobileno || ''} ${r.districtname || ''} ${r.statename || ''} ${r.delivery_address || ''} ${dlText} ${r.dispatch_status_label || ''}`.toLowerCase();
                    return haystack.includes(searchKeyword);
                });
            }
        }

        return list;
    }

    function populateDynamicFilters() {
        isUpdatingFilters = true;

        let curDate = $("#tableDeliveryDateFilter").val() || "all";
        let curClient = $("#tableClientFilter").val() || "all";
        let curType = $("#tableGraftedTypeFilter").val() || "all";
        let curProduct = $("#tableProductFilter").val() || "all";
        let curPolyhouse = $("#tablePolyhouseFilter").val() || "all";

        // 1. Delivery Dates dropdown (calculated from dataset matching other filters)
        let dateData = getFilteredDataset('delivery_date');
        let uniqueDates = {};
        dateData.forEach(item => {
            if (item.delivery_date && item.formatted_delivery_date) {
                uniqueDates[item.delivery_date] = item.formatted_delivery_date;
            }
        });
        let sortedDateKeys = Object.keys(uniqueDates).sort();
        let dateHtml = `<option value="all">All Dates (${sortedDateKeys.length})</option>`;
        sortedDateKeys.forEach(dk => {
            let isSelected = (curDate === dk) ? 'selected' : '';
            dateHtml += `<option value="${dk}" ${isSelected}>${uniqueDates[dk]}</option>`;
        });
        $("#tableDeliveryDateFilter").html(dateHtml);
        if (!uniqueDates[curDate] && curDate !== "all") {
            $("#tableDeliveryDateFilter").val("all");
        }

        // 2. Client / Sales Order dropdown (calculated from dataset matching other filters)
        let clientData = getFilteredDataset('client');
        let clientMap = {};
        clientData.forEach(item => {
            let clientName = (item.clientname || '').trim();
            let sono = (item.sono || '').trim();
            if (clientName !== '') {
                let key = clientName;
                if (!clientMap[key]) {
                    clientMap[key] = { name: clientName, sonos: new Set() };
                }
                if (sono) clientMap[key].sonos.add(sono);
            }
        });
        let clientHtml = `<option value="all">All Clients & Orders (${Object.keys(clientMap).length})</option>`;
        Object.keys(clientMap).sort().forEach(cKey => {
            let cObj = clientMap[cKey];
            let sonoList = Array.from(cObj.sonos).join(', ');
            let displayText = sonoList ? `${cObj.name} (SO: ${sonoList})` : cObj.name;
            let isSelected = (curClient === cKey) ? 'selected' : '';
            clientHtml += `<option value="${cKey}" ${isSelected}>${displayText}</option>`;
        });
        $("#tableClientFilter").html(clientHtml);
        if (!clientMap[curClient] && curClient !== "all") {
            $("#tableClientFilter").val("all");
        }

        // 3. Type (Grafted / Non-Grafted) dropdown
        let typeData = getFilteredDataset('grafted_type');
        let graftedCount = 0;
        let nonGraftedCount = 0;
        typeData.forEach(item => {
            if (isProductGrafted(item.product_name)) {
                graftedCount++;
            } else {
                nonGraftedCount++;
            }
        });
        let typeHtml = `
            <option value="all" ${curType === 'all' ? 'selected' : ''}>All Types (${typeData.length})</option>
            <option value="grafted" ${curType === 'grafted' ? 'selected' : ''}>Grafted (${graftedCount})</option>
            <option value="nongrafted" ${curType === 'nongrafted' ? 'selected' : ''}>Non-Grafted (${nonGraftedCount})</option>
        `;
        $("#tableGraftedTypeFilter").html(typeHtml);
        if (curType !== "all" && curType !== "grafted" && curType !== "nongrafted") {
            $("#tableGraftedTypeFilter").val("all");
        }

        // 4. Product dropdown (Format display to strip 'NonGrafted-' prefix while preserving raw value for matching)
        let productData = getFilteredDataset('product');
        let productMap = {};
        productData.forEach(item => {
            let pName = (item.product_name || '').trim();
            let pId = item.product_id;
            if (pName !== '') {
                productMap[pName] = pId;
            }
        });
        let productHtml = `<option value="all">All Products (${Object.keys(productMap).length})</option>`;
        Object.keys(productMap).sort().forEach(pName => {
            let isSelected = (curProduct === pName) ? 'selected' : '';
            let formattedName = formatProductNameDisplay(pName);
            productHtml += `<option value="${pName}" ${isSelected}>${formattedName}</option>`;
        });
        $("#tableProductFilter").html(productHtml);
        if (!productMap[curProduct] && curProduct !== "all") {
            $("#tableProductFilter").val("all");
        }

        // 5. Polyhouse dropdown (calculated from dataset matching other filters)
        let polyhouseData = getFilteredDataset('polyhouse');
        let polyhouseSet = new Set();
        polyhouseData.forEach(item => {
            let pNames = item.polyhouse_names || '';
            if (pNames && pNames !== 'N/A') {
                pNames.split(',').forEach(ph => {
                    let cleanPh = ph.trim();
                    if (cleanPh && cleanPh !== 'N/A') {
                        polyhouseSet.add(cleanPh);
                    }
                });
            }
        });
        let polyhouseHtml = `<option value="all">All Polyhouses (${polyhouseSet.size})</option>`;
        Array.from(polyhouseSet).sort().forEach(ph => {
            let isSelected = (curPolyhouse === ph) ? 'selected' : '';
            polyhouseHtml += `<option value="${ph}" ${isSelected}>${ph}</option>`;
        });
        $("#tablePolyhouseFilter").html(polyhouseHtml);
        if (!polyhouseSet.has(curPolyhouse) && curPolyhouse !== "all") {
            $("#tablePolyhouseFilter").val("all");
        }

        initSelect2Filters();
        isUpdatingFilters = false;
    }

    function loadFinalDispatchSchedule(weekRange, targetFilter = null) {
        let projectid = $("body").data("projectid") || 17;
        let [datefrom, dateto] = (weekRange || "").split(" to ");

        let today = new Date();
        let todayStr = today.toISOString().split("T")[0];
        let isCurrentWeek = (todayStr >= datefrom && todayStr <= dateto) ? 1 : 0;

        let tbody = $("#finalDispatchTableBody");
        tbody.html(`<tr><td colspan="6" class="text-center py-4 text-muted"><i class="fa fa-spinner fa-spin me-2"></i>Loading Schedule for ${weekRange}...</td></tr>`);

        $.ajax({
            url: "modal/finaldispatch_modal.php?action=GetFinalDispatchScheduleData",
            type: "POST",
            data: {
                projectid: projectid,
                datefrom: datefrom,
                dateto: dateto,
                is_current_week: isCurrentWeek
            },
            dataType: "json",
            success: function(res) {
                currentRawData = res.data ? res.data : (Array.isArray(res) ? res : []);
                currentRawDataIsOldPending = false;
                currentWeekRange = weekRange;
                if (weekRange && !weekRange.includes("-01-01 to ")) {
                    lastRegularWeekRange = weekRange;
                }
                let oldPendingCount = res.old_pending_count !== undefined ? res.old_pending_count : 0;
                let oldPendingQty = res.old_pending_qty !== undefined ? res.old_pending_qty : 0;
                
                let stats = updateStatusCards(currentRawData, oldPendingCount, oldPendingQty);

                // Determine active status tab: preserve currentFilter if set (and not oldpending), or use targetFilter, or fallback
                let activeTab = targetFilter || (currentFilter && currentFilter !== "oldpending" ? currentFilter : null);
                if (activeTab && activeTab !== "all") {
                    currentFilter = activeTab;
                    $(".status-filter-card").removeClass("active");
                    $(`.status-filter-card.card-${activeTab}`).addClass("active");
                    isUpdatingFilters = true;
                    $("#tableStatusFilter").val(activeTab);
                    isUpdatingFilters = false;
                } else if (stats && stats.pendingOrders > 0 && !targetFilter) {
                    currentFilter = "pending";
                    $(".status-filter-card").removeClass("active");
                    $(".status-filter-card.card-pending").addClass("active");
                    isUpdatingFilters = true;
                    $("#tableStatusFilter").val("pending");
                    isUpdatingFilters = false;
                } else {
                    currentFilter = "all";
                    $(".status-filter-card").removeClass("active");
                    $(".status-filter-card.card-total").addClass("active");
                    isUpdatingFilters = true;
                    $("#tableStatusFilter").val("all");
                    isUpdatingFilters = false;
                }

                applyFilterAndRender(true);
            },
            error: function() {
                tbody.html(`<tr><td colspan="6" class="text-center py-4 text-danger"><i class="fa fa-exclamation-triangle me-2"></i>Failed to load schedule.</td></tr>`);
            }
        });
    }

    function updateStatusCards(data, oldPendingCount, oldPendingQty) {
        let totalItems = data.length;
        let pendingCount = 0;
        let pendingQty = 0;
        let allocatedCount = 0;
        let allocatedQty = 0;
        let readyToLoadCount = 0;
        let readyToLoadQty = 0;
        let readyToDispatchCount = 0;
        let readyToDispatchQty = 0;
        let dispatchedCount = 0;
        let dispatchedQty = 0;

        data.forEach(item => {
            let isItemCancelled = Boolean(
                item.is_cancelled == 1 ||
                item.dispatch_status_code === 'cancelled' ||
                (item.item_status && item.item_status.toLowerCase() === 'cancelled') ||
                (item.soi_status && item.soi_status.toLowerCase() === 'cancelled') ||
                (item.sales_order_status && item.sales_order_status.toLowerCase() === 'cancelled') ||
                (item.pso_status && item.pso_status.toLowerCase() === 'cancelled')
            );
            if (isItemCancelled) {
                // Cancelled orders should not appear in active pending, allocated, loading, or dispatched cards
                return;
            }

            let isPartialPending = Boolean(item.is_partial_pending == 1);
            let orderQty = parseInt(item.order_qty || 0);
            let allocQty = parseInt(item.allocated_qty || item.allocate_ordqty || item.reserved_qty || 0);
            let invDispatched = parseInt(item.dispatched_plant_qty || 0);
            let hasPreparedInvoice = Boolean(item.has_prepared_invoice || (allocQty > 0 && item.dinvoiceno && item.remaining_uninvoiced_alloc_qty <= 0) || item.dispatch_status_code === 'readytoload');
            let isPhysDispatched = Boolean(item.is_physically_dispatched || item.dispatch_status_code === 'dispatched');
            let status = item.dispatch_status_code;

            if (status === 'dispatched' || (isPhysDispatched && !isPartialPending)) {
                dispatchedCount++;
                dispatchedQty += (invDispatched > 0 ? invDispatched : orderQty);
            } else if (status === 'readytoload') {
                // 2. Ready to Loading Card (Invoice is prepared and ready for loading):
                readyToLoadCount++;
                readyToLoadQty += (allocQty > 0 ? allocQty : (invDispatched > 0 ? invDispatched : orderQty));
            } else if (status === 'allocated' || (!isPartialPending && allocQty > 0 && status !== 'readytoload')) {
                // 1. Allocated (For Prepared Invoice) Card (Allocated but invoice not yet prepared):
                allocatedCount++;
                allocatedQty += (allocQty > 0 ? allocQty : orderQty);
            } else if (status === 'pending' || isPartialPending || allocQty === 0) {
                // Pending Allocation Card:
                pendingCount++;
                pendingQty += orderQty;
            }
        });

        let totalOrdersCount = totalItems;
        let totalQty = pendingQty + allocatedQty + readyToLoadQty + dispatchedQty;

        // Card 1: Pending Allocation
        $("#cardPendingOrders").text(pendingCount);
        $("#cardPendingQty").text(pendingCount > 0 ? `${pendingQty.toLocaleString()} Plants Qty` : 'Awaiting Stock Order');

        // Card 2: Allocated (For Prepared Invoice)
        $("#cardAllocatedOrders").text(allocatedCount);
        $("#cardAllocatedQty").text(`${allocatedQty.toLocaleString()} Allocated`);

        // Card 3: Ready to Loading
        $("#cardReadyToLoadOrders").text(readyToLoadCount);
        $("#cardReadyToLoadQty").text(`${readyToLoadQty.toLocaleString()} Ready to Load`);

        // Card 5: Final Dispatched
        $("#cardDispatchedOrders").text(dispatchedCount);
        $("#cardDispatchedQty").text(`${dispatchedQty.toLocaleString()} Completed`);

        // Card 6: Total Orders
        $("#cardTotalOrders").text(totalOrdersCount);
        $("#cardTotalQty").text(`${totalQty.toLocaleString()} Plants Qty`);

        // Card 7: Old Pending
        $("#cardOldPendingOrders").text(oldPendingCount || 0);
        $("#cardOldPendingQty").text(`${(oldPendingQty || 0).toLocaleString()} Plants`);
        $("#totalRowCountDisplay").text(totalOrdersCount);

        return {
            totalOrders: totalOrdersCount,
            totalQty: totalQty,
            pendingOrders: pendingCount,
            pendingQty: pendingQty,
            allocatedOrders: allocatedCount,
            allocatedQty: allocatedQty,
            readyToLoadOrders: readyToLoadCount,
            readyToLoadQty: readyToLoadQty,
            readyToDispatchOrders: readyToDispatchCount,
            readyToDispatchQty: readyToDispatchQty,
            dispatchedOrders: dispatchedCount,
            dispatchedQty: dispatchedQty,
            oldPendingCount: oldPendingCount,
            oldPendingQty: oldPendingQty
        };
    }

    function loadOldPendingSchedule() {
        let projectid = $("body").data("projectid") || 17;

        // 1. Auto select All Month in #dispatchMonthFilter
        if ($("#dispatchMonthFilter option[value='all']").length === 0) {
            $("#dispatchMonthFilter").prepend(`<option value="all">All Month</option>`);
        }
        $("#dispatchMonthFilter").val("all");

        // 2. Auto select All Week of All Month in #dispatchWeekSelector (skip regular schedule load)
        populateWeeks(globalWeekMap, globalSortedWeeks, "all", true);

        // 3. Determine cutoff reference date for Old Pending (start of current week, e.g. 2026-10-12)
        let refDateFrom = "";
        let weekToCheck = lastRegularWeekRange || currentWeekRange || "";
        if (weekToCheck && weekToCheck.includes(" to ") && !weekToCheck.includes("-01-01 to ")) {
            let parts = weekToCheck.split(" to ");
            if (parts[0] && parts[0].length === 10) {
                refDateFrom = parts[0];
            }
        }
        if (!refDateFrom) {
            let today = new Date();
            let tDay = today.getDay();
            let tDiff = (tDay === 0) ? -6 : (1 - tDay);
            let tMon = new Date(today);
            tMon.setDate(today.getDate() + tDiff);
            refDateFrom = tMon.toISOString().split("T")[0];
        }

        let tbody = $("#finalDispatchTableBody");
        tbody.html(`<tr><td colspan="6" class="text-center py-4 text-muted"><i class="fa fa-spinner fa-spin me-2"></i>Loading Old Pending Allocation Orders...</td></tr>`);

        $.ajax({
            url: "modal/finaldispatch_modal.php?action=GetFinalDispatchScheduleData",
            type: "POST",
            data: {
                projectid: projectid,
                datefrom: refDateFrom,
                is_old_pending: 1
            },
            dataType: "json",
            success: function(res) {
                let oldData = res.data ? res.data : (Array.isArray(res) ? res : []);
                currentRawData = oldData;
                currentRawDataIsOldPending = true;

                // Reset all table secondary filters so nothing restricts the 442 rows
                isUpdatingFilters = true;
                $("#tableStatusFilter").val("all");
                $("#tableDeliveryDateFilter").val("all");
                $("#tableClientFilter").val("all");
                $("#tableGraftedTypeFilter").val("all");
                $("#tableProductFilter").val("all");
                $("#tablePolyhouseFilter").val("all");
                $("#tableSearchInput").val("");
                initSelect2Filters();
                isUpdatingFilters = false;

                // Populate dynamic dropdown filters with the old pending dataset
                populateDynamicFilters();
                updateFilterActiveVisuals();

                // Render table with full entries
                let filtered = getFilteredDataset();
                $("#totalRowCountDisplay").text(oldData.length);
                renderFinalDispatchTable(filtered, "Old Pending Allocation Orders (All Prior Weeks)");
            },
            error: function() {
                tbody.html(`<tr><td colspan="6" class="text-center py-4 text-danger"><i class="fa fa-exclamation-triangle me-2"></i>Failed to load Old Pending Allocation Orders.</td></tr>`);
            }
        });
    }

    function applyFilterAndRender(updateDropdowns = true) {
        let filtered = getFilteredDataset();

        if (updateDropdowns) {
            populateDynamicFilters();
        }

        updateFilterActiveVisuals();
        let displayLabel = currentRawDataIsOldPending ? "Old Pending Allocation Orders" : currentWeekRange;
        renderFinalDispatchTable(filtered, displayLabel);
    }

    // Dynamic Filter Change Handlers
    $("#tableDeliveryDateFilter, #tableClientFilter, #tableGraftedTypeFilter, #tableProductFilter, #tablePolyhouseFilter").on("change", function() {
        if (isUpdatingFilters) return;
        applyFilterAndRender(true);
    });

    // Status Dropdown Filter change
    $("#tableStatusFilter").on("change", function() {
        if (isUpdatingFilters) return;
        let val = $(this).val();
        currentFilter = val;
        $(".status-filter-card").removeClass("active");
        if (val === "all") {
            if (currentRawDataIsOldPending) {
                $(".status-filter-card.card-oldpending").addClass("active");
            } else {
                $(".status-filter-card.card-total").addClass("active");
            }
        } else if (val === "pending") {
            $(".status-filter-card.card-pending").addClass("active");
        } else if (val === "allocated") {
            $(".status-filter-card.card-allocated").addClass("active");
        } else if (val === "readytoload") {
            $(".status-filter-card.card-readytoload").addClass("active");
        } else if (val === "readytodispatch") {
            $(".status-filter-card.card-ready").addClass("active");
        } else if (val === "dispatched") {
            $(".status-filter-card.card-dispatched").addClass("active");
        }

        applyFilterAndRender(true);
    });

    // Status Filter Card click
    $(document).on("click", ".status-filter-card", function() {
        $(".status-filter-card").removeClass("active");
        $(this).addClass("active");
        let filter = $(this).data("filter") || "all";
        currentFilter = filter;

        if (filter === "oldpending") {
            loadOldPendingSchedule();
        } else {
            if (currentRawDataIsOldPending) {
                currentRawDataIsOldPending = false;
                isUpdatingFilters = true;
                $("#tableStatusFilter").val(filter);
                initSelect2Filters();
                isUpdatingFilters = false;

                // Restore previous regular month and week
                let targetWeek = lastRegularWeekRange;
                if (!targetWeek || targetWeek.includes("-01-01 to ")) {
                    targetWeek = "current";
                }
                let targetMonth = (targetWeek && targetWeek.includes("-")) ? targetWeek.substring(0, 7) : new Date().toISOString().substring(0, 7);
                $("#dispatchMonthFilter").val(targetMonth);
                populateWeeks(globalWeekMap, globalSortedWeeks, targetWeek);
            } else {
                isUpdatingFilters = true;
                $("#tableStatusFilter").val(filter);
                initSelect2Filters();
                isUpdatingFilters = false;
                applyFilterAndRender(true);
            }
        }
    });

    // Real-time keyword search input
    $("#tableSearchInput").on("input keyup", function() {
        applyFilterAndRender(true);
    });

    // Reset Filters button
    $("#resetTableFiltersBtn").on("click", function() {
        isUpdatingFilters = true;
        $("#tableDeliveryDateFilter").val("all");
        $("#tableClientFilter").val("all");
        $("#tableGraftedTypeFilter").val("all");
        $("#tableProductFilter").val("all");
        $("#tablePolyhouseFilter").val("all");
        $("#tableStatusFilter").val("all");
        $("#tableSearchInput").val("");
        initSelect2Filters();
        isUpdatingFilters = false;

        if (currentRawDataIsOldPending) {
            currentFilter = "oldpending";
            $(".status-filter-card").removeClass("active");
            $(".status-filter-card.card-oldpending").addClass("active");
            applyFilterAndRender(true);
        } else {
            currentFilter = "all";
            $(".status-filter-card").removeClass("active");
            $(".status-filter-card.card-total").addClass("active");
            applyFilterAndRender(true);
        }
    });

    // Helper: Render Delivery Date Column HTML with Overdue / Today / After X Days badges & Re-Schedule features
    function renderDeliveryDateColHtml(row) {
        let isRowCancelled = Boolean(
            row.is_cancelled == 1 ||
            row.dispatch_status_code === 'cancelled' ||
            (row.item_status && row.item_status.toLowerCase() === 'cancelled') ||
            (row.soi_status && row.soi_status.toLowerCase() === 'cancelled') ||
            (row.sales_order_status && row.sales_order_status.toLowerCase() === 'cancelled') ||
            (row.pso_status && row.pso_status.toLowerCase() === 'cancelled')
        );

        let rawDateStr = row.confirm_delivery_date || row.delivery_date;
        let originalDateStr = row.original_delivery_date || row.delivery_date;
        let formattedDate = row.formatted_delivery_date || rawDateStr || '-';
        let formattedOrigDate = row.formatted_original_delivery_date || originalDateStr || '-';
        let isRescheduled = Boolean(row.is_rescheduled || (row.confirm_delivery_date && row.confirm_delivery_date !== '0000-00-00' && row.confirm_delivery_date !== originalDateStr));
        let rescheduleRemark = (row.confirm_delivery_date_remark || '').trim();

        if (isRowCancelled) {
            let cancelBadge = `<span class="badge bg-danger text-white shadow-xs d-inline-flex align-items-center gap-1" style="font-size: 10px; font-weight: 600; padding: 3px 6px; border-radius: 4px; max-width: 100%; white-space: nowrap;"><i class="fa fa-ban"></i><span>Cancelled</span></span>`;
            return `
                <div class="d-flex flex-column align-items-start" style="gap: 5px; width: 100%; max-width: 100%;">
                    <div class="fw-bold date-highlight text-muted text-decoration-line-through" style="font-size: 13px; line-height: 1.2;">${formattedDate}</div>
                    <div style="max-width: 100%;">${cancelBadge}</div>
                </div>
            `;
        }

        if (!rawDateStr || rawDateStr === '0000-00-00' || formattedDate === '-') {
            return `
                <div class="text-muted small mb-1">-</div>
                <button type="button" class="btn btn-xs btn-outline-primary py-0 px-1 btn-reschedule-modal" 
                    style="font-size: 10px; line-height: 1.35; border-radius: 4px;" 
                    data-soiid="${row.soi_id}" title="Set / Re-schedule Delivery Date">
                    <i class="fa fa-calendar-plus me-1"></i> Set Date
                </button>
            `;
        }

        let today = new Date();
        today.setHours(0, 0, 0, 0);

        let parts = String(rawDateStr).split('-');
        let delivDate;
        if (parts.length === 3) {
            delivDate = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
        } else {
            delivDate = new Date(rawDateStr);
        }
        delivDate.setHours(0, 0, 0, 0);

        let diffTime = delivDate.getTime() - today.getTime();
        let diffDays = Math.round(diffTime / (1000 * 60 * 60 * 24));

        let badgeHtml = "";
        if (row.dispatch_status_code === 'dispatched') {
            badgeHtml = `<span class="badge bg-secondary text-white shadow-xs d-inline-flex align-items-center gap-1" style="font-size: 10px; font-weight: 600; padding: 3px 6px; border-radius: 4px; max-width: 100%; white-space: nowrap;"><i class="fa fa-check"></i><span>Dispatched</span></span>`;
        } else if (diffDays < 0) {
            let absDays = Math.abs(diffDays);
            let dayLabel = (absDays === 1) ? '1 Day Overdue' : `${absDays} Days Overdue`;
            badgeHtml = `<span class="badge bg-danger text-white shadow-xs d-inline-flex align-items-center gap-1" style="font-size: 10px; font-weight: 600; padding: 3px 6px; border-radius: 4px; max-width: 100%; white-space: nowrap;"><i class="fa fa-exclamation-triangle"></i><span>${dayLabel}</span></span>`;
        } else if (diffDays === 0) {
            badgeHtml = `<span class="badge bg-primary text-white shadow-xs d-inline-flex align-items-center gap-1" style="font-size: 10px; font-weight: 600; padding: 3px 6px; border-radius: 4px; max-width: 100%; white-space: nowrap;"><i class="fa fa-calendar-day"></i><span>Today</span></span>`;
        } else {
            let dayLabel = (diffDays === 1) ? 'Tomorrow' : `After ${diffDays} Days`;
            badgeHtml = `<span class="badge text-white shadow-xs d-inline-flex align-items-center gap-1" style="font-size: 10px; font-weight: 600; padding: 3px 6px; border-radius: 4px; background-color: #0d9488; max-width: 100%; white-space: nowrap;"><i class="fa fa-clock"></i><span>${dayLabel}</span></span>`;
        }

        let rescheduleTag = "";
        if (isRescheduled) {
            rescheduleTag = `
                <div class="mt-0.5" style="max-width: 100%;">
                    <span class="badge bg-warning text-dark border border-warning" style="font-size: 9.5px; font-weight: 600; padding: 2px 5px;" title="Delivery Date Re-Scheduled"><i class="fa fa-calendar-check me-1"></i>Re-Scheduled</span>
                </div>
                ${formattedOrigDate !== '-' && formattedOrigDate !== formattedDate ? `<div class="text-muted small mt-0.5" style="font-size: 10px; text-decoration: line-through;" title="Original Delivery Date">Orig: ${formattedOrigDate}</div>` : ''}
                ${rescheduleRemark ? `<div class="text-secondary small mt-0.5 text-truncate" style="font-size: 10px; max-width: 130px; cursor: pointer;" title="${escapeHtml(rescheduleRemark)}"><i class="fa fa-comment-dots text-info me-1"></i>${escapeHtml(rescheduleRemark)}</div>` : ''}
            `;
        }

        return `
            <div class="d-flex flex-column align-items-start" style="gap: 5px; width: 100%; max-width: 100%;">
                <div class="fw-bold date-highlight text-dark" style="font-size: 13px; line-height: 1.2;">${formattedDate}</div>
                <div style="max-width: 100%;">${badgeHtml}</div>
                ${rescheduleTag}
                <div class="w-100" style="margin-top: 2px;">
                    <button type="button" class="btn btn-xs btn-outline-primary py-1 px-1 btn-reschedule-modal shadow-xs w-100" 
                        style="font-size: 10.5px; line-height: 1.25; border-radius: 4px; font-weight: 600; white-space: nowrap; display: inline-flex; align-items: center; justify-content: center; gap: 4px;" 
                        data-soiid="${row.soi_id}" data-is-partial="${row.is_partial_pending == 1 ? 1 : 0}" data-parent-soiid="${row.parent_soi_id || row.soi_id}" title="Update / Re-schedule Delivery Date">
                        <i class="fa fa-calendar-alt"></i> ${row.is_partial_pending == 1 ? 'Re-Schedule Date' : 'Re-Schedule'}
                    </button>
                </div>
            </div>
        `;
    }

    // Helper: Render Order Qty Column HTML for an item
    function renderOrderQtyColHtml(row) {
        let orderQtyVal = parseInt(row.order_qty || 0);
        let reservedQtyVal = parseInt(row.reserved_qty || 0);
        let balanceQtyVal = parseInt(row.balance_reserved_qty !== undefined ? row.balance_reserved_qty : (orderQtyVal - reservedQtyVal));

        return `
            <div class="fw-bold text-dark" style="font-size: 15px;">${orderQtyVal.toLocaleString()}</div>
            <div class="small" style="color: #0d9488; font-size: 11.5px;"><b>Reserved:</b> ${reservedQtyVal.toLocaleString()}</div>
            <div class="text-muted small" style="font-size: 11.5px;"><b>Balance:</b> ${balanceQtyVal.toLocaleString()}</div>
        `;
    }

    // Helper: Render Allocated Stock Order Column HTML for an item
    function renderAllocatedStockColHtml(row) {
        let isStockLinked = row.effective_stockorderno && row.effective_stockorderno !== 'Not Allocated';
        let reservedPlants = parseInt(row.reserved_qty || 0);
        let consumedPlants = parseInt(row.consumed_qty || 0);
        let allocDisplayQty = (reservedPlants > 0) ? reservedPlants : consumedPlants;
        let allocName = (row.allocated_by_name || '').trim();
        if (allocName === '8871765657') allocName = 'Niteesh Mahant';

        if (isStockLinked && (row.dispatch_status_code === 'allocated' || row.dispatch_status_code === 'readytoload' || row.dispatch_status_code === 'readytodispatch' || row.dispatch_status_code === 'dispatched' || allocDisplayQty > 0)) {
            let isSwapped = (row.poc_allocated_stockorderno && row.original_sown_stockorderno && row.poc_allocated_stockorderno !== row.original_sown_stockorderno && row.original_sown_stockorderno !== 'N/A');
            let swapBadge = isSwapped
                ? `<div class="mt-1"><span class="badge bg-warning-subtle text-warning-emphasis border border-warning" style="font-size: 10px;" title="Original Sown Batch: ${row.original_sown_stockorderno} (${row.clientname || ''}), Allocated Batch: ${row.effective_stockorderno} (From ${row.allocated_clientname || 'Other Order'}, PSONO: ${row.allocated_psono || '-'})"><i class="fa fa-exchange-alt me-1"></i> Swapped (Orig: ${row.original_sown_stockorderno})</span></div>`
                : '';

            return `
                <div class="fw-bold text-primary mb-1" style="font-size: 13.5px;">${row.effective_stockorderno}</div>
                ${swapBadge}
                <div class="small mb-1">
                    <span class="text-muted">Allocated Qty:</span> <span class="badge bg-primary text-white">${allocDisplayQty.toLocaleString()}</span>
                </div>
                <div class="text-muted small" style="font-size: 11px;">
                    Sowing: ${row.formatted_sowing_date || '-'} | Expected: ${row.formatted_pso_delivery_date || '-'}
                </div>
                <div class="text-muted small" style="font-size: 10.5px;">
                    Allocated by: ${allocName || '-'} (${row.formatted_allocated_at || '-'})
                </div>
            `;
        } else if (isStockLinked) {
            return `
                <div class="fw-bold text-primary mb-1" style="font-size: 13.5px;">${row.effective_stockorderno}</div>
                <div class="small text-muted">Stock linked, 0 plants allocated.</div>
            `;
        } else {
            return `
                <span class="badge bg-danger text-white mb-1"><i class="fa fa-exclamation-circle me-1"></i>Not Allocated</span>
                <div class="text-muted small" style="font-size: 11px;">No production stock order linked.</div>
            `;
        }
    }

    // Helper: Render Polyhouse Plants Column HTML for an item
    function renderPolyhouseHtml(row) {
        let totalPlants = parseInt(row.polyhouse_plant_qty || 0);
        let trayCount = row.polyhouse_tray_count || 0;
        let readyPlants = parseInt(row.ready_plant_qty || 0);
        let dispatchedPlants = parseInt(row.dispatched_plant_qty || 0);
        let phNames = row.polyhouse_names || 'N/A';
        let phBeds = (row.bed_numbers && row.bed_numbers !== '-' && row.bed_numbers !== 'N/A') ? row.bed_numbers.trim() : '';

        return `
            <div class="small mb-1">
                Total Plants: <span class="fw-bold text-dark">${totalPlants.toLocaleString()}</span> (${trayCount} Trays)
            </div>
            <div class="small mb-1">
                Ready: <span class="badge bg-info text-dark me-1">${readyPlants.toLocaleString()}</span>
                Dispatched: <span class="badge bg-secondary text-white">${dispatchedPlants.toLocaleString()}</span>
            </div>
            <div class="text-muted small" style="font-size: 11px;">
                <i class="fa fa-warehouse me-1 text-secondary"></i>${phNames}${phBeds ? ` <span class="ms-1 badge bg-light text-dark border">Bed: ${escapeHtml(phBeds)}</span>` : ''}
            </div>
        `;
    }

    // Helper: Lookup dispatch row object by soi_id from current dataset
    function getDispatchRowBySoiId(soiId) {
        if (!currentRawData || !Array.isArray(currentRawData)) return null;
        let cleanId = (typeof soiId === 'string' && soiId.includes('_')) ? parseInt(soiId, 10) : soiId;
        return currentRawData.find(r => 
            String(r.soi_id) === String(soiId) || 
            String(r.row_id) === String(soiId) || 
            String(r.soi_id) === String(cleanId) || 
            String(r.real_soi_id) === String(cleanId) ||
            String(r.parent_soi_id) === String(cleanId)
        ) || null;
    }

    // Helper: Render Status Button HTML for an item
    function renderStatusButtonHtml(row) {
        let statusBtnClass = "btn-danger text-white";
        let statusIcon = "fa-clock";
        let statusText = "Pending Allocation";

        if (row.dispatch_status_code === 'dispatched') {
            statusBtnClass = "btn-secondary text-white";
            statusIcon = "fa-truck-moving";
            statusText = "Final Dispatched";
        } else if (row.dispatch_status_code === 'readytodispatch') {
            statusBtnClass = "btn-teal text-white";
            statusIcon = "fa-warehouse";
            statusText = "Ready to Dispatch";
        } else if (row.dispatch_status_code === 'readytoload') {
            statusBtnClass = "btn-warning text-dark";
            statusIcon = "fa-boxes-packing";
            statusText = "Ready to Loading";
        } else if (row.dispatch_status_code === 'allocated') {
            statusBtnClass = "btn-success text-white";
            statusIcon = "fa-check-circle";
            statusText = "Allocated (For Prepared Invoice)";
        }

        return `
            <button type="button" class="btn btn-sm ${statusBtnClass} w-100 py-2 view-order-growth-status-btn shadow-sm" style="font-weight: 600; font-size: 12px; cursor: pointer;" data-soiid="${row.soi_id}" title="Click to view full allocated production order details, growing status & polyhouse trays">
                <i class="fa ${statusIcon} me-1"></i>${statusText}
            </button>
        `;
    }

    // Helper: Render Action Buttons HTML for an item
    function renderActionButtonsHtml(row) {
        let isAllocated = (row.dispatch_status_code === 'allocated' || row.dispatch_status_code === 'readytodispatch' || row.dispatch_status_code === 'readytoload');
        let isDispatched = (row.dispatch_status_code === 'dispatched');
        let canMarkDispatch = canUserMarkDispatch();
        let isReadyToLoadSection = Boolean(
            (typeof currentFilter !== 'undefined' && currentFilter === 'readytoload') ||
            (typeof $("#tableStatusFilter") !== 'undefined' && $("#tableStatusFilter").length && $("#tableStatusFilter").val() === 'readytoload') ||
            row.dispatch_status_code === 'readytoload' ||
            row.dispatch_status_code === 'readytodispatch'
        );

        return `
            <div class="d-flex flex-column gap-1">
                <button type="button" class="btn btn-sm btn-outline-success ${isAllocated ? 'open-allocate-modal-btn' : 'view-order-growth-status-btn'}" data-soiid="${row.soi_id}">
                    <i class="fa fa-exchange-alt me-1"></i> ${isAllocated ? 'Re-Allocate' : 'Allocate & Confirm Order'}
                </button>
                ${isAllocated ? `
                <button type="button" class="btn btn-sm btn-primary prepare-dispatch-invoice-btn shadow-sm" data-soiid="${row.soi_id}" title="Prepare Dispatch Invoice">
                    <i class="fa fa-file-invoice me-1"></i> Prepared Invoice
                </button>` : ''}
                ${(!isDispatched && canMarkDispatch && !isReadyToLoadSection) ? `
                <button type="button" class="btn btn-sm btn-outline-primary mark-dispatched-btn" data-soiid="${row.soi_id}" data-stockorder="${row.effective_stockorderno || ''}" data-qty="${row.order_qty || 0}">
                    <i class="fa fa-truck me-1"></i> Mark Dispatch
                </button>
                <button type="button" class="btn btn-sm mark-dispatched-no-deduct-btn mt-1" 
                        style="background-color: #fffbeb; color: #b45309; border: 1.5px solid #d97706; border-radius: 6px; font-size: 11px; line-height: 1.25;"
                        data-soiid="${row.soi_id}" data-stockorder="${row.effective_stockorderno || ''}" data-qty="${row.order_qty || 0}"
                        title="Mark Dispatched Without Plant Deduction">
                    <i class="fa fa-truck-loading me-1"></i> Mark-Dispatched-Without Plant Deduction
                </button>` : ''}
            </div>
        `;
    }

    
    function renderFinalDispatchTable(data, weekRange) {
        let tbody = $("#finalDispatchTableBody");
        tbody.html("");

        $("#filteredRowCountDisplay").text(Array.isArray(data) ? data.length : 0);

        if (!Array.isArray(data) || data.length === 0) {
            let filterLabel = "orders";
            if (currentFilter === "pending") filterLabel = "pending allocation orders";
            else if (currentFilter === "allocated") filterLabel = "allocated orders";
            else if (currentFilter === "readytoload") filterLabel = "orders ready for loading";
            else if (currentFilter === "readytodispatch") filterLabel = "orders ready for dispatch";
            else if (currentFilter === "dispatched") filterLabel = "dispatched orders";
            else if (currentFilter === "oldpending") filterLabel = "old pending allocation orders";

            tbody.append(`
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted fw-bold">
                        <i class="fa fa-info-circle me-2"></i>No ${filterLabel} found for: ${weekRange}
                    </td>
                </tr>
            `);
            return;
        }

        let today = new Date();
        today.setHours(0, 0, 0, 0);
        let todayStr = today.toISOString().split("T")[0];

        data.forEach((row, index) => {
            let rowDataJson = JSON.stringify(row).replace(/"/g, '&quot;');
            let deliveryDateStr = row.delivery_date;
            let rowClass = "";
            if (row.dispatch_status_code === 'dispatched') {
                rowClass = "row-dispatched";
            } else if (row.dispatch_status_code === 'ready_dispatch') {
                rowClass = "row-ready";
            } else if (deliveryDateStr < todayStr && row.dispatch_status_code !== 'dispatched') {
                rowClass = "row-pending-alert";
            }

            // Client & SO column
            let deliveryLocationHtml = formatDeliveryLocationDisplay(row);
            let clientAndSoCol = `
                <div class="fw-bold text-dark">${row.clientname || 'N/A'}</div>
                <div class="text-muted small"><b>SO:</b> ${row.sono || '-'} | <b>PSONO:</b> ${row.psono || '-'}</div>
                <div class="text-secondary small"><i class="fa fa-phone me-1"></i>${row.mobileno || '-'} | ${row.districtname || ''}, ${row.statename || ''}</div>
                ${deliveryLocationHtml}
            `;

            // Product Column (Show allocated product name, and booked product notice if different)
            let effProdName = row.allocated_product_name || row.product_name;
            let formattedProdName = formatProductNameDisplay(effProdName);
            let isGrafted = isProductGrafted(effProdName);
            let bookedDiffHtml = (row.allocated_product_name && row.product_name && row.allocated_product_name.trim().toLowerCase() !== row.product_name.trim().toLowerCase()) 
                ? `<div class="text-muted small mt-0.5" style="font-size: 11px;" title="Original Booked: ${escapeHtml(row.product_name)}"><span class="badge bg-light text-secondary border me-1" style="font-size: 9.5px;">Booked:</span>${escapeHtml(formatProductNameDisplay(row.product_name))}</div>` 
                : '';
            let productCol = `
                <div class="fw-bold text-dark">${formattedProdName}</div>
                ${bookedDiffHtml}
                ${isGrafted ? `<span class="badge bg-success-subtle text-success border border-success-subtle mt-1" style="font-size: 10px;"><i class="fa fa-seedling me-1"></i>Grafted</span>` : ''}
            `;

            // Production Order & Quantity Merged Column
            let isRowPending = (row.dispatch_status_code === 'pending' || row.dispatch_status_code === 'unallocated');
            let origBatch = (row.original_sown_stockorderno || row.stockorder_no || '').trim();
            let allocBatch = (row.poc_allocated_stockorderno || row.allocated_stockorderno || row.effective_stockorderno || '').trim();
            let activeBatch = (allocBatch || row.effective_stockorderno || '').trim();

            // Standard 4-part Quantity Metrics requested by user:
            // Order Qty: final sales order line qty
            // Allocated: allocated in beds/trays but NOT yet dispatched
            // Dispatched: final dispatched via invoice
            // Pending Qty: Order Qty - (Allocated + Dispatched)
            let totalOrderQty = parseInt(row.total_so_order_qty !== undefined ? row.total_so_order_qty : (row.original_total_order_qty || row.order_qty || 0));
            let totalAllocQty = parseInt(row.total_so_allocated_qty !== undefined ? row.total_so_allocated_qty : (row.allocated_qty || 0));
            let totalDispQty = parseInt(row.total_so_dispatched_qty !== undefined ? row.total_so_dispatched_qty : (row.dispatched_plant_qty || 0));
            let totalPendingQty = parseInt(row.total_so_pending_qty !== undefined ? row.total_so_pending_qty : Math.max(0, totalOrderQty - (totalAllocQty + totalDispQty)));

            let orderQtyVal = parseInt(row.original_total_order_qty || row.order_qty || 0);
            let orderQtyFormatted = orderQtyVal.toLocaleString();

            let isLockedAllocated = (totalAllocQty > 0 && activeBatch && activeBatch !== 'Not Allocated' && activeBatch !== '0');

            let rawAllocName = (row.allocated_by_name || '').trim();
            if (rawAllocName === '8871765657') rawAllocName = 'Niteesh Mahant';
            let allocByDisplay = (totalAllocQty > 0 && rawAllocName && rawAllocName !== '-' && rawAllocName !== '0') 
                ? `${rawAllocName} (${row.formatted_allocated_at || '-'})` 
                : '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-1.5 py-0.5">Unallocated</span>';

            let isOpenStock = Boolean(
                row.is_open_stock == 1 || 
                row.stock_type_label === 'Open Stock' ||
                activeBatch.startsWith('RSO-') || 
                activeBatch.startsWith('RTS-') || 
                activeBatch.startsWith('READY-') ||
                row.pso_clientid === 'C00001' || 
                parseInt(row.pso_is_trader) === 1 || 
                (row.pso_remarks && row.pso_remarks.toLowerCase().includes('ready stock'))
            );

            let isOwnSown = !isOpenStock && Boolean(
                row.is_own_sown == 1 || 
                row.stock_type_label === 'Own Sown' ||
                (
                    (row.pso_psono && row.psono && row.pso_psono === row.psono) ||
                    (row.pso_clientid && row.clientid && row.pso_clientid === row.clientid && row.pso_clientid !== 'C00001')
                )
            );

            let isSwapped = (row.is_swapped == 1 || row.is_swapped === true || (origBatch && allocBatch && origBatch !== 'N/A' && allocBatch !== 'Not Allocated' && origBatch !== '0' && allocBatch !== '0' && origBatch !== allocBatch));
            let isBatchSwapped = !isOpenStock && !isOwnSown && isSwapped;

            let proOrderBadgeHtml = "";
            if (isLockedAllocated) {
                if (isOpenStock) {
                    proOrderBadgeHtml = `
                        <div class="mb-1 d-flex align-items-center flex-wrap gap-1">
                            <span class="text-secondary small"><b>Allocated:</b></span> 
                            <span class="badge bg-primary text-white fw-bold px-2 py-1 font-monospace" style="font-size: 11.5px;">${activeBatch}</span>
                            <span class="badge bg-info-subtle text-primary border border-primary-subtle" style="font-size: 10px;" title="Trader Production Order / Open Stock Batch">
                                <i class="fa fa-boxes me-1"></i>Open Stock
                            </span>
                        </div>
                    `;
                } else if (isBatchSwapped) {
                    proOrderBadgeHtml = `
                        <div class="mb-1">
                            <div class="d-flex align-items-center flex-wrap gap-1 mb-0.5">
                                <span class="text-secondary small"><b>Allocated:</b></span> 
                                <span class="badge bg-primary text-white fw-bold px-2 py-1 font-monospace" style="font-size: 11.5px;">${allocBatch}</span>
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning" style="font-size: 10px;" title="Swapped / Re-allocated from batch ${origBatch}">
                                    <i class="fa fa-exchange-alt me-1"></i>Swapped
                                </span>
                            </div>
                            <div class="small text-muted" style="font-size: 11px;">
                                <b>Original Sown:</b> <span class="badge bg-light text-secondary border font-monospace">${origBatch}</span>
                            </div>
                        </div>
                    `;
                } else {
                    proOrderBadgeHtml = `
                        <div class="mb-1 d-flex align-items-center flex-wrap gap-1">
                            <span class="text-secondary small"><b>Allocated:</b></span> 
                            <span class="badge bg-primary text-white fw-bold px-2 py-1 font-monospace" style="font-size: 11.5px;">${activeBatch}</span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 10px;" title="Production Order Sown for this Client">
                                <i class="fa fa-check-circle me-1"></i>Own Sown
                            </span>
                        </div>
                    `;
                }
            } else if (activeBatch && activeBatch !== 'Not Allocated' && activeBatch !== '0') {
                proOrderBadgeHtml = `
                    <div class="mb-1 d-flex align-items-center flex-wrap gap-1">
                        <span class="text-secondary small"><b>Batch:</b></span> 
                        <span class="badge bg-light text-dark border font-monospace" style="font-size: 11.5px;">${activeBatch}</span>
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning" style="font-size: 10px;" title="Suggested Production Batch - Awaiting Allocation">
                            <i class="fa fa-hourglass-start me-1"></i>Awaiting Allocation
                        </span>
                        ${isOpenStock ? `<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle" style="font-size: 10px;"><i class="fa fa-boxes me-1"></i>Open Stock</span>` : ''}
                    </div>
                `;
            } else {
                proOrderBadgeHtml = `
                    <div class="mb-1">
                        <span class="text-secondary small"><b>Batch:</b></span> 
                        <span class="badge bg-danger text-white"><i class="fa fa-exclamation-circle me-1"></i>Unallocated</span>
                    </div>
                `;
            }

            let orderBadge = `<span class="badge bg-dark text-white fw-bold px-2 py-0.5" style="font-size: 11.5px;">${totalOrderQty.toLocaleString()}</span>`;
            let allocBadge = `<span class="badge ${totalAllocQty > 0 ? 'bg-success' : 'bg-secondary'} text-white fw-bold px-2 py-0.5" style="font-size: 11.5px;" title="Allocated in nursery beds, awaiting dispatch">${totalAllocQty.toLocaleString()}</span>`;
            let dispBadge = `<span class="badge ${totalDispQty > 0 ? 'bg-primary' : 'bg-secondary'} text-white fw-bold px-2 py-0.5" style="font-size: 11.5px;" title="Final dispatched via sales invoice">${totalDispQty.toLocaleString()}</span>`;
            let pendingBadgeClass = totalPendingQty > 0 ? 'bg-danger text-white fw-bold' : 'bg-light text-muted border';
            let pendingBadge = `
                <span class="badge ${pendingBadgeClass} px-2 py-0.5 clickable-pending-qty shadow-2xs" 
                      style="font-size: 11.5px; cursor: pointer;" 
                      data-soiid="${row.soi_id}" 
                      data-psono="${escapeHtml(row.psono || '')}" 
                      data-productid="${escapeHtml(String(row.product_id || ''))}" 
                      data-orderqty="${totalOrderQty}" 
                      data-allocqty="${totalAllocQty}" 
                      data-dispqty="${totalDispQty}" 
                      data-pendingqty="${totalPendingQty}" 
                      data-row="${rowDataJson}"
                      title="Click to process Pending Quantity: Dispatch or Close/Revise with Remark">
                    <i class="fa fa-clock me-1"></i>${totalPendingQty.toLocaleString()}
                </span>
            `;

            // Polyhouse Plants info integrated inside PRO. ORDER & QUANTITY
            let phTotalPlants = parseInt(row.polyhouse_plant_qty || 0);
            let phTrayCount = row.polyhouse_tray_count || 0;
            let phReadyQty = parseInt(row.ready_plant_qty || 0);
            let phDispatchedQty = parseInt(row.dispatched_plant_qty || 0);
            let phLocationName = (row.polyhouse_names || '').trim() || 'N/A';
            let phBedNumbers = (row.bed_numbers && row.bed_numbers !== '-' && row.bed_numbers !== 'N/A') ? row.bed_numbers.trim() : '';
            if (!phBedNumbers && row.rsa_allocatedbed) {
                let rBed = String(row.rsa_allocatedbed).trim();
                if (rBed.startsWith('[')) {
                    try {
                        let parsed = JSON.parse(rBed);
                        if (Array.isArray(parsed)) {
                            let bList = parsed.map(b => (typeof b === 'string') ? b : (b.bed_no || b.bed_name || b.bed || b.bedrackname || '')).filter(Boolean);
                            if (bList.length > 0) phBedNumbers = [...new Set(bList)].join(', ');
                        }
                    } catch(e) {}
                } else if (rBed !== '-' && rBed !== 'N/A') {
                    phBedNumbers = rBed;
                }
            }

            let partialBadgeHtml = '';
            if (row.is_partial_pending == 1) {
                partialBadgeHtml = `
                    <div class="mb-1">
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning px-2 py-0.5 shadow-2xs" style="font-size: 11px;">
                            <i class="fa fa-hourglass-half me-1"></i>Remaining Balance (${parseInt(row.order_qty).toLocaleString()} Plants of ${parseInt(row.original_total_order_qty || row.order_qty).toLocaleString()} Order)
                        </span>
                    </div>
                `;
            } else if (row.has_partial_pending == 1 || (row.is_partial_tranche == 1 && parseInt(row.original_total_order_qty || 0) > parseInt(row.fulfilled_qty || row.allocated_qty || row.reserved_qty || 0))) {
                let currentAllocQty = parseInt(row.fulfilled_qty || row.allocated_qty || row.reserved_qty || row.order_qty || 0);
                let origOrderTotal = parseInt(row.original_total_order_qty || row.order_qty || 0);
                let isDispatchedTranche = (row.dispatch_status_code === 'dispatched' || parseInt(row.dispatched_plant_qty || 0) > 0);
                let trancheLabel = isDispatchedTranche ? 'Partially Dispatched' : 'Partially Allocated';
                let trancheIcon = isDispatchedTranche ? 'fa-truck' : 'fa-truck-loading';
                partialBadgeHtml = `
                    <div class="mb-1">
                        <span class="badge bg-info-subtle text-info-emphasis border border-info px-2 py-0.5 shadow-2xs" style="font-size: 11px;">
                            <i class="fa ${trancheIcon} me-1"></i>${trancheLabel} (${currentAllocQty.toLocaleString()} Plants of ${origOrderTotal.toLocaleString()} Order)
                        </span>
                    </div>
                `;
            }

            let orderStockCol = `
                ${proOrderBadgeHtml}
                ${partialBadgeHtml}
                <div class="d-flex align-items-center flex-wrap gap-2 mb-1 text-dark" style="font-size: 11.5px;">
                    <span><b>Order Qty:</b> ${orderBadge}</span>
                    <span class="text-muted">|</span>
                    <span><b>Allocated:</b> ${allocBadge}</span>
                    <span class="text-muted">|</span>
                    <span><b>Dispatched:</b> ${dispBadge}</span>
                    <span class="text-muted">|</span>
                    <span><b>Pending Qty:</b> ${pendingBadge}</span>
                </div>
                <div class="small mb-1 text-secondary d-flex align-items-center flex-wrap gap-2" style="font-size: 11px;">
                    <span><b>Ready Plants:</b> <span class="badge bg-success text-white fw-bold px-1.5 py-0.5">${phReadyQty.toLocaleString()}</span></span>
                    <span><b>Total in Batch:</b> <span class="fw-bold text-dark">${phTotalPlants.toLocaleString()}</span> <span class="text-muted">(${phTrayCount} Trays)</span></span>
                    <span><i class="fa fa-warehouse text-primary me-1"></i><span class="fw-semibold text-dark">${phLocationName}</span></span>
                    ${phBedNumbers ? `<span><i class="fa fa-layer-group text-success me-1"></i><b>Bed:</b> <span class="fw-bold text-dark badge bg-light text-dark border px-1.5 py-0.5" style="font-size: 10px;">${escapeHtml(phBedNumbers)}</span></span>` : ''}
                </div>
                <div class="text-muted small" style="font-size: 10.5px; line-height: 1.3;">
                    <div><b>Sowing:</b> ${row.formatted_sowing_date || '-'} | <b>Expected:</b> ${row.formatted_pso_delivery_date || '-'}</div>
                    <div><b>Allocated by:</b> ${allocByDisplay}</div>
                </div>
            `;

            let isCancelled = Boolean(
                row.is_cancelled == 1 ||
                row.dispatch_status_code === 'cancelled' ||
                (row.item_status && row.item_status.toLowerCase() === 'cancelled') ||
                (row.soi_status && row.soi_status.toLowerCase() === 'cancelled') ||
                (row.sales_order_status && row.sales_order_status.toLowerCase() === 'cancelled') ||
                (row.pso_status && row.pso_status.toLowerCase() === 'cancelled')
            );

            // Check if this item is in pending allocation state
            let isPending = (!isCancelled && (row.dispatch_status_code === 'pending' || currentFilter === 'pending' || currentFilter === 'oldpending'));
            let isDispatched = (!isCancelled && row.dispatch_status_code === 'dispatched');

            // Status Badge (Shown as badge in Action column ONLY when viewing Total Orders / currentFilter === 'all')
            let showStatusBadge = (currentFilter === "all" || !currentFilter);
            let statusBadgeHtml = "";

            if (showStatusBadge) {
                let statusStyle = "background-color: #fee2e2; color: #dc2626; border: 1px solid #fca5a5;";
                let statusIcon = "fa-clock";
                let statusText = "Pending Allocation";

                if (isCancelled) {
                    statusStyle = "background-color: #fee2e2; color: #b91c1c; border: 1px solid #f87171;";
                    statusIcon = "fa-ban";
                    statusText = "Order Cancelled";
                } else if (row.dispatch_status_code === 'dispatched') {
                    statusStyle = "background-color: #f1f5f9; color: #334155; border: 1px solid #cbd5e1;";
                    statusIcon = "fa-truck";
                    statusText = "Final Dispatched";
                } else if (row.dispatch_status_code === 'readytodispatch') {
                    statusStyle = "background-color: #cffafe; color: #0e7490; border: 1px solid #67e8f9;";
                    statusIcon = "fa-warehouse";
                    statusText = "Ready to Dispatch";
                } else if (row.dispatch_status_code === 'readytoload') {
                    statusStyle = "background-color: #fef3c7; color: #b45309; border: 1px solid #fcd34d;";
                    statusIcon = "fa-boxes-packing";
                    statusText = "Ready to Loading";
                } else if (row.dispatch_status_code === 'allocated') {
                    statusStyle = "background-color: #d1fae5; color: #047857; border: 1px solid #6ee7b7;";
                    statusIcon = "fa-check-circle";
                    statusText = "Allocated (For Prepared Invoice)";
                }

                if (isCancelled) {
                    statusBadgeHtml = `
                        <div class="badge bg-danger text-white py-1.5 px-2 fw-bold w-100 mb-1 shadow-xs" 
                            style="font-size: 11.5px; border-radius: 6px; letter-spacing: 0.3px;">
                            <i class="fa fa-ban me-1"></i>Order Cancelled
                        </div>
                    `;
                } else {
                    statusBadgeHtml = `
                        <button type="button" class="btn btn-sm w-100 py-1 fw-bold view-order-growth-status-btn shadow-sm" 
                            style="font-size: 11.5px; border-radius: 6px; ${statusStyle}" 
                            data-soiid="${row.soi_id}" data-row="${rowDataJson}" title="Click to view full allocated production order details, growing status & polyhouse trays">
                            <i class="fa ${statusIcon} me-1"></i>${statusText}
                        </button>
                    `;
                }
            }

            // Action Column:
            // 0. If Cancelled: ONLY display 'Order is Cancelled' message, stop all allocation / other actions
            // 1. If Pending Allocation: ONLY display 'Allocate & Confirm Order' button (no other buttons)
            // 2. If Fully Dispatched: Display 'Fully Dispatched' status badge, 'Download Invoice' (PDF), and 'Prepared Invoice'
            // 3. If Allocated / In Growth: Display 'Re-Allocate', 'Prepared Invoice', and 'Mark Dispatch'
            // 4. If Ready to Loading Section: Display 'Download Invoice', 'Edit Invoice' / 'Prepare Invoice', and 'Re-Allocate' (Hide 'Mark Dispatch')
            let actionCol = "";
            let invoiceNoForPdf = row.dinvoiceno || row.soi_invoiceno || row.sono || row.psono || '';
            let isReadyToLoadSection = Boolean(
                (typeof currentFilter !== 'undefined' && currentFilter === 'readytoload') ||
                (typeof $("#tableStatusFilter") !== 'undefined' && $("#tableStatusFilter").length && $("#tableStatusFilter").val() === 'readytoload') ||
                row.dispatch_status_code === 'readytoload' ||
                row.dispatch_status_code === 'readytodispatch'
            );

            if (isCancelled) {
                let cancelReason = row.cancel_reason || 'Order is Cancelled';
                actionCol = `
                    <div class="d-flex flex-column align-items-center justify-content-center p-2 text-center shadow-xs" 
                         style="background: #fef2f2; border: 1.5px dashed #ef4444; border-radius: 8px; min-height: 60px;">
                        <span class="badge bg-danger text-white px-2.5 py-1.5 fw-bold shadow-xs d-inline-flex align-items-center gap-1" style="font-size: 11.5px; letter-spacing: 0.3px;">
                            <i class="fa fa-ban"></i> Order is Cancelled
                        </span>
                        <small class="text-danger mt-1 fw-semibold text-break" style="font-size: 10px; line-height: 1.25;">
                            ${escapeHtml(cancelReason)}
                        </small>
                    </div>
                `;
            } else if (isPending) {
                let canMarkDispatch = canUserMarkDispatch();
                actionCol = `
                    <div class="d-flex flex-column gap-1">
                        ${statusBadgeHtml}
                        <button type="button" class="btn btn-sm w-100 py-1.5 fw-bold view-order-growth-status-btn shadow-sm" 
                            style="font-size: 11.5px; background-color: #ffffff; color: #047857; border: 1.5px solid #059669; border-radius: 6px; white-space: normal; line-height: 1.25;"
                            data-soiid="${row.soi_id}" data-row="${rowDataJson}" title="Click to view full allocated production order details, growing status & polyhouse trays">
                            <i class="fa fa-clipboard-check me-1"></i> Allocate & Confirm Order
                        </button>
                    </div>
                    ${canMarkDispatch ? `
                    <button type="button" class="btn btn-sm w-100 py-1 fw-bold mark-dispatched-btn shadow-sm" 
                            style="font-size: 11.5px; background-color: #ffffff; color: #0891b2; border: 1.5px solid #0891b2; border-radius: 6px; white-space: normal; line-height: 1.25;"
                            data-soiid="${row.soi_id}" data-stockorder="${row.effective_stockorderno || ''}" data-qty="${totalPendingQty > 0 ? totalPendingQty : (row.order_qty || 0)}" data-row="${rowDataJson}">
                            <i class="fa fa-truck me-1"></i> Mark Dispatch
                    </button>
                    <button type="button" class="btn btn-sm w-100 py-1 fw-bold mark-dispatched-no-deduct-btn shadow-sm mt-1" 
                            style="font-size: 11px; background-color: #fffbeb; color: #b45309; border: 1.5px solid #d97706; border-radius: 6px; white-space: normal; line-height: 1.25;"
                            data-soiid="${row.soi_id}" data-stockorder="${row.effective_stockorderno || ''}" data-qty="${totalPendingQty > 0 ? totalPendingQty : (row.order_qty || 0)}" data-row="${rowDataJson}"
                            title="Mark Dispatched Without Plant Deduction">
                            <i class="fa fa-truck-loading me-1"></i> Mark-Dispatched-Without Plant Deduction
                    </button>` : ''}
                    <button type="button" class="btn btn-sm w-100 py-1 fw-bold close-pending-qty-btn shadow-sm mt-1" 
                            style="font-size: 11px; background-color: #fff1f2; color: #be123c; border: 1.5px solid #f43f5e; border-radius: 6px; white-space: normal; line-height: 1.25;"
                            data-soiid="${row.soi_id}" 
                            data-psono="${escapeHtml(row.psono || '')}" 
                            data-productid="${escapeHtml(String(row.product_id || ''))}" 
                            data-orderqty="${totalOrderQty}" 
                            data-allocqty="${totalAllocQty}" 
                            data-dispqty="${totalDispQty}" 
                            data-pendingqty="${totalPendingQty}" 
                            data-row="${rowDataJson}"
                            title="Pending quantity is not required delivered: Edit sales order and close with remark">
                            <i class="fa fa-times-circle me-1"></i> Pending Qty Not Required / Edit SO
                    </button>
                `;
            } else if (isDispatched) {
                actionCol = `
                    <div class="d-flex flex-column gap-1">
                        ${statusBadgeHtml}
                        <button type="button" class="btn btn-sm w-100 py-1 fw-bold view-download-invoice-pdf-btn text-white shadow-sm" 
                            style="font-size: 11.5px; background-color: #059669; border: 1px solid #047857; border-radius: 6px; white-space: normal; line-height: 1.25;"
                            data-soiid="${row.soi_id}" data-invoiceno="${invoiceNoForPdf}" data-row="${rowDataJson}" title="View / Download Final Dispatched Invoice (PDF)">
                            <i class="fa fa-file-pdf me-1"></i> Download Invoice
                        </button>
                    </div>
                `;
            } else if (row.dispatch_status_code === 'readytoload' || row.dispatch_status_code === 'readytodispatch' || isReadyToLoadSection) {
                // Ready to Loading Section: Display 'Download Invoice', 'Edit Invoice', 'Re-Allocate'
                // NOTE: 'Mark Dispatch' and 'Mark-Dispatched-Without Plant Deduction' are intentionally hidden in Ready to Loading tab!
                actionCol = `
                    <div class="d-flex flex-column gap-1">
                        ${statusBadgeHtml}
                        <button type="button" class="btn btn-sm w-100 py-1 fw-bold view-download-invoice-pdf-btn text-white shadow-sm" 
                            style="font-size: 11.5px; background-color: #059669; border: 1px solid #047857; border-radius: 6px; white-space: normal; line-height: 1.25;"
                            data-soiid="${row.soi_id}" data-invoiceno="${invoiceNoForPdf}" data-row="${rowDataJson}" title="View / Download Dispatch Invoice (PDF)">
                            <i class="fa fa-file-pdf me-1"></i> Download Invoice
                        </button>
                        <button type="button" class="btn btn-sm w-100 py-1 fw-bold prepare-dispatch-invoice-btn text-white shadow-sm" 
                            style="font-size: 11.5px; background-color: #2563eb; border: 1px solid #1d4ed8; border-radius: 6px; white-space: normal; line-height: 1.25;"
                            data-soiid="${row.soi_id}" data-invoiceno="${invoiceNoForPdf}" data-row="${rowDataJson}" title="Edit / View Dispatch Invoice">
                            <i class="fa fa-file-invoice me-1"></i> Edit Invoice
                        </button>
                        <button type="button" class="btn btn-sm w-100 py-1 fw-bold open-allocate-modal-btn shadow-sm" 
                            style="font-size: 11.5px; background-color: #ffffff; color: #047857; border: 1.5px solid #059669; border-radius: 6px; white-space: normal; line-height: 1.25;"
                            data-soiid="${row.soi_id}" data-row="${rowDataJson}" title="Re-allocate / Replace Production Batch">
                            <i class="fa fa-exchange-alt me-1"></i> Re-Allocate
                        </button>
                        ${(!isDispatched && totalPendingQty > 0) ? `
                        <button type="button" class="btn btn-sm w-100 py-1 fw-bold close-pending-qty-btn shadow-sm mt-1" 
                            style="font-size: 11px; background-color: #fff1f2; color: #be123c; border: 1.5px solid #f43f5e; border-radius: 6px; white-space: normal; line-height: 1.25;"
                            data-soiid="${row.soi_id}" 
                            data-psono="${escapeHtml(row.psono || '')}" 
                            data-productid="${escapeHtml(String(row.product_id || ''))}" 
                            data-orderqty="${totalOrderQty}" 
                            data-allocqty="${totalAllocQty}" 
                            data-dispqty="${totalDispQty}" 
                            data-pendingqty="${totalPendingQty}" 
                            data-row="${rowDataJson}"
                            title="Pending quantity is not required delivered: Edit sales order and close with remark">
                            <i class="fa fa-times-circle me-1"></i> Pending Qty Not Required / Edit SO
                        </button>` : ''}
                    </div>
                `;
            } else {
                let canMarkDispatch = canUserMarkDispatch();
                actionCol = `
                    <div class="d-flex flex-column gap-1">
                        ${statusBadgeHtml}
                        ${(phDispatchedQty > 0 || isReadyToLoadSection) ? `
                        <button type="button" class="btn btn-sm w-100 py-1 fw-bold view-download-invoice-pdf-btn text-white shadow-sm" 
                            style="font-size: 11.5px; background-color: #059669; border: 1px solid #047857; border-radius: 6px; white-space: normal; line-height: 1.25;"
                            data-soiid="${row.soi_id}" data-invoiceno="${invoiceNoForPdf}" data-row="${rowDataJson}" title="View / Download Previous Invoice (PDF)">
                            <i class="fa fa-file-pdf me-1"></i> Download Invoice
                        </button>` : ''}
                        <button type="button" class="btn btn-sm w-100 py-1 fw-bold open-allocate-modal-btn shadow-sm" 
                            style="font-size: 11.5px; background-color: #ffffff; color: #047857; border: 1.5px solid #059669; border-radius: 6px; white-space: normal; line-height: 1.25;"
                            data-soiid="${row.soi_id}" data-row="${rowDataJson}" title="Re-allocate / Replace Production Batch">
                            <i class="fa fa-exchange-alt me-1"></i> Re-Allocate
                        </button>
                        <button type="button" class="btn btn-sm w-100 py-1 fw-bold prepare-dispatch-invoice-btn text-white shadow-sm" 
                            style="font-size: 11.5px; background-color: #2563eb; border: 1px solid #1d4ed8; border-radius: 6px; white-space: normal; line-height: 1.25;"
                            data-soiid="${row.soi_id}" data-row="${rowDataJson}" title="${phDispatchedQty > 0 ? 'Prepare Remaining Invoice' : 'Prepare Dispatch Invoice'}">
                            <i class="fa fa-file-invoice me-1"></i> ${phDispatchedQty > 0 ? 'Prepare Invoice' : 'Prepared Invoice'}
                        </button>
                        ${(!isDispatched && canMarkDispatch && !isReadyToLoadSection) ? `
                        <button type="button" class="btn btn-sm w-100 py-1 fw-bold mark-dispatched-btn shadow-sm" 
                            style="font-size: 11.5px; background-color: #ffffff; color: #0891b2; border: 1.5px solid #0891b2; border-radius: 6px; white-space: normal; line-height: 1.25;"
                            data-soiid="${row.soi_id}" data-stockorder="${row.effective_stockorderno || ''}" data-qty="${(row.is_partial_pending == 1 && totalPendingQty > 0) ? totalPendingQty : (row.order_qty || 0)}" data-row="${rowDataJson}">
                            <i class="fa fa-truck me-1"></i> Mark Dispatch
                        </button>
                        <button type="button" class="btn btn-sm w-100 py-1 fw-bold mark-dispatched-no-deduct-btn shadow-sm mt-1" 
                            style="font-size: 11px; background-color: #fffbeb; color: #b45309; border: 1.5px solid #d97706; border-radius: 6px; white-space: normal; line-height: 1.25;"
                            data-soiid="${row.soi_id}" data-stockorder="${row.effective_stockorderno || ''}" data-qty="${(row.is_partial_pending == 1 && totalPendingQty > 0) ? totalPendingQty : (row.order_qty || 0)}" data-row="${rowDataJson}"
                            title="Mark Dispatched Without Plant Deduction">
                            <i class="fa fa-truck-loading me-1"></i> Mark-Dispatched-Without Plant Deduction
                        </button>` : ''}
                        ${(!isDispatched && totalPendingQty > 0) ? `
                        <button type="button" class="btn btn-sm w-100 py-1 fw-bold close-pending-qty-btn shadow-sm mt-1" 
                            style="font-size: 11px; background-color: #fff1f2; color: #be123c; border: 1.5px solid #f43f5e; border-radius: 6px; white-space: normal; line-height: 1.25;"
                            data-soiid="${row.soi_id}" 
                            data-psono="${escapeHtml(row.psono || '')}" 
                            data-productid="${escapeHtml(String(row.product_id || ''))}" 
                            data-orderqty="${totalOrderQty}" 
                            data-allocqty="${totalAllocQty}" 
                            data-dispqty="${totalDispQty}" 
                            data-pendingqty="${totalPendingQty}" 
                            data-row="${rowDataJson}"
                            title="Pending quantity is not required delivered: Edit sales order and close with remark">
                            <i class="fa fa-times-circle me-1"></i> Pending Qty Not Required / Edit SO
                        </button>` : ''}
                    </div>
                `;
            }

            tbody.append(`
                <tr class="${rowClass}">
                    <td class="text-center fw-bold text-secondary">${index + 1}</td>
                    <td>${renderDeliveryDateColHtml(row)}</td>
                    <td>${clientAndSoCol}</td>
                    <td>${productCol}</td>
                    <td>${orderStockCol}</td>
                    <td class="text-center">${actionCol}</td>
                </tr>
            `);
        });
    }

    let lastSelectedGrowthRow = null;

    // Click Status Button to Open 100% Width Growth & Trays Modal
    $(document).on("click", ".view-order-growth-status-btn", function() {
        let soiId = $(this).data("soiid");
        let row = $(this).data("row") || getDispatchRowBySoiId(soiId);
        if (row) {
            let isCancelled = Boolean(
                row.is_cancelled == 1 ||
                row.dispatch_status_code === 'cancelled' ||
                (row.item_status && row.item_status.toLowerCase() === 'cancelled') ||
                (row.soi_status && row.soi_status.toLowerCase() === 'cancelled') ||
                (row.sales_order_status && row.sales_order_status.toLowerCase() === 'cancelled') ||
                (row.pso_status && row.pso_status.toLowerCase() === 'cancelled')
            );
            if (isCancelled) {
                Swal.fire({
                    icon: 'error',
                    title: 'Order is Cancelled',
                    text: row.cancel_reason || 'This order has been cancelled. Allocation and growth actions are stopped.',
                    confirmButtonColor: '#dc2626'
                });
                return;
            }
            lastSelectedGrowthRow = row;
            loadOrderGrowthStatusModal(row);
        }
    });

    function loadOrderGrowthStatusModal(row) {
        let projectid = $("body").data("projectid") || 0;
        let soiId = row.soi_id;
        let stockOrderNo = row.effective_stockorderno || '';

        $("#orderGrowthStatusModalLabel").html(`
            <span class="text-dark fw-bold text-uppercase">${formatProductNameDisplay(row.product_name)}</span> 
            <span class="badge bg-white text-dark border ms-2 fw-semibold fs-6 shadow-sm">SO: ${row.sono || '-'} | PSONO: ${row.psono || '-'}</span>
        `);
        $("#growthModalSubtitle").html(`<span class="text-dark fw-semibold">Client:</span> ${row.clientname || 'N/A'} <span class="mx-1 text-muted">|</span> <span class="text-dark fw-semibold">Delivery Date:</span> ${row.formatted_delivery_date} <span class="mx-1 text-muted">|</span> <span class="text-dark fw-semibold">Ordered Qty:</span> ${parseInt(row.order_qty || 0).toLocaleString()}`);

        let modalBody = $("#orderGrowthModalBody");
        modalBody.html(`
            <div class="text-center py-5 text-muted">
                <i class="fa fa-spinner fa-spin fa-3x text-success mb-3"></i>
                <h5 class="fw-bold text-dark">Fetching Production Order & Polyhouse Trays Details...</h5>
                <p class="text-muted small">Loading live growing stages, tray locations and capacity data</p>
            </div>
        `);

        $("#orderGrowthStatusModal").modal("show");

        $.ajax({
            url: "modal/finaldispatch_modal.php?action=GetOrderGrowthAndTrayDetails",
            type: "POST",
            data: {
                projectid: projectid,
                soi_id: soiId,
                stockorder_no: stockOrderNo
            },
            dataType: "json",
            success: function(res) {
                if (res.status !== "success") {
                    modalBody.html(`
                        <div class="alert alert-danger m-4">
                            <i class="fa fa-exclamation-triangle me-2"></i> ${res.message || 'Error loading details.'}
                        </div>
                    `);
                    return;
                }

                let order = res.order_item || {};
                let stock = res.stock_order || {};
                let alloc = res.allocation || {};
                let allocations = res.allocations || [];
                let sum = res.summary || {};
                let polyhouses = res.polyhouses || [];
                let beds = res.beds || [];
                let trays = res.trays || [];

                if (order && order.allocated_batches && lastSelectedGrowthRow) {
                    lastSelectedGrowthRow.allocated_batches = order.allocated_batches;
                }

                let isAlloc = stock.stockorder_no && stock.stockorder_no !== 'Not Allocated';
                let isSowingPending = isAlloc && (
                    stock.sowing_status_code === 'pending' || 
                    (stock.sowing_status_label || '').toLowerCase() === 'pending' || 
                    (!stock.sowing_status_code && parseInt(stock.actual_sown_plants || 0) <= 0)
                );
                let isGrafted = order.is_grafted === 1;

                let isPartialPending = Boolean(row.is_partial_pending === 1 || row.dispatch_status_code === 'pending' || row.dispatch_status_code === 'unallocated' || order.is_partial_dispatched === true);
                let isFullyDispatched = !isPartialPending && (order.is_fully_dispatched === true);
                let dispatchedDateStr = order.dispatched_date || row.formatted_delivery_date || '-';
                let dispatchedByName = order.dispatched_by || row.dispatched_by_name || '';
                let invoiceNoForPdf = order.dinvoiceno || order.soi_invoiceno || row.dinvoiceno || row.soi_invoiceno || order.sono || order.psono || row.sono || row.psono || '';

                if (isFullyDispatched) {
                    $("#orderGrowthModalHeaderDispatchedBadge").removeClass("d-none");
                    $("#orderGrowthModalHeaderInvoicePdfBtn")
                        .removeClass("d-none")
                        .attr("data-invoiceno", invoiceNoForPdf)
                        .data("invoiceno", invoiceNoForPdf)
                        .data("soiid", soiId);
                    $("#orderGrowthModalFooterReallocBtn").addClass("d-none");
                    $("#orderGrowthModalFooterInvoicePdfBtn")
                        .removeClass("d-none")
                        .attr("data-invoiceno", invoiceNoForPdf)
                        .data("invoiceno", invoiceNoForPdf)
                        .data("soiid", soiId);
                    $("#orderGrowthModalFooterNotice").html(`
                        <span class="text-success fw-bold">
                            <i class="fa fa-check-circle me-1"></i> This order was fully dispatched on <b>${dispatchedDateStr}</b>${dispatchedByName ? ` by <b>${dispatchedByName}</b>` : ''}.
                        </span>
                    `);
                } else if (order.is_partial_dispatched || isPartialPending) {
                    $("#orderGrowthModalHeaderDispatchedBadge").addClass("d-none");
                    $("#orderGrowthModalHeaderInvoicePdfBtn").addClass("d-none");
                    $("#orderGrowthModalFooterReallocBtn").removeClass("d-none");
                    $("#orderGrowthModalFooterInvoicePdfBtn").addClass("d-none");
                    let dispCount = parseInt(order.total_dispatched_so_far || (parseInt(order.order_qty || 0) - parseInt(row.order_qty || 0)));
                    let pendCount = parseInt(row.order_qty || order.pending_remaining_qty || 0);
                    $("#orderGrowthModalFooterNotice").html(`
                        <span class="text-warning-emphasis fw-bold">
                            <i class="fa fa-info-circle me-1"></i> Partially Dispatched: <b>${dispCount.toLocaleString()} Plants</b> dispatched. Remaining pending balance: <b>${pendCount.toLocaleString()} Plants</b> to allocate.
                        </span>
                    `);
                } else {
                    $("#orderGrowthModalHeaderDispatchedBadge").addClass("d-none");
                    $("#orderGrowthModalHeaderInvoicePdfBtn").addClass("d-none");
                    $("#orderGrowthModalFooterReallocBtn").removeClass("d-none");
                    $("#orderGrowthModalFooterInvoicePdfBtn").addClass("d-none");
                    $("#orderGrowthModalFooterNotice").html(`
                        <i class="fa fa-info-circle text-primary me-1"></i> Choose production stock order batch and beds above, then click <b>Save Allocation</b> or <b>Confirm &amp; Prepare Invoice</b>.
                    `);
                }

                // 1. Top 3 Cards Grid
                let topCardsHtml = `
                    <div class="row g-3 mb-4">
                        <!-- Card 1: Sales Order & Client -->
                        <div class="col-12 col-lg-4">
                            <div class="card h-100 border-0 shadow-sm rounded-3">
                                <div class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                    <span class="fw-bold text-primary small text-uppercase"><i class="fa fa-user me-1"></i> Client & Sales Order</span>
                                    <span class="badge bg-primary">${order.item_status || 'Active'}</span>
                                </div>
                                <div class="card-body p-3">
                                    <h5 class="fw-bold text-dark mb-1">${order.clientname || 'N/A'}</h5>
                                    <div class="text-muted small mb-2"><i class="fa fa-phone text-secondary me-1"></i>${order.mobileno || '-'} | ${order.districtname || ''}, ${order.statename || ''}</div>
                                    ${formatDeliveryLocationDisplay(order)}
                                    <hr class="my-2">
                                    <div class="row g-2 small">
                                        <div class="col-6">
                                            <span class="text-muted d-block">Sales Order:</span>
                                            <span class="fw-bold text-dark">${order.sono || '-'}</span>
                                        </div>
                                        <div class="col-6">
                                            <span class="text-muted d-block">PSONO:</span>
                                            <span class="fw-bold text-dark">${order.psono || '-'}</span>
                                        </div>
                                        <div class="col-6">
                                            <span class="text-muted d-block">Ordered Qty:</span>
                                            <span class="fw-bold text-dark fs-6">${parseInt(order.order_qty || 0).toLocaleString()} Plants</span>
                                        </div>
                                        <div class="col-6">
                                            <span class="text-muted d-block">Delivery Date:</span>
                                            <span class="fw-bold text-success">${order.formatted_delivery_date || '-'}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Allocated Production Stock Order -->
                        <div class="col-12 col-lg-4">
                            <div class="card h-100 ${isSowingPending ? 'border border-danger-subtle' : 'border-0'} shadow-sm rounded-3">
                                <div class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                    <span class="fw-bold ${isSowingPending ? 'text-danger' : 'text-success'} small text-uppercase"><i class="fa fa-cogs me-1"></i> Allocated Production Order</span>
                                    <div class="d-flex align-items-center gap-1 flex-wrap">
                                        ${isAlloc ? `
                                            ${parseInt(stock.is_trader) === 1 ? `
                                                <span class="badge bg-warning-subtle text-dark border border-warning"><i class="fa fa-store me-1"></i>Trader</span>
                                            ` : `
                                                <span class="badge ${isSowingPending ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-success-subtle text-success border border-success-subtle'}"><i class="fa fa-seedling me-1"></i>Self Farm</span>
                                            `}
                                            <span class="badge ${stock.sowing_badge_class || 'bg-warning-subtle text-warning-emphasis border border-warning-subtle'}" title="Batch Sowing Status">
                                                <i class="fa ${stock.sowing_status_code === 'sowed' ? 'fa-check-circle' : (stock.sowing_status_code === 'start' ? 'fa-spinner fa-spin' : 'fa-clock')} me-1"></i>Sowing: ${stock.sowing_status_label || 'Pending'}
                                            </span>
                                            <span class="badge bg-success">${stock.workingstatus || stock.status || 'Allocated'}</span>
                                        ` : `<span class="badge bg-danger">Not Allocated</span>`}
                                    </div>
                                </div>
                                <div class="card-body p-3">
                                    ${isAlloc ? `
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <h5 class="fw-bold text-primary mb-0">${stock.stockorder_no}</h5>
                                                <div class="text-muted small">${stock.clean_product_name || stock.product_name}</div>
                                            </div>
                                            <div>
                                                ${parseInt(stock.is_trader) === 1 ? `
                                                    <span class="badge bg-warning text-dark border border-warning px-2 py-1 fw-bold shadow-sm" title="Allocated to Trader Production Order">
                                                        <i class="fa fa-handshake me-1"></i> Trader Production Order
                                                    </span>
                                                ` : `
                                                    <span class="badge ${isSowingPending ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-success-subtle text-success border border-success-subtle'} px-2 py-1 fw-bold" title="${isSowingPending ? 'Self Production Order (Sowing Pending)' : 'Self Production Order'}">
                                                        <i class="fa fa-seedling ${isSowingPending ? 'text-danger' : 'text-success'} me-1"></i> Self Production Order
                                                    </span>
                                                `}
                                            </div>
                                        </div>
                                        <div class="row g-2 small">
                                            <div class="col-6">
                                                <span class="text-muted d-block">Batch Total Qty:</span>
                                                <span class="fw-bold text-dark">${parseInt(stock.quantity || 0).toLocaleString()}</span>
                                            </div>
                                            <div class="col-6">
                                                <span class="text-muted d-block">Allocated for this SO:</span>
                                                <span class="badge bg-primary fs-6">${parseInt(alloc.consumed_qty || order.order_qty || 0).toLocaleString()}</span>
                                            </div>
                                            <div class="col-6">
                                                <span class="text-muted d-block">Sowing Status:</span>
                                                <span class="badge ${stock.sowing_badge_class || 'bg-warning-subtle text-warning-emphasis border border-warning-subtle'}">
                                                    <i class="fa ${stock.sowing_status_code === 'sowed' ? 'fa-check-circle' : (stock.sowing_status_code === 'start' ? 'fa-spinner fa-spin' : 'fa-clock')} me-1"></i>
                                                    ${stock.sowing_status_label || 'Pending'}
                                                </span>
                                                ${stock.is_grafted && stock.scion_sowing_status_label && stock.scion_sowing_status_label !== stock.sowing_status_label ? `
                                                    <span class="badge ${stock.scion_sowing_badge_class || 'bg-secondary-subtle'} ms-1" style="font-size: 9.5px;" title="Scion Sowing Status">Scion: ${stock.scion_sowing_status_label}</span>
                                                ` : ''}
                                            </div>
                                            <div class="col-6">
                                                <span class="text-muted d-block">Sown / Planned Qty:</span>
                                                <span class="fw-bold text-dark">
                                                    ${stock.actual_sown_plants > 0 ? `
                                                        <span class="text-success">${stock.actual_sown_plants.toLocaleString()} Sown</span>
                                                        <span class="text-muted" style="font-size: 10px;">(${stock.actual_sown_trays} Trays)</span>
                                                    ` : (stock.planned_sowing_qty > 0 ? `
                                                        <span class="text-secondary">${stock.planned_sowing_qty.toLocaleString()} Planned</span>
                                                    ` : '-')}
                                                </span>
                                            </div>
                                            <div class="col-6">
                                                <span class="text-muted d-block">${stock.is_grafted ? 'Root Sowing Date:' : 'Sowing Date:'}</span>
                                                <span class="fw-bold text-dark">${stock.formatted_sowing_date || '-'}</span>
                                            </div>
                                            <div class="col-6">
                                                <span class="text-muted d-block">Expected Ready:</span>
                                                <span class="fw-bold text-dark">${stock.formatted_expected_delivery || '-'}</span>
                                            </div>
                                            ${stock.is_grafted && stock.formatted_scion_sowing_date && stock.formatted_scion_sowing_date !== '-' && stock.formatted_scion_sowing_date !== stock.formatted_sowing_date ? `
                                            <div class="col-6">
                                                <span class="text-muted d-block">Scion Sowing Date:</span>
                                                <span class="fw-bold text-dark">${stock.formatted_scion_sowing_date}</span>
                                            </div>
                                            ` : ''}
                                            <div class="col-12 mt-1">
                                                <span class="text-muted d-block" style="font-size: 11px;">
                                                    Nature: <b class="${parseInt(stock.is_trader) === 1 ? 'text-warning-emphasis' : (isSowingPending ? 'text-danger' : 'text-success')}">${parseInt(stock.is_trader) === 1 ? 'Trader Production Order' : 'Self Production Order'}</b> 
                                                    ${stock.stock_client_name ? `<span class="text-muted">(${stock.stock_client_name})</span>` : ''}
                                                    <span class="mx-1 text-muted">|</span> Allocated At: ${alloc.formatted_allocated_at || '-'} ${alloc.remark ? `| Note: <i>${alloc.remark}</i>` : ''}
                                                </span>
                                            </div>
                                        </div>
                                    ` : `
                                        <div class="text-center py-4 text-muted">
                                            <i class="fa fa-exclamation-circle text-danger fa-2x mb-2"></i>
                                            <p class="mb-2 fw-bold text-danger">No Production Stock Order Linked</p>
                                        </div>
                                    `}
                                </div>
                            </div>
                        </div>

                        <!-- Card 3: Live Polyhouse Inventory & Growing Metrics -->
                        <div class="col-12 col-lg-4">
                            <div class="card h-100 border-0 shadow-sm rounded-3">
                                <div class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                    <span class="fw-bold text-teal small text-uppercase" style="color: #0d9488;"><i class="fa fa-warehouse me-1"></i> Live Inventory & Health Metrics</span>
                                    <span class="badge bg-teal text-white" style="background-color: #0d9488;">${sum.polyhouse_count || 0} PH | ${sum.bed_count || beds.length || 0} Beds</span>
                                </div>
                                <div class="card-body p-3">
                                    <div class="row g-2 text-center">
                                        <div class="col-4">
                                            <div class="bg-light p-2 rounded-3 border">
                                                <span class="text-muted small d-block" style="font-size: 11px;">Total Trays</span>
                                                <h4 class="fw-bold text-dark mb-0">${sum.total_trays || 0}</h4>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="bg-light p-2 rounded-3 border">
                                                <span class="text-muted small d-block" style="font-size: 11px;">Healthy Plants</span>
                                                <h4 class="fw-bold text-success mb-0">${(sum.total_plants || 0).toLocaleString()}</h4>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="bg-info-subtle p-2 rounded-3 border border-info-subtle">
                                                <span class="text-dark small d-block fw-bold" style="font-size: 11px;">Open Stock</span>
                                                <h4 class="fw-bold text-info mb-0">${(sum.open_stock_plants !== undefined ? sum.open_stock_plants : (sum.ready_to_allocate_plants !== undefined ? sum.ready_to_allocate_plants : sum.ready_plants || 0)).toLocaleString()}</h4>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3 small d-flex justify-content-between flex-wrap gap-1">
                                        <span><i class="fa fa-clipboard-check text-primary me-1"></i> Allocated: <b>${(sum.allocated_plants || 0).toLocaleString()} Plants (${sum.allocated_trays || 0} Trays)</b></span>
                                        <span><i class="fa fa-truck text-success me-1"></i> Dispatched: <b>${(sum.dispatched_plants || 0).toLocaleString()}</b></span>
                                    </div>
                                    ${(sum.total_failure_plants || 0) > 0 ? `
                                        <div class="mt-2 small text-danger text-center bg-danger-subtle py-1 rounded-2 border border-danger-subtle">
                                            <i class="fa fa-exclamation-triangle me-1"></i> Total Failure Plant Loss: <b>${(sum.total_failure_plants).toLocaleString()}</b>
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                // 2. Growing Lifecycle Progress Bar
                let openStockPlants = (sum.open_stock_plants !== undefined ? sum.open_stock_plants : (sum.ready_to_allocate_plants !== undefined ? sum.ready_to_allocate_plants : sum.ready_plants || 0));
                let netReadyPlants = openStockPlants;
                let totalMarkedReadyPlants = (sum.ready_plants !== undefined ? sum.ready_plants : (sum.total_plants || 0));
                let missingReadyPlants = (sum.missing_ready_to_sale_plants !== undefined ? sum.missing_ready_to_sale_plants : (sum.growing_plants || 0));
                let missingReadyTrays = (sum.missing_ready_to_sale_trays !== undefined ? sum.missing_ready_to_sale_trays : 0);
                let isReady = (totalMarkedReadyPlants > 0);
                let isDispatched = (sum.dispatched_plants >= parseInt(order.order_qty || 0)) && (sum.dispatched_plants > 0);
                let hasMissingReady = (missingReadyPlants > 0 || missingReadyTrays > 0);

                let timelineHtml = `
                    <div class="card border-0 shadow-sm rounded-3 mb-4">
                        <div class="card-header bg-white py-2 px-3 border-bottom">
                            <span class="fw-bold text-dark small text-uppercase"><i class="fa fa-chart-line text-success me-1"></i> Seedling Growth & Dispatch Stage Tracking</span>
                        </div>
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center position-relative flex-wrap gap-2 text-center">
                                <div class="flex-grow-1">
                                    <div class="p-2 rounded-3 ${isAlloc ? 'bg-success text-white' : 'bg-secondary text-white'} fw-bold small">
                                        1. Sowing & Seed Placement
                                    </div>
                                    <div class="text-muted small mt-1">${stock.formatted_sowing_date || 'Awaiting'}</div>
                                </div>
                                <div class="text-muted"><i class="fa fa-arrow-right"></i></div>
                                <div class="flex-grow-1">
                                    <div class="p-2 rounded-3 ${isAlloc ? 'bg-success text-white' : 'bg-light text-muted border'} fw-bold small">
                                        2. Germination Box Stage
                                    </div>
                                    <div class="text-muted small mt-1">Controlled Environment</div>
                                </div>
                                <div class="text-muted"><i class="fa fa-arrow-right"></i></div>
                                <div class="flex-grow-1">
                                    <div class="p-2 rounded-3 ${sum.total_trays > 0 ? 'bg-primary text-white' : 'bg-light text-muted border'} fw-bold small">
                                        3. Polyhouse Bed Growth (${sum.total_trays} Trays)
                                    </div>
                                    <div class="text-muted small mt-1">${sum.polyhouse_count} Polyhouses | ${beds.length} Beds</div>
                                </div>
                                <div class="text-muted"><i class="fa fa-arrow-right"></i></div>
                                <div class="flex-grow-1">
                                    <div class="p-2 rounded-3 ${hasMissingReady ? 'bg-warning-subtle text-warning-emphasis border border-warning' : 'bg-light text-muted border'} fw-bold small" style="font-size: 12px;">
                                        4. Missing Mark Ready To Sale
                                    </div>
                                    <div class="text-muted small mt-1">${missingReadyPlants.toLocaleString()} Plants ${missingReadyTrays > 0 ? `(${missingReadyTrays} Trays)` : ''}</div>
                                </div>
                                <div class="text-muted"><i class="fa fa-arrow-right"></i></div>
                                <div class="flex-grow-1">
                                    <div class="p-2 rounded-3 ${isReady ? 'bg-teal text-white' : 'bg-light text-muted border'}" style="${isReady ? 'background-color: #0d9488;' : ''} font-weight: bold; font-size: 12px;">
                                        5. Ready to Allocate
                                    </div>
                                    <div class="text-muted small mt-1">${totalMarkedReadyPlants.toLocaleString()} Plants Ready</div>
                                </div>
                                <div class="text-muted"><i class="fa fa-arrow-right"></i></div>
                                <div class="flex-grow-1">
                                    <div class="p-2 rounded-3 ${isDispatched ? 'bg-success text-white' : 'bg-light text-muted border'} fw-bold small">
                                        6. Final Dispatched
                                    </div>
                                    <div class="text-muted small mt-1">${sum.dispatched_plants.toLocaleString()} Plants Sent</div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                // Embed the New Production Stock Order & Bed Allocation Section (Image 4 Concept)
                let rowQty = parseInt(row.order_qty || 0);
                let dbOrderQty = parseInt(order.order_qty || 0);
                let orderQty = (rowQty > 0) ? rowQty : dbOrderQty;
                let stockNo = stock.stockorder_no || row.effective_stockorderno || row.stockorder_no || '';
                let defaultTrays = Math.ceil(orderQty / 125);

                let allocationSectionHtml = `
                    <div class="card border-0 shadow-sm rounded-3 mb-4" id="growthModalAllocationCard">
                        <div class="card-header bg-white py-3 px-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-primary text-white rounded-3 p-2 d-flex align-items-center justify-content-center shadow-xs" style="width: 38px; height: 38px;">
                                    <i class="fa fa-boxes fs-5"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold text-dark mb-0">Production Stock Order & Bed Allocation</h5>
                                    <span class="text-secondary small">Allocate ordered crop quantities and select specific polyhouse beds for loading & dispatch</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span id="growth_modal_stockorder_grid_count" class="badge bg-secondary px-2.5 py-1.5 fs-6 shadow-2xs">0 Batches</span>
                                <label class="custom-variety-toggle mb-0" for="growth_modal_allow_all_products_toggle">
                                    <input type="checkbox" id="growth_modal_allow_all_products_toggle" class="custom-variety-input">
                                    <span class="custom-variety-slider"></span>
                                    <span class="custom-variety-label">All Varieties</span>
                                </label>
                            </div>
                        </div>
                        <div class="card-body p-3 bg-light">
                            <!-- Hidden inputs for saving allocation -->
                            <input type="hidden" id="growth_modal_soi_id" value="${order.soi_id || row.soi_id || ''}">
                            <input type="hidden" id="growth_modal_psono" value="${order.psono || row.psono || ''}">
                            <input type="hidden" id="growth_modal_sono" value="${order.sono || row.sono || ''}">
                            <input type="hidden" id="growth_modal_client_id" value="${order.clientid || row.clientid || row.client_id || ''}">
                            <input type="hidden" id="growth_modal_product_id" value="${order.product_id || row.product_id || ''}">
                            <input type="hidden" id="growth_modal_product_name" value="${order.product_name || row.product_name || ''}">
                            <input type="hidden" id="growth_modal_stockorder_no" value="${stockNo}">
                            <input type="hidden" id="growth_modal_allocated_psono" value="${row.poc_allocated_psono || ''}">
                            <input type="hidden" id="growth_modal_client_order_qty" value="${orderQty}">
                            <input type="hidden" id="growth_modal_selected_ready_plants" value="0">
                            <input type="hidden" id="growth_modal_selected_ready_trays" value="0">
                            <input type="hidden" id="growth_modal_healthy_plant" value="${orderQty}">
                            <input type="hidden" id="growth_modal_unhealthy_plant" value="0">

                            <!-- Do you allocate shifted plants? Card -->
                            <div class="card border border-primary-subtle bg-white p-3 rounded-3 mb-3 shadow-xs" id="growth_shiftedPlantsQuestionCard">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                                    <div>
                                        <div class="fw-bold text-dark"><i class="fa fa-question-circle text-primary me-1"></i> Do you allocate shifted plants?</div>
                                        <div class="text-muted small" style="font-size: 11px;">Select YES for shifted plants (exact order qty), or NO to specify extra plants for non-shifted trays.</div>
                                    </div>
                                    <div class="btn-group" role="group" aria-label="Allocate shifted plants">
                                        <input type="radio" class="btn-check shifted-toggle-radio" name="growth_allocate_shifted_plants" id="growth_shifted_yes" value="yes" checked>
                                        <label class="btn shifted-toggle-btn btn-sm px-3 fw-bold" for="growth_shifted_yes"><i class="fa fa-check me-1"></i> YES</label>
                                        
                                        <input type="radio" class="btn-check shifted-toggle-radio" name="growth_allocate_shifted_plants" id="growth_shifted_no" value="no">
                                        <label class="btn shifted-toggle-btn btn-sm px-3 fw-bold" for="growth_shifted_no"><i class="fa fa-times me-1"></i> NO</label>
                                    </div>
                                </div>

                                <!-- Extra Plant Calculation Details (shown only when NO) -->
                                <div id="growth_shiftedPlantsCalcSection" class="bg-light p-3 rounded-2 border mt-2 d-none">
                                    <div class="row g-3 align-items-center">
                                        <div class="col-12 col-md-4">
                                            <span class="text-muted small d-block">Client Order Qty:</span>
                                            <span class="fw-bold text-dark fs-6" id="growth_calc_order_qty">${orderQty.toLocaleString()} Plants</span>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <label class="text-muted small d-block mb-0" for="growth_calc_extra_percent">% of Extra Plant: <span class="text-danger fw-bold">*</span></label>
                                            <div class="input-group input-group-sm mt-1">
                                                <input type="number" class="form-control fw-bold text-primary" id="growth_calc_extra_percent" step="0.01" min="0" placeholder="e.g. 5">
                                                <span class="input-group-text bg-light fw-bold">%</span>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <span class="text-muted small d-block">Extra Plants (Non-Shifted Trays):</span>
                                            <span class="fw-bold text-warning-emphasis fs-6" id="growth_calc_extra_plants">+0 Plants</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Search input and Quick Auto-Allocate Actions -->
                            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                                <div class="input-group input-group-sm shadow-sm flex-grow-1" style="max-width: 580px;">
                                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa fa-search"></i></span>
                                    <input type="text" id="growth_modal_stockorder_grid_search" class="form-control border-start-0" placeholder="Search batch no, product, allocated client, polyhouse, bed, dates...">
                                    <button class="btn btn-outline-secondary" type="button" id="growth_modal_clear_stockorder_search" title="Clear search"><i class="fa fa-times"></i></button>
                                </div>
                                <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                    <button type="button" class="btn btn-sm btn-outline-success fw-bold shadow-xs py-1 px-2.5" id="growth_btnAutoAllocateByBalance" title="Automatically allocate required order quantity from available stock order batches by balance">
                                        <i class="fa fa-magic me-1"></i> Auto-Allocate by Balance
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary fw-semibold shadow-xs py-1 px-2" id="growth_btnResetAllocation" title="Reset all selected batches">
                                        <i class="fa fa-undo me-1"></i> Reset
                                    </button>
                                </div>
                            </div>

                            <!-- Live Multi-Batch Allocation Summary Bar -->
                            <div id="growth_modalMultiBatchAllocationSummary" class="card border border-primary-subtle p-2.5 rounded-3 mb-2 shadow-xs" style="background: linear-gradient(135deg, #f0fdf4 0%, #f8fafc 100%);">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div class="d-flex align-items-center gap-3 flex-wrap">
                                        <div>
                                            <span class="text-secondary small fw-bold text-uppercase" style="font-size: 11px;"><i class="fa fa-clipboard-list text-primary me-1"></i> Target Order Qty:</span>
                                            <span class="fw-bold text-dark fs-6 ms-1 font-monospace" id="growth_multi_alloc_target_qty">${orderQty.toLocaleString()} Plants</span>
                                        </div>
                                        <div class="border-start ps-3">
                                            <span class="text-secondary small fw-bold text-uppercase" style="font-size: 11px;"><i class="fa fa-check-double text-success me-1"></i> Total Allocated:</span>
                                            <span class="fw-bold fs-6 ms-1 font-monospace text-success" id="growth_multi_alloc_total_qty">0 Plants</span>
                                        </div>
                                        <div class="border-start ps-3">
                                            <span class="text-secondary small fw-bold text-uppercase" style="font-size: 11px;"><i class="fa fa-balance-scale text-warning-emphasis me-1"></i> Remaining Needed:</span>
                                            <span class="fw-bold fs-6 ms-1 font-monospace" id="growth_multi_alloc_diff_qty">${orderQty.toLocaleString()} Plants</span>
                                        </div>
                                    </div>
                                    <div id="growth_multi_alloc_status_badge">
                                        <span class="badge bg-secondary px-2.5 py-1.5"><i class="fa fa-info-circle me-1"></i> Please allocate batches</span>
                                    </div>
                                </div>
                                <div id="growth_multi_alloc_batches_chips" class="mt-2 pt-2 border-top d-flex align-items-center flex-wrap gap-1.5 d-none">
                                    <!-- Populated dynamically with badges for each allocated batch -->
                                </div>
                            </div>

                            <!-- Table Grid with max-height: 420px -->
                            <div class="table-responsive rounded-2 border bg-white shadow-sm mb-3" style="max-height: 420px; overflow-y: auto;">
                                <table class="table table-hover table-sm align-middle mb-0 text-nowrap" id="growth_modalStockOrderGridTable" style="font-size: 12px;">
                                    <thead class="table-light sticky-top" style="z-index: 2;">
                                        <tr class="text-uppercase text-secondary" style="font-size: 11px;">
                                            <th class="text-center align-middle" style="width: 55px; min-width: 55px; text-align: center;">Select</th>
                                            <th>Stock Order / Batch</th>
                                            <th>Product Name</th>
                                            <th class="text-end" style="min-width: 105px;" title="Order Qty & Plants in Polyhouse">QTY</th>
                                            <th class="text-end" style="min-width: 90px;" title="Assigned / Confirmed Allocations from krf_productionorder_consumed">Assigned Qty</th>
                                            <th class="text-end" style="min-width: 105px;" title="Plants Consumed or Removed from Polyhouse">Consumed/Removed</th>
                                            <th class="text-end" style="min-width: 95px;" title="Ready to Allocate Available Balance">Avail Bal</th>
                                            <th class="text-center" style="width: 140px; min-width: 130px; text-align: center;">Allocating Qty</th>
                                            <th class="text-center" style="width: 125px; min-width: 115px; text-align: center;">Extra Plant</th>
                                            <th style="min-width: 110px;">Polyhouse &amp; Bed</th>
                                            <th style="min-width: 220px;"><i class="fa fa-th-large text-success me-1"></i>Allocated Bed</th>
                                            <th>Delivery Due</th>
                                        </tr>
                                    </thead>
                                    <tbody id="growth_modalStockOrderGridBody">
                                        <tr>
                                            <td colspan="12" class="text-center py-4 text-muted">
                                                <i class="fa fa-spinner fa-spin me-1 text-primary"></i> Loading production stock orders...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Order Qty, Extra Qty & Remarks -->
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold text-dark mb-1">
                                        Order Qty <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" class="form-control fw-bold text-success fs-5" id="growth_modal_allocated_qty" value="${row.consumed_qty > 0 ? row.consumed_qty : orderQty}" min="1" step="any" required>
                                    <div class="form-text text-muted" id="growth_modal_alloc_qty_help">Base order plant quantity.</div>
                                </div>
                                <div class="col-md-4" id="growth_modalExtraQtyContainer">
                                    <label class="form-label fw-bold text-dark mb-1">
                                        Extra Qty (Plants)
                                    </label>
                                    <input type="number" class="form-control fw-bold text-warning-emphasis fs-5 bg-light" id="growth_modal_extra_qty" value="${row.shiftingqty || 0}">
                                    <div class="form-text text-muted" id="growth_modal_extra_qty_help">Calculated from % extra plant (Read Only).</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold text-dark mb-1">Allocation / Substitution Remark</label>
                                    <input type="text" class="form-control" id="growth_modal_allocation_remark" value="${row.consumed_remark || ''}" placeholder="e.g. Allocated ready trays with shifted plants">
                                </div>
                            </div>

                            <!-- Validation Alert -->
                            <div id="growth_modalReadyPlantsValidationAlert" class="mb-3 d-none"></div>

                            <!-- Informational Notice (Actions are in the sticky modal footer below) -->
                            <div class="alert alert-info py-2 px-3 mb-0 rounded-2 d-flex align-items-center justify-content-between flex-wrap gap-2" style="background-color: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af;">
                                <div class="small">
                                    <i class="fa fa-info-circle me-1 text-primary"></i> <b>Tip:</b> Rows highlighted in green indicate client's own production order. Choose specific beds in the <b>Allocated Bed</b> column.
                                </div>
                                <div class="small text-muted">
                                    <i class="fa fa-arrow-down me-1 text-primary"></i> Action buttons (<b>Save Allocation</b> and <b>Confirm &amp; Prepare Invoice</b>) are located in the footer below.
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                modalBody.html(topCardsHtml + timelineHtml + allocationSectionHtml);

                // Initialize allocation state for growth modal
                currentModalDispatchRow = row;
                currentModalProductId = order.product_id || row.product_id || '';
                currentSelectedStockOrder = stockNo;
                currentModalDeliveryDate = order.delivery_date || row.delivery_date || '';
                currentBatchAllocations = {};
                currentBatchExtraAllocations = {};
                cachedAvailableStockOrders = [];

                if (isPartialPending) {
                    // For companion pending balance tranche, do not pre-load the old dispatched batch
                    currentBatchAllocations = {};
                    currentBatchExtraAllocations = {};
                } else if (row.allocated_batches && Array.isArray(row.allocated_batches) && row.allocated_batches.length > 0) {
                    row.allocated_batches.forEach(b => {
                        let bNo = b.stockorder_no || b.stockorderno;
                        let bQty = parseInt(b.allocated_qty || b.consumed_qty || 0);
                        if (bNo && bQty > 0) {
                            currentBatchAllocations[bNo] = bQty;
                            let bExtra = parseInt(b.extra_qty !== undefined ? b.extra_qty : (b.extraqtyforshifting !== undefined ? b.extraqtyforshifting : (b.shiftingqty !== undefined ? b.shiftingqty : 0))) || 0;
                            if (bExtra > 0) currentBatchExtraAllocations[bNo] = bExtra;
                        }
                    });
                } else if (stockNo && stockNo !== 'Not Allocated' && stockNo !== '0' && stockNo !== '-') {
                    let parts = stockNo.split(',').map(s => s.trim()).filter(Boolean);
                    if (parts.length > 1) {
                        let evenQty = Math.floor(orderQty / parts.length);
                        parts.forEach((p, idx) => {
                            currentBatchAllocations[p] = (idx === parts.length - 1) ? (orderQty - evenQty * (parts.length - 1)) : evenQty;
                        });
                    } else if (parts.length === 1) {
                        currentBatchAllocations[parts[0]] = parseInt(row.consumed_qty > 0 ? row.consumed_qty : orderQty);
                        let rowExtra = parseInt(row.shiftingqty || 0);
                        if (rowExtra > 0) currentBatchExtraAllocations[parts[0]] = rowExtra;
                    }
                }

                // Initialize Shifted Plants question state
                let isShifted = (row.isshifted === 'yes' || row.isshifted === 1 || row.isshifted === '1' || !row.isshifted);
                if (isShifted) {
                    $("#growth_shifted_yes, #shifted_yes").prop("checked", true);
                    $("#growth_shiftedPlantsCalcSection, #shiftedPlantsCalcSection").addClass("d-none");
                    $("#growth_calc_extra_percent, #calc_extra_percent").val("").prop("required", false);
                    $("#growth_calc_extra_plants, #calc_extra_plants").text("+0 Plants");
                    $("#growth_modal_extra_qty, #modal_extra_qty").val(0);
                } else {
                    $("#growth_shifted_no, #shifted_no").prop("checked", true);
                    $("#growth_shiftedPlantsCalcSection, #shiftedPlantsCalcSection").removeClass("d-none");
                    let extraPct = parseFloat(row.percentextrashifting) || 0;
                    $("#growth_calc_extra_percent, #calc_extra_percent").val(extraPct > 0 ? extraPct : "").prop("required", true);
                    let extraPlants = parseInt(row.shiftingqty) || Math.round(orderQty * (extraPct / 100));
                    $("#growth_calc_extra_plants, #calc_extra_plants").text(`+${extraPlants.toLocaleString()} Plants`);
                    $("#growth_modal_extra_qty, #modal_extra_qty").val(extraPlants);
                }

                // Load available production batches for this order
                loadAvailableStockOrdersForModal(currentModalProductId, false, stockNo, currentModalDeliveryDate);
            },
            error: function() {
                modalBody.html(`
                    <div class="alert alert-danger m-4">
                        <i class="fa fa-exclamation-circle me-2"></i> Failed to fetch order and growth details from server.
                    </div>
                `);
            }
        });
    }

    // Select All Beds in Growth Modal
    $(document).on("change", ".select-all-growth-beds", function() {
        let isChecked = $(this).is(":checked");
        $(".bed-alloc-checkbox").prop("checked", isChecked).trigger("change");
    });

    // Bed Checkbox Selection Handler to update Allocate QTY button
    $(document).on("change", ".bed-alloc-checkbox", function() {
        let totalReadyPlants = 0;
        let totalReadyTrays = 0;
        let checkedCount = 0;

        $(".bed-alloc-checkbox:checked").each(function() {
            checkedCount++;
            totalReadyPlants += parseInt($(this).data("readyplants") || 0);
            totalReadyTrays += parseInt($(this).data("readytrays") || 0);
        });

        if (checkedCount > 0) {
            $("#allocateSelectedBedsBtn").removeClass("d-none");
            $("#selectedAllocPlantsCount").text(totalReadyPlants.toLocaleString());
            $("#allocateSelectedBedsBtn").html(`<i class="fa fa-check-circle me-1"></i> Allocate QTY: <b>${totalReadyPlants.toLocaleString()} Plants</b> (${totalReadyTrays} Trays)`);
        } else {
            $("#allocateSelectedBedsBtn").addClass("d-none");
        }
    });

    // Trigger Allocation on clicking Allocate QTY Button
    $(document).on("click", "#allocateSelectedBedsBtn", function() {
        let totalReadyPlants = 0;
        let totalReadyTrays = 0;
        let stockOrderNo = '';

        $(".bed-alloc-checkbox:checked").each(function() {
            totalReadyPlants += parseInt($(this).data("readyplants") || 0);
            totalReadyTrays += parseInt($(this).data("readytrays") || 0);
            if (!stockOrderNo) {
                stockOrderNo = $(this).data("stockorderno") || '';
            }
        });

        if (lastSelectedGrowthRow) {
            previousOpenedModalBeforeAllocate = '#orderGrowthStatusModal';
            $("#orderGrowthStatusModal").modal("hide");
            openShiftedAllocationModal(lastSelectedGrowthRow, totalReadyPlants, totalReadyTrays, stockOrderNo);
        }
    });

    $(document).on("hidden.bs.modal", "#orderGrowthStatusModal", function() {
        $("#orderGrowthModalBody #growthModalAllocationCard").remove();
    });

    let previousOpenedModalBeforeAllocate = null;
    let currentModalDispatchRow = null;

    function updateModalAllocationHeaderDetails(row, selectedStockOrder = null, selectedFoundObj = null) {
        if (!row) return;

        let origBatch = row.original_sown_stockorderno || row.consumed_stockorderno || row.soi_stockorderno || '';
        if (!origBatch || origBatch === 'N/A') {
            origBatch = row.stockorder_no || row.effective_stockorderno || '';
        }
        let origPsono = row.psono || '-';
        let origClient = row.clientname || 'Current Client';
        let origSono = row.sono || '-';

        let activeStockNo = selectedStockOrder || row.poc_allocated_stockorderno || row.allocated_stockorderno || row.effective_stockorderno || origBatch;

        let allocBatch = '';
        let allocPsono = '';
        let allocClient = '';
        let allocSono = '';

        if (selectedFoundObj) {
            allocBatch = selectedFoundObj.stockorder_no || '';
            allocPsono = selectedFoundObj.psono || selectedFoundObj.allocated_psono_list || '-';
            allocClient = selectedFoundObj.sown_client_name || selectedFoundObj.allocated_client_names || selectedFoundObj.allocated_to_clients || 'Other Client';
            allocSono = selectedFoundObj.sown_sono || selectedFoundObj.allocated_sono_list || '-';
        } else {
            allocBatch = row.poc_allocated_stockorderno || row.allocated_stockorderno || '';
            allocPsono = row.allocated_psono || row.poc_allocated_psono || '-';
            allocClient = row.allocated_clientname || '';
            allocSono = row.allocated_sono || '-';

            if ((!allocClient || allocClient === '') && allocBatch && Array.isArray(cachedAvailableStockOrders)) {
                let match = cachedAvailableStockOrders.find(s => s.stockorder_no === allocBatch);
                if (match) {
                    allocClient = match.sown_client_name || match.allocated_client_names || match.allocated_to_clients || '';
                    if (!allocPsono || allocPsono === '-') allocPsono = match.psono || match.allocated_psono_list || '-';
                    if (!allocSono || allocSono === '-') allocSono = match.sown_sono || match.allocated_sono_list || '-';
                }
            }
        }

        let isAllocatedOther = (activeStockNo && origBatch && activeStockNo !== origBatch && activeStockNo !== 'Not Allocated');

        $("#modal_stockorder_badge").text(activeStockNo || 'N/A');

        if (isAllocatedOther) {
            $("#modal_stockorder_type_badge").html(`
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning px-2 py-1" style="font-size: 11px;">
                    <i class="fa fa-exchange-alt me-1"></i> Re-Allocated (Orig: ${origBatch})
                </span>
            `);
            $("#modal_alloc_batch_badge").text(activeStockNo);
            $("#modal_alloc_client_name").text(allocClient || 'Other Client');
            $("#modal_alloc_psono").text(allocPsono || '-');
            $("#modal_alloc_sono").text(allocSono || '-');

            $("#modal_orig_batch_badge").text(origBatch);
            $("#modal_orig_client_name").text(origClient || '-');
            $("#modal_orig_psono").text(origPsono || '-');
            $("#modal_orig_sono").text(origSono || '-');

            $("#modal_cross_allocation_section").removeClass("d-none");
        } else {
            let isOpenStockModal = Boolean(
                (row && (row.is_open_stock == 1 || row.stock_type_label === 'Open Stock')) ||
                activeStockNo.startsWith('RSO-') || 
                activeStockNo.startsWith('RTS-') || 
                activeStockNo.startsWith('READY-') ||
                (selectedFoundObj && (selectedFoundObj.clientid === 'C00001' || parseInt(selectedFoundObj.is_trader) === 1 || (selectedFoundObj.remarks && selectedFoundObj.remarks.toLowerCase().includes('ready stock')))) ||
                (row && (row.pso_clientid === 'C00001' || parseInt(row.pso_is_trader) === 1))
            );

            if (isOpenStockModal) {
                $("#modal_stockorder_type_badge").html(`
                    <span class="badge bg-info-subtle text-primary border border-primary-subtle px-2 py-1" style="font-size: 11px;">
                        <i class="fa fa-boxes me-1"></i> Open Stock Batch
                    </span>
                `);
            } else {
                $("#modal_stockorder_type_badge").html(`
                    <span class="badge bg-success-subtle text-success border border-success px-2 py-1" style="font-size: 11px;">
                        <i class="fa fa-check-circle me-1"></i> Own Sown Batch
                    </span>
                `);
            }
            $("#modal_cross_allocation_section").addClass("d-none");
        }
    }

    function openShiftedAllocationModal(row, readyPlants, readyTrays, stockOrderNo) {
        $("#orderGrowthModalBody #growthModalAllocationCard").remove();
        let orderQty = parseInt(row.order_qty || 0);
        let stockNo = stockOrderNo || row.effective_stockorderno || row.stockorder_no || '';
        let healthyPlants = readyPlants > 0 ? readyPlants : parseInt(row.healthy_plant_count || row.total_plants || orderQty);
        let unhealthyPlants = parseInt(row.unhealthy_plant_count || 0);

        let actualSoiId = row.real_soi_id || row.parent_soi_id || row.soi_id;
        $("#modal_soi_id").val(actualSoiId);
        $("#modal_psono").val(row.psono);
        $("#modal_sono").val(row.sono);
        $("#modal_client_id").val(row.clientid || row.client_id || '');
        $("#modal_product_id").val(row.product_id);
        $("#modal_product_name").val(row.product_name);
        $("#modal_stockorder_no").val(stockNo);
        $("#modal_client_order_qty").val(orderQty);
        $("#modal_selected_ready_plants").val(readyPlants);
        $("#modal_selected_ready_trays").val(readyTrays);
        $("#modal_healthy_plant").val(healthyPlants);
        $("#modal_unhealthy_plant").val(unhealthyPlants);

        currentModalDispatchRow = row;
        currentModalProductId = row.product_id;
        currentSelectedStockOrder = stockNo;
        currentModalDeliveryDate = row.delivery_date || '';

        // Reset multi-batch allocations and ensure Selected Stock Order Details is hidden for single batch allocation
        currentBatchAllocations = {};
        cachedAvailableStockOrders = [];
        $("#multi_alloc_batches_chips").addClass("d-none").empty();
        $("#modalStockOrderInfoContent").empty();
        $("#modalStockOrderInfo").addClass("d-none");

        if (stockNo && stockNo !== 'Not Allocated' && stockNo !== '0') {
            currentBatchAllocations[stockNo] = orderQty;
        }

        $("#modal_client_name").text(row.clientname || 'N/A');
        $("#modal_so_display").text(`SO: ${row.sono || '-'} | PSONO: ${row.psono || '-'}`);
        let modalProdName = row.allocated_product_name || row.product_name;
        if (row.allocated_product_name && row.product_name && row.allocated_product_name.trim().toLowerCase() !== row.product_name.trim().toLowerCase()) {
            $("#modal_product_display").html(`${formatProductNameDisplay(row.allocated_product_name)} <span class="text-muted small fw-normal ms-1">(Booked: ${formatProductNameDisplay(row.product_name)})</span>`);
        } else {
            $("#modal_product_display").text(formatProductNameDisplay(modalProdName));
        }
        $("#modal_qty_date_display").text(`${orderQty.toLocaleString()} Plants | Due: ${row.formatted_delivery_date || '-'}`);
        updateModalAllocationHeaderDetails(row, stockNo);
        $("#modal_ready_summary_badge").text(`${readyTrays} Trays | ${readyPlants.toLocaleString()} Plants Ready`);

        $("#calc_order_qty").text(`${orderQty.toLocaleString()} Plants`);
        $("#modalStockOrderSelectContainer").addClass("d-none");
        $("#shiftedPlantsQuestionCard").removeClass("d-none");

        // Default state: YES (shifted plants -> at allocation time extra bonus qty is 0. Free bonus plant per tray is applicable during loading & final dispatch only)
        let defaultTrays = Math.ceil(orderQty / 125);
        $("#shifted_yes").prop("checked", true);
        $("#shiftedPlantsCalcSection").addClass("d-none");
        $("#calc_extra_percent").val("").prop("required", false);
        $("#calc_extra_plants").text("+0 Plants");
        $("#modal_allocated_qty").val(orderQty);
        $("#modal_extra_qty").val(0);
        $("#modal_allocation_remark").val(`Allocated shifted plants (${orderQty.toLocaleString()} plants, ${defaultTrays} trays)`);

        $("#allocateModalLabel").html(`<i class="fa fa-clipboard-check me-2"></i> Allocate Ready Shifted Plants`);
        $("#saveAllocationBtn").html(`<i class="fa fa-check me-1"></i> Save Allocation`);
        $("#confirmAndPrepareInvoiceBtn").html(`<i class="fa fa-file-invoice me-1"></i> Confirm &amp; Prepare Invoice <i class="fa fa-arrow-right ms-1"></i>`).show();

        // Configure Back to Previous button
        if (previousOpenedModalBeforeAllocate === '#orderGrowthStatusModal' || lastSelectedGrowthRow) {
            $("#modal_back_to_prev_header_btn").html(`<i class="fa fa-arrow-left me-1"></i> Back`).attr("title", "Go back to Ready Beds / Growth Status Modal").removeClass("d-none");
            $("#modal_back_to_prev_footer_btn").html(`<i class="fa fa-arrow-left me-1"></i> Back to Ready Beds Modal`).removeClass("d-none");
        } else if (previousOpenedModalBeforeAllocate === '#prepareDispatchInvoiceModal') {
            $("#modal_back_to_prev_header_btn").html(`<i class="fa fa-arrow-left me-1"></i> Back`).attr("title", "Go back to Dispatch Invoice").removeClass("d-none");
            $("#modal_back_to_prev_footer_btn").html(`<i class="fa fa-arrow-left me-1"></i> Back to Invoice Modal`).removeClass("d-none");
        } else {
            $("#modal_back_to_prev_header_btn").html(`<i class="fa fa-arrow-left me-1"></i> Back`).attr("title", "Go back to Ready Beds / Growth Status").removeClass("d-none");
            $("#modal_back_to_prev_footer_btn").html(`<i class="fa fa-arrow-left me-1"></i> Back to Ready Beds Modal`).removeClass("d-none");
        }

        // Validate ready plants vs allocated qty immediately upon opening
        validateModalAllocationQuantities();

        $("#allocateStockOrderModal").modal("show");
    }

    function getPreviouslyAllocatedBedsMap() {
        let map = {};
        if (!currentModalDispatchRow) return map;

        let rawBeds = currentModalDispatchRow.allocatedbed || currentModalDispatchRow.rsa_allocatedbed;
        let list = [];
        if (typeof rawBeds === 'string' && rawBeds.trim()) {
            try {
                list = JSON.parse(rawBeds);
            } catch(e) {}
        } else if (Array.isArray(rawBeds)) {
            list = rawBeds;
        }

        if (Array.isArray(list)) {
            list.forEach(item => {
                let bedName = (item.bed_no || item.bed_name || item.bed || item.bedrackname || (typeof item === 'string' ? item : '')).trim();
                let bStock = (item.stockorder_no || item.stockorderno || '').trim();
                if (bedName) {
                    let key = bStock ? bStock : '_all_';
                    if (!map[key]) map[key] = new Set();
                    map[key].add(bedName.toLowerCase());
                }
            });
        }

        if (currentModalDispatchRow.allocated_batches && Array.isArray(currentModalDispatchRow.allocated_batches)) {
            currentModalDispatchRow.allocated_batches.forEach(b => {
                let bStock = (b.stockorder_no || b.stockorderno || '').trim();
                let bList = [];
                if (b.allocatedbed) {
                    if (typeof b.allocatedbed === 'string') {
                        try { bList = JSON.parse(b.allocatedbed); } catch(e) {}
                    } else if (Array.isArray(b.allocatedbed)) {
                        bList = b.allocatedbed;
                    }
                }
                bList.forEach(item => {
                    let bedName = (item.bed_no || item.bed_name || item.bed || item.bedrackname || (typeof item === 'string' ? item : '')).trim();
                    if (bedName) {
                        let key = bStock ? bStock : '_all_';
                        if (!map[key]) map[key] = new Set();
                        map[key].add(bedName.toLowerCase());
                    }
                });
            });
        }

        let bedText = currentModalDispatchRow.allocated_bed_numbers || currentModalDispatchRow.bed_numbers || '';
        if (Object.keys(map).length === 0 && bedText && bedText !== '-' && bedText !== 'N/A') {
            let names = bedText.split(',').map(s => s.trim().toLowerCase()).filter(Boolean);
            if (names.length > 0) {
                map['_all_'] = new Set(names);
            }
        }

        return map;
    }

    function setBatchBedsChecked(stockNo, isChecked) {
        let $beds = $(`.batch-bed-checkbox[data-stockno="${stockNo}"]`);
        if (!isChecked) {
            $beds.prop("checked", false);
            $beds.closest(".bed-select-pill").removeClass("bed-checked border-success-subtle bg-success-subtle text-success-emphasis");
            return;
        }

        // If beds are already checked by user in this batch, preserve them
        let alreadyCheckedCount = $beds.filter(":checked").length;
        if (alreadyCheckedCount > 0) {
            return;
        }

        let isReallocating = Boolean(currentModalDispatchRow && (
            currentModalDispatchRow.dispatch_status_code !== 'pending' &&
            currentModalDispatchRow.dispatch_status_code !== 'unallocated' &&
            currentModalDispatchRow.effective_stockorderno &&
            currentModalDispatchRow.effective_stockorderno !== 'Not Allocated' &&
            parseInt(currentModalDispatchRow.consumed_qty || 0) > 0
        ));

        let prevMap = getPreviouslyAllocatedBedsMap();
        let prevBeds = prevMap[stockNo] || prevMap['_all_'];

        if (isReallocating && prevBeds && prevBeds.size > 0) {
            // Re-allocate: check ONLY beds mentioned in previous allocation
            $beds.each(function() {
                let bName = ($(this).data("bed") || '').toString().trim().toLowerCase();
                let shouldCheck = prevBeds.has(bName);
                $(this).prop("checked", shouldCheck);
                $(this).closest(".bed-select-pill").toggleClass("bed-checked border-success-subtle bg-success-subtle text-success-emphasis", shouldCheck);
            });
        } else {
            // New allocation: default to UNCHECKED so the user can manually select bed
            $beds.prop("checked", false);
            $beds.closest(".bed-select-pill").removeClass("bed-checked border-success-subtle bg-success-subtle text-success-emphasis");
        }
    }

    function getActiveModalTargetOrderQty() {
        let isGrowthModal = $("#orderGrowthStatusModal").hasClass("show") || ($("#orderGrowthStatusModal").length && $("#orderGrowthStatusModal").is(":visible"));
        let targetQty = 0;
        if (isGrowthModal) {
            targetQty = parseInt($("#growth_modal_client_order_qty").val()) || 0;
            if (targetQty <= 0 && currentModalDispatchRow) {
                targetQty = parseInt(currentModalDispatchRow.order_qty) || 0;
            }
            if (targetQty <= 0) {
                targetQty = parseInt($("#growth_modal_allocated_qty").val()) || 0;
            }
        } else {
            targetQty = parseInt($("#modal_client_order_qty").val()) || 0;
            if (targetQty <= 0 && currentModalDispatchRow) {
                targetQty = parseInt(currentModalDispatchRow.order_qty) || 0;
            }
            if (targetQty <= 0) {
                targetQty = parseInt($("#modal_allocated_qty").val()) || 0;
            }
        }
        if (targetQty <= 0 && currentModalDispatchRow) {
            targetQty = parseInt(currentModalDispatchRow.order_qty) || 0;
        }
        return targetQty;
    }

    function syncExtraPlantsFromBatches() {
        let isShifted = $("#growth_shifted_yes").length ? $("#growth_shifted_yes").is(":checked") : $("#shifted_yes").is(":checked");
        if (isShifted) {
            $("#calc_extra_plants, #growth_calc_extra_plants").text("+0 Plants");
            $("#modal_extra_qty, #growth_modal_extra_qty").val(0);
            return;
        }
        let sumExtra = 0;
        Object.entries(currentBatchExtraAllocations).forEach(([sNo, extra]) => {
            sumExtra += (parseInt(extra) || 0);
        });
        let extraPct = parseFloat($("#growth_calc_extra_percent").length && $("#growth_calc_extra_percent").val() ? $("#growth_calc_extra_percent").val() : $("#calc_extra_percent").val()) || 0;
        let bonusText = sumExtra > 0 ? `+${sumExtra.toLocaleString()} Plants` + (extraPct > 0 ? ` (${extraPct}%)` : '') : `+0 Plants`;
        $("#calc_extra_plants, #growth_calc_extra_plants").text(bonusText);
        $("#modal_extra_qty, #growth_modal_extra_qty").val(sumExtra);
        
        let orderQty = getActiveModalTargetOrderQty();
        if (sumExtra > 0) {
            $("#modal_allocation_remark, #growth_modal_allocation_remark").val(`Allocated non-shifted trays with extra plants (${sumExtra.toLocaleString()} extra)`);
        } else {
            $("#modal_allocation_remark, #growth_modal_allocation_remark").val(`Allocated non-shifted trays (${orderQty.toLocaleString()} plants)`);
        }
        validateModalAllocationQuantities();
    }

    function recalculateExtraPlants() {
        let orderQty = getActiveModalTargetOrderQty();
        let isShifted = $("#growth_shifted_yes").length ? $("#growth_shifted_yes").is(":checked") : $("#shifted_yes").is(":checked");
        let trayCount = Math.ceil(orderQty / 125);

        if (isShifted) {
            // Shifted plants: At allocation time, extra bonus quantity is 0 (applicable only during loading & final dispatch)
            currentBatchExtraAllocations = {};
            $(".stockorder-extra-qty-input").prop("disabled", true).val("");
            $("#calc_extra_plants, #growth_calc_extra_plants").text("+0 Plants");
            $("#modal_extra_qty, #growth_modal_extra_qty").val(0);
            $("#modal_allocation_remark, #growth_modal_allocation_remark").val(`Allocated shifted plants (${orderQty.toLocaleString()} plants, ${trayCount} trays)`);
        } else {
            // Non-shifted plants: Applicable as per % extra calculation and batch table inputs
            let extraPct = parseFloat($("#growth_calc_extra_percent").length && $("#growth_calc_extra_percent").val() ? $("#growth_calc_extra_percent").val() : $("#calc_extra_percent").val()) || 0;
            let targetTotalExtra = (extraPct > 0 && orderQty > 0) ? Math.round(orderQty * (extraPct / 100)) : 0;
            
            currentBatchExtraAllocations = {};
            let remainingExtraNeeded = targetTotalExtra;
            let totalBatchExtra = 0;

            // Pass 1: Try to allocate extra plants from currently selected batches that have remaining capacity (availBal - allocQty > 0)
            let selectedEntries = Object.entries(currentBatchAllocations).filter(([sNo, allocQty]) => parseInt(allocQty) > 0);
            selectedEntries.forEach(([sNo, allocQty]) => {
                let $row = $(`#modalStockOrderGridBody, #growth_modalStockOrderGridBody`).find(`tr[data-stockno="${sNo}"]`);
                let availBal = parseInt($row.find(".stockorder-alloc-qty-input").data("availbal") || 0);
                let nAlloc = parseInt(allocQty) || 0;
                let remainingCap = Math.max(0, availBal - nAlloc);

                if (remainingCap > 0 && remainingExtraNeeded > 0) {
                    let toTake = Math.min(remainingCap, remainingExtraNeeded);
                    currentBatchExtraAllocations[sNo] = toTake;
                    remainingExtraNeeded -= toTake;
                    totalBatchExtra += toTake;
                }
            });

            // Pass 2: If extra plants are still needed (e.g. selected batch like KFP8266 is already 100% allocated),
            // automatically draw the extra plants from another available batch with stock (e.g. RSO-KFP-6884)
            if (remainingExtraNeeded > 0) {
                $("#modalStockOrderGridBody, #growth_modalStockOrderGridBody").find("tr.stockorder-grid-row").each(function() {
                    let sNo = $(this).data("stockno");
                    if (!sNo || remainingExtraNeeded <= 0) return;
                    let nAlloc = parseInt(currentBatchAllocations[sNo] || 0);
                    let alreadyGivenExtra = currentBatchExtraAllocations[sNo] || 0;
                    let availBal = parseInt($(this).find(".stockorder-alloc-qty-input").data("availbal") || 0);
                    let remainingCap = Math.max(0, availBal - nAlloc - alreadyGivenExtra);

                    if (remainingCap > 0) {
                        let toTake = Math.min(remainingCap, remainingExtraNeeded);
                        currentBatchExtraAllocations[sNo] = alreadyGivenExtra + toTake;
                        remainingExtraNeeded -= toTake;
                        totalBatchExtra += toTake;
                    }
                });
            }

            // Pass 3: Update all table rows, inputs, max constraints, and tooltips
            $("#modalStockOrderGridBody, #growth_modalStockOrderGridBody").find("tr.stockorder-grid-row").each(function() {
                let $row = $(this);
                let sNo = $row.data("stockno");
                if (!sNo) return;

                let availBal = parseInt($row.find(".stockorder-alloc-qty-input").data("availbal") || 0);
                let allocQty = parseInt(currentBatchAllocations[sNo] || 0);
                let extraQty = parseInt(currentBatchExtraAllocations[sNo] || 0);
                let remainingCap = Math.max(0, availBal - allocQty);
                let $extraInp = $row.find(".stockorder-extra-qty-input");
                let $chk = $row.find(".stockorder-table-checkbox");

                if (availBal <= 0) {
                    $extraInp.prop("disabled", true).val('').attr("title", "No plants available in this batch (0 Bal)");
                } else {
                    $extraInp.prop("disabled", false).val(extraQty > 0 ? extraQty : '').attr("max", remainingCap > 0 ? remainingCap : availBal);
                    if (remainingCap <= 0 && extraQty <= 0) {
                        $extraInp.attr("title", `Batch balance (${availBal.toLocaleString()}) is already fully allocated to base order qty`);
                    } else {
                        $extraInp.attr("title", `Extra plants for this batch (Max balance available: ${remainingCap.toLocaleString()})`);
                    }
                }

                if (allocQty > 0 || extraQty > 0) {
                    $row.addClass("stockorder-row-selected table-success");
                    $chk.prop("checked", true);
                    setBatchBedsChecked(sNo, true);
                } else {
                    $row.removeClass("stockorder-row-selected table-success");
                    $chk.prop("checked", false);
                    setBatchBedsChecked(sNo, false);
                }
            });

            let displayExtra = totalBatchExtra;
            let bonusText = extraPct > 0 ? `+${displayExtra.toLocaleString()} Plants (${extraPct}%)` : `+0 Plants`;
            $("#calc_extra_plants, #growth_calc_extra_plants").text(bonusText);
            $("#modal_extra_qty, #growth_modal_extra_qty").val(displayExtra);

            if (remainingExtraNeeded > 0) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'warning',
                    title: `Notice: Only ${totalBatchExtra.toLocaleString()} extra plants could be allocated. Total available balance across all batches is exhausted (${remainingExtraNeeded.toLocaleString()} shortfall).`,
                    timer: 5000,
                    showConfirmButton: false
                });
            }

            if (extraPct > 0) {
                let totalNonShiftedPlants = orderQty + displayExtra;
                let nonShiftedTrays = Math.ceil(totalNonShiftedPlants / 126);
                $("#modal_allocation_remark").val(`Allocated non-shifted trays with ${extraPct}% extra plants (${orderQty.toLocaleString()} + ${displayExtra.toLocaleString()} extra = ${totalNonShiftedPlants.toLocaleString()} plants, ${nonShiftedTrays} trays)`);
            } else {
                let nonShiftedTrays = Math.ceil(orderQty / 126);
                $("#modal_allocation_remark").val(`Allocated non-shifted trays (${orderQty.toLocaleString()} plants, ${nonShiftedTrays} trays)`);
            }
        }

        validateModalAllocationQuantities();
    }

    // Validate if allocated qty exceeds available ready plants from selected polyhouse beds
    function validateModalAllocationQuantities() {
        let orderQty = parseInt($("#modal_allocated_qty").val()) || 0;
        let readyPlants = parseInt($("#modal_selected_ready_plants").val()) || 0;
        let isShifted = $("#shifted_yes").is(":checked");
        let extraPlants = isShifted ? 0 : (parseInt($("#modal_extra_qty").val()) || 0);
        let totalNeeded = orderQty + extraPlants;

        let $alert = $("#modalReadyPlantsValidationAlert");
        let $saveBtn = $("#saveAllocationBtn");
        let $confirmBtn = $("#confirmAndPrepareInvoiceBtn");

        // When readyPlants > 0 (Shifted Ready Beds Mode)
        if (readyPlants > 0) {
            if (orderQty <= 0) {
                $alert.removeClass("d-none").html(`
                    <div class="p-2.5 px-3 rounded-3 shadow-xs border border-danger d-flex align-items-center gap-2" style="background: #fff1f2;">
                        <i class="fa fa-exclamation-circle text-danger fs-5"></i>
                        <span class="text-danger fw-bold">Please enter a valid order quantity greater than 0.</span>
                    </div>
                `);
                $saveBtn.prop("disabled", true).addClass("opacity-50").attr("title", "Enter a valid order quantity");
                $confirmBtn.prop("disabled", true).addClass("opacity-50").attr("title", "Enter a valid order quantity");
                return false;
            }

            if (totalNeeded > readyPlants) {
                // Shortage! Stop allocation and disable buttons
                let shortage = totalNeeded - readyPlants;
                let extraNotice = extraPlants > 0 ? ` (${orderQty.toLocaleString()} base + ${extraPlants.toLocaleString()} extra)` : '';
                $alert.removeClass("d-none").html(`
                    <div class="p-3 rounded-3 shadow-sm border border-danger d-flex align-items-center justify-content-between flex-wrap gap-2" style="background: linear-gradient(135deg, #fff1f2 0%, #fee2e2 100%);">
                        <div class="d-flex align-items-center gap-3">
                            <div class="text-danger" style="font-size: 28px; line-height: 1;"><i class="fa fa-ban"></i></div>
                            <div>
                                <div class="fw-bold text-danger fs-6 mb-0.5">
                                    <i class="fa fa-exclamation-triangle me-1"></i> Cannot Allocate: Ready Plants Shortage!
                                </div>
                                <div class="text-dark" style="font-size: 13.5px; line-height: 1.5;">
                                    Selected polyhouse bed only has <b class="text-danger font-monospace fs-6">${readyPlants.toLocaleString()} Ready Plants</b>, but required allocation is <b class="text-dark font-monospace fs-6">${totalNeeded.toLocaleString()} Plants</b>${extraNotice} (Short by <b class="text-danger">${shortage.toLocaleString()} plants</b>).
                                    <span class="d-block text-danger fw-bold mt-1">
                                        <i class="fa fa-hand-point-right me-1"></i> Allocation stopped: Please edit the Order Qty to <b>${readyPlants.toLocaleString()} plants</b> or less to enable confirmation.
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-danger fw-bold px-3 py-1.5 shadow-xs" id="btn_quick_set_ready_qty" data-ready="${readyPlants}">
                                <i class="fa fa-edit me-1"></i> Set Qty to ${readyPlants.toLocaleString()} Ready Plants
                            </button>
                        </div>
                    </div>
                `);
                $saveBtn.prop("disabled", true).addClass("opacity-50").attr("title", `Cannot allocate ${totalNeeded} plants. Only ${readyPlants} ready plants available from selected bed.`);
                $confirmBtn.prop("disabled", true).addClass("opacity-50").attr("title", `Cannot allocate ${totalNeeded} plants. Only ${readyPlants} ready plants available from selected bed.`);
                return false;
            } else {
                // Quantity is within ready plants -> Success!
                $alert.removeClass("d-none").html(`
                    <div class="p-2.5 px-3 rounded-3 shadow-xs border border-success d-flex align-items-center justify-content-between flex-wrap gap-2" style="background: #f0fdf4;">
                        <div class="d-flex align-items-center gap-2 text-dark" style="font-size: 13.5px;">
                            <i class="fa fa-check-circle text-success fs-5"></i>
                            <span>Order quantity <b>${totalNeeded.toLocaleString()} Plants</b> is fully available from selected bed (<b>${readyPlants.toLocaleString()} Ready Plants</b>).</span>
                        </div>
                        <span class="badge bg-success text-white px-2.5 py-1 fw-bold"><i class="fa fa-check me-1"></i> Ready to Confirm</span>
                    </div>
                `);
                $saveBtn.prop("disabled", false).removeClass("opacity-50").removeAttr("title");
                $confirmBtn.prop("disabled", false).removeClass("opacity-50").removeAttr("title");
                return true;
            }
        } else {
            $alert.addClass("d-none").empty();
            if (currentSelectedStockOrder) {
                let found = cachedAvailableStockOrders.find(s => s.stockorder_no === currentSelectedStockOrder);
                if (found) {
                    let availBal = parseInt(found.available_balance_qty !== undefined ? found.available_balance_qty : (found.available_balance || 0));
                    if (orderQty > 0 && availBal < orderQty) {
                        $saveBtn.prop("disabled", true).addClass("opacity-50");
                        $confirmBtn.prop("disabled", true).addClass("opacity-50");
                        return false;
                    }
                }
            }
            $saveBtn.prop("disabled", false).removeClass("opacity-50").removeAttr("title");
            $confirmBtn.prop("disabled", false).removeClass("opacity-50").removeAttr("title");
            return true;
        }
    }

    // Quick Action: Set Order Qty to Ready Plants
    $(document).on("click", "#btn_quick_set_ready_qty", function(e) {
        e.preventDefault();
        let readyQty = parseInt($(this).data("ready")) || parseInt($("#modal_selected_ready_plants").val()) || 0;
        if (readyQty > 0) {
            $("#modal_allocated_qty").val(readyQty).trigger("input");
            $("#modal_healthy_plant").val(readyQty);
        }
    });

    // Toggle Shifted Plants Allocation (YES / NO)
    $(document).on("change", 'input[name="allocate_shifted_plants"], input[name="growth_allocate_shifted_plants"]', function() {
        let val = $(this).val();
        let orderQty = getActiveModalTargetOrderQty();

        if (val === "no") {
            // NO (Non-shifted plants) -> Open section to fill extra plant details (required input)
            $("#shiftedPlantsCalcSection, #growth_shiftedPlantsCalcSection").removeClass("d-none");
            $("#calc_extra_percent, #growth_calc_extra_percent").prop("required", true).focus();
            recalculateExtraPlants();
        } else {
            // YES (Shifted plants) -> Hide extra plant option, allocate exact order qty
            $("#shiftedPlantsCalcSection, #growth_shiftedPlantsCalcSection").addClass("d-none");
            $("#calc_extra_percent, #growth_calc_extra_percent").prop("required", false).val("");
            $("#calc_extra_plants, #growth_calc_extra_plants").text("+0 Plants");
            $("#modal_extra_qty, #growth_modal_extra_qty").val(0);
            $("#modal_allocation_remark, #growth_modal_allocation_remark").val(`Allocated shifted plants (${orderQty.toLocaleString()} plants)`);
            recalculateExtraPlants();
        }
    });

    // Dynamic Recalculation on Extra Percent Change
    $(document).on("input change", "#calc_extra_percent, #growth_calc_extra_percent", function() {
        recalculateExtraPlants();
    });

    // Dynamic Recalculation on Order Qty Change (Recalculates Extra Qty readonly input)
    $(document).on("input change", "#modal_allocated_qty, #growth_modal_allocated_qty", function() {
        recalculateExtraPlants();
    });

    // Prompt Allocation or Re-allocation Options when an order has both allocated and pending quantities
    function promptAllocationOrReallocation(row, isFromGrowthModal = false) {
        if (!row) return;

        let isCancelled = Boolean(
            row.is_cancelled == 1 ||
            row.dispatch_status_code === 'cancelled' ||
            (row.item_status && row.item_status.toLowerCase() === 'cancelled') ||
            (row.soi_status && row.soi_status.toLowerCase() === 'cancelled') ||
            (row.sales_order_status && row.sales_order_status.toLowerCase() === 'cancelled') ||
            (row.pso_status && row.pso_status.toLowerCase() === 'cancelled')
        );
        if (isCancelled) {
            Swal.fire({
                icon: 'error',
                title: 'Order is Cancelled',
                text: row.cancel_reason || 'This order has been cancelled. Allocation cannot be performed.',
                confirmButtonColor: '#dc2626'
            });
            return;
        }

        let totalOrderQty = parseInt(row.total_so_order_qty !== undefined ? row.total_so_order_qty : (row.original_total_order_qty || row.order_qty || 0));
        let totalAllocQty = parseInt(row.total_so_allocated_qty !== undefined ? row.total_so_allocated_qty : (row.allocated_qty || 0));
        let totalDispQty = parseInt(row.total_so_dispatched_qty !== undefined ? row.total_so_dispatched_qty : (row.dispatched_plant_qty || 0));
        let totalPendingQty = parseInt(row.total_so_pending_qty !== undefined ? row.total_so_pending_qty : Math.max(0, totalOrderQty - (totalAllocQty + totalDispQty)));

        let hasAllocated = (totalAllocQty > 0 || (row.allocated_batches && row.allocated_batches.length > 0));
        let hasPending = (totalPendingQty > 0);

        // If order has BOTH allocated quantity AND pending quantity -> Show choice popup
        if (hasAllocated && hasPending) {
            Swal.fire({
                title: "<div class='fs-5 fw-bold text-dark'><i class='fa fa-layer-group text-primary me-2'></i>Order Allocation Options</div>",
                html: `
                    <div class="text-start">
                        <div class="card bg-light border p-2.5 mb-2 shadow-none" style="font-size: 12px;">
                            <div class="d-flex justify-content-between mb-1 pb-1 border-bottom">
                                <span class="text-muted">Client:</span>
                                <span class="fw-bold text-dark">${escapeHtml(row.clientname || 'Customer')}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 pb-1 border-bottom">
                                <span class="text-muted">Order:</span>
                                <span class="font-monospace fw-bold text-primary">${escapeHtml(row.psono || '-')}</span>
                            </div>
                            <div class="row g-2 text-center mt-1">
                                <div class="col-4">
                                    <div class="p-1.5 border rounded bg-white">
                                        <small class="text-muted d-block" style="font-size: 10.5px;">Total Order</small>
                                        <span class="badge bg-dark fs-6">${totalOrderQty.toLocaleString()}</span>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="p-1.5 border rounded bg-white">
                                        <small class="text-muted d-block" style="font-size: 10.5px;">Already Allocated</small>
                                        <span class="badge bg-success fs-6">${totalAllocQty.toLocaleString()}</span>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="p-1.5 border rounded bg-white">
                                        <small class="text-muted d-block" style="font-size: 10.5px;">Pending Qty</small>
                                        <span class="badge bg-danger fs-6">${totalPendingQty.toLocaleString()}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="p-2.5 border rounded bg-light-subtle mb-1 small text-dark" style="font-size: 12px; line-height: 1.5;">
                            <i class="fa fa-info-circle text-primary me-1"></i> This order already has <b>${totalAllocQty.toLocaleString()} plants</b> allocated and <b>${totalPendingQty.toLocaleString()} plants</b> pending.<br><br>
                            How would you like to allocate?
                        </div>
                    </div>
                `,
                icon: "question",
                width: "580px",
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonColor: "#059669",
                denyButtonColor: "#2563eb",
                cancelButtonColor: "#6c757d",
                confirmButtonText: `<i class="fa fa-object-group me-1"></i> Merge with Already Allocated (${totalOrderQty.toLocaleString()} Plants)`,
                denyButtonText: `<i class="fa fa-plus-circle me-1"></i> Go with Already Allocated (Allocate Pending ${totalPendingQty.toLocaleString()} Only)`,
                cancelButtonText: "Cancel"
            }).then((choice) => {
                if (choice.isConfirmed) {
                    // Option A: Merge with already allocated quantity -> Allocate full order
                    let mergedRow = $.extend({}, row, {
                        order_qty: totalOrderQty,
                        allocation_mode: 'merge_full',
                        target_alloc_qty: totalOrderQty
                    });
                    previousOpenedModalBeforeAllocate = isFromGrowthModal ? '#orderGrowthStatusModal' : null;
                    openAllocateModalWithData(mergedRow);
                } else if (choice.isDenied) {
                    // Option B: Go with already allocated -> Allocate only pending quantity
                    let pendingRow = $.extend({}, row, {
                        order_qty: totalPendingQty,
                        allocation_mode: 'pending_only',
                        target_alloc_qty: totalPendingQty,
                        is_partial_pending: 1
                    });
                    previousOpenedModalBeforeAllocate = isFromGrowthModal ? '#orderGrowthStatusModal' : null;
                    openAllocateModalWithData(pendingRow);
                }
            });
        } else {
            // Direct allocation / re-allocation
            previousOpenedModalBeforeAllocate = isFromGrowthModal ? '#orderGrowthStatusModal' : null;
            openAllocateModalWithData(row);
        }
    }

    // Trigger Re-allocate / Switch Batch from Inside 100% Width Growth Modal
    $(document).on("click", ".reallocate-from-growth-modal-btn", function() {
        previousOpenedModalBeforeAllocate = '#orderGrowthStatusModal';
        $("#orderGrowthStatusModal").modal("hide");
        if (lastSelectedGrowthRow) {
            promptAllocationOrReallocation(lastSelectedGrowthRow, true);
        }
    });

    // Open Allocate Modal Button in Grid Table (For switching stock orders)
    $(document).on("click", ".open-allocate-modal-btn", function() {
        let soiId = $(this).data("soiid");
        let row = $(this).data("row") || getDispatchRowBySoiId(soiId);
        if (row) {
            promptAllocationOrReallocation(row, false);
        }
    });

    function openAllocateModalWithData(row) {
        if (row) {
            let isCancelled = Boolean(
                row.is_cancelled == 1 ||
                row.dispatch_status_code === 'cancelled' ||
                (row.item_status && row.item_status.toLowerCase() === 'cancelled') ||
                (row.soi_status && row.soi_status.toLowerCase() === 'cancelled') ||
                (row.sales_order_status && row.sales_order_status.toLowerCase() === 'cancelled') ||
                (row.pso_status && row.pso_status.toLowerCase() === 'cancelled')
            );
            if (isCancelled) {
                Swal.fire({
                    icon: 'error',
                    title: 'Order is Cancelled',
                    text: row.cancel_reason || 'This order has been cancelled. Allocation cannot be performed.',
                    confirmButtonColor: '#dc2626'
                });
                return;
            }
        }
        $("#orderGrowthModalBody #growthModalAllocationCard").remove();
        let orderQty = parseInt(row.order_qty || 0);
        let stockNo = row.effective_stockorderno || row.stockorder_no || '';

        let actualSoiId = row.real_soi_id || row.parent_soi_id || row.soi_id;
        $("#modal_soi_id").val(actualSoiId);
        $("#modal_psono").val(row.psono);
        $("#modal_sono").val(row.sono);
        $("#modal_client_id").val(row.clientid || row.client_id || '');
        $("#modal_product_id").val(row.product_id);
        $("#modal_product_name").val(row.product_name);
        $("#modal_stockorder_no").val(stockNo);
        $("#modal_allocated_psono").val(row.poc_allocated_psono || '');
        $("#modal_client_order_qty").val(orderQty);
        $("#modal_selected_ready_plants").val(0);
        $("#modal_selected_ready_trays").val(0);
        $("#modal_healthy_plant").val(orderQty);
        $("#modal_unhealthy_plant").val(0);

        currentModalDispatchRow = row;
        $("#modal_client_name").text(row.clientname || 'N/A');
        $("#modal_so_display").text(`SO: ${row.sono || '-'} | PSONO: ${row.psono || '-'}`);
        let modalProdName = row.allocated_product_name || row.product_name;
        if (row.allocated_product_name && row.product_name && row.allocated_product_name.trim().toLowerCase() !== row.product_name.trim().toLowerCase()) {
            $("#modal_product_display").html(`${formatProductNameDisplay(row.allocated_product_name)} <span class="text-muted small fw-normal ms-1">(Booked: ${formatProductNameDisplay(row.product_name)})</span>`);
        } else {
            $("#modal_product_display").text(formatProductNameDisplay(modalProdName));
        }
        $("#modal_qty_date_display").text(`${orderQty.toLocaleString()} Plants | Due: ${row.formatted_delivery_date || '-'}`);
        updateModalAllocationHeaderDetails(row, stockNo);
        $("#modal_ready_summary_badge").text(`${orderQty.toLocaleString()} Plants`);
        let baseAllocQty = parseInt(row.consumed_qty) > 0 ? parseInt(row.consumed_qty) : orderQty;
        $("#modal_allocated_qty").val(baseAllocQty > 0 ? baseAllocQty : orderQty);
        $("#modal_extra_qty").val(row.shiftingqty || 0);
        $("#modal_allocation_remark").val(row.consumed_remark || '');

        // Initialize multi-batch allocation tracking map
        currentBatchAllocations = {};
        currentBatchExtraAllocations = {};
        cachedAvailableStockOrders = [];
        $("#multi_alloc_batches_chips").addClass("d-none").empty();
        $("#modalStockOrderInfoContent").empty();
        $("#modalStockOrderInfo").addClass("d-none");
        currentModalProductId = row.product_id;
        currentSelectedStockOrder = stockNo;
        currentModalDeliveryDate = row.delivery_date || '';

        $("#modalAllocationModeNotice").remove();
        if (row.allocation_mode === 'merge_full') {
            $("#allocateStockOrderModal .modal-body").prepend(`
                <div id="modalAllocationModeNotice" class="alert alert-success py-2 px-3 small mb-2 border rounded shadow-2xs">
                    <i class="fa fa-object-group text-success me-1"></i> <b>Merge Mode:</b> Allocating the full combined order quantity of <b>${orderQty.toLocaleString()} Plants</b>.
                </div>
            `);
        } else if (row.allocation_mode === 'pending_only') {
            $("#allocateStockOrderModal .modal-body").prepend(`
                <div id="modalAllocationModeNotice" class="alert alert-warning py-2 px-3 small mb-2 border rounded shadow-2xs">
                    <i class="fa fa-plus-circle text-warning-emphasis me-1"></i> <b>Allocate Pending Only:</b> Allocating <b>${orderQty.toLocaleString()} Pending Plants</b>. Existing allocated plants remain intact.
                </div>
            `);
        }

        let isPendingTrancheA = Boolean(row.is_partial_pending === 1 || row.dispatch_status_code === 'pending' || row.dispatch_status_code === 'unallocated' || row.allocation_mode === 'pending_only');
        if (isPendingTrancheA) {
            currentBatchAllocations = {};
            currentBatchExtraAllocations = {};
        } else if (row.allocated_batches && Array.isArray(row.allocated_batches) && row.allocated_batches.length > 0) {
            row.allocated_batches.forEach(b => {
                let bNo = b.stockorder_no || b.stockorderno;
                let bQty = parseInt(b.allocated_qty || b.consumed_qty || 0);
                if (bNo && bQty > 0) {
                    currentBatchAllocations[bNo] = bQty;
                    let bExtra = parseInt(b.extra_qty !== undefined ? b.extra_qty : (b.extraqtyforshifting !== undefined ? b.extraqtyforshifting : (b.shiftingqty !== undefined ? b.shiftingqty : 0))) || 0;
                    if (bExtra > 0) currentBatchExtraAllocations[bNo] = bExtra;
                }
            });
        } else if (stockNo && stockNo !== 'Not Allocated' && stockNo !== '0') {
            let parts = stockNo.split(',').map(s => s.trim()).filter(Boolean);
            if (parts.length > 1) {
                let evenQty = Math.floor(orderQty / parts.length);
                parts.forEach((p, idx) => {
                    currentBatchAllocations[p] = (idx === parts.length - 1) ? (orderQty - evenQty * (parts.length - 1)) : evenQty;
                });
            } else if (parts.length === 1) {
                currentBatchAllocations[parts[0]] = parseInt(row.consumed_qty > 0 ? row.consumed_qty : orderQty);
                let rowExtra = parseInt(row.shiftingqty || 0);
                if (rowExtra > 0) currentBatchExtraAllocations[parts[0]] = rowExtra;
            }
        }

        $("#calc_order_qty").text(`${orderQty.toLocaleString()} Plants`);
        $("#shiftedPlantsQuestionCard").removeClass("d-none");
        $("#modalStockOrderSelectContainer").removeClass("d-none");
        $("#modal_allow_all_products_toggle, #growth_modal_allow_all_products_toggle").prop("checked", false);

        // Initialize Shifted Plants question state based on row data
        let isShifted = (row.isshifted === 'yes' || row.isshifted === 1 || row.isshifted === '1' || !row.isshifted);
        if (isShifted) {
            $("#shifted_yes").prop("checked", true);
            $("#shiftedPlantsCalcSection").addClass("d-none");
            $("#calc_extra_percent").val("").prop("required", false);
            $("#calc_extra_plants").text("+0 Plants");
            $("#modal_extra_qty").val(0);
        } else {
            $("#shifted_no").prop("checked", true);
            $("#shiftedPlantsCalcSection").removeClass("d-none");
            let extraPct = parseFloat(row.percentextrashifting) || 0;
            $("#calc_extra_percent").val(extraPct > 0 ? extraPct : "").prop("required", true);
            let extraPlants = parseInt(row.shiftingqty) || Math.round(orderQty * (extraPct / 100));
            $("#calc_extra_plants").text(`+${extraPlants.toLocaleString()} Plants`);
            $("#modal_extra_qty").val(extraPlants);
        }

        // Immediately initialize allocation metrics and UI state before async load
        updateMultiBatchAllocationState();

        currentModalDeliveryDate = row.delivery_date || '';
        loadAvailableStockOrdersForModal(row.product_id, false, stockNo, currentModalDeliveryDate);

        let isPending = (row.dispatch_status_code === 'pending' || row.dispatch_status_code === 'unallocated' || !row.effective_stockorderno || row.effective_stockorderno === 'Not Allocated' || row.consumed_qty <= 0);
        if (isPending) {
            $("#allocateModalLabel").html(`<i class="fa fa-clipboard-check me-2"></i> Allocate &amp; Confirm Order for Prepared Invoice`);
            $("#saveAllocationBtn").html(`<i class="fa fa-check me-1"></i> Save Allocation`);
            $("#confirmAndPrepareInvoiceBtn").html(`<i class="fa fa-file-invoice me-1"></i> Confirm &amp; Prepare Invoice <i class="fa fa-arrow-right ms-1"></i>`).show();
        } else {
            $("#allocateModalLabel").html(`<i class="fa fa-exchange-alt me-2"></i> Re-Allocate / Replace Production Batch`);
            $("#saveAllocationBtn").html(`<i class="fa fa-save me-1"></i> Save Re-Allocation`);
            $("#confirmAndPrepareInvoiceBtn").html(`<i class="fa fa-file-invoice me-1"></i> Save &amp; Go to Invoice <i class="fa fa-arrow-right ms-1"></i>`).show();
        }

        // Configure Back to Previous button
        if (previousOpenedModalBeforeAllocate === '#orderGrowthStatusModal' || lastSelectedGrowthRow) {
            $("#modal_back_to_prev_header_btn").html(`<i class="fa fa-arrow-left me-1"></i> Back`).attr("title", "Go back to Ready Beds / Growth Status Modal").removeClass("d-none");
            $("#modal_back_to_prev_footer_btn").html(`<i class="fa fa-arrow-left me-1"></i> Back to Ready Beds Modal`).removeClass("d-none");
        } else if (previousOpenedModalBeforeAllocate === '#prepareDispatchInvoiceModal') {
            $("#modal_back_to_prev_header_btn").html(`<i class="fa fa-arrow-left me-1"></i> Back`).attr("title", "Go back to Dispatch Invoice").removeClass("d-none");
            $("#modal_back_to_prev_footer_btn").html(`<i class="fa fa-arrow-left me-1"></i> Back to Invoice Modal`).removeClass("d-none");
        } else {
            $("#modal_back_to_prev_header_btn").html(`<i class="fa fa-arrow-left me-1"></i> Back`).attr("title", "Go back to Ready Beds / Growth Status").removeClass("d-none");
            $("#modal_back_to_prev_footer_btn").html(`<i class="fa fa-arrow-left me-1"></i> Back to Ready Beds Modal`).removeClass("d-none");
        }

        validateModalAllocationQuantities();
        $("#allocateStockOrderModal").modal("show");
    }

    let currentBatchAllocations = {};
    let currentBatchExtraAllocations = {};
    let currentModalProductId = '';
    let currentSelectedStockOrder = '';
    let currentModalDeliveryDate = '';

    function loadAvailableStockOrdersForModal(productId, allowAll = false, currentStockOrder = '', deliveryDate = '') {
        currentModalProductId = productId;
        if (currentStockOrder) currentSelectedStockOrder = currentStockOrder;
        if (deliveryDate) currentModalDeliveryDate = deliveryDate;
        let projectid = $("body").data("projectid") || (typeof CURRENT_APP_PROJECTID !== 'undefined' && CURRENT_APP_PROJECTID ? CURRENT_APP_PROJECTID : 17);
        let $tbody = $("#modalStockOrderGridBody, #growth_modalStockOrderGridBody");
        $tbody.html(`
            <tr>
                <td colspan="12" class="text-center py-4 text-muted">
                    <i class="fa fa-spinner fa-spin me-2 text-primary"></i> Loading available production stock orders...
                </td>
            </tr>
        `);
        $("#modal_stockorder_grid_search, #growth_modal_stockorder_grid_search").val("");
        $("#modalStockOrderInfo, #growth_modalStockOrderInfo").addClass("d-none");

        let isGrowthModal = $("#orderGrowthStatusModal").hasClass("show") || ($("#orderGrowthStatusModal").length && $("#orderGrowthStatusModal").is(":visible"));
        let reqClientId = isGrowthModal
            ? ($("#growth_modal_client_id").val() || (currentModalDispatchRow ? (currentModalDispatchRow.clientid || currentModalDispatchRow.client_id) : '') || $("#modal_client_id").val())
            : ($("#modal_client_id").val() || (currentModalDispatchRow ? (currentModalDispatchRow.clientid || currentModalDispatchRow.client_id) : '') || $("#growth_modal_client_id").val());
        let reqPsono = isGrowthModal
            ? ($("#growth_modal_psono").val() || (currentModalDispatchRow ? currentModalDispatchRow.psono : '') || $("#modal_psono").val())
            : ($("#modal_psono").val() || (currentModalDispatchRow ? currentModalDispatchRow.psono : '') || $("#growth_modal_psono").val());
        let reqSono = isGrowthModal
            ? ($("#growth_modal_sono").val() || (currentModalDispatchRow ? currentModalDispatchRow.sono : '') || $("#modal_sono").val())
            : ($("#modal_sono").val() || (currentModalDispatchRow ? currentModalDispatchRow.sono : '') || $("#growth_modal_sono").val());

        $.ajax({
            url: "modal/finaldispatch_modal.php?action=GetAvailableStockOrdersForProduct",
            type: "POST",
            data: {
                projectid: projectid,
                product_id: productId,
                allow_all_products: allowAll ? 1 : 0,
                delivery_date: currentModalDeliveryDate,
                current_stockorder: currentSelectedStockOrder,
                client_id: reqClientId,
                psono: reqPsono,
                sono: reqSono
            },
            dataType: "json",
            success: function(res) {
                cachedAvailableStockOrders = res.stock_orders || [];
                renderStockOrderGrid(cachedAvailableStockOrders);
                updateMultiBatchAllocationState();
                let isShiftedNo = $("#growth_shifted_no").length ? $("#growth_shifted_no").is(":checked") : $("#shifted_no").is(":checked");
                if (isShiftedNo) {
                    if (Object.keys(currentBatchExtraAllocations).length === 0 && Object.keys(currentBatchAllocations).length > 0) {
                        recalculateExtraPlants();
                    } else {
                        syncExtraPlantsFromBatches();
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error("Failed to load stock orders:", status, error, xhr.responseText);
                $tbody.html(`
                    <tr>
                        <td colspan="12" class="text-center py-3 text-danger">
                            <i class="fa fa-exclamation-triangle me-1"></i> Failed to load stock orders. Please try again.
                        </td>
                    </tr>
                `);
            }
        });
    }

    function renderStockOrderGrid(list) {
        let $tbody = $("#modalStockOrderGridBody, #growth_modalStockOrderGridBody");
        $tbody.empty();
        $("#modal_stockorder_grid_count, #growth_modal_stockorder_grid_count").text(`${list ? list.length : 0} Batches`);

        if (!list || list.length === 0) {
            $tbody.html(`
                <tr>
                    <td colspan="12" class="text-center py-4 text-muted">
                        <i class="fa fa-info-circle me-1"></i> No matching production stock orders found.
                    </td>
                </tr>
            `);
            return;
        }

        let isShiftedYes = $("#growth_shifted_yes").length ? $("#growth_shifted_yes").is(":checked") : $("#shifted_yes").is(":checked");

        list.forEach(item => {
            let sNo = item.stockorder_no;
            let currentAlloc = parseInt(currentBatchAllocations[sNo] || 0);
            let currentExtra = parseInt(currentBatchExtraAllocations[sNo] || 0);
            let isOwnOrder = (parseInt(item.is_own_production_order || 0) === 1);

            // Polyhouse Plant Counts
            let phTotalPlants = parseInt(item.ph_total_plants || 0);
            let phAvailPlants = parseInt(item.ph_avail_plants !== undefined ? item.ph_avail_plants : (item.polyhouse_ready_qty || 0));
            let phConsumedPlants = parseInt(item.ph_consumed_plants || 0);

            // Sowing Status Resolution
            let rawSowStatus = (item.sowingstatus || '').toLowerCase().trim();
            let schedStatus = (item.sowing_schedule_status || '').toLowerCase().trim();
            let isSowingPending = (
                rawSowStatus === 'pending' ||
                rawSowStatus === 'unconfirmsowing' ||
                schedStatus === 'pending' ||
                (!rawSowStatus && parseInt(item.actual_sown_plants || 0) <= 0 && phTotalPlants <= 0)
            );

            let totalQty = parseInt(item.total_qty || 0);
            let assignedQty = parseInt(item.assigned_qty !== undefined ? item.assigned_qty : (item.total_allocated_qty || 0));
            let consumedRemovedQty = parseInt(item.consumed_removed_qty !== undefined ? item.consumed_removed_qty : (item.ph_consumed_plants || 0));
            let availQty = parseInt(item.available_balance_qty !== undefined ? item.available_balance_qty : (item.available_balance || 0));
            let isSwap = (item.allocated_to_clients && item.allocated_to_clients.trim() !== '');

            // Plant is available in PH only if both ph_total_plants > 0 and ph_avail_plants > 0 in krf_polyhouse_plant_details
            let isPlantInPolyhouse = (phTotalPlants > 0 && phAvailPlants > 0);

            // Disable checkbox and allocation inputs if batch is not available in polyhouse, or sowing is pending, or avail plants <= 0
            let isBatchDisabled = (!isPlantInPolyhouse || (isOwnOrder && isSowingPending) || availQty <= 0);
            let disabledReason = '';
            if (!isPlantInPolyhouse || phTotalPlants <= 0 || phAvailPlants <= 0) {
                disabledReason = 'Plant not available in PH.';
            } else if (isOwnOrder && isSowingPending) {
                disabledReason = 'Cannot allocate: Sowing is pending in Polyhouse for this order';
            } else if (availQty <= 0) {
                disabledReason = 'Cannot allocate: 0 balance available (Already fully assigned or consumed)';
            }

            // If batch is disabled, clear any pre-selected allocation
            if (isBatchDisabled) {
                if (currentAlloc > 0) {
                    delete currentBatchAllocations[sNo];
                    delete currentBatchExtraAllocations[sNo];
                    currentAlloc = 0;
                    currentExtra = 0;
                }
            }

            let isSelected = !isBatchDisabled && (currentAlloc > 0);

            let allocClientHtml = '';
            if (isSwap) {
                if (item.allocated_orders_details && Array.isArray(item.allocated_orders_details) && item.allocated_orders_details.length > 0) {
                    let detailsHtml = item.allocated_orders_details.map(od => {
                        let orderBadge = od.order_no ? `<span class="badge bg-white text-primary border font-monospace px-1 py-0 shadow-2xs" style="font-size: 9.5px;" title="Order No">${escapeHtml(od.order_no)}</span>` : '';
                        let ordQtyFormatted = parseInt(od.order_qty || 0).toLocaleString();
                        let allocQtyFormatted = parseInt(od.alloc_qty || 0).toLocaleString();
                        let qtyInfo = `<div class="d-flex align-items-center gap-1 text-secondary mt-0.5" style="font-size: 9.5px;">
                            <span>Ord: <b class="text-dark font-monospace">${ordQtyFormatted}</b></span>
                            <span class="text-muted">|</span>
                            <span>Alloc: <b class="text-success font-monospace">${allocQtyFormatted}</b></span>
                        </div>`;
                        return `<div class="p-1 rounded bg-white bg-opacity-80 border border-warning-subtle mb-1" style="line-height: 1.25;">
                            <div class="d-flex align-items-center flex-wrap gap-1">
                                <span class="fw-bold text-dark" style="font-size: 10px;">${escapeHtml(od.client_name)}</span>
                                ${orderBadge}
                            </div>
                            ${qtyInfo}
                        </div>`;
                    }).join('');

                    allocClientHtml = `
                        <div class="mt-1 p-1.5 rounded-2 bg-warning-subtle border border-warning shadow-2xs text-wrap text-start" style="max-width: 250px;" title="${escapeHtml(item.allocated_to_clients || '')}">
                            <div class="text-warning-emphasis fw-bold mb-1 d-flex align-items-center" style="font-size: 10px;">
                                <i class="fa fa-exchange-alt me-1"></i> Allocated Orders (${item.allocated_orders_details.length}):
                            </div>
                            ${detailsHtml}
                        </div>
                    `;
                } else {
                    allocClientHtml = `<div class="mt-1"><span class="badge bg-warning-subtle text-dark border border-warning text-wrap text-start d-inline-block" style="max-width: 230px; font-size: 10px;" title="${escapeHtml(item.allocated_to_clients || '')}"><i class="fa fa-exchange-alt me-1 text-warning-emphasis"></i> ${escapeHtml(item.allocated_to_clients || '')}</span></div>`;
                }
            } else {
                allocClientHtml = `<div class="mt-1"><span class="badge bg-success-subtle text-success border border-success d-inline-block" style="font-size: 10px;"><i class="fa fa-check-circle me-1"></i> Open Stock</span></div>`;
            }

            let notInPhBadge = '';
            if (!isPlantInPolyhouse || phTotalPlants <= 0 || phAvailPlants <= 0) {
                notInPhBadge = `<div class="mt-1"><span class="badge bg-danger-subtle text-danger border border-danger shadow-2xs" style="font-size: 10px;" title="Cannot allocate: Plant not available in Polyhouse"><i class="fa fa-times-circle me-1"></i> Plant not available in PH.</span></div>`;
            }

            let sowStatusText = item.sowing_schedule_status 
                ? (item.sowing_schedule_status.charAt(0).toUpperCase() + item.sowing_schedule_status.slice(1)) 
                : (item.sowingstatus ? (item.sowingstatus.charAt(0).toUpperCase() + item.sowingstatus.slice(1)) : 'Pending');

            let ownOrderBadge = '';
            if (isOwnOrder) {
                if (isSowingPending) {
                    let teditRemarkHtml = '';
                    if (item.sowing_schedule_teditremark && item.sowing_schedule_teditremark.trim() !== '') {
                        teditRemarkHtml = `<div class="text-danger fw-semibold mt-0.5" style="font-size: 10px;" title="Sowing Remark: ${escapeHtml(item.sowing_schedule_teditremark)}"><i class="fa fa-info-circle me-1"></i>${escapeHtml(item.sowing_schedule_teditremark)}</div>`;
                    } else if (item.sowing_schedule_remarks && item.sowing_schedule_remarks.trim() !== '' && item.sowing_schedule_remarks !== 'Created with new production stock order') {
                        teditRemarkHtml = `<div class="text-danger-emphasis mt-0.5" style="font-size: 10px;" title="${escapeHtml(item.sowing_schedule_remarks)}"><i class="fa fa-info-circle me-1"></i>${escapeHtml(item.sowing_schedule_remarks)}</div>`;
                    }
                    ownOrderBadge = `<div class="mt-1">
                        <span class="badge bg-danger text-white shadow-2xs" style="font-size: 10px;" title="Sowing Schedule Status: ${escapeHtml(sowStatusText)}"><i class="fa fa-exclamation-triangle me-1"></i> Own Order (Sowing: ${escapeHtml(sowStatusText)})</span>
                        ${teditRemarkHtml}
                    </div>`;
                } else {
                    ownOrderBadge = `<div class="mt-1"><span class="badge bg-success text-white shadow-2xs" style="font-size: 10px;" title="Client's Own Production Order"><i class="fa fa-star me-1 text-warning"></i> Own Production Order</span></div>`;
                }
            }

            let daysDiff = null;
            let isOpenStockOrReady = Boolean((item.stockorder_no && (item.stockorder_no.startsWith('RSO-') || item.stockorder_no.startsWith('RTS-') || item.stockorder_no.startsWith('READY-'))) || phAvailPlants > 0);
            let isDateEligible = true;
            if (currentModalDeliveryDate && item.expected_delivery_date) {
                let d1 = new Date(currentModalDeliveryDate);
                let d2 = new Date(item.expected_delivery_date);
                let timeDiff = Math.abs(d2.getTime() - d1.getTime());
                daysDiff = Math.round(timeDiff / (1000 * 3600 * 24));
                isDateEligible = (daysDiff <= 10) || isOpenStockOrReady;
            }

            let diffBadge = '';
            if (isOpenStockOrReady) {
                diffBadge = `<div class="mt-1"><span class="badge bg-success-subtle text-success border border-success" style="font-size: 10px;" title="Ready Stock in Polyhouse"><i class="fa fa-seedling me-1"></i> Ready Stock (${daysDiff !== null ? daysDiff + 'd' : 'Ready'})</span></div>`;
            } else if (daysDiff !== null) {
                diffBadge = isDateEligible
                    ? `<div class="mt-1"><span class="badge bg-success-subtle text-success border border-success" style="font-size: 10px;" title="Expected delivery date difference is ${daysDiff} days (Eligible)"><i class="fa fa-calendar-check me-1"></i> Diff: ${daysDiff}d</span></div>`
                    : `<div class="mt-1"><span class="badge bg-danger-subtle text-danger border border-danger" style="font-size: 10px;" title="Expected delivery date difference is ${daysDiff} days (Exceeds 10-day limit)"><i class="fa fa-exclamation-triangle me-1"></i> Diff: ${daysDiff}d (>10d)</span></div>`;
            }

            let traderOrFarmerBadge = '';
            if (parseInt(item.is_trader || 0) === 1 || (item.stockorder_no && (item.stockorder_no.startsWith('RSO') || item.stockorder_no.startsWith('RTS')))) {
                traderOrFarmerBadge = `<div class="mt-1"><span class="badge bg-info-subtle text-info-emphasis border border-info px-1.5 py-0.5" style="font-size: 9.5px;" title="Trader / Open Stock Pool: Multi-batch splitting allowed"><i class="fa fa-boxes me-1 text-info"></i>Trader / Pool Batch</span></div>`;
            } else if (!isOwnOrder) {
                let fOwner = item.sown_client_name || item.clientid || 'Farmer';
                traderOrFarmerBadge = `<div class="mt-1"><span class="badge bg-warning-subtle text-warning-emphasis border border-warning px-1.5 py-0.5" style="font-size: 9.5px;" title="Farmer Dedicated Batch: 1-to-1 Switch Only"><i class="fa fa-user-lock me-1 text-warning-emphasis"></i>Farmer Batch (${escapeHtml(fOwner)} - 1-to-1 Switch)</span></div>`;
            }

            // Sanitize bed numbers string (filter out batch numbers and placeholders)
            let cleanBedNumbers = (item.bed_numbers || '')
                .split(',')
                .map(s => s.trim())
                .filter(b => {
                    if (!b) return false;
                    if (sNo && b.toLowerCase() === sNo.toLowerCase()) return false;
                    if (item.psono && b.toLowerCase() === item.psono.toLowerCase()) return false;
                    if (/^(RSO|KFP|KF-PS|PSO|KF-|SO-|PO-|BATCH-)/i.test(b)) return false;
                    if (['n/a', 'na', 'none', '-', 'bed', 'null', 'undefined', 'karjee'].includes(b.toLowerCase())) return false;
                    return true;
                })
                .join(', ');

            // Sanitize available_beds (filter out batch numbers and placeholders)
            let validBeds = (item.available_beds && Array.isArray(item.available_beds)) ? item.available_beds.filter(bed => {
                let b = (bed.bed_no || bed.bed_name || '').trim();
                if (!b) return false;
                if (sNo && b.toLowerCase() === sNo.toLowerCase()) return false;
                if (item.psono && b.toLowerCase() === item.psono.toLowerCase()) return false;
                if (/^(RSO|KFP|KF-PS|PSO|KF-|SO-|PO-|BATCH-)/i.test(b)) return false;
                if (['n/a', 'na', 'none', '-', 'bed', 'null', 'undefined', 'karjee'].includes(b.toLowerCase())) return false;
                return true;
            }) : [];

            let isReallocating = Boolean(currentModalDispatchRow && (
                currentModalDispatchRow.dispatch_status_code !== 'pending' &&
                currentModalDispatchRow.dispatch_status_code !== 'unallocated' &&
                currentModalDispatchRow.effective_stockorderno &&
                currentModalDispatchRow.effective_stockorderno !== 'Not Allocated' &&
                parseInt(currentModalDispatchRow.consumed_qty || 0) > 0
            ));
            let prevMap = getPreviouslyAllocatedBedsMap();
            let prevBeds = prevMap[sNo] || prevMap['_all_'];

            let allocatedBedsColHtml = '';
            if (validBeds.length > 0) {
                allocatedBedsColHtml = `<div class="d-flex flex-wrap gap-1 align-items-center batch-beds-wrapper" data-stockno="${sNo}">`;
                validBeds.forEach((bed, bIdx) => {
                    let bedCheckId = `bed_${sNo.replace(/[^a-zA-Z0-9]/g, '_')}_${bIdx}`;
                    let isBedChecked = false;
                    if (isReallocating && isSelected && prevBeds && prevBeds.size > 0) {
                        let bName = (bed.bed_no || bed.bed_name || '').toString().trim().toLowerCase();
                        isBedChecked = prevBeds.has(bName);
                    }
                    let dispBedNo = (bed.bed_no || bed.bed_name || '').trim();
                    allocatedBedsColHtml += `
                        <label class="bed-select-pill ${isBedChecked ? 'bed-checked border-success-subtle bg-success-subtle text-success-emphasis' : ''}" for="${bedCheckId}">
                            <input type="checkbox" class="batch-bed-checkbox" 
                                id="${bedCheckId}"
                                data-stockno="${sNo}"
                                data-polyhouseid="${bed.polyhouse_id || ''}"
                                data-polyhouse="${bed.polyhouse_name || ''}" 
                                data-bed="${dispBedNo}" 
                                data-trayid="${bed.tray_id || 2}"
                                data-cavity="${bed.cavity_count || 126}"
                                data-trays="${bed.tray_count || 0}" 
                                data-plants="${bed.healthy_plants || 0}" 
                                ${isBedChecked ? 'checked' : ''}
                                ${isBatchDisabled ? 'disabled' : ''}>
                            <span class="fw-bold">${bed.polyhouse_name ? bed.polyhouse_name + ' - ' : ''}${dispBedNo}</span>
                            ${(bed.tray_count > 0 || bed.healthy_plants > 0) ? `<span class="text-success fw-bold font-monospace">(${bed.tray_count}T / ${parseInt(bed.healthy_plants || 0).toLocaleString()}P)</span>` : ''}
                        </label>
                    `;
                });
                allocatedBedsColHtml += `</div>`;
            } else if (cleanBedNumbers !== '') {
                allocatedBedsColHtml = `<span class="badge bg-light text-dark border px-2 py-1" style="font-size: 11px;"><i class="fa fa-th-large me-1 text-success"></i> ${escapeHtml(cleanBedNumbers)}</span>`;
            } else {
                allocatedBedsColHtml = `<span class="badge bg-light text-muted border px-2 py-1" style="font-size: 11px;"><i class="fa fa-minus me-1 text-secondary"></i> Not Assigned</span>`;
            }

            // Row styling: Own Order with Sowing Pending = Light Red (#fef2f2 / #ef4444); Sown Own Order = Light Green (#ecfdf5 / #10b981)
            let rowStyle = '';
            if (!isPlantInPolyhouse || phTotalPlants <= 0 || phAvailPlants <= 0) {
                rowStyle = `background-color: #fff5f5 !important; opacity: 0.82;`;
            } else if (isOwnOrder) {
                if (isSowingPending) {
                    rowStyle = `background-color: #fef2f2 !important; border-left: 5px solid #ef4444 !important;`;
                } else {
                    rowStyle = `background-color: #ecfdf5 !important; border-left: 5px solid #10b981 !important;`;
                }
            } else if (isBatchDisabled) {
                rowStyle = `opacity: 0.85;`;
            }

            let rowHtml = `
                <tr class="stockorder-grid-row ${isSelected ? 'stockorder-row-selected table-success' : ''}" style="${rowStyle}" data-stockno="${sNo}" data-sowingpending="${isSowingPending ? '1' : '0'}" data-batchdisabled="${isBatchDisabled ? '1' : '0'}">
                    <td class="text-center align-middle" style="width: 55px; min-width: 55px; text-align: center; vertical-align: middle;">
                        <div class="d-flex justify-content-center align-items-center w-100" style="margin: 0; padding: 0;">
                            <input type="checkbox" class="form-check-input stockorder-table-checkbox" 
                                value="${sNo}" 
                                data-stockno="${sNo}" 
                                data-availbal="${availQty}" 
                                data-phavail="${phAvailPlants}"
                                ${isSelected ? 'checked' : ''} 
                                ${isBatchDisabled ? 'disabled' : ''}
                                title="${isBatchDisabled ? escapeHtml(disabledReason) : 'Select batch for allocation'}"
                                style="cursor: ${isBatchDisabled ? 'not-allowed' : 'pointer'}; width: 18px; height: 18px; margin: 0; float: none; position: static; display: block; ${isBatchDisabled ? 'opacity: 0.35;' : ''}">
                        </div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center flex-wrap gap-1">
                            <span class="badge bg-primary fs-6 font-monospace">${sNo}</span>
                            ${item.sown_order_no ? `<span class="badge bg-light text-secondary border font-monospace px-1.5 py-0.5" style="font-size: 10px;" title="Originally Sown for Order: ${escapeHtml(item.sown_order_no)}"><i class="fa fa-file-invoice me-0.5 text-muted"></i>${escapeHtml(item.sown_order_no)}</span>` : ''}
                            ${ownOrderBadge}
                        </div>
                        ${traderOrFarmerBadge}
                        ${allocClientHtml}
                        ${notInPhBadge}
                    </td>
                    <td class="fw-bold text-dark text-wrap align-middle" style="max-width: 180px;">${item.clean_product_name || item.product_name}</td>
                    <td class="text-end align-middle" style="min-width: 105px;">
                        <div class="d-flex flex-column align-items-end gap-1">
                            <span class="badge bg-secondary-subtle text-secondary border px-1.5 py-0.5" style="font-size: 11px;" title="Production Order Sowing Target: ${totalQty.toLocaleString()}${item.sown_order_qty && parseInt(item.sown_order_qty) !== totalQty ? ' | Client Order Qty: ' + parseInt(item.sown_order_qty).toLocaleString() : ''}">
                                Ord: <b>${totalQty.toLocaleString()}</b>
                            </span>
                            <span class="badge bg-info-subtle text-info-emphasis border px-1.5 py-0.5" style="font-size: 11px;" title="Total Plants in Polyhouse">
                                PH: <b>${phTotalPlants.toLocaleString()}</b>
                            </span>
                        </div>
                    </td>
                    <td class="text-end fw-semibold align-middle ${assignedQty > 0 ? 'text-primary' : 'text-muted'}" style="min-width: 90px; font-size: 12.5px;" title="Assigned / Confirmed quantity to other orders">
                        ${assignedQty.toLocaleString()}
                    </td>
                    <td class="text-end fw-semibold align-middle ${consumedRemovedQty > 0 ? 'text-secondary' : 'text-muted'}" style="min-width: 105px; font-size: 12.5px;" title="Consumed or Removed from Polyhouse: ${consumedRemovedQty.toLocaleString()} plants">
                        ${consumedRemovedQty.toLocaleString()}
                    </td>
                    <td class="text-end fw-bold align-middle ${availQty > 0 ? 'text-success' : 'text-danger'}" style="min-width: 95px; font-size: 13px;">
                        ${availQty.toLocaleString()}
                        ${(!isPlantInPolyhouse || phTotalPlants <= 0 || phAvailPlants <= 0) 
                            ? '<div class="badge bg-danger-subtle text-danger border border-danger px-1 py-0.5 mt-0.5" style="font-size: 9.5px;" title="Plant not available in Polyhouse"><i class="fa fa-times-circle me-0.5"></i>Not in PH</div>' 
                            : (availQty <= 0 ? '<div class="badge bg-danger-subtle text-danger border border-danger px-1 py-0.5 mt-0.5" style="font-size: 9.5px;"><i class="fa fa-ban me-0.5"></i>0 Bal</div>' : '')}
                    </td>
                    <td class="text-center align-middle" style="width: 140px; min-width: 130px;">
                        <div class="input-group input-group-sm" style="max-width: 135px; margin: 0 auto;">
                            <input type="number" class="form-control form-control-sm text-end fw-bold font-monospace stockorder-alloc-qty-input" 
                                data-stockno="${sNo}" 
                                data-availbal="${availQty}" 
                                data-phavail="${phAvailPlants}"
                                min="0" 
                                value="${currentAlloc > 0 ? currentAlloc : ''}" 
                                placeholder="0" 
                                ${isBatchDisabled ? 'disabled' : ''}
                                title="${isBatchDisabled ? escapeHtml(disabledReason) : 'Allocating quantity'}"
                                style="color: #047857; font-size: 12.5px; ${isBatchDisabled ? 'background-color: #f3f4f6; cursor: not-allowed;' : ''}">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-1.5 py-0 btn-max-batch-qty" 
                                data-stockno="${sNo}" 
                                data-availbal="${availQty}" 
                                data-phavail="${phAvailPlants}"
                                ${isBatchDisabled ? 'disabled' : ''}
                                title="${isBatchDisabled ? escapeHtml(disabledReason) : 'Fill available balance (up to order qty)'}" 
                                style="font-size: 11px; ${isBatchDisabled ? 'cursor: not-allowed; opacity: 0.5;' : ''}">Max</button>
                        </div>
                    </td>
                    <td class="text-center align-middle" style="width: 125px; min-width: 115px;">
                        <div style="max-width: 115px; margin: 0 auto;">
                            <input type="number" class="form-control form-control-sm text-end fw-bold font-monospace stockorder-extra-qty-input" 
                                data-stockno="${sNo}" 
                                data-availbal="${availQty}" 
                                min="0" 
                                value="${(availQty > 0 && currentExtra > 0) ? currentExtra : ''}" 
                                placeholder="0" 
                                style="color: #ea580c; font-size: 12.5px; ${(isBatchDisabled || isShiftedYes || availQty <= 0) ? 'background-color: #f3f4f6; cursor: not-allowed;' : ''}" 
                                ${(isBatchDisabled || isShiftedYes || availQty <= 0) ? 'disabled' : ''} 
                                title="${isBatchDisabled ? escapeHtml(disabledReason) : (availQty <= 0 ? 'No plants available in this batch (0 Bal)' : (isShiftedYes ? 'Extra plants only apply for non-shifted trays' : 'Extra plants for this batch'))}">
                        </div>
                    </td>
                    <td class="small align-middle" style="min-width: 110px;">
                        <div class="fw-bold text-dark">${item.polyhouse_locations || 'N/A'}</div>
                        ${cleanBedNumbers ? `<div class="text-secondary mt-0.5" style="font-size: 11px;"><i class="fa fa-th-large me-1 text-primary" style="font-size: 10px;"></i><b>Bed:</b> ${escapeHtml(cleanBedNumbers)}</div>` : ''}
                    </td>
                    <td class="small align-middle" style="min-width: 220px;">${allocatedBedsColHtml}</td>
                    <td class="small text-muted align-middle">${item.expected_delivery_date || '-'}${diffBadge}</td>
                </tr>
            `;
            $tbody.append(rowHtml);
        });
    }

    function updateMultiBatchAllocationState() {
        let targetQty = getActiveModalTargetOrderQty();
        let totalAllocated = 0;
        
        // Filter selected batches:
        // Must ONLY allow batches of the SAME product and SAME client of the current modal order!
        let validBatches = [];
        Object.entries(currentBatchAllocations).forEach(([sNo, qty]) => {
            let nQty = parseInt(qty) || 0;
            if (nQty <= 0) return;

            // If we have cached available stock orders, verify product matches current order
            if (Array.isArray(cachedAvailableStockOrders) && cachedAvailableStockOrders.length > 0) {
                let found = cachedAvailableStockOrders.find(s => s.stockorder_no === sNo);
                if (found) {
                    if (currentModalProductId && found.product_id && String(found.product_id) !== String(currentModalProductId)) {
                        delete currentBatchAllocations[sNo];
                        return; // Discard different product batch
                    }
                }
            }
            validBatches.push([sNo, nQty]);
            totalAllocated += nQty;
        });

        let diffQty = targetQty - totalAllocated;
        let remainingNeeded = Math.max(0, diffQty);

        $("#multi_alloc_target_qty, #growth_multi_alloc_target_qty").text(`${targetQty.toLocaleString()} Plants`);
        $("#multi_alloc_total_qty, #growth_multi_alloc_total_qty").text(`${totalAllocated.toLocaleString()} Plants`);
        $("#multi_alloc_diff_qty, #growth_multi_alloc_diff_qty").text(`${remainingNeeded.toLocaleString()} Plants`);

        let $statusBadge = $("#multi_alloc_status_badge, #growth_multi_alloc_status_badge");
        let $saveBtn = $("#saveAllocationBtn, #growth_saveAllocationBtn");
        let $confirmBtn = $("#confirmAndPrepareInvoiceBtn, #growth_confirmAndPrepareInvoiceBtn");
        let $chipsContainer = $("#multi_alloc_batches_chips, #growth_multi_alloc_batches_chips");

        if (totalAllocated === 0) {
            $statusBadge.html(`<span class="badge bg-secondary px-2.5 py-1.5 shadow-2xs"><i class="fa fa-info-circle me-1"></i> Please allocate batches</span>`);
            $chipsContainer.addClass("d-none").empty();
            $saveBtn.prop("disabled", true).addClass("opacity-50").attr("title", "Please allocate at least one batch with valid quantity.");
            $confirmBtn.prop("disabled", true).addClass("opacity-50").attr("title", "Please allocate at least one batch with valid quantity.");
            $("#modalStockOrderInfo, #growth_modalStockOrderInfo").addClass("d-none");
            $("#modalStockOrderInfoContent, #growth_modalStockOrderInfoContent").empty();
        } else if (totalAllocated === targetQty) {
            $statusBadge.html(`<span class="badge bg-success px-2.5 py-1.5 shadow-2xs"><i class="fa fa-check-circle me-1"></i> Fully Allocated (${validBatches.length} ${validBatches.length > 1 ? 'Batches' : 'Batch'})</span>`);
            $saveBtn.prop("disabled", false).removeClass("opacity-50").removeAttr("title");
            $confirmBtn.prop("disabled", false).removeClass("opacity-50").removeAttr("title");
        } else if (totalAllocated < targetQty) {
            $statusBadge.html(`<span class="badge bg-warning text-dark px-2.5 py-1.5 shadow-2xs"><i class="fa fa-exclamation-triangle me-1"></i> Partially Allocated (Short: ${remainingNeeded.toLocaleString()} Plants)</span>`);
            $saveBtn.prop("disabled", false).removeClass("opacity-50").removeAttr("title");
            $confirmBtn.prop("disabled", false).removeClass("opacity-50").removeAttr("title");
        } else {
            let overAllocated = totalAllocated - targetQty;
            $statusBadge.html(`<span class="badge bg-warning text-dark px-2.5 py-1.5 shadow-2xs"><i class="fa fa-exclamation-triangle me-1"></i> Over-Allocated (+${overAllocated.toLocaleString()} Plants)</span>`);
            $saveBtn.prop("disabled", false).removeClass("opacity-50").removeAttr("title");
            $confirmBtn.prop("disabled", false).removeClass("opacity-50").removeAttr("title");
        }

        // Render chips for selected batches
        if (validBatches.length > 0) {
            let chipsHtml = `<span class="small fw-bold text-secondary me-1" style="font-size: 11px;"><i class="fa fa-layer-group me-1"></i> Selected (${validBatches.length}):</span>`;
            validBatches.forEach(([sNo, qty]) => {
                chipsHtml += `
                    <span class="badge bg-primary-subtle text-primary border border-primary px-2.5 py-1 font-monospace d-inline-flex align-items-center gap-1.5 shadow-2xs" style="font-size: 11.5px;">
                        <i class="fa fa-box me-0.5 text-primary"></i> <b>${sNo}</b>: <span class="text-dark fw-bold">${parseInt(qty).toLocaleString()} plants</span>
                        <i class="fa fa-times-circle text-danger ms-1 cursor-pointer remove-batch-chip" data-stockno="${sNo}" title="Remove allocation from ${sNo}" style="font-size: 13px;"></i>
                    </span>
                `;
            });
            $chipsContainer.html(chipsHtml).removeClass("d-none");
        } else {
            $chipsContainer.addClass("d-none").empty();
        }

        // Synchronize hidden input values and lock base order qty to targetQty
        let allStockNos = validBatches.map(([sNo]) => sNo);
        currentSelectedStockOrder = allStockNos.join(', ');
        let isGrowthModal = $("#orderGrowthStatusModal").hasClass("show") || ($("#orderGrowthStatusModal").length && $("#orderGrowthStatusModal").is(":visible"));
        let setAllocQty = totalAllocated > 0 ? totalAllocated : targetQty;
        if (isGrowthModal) {
            $("#growth_modal_stockorder_no").val(currentSelectedStockOrder);
            $("#growth_modal_allocated_qty").val(setAllocQty);
        } else {
            $("#modal_stockorder_no").val(currentSelectedStockOrder);
            $("#modal_allocated_qty").val(setAllocQty);
        }

        // Dynamically adjust tooltips for batch row checkboxes
        $(".stockorder-grid-row").each(function() {
            let rowStockNo = $(this).data("stockno");
            let rowAvail = parseInt($(this).find(".stockorder-alloc-qty-input").data("availbal") || 0);
            let rowAlloc = parseInt(currentBatchAllocations[rowStockNo] || 0);

            let $chk = $(this).find(".stockorder-table-checkbox");
            if (!$chk.is(":checked")) {
                if (totalAllocated >= targetQty) {
                    $chk.attr("title", `Target order fulfilled (${totalAllocated.toLocaleString()}/${targetQty.toLocaleString()})`);
                } else {
                    $chk.removeAttr("title");
                }
            }
        });

        // Core Requirement:
        // "if same psono allocation multiple time than show other wise did not show, same product of same clientid product allow to show"
        if (validBatches.length > 1) {
            let rowsHtml = '';
            validBatches.forEach(([sNo, qty], bIdx) => {
                let found = Array.isArray(cachedAvailableStockOrders) ? cachedAvailableStockOrders.find(s => s.stockorder_no === sNo) : null;
                let avail = found ? parseInt(found.available_balance_qty !== undefined ? found.available_balance_qty : (found.available_balance || 0)) : '-';
                let ph = found ? (found.polyhouse_locations || 'N/A') : 'N/A';
                let beds = (found && found.bed_numbers) ? found.bed_numbers : '';
                let prod = found ? (found.clean_product_name || found.product_name || $("#modal_product_name").val() || '-') : ($("#modal_product_name").val() || '-');
                rowsHtml += `
                    <tr>
                        <td class="text-center font-monospace fw-bold text-secondary">${bIdx + 1}</td>
                        <td><span class="badge bg-primary fs-6 font-monospace">${sNo}</span></td>
                        <td class="small text-dark fw-semibold text-truncate" style="max-width: 160px;">${prod}</td>
                        <td class="text-end fw-bold text-success font-monospace">${parseInt(qty).toLocaleString()}</td>
                        <td class="text-end text-muted font-monospace">${typeof avail === 'number' ? avail.toLocaleString() : avail}</td>
                        <td class="small">
                            <span class="fw-semibold text-dark">${ph}</span>
                            ${beds ? `<div class="text-muted" style="font-size: 11px;"><b>Bed:</b> ${beds}</div>` : ''}
                        </td>
                    </tr>
                `;
            });

            $("#modalStockOrderInfoContent").html(`
                <div class="card border border-success-subtle shadow-xs mb-1" style="background: #f0fdf4;">
                    <div class="card-header py-1.5 px-3 bg-white border-bottom d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-success small text-uppercase" style="font-size: 11.5px;">
                            <i class="fa fa-boxes me-1 text-success"></i> Multi-Batch Allocation Breakdown (${validBatches.length} Production Batches)
                        </span>
                        <span class="badge bg-success text-white font-monospace">Total: ${totalAllocated.toLocaleString()} Plants</span>
                    </div>
                    <div class="table-responsive bg-white">
                        <table class="table table-sm table-bordered align-middle mb-0" style="font-size: 11.5px;">
                            <thead class="table-light">
                                <tr class="text-secondary text-uppercase" style="font-size: 10.5px;">
                                    <th style="width: 35px;" class="text-center">#</th>
                                    <th>Stock Order</th>
                                    <th>Product</th>
                                    <th class="text-end">Allocating Qty</th>
                                    <th class="text-end">Avail Bal</th>
                                    <th>Polyhouse</th>
                                </tr>
                            </thead>
                            <tbody>${rowsHtml}</tbody>
                        </table>
                    </div>
                </div>
            `);
            $("#modalStockOrderInfo").removeClass("d-none");
        } else {
            // Otherwise (single batch or 0 batches): DO NOT SHOW Selected Stock Order Details!
            $("#modalStockOrderInfo").addClass("d-none");
            $("#modalStockOrderInfoContent").empty();
        }
    }

    function renderSingleBatchDetailView(found, allocQty = 0) {
        let stockNo = found.stockorder_no;
        let availBal = parseInt(found.available_balance_qty !== undefined ? found.available_balance_qty : (found.available_balance || 0));
        let reqQty = allocQty > 0 ? allocQty : getActiveModalTargetOrderQty();
        let cannotFulfill = (reqQty > 0 && availBal < reqQty);
        let isSwap = (found.allocated_to_clients && found.allocated_to_clients.trim() !== '') || (currentModalDispatchRow && stockNo !== (currentModalDispatchRow.original_sown_stockorderno || currentModalDispatchRow.stockorder_no));

        let daysDiff = null;
        let isDateEligible = true;
        if (currentModalDeliveryDate && found.expected_delivery_date) {
            let d1 = new Date(currentModalDeliveryDate);
            let d2 = new Date(found.expected_delivery_date);
            let timeDiff = Math.abs(d2.getTime() - d1.getTime());
            daysDiff = Math.round(timeDiff / (1000 * 3600 * 24));
            isDateEligible = (daysDiff <= 10);
        }

        let isGrowth = $("#orderGrowthStatusModal").hasClass("show") || ($("#orderGrowthStatusModal").length && $("#orderGrowthStatusModal").is(":visible"));
        let origClientName = (currentModalDispatchRow ? (currentModalDispatchRow.clientname || currentModalDispatchRow.client_name) : '') || $("#modal_client_name").text() || 'Current Client';
        let origPsono = isGrowth ? ($("#growth_modal_psono").val() || (currentModalDispatchRow ? currentModalDispatchRow.psono : '-')) : ($("#modal_psono").val() || '-');
        let targetClientName = found.sown_client_name || found.allocated_client_names || found.allocated_to_clients || 'Other Client';
        let targetPsono = found.psono || found.allocated_psono_list || '-';

        let isOpenStock = (
            stockNo.startsWith('RSO-') || 
            stockNo.startsWith('RTS-') || 
            stockNo.startsWith('READY-') ||
            found.clientid === 'C00001' || 
            parseInt(found.is_trader) === 1 || 
            (found.remarks && found.remarks.toLowerCase().includes('ready stock'))
        );

        let swapNotice = '';
        if (isOpenStock) {
            swapNotice = `
                <div class="mt-2 p-2.5 rounded-2 shadow-xs" style="background: #f0f9ff; border: 1.5px solid #0284c7;">
                    <div class="d-flex align-items-center justify-content-between mb-1 pb-1 border-bottom border-info-subtle">
                        <span class="badge px-2 py-0.5 text-white fw-bold" style="background-color: #0284c7; font-size: 11px;">
                            <i class="fa fa-boxes me-1"></i> OPEN STOCK PRODUCTION ORDER
                        </span>
                        <span class="badge bg-white text-primary border border-info-subtle px-2 py-0.5" style="font-size: 10.5px;">Open Stock</span>
                    </div>
                    <div class="small text-dark" style="font-size: 12.5px;">
                        Production Batch <b class="font-monospace">${stockNo}</b> is an Open Stock / Trader batch available for allocation.
                    </div>
                </div>
            `;
        } else if (isSwap) {
            swapNotice = `
                <div class="mt-2 p-2.5 rounded-2 shadow-xs" style="background: #eff6ff; border: 1.5px solid #2563eb;">
                    <div class="d-flex align-items-center gap-2 mb-1 pb-1 border-bottom border-primary-subtle">
                        <span class="badge px-2 py-0.5 text-white fw-bold" style="background-color: #1d4ed8; font-size: 11px;">
                            <i class="fa fa-exchange-alt me-1"></i> MUTUAL BATCH SWAP
                        </span>
                        <span class="fw-bold small text-primary-emphasis">Cross-Allocation (${daysDiff !== null ? `Diff: ${daysDiff}d` : 'Eligible'})</span>
                    </div>
                    <div class="small text-dark" style="font-size: 12px; line-height: 1.5;">
                        <div>• <b>Allocated Batch:</b> <span class="badge bg-primary font-monospace">${found.stockorder_no}</span> (${targetClientName}) &rarr; ${origClientName}</div>
                    </div>
                </div>
            `;
        }

        $("#modalStockOrderInfoContent").html(`
            <div class="row g-2">
                <div class="col-md-6">
                    <div class="p-2 rounded border bg-light">
                        <span class="text-muted small">Stock Order No:</span> 
                        <span class="badge bg-primary fs-6 font-monospace ms-1">${found.stockorder_no}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-2 rounded border bg-light">
                        <span class="text-muted small">Product:</span> 
                        <span class="fw-bold text-dark ms-1">${found.clean_product_name || found.product_name}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-2 rounded border bg-white">
                        <span class="text-muted small">Total Batch Qty:</span> <b class="text-dark">${parseInt(found.total_qty).toLocaleString()}</b> 
                        <span class="text-muted mx-1">|</span> 
                        <span class="text-muted small">Allocated:</span> <b class="text-secondary">${parseInt(found.total_allocated_qty).toLocaleString()}</b>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-2 rounded border border-success bg-success-subtle">
                        <span class="small fw-semibold text-success">Available Balance:</span> 
                        <span class="fs-6 fw-bold ms-1 text-success">${availBal.toLocaleString()} Plants</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-2 rounded border bg-white">
                        <span class="text-muted small">Ready Polyhouse Plants:</span> 
                        <span class="text-primary fw-bold ms-1">${parseInt(found.polyhouse_ready_qty || 0).toLocaleString()}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-2 rounded border bg-white">
                        <span class="text-muted small">Polyhouses:</span> 
                        <span class="fw-semibold text-dark ms-1">${found.polyhouse_locations || 'N/A'}</span>
                    </div>
                </div>
            </div>
            ${swapNotice}
        `);
        $("#modalStockOrderInfo").removeClass("d-none");
    }

    // Checkbox selection toggle
    $(document).on("change", ".stockorder-table-checkbox", function() {
        if ($(this).is(":disabled")) {
            $(this).prop("checked", false);
            return;
        }
        let sNo = $(this).data("stockno");
        let isChecked = $(this).is(":checked");
        let availBal = parseInt($(this).data("availbal") || 0);
        let $row = $(this).closest("tr");
        let $input = $row.find(".stockorder-alloc-qty-input");

        let targetQty = getActiveModalTargetOrderQty();

        let otherCurrent = 0;
        Object.entries(currentBatchAllocations).forEach(([b, q]) => {
            if (b !== sNo) otherCurrent += parseInt(q);
        });
        let remainingNeeded = Math.max(0, targetQty - otherCurrent);

        if (isChecked) {
            let toAlloc = 0;
            if (remainingNeeded > 0) {
                toAlloc = (availBal > 0) ? Math.min(availBal, remainingNeeded) : remainingNeeded;
            } else {
                toAlloc = (availBal > 0) ? availBal : (targetQty > 0 ? targetQty : 1);
            }
            if (toAlloc <= 0) toAlloc = (targetQty > 0) ? targetQty : 1;

            currentBatchAllocations[sNo] = toAlloc;
            $input.val(toAlloc > 0 ? toAlloc : '');
            $row.addClass("stockorder-row-selected table-success");
            setBatchBedsChecked(sNo, true);

            if ($("#shifted_no").is(":checked")) {
                let remainingBatchCap = Math.max(0, availBal - toAlloc);
                $row.find(".stockorder-extra-qty-input").attr("max", remainingBatchCap);
                if (remainingBatchCap <= 0) {
                    $row.find(".stockorder-extra-qty-input").prop("disabled", true).val('').attr("title", "Batch balance fully allocated to base order qty");
                    delete currentBatchExtraAllocations[sNo];
                } else {
                    $row.find(".stockorder-extra-qty-input").prop("disabled", false).attr("title", `Extra plants for this batch (Max: ${remainingBatchCap.toLocaleString()})`);
                    let extraPct = parseFloat($("#calc_extra_percent").val()) || 0;
                    let targetExtra = Math.round(targetQty * (extraPct / 100));
                    let currentOtherExtra = 0;
                    Object.entries(currentBatchExtraAllocations).forEach(([b, e]) => { if (b !== sNo) currentOtherExtra += parseInt(e || 0); });
                    let bExtra = Math.min(remainingBatchCap, Math.max(0, targetExtra - currentOtherExtra));
                    if (bExtra > 0) {
                        currentBatchExtraAllocations[sNo] = bExtra;
                        $row.find(".stockorder-extra-qty-input").val(bExtra);
                    } else {
                        delete currentBatchExtraAllocations[sNo];
                        $row.find(".stockorder-extra-qty-input").val('');
                    }
                }
                syncExtraPlantsFromBatches();
            }
        } else {
            delete currentBatchAllocations[sNo];
            delete currentBatchExtraAllocations[sNo];
            $input.val('');
            let $extraInp = $row.find(".stockorder-extra-qty-input");
            $extraInp.val('').attr("max", availBal);
            if (availBal <= 0) {
                $extraInp.prop("disabled", true).attr("title", "No plants available in this batch");
            } else if ($("#shifted_no").is(":checked")) {
                $extraInp.prop("disabled", false).attr("title", `Extra plants for this batch (Max: ${availBal.toLocaleString()})`);
            }
            $row.removeClass("stockorder-row-selected table-success");
            setBatchBedsChecked(sNo, false);
            syncExtraPlantsFromBatches();
        }

        updateMultiBatchAllocationState();
    });

    // Allocating Qty number input handler
    $(document).on("input change", ".stockorder-alloc-qty-input", function() {
        if ($(this).is(":disabled")) return;
        let sNo = $(this).data("stockno");
        let availBal = parseInt($(this).data("availbal") || 0);
        let rawVal = $(this).val().trim();
        let val = parseInt(rawVal) || 0;
        let $row = $(this).closest("tr");
        let $chk = $row.find(".stockorder-table-checkbox");
        let $extraInp = $row.find(".stockorder-extra-qty-input");

        if (val < 0) {
            val = 0;
            $(this).val('');
        }

        // Allow entering any positive quantity freely without capping or blocking error toasts
        if (val > 0) {
            currentBatchAllocations[sNo] = val;
            $chk.prop("checked", true);
            $row.addClass("stockorder-row-selected table-success");
            setBatchBedsChecked(sNo, true);

            if ($("#shifted_no").is(":checked")) {
                let remainingBatchCap = Math.max(0, availBal - val);
                $extraInp.attr("max", remainingBatchCap);
                if (remainingBatchCap <= 0) {
                    $extraInp.prop("disabled", true).val('').attr("title", "Batch balance fully allocated to base order qty");
                    delete currentBatchExtraAllocations[sNo];
                } else {
                    $extraInp.prop("disabled", false).attr("title", `Extra plants for this batch (Max: ${remainingBatchCap.toLocaleString()})`);
                    let exExtra = currentBatchExtraAllocations[sNo] || 0;
                    if (exExtra > remainingBatchCap) {
                        currentBatchExtraAllocations[sNo] = remainingBatchCap;
                        $extraInp.val(remainingBatchCap > 0 ? remainingBatchCap : '');
                    }
                }
                syncExtraPlantsFromBatches();
            }
        } else {
            delete currentBatchAllocations[sNo];
            $chk.prop("checked", false);
            $row.removeClass("stockorder-row-selected table-success");
            setBatchBedsChecked(sNo, false);
            let remainingBatchCap = availBal;
            $extraInp.attr("max", remainingBatchCap);
            if (remainingBatchCap <= 0) {
                delete currentBatchExtraAllocations[sNo];
                $extraInp.prop("disabled", true).val('').attr("title", "No plants available in this batch");
            } else if ($("#shifted_no").is(":checked")) {
                $extraInp.prop("disabled", false).attr("title", `Extra plants for this batch (Max: ${remainingBatchCap.toLocaleString()})`);
            }
            syncExtraPlantsFromBatches();
        }

        updateMultiBatchAllocationState();
    });

    // Extra Plant number input handler (per batch extra quantity entry)
    $(document).on("input change", ".stockorder-extra-qty-input", function() {
        let sNo = $(this).data("stockno");
        let availBal = parseInt($(this).data("availbal") || 0);
        let $row = $(this).closest("tr");
        let $chk = $row.find(".stockorder-table-checkbox");
        
        // 1. If batch has 0 or negative available balance, completely block
        if (availBal <= 0) {
            $(this).val('').prop('disabled', true);
            delete currentBatchExtraAllocations[sNo];
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: `Batch ${sNo} has 0 available plants. Cannot allocate extra plants.`,
                timer: 3000,
                showConfirmButton: false
            });
            syncExtraPlantsFromBatches();
            return;
        }

        let rawVal = $(this).val().trim();
        let val = parseInt(rawVal) || 0;
        if (val < 0) val = 0;

        // 2. Check remaining capacity of this batch: (availBal - currentAlloc)
        let currentAlloc = parseInt(currentBatchAllocations[sNo] || 0);
        let maxBatchCap = Math.max(0, availBal - currentAlloc);

        if (maxBatchCap <= 0) {
            val = 0;
            $(this).val('').prop('disabled', true);
            delete currentBatchExtraAllocations[sNo];
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'warning',
                title: `Batch ${sNo}: Balance (${availBal.toLocaleString()} plants) is already 100% allocated to base order qty (${currentAlloc.toLocaleString()}). No extra plants can be taken from this batch.`,
                timer: 4000,
                showConfirmButton: false
            });
            syncExtraPlantsFromBatches();
            return;
        }

        if (val > maxBatchCap) {
            val = maxBatchCap;
            $(this).val(val > 0 ? val : '');
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'warning',
                title: `Batch ${sNo}: Extra cannot exceed remaining batch balance of ${maxBatchCap.toLocaleString()} plants (Avail: ${availBal.toLocaleString()}, Base: ${currentAlloc.toLocaleString()}).`,
                timer: 3500,
                showConfirmButton: false
            });
        }

        // 3. Check overall target extra plants ceiling based on % of Extra Plant
        let orderQty = getActiveModalTargetOrderQty();
        let extraPct = parseFloat($("#growth_calc_extra_percent").length && $("#growth_calc_extra_percent").val() ? $("#growth_calc_extra_percent").val() : $("#calc_extra_percent").val()) || 0;
        let targetExtra = (extraPct > 0 && orderQty > 0) ? Math.round(orderQty * (extraPct / 100)) : 0;

        if (targetExtra > 0) {
            let otherBatchesExtra = 0;
            Object.entries(currentBatchExtraAllocations).forEach(([bNo, bExt]) => {
                if (bNo !== sNo) otherBatchesExtra += parseInt(bExt || 0);
            });
            let maxAllowedFromTarget = Math.max(0, targetExtra - otherBatchesExtra);
            if (val > maxAllowedFromTarget) {
                val = maxAllowedFromTarget;
                $(this).val(val > 0 ? val : '');
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'warning',
                    title: `Total extra plants cannot exceed target of ${targetExtra.toLocaleString()} plants (${extraPct}% of ${orderQty.toLocaleString()}).`,
                    timer: 3500,
                    showConfirmButton: false
                });
            }
        }

        if (val > 0) {
            currentBatchExtraAllocations[sNo] = val;
            $row.addClass("stockorder-row-selected table-success");
            $chk.prop("checked", true);
        } else {
            delete currentBatchExtraAllocations[sNo];
            $(this).val('');
            if (!currentBatchAllocations[sNo] || currentBatchAllocations[sNo] <= 0) {
                $row.removeClass("stockorder-row-selected table-success");
                $chk.prop("checked", false);
            }
        }
        syncExtraPlantsFromBatches();
    });

    // Max Button Handler
    $(document).on("click", ".btn-max-batch-qty", function(e) {
        e.stopPropagation();
        if ($(this).is(":disabled")) return;
        let sNo = $(this).data("stockno");
        let availBal = parseInt($(this).data("availbal") || 0);
        let $row = $(this).closest("tr");
        let $input = $row.find(".stockorder-alloc-qty-input");
        let $chk = $row.find(".stockorder-table-checkbox");
        let $extraInp = $row.find(".stockorder-extra-qty-input");

        let targetQty = getActiveModalTargetOrderQty();

        let otherCurrent = 0;
        Object.entries(currentBatchAllocations).forEach(([b, q]) => {
            if (b !== sNo) otherCurrent += parseInt(q);
        });
        let remainingNeeded = Math.max(0, targetQty - otherCurrent);

        let maxCanTake = 0;
        if (availBal > 0) {
            maxCanTake = (remainingNeeded > 0) ? Math.min(availBal, remainingNeeded + (currentBatchAllocations[sNo] || 0)) : availBal;
        } else {
            maxCanTake = (remainingNeeded > 0) ? remainingNeeded : (targetQty > 0 ? targetQty : 1);
        }

        if (maxCanTake <= 0) {
            delete currentBatchAllocations[sNo];
            delete currentBatchExtraAllocations[sNo];
            $input.val('');
            $extraInp.val('').attr("max", availBal);
            $chk.prop("checked", false);
            $row.removeClass("stockorder-row-selected table-success");
            setBatchBedsChecked(sNo, false);
            syncExtraPlantsFromBatches();
        } else {
            currentBatchAllocations[sNo] = maxCanTake;
            $input.val(maxCanTake);
            $chk.prop("checked", true);
            $row.addClass("stockorder-row-selected table-success");
            setBatchBedsChecked(sNo, true);

            if ($("#shifted_no").is(":checked")) {
                let remainingBatchCap = Math.max(0, availBal - maxCanTake);
                $extraInp.attr("max", remainingBatchCap);
                if (remainingBatchCap <= 0) {
                    $extraInp.prop("disabled", true).val('').attr("title", "Batch balance fully allocated to base order qty");
                    delete currentBatchExtraAllocations[sNo];
                } else {
                    $extraInp.prop("disabled", false).attr("title", `Extra plants for this batch (Max: ${remainingBatchCap.toLocaleString()})`);
                    let exExtra = currentBatchExtraAllocations[sNo] || 0;
                    if (exExtra > remainingBatchCap) {
                        currentBatchExtraAllocations[sNo] = remainingBatchCap;
                        $extraInp.val(remainingBatchCap > 0 ? remainingBatchCap : '');
                    }
                }
                syncExtraPlantsFromBatches();
            }
        }

        updateMultiBatchAllocationState();
    });

    // Remove batch chip handler
    $(document).on("click", ".remove-batch-chip", function(e) {
        e.stopPropagation();
        let sNo = $(this).data("stockno");
        delete currentBatchAllocations[sNo];
        delete currentBatchExtraAllocations[sNo];
        let $row = $(`.stockorder-grid-row[data-stockno="${sNo}"]`);
        $row.find(".stockorder-alloc-qty-input").val('');
        $row.find(".stockorder-extra-qty-input").val('');
        $row.find(".stockorder-table-checkbox").prop("checked", false);
        $row.removeClass("stockorder-row-selected table-success");
        setBatchBedsChecked(sNo, false);
        updateMultiBatchAllocationState();
        syncExtraPlantsFromBatches();
    });

    // Reset All Allocations
    $(document).on("click", "#btnResetAllocation, #growth_btnResetAllocation", function() {
        currentBatchAllocations = {};
        currentBatchExtraAllocations = {};
        $(".stockorder-alloc-qty-input").val('');
        $(".stockorder-extra-qty-input").val('');
        $(".stockorder-table-checkbox").prop("checked", false);
        $(".stockorder-grid-row").removeClass("stockorder-row-selected table-success");
        $(".batch-bed-checkbox").prop("checked", false);
        $(".bed-select-pill").removeClass("bed-checked border-success-subtle bg-success-subtle text-success-emphasis");
        updateMultiBatchAllocationState();
        syncExtraPlantsFromBatches();
    });

    // Auto-Allocate by Balance Button Handler
    $(document).on("click", "#btnAutoAllocateByBalance, #growth_btnAutoAllocateByBalance", function() {
        let targetQty = getActiveModalTargetOrderQty();
        if (targetQty <= 0) {
            Swal.fire("Warning", "Target order quantity is zero. Cannot auto-allocate.", "warning");
            return;
        }

        if (!cachedAvailableStockOrders || cachedAvailableStockOrders.length === 0) {
            Swal.fire("Notice", "No available production stock orders to allocate from.", "info");
            return;
        }

        currentBatchAllocations = {};
        let remainingNeeded = targetQty;

        // Pass 1: Prioritize Date-Eligible batches (diff <= 10 days) with positive balance
        let eligibleBatches = [];
        let otherBatches = [];

        cachedAvailableStockOrders.forEach(b => {
            let bAvail = parseInt(b.available_balance_qty !== undefined ? b.available_balance_qty : (b.available_balance || 0));
            let bPhTotal = parseInt(b.ph_total_plants || 0);
            let bPhAvail = parseInt(b.ph_avail_plants !== undefined ? b.ph_avail_plants : (b.polyhouse_ready_qty || 0));
            let bIsOwn = (parseInt(b.is_own_production_order || 0) === 1);
            let bSowPending = ((b.sowingstatus || '').toLowerCase().trim() === 'pending' || (b.sowing_schedule_status || '').toLowerCase().trim() === 'pending');

            // Must have positive available balance, plants available in polyhouse (both total and avail > 0), and sowing not pending
            if (bAvail > 0 && bPhTotal > 0 && bPhAvail > 0 && !(bIsOwn && bSowPending)) {
                let daysDiff = null;
                let isEligible = true;
                if (currentModalDeliveryDate && b.expected_delivery_date) {
                    let d1 = new Date(currentModalDeliveryDate);
                    let d2 = new Date(b.expected_delivery_date);
                    daysDiff = Math.round(Math.abs(d2.getTime() - d1.getTime()) / (1000 * 3600 * 24));
                    let isReadyStock = (b.stockorder_no && (b.stockorder_no.startsWith('RSO-') || b.stockorder_no.startsWith('RTS-') || b.stockorder_no.startsWith('READY-'))) || parseInt(b.polyhouse_ready_qty || 0) > 0;
                    isEligible = (daysDiff <= 10) || isReadyStock;
                }
                if (isEligible) {
                    eligibleBatches.push({ batch: b, avail: bAvail, diff: daysDiff !== null ? daysDiff : 0 });
                } else {
                    otherBatches.push({ batch: b, avail: bAvail, diff: daysDiff !== null ? daysDiff : 999 });
                }
            }
        });

        // Sort eligible batches by closest delivery date diff, then highest available balance
        eligibleBatches.sort((a, b) => a.diff - b.diff || b.avail - a.avail);
        otherBatches.sort((a, b) => a.diff - b.diff || b.avail - a.avail);

        let candidateBatches = eligibleBatches.concat(otherBatches);

        candidateBatches.forEach(c => {
            if (remainingNeeded > 0 && c.avail > 0) {
                let take = Math.min(c.avail, remainingNeeded);
                currentBatchAllocations[c.batch.stockorder_no] = take;
                remainingNeeded -= take;
            }
        });

        // Sync inputs in the table
        renderStockOrderGrid(cachedAvailableStockOrders);
        updateMultiBatchAllocationState();
        let isShiftedNo = $("#growth_shifted_no").length ? $("#growth_shifted_no").is(":checked") : $("#shifted_no").is(":checked");
        if (isShiftedNo) {
            recalculateExtraPlants();
        }

        let totalAlloc = targetQty - remainingNeeded;
        let batchCount = Object.keys(currentBatchAllocations).length;

        if (remainingNeeded === 0) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: `Auto-allocated ${totalAlloc.toLocaleString()} plants across ${batchCount} production ${batchCount > 1 ? 'batches' : 'batch'}!`,
                showConfirmButton: false,
                timer: 4000
            });
        } else {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'warning',
                title: `Partially allocated ${totalAlloc.toLocaleString()} of ${targetQty.toLocaleString()} plants across ${batchCount} batches. Short by ${remainingNeeded.toLocaleString()} plants.`,
                showConfirmButton: false,
                timer: 5000
            });
        }
    });

    // Real-time Search Box filtering
    $(document).on("input keyup", "#modal_stockorder_grid_search, #growth_modal_stockorder_grid_search", function() {
        let query = $(this).val().toLowerCase().trim();
        // Sync both search inputs if both exist in DOM
        if ($(this).attr("id") === "growth_modal_stockorder_grid_search") {
            $("#modal_stockorder_grid_search").val($(this).val());
        } else {
            $("#growth_modal_stockorder_grid_search").val($(this).val());
        }

        if (!query) {
            renderStockOrderGrid(cachedAvailableStockOrders);
            return;
        }

        let filtered = cachedAvailableStockOrders.filter(item => {
            let sNo = (item.stockorder_no || '').toLowerCase();
            let pNo = (item.psono || '').toLowerCase();
            let pName = (item.product_name || '').toLowerCase();
            let cName = (item.clean_product_name || '').toLowerCase();
            let clients = (item.allocated_to_clients || '').toLowerCase();
            let allocNames = (item.allocated_client_names || '').toLowerCase();
            let sownClient = (item.sown_client_name || '').toLowerCase();
            let sownSono = (item.sown_sono || '').toLowerCase();
            let ph = (item.polyhouse_locations || '').toLowerCase();
            let beds = (item.bed_numbers || '').toLowerCase();
            let sow = (item.sowingdate || '').toLowerCase();
            let del = (item.expected_delivery_date || '').toLowerCase();
            let rem = (item.remarks || '').toLowerCase();

            return sNo.includes(query) || pNo.includes(query) || pName.includes(query) || cName.includes(query) 
                || clients.includes(query) || allocNames.includes(query) || sownClient.includes(query) || sownSono.includes(query)
                || ph.includes(query) || beds.includes(query) || sow.includes(query) || del.includes(query) || rem.includes(query);
        });

        renderStockOrderGrid(filtered);
    });

    // Clear Search Input
    $(document).on("click", "#modal_clear_stockorder_search, #growth_modal_clear_stockorder_search", function() {
        $("#modal_stockorder_grid_search, #growth_modal_stockorder_grid_search").val("");
        renderStockOrderGrid(cachedAvailableStockOrders);
    });

    // Toggle All Varieties
    $(document).on("change", "#modal_allow_all_products_toggle, #growth_modal_allow_all_products_toggle", function() {
        let isAll = $(this).is(":checked");
        // Keep both toggles in sync
        $("#modal_allow_all_products_toggle, #growth_modal_allow_all_products_toggle").prop("checked", isAll);
        loadAvailableStockOrdersForModal(currentModalProductId, isAll, currentSelectedStockOrder, currentModalDeliveryDate);
    });

    // Save Allocation Handler
    function submitAllocationForm(btn, andOpenInvoice = false) {
        let isGrowthModal = $("#orderGrowthStatusModal").hasClass("show") || $("#orderGrowthStatusModal").is(":visible");
        let prefix = isGrowthModal ? "growth_" : "";

        let soi_id = $(`#${prefix}modal_soi_id`).val() || $("#modal_soi_id").val() || $("#growth_modal_soi_id").val() || (currentModalDispatchRow ? currentModalDispatchRow.soi_id : '');
        let cleanSoiId = parseInt(soi_id) || soi_id;
        let psono = $(`#${prefix}modal_psono`).val() || $("#modal_psono").val() || $("#growth_modal_psono").val() || (currentModalDispatchRow ? currentModalDispatchRow.psono : '');
        let sono = $(`#${prefix}modal_sono`).val() || $("#modal_sono").val() || $("#growth_modal_sono").val() || (currentModalDispatchRow ? currentModalDispatchRow.sono : '');
        let client_id = $(`#${prefix}modal_client_id`).val() || $("#modal_client_id").val() || $("#growth_modal_client_id").val() || (currentModalDispatchRow ? (currentModalDispatchRow.clientid || currentModalDispatchRow.client_id) : '');
        let product_id = $(`#${prefix}modal_product_id`).val() || $("#modal_product_id").val() || $("#growth_modal_product_id").val() || (currentModalDispatchRow ? currentModalDispatchRow.product_id : '');
        let product_name = $(`#${prefix}modal_product_name`).val() || $("#modal_product_name").val() || $("#growth_modal_product_name").val() || (currentModalDispatchRow ? currentModalDispatchRow.product_name : '');
        let allocated_psono = $(`#${prefix}modal_allocated_psono`).val() || $("#modal_allocated_psono").val() || $("#growth_modal_allocated_psono").val() || (currentModalDispatchRow ? currentModalDispatchRow.poc_allocated_psono : '') || '';

        // Multi-Batch allocation collection (includes batches with base allocation OR extra plant allocation)
        let allBatchKeys = Array.from(new Set([
            ...Object.keys(currentBatchAllocations).filter(s => parseInt(currentBatchAllocations[s] || 0) > 0),
            ...Object.keys(currentBatchExtraAllocations).filter(s => parseInt(currentBatchExtraAllocations[s] || 0) > 0)
        ]));

        if (allBatchKeys.length === 0) {
            Swal.fire("Warning", "Please allocate order quantity from at least one production stock order batch.", "warning");
            return;
        }

        let isshifted = isGrowthModal 
            ? ($("#growth_shifted_yes").is(":checked") ? 1 : 0)
            : ($("#shifted_yes").is(":checked") ? 1 : ($("#growth_shifted_yes").is(":checked") ? 1 : 0));
        let totalAllocatedQty = 0;
        let allSelectedBeds = [];
        let validationErrors = [];

        let allocationsList = [];
        for (let sNo of allBatchKeys) {
            let intQ = parseInt(currentBatchAllocations[sNo] || 0);
            totalAllocatedQty += intQ;
            let found = cachedAvailableStockOrders.find(s => s.stockorder_no === sNo);

            let phTot = parseInt(found ? (found.ph_total_plants || 0) : 0);
            let phAvail = parseInt(found ? (found.ph_avail_plants !== undefined ? found.ph_avail_plants : (found.polyhouse_ready_qty || 0)) : 0);

            if (phTot <= 0 || phAvail <= 0) {
                validationErrors.push(`For batch <b>${sNo}</b>, plant is not available in PH. Cannot allocate from this batch.`);
            }

            let intExtra = 0;
            if (isshifted === 0) {
                if (currentBatchExtraAllocations[sNo] !== undefined) {
                    intExtra = parseInt(currentBatchExtraAllocations[sNo]) || 0;
                } else {
                    intExtra = parseInt($(`.stockorder-extra-qty-input[data-stockno="${sNo}"]`).val()) || 0;
                }
            }

            // Collect selected beds for this batch from the ALLOCATED BED column
            let rawCheckedBeds = [];
            $(`.batch-bed-checkbox[data-stockno="${sNo}"]:checked`).each(function() {
                rawCheckedBeds.push({
                    polyhouse_id: $(this).data("polyhouseid") || '',
                    polyhouse_name: $(this).data("polyhouse") || '',
                    bed_name: $(this).data("bed") || '',
                    bed_no: $(this).data("bed") || '',
                    tray_id: parseInt($(this).data("trayid") || 0),
                    cavity_count: parseInt($(this).data("cavity") || 126),
                    avail_trays: parseInt($(this).data("trays") || 0),
                    avail_plants: parseInt($(this).data("plants") || 0)
                });
            });

            // De-duplicate selected beds in case multiple modals exist in DOM
            let uniqueRawBeds = [];
            let seenBedKeys = {};
            rawCheckedBeds.forEach(b => {
                let bk = (b.polyhouse_id || '') + '|' + (b.polyhouse_name || '') + '|' + (b.bed_name || '');
                if (!seenBedKeys[bk]) {
                    seenBedKeys[bk] = true;
                    uniqueRawBeds.push(b);
                }
            });
            rawCheckedBeds = uniqueRawBeds;

            // Check if this batch has physical beds rendered or known
            let hasBedCheckboxes = $(`.batch-bed-checkbox[data-stockno="${sNo}"]`).length > 0;
            let hasAvailableBeds = (found && Array.isArray(found.available_beds) && found.available_beds.length > 0);
            let hasPhysicalBeds = hasBedCheckboxes || hasAvailableBeds;

            let targetBatchOrderPlants = intQ;
            let targetBatchExtraPlants = (isshifted === 1) ? 0 : intExtra;
            let totalBatchPlantsToFulfill = targetBatchOrderPlants + targetBatchExtraPlants;

            let selectedBeds = [];

            if (intQ > 0 || totalBatchPlantsToFulfill > 0) {
                if (hasPhysicalBeds) {
                    // If no beds checked but available_beds exists and has exactly 1 bed, auto-fallback to that bed
                    if (rawCheckedBeds.length === 0 && hasAvailableBeds && found.available_beds.length === 1) {
                        let singleB = found.available_beds[0];
                        rawCheckedBeds = [{
                            polyhouse_id: singleB.polyhouse_id || '',
                            polyhouse_name: singleB.polyhouse_name || '',
                            bed_name: singleB.bed_no || singleB.bed_name || '',
                            bed_no: singleB.bed_no || singleB.bed_name || '',
                            tray_id: parseInt(singleB.tray_id || 0),
                            cavity_count: parseInt(singleB.cavity_count || 126),
                            avail_trays: parseInt(singleB.tray_count || 0),
                            avail_plants: parseInt(singleB.healthy_plants || 0)
                        }];
                    }

                    if (rawCheckedBeds.length === 0) {
                        validationErrors.push(`For batch <b>${sNo}</b>, no bed is selected. Please select at least one bed to fulfill <b>${totalBatchPlantsToFulfill.toLocaleString()}</b> plants.`);
                    } else {
                        let totalSelectedCapacity = rawCheckedBeds.reduce((sum, b) => sum + (b.avail_plants || 0), 0);
                        if (totalSelectedCapacity < totalBatchPlantsToFulfill) {
                            let deficit = totalBatchPlantsToFulfill - totalSelectedCapacity;
                            let extraLabel = targetBatchExtraPlants > 0 ? ` (Order: ${targetBatchOrderPlants.toLocaleString()} + Extra: ${targetBatchExtraPlants.toLocaleString()})` : '';
                            validationErrors.push(`For batch <b>${sNo}</b>, allocated quantity${extraLabel} (<b>${totalBatchPlantsToFulfill.toLocaleString()}</b> plants) exceeds total available plants in selected bed(s) (<b>${totalSelectedCapacity.toLocaleString()}</b> plants, short by <b>${deficit.toLocaleString()}</b> plants). Available bed capacity is insufficient. Please correct the bed selection.`);
                        } else {
                            // Distribute totalBatchPlantsToFulfill across selected beds
                            let remainingToDistribute = totalBatchPlantsToFulfill;
                            let distributedBeds = [];

                            for (let b of rawCheckedBeds) {
                                if (remainingToDistribute <= 0) break;

                                let takePlants = Math.min(b.avail_plants, remainingToDistribute);
                                if (takePlants <= 0) continue;

                                // Master cavity count from krf_tray_master via tray_id in krf_polyhouse_plant_details
                                let masterCavity = parseInt(b.cavity_count || 126);
                                if (masterCavity <= 0) masterCavity = 126;

                                let takeTrays = 0;
                                if (isshifted === 1) {
                                    // Condition 1: Shifted Plants (YES)
                                    // 1 free bonus plant per tray: effective cavity = masterCavity - 1 (e.g. 126 - 1 = 125)
                                    let effCavity = Math.max(1, masterCavity - 1);
                                    takeTrays = Math.ceil(takePlants / effCavity);
                                } else {
                                    // Condition 2: Non-Shifted Plants (NO)
                                    // Full master tray cavity (e.g. 126)
                                    takeTrays = Math.ceil(takePlants / masterCavity);
                                }

                                if (takePlants === b.avail_plants && b.avail_trays > 0 && takeTrays < b.avail_trays) {
                                    takeTrays = b.avail_trays;
                                }

                                // Portion of base order plants allocated to this bed
                                let bedBasePlants = (totalBatchPlantsToFulfill > 0) 
                                    ? Math.round(targetBatchOrderPlants * (takePlants / totalBatchPlantsToFulfill)) 
                                    : takePlants;
                                if (takePlants === totalBatchPlantsToFulfill) {
                                    bedBasePlants = targetBatchOrderPlants;
                                }

                                distributedBeds.push({
                                    polyhouse_id: b.polyhouse_id,
                                    polyhouse_name: b.polyhouse_name,
                                    bed_name: b.bed_no || b.bed_name,
                                    bed_no: b.bed_no || b.bed_name,
                                    tray_id: b.tray_id || 2,
                                    cavity_count: masterCavity,
                                    tray_count: takeTrays,
                                    plants: (isshifted === 1) ? takePlants : bedBasePlants,
                                    total_plants: takePlants,
                                    extra_plants: (isshifted === 1) ? 0 : Math.max(0, takePlants - bedBasePlants)
                                });

                                remainingToDistribute -= takePlants;
                            }

                            // Ensure sum of bedBasePlants strictly equals targetBatchOrderPlants (intQ)
                            let sumBedBasePlants = distributedBeds.reduce((sum, b) => sum + b.plants, 0);
                            if (distributedBeds.length > 0 && sumBedBasePlants !== targetBatchOrderPlants) {
                                let diff = targetBatchOrderPlants - sumBedBasePlants;
                                distributedBeds[distributedBeds.length - 1].plants += diff;
                            }

                            let sumTotalPlants = distributedBeds.reduce((sum, b) => sum + (b.total_plants || b.plants), 0);
                            if (sumTotalPlants !== totalBatchPlantsToFulfill) {
                                validationErrors.push(`For batch <b>${sNo}</b>, allocated quantity (<b>${totalBatchPlantsToFulfill.toLocaleString()}</b> plants) does not match total plants in allocated beds JSON (<b>${sumTotalPlants.toLocaleString()}</b> plants). Please correct the bed selection.`);
                            } else {
                                selectedBeds = distributedBeds;
                            }
                        }
                    }
                } else {
                    // Pre-sowing or unassigned batch without physical beds
                    let defPh = (found && found.polyhouse_locations) ? found.polyhouse_locations : 'Main Polyhouse';
                    let defBed = (found && found.bed_numbers) ? found.bed_numbers : 'Bed';
                    let defCavity = 126;
                    let defTray = (isshifted === 1)
                        ? Math.ceil(targetBatchOrderPlants / Math.max(1, defCavity - 1))
                        : Math.ceil(totalBatchPlantsToFulfill / defCavity);
                    if (!defTray || defTray <= 0) defTray = 20;

                    selectedBeds = [{
                        polyhouse_id: '',
                        polyhouse_name: defPh,
                        bed_name: defBed,
                        bed_no: defBed,
                        tray_count: defTray,
                        plants: targetBatchOrderPlants,
                        total_plants: totalBatchPlantsToFulfill,
                        extra_plants: targetBatchExtraPlants
                    }];
                }
            } else {
                selectedBeds = [];
            }

            allSelectedBeds = allSelectedBeds.concat(selectedBeds);

            allocationsList.push({
                stockorder_no: sNo,
                allocated_qty: intQ,
                extra_qty: intExtra,
                psono: psono || (found ? (found.psono || '') : ''),
                clientid: client_id || (found ? (found.clientid || '') : ''),
                product_id: found ? (found.productid || product_id) : product_id,
                product_name: found ? (found.product_name || product_name) : product_name,
                polyhouse_locations: found ? (found.polyhouse_locations || '') : '',
                allocatedbed: selectedBeds
            });
        }

        // Strict validation: Stop immediately if any batch has insufficient bed capacity or unselected beds
        if (validationErrors.length > 0) {
            Swal.fire({
                icon: "warning",
                title: "Correct Bed Selection",
                html: `<div class="text-start" style="font-size: 14px; line-height: 1.6;">${validationErrors.join("<br><br>")}</div>`,
                confirmButtonText: "OK",
                confirmButtonColor: "#f59e0b"
            });
            return;
        }

        let stockorder_no = allBatchKeys.join(', ');
        if (!stockorder_no) {
            stockorder_no = $(`#${prefix}modal_stockorder_no`).val() 
                || $("#modal_stockorder_no").val() 
                || $("#growth_modal_stockorder_no").val() 
                || (currentModalDispatchRow ? (currentModalDispatchRow.effective_stockorderno || currentModalDispatchRow.stockorder_no) : '');
        }
        let allocated_qty = totalAllocatedQty;
        let order_qty = getActiveModalTargetOrderQty() || totalAllocatedQty;

        // Warnings collection for informational confirmation
        let allocWarnings = [];

        if (order_qty > 0 && totalAllocatedQty > order_qty) {
            let over = totalAllocatedQty - order_qty;
            allocWarnings.push(`Total allocated quantity (<b>${totalAllocatedQty.toLocaleString()}</b> plants) exceeds target order quantity (<b>${order_qty.toLocaleString()}</b> plants) by <b>+${over.toLocaleString()}</b> plants.`);
        } else if (order_qty > 0 && totalAllocatedQty < order_qty) {
            let short = order_qty - totalAllocatedQty;
            allocWarnings.push(`Total allocated quantity (<b>${totalAllocatedQty.toLocaleString()}</b> plants) is less than target order quantity (<b>${order_qty.toLocaleString()}</b> plants, short by <b>${short.toLocaleString()}</b> plants).`);
        }

        // Strict Validation: If user selects multiple batches, verify none of them is another farmer's dedicated batch (is_trader != 1)
        if (allBatchKeys.length > 1) {
            for (let k of allBatchKeys) {
                let foundB = (cachedAvailableStockOrders || []).find(s => s.stockorder_no === k);
                if (foundB && parseInt(foundB.is_trader || 0) === 0 && parseInt(foundB.is_own_production_order || 0) !== 1) {
                    let fName = foundB.sown_client_name || foundB.clientid || 'Farmer';
                    Swal.fire({
                        icon: "warning",
                        title: "Farmer Batch Multi-Allocation Not Allowed",
                        html: `Batch <b>${escapeHtml(k)}</b> is a dedicated production order sown for Farmer <b>${escapeHtml(fName)}</b>.<br><br>As per company policy, Farmer orders can only be allocated 1-to-1 (single full batch swap). They cannot be fragmented across multiple batches. Please allocate Trader / Pool batches for multi-batch splitting.`
                    });
                    return;
                }
            }
        }

        // Validate that no batch exceeds physical available balance (Base + Extra <= Avail Bal)
        for (let a of allocationsList) {
            let found = cachedAvailableStockOrders.find(s => s.stockorder_no === a.stockorder_no);
            let avail = found ? (parseInt(found.available_balance_qty !== undefined ? found.available_balance_qty : (found.available_balance || 0))) : 0;
            let totalBatch = a.allocated_qty + (isshifted === 1 ? 0 : a.extra_qty);
            if (totalBatch > avail) {
                allocWarnings.push(`Batch <b>${a.stockorder_no}</b> requested <b>${totalBatch.toLocaleString()}</b> plants (Base: ${a.allocated_qty.toLocaleString()}${a.extra_qty > 0 ? ` + Extra: ${a.extra_qty.toLocaleString()}` : ''}) which exceeds its available balance of <b>${avail.toLocaleString()}</b> plants.`);
            }
        }

        let healthyplant = $(`#${prefix}modal_healthy_plant`).val() || $("#modal_healthy_plant").val() || $("#growth_modal_healthy_plant").val() || totalAllocatedQty;
        let unhealthyplant = $(`#${prefix}modal_unhealthy_plant`).val() || $("#modal_unhealthy_plant").val() || $("#growth_modal_unhealthy_plant").val() || 0;
        let percentextrashifting = 0;
        let extraqtyforshifting = 0;

        if (isshifted === 1) {
            extraqtyforshifting = 0;
            percentextrashifting = 0;
        } else {
            let pctVal = ($(`#${prefix}calc_extra_percent`).val() || $("#calc_extra_percent").val() || $("#growth_calc_extra_percent").val() || "").toString().trim();
            if (pctVal === "" || isNaN(parseFloat(pctVal)) || parseFloat(pctVal) < 0) {
                Swal.fire("Required Field", "Please enter % of Extra Plant for non-shifted trays.", "warning");
                $(`#${prefix}calc_extra_percent, #calc_extra_percent`).focus();
                return;
            }
            percentextrashifting = parseFloat(pctVal);

            let sumBatchExtra = 0;
            allocationsList.forEach(a => { sumBatchExtra += (parseInt(a.extra_qty) || 0); });
            let extraInputVal = parseInt($(`#${prefix}modal_extra_qty`).val()) || parseInt($("#modal_extra_qty").val()) || parseInt($("#growth_modal_extra_qty").val()) || 0;
            extraqtyforshifting = (sumBatchExtra > 0) ? sumBatchExtra : (extraInputVal || Math.round(totalAllocatedQty * (percentextrashifting / 100)));

            if (percentextrashifting > 0 && order_qty > 0) {
                let targetMaxExtra = Math.round(order_qty * (percentextrashifting / 100));
                if (extraqtyforshifting > targetMaxExtra) {
                    allocWarnings.push(`Total extra plants (<b>${extraqtyforshifting.toLocaleString()}</b>) exceeds the target of <b>${targetMaxExtra.toLocaleString()}</b> plants (${percentextrashifting}%).`);
                }
            }
        }

        let remark = $(`#${prefix}modal_allocation_remark`).val() || $("#modal_allocation_remark").val() || $("#growth_modal_allocation_remark").val() || '';
        if (!remark || remark.startsWith("Allocated") || remark.startsWith("Swapped")) {
            remark = `Allocated across ${allBatchKeys.length} batches: ${allBatchKeys.map(s => s + ' (Base: ' + (parseInt(currentBatchAllocations[s] || 0)).toLocaleString() + ', Extra: ' + (parseInt(currentBatchExtraAllocations[s] || 0)).toLocaleString() + ')').join(', ')}`;
        }

        let projectid = $("body").data("projectid") || (typeof CURRENT_APP_PROJECTID !== 'undefined' && CURRENT_APP_PROJECTID ? CURRENT_APP_PROJECTID : 17);
        let loginid = $("body").data("loginid") || (typeof CURRENT_APP_LOGINID !== 'undefined' && CURRENT_APP_LOGINID ? CURRENT_APP_LOGINID : (typeof CURRENT_APP_LOGINNAME !== 'undefined' && CURRENT_APP_LOGINNAME ? CURRENT_APP_LOGINNAME : "admin"));

        let doPostSaveAllocation = function(allowOverride) {
            let originalBtnHtml = btn.html();
            btn.prop("disabled", true).html(`<i class="fa fa-spinner fa-spin me-1"></i> Saving Allocation...`);

            $.ajax({
                url: "modal/finaldispatch_modal.php?action=UpdateAllocation",
                type: "POST",
                data: {
                    projectid: projectid,
                    soi_id: cleanSoiId,
                    psono: psono,
                    sono: sono,
                    client_id: client_id,
                    product_id: product_id,
                    product_name: product_name,
                    stockorder_no: stockorder_no,
                    allocate_stockorder_no: stockorder_no,
                    allocated_psono: allocated_psono,
                    allocated_qty: allocated_qty,
                    order_qty: order_qty,
                    healthyplant: healthyplant,
                    unhealthyplant: unhealthyplant,
                    isshifted: isshifted,
                    percentextrashifting: percentextrashifting,
                    extraqtyforshifting: extraqtyforshifting,
                    remark: remark,
                    loginid: loginid,
                    allow_override: allowOverride ? 1 : 0,
                    allocations_json: JSON.stringify(allocationsList),
                    allocatedbed: JSON.stringify(allSelectedBeds)
                },
                dataType: "json",
                success: function(res) {
                    btn.prop("disabled", false).html(originalBtnHtml);
                    if (res.status === "success") {
                        $("#allocateStockOrderModal").modal("hide");
                        $("#orderGrowthStatusModal").modal("hide");
                        let effectiveSoiId = (res.soi_id && res.soi_id > 0) ? res.soi_id : cleanSoiId;
                        let savedRow = currentModalDispatchRow || {};
                        savedRow.id = effectiveSoiId;
                        savedRow.soi_id = effectiveSoiId;
                        savedRow.effective_stockorderno = stockorder_no;
                        savedRow.stockorder_no = stockorder_no;
                        savedRow.consumed_qty = allocated_qty;
                        savedRow.isshifted = isshifted;
                        savedRow.percentextrashifting = percentextrashifting;
                        savedRow.shiftingqty = extraqtyforshifting;
                        savedRow.allocated_batches = allocationsList;
                        savedRow.allocatedbed = JSON.stringify(allSelectedBeds);

                        if (andOpenInvoice) {
                            loadFinalDispatchSchedule($("#dispatchWeekSelector").val(), "allocated");
                            setTimeout(() => {
                                openPrepareDispatchInvoiceModal(savedRow, effectiveSoiId);
                            }, 350);
                        } else {
                            Swal.fire("Success", res.message || "Allocation updated successfully.", "success");
                            loadFinalDispatchSchedule($("#dispatchWeekSelector").val(), "allocated");
                        }
                    } else {
                        Swal.fire("Error", res.message || "Failed to update allocation.", "error");
                    }
                },
                error: function(xhr, status, error) {
                    btn.prop("disabled", false).html(originalBtnHtml);
                    let errDetail = "Server error while saving allocation.";
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errDetail = xhr.responseJSON.message;
                    } else if (xhr.responseText) {
                        try {
                            let parsed = JSON.parse(xhr.responseText);
                            if (parsed.message) errDetail = parsed.message;
                        } catch(e) {
                            let cleanText = xhr.responseText.replace(/<[^>]*>?/gm, ' ').replace(/\s+/g, ' ').trim();
                            if (cleanText) {
                                errDetail = cleanText.substring(0, 300);
                            }
                        }
                    }
                    Swal.fire("Error", errDetail, "error");
                }
            });
        };

        if (allocWarnings.length > 0) {
            let warningHtml = `
                <div class="text-start" style="font-size: 13px; line-height: 1.5;">
                    <div class="alert alert-warning py-2 px-3 mb-2 border border-warning">
                        <i class="fa fa-exclamation-triangle me-1 text-warning-emphasis"></i>
                        <b>Allocation Quantity Warning:</b>
                    </div>
                    <ul class="mb-2 ps-3 text-dark">
                        ${allocWarnings.map(w => `<li class="mb-1">${w}</li>`).join('')}
                    </ul>
                    <div class="p-2 bg-light rounded border text-muted">
                        Do you want to proceed and save the allocation with the new quantity as given?
                    </div>
                </div>
            `;

            Swal.fire({
                title: 'Confirm Allocation Quantity',
                html: warningHtml,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fa fa-check me-1"></i> Yes, Proceed with New Qty',
                cancelButtonText: '<i class="fa fa-times me-1"></i> Review / Edit',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    doPostSaveAllocation(true);
                }
            });
        } else {
            doPostSaveAllocation(false);
        }
    }

    $(document).on("click", "#saveAllocationBtn, #growthModalSaveAllocationBtn, #growthModalFooterSaveAllocationBtn", function() {
        submitAllocationForm($(this), false);
    });

    $(document).on("click", "#confirmAndPrepareInvoiceBtn, #growthModalSaveAndInvoiceBtn, #growthModalFooterSaveAndInvoiceBtn", function() {
        submitAllocationForm($(this), true);
    });

    // Toggle individual bed selection pill style, enforce fulfillment limit, & auto-check/uncheck batch
    $(document).on("click", ".batch-bed-checkbox", function(e) {
        let sNo = $(this).data("stockno");
        let isChecking = $(this).is(":checked");

        if (isChecking) {
            // User is attempting to check this bed
            let batchAlloc = parseInt(currentBatchAllocations[sNo] || 0);
            let isShifted = $("#growth_shifted_yes").length ? $("#growth_shifted_yes").is(":checked") : $("#shifted_yes").is(":checked");
            let batchExtra = isShifted ? 0 : parseInt(currentBatchExtraAllocations[sNo] || 0);
            let batchTotalReq = batchAlloc + batchExtra;

            let targetOrderQty = getActiveModalTargetOrderQty();

            if (batchTotalReq <= 0) {
                let otherBatchesTotal = 0;
                Object.entries(currentBatchAllocations).forEach(([b, q]) => {
                    if (b !== sNo) otherBatchesTotal += parseInt(q || 0);
                });
                batchTotalReq = Math.max(0, targetOrderQty - otherBatchesTotal);
                if (batchTotalReq <= 0 && targetOrderQty > 0) batchTotalReq = targetOrderQty;
            }

            // Calculate healthy plants capacity of beds already checked in this batch (excluding this one)
            let alreadyCheckedCapacity = 0;
            $(`.batch-bed-checkbox[data-stockno="${sNo}"]:checked`).not(this).each(function() {
                alreadyCheckedCapacity += parseInt($(this).data("plants") || 0);
            });

            // Prompt and block if required quantity is ALREADY fulfilled by selected beds
            if (alreadyCheckedCapacity >= batchTotalReq && batchTotalReq > 0) {
                e.preventDefault();
                $(this).prop("checked", false);
                $(this).closest(".bed-select-pill").removeClass("bed-checked border-success-subtle bg-success-subtle text-success-emphasis");
                
                Swal.fire({
                    icon: "info",
                    title: "Required Quantity Already Fulfilled",
                    html: `Required order quantity (<b>${batchTotalReq.toLocaleString()} Plants</b>) is already fulfilled by selected bed(s) in this batch.<br><br>You can check the checkbox for required order quantity; otherwise, uncheck an existing bed first if you wish to allocate from this bed instead.`,
                    confirmButtonText: "OK",
                    confirmButtonColor: "#10b981"
                });
                return false;
            }

            // Allowed! Style as checked
            $(this).closest(".bed-select-pill").addClass("bed-checked border-success-subtle bg-success-subtle text-success-emphasis");

            // Auto-check batch row if not checked
            let $batchChk = $(`.stockorder-table-checkbox[data-stockno="${sNo}"]`);
            let $row = $(`.stockorder-grid-row[data-stockno="${sNo}"]`);
            let $input = $row.find(".stockorder-alloc-qty-input");
            if (!$batchChk.is(":checked") && !$batchChk.is(":disabled")) {
                $batchChk.prop("checked", true);
                $row.addClass("stockorder-row-selected table-success");
            }

            if (!currentBatchAllocations[sNo] || currentBatchAllocations[sNo] <= 0) {
                let availBal = parseInt($input.data("availbal") || 0);
                let toAlloc = (availBal > 0) ? Math.min(availBal, batchTotalReq) : batchTotalReq;
                if (toAlloc > 0) {
                    currentBatchAllocations[sNo] = toAlloc;
                    $input.val(toAlloc);
                    updateMultiBatchAllocationState();
                }
            }
        } else {
            // Uncheck bed
            $(this).closest(".bed-select-pill").removeClass("bed-checked border-success-subtle bg-success-subtle text-success-emphasis");
            let anyRemaining = $(`.batch-bed-checkbox[data-stockno="${sNo}"]:checked`).length > 0;
            if (!anyRemaining) {
                let $batchChk = $(`.stockorder-table-checkbox[data-stockno="${sNo}"]`);
                let $row = $(`.stockorder-grid-row[data-stockno="${sNo}"]`);
                $batchChk.prop("checked", false);
                $row.removeClass("stockorder-row-selected table-success");
                $row.find(".stockorder-alloc-qty-input").val('');
                $row.find(".stockorder-extra-qty-input").val('');
                delete currentBatchAllocations[sNo];
                delete currentBatchExtraAllocations[sNo];
                updateMultiBatchAllocationState();
                syncExtraPlantsFromBatches();
            }
        }
    });

    // Handler to Go Back to Previously Opened Modal
    $(document).on("click", ".back-to-prev-modal-btn", function() {
        $("#allocateStockOrderModal").modal("hide");
        if (previousOpenedModalBeforeAllocate === '#prepareDispatchInvoiceModal') {
            setTimeout(function() {
                $("#prepareDispatchInvoiceModal").modal("show");
            }, 300);
        } else {
            let rowToOpen = (previousOpenedModalBeforeAllocate === '#orderGrowthStatusModal' && lastSelectedGrowthRow)
                ? lastSelectedGrowthRow
                : (lastSelectedGrowthRow || currentModalDispatchRow);

            if (rowToOpen) {
                setTimeout(function() {
                    loadOrderGrowthStatusModal(rowToOpen);
                }, 300);
            }
        }
    });

    // Cleanup state when Allocate Modal is closed
    $(document).on("hidden.bs.modal", "#allocateStockOrderModal", function() {
        currentBatchAllocations = {};
        currentBatchExtraAllocations = {};
        cachedAvailableStockOrders = [];
        $("#modal_client_order_qty").val("0");
        $("#modal_allocated_qty").val("0");
        $("#modal_soi_id").val("");
        $("#modal_psono").val("");
        $("#modal_client_id").val("");
        $("#modal_product_id").val("");
        $("#multi_alloc_batches_chips").addClass("d-none").empty();
        $("#modalStockOrderInfo").addClass("d-none");
        $("#modalStockOrderInfoContent").empty();
    });

    // Cleanup state when Growth Status Modal is closed
    $(document).on("hidden.bs.modal", "#orderGrowthStatusModal", function() {
        currentBatchAllocations = {};
        currentBatchExtraAllocations = {};
        cachedAvailableStockOrders = [];
        $("#growth_modal_client_order_qty").val("0");
        $("#growth_modal_allocated_qty").val("0");
        $("#growth_modal_soi_id").val("");
        $("#growth_modal_psono").val("");
        $("#growth_modal_client_id").val("");
        $("#growth_modal_product_id").val("");
        $("#growth_multi_alloc_batches_chips").addClass("d-none").empty();
        $("#growth_modalStockOrderInfo").addClass("d-none");
        $("#growth_modalStockOrderInfoContent").empty();
    });

    // ==========================================
    // Re-Schedule Delivery Date Modal Handlers
    // ==========================================
    $(document).on("click", ".btn-reschedule-modal", function(e) {
        e.preventDefault();
        e.stopPropagation();

        let soiId = $(this).data("soiid");
        let isPartialTrigger = $(this).data("is-partial") == 1;
        let explicitPendingQty = $(this).data("pending-qty");
        let parentSoiId = $(this).data("parent-soiid") || soiId;
        let row = $(this).data("row") || getDispatchRowBySoiId(soiId);

        if (!row) {
            Swal.fire("Error", "Could not find order details for this item.", "error");
            return;
        }

        let isReschedCancelled = Boolean(
            row.is_cancelled == 1 ||
            row.dispatch_status_code === 'cancelled' ||
            (row.item_status && row.item_status.toLowerCase() === 'cancelled') ||
            (row.soi_status && row.soi_status.toLowerCase() === 'cancelled') ||
            (row.sales_order_status && row.sales_order_status.toLowerCase() === 'cancelled') ||
            (row.pso_status && row.pso_status.toLowerCase() === 'cancelled')
        );
        if (isReschedCancelled) {
            Swal.fire({
                icon: 'error',
                title: 'Order is Cancelled',
                text: 'This order is cancelled. Delivery date cannot be re-scheduled.',
                confirmButtonColor: '#dc2626'
            });
            return;
        }

        let isPartial = isPartialTrigger || (row.is_partial_pending == 1);
        let pendingQtyVal = explicitPendingQty ? parseInt(explicitPendingQty) : (row.is_partial_pending == 1 ? parseInt(row.order_qty || 0) : parseInt(row.pending_balance_qty || 0));

        $("#reschedule_soi_id").val(row.soi_id || '');
        $("#reschedule_parent_soi_id").val(parentSoiId || row.parent_soi_id || row.soi_id || '');
        $("#reschedule_is_partial").val(isPartial ? 1 : 0);
        $("#reschedule_pending_qty").val(pendingQtyVal || 0);
        $("#reschedule_psono").val(row.psono || '');
        $("#reschedule_sono").val(row.sono || '');
        $("#reschedule_stockorder_no").val(row.effective_stockorderno || row.soi_stockorderno || '');
        $("#reschedule_projectid").val(row.projectid || CURRENT_APP_PROJECTID);

        let cleanProdName = formatProductNameDisplay(row.product_name || '');
        $("#reschedule_modal_product_name").text(cleanProdName);
        let soDisplay = `SO: ${row.sono || '-'} | PSONO: ${row.psono || '-'}`;
        $("#reschedule_modal_so_info").text(soDisplay);
        
        if (isPartial && pendingQtyVal > 0) {
            $("#rescheduleDeliveryDateModalLabel").html(`<i class="fa fa-calendar-alt me-2"></i> Re-Schedule Remaining Balance for Another Day`);
            $("#reschedule_modal_order_qty").html(`<span class="text-primary fw-bold">${pendingQtyVal.toLocaleString()} Plants</span> <span class="badge bg-warning-subtle text-warning-emphasis border border-warning ms-1">Remaining Balance</span>`);
        } else {
            $("#rescheduleDeliveryDateModalLabel").html(`<i class="fa fa-calendar-alt me-2"></i> Re-Schedule Delivery Date`);
            $("#reschedule_modal_order_qty").text(`${parseInt(row.order_qty || 0).toLocaleString()} Plants`);
        }
        $("#reschedule_modal_client_name").text(row.clientname || 'N/A');

        let curDelivDisplay = row.formatted_delivery_date || row.delivery_date || '-';
        if (row.is_rescheduled && row.formatted_original_delivery_date && row.formatted_original_delivery_date !== '-') {
            curDelivDisplay += ` (Orig: ${row.formatted_original_delivery_date})`;
        }
        $("#reschedule_modal_current_date").text(curDelivDisplay);

        let effStock = row.effective_stockorderno || 'Not Allocated';
        $("#reschedule_modal_stock_order").text(effStock);

        let isTraderOrder = (parseInt(row.is_trader) === 1 || (row.client_type && String(row.client_type).toLowerCase() === 'trader'));
        if (isTraderOrder) {
            $("#reschedule_modal_client_type").html(`<span class="badge bg-secondary"><i class="fa fa-user-tag me-1"></i>Trader Order</span>`);
        } else {
            $("#reschedule_modal_client_type").html(`<span class="badge bg-success"><i class="fa fa-user-check me-1"></i>Client Order (Syncs PSO)</span>`);
        }

        let existingRemark = (row.confirm_delivery_date_remark || '').trim();
        if (existingRemark) {
            $("#reschedule_previous_remark_text").text(existingRemark);
            $("#reschedule_previous_remark_container").removeClass("d-none");
        } else {
            $("#reschedule_previous_remark_text").text("");
            $("#reschedule_previous_remark_container").addClass("d-none");
        }

        let defaultNewDate = row.confirm_delivery_date || row.delivery_date || '';
        if (defaultNewDate === '0000-00-00') defaultNewDate = '';
        $("#reschedule_new_delivery_date").val(defaultNewDate);
        $("#reschedule_remark").val("");

        let activeLoginName = CURRENT_APP_LOGINNAME || $("body").data("loginname") || CURRENT_APP_LOGINID || $("body").data("loginid") || "Admin";
        $("#reschedule_modal_loginname_display").text(activeLoginName);

        $("#rescheduleDeliveryDateModal").modal("show");
    });

    $(document).on("click", "#btn_save_reschedule_delivery_date", function() {
        let soiId = parseInt($("#reschedule_soi_id").val()) || 0;
        let psono = $("#reschedule_psono").val() || '';
        let sono = $("#reschedule_sono").val() || '';
        let stockOrderNo = $("#reschedule_stockorder_no").val() || '';
        let projectid = $("#reschedule_projectid").val() || CURRENT_APP_PROJECTID;
        let newDate = $("#reschedule_new_delivery_date").val();
        let remark = ($("#reschedule_remark").val() || "").trim();
        let loginid = $("body").data("loginid") || CURRENT_APP_LOGINID || "adminsystem";
        let loginname = CURRENT_APP_LOGINNAME || $("body").data("loginname") || loginid;

        if (!soiId) {
            Swal.fire("Warning", "Invalid Order Item ID.", "warning");
            return;
        }
        if (!newDate) {
            Swal.fire("Mandatory Field", "Please select the new Re-Scheduled Delivery Date.", "warning");
            $("#reschedule_new_delivery_date").focus();
            return;
        }
        if (!remark) {
            Swal.fire("Mandatory Field", "Please enter a Reason / Remark for re-scheduling the delivery date.", "warning");
            $("#reschedule_remark").focus();
            return;
        }

        let saveBtn = $(this);
        saveBtn.prop("disabled", true).html(`<i class="fa fa-spinner fa-spin me-1"></i> Saving Date...`);

        $.ajax({
            url: "modal/finaldispatch_modal.php?action=UpdateRescheduleDeliveryDate",
            type: "POST",
            dataType: "json",
            data: {
                action: "UpdateRescheduleDeliveryDate",
                soi_id: soiId,
                parent_soi_id: $("#reschedule_parent_soi_id").val() || soiId,
                is_partial_pending: $("#reschedule_is_partial").val() || 0,
                pending_qty: $("#reschedule_pending_qty").val() || 0,
                psono: psono,
                sono: sono,
                stockorder_no: stockOrderNo,
                projectid: projectid,
                new_delivery_date: newDate,
                remark: remark,
                loginid: loginid,
                loginname: loginname
            },
            success: function(resp) {
                saveBtn.prop("disabled", false).html(`<i class="fa fa-save me-1"></i> Update & Re-Schedule Date`);
                if (resp && resp.status === "success") {
                    $("#rescheduleDeliveryDateModal").modal("hide");
                    Swal.fire({
                        icon: "success",
                        title: "Re-Scheduled Successfully",
                        text: resp.message || "Delivery date updated successfully.",
                        timer: 1800,
                        showConfirmButton: false
                    });
                    loadFinalDispatchSchedule($("#dispatchWeekSelector").val() || currentWeekRange);
                } else {
                    Swal.fire("Error", resp.message || "Failed to update delivery date.", "error");
                }
            },
            error: function(xhr, status, error) {
                saveBtn.prop("disabled", false).html(`<i class="fa fa-save me-1"></i> Update & Re-Schedule Date`);
                Swal.fire("Error", "Server error while updating delivery date: " + error, "error");
            }
        });
    });

    // Mark Final Dispatched function with balance check, consumption pre-check & auto-fill cash confirmation
    function executeMarkFinalDispatched(soiId, stockOrder, qty, forceInsert = 0, autoFillCash = 0, row = null, skipPlantDeduction = 0, remainingDeliveryDate = '') {
        if (!canUserMarkDispatch()) {
            Swal.fire({
                icon: 'error',
                title: 'Access Restricted',
                text: 'You do not have permission to mark final dispatch (Restricted to Login Role ID < 3).',
                confirmButtonColor: '#dc2626'
            });
            return;
        }

        let projectid = $("body").data("projectid") || 0;
        let loginid = $("body").data("loginid") || "";
        let loginroleid = parseInt(CURRENT_APP_LOGINROLEID || $("body").data("loginroleid") || 0);

        if (!row && soiId) {
            row = getDispatchRowBySoiId(soiId);
        }

        let cleanSoiId = (row && (row.real_soi_id || row.parent_soi_id)) 
            ? (row.real_soi_id || row.parent_soi_id) 
            : ((typeof soiId === 'string' && soiId.includes('_')) ? parseInt(soiId, 10) : soiId);

        if (!stockOrder && row) {
            stockOrder = row.effective_stockorderno || row.stockorder_no || row.original_sown_stockorderno || '';
        }
        if ((!qty || qty <= 0) && row) {
            qty = parseInt(row.order_qty || row.total_so_pending_qty || row.quantity || 0);
        }

        let isOpenStockVal = (row && (row.is_open_stock || row.stock_type_label === 'Open Stock' || parseInt(row.is_trader) === 1)) ? 1 : 0;
        let isOwnSownVal = (row && (row.is_own_sown || row.stock_type_label === 'Own Sown')) ? 1 : 0;

        Swal.fire({
            title: "Updating Dispatch Status",
            text: "Processing final dispatch...",
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: "modal/finaldispatch_modal.php?action=MarkFinalDispatched",
            type: "POST",
            data: {
                projectid: projectid,
                soi_id: cleanSoiId,
                stockorder_no: stockOrder,
                dispatch_qty: qty,
                remaining_delivery_date: remainingDeliveryDate,
                force_insert: forceInsert,
                autofill_cash: autoFillCash,
                is_open_stock: isOpenStockVal,
                is_own_sown: isOwnSownVal,
                skip_plant_deduction: skipPlantDeduction,
                loginid: loginid,
                loginroleid: loginroleid
            },
            dataType: "json",
            success: function(res) {
                Swal.close();
                if (res.status === "success") {
                    Swal.fire({
                        title: "Dispatched!",
                        text: res.message || "Order item marked as final dispatched successfully.",
                        icon: "success",
                        confirmButtonColor: "#059669"
                    });
                    loadFinalDispatchSchedule($("#dispatchWeekSelector").val(), currentFilter);
                } else if (res.status === "pending_balance") {
                    // Pending Balance Due -> Prompt user to auto-fill cash by adminsystem
                    let psonoText = res.psono || res.sono || 'N/A';
                    let clientText = res.clientname || 'Customer';
                    let pendingAmt = parseFloat(res.pending_balance || 0);
                    let grandTotal = parseFloat(res.grand_total || 0);
                    let totalPaid = parseFloat(res.total_paid || 0);

                    let roleId = parseInt(CURRENT_APP_LOGINROLEID);
                    if (isNaN(roleId)) {
                        roleId = parseInt($("body").data("loginroleid") || 99);
                    }
                    if (roleId < 3) {
                        Swal.fire({
                            title: "Pending Balance Due",
                            html: `Order <code>${escapeHtml(psonoText)}</code> for <b>${escapeHtml(clientText)}</b> has a pending balance of <b>₹ ${pendingAmt.toFixed(2)}</b>.<br><br>
                                   <div class="alert alert-danger py-2.5 px-3 text-start small mb-0 rounded-2 border">
                                       <div class="d-flex justify-content-between mb-1"><span><b>Invoice Grand Total:</b></span> <span class="font-monospace">₹ ${grandTotal.toFixed(2)}</span></div>
                                       <div class="d-flex justify-content-between mb-1"><span><b>Total Received:</b></span> <span class="font-monospace text-success">₹ ${totalPaid.toFixed(2)}</span></div>
                                       <div class="d-flex justify-content-between border-top pt-1"><span><b>Pending Balance:</b></span> <span class="font-monospace fw-bold text-danger">₹ ${pendingAmt.toFixed(2)}</span></div>
                                   </div><br>
                                   Final dispatch <b>cannot be updated</b> while balance is pending.<br><br>
                                   Would you like to <b>auto-fill this pending balance as Cash payment received by adminsystem</b> and proceed with Final Dispatch?`,
                            icon: "warning",
                            showCancelButton: true,
                            confirmButtonColor: "#059669",
                            cancelButtonColor: "#6c757d",
                            confirmButtonText: "<i class='fa fa-money-bill-wave me-1'></i> Yes, Auto-Fill Cash & Dispatch",
                            cancelButtonText: "Cancel (Do Not Dispatch)"
                        }).then((promptRes) => {
                            if (promptRes.isConfirmed) {
                                executeMarkFinalDispatched(cleanSoiId, stockOrder, qty, forceInsert, 1, row, skipPlantDeduction, remainingDeliveryDate);
                            }
                        });
                    } else {
                        Swal.fire({
                            title: "Pending Balance Due",
                            html: `Order <code>${escapeHtml(psonoText)}</code> for <b>${escapeHtml(clientText)}</b> has a pending balance of <b>₹ ${pendingAmt.toFixed(2)}</b>.<br><br>
                                   <div class="alert alert-danger py-2.5 px-3 text-start small mb-0 rounded-2 border">
                                       <div class="d-flex justify-content-between mb-1"><span><b>Invoice Grand Total:</b></span> <span class="font-monospace">₹ ${grandTotal.toFixed(2)}</span></div>
                                       <div class="d-flex justify-content-between mb-1"><span><b>Total Received:</b></span> <span class="font-monospace text-success">₹ ${totalPaid.toFixed(2)}</span></div>
                                       <div class="d-flex justify-content-between border-top pt-1"><span><b>Pending Balance:</b></span> <span class="font-monospace fw-bold text-danger">₹ ${pendingAmt.toFixed(2)}</span></div>
                                   </div><br>
                                   Final dispatch <b>cannot be updated</b> while balance is pending. Please clear the pending balance with the Accounts department first.`,
                            icon: "error",
                            confirmButtonColor: "#dc2626",
                            confirmButtonText: "Close"
                        });
                    }
                } else if (res.status === "missing_consumption") {
                    // Consumption details not found in krf_productionorder_consumed -> Prompt the user
                    let psonoText = res.psono || res.sono || 'N/A';
                    let prodText = res.product_name || 'Selected Product';
                    let itemQty = res.quantity || qty || 0;
                    let batchText = res.stockorder_no || stockOrder || 'Not Allocated';

                    Swal.fire({
                        title: "Consumption Details Not Found",
                        html: `Consumption / Allocation details are <b>not available</b> in <code>krf_productionorder_consumed</code> for:<br><br>
                               <div class="alert alert-warning py-2.5 px-3 text-start small mb-0 rounded-2 border">
                                 <div class="mb-1"><b>Provisional Order:</b> <span class="text-dark font-monospace fw-bold">${escapeHtml(psonoText)}</span></div>
                                 <div class="mb-1"><b>Product:</b> <span class="text-dark fw-bold">${escapeHtml(prodText)}</span></div>
                                 <div class="mb-1"><b>Quantity:</b> <span class="text-dark fw-bold">${itemQty.toLocaleString()} Plants</span></div>
                                 <div><b>Production Batch:</b> <span class="badge bg-secondary font-monospace">${escapeHtml(batchText)}</span></div>
                                </div><br>
                                Do you want to automatically <b>create the consumption entry</b> against this <code>krf_sales_order_items</code> record and proceed to mark <b>Final Dispatch</b>?`,
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#059669",
                        cancelButtonColor: "#6c757d",
                        confirmButtonText: "<i class='fa fa-check-circle me-1'></i> Yes, Insert & Mark Dispatched",
                        cancelButtonText: "Cancel"
                    }).then((promptRes) => {
                        if (promptRes.isConfirmed) {
                            executeMarkFinalDispatched(cleanSoiId, stockOrder, qty, 1, autoFillCash, row, skipPlantDeduction, remainingDeliveryDate);
                        }
                    });
                } else {
                    Swal.fire("Error", res.message || "Failed to mark as dispatched.", "error");
                }
            },
            error: function(xhr, status, error) {
                Swal.close();
                let errMsg = "Server error while updating dispatch status.";
                if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                    errMsg = xhr.responseJSON.message;
                } else if (xhr && xhr.responseText) {
                    try {
                        let parsed = JSON.parse(xhr.responseText);
                        if (parsed.message) errMsg = parsed.message;
                    } catch(e) {}
                }
                Swal.fire("Error", errMsg, "error");
            }
        });
    }

    // Mark Final Dispatched click handler with rich confirmation modal
    $(document).on("click", ".mark-dispatched-btn", function() {
        if (!canUserMarkDispatch()) {
            Swal.fire({
                icon: 'error',
                title: 'Access Restricted',
                text: 'You do not have permission to mark final dispatch (Restricted to Login Role ID < 3).',
                confirmButtonColor: '#dc2626'
            });
            return;
        }

        let rawSoiId = $(this).data("soiid");
        let stockOrder = $(this).data("stockorder");
        let qty = parseInt($(this).data("qty") || 0);
        let row = $(this).data("row") || getDispatchRowBySoiId(rawSoiId) || {};
        let cleanSoiId = (row && (row.real_soi_id || row.parent_soi_id)) 
            ? (row.real_soi_id || row.parent_soi_id) 
            : ((typeof rawSoiId === 'string' && rawSoiId.includes('_')) ? parseInt(rawSoiId, 10) : rawSoiId);

        if (qty <= 0) {
            qty = parseInt(row.order_qty || row.total_so_pending_qty || row.quantity || 0);
        }

        let isRowCancelled = Boolean(
            row.is_cancelled == 1 ||
            row.dispatch_status_code === 'cancelled' ||
            (row.item_status && row.item_status.toLowerCase() === 'cancelled') ||
            (row.soi_status && row.soi_status.toLowerCase() === 'cancelled') ||
            (row.sales_order_status && row.sales_order_status.toLowerCase() === 'cancelled') ||
            (row.pso_status && row.pso_status.toLowerCase() === 'cancelled')
        );
        if (isRowCancelled) {
            Swal.fire({
                icon: 'error',
                title: 'Order is Cancelled',
                text: row.cancel_reason || 'This order is cancelled. Dispatch actions are stopped.',
                confirmButtonColor: '#dc2626'
            });
            return;
        }

        let psono = row.psono || '-';
        let sono = row.sono || '';
        let clientName = row.clientname || row.client_name || 'Customer';
        let clientId = row.clientid || '';
        let productName = row.product_name || row.productname || 'Selected Product';
        let effectiveBatch = row.effective_stockorderno || stockOrder || row.stockorder_no || row.original_sown_stockorderno || 'Not Allocated';

        let isOpenStock = Boolean(row.is_open_stock || row.stock_type_label === 'Open Stock' || parseInt(row.is_trader) === 1);
        let isOwnSown = Boolean(row.is_own_sown || row.stock_type_label === 'Own Sown');
        let isSwapped = Boolean(row.is_swapped || row.stock_type_label === 'Swapped');

        let batchBadgeHtml = '';
        let operationalNoticeHtml = '';

        if (isOwnSown) {
            batchBadgeHtml = `<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="fa fa-check-circle me-1"></i> Own Production Order</span>`;
            operationalNoticeHtml = `
                <div class="alert alert-success py-2 px-3 small text-start mb-2 border rounded-2" style="font-size: 11.5px; line-height: 1.45;">
                    <i class="fa fa-info-circle text-success me-1"></i> <b>Own Production Order:</b> This batch was sown specifically for this client. Marking final dispatch will set the <b>entire production stock order</b> and all associated polyhouse trays to <b>'Final Dispatch'</b>.
                </div>
            `;
        } else if (isOpenStock) {
            batchBadgeHtml = `<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1"><i class="fa fa-store me-1"></i> Allocated to OpenStock / Trader</span>`;
            operationalNoticeHtml = `
                <div class="alert alert-info py-2 px-3 small text-start mb-2 border rounded-2" style="font-size: 11.5px; line-height: 1.45;">
                    <i class="fa fa-info-circle text-primary me-1"></i> <b>Open Stock / Trader Batch:</b> This item is allocated from Open Stock. Only the <b>exact number of trays required for ${qty.toLocaleString()} plants</b> will be updated to <b>'Final Dispatch'</b>; remaining trays will remain <b>'Ready to Sale'</b> in the polyhouse.
                </div>
            `;
        } else if (isSwapped) {
            batchBadgeHtml = `<span class="badge bg-warning-subtle text-warning-emphasis border border-warning px-2 py-1"><i class="fa fa-exchange-alt me-1"></i> Swapped Production Batch</span>`;
            operationalNoticeHtml = `
                <div class="alert alert-warning py-2 px-3 small text-start mb-2 border rounded-2" style="font-size: 11.5px; line-height: 1.45;">
                    <i class="fa fa-exclamation-triangle text-warning me-1"></i> <b>Swapped Batch:</b> This item is allocated from swapped batch <code>${escapeHtml(effectiveBatch)}</code>. Trays for <b>${qty.toLocaleString()} plants</b> will be marked as <b>'Final Dispatch'</b> against this client.
                </div>
            `;
        } else {
            batchBadgeHtml = `<span class="badge bg-secondary px-2 py-1">${escapeHtml(row.stock_type_label || 'Allocated Batch')}</span>`;
            operationalNoticeHtml = `
                <div class="alert alert-secondary py-2 px-3 small text-start mb-2 border rounded-2" style="font-size: 11.5px; line-height: 1.45;">
                    <i class="fa fa-info-circle me-1"></i> Trays for <b>${qty.toLocaleString()} plants</b> in batch <code>${escapeHtml(effectiveBatch)}</code> will be marked as <b>'Final Dispatch'</b>.
                </div>
            `;
        }

        let defaultRemDate = (row.delivery_date && row.delivery_date !== '0000-00-00') ? row.delivery_date : new Date(Date.now() + 7*86400000).toISOString().split('T')[0];

        let modalHtml = `
            <div class="text-start">
                <div class="card bg-light border mb-2 shadow-none">
                    <div class="card-body p-2.5" style="font-size: 12px;">
                        <div class="d-flex justify-content-between align-items-center mb-1 pb-1 border-bottom">
                            <span class="text-muted">Client:</span>
                            <span class="fw-bold text-dark">${escapeHtml(clientName)} ${clientId ? `<span class="text-muted">(${escapeHtml(clientId)})</span>` : ''}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-1 pb-1 border-bottom">
                            <span class="text-muted">Order:</span>
                            <span class="font-monospace fw-bold text-primary">${escapeHtml(psono)} ${sono ? `<span class="badge bg-light text-dark border">SO: ${escapeHtml(sono)}</span>` : ''}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-1 pb-1 border-bottom">
                            <span class="text-muted">Product:</span>
                            <span class="fw-semibold text-dark text-truncate" style="max-width: 250px;" title="${escapeHtml(productName)}">${escapeHtml(productName)}</span>
                        </div>
                        <div class="mb-2 pb-2 border-bottom">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-muted fw-semibold">Dispatch Quantity:</span>
                                <span class="badge bg-dark font-monospace">Available: ${qty.toLocaleString()} Plants</span>
                            </div>
                            <div class="input-group input-group-sm">
                                <input type="number" id="swal_dispatch_qty" class="form-control text-center fw-bold fs-6" 
                                       value="${qty}" min="1" max="${qty}" style="border: 1.5px solid #059669;">
                                <button class="btn btn-outline-secondary" type="button" id="swal_btn_set_full" title="Dispatch full available quantity">
                                    All (${qty.toLocaleString()})
                                </button>
                            </div>
                            <div id="swal_qty_error" class="text-danger small fw-semibold mt-1" style="display:none;"></div>
                        </div>

                        <div id="swal_staggered_section" class="alert alert-info py-2 px-3 small text-start mb-2 border rounded-2" style="display:none; font-size: 11.5px; line-height: 1.45;">
                            <div class="fw-bold mb-1 text-primary">
                                <i class="fa fa-calendar-alt me-1"></i> <b>Staggered / Partial Delivery:</b>
                            </div>
                            Delivering <b id="swal_delivering_qty_text" class="text-dark">0</b> plants today. The remaining balance of <b class="text-danger" id="swal_remaining_qty_text">0</b> plants will be rescheduled on timetable for:
                            <div class="mt-2">
                                <label class="form-label text-dark fw-bold mb-1" style="font-size: 11px;">Delivery Date for Remaining Plants:</label>
                                <input type="date" id="swal_remaining_delivery_date" class="form-control form-control-sm" value="${defaultRemDate}">
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Production Batch:</span>
                            <div class="d-flex align-items-center gap-1">
                                <span class="badge bg-dark font-monospace">${escapeHtml(effectiveBatch)}</span>
                                ${batchBadgeHtml}
                            </div>
                        </div>
                    </div>
                </div>

                ${operationalNoticeHtml}

                <div class="text-muted small text-center" style="font-size: 11px;">
                    <i class="fa fa-file-invoice text-primary me-1"></i> A final dispatch invoice will be recorded in <code>sales_invoice</code> with status <b>'dispatched'</b>.
                </div>
            </div>
        `;

        Swal.fire({
            title: "Confirm Final Dispatch?",
            html: modalHtml,
            icon: "question",
            width: "560px",
            showCancelButton: true,
            confirmButtonColor: "#059669",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "<i class='fa fa-truck me-1'></i> Yes, Mark Dispatched",
            cancelButtonText: "Cancel",
            focusCancel: true,
            didOpen: () => {
                let $input = $("#swal_dispatch_qty");
                let $btnAll = $("#swal_btn_set_full");
                let $staggered = $("#swal_staggered_section");
                let $deliveringText = $("#swal_delivering_qty_text");
                let $remainingText = $("#swal_remaining_qty_text");
                let $err = $("#swal_qty_error");

                function updatePartialState() {
                    let val = parseInt($input.val()) || 0;
                    if (val <= 0) {
                        $err.text("Dispatch quantity must be greater than 0.").show();
                        $staggered.hide();
                    } else if (val > qty) {
                        $err.text(`Quantity cannot exceed available ${qty.toLocaleString()} plants.`).show();
                        $staggered.hide();
                    } else {
                        $err.hide();
                        if (val < qty) {
                            let rem = qty - val;
                            $deliveringText.text(val.toLocaleString());
                            $remainingText.text(rem.toLocaleString());
                            $staggered.slideDown(150);
                        } else {
                            $staggered.slideUp(150);
                        }
                    }
                }

                $input.on("input change", updatePartialState);
                $btnAll.on("click", function() {
                    $input.val(qty);
                    updatePartialState();
                });
                updatePartialState();
            },
            preConfirm: () => {
                let val = parseInt($("#swal_dispatch_qty").val()) || 0;
                if (val <= 0) {
                    Swal.showValidationMessage("Please enter a valid dispatch quantity greater than 0.");
                    return false;
                }
                if (val > qty) {
                    Swal.showValidationMessage(`Quantity cannot exceed available ${qty.toLocaleString()} plants.`);
                    return false;
                }
                let remDate = $("#swal_remaining_delivery_date").val();
                if (val < qty && !remDate) {
                    Swal.showValidationMessage("Please select a delivery date for the remaining plants balance.");
                    return false;
                }
                return {
                    dispatch_qty: val,
                    remaining_delivery_date: remDate
                };
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                executeMarkFinalDispatched(cleanSoiId, effectiveBatch, result.value.dispatch_qty, 0, 0, row, 0, result.value.remaining_delivery_date);
            }
        });
    });

    // Mark Final Dispatched Without Plant Deduction click handler
    $(document).on("click", ".mark-dispatched-no-deduct-btn", function() {
        if (!canUserMarkDispatch()) {
            Swal.fire({
                icon: 'error',
                title: 'Access Restricted',
                text: 'You do not have permission to mark final dispatch (Restricted to Login Role ID < 3).',
                confirmButtonColor: '#dc2626'
            });
            return;
        }

        let rawSoiId = $(this).data("soiid");
        let stockOrder = $(this).data("stockorder");
        let qty = parseInt($(this).data("qty") || 0);
        let row = $(this).data("row") || getDispatchRowBySoiId(rawSoiId) || {};
        let cleanSoiId = (row && (row.real_soi_id || row.parent_soi_id)) 
            ? (row.real_soi_id || row.parent_soi_id) 
            : ((typeof rawSoiId === 'string' && rawSoiId.includes('_')) ? parseInt(rawSoiId, 10) : rawSoiId);

        if (qty <= 0) {
            qty = parseInt(row.order_qty || row.total_so_pending_qty || row.quantity || 0);
        }

        let isRowCancelled = Boolean(
            row.is_cancelled == 1 ||
            row.dispatch_status_code === 'cancelled' ||
            (row.item_status && row.item_status.toLowerCase() === 'cancelled') ||
            (row.soi_status && row.soi_status.toLowerCase() === 'cancelled') ||
            (row.sales_order_status && row.sales_order_status.toLowerCase() === 'cancelled') ||
            (row.pso_status && row.pso_status.toLowerCase() === 'cancelled')
        );
        if (isRowCancelled) {
            Swal.fire({
                icon: 'error',
                title: 'Order is Cancelled',
                text: row.cancel_reason || 'This order is cancelled. Dispatch actions are stopped.',
                confirmButtonColor: '#dc2626'
            });
            return;
        }

        let psono = row.psono || '-';
        let sono = row.sono || '';
        let clientName = row.clientname || row.client_name || 'Customer';
        let clientId = row.clientid || '';
        let productName = row.product_name || row.productname || 'Selected Product';
        let effectiveBatch = row.effective_stockorderno || stockOrder || row.stockorder_no || row.original_sown_stockorderno || 'Not Allocated';
        let defaultRemDate = (row.delivery_date && row.delivery_date !== '0000-00-00') ? row.delivery_date : new Date(Date.now() + 7*86400000).toISOString().split('T')[0];

        let modalHtml = `
            <div class="text-start">
                <div class="alert alert-warning py-2.5 px-3 small text-start mb-2 border rounded-2" style="font-size: 12px; line-height: 1.45; background-color: #fffbeb; border-color: #fde68a; color: #92400e;">
                    <div class="fw-bold mb-1"><i class="fa fa-exclamation-triangle text-warning me-1"></i> <b>WITHOUT PLANT DEDUCTION MODE</b></div>
                    This action will update order items, sales invoice, and stock order counts to <b>'Dispatched'</b>, but <b>NO trays or plants will be deducted in the polyhouse</b> (<code>krf_polyhouse_plant_details</code> remains untouched).
                </div>

                <div class="card bg-light border mb-2 shadow-none">
                    <div class="card-body p-2.5" style="font-size: 12px;">
                        <div class="d-flex justify-content-between align-items-center mb-1 pb-1 border-bottom">
                            <span class="text-muted">Client:</span>
                            <span class="fw-bold text-dark">${escapeHtml(clientName)} ${clientId ? `<span class="text-muted">(${escapeHtml(clientId)})</span>` : ''}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-1 pb-1 border-bottom">
                            <span class="text-muted">Order:</span>
                            <span class="font-monospace fw-bold text-primary">${escapeHtml(psono)} ${sono ? `<span class="badge bg-light text-dark border">SO: ${escapeHtml(sono)}</span>` : ''}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-1 pb-1 border-bottom">
                            <span class="text-muted">Product:</span>
                            <span class="fw-semibold text-dark text-truncate" style="max-width: 250px;" title="${escapeHtml(productName)}">${escapeHtml(productName)}</span>
                        </div>
                        <div class="mb-2 pb-2 border-bottom">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-muted fw-semibold">Dispatch Quantity:</span>
                                <span class="badge bg-warning text-dark font-monospace">Available: ${qty.toLocaleString()} Plants</span>
                            </div>
                            <div class="input-group input-group-sm">
                                <input type="number" id="swal_dispatch_qty" class="form-control text-center fw-bold fs-6" 
                                       value="${qty}" min="1" max="${qty}" style="border: 1.5px solid #d97706;">
                                <button class="btn btn-outline-secondary" type="button" id="swal_btn_set_full" title="Dispatch full available quantity">
                                    All (${qty.toLocaleString()})
                                </button>
                            </div>
                            <div id="swal_qty_error" class="text-danger small fw-semibold mt-1" style="display:none;"></div>
                        </div>

                        <div id="swal_staggered_section" class="alert alert-info py-2 px-3 small text-start mb-2 border rounded-2" style="display:none; font-size: 11.5px; line-height: 1.45;">
                            <div class="fw-bold mb-1 text-primary">
                                <i class="fa fa-calendar-alt me-1"></i> <b>Staggered / Partial Delivery:</b>
                            </div>
                            Delivering <b id="swal_delivering_qty_text" class="text-dark">0</b> plants today. The remaining balance of <b class="text-danger" id="swal_remaining_qty_text">0</b> plants will be rescheduled on timetable for:
                            <div class="mt-2">
                                <label class="form-label text-dark fw-bold mb-1" style="font-size: 11px;">Delivery Date for Remaining Plants:</label>
                                <input type="date" id="swal_remaining_delivery_date" class="form-control form-control-sm" value="${defaultRemDate}">
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Production Batch:</span>
                            <span class="badge bg-dark font-monospace">${escapeHtml(effectiveBatch)}</span>
                        </div>
                    </div>
                </div>

                <div class="text-muted small text-center" style="font-size: 11px;">
                    <i class="fa fa-file-invoice text-primary me-1"></i> A final dispatch invoice will be recorded in <code>sales_invoice</code> without altering polyhouse tray inventory.
                </div>
            </div>
        `;

        Swal.fire({
            title: "Confirm Dispatch Without Plant Deduction?",
            html: modalHtml,
            icon: "warning",
            width: "560px",
            showCancelButton: true,
            confirmButtonColor: "#d97706",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "<i class='fa fa-truck-loading me-1'></i> Yes, Mark Dispatched (No Plant Deduction)",
            cancelButtonText: "Cancel",
            focusCancel: true,
            didOpen: () => {
                let $input = $("#swal_dispatch_qty");
                let $btnAll = $("#swal_btn_set_full");
                let $staggered = $("#swal_staggered_section");
                let $deliveringText = $("#swal_delivering_qty_text");
                let $remainingText = $("#swal_remaining_qty_text");
                let $err = $("#swal_qty_error");

                function updatePartialState() {
                    let val = parseInt($input.val()) || 0;
                    if (val <= 0) {
                        $err.text("Dispatch quantity must be greater than 0.").show();
                        $staggered.hide();
                    } else if (val > qty) {
                        $err.text(`Quantity cannot exceed available ${qty.toLocaleString()} plants.`).show();
                        $staggered.hide();
                    } else {
                        $err.hide();
                        if (val < qty) {
                            let rem = qty - val;
                            $deliveringText.text(val.toLocaleString());
                            $remainingText.text(rem.toLocaleString());
                            $staggered.slideDown(150);
                        } else {
                            $staggered.slideUp(150);
                        }
                    }
                }

                $input.on("input change", updatePartialState);
                $btnAll.on("click", function() {
                    $input.val(qty);
                    updatePartialState();
                });
                updatePartialState();
            },
            preConfirm: () => {
                let val = parseInt($("#swal_dispatch_qty").val()) || 0;
                if (val <= 0) {
                    Swal.showValidationMessage("Please enter a valid dispatch quantity greater than 0.");
                    return false;
                }
                if (val > qty) {
                    Swal.showValidationMessage(`Quantity cannot exceed available ${qty.toLocaleString()} plants.`);
                    return false;
                }
                let remDate = $("#swal_remaining_delivery_date").val();
                if (val < qty && !remDate) {
                    Swal.showValidationMessage("Please select a delivery date for the remaining plants balance.");
                    return false;
                }
                return {
                    dispatch_qty: val,
                    remaining_delivery_date: remDate
                };
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                executeMarkFinalDispatched(cleanSoiId, effectiveBatch, result.value.dispatch_qty, 0, 0, row, 1, result.value.remaining_delivery_date);
            }
        });
    });

    // =========================================================================
    // PENDING QUANTITY WORKFLOW: DISPATCH & ORDER CLOSURE / REMARKS
    // =========================================================================

    // 1. Click on Pending Quantity Badge (.clickable-pending-qty)
    $(document).on("click", ".clickable-pending-qty", function(e) {
        e.stopPropagation();
        let soiId = $(this).data("soiid");
        let psono = $(this).data("psono") || '';
        let productId = $(this).data("productid") || '';
        let orderQty = parseInt($(this).data("orderqty") || 0);
        let allocQty = parseInt($(this).data("allocqty") || 0);
        let dispQty = parseInt($(this).data("dispqty") || 0);
        let pendingQty = parseInt($(this).data("pendingqty") || 0);
        let row = $(this).data("row") || getDispatchRowBySoiId(soiId) || {};

        if (pendingQty <= 0) {
            Swal.fire({
                icon: 'info',
                title: 'No Pending Quantity',
                text: 'This sales order line has no remaining pending quantity to process.',
                timer: 2000,
                showConfirmButton: false
            });
            return;
        }

        let isRowCancelled = Boolean(
            row.is_cancelled == 1 ||
            row.dispatch_status_code === 'cancelled' ||
            (row.item_status && row.item_status.toLowerCase() === 'cancelled') ||
            (row.soi_status && row.soi_status.toLowerCase() === 'cancelled') ||
            (row.sales_order_status && row.sales_order_status.toLowerCase() === 'cancelled') ||
            (row.pso_status && row.pso_status.toLowerCase() === 'cancelled')
        );
        if (isRowCancelled) {
            Swal.fire({
                icon: 'error',
                title: 'Order is Cancelled',
                text: row.cancel_reason || 'This order is cancelled. Dispatch actions are stopped.',
                confirmButtonColor: '#dc2626'
            });
            return;
        }

        let clientName = row.clientname || row.client_name || 'Customer';
        let productName = row.product_name || row.productname || 'Selected Product';
        let sono = row.sono || '';

        let modalContent = `
            <div class="text-start">
                <div class="card bg-light border mb-3 shadow-none">
                    <div class="card-body p-2.5" style="font-size: 12.5px;">
                        <div class="d-flex justify-content-between mb-1 pb-1 border-bottom">
                            <span class="text-muted">Client:</span>
                            <span class="fw-bold text-dark">${escapeHtml(clientName)}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1 pb-1 border-bottom">
                            <span class="text-muted">Order:</span>
                            <span class="font-monospace fw-bold text-primary">${escapeHtml(psono)} ${sono ? `<span class="badge bg-light text-dark border">SO: ${escapeHtml(sono)}</span>` : ''}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1 pb-1 border-bottom">
                            <span class="text-muted">Product:</span>
                            <span class="fw-semibold text-dark">${escapeHtml(productName)}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Quantities:</span>
                            <div class="d-flex gap-1" style="font-size: 11px;">
                                <span class="badge bg-dark">Order: ${orderQty.toLocaleString()}</span>
                                <span class="badge bg-success">Alloc: ${allocQty.toLocaleString()}</span>
                                <span class="badge bg-primary">Disp: ${dispQty.toLocaleString()}</span>
                                <span class="badge bg-danger">Pending: ${pendingQty.toLocaleString()}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-secondary small mb-2 fw-semibold text-center">
                    Please select an action for the remaining <b>${pendingQty.toLocaleString()} pending plants</b>:
                </div>
            </div>
        `;

        Swal.fire({
            title: `<div class="d-flex align-items-center justify-content-center gap-2">
                        <span class="badge bg-danger px-3 py-1.5 fs-6 shadow-sm"><i class="fa fa-clock me-1"></i> Pending Qty: ${pendingQty.toLocaleString()} Plants</span>
                    </div>`,
            html: modalContent,
            showDenyButton: true,
            showCancelButton: true,
            confirmButtonColor: '#059669',
            denyButtonColor: '#be123c',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fa fa-truck me-1"></i> Update Final Dispatched',
            denyButtonText: '<i class="fa fa-edit me-1"></i> Pending Qty Not Required / Edit SO',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                promptDispatchForPendingQty(row, soiId, pendingQty);
            } else if (result.isDenied) {
                setTimeout(function() {
                    openClosePendingQtyModal(row, soiId);
                }, 200);
            }
        });
    });

    // Helper: Dispatch Option Prompt for Pending Quantity
    function promptDispatchForPendingQty(row, soiId, pendingQty) {
        let cleanSoiId = (row && (row.real_soi_id || row.parent_soi_id)) ? (row.real_soi_id || row.parent_soi_id) : soiId;
        let effectiveBatch = row.effective_stockorderno || row.stockorder_no || '';
        let psono = row.psono || '-';
        let clientName = row.clientname || row.client_name || 'Customer';

        Swal.fire({
            title: "Final Dispatch Pending Quantity",
            html: `
                <div class="text-start">
                    <div class="alert alert-info py-2 px-3 small mb-3 border rounded-2" style="font-size: 12px; line-height: 1.45;">
                        <i class="fa fa-info-circle text-primary me-1"></i> Dispatching remaining pending quantity of <b>${pendingQty.toLocaleString()} plants</b> for order <b>${escapeHtml(psono)}</b> (${escapeHtml(clientName)}).
                    </div>
                    <div class="d-grid gap-2">
                        <button type="button" id="swal_btn_dispatch_normal" class="btn btn-success text-white py-2 px-3 text-start fw-bold shadow-sm" style="font-size: 12.5px;">
                            <i class="fa fa-truck me-2"></i> Standard Final Dispatch (With Plant Deduction)
                            <div class="fw-normal small text-white-50" style="font-size: 11px;">Generates final dispatch invoice & updates polyhouse tray status</div>
                        </button>
                        <button type="button" id="swal_btn_dispatch_no_deduct" class="btn btn-outline-warning text-dark py-2 px-3 text-start fw-bold shadow-sm" style="font-size: 12.5px; background-color: #fffbeb; border: 1.5px solid #d97706; color: #92400e !important;">
                            <i class="fa fa-truck-loading me-2"></i> Final Dispatch Without Plant Deduction
                            <div class="fw-normal small text-muted" style="font-size: 11px;">Generates final dispatch invoice without altering polyhouse inventory</div>
                        </button>
                    </div>
                </div>
            `,
            showConfirmButton: false,
            showCancelButton: true,
            cancelButtonText: "Cancel",
            cancelButtonColor: "#6c757d",
            didOpen: () => {
                const btnNorm = document.getElementById('swal_btn_dispatch_normal');
                if (btnNorm) {
                    btnNorm.addEventListener('click', () => {
                        Swal.close();
                        executeMarkFinalDispatched(cleanSoiId, effectiveBatch, pendingQty, 0, 0, row, 0);
                    });
                }
                const btnNoDed = document.getElementById('swal_btn_dispatch_no_deduct');
                if (btnNoDed) {
                    btnNoDed.addEventListener('click', () => {
                        Swal.close();
                        executeMarkFinalDispatched(cleanSoiId, effectiveBatch, pendingQty, 0, 0, row, 1);
                    });
                }
            }
        });
    }

    // 2. Click on "Pending Qty Not Required / Edit SO" button (.close-pending-qty-btn)
    $(document).on("click", ".close-pending-qty-btn", function(e) {
        e.stopPropagation();
        let soiId = $(this).data("soiid");
        let row = $(this).data("row") || getDispatchRowBySoiId(soiId) || {};
        openClosePendingQtyModal(row, soiId);
    });

    // Helper: Safely show Close Pending Quantity Modal
    function showClosePendingQtyModal() {
        let $m = $("#closePendingQtyModal");
        try {
            if ($.fn.modal) {
                $m.modal("show");
            } else if (window.bootstrap && bootstrap.Modal) {
                let modalObj = bootstrap.Modal.getInstance($m[0]) || new bootstrap.Modal($m[0]);
                modalObj.show();
            } else {
                $m.show().addClass("show");
            }
        } catch(e) {
            console.warn("Bootstrap modal show fallback:", e);
            try { $m.modal("show"); } catch(err) { $m.show().addClass("show"); }
        }
        $("body").addClass("modal-open");
    }

    // Helper: Safely hide Close Pending Quantity Modal
    function hideClosePendingQtyModal() {
        let $m = $("#closePendingQtyModal");
        try {
            if ($.fn.modal) {
                $m.modal("hide");
            } else if (window.bootstrap && bootstrap.Modal) {
                let modalObj = bootstrap.Modal.getInstance($m[0]);
                if (modalObj) modalObj.hide();
            }
        } catch(e) {}
        $m.hide().removeClass("show");
        $(".modal-backdrop").remove();
        $("body").removeClass("modal-open").css("overflow", "").css("padding-right", "");
    }

    // 3. Populate and show Close Pending Qty Modal
    function openClosePendingQtyModal(row, soiId) {
        if (typeof row === 'string') {
            try { row = JSON.parse(row); } catch(e) {}
        }
        if ((!row || !row.soi_id || !row.psono) && soiId) {
            let found = getDispatchRowBySoiId(soiId);
            if (found) row = found;
        }
        if (!row) row = {};

        let cleanSoiId = (row && (row.real_soi_id || row.parent_soi_id)) 
            ? (row.real_soi_id || row.parent_soi_id) 
            : ((typeof soiId === 'string' && soiId.includes('_')) ? parseInt(soiId) : soiId);

        let psono = row.psono || '';
        let sono = row.sono || '';
        let productId = row.product_id || '';
        let productName = row.product_name || row.productname || 'Selected Product';
        let clientName = row.clientname || row.client_name || '-';
        let deliveryDate = row.formatted_delivery_date || row.delivery_date || '-';
        let projectId = row.projectid || $("body").data("projectid") || 0;

        let totalOrderQty = parseInt(row.total_so_order_qty !== undefined ? row.total_so_order_qty : (row.original_total_order_qty || row.order_qty || 0));
        let totalAllocQty = parseInt(row.total_so_allocated_qty !== undefined ? row.total_so_allocated_qty : (row.allocated_plant_qty !== undefined ? row.allocated_plant_qty : Math.max(0, parseInt(row.reserved_qty || 0) - parseInt(row.consumed_qty || 0))));
        let totalDispQty = parseInt(row.total_so_dispatched_qty !== undefined ? row.total_so_dispatched_qty : (row.dispatched_plant_qty !== undefined ? row.dispatched_plant_qty : parseInt(row.consumed_qty || 0)));
        let totalPendingQty = parseInt(row.total_so_pending_qty !== undefined ? row.total_so_pending_qty : Math.max(0, totalOrderQty - (totalAllocQty + totalDispQty)));
        let fulfilledQty = totalAllocQty + totalDispQty;

        // Hidden input population
        $("#cpq_soi_id").val(cleanSoiId);
        $("#cpq_psono").val(psono);
        $("#cpq_sono").val(sono);
        $("#cpq_product_id").val(productId);
        $("#cpq_projectid").val(projectId);
        $("#cpq_fulfilled_qty").val(fulfilledQty);
        $("#cpq_pending_qty").val(totalPendingQty);

        // Text & Badge bindings
        $("#cpq_modal_product_name").text(productName);
        $("#cpq_modal_so_info").html(`PSONO: <b>${escapeHtml(psono)}</b> ${sono ? `| SO: <b>${escapeHtml(sono)}</b>` : ''} | Item ID: #${cleanSoiId}`);
        $("#cpq_modal_client_name").text(clientName);
        $("#cpq_modal_delivery_date").text(deliveryDate);
        $("#cpq_modal_pending_badge").text(`Pending: ${totalPendingQty.toLocaleString()} Plants`);

        $("#cpq_modal_order_qty").text(totalOrderQty.toLocaleString());
        $("#cpq_modal_alloc_qty").text(totalAllocQty.toLocaleString());
        $("#cpq_modal_disp_qty").text(totalDispQty.toLocaleString());
        $("#cpq_modal_pending_qty").text(totalPendingQty.toLocaleString());

        $("#cpq_fulfilled_label").text(fulfilledQty.toLocaleString());
        $("#cpq_pending_label").text(totalPendingQty.toLocaleString());
        $("#cpq_pending_label2").text(totalPendingQty.toLocaleString());

        // Initialize custom revised quantity input
        $("#cpq_custom_new_qty").attr("max", totalOrderQty).val(fulfilledQty);
        $("#cpq_btn_fulfilled_val").text(fulfilledQty.toLocaleString());
        $("#cpq_summary_orig").text(totalOrderQty.toLocaleString());
        $("#cpq_summary_revised").text(fulfilledQty.toLocaleString());
        $("#cpq_summary_dropped").text(Math.max(0, totalOrderQty - fulfilledQty).toLocaleString());

        // Default radio to revise_order_qty
        $("#cpq_action_revise").prop("checked", true);
        $("#cpq_remark").val("");
        $("#cpq_remark_error").hide();
        $("#btn_save_close_pending_qty").prop("disabled", false).html('<i class="fa fa-check-circle me-1"></i> Update & Close Pending Qty');

        // Show Modal safely
        showClosePendingQtyModal();
    }

    // Modal Close button
    $(document).on("click", "#closePendingQtyModal .close-modal-btn", function() {
        hideClosePendingQtyModal();
    });

    // Dynamic calculation handlers for Close Pending Qty Modal
    $(document).on("input change", "#cpq_custom_new_qty", function() {
        let max = parseInt($(this).attr("max")) || 0;
        let val = parseInt($(this).val());
        if (isNaN(val) || val < 0) val = 0;
        if (val > max) { val = max; $(this).val(val); }
        let dropped = Math.max(0, max - val);
        $("#cpq_summary_revised").text(val.toLocaleString());
        $("#cpq_summary_dropped").text(dropped.toLocaleString());
    });

    $(document).on("click", "#cpq_btn_set_fulfilled", function() {
        let fVal = parseInt($("#cpq_fulfilled_qty").val()) || 0;
        $("#cpq_custom_new_qty").val(fVal).trigger("change");
    });

    $(document).on("click", "#cpq_btn_set_zero", function() {
        $("#cpq_custom_new_qty").val(0).trigger("change");
    });

    // Open Full SO Editor button in modal
    $(document).on("click", "#cpq_open_full_so_btn", function() {
        let psono = $("#cpq_psono").val();
        if (!psono) {
            Swal.fire("Error", "No PSONO identifier found for this order item.", "error");
            return;
        }
        let mdidParam = CURRENT_APP_MDID || '';
        let url = `addNewProvisionalsaleorder.php?mode=edit&psono=${encodeURIComponent(psono)}&iframe=true`;
        if (mdidParam) url += `&mdid=${encodeURIComponent(mdidParam)}`;
        window.open(url, '_blank');
    });

    // 4. Save and Submit Pending Order Quantity Closure
    $(document).on("click", "#btn_save_close_pending_qty", function() {
        let soiId = $("#cpq_soi_id").val();
        let psono = $("#cpq_psono").val();
        let productId = $("#cpq_product_id").val();
        let closureAction = $("input[name='cpq_closure_action']:checked").val() || 'revise_order_qty';
        let remark = $.trim($("#cpq_remark").val());
        let projectid = $("#cpq_projectid").val() || $("body").data("projectid") || 0;
        let customNewQty = parseInt($("#cpq_custom_new_qty").val());
        if (isNaN(customNewQty) || customNewQty < 0) customNewQty = 0;

        if (!remark || remark.length < 3) {
            $("#cpq_remark_error").slideDown();
            $("#cpq_remark").focus();
            Swal.fire({
                icon: 'warning',
                title: 'Mandatory Remark Required',
                text: 'Please enter a valid reason / remark (minimum 3 characters) explaining why this pending quantity is not required.',
                confirmButtonColor: '#e11d48',
                willOpen: () => {
                    $(document).off('focusin.bs.modal');
                }
            });
            return;
        }
        $("#cpq_remark_error").hide();

        let totalOrderQty = parseInt($("#cpq_modal_order_qty").text().replace(/,/g, '')) || 0;
        let droppedQty = Math.max(0, totalOrderQty - customNewQty);

        let confirmTitle = closureAction === 'revise_order_qty' ? "Revise Sales Order Quantity?" : "Cancel Remaining Balance?";
        let confirmText = closureAction === 'revise_order_qty'
            ? `This action will revise the booked order quantity from ${totalOrderQty.toLocaleString()} to ${customNewQty.toLocaleString()} plants (cancelling / dropping ${droppedQty.toLocaleString()} plants), recalculate order totals, and record your remark. Proceed?`
            : `This action will keep the original order quantity, set the unfulfilled pending balance to 0, and record your closure remark. Proceed?`;

        Swal.fire({
            title: confirmTitle,
            text: confirmText,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#be123c",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "<i class='fa fa-check me-1'></i> Yes, Update & Close",
            cancelButtonText: "Cancel",
            focusCancel: true,
            willOpen: () => {
                $(document).off('focusin.bs.modal');
            },
            didOpen: () => {
                $(document).off('focusin.bs.modal');
            }
        }).then((res) => {
            if (!res.isConfirmed) return;

            let $btn = $("#btn_save_close_pending_qty");
            $btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin me-1"></i> Updating...');

            let fulfilledQty = $("#cpq_fulfilled_qty").val() || 0;
            let pendingQty = $("#cpq_pending_qty").val() || 0;

            $.ajax({
                url: "modal/finaldispatch_modal.php?action=ClosePendingOrderQty",
                type: "POST",
                data: {
                    soi_id: soiId,
                    psono: psono,
                    product_id: productId,
                    closure_action: closureAction,
                    custom_new_qty: customNewQty,
                    remark: remark,
                    fulfilled_qty: fulfilledQty,
                    pending_qty: pendingQty,
                    projectid: projectid
                },
                dataType: "json",
                success: function(resp) {
                    $btn.prop("disabled", false).html('<i class="fa fa-check-circle me-1"></i> Update & Close Pending Qty');
                    if (resp && resp.status === 'success') {
                        // Close modal safely
                        hideClosePendingQtyModal();

                        Swal.fire({
                            icon: 'success',
                            title: 'Order Updated Successfully',
                            text: resp.message || 'Pending quantity was closed and order remarks updated successfully.',
                            confirmButtonColor: '#059669'
                        }).then(() => {
                            // Reload timetable data and KPI counters
                            let currentWeek = $("#dispatchWeekSelector").val();
                            loadFinalDispatchSchedule(currentWeek);
                        });
                    } else {
                        let errMsg = resp && resp.message ? resp.message : "Failed to update pending quantity closure.";
                        Swal.fire({
                            icon: "error",
                            title: "Update Failed",
                            text: errMsg,
                            confirmButtonColor: "#dc2626",
                            willOpen: () => {
                                $(document).off('focusin.bs.modal');
                            }
                        });
                    }
                },
                error: function(xhr, status, err) {
                    $btn.prop("disabled", false).html('<i class="fa fa-check-circle me-1"></i> Update & Close Pending Qty');
                    let errMsg = "An error occurred while communicating with the server.";
                    if (xhr && xhr.responseJSON && xhr.responseJSON.message) errMsg = xhr.responseJSON.message;
                    Swal.fire({
                        icon: "error",
                        title: "Server Error",
                        text: errMsg,
                        confirmButtonColor: "#dc2626",
                        willOpen: () => {
                            $(document).off('focusin.bs.modal');
                        }
                    });
                }
            });
        });
    });

    // Global state for current invoice modal
    let currentInvoiceData = null;

    // Open Prepare Dispatch Invoice Modal
    $(document).on("click", ".prepare-dispatch-invoice-btn", function() {
        let soiId = $(this).data("soiid");
        let row = $(this).data("row") || getDispatchRowBySoiId(soiId);
        if (!row) return;
        let invNo = $(this).data("invoiceno") || $(this).attr("data-invoiceno") || (row ? (row.dinvoiceno || row.soi_invoiceno || row.invoiceno || '') : '');
        openPrepareDispatchInvoiceModal(row, soiId, invNo);
    });

    function openPrepareDispatchInvoiceModal(row, soiId, editInvoiceNo) {
        if (!soiId && row) soiId = row.soi_id;
        if (!row) return;

        let cleanSoiId = (row && (row.real_soi_id || row.parent_soi_id)) 
            ? (row.real_soi_id || row.parent_soi_id) 
            : ((typeof soiId === 'string' && soiId.includes('_')) ? parseInt(soiId) : soiId);

        let isRowCancelled = Boolean(
            row.is_cancelled == 1 ||
            row.dispatch_status_code === 'cancelled' ||
            (row.item_status && row.item_status.toLowerCase() === 'cancelled') ||
            (row.soi_status && row.soi_status.toLowerCase() === 'cancelled') ||
            (row.sales_order_status && row.sales_order_status.toLowerCase() === 'cancelled') ||
            (row.pso_status && row.pso_status.toLowerCase() === 'cancelled')
        );
        if (isRowCancelled) {
            Swal.fire({
                icon: 'error',
                title: 'Order is Cancelled',
                text: row.cancel_reason || 'This order is cancelled. Invoice preparation is stopped.',
                confirmButtonColor: '#dc2626'
            });
            return;
        }

        let projectid = $("body").data("projectid") || 0;
        let sono = row.sono || '';
        let psono = row.psono || '';

        // Reset form
        $("#prepareDispatchInvoiceForm")[0].reset();
        $("#dinv_soi_id").val(cleanSoiId);
        $("#dinv_sono").val(sono);
        $("#dinv_psono").val(psono);
        $("#dinv_projectid").val(projectid);
        $("#dinv_edit_invoiceno").val(editInvoiceNo || '');
        $("#dinv_sono_badge").text(`SO: ${sono || '-'} | PSONO: ${psono || '-'}`);
        $("#dinvProductsContainer").empty();
        $("#dinv_enable_box_packing").prop("checked", false);
        $("#dinv_box_packing_body").hide();
        $("#dinv_box_count").val(0);
        $("#dinv_box_total_charge").val("0.00");
        $("#dinv_box_calc_formula_text").text("0 boxes × ₹ 100.00 = ₹ 0.00");
        $("#dinv_box_capacity_hint").text("Capacity: 500 plants / box");
        $("#dinv_auto_calc_boxes_btn").attr("title", "Auto calculate boxes from Total Dispatch Plants (500 Plants/Box)");

        Swal.fire({
            title: "Loading Invoice Details",
            text: "Fetching client final sale order & allocation details...",
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: "modal/finaldispatch_modal.php?action=GetDispatchInvoiceDetails",
            type: "POST",
            data: {
                projectid: projectid,
                soi_id: cleanSoiId,
                sono: sono,
                psono: psono,
                edit_invoiceno: editInvoiceNo || ''
            },
            dataType: "json",
            success: function(res) {
                Swal.close();
                if (res.status !== "success" || !res.data) {
                    Swal.fire("Error", res.message || "Failed to load dispatch invoice details.", "error");
                    return;
                }

                currentInvoiceData = res.data;
                let d = res.data;
                let ord = d.order || {};
                let dl = d.delivery_location || {};
                let fc = d.freight_charges || {};
                let items = d.items || [];

                // 1. Client & Customer Details (as per Client Master)
                let clientName = ord.clientname || d.client_info?.clientname || d.client_info?.client_name || '-';
                let mobileNo = ord.mobileno || d.client_info?.mobileno || d.client_info?.phones || '-';
                let emailId = ord.emailid || d.client_info?.emailid || d.client_info?.client_email || 'N/A';
                let invAddr = d.client_info?.resolved_invoicing_address || d.client_info?.invoicing_address || ord.invoicing_address || ord.address || '-';

                $("#dinv_clientid").val(ord.clientid || d.client_info?.client_id || '');
                $("#dinv_clientname").val(clientName);
                $("#dinv_lbl_clientname").text(clientName);
                $("#dinv_mobileno").val(mobileNo !== '-' ? mobileNo : '');
                $("#dinv_lbl_mobileno").text(mobileNo);
                $("#dinv_emailid").val(emailId !== 'N/A' ? emailId : '');
                $("#dinv_lbl_emailid").text(emailId);
                $("#dinv_address").val(invAddr !== '-' && invAddr !== 'N/A' ? invAddr : '');
                $("#dinv_invoicing_address").val(invAddr !== '-' && invAddr !== 'N/A' ? invAddr : '');
                $("#dinv_lbl_invoice_address").text(invAddr);

                // Hidden delivery location fields for dispatch save
                let fullAddr = dl.deliveryaddress || ord.delivery_address || d.client_info?.delivery_address || '';
                let destDist = dl.dest_district || ord.districtname || d.client_info?.districtname || '';
                let destSt = dl.dest_state || ord.statename || d.client_info?.statename || '';
                $("#dinv_delivery_address").val(fullAddr);
                $("#dinv_districtname").val(destDist);
                $("#dinv_statename").val(destSt);
                $("#dinv_companyid").val(ord.companyid || '');
                $("#dinv_subcompanyid").val(ord.subcompanyid || '');

                // Right Column order info
                $("#dinv_lbl_orderno").text(ord.psono || ord.sono || '-');
                $("#dinv_lbl_orderdate").text(ord.formatted_order_date || (ord.created_at ? new Date(ord.created_at).toLocaleDateString('en-GB', {day: '2-digit', month: 'short', year: 'numeric'}) : '-'));
                $("#dinv_lbl_orderstatus").text(ord.status || 'Confirmed');
                $("#dinv_lbl_createdby").text(ord.created_by_name || 'Admin');

                // Set Invoice No (KF/{financialyear}/(dispatchinvoicecount+1))
                let now = new Date();
                let curFY = (now.getMonth() >= 3) ? `${String(now.getFullYear()).slice(-2)}-${String(now.getFullYear() + 1).slice(-2)}` : `${String(now.getFullYear() - 1).slice(-2)}-${String(now.getFullYear()).slice(-2)}`;
                let nextInvNo = d.is_edit_mode
                    ? (d.saved_dinvoiceno || d.next_invoice_no || ord.dinvoiceno || `KF/${curFY}/1`)
                    : (d.next_invoice_no || `KF/${curFY}/1`);
                $("#dinv_invoiceno").val(nextInvNo);
                $("#dinv_invoiceno_badge").text(nextInvNo);

                // Set Customer Type
                let custType = d.customer_type || 'Farmer';
                $("#dinv_lbl_cust_type_pill").text(custType);
                $("#dinv_cust_type_badge").text(`Customer: ${custType}`);

                // Set Invoice Date (Default saved date or today; editable if loginroleid < 3; NO hint text)
                let invDate = d.default_invoice_date || (ord.invoicedate ? ord.invoicedate.split('T')[0].split(' ')[0] : new Date().toISOString().split('T')[0]);
                $("#dinv_invoicedate").val(invDate);

                let roleId = parseInt(CURRENT_APP_LOGINROLEID);
                if (isNaN(roleId)) {
                    roleId = parseInt($("body").data("loginroleid") || 99);
                }

                if (roleId < 3) {
                    $("#dinv_invoicedate").prop("readonly", false).removeClass("bg-light");
                    $("#dinv_btn_autofill_cash").show();
                } else {
                    $("#dinv_invoicedate").prop("readonly", true).addClass("bg-light");
                    $("#dinv_btn_autofill_cash").hide();
                }

                let existingLat = dl.dest_latitude || '';
                let existingLon = dl.dest_longitude || '';
                if (existingLat && existingLon) {
                    $("#latinput").val(existingLat);
                    $("#longinput").val(existingLon);
                    $("#latlonginput").val(`${existingLat}, ${existingLon}`);
                } else {
                    $("#latinput").val('');
                    $("#longinput").val('');
                    $("#latlonginput").val('');
                }

                if (custType === 'Dealer') {
                    $("#dinv_cust_type_badge").removeClass("bg-secondary-subtle").addClass("bg-warning-subtle text-warning-emphasis border-warning-subtle");
                    $("#dinv_lbl_cust_type_pill").removeClass("bg-white text-dark").addClass("bg-warning-subtle text-warning-emphasis border-warning-subtle");
                } else {
                    $("#dinv_cust_type_badge").removeClass("bg-warning-subtle text-warning-emphasis border-warning-subtle").addClass("bg-secondary-subtle text-dark");
                    $("#dinv_lbl_cust_type_pill").removeClass("bg-warning-subtle text-warning-emphasis border-warning-subtle").addClass("bg-white text-dark");
                }

                $("#dinv_product_count_badge").text(`${items.length} Product${items.length > 1 ? 's' : ''}`);

                // 2. Provisional Freight & Delivery Location Preview
                let pickupType = dl.pickuptype || 'delivered';
                let pickupTypeDisplay = (pickupType === 'client_pickup') ? 'Client Pickup' : (pickupType === 'boxpacking' ? 'Box Packing (Bus)' : 'Delivered (Vehicle)');
                $("#dinv_delivery_mode_badge").text(`Mode: ${pickupTypeDisplay}`);

                let destDistrict = dl.dest_district || ord.districtname || '-';
                let destState = dl.dest_state || ord.statename || '-';
                let destAddr = dl.deliveryaddress || ord.delivery_address || '';
                $("#dinv_prev_dest").text(`${destDistrict}, ${destState} ${destAddr ? '(' + destAddr.substring(0, 35) + '...)' : ''}`);

                let totalDist = parseFloat(dl.totaldistance || 0);
                let freeDist = parseFloat(dl.freedistance || 0);
                let chargeDist = parseFloat(dl.distance || (totalDist > freeDist ? totalDist - freeDist : 0));
                $("#dinv_prev_distance_text").text(`Total: ${totalDist} km | Free: ${freeDist} km | Chargeable: ${chargeDist} km`);

                let baseFreight = parseFloat(d.original_base_freight || 0);
                let origPayable = parseFloat(d.original_payable_freight || 0);
                let origDiscountRemark = d.original_discount_remark || '';
                $("#dinv_prev_freight_text").html(`Base: <b>₹ ${baseFreight.toFixed(2)}</b> | Payable: <b class="text-success">₹ ${origPayable.toFixed(2)}</b> ${origDiscountRemark ? '<br><span class="text-muted" style="font-size:10px;">(' + origDiscountRemark + ')</span>' : ''}`);

                // Box Packing Capacity & Rate initialization
                let boxCapacity = 500;
                if (dl && parseInt(dl.boxperpack) > 0) {
                    boxCapacity = parseInt(dl.boxperpack);
                }
                let savedBp = d.saved_box_packing || {};
                // Packing Rate / Box defaults strictly to ₹100.00 (dl.chargesperbox is transport freight, NOT box packing)
                let boxRate = (savedBp && parseFloat(savedBp.box_rate) > 0 && d.is_edit_mode) ? parseFloat(savedBp.box_rate) : 100.00;
                $("#dinv_box_rate").val(boxRate.toFixed(2));
                $("#dinv_box_rate_badge").text(`₹ ${boxRate.toFixed(2)} / Box`);
                $("#dinv_box_capacity_hint").text(`Capacity: ${boxCapacity.toLocaleString()} plants / box`);
                $("#dinv_auto_calc_boxes_btn").attr("title", `Auto calculate boxes from Total Dispatch Plants (${boxCapacity.toLocaleString()} Plants/Box)`);

                // Auto-fill saved Box Packing Charges if existing/saved in invoice
                if (savedBp.enabled && (parseInt(savedBp.box_count) > 0 || parseFloat(savedBp.box_total) > 0)) {
                    let bCount = parseInt(savedBp.box_count) || 0;
                    let bRate = (parseFloat(savedBp.box_rate) > 0 && d.is_edit_mode) ? parseFloat(savedBp.box_rate) : 100.00;
                    let bTotal = Math.round(bCount * bRate * 100) / 100;
                    $("#dinv_enable_box_packing").prop("checked", true);
                    $("#dinv_box_packing_body").show();
                    $("#dinv_box_count").val(bCount);
                    $("#dinv_box_rate").val(bRate.toFixed(2));
                    $("#dinv_box_rate_badge").text(`₹ ${bRate.toFixed(2)} / Box`);
                    $("#dinv_box_total_charge").val(bTotal.toFixed(2));
                    $("#dinv_box_calc_formula_text").text(`${bCount} boxes × ₹ ${bRate.toFixed(2)} = ₹ ${bTotal.toFixed(2)}`);
                } else {
                    $("#dinv_enable_box_packing").prop("checked", false);
                    $("#dinv_box_packing_body").hide();
                    $("#dinv_box_count").val(0);
                    $("#dinv_box_rate").val(boxRate.toFixed(2));
                    $("#dinv_box_rate_badge").text(`₹ ${boxRate.toFixed(2)} / Box`);
                    $("#dinv_box_total_charge").val("0.00");
                    $("#dinv_box_calc_formula_text").text(`0 boxes × ₹ ${boxRate.toFixed(2)} = ₹ 0.00`);
                }

                // If in Edit Mode, update modal header title, badge and button
                if (d.is_edit_mode) {
                    let editInvNum = d.saved_dinvoiceno || nextInvNo;
                    $("#prepareDispatchInvoiceModalLabel").html(`<i class="fa fa-edit me-1 text-info"></i> Edit Dispatch Invoice <span class="badge bg-info text-dark font-monospace ms-2">${editInvNum}</span>`);
                    $("#dinvStatusBadge").removeClass("bg-success-subtle text-success border-success-subtle").addClass("bg-info-subtle text-info border-info-subtle").html(`<i class="fa fa-file-invoice me-1"></i> Edit Mode`);
                    $("#confirmGenerateDispatchInvoiceBtn").html(`<i class="fa fa-save me-1"></i> Update &amp; Save Dispatch Invoice`);
                    $(".dinv-top-btn-confirm-save").html(`<i class="fa fa-save me-1"></i> Update &amp; Save Invoice`);
                } else {
                    $("#prepareDispatchInvoiceModalLabel").html(`<i class="fa fa-file-invoice me-1 text-primary"></i> Prepared Dispatch Invoice <span class="badge bg-primary text-white font-monospace ms-2">${nextInvNo}</span>`);
                    $("#dinvStatusBadge").removeClass("bg-info-subtle text-info border-info-subtle").addClass("bg-success-subtle text-success border-success-subtle").html(`<i class="fa fa-check-circle me-1"></i> New Invoice`);
                    $("#confirmGenerateDispatchInvoiceBtn").html(`<i class="fa fa-check-circle me-1"></i> Generate &amp; Save Dispatch Invoice`);
                    $(".dinv-top-btn-confirm-save").html(`<i class="fa fa-check-circle me-1"></i> Generate Invoice`);
                }

                // 2.9. Render Past Generated Invoices (displayed on top of Product Items)
                renderPastGeneratedInvoices(d.past_invoices || []);

                // 3. Render Product Items
                renderDispatchInvoiceItems(items);

                // 4. Initial Recalculate Totals & Freight Rule
                recalculateAllInvoiceData();

                window.goToDinvStep(1);
                $("#prepareDispatchInvoiceModal").modal("show");
            },
            error: function() {
                Swal.close();
                Swal.fire("Error", "Server error while fetching dispatch invoice details.", "error");
            }
        });
    }

    // Render Past Generated Invoices on top of Product Items & Shifting Allocation
    function renderPastGeneratedInvoices(pastInvoices) {
        let container = $("#dinvPastInvoicesContainer");
        if (!pastInvoices || pastInvoices.length === 0) {
            container.empty().hide();
            return;
        }

        let totalPlantsDispatched = 0;
        let totalAmountInvoiced = 0;
        let rowsHtml = '';

        pastInvoices.forEach((inv, i) => {
            let plants = parseInt(inv.total_plants || 0);
            let amt = parseFloat(inv.payableamount || inv.invoiceamount || 0);
            totalPlantsDispatched += plants;
            totalAmountInvoiced += amt;

            let deliveryDisplay = '';
            if (inv.pickuptype === 'pickup') {
                deliveryDisplay = '<span class="badge bg-secondary-subtle text-dark border"><i class="fa fa-walking me-1"></i> Client Pickup</span>';
            } else if (inv.pickuptype === 'boxpacking') {
                deliveryDisplay = `<span class="badge bg-warning-subtle text-dark border border-warning"><i class="fa fa-boxes me-1"></i> Box Packing (${inv.box_count || 0} Boxes)</span>`;
            } else {
                let veh = (inv.vehicleno || '').trim();
                deliveryDisplay = `<span class="badge bg-info-subtle text-dark border border-info"><i class="fa fa-truck me-1"></i> ${escapeHtml(veh || 'Delivered')}</span>`;
            }

            rowsHtml += `
                <tr>
                    <td class="text-center fw-bold text-secondary">${i + 1}</td>
                    <td>
                        <span class="fw-bold font-monospace text-primary">${escapeHtml(inv.invoiceno)}</span>
                    </td>
                    <td>${escapeHtml(inv.formatted_date || inv.invoicedate || '-')}</td>
                    <td>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fw-bold">
                            <i class="fa fa-seedling me-1"></i> ${plants.toLocaleString()} Plants
                        </span>
                    </td>
                    <td class="fw-bold text-dark">₹ ${amt.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    <td>${deliveryDisplay}</td>
                    <td>
                        <span class="badge bg-success text-white px-2 py-0.5 text-uppercase" style="font-size: 10px;">${escapeHtml(inv.status || 'Active')}</span>
                    </td>
                    <td class="text-center" style="white-space: nowrap;">
                        <button type="button" class="btn btn-sm btn-outline-info py-0.5 px-2 fw-semibold dinv-past-inv-edit-btn me-1" data-invoiceno="${escapeHtml(inv.invoiceno)}" title="Edit Dispatched Invoice">
                            <i class="fa fa-edit me-1"></i> Edit
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success py-0.5 px-2 fw-semibold dinv-past-inv-pdf-btn" data-invoiceno="${escapeHtml(inv.invoiceno)}" title="Download / View Dispatched Invoice PDF">
                            <i class="fa fa-file-pdf me-1"></i> PDF
                        </button>
                    </td>
                </tr>
            `;
        });

        let cardHtml = `
            <div class="card border border-primary-subtle shadow-sm rounded-3">
                <div class="card-header py-2 px-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2" style="background: #eff6ff;">
                    <div class="d-flex align-items-center gap-2">
                        <span class="fw-bold text-primary fs-6">
                            <i class="fa fa-file-invoice text-primary me-2"></i> Past Generated Invoices for this Order
                        </span>
                        <span class="badge bg-primary text-white rounded-pill px-2.5 py-0.5" style="font-size: 11px;">
                            ${pastInvoices.length} Invoice${pastInvoices.length > 1 ? 's' : ''}
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="badge bg-white text-success border border-success-subtle px-2.5 py-1 fw-bold shadow-xs" style="font-size: 11.5px;">
                            <i class="fa fa-check-circle me-1 text-success"></i> Total Dispatched: ${totalPlantsDispatched.toLocaleString()} Plants
                        </span>
                        <span class="badge bg-white text-dark border px-2.5 py-1 fw-bold shadow-xs" style="font-size: 11.5px;">
                            <i class="fa fa-rupee-sign me-1 text-primary"></i> Total Invoiced: ₹ ${totalAmountInvoiced.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                        </span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0" style="font-size: 12px;">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width: 40px;">#</th>
                                    <th>Invoice No</th>
                                    <th>Invoice Date</th>
                                    <th>Dispatched Plants</th>
                                    <th>Total Amount</th>
                                    <th>Delivery / Vehicle</th>
                                    <th>Status</th>
                                    <th class="text-center" style="width: 90px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${rowsHtml}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;

        container.html(cardHtml).show();
    }

    // Render Multi-Product Cards inside Modal
    function renderDispatchInvoiceItems(items) {
        let container = $("#dinvProductsContainer");
        container.empty();

        if (!items || items.length === 0) {
            container.html(`<div class="alert alert-warning mb-0"><i class="fa fa-exclamation-triangle me-2"></i>No product items found for this provisional / sales order.</div>`);
            $("#dinv_product_count_badge").text("0 Products");
            return;
        }

        $("#dinv_product_count_badge").text(`${items.length} of ${items.length} Products Selected`);

        items.forEach((it, idx) => {
            let isShifted = (it.isshifted === 'yes' || it.isshifted === '1' || it.isshifted === 1 || it.isshifted === 'YES');
            let orderQty = parseInt(it.original_orderqty || it.quantity || 0);
            let origAllocQty = parseInt(it.original_allocqty || it.allocate_ordqty || 0);
            let dispatchedInvQty = parseInt(it.dispatched_invoice_qty || 0);
            let partiallyDispatchedQty = parseInt(it.partially_dispatched_qty !== undefined ? it.partially_dispatched_qty : (it.dispatched_invoice_qty || 0));
            let totalAllocQty = parseInt(it.total_allocated_qty || (parseInt(it.allocate_ordqty || 0) + partiallyDispatchedQty) || origAllocQty);
            let pendingAllocQty = it.pending_balance_alloc_qty !== undefined 
                ? parseInt(it.pending_balance_alloc_qty || 0) 
                : Math.max(0, orderQty - totalAllocQty);
            let allocQty = parseInt(it.allocate_ordqty !== undefined ? it.allocate_ordqty : (it.original_allocqty || 0));
            let isItemEditMode = Boolean(it.is_invoice_edit_item || (currentInvoiceData && currentInvoiceData.is_edit_mode));
            let healthyQty = parseInt(it.healthyplant !== undefined ? it.healthyplant : allocQty);
            let unhealthyQty = parseInt(it.unhealthyplant || 0);
            let rate = parseFloat(it.rate || 0);
            let discPct = parseFloat(it.discount_percent || 0);
            let cgstPct = parseFloat(it.cgst_percent || 0);
            let sgstPct = parseFloat(it.sgst_percent || 0);
            let igstPct = parseFloat(it.igst_percent || 0);

            // Shifting buffer values
            let extraPct = '';
            let extraQty = 0;
            if (!isShifted) {
                let p = parseFloat(it.percentextrashifting || 0);
                extraPct = p > 0 ? p : 30; // default 30% if not shifted
                let q = parseInt(it.shiftingqty || 0);
                extraQty = (extraPct > 0 && allocQty > 0) ? Math.round(allocQty * (extraPct / 100)) : (q > 0 ? q : 0);
            }

            let effName = it.allocated_product_name || it.product_name;
            let cleanName = it.clean_product_name || formatProductNameDisplay(effName);
            let isGrafted = isProductGrafted(effName);
            let plantTypeBadge = isGrafted 
                ? `<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="fa fa-seedling me-1"></i> Grafted Crop</span>`
                : `<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1"><i class="fa fa-leaf me-1"></i> Non-Grafted Crop</span>`;
            let bookedProdNameDisplay = it.booked_product_name || it.product_name || '';
            let bookedDiffBadge = (it.allocated_product_name && bookedProdNameDisplay && it.allocated_product_name.trim().toLowerCase() !== bookedProdNameDisplay.trim().toLowerCase())
                ? `<span class="badge bg-light text-secondary border ms-1" style="font-size: 11px;" title="Original Booked: ${escapeHtml(bookedProdNameDisplay)}"><i class="fa fa-bookmark text-secondary me-1"></i>Booked: ${escapeHtml(formatProductNameDisplay(bookedProdNameDisplay))}</span>`
                : '';

            let phQty = parseInt(it.polyhouse_plant_qty || 0);
            let readyQty = parseInt(it.ready_plant_qty || 0);
            let trayCount = parseInt(it.polyhouse_tray_count || 0);
            let phNames = it.polyhouse_names || 'N/A';
            let phBedInfo = (it.bed_numbers && it.bed_numbers !== '-' && it.bed_numbers !== 'N/A') ? ` | Bed: <b class="text-dark">${escapeHtml(it.bed_numbers)}</b>` : '';
            let isPhAvailable = (phQty > 0 || readyQty > 0 || trayCount > 0);
            let isItemChecked = isPhAvailable;

            let polyhouseNoticeHtml = "";
            if (!isPhAvailable) {
                polyhouseNoticeHtml = `
                    <div class="alert alert-danger py-2 px-3 mb-2.5 d-flex align-items-center justify-content-between flex-wrap gap-2 shadow-xs dinv-zero-ph-alert" style="font-size: 11.5px; border-radius: 6px; border-left: 4px solid #dc2626; background-color: #fef2f2;">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa fa-exclamation-triangle text-danger fs-6"></i>
                            <div>
                                <span class="fw-bold text-danger" style="font-size: 12px;">Plant Not in Polyhouse: Total Available: 0 Plants (0 Trays). go back and realloacte</span>
                                <div class="text-danger small mt-0.5"><i class="fa fa-info-circle me-1"></i>Please reallocate stock order in timetable before including in dispatch invoice.</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-danger fw-bold dinv-go-back-reallocate-btn shadow-xs" data-soi-id="${it.id || ''}" data-bs-dismiss="modal" data-dismiss="modal" style="font-size: 11.5px; border-radius: 4px; padding: 3px 10px;">
                                <i class="fa fa-arrow-left me-1"></i> Go Back &amp; Reallocate
                            </button>
                            <span class="badge bg-danger text-white px-2 py-1"><i class="fa fa-times-circle me-1"></i>0 in Polyhouse (Cannot Load)</span>
                        </div>
                    </div>
                `;
            } else {
                polyhouseNoticeHtml = `
                    <div class="alert alert-success-subtle border-success-subtle text-success py-1 px-2.5 mb-2.5 d-flex align-items-center justify-content-between flex-wrap gap-2 shadow-xs" style="font-size: 11px; border-radius: 6px;">
                        <div>
                            <i class="fa fa-warehouse me-1"></i> <b>Polyhouse Stock:</b> ${phQty.toLocaleString()} Total Plants (${trayCount} Trays) | Ready: <b>${readyQty.toLocaleString()}</b> | Location: <b>${phNames}</b>${phBedInfo}
                        </div>
                        <span class="badge bg-success text-white px-2 py-0.5"><i class="fa fa-check me-1"></i>Available</span>
                    </div>
                `;
            }

            let cardExtraClasses = isItemChecked ? "" : "border-dashed bg-light opacity-75 dinv-card-excluded";
            let cardHeaderBg = isItemChecked ? "background: #f8fafc;" : "background: #f1f5f9;";
            let cardBodyBg = isItemChecked ? "background: #ffffff;" : "background: #fafafa;";
            let statusBadgeHtml = isItemChecked 
                ? `<span class="badge bg-success text-white dinv-item-status-badge px-2.5 py-1" style="font-size: 11px;"><i class="fa fa-check-circle me-1"></i> Included</span>`
                : `<span class="badge bg-secondary text-white dinv-item-status-badge px-2.5 py-1" style="font-size: 11px;"><i class="fa fa-ban me-1"></i> Excluded from Invoice</span>`;

            let itemHtml = `
                <div class="card border border-secondary-subtle shadow-sm rounded-3 mb-3 dinv-product-card ${cardExtraClasses}" data-index="${idx}" data-clean-name="${escapeHtml(cleanName)}" data-ph-qty="${phQty}" data-ready-qty="${readyQty}" data-tray-count="${trayCount}" style="${cardBodyBg} transition: all 0.2s ease;">
                    <!-- Card Header with Non-overlapping Checkbox -->
                    <div class="card-header py-2.5 px-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2 dinv-card-header" style="${cardHeaderBg}">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <div class="dinv-include-wrapper d-inline-flex align-items-center me-2" style="padding: 4px 10px; margin-right: 10px; background: #ffffff; border: 1px solid #ced4da; border-radius: 6px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); gap: 8px;">
                                <input class="dinv-item-include-checkbox" type="checkbox" id="dinv_include_${idx}" ${isItemChecked ? 'checked' : ''} 
                                    style="width: 15px !important; height: 15px !important; min-width: 15px !important; min-height: 15px !important; max-width: 15px !important; max-height: 15px !important; margin: 0 8px 0 0 !important; cursor: pointer; accent-color: #059669; display: inline-block !important; vertical-align: middle !important; position: static !important; flex-shrink: 0 !important; box-sizing: border-box !important;">
                                <label class="fw-bold text-dark mb-0 cursor-pointer dinv-include-label" for="dinv_include_${idx}" 
                                    style="user-select: none; font-size: 12px; font-weight: 700; color: #1e293b; cursor: pointer; line-height: 1.2; white-space: nowrap; margin: 0; padding: 0; display: inline-block; vertical-align: middle;">
                                    Include in Invoice
                                </label>
                            </div>
                            <span class="badge bg-dark text-white rounded-pill px-2.5 py-1" style="font-size: 11px;">Item #${idx + 1}</span>
                            ${statusBadgeHtml}
                            <span class="fw-bold text-dark fs-6 ms-1 dinv-product-clean-title" data-clean-title="${escapeHtml(cleanName)}">${cleanName}</span>
                            ${bookedDiffBadge}
                            ${plantTypeBadge}
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            ${(() => {
                                let activeStockItem = it.allocated_stockorder_no || it.original_stockorder_no || it.stockorder_no || '';
                                if (it.allocated_batches && Array.isArray(it.allocated_batches) && it.allocated_batches.length > 1) {
                                    let badges = it.allocated_batches.map(b => `
                                        <span class="badge bg-primary text-white border px-2 py-1 font-monospace shadow-xs" title="Allocated Batch: ${b.stockorder_no}">
                                            <i class="fa fa-boxes me-1"></i> ${b.stockorder_no} (<b>${parseInt(b.allocated_qty || b.consumed_qty).toLocaleString()}</b>)
                                        </span>
                                    `).join('');
                                    return `
                                        <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                            <span class="badge bg-primary-subtle text-primary border border-primary px-2 py-1" style="font-size: 10.5px;">
                                                <i class="fa fa-layer-group me-1"></i> Multi-Batch (${it.allocated_batches.length}):
                                            </span>
                                            ${badges}
                                        </div>
                                    `;
                                } else if (activeStockItem.includes(',')) {
                                    let parts = activeStockItem.split(',').map(s => s.trim()).filter(Boolean);
                                    let badges = parts.map(p => `
                                        <span class="badge bg-primary text-white border px-2 py-1 font-monospace shadow-xs">
                                            <i class="fa fa-boxes me-1"></i> ${p}
                                        </span>
                                    `).join('');
                                    return `
                                        <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                            <span class="badge bg-primary-subtle text-primary border border-primary px-2 py-1" style="font-size: 10.5px;">
                                                <i class="fa fa-layer-group me-1"></i> Batches:
                                            </span>
                                            ${badges}
                                        </div>
                                    `;
                                }
                                let isOpenStockItem = Boolean(
                                    it.is_open_stock == 1 ||
                                    it.stock_type_label === 'Open Stock' ||
                                    activeStockItem.startsWith('RSO-') || 
                                    activeStockItem.startsWith('RTS-') || 
                                    activeStockItem.startsWith('READY-')
                                );
                                if (it.is_swapped && !isOpenStockItem) {
                                    return `
                                        <span class="badge bg-primary text-white border px-2.5 py-1 font-monospace shadow-xs" title="Allocated Production Stock Order">
                                            <i class="fa fa-boxes me-1"></i> Allocated Stock: <b>${it.allocated_stockorder_no || '-'}</b>
                                        </span>
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning px-2 py-1" style="font-size: 10px;" title="Swapped from original provisional batch">
                                            <i class="fa fa-exchange-alt me-1"></i> Swapped
                                        </span>
                                        <span class="badge bg-light text-muted border px-2 py-1 font-monospace" title="Original Provisional Batch">
                                            Orig: <b>${it.original_stockorder_no || '-'}</b>
                                        </span>
                                    `;
                                } else if (isOpenStockItem) {
                                    return `
                                        <span class="badge bg-primary text-white border px-2.5 py-1 font-monospace shadow-xs" title="Production Stock Order">
                                            <i class="fa fa-boxes me-1"></i> Stock: <b>${activeStockItem || '-'}</b>
                                        </span>
                                        <span class="badge bg-info-subtle text-primary border border-primary-subtle px-2 py-1" style="font-size: 10px;" title="Trader Production Order / Open Stock Batch">
                                            <i class="fa fa-boxes me-1"></i> Open Stock
                                        </span>
                                    `;
                                } else {
                                    return `
                                        <span class="badge bg-primary text-white border px-2.5 py-1 font-monospace shadow-xs" title="Production Stock Order">
                                            <i class="fa fa-boxes me-1"></i> Stock: <b>${activeStockItem || '-'}</b>
                                        </span>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size: 10px;" title="Production Order Sown for this Client">
                                            <i class="fa fa-check-circle me-1"></i> Own Sown
                                        </span>
                                    `;
                                }
                            })()}
                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2.5 py-1 shadow-xs" title="Unallocated Remaining Plants from this Stock Order Batch">
                                <i class="fa fa-layer-group me-1"></i> Unallocated Batch Bal: <b>${parseInt(it.rest_unallocated_qty || 0).toLocaleString()} Plants</b>
                            </span>
                            <span class="badge bg-white text-dark border shadow-xs px-3 py-1.5 d-inline-flex align-items-center" style="font-size: 12.5px; border-color: #cbd5e1 !important;" title="Scheduled Delivery Date">
                                <i class="fa fa-calendar-alt text-primary me-1.5" style="font-size: 13px;"></i>
                                <span class="text-secondary fw-semibold">Delivery:</span>
                                <span class="dinv-item-deliverydate-display fw-bold text-dark px-2 py-0.5 rounded ms-1.5 shadow-xs" style="background-color: #fef08a; color: #854d0e; border: 1px solid #fde047; font-size: 12.5px; letter-spacing: 0.2px;">${escapeHtml(it.formatted_delivery_date || '-')}</span>
                            </span>
                        </div>
                    </div>

                    <div class="card-body p-3 dinv-card-body">
                        ${polyhouseNoticeHtml}
                        <!-- Allocated Stock Order & Unallocated Capacity Strip -->
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 py-1.5 px-3 mb-2.5 rounded-2 border" style="background: #f8fafc; font-size: 11.5px;">
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <div>
                                    <span class="text-secondary"><i class="fa fa-barcode me-1 text-primary"></i><b>Allocated Stock Order:</b></span> 
                                    <span class="fw-bold text-primary font-monospace ms-1">${it.allocated_stockorder_no || it.stockorder_no || '-'}</span>
                                    ${it.allocated_batches && it.allocated_batches.length > 1 ? `
                                        <span class="badge bg-success-subtle text-success border border-success ms-1 px-1.5 py-0.5" style="font-size: 10px;">
                                            <i class="fa fa-layer-group me-1"></i>${it.allocated_batches.length} Batches: ${it.allocated_batches.map(b => b.stockorder_no + ' (' + parseInt(b.allocated_qty || b.consumed_qty).toLocaleString() + ')').join(', ')}
                                        </span>
                                    ` : (it.is_swapped ? `<span class="badge bg-warning-subtle text-warning-emphasis border border-warning ms-1" style="font-size: 10px;"><i class="fa fa-exchange-alt me-1"></i>Swapped from original ${it.original_stockorder_no || '-'}</span>` : '')}
                                </div>
                                <div class="border-start ps-3">
                                    <span class="text-muted">Batch Total Qty:</span> 
                                    <b class="text-dark">${parseInt(it.pso_batch_qty || 0).toLocaleString()}</b>
                                </div>
                                <div class="border-start ps-3">
                                    <span class="text-muted">Total Batch Allocated:</span> 
                                    <b class="text-success">${parseInt(it.total_batch_allocated_qty || 0).toLocaleString()}</b>
                                </div>
                                ${partiallyDispatchedQty > 0 ? `
                                <div class="border-start ps-3">
                                    <span class="text-muted">Partially Dispatched:</span> 
                                    <b class="text-primary">${partiallyDispatchedQty.toLocaleString()} Plants</b>
                                </div>` : ''}
                                <div class="border-start ps-3">
                                    <span class="text-muted">Rest Unallocated Qty:</span> 
                                    <span class="badge bg-info text-dark fw-bold ms-1" style="font-size: 11px;">${parseInt(it.rest_unallocated_qty || 0).toLocaleString()} Plants</span>
                                </div>
                            </div>
                            <div class="text-muted" style="font-size: 10.5px;">
                                <i class="fa fa-database me-1"></i>karjeefarm.krf_production_stockorder &amp; krf_readystock_allocation
                            </div>
                        </div>
                        <input type="hidden" class="dinv-item-soi-id" value="${it.id || ''}">
                        <input type="hidden" class="dinv-item-product-id" value="${it.allocated_product_id || it.product_id || ''}">
                        <input type="hidden" class="dinv-item-product-name" value="${it.allocated_product_name || it.product_name || ''}">
                        <input type="hidden" class="dinv-item-allocated-product-name" value="${it.allocated_product_name || ''}">
                        <input type="hidden" class="dinv-item-booked-product-name" value="${it.booked_product_name || it.product_name || ''}">
                        <input type="hidden" class="dinv-item-stockorder-no" value="${it.allocated_stockorder_no || it.stockorder_no || it.original_stockorder_no || ''}">
                        <input type="hidden" class="dinv-item-allocated-stockorder-no" value="${it.allocated_stockorder_no || it.stockorder_no || ''}">
                        <input type="hidden" class="dinv-item-original-stockorder-no" value="${it.original_stockorder_no || it.stockorder_no || ''}">
                        <input type="hidden" class="dinv-item-is-swapped" value="${it.is_swapped ? 1 : 0}">
                        <input type="hidden" class="dinv-item-pso-batch-qty" value="${it.pso_batch_qty || 0}">
                        <input type="hidden" class="dinv-item-total-batch-allocated-qty" value="${it.total_batch_allocated_qty || 0}">
                        <input type="hidden" class="dinv-item-rest-unallocated-qty" value="${it.rest_unallocated_qty || 0}">
                        <input type="hidden" class="dinv-item-is-grafted" value="${it.is_grafted ? 1 : 0}">
                        <input type="hidden" class="dinv-item-plant-type" value="${it.plant_type || (it.is_grafted ? 'Grafted' : 'Nongrafted')}">
                        <input type="hidden" class="dinv-item-deliverydate" value="${it.deliverydate || it.confirmdeliverydate || ''}">
                        <input type="hidden" class="dinv-item-formatted-delivery-date" value="${it.formatted_delivery_date || ''}">
                        <input type="hidden" class="dinv-item-original-alloc-qty" value="${origAllocQty}">
                        <input type="hidden" class="dinv-item-past-dispatched-qty" value="${partiallyDispatchedQty}">
                        <input type="hidden" class="dinv-item-partially-dispatched-qty" value="${partiallyDispatchedQty}">
                        <input type="hidden" class="dinv-item-pending-alloc-qty" value="${pendingAllocQty}">

                        <!-- 1. Quantities Row (5 Clean Columns) -->
                        <div class="row g-2 mb-3">
                            <div class="col-xl-2 col-md-4 col-sm-6">
                                <label class="form-label text-muted small fw-bold mb-1">Original Order Qty</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" class="form-control fw-bold bg-light dinv-item-order-qty" value="${orderQty}" readonly>
                                    <span class="input-group-text bg-light text-muted">Plants</span>
                                </div>
                                <div class="mt-0.5" style="font-size: 11px;">
                                    ${pendingAllocQty > 0 
                                        ? `<span class="text-warning-emphasis fw-semibold" title="Pending Allocation Balance = Original Order (${orderQty.toLocaleString()}) - Total Allocated (${totalAllocQty.toLocaleString()})"><i class="fa fa-clock text-warning me-1"></i> Pending Allocation: <b>${pendingAllocQty.toLocaleString()} Plants</b></span>`
                                        : `<span class="text-success fw-semibold" title="Order quantity is fully allocated"><i class="fa fa-check-circle text-success me-1"></i> Fully Allocated (0 Pending)</span>`
                                    }
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-4 col-sm-6">
                                <label class="form-label text-primary small fw-bold mb-1">Allocated Qty (Invoiced / Billable) <i class="fa fa-lock text-muted ms-1" title="Locked: Governed by Stock Order Allocation"></i></label>
                                <div class="input-group input-group-sm">
                                    <input type="number" class="form-control fw-bold text-primary bg-light dinv-item-alloc-qty border-secondary-subtle" value="${allocQty}" readonly tabindex="-1" style="cursor: not-allowed; background-color: #f8fafc !important;">
                                    <span class="input-group-text bg-light text-muted fw-bold">Plants</span>
                                </div>
                                <div class="mt-0.5" style="font-size: 11px;">
                                    ${isItemEditMode 
                                        ? `<span class="text-info fw-semibold" title="Current Invoice Saved Qty: ${allocQty.toLocaleString()} Plants | Total Order Allocation: ${totalAllocQty.toLocaleString()} Plants"><i class="fa fa-file-invoice me-1"></i> Invoice Qty: <b>${allocQty.toLocaleString()} Plants</b></span>`
                                        : (partiallyDispatchedQty > 0 
                                            ? `<span class="text-primary fw-semibold" title="Allocated for this Invoice: ${allocQty.toLocaleString()} Plants | Total Allocated to date: ${totalAllocQty.toLocaleString()} Plants (${partiallyDispatchedQty.toLocaleString()} Dispatched + ${allocQty.toLocaleString()} This Invoice)"><i class="fa fa-receipt me-1"></i> Allocated for this Invoice (<b>${allocQty.toLocaleString()} Plants</b>)</span>`
                                            : `<span class="text-muted"><i class="fa fa-lock text-secondary me-1"></i> Fixed by stock allocation (${allocQty.toLocaleString()} Plants)</span>`
                                        )
                                    }
                                </div>
                            </div>
                            <div class="col-xl-2 col-md-4 col-sm-6">
                                <label class="form-label text-secondary small fw-bold mb-1">Partially Dispatched Qty <i class="fa fa-info-circle text-muted ms-1" title="Dispatched quantity against this psono / product for reference"></i></label>
                                <div class="input-group input-group-sm">
                                    <input type="number" class="form-control fw-bold dinv-item-dispatched-invoice-qty" value="${partiallyDispatchedQty}" readonly style="background-color: #f1f5f9; color: #475569;">
                                    <span class="input-group-text bg-light text-muted">Plants</span>
                                </div>
                                <div class="mt-0.5" style="font-size: 11px;">
                                    ${partiallyDispatchedQty > 0 
                                        ? `<span class="text-primary fw-semibold"><i class="fa fa-truck-loading me-1"></i> Dispatched for reference</span>`
                                        : `<span class="text-muted"><i class="fa fa-history me-1 text-secondary"></i> 0 Dispatched</span>`
                                    }
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6 col-sm-6">
                                <label class="form-label text-success small fw-bold mb-1">Healthy Plants (QC Passed)</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" class="form-control fw-bold text-success dinv-item-healthy dinv-item-calc-input" value="${healthyQty}" min="0">
                                    <span class="input-group-text bg-light text-muted">Plants</span>
                                </div>
                                <div class="text-muted mt-0.5" style="font-size: 11px;"><i class="fa fa-check text-success me-1"></i> Ready for dispatch</div>
                            </div>
                            <div class="col-xl-2 col-md-6 col-sm-6">
                                <label class="form-label text-danger small fw-bold mb-1">Unhealthy / Rejection</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" class="form-control fw-bold text-danger dinv-item-unhealthy dinv-item-calc-input" value="${unhealthyQty}" min="0">
                                    <span class="input-group-text bg-light text-muted">Plants</span>
                                </div>
                                <div class="text-muted mt-0.5" style="font-size: 11px;"><i class="fa fa-times text-danger me-1"></i> QC rejection count</div>
                            </div>
                        </div>

                        <!-- 2. Shifting Configuration & Buffer Policy Box -->
                        <div class="p-3 rounded-3 border mb-3" style="background: #f8fafc;">
                            <div class="row g-3 align-items-center">
                                <div class="col-lg-5 col-md-12">
                                    <label class="form-label text-dark small fw-bold d-block mb-1">
                                        <i class="fa fa-seedling text-success me-1"></i> Is Shifted Plant?
                                    </label>
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check dinv-item-shifted-radio" name="item_isshifted_${idx}" id="dinv_item_shifted_yes_${idx}" value="yes" ${isShifted ? 'checked' : ''}>
                                        <label class="btn btn-outline-success btn-sm fw-bold py-1.5" for="dinv_item_shifted_yes_${idx}">
                                            <i class="fa fa-check-circle me-1"></i> YES (Exact Qty, 0% Extra)
                                        </label>
                                        
                                        <input type="radio" class="btn-check dinv-item-shifted-radio" name="item_isshifted_${idx}" id="dinv_item_shifted_no_${idx}" value="no" ${!isShifted ? 'checked' : ''}>
                                        <label class="btn btn-outline-secondary btn-sm fw-bold py-1.5" for="dinv_item_shifted_no_${idx}">
                                            <i class="fa fa-plus-circle me-1"></i> NO (Add Free Extra %)
                                        </label>
                                    </div>
                                </div>

                                <div class="col-lg-3 col-md-6 dinv-item-extra-percent-group ${isShifted ? 'opacity-50' : ''}">
                                    <label class="form-label text-muted small fw-bold mb-1">% Extra Shifting Buffer</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control fw-bold text-dark dinv-item-extra-pct dinv-item-calc-input" step="0.01" min="0" value="${isShifted ? '' : extraPct}" placeholder="e.g. 30" ${isShifted ? 'disabled' : ''}>
                                        <span class="input-group-text bg-white fw-bold">%</span>
                                    </div>
                                    <div class="text-muted mt-0.5" style="font-size: 11px;">Complimentary safety %</div>
                                </div>

                                <div class="col-lg-4 col-md-6 dinv-item-extra-qty-group">
                                    <label class="form-label text-muted small fw-bold mb-1">Extra Shifting Qty (Free Plants)</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control fw-bold text-warning-emphasis dinv-item-extra-qty dinv-item-calc-input" min="0" value="${extraQty > 0 ? extraQty : (isShifted ? Math.ceil(allocQty / 125) : 0)}" ${isShifted ? 'readonly' : ''}>
                                        <span class="input-group-text bg-white text-muted">Plants</span>
                                    </div>
                                    <div class="text-muted mt-0.5" style="font-size: 11px;">${isShifted ? '1 Free Plant per 126 Tray' : '0-cost transit buffer plants'}</div>
                                </div>
                            </div>

                            <!-- Live Shifting & Billing Policy Notification Banner -->
                            <div class="mt-2.5 dinv-item-shifting-notice">
                                <!-- Populated dynamically by JS -->
                            </div>
                        </div>

                        <!-- 3. Financial & Tax Breakdown per Product -->
                        <div class="row g-2 align-items-center">
                            <div class="col-md-2 col-sm-4">
                                <label class="form-label text-muted small fw-bold mb-1">Unit Rate (₹) <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light">₹</span>
                                    <input type="number" class="form-control fw-bold dinv-item-rate dinv-item-calc-input" step="0.01" min="0" value="${rate.toFixed(2)}">
                                </div>
                            </div>

                            <div class="col-md-2 col-sm-4">
                                <label class="form-label text-muted small fw-bold mb-1">Discount (%)</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" class="form-control dinv-item-disc-pct dinv-item-calc-input" step="0.01" min="0" value="${discPct > 0 ? discPct.toFixed(2) : '0.00'}">
                                    <span class="input-group-text bg-light">%</span>
                                </div>
                            </div>

                            <div class="col-md-2 col-sm-4">
                                <label class="form-label text-muted small fw-bold mb-1">Taxable Amount (₹)</label>
                                <input type="number" class="form-control form-control-sm fw-bold bg-light dinv-item-taxable text-dark" value="0.00" readonly>
                            </div>

                            <div class="col-md-2 col-sm-4">
                                <label class="form-label text-muted small fw-bold mb-1">CGST (%)</label>
                                <input type="number" class="form-control form-control-sm dinv-item-cgst-pct dinv-item-calc-input" step="0.01" min="0" value="${cgstPct > 0 ? cgstPct.toFixed(2) : ''}" placeholder="%">
                                <input type="hidden" class="dinv-item-cgst-amt" value="0.00">
                            </div>

                            <div class="col-md-2 col-sm-4">
                                <label class="form-label text-muted small fw-bold mb-1">SGST (%)</label>
                                <input type="number" class="form-control form-control-sm dinv-item-sgst-pct dinv-item-calc-input" step="0.01" min="0" value="${sgstPct > 0 ? sgstPct.toFixed(2) : ''}" placeholder="%">
                                <input type="hidden" class="dinv-item-sgst-amt" value="0.00">
                            </div>

                            <div class="col-md-2 col-sm-4">
                                <label class="form-label text-muted small fw-bold mb-1">IGST (%)</label>
                                <input type="number" class="form-control form-control-sm dinv-item-igst-pct dinv-item-calc-input" step="0.01" min="0" value="${igstPct > 0 ? igstPct.toFixed(2) : ''}" placeholder="%">
                                <input type="hidden" class="dinv-item-igst-amt" value="0.00">
                            </div>

                            <input type="hidden" class="dinv-item-disc-amt" value="0.00">
                            <input type="hidden" class="dinv-item-tax-amt" value="0.00">
                            <input type="hidden" class="dinv-item-subtotal" value="0.00">
                            <input type="hidden" class="dinv-item-billable-qty" value="0">
                            <input type="hidden" class="dinv-item-dispatch-qty" value="0">
                        </div>

                        <!-- 4. Product Item Summary Banner (Physical Dispatch vs Invoiced Billing) -->
                        <div class="mt-3 p-2.5 rounded-3 border d-flex justify-content-between align-items-center flex-wrap gap-2 text-dark" style="background: #f1f5f9;">
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <div>
                                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 11px;"><i class="fa fa-truck text-primary me-1"></i> Physical Dispatch:</span>
                                    <span class="dinv-item-dispatch-formula fw-bold text-dark ms-1">0 Plants</span>
                                </div>
                                <div class="border-start ps-3">
                                    <span class="text-success small fw-bold text-uppercase" style="font-size: 11px;"><i class="fa fa-receipt text-success me-1"></i> Invoiced / Billable:</span>
                                    <span class="dinv-item-billable-formula fw-bold text-success ms-1">0 Plants</span>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle ms-1" style="font-size: 10px;">Allocated Qty Only</span>
                                </div>
                            </div>
                            <div>
                                <span class="text-muted small fw-bold text-uppercase" style="font-size: 11px;">Item Subtotal (Taxable + GST):</span>
                                <span class="dinv-item-subtotal-display fw-bold text-success fs-6 ms-1 font-monospace">₹ 0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            container.append(itemHtml);
        });
    }

    // Individual Item Checkbox Include / Exclude Handler
    $(document).on("change", ".dinv-item-include-checkbox", function() {
        let card = $(this).closest(".dinv-product-card");
        let isChecked = $(this).is(":checked");
        
        if (isChecked) {
            card.removeClass("border-dashed bg-light opacity-75 dinv-card-excluded");
            card.find(".dinv-item-status-badge").removeClass("bg-secondary bg-danger").addClass("bg-success").html(`<i class="fa fa-check-circle me-1"></i> Included`);
            card.find(".dinv-card-header").css("background", "#f8fafc");
            card.find(".dinv-card-body").css("background", "#ffffff");
            card.find("input:not(.dinv-item-include-checkbox), select, button:not(.dinv-go-back-reallocate-btn)").prop("disabled", false);
            // Restore shifted disabled inputs if needed
            let isShifted = card.find(".dinv-item-shifted-radio:checked").val() === "yes";
            if (isShifted) {
                card.find(".dinv-item-extra-pct, .dinv-item-extra-qty").prop("disabled", true);
            }
        } else {
            card.addClass("border-dashed bg-light opacity-75 dinv-card-excluded");
            let phQty = parseInt(card.attr("data-ph-qty") || card.data("ph-qty") || 0);
            let readyQty = parseInt(card.attr("data-ready-qty") || card.data("ready-qty") || 0);
            let trayCount = parseInt(card.attr("data-tray-count") || card.data("tray-count") || 0);
            let isZeroPh = (phQty <= 0 && readyQty <= 0 && trayCount <= 0);
            let badgeText = isZeroPh ? `<i class="fa fa-ban me-1"></i> Excluded (0 in Polyhouse)` : `<i class="fa fa-ban me-1"></i> Excluded from Invoice`;
            card.find(".dinv-item-status-badge").removeClass("bg-success").addClass("bg-secondary").html(badgeText);
            card.find(".dinv-card-header").css("background", "#f1f5f9");
            card.find(".dinv-card-body").css("background", "#fafafa");
            card.find("input:not(.dinv-item-include-checkbox), select, button:not(.dinv-go-back-reallocate-btn)").prop("disabled", true);
        }

        recalculateAllInvoiceData();
    });

    // Go Back & Reallocate Button Handler
    $(document).on("click", ".dinv-go-back-reallocate-btn", function(e) {
        e.preventDefault();
        let soiId = $(this).data("soi-id");
        $("#prepareDispatchInvoiceModal").modal("hide");
        if (soiId) {
            let $row = $(`tr[data-soi-id='${soiId}'], tr[data-id='${soiId}']`);
            if ($row.length) {
                $('html, body').animate({
                    scrollTop: $row.offset().top - 120
                }, 500);
                $row.addClass('table-warning');
                setTimeout(function() {
                    $row.removeClass('table-warning');
                }, 3000);
            }
        }
    });

    // Shifting toggle handler per product item
    $(document).on("change", ".dinv-item-shifted-radio", function() {
        let card = $(this).closest(".dinv-product-card");
        let isShifted = card.find(".dinv-item-shifted-radio:checked").val() === "yes";

        if (isShifted) {
            let allocQty = parseInt(card.find(".dinv-item-alloc-qty").val()) || 0;
            let defaultBonus = Math.ceil(allocQty / 125);
            card.find(".dinv-item-extra-pct").val("").prop("disabled", true);
            card.find(".dinv-item-extra-qty").val(defaultBonus).prop("disabled", false).prop("readonly", true);
            card.find(".dinv-item-extra-percent-group").addClass("opacity-50");
            card.find(".dinv-item-extra-qty-group").removeClass("opacity-50");
        } else {
            card.find(".dinv-item-extra-pct").prop("disabled", false);
            card.find(".dinv-item-extra-qty").prop("disabled", false).prop("readonly", false);
            card.find(".dinv-item-extra-percent-group, .dinv-item-extra-qty-group").removeClass("opacity-50");

            let allocQty = parseInt(card.find(".dinv-item-alloc-qty").val()) || 0;
            let currentPct = parseFloat(card.find(".dinv-item-extra-pct").val()) || 0;
            if (currentPct <= 0) {
                currentPct = 30; // default 30% when switching to NO
                card.find(".dinv-item-extra-pct").val(currentPct);
            }
            let extra = Math.round(allocQty * (currentPct / 100));
            card.find(".dinv-item-extra-qty").val(extra);
        }
        recalculateAllInvoiceData();
    });

    // Dynamic input calculation handler
    $(document).on("input change", ".dinv-item-calc-input", function() {
        let card = $(this).closest(".dinv-product-card");
        let isShifted = card.find(".dinv-item-shifted-radio:checked").val() === "yes";

        if ($(this).hasClass("dinv-item-alloc-qty")) {
            let newAlloc = parseInt($(this).val()) || 0;
            let unhealthy = parseInt(card.find(".dinv-item-unhealthy").val()) || 0;
            card.find(".dinv-item-healthy").val(Math.max(0, newAlloc - unhealthy));
            if (isShifted) {
                let bonus = Math.ceil(newAlloc / 125);
                card.find(".dinv-item-extra-qty").val(bonus);
            } else {
                let pct = parseFloat(card.find(".dinv-item-extra-pct").val()) || 0;
                let extra = Math.round(newAlloc * (pct / 100));
                card.find(".dinv-item-extra-qty").val(extra);
            }
        } else if ($(this).hasClass("dinv-item-unhealthy")) {
            let alloc = parseInt(card.find(".dinv-item-alloc-qty").val()) || 0;
            let unh = parseInt($(this).val()) || 0;
            card.find(".dinv-item-healthy").val(Math.max(0, alloc - unh));
        }

        if (!isShifted) {
            let allocQty = parseInt(card.find(".dinv-item-alloc-qty").val()) || 0;
            if ($(this).hasClass("dinv-item-extra-pct")) {
                let pct = parseFloat($(this).val()) || 0;
                let extra = Math.round(allocQty * (pct / 100));
                card.find(".dinv-item-extra-qty").val(extra);
            } else if ($(this).hasClass("dinv-item-extra-qty")) {
                let extraQty = parseInt($(this).val()) || 0;
                if (allocQty > 0) {
                    let pct = Math.round(((extraQty / allocQty) * 100) * 100) / 100;
                    card.find(".dinv-item-extra-pct").val(pct);
                }
            }
        }
        recalculateAllInvoiceData();
    });

    // Manual change of freight charges input
    $(document).on("input change", "#dinv_freight_charges", function() {
        recalculateGrandTotalOnly();
    });

    // Box Packing charges toggle & calculation handlers
    $(document).on("change", "#dinv_enable_box_packing", function() {
        let isChecked = $(this).is(":checked");
        if (isChecked) {
            $("#dinv_box_packing_body").slideDown(150);
            let curCount = parseInt($("#dinv_box_count").val()) || 0;
            if (curCount <= 0) {
                let totalDispatchQty = parseInt(($("#dinv_sum_dispatch_qty").text() || '').replace(/[^0-9]/g, '')) || 0;
                let boxCapacity = 500;
                if (typeof currentInvoiceData !== 'undefined' && currentInvoiceData && currentInvoiceData.delivery_location && parseInt(currentInvoiceData.delivery_location.boxperpack) > 0) {
                    boxCapacity = parseInt(currentInvoiceData.delivery_location.boxperpack);
                }
                let suggested = totalDispatchQty > 0 ? Math.ceil(totalDispatchQty / boxCapacity) : 0;
                $("#dinv_box_count").val(suggested);
            }
        } else {
            $("#dinv_box_packing_body").slideUp(150);
        }
        recalculateAllInvoiceData();
    });

    $(document).on("input change", "#dinv_box_count", function() {
        let val = parseInt($(this).val()) || 0;
        if (val < 0) {
            $(this).val(0);
        }
        recalculateAllInvoiceData();
    });

    $(document).on("input change", "#dinv_box_rate", function() {
        let rateVal = parseFloat($(this).val());
        if (isNaN(rateVal) || rateVal < 0) {
            rateVal = 0;
        }
        $("#dinv_box_rate_badge").text(`₹ ${rateVal.toFixed(2)} / Box`);
        recalculateAllInvoiceData();
    });

    $(document).on("click", "#dinv_auto_calc_boxes_btn", function() {
        if (!$("#dinv_enable_box_packing").is(":checked")) {
            $("#dinv_enable_box_packing").prop("checked", true);
            $("#dinv_box_packing_body").slideDown(150);
        }
        let totalDispatchQty = parseInt(($("#dinv_sum_dispatch_qty").text() || '').replace(/[^0-9]/g, '')) || 0;
        let boxCapacity = 500;
        if (typeof currentInvoiceData !== 'undefined' && currentInvoiceData && currentInvoiceData.delivery_location && parseInt(currentInvoiceData.delivery_location.boxperpack) > 0) {
            boxCapacity = parseInt(currentInvoiceData.delivery_location.boxperpack);
        }
        let suggested = totalDispatchQty > 0 ? Math.ceil(totalDispatchQty / boxCapacity) : 0;
        $("#dinv_box_count").val(suggested);
        recalculateAllInvoiceData();
    });

    // Recalculate all invoice items, order summary, evaluate freight discount rule, and grand total
    function recalculateAllInvoiceData() {
        let totalCards = $(".dinv-product-card").length;
        let checkedCards = $(".dinv-product-card").filter(function() {
            return $(this).find(".dinv-item-include-checkbox").is(":checked");
        });

        $("#dinv_product_count_badge").text(`${checkedCards.length} of ${totalCards} Product${totalCards > 1 ? 's' : ''} Selected`);
        if (checkedCards.length === 0) {
            $("#dinv_product_count_badge").removeClass("bg-success-subtle text-success border-success-subtle").addClass("bg-danger-subtle text-danger border-danger-subtle");
        } else {
            $("#dinv_product_count_badge").removeClass("bg-danger-subtle text-danger border-danger-subtle").addClass("bg-success-subtle text-success border-success-subtle");
        }

        let totalOrderQty = 0;
        let totalAllocQty = 0;
        let totalShiftingQty = 0;
        let totalDispatchQty = 0;
        let totalTaxableAmt = 0;
        let totalTaxAmt = 0;

        let hasSelectedGrafted = false;
        let hasSelectedNongrafted = false;

        $(".dinv-product-card").each(function() {
            let card = $(this);
            let isIncluded = card.find(".dinv-item-include-checkbox").is(":checked");

            let orderQty = parseInt(card.find(".dinv-item-order-qty").val()) || 0;
            let allocQty = parseInt(card.find(".dinv-item-alloc-qty").val()) || 0;
            let isShifted = card.find(".dinv-item-shifted-radio:checked").val() === "yes";
            let defaultBonus = Math.ceil(allocQty / 125);
            let shiftingQty = isShifted ? (parseInt(card.find(".dinv-item-extra-qty").val()) || defaultBonus) : (parseInt(card.find(".dinv-item-extra-qty").val()) || 0);
            let shiftingPct = isShifted ? 0 : (parseFloat(card.find(".dinv-item-extra-pct").val()) || 0);

            // Billable Qty is STRICTLY the Allocated Quantity
            let billableQty = allocQty;
            // Physical Dispatch Qty includes complimentary shifting plants
            let dispatchQty = allocQty + shiftingQty;

            card.find(".dinv-item-billable-qty").val(billableQty);
            card.find(".dinv-item-dispatch-qty").val(dispatchQty);

            // Update live shifting & billing notice inside the card
            let noticeContainer = card.find(".dinv-item-shifting-notice");
            if (isIncluded) {
                if (isShifted) {
                    noticeContainer.html(`
                        <div class="alert alert-success d-flex align-items-center gap-2 mb-0 py-1.5 px-2.5 rounded-2 border-success-subtle small" style="font-size: 11.5px;">
                            <i class="fa fa-check-circle text-success fs-6"></i>
                            <div>
                                <b>Shifted Order (1 Free Plant per 126 Tray):</b> <b>+${shiftingQty.toLocaleString()} Free Bonus Plants</b> (1 per tray). 
                                Physical Dispatch: <b>${dispatchQty.toLocaleString()} Plants</b> (${Math.ceil(allocQty / 125)} Trays). Customer billed strictly for <b>${billableQty.toLocaleString()} Plants</b>.
                            </div>
                        </div>
                    `);
                    card.find(".dinv-item-dispatch-formula").html(`${dispatchQty.toLocaleString()} Plants <span class="text-muted fw-normal" style="font-size:11px;">(${allocQty.toLocaleString()} Billed + ${shiftingQty.toLocaleString()} Free Bonus)</span>`);
                    card.find(".dinv-item-billable-formula").text(`${billableQty.toLocaleString()} Plants`);
                } else {
                    noticeContainer.html(`
                        <div class="alert alert-warning d-flex align-items-center gap-2 mb-0 py-1.5 px-2.5 rounded-2 border-warning-subtle small text-dark" style="font-size: 11.5px; background: #fffbeb;">
                            <i class="fa fa-info-circle text-warning-emphasis fs-6"></i>
                            <div>
                                <b>Non-Shifted Plant with Free Buffer:</b> <b>+${shiftingQty.toLocaleString()} Complimentary Buffer Plants (+${shiftingPct}%)</b> added for transit safety. 
                                Physical Dispatch: <b>${dispatchQty.toLocaleString()} Plants</b> | Billing is strictly on <b>${billableQty.toLocaleString()} Allocated Plants</b>.
                            </div>
                        </div>
                    `);
                    card.find(".dinv-item-dispatch-formula").html(`${dispatchQty.toLocaleString()} Plants <span class="text-muted fw-normal" style="font-size:11px;">(${allocQty.toLocaleString()} Alloc + ${shiftingQty.toLocaleString()} Free Buffer)</span>`);
                    card.find(".dinv-item-billable-formula").text(`${billableQty.toLocaleString()} Plants`);
                }
            } else {
                noticeContainer.html(`
                    <div class="alert alert-secondary d-flex align-items-center gap-2 mb-0 py-1.5 px-2.5 rounded-2 border-secondary-subtle small text-muted" style="font-size: 11.5px;">
                        <i class="fa fa-ban text-secondary fs-6"></i>
                        <div>
                            <b>Item Excluded from this Dispatch Invoice:</b> This product item will not be billed or counted in totals/freight for this invoice generation.
                        </div>
                    </div>
                `);
                card.find(".dinv-item-dispatch-formula").html(`<span class="text-muted"><del>${dispatchQty.toLocaleString()} Plants</del> (Excluded)</span>`);
                card.find(".dinv-item-billable-formula").html(`<span class="text-muted"><del>${billableQty.toLocaleString()} Plants</del> (Excluded)</span>`);
            }

            let rate = parseFloat(card.find(".dinv-item-rate").val()) || 0;
            // Gross amount is STRICTLY Billable Qty (Allocated Qty) multiplied by Rate
            let grossAmount = billableQty * rate;

            let discPct = parseFloat(card.find(".dinv-item-disc-pct").val()) || 0;
            let discAmt = 0;
            if (discPct > 0) {
                discAmt = Math.round((grossAmount * (discPct / 100)) * 100) / 100;
            }
            card.find(".dinv-item-disc-amt").val(discAmt.toFixed(2));

            let taxable = Math.max(0, grossAmount - discAmt);
            card.find(".dinv-item-taxable").val(taxable.toFixed(2));

            let cgstPct = parseFloat(card.find(".dinv-item-cgst-pct").val()) || 0;
            let sgstPct = parseFloat(card.find(".dinv-item-sgst-pct").val()) || 0;
            let igstPct = parseFloat(card.find(".dinv-item-igst-pct").val()) || 0;

            let cgstAmt = Math.round((taxable * (cgstPct / 100)) * 100) / 100;
            let sgstAmt = Math.round((taxable * (sgstPct / 100)) * 100) / 100;
            let igstAmt = Math.round((taxable * (igstPct / 100)) * 100) / 100;

            card.find(".dinv-item-cgst-amt").val(cgstAmt.toFixed(2));
            card.find(".dinv-item-sgst-amt").val(sgstAmt.toFixed(2));
            card.find(".dinv-item-igst-amt").val(igstAmt.toFixed(2));

            let itemTax = cgstAmt + sgstAmt + igstAmt;
            card.find(".dinv-item-tax-amt").val(itemTax.toFixed(2));

            let itemSubtotal = taxable + itemTax;
            card.find(".dinv-item-subtotal").val(itemSubtotal.toFixed(2));

            if (isIncluded) {
                card.find(".dinv-item-subtotal-display").removeClass("text-muted text-decoration-line-through").addClass("text-success").text(`₹ ${itemSubtotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
                
                let isGrafted = card.find(".dinv-item-is-grafted").val() == "1";
                if (isGrafted) hasSelectedGrafted = true; else hasSelectedNongrafted = true;

                totalOrderQty += orderQty;
                totalAllocQty += allocQty;
                totalShiftingQty += shiftingQty;
                totalDispatchQty += dispatchQty;
                totalTaxableAmt += taxable;
                totalTaxAmt += itemTax;
            } else {
                card.find(".dinv-item-subtotal-display").removeClass("text-success").addClass("text-muted text-decoration-line-through").text(`₹ ${itemSubtotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} (Excluded)`);
            }
        });

        // Box Packing Charges Auto-Calculation
        let isBoxPacking = $("#dinv_enable_box_packing").is(":checked");
        let boxCount = isBoxPacking ? (parseInt($("#dinv_box_count").val()) || 0) : 0;
        let boxRate = parseFloat($("#dinv_box_rate").val()) || 100.00;
        let boxTotal = isBoxPacking ? Math.round(boxCount * boxRate * 100) / 100 : 0;

        $("#dinv_box_dispatch_plants_display").val(`${totalDispatchQty.toLocaleString()} Plants`);
        $("#dinv_box_total_charge").val(boxTotal.toFixed(2));
        $("#dinv_box_calc_formula_text").text(`${boxCount} boxes × ₹ ${boxRate.toFixed(2)} = ₹ ${boxTotal.toFixed(2)}`);

        // Add box packing amount to total taxable / billable amount
        totalTaxableAmt += boxTotal;

        // Update Combined Order Summary Bar
        $("#dinv_sum_order_qty").text(`${totalOrderQty.toLocaleString()} Plants`);
        $("#dinv_sum_alloc_qty").text(`${totalAllocQty.toLocaleString()} Plants`);
        $("#dinv_sum_shifting_qty").text(`${totalShiftingQty.toLocaleString()} Plants`);
        $("#dinv_sum_dispatch_qty").text(`${totalDispatchQty.toLocaleString()} Plants`);
        $("#dinv_sum_taxable_amt").text(`₹ ${totalTaxableAmt.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
        $("#dinv_sum_tax_amt").text(`₹ ${totalTaxAmt.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);

        $("#dinv_tax_amount").val(totalTaxAmt.toFixed(2));
        $("#dinv_total_amount").val(totalTaxableAmt.toFixed(2));

        // Disable / Enable "Next: Freight Details" button based on Total Order Qty
        let $btnNext = $("#dinv_btn_next_to_2");
        let $btnNextTop = $(".dinv-top-btn-next-to-2");
        if (totalOrderQty <= 0 || totalAllocQty <= 0) {
            $btnNext.prop("disabled", true)
                    .addClass("disabled")
                    .css({ "opacity": "0.55", "cursor": "not-allowed" })
                    .attr("title", "Cannot proceed: Total Order Qty is 0 Plants. Please include at least one valid product item.");
            $btnNextTop.prop("disabled", true)
                       .addClass("disabled")
                       .css({ "opacity": "0.55", "cursor": "not-allowed" })
                       .attr("title", "Cannot proceed: Total Order Qty is 0 Plants. Please include at least one valid product item.");
        } else {
            $btnNext.prop("disabled", false)
                    .removeClass("disabled")
                    .css({ "opacity": "1", "cursor": "pointer" })
                    .removeAttr("title");
            $btnNextTop.prop("disabled", false)
                       .removeClass("disabled")
                       .css({ "opacity": "1", "cursor": "pointer" })
                       .removeAttr("title");
        }

        // Render Freight Group Table & Evaluate Discount Rules
        renderDispatchFreightTable();

        recalculateGrandTotalOnly();
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Global state for dispatch freight rules & groups
    window.currentDispatchFreightRules = [];
    window._currentFreightGroups = [];
    window._activeDeliveryGroupIndex = 0;

    /**
     * Format a date string (ISO YYYY-MM-DD or DD-Mon-YYYY or Date) into standard display DD-Mon-YYYY (e.g. 11-Oct-2026)
     */
    function formatDispatchDateDisplay(dateStr) {
        if (!dateStr || dateStr === '-' || dateStr === 'N/A') return '-';
        if (typeof dateStr === 'string' && /^\d{1,2}-[A-Za-z]{3}-\d{4}$/.test(dateStr.trim())) {
            return dateStr.trim();
        }
        let d = null;
        if (typeof dateStr === 'string') {
            let s = dateStr.trim();
            if (/^\d{4}-\d{2}-\d{2}/.test(s)) {
                let p = s.split('T')[0].split('-');
                d = new Date(parseInt(p[0]), parseInt(p[1]) - 1, parseInt(p[2]));
            } else if (/^\d{1,2}-[A-Za-z]{3}-\d{4}/.test(s)) {
                let p = s.split('-');
                const mObj = {'jan':0,'feb':1,'mar':2,'apr':3,'may':4,'jun':5,'jul':6,'aug':7,'sep':8,'oct':9,'nov':10,'dec':11};
                let m = mObj[p[1].toLowerCase()];
                if (m !== undefined) d = new Date(parseInt(p[2]), m, parseInt(p[0]));
            }
        }
        if (!d || isNaN(d.getTime())) {
            d = new Date(dateStr);
        }
        if (!d || isNaN(d.getTime())) return String(dateStr);
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        let day = String(d.getDate()).padStart(2, '0');
        let month = months[d.getMonth()];
        let year = d.getFullYear();
        return `${day}-${month}-${year}`;
    }

    /**
     * Format a date string into ISO YYYY-MM-DD for HTML5 date inputs
     */
    function formatDispatchDateISO(dateStr) {
        if (!dateStr || dateStr === '-' || dateStr === 'N/A') return '';
        if (typeof dateStr === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(dateStr.trim())) {
            return dateStr.trim();
        }
        let d = null;
        if (typeof dateStr === 'string') {
            let s = dateStr.trim();
            if (/^\d{1,2}-[A-Za-z]{3}-\d{4}/.test(s)) {
                let p = s.split('-');
                const mObj = {'jan':0,'feb':1,'mar':2,'apr':3,'may':4,'jun':5,'jul':6,'aug':7,'sep':8,'oct':9,'nov':10,'dec':11};
                let m = mObj[p[1].toLowerCase()];
                if (m !== undefined) d = new Date(parseInt(p[2]), m, parseInt(p[0]));
            }
        }
        if (!d || isNaN(d.getTime())) {
            d = new Date(dateStr);
        }
        if (!d || isNaN(d.getTime())) return '';
        let year = d.getFullYear();
        let month = String(d.getMonth() + 1).padStart(2, '0');
        let day = String(d.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    /**
     * Unify all included product items to 1 single delivery date
     */
    function applyUnifiedDeliveryDate(targetDateStr) {
        if (!targetDateStr) return;
        let formattedDate = formatDispatchDateDisplay(targetDateStr);
        let isoDate = formatDispatchDateISO(targetDateStr);

        if (!currentInvoiceData) return;

        // Backup original dates if not yet backed up
        if (!currentInvoiceData._original_item_dates) {
            currentInvoiceData._original_item_dates = [];
            $(".dinv-product-card").each(function() {
                let card = $(this);
                currentInvoiceData._original_item_dates.push({
                    index: card.data("index"),
                    formatted: card.find(".dinv-item-formatted-delivery-date").val() || '',
                    raw: card.find(".dinv-item-deliverydate").val() || ''
                });
            });
        }

        // 1. Update all product cards in DOM
        $(".dinv-product-card").each(function() {
            let card = $(this);
            card.find(".dinv-item-formatted-delivery-date").val(formattedDate);
            card.find(".dinv-item-deliverydate").val(isoDate);
            card.find(".dinv-item-deliverydate-display").text(formattedDate);
        });

        // 2. Update currentInvoiceData.items array
        if (Array.isArray(currentInvoiceData.items)) {
            currentInvoiceData.items.forEach(it => {
                it.formatted_delivery_date = formattedDate;
                it.deliverydate = isoDate;
                it.confirmdeliverydate = isoDate;
            });
        }

        // 3. Mark unification state
        currentInvoiceData.unified_delivery_date = formattedDate;
        currentInvoiceData.has_date_unification_applied = true;
        $("#dinv_scheduled_dispatch_date").val(isoDate);

        // 4. Re-render freight table fresh - now all items with same address group into 1 row!
        renderDispatchFreightTable();

        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: `Unified delivery date set to: ${formattedDate}`,
            showConfirmButton: false,
            timer: 2000
        });
    }

    /**
     * Restore original distinct delivery dates
     */
    function resetUnifiedDeliveryDates() {
        if (!currentInvoiceData || !currentInvoiceData._original_item_dates) return;

        currentInvoiceData._original_item_dates.forEach(orig => {
            let card = $(`.dinv-product-card[data-index="${orig.index}"]`);
            if (card.length) {
                card.find(".dinv-item-formatted-delivery-date").val(orig.formatted);
                card.find(".dinv-item-deliverydate").val(orig.raw);
                card.find(".dinv-item-deliverydate-display").text(orig.formatted || '-');
            }
            if (currentInvoiceData.items && currentInvoiceData.items[orig.index]) {
                currentInvoiceData.items[orig.index].formatted_delivery_date = orig.formatted;
                currentInvoiceData.items[orig.index].deliverydate = orig.raw;
                currentInvoiceData.items[orig.index].confirmdeliverydate = orig.raw;
            }
        });

        delete currentInvoiceData.unified_delivery_date;
        currentInvoiceData.has_date_unification_applied = false;

        renderDispatchFreightTable();

        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'info',
            title: 'Restored original distinct delivery dates',
            showConfirmButton: false,
            timer: 1500
        });
    }

    /**
     * Render the Unified / Single Delivery Date Option Control Banner
     */
    function renderUnifiedDeliveryDateControl(distinctDates, totalCheckedCards) {
        let container = $("#dinvUnifiedDeliveryDateSection");
        if (!container.length) return;
        container.empty();

        if (!distinctDates || distinctDates.length === 0) {
            container.hide();
            return;
        }

        container.show();
        let firstIso = formatDispatchDateISO(distinctDates[0]);
        let activeDelMode = $("input[name='dinv_delivery_type']:checked").val() || 'delivered';
        let modeLabel = (activeDelMode === 'pickup') ? 'Self Pickup' : (activeDelMode === 'boxpacking' ? 'Box Packing' : 'Farm Delivery');

        if (distinctDates.length > 1) {
            // Multiple different dates! Prompt farmer alignment to 1 single delivery date
            let html = `
                <div class="card border border-warning-subtle shadow-sm rounded-3 mb-2" style="background: linear-gradient(to right, #fffbeb, #ffffff);">
                    <div class="card-body p-2.5 px-3">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-warning text-dark p-1.5 rounded-circle d-inline-flex align-items-center justify-content-center shadow-2xs" style="width: 28px; height: 28px;">
                                    <i class="fa fa-calendar-alt" style="font-size: 13px;"></i>
                                </span>
                                <div>
                                    <div class="fw-bold text-dark d-flex align-items-center gap-1.5" style="font-size: 13px;">
                                        <i class="fa fa-exclamation-triangle text-warning-emphasis"></i>
                                        <span>Different Delivery Dates Detected (${distinctDates.length} Dates: ${distinctDates.join(', ')})</span>
                                    </div>
                                    <div class="text-secondary small" style="font-size: 11.5px; line-height: 1.35;">
                                        Farmer agreed to dispatch all products on <b>1 single delivery date</b> (${modeLabel})? Select 1 date below to unify all products into 1 single dispatch trip:
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-1.5 flex-wrap ms-auto">
                                <span class="small fw-bold text-dark text-nowrap"><i class="fa fa-mouse-pointer text-primary me-1"></i>Select 1 Date:</span>
                                ${distinctDates.map(dt => `
                                    <button type="button" class="btn btn-sm btn-outline-primary fw-bold dinv-quick-unify-date-btn px-2.5 py-1 rounded-2 shadow-2xs" data-date="${escapeHtml(dt)}" title="Unify all products to ${escapeHtml(dt)}">
                                        <i class="fa fa-calendar-check me-1"></i> ${escapeHtml(dt)}
                                    </button>
                                `).join('')}
                                <div class="input-group input-group-sm ms-sm-1" style="width: 160px;">
                                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa fa-calendar text-primary"></i></span>
                                    <input type="date" class="form-control form-control-sm border-start-0 fw-bold text-dark" id="dinv_custom_unified_date" value="${firstIso}" title="Choose custom single delivery date">
                                </div>
                                <button type="button" class="btn btn-sm btn-primary fw-bold px-2.5 py-1 shadow-2xs" id="dinv_apply_custom_unified_date_btn" title="Set chosen date for all products">
                                    <i class="fa fa-check me-1"></i> Set 1 Date
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            container.html(html);
        } else {
            // All products have 1 single unified delivery date
            let singleDate = distinctDates[0];
            let isUnificationApplied = Boolean(currentInvoiceData && currentInvoiceData.has_date_unification_applied);
            let html = `
                <div class="card border border-success-subtle shadow-sm rounded-3 mb-2" style="background: linear-gradient(to right, #f0fdf4, #ffffff);">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-success text-white p-1 rounded-circle d-inline-flex align-items-center justify-content-center shadow-2xs" style="width: 24px; height: 24px;">
                                    <i class="fa fa-check" style="font-size: 11px;"></i>
                                </span>
                                <div>
                                    <span class="fw-bold text-dark" style="font-size: 12.5px;">Single Delivery Date for Dispatch:</span>
                                    <span class="badge bg-success text-white px-2.5 py-1 ms-1 font-monospace" style="font-size: 12px;"><i class="fa fa-calendar-check me-1"></i>${escapeHtml(singleDate)}</span>
                                    <span class="text-muted small ms-2 d-none d-md-inline">(${totalCheckedCards} product(s) aligned to 1 date for ${modeLabel})</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-1.5 flex-wrap ms-auto">
                                <span class="small text-secondary fw-semibold">Change 1 Date for All:</span>
                                <div class="input-group input-group-sm" style="width: 155px;">
                                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa fa-calendar text-primary"></i></span>
                                    <input type="date" class="form-control form-control-sm border-start-0 fw-bold text-dark" id="dinv_custom_unified_date" value="${firstIso}">
                                </div>
                                <button type="button" class="btn btn-xs btn-outline-primary fw-bold px-2.5 py-1 shadow-2xs" id="dinv_apply_custom_unified_date_btn">
                                    <i class="fa fa-edit me-1"></i> Update Date
                                </button>
                                ${isUnificationApplied ? `
                                    <button type="button" class="btn btn-xs btn-outline-secondary px-2 py-1 shadow-2xs dinv-reset-unify-date-btn" title="Restore original distinct dates">
                                        <i class="fa fa-undo me-1"></i> Reset
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            `;
            container.html(html);
        }
    }

    /**
     * Render the Freight Charge Details (Grouped by Address & Date) table rows
     */
    function renderDispatchFreightTable() {
        if (!currentInvoiceData) return;

        const $tbody = $("#dinvFreightGroupTable tbody");
        $tbody.empty();

        let d = currentInvoiceData;
        let defaultDl = d.delivery_location || {};
        let ord = d.order || {};
        let allLocs = Array.isArray(d.delivery_locations) && d.delivery_locations.length > 0 ? d.delivery_locations : [defaultDl];

        let checkedCards = $(".dinv-product-card").filter(function() {
            return $(this).find(".dinv-item-include-checkbox").is(":checked");
        });

        if (checkedCards.length === 0) {
            $tbody.append('<tr><td colspan="8" class="text-center py-3 text-muted">No product items selected. Check at least one product above.</td></tr>');
            $("#dinv_tbl_base_freight_display").text('0.00');
            $("#dinv_tbl_payable_freight_display").text('0.00');
            $("#dinv_freight_charges").val('0.00');
            $("#dinvFreeFreightNotification").empty().hide();
            $("#dinvUnifiedDeliveryDateSection").empty().hide();
            window._currentFreightGroups = [];
            return;
        }

        // Build groups by (Address/Location + Delivery Date)
        let groupMap = {};
        let groupsOrder = [];
        let distinctDates = [];
        let distinctDatesMap = {};

        checkedCards.each(function() {
            let card = $(this);
            let itemIdx = card.data("index");
            let itemData = (d.items && d.items[itemIdx]) ? d.items[itemIdx] : {};
            let pId = card.find(".dinv-item-product-id").val() || itemData.product_id || '';
            let pName = card.find(".dinv-item-product-name").val() || itemData.clean_product_name || itemData.product_name || 'Product';
            let titleEl = card.find(".dinv-product-clean-title");
            let cleanName = titleEl.length ? titleEl.text().trim() : (card.find(".dinv-card-header .fw-bold").not('.dinv-include-label').text().trim() || pName);
            cleanName = cleanName.replace(/Include in Invoice/gi, '').trim();
            if (!cleanName) cleanName = pName;

            let orderQty = parseInt(card.find(".dinv-item-order-qty").val()) || 0;
            let allocQty = parseInt(card.find(".dinv-item-alloc-qty").val()) || 0;
            let isGrafted = card.find(".dinv-item-is-grafted").val() == "1";
            let itemDate = card.find(".dinv-item-formatted-delivery-date").val() || card.find(".dinv-item-deliverydate").val() || itemData.formatted_delivery_date || itemData.deliverydate || ord.orderdate || '';
            if (!itemDate || itemDate === '-') {
                itemDate = (ord.orderdate || ord.created_at) ? new Date(ord.orderdate || ord.created_at).toISOString().split('T')[0] : 'N/A';
            }
            let dispDate = formatDispatchDateDisplay(itemDate);
            if (!distinctDatesMap[dispDate]) {
                distinctDatesMap[dispDate] = itemDate;
                distinctDates.push(dispDate);
            }

            // Find matching delivery location for this product exclusively from krf_deliveryaddress_location
            let matchedLoc = null;
            if (pId && Array.isArray(allLocs) && allLocs.length > 0) {
                matchedLoc = allLocs.find(function(l) {
                    if (!l) return false;
                    if (l.productid && (String(l.productid) === String(pId) || String(l.productid).split(',').map(s=>s.trim()).includes(String(pId)))) {
                        return true;
                    }
                    return false;
                });
            }
            if (!matchedLoc) {
                matchedLoc = defaultDl || {};
            }

            // Check if valid delivery location is present in krf_deliveryaddress_location
            let isLocAvailable = Boolean(matchedLoc && matchedLoc.is_location_available !== false && (matchedLoc.deliveryaddress || matchedLoc.dest_city || matchedLoc.dest_district || matchedLoc.dest_state));

            let city = matchedLoc.dest_city || '';
            let district = matchedLoc.dest_district || '';
            let state = matchedLoc.dest_state || '';
            let pincode = matchedLoc.dest_pincode || '';
            let address = matchedLoc.deliveryaddress || '';
            let km = parseFloat(matchedLoc.distance || matchedLoc.totaldistance || 0);
            let pickupType = matchedLoc.pickuptype || 'delivered';

            let locKey = (matchedLoc.id ? ('id_' + matchedLoc.id) : (address ? address.toLowerCase().trim() : (isLocAvailable ? 'loc_available' : 'loc_missing')));
            let dateKey = dispDate.trim();
            let groupKey = locKey + '___' + dateKey;

            if (!groupMap[groupKey]) {
                groupMap[groupKey] = {
                    groupKey: groupKey,
                    isLocationAvailable: isLocAvailable,
                    location: Object.assign({}, matchedLoc, {
                        dest_city: city,
                        dest_district: district,
                        dest_state: state,
                        dest_pincode: pincode,
                        deliveryaddress: address,
                        distance: km,
                        totaldistance: km,
                        pickuptype: pickupType,
                        is_location_available: isLocAvailable
                    }),
                    deliveryDate: dispDate,
                    items: [],
                    totalOrderQty: 0,
                    totalAllocQty: 0,
                    totalGraftedQty: 0,
                    hasGrafted: false,
                    hasNongrafted: false,
                    baseFreight: null,
                    payableFreight: null,
                    discPct: 0
                };
                groupsOrder.push(groupKey);
            }

            let g = groupMap[groupKey];
            g.totalOrderQty += orderQty;
            g.totalAllocQty += allocQty;
            if (isGrafted) {
                g.hasGrafted = true;
                g.totalGraftedQty += allocQty;
            } else {
                g.hasNongrafted = true;
            }
            g.items.push({
                name: cleanName,
                orderQty: orderQty,
                allocQty: allocQty,
                isGrafted: isGrafted
            });
        });

        let groups = groupsOrder.map(k => groupMap[k]);
        window._currentFreightGroups = groups;

        // Render the Single / Unified Delivery Date Option Control Banner
        renderUnifiedDeliveryDateControl(distinctDates, checkedCards.length);
        if (distinctDates.length > 0) {
            let firstIso = formatDispatchDateISO(distinctDates[0]);
            $("#dinv_scheduled_dispatch_date").val(firstIso);
        }

        let totalTableBaseFreight = 0;
        let totalTablePayableFreight = 0;
        let isCleared = (d._discount_cleared === true);
        let activeDeliveryMode = d.delivery_location?.pickuptype || (groups[0] && groups[0].location ? groups[0].location.pickuptype : 'delivered');
        let normActiveMode = (activeDeliveryMode === 'client_pickup' || activeDeliveryMode === 'pickupbyclient' || activeDeliveryMode === 'pickup') ? 'pickup' : (activeDeliveryMode === 'boxpacking' ? 'boxpacking' : 'delivered');

        // Sync header radio selector and badge
        $(`input[name="dinv_delivery_type"][value="${normActiveMode}"]`).prop("checked", true);
        if (normActiveMode === 'pickup') {
            $("#dinv_delivery_mode_badge").attr('class', 'badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2.5 py-1').html('<i class="fa fa-user-check me-1"></i>Mode: 1. Self Pickup');
        } else if (normActiveMode === 'boxpacking') {
            $("#dinv_delivery_mode_badge").attr('class', 'badge bg-warning-subtle text-dark border border-warning-subtle px-2.5 py-1').html('<i class="fa fa-box me-1"></i>Mode: 3. Box Packing');
        } else {
            $("#dinv_delivery_mode_badge").attr('class', 'badge bg-info-subtle text-info-emphasis border border-info-subtle px-2.5 py-1').html('<i class="fa fa-truck me-1"></i>Mode: 2. Farm Delivery');
        }

        groups.forEach(function(g, gIndex) {
            let loc = g.location;
            let pickupType = loc.pickuptype || normActiveMode || 'delivered';
            let isPickup = (pickupType === 'client_pickup' || pickupType === 'pickupbyclient' || pickupType === 'pickup');
            let isBoxPacking = (pickupType === 'boxpacking');
            let isDelivered = !isPickup && !isBoxPacking;

            let isLocAvailable = Boolean(g.isLocationAvailable && (loc.deliveryaddress || loc.dest_city || loc.dest_district || loc.dest_state));
            let km = isPickup ? 0 : parseFloat(loc.distance || loc.totaldistance || 0);
            let plantType = (g.hasGrafted && g.hasNongrafted) ? 'Both' : (g.hasGrafted ? 'Grafted' : 'Nongrafted');

            let baseFreight = 0;
            let payableFreight = 0;
            let discPct = 0;

            if (isPickup) {
                baseFreight = 0;
                payableFreight = 0;
                discPct = 0;
            } else if (isBoxPacking) {
                let boxPerPack = parseInt(loc.boxperpack || 0);
                if (boxPerPack <= 0) boxPerPack = 1000;
                let chargesPerBox = parseFloat(loc.chargesperbox || 0);
                if (chargesPerBox <= 0) chargesPerBox = 70.00;
                let calcBoxes = g.totalAllocQty > 0 ? Math.ceil(g.totalAllocQty / boxPerPack) : parseInt(loc.noofbox || 1);
                baseFreight = Math.round((calcBoxes * chargesPerBox) * 100) / 100;
                payableFreight = baseFreight;
                discPct = 0;
            } else {
                // Farm Delivery (Vehicle)
                if (groups.length === 1 && typeof d.original_base_freight !== 'undefined' && parseFloat(d.original_base_freight) >= 0) {
                    baseFreight = parseFloat(d.original_base_freight);
                    payableFreight = parseFloat(d.original_payable_freight || baseFreight);
                } else if (parseFloat(loc.deliverycharges || loc.freightamt || 0) > 0) {
                    baseFreight = parseFloat(loc.deliverycharges || loc.freightamt);
                    payableFreight = parseFloat(loc.freightamt || baseFreight);
                } else {
                    baseFreight = 0;
                    payableFreight = 0;
                }

                if (typeof d.original_discount_percent !== 'undefined' && parseFloat(d.original_discount_percent) > 0) {
                    discPct = parseFloat(d.original_discount_percent);
                } else if (baseFreight > 0 && payableFreight < baseFreight) {
                    discPct = Math.round(((baseFreight - payableFreight) / baseFreight * 100) * 100) / 100;
                }
            }

            let discAmt = Math.round((baseFreight * (discPct / 100)) * 100) / 100;

            g.baseFreight = baseFreight;
            g.payableFreight = payableFreight;
            g.discPct = discPct;
            g.discAmt = discAmt;

            totalTableBaseFreight += baseFreight;
            totalTablePayableFreight += payableFreight;

            let currentVehName = '';
            let currentVehNo = '';
            let currentVehicleId = 0;
            let currentTransporter = '';
            let currentDriverName = '';
            let currentDriverMobile = '';
            let currentDriverId = '';
            let currentTripId = '';
            let isVehicleChosen = false;
            const nonVehicleNames = ['Bus', 'Train', 'Courier', 'Air', 'Railway', 'Airport', 'Transport', 'Company Delivery', 'Pickup Self'];

            if (loc.vehiclename && loc.vehiclename.trim() !== '' && !nonVehicleNames.includes(loc.vehiclename.trim())) {
                currentVehName = loc.vehiclename.trim();
                isVehicleChosen = true;
            } else if (loc.transporttype && loc.transporttype.trim() !== '' && !nonVehicleNames.includes(loc.transporttype.trim())) {
                currentVehName = loc.transporttype.trim();
                isVehicleChosen = true;
            } else if (d.freight_charges?.vehiclename && d.freight_charges.vehiclename.trim() !== '' && !nonVehicleNames.includes(d.freight_charges.vehiclename.trim())) {
                currentVehName = d.freight_charges.vehiclename.trim();
                isVehicleChosen = true;
            } else if (d.order?.vehiclename && d.order.vehiclename.trim() !== '' && !nonVehicleNames.includes(d.order.vehiclename.trim())) {
                currentVehName = d.order.vehiclename.trim();
                isVehicleChosen = true;
            }

            currentVehNo = loc.vehicleno || d.vehicle_allocation?.vehicleno || d.freight_charges?.vehicle_no || d.order?.vehicleno || '';
            currentVehicleId = parseInt(loc.vehicleid || d.vehicle_allocation?.vehicle_id || d.freight_charges?.vehicleid || d.order?.vehicleid || 0);
            currentTransporter = loc.vehicleownername || d.vehicle_allocation?.vehicleownername || d.freight_charges?.vehicleownername || '';
            currentDriverName = loc.drivername || d.vehicle_allocation?.drivername || d.freight_charges?.driver_name || '';
            currentDriverMobile = loc.drivercontactno || d.vehicle_allocation?.drivermobileno || d.freight_charges?.driver_mobile || '';
            currentDriverId = loc.driverid || d.vehicle_allocation?.driver_id || d.freight_charges?.driver_id || '';
            currentTripId = loc.trip_id || d.vehicle_allocation?.trip_id || d.freight_charges?.trip_id || d.order?.trip_id || '';

            if (!currentVehName && d.vehicle_allocation?.vehiclename && !nonVehicleNames.includes(d.vehicle_allocation.vehiclename.trim())) {
                currentVehName = d.vehicle_allocation.vehiclename.trim();
                isVehicleChosen = true;
            } else if (!currentVehName && currentVehNo) {
                currentVehName = currentVehNo;
                isVehicleChosen = true;
            }

            let vehicleBadge = '';
            if (isPickup) {
                vehicleBadge = '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1"><i class="fa fa-store me-1"></i>Self Pickup</span>';
            } else if (isBoxPacking) {
                vehicleBadge = `<span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-1"><i class="fa fa-box me-1"></i>${escapeHtml(loc.transporttype || 'Bus')}</span>`;
            } else {
                if (isVehicleChosen && currentVehName) {
                    vehicleBadge = `
                        <div class="d-flex flex-column align-items-center gap-1">
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" title="Selected Vehicle"><i class="fa fa-truck me-1"></i>${escapeHtml(currentVehName)}</span>
                            ${currentVehNo ? `<span class="badge bg-light text-dark border font-monospace" style="font-size:10px;">${escapeHtml(currentVehNo)}</span>` : ''}
                            ${currentTransporter ? `<div class="text-muted text-truncate" style="font-size:10px; max-width: 140px;" title="Transporter: ${escapeHtml(currentTransporter)}"><i class="fa fa-building text-primary me-1"></i>${escapeHtml(currentTransporter)}</div>` : ''}
                            ${currentDriverName ? `<div class="text-secondary fw-semibold text-truncate" style="font-size:10.5px; max-width: 140px;" title="Driver: ${escapeHtml(currentDriverName)} (${escapeHtml(currentDriverMobile)})"><i class="fa fa-user me-1 text-muted"></i>${escapeHtml(currentDriverName)}</div>` : ''}
                            ${currentTripId ? `<span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace" style="font-size:10px;" title="Trip ID"><i class="fa fa-route me-1"></i>${escapeHtml(currentTripId)}</span>` : ''}
                            <button type="button" class="btn btn-xs btn-outline-primary dinv-open-assign-vehicle-btn px-2 py-0.5 mt-0.5 shadow-2xs" data-group-index="${gIndex}" style="font-size: 10.5px;" title="Edit Vehicle, Transporter & Driver">
                                <i class="fa fa-edit me-1"></i> Edit
                            </button>
                        </div>
                    `;
                } else {
                    vehicleBadge = `
                        <div class="d-flex flex-column align-items-center gap-1">
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1" title="Vehicle not selected yet"><i class="fa fa-exclamation-triangle me-1"></i>Missing Vehicle</span>
                            <button type="button" class="btn btn-xs btn-outline-secondary dinv-open-assign-vehicle-btn px-2 py-0.5 mt-0.5 shadow-2xs" data-group-index="${gIndex}" style="font-size: 10.5px;" title="Assign Vehicle & Driver Directly">
                                <i class="fa fa-truck me-1"></i> Assign
                            </button>
                        </div>
                    `;
                }
            }

            let rowHtml = `
                <tr class="dinv-group-row" data-group-index="${gIndex}" data-totalqty="${g.totalAllocQty}" data-orderqty="${g.totalOrderQty}" data-graftedqty="${g.totalGraftedQty}" data-planttype="${plantType}" data-km="${km}" ${isCleared ? 'data-discount-cleared="true"' : ''}>
                    <!-- 1. CROP NAME / DESCRIPTION -->
                    <td class="align-middle" style="white-space: normal; word-break: break-word; min-width: 220px; max-width: 320px;">
                        <div class="d-flex flex-wrap gap-1 mb-1">
                            ${g.items.map(i => `
                                <div class="p-1 px-2 rounded border bg-light d-inline-flex align-items-center flex-wrap gap-1 shadow-xs me-1 mb-1" style="white-space: normal; word-break: break-word;">
                                    <span class="fw-bold text-dark" style="white-space: normal; word-break: break-word;">${escapeHtml(i.name)}</span>
                                    ${i.isGrafted 
                                        ? '<span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:0.65rem;"><i class="fa fa-seedling me-1"></i>Grafted</span>' 
                                        : '<span class="badge bg-warning-subtle text-dark border border-warning-subtle" style="font-size:0.65rem;"><i class="fa fa-leaf me-1"></i>Non-Grafted</span>'}
                                </div>
                            `).join('')}
                        </div>
                    </td>

                    <!-- 2. DELIVERY DATE -->
                    <td class="text-center align-middle" style="width: 140px; min-width: 130px;">
                        <div class="fw-bold text-primary dinv-row-date-text" style="font-size: 0.95rem;">${escapeHtml(g.deliveryDate)}</div>
                        <div class="mt-1">
                            <button type="button" class="btn btn-xs btn-outline-primary dinv-row-unify-date-btn px-2 py-0.5 rounded-pill shadow-2xs" data-date="${escapeHtml(g.deliveryDate)}" style="font-size: 10.5px;" title="Set this date for all products (Farmer agreed 1 dispatch date)">
                                <i class="fa fa-calendar-check me-0.5"></i> Set 1 Date
                            </button>
                        </div>
                    </td>

                    <!-- 3. DELIVERY LOCATION -->
                    <td class="align-middle" style="min-width: 240px;">
                        ${isPickup ? `
                            <div class="p-2 rounded-2 border border-secondary-subtle bg-secondary-subtle bg-opacity-10 shadow-2xs">
                                <div class="d-flex align-items-center gap-1.5 text-secondary fw-bold" style="font-size: 11.5px;">
                                    <i class="fa fa-user-check"></i>
                                    <span>1. Self Pickup By Farmer/Client</span>
                                </div>
                                <div class="text-muted small mt-1" style="font-size: 11px; line-height: 1.3;">
                                    <i class="fa fa-store text-secondary me-1"></i>Ex-Nursery / Farm Gate Pickup (Freight: ₹ 0.00)
                                </div>
                            </div>
                        ` : isBoxPacking ? `
                            <div class="p-2 rounded-2 border border-warning-subtle bg-warning-subtle bg-opacity-10 shadow-2xs">
                                <div class="d-flex align-items-center justify-content-between gap-1 mb-1">
                                    <div class="d-flex align-items-center gap-1.5 text-dark fw-bold" style="font-size: 11.5px;">
                                        <i class="fa fa-box text-warning"></i>
                                        <span>3. Box Packing (${escapeHtml(loc.transporttype || 'Bus')})</span>
                                    </div>
                                    <button type="button" class="btn btn-xs btn-outline-warning text-dark fw-bold dinv-open-boxpacking-modal-btn px-2 py-0.5 shadow-2xs" data-group-index="${gIndex}" style="font-size: 10.5px;">
                                        <i class="fa fa-cog me-1"></i> Configure
                                    </button>
                                </div>
                                <div class="small text-dark fw-semibold" style="font-size: 11px;">
                                    <b>Destination:</b> ${escapeHtml([loc.dest_city, loc.dest_district, loc.dest_state].filter(Boolean).join(', ') || 'Not Selected')}
                                </div>
                                <div class="small text-muted mt-0.5" style="font-size: 10.5px;">
                                    <b>Boxes:</b> ${g.totalAllocQty > 0 ? Math.ceil(g.totalAllocQty / (parseInt(loc.boxperpack)||1000)) : (loc.noofbox||1)} (${parseInt(loc.boxperpack)||1000} plants/box) @ ₹${(parseFloat(loc.chargesperbox)||70).toFixed(2)}/box
                                </div>
                                ${loc.remark ? `<div class="small text-secondary fst-italic mt-0.5" style="font-size: 10px;"><i class="fa fa-info-circle me-1 text-warning"></i>${escapeHtml(loc.remark)}</div>` : ''}
                            </div>
                        ` : isLocAvailable ? `
                            <div class="d-flex flex-column gap-1.5">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="fa fa-map-marker-alt text-danger mt-1"></i>
                                    <div>
                                        <div class="fw-bold text-dark dinv-group-city">${escapeHtml(loc.dest_city || loc.dest_district || 'Delivery Point')}</div>
                                        ${loc.dest_district && loc.dest_district !== loc.dest_city ? `<div class="small text-muted dinv-group-district">${escapeHtml(loc.dest_district)}</div>` : ''}
                                        ${loc.deliveryaddress ? `<div class="small text-secondary dinv-group-address" style="max-width: 240px; white-space: normal; word-break: break-word;">${escapeHtml(loc.deliveryaddress)}</div>` : ''}
                                        ${loc.dest_state ? `<div class="small text-muted dinv-group-state">${escapeHtml(loc.dest_state)} ${escapeHtml(loc.dest_pincode || '')}</div>` : ''}
                                    </div>
                                </div>
                                <div class="mt-1 pt-1 border-top border-light-subtle d-flex flex-wrap gap-1">
                                    <button type="button" class="btn btn-xs btn-outline-primary fw-bold dinv-open-map-modal-btn px-2 py-0.5 d-inline-flex align-items-center gap-1 shadow-2xs" data-group-index="${gIndex}" style="font-size: 11px;" title="1. Location Coordinate (Map / GPS)">
                                        <i class="fa fa-map-marker-alt text-primary"></i> 1. Location Coordinate
                                    </button>
                                    <button type="button" class="btn btn-xs btn-outline-secondary fw-bold dinv-open-manual-addr-modal-btn px-2 py-0.5 d-inline-flex align-items-center gap-1 shadow-2xs" data-group-index="${gIndex}" style="font-size: 11px;" title="2. Manual Address (State, District, Village, Distance)">
                                        <i class="fa fa-edit text-secondary"></i> 2. Manual Address
                                    </button>
                                </div>
                            </div>
                        ` : `
                            <div class="d-flex flex-column gap-1.5 p-2 rounded-2 border border-danger-subtle bg-danger-subtle bg-opacity-10 shadow-2xs">
                                <div class="d-flex align-items-center gap-1.5 text-danger fw-bold" style="font-size: 11.5px;">
                                    <i class="fa fa-exclamation-triangle"></i>
                                    <span>Missing Delivery Address</span>
                                </div>
                                <div class="text-danger small fst-italic" style="font-size: 11px; line-height: 1.3;">
                                    Missing delivery address. Please update first.
                                </div>
                                <div class="d-flex flex-column gap-1 mt-1">
                                    <button type="button" class="btn btn-xs btn-danger fw-bold dinv-open-map-modal-btn px-2 py-1 d-inline-flex align-items-center gap-1 shadow-xs text-start" data-group-index="${gIndex}" style="font-size: 11px;" title="1. Location Coordinate (Map / GPS)">
                                        <i class="fa fa-map-marker-alt"></i> 1. Location Coordinate
                                    </button>
                                    <button type="button" class="btn btn-xs btn-outline-danger fw-bold dinv-open-manual-addr-modal-btn px-2 py-1 d-inline-flex align-items-center gap-1 shadow-xs text-start bg-white" data-group-index="${gIndex}" style="font-size: 11px;" title="2. Manual Address (State, District, Village, Distance)">
                                        <i class="fa fa-edit"></i> 2. Manual Address
                                    </button>
                                </div>
                            </div>
                        `}
                    </td>

                    <!-- 4. Distance (KM) -->
                    <td class="text-center align-middle" style="width: 110px;">
                        <span class="fw-bold text-dark fs-5 dinv-row-km-text">${isPickup ? '0' : (isBoxPacking ? '--' : (km > 0 ? km : '--'))}</span><br>
                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">${isBoxPacking ? 'BOX' : 'KM'}</small>
                    </td>

                    <!-- 5. FREIGHT AMOUNT -->
                    <td class="align-middle" style="min-width: 140px; width: 150px;">
                        <div class="input-group input-group-sm shadow-2xs">
                            <span class="input-group-text bg-white border-end-0 fw-bold text-muted">Rs</span>
                            <input type="number" step="0.01" min="0" class="form-control dinv_f_amt_input border-start-0 fw-bold text-dark" data-group-index="${gIndex}" value="${baseFreight.toFixed(2)}" ${isPickup ? 'readonly' : ''} />
                        </div>
                    </td>

                    <!-- 6. DISCOUNT (% & AMOUNT) -->
                    <td class="align-middle" style="min-width: 220px; width: 240px;">
                        <div class="d-flex flex-column gap-1">
                            <div class="d-flex align-items-center gap-1.5">
                                <div class="input-group input-group-sm shadow-2xs" style="flex: 1 1 45%;">
                                    <input type="number" step="0.01" min="0" max="100" class="form-control dinv_f_disc_input text-center fw-bold bg-white" data-group-index="${gIndex}" value="${discPct}" ${isPickup ? 'readonly' : ''} placeholder="0" title="Discount Percentage (%)" />
                                    <span class="input-group-text bg-light border-start-0 text-muted px-1.5 fw-bold">%</span>
                                </div>
                                <div class="input-group input-group-sm shadow-2xs" style="flex: 1 1 55%;">
                                    <span class="input-group-text bg-light text-success border-end-0 px-1.5 fw-bold" style="font-size: 11px;">-₹</span>
                                    <input type="number" step="0.01" min="0" class="form-control dinv_f_disc_amt_input text-center fw-bold text-success bg-white border-start-0" data-group-index="${gIndex}" value="${discAmt.toFixed(2)}" ${isPickup ? 'readonly' : ''} placeholder="0.00" title="Discount Amount (₹)" />
                                </div>
                            </div>
                            <div class="dinv_f_discount_info small text-success mt-0.5" style="font-size: 0.72rem; ${discAmt > 0 ? '' : 'display:none;'}"></div>
                        </div>
                    </td>

                    <!-- 7. PAYABLE FREIGHT -->
                    <td class="align-middle" style="min-width: 140px; width: 150px;">
                        <div class="input-group input-group-sm shadow-2xs">
                            <span class="input-group-text bg-light border-end-0 fw-bold text-primary">Rs</span>
                            <input type="text" class="form-control dinv_f_payable_input bg-light fw-bold text-primary border-start-0 font-monospace" data-group-index="${gIndex}" readonly value="${payableFreight.toFixed(2)}" />
                        </div>
                    </td>

                    <!-- 8. VEHICLE -->
                    <td class="text-center align-middle" style="width: 140px; min-width: 140px;">
                        <div class="d-flex flex-column align-items-center gap-1.5">
                            ${isDelivered ? `
                                <button type="button" class="btn ${isVehicleChosen ? 'btn-primary' : 'btn-outline-danger'} btn-sm rounded-circle dinv-open-vehicle-calc-btn shadow-xs" data-group-index="${gIndex}" data-km="${km}" data-totalqty="${g.totalAllocQty}" title="${isVehicleChosen ? 'Choose vehicle again / Calculate freight' : 'Choose Vehicle'}">
                                    <i class="fa fa-calculator"></i>
                                </button>
                            ` : isBoxPacking ? `
                                <button type="button" class="btn btn-warning btn-sm rounded-circle dinv-open-boxpacking-modal-btn shadow-xs text-dark" data-group-index="${gIndex}" title="Configure Box Packing">
                                    <i class="fa fa-box-open"></i>
                                </button>
                            ` : ''}
                            <div class="dinv_f_vehicle_badge">
                                ${vehicleBadge}
                            </div>
                        </div>
                        <input type="hidden" class="dinv_f_vehicle_id" value="${currentVehicleId}" />
                        <input type="hidden" class="dinv_f_vehicle_info" value="${escapeHtml(currentVehName)}" />
                        <input type="hidden" class="dinv_f_vehicle_no" value="${escapeHtml(currentVehNo)}" />
                        <input type="hidden" class="dinv_f_transporter_name" value="${escapeHtml(currentTransporter)}" />
                        <input type="hidden" class="dinv_f_driver_id" value="${escapeHtml(currentDriverId)}" />
                        <input type="hidden" class="dinv_f_driver_name" value="${escapeHtml(currentDriverName)}" />
                        <input type="hidden" class="dinv_f_driver_mobile" value="${escapeHtml(currentDriverMobile)}" />
                        <input type="hidden" class="dinv_f_trip_id" value="${escapeHtml(currentTripId)}" />
                        <input type="hidden" class="dinv_f_distance_info" value="${km}" />
                        <input type="hidden" class="dinv_f_totaldistance_input" value="${km}" />
                        <input type="hidden" class="dinv_f_freedistance_input" value="0" />
                        <input type="hidden" class="dinv_f_payabledistance_input" value="${km}" />
                        <input type="hidden" class="dinv_f_narration_input" value="" />
                    </td>
                </tr>
            `;

            $tbody.append(rowHtml);
        });

        $("#dinv_tbl_base_freight_display").text(totalTableBaseFreight.toFixed(2));
        $("#dinv_tbl_payable_freight_display").text(totalTablePayableFreight.toFixed(2));
        $("#dinv_freight_charges").val(totalTablePayableFreight.toFixed(2));

        // Evaluate and render Freight Eligibility Rules banner
        renderDispatchFreightRules(true);
    }

    /**
     * Update table footers and synchronize grand total
     */
    function updateFreightTableFootersAndGrandTotal() {
        let totalBase = 0;
        let totalDisc = 0;
        let totalPayable = 0;
        $("#dinvFreightGroupTable tbody tr.dinv-group-row").each(function() {
            let base = parseFloat($(this).find(".dinv_f_amt_input").val()) || 0;
            let discA = parseFloat($(this).find(".dinv_f_disc_amt_input").val()) || 0;
            let payable = parseFloat($(this).find(".dinv_f_payable_input").val()) || 0;
            totalBase += base;
            totalDisc += discA;
            totalPayable += payable;
        });
        $("#dinv_tbl_base_freight_display").text(totalBase.toFixed(2));
        $("#dinv_tbl_disc_freight_display").text(totalDisc > 0 ? `- ₹ ${totalDisc.toFixed(2)}` : '0.00');
        $("#dinv_tbl_payable_freight_display").text(totalPayable.toFixed(2));
        $("#dinv_freight_charges").val(totalPayable.toFixed(2));
        recalculateGrandTotalOnly();
    }

    /**
     * Render the Freight Eligibility Rules banner dynamically with full-width row layout and Apply/Clear buttons
     */
    window.renderDispatchFreightRules = function(autoApply = true) {
        const $notif = $("#dinvFreeFreightNotification");
        if (!currentInvoiceData) {
            $notif.empty().hide();
            return;
        }

        let d = currentInvoiceData;
        let dl = d.delivery_location || {};
        let pickupType = dl.pickuptype || 'delivered';

        if (pickupType !== 'delivered') {
            $notif.empty().hide();
            return;
        }

        const customerType = d.customer_type || 'Farmer';
        const $row = $("#dinvFreightGroupTable tbody tr.dinv-group-row");
        if ($row.length === 0) {
            $notif.empty().hide();
            return;
        }

        const qty = parseFloat($row.data("totalqty")) || 0;
        const dist = parseFloat($row.data("km")) || parseFloat($row.find(".dinv_f_distance_info").val()) || 0;
        const plantType = $row.data("planttype") || 'Grafted';
        const discInput = parseFloat($row.find(".dinv_f_disc_input").val()) || 0;
        const amt = parseFloat($row.find(".dinv_f_amt_input").val()) || 0;
        const isCleared = $row.attr("data-discount-cleared") === "true";

        if (qty <= 0 && dist <= 0 && amt <= 0) {
            $notif.empty().hide();
            return;
        }

        let ajaxUrl = "../freight_discount_module/modal/freight_discount_modal.php?action=get_active_rules";

        $.ajax({
            url: ajaxUrl,
            type: "POST",
            data: {
                customer_type: customerType,
                plant_type: 'All'
            },
            dataType: "JSON",
            success: function(res) {
                if (!res || !res.status || !Array.isArray(res.data) || res.data.length === 0) {
                    $notif.empty().hide();
                    return;
                }

                const rules = res.data;
                window.currentDispatchFreightRules = rules;
                let anyEligible = false;
                let isAnyApplied = (discInput > 0);
                let matchedRulesToAutoApply = [];
                let rulesHtmlArray = [];

                rules.forEach(function(rule) {
                    const minQty = parseInt(rule.min_order_qty) || 0;
                    const maxQty = parseInt(rule.max_order_qty) || 0;
                    const minDist = parseFloat(rule.min_distance_km) || 0;
                    const maxDist = parseFloat(rule.max_distance_km) || 0;
                    const discVal = parseFloat(rule.discount_value) || 0;
                    const discType = rule.discount_type;

                    let qtyStr = '';
                    if (minQty > 0 && maxQty > 0) {
                        qtyStr = `Order Qty &ge; <strong>${minQty.toLocaleString()}</strong> && &le; <strong>${maxQty.toLocaleString()}</strong>`;
                    } else if (minQty > 0) {
                        qtyStr = `Order Qty &ge; <strong>${minQty.toLocaleString()}</strong>`;
                    } else if (maxQty > 0) {
                        qtyStr = `Order Qty &le; <strong>${maxQty.toLocaleString()}</strong>`;
                    } else {
                        qtyStr = `Any Order Qty`;
                    }

                    let distStr = '';
                    if (minDist > 0 && maxDist > 0) {
                        distStr = `Distance &ge; <strong>${minDist} KM</strong> && &le; <strong>${maxDist} KM</strong>`;
                    } else if (minDist > 0) {
                        distStr = `Distance &le; <strong>${minDist} KM</strong>`;
                    } else if (maxDist > 0) {
                        distStr = `Distance &le; <strong>${maxDist} KM</strong>`;
                    } else {
                        distStr = `Any Distance`;
                    }

                    let benefitBadge = '';
                    let benefitText = '';
                    if (discType === 'Percentage') {
                        if (discVal >= 100) {
                            benefitBadge = '<span class="badge bg-success text-white px-2 py-1 fs-7"><i class="fa fa-gift me-1"></i>100% Free Freight</span>';
                            benefitText = '100% Free Freight';
                        } else {
                            benefitBadge = `<span class="badge bg-info text-dark px-2 py-1 fs-7"><i class="fa fa-percent me-1"></i>${discVal}% Off</span>`;
                            benefitText = `${discVal}% Off`;
                        }
                    } else if (discType === 'Fixed_Amount') {
                        benefitBadge = `<span class="badge bg-primary text-white px-2 py-1 fs-7">Rs ${discVal} Off</span>`;
                        benefitText = `Rs ${discVal} Off`;
                    } else if (discType === 'Per_Plant') {
                        benefitBadge = `<span class="badge bg-primary text-white px-2 py-1 fs-7">Rs ${discVal}/Plant Off</span>`;
                        benefitText = `Rs ${discVal}/Plant Off`;
                    } else if (discType === 'Per_KM') {
                        benefitBadge = `<span class="badge bg-primary text-white px-2 py-1 fs-7">Rs ${discVal}/KM Off</span>`;
                        benefitText = `Rs ${discVal}/KM Off`;
                    }

                    let plantBadge = '';
                    if (rule.plant_type === 'Grafted') {
                        plantBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle me-1"><i class="fa fa-seedling me-1"></i>Grafted</span>';
                    } else if (rule.plant_type === 'Nongrafted') {
                        plantBadge = '<span class="badge bg-warning-subtle text-dark border border-warning-subtle me-1"><i class="fa fa-leaf me-1"></i>Non-Grafted</span>';
                    } else {
                        plantBadge = '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle me-1">All Crops</span>';
                    }

                    let custBadge = '';
                    if (rule.customer_type === 'Dealer') {
                        custBadge = '<span class="badge bg-info-subtle text-info border border-info-subtle me-1"><i class="fa fa-briefcase me-1"></i>Dealer</span>';
                    } else if (rule.customer_type === 'Farmer') {
                        custBadge = '<span class="badge bg-light text-dark border me-1"><i class="fa fa-user me-1"></i>Farmer</span>';
                    } else {
                        custBadge = '<span class="badge bg-light text-muted border me-1">All Customers</span>';
                    }

                    const custOk = (rule.customer_type === 'All' || rule.customer_type === customerType);
                    const plantOk = (rule.plant_type === 'Both' || rule.plant_type === 'All' || rule.plant_type.toLowerCase() === plantType.toLowerCase());
                    const qtyOk = (minQty === 0 || qty >= minQty) && (maxQty === 0 || qty <= maxQty);
                    const distOk = (maxDist === 0 || dist <= maxDist);

                    let isMatched = false;
                    if (custOk && plantOk && qtyOk && distOk && qty > 0 && dist > 0) {
                        isMatched = true;
                        anyEligible = true;
                        if (!isCleared && amt > 0 && discInput === 0) {
                            matchedRulesToAutoApply.push(rule.id);
                        }
                    }

                    if (!isMatched) return;

                    const cardClass = 'border-success bg-success-subtle shadow-sm';

                    rulesHtmlArray.push(`
                        <div class="col-12 mb-2">
                            <div class="card ${cardClass} rounded-3 border p-2 p-md-3" style="transition: all 0.2s ease;">
                                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2">
                                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center gap-2 gap-md-4 flex-grow-1">
                                        <div style="min-width: 290px;">
                                            <div class="fw-bold text-success fs-6 mb-1">
                                                <i class="fa fa-file-invoice text-success me-1"></i>${escapeHtml(rule.plan_name)}
                                            </div>
                                            <div class="d-flex flex-wrap gap-1 align-items-center">
                                                <span class="badge bg-light text-muted border" style="font-size:0.72rem;">${escapeHtml(rule.plan_code)}</span>
                                                ${custBadge}
                                                ${plantBadge}
                                                <span class="badge bg-success text-white"><i class="fa fa-check-circle me-1"></i>Eligible & Matched</span>
                                            </div>
                                        </div>
                                        <div class="border-start-md ps-md-3 text-secondary small flex-grow-1" style="font-size: 0.84rem; line-height: 1.5;">
                                            <div class="d-flex flex-wrap gap-3 align-items-center">
                                                <div><i class="fa fa-road text-muted me-1"></i>${distStr}</div>
                                                <div><i class="fa fa-boxes text-muted me-1"></i>${qtyStr}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between justify-content-lg-end gap-2 mt-2 mt-lg-0 pt-2 pt-lg-0 border-top border-top-lg-0">
                                        <div class="me-2">${benefitBadge}</div>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-success px-3 shadow-sm" onclick="applyDispatchPlanDiscount(${rule.id})" title="Apply ${benefitText}">
                                                <i class="fa fa-check-circle me-1"></i>Apply
                                            </button>
                                            <button type="button" class="btn btn-outline-danger px-3" onclick="clearDispatchPlanDiscount(${rule.id})" title="Clear discount">
                                                <i class="fa fa-times me-1"></i>Clear
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `);
                });

                if (autoApply && matchedRulesToAutoApply.length > 0) {
                    applyDispatchPlanDiscount(matchedRulesToAutoApply[0], true);
                    return;
                }

                if (!anyEligible || rulesHtmlArray.length === 0) {
                    $notif.empty().hide();
                    return;
                }

                let rulesHtml = rulesHtmlArray.join('');
                let bannerHtml = `
                    <div class="alert alert-success border-success shadow-sm rounded-3 mb-3 p-3">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2 pb-2 border-bottom">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa fa-check-circle text-success fs-5"></i>
                                <strong class="fs-6 text-success">Freight Eligibility Rules</strong>
                                <span class="badge bg-white text-secondary border ms-1">${customerType} Active Plans</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                ${isAnyApplied ? '<span class="badge bg-success text-white px-3 py-2 fs-7 shadow-sm"><i class="fa fa-gift me-1"></i>Free Freight / Discount Applied!</span>' : ''}
                                <button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2" onclick="clearDispatchPlanDiscount(0)" title="Clear all applied discounts">
                                    <i class="fa fa-undo me-1"></i>Clear All Discounts
                                </button>
                            </div>
                        </div>
                        <div class="row g-2">
                            ${rulesHtml}
                        </div>
                    </div>
                `;

                $notif.html(bannerHtml).slideDown(200);
            },
            error: function() {
                $notif.empty().hide();
            }
        });
    };

    window.applyDispatchPlanDiscount = function(planId, isAuto = false) {
        const rules = window.currentDispatchFreightRules || [];
        const plan = rules.find(r => String(r.id) === String(planId));
        if (!plan) return;

        const $row = $("#dinvFreightGroupTable tbody tr.dinv-group-row").first();
        if ($row.length === 0) return;

        const gIdx = parseInt($row.data("group-index") || 0);
        const rowAmt = parseFloat($row.find(".dinv_f_amt_input").val()) || 0;
        const rowDist = parseFloat($row.data("km")) || parseFloat($row.find(".dinv_f_distance_info").val()) || 0;
        const rowQty = parseFloat($row.data("totalqty")) || 0;
        const discType = plan.discount_type;
        const discVal = parseFloat(plan.discount_value) || 0;
        const maxDiscAmt = parseFloat(plan.max_discount_amount) || 0;
        const minDist = parseFloat(plan.min_distance_km) || 0;

        $row.removeAttr("data-discount-cleared");
        if (currentInvoiceData) currentInvoiceData._discount_cleared = false;

        let discountAmt = 0;
        let discPct = 0;
        let freeDist = 0;
        let payableDist = rowDist;

        if (discType === 'Percentage') {
            if (discVal >= 100) {
                if (minDist > 0 && rowDist > minDist) {
                    freeDist = minDist;
                    payableDist = Math.max(0, rowDist - minDist);
                    const freeRatio = minDist / rowDist;
                    discountAmt = rowAmt * freeRatio;
                    discPct = freeRatio * 100;
                } else {
                    freeDist = rowDist;
                    payableDist = 0;
                    discPct = 100;
                    discountAmt = rowAmt;
                }
            } else {
                freeDist = (rowDist * discVal) / 100.0;
                payableDist = Math.max(0, rowDist - freeDist);
                discPct = discVal;
                discountAmt = (rowAmt * discVal) / 100.0;
            }
        } else if (discType === 'Fixed_Amount') {
            discountAmt = Math.min(discVal, rowAmt);
            discPct = (rowAmt > 0) ? (discountAmt / rowAmt) * 100 : 0;
            freeDist = (rowAmt > 0 && rowDist > 0) ? (discountAmt / rowAmt) * rowDist : 0;
            payableDist = Math.max(0, rowDist - freeDist);
        } else if (discType === 'Per_Plant') {
            discountAmt = Math.min(rowQty * discVal, rowAmt);
            discPct = (rowAmt > 0) ? (discountAmt / rowAmt) * 100 : 0;
            freeDist = (rowAmt > 0 && rowDist > 0) ? (discountAmt / rowAmt) * rowDist : 0;
            payableDist = Math.max(0, rowDist - freeDist);
        } else if (discType === 'Per_KM') {
            discountAmt = Math.min(rowDist * discVal, rowAmt);
            discPct = (rowAmt > 0) ? (discountAmt / rowAmt) * 100 : 0;
            freeDist = (rowAmt > 0 && rowDist > 0) ? (discountAmt / rowAmt) * rowDist : 0;
            payableDist = Math.max(0, rowDist - freeDist);
        }

        if (maxDiscAmt > 0 && discountAmt > maxDiscAmt) {
            discountAmt = maxDiscAmt;
            discPct = (rowAmt > 0) ? (discountAmt / rowAmt) * 100 : 0;
        }

        discPct = Math.min(100, Math.max(0, Math.round(discPct * 100) / 100));
        const payable = Math.max(0, rowAmt - discountAmt);

        $row.find(".dinv_f_disc_input").val(discPct);
        $row.find(".dinv_f_disc_amt_input").val(discountAmt.toFixed(2));
        $row.find(".dinv_f_payable_input").val(payable.toFixed(2));

        if (window._currentFreightGroups && window._currentFreightGroups[gIdx]) {
            window._currentFreightGroups[gIdx].discPct = discPct;
            window._currentFreightGroups[gIdx].discAmt = discountAmt;
            window._currentFreightGroups[gIdx].payableFreight = payable;
        }

        let tagText = escapeHtml(plan.plan_name);
        if (discVal >= 100 && minDist > 0 && rowDist > minDist) {
            tagText += ` (Free up to ${minDist} KM, -Rs ${discountAmt.toFixed(2)})`;
        } else {
            tagText += ` (-Rs ${discountAmt.toFixed(2)})`;
        }

        $row.find(".dinv_f_totaldistance_input").val(rowDist);
        $row.find(".dinv_f_freedistance_input").val(freeDist);
        $row.find(".dinv_f_payabledistance_input").val(payableDist);
        $row.find(".dinv_f_narration_input").val(tagText);

        const infoHtml = `<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fa fa-tag me-1"></i>${tagText}</span> <button type="button" class="btn btn-link btn-sm text-danger p-0 ms-1 text-decoration-none" onclick="clearDispatchPlanDiscount(${plan.id})" title="Clear Discount"><i class="fa fa-times-circle"></i> Clear</button>`;
        $row.find(".dinv_f_discount_info").html(infoHtml).show();

        updateFreightTableFootersAndGrandTotal();
        updateDispatchDiscountRemarks();
        renderDispatchFreightRules(false);

        if (!isAuto && typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: `Applied "${plan.plan_name}"!`,
                showConfirmButton: false,
                timer: 2500
            });
        }
    };

    window.clearDispatchPlanDiscount = function(planId) {
        const $row = $("#dinvFreightGroupTable tbody tr.dinv-group-row").first();
        if ($row.length === 0) return;

        const gIdx = parseInt($row.data("group-index") || 0);
        const rowAmt = parseFloat($row.find(".dinv_f_amt_input").val()) || 0;
        const rowDist = parseFloat($row.data("km")) || 0;

        $row.attr("data-discount-cleared", "true");
        if (currentInvoiceData) currentInvoiceData._discount_cleared = true;

        $row.find(".dinv_f_disc_input").val("0");
        $row.find(".dinv_f_disc_amt_input").val("0.00");
        $row.find(".dinv_f_payable_input").val(rowAmt.toFixed(2));

        if (window._currentFreightGroups && window._currentFreightGroups[gIdx]) {
            window._currentFreightGroups[gIdx].discPct = 0;
            window._currentFreightGroups[gIdx].discAmt = 0;
            window._currentFreightGroups[gIdx].payableFreight = rowAmt;
        }

        $row.find(".dinv_f_totaldistance_input").val(rowDist);
        $row.find(".dinv_f_freedistance_input").val(0);
        $row.find(".dinv_f_payabledistance_input").val(rowDist);
        $row.find(".dinv_f_narration_input").val("");
        $row.find(".dinv_f_discount_info").hide().empty();

        updateFreightTableFootersAndGrandTotal();
        updateDispatchDiscountRemarks();
        renderDispatchFreightRules(false);
    };

    function updateDispatchDiscountRemarks() {
        const $row = $("#dinvFreightGroupTable tbody tr.dinv-group-row").first();
        if ($row.length === 0) return;
        const narration = $row.find(".dinv_f_narration_input").val();
        if (narration) {
            $("#dinv_remarks").val(`Freight Discount: Row 1: ${narration}`);
        } else {
            $("#dinv_remarks").val('');
        }
    }

    // Dynamic change of Freight Amount in the table row
    $(document).on("input change", ".dinv_f_amt_input", function() {
        const $row = $(this).closest("tr");
        const gIdx = parseInt($(this).data("group-index") || 0);
        const amt = parseFloat($(this).val()) || 0;
        let discPct = parseFloat($row.find(".dinv_f_disc_input").val()) || 0;
        let discAmt = Math.round((amt * (discPct / 100)) * 100) / 100;
        const payable = Math.max(0, amt - discAmt);

        $row.find(".dinv_f_disc_amt_input").val(discAmt.toFixed(2));
        $row.find(".dinv_f_payable_input").val(payable.toFixed(2));

        if (window._currentFreightGroups && window._currentFreightGroups[gIdx]) {
            window._currentFreightGroups[gIdx].baseFreight = amt;
            window._currentFreightGroups[gIdx].discPct = discPct;
            window._currentFreightGroups[gIdx].discAmt = discAmt;
            window._currentFreightGroups[gIdx].payableFreight = payable;
        }

        if (gIdx === 0 && currentInvoiceData) {
            currentInvoiceData.original_base_freight = amt;
            currentInvoiceData.original_payable_freight = payable;
        }

        updateFreightTableFootersAndGrandTotal();
        renderDispatchFreightRules(false);
    });

    // Dynamic change of Discount (%) in the table row
    $(document).on("input change", ".dinv_f_disc_input", function() {
        const $row = $(this).closest("tr");
        const gIdx = parseInt($(this).data("group-index") || 0);
        const amt = parseFloat($row.find(".dinv_f_amt_input").val()) || 0;
        let discPct = parseFloat($(this).val()) || 0;
        discPct = Math.min(100, Math.max(0, discPct));

        let discAmt = Math.round((amt * (discPct / 100)) * 100) / 100;
        const payable = Math.max(0, amt - discAmt);

        $row.find(".dinv_f_disc_amt_input").val(discAmt.toFixed(2));
        $row.find(".dinv_f_payable_input").val(payable.toFixed(2));

        if (discPct === 0) {
            $row.attr("data-discount-cleared", "true");
            $row.find(".dinv_f_discount_info").hide().empty();
            $row.find(".dinv_f_narration_input").val("");
            updateDispatchDiscountRemarks();
        } else {
            $row.removeAttr("data-discount-cleared");
            if (currentInvoiceData) currentInvoiceData._discount_cleared = false;
            let tagText = `Manual Disc (${discPct}%, -Rs ${discAmt.toFixed(2)})`;
            $row.find(".dinv_f_narration_input").val(tagText);
            $row.find(".dinv_f_discount_info").html(`<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fa fa-percent me-1"></i>${discPct}% (-₹${discAmt.toFixed(2)})</span>`).show();
            updateDispatchDiscountRemarks();
        }

        if (window._currentFreightGroups && window._currentFreightGroups[gIdx]) {
            window._currentFreightGroups[gIdx].discPct = discPct;
            window._currentFreightGroups[gIdx].discAmt = discAmt;
            window._currentFreightGroups[gIdx].payableFreight = payable;
        }

        if (gIdx === 0 && currentInvoiceData) {
            currentInvoiceData.original_discount_percent = discPct;
            currentInvoiceData.original_payable_freight = payable;
        }

        updateFreightTableFootersAndGrandTotal();
    });

    // Dynamic change of Discount Amount (₹) in the table row
    $(document).on("input change", ".dinv_f_disc_amt_input", function() {
        const $row = $(this).closest("tr");
        const gIdx = parseInt($(this).data("group-index") || 0);
        const amt = parseFloat($row.find(".dinv_f_amt_input").val()) || 0;
        let discAmt = parseFloat($(this).val()) || 0;
        discAmt = Math.max(0, Math.min(amt, discAmt));

        let discPct = (amt > 0) ? Math.round(((discAmt / amt) * 100) * 100) / 100 : 0;
        const payable = Math.max(0, amt - discAmt);

        $row.find(".dinv_f_disc_input").val(discPct);
        $row.find(".dinv_f_payable_input").val(payable.toFixed(2));

        if (discAmt === 0) {
            $row.attr("data-discount-cleared", "true");
            $row.find(".dinv_f_discount_info").hide().empty();
            $row.find(".dinv_f_narration_input").val("");
            updateDispatchDiscountRemarks();
        } else {
            $row.removeAttr("data-discount-cleared");
            if (currentInvoiceData) currentInvoiceData._discount_cleared = false;
            let tagText = `Manual Disc (-Rs ${discAmt.toFixed(2)}, ${discPct}%)`;
            $row.find(".dinv_f_narration_input").val(tagText);
            $row.find(".dinv_f_discount_info").html(`<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fa fa-tag me-1"></i>-₹${discAmt.toFixed(2)} (${discPct}%)</span>`).show();
            updateDispatchDiscountRemarks();
        }

        if (window._currentFreightGroups && window._currentFreightGroups[gIdx]) {
            window._currentFreightGroups[gIdx].discPct = discPct;
            window._currentFreightGroups[gIdx].discAmt = discAmt;
            window._currentFreightGroups[gIdx].payableFreight = payable;
        }

        if (gIdx === 0 && currentInvoiceData) {
            currentInvoiceData.original_discount_percent = discPct;
            currentInvoiceData.original_payable_freight = payable;
        }

        updateFreightTableFootersAndGrandTotal();
    });

    // -------------------------------------------------------------
    // Vehicle, Transporter & Driver Master Data & Capacity Check
    // -------------------------------------------------------------
    let cachedVehicleDriverMasterData = null;

    function loadVehicleDriverMasterData(callback) {
        let projId = currentInvoiceData?.order?.projectid || $("#dinv_projectid").val() || CURRENT_APP_PROJECTID;
        let delDate = $("#dinv_invoicedate").val() || (currentInvoiceData?.order?.deliverydate || '');

        $.ajax({
            url: "modal/finaldispatch_modal.php?action=GetVehicleAndDriverMasterData",
            type: "POST",
            data: {
                projectid: projId,
                deliverydate: delDate
            },
            dataType: "json",
            success: function(res) {
                if (res && res.status === "success") {
                    cachedVehicleDriverMasterData = res;
                    if (typeof callback === "function") callback(res);
                } else {
                    if (typeof callback === "function") callback(null);
                }
            },
            error: function() {
                if (typeof callback === "function") callback(null);
            }
        });
    }

    function checkLiveVehicleTripCapacity() {
        let projId = $("#vmodal_projectid").val() || CURRENT_APP_PROJECTID;
        let vehId = parseInt($("#vmodal_vehicle_select").val()) || 0;
        let vehNo = $("#vmodal_vehicleno").val()?.trim() || '';
        let vehName = $("#vmodal_vehiclename").val()?.trim() || '';
        let delDate = $("#vmodal_deliverydate").val() || $("#dinv_invoicedate").val() || '';
        let orderPlants = parseInt($("#vmodal_order_plants").val()) || 0;

        if (!vehId && !vehNo && !vehName) {
            $("#vmodal_cap_total").text("0 Plants");
            $("#vmodal_cap_active").text("0 Plants");
            $("#vmodal_cap_this_order").text(`${orderPlants.toLocaleString()} Plants`);
            $("#vmodal_cap_remaining").text("0 Plants");
            $("#vmodal_capacity_bar").css("width", "0%").removeClass("bg-danger bg-warning").addClass("bg-success");
            $("#vmodal_progress_percent").text("0%");
            $("#vmodal_trip_notice_text").text("Select a vehicle to verify capacity and trip schedule.");
            return;
        }

        $.ajax({
            url: "modal/finaldispatch_modal.php?action=CheckVehicleTripCapacity",
            type: "POST",
            data: {
                projectid: projId,
                vehicleid: vehId,
                vehicleno: vehNo,
                vehiclename: vehName,
                deliverydate: delDate,
                order_plants: orderPlants
            },
            dataType: "json",
            success: function(res) {
                if (res && res.status === "success" && res.data) {
                    let d = res.data;
                    let cap = d.plant_capacity || 0;
                    let activeLoad = d.current_trip_plants || 0;
                    let newOrder = d.new_order_plants || orderPlants;
                    let totalProjected = d.total_projected_plants || (activeLoad + newOrder);
                    let remaining = d.remaining_capacity || 0;
                    let tripId = d.trip_id || '';
                    let isNew = d.is_new_trip;
                    let locCount = d.location_count || 0;

                    $("#vmodal_trip_id").val(tripId);
                    $("#vmodal_trip_id_text").text(tripId);
                    $("#vmodal_trip_id_header_badge").text(`Trip: ${tripId}`);

                    $("#vmodal_cap_total").text(cap > 0 ? `${cap.toLocaleString()} Plants` : 'Uncapped');
                    $("#vmodal_cap_active").text(`${activeLoad.toLocaleString()} Plants`);
                    $("#vmodal_cap_this_order").text(`${newOrder.toLocaleString()} Plants`);
                    $("#vmodal_cap_remaining").text(cap > 0 ? `${remaining.toLocaleString()} Plants` : 'Available');

                    let pct = (cap > 0) ? Math.min(100, Math.round((totalProjected / cap) * 100)) : 0;
                    $("#vmodal_progress_percent").text(`${pct}%`);
                    $("#vmodal_capacity_bar").css("width", `${pct}%`);

                    if (pct > 100 || d.capacity_exceeded) {
                        $("#vmodal_capacity_bar").removeClass("bg-success bg-warning").addClass("bg-danger");
                        $("#vmodal_cap_remaining_box").removeClass("bg-success-subtle border-success-subtle text-success").addClass("bg-danger-subtle border-danger-subtle text-danger");
                        $("#vmodal_trip_status_badge").attr("class", "badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1 fw-bold").html('<i class="fa fa-exclamation-circle me-1"></i> New Trip Generated (Capacity Exceeded)');
                        $("#vmodal_trip_notice").removeClass("alert-info alert-success").addClass("alert-warning");
                        $("#vmodal_trip_notice_text").html(`<b>Capacity Exceeded:</b> Total projected plants (${totalProjected.toLocaleString()}) exceed vehicle capacity (${cap.toLocaleString()}). A separate new Trip ID (<b>${tripId}</b>) is allocated for this order on ${escapeHtml(delDate)}.`);
                    } else if (activeLoad > 0) {
                        $("#vmodal_capacity_bar").removeClass("bg-danger").addClass(pct > 80 ? "bg-warning" : "bg-success");
                        $("#vmodal_cap_remaining_box").removeClass("bg-danger-subtle border-danger-subtle text-danger").addClass("bg-success-subtle border-success-subtle text-success");
                        $("#vmodal_trip_status_badge").attr("class", "badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 fw-bold").html('<i class="fa fa-route me-1"></i> Combined Multi-Location Trip');
                        $("#vmodal_trip_notice").removeClass("alert-warning alert-danger").addClass("alert-success");
                        $("#vmodal_trip_notice_text").html(`<b>Multi-Location Route:</b> Trip <b>${tripId}</b> is already delivering to <b>${locCount} location(s)</b> (${activeLoad.toLocaleString()} plants) on ${escapeHtml(delDate)}. This order fits within capacity (${remaining.toLocaleString()} plants remaining) and is joined to the same trip.`);
                    } else {
                        $("#vmodal_capacity_bar").removeClass("bg-danger bg-warning").addClass("bg-success");
                        $("#vmodal_cap_remaining_box").removeClass("bg-danger-subtle border-danger-subtle text-danger").addClass("bg-success-subtle border-success-subtle text-success");
                        $("#vmodal_trip_status_badge").attr("class", "badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 fw-bold").html('<i class="fa fa-check-circle me-1"></i> Single Trip Ready');
                        $("#vmodal_trip_notice").removeClass("alert-warning alert-danger").addClass("alert-info");
                        $("#vmodal_trip_notice_text").html(`Trip <b>${tripId}</b> initiated for ${escapeHtml(delDate)}. Capacity: <b>${cap > 0 ? cap.toLocaleString() + ' plants' : 'N/A'}</b>.`);
                    }

                    // If existing driver in active trip and inputs are empty, pre-fill
                    if (d.existing_driver_name && !$("#vmodal_driver_name").val()) {
                        $("#vmodal_driver_name").val(d.existing_driver_name);
                    }
                    if (d.existing_driver_mobile && !$("#vmodal_driver_mobile").val()) {
                        $("#vmodal_driver_mobile").val(d.existing_driver_mobile);
                    }
                    if (d.existing_driver_id && !$("#vmodal_driver_id").val()) {
                        $("#vmodal_driver_id").val(d.existing_driver_id);
                    }
                    if (d.vehicle_owner && !$("#vmodal_transporter_name").val()) {
                        $("#vmodal_transporter_name").val(d.vehicle_owner);
                    }
                }
            }
        });
    }

    function openAssignVehicleDriverModal(gIdx, initialData) {
        gIdx = parseInt(gIdx) || 0;
        let group = (window._currentFreightGroups && window._currentFreightGroups[gIdx]) ? window._currentFreightGroups[gIdx] : null;
        let loc = group ? group.location : (currentInvoiceData?.delivery_location || {});
        let psono = $("#dinv_psono").val() || (currentInvoiceData?.order?.psono || '');
        let clientId = $("#dinv_clientid").val() || (currentInvoiceData?.client_info?.client_id || '');
        let projId = currentInvoiceData?.order?.projectid || $("#dinv_projectid").val() || CURRENT_APP_PROJECTID;
        let delDate = group?.deliveryDate || $("#dinv_invoicedate").val() || currentInvoiceData?.order?.deliverydate || '';
        let totalQty = group ? group.totalAllocQty : parseInt($("#dinv_sum_alloc_qty").text() || 0);
        if (totalQty <= 0) totalQty = parseInt(currentInvoiceData?.total_original_order_qty || 0);
        let curKm = parseFloat(group?.location?.distance || group?.location?.totaldistance || currentInvoiceData?.delivery_location?.distance || 0);
        let baseFreight = parseFloat(group?.baseFreight || currentInvoiceData?.original_base_freight || 0);

        let initVehName = initialData?.vehiclename || group?.location?.vehiclename || loc.vehiclename || loc.transporttype || currentInvoiceData?.freight_charges?.vehiclename || '';
        let initVehNo = initialData?.vehicleno || group?.location?.vehicleno || loc.vehicleno || currentInvoiceData?.freight_charges?.vehicle_no || '';
        let initTransporter = initialData?.transporter_name || group?.location?.vehicleownername || loc.vehicleownername || currentInvoiceData?.freight_charges?.vehicleownername || '';
        let initDriverName = initialData?.driver_name || group?.location?.drivername || loc.drivername || currentInvoiceData?.freight_charges?.driver_name || '';
        let initDriverMobile = initialData?.driver_mobile || group?.location?.drivercontactno || loc.drivercontactno || currentInvoiceData?.freight_charges?.driver_mobile || '';
        let initDriverId = initialData?.driver_id || group?.location?.driverid || loc.driverid || currentInvoiceData?.freight_charges?.driver_id || '';
        let initTripId = initialData?.trip_id || group?.location?.trip_id || loc.trip_id || currentInvoiceData?.freight_charges?.trip_id || '';

        $("#vmodal_group_index").val(gIdx);
        $("#vmodal_psono").val(psono);
        $("#vmodal_clientid").val(clientId);
        $("#vmodal_projectid").val(projId);
        $("#vmodal_deliverydate").val(delDate);
        $("#vmodal_order_plants").val(totalQty);
        $("#vmodal_distance").val(curKm);
        $("#vmodal_freightamt").val(baseFreight);

        $("#vmodal_vehiclename").val(initVehName);
        $("#vmodal_vehicleno").val(initVehNo);
        $("#vmodal_transporter_name").val(initTransporter);
        $("#vmodal_driver_name").val(initDriverName);
        $("#vmodal_driver_mobile").val(initDriverMobile);
        $("#vmodal_driver_id").val(initDriverId);
        $("#vmodal_trip_id").val(initTripId);
        $("#vmodal_trip_id_text").text(initTripId || 'TRIP-AUTO');
        $("#vmodal_trip_id_header_badge").text(initTripId ? `Trip: ${initTripId}` : 'Trip: Auto-Assign');

        loadVehicleDriverMasterData(function(data) {
            if (!data) return;
            
            // Populate Vehicle Dropdown
            let $vSel = $("#vmodal_vehicle_select");
            $vSel.empty().append('<option value="">-- Choose Vehicle from Master --</option>');
            let matchedVehId = 0;
            let selectedVehOwner = '';

            (data.vehicles || []).forEach(function(v) {
                let isSelected = false;
                if (initVehNo && v.vehicleno && v.vehicleno.toLowerCase() === initVehNo.toLowerCase()) {
                    isSelected = true;
                    matchedVehId = v.vehicleid;
                    selectedVehOwner = v.vehicleownername || '';
                } else if (!matchedVehId && initVehName && v.vehiclename && v.vehiclename.toLowerCase() === initVehName.toLowerCase()) {
                    isSelected = true;
                    matchedVehId = v.vehicleid;
                    selectedVehOwner = v.vehicleownername || '';
                }
                $vSel.append(`<option value="${v.vehicleid}" data-vehiclename="${escapeHtml(v.vehiclename)}" data-vehicleno="${escapeHtml(v.vehicleno)}" data-owner="${escapeHtml(v.vehicleownername||'')}" data-plantcap="${v.plantscapacity||0}" data-traycap="${v.traycapacity||0}" ${isSelected ? 'selected' : ''}>${escapeHtml(v.vehiclename)} (${escapeHtml(v.vehicleno || 'No Plate')}) - Cap: ${parseInt(v.plantscapacity||0).toLocaleString()} plants</option>`);
            });
            $vSel.append('<option value="custom">-- Custom / Other Vehicle --</option>');

            // Populate Transporter Dropdown
            let $trSel = $("#vmodal_transporter_select");
            $trSel.empty().append('<option value="">-- Choose Transporter or Type New Below --</option>');
            $trSel.append('<option value="new">+ Add New Transporter Details</option>');
            let curTransporter = initTransporter || selectedVehOwner;
            let matchedTransporter = false;

            (data.transporters || []).forEach(function(t) {
                let isSel = (curTransporter && curTransporter.toLowerCase() === t.toLowerCase());
                if (isSel) matchedTransporter = true;
                $trSel.append(`<option value="${escapeHtml(t)}" ${isSel ? 'selected' : ''}>${escapeHtml(t)}</option>`);
            });
            if (curTransporter && !matchedTransporter) {
                $trSel.append(`<option value="${escapeHtml(curTransporter)}" selected>${escapeHtml(curTransporter)}</option>`);
            }

            // Populate Driver Dropdown
            let $dSel = $("#vmodal_driver_select");
            $dSel.empty().append('<option value="">-- Select Existing Driver --</option>');
            $dSel.append('<option value="new">+ Add New Driver Details</option>');
            let matchedDriverId = 0;

            (data.drivers || []).forEach(function(d) {
                let isSel = false;
                if (initDriverId && String(d.driverid) === String(initDriverId)) {
                    isSel = true;
                    matchedDriverId = d.driverid;
                } else if (!matchedDriverId && initDriverName && d.drivername && d.drivername.toLowerCase() === initDriverName.toLowerCase()) {
                    isSel = true;
                    matchedDriverId = d.driverid;
                }
                $dSel.append(`<option value="${d.driverid}" data-drivername="${escapeHtml(d.drivername)}" data-mobile="${escapeHtml(d.drivermobileno||'')}" ${isSel ? 'selected' : ''}>${escapeHtml(d.drivername)} (${escapeHtml(d.drivermobileno||'-')})</option>`);
            });

            // If selected vehicle has no owner/transporter, show missing badge
            if (matchedVehId && !curTransporter) {
                $("#vmodal_transporter_missing_badge").show();
            } else {
                $("#vmodal_transporter_missing_badge").hide();
            }

            // Initialize Select2 on all dropdowns
            initVmodalSelect2();

            // Trigger Capacity Check for selected vehicle
            checkLiveVehicleTripCapacity();
        });

        $("#assignVehicleDriverModal").modal("show");
    }

    function initVmodalSelect2() {
        if ($.fn.select2) {
            if ($("#vmodal_vehicle_select").hasClass("select2-hidden-accessible")) {
                $("#vmodal_vehicle_select").select2("destroy");
            }
            $("#vmodal_vehicle_select").select2({
                dropdownParent: $("#assignVehicleDriverModal"),
                width: "100%",
                placeholder: "-- Choose Vehicle from Master --"
            });

            if ($("#vmodal_transporter_select").hasClass("select2-hidden-accessible")) {
                $("#vmodal_transporter_select").select2("destroy");
            }
            $("#vmodal_transporter_select").select2({
                dropdownParent: $("#assignVehicleDriverModal"),
                width: "100%",
                placeholder: "-- Choose Transporter or Type New Below --"
            });

            if ($("#vmodal_driver_select").hasClass("select2-hidden-accessible")) {
                $("#vmodal_driver_select").select2("destroy");
            }
            $("#vmodal_driver_select").select2({
                dropdownParent: $("#assignVehicleDriverModal"),
                width: "100%",
                placeholder: "-- Select Existing Driver --"
            });
        }
    }

    $("#assignVehicleDriverModal").on("shown.bs.modal", function() {
        initVmodalSelect2();
    });

    // Vehicle Selection in Modal
    $(document).on("change", "#vmodal_vehicle_select", function() {
        let val = $(this).val();
        if (val === "custom") {
            $("#vmodal_vehiclename").val('').focus();
            $("#vmodal_vehicleno").val('');
            $("#vmodal_transporter_name").val('');
            $("#vmodal_transporter_select").val('').trigger('change.select2');
            $("#vmodal_transporter_missing_badge").hide();
        } else if (val) {
            let $opt = $(this).find("option:selected");
            let vName = $opt.data("vehiclename") || '';
            let vNo = $opt.data("vehicleno") || '';
            let vOwner = $opt.data("owner") || '';
            $("#vmodal_vehiclename").val(vName);
            $("#vmodal_vehicleno").val(vNo);
            if (vOwner) {
                $("#vmodal_transporter_name").val(vOwner);
                $("#vmodal_transporter_select").val(vOwner).trigger('change.select2');
                $("#vmodal_transporter_missing_badge").hide();
            } else {
                $("#vmodal_transporter_name").val('');
                $("#vmodal_transporter_select").val('').trigger('change.select2');
                $("#vmodal_transporter_missing_badge").show();
            }
        }
        checkLiveVehicleTripCapacity();
    });

    // Transporter Selection in Modal
    $(document).on("change", "#vmodal_transporter_select", function() {
        let val = $(this).val();
        if (val === "new") {
            $("#vmodal_transporter_name").val('').focus();
            $("#vmodal_transporter_missing_badge").hide();
        } else if (val) {
            $("#vmodal_transporter_name").val(val);
            $("#vmodal_transporter_missing_badge").hide();
        }
    });

    // Real-time typing in Transporter Name removes missing warning
    $(document).on("input", "#vmodal_transporter_name", function() {
        if ($(this).val().trim()) {
            $("#vmodal_transporter_missing_badge").hide();
        }
    });

    // Driver Selection in Modal
    $(document).on("change", "#vmodal_driver_select", function() {
        let val = $(this).val();
        if (val === "new") {
            $("#vmodal_driver_id").val('');
            $("#vmodal_driver_name").val('').focus();
            $("#vmodal_driver_mobile").val('');
        } else if (val) {
            let $opt = $(this).find("option:selected");
            let dName = $opt.data("drivername") || '';
            let dMobile = $opt.data("mobile") || '';
            $("#vmodal_driver_id").val(val);
            $("#vmodal_driver_name").val(dName);
            $("#vmodal_driver_mobile").val(dMobile);
        }
    });

    // Reload Freight & Vehicle Allocation from database
    $(document).on("click", "#dinv_btn_reload_freight, .dinv-reload-freight-btn", function(e) {
        e.preventDefault();
        let $btn = $(this);
        let origHtml = $btn.html();

        let psono = $("#dinv_psono").val() || (currentInvoiceData?.order?.psono || '');
        let sono = $("#dinv_sono").val() || (currentInvoiceData?.order?.sono || '');
        let projectid = $("#dinv_projectid").val() || (currentInvoiceData?.order?.projectid || CURRENT_APP_PROJECTID);

        if (!psono && !sono) {
            Swal.fire("Notice", "No active order found to reload freight.", "info");
            return;
        }

        $btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Reloading...');

        $.ajax({
            url: "modal/finaldispatch_modal.php?action=ReloadFreightAndVehicleAllocation",
            type: "POST",
            data: {
                psono: psono,
                sono: sono,
                projectid: projectid
            },
            dataType: "json",
            success: function(res) {
                $btn.prop("disabled", false).html(origHtml);
                if (res && res.status === "success" && res.data) {
                    if (!currentInvoiceData) currentInvoiceData = {};

                    // Update currentInvoiceData with latest records
                    if (res.data.delivery_location) {
                        currentInvoiceData.delivery_location = res.data.delivery_location;
                    }
                    if (Array.isArray(res.data.delivery_locations) && res.data.delivery_locations.length > 0) {
                        currentInvoiceData.delivery_locations = res.data.delivery_locations;
                    }
                    if (res.data.freight_charges) {
                        currentInvoiceData.freight_charges = res.data.freight_charges;
                    }
                    if (res.data.vehicle_allocation) {
                        currentInvoiceData.vehicle_allocation = res.data.vehicle_allocation;
                    }

                    currentInvoiceData.original_base_freight = res.data.original_base_freight;
                    currentInvoiceData.original_payable_freight = res.data.original_payable_freight;
                    currentInvoiceData.original_discount_percent = res.data.original_discount_percent;
                    currentInvoiceData.original_discount_amount = res.data.original_discount_amount;
                    currentInvoiceData.original_discount_remark = res.data.original_discount_remark;

                    // Sync delivery type radio if updated in db
                    let dlMode = res.data.delivery_location?.pickuptype || 'delivered';
                    let normDlMode = (dlMode === 'client_pickup' || dlMode === 'pickupbyclient' || dlMode === 'pickup') ? 'pickup' : (dlMode === 'boxpacking' ? 'boxpacking' : 'delivered');
                    $(`input[name="dinv_delivery_type"][value="${normDlMode}"]`).prop("checked", true);

                    // Re-render freight table and recalculate invoice totals live on screen
                    renderDispatchFreightTable();
                    recalculateAllInvoiceData();

                    Swal.fire({
                        icon: "success",
                        title: "Freight & Vehicle Reloaded",
                        text: "Latest freight charges and vehicle allocation have been updated on screen.",
                        timer: 2000,
                        showConfirmButton: false,
                        toast: true,
                        position: "top-end"
                    });
                } else {
                    Swal.fire("Error", res?.message || "Failed to reload freight details.", "error");
                }
            },
            error: function(xhr) {
                $btn.prop("disabled", false).html(origHtml);
                Swal.fire("Error", "Server error while reloading freight data.", "error");
            }
        });
    });

    // Direct button click to open Assign Vehicle & Driver Modal
    $(document).on("click", ".dinv-open-assign-vehicle-btn", function(e) {
        e.preventDefault();
        let gIdx = $(this).data("group-index");
        openAssignVehicleDriverModal(gIdx);
    });

    // Vehicle Selection & Freight Estimation Modal Trigger
    $(document).on("click", ".dinv-open-vehicle-calc-btn", function(e) {
        e.preventDefault();
        const $btn = $(this);
        const $row = $btn.closest("tr");
        const gIndex = parseInt($btn.data("group-index") || 0);
        const km = parseFloat($btn.data("km") || $row.find(".dinv_f_distance_info").val() || 0);
        const totalQty = parseInt($btn.data("totalqty") || $row.data("totalqty") || 0);
        const projId = currentInvoiceData?.order?.projectid || $("#dinv_projectid").val() || CURRENT_APP_PROJECTID;
        const rootUrl = typeof CURRENT_ROOTURL !== 'undefined' ? CURRENT_ROOTURL : '';

        if (typeof openFreightCalculator === 'function') {
            openFreightCalculator(km, projId, rootUrl, function(data) {
                let chargeVal = parseFloat(data.charge || 0);
                let vehName = data.vehiclename || 'Vehicle';
                let vehNo = data.vehicleno || '';
                let calcKm = parseFloat(data.calc_km || km || 0);

                $row.find(".dinv_f_amt_input").val(chargeVal.toFixed(2)).trigger("input");
                $row.find(".dinv_f_vehicle_info").val(vehName);
                $row.find(".dinv_f_vehicle_no").val(vehNo);
                $row.find(".dinv-open-vehicle-calc-btn")
                    .removeClass("btn-outline-danger")
                    .addClass("btn-primary")
                    .attr("title", "Choose vehicle again / Calculate freight");

                if (calcKm > 0) {
                    $row.find(".dinv-row-km-text").text(calcKm);
                    $row.find(".dinv_f_distance_info").val(calcKm);
                    $row.find(".dinv_f_totaldistance_input").val(calcKm);
                    $row.data("km", calcKm);
                    $btn.data("km", calcKm);
                }

                if (window._currentFreightGroups && window._currentFreightGroups[gIndex]) {
                    window._currentFreightGroups[gIndex].baseFreight = chargeVal;
                    if (window._currentFreightGroups[gIndex].location) {
                        window._currentFreightGroups[gIndex].location.vehiclename = vehName;
                        window._currentFreightGroups[gIndex].location.vehicleno = vehNo;
                        window._currentFreightGroups[gIndex].location.transporttype = vehName;
                        if (calcKm > 0) {
                            window._currentFreightGroups[gIndex].location.distance = calcKm;
                        }
                    }
                }

                if (gIndex === 0 && currentInvoiceData) {
                    currentInvoiceData.original_base_freight = chargeVal;
                    if (currentInvoiceData.delivery_location) {
                        currentInvoiceData.delivery_location.vehiclename = vehName;
                        currentInvoiceData.delivery_location.vehicleno = vehNo;
                        currentInvoiceData.delivery_location.transporttype = vehName;
                    }
                }

                updateFreightTableFootersAndGrandTotal();
                renderDispatchFreightRules(true);

                // Immediately open the Assign Vehicle, Transporter & Driver modal with trip capacity visualizer
                openAssignVehicleDriverModal(gIndex, {
                    vehiclename: vehName,
                    vehicleno: vehNo,
                    charge: chargeVal,
                    calc_km: calcKm
                });
            });
        } else {
            $.ajax({
                url: "modal/vehicle_modal.php?action=calculateFreight",
                type: "POST",
                data: { projectid: projId, km: km, total_qty: totalQty },
                dataType: "json",
                success: function(res) {
                    let chargeVal = parseFloat(res.recommended_charge || 0);
                    $row.find(".dinv_f_amt_input").val(chargeVal.toFixed(2)).trigger("input");
                    updateFreightTableFootersAndGrandTotal();
                    renderDispatchFreightRules(true);
                    openAssignVehicleDriverModal(gIndex, { charge: chargeVal, calc_km: km });
                }
            });
        }
    });

    // Form Submit Handler for Assigning Vehicle, Transporter, Driver & Trip
    $(document).on("submit", "#assignVehicleDriverForm", function(e) {
        e.preventDefault();

        let gIdx = parseInt($("#vmodal_group_index").val()) || 0;
        let psono = $("#vmodal_psono").val() || $("#dinv_psono").val() || (currentInvoiceData?.order?.psono || '');
        let clientId = $("#vmodal_clientid").val() || $("#dinv_clientid").val() || (currentInvoiceData?.client_info?.client_id || '');
        let projectId = $("#vmodal_projectid").val() || currentInvoiceData?.order?.projectid || CURRENT_APP_PROJECTID;
        let deliveryDate = $("#vmodal_deliverydate").val() || $("#dinv_invoicedate").val() || '';
        let orderPlants = parseInt($("#vmodal_order_plants").val()) || 0;
        let distance = parseFloat($("#vmodal_distance").val()) || 0;
        let freightAmt = parseFloat($("#vmodal_freightamt").val()) || 0;

        let vehId = parseInt($("#vmodal_vehicle_select").val()) || 0;
        let vehName = $("#vmodal_vehiclename").val().trim();
        let vehNo = $("#vmodal_vehicleno").val().trim();
        let transporterName = $("#vmodal_transporter_name").val().trim();
        let driverName = $("#vmodal_driver_name").val().trim();
        let driverMobile = $("#vmodal_driver_mobile").val().trim();
        let driverId = $("#vmodal_driver_id").val().trim();
        let tripId = $("#vmodal_trip_id").val().trim();

        if (!vehName) {
            Swal.fire('Validation Error', 'Please enter Vehicle Name.', 'warning');
            return false;
        }
        if (!vehNo) {
            Swal.fire('Validation Error', 'Please enter Vehicle Registration / Plate Number.', 'warning');
            return false;
        }
        if (!transporterName) {
            Swal.fire('Validation Error', 'Please enter Transporter / Vehicle Owner Name.', 'warning');
            return false;
        }
        if (!driverName) {
            Swal.fire('Validation Error', 'Please enter Driver Name.', 'warning');
            return false;
        }
        if (!driverMobile) {
            Swal.fire('Validation Error', 'Please enter Driver Mobile Number.', 'warning');
            return false;
        }

        let $btn = $("#btn_save_vehicle_driver");
        $btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin me-1"></i> Saving...');

        $.ajax({
            url: "modal/finaldispatch_modal.php?action=SaveDeliveryVehicle",
            type: "POST",
            data: {
                orderid: psono,
                clientid: clientId,
                projectid: projectId,
                vehicleid: vehId,
                vehiclename: vehName,
                vehicleno: vehNo,
                transporter_name: transporterName,
                driver_name: driverName,
                driver_mobile: driverMobile,
                driver_id: driverId,
                trip_id: tripId,
                deliverydate: deliveryDate,
                order_plants: orderPlants,
                distance: distance,
                freightamt: freightAmt
            },
            dataType: "json",
            success: function(res) {
                $btn.prop("disabled", false).html('<i class="fa fa-check-circle me-1"></i> Save &amp; Confirm Vehicle &amp; Trip');
                if (res && res.status === "success") {
                    let savedTripId = res.data?.trip_id || tripId;

                    // Update activeGroup location & data in memory
                    let activeGroup = (window._currentFreightGroups && window._currentFreightGroups[gIdx]) ? window._currentFreightGroups[gIdx] : null;
                    if (activeGroup) {
                        if (!activeGroup.location) activeGroup.location = {};
                        activeGroup.location.vehiclename = vehName;
                        activeGroup.location.vehicleno = vehNo;
                        activeGroup.location.vehicleownername = transporterName;
                        activeGroup.location.drivername = driverName;
                        activeGroup.location.drivercontactno = driverMobile;
                        activeGroup.location.driverid = driverId;
                        activeGroup.location.trip_id = savedTripId;
                        activeGroup.location.transporttype = vehName;
                        activeGroup.location.vehicleid = vehId;
                    }

                    if (gIdx === 0 && currentInvoiceData) {
                        if (!currentInvoiceData.delivery_location) currentInvoiceData.delivery_location = {};
                        currentInvoiceData.delivery_location.vehiclename = vehName;
                        currentInvoiceData.delivery_location.vehicleno = vehNo;
                        currentInvoiceData.delivery_location.vehicleownername = transporterName;
                        currentInvoiceData.delivery_location.drivername = driverName;
                        currentInvoiceData.delivery_location.drivercontactno = driverMobile;
                        currentInvoiceData.delivery_location.driverid = driverId;
                        currentInvoiceData.delivery_location.trip_id = savedTripId;
                        currentInvoiceData.delivery_location.transporttype = vehName;

                        if (!currentInvoiceData.freight_charges) currentInvoiceData.freight_charges = {};
                        currentInvoiceData.freight_charges.vehiclename = vehName;
                        currentInvoiceData.freight_charges.vehicle_no = vehNo;
                        currentInvoiceData.freight_charges.vehicleownername = transporterName;
                        currentInvoiceData.freight_charges.driver_name = driverName;
                        currentInvoiceData.freight_charges.driver_mobile = driverMobile;
                        currentInvoiceData.freight_charges.driver_id = driverId;
                        currentInvoiceData.freight_charges.trip_id = savedTripId;
                        currentInvoiceData.freight_charges.vehicleid = vehId;
                    }

                    // Update the table row in DOM
                    const $row = $(`#dinvFreightGroupTable tbody tr.dinv-group-row[data-group-index="${gIdx}"]`);
                    if ($row.length > 0) {
                        $row.find(".dinv_f_vehicle_id").val(vehId);
                        $row.find(".dinv_f_vehicle_info").val(vehName);
                        $row.find(".dinv_f_vehicle_no").val(vehNo);
                        $row.find(".dinv_f_transporter_name").val(transporterName);
                        $row.find(".dinv_f_driver_id").val(driverId);
                        $row.find(".dinv_f_driver_name").val(driverName);
                        $row.find(".dinv_f_driver_mobile").val(driverMobile);
                        $row.find(".dinv_f_trip_id").val(savedTripId);

                        let badgeHtml = `
                            <div class="d-flex flex-column align-items-center gap-1">
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" title="Selected Vehicle"><i class="fa fa-truck me-1"></i>${escapeHtml(vehName)}</span>
                                <span class="badge bg-light text-dark border font-monospace" style="font-size:10px;">${escapeHtml(vehNo)}</span>
                                ${transporterName ? `<div class="text-muted text-truncate" style="font-size:10px; max-width: 140px;" title="Transporter: ${escapeHtml(transporterName)}"><i class="fa fa-building text-primary me-1"></i>${escapeHtml(transporterName)}</div>` : ''}
                                <div class="text-secondary fw-semibold text-truncate" style="font-size:10.5px; max-width: 140px;" title="Driver: ${escapeHtml(driverName)} (${escapeHtml(driverMobile)})"><i class="fa fa-user me-1 text-muted"></i>${escapeHtml(driverName)}</div>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace" style="font-size:10px;" title="Trip ID"><i class="fa fa-route me-1"></i>${escapeHtml(savedTripId)}</span>
                                <button type="button" class="btn btn-xs btn-outline-primary dinv-open-assign-vehicle-btn px-2 py-0.5 mt-0.5 shadow-2xs" data-group-index="${gIdx}" style="font-size: 10.5px;" title="Edit Vehicle, Transporter &amp; Driver">
                                    <i class="fa fa-edit me-1"></i> Edit
                                </button>
                            </div>
                        `;
                        $row.find(".dinv_f_vehicle_badge").html(badgeHtml);
                        $row.find(".dinv-open-vehicle-calc-btn")
                            .removeClass("btn-outline-danger")
                            .addClass("btn-primary")
                            .attr("title", "Choose vehicle again / Calculate freight");
                    }

                    $("#assignVehicleDriverModal").modal("hide");

                    Swal.fire({
                        title: "Vehicle &amp; Driver Assigned!",
                        html: `<b>Vehicle:</b> ${vehName} (${vehNo})<br><b>Transporter:</b> ${transporterName}<br><b>Driver:</b> ${driverName} (${driverMobile})<br><b>Trip ID:</b> <span class="badge bg-primary font-monospace">${savedTripId}</span>`,
                        icon: "success",
                        timer: 2500,
                        showConfirmButton: false,
                        toast: true,
                        position: "top-end"
                    });
                } else {
                    Swal.fire('Save Failed', res.message || 'Could not save vehicle and driver details.', 'error');
                }
            },
            error: function() {
                $btn.prop("disabled", false).html('<i class="fa fa-check-circle me-1"></i> Save &amp; Confirm Vehicle &amp; Trip');
                Swal.fire('Error', 'Server error while saving vehicle &amp; driver details.', 'error');
            }
        });
    });

    // -------------------------------------------------------------
    // Delivery Address & Map Locator Event Handlers
    // -------------------------------------------------------------
    function syncLatLongInputsFromCombined() {
        let val = $("#latlonginput").val() ? $("#latlonginput").val().trim() : '';
        if (!val) {
            $("#latinput").val('');
            $("#longinput").val('');
            return;
        }
        let parts = val.split(',');
        let lat = parts[0] ? parts[0].trim() : '';
        let lon = parts[1] ? parts[1].trim() : '';
        $("#latinput").val(lat);
        $("#longinput").val(lon);

        if (lat && lon && !isNaN(lat) && !isNaN(lon)) {
            let clientId = $("#dinv_clientid").val() || (currentInvoiceData?.client_info?.client_id || '');
            let psono = $("#dinv_psono").val() || (currentInvoiceData?.order?.psono || '');
            let url = 'locatioinaddressget_api.php?lat=' + encodeURIComponent(lat) + 
                      '&lon=' + encodeURIComponent(lon) + 
                      '&clid=' + encodeURIComponent(clientId) + 
                      '&orderid=' + encodeURIComponent(psono) + 
                      '&bindbtnid=latlonginput';
            let currentSrc = $('#appenddeliveryaddressdiv iframe').attr('src');
            if (currentSrc !== url) {
                $('#appenddeliveryaddressdiv').html('<iframe src="' + url + '" id="location-iframe" style="width:100%; height:640px; border:none; border-radius:12px; overflow:hidden;" scrolling="no"></iframe>');
            }
        }
    }

    function syncCombinedFromIndividualLatLong() {
        let lat = $("#latinput").val() ? $("#latinput").val().trim() : '';
        let lon = $("#longinput").val() ? $("#longinput").val().trim() : '';
        if (lat || lon) {
            $("#latlonginput").val(`${lat}${lon ? ', ' + lon : ''}`);
        } else {
            $("#latlonginput").val('');
        }

        if (lat && lon && !isNaN(lat) && !isNaN(lon)) {
            let clientId = $("#dinv_clientid").val() || (currentInvoiceData?.client_info?.client_id || '');
            let psono = $("#dinv_psono").val() || (currentInvoiceData?.order?.psono || '');
            let url = 'locatioinaddressget_api.php?lat=' + encodeURIComponent(lat) + 
                      '&lon=' + encodeURIComponent(lon) + 
                      '&clid=' + encodeURIComponent(clientId) + 
                      '&orderid=' + encodeURIComponent(psono) + 
                      '&bindbtnid=latlonginput';
            let currentSrc = $('#appenddeliveryaddressdiv iframe').attr('src');
            if (currentSrc !== url) {
                $('#appenddeliveryaddressdiv').html('<iframe src="' + url + '" id="location-iframe" style="width:100%; height:640px; border:none; border-radius:12px; overflow:hidden;" scrolling="no"></iframe>');
            }
        }
    }

    window.initLatLongSplit = syncLatLongInputsFromCombined;

    $(document).on("input paste change keyup", "#latlonginput", function() {
        setTimeout(syncLatLongInputsFromCombined, 20);
    });

    $(document).on("input paste change keyup", "#latinput, #longinput", function() {
        setTimeout(syncCombinedFromIndividualLatLong, 20);
    });

    $(document).on("click", ".dinv-open-map-modal-btn, #dinv_btn_update_address", function(e) {
        e.preventDefault();
        let gIdx = $(this).data("group-index");
        if (typeof gIdx === "undefined" || isNaN(parseInt(gIdx))) {
            gIdx = 0;
        } else {
            gIdx = parseInt(gIdx);
        }
        window._activeDeliveryGroupIndex = gIdx;

        let activeGroup = (window._currentFreightGroups && window._currentFreightGroups[gIdx]) ? window._currentFreightGroups[gIdx] : null;
        let targetLoc = activeGroup ? activeGroup.location : (currentInvoiceData?.delivery_location || {});

        let clientId = $("#dinv_clientid").val() || (currentInvoiceData?.client_info?.client_id || '');
        let psono = $("#dinv_psono").val() || (currentInvoiceData?.order?.psono || '');
        let currentLat = targetLoc.dest_latitude || '';
        let currentLon = targetLoc.dest_longitude || '';

        // If coordinates already exist in targetLoc, populate inputs
        if (currentLat && currentLon) {
            $("#latinput").val(currentLat);
            $("#longinput").val(currentLon);
            $("#latlonginput").val(`${currentLat}, ${currentLon}`);
        } else {
            let existingVal = $("#latlonginput").val();
            if (existingVal) {
                syncLatLongInputsFromCombined();
                currentLat = $("#latinput").val() || '';
                currentLon = $("#longinput").val() || '';
            } else {
                $("#latinput").val('');
                $("#longinput").val('');
            }
        }

        let url = 'locatioinaddressget_api.php?lat=' + encodeURIComponent(currentLat) + 
                  '&lon=' + encodeURIComponent(currentLon) + 
                  '&clid=' + encodeURIComponent(clientId) + 
                  '&orderid=' + encodeURIComponent(psono) + 
                  '&bindbtnid=latlonginput';

        let $container = $('#appenddeliveryaddressdiv');
        $container.html('<div class="text-center py-5" style="min-height:400px; display:flex; flex-direction:column; justify-content:center; align-items:center;"><div class="spinner-border text-primary" role="status"></div><p class="mt-3 text-muted fw-bold">Loading map & location locator...</p></div>');

        setTimeout(function() {
            $container.html('<iframe src="' + url + '" id="location-iframe" style="width:100%; height:640px; border:none; border-radius:12px; overflow:hidden;" scrolling="no"></iframe>');
        }, 150);

        $("#adddeliveryaddressModal").modal("show");
    });

    // Global function called from the Iframe in locatioinaddressget_api.php
    window.appendAddressRow = function(data) {
        if (!data || !data.full_address) {
            Swal.fire('Incomplete Data', 'Delivery address is required', 'warning');
            return false;
        }

        let lat = data.lat || "";
        let lon = data.lon || "";
        let state = data.state || "";
        let district = data.district || "";
        let city = data.city || "";
        let pincode = data.pincode || "";
        let fullAddress = data.full_address || "";
        let calcKm = parseFloat(data.calc_km || data.distance_km || 0);

        // Update modal coordinate inputs
        if (lat && lon) {
            $("#latinput").val(lat);
            $("#longinput").val(lon);
            $("#latlonginput").val(`${lat}, ${lon}`);
        }

        let gIdx = window._activeDeliveryGroupIndex || 0;
        let activeGroup = (window._currentFreightGroups && window._currentFreightGroups[gIdx]) ? window._currentFreightGroups[gIdx] : null;

        if (activeGroup) {
            activeGroup.isLocationAvailable = true;
            activeGroup.location.is_location_available = true;
            activeGroup.location.deliveryaddress = fullAddress;
            activeGroup.location.dest_state = state;
            activeGroup.location.dest_district = district;
            activeGroup.location.dest_city = city;
            activeGroup.location.dest_pincode = pincode;
            activeGroup.location.dest_latitude = lat;
            activeGroup.location.dest_longitude = lon;
            activeGroup.location.totaldistance = calcKm;
            activeGroup.location.distance = calcKm;
            activeGroup.location.pickuptype = 'delivered';
        }

        if (gIdx === 0 || !currentInvoiceData?.delivery_location) {
            $("#dinv_delivery_address").val(fullAddress);
            $("#dinv_statename").val(state);
            $("#dinv_districtname").val(district);

            if (currentInvoiceData) {
                if (!currentInvoiceData.delivery_location) currentInvoiceData.delivery_location = {};
                currentInvoiceData.delivery_location.is_location_available = true;
                currentInvoiceData.delivery_location.deliveryaddress = fullAddress;
                currentInvoiceData.delivery_location.dest_state = state;
                currentInvoiceData.delivery_location.dest_district = district;
                currentInvoiceData.delivery_location.dest_city = city;
                currentInvoiceData.delivery_location.dest_pincode = pincode;
                currentInvoiceData.delivery_location.dest_latitude = lat;
                currentInvoiceData.delivery_location.dest_longitude = lon;
                currentInvoiceData.delivery_location.totaldistance = calcKm;
                currentInvoiceData.delivery_location.freedistance = 0;
                currentInvoiceData.delivery_location.distance = calcKm;
                currentInvoiceData.delivery_location.pickuptype = 'delivered';

                let shortAddr = fullAddress ? ` (${fullAddress.substring(0, 30)}...)` : '';
                $("#dinv_prev_dest").text(`${district || '-'}, ${state || '-'}${shortAddr}`);
                $("#dinv_prev_distance_text").text(`Total: ${calcKm} km | Free: 0 km | Chargeable: ${calcKm} km`);
                $("#dinv_delivery_mode_badge").text("Mode: Delivered (Vehicle)");
            }
        }

        let targetQty = activeGroup ? activeGroup.totalAllocQty : 0;
        if (targetQty <= 0) {
            $(".dinv-product-card").each(function() {
                if ($(this).find(".dinv-item-include-checkbox").is(":checked")) {
                    let idx = $(this).data("index");
                    if (currentInvoiceData?.items && currentInvoiceData.items[idx]) {
                        let it = currentInvoiceData.items[idx];
                        targetQty += parseInt(it.allocate_ordqty || it.original_orderqty || 0);
                    }
                }
            });
        }
        if (targetQty <= 0) targetQty = parseInt(currentInvoiceData?.total_original_order_qty || 0);

        let projId = currentInvoiceData?.order?.projectid || $("#dinv_projectid").val() || CURRENT_APP_PROJECTID;
        let activeOrderId = currentInvoiceData?.order?.psono || currentInvoiceData?.order?.sono || $("#dinv_sono").val() || $("#dinv_psono").val();
        let activeClientId = currentInvoiceData?.order?.clientid || $("#dinv_clientid").val();

        // Immediately sync GPS location with krf_vehicletrip & logistics tables
        if (activeOrderId) {
            $.ajax({
                url: "modal/finaldispatch_modal.php?action=SaveDeliveryLocationAddress",
                type: "POST",
                data: {
                    orderid: activeOrderId,
                    clientid: activeClientId,
                    projectid: projId,
                    state: state,
                    district: district,
                    city: city,
                    pincode: pincode,
                    full_address: fullAddress,
                    lat: lat,
                    lon: lon,
                    distance_km: calcKm,
                    total_qty: targetQty,
                    source: 'GPS'
                },
                dataType: "json",
                success: function(syncRes) {
                    console.log("GPS Location synced to krf_vehicletrip:", syncRes);
                }
            });
        }

        // Fetch vehicle rate and freight recommendation from matrix
        $.ajax({
            url: "modal/vehicle_modal.php?action=calculateFreight",
            type: "POST",
            data: {
                projectid: projId,
                km: calcKm,
                total_qty: targetQty
            },
            dataType: "json",
            success: function(res) {
                let recCharge = parseFloat(res.recommended_charge || 0);
                if (activeGroup) {
                    activeGroup.baseFreight = recCharge;
                    activeGroup.payableFreight = recCharge;
                }
                if (currentInvoiceData) {
                    currentInvoiceData.original_base_freight = recCharge;
                    currentInvoiceData.original_payable_freight = recCharge;
                    $("#dinv_prev_freight_text").html(`Base: <b>₹ ${recCharge.toFixed(2)}</b> | Calculated from ${calcKm} km`);
                }

                // Recalculate invoice totals and evaluate freight discount rules
                recalculateAllInvoiceData();

                $("#adddeliveryaddressModal").modal("hide");

                Swal.fire({
                    title: "Delivery Address & Freight Updated!",
                    html: `<b>Destination:</b> ${district}, ${state}<br><b>Distance:</b> ${calcKm} km<br><b>Base Vehicle Freight:</b> ₹ ${recCharge.toFixed(2)}<br><small class="text-success fw-bold">Discount rules have been automatically evaluated!</small>`,
                    icon: "success",
                    timer: 2500,
                    showConfirmButton: false
                });
            },
            error: function() {
                if (activeGroup) {
                    activeGroup.baseFreight = 0;
                    activeGroup.payableFreight = 0;
                }
                recalculateAllInvoiceData();
                $("#adddeliveryaddressModal").modal("hide");
            }
        });
    };

    // Support live updates and message from iframe
    window.addEventListener('message', function(event) {
        if (!event.data) return;
        if (event.data.type === 'coords_updated') {
            let lat = event.data.lat || '';
            let lon = event.data.lon || '';
            if (lat && lon) {
                $("#latinput").val(lat);
                $("#longinput").val(lon);
                $("#latlonginput").val(`${lat}, ${lon}`);
            }
        } else if (event.data.type === 'delivery_address_selected' && event.data.data) {
            window.appendAddressRow(event.data.data);
        }
    });

    // -------------------------------------------------------------
    // MANUAL DELIVERY ADDRESS & DISTANCE MODAL HANDLERS
    // -------------------------------------------------------------
    let cachedMasterStates = null;
    let cachedDistrictsByState = {};

    function loadMasterStates(selectedState, selectedDistrict) {
        if (typeof selectedState === 'undefined' || !selectedState) {
            selectedState = "Chhattisgarh";
        }
        if (typeof selectedDistrict === 'undefined') {
            selectedDistrict = "";
        }

        const $stateSelect = $("#manual_addr_state");
        
        let populateStates = function(states) {
            $stateSelect.empty().append('<option value="">-- Select State --</option>');
            let matched = false;
            let targetStateName = String(selectedState || "Chhattisgarh").trim().toLowerCase();

            states.forEach(function(st) {
                let stName = st.statename || '';
                let stId = st.stateid || stName;
                let isSelected = false;
                if (stName.toLowerCase() === targetStateName || String(stId).toLowerCase() === targetStateName) {
                    isSelected = true;
                    matched = true;
                }
                $stateSelect.append(`<option value="${escapeHtml(stName)}" data-stateid="${escapeHtml(stId)}" ${isSelected ? 'selected' : ''}>${escapeHtml(stName)}</option>`);
            });

            // If target wasn't matched explicitly, fallback to selecting Chhattisgarh
            if (!matched) {
                let cgOpt = $stateSelect.find("option").filter(function() { return $(this).text().trim().toLowerCase() === "chhattisgarh"; });
                if (cgOpt.length) {
                    cgOpt.prop("selected", true);
                }
            }

            let currentStateVal = $stateSelect.val() || "Chhattisgarh";
            loadMasterDistricts(currentStateVal, selectedDistrict);
        };

        if (cachedMasterStates && cachedMasterStates.length > 0) {
            populateStates(cachedMasterStates);
        } else {
            $stateSelect.html('<option value="">Loading States...</option>');
            $.ajax({
                url: "modal/finaldispatch_modal.php?action=GetMasterStates",
                type: "GET",
                dataType: "json",
                success: function(res) {
                    if (res && res.status === "success" && Array.isArray(res.data) && res.data.length > 0) {
                        cachedMasterStates = res.data;
                        populateStates(res.data);
                    } else {
                        $stateSelect.html('<option value="Chhattisgarh" selected>Chhattisgarh</option>');
                        loadMasterDistricts("Chhattisgarh", selectedDistrict);
                    }
                },
                error: function() {
                    $stateSelect.html('<option value="Chhattisgarh" selected>Chhattisgarh</option>');
                    loadMasterDistricts("Chhattisgarh", selectedDistrict);
                }
            });
        }
    }

    function loadMasterDistricts(stateVal, selectedDistrict) {
        const $distSelect = $("#manual_addr_district");
        if (!stateVal) {
            $distSelect.empty().append('<option value="">-- Select District --</option>');
            return;
        }

        if (typeof selectedDistrict === 'undefined') {
            selectedDistrict = "";
        }

        let populateDistricts = function(districts) {
            $distSelect.empty().append('<option value="">-- Select District --</option>');
            let targetDist = String(selectedDistrict || "").trim().toLowerCase();

            districts.forEach(function(dt) {
                let dtName = dt.districtname || '';
                let dtId = dt.districtid || dtName;
                let isSelected = (dtName.toLowerCase() === targetDist || String(dtId).toLowerCase() === targetDist);
                $distSelect.append(`<option value="${escapeHtml(dtName)}" data-districtid="${escapeHtml(dtId)}" ${isSelected ? 'selected' : ''}>${escapeHtml(dtName)}</option>`);
            });
        };

        let cacheKey = String(stateVal).trim().toLowerCase();
        if (cachedDistrictsByState[cacheKey] && cachedDistrictsByState[cacheKey].length > 0) {
            populateDistricts(cachedDistrictsByState[cacheKey]);
        } else {
            $distSelect.html('<option value="">Loading Districts...</option>');
            $.ajax({
                url: "modal/finaldispatch_modal.php?action=GetMasterDistricts",
                type: "POST",
                data: { state: stateVal },
                dataType: "json",
                success: function(res) {
                    if (res && res.status === "success" && Array.isArray(res.data)) {
                        cachedDistrictsByState[cacheKey] = res.data;
                        populateDistricts(res.data);
                    } else {
                        $distSelect.empty().append('<option value="">-- Select District --</option>');
                    }
                },
                error: function() {
                    $distSelect.empty().append('<option value="">-- Select District --</option>');
                }
            });
        }
    }

    // State change event to cascade districts
    $(document).on("change", "#manual_addr_state", function() {
        let stateVal = $(this).val();
        loadMasterDistricts(stateVal, "");
    });

    // Open Manual Address Modal Handler
    $(document).on("click", ".dinv-open-manual-addr-modal-btn", function(e) {
        e.preventDefault();
        let gIdx = $(this).data("group-index");
        if (typeof gIdx === "undefined" || isNaN(parseInt(gIdx))) {
            gIdx = 0;
        } else {
            gIdx = parseInt(gIdx);
        }
        window._activeDeliveryGroupIndex = gIdx;

        let activeGroup = (window._currentFreightGroups && window._currentFreightGroups[gIdx]) ? window._currentFreightGroups[gIdx] : null;
        let targetLoc = activeGroup ? activeGroup.location : (currentInvoiceData?.delivery_location || {});

        let clientId = $("#dinv_clientid").val() || (currentInvoiceData?.client_info?.client_id || '');
        let psono = $("#dinv_psono").val() || (currentInvoiceData?.order?.psono || '');
        let projectId = currentInvoiceData?.order?.projectid || $("#dinv_projectid").val() || CURRENT_APP_PROJECTID;

        $("#manual_addr_group_index").val(gIdx);
        $("#manual_addr_psono").val(psono);
        $("#manual_addr_clientid").val(clientId);
        $("#manual_addr_projectid").val(projectId);

        let curState = targetLoc.dest_state || 'Chhattisgarh';
        let curDistrict = targetLoc.dest_district || '';
        let curBlock = targetLoc.dest_block || targetLoc.block || '';
        let curCity = targetLoc.dest_city || targetLoc.city || '';
        let curPincode = targetLoc.dest_pincode || targetLoc.pincode || '';
        let curKm = parseFloat(targetLoc.distance || targetLoc.totaldistance || 0);

        $("#manual_addr_block").val(curBlock);
        $("#manual_addr_city").val(curCity);
        $("#manual_addr_pincode").val(curPincode);
        $("#manual_addr_distance").val(curKm > 0 ? curKm : '');

        // Populate state and district dropdowns
        loadMasterStates(curState, curDistrict);

        $("#manualDeliveryAddressModal").modal("show");
    });

    // Form Submit Handler for Manual Address & Distance
    $(document).on("submit", "#manualDeliveryAddressForm", function(e) {
        e.preventDefault();

        let gIdx = parseInt($("#manual_addr_group_index").val()) || window._activeDeliveryGroupIndex || 0;
        let psono = $("#manual_addr_psono").val() || $("#dinv_psono").val() || (currentInvoiceData?.order?.psono || '');
        let clientId = $("#manual_addr_clientid").val() || $("#dinv_clientid").val() || (currentInvoiceData?.client_info?.client_id || '');
        let projectId = $("#manual_addr_projectid").val() || currentInvoiceData?.order?.projectid || CURRENT_APP_PROJECTID;

        let state = $("#manual_addr_state").val() || 'Chhattisgarh';
        let district = $("#manual_addr_district").val() || '';
        let block = $("#manual_addr_block").val().trim();
        let city = $("#manual_addr_city").val().trim();
        let pincode = $("#manual_addr_pincode").val().trim();
        let distanceKm = parseFloat($("#manual_addr_distance").val()) || 0;

        if (!district) {
            Swal.fire('Validation Error', 'Please select a District from Master.', 'warning');
            return false;
        }
        if (!city) {
            Swal.fire('Validation Error', 'Please enter City / Village.', 'warning');
            return false;
        }
        if (distanceKm <= 0 || isNaN(distanceKm)) {
            Swal.fire('Validation Error', 'Please enter a valid Approx Distance (KM).', 'warning');
            return false;
        }

        let activeGroup = (window._currentFreightGroups && window._currentFreightGroups[gIdx]) ? window._currentFreightGroups[gIdx] : null;

        let targetQty = activeGroup ? activeGroup.totalAllocQty : 0;
        if (targetQty <= 0) {
            $(".dinv-product-card").each(function() {
                if ($(this).find(".dinv-item-include-checkbox").is(":checked")) {
                    let idx = $(this).data("index");
                    if (currentInvoiceData?.items && currentInvoiceData.items[idx]) {
                        let it = currentInvoiceData.items[idx];
                        targetQty += parseInt(it.allocate_ordqty || it.original_orderqty || 0);
                    }
                }
            });
        }
        if (targetQty <= 0) targetQty = parseInt(currentInvoiceData?.total_original_order_qty || 0);

        let $btnSubmit = $("#btn_save_manual_address");
        $btnSubmit.prop("disabled", true).html('<i class="fa fa-spinner fa-spin me-1"></i> Saving...');

        $.ajax({
            url: "modal/finaldispatch_modal.php?action=SaveManualDeliveryAddress",
            type: "POST",
            data: {
                orderid: psono,
                clientid: clientId,
                projectid: projectId,
                state: state,
                district: district,
                block: block,
                city: city,
                pincode: pincode,
                distance_km: distanceKm,
                total_qty: targetQty
            },
            dataType: "json",
            success: function(res) {
                $btnSubmit.prop("disabled", false).html('<i class="fa fa-check-circle me-1"></i> Save & Refresh Distance');
                if (res && res.status === "success") {
                    let fullAddr = res.data?.full_address || [city, block, district, state, pincode ? 'PIN: '+pincode : ''].filter(Boolean).join(', ');
                    let calcKm = parseFloat(res.data?.calc_km || distanceKm);
                    let recCharge = parseFloat(res.data?.recommended_charge || 0);

                    // Update activeGroup location state
                    if (activeGroup) {
                        activeGroup.isLocationAvailable = true;
                        activeGroup.location.is_location_available = true;
                        activeGroup.location.deliveryaddress = fullAddr;
                        activeGroup.location.dest_state = state;
                        activeGroup.location.dest_district = district;
                        activeGroup.location.dest_city = city;
                        activeGroup.location.dest_block = block;
                        activeGroup.location.dest_pincode = pincode;
                        activeGroup.location.totaldistance = calcKm;
                        activeGroup.location.distance = calcKm;
                        activeGroup.location.pickuptype = 'delivered';
                    }

                    // Update currentInvoiceData
                    if (gIdx === 0 || !currentInvoiceData?.delivery_location) {
                        $("#dinv_delivery_address").val(fullAddr);
                        $("#dinv_statename").val(state);
                        $("#dinv_districtname").val(district);

                        if (currentInvoiceData) {
                            if (!currentInvoiceData.delivery_location) currentInvoiceData.delivery_location = {};
                            currentInvoiceData.delivery_location.is_location_available = true;
                            currentInvoiceData.delivery_location.deliveryaddress = fullAddr;
                            currentInvoiceData.delivery_location.dest_state = state;
                            currentInvoiceData.delivery_location.dest_district = district;
                            currentInvoiceData.delivery_location.dest_city = city;
                            currentInvoiceData.delivery_location.dest_block = block;
                            currentInvoiceData.delivery_location.dest_pincode = pincode;
                            currentInvoiceData.delivery_location.totaldistance = calcKm;
                            currentInvoiceData.delivery_location.freedistance = 0;
                            currentInvoiceData.delivery_location.distance = calcKm;
                            currentInvoiceData.delivery_location.pickuptype = 'delivered';

                            let shortAddr = fullAddr ? ` (${fullAddr.substring(0, 30)}...)` : '';
                            $("#dinv_prev_dest").text(`${district || '-'}, ${state || '-'}${shortAddr}`);
                            $("#dinv_prev_distance_text").text(`Total: ${calcKm} km | Free: 0 km | Chargeable: ${calcKm} km`);
                            $("#dinv_delivery_mode_badge").text("Mode: Delivered (Vehicle)");
                        }
                    }

                    // Query vehicle freight recommendation if not returned
                    if (recCharge <= 0 && calcKm > 0) {
                        $.ajax({
                            url: "modal/vehicle_modal.php?action=calculateFreight",
                            type: "POST",
                            data: {
                                projectid: projectId,
                                km: calcKm,
                                total_qty: targetQty
                            },
                            dataType: "json",
                            success: function(vRes) {
                                let vRecCharge = parseFloat(vRes.recommended_charge || 0);
                                if (activeGroup) {
                                    activeGroup.baseFreight = vRecCharge;
                                    activeGroup.payableFreight = vRecCharge;
                                }
                                if (currentInvoiceData) {
                                    currentInvoiceData.original_base_freight = vRecCharge;
                                    currentInvoiceData.original_payable_freight = vRecCharge;
                                    $("#dinv_prev_freight_text").html(`Base: <b>₹ ${vRecCharge.toFixed(2)}</b> | Calculated from ${calcKm} km`);
                                }
                                recalculateAllInvoiceData();
                                $("#manualDeliveryAddressModal").modal("hide");

                                Swal.fire({
                                    title: "Delivery Address & Distance Saved!",
                                    html: `<b>Destination:</b> ${district}, ${state}<br><b>Approx Distance:</b> ${calcKm} KM<br><b>Base Freight:</b> ₹ ${vRecCharge.toFixed(2)}<br><small class="text-success fw-bold">Distance & freight updated dynamically!</small>`,
                                    icon: "success",
                                    timer: 2500,
                                    showConfirmButton: false
                                });
                            },
                            error: function() {
                                recalculateAllInvoiceData();
                                $("#manualDeliveryAddressModal").modal("hide");
                            }
                        });
                    } else {
                        if (activeGroup) {
                            activeGroup.baseFreight = recCharge;
                            activeGroup.payableFreight = recCharge;
                        }
                        if (currentInvoiceData) {
                            currentInvoiceData.original_base_freight = recCharge;
                            currentInvoiceData.original_payable_freight = recCharge;
                            $("#dinv_prev_freight_text").html(`Base: <b>₹ ${recCharge.toFixed(2)}</b> | Calculated from ${calcKm} km`);
                        }
                        recalculateAllInvoiceData();
                        $("#manualDeliveryAddressModal").modal("hide");

                        Swal.fire({
                            title: "Delivery Address & Distance Saved!",
                            html: `<b>Destination:</b> ${district}, ${state}<br><b>Approx Distance:</b> ${calcKm} KM<br><b>Base Freight:</b> ₹ ${recCharge.toFixed(2)}<br><small class="text-success fw-bold">Distance & freight updated dynamically!</small>`,
                            icon: "success",
                            timer: 2500,
                            showConfirmButton: false
                        });
                    }
                } else {
                    Swal.fire('Save Failed', res.message || 'Could not save manual address.', 'error');
                }
            },
            error: function(xhr) {
                $btnSubmit.prop("disabled", false).html('<i class="fa fa-check-circle me-1"></i> Save & Refresh Distance');
                Swal.fire('Error', 'An error occurred while saving delivery address.', 'error');
            }
        });
    });

    // -------------------------------------------------------------
    // DELIVERY TYPE (1. Self Pickup, 2. Farm Delivery, 3. Box Packing) HANDLERS
    // -------------------------------------------------------------
    $(document).on("change", ".dinv-delivery-type-radio", function() {
        let chosenMode = $(this).val();
        let psono = $("#dinv_psono").val() || (currentInvoiceData?.order?.psono || '');
        let clientId = $("#dinv_clientid").val() || (currentInvoiceData?.client_info?.client_id || '');
        let projectId = currentInvoiceData?.order?.projectid || $("#dinv_projectid").val() || CURRENT_APP_PROJECTID;

        let targetQty = 0;
        $(".dinv-product-card").each(function() {
            if ($(this).find(".dinv-item-include-checkbox").is(":checked")) {
                let idx = $(this).data("index");
                if (currentInvoiceData?.items && currentInvoiceData.items[idx]) {
                    let it = currentInvoiceData.items[idx];
                    targetQty += parseInt(it.allocate_ordqty || it.original_orderqty || 0);
                }
            }
        });
        if (targetQty <= 0) targetQty = parseInt(currentInvoiceData?.total_original_order_qty || 0);

        if (chosenMode === "pickup") {
            // Mode 1: Self Pickup By Farmer/Client
            $.ajax({
                url: "modal/finaldispatch_modal.php?action=ChangeDeliveryMode",
                type: "POST",
                data: {
                    orderid: psono,
                    clientid: clientId,
                    projectid: projectId,
                    pickuptype: 'pickup'
                },
                dataType: "json",
                success: function(res) {
                    if (currentInvoiceData) {
                        if (!currentInvoiceData.delivery_location) currentInvoiceData.delivery_location = {};
                        currentInvoiceData.delivery_location.pickuptype = 'pickup';
                        currentInvoiceData.delivery_location.distance = 0;
                        currentInvoiceData.delivery_location.totaldistance = 0;
                        currentInvoiceData.delivery_location.freedistance = 0;
                        currentInvoiceData.delivery_location.freightamt = 0;
                        currentInvoiceData.delivery_location.deliverycharges = 0;
                        currentInvoiceData.original_base_freight = 0;
                        currentInvoiceData.original_payable_freight = 0;
                        $("#dinv_prev_dest").text("Self Pickup (Ex-Nursery / Farm Gate)");
                        $("#dinv_prev_distance_text").text("Total: 0 km | Free: 0 km | Chargeable: 0 km");
                        $("#dinv_prev_freight_text").html("Base: <b>₹ 0.00</b> (Self Pickup)");
                    }
                    if (window._currentFreightGroups) {
                        window._currentFreightGroups.forEach(g => {
                            if (g.location) {
                                g.location.pickuptype = 'pickup';
                                g.location.distance = 0;
                                g.location.totaldistance = 0;
                                g.location.freedistance = 0;
                                g.location.freightamt = 0;
                            }
                            g.baseFreight = 0;
                            g.payableFreight = 0;
                            g.discPct = 0;
                        });
                    }
                    recalculateAllInvoiceData();
                    Swal.fire({
                        title: "1. Self Pickup Selected",
                        text: "Delivery mode updated to Self Pickup. No freight charges applicable.",
                        icon: "info",
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            });
        } else if (chosenMode === "boxpacking") {
            // Mode 3: Box Packing
            openBoxPackingModal(window._activeDeliveryGroupIndex || 0);
        } else {
            // Mode 2: Farm Delivery
            $.ajax({
                url: "modal/finaldispatch_modal.php?action=ChangeDeliveryMode",
                type: "POST",
                data: {
                    orderid: psono,
                    clientid: clientId,
                    projectid: projectId,
                    pickuptype: 'delivered'
                },
                dataType: "json",
                success: function(res) {
                    if (currentInvoiceData) {
                        if (!currentInvoiceData.delivery_location) currentInvoiceData.delivery_location = {};
                        currentInvoiceData.delivery_location.pickuptype = 'delivered';
                    }
                    if (window._currentFreightGroups) {
                        window._currentFreightGroups.forEach(g => {
                            if (g.location) {
                                g.location.pickuptype = 'delivered';
                            }
                        });
                    }
                    let km = parseFloat(currentInvoiceData?.delivery_location?.distance || 0);
                    if (km > 0) {
                        $.ajax({
                            url: "modal/vehicle_modal.php?action=calculateFreight",
                            type: "POST",
                            data: {
                                projectid: projectId,
                                km: km,
                                total_qty: targetQty
                            },
                            dataType: "json",
                            success: function(vRes) {
                                let recCharge = parseFloat(vRes.recommended_charge || 0);
                                if (currentInvoiceData) {
                                    currentInvoiceData.original_base_freight = recCharge;
                                    currentInvoiceData.original_payable_freight = recCharge;
                                    $("#dinv_prev_freight_text").html(`Base: <b>₹ ${recCharge.toFixed(2)}</b> | Calculated from ${km} km`);
                                }
                                recalculateAllInvoiceData();
                            },
                            error: function() {
                                recalculateAllInvoiceData();
                            }
                        });
                    } else {
                        recalculateAllInvoiceData();
                    }
                    Swal.fire({
                        title: "2. Farm Delivery Selected",
                        text: "Delivery mode updated to Farm Delivery (Company Vehicle Transport).",
                        icon: "success",
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            });
        }
    });

    // -------------------------------------------------------------
    // UNIFIED DELIVERY DATE EVENT HANDLERS
    // -------------------------------------------------------------
    $(document).on("click", ".dinv-quick-unify-date-btn, .dinv-row-unify-date-btn", function(e) {
        e.preventDefault();
        let selectedDate = $(this).attr("data-date") || $(this).data("date");
        if (selectedDate) {
            applyUnifiedDeliveryDate(selectedDate);
        }
    });

    $(document).on("click", "#dinv_apply_custom_unified_date_btn", function(e) {
        e.preventDefault();
        let customVal = $("#dinv_custom_unified_date").val();
        if (!customVal) {
            Swal.fire("Select Date", "Please select a valid date from the calendar input.", "warning");
            return;
        }
        applyUnifiedDeliveryDate(customVal);
    });

    $(document).on("click", ".dinv-reset-unify-date-btn", function(e) {
        e.preventDefault();
        resetUnifiedDeliveryDates();
    });

    // -------------------------------------------------------------
    // BOX PACKING MODAL & CALCULATION LOGIC
    // -------------------------------------------------------------
    function openBoxPackingModal(gIdx) {
        if (typeof gIdx === 'undefined' || isNaN(parseInt(gIdx))) gIdx = 0;
        window._activeDeliveryGroupIndex = gIdx;

        let activeGroup = (window._currentFreightGroups && window._currentFreightGroups[gIdx]) ? window._currentFreightGroups[gIdx] : null;
        let targetLoc = activeGroup ? activeGroup.location : (currentInvoiceData?.delivery_location || {});

        let psono = $("#dinv_psono").val() || (currentInvoiceData?.order?.psono || '');
        let clientId = $("#dinv_clientid").val() || (currentInvoiceData?.client_info?.client_id || '');
        let projectId = currentInvoiceData?.order?.projectid || $("#dinv_projectid").val() || CURRENT_APP_PROJECTID;

        let targetQty = activeGroup ? activeGroup.totalAllocQty : 0;
        if (targetQty <= 0) {
            $(".dinv-product-card").each(function() {
                if ($(this).find(".dinv-item-include-checkbox").is(":checked")) {
                    let idx = $(this).data("index");
                    if (currentInvoiceData?.items && currentInvoiceData.items[idx]) {
                        let it = currentInvoiceData.items[idx];
                        targetQty += parseInt(it.allocate_ordqty || it.original_orderqty || 0);
                    }
                }
            });
        }
        if (targetQty <= 0) targetQty = parseInt(currentInvoiceData?.total_original_order_qty || 1000);

        $("#bp_group_index").val(gIdx);
        $("#bp_psono").val(psono);
        $("#bp_clientid").val(clientId);
        $("#bp_projectid").val(projectId);
        $("#bp_total_qty").val(targetQty);

        let curState = targetLoc.dest_state || 'Chhattisgarh';
        let curDistrict = targetLoc.dest_district || '';
        let curCity = targetLoc.dest_city || '';
        let curTransport = targetLoc.transporttype || 'Bus';
        let plantsPerBox = parseInt(targetLoc.boxperpack || 1000);
        if (plantsPerBox <= 0) plantsPerBox = 1000;
        let chargePerBox = parseFloat(targetLoc.chargesperbox || 70.00);
        if (chargePerBox <= 0) chargePerBox = 70.00;
        let remark = targetLoc.remark || '';

        $("#bp_city").val(curCity);
        $("#bp_transport_type").val(curTransport);
        $("#bp_plants_per_box").val(plantsPerBox);
        $("#bp_charge_per_box").val(chargePerBox.toFixed(2));
        $("#bp_remark").val(remark);

        let calcBoxes = Math.ceil(targetQty / plantsPerBox);
        $("#bp_box_count").val(calcBoxes);
        $("#bp_total_freight").val((calcBoxes * chargePerBox).toFixed(2));

        // Load Box Packing Master States & Districts
        loadBoxPackingStates(curState, curDistrict);

        $("#boxPackingModal").modal("show");
    }

    function loadBoxPackingStates(selectedState, selectedDistrict) {
        const $stateSelect = $("#bp_state");
        let targetStateName = String(selectedState || "Chhattisgarh").trim().toLowerCase();

        let populate = function(states) {
            $stateSelect.empty().append('<option value="">-- Select State --</option>');
            let matched = false;
            states.forEach(function(st) {
                let stName = st.statename || '';
                let stId = st.stateid || stName;
                let isSelected = (stName.toLowerCase() === targetStateName || String(stId).toLowerCase() === targetStateName);
                if (isSelected) matched = true;
                $stateSelect.append(`<option value="${escapeHtml(stName)}" data-stateid="${escapeHtml(stId)}" ${isSelected ? 'selected' : ''}>${escapeHtml(stName)}</option>`);
            });
            if (!matched) {
                let cgOpt = $stateSelect.find("option").filter(function() { return $(this).text().trim().toLowerCase() === "chhattisgarh"; });
                if (cgOpt.length) cgOpt.prop("selected", true);
            }
            loadBoxPackingDistricts($stateSelect.val() || "Chhattisgarh", selectedDistrict);
        };

        if (cachedMasterStates && cachedMasterStates.length > 0) {
            populate(cachedMasterStates);
        } else {
            $.ajax({
                url: "modal/finaldispatch_modal.php?action=GetMasterStates",
                type: "GET",
                dataType: "json",
                success: function(res) {
                    if (res && res.status === "success" && Array.isArray(res.data)) {
                        cachedMasterStates = res.data;
                        populate(res.data);
                    } else {
                        $stateSelect.html('<option value="Chhattisgarh" selected>Chhattisgarh</option>');
                        loadBoxPackingDistricts("Chhattisgarh", selectedDistrict);
                    }
                },
                error: function() {
                    $stateSelect.html('<option value="Chhattisgarh" selected>Chhattisgarh</option>');
                    loadBoxPackingDistricts("Chhattisgarh", selectedDistrict);
                }
            });
        }
    }

    function loadBoxPackingDistricts(stateVal, selectedDistrict) {
        const $distSelect = $("#bp_district");
        if (!stateVal) {
            $distSelect.empty().append('<option value="">-- Select District --</option>');
            return;
        }

        let populate = function(districts) {
            $distSelect.empty().append('<option value="">-- Select District --</option>');
            let targetDist = String(selectedDistrict || "").trim().toLowerCase();
            districts.forEach(function(dt) {
                let dtName = dt.districtname || '';
                let dtId = dt.districtid || dtName;
                let isSelected = (dtName.toLowerCase() === targetDist || String(dtId).toLowerCase() === targetDist);
                $distSelect.append(`<option value="${escapeHtml(dtName)}" data-districtid="${escapeHtml(dtId)}" ${isSelected ? 'selected' : ''}>${escapeHtml(dtName)}</option>`);
            });
        };

        let cacheKey = String(stateVal).trim().toLowerCase();
        if (cachedDistrictsByState[cacheKey] && cachedDistrictsByState[cacheKey].length > 0) {
            populate(cachedDistrictsByState[cacheKey]);
        } else {
            $.ajax({
                url: "modal/finaldispatch_modal.php?action=GetMasterDistricts",
                type: "POST",
                data: { state: stateVal },
                dataType: "json",
                success: function(res) {
                    if (res && res.status === "success" && Array.isArray(res.data)) {
                        cachedDistrictsByState[cacheKey] = res.data;
                        populate(res.data);
                    } else {
                        $distSelect.empty().append('<option value="">-- Select District --</option>');
                    }
                }
            });
        }
    }

    // Box Packing state change
    $(document).on("change", "#bp_state", function() {
        loadBoxPackingDistricts($(this).val(), "");
    });

    // Box Packing live calculations
    $(document).on("input change", "#bp_plants_per_box, #bp_charge_per_box, #bp_box_count", function() {
        let totalQty = parseInt($("#bp_total_qty").val()) || 0;
        let plantsPerBox = parseInt($("#bp_plants_per_box").val()) || 1000;
        if (plantsPerBox <= 0) plantsPerBox = 1000;

        let boxes = Math.ceil(totalQty / plantsPerBox);
        if ($(this).attr("id") === "bp_box_count") {
            boxes = parseInt($("#bp_box_count").val()) || 1;
        } else {
            $("#bp_box_count").val(boxes);
        }

        let chargePerBox = parseFloat($("#bp_charge_per_box").val()) || 0;
        let totalFreight = Math.round((boxes * chargePerBox) * 100) / 100;
        $("#bp_total_freight").val(totalFreight.toFixed(2));
    });

    $(document).on("click", ".dinv-open-boxpacking-modal-btn", function(e) {
        e.preventDefault();
        let gIdx = $(this).data("group-index");
        openBoxPackingModal(gIdx);
    });

    // Box Packing Form Submit
    $(document).on("submit", "#boxPackingDeliveryForm", function(e) {
        e.preventDefault();

        let gIdx = parseInt($("#bp_group_index").val()) || 0;
        let psono = $("#bp_psono").val() || $("#dinv_psono").val() || (currentInvoiceData?.order?.psono || '');
        let clientId = $("#bp_clientid").val() || $("#dinv_clientid").val() || (currentInvoiceData?.client_info?.client_id || '');
        let projectId = $("#bp_projectid").val() || currentInvoiceData?.order?.projectid || CURRENT_APP_PROJECTID;

        let state = $("#bp_state").val() || 'Chhattisgarh';
        let district = $("#bp_district").val() || '';
        let city = $("#bp_city").val().trim();
        let transportType = $("#bp_transport_type").val() || 'Bus';
        let plantsPerBox = parseInt($("#bp_plants_per_box").val()) || 1000;
        let boxes = parseInt($("#bp_box_count").val()) || 1;
        let chargesPerBox = parseFloat($("#bp_charge_per_box").val()) || 0;
        let totalFreight = parseFloat($("#bp_total_freight").val()) || (boxes * chargesPerBox);
        let remark = $("#bp_remark").val().trim();
        let totalQty = parseInt($("#bp_total_qty").val()) || 0;

        if (!district) {
            Swal.fire('Validation Error', 'Please select a Destination District.', 'warning');
            return false;
        }
        if (!city) {
            Swal.fire('Validation Error', 'Please enter Destination City / Station.', 'warning');
            return false;
        }

        let $btnSubmit = $("#btn_save_box_packing");
        $btnSubmit.prop("disabled", true).html('<i class="fa fa-spinner fa-spin me-1"></i> Saving...');

        $.ajax({
            url: "modal/finaldispatch_modal.php?action=SaveBoxPackingDelivery",
            type: "POST",
            data: {
                orderid: psono,
                clientid: clientId,
                projectid: projectId,
                state: state,
                district: district,
                city: city,
                plants_per_box: plantsPerBox,
                boxes: boxes,
                charges_per_box: chargesPerBox,
                total_freight: totalFreight,
                transport_type: transportType,
                remark: remark,
                total_qty: totalQty
            },
            dataType: "json",
            success: function(res) {
                $btnSubmit.prop("disabled", false).html('<i class="fa fa-check-circle me-1"></i> Apply & Refresh Box Freight');
                if (res && res.status === "success") {
                    let activeGroup = (window._currentFreightGroups && window._currentFreightGroups[gIdx]) ? window._currentFreightGroups[gIdx] : null;
                    let fullAddr = res.data?.full_address || [city, district, state].filter(Boolean).join(', ');

                    if (activeGroup) {
                        activeGroup.isLocationAvailable = true;
                        activeGroup.location.is_location_available = true;
                        activeGroup.location.pickuptype = 'boxpacking';
                        activeGroup.location.deliveryaddress = fullAddr;
                        activeGroup.location.dest_state = state;
                        activeGroup.location.dest_district = district;
                        activeGroup.location.dest_city = city;
                        activeGroup.location.boxperpack = plantsPerBox;
                        activeGroup.location.noofbox = boxes;
                        activeGroup.location.chargesperbox = chargesPerBox;
                        activeGroup.location.freightamt = totalFreight;
                        activeGroup.location.deliverycharges = totalFreight;
                        activeGroup.location.transporttype = transportType;
                        activeGroup.location.remark = remark;
                        activeGroup.location.distance = 0;
                        activeGroup.location.totaldistance = 0;
                        activeGroup.baseFreight = totalFreight;
                        activeGroup.payableFreight = totalFreight;
                        activeGroup.discPct = 0;
                    }

                    if (gIdx === 0 || !currentInvoiceData?.delivery_location) {
                        if (currentInvoiceData) {
                            if (!currentInvoiceData.delivery_location) currentInvoiceData.delivery_location = {};
                            currentInvoiceData.delivery_location.is_location_available = true;
                            currentInvoiceData.delivery_location.pickuptype = 'boxpacking';
                            currentInvoiceData.delivery_location.deliveryaddress = fullAddr;
                            currentInvoiceData.delivery_location.dest_state = state;
                            currentInvoiceData.delivery_location.dest_district = district;
                            currentInvoiceData.delivery_location.dest_city = city;
                            currentInvoiceData.delivery_location.boxperpack = plantsPerBox;
                            currentInvoiceData.delivery_location.noofbox = boxes;
                            currentInvoiceData.delivery_location.chargesperbox = chargesPerBox;
                            currentInvoiceData.delivery_location.freightamt = totalFreight;
                            currentInvoiceData.delivery_location.deliverycharges = totalFreight;
                            currentInvoiceData.delivery_location.transporttype = transportType;
                            currentInvoiceData.delivery_location.remark = remark;
                            currentInvoiceData.delivery_location.distance = 0;
                            currentInvoiceData.delivery_location.totaldistance = 0;
                            currentInvoiceData.original_base_freight = totalFreight;
                            currentInvoiceData.original_payable_freight = totalFreight;

                            $("#dinv_prev_dest").text(`${district || '-'}, ${state || '-'} (Box Packing)`);
                            $("#dinv_prev_distance_text").text(`Total Boxes: ${boxes} | Rate/Box: ₹${chargesPerBox.toFixed(2)}`);
                            $("#dinv_prev_freight_text").html(`Box Freight: <b>₹ ${totalFreight.toFixed(2)}</b> (${boxes} boxes × ₹${chargesPerBox.toFixed(2)})`);
                            $("#dinv_delivery_mode_badge").attr('class', 'badge bg-warning-subtle text-dark border border-warning-subtle px-2.5 py-1').html('<i class="fa fa-box me-1"></i>Mode: 3. Box Packing');
                        }
                    }

                    recalculateAllInvoiceData();
                    $("#boxPackingModal").modal("hide");

                    Swal.fire({
                        title: "Box Packing Applied!",
                        html: `<b>Destination:</b> ${city}, ${district}<br><b>Boxes:</b> ${boxes} (${plantsPerBox} plants/box)<br><b>Rate:</b> ₹ ${chargesPerBox.toFixed(2)}/box<br><b>Total Box Freight:</b> ₹ ${totalFreight.toFixed(2)}`,
                        icon: "success",
                        timer: 2500,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire('Save Failed', res.message || 'Could not save box packing details.', 'error');
                }
            },
            error: function() {
                $btnSubmit.prop("disabled", false).html('<i class="fa fa-check-circle me-1"></i> Apply & Refresh Box Freight');
                Swal.fire('Error', 'An error occurred while saving box packing delivery.', 'error');
            }
        });
    });

    // Recalculate Grand Total from Taxable + Tax + Freight & Update Payment Balance
    function recalculateGrandTotalOnly() {
        let totalTaxable = parseFloat($("#dinv_total_amount").val()) || 0;
        let totalTax = parseFloat($("#dinv_tax_amount").val()) || 0;
        let freight = parseFloat($("#dinv_freight_charges").val()) || 0;
        let grandTotal = Math.round((totalTaxable + totalTax + freight) * 100) / 100;

        $("#dinv_grand_total").val(grandTotal.toFixed(2));
        $("#dinv_grand_total_display").text(`₹ ${grandTotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
        
        let isBoxPackingBreakdown = $("#dinv_enable_box_packing").is(":checked");
        let boxCountBreakdown = isBoxPackingBreakdown ? (parseInt($("#dinv_box_count").val()) || 0) : 0;
        let boxTotalBreakdown = isBoxPackingBreakdown ? (boxCountBreakdown * (parseFloat($("#dinv_box_rate").val()) || 100.00)) : 0;
        let breakdownHtml = `Taxable Amount: <b>₹ ${totalTaxable.toFixed(2)}</b> + Tax: <b>₹ ${totalTax.toFixed(2)}</b> + Freight: <b>₹ ${freight.toFixed(2)}</b>`;
        if (boxTotalBreakdown > 0) {
            breakdownHtml += ` <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1 fw-bold" style="font-size: 11px;">(Incl. Box Packing: <b>₹ ${boxTotalBreakdown.toFixed(2)}</b> for ${boxCountBreakdown} boxes)</span>`;
        }
        $("#dinv_grand_total_breakdown").html(breakdownHtml);

        // Render payment history & balance amount against provisional order
        renderDispatchPaymentSection(currentInvoiceData);
    }

    /**
     * Render Payment History and Balance Details against Provisional Order
     */
    function renderDispatchPaymentSection(d) {
        if (!d) return;

        let ord = d.order || {};
        let psono = ord.psono || $("#dinv_psono").val() || '';
        let grandTotal = parseFloat($("#dinv_grand_total").val()) || 0;
        let totalPaid = 0;
        let verifiedPaid = 0;
        let unverifiedAmount = 0;
        let unverifiedCount = 0;
        let payments = Array.isArray(d.payment_history) ? d.payment_history : [];

        // Calculate verified vs unverified totals
        let totalDiscount = 0;
        let totalRoundoff = 0;
        if (payments.length > 0) {
            payments.forEach(p => {
                let amt = parseFloat(p.advanceamount || 0);
                let disc = parseFloat(p.otherdiscount || 0);
                let roundoff = parseFloat(p.roundoffamount || 0);
                totalDiscount += disc;
                totalRoundoff += roundoff;
                let effectiveSettled = amt + disc;
                totalPaid += effectiveSettled;
                let ver = (p.verification || '').toString().toLowerCase().trim();
                if (ver === 'verified') {
                    verifiedPaid += effectiveSettled;
                } else {
                    unverifiedAmount += amt;
                    unverifiedCount++;
                }
            });
            d.total_paid_amount = totalPaid;
        } else {
            totalPaid = parseFloat(d.total_paid_amount || 0);
        }

        totalPaid = Math.round(totalPaid * 100) / 100;
        verifiedPaid = Math.round(verifiedPaid * 100) / 100;
        unverifiedAmount = Math.round(unverifiedAmount * 100) / 100;
        let hasUnverified = (unverifiedCount > 0 && unverifiedAmount > 0.01);
        let balance = Math.round(Math.max(0, grandTotal - totalPaid) * 100) / 100;

        // Update Header Badges
        $("#dinv_pay_psono_badge").text(psono || 'N/A');
        $("#dinv_pay_psono_display").text(psono || 'N/A');

        // Update 3 Metrics
        $("#dinv_metric_grand_total").text(`₹ ${grandTotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
        
        let totalSettledText = `₹ ${totalPaid.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        if (totalDiscount > 0) {
            totalSettledText += `<div class="small fw-semibold text-info mt-0.5" style="font-size: 11px;"><i class="fa fa-tag me-1"></i>Discount: ₹ ${totalDiscount.toFixed(2)}</div>`;
        }
        if (totalRoundoff > 0) {
            totalSettledText += `<div class="small fw-semibold text-secondary mt-0.5" style="font-size: 11px;"><i class="fa fa-adjust me-1"></i>Round-off: +₹ ${totalRoundoff.toFixed(2)}</div>`;
        }

        if (hasUnverified) {
            $("#dinv_metric_total_received").html(`${totalSettledText}<div class="small fw-semibold text-danger mt-0.5" style="font-size: 11px;"><i class="fa fa-clock me-1"></i>Unverified: ₹ ${unverifiedAmount.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</div>`);
        } else {
            $("#dinv_metric_total_received").html(totalSettledText);
        }

        // Unverified Alert Container
        if (hasUnverified) {
            $("#dinv_unverified_alert_container").html(`
                <div class="alert alert-warning border border-warning shadow-xs py-2.5 px-3 mb-0 d-flex align-items-center justify-content-between flex-wrap gap-2" style="border-radius: 8px; background-color: #fffbeb;">
                    <div class="d-flex align-items-center">
                        <i class="fa fa-exclamation-triangle text-danger fs-4 me-3"></i>
                        <div>
                            <div class="fw-bold text-dark fs-6 mb-0.5"><i class="fa fa-lock text-danger me-1"></i> Account Department Verification Required</div>
                            <div class="text-dark small">
                                Unverified payment amount of <b class="text-danger">₹ ${unverifiedAmount.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</b> (${unverifiedCount} transaction${unverifiedCount > 1 ? 's' : ''}) detected. 
                                <span class="fw-bold text-danger">Please contact the Account Department to verify the unverified amount.</span> All payments must be verified before proceeding to save and dispatch the invoice.
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-outline-dark fw-bold px-3 py-1.5 dinv-refresh-payments-btn">
                            <i class="fa fa-sync-alt me-1"></i> Re-check Status
                        </button>
                    </div>
                </div>
            `).show();
        } else {
            $("#dinv_unverified_alert_container").empty().hide();
        }

        if (hasUnverified) {
            // STRICT GUARD: IF UNVERIFIED TRANSACTIONS EXIST, SAVE BUTTON MUST BE HIDDEN
            window._isDispatchInvoiceReadyToSave = false;
            $("#confirmGenerateDispatchInvoiceBtn").hide();
            $(".dinv-top-btn-confirm-save").hide();
            if (window._currentDinvStep === 3) {
                $(".dinv-top-btn-preview-pdf").show();
            }
            $("#previewDispatchInvoicePdfBtn")
                .removeClass("btn-outline-primary")
                .addClass("btn-info text-white")
                .html('<i class="fa fa-file-pdf me-1"></i> Preview Invoice (PDF)');

            if (balance <= 0) {
                $("#dinv_balance_label").text("Balance Status (Unverified Payment)");
                $("#dinv_balance_card_container")
                    .removeClass("bg-success-subtle border-success-subtle bg-danger-subtle border-danger-subtle text-danger text-success")
                    .addClass("bg-warning-subtle border-warning-subtle text-dark");
                $("#dinv_metric_balance_amount")
                    .removeClass("text-success text-danger")
                    .addClass("text-warning-emphasis")
                    .html('₹ 0.00 <span class="d-block text-danger fw-semibold" style="font-size: 11px;"><i class="fa fa-clock me-1"></i>Awaiting Accounts Verification</span>');
                $("#dinv_btn_open_receive_payment")
                    .removeClass("btn-primary btn-success")
                    .addClass("btn-warning text-dark fw-bold")
                    .html(`<i class="fa fa-exclamation-triangle text-danger me-1"></i> Awaiting Accounts Verification (₹ ${unverifiedAmount.toFixed(2)})`);
            } else {
                $("#dinv_balance_label").text("Balance Amount Due");
                $("#dinv_balance_card_container")
                    .removeClass("bg-success-subtle border-success-subtle text-success bg-warning-subtle border-warning-subtle")
                    .addClass("bg-danger-subtle border-danger-subtle text-danger");
                $("#dinv_metric_balance_amount")
                    .removeClass("text-success text-warning-emphasis")
                    .addClass("text-danger")
                    .text(`₹ ${balance.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
                $("#dinv_btn_open_receive_payment")
                    .removeClass("btn-success btn-warning text-dark")
                    .addClass("btn-primary")
                    .html(`<i class="fa fa-hand-holding-usd me-1"></i> Receive Balance (₹ ${balance.toFixed(2)})`);
            }

            $("#dinv_footer_balance_status").html(`
                <span class="badge bg-warning text-dark border border-warning px-2.5 py-1.5 font-monospace fw-bold shadow-xs">
                    <i class="fa fa-exclamation-triangle text-danger me-1"></i> 
                    Unverified Payment: ₹ ${unverifiedAmount.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} (${unverifiedCount} txn) &mdash; Contact Account Department to verify
                </span>
            `);
        } else if (balance <= 0) {
            // FULLY PAID AND 100% VERIFIED -> READY TO SAVE INVOICE
            window._isDispatchInvoiceReadyToSave = true;
            $("#dinv_balance_label").text("Balance Amount (Fully Paid & Verified)");
            $("#dinv_balance_card_container")
                .removeClass("bg-danger-subtle border-danger-subtle bg-warning-subtle border-warning-subtle text-danger text-warning-emphasis")
                .addClass("bg-success-subtle border-success-subtle text-success");
            $("#dinv_metric_balance_amount")
                .removeClass("text-danger text-warning-emphasis")
                .addClass("text-success")
                .text("₹ 0.00 (Clear)");
            $("#dinv_btn_open_receive_payment")
                .removeClass("btn-primary btn-warning text-dark")
                .addClass("btn-success")
                .html('<i class="fa fa-check-circle me-1"></i> Paid in Full & Verified');

            // Dispatch invoice can now be generated and saved (visible if currently on Step 3)
            if (window._currentDinvStep === 3) {
                $("#confirmGenerateDispatchInvoiceBtn").show();
                $(".dinv-top-btn-confirm-save").show();
                $(".dinv-top-btn-preview-pdf").hide();
            } else {
                $("#confirmGenerateDispatchInvoiceBtn").hide();
                $(".dinv-top-btn-confirm-save").hide();
                $(".dinv-top-btn-preview-pdf").hide();
            }
            $("#previewDispatchInvoicePdfBtn")
                .removeClass("btn-info text-white")
                .addClass("btn-outline-primary")
                .html('<i class="fa fa-file-pdf me-1"></i> Preview Invoice (PDF)');
            $("#dinv_footer_balance_status").html('<span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 font-monospace"><i class="fa fa-check-circle me-1"></i> All Payments Verified (₹ 0.00 Due) - Ready to Save</span>');
        } else {
            // PENDING BALANCE DUE
            window._isDispatchInvoiceReadyToSave = false;
            $("#dinv_balance_label").text("Balance Amount Due");
            $("#dinv_balance_card_container")
                .removeClass("bg-success-subtle border-success-subtle text-success bg-warning-subtle border-warning-subtle")
                .addClass("bg-danger-subtle border-danger-subtle text-danger");
            $("#dinv_metric_balance_amount")
                .removeClass("text-success text-warning-emphasis")
                .addClass("text-danger")
                .text(`₹ ${balance.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
            $("#dinv_btn_open_receive_payment")
                .removeClass("btn-success btn-warning text-dark")
                .addClass("btn-primary")
                .html(`<i class="fa fa-hand-holding-usd me-1"></i> Receive Balance (₹ ${balance.toFixed(2)})`);

            // When pending balance > 0, ONLY Preview Invoice is shown, Save Dispatch Invoice is hidden!
            $("#confirmGenerateDispatchInvoiceBtn").hide();
            $(".dinv-top-btn-confirm-save").hide();
            if (window._currentDinvStep === 3) {
                $(".dinv-top-btn-preview-pdf").show();
            }
            $("#previewDispatchInvoicePdfBtn")
                .removeClass("btn-outline-primary")
                .addClass("btn-info text-white")
                .html('<i class="fa fa-file-pdf me-1"></i> Preview Invoice (PDF)');
            $("#dinv_footer_balance_status").html(`<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1.5 font-monospace"><i class="fa fa-exclamation-circle me-1"></i> Pending Balance: ₹ ${balance.toFixed(2)} (Receive payment to enable Save Dispatch Invoice)</span>`);
        }

        // Render Table Body
        const $tbody = $("#dinvPaymentHistoryTable tbody");
        $tbody.empty();

        if (payments.length === 0) {
            $tbody.append('<tr><td colspan="9" class="text-center py-3 text-muted"><i class="fa fa-info-circle me-1 text-primary"></i> No advance / payment transactions recorded for this provisional order yet.</td></tr>');
            return;
        }

        payments.forEach((p, idx) => {
            let pDate = p.formatted_payment_date || (p.paymentdate ? new Date(p.paymentdate).toLocaleDateString('en-GB') : '-');
            let pAmt = parseFloat(p.advanceamount || 0);
            let pSource = p.sourceofpayment || 'Cash';
            let sourceBadge = '';
            if (pSource.toLowerCase().includes('cash')) {
                sourceBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="fa fa-money-bill-wave me-1"></i>Cash</span>';
            } else if (pSource.toLowerCase().includes('cheque')) {
                sourceBadge = '<span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-1"><i class="fa fa-money-check me-1"></i>Cheque</span>';
            } else {
                sourceBadge = '<span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1"><i class="fa fa-mobile-alt me-1"></i>' + escapeHtml(pSource) + '</span>';
            }

            let isVer = (p.verification || '').toString().toLowerCase().trim() === 'verified';
            let verStatus = isVer
                ? '<span class="badge bg-success text-white px-2 py-1"><i class="fa fa-check-circle me-1"></i>Verified</span>'
                : '<span class="badge bg-warning text-dark px-2 py-1"><i class="fa fa-clock me-1"></i>Unverified</span>';

            let receiptLink = '-';
            if (p.receiptpath) {
                let rPath = p.receiptpath;
                if (!rPath.startsWith('http') && !rPath.startsWith('../') && !rPath.startsWith('/')) {
                    rPath = '../' + rPath;
                }
                receiptLink = `<a href="${escapeHtml(rPath)}" target="_blank" class="btn btn-xs btn-outline-primary py-0.5 px-2 shadow-2xs" style="font-size: 11px;" title="View Receipt Document"><i class="fa fa-file-image me-1"></i>View</a>`;
            }

            let recInfo = escapeHtml(p.receivedby || '-');
            if (p.receivedlocation) {
                recInfo += `<br><span class="text-muted small" style="font-size: 10.5px;"><i class="fa fa-map-marker-alt text-muted me-1"></i>${escapeHtml(p.receivedlocation)}</span>`;
            }

            let pDisc = parseFloat(p.otherdiscount || 0);
            let pRound = parseFloat(p.roundoffamount || 0);
            let amtBadges = '';
            if (pDisc > 0) {
                amtBadges += `<div class="badge bg-info-subtle text-info border border-info-subtle px-1.5 py-0.5 mt-0.5 d-block text-end" style="font-size: 10px;" title="Other Discount applied"><i class="fa fa-tag me-1"></i>Discount: ₹ ${pDisc.toFixed(2)}</div>`;
            }
            if (pRound > 0) {
                amtBadges += `<div class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-1.5 py-0.5 mt-0.5 d-block text-end" style="font-size: 10px;" title="Round-off Amount"><i class="fa fa-adjust me-1"></i>Round-off: +₹ ${pRound.toFixed(2)}</div>`;
            }

            let rowBgStyle = !isVer ? 'style="background-color: #fffdf5;"' : '';

            let tr = `
                <tr ${rowBgStyle}>
                    <td class="text-center fw-bold text-muted">${idx + 1}</td>
                    <td class="text-center fw-semibold text-dark font-monospace">${escapeHtml(pDate)}</td>
                    <td class="text-end font-monospace">
                        <div class="fw-bold ${isVer ? 'text-success' : 'text-danger'}">₹ ${pAmt.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</div>
                        ${amtBadges}
                    </td>
                    <td class="text-center">${sourceBadge}</td>
                    <td>
                        <div class="fw-semibold text-dark font-monospace" style="font-size: 0.8rem;">${escapeHtml(p.utrnumber || p.paymentref_no || '-')}</div>
                        ${p.remarks ? `<small class="text-muted" style="font-size: 10px;">${escapeHtml(p.remarks)}</small>` : ''}
                    </td>
                    <td>${recInfo}</td>
                    <td class="text-center">${receiptLink}</td>
                    <td class="text-center">${verStatus}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-xs btn-outline-danger py-0.5 px-2 dinv-remove-payment-btn" 
                                data-sno="${p.sno}" 
                                data-amt="${pAmt}" 
                                data-utr="${escapeHtml(p.utrnumber || p.paymentref_no || '')}" 
                                data-date="${escapeHtml(pDate)}" 
                                title="Cancel / Remove Wrong Payment">
                            <i class="fa fa-trash-alt me-1"></i>Cancel
                        </button>
                    </td>
                </tr>
            `;
            $tbody.append(tr);
        });
    }

    // Polling interval timer reference for live payment confirmation checking
    window.paymentConfirmationPollingInterval = null;

    /**
     * API function: Check payment status confirmation dynamically
     * Queries CheckPaymentConfirmationStatus backend API to verify if any payments are unconfirmed
     * or if all payments are confirmed/verified with zero balance.
     */
    window.checkOrderPaymentConfirmationStatus = function(psono, sono, clientId, grandTotal, callback, isSilent = false) {
        if (!psono && !sono) {
            psono = currentInvoiceData?.order?.psono || $("#dinv_psono").val() || '';
            sono = currentInvoiceData?.order?.sono || $("#dinv_sono").val() || '';
            clientId = currentInvoiceData?.order?.clientid || $("#dinv_clientid").val() || '';
        }

        if (grandTotal === undefined || grandTotal === null) {
            grandTotal = parseFloat($("#dinv_grand_total").val()) || 0;
        }

        let projId = currentInvoiceData?.order?.projectid || CURRENT_APP_PROJECTID;

        $.ajax({
            url: "modal/finaldispatch_modal.php?action=CheckPaymentConfirmationStatus",
            type: "POST",
            data: {
                projectid: projId,
                psono: psono,
                sono: sono,
                clientid: clientId,
                grand_total: grandTotal
            },
            dataType: "json",
            success: function(res) {
                if (res && res.status === "success" && res.data) {
                    let d = res.data;
                    if (currentInvoiceData) {
                        currentInvoiceData.payment_history = d.payment_history || [];
                        currentInvoiceData.total_paid_amount = parseFloat(d.total_paid_amount || 0);
                    }
                    renderDispatchPaymentSection(currentInvoiceData);
                }
                if (typeof callback === 'function') callback(res);
            },
            error: function(err) {
                if (!isSilent) console.error("Error checking payment confirmation status:", err);
                if (typeof callback === 'function') callback(null);
            }
        });
    };

    /**
     * Refresh payment history dynamically via AJAX without reloading window
     */
    window.refreshDispatchPaymentHistory = function(psono, sono, clientId, callback) {
        window.checkOrderPaymentConfirmationStatus(psono, sono, clientId, null, callback, false);
    };

    /**
     * Start live auto-polling to check payment confirmation status while invoice modal is open
     */
    function startPaymentConfirmationPolling() {
        stopPaymentConfirmationPolling();
        window.paymentConfirmationPollingInterval = setInterval(function() {
            if ($("#prepareDispatchInvoiceModal").is(":visible") && currentInvoiceData) {
                let psono = currentInvoiceData?.order?.psono || $("#dinv_psono").val() || '';
                let sono = currentInvoiceData?.order?.sono || $("#dinv_sono").val() || '';
                let clientId = currentInvoiceData?.order?.clientid || $("#dinv_clientid").val() || '';
                let grandTotal = parseFloat($("#dinv_grand_total").val()) || 0;
                window.checkOrderPaymentConfirmationStatus(psono, sono, clientId, grandTotal, null, true);
            } else {
                stopPaymentConfirmationPolling();
            }
        }, 4500);
    }

    /**
     * Stop live auto-polling
     */
    function stopPaymentConfirmationPolling() {
        if (window.paymentConfirmationPollingInterval) {
            clearInterval(window.paymentConfirmationPollingInterval);
            window.paymentConfirmationPollingInterval = null;
        }
    }

    // Modal life-cycle events to start/stop polling automatically
    $(document).on("shown.bs.modal", "#prepareDispatchInvoiceModal", function() {
        startPaymentConfirmationPolling();
        window.goToDinvStep(1);
    });

    $(document).on("hidden.bs.modal", "#prepareDispatchInvoiceModal", function() {
        stopPaymentConfirmationPolling();
    });

    // =============================================================
    // 3-STEP WIZARD CONTROLLER FOR PREPARE DISPATCH INVOICE MODAL
    // =============================================================
    window._currentDinvStep = 1;

    window.goToDinvStep = function(step) {
        step = parseInt(step) || 1;
        if (step < 1) step = 1;
        if (step > 3) step = 3;

        // Validation when advancing beyond Step 1
        if (step > 1) {
            let totalChecked = $(".dinv-product-card").filter(function() {
                return $(this).find(".dinv-item-include-checkbox").is(":checked");
            }).length;

            if (totalChecked === 0) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No Products Selected',
                        text: 'Please select at least one product item to include in the invoice before proceeding.'
                    });
                } else {
                    alert('Please select at least one product item to include in the invoice before proceeding.');
                }
                return false;
            }

            // Check if any included product has billable quantity <= 0
            let hasZeroQty = false;
            $(".dinv-product-card").each(function() {
                let card = $(this);
                if (card.find(".dinv-item-include-checkbox").is(":checked")) {
                    let allocQty = parseInt(card.find(".dinv-item-alloc-qty").val()) || 0;
                    if (allocQty <= 0) {
                        hasZeroQty = true;
                    }
                }
            });

            if (hasZeroQty) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Zero Allocated Quantity',
                        text: 'Please ensure allocated quantity is greater than zero for all included products.'
                    });
                } else {
                    alert('Please ensure allocated quantity is greater than zero for all included products.');
                }
                return false;
            }
        }

        if (step === 2) {
            // Render freight table fresh
            renderDispatchFreightTable();
            $("#dinvModalSubTitle").text("Step 2 of 3: Freight Charge Details (Grouped by Address & Date)");
        } else if (step === 3) {
            // Recalculate freight totals, grand total & payment reconciliation
            updateFreightTableFootersAndGrandTotal();
            recalculateGrandTotalOnly();
            $("#dinvModalSubTitle").text("Step 3 of 3: Dispatch Invoice Final Grand Total & Payment Reconciliation");
        } else {
            $("#dinvModalSubTitle").text("Step 1 of 3: Client & Invoice Information, Product Items & Shifting Allocation");
        }

        // Toggle Step Panes
        $(".dinv-step-pane").hide();
        $(`#dinv_step_${step}`).show();

        // Update Wizard Stepper Header Tabs
        $(".dinv-step-item").removeClass("active completed");
        for (let s = 1; s <= 3; s++) {
            let $item = $(`.dinv-step-item[data-step="${s}"]`);
            let $line = $(`.dinv-step-line[data-step-line="${s}"]`);
            if (s < step) {
                $item.addClass("completed");
                $item.find(".dinv-step-circle").html('<i class="fa fa-check text-white" style="font-size:12px;"></i>');
                if ($line.length) $line.addClass("completed");
            } else if (s === step) {
                $item.addClass("active");
                $item.find(".dinv-step-circle").text(s);
                if ($line.length) $line.removeClass("completed");
            } else {
                $item.find(".dinv-step-circle").text(s);
                if ($line.length) $line.removeClass("completed");
            }
        }

        // Toggle Footer Navigation Buttons
        $(".dinv-footer-step-btn").hide();
        $(`.dinv-footer-step-${step}`).show();

        // Toggle Top Mobile Navigation Buttons
        $(".dinv-top-step-btn").removeClass("active");
        $(`.dinv-top-step-${step}`).addClass("active");

        // Manage Save button specifically for Step 3
        if (step === 3) {
            if (window._isDispatchInvoiceReadyToSave) {
                $("#confirmGenerateDispatchInvoiceBtn").show();
                $(".dinv-top-btn-confirm-save").show();
                $(".dinv-top-btn-preview-pdf").hide();
            } else {
                $("#confirmGenerateDispatchInvoiceBtn").hide();
                $(".dinv-top-btn-confirm-save").hide();
                $(".dinv-top-btn-preview-pdf").show();
            }
        } else {
            $("#confirmGenerateDispatchInvoiceBtn").hide();
            $(".dinv-top-btn-confirm-save").hide();
            $(".dinv-top-btn-preview-pdf").hide();
        }

        // Scroll modal content to top smoothly
        $("#prepareDispatchInvoiceModalBody").scrollTop(0);

        window._currentDinvStep = step;
        return true;
    };

    // Helper: Save Step 1 Products and advance to Step 2
    function saveStep1ProductsAndProceed() {
        let totalChecked = $(".dinv-product-card").filter(function() {
            return $(this).find(".dinv-item-include-checkbox").is(":checked");
        }).length;

        if (totalChecked === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Products Selected',
                text: 'Please select at least one product item to include in the invoice before proceeding.'
            });
            return;
        }

        let totalOrderQty = 0;
        let totalAllocQty = 0;
        $(".dinv-product-card").each(function() {
            let card = $(this);
            if (card.find(".dinv-item-include-checkbox").is(":checked")) {
                totalOrderQty += parseInt(card.find(".dinv-item-order-qty").val()) || 0;
                totalAllocQty += parseInt(card.find(".dinv-item-alloc-qty").val()) || 0;
            }
        });

        if (totalOrderQty <= 0 || totalAllocQty <= 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Cannot Proceed to Freight Details',
                html: '<b>Total Order Qty is 0 Plants.</b><br>Please select and include at least one valid product item with quantity before proceeding to Freight Details.',
                confirmButtonColor: '#dc2626',
                confirmButtonText: 'Understood'
            });
            return;
        }

        let hasZeroQty = false;
        let items = [];

        $(".dinv-product-card").each(function() {
            let card = $(this);
            let isIncluded = card.find(".dinv-item-include-checkbox").is(":checked");
            let soiId = parseInt(card.find(".dinv-item-soi-id").val()) || 0;
            let productId = card.find(".dinv-item-product-id").val() || '';
            let productName = card.find(".dinv-item-product-name").val() || '';
            let stockOrderNo = card.find(".dinv-item-stockorder-no").val() || '';
            let orderQty = parseInt(card.find(".dinv-item-order-qty").val()) || 0;
            let allocQty = parseInt(card.find(".dinv-item-alloc-qty").val()) || 0;
            let healthyPlant = parseInt(card.find(".dinv-item-healthy").val()) || 0;
            let unhealthyPlant = parseInt(card.find(".dinv-item-unhealthy").val()) || 0;
            let isShifted = card.find(".dinv-item-shifted-radio:checked").val() === "yes" ? "yes" : "no";
            let extraPct = isShifted === "yes" ? 0 : (parseFloat(card.find(".dinv-item-extra-pct").val()) || 0);
            let extraQty = isShifted === "yes" ? (parseInt(card.find(".dinv-item-extra-qty").val()) || Math.ceil(allocQty / 125)) : (parseInt(card.find(".dinv-item-extra-qty").val()) || 0);
            let rate = parseFloat(card.find(".dinv-item-rate").val()) || 0;
            let discPct = parseFloat(card.find(".dinv-item-disc-pct").val()) || 0;
            let discAmt = parseFloat(card.find(".dinv-item-disc-amt").val()) || 0;
            let taxable = parseFloat(card.find(".dinv-item-taxable").val()) || 0;
            let cgstPct = parseFloat(card.find(".dinv-item-cgst-pct").val()) || 0;
            let cgstAmt = parseFloat(card.find(".dinv-item-cgst-amt").val()) || 0;
            let sgstPct = parseFloat(card.find(".dinv-item-sgst-pct").val()) || 0;
            let sgstAmt = parseFloat(card.find(".dinv-item-sgst-amt").val()) || 0;
            let igstPct = parseFloat(card.find(".dinv-item-igst-pct").val()) || 0;
            let igstAmt = parseFloat(card.find(".dinv-item-igst-amt").val()) || 0;
            let taxAmt = parseFloat(card.find(".dinv-item-tax-amt").val()) || 0;
            let itemSubtotal = parseFloat(card.find(".dinv-item-subtotal").val()) || 0;
            let itemDelivDate = card.find(".dinv-item-deliverydate").val() || '';
            let itemFormattedDelivDate = card.find(".dinv-item-formatted-delivery-date").val() || '';

            if (isIncluded && allocQty <= 0) {
                hasZeroQty = true;
            }

            items.push({
                soi_id: soiId,
                product_id: productId,
                product_name: productName,
                stockorder_no: stockOrderNo,
                order_qty: orderQty,
                allocate_ordqty: allocQty,
                healthyplant: healthyPlant,
                unhealthyplant: unhealthyPlant,
                isshifted: isShifted,
                percentextrashifting: extraPct,
                shiftingqty: extraQty,
                rate: rate,
                discount_percent: discPct,
                discount_amount: discAmt,
                taxableamount: taxable,
                cgst_percent: cgstPct,
                cgst_amount: cgstAmt,
                sgst_percent: sgstPct,
                sgst_amount: sgstAmt,
                igst_percent: igstPct,
                igst_amount: igstAmt,
                tax_amount: taxAmt,
                amount: itemSubtotal,
                deliverydate: itemDelivDate,
                formatted_delivery_date: itemFormattedDelivDate,
                is_included: isIncluded ? 1 : 0
            });
        });

        if (hasZeroQty) {
            Swal.fire({
                icon: 'warning',
                title: 'Zero Allocated Quantity',
                text: 'Please ensure allocated quantity is greater than zero for all included products.'
            });
            return;
        }

        let $btn = $("#dinv_btn_next_to_2");
        let $btnTop = $(".dinv-top-btn-next-to-2");
        $btn.prop("disabled", true).html(`<i class="fa fa-spinner fa-spin me-1"></i> Saving Changes...`);
        $btnTop.prop("disabled", true).html(`<i class="fa fa-spinner fa-spin me-1"></i> Saving...`);

        $.ajax({
            url: "modal/finaldispatch_modal.php?action=UpdateDispatchInvoiceStep1Products",
            type: "POST",
            data: {
                projectid: $("#dinv_projectid").val() || CURRENT_APP_PROJECTID,
                psono: $("#dinv_psono").val() || '',
                sono: $("#dinv_sono").val() || '',
                clientname: $("#dinv_clientname").val() || '',
                emailid: $("#dinv_emailid").val() || '',
                mobileno: $("#dinv_mobileno").val() || '',
                address: $("#dinv_address").val() || '',
                delivery_address: $("#dinv_delivery_address").val() || '',
                remarks: $("#dinv_remarks").val() || '',
                payment_terms: $("#dinv_payment_terms").val() || '',
                loginid: $("body").data("loginid") || '',
                items_json: JSON.stringify(items)
            },
            dataType: "json",
            success: function(res) {
                $btn.prop("disabled", false).html(`Next: Freight Details <i class="fa fa-arrow-right ms-1"></i>`);
                $btnTop.prop("disabled", false).html(`Next: Freight Details <i class="fa fa-arrow-right ms-1"></i>`);
                if (res.status === "success") {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Product items updated successfully',
                        showConfirmButton: false,
                        timer: 1500
                    });
                    window.goToDinvStep(2);
                } else {
                    Swal.fire("Error", res.message || "Failed to update product items.", "error");
                }
            },
            error: function(xhr) {
                $btn.prop("disabled", false).html(`Next: Freight Details <i class="fa fa-arrow-right ms-1"></i>`);
                $btnTop.prop("disabled", false).html(`Next: Freight Details <i class="fa fa-arrow-right ms-1"></i>`);
                let errMsg = "Server error while updating product items.";
                if (xhr?.responseJSON?.message) errMsg = xhr.responseJSON.message;
                Swal.fire("Error", errMsg, "error");
            }
        });
    }

    // Helper: Save Step 2 Freight and advance to Step 3
    function saveStep2FreightAndProceed() {
        let activeDelType = $("input[name='dinv_delivery_type']:checked").val() || 'delivered';
        let grandFreight = parseFloat($("#dinv_freight_charges").val()) || 0;
        let $firstRow = $("#dinvFreightGroupTable tbody tr.dinv-group-row").first();

        // Check if delivery address is missing when mode is delivered
        if (activeDelType === 'delivered') {
            let missingAddressGroups = (window._currentFreightGroups || []).filter(g => {
                let pType = g.location?.pickuptype || 'delivered';
                return pType === 'delivered' && (!g.isLocationAvailable || (!g.location?.deliveryaddress && !g.location?.dest_city && !g.location?.dest_district));
            });
            if (missingAddressGroups.length > 0) {
                Swal.fire({
                    title: "Missing Delivery Address",
                    html: "One or more items do not have a delivery address in <b>krf_deliveryaddress_location</b>.<br><br>Please click <b>Update Delivery Address & Distance</b> in the table grid to update delivery location on the map first.",
                    icon: "warning",
                    confirmButtonColor: "#dc2626",
                    confirmButtonText: "<i class='fa fa-map-marker-alt me-1'></i> Understood"
                });
                return;
            }
        }

        let driverMobile = $firstRow.find(".dinv_f_driver_mobile").val() || (window._currentFreightGroups?.[0]?.location?.drivercontactno || '');
        if (driverMobile && !/^[6-9]\d{9}$/.test(driverMobile)) {
            Swal.fire("Invalid Mobile Number", "Please enter a valid 10-digit driver mobile number.", "warning");
            return;
        }

        let $btn = $("#dinv_btn_next_to_3");
        let $btnTop = $(".dinv-top-btn-next-to-3");
        $btn.prop("disabled", true).html(`<i class="fa fa-spinner fa-spin me-1"></i> Saving Freight Details...`);
        $btnTop.prop("disabled", true).html(`<i class="fa fa-spinner fa-spin me-1"></i> Saving...`);

        let itemDatesList = [];
        $(".dinv-product-card").each(function() {
            let card = $(this);
            itemDatesList.push({
                soi_id: parseInt(card.find(".dinv-item-soi-id").val()) || 0,
                product_id: card.find(".dinv-item-product-id").val() || '',
                deliverydate: card.find(".dinv-item-deliverydate").val() || '',
                formatted_delivery_date: card.find(".dinv-item-formatted-delivery-date").val() || ''
            });
        });

        let postData = {
            projectid: $("#dinv_projectid").val() || CURRENT_APP_PROJECTID,
            psono: $("#dinv_psono").val() || '',
            sono: $("#dinv_sono").val() || '',
            clientid: $("#dinv_clientid").val() || '',
            loginid: $("body").data("loginid") || '',
            dinv_delivery_type: activeDelType,
            delivery_type: activeDelType,
            pickuptype: activeDelType,
            freight_charges: grandFreight,
            vehicle_id: $firstRow.find(".dinv_f_vehicle_id").val() || (window._currentFreightGroups?.[0]?.location?.vehicleid || 0),
            vehiclename: $firstRow.find(".dinv_f_vehicle_info").val() || (window._currentFreightGroups?.[0]?.location?.vehiclename || ''),
            vehicleno: $firstRow.find(".dinv_f_vehicle_no").val() || (window._currentFreightGroups?.[0]?.location?.vehicleno || ''),
            vehicleownername: $firstRow.find(".dinv_f_transporter_name").val() || (window._currentFreightGroups?.[0]?.location?.vehicleownername || ''),
            driver_id: $firstRow.find(".dinv_f_driver_id").val() || (window._currentFreightGroups?.[0]?.location?.driverid || ''),
            drivername: $firstRow.find(".dinv_f_driver_name").val() || (window._currentFreightGroups?.[0]?.location?.drivername || ''),
            drivermobileno: driverMobile,
            scheduled_dispatch_date: $("#dinv_scheduled_dispatch_date").val() || (window._currentFreightGroups?.[0]?.deliveryDate ? formatDispatchDateISO(window._currentFreightGroups[0].deliveryDate) : ''),
            unified_delivery_date: currentInvoiceData?.unified_delivery_date || (window._currentFreightGroups?.[0]?.deliveryDate || ''),
            items_dates_json: JSON.stringify(itemDatesList),
            transport_remarks: $firstRow.find(".dinv_f_narration_input").val() || (window._currentFreightGroups?.[0]?.location?.remark || ''),
            freight_discount_percent: parseFloat($firstRow.find(".dinv_f_disc_input").val()) || (window._currentFreightGroups?.[0]?.discPct || 0),
            freight_discount_amount: parseFloat($firstRow.find(".dinv_f_disc_amt_input").val()) || (window._currentFreightGroups?.[0]?.discAmt || 0),
            freight_narration: $firstRow.find(".dinv_f_narration_input").val() || (window._currentFreightGroups?.[0]?.location?.remark || ''),
            free_distance: parseFloat($firstRow.find(".dinv_f_freedistance_input").val()) || (window._currentFreightGroups?.[0]?.location?.freedistance || 0),
            total_distance: parseFloat($firstRow.find(".dinv_f_totaldistance_input").val()) || (window._currentFreightGroups?.[0]?.location?.totaldistance || 0),
            payable_distance: parseFloat($firstRow.find(".dinv_f_payabledistance_input").val()) || (window._currentFreightGroups?.[0]?.location?.distance || 0),
            freight_groups: JSON.stringify(window._currentFreightGroups || [])
        };

        if (activeDelType === 'pickup') {
            if (!postData.vehicleno) postData.vehicleno = 'Self Pickup';
            if (!postData.vehiclename) postData.vehiclename = 'Self Pickup';
        }

        $.ajax({
            url: "modal/finaldispatch_modal.php?action=UpdateDispatchInvoiceStep2Freight",
            type: "POST",
            data: postData,
            dataType: "json",
            success: function(res) {
                $btn.prop("disabled", false).html(`Next: Payment & Finalize <i class="fa fa-arrow-right ms-1"></i>`);
                $btnTop.prop("disabled", false).html(`Next: Payment & Finalize <i class="fa fa-arrow-right ms-1"></i>`);
                if (res.status === "success") {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Freight charge details updated successfully',
                        showConfirmButton: false,
                        timer: 1500
                    });
                    window.goToDinvStep(3);
                } else {
                    Swal.fire("Error", res.message || "Failed to update freight details.", "error");
                }
            },
            error: function(xhr) {
                $btn.prop("disabled", false).html(`Next: Payment & Finalize <i class="fa fa-arrow-right ms-1"></i>`);
                $btnTop.prop("disabled", false).html(`Next: Payment & Finalize <i class="fa fa-arrow-right ms-1"></i>`);
                let errMsg = "Server error while updating freight details.";
                if (xhr?.responseJSON?.message) errMsg = xhr.responseJSON.message;
                Swal.fire("Error", errMsg, "error");
            }
        });
    }

    // Wizard navigation event listeners
    $(document).on("click", ".dinv-step-item", function() {
        let targetStep = parseInt($(this).data("step")) || 1;
        let currentStep = window._currentDinvStep || 1;
        if (targetStep === currentStep) return;

        if (currentStep === 1 && targetStep > 1) {
            saveStep1ProductsAndProceed();
        } else if (currentStep === 2 && targetStep > 2) {
            saveStep2FreightAndProceed();
        } else {
            window.goToDinvStep(targetStep);
        }
    });

    $(document).on("click", "#dinv_btn_next_to_2", function(e) {
        if ($(this).hasClass("disabled") || $(this).prop("disabled")) {
            e.preventDefault();
            e.stopPropagation();
            Swal.fire({
                icon: 'warning',
                title: 'Cannot Proceed to Freight Details',
                html: '<b>Total Order Qty is 0 Plants.</b><br>Please select and include at least one valid product item before proceeding to Freight Details.',
                confirmButtonColor: '#dc2626',
                confirmButtonText: 'Understood'
            });
            return false;
        }
        saveStep1ProductsAndProceed();
    });

    $(document).on("click", "#dinv_btn_next_to_3", function() {
        saveStep2FreightAndProceed();
    });

    $(document).on("click", "#dinv_btn_back_to_1", function() {
        window.goToDinvStep(1);
    });

    $(document).on("click", "#dinv_btn_back_to_2", function() {
        window.goToDinvStep(2);
    });

    // Top Mobile Sticky Navigation Button Click Handlers
    $(document).on("click", ".dinv-top-btn-next-to-2", function(e) {
        $("#dinv_btn_next_to_2").trigger("click");
    });

    $(document).on("click", ".dinv-top-btn-next-to-3", function(e) {
        $("#dinv_btn_next_to_3").trigger("click");
    });

    $(document).on("click", ".dinv-top-btn-back-to-1", function(e) {
        $("#dinv_btn_back_to_1").trigger("click");
    });

    $(document).on("click", ".dinv-top-btn-back-to-2", function(e) {
        $("#dinv_btn_back_to_2").trigger("click");
    });

    $(document).on("click", ".dinv-top-btn-preview-pdf", function(e) {
        $("#previewDispatchInvoicePdfBtn").trigger("click");
    });

    $(document).on("click", ".dinv-top-btn-confirm-save", function(e) {
        $("#confirmGenerateDispatchInvoiceBtn").trigger("click");
    });

    /**
     * Open Payment Advice Manual modal prefilled against invoice
    /**
     * Open Receive Payment Modal displaying paymentadvicemanual.php in responsive 95vw modal iframe
     */
    function openReceivePaymentModal() {
        if (!currentInvoiceData) {
            Swal.fire("Notice", "Please open an invoice first.", "info");
            return;
        }

        let ord = currentInvoiceData.order || {};
        let psono = ord.psono || $("#dinv_psono").val() || '';
        let sono = ord.sono || $("#dinv_sono").val() || '';
        let dinvoiceno = ord.dinvoiceno || $("#dinv_invoiceno").val() || currentInvoiceData.next_invoice_no || '';
        let clientName = ord.clientname || currentInvoiceData.client_info?.clientname || $("#dinv_clientname").val() || '';
        let clientMobile = ord.mobileno || currentInvoiceData.client_info?.mobileno || $("#dinv_mobileno").val() || '';
        let clientId = ord.clientid || currentInvoiceData.client_info?.client_id || $("#dinv_clientid").val() || '';

        let grandTotal = parseFloat($("#dinv_grand_total").val()) || 0;
        let totalPaid = parseFloat(currentInvoiceData.total_paid_amount || 0);
        let balance = Math.max(0, grandTotal - totalPaid);

        let kParam = CURRENT_APP_KEY || '';
        let mdidParam = CURRENT_APP_MDID || '';
        let dzParam = CURRENT_APP_DZ || '';

        let pamUrl = `paymentadvicemanual.php?psono=${encodeURIComponent(psono)}&sono=${encodeURIComponent(sono)}&dinvoiceno=${encodeURIComponent(dinvoiceno)}&cname=${encodeURIComponent(clientName)}&mob=${encodeURIComponent(clientMobile)}&clid=${encodeURIComponent(clientId)}&amt=${encodeURIComponent(balance > 0 ? balance.toFixed(2) : '')}&purpose=paymentagainstinvoice&remarks=${encodeURIComponent('Payment against Provisional Order #' + psono + (dinvoiceno ? ' (Inv: ' + dinvoiceno + ')' : ''))}&iframe=true`;
        if (kParam) pamUrl += `&k=${encodeURIComponent(kParam)}`;
        if (mdidParam) pamUrl += `&mdid=${encodeURIComponent(mdidParam)}`;
        if (dzParam) pamUrl += `&dz=${encodeURIComponent(dzParam)}`;
        pamUrl += `&v=${Date.now()}`;

        $("#dinv_pay_modal_ord_badge").text(`Order: ${psono || '-'}`);
        $("#dinvPaymentAdviceIframe").attr("src", pamUrl);
        $("#dinvReceivePaymentModal").modal("show");
    }

    // Reload iframe inside payment advice modal
    $(document).on("click", "#dinv_btn_refresh_iframe_pam", function(e) {
        e.preventDefault();
        let iframe = document.getElementById("dinvPaymentAdviceIframe");
        if (iframe && iframe.contentWindow) {
            iframe.contentWindow.location.reload();
        }
    });

    // Callback when payment is saved from inside paymentadvicemanual.php iframe
    window.onPaymentAdviceModalSaved = function(res) {
        $("#dinvReceivePaymentModal").modal("hide");
        refreshDispatchPaymentHistory(null, null, null, function() {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Payment Advice recorded & updated!',
                showConfirmButton: false,
                timer: 2200
            });
        });
    };

    // When modal is hidden, reset iframe src and refresh payments
    $(document).on("hidden.bs.modal", "#dinvReceivePaymentModal", function() {
        $("#dinvPaymentAdviceIframe").attr("src", "");
        refreshDispatchPaymentHistory();
    });

    // Trigger Auto-Fill Pending Balance as Cash by adminsystem
    function triggerAutoFillCashAdmin(onSuccessCallback) {
        if (!currentInvoiceData) return;
        let roleId = parseInt(CURRENT_APP_LOGINROLEID);
        if (isNaN(roleId)) {
            roleId = parseInt($("body").data("loginroleid") || 99);
        }
        if (roleId >= 3) {
            Swal.fire({
                icon: "error",
                title: "Access Denied",
                text: "Auto-fill Cash is only available for Admin users (Role < 3)."
            });
            return;
        }

        let ord = currentInvoiceData.order || {};
        let psono = ord.psono || $("#dinv_psono").val() || '';
        let sono = ord.sono || $("#dinv_sono").val() || '';
        let clientId = ord.clientid || currentInvoiceData.client_info?.client_id || $("#dinv_clientid").val() || '';
        let projectid = $("body").data("projectid") || 0;
        let loginid = $("body").data("loginid") || "adminsystem";
        let grandTotal = parseFloat($("#dinv_grand_total").val()) || 0;
        let totalPaid = parseFloat(currentInvoiceData?.total_paid_amount || 0);
        let balance = Math.round(Math.max(0, grandTotal - totalPaid) * 100) / 100;

        Swal.fire({
            title: "Recording Cash Payment",
            text: "Auto-filling pending balance as Cash payment by adminsystem...",
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: "modal/finaldispatch_modal.php?action=AutoFillBalanceAsCash",
            type: "POST",
            data: {
                projectid: projectid,
                psono: psono,
                sono: sono,
                clientid: clientId,
                loginid: loginid,
                loginroleid: roleId,
                balance_amount: balance,
                grand_total: grandTotal
            },
            dataType: "json",
            success: function(res) {
                Swal.close();
                if (res.status === "success") {
                    refreshDispatchPaymentHistory(null, null, null, function() {
                        Swal.fire({
                            icon: "success",
                            title: "Cash Payment Recorded!",
                            text: res.message || "Pending balance auto-filled as Cash by adminsystem.",
                            confirmButtonColor: "#059669"
                        }).then(() => {
                            if (typeof onSuccessCallback === "function") {
                                onSuccessCallback();
                            }
                        });
                    });
                } else {
                    Swal.fire("Error", res.message || "Failed to auto-fill cash payment.", "error");
                }
            },
            error: function() {
                Swal.close();
                Swal.fire("Error", "Server error while recording auto-fill cash payment.", "error");
            }
        });
    }

    // Auto-fill Cash button in Dispatch Invoice Modal header
    $(document).on("click", "#dinv_btn_autofill_cash", function(e) {
        e.preventDefault();
        let roleId = parseInt(CURRENT_APP_LOGINROLEID);
        if (isNaN(roleId)) {
            roleId = parseInt($("body").data("loginroleid") || 99);
        }
        if (roleId >= 3) {
            Swal.fire({
                icon: "error",
                title: "Access Denied",
                text: "Auto-fill Cash is only available for Admin users (Role < 3)."
            });
            return;
        }
        let grandTotal = parseFloat($("#dinv_grand_total").val()) || 0;
        let totalPaid = parseFloat(currentInvoiceData?.total_paid_amount || 0);
        let balance = Math.round(Math.max(0, grandTotal - totalPaid) * 100) / 100;

        if (balance <= 0) {
            Swal.fire({
                icon: "info",
                title: "Balance Clear",
                text: "The order balance is already fully paid (₹ 0.00 Due).",
                confirmButtonColor: "#059669"
            });
            return;
        }

        Swal.fire({
            title: "Auto-Fill Balance as Cash?",
            html: `Are you sure you want to auto-fill the pending balance of <b>₹ ${balance.toFixed(2)}</b> as <b>Cash</b> payment received by <b>adminsystem</b>?`,
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#059669",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "<i class='fa fa-money-bill-wave me-1'></i> Yes, Auto-Fill Cash",
            cancelButtonText: "Cancel"
        }).then((res) => {
            if (res.isConfirmed) {
                triggerAutoFillCashAdmin();
            }
        });
    });

    // Event listener for opening receive payment modal or alerting about unverified payments
    $(document).on("click", "#dinv_btn_open_receive_payment", function(e) {
        e.preventDefault();
        let payments = Array.isArray(currentInvoiceData?.payment_history) ? currentInvoiceData.payment_history : [];
        let unverifiedList = payments.filter(p => (p.verification || '').toString().toLowerCase().trim() !== 'verified');
        let unverifiedSum = unverifiedList.reduce((sum, p) => sum + (parseFloat(p.advanceamount) || 0), 0);
        let grandTotal = parseFloat($("#dinv_grand_total").val()) || 0;
        let totalPaid = parseFloat(currentInvoiceData?.total_paid_amount || 0);
        let balance = Math.round(Math.max(0, grandTotal - totalPaid) * 100) / 100;

        if (unverifiedList.length > 0 && unverifiedSum > 0.01 && balance <= 0) {
            Swal.fire({
                title: "Payment Verification Required",
                html: `<div class="text-start">
                    <p class="mb-2">Payment total matches invoice, but <b class="text-danger">₹ ${unverifiedSum.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</b> (${unverifiedList.length} transaction${unverifiedList.length > 1 ? 's' : ''}) is currently <span class="badge bg-warning text-dark">Unverified</span>.</p>
                    <div class="alert alert-warning py-2.5 px-3 mb-2">
                        <i class="fa fa-exclamation-triangle text-danger me-1"></i> <b>Action Required:</b> Please <b>contact Account Department</b> to verify the unverified transaction in their portal before proceeding.
                    </div>
                    <p class="text-muted small mb-0">Do you want to re-check verification status or record an additional payment?</p>
                </div>`,
                icon: "warning",
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonColor: "#2563eb",
                denyButtonColor: "#059669",
                cancelButtonColor: "#6c757d",
                confirmButtonText: "<i class='fa fa-sync-alt me-1'></i> Re-check Status",
                denyButtonText: "<i class='fa fa-plus-circle me-1'></i> Add More Payment",
                cancelButtonText: "Close"
            }).then((res) => {
                if (res.isConfirmed) {
                    refreshDispatchPaymentHistory();
                } else if (res.isDenied) {
                    openReceivePaymentModal();
                }
            });
            return;
        }
        openReceivePaymentModal();
    });

    // Refresh payments button (both header and inline buttons)
    $(document).on("click", "#dinv_btn_refresh_payments, .dinv-refresh-payments-btn", function(e) {
        e.preventDefault();
        let $btn = $(this);
        $btn.find("i").addClass("fa-spin");
        refreshDispatchPaymentHistory(null, null, null, function() {
            $btn.find("i").removeClass("fa-spin");
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Payment history updated!',
                showConfirmButton: false,
                timer: 1800
            });
        });
    });

    // Cancel / Remove Wrong Payment Transaction
    $(document).on("click", ".dinv-remove-payment-btn", function(e) {
        e.preventDefault();
        let $btn = $(this);
        let sno = $btn.data("sno");
        let amt = parseFloat($btn.data("amt") || 0);
        let utr = $btn.data("utr") || '-';
        let date = $btn.data("date") || '-';

        if (!sno) {
            Swal.fire("Error", "Invalid payment transaction ID.", "error");
            return;
        }

        Swal.fire({
            title: "Cancel Payment Transaction?",
            html: `
                <div class="text-start">
                    <p class="text-muted mb-2">Are you sure you want to cancel this payment transaction? This will mark the record as cancelled (<code>tedit=1</code>, <code>transaction_type=Cancel</code>) and remove it from invoice accounting calculations.</p>
                    <div class="p-2.5 bg-light rounded-3 border text-dark font-monospace small mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Transaction ID:</span>
                            <span class="fw-bold">#${sno}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Payment Date:</span>
                            <span class="fw-bold">${escapeHtml(date)}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">UTR / Ref No:</span>
                            <span class="fw-bold">${escapeHtml(utr)}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Amount:</span>
                            <span class="fw-bold text-danger">₹ ${amt.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                        </div>
                    </div>
                    <label class="form-label small fw-bold text-muted mb-1">Cancellation Remark / Reason <span class="text-danger">*</span></label>
                </div>
            `,
            input: 'text',
            inputValue: 'Wrong payment entry cancelled',
            inputPlaceholder: 'Enter cancellation reason...',
            inputAttributes: {
                autocapitalize: 'off',
                class: 'form-control form-control-sm'
            },
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#dc3545",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "<i class='fa fa-trash-alt me-1'></i> Yes, Cancel Payment",
            cancelButtonText: "No, Keep It",
            inputValidator: (value) => {
                if (!value || !value.trim()) {
                    return 'Please enter a cancellation remark/reason!';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                let remark = result.value ? result.value.trim() : 'Wrong payment entry cancelled';
                $btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i>');

                $.ajax({
                    url: "modal/finaldispatch_modal.php?action=CancelAdvancePaymentTransaction",
                    type: "POST",
                    dataType: "json",
                    data: {
                        sno: sno,
                        cancel_remark: remark
                    },
                    success: function(resp) {
                        if (resp && resp.status === "success") {
                            Swal.fire({
                                icon: "success",
                                title: "Cancelled!",
                                text: resp.message || "Payment transaction has been cancelled.",
                                timer: 2000,
                                showConfirmButton: false
                            });
                            // Refresh payment history and dynamic totals
                            refreshDispatchPaymentHistory();
                        } else {
                            $btn.prop("disabled", false).html('<i class="fa fa-trash-alt me-1"></i>Cancel');
                            Swal.fire("Failed", resp.message || "Failed to cancel transaction.", "error");
                        }
                    },
                    error: function(xhr, status, err) {
                        $btn.prop("disabled", false).html('<i class="fa fa-trash-alt me-1"></i>Cancel');
                        Swal.fire("Server Error", "An error occurred while contacting the server: " + (err || status), "error");
                    }
                });
            }
        });
    });

    // Source of payment toggle buttons
    $(document).on("click", ".dinv-source-btn", function(e) {
        e.preventDefault();
        $(".dinv-source-btn").removeClass("active");
        $(this).addClass("active");
        let src = $(this).data("source");
        $("#dinv_pay_sourceofpayment").val(src);
        if (src === "Cash") {
            $("#dinv_pay_utr_wrapper").slideUp(150);
            $("#dinv_pay_utrnumber").removeAttr("required");
        } else {
            $("#dinv_pay_utr_wrapper").slideDown(150);
            $("#dinv_pay_utrnumber").attr("placeholder", src === "Cheque" ? "Enter Cheque Number" : "Enter UTR / Transaction Reference No");
        }
    });

    // Form Submit: Save Payment Advice via AJAX
    $("#dinvAdvancePaymentForm").on("submit", function(e) {
        e.preventDefault();
        let form = this;
        let amt = parseFloat($("#dinv_pay_amount").val()) || 0;
        let pDate = $("#dinv_pay_paymentdate").val();
        let src = $("#dinv_pay_sourceofpayment").val();
        let utr = $("#dinv_pay_utrnumber").val()?.trim() || '';

        if (amt <= 0) {
            Swal.fire("Invalid Amount", "Please enter a valid payment amount greater than 0.", "warning");
            return;
        }

        if (!pDate) {
            Swal.fire("Required", "Please select payment date.", "warning");
            return;
        }

        if (src !== 'Cash' && !utr) {
            Swal.fire("Required", `Please enter ${src === 'Cheque' ? 'Cheque Number' : 'UTR / Reference Number'}.`, "warning");
            return;
        }

        let formData = new FormData(form);
        let $submitBtn = $("#dinv_submit_payment_btn");
        $submitBtn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

        $.ajax({
            url: "modal/paymentadvice_modal.php?action=SaveAdvancePaymentManulal",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function(res) {
                $submitBtn.prop("disabled", false).html('<i class="fa fa-check-circle me-1"></i> Save Payment Advice');
                if (res && res.status === "success") {
                    $("#dinvReceivePaymentModal").modal("hide");

                    // Refresh payment history and balance without reloading page
                    refreshDispatchPaymentHistory(null, null, null, function() {
                        Swal.fire({
                            icon: "success",
                            title: "Payment Recorded!",
                            text: res.message || "Payment advice logged successfully and awaiting verification.",
                            confirmButtonColor: "#2563eb"
                        });
                    });
                } else {
                    Swal.fire("Error", res?.message || "Failed to record payment.", "error");
                }
            },
            error: function(xhr) {
                $submitBtn.prop("disabled", false).html('<i class="fa fa-check-circle me-1"></i> Save Payment Advice');
                console.error("Payment save error:", xhr);
                Swal.fire("Error", "Server error while saving payment advice.", "error");
            }
        });
    });

    /**
     * Preview Invoice as PDF inside Modal with embedded Iframe (Live data with selected items & freight)
     */
    function openInvoicePdfPreviewModal() {
        let psono = $("#dinv_psono").val() || currentInvoiceData?.order?.psono || '';
        let sono = $("#dinv_sono").val() || currentInvoiceData?.order?.sono || '';
        let companyId = $("#dinv_companyid").val() || currentInvoiceData?.order?.companyid || CURRENT_APP_COMPANYID;
        let subcompanyId = $("#dinv_subcompanyid").val() || currentInvoiceData?.order?.subcompanyid || CURRENT_APP_SUBCOMPANYID;
        let projectId = $("#dinv_projectid").val() || currentInvoiceData?.order?.projectid || CURRENT_APP_PROJECTID;
        let invoiceNo = $("#dinv_invoiceno").val() || '';
        let invoiceDate = $("#dinv_invoicedate").val() || '';
        let clientName = $("#dinv_clientname").val() || currentInvoiceData?.order?.clientname || '';
        let mobileNo = $("#dinv_mobileno").val() || currentInvoiceData?.order?.mobileno || '';
        let emailId = $("#dinv_emailid").val() || currentInvoiceData?.order?.emailid || '';
        let invoicingAddress = $("#dinv_invoicing_address").val() || $("#dinv_address").val() || currentInvoiceData?.client_info?.resolved_invoicing_address || currentInvoiceData?.client_info?.invoicing_address || currentInvoiceData?.order?.invoicing_address || currentInvoiceData?.order?.address || '';

        // 1. Collect ONLY checked items
        let items = [];
        $(".dinv-product-card").each(function() {
            let card = $(this);
            let isIncluded = card.find(".dinv-item-include-checkbox").is(":checked");
            if (!isIncluded) return; // Skip excluded items

            let soiId = parseInt(card.find(".dinv-item-soi-id").val()) || 0;
            let productId = card.find(".dinv-item-product-id").val() || '';
            let productName = card.find(".dinv-item-product-name").val() || '';
            let cleanName = card.data("clean-name") || card.find(".dinv-product-clean-title").data("clean-title") || card.find(".dinv-product-clean-title").text().trim() || productName;
            cleanName = cleanName.replace(/Include in Invoice/gi, '').trim();
            let isGrafted = card.find(".dinv-item-is-grafted").val() == '1';
            let plantType = card.find(".dinv-item-plant-type").val() || (isGrafted ? 'Grafted' : 'NonGrafted');
            
            let fullProductName = cleanName;
            if (!fullProductName.startsWith('Grafted-') && !fullProductName.startsWith('NonGrafted-') && !fullProductName.startsWith('Non-Grafted-')) {
                let prefix = (plantType.toLowerCase().includes('nongrafted') || plantType.toLowerCase().includes('non-grafted') || !isGrafted) ? 'NonGrafted-' : 'Grafted-';
                fullProductName = prefix + cleanName;
            }

            let stockOrderNo = card.find(".dinv-item-stockorder-no").val() || '';
            let orderQty = parseInt(card.find(".dinv-item-order-qty").val()) || 0;
            let allocQty = parseInt(card.find(".dinv-item-alloc-qty").val()) || 0;
            let rate = parseFloat(card.find(".dinv-item-rate").val()) || 0;
            let discPct = parseFloat(card.find(".dinv-item-disc-pct").val()) || 0;
            let discAmt = parseFloat(card.find(".dinv-item-disc-amt").val()) || 0;
            let itemSubtotal = parseFloat(card.find(".dinv-item-subtotal").val()) || (allocQty * rate - discAmt);
            
            let delDate = card.find(".dinv-item-formatted-delivery-date").val() || card.find(".dinv-item-deliverydate").val() || '';
            if (!delDate || delDate === '-') {
                delDate = invoiceDate || (new Date()).toISOString().split('T')[0];
            }

            let isShifted = card.find(".dinv-item-shifted-radio:checked").val() === "yes" ? "yes" : "no";
            let extraPct = isShifted === "yes" ? 0 : (parseFloat(card.find(".dinv-item-extra-pct").val()) || 0);
            let extraQty = isShifted === "yes" ? (parseInt(card.find(".dinv-item-extra-qty").val()) || Math.ceil(allocQty / 125)) : (parseInt(card.find(".dinv-item-extra-qty").val()) || 0);
            let extraReason = isShifted === "yes" ? "Free Bonus Plants (1 Plant/Tray)" : "Non-Shifting Offer";

            items.push({
                soi_id: soiId,
                product_id: productId,
                product_name: fullProductName,
                clean_product_name: fullProductName,
                stockorder_no: stockOrderNo,
                order_qty: orderQty,
                quantity: allocQty,
                allocate_ordqty: allocQty,
                rate: rate,
                discount_percent: discPct,
                discount_amount: discAmt,
                deliverydate: delDate,
                amount: itemSubtotal,
                is_shifted: isShifted,
                extra_qty: extraQty,
                extra_pct: extraPct,
                extra_reason: extraReason,
                is_included: true
            });
        });

        // Add Box Packing Charges item to preview if enabled
        let isBoxPackingPreview = $("#dinv_enable_box_packing").is(":checked");
        let boxCountPreview = isBoxPackingPreview ? (parseInt($("#dinv_box_count").val()) || 0) : 0;
        let boxRatePreview = parseFloat($("#dinv_box_rate").val()) || 100.00;
        let boxTotalPreview = isBoxPackingPreview ? Math.round(boxCountPreview * boxRatePreview * 100) / 100 : 0;

        if (isBoxPackingPreview && boxCountPreview > 0) {
            items.push({
                soi_id: 0,
                product_id: 'BOX_PACKING',
                product_name: `Box Packing Charges (${boxCountPreview} Boxes @ Rs ${boxRatePreview.toFixed(0)})`,
                clean_product_name: `Box Packing Charges (${boxCountPreview} Boxes @ Rs ${boxRatePreview.toFixed(0)})`,
                stockorder_no: '',
                order_qty: boxCountPreview,
                quantity: boxCountPreview,
                allocate_ordqty: boxCountPreview,
                rate: boxRatePreview,
                discount_percent: 0,
                discount_amount: 0,
                deliverydate: invoiceDate || (new Date()).toISOString().split('T')[0],
                amount: boxTotalPreview,
                taxableamount: boxTotalPreview,
                tax_amount: 0,
                is_shifted: 'yes',
                extra_qty: 0,
                extra_pct: 0,
                extra_reason: '',
                is_included: true,
                is_box_packing: true,
                unit: 'Boxes'
            });
        }

        if (items.length === 0) {
            Swal.fire("No Products Selected", "Please select at least one product item checkbox to preview in the invoice.", "warning");
            return;
        }

        // 2. Collect Freight Details
        let delType = $("input[name='dinv_delivery_type']:checked").val() || 'delivered';
        let transportType = 'Farm Delivery';
        if (delType === 'pickup') {
            transportType = 'Self Pickup By Farmer/Client';
        } else if (delType === 'boxpacking') {
            let boxTrans = currentInvoiceData?.delivery_location?.transporttype || 'Bus';
            transportType = `Box Packing (${boxTrans})`;
        } else {
            transportType = 'Farm Delivery';
        }

        let allFreightGroups = [];
        $("#dinvFreightGroupTable tbody tr.dinv-group-row").each(function(idx) {
            let row = $(this);
            let addr = row.find(".dinv-group-address").text().trim();
            let city = row.find(".dinv-group-city").text().trim();
            let district = row.find(".dinv-group-district").text().trim();
            let state = row.find(".dinv-group-state").text().trim();
            
            let addrParts = [];
            if (addr) addrParts.push(addr);
            if (city && !addr.includes(city)) addrParts.push(city);
            if (district && !addr.includes(district) && district !== city) addrParts.push(district);
            if (state && !addr.includes(state)) addrParts.push(state);
            let combinedAddr = addrParts.join(', ');
            if (!combinedAddr && window._currentFreightGroups && window._currentFreightGroups[idx]) {
                let loc = window._currentFreightGroups[idx].location || {};
                combinedAddr = loc.deliveryaddress || [loc.dest_city, loc.dest_district, loc.dest_state].filter(Boolean).join(', ');
            }
            if (!combinedAddr) {
                combinedAddr = currentInvoiceData?.delivery_location?.deliveryaddress || [currentInvoiceData?.delivery_location?.dest_city, currentInvoiceData?.delivery_location?.dest_district, currentInvoiceData?.delivery_location?.dest_state].filter(Boolean).join(', ') || '';
            }

            let dist = parseFloat(row.find(".dinv_f_distance_info").val()) || parseFloat(row.find(".dinv_f_payabledistance_input").val()) || parseFloat(row.find(".dinv-row-km-text").text()) || 0;
            let totalDist = parseFloat(row.find(".dinv_f_totaldistance_input").val()) || dist;
            let freeDist = parseFloat(row.find(".dinv_f_freedistance_input").val()) || 0;
            let payableDist = parseFloat(row.find(".dinv_f_payabledistance_input").val()) || dist;
            let baseF = parseFloat(row.find(".dinv_f_amt_input").val()) || 0;
            let payableF = parseFloat(row.find(".dinv_f_payable_input").val()) || 0;
            let vNo = row.find(".dinv_f_vehicle_no").val() || (window._currentFreightGroups?.[idx]?.location?.vehicleno || '');
            let vName = row.find(".dinv_f_vehicle_info").val() || (window._currentFreightGroups?.[idx]?.location?.vehiclename || '');
            let tName = row.find(".dinv_f_transporter_name").val() || (window._currentFreightGroups?.[idx]?.location?.vehicleownername || '');
            let dName = row.find(".dinv_f_driver_name").val() || (window._currentFreightGroups?.[idx]?.location?.drivername || '');
            let dMobile = row.find(".dinv_f_driver_mobile").val() || (window._currentFreightGroups?.[idx]?.location?.drivercontactno || '');
            let tripId = row.find(".dinv_f_trip_id").val() || (window._currentFreightGroups?.[idx]?.location?.trip_id || '');
            let remark = row.find(".dinv_f_narration_input").val() || $("#dinv_remarks").val() || '';

            allFreightGroups.push({
                transport_type: transportType,
                delivery_address: combinedAddr,
                distance_km: dist,
                total_distance: totalDist,
                free_distance: freeDist,
                payable_distance: payableDist,
                freight_amount: baseF,
                payable_freight_amount: payableF,
                vehicle_no: vNo,
                vehicle_name: vName,
                transporter_name: tName,
                driver_name: dName,
                driver_mobile: dMobile,
                trip_id: tripId,
                remark: remark
            });
        });

        if (allFreightGroups.length === 0 && window._currentFreightGroups && window._currentFreightGroups.length > 0) {
            window._currentFreightGroups.forEach(function(g0) {
                let loc = g0.location || {};
                let addr = loc.deliveryaddress || [loc.dest_city, loc.dest_district, loc.dest_state].filter(Boolean).join(', ');
                let dist = parseFloat(loc.distance || loc.totaldistance || 0);
                allFreightGroups.push({
                    transport_type: transportType,
                    delivery_address: addr,
                    distance_km: dist,
                    total_distance: parseFloat(loc.totaldistance || dist),
                    free_distance: parseFloat(loc.freedistance || 0),
                    payable_distance: dist,
                    freight_amount: parseFloat(g0.baseFreight || 0),
                    payable_freight_amount: parseFloat(g0.payableFreight || 0),
                    vehicle_no: loc.vehicleno || '',
                    vehicle_name: loc.vehiclename || '',
                    transporter_name: loc.vehicleownername || '',
                    driver_name: loc.drivername || '',
                    driver_mobile: loc.drivercontactno || '',
                    trip_id: loc.trip_id || '',
                    remark: loc.remark || ''
                });
            });
        }

        let freightData = allFreightGroups[0] || {
            transport_type: transportType,
            delivery_address: '',
            distance_km: 0,
            freight_amount: 0,
            payable_freight_amount: 0,
            vehicle_no: '',
            vehicle_name: '',
            transporter_name: '',
            driver_name: '',
            driver_mobile: '',
            trip_id: '',
            remark: ''
        };

        // Total received payment & breakdown
        let totalReceived = 0;
        let cashReceived = 0;
        let chequeReceived = 0;
        let upiBankReceived = 0;
        let otherDiscount = 0;
        let payments = Array.isArray(currentInvoiceData?.payment_history) ? currentInvoiceData.payment_history : [];
        payments.forEach(p => {
            let amt = (parseFloat(p.advanceamount) || 0);
            let disc = (parseFloat(p.otherdiscount) || 0);
            otherDiscount += disc;
            let src = (p.sourceofpayment || '').toLowerCase().trim();
            if (src === 'cash') {
                cashReceived += amt;
            } else if (src === 'cheque' || src === 'check') {
                chequeReceived += amt;
            } else {
                upiBankReceived += amt;
            }
            totalReceived += amt;
        });
        if (totalReceived <= 0 && currentInvoiceData?.total_paid_amount) {
            totalReceived = parseFloat(currentInvoiceData.total_paid_amount) || 0;
            cashReceived = totalReceived;
        }

        let payload = {
            psono: psono,
            sono: sono,
            companyid: companyId,
            subcompanyid: subcompanyId,
            projectid: projectId,
            invoiceno: invoiceNo,
            invoicedate: invoiceDate,
            clientname: clientName,
            mobileno: mobileNo,
            emailid: emailId,
            invoicing_address: invoicingAddress,
            items: items,
            freight: freightData,
            freight_groups: allFreightGroups,
            total_received: totalReceived,
            cash_received: cashReceived,
            cheque_received: chequeReceived,
            upi_bank_received: upiBankReceived,
            other_discount: otherDiscount,
            special_discount: otherDiscount,
            mode: 'I'
        };

        let orderRef = psono ? `Provisional: ${psono} | Invoice: ${invoiceNo || 'Auto'}` : `SO: ${sono} | Invoice: ${invoiceNo || 'Auto'}`;
        $("#dinv_preview_psono_badge").text(orderRef);

        // Show loading in iframe container
        $("#dinv_pdf_iframe_container").html(`
            <div class="d-flex flex-column align-items-center justify-content-center h-100 py-5 text-muted">
                <div class="spinner-border text-primary mb-3" role="status"></div>
                <div class="fw-bold fs-6">Generating Live Dispatch Invoice Preview...</div>
                <div class="small">Including ${items.length} selected product(s) and assigned freight route</div>
            </div>
        `);
        
        $("#dinvInvoicePdfPreviewModal").modal("show");

        // Post snapshot to finaldispatch_modal.php?action=SetInvoicePdfPreviewData
        $.ajax({
            url: "modal/finaldispatch_modal.php?action=SetInvoicePdfPreviewData",
            type: "POST",
            data: { payload: JSON.stringify(payload) },
            dataType: "json",
            success: function(res) {
                if (res && res.status === "success" && res.token) {
                    let pdfUrl = `modal/dispatchInvoicePDF.php?token=${encodeURIComponent(res.token)}&mode=I`;
                    $("#dinv_pdf_download_link").attr("href", pdfUrl);
                    let iframeHtml = `<iframe src="${pdfUrl}#toolbar=1" style="width: 100%; height: 100%; border: none;" allowfullscreen></iframe>`;
                    $("#dinv_pdf_iframe_container").html(iframeHtml);
                } else {
                    $("#dinv_pdf_iframe_container").html(`
                        <div class="alert alert-danger m-4">
                            <i class="fa fa-exclamation-triangle me-2"></i> Failed to prepare preview data: ${res?.message || 'Unknown error'}
                        </div>
                    `);
                }
            },
            error: function(xhr) {
                console.error("Preview data error:", xhr);
                $("#dinv_pdf_iframe_container").html(`
                    <div class="alert alert-danger m-4">
                        <i class="fa fa-exclamation-triangle me-2"></i> Server error while generating invoice preview.
                    </div>
                `);
            }
        });
    }

    // Bind Preview Invoice (PDF) button click
    $(document).on("click", "#previewDispatchInvoicePdfBtn", function(e) {
        e.preventDefault();
        openInvoicePdfPreviewModal();
    });

    // Ensure scrolling works properly on parent modal when pdf preview modal closes
    $('#dinvInvoicePdfPreviewModal').on('hidden.bs.modal', function () {
        if ($('#prepareDispatchInvoiceModal').hasClass('show') || $('#prepareDispatchInvoiceModal').is(':visible')) {
            $('body').addClass('modal-open');
        }
    });

    // Confirm & Generate Dispatch Invoice
    $("#confirmGenerateDispatchInvoiceBtn").on("click", function() {
        let clientName = $("#dinv_clientname").val() || 'Client';
        let grandTotal = parseFloat($("#dinv_grand_total").val()) || 0;
        let sono = $("#dinv_sono").val() || '';
        let psono = $("#dinv_psono").val() || '';
        let freightCharges = parseFloat($("#dinv_freight_charges").val()) || 0;

        // Strict guard 1: Check for unverified payments
        let payments = Array.isArray(currentInvoiceData?.payment_history) ? currentInvoiceData.payment_history : [];
        let unverifiedList = payments.filter(p => (p.verification || '').toString().toLowerCase().trim() !== 'verified');
        let unverifiedSum = unverifiedList.reduce((sum, p) => sum + (parseFloat(p.advanceamount) || 0), 0);

        if (unverifiedList.length > 0 && unverifiedSum > 0.01) {
            Swal.fire({
                title: "Payment Verification Required",
                html: `<div class="text-start">
                    <p class="mb-2">There is an unverified payment amount of <b class="text-danger">₹ ${unverifiedSum.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</b> (${unverifiedList.length} transaction${unverifiedList.length > 1 ? 's' : ''}).</p>
                    <div class="alert alert-danger py-2.5 px-3 mb-2">
                        <i class="fa fa-exclamation-triangle text-danger me-1"></i> <b>Action Required:</b> Please <b>contact Account Department</b> to verify the unverified payment transactions.
                    </div>
                    <p class="text-muted small mb-0">All payments must be verified before the dispatch invoice can be generated and saved.</p>
                </div>`,
                icon: "warning",
                confirmButtonColor: "#2563eb",
                confirmButtonText: "<i class='fa fa-sync-alt me-1'></i> Re-check Verification Status",
                showCancelButton: true,
                cancelButtonText: "Close"
            }).then((res) => {
                if (res.isConfirmed) {
                    refreshDispatchPaymentHistory();
                }
            });
            return;
        }

        // Strict guard 2: ensure pending balance is 0 before allowing generate & save dispatch invoice (including special discount)
        let totalPaid = parseFloat(currentInvoiceData?.total_paid_amount || 0);
        if (payments.length > 0) {
            totalPaid = payments.reduce((sum, p) => sum + (parseFloat(p.advanceamount) || 0) + (parseFloat(p.otherdiscount) || 0), 0);
        }
        let balance = Math.round(Math.max(0, grandTotal - totalPaid) * 100) / 100;
        if (balance > 0) {
            let roleId = parseInt(CURRENT_APP_LOGINROLEID);
            if (isNaN(roleId)) {
                roleId = parseInt($("body").data("loginroleid") || 99);
            }
            if (roleId < 3) {
                Swal.fire({
                    title: "Pending Balance Due",
                    html: `Cannot generate & save dispatch invoice while pending balance is <b>₹ ${balance.toFixed(2)}</b>.<br><br>Please clear the pending balance or auto-fill as Cash payment by adminsystem.`,
                    icon: "warning",
                    showCancelButton: true,
                    showDenyButton: true,
                    confirmButtonColor: "#059669",
                    denyButtonColor: "#2563eb",
                    cancelButtonColor: "#6c757d",
                    confirmButtonText: "<i class='fa fa-money-bill-wave me-1'></i> Auto-fill Cash (adminsystem)",
                    denyButtonText: "<i class='fa fa-hand-holding-usd me-1'></i> Receive Custom Payment",
                    cancelButtonText: "Cancel"
                }).then((res) => {
                    if (res.isConfirmed) {
                        triggerAutoFillCashAdmin();
                    } else if (res.isDenied) {
                        openReceivePaymentModal();
                    }
                });
            } else {
                Swal.fire({
                    title: "Pending Balance Due",
                    html: `Cannot generate & save dispatch invoice while pending balance is <b>₹ ${balance.toFixed(2)}</b>.<br><br>Please clear the pending balance to proceed with Dispatch Invoice.`,
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#2563eb",
                    cancelButtonColor: "#6c757d",
                    confirmButtonText: "<i class='fa fa-hand-holding-usd me-1'></i> Receive Payment",
                    cancelButtonText: "Cancel"
                }).then((res) => {
                    if (res.isConfirmed) {
                        openReceivePaymentModal();
                    }
                });
            }
            return;
        }

        // Validate delivery address for included items from krf_deliveryaddress_location
        let missingAddressGroups = (window._currentFreightGroups || []).filter(g => {
            let pType = g.location?.pickuptype || 'delivered';
            return pType === 'delivered' && (!g.isLocationAvailable || (!g.location?.deliveryaddress && !g.location?.dest_city && !g.location?.dest_district));
        });
        if (missingAddressGroups.length > 0) {
            Swal.fire({
                title: "Missing Delivery Address",
                html: "One or more items do not have a delivery address in <b>krf_deliveryaddress_location</b>.<br><br>Please click <b>Update Delivery Address & Distance</b> in the table grid to update delivery location on the map first.",
                icon: "warning",
                confirmButtonColor: "#dc2626",
                confirmButtonText: "<i class='fa fa-map-marker-alt me-1'></i> Understood"
            });
            return;
        }

        // Collect ONLY checked items
        let items = [];
        let hasInvalidQty = false;

        $(".dinv-product-card").each(function() {
            let card = $(this);
            let isIncluded = card.find(".dinv-item-include-checkbox").is(":checked");
            if (!isIncluded) return; // Skip excluded items

            let soiId = parseInt(card.find(".dinv-item-soi-id").val()) || 0;
            let productId = card.find(".dinv-item-product-id").val() || '';
            let productName = card.find(".dinv-item-product-name").val() || '';
            let allocProdName = card.find(".dinv-item-allocated-product-name").val() || productName;
            let bookedProdName = card.find(".dinv-item-booked-product-name").val() || productName;
            let stockOrderNo = card.find(".dinv-item-stockorder-no").val() || '';
            let orderQty = parseInt(card.find(".dinv-item-order-qty").val()) || 0;
            let allocQty = parseInt(card.find(".dinv-item-alloc-qty").val()) || 0;
            let healthyPlant = parseInt(card.find(".dinv-item-healthy").val()) || 0;
            let unhealthyPlant = parseInt(card.find(".dinv-item-unhealthy").val()) || 0;
            let isShifted = card.find(".dinv-item-shifted-radio:checked").val() === "yes" ? "yes" : "no";
            let extraPct = isShifted === "yes" ? 0 : (parseFloat(card.find(".dinv-item-extra-pct").val()) || 0);
            let extraQty = isShifted === "yes" ? (parseInt(card.find(".dinv-item-extra-qty").val()) || Math.ceil(allocQty / 125)) : (parseInt(card.find(".dinv-item-extra-qty").val()) || 0);
            let rate = parseFloat(card.find(".dinv-item-rate").val()) || 0;
            let discPct = parseFloat(card.find(".dinv-item-disc-pct").val()) || 0;
            let discAmt = parseFloat(card.find(".dinv-item-disc-amt").val()) || 0;
            let taxable = parseFloat(card.find(".dinv-item-taxable").val()) || 0;
            let cgstPct = parseFloat(card.find(".dinv-item-cgst-pct").val()) || 0;
            let cgstAmt = parseFloat(card.find(".dinv-item-cgst-amt").val()) || 0;
            let sgstPct = parseFloat(card.find(".dinv-item-sgst-pct").val()) || 0;
            let sgstAmt = parseFloat(card.find(".dinv-item-sgst-amt").val()) || 0;
            let igstPct = parseFloat(card.find(".dinv-item-igst-pct").val()) || 0;
            let igstAmt = parseFloat(card.find(".dinv-item-igst-amt").val()) || 0;
            let taxAmt = parseFloat(card.find(".dinv-item-tax-amt").val()) || 0;
            let itemSubtotal = parseFloat(card.find(".dinv-item-subtotal").val()) || 0;
            let itemDelivDate = card.find(".dinv-item-deliverydate").val() || '';
            let itemFormattedDelivDate = card.find(".dinv-item-formatted-delivery-date").val() || '';

            if (allocQty < 0) {
                hasInvalidQty = true;
            }

            items.push({
                soi_id: soiId,
                product_id: productId,
                product_name: productName,
                allocated_product_name: allocProdName,
                allocated_product_id: productId,
                booked_product_name: bookedProdName,
                stockorder_no: stockOrderNo,
                order_qty: orderQty,
                allocate_ordqty: allocQty,
                healthyplant: healthyPlant,
                unhealthyplant: unhealthyPlant,
                isshifted: isShifted,
                percentextrashifting: extraPct,
                shiftingqty: extraQty,
                rate: rate,
                discount_percent: discPct,
                discount_amount: discAmt,
                taxableamount: taxable,
                cgst_percent: cgstPct,
                cgst_amount: cgstAmt,
                sgst_percent: sgstPct,
                sgst_amount: sgstAmt,
                igst_percent: igstPct,
                igst_amount: igstAmt,
                tax_amount: taxAmt,
                amount: itemSubtotal,
                deliverydate: itemDelivDate,
                formatted_delivery_date: itemFormattedDelivDate
            });
        });

        // Add Box Packing Charges item if enabled
        let isBoxPackingSave = $("#dinv_enable_box_packing").is(":checked");
        let boxCountSave = isBoxPackingSave ? (parseInt($("#dinv_box_count").val()) || 0) : 0;
        let boxRateSave = parseFloat($("#dinv_box_rate").val()) || 100.00;
        let boxTotalSave = isBoxPackingSave ? Math.round(boxCountSave * boxRateSave * 100) / 100 : 0;

        if (isBoxPackingSave && boxCountSave > 0) {
            items.push({
                soi_id: 0,
                product_id: 'BOX_PACKING',
                product_name: `Box Packing Charges (${boxCountSave} Boxes @ Rs ${boxRateSave.toFixed(0)})`,
                stockorder_no: '',
                order_qty: boxCountSave,
                allocate_ordqty: boxCountSave,
                healthyplant: 0,
                unhealthyplant: 0,
                isshifted: 'yes',
                percentextrashifting: 0,
                shiftingqty: 0,
                rate: boxRateSave,
                discount_percent: 0,
                discount_amount: 0,
                taxableamount: boxTotalSave,
                cgst_percent: 0,
                cgst_amount: 0,
                sgst_percent: 0,
                sgst_amount: 0,
                igst_percent: 0,
                igst_amount: 0,
                tax_amount: 0,
                amount: boxTotalSave,
                deliverydate: $("#dinv_scheduled_dispatch_date").val() || (new Date()).toISOString().split('T')[0],
                is_box_packing: 1
            });
        }

        if (items.length === 0) {
            Swal.fire("No Products Selected", "Please select at least one product item checkbox to include in the dispatch invoice.", "warning");
            return;
        }

        if (hasInvalidQty) {
            Swal.fire("Invalid Quantity", "Allocated quantity cannot be negative.", "warning");
            return;
        }

        // Strict guard 3: Verify polyhouse plant availability for all included items
        let unavailablePolyhouseItems = [];
        $(".dinv-product-card").each(function() {
            let card = $(this);
            let isIncluded = card.find(".dinv-item-include-checkbox").is(":checked");
            if (!isIncluded) return;

            let stkNo = card.find(".dinv-item-stockorder-no").val() || '';
            let prodName = card.find(".dinv-product-clean-title").text() || 'Product';
            let phQty = parseInt(card.attr("data-ph-qty") || card.data("ph-qty") || 0);
            let readyQty = parseInt(card.attr("data-ready-qty") || card.data("ready-qty") || 0);
            let trayCount = parseInt(card.attr("data-tray-count") || card.data("tray-count") || 0);

            if (phQty <= 0 && readyQty <= 0 && trayCount <= 0) {
                unavailablePolyhouseItems.push({
                    name: prodName,
                    stk: stkNo
                });
            }
        });

        if (unavailablePolyhouseItems.length > 0) {
            let itemDesc = unavailablePolyhouseItems.map(u => `• <b>${escapeHtml(u.name)}</b> (Stock Order: <code>${escapeHtml(u.stk || 'N/A')}</code>)`).join('<br>');
            Swal.fire({
                title: "Plants Not Available in Polyhouse",
                html: `<div class="text-start">
                    <p class="mb-2">Cannot generate dispatch invoice for loading because plants are <b>not available in the polyhouse</b> (Total Plants: 0):</p>
                    <div class="alert alert-danger py-2 px-3 mb-2" style="font-size: 12.5px; line-height: 1.5;">
                        ${itemDesc}
                    </div>
                    <p class="text-muted small mb-0"><i class="fa fa-info-circle me-1"></i> Orders cannot move to <b>Ready to Loading</b> without plants physically present in the polyhouse. Please ensure polyhouse plants are allocated and available before generating invoice.</p>
                </div>`,
                icon: "error",
                confirmButtonColor: "#dc2626",
                confirmButtonText: "Understand"
            });
            return;
        }

        let totalBillablePlants = $("#dinv_sum_alloc_qty").text() || '0 Plants';
        let boxPackingNotice = (isBoxPackingSave && boxCountSave > 0) ? `<b>Box Packing Charges:</b> ${boxCountSave} Boxes @ ₹${boxRateSave.toFixed(2)} (₹ ${boxTotalSave.toFixed(2)})<br>` : '';

        Swal.fire({
            title: "Generate Dispatch Invoice?",
            html: `Are you sure you want to generate and confirm the <b>Dispatch Invoice</b> for <b>${clientName}</b>?<br><br>
                   <div class="alert alert-info py-2 px-3 text-start small mb-0">
                     <b>Provisional Order:</b> ${psono || '-'} | <b>SO:</b> ${sono || '-'}<br>
                     <b>Selected Products:</b> ${items.length} Item(s) included<br>
                     <b>Total Billable Plants:</b> ${totalBillablePlants}<br>
                     ${boxPackingNotice}<b>Freight Charges:</b> ₹ ${freightCharges.toFixed(2)}<br>
                     <b>Grand Total:</b> <span class="text-success fw-bold">${$("#dinv_grand_total_display").text()}</span>
                   </div>`,
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#059669",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "<i class='fa fa-check-circle me-1'></i> Yes, Generate Dispatch Invoice"
        }).then((result) => {
            if (result.isConfirmed) {
                let btn = $("#confirmGenerateDispatchInvoiceBtn");
                btn.prop("disabled", true).html(`<i class="fa fa-spinner fa-spin me-1"></i> Generating...`);

                let formData = $("#prepareDispatchInvoiceForm").serializeArray();
                let postData = {};
                formData.forEach(item => { postData[item.name] = item.value; });
                postData.loginid = $("body").data("loginid") || "";
                postData.items_json = JSON.stringify(items);
                postData.freight_groups = JSON.stringify(window._currentFreightGroups || []);
                postData.scheduled_dispatch_date = $("#dinv_scheduled_dispatch_date").val() || (window._currentFreightGroups?.[0]?.deliveryDate ? formatDispatchDateISO(window._currentFreightGroups[0].deliveryDate) : '');
                postData.unified_delivery_date = currentInvoiceData?.unified_delivery_date || (window._currentFreightGroups?.[0]?.deliveryDate || '');

                let activeDelType = $("input[name='dinv_delivery_type']:checked").val() || 'delivered';
                postData.dinv_delivery_type = activeDelType;
                postData.pickuptype = activeDelType;

                let $firstRow = $("#dinvFreightGroupTable tbody tr.dinv-group-row").first();
                postData.vehicleid = $firstRow.find(".dinv_f_vehicle_id").val() || (window._currentFreightGroups?.[0]?.location?.vehicleid || 0);
                postData.vehiclename = $firstRow.find(".dinv_f_vehicle_info").val() || (window._currentFreightGroups?.[0]?.location?.vehiclename || '');
                postData.vehicleno = $firstRow.find(".dinv_f_vehicle_no").val() || (window._currentFreightGroups?.[0]?.location?.vehicleno || '');
                if (activeDelType === 'pickup') {
                    if (!postData.vehicleno) postData.vehicleno = 'Self Pickup';
                    if (!postData.vehiclename) postData.vehiclename = 'Self Pickup';
                }
                postData.transporter_name = $firstRow.find(".dinv_f_transporter_name").val() || (window._currentFreightGroups?.[0]?.location?.vehicleownername || '');
                postData.driverid = $firstRow.find(".dinv_f_driver_id").val() || (window._currentFreightGroups?.[0]?.location?.driverid || '');
                postData.drivername = $firstRow.find(".dinv_f_driver_name").val() || (window._currentFreightGroups?.[0]?.location?.drivername || '');
                postData.drivermobile = $firstRow.find(".dinv_f_driver_mobile").val() || (window._currentFreightGroups?.[0]?.location?.drivercontactno || '');
                postData.trip_id = $firstRow.find(".dinv_f_trip_id").val() || (window._currentFreightGroups?.[0]?.location?.trip_id || '');
                postData.freight_discount_percent = parseFloat($firstRow.find(".dinv_f_disc_input").val()) || (window._currentFreightGroups?.[0]?.discPct || 0);
                postData.freight_discount_amount = parseFloat($firstRow.find(".dinv_f_disc_amt_input").val()) || (window._currentFreightGroups?.[0]?.discAmt || 0);
                postData.freight_narration = $firstRow.find(".dinv_f_narration_input").val() || (window._currentFreightGroups?.[0]?.location?.remark || '');
                postData.free_distance = parseFloat($firstRow.find(".dinv_f_freedistance_input").val()) || (window._currentFreightGroups?.[0]?.location?.freedistance || 0);
                postData.total_distance = parseFloat($firstRow.find(".dinv_f_totaldistance_input").val()) || (window._currentFreightGroups?.[0]?.location?.totaldistance || 0);
                postData.payable_distance = parseFloat($firstRow.find(".dinv_f_payabledistance_input").val()) || (window._currentFreightGroups?.[0]?.location?.distance || 0);

                $.ajax({
                    url: "modal/finaldispatch_modal.php?action=SaveDispatchInvoice",
                    type: "POST",
                    data: postData,
                    dataType: "json",
                    success: function(res) {
                        btn.prop("disabled", false).html(`<i class="fa fa-check-circle me-1"></i> Generate & Save Dispatch Invoice`);
                        if (res.status === "success") {
                            $("#prepareDispatchInvoiceModal").modal("hide");
                            let isEdit = currentInvoiceData?.is_edit_mode || false;
                            Swal.fire({
                                title: isEdit ? "Dispatch Invoice Updated!" : "Dispatch Invoice Created!",
                                html: `Dispatch Invoice <b>${res.dinvoiceno || ''}</b> has been ${isEdit ? 'updated' : 'generated and saved'} successfully for <b>${items.length} product(s)</b>.<br>Grand Total: <b>₹ ${(res.grand_total || grandTotal).toLocaleString()}</b>`,
                                icon: "success",
                                confirmButtonColor: "#059669"
                            });
                            let targetTab = (currentInvoiceData?.is_dispatched || currentInvoiceData?.order?.status === 'FullDispatch' || currentInvoiceData?.order?.status === 'PartialDispatch')
                                ? (currentInvoiceData?.order?.status === 'FullDispatch' ? 'dispatched' : 'allocated')
                                : 'readytoload';
                            loadFinalDispatchSchedule($("#dispatchWeekSelector").val(), targetTab);
                        } else {
                            Swal.fire("Error", res.message || "Failed to generate dispatch invoice.", "error");
                        }
                    },
                    error: function(xhr) {
                        btn.prop("disabled", false).html(`<i class="fa fa-check-circle me-1"></i> Generate & Save Dispatch Invoice`);
                        let errMsg = "Server error while generating dispatch invoice.";
                        if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                            errMsg = xhr.responseJSON.message;
                        } else if (xhr && xhr.responseText) {
                            try {
                                let parsed = JSON.parse(xhr.responseText);
                                if (parsed.message) errMsg = parsed.message;
                            } catch(e) {
                                console.error(xhr.responseText);
                            }
                        }
                        Swal.fire("Error", errMsg, "error");
                    }
                });
            }
        });
    });

    // View / Download Dispatched Invoice PDF
    function openDispatchedInvoicePdf(invoiceNo) {
        if (!invoiceNo) {
            Swal.fire("Invoice Not Found", "No valid invoice number or sales order number found for this dispatched order.", "warning");
            return;
        }
        let url = `modal/genrateInvoicePDF.php?invno=${encodeURIComponent(invoiceNo)}&companyid=${encodeURIComponent(CURRENT_APP_COMPANYID)}&subcompanyid=${encodeURIComponent(CURRENT_APP_SUBCOMPANYID)}&projectid=${encodeURIComponent(CURRENT_APP_PROJECTID)}&loginname=${encodeURIComponent(CURRENT_APP_LOGINNAME)}`;
        window.open(url, '_blank');
    }

    // Open Invoices Download List Modal
    function openOrderInvoicesDownloadModal(psono, sono, soiId, row) {
        let clientName = row?.clientname || row?.client_name || '-';
        let mobile = row?.mobileno || row?.phones || '-';
        psono = psono || row?.psono || '';
        sono = sono || row?.sono || '';

        $("#orderInvModalClientName").text(clientName);
        $("#orderInvModalMobile").text(mobile);
        $("#orderInvModalPsonoBadge").text(`PSONO: ${psono || '-'}`);
        $("#orderInvModalSonoBadge").text(`SO: ${sono || '-'}`);
        $("#orderInvoicesSummaryCount").text("Loading invoices...");

        let tbody = $("#orderInvoicesTableBody");
        tbody.html(`
            <tr>
                <td colspan="8" class="text-center py-4 text-muted">
                    <i class="fa fa-spinner fa-spin me-2 text-success"></i> Loading respective invoices...
                </td>
            </tr>
        `);

        $("#orderInvoicesDownloadModal").modal("show");

        $.ajax({
            url: "modal/finaldispatch_modal.php?action=GetOrderInvoicesList",
            type: "POST",
            data: {
                psono: psono,
                sono: sono,
                soi_id: soiId || 0
            },
            dataType: "json",
            success: function(res) {
                if (res.status !== "success" || !res.data) {
                    tbody.html(`<tr><td colspan="8" class="text-center text-danger py-4">${escapeHtml(res.message || "Failed to load invoices.")}</td></tr>`);
                    $("#orderInvoicesSummaryCount").text("0 invoices");
                    return;
                }

                let d = res.data;
                let ord = d.order || {};
                if (ord.clientname) $("#orderInvModalClientName").text(ord.clientname);
                if (ord.mobileno) $("#orderInvModalMobile").text(ord.mobileno);
                if (ord.psono) $("#orderInvModalPsonoBadge").text(`PSONO: ${ord.psono}`);
                if (ord.sono) $("#orderInvModalSonoBadge").text(`SO: ${ord.sono}`);

                let invoices = d.invoices || [];
                $("#orderInvoicesSummaryCount").text(`Total Invoices: ${invoices.length}`);

                if (invoices.length === 0) {
                    tbody.html(`
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="fa fa-info-circle me-1 text-secondary"></i> No dispatch invoices generated yet for this order.
                            </td>
                        </tr>
                    `);
                    return;
                }

                let rowsHtml = '';
                invoices.forEach((inv, idx) => {
                    let plants = parseInt(inv.total_plants || 0);
                    let amt = parseFloat(inv.payableamount || inv.invoiceamount || 0);

                    let deliveryDisplay = '';
                    if (inv.pickuptype === 'pickup') {
                        deliveryDisplay = '<span class="badge bg-secondary-subtle text-dark border"><i class="fa fa-walking me-1"></i> Client Pickup</span>';
                    } else if (inv.pickuptype === 'boxpacking') {
                        deliveryDisplay = `<span class="badge bg-warning-subtle text-dark border border-warning"><i class="fa fa-boxes me-1"></i> Box Packing (${inv.box_count || 0} Boxes)</span>`;
                    } else {
                        let veh = (inv.vehicleno || '').trim();
                        deliveryDisplay = `<span class="badge bg-info-subtle text-dark border border-info"><i class="fa fa-truck me-1"></i> ${escapeHtml(veh || 'Delivered')}</span>`;
                    }

                    rowsHtml += `
                        <tr>
                            <td class="text-center fw-bold text-secondary">${idx + 1}</td>
                            <td>
                                <span class="fw-bold font-monospace text-primary">${escapeHtml(inv.invoiceno)}</span>
                            </td>
                            <td>${escapeHtml(inv.formatted_date || inv.invoicedate || '-')}</td>
                            <td class="text-center">
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fw-bold">
                                    <i class="fa fa-seedling me-1"></i> ${plants.toLocaleString()} Plants
                                </span>
                            </td>
                            <td class="text-end fw-bold text-dark">₹ ${amt.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                            <td>${deliveryDisplay}</td>
                            <td class="text-center">
                                <span class="badge bg-success text-white px-2 py-0.5 text-uppercase" style="font-size: 10px;">${escapeHtml(inv.status || 'Active')}</span>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-success py-1 px-2.5 fw-bold shadow-2xs download-single-order-invoice-btn" data-invoiceno="${escapeHtml(inv.invoiceno)}" title="Download / View Dispatched Invoice PDF">
                                    <i class="fa fa-file-pdf me-1"></i> Download PDF
                                </button>
                            </td>
                        </tr>
                    `;
                });

                tbody.html(rowsHtml);
            },
            error: function() {
                tbody.html(`<tr><td colspan="8" class="text-center text-danger py-4">Server error while fetching invoices list.</td></tr>`);
                $("#orderInvoicesSummaryCount").text("Error");
            }
        });
    }

    // Download single PDF from invoices list popup
    $(document).on("click", ".download-single-order-invoice-btn", function(e) {
        e.preventDefault();
        let invNo = $(this).attr("data-invoiceno") || $(this).data("invoiceno");
        openDispatchedInvoicePdf(invNo);
    });

    // View / Download Invoice click handler from schedule table
    $(document).on("click", ".view-download-invoice-pdf-btn", function(e) {
        e.preventDefault();
        let soiId = $(this).attr("data-soiid") || $(this).data("soiid");
        let row = $(this).data("row") || (soiId ? getDispatchRowBySoiId(soiId) : null);
        let psono = row?.psono || '';
        let sono = row?.sono || '';

        if (!psono && !sono && lastSelectedGrowthRow) {
            psono = lastSelectedGrowthRow.psono || '';
            sono = lastSelectedGrowthRow.sono || '';
            row = lastSelectedGrowthRow;
        }

        openOrderInvoicesDownloadModal(psono, sono, soiId, row);
    });

    // Direct PDF button click from inside the Prepare Dispatch Invoice modal past invoices table
    $(document).on("click", ".dinv-past-inv-pdf-btn", function(e) {
        e.preventDefault();
        let invoiceNo = $(this).attr("data-invoiceno") || $(this).data("invoiceno");
        openDispatchedInvoicePdf(invoiceNo);
    });

    // Edit a specific past dispatched invoice from the past invoices table
    $(document).on("click", ".dinv-past-inv-edit-btn", function(e) {
        e.preventDefault();
        let invNo = $(this).attr("data-invoiceno") || $(this).data("invoiceno");
        let soiId = $("#dinv_soi_id").val();
        let row = (currentInvoiceData && currentInvoiceData.order) ? currentInvoiceData.order : (soiId ? getDispatchRowBySoiId(soiId) : null);
        if (!row && lastSelectedGrowthRow) row = lastSelectedGrowthRow;
        if (row) {
            openPrepareDispatchInvoiceModal(row, soiId, invNo);
        } else {
            Swal.fire("Error", "Could not locate order details to edit invoice " + invNo, "error");
        }
    });

    // ==========================================
    // STRUCTURED EXCEL EXPORT (12 Requested Fields)
    // ==========================================
    $("#btnExportExcelSchedule, #exportExcelBtn, #exportExcelBtnTop").off("click").on("click", function () {
        let orders = (typeof getFilteredDataset === 'function' ? getFilteredDataset() : null) || currentRawData || [];
        if (!orders || orders.length === 0) {
            Swal.fire("No Data", "No dispatch schedule orders available in current view to export.", "warning");
            return;
        }

        if (typeof XLSX === 'undefined') {
            Swal.fire("Error", "Excel library (SheetJS) is not loaded. Please refresh the page.", "error");
            return;
        }

        let excelRows = [];
        orders.forEach((row, index) => {
            // 1. Polyhouse Number
            let polyhouseNo = (row.polyhouse_names && row.polyhouse_names !== 'N/A') ? row.polyhouse_names : '-';
            // 2. Bed Number
            let bedNo = (row.bed_numbers && row.bed_numbers !== 'N/A') ? row.bed_numbers : '-';
            // 3. Order Number (Stock order / PSONO)
            let orderNo = (row.effective_stockorderno && row.effective_stockorderno !== 'Not Allocated' && row.effective_stockorderno !== '0') ? row.effective_stockorderno : (row.soi_stockorderno || row.psono || '-');
            // 4. Sales Order
            let salesOrder = row.sono || '-';
            // 5. Booking Date
            let bookingDate = row.formatted_booking_date || ((row.sales_order_created_at && row.sales_order_created_at !== '0000-00-00 00:00:00') ? row.sales_order_created_at.split(' ')[0] : '-');
            // 6. Client Name
            let clientName = row.clientname || 'N/A';
            // 7. Client Address
            let clientAddress = (row.delivery_address || row.dl_deliveryaddress || [row.districtname, row.statename].filter(Boolean).join(', ') || '-').trim();
            // 8. Contact Number
            let contactNo = row.mobileno || '-';
            // 9. Crop Variety
            let cropVariety = row.clean_product_name || (typeof formatProductNameDisplay === 'function' ? formatProductNameDisplay(row.product_name || '-') : (row.product_name || '-'));
            // 10. Total Order
            let totalOrder = parseInt(row.order_qty || 0);
            // 11. Sowing Date
            let sowingDate = row.formatted_sowing_date || row.pso_sowingdate || '-';
            // 12. Dispatch Date
            let dispatchDate = row.formatted_delivery_date || row.delivery_date || '-';

            excelRows.push({
                "Polyhouse Number": polyhouseNo,
                "Bed Number": bedNo,
                "Order Number": orderNo,
                "Sales Order": salesOrder,
                "Booking Date": bookingDate,
                "Client Name": clientName,
                "Client Address": clientAddress,
                "Contact Number": contactNo,
                "Crop Variety": cropVariety,
                "Total Order": totalOrder,
                "Sowing Date": sowingDate,
                "Dispatch Date": dispatchDate
            });
        });

        let ws = XLSX.utils.json_to_sheet(excelRows);
        ws['!cols'] = [
            { wch: 18 }, // Polyhouse Number
            { wch: 16 }, // Bed Number
            { wch: 18 }, // Order Number
            { wch: 16 }, // Sales Order
            { wch: 14 }, // Booking Date
            { wch: 28 }, // Client Name
            { wch: 35 }, // Client Address
            { wch: 16 }, // Contact Number
            { wch: 26 }, // Crop Variety
            { wch: 14 }, // Total Order
            { wch: 14 }, // Sowing Date
            { wch: 14 }  // Dispatch Date
        ];

        let wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Final Dispatch Schedule");

        let selectedWeek = ($("#dispatchWeekSelector").val() || "Schedule").replace(/[^a-zA-Z0-9_-]/g, '_');
        let fileName = `Final_Dispatch_Schedule_${selectedWeek}.xlsx`;
        XLSX.writeFile(wb, fileName);

        Swal.fire({
            title: "Excel Exported!",
            text: `${excelRows.length} dispatch order(s) successfully exported to ${fileName}`,
            icon: "success",
            timer: 2000,
            showConfirmButton: false
        });
    });

    // ==========================================
    // PDF REPORT & PRINT PREVIEW
    // ==========================================
    function showDispatchPdfPreviewModal() {
        try {
            let el = document.getElementById('dispatchPdfPreviewModal');
            if (window.bootstrap && bootstrap.Modal) {
                let bsModal = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
                bsModal.show();
                return;
            }
        } catch (e) { }
        $("#dispatchPdfPreviewModal").modal("show");
    }

    $("#btnExportPdfSchedule").on("click", function () {
        let orders = (typeof getFilteredDataset === 'function' ? getFilteredDataset() : null) || currentRawData || [];
        if (!orders || orders.length === 0) {
            Swal.fire("No Data", "No dispatch schedule records available in the current view to generate report.", "warning");
            return;
        }

        let docHtml = buildDispatchPdfHtml(orders);
        $("#dispatchPdfPrintableDocument").html(docHtml);
        $("#dispatchPdfFooterCount").text(`Total orders in report: ${orders.length}`);

        let activeStatusName = ($("#dispatchPdfActiveStatusBadge").text() || '').trim();
        if (activeStatusName) {
            $("#dispatchPdfPreviewSubtitle").text(`A4 Landscape Print / PDF Document • ${activeStatusName}`);
        } else {
            $("#dispatchPdfPreviewSubtitle").text(`A4 Landscape Print / PDF Document`);
        }

        showDispatchPdfPreviewModal();
    });

    $("#btnDispatchPdfModalPrint, #btnDispatchPdfModalPrintBottom").on("click", function () {
        printDispatchPdf();
    });

    function printDispatchPdf() {
        let content = document.getElementById('dispatchPdfPrintableDocument');
        if (!content) return;

        let printWin = window.open('', '_blank', 'width=1120,height=800');
        if (!printWin) {
            window.print();
            return;
        }

        printWin.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Final Dispatch Schedule - Karjee Farm</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                <style>
                    @page { 
                        size: A4 landscape; 
                        margin: 6mm 6mm 6mm 6mm; 
                    }
                    * {
                        box-sizing: border-box;
                    }
                    html, body { 
                        width: 100% !important; 
                        height: auto !important;
                        background: #ffffff !important; 
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
                        padding: 0 !important; 
                        margin: 0 !important; 
                        color: #0f172a; 
                        -webkit-print-color-adjust: exact !important; 
                        print-color-adjust: exact !important;
                    }
                    table { 
                        border-collapse: collapse; 
                        width: 100% !important; 
                        table-layout: fixed;
                        page-break-inside: auto; 
                    }
                    thead { 
                        display: table-header-group; 
                    }
                    tr { 
                        page-break-inside: avoid; 
                        page-break-after: auto; 
                    }
                    th, td { 
                        border: 1px solid #cbd5e1 !important; 
                    }
                    .pdf-qty-table, .pdf-qty-table tr, .pdf-qty-table td {
                        border: none !important;
                        padding: 1px 0 !important;
                        background: transparent !important;
                    }
                </style>
            </head>
            <body>
                <div style="width: 100%; margin: 0; padding: 0;">
                    ${content.innerHTML}
                </div>
                <script>
                    window.onload = function() {
                        window.focus();
                        window.print();
                        setTimeout(function() { window.close(); }, 1000);
                    };
                <\/script>
            </body>
            </html>
        `);
        printWin.document.close();
    }

    $("#btnDispatchPdfModalDownload, #btnDispatchPdfModalDownloadBottom").on("click", function () {
        downloadDispatchPdf();
    });

    function downloadDispatchPdf() {
        let element = document.getElementById('dispatchPdfPrintableDocument');
        if (!element) return;

        let weekVal = ($("#dispatchWeekSelector").val() || 'Schedule').replace(/[^a-zA-Z0-9_-]/g, '_');
        let activeStatusName = ($("#dispatchPdfActiveStatusBadge").text() || '').trim().replace(/[^a-zA-Z0-9_-]/g, '_');
        let statusFilePart = activeStatusName ? `_${activeStatusName}` : '';
        let btn = $("#btnDispatchPdfModalDownload, #btnDispatchPdfModalDownloadBottom");
        let origHtml = btn.html();
        btn.prop("disabled", true).html(`<i class="fa fa-spinner fa-spin me-1"></i> Downloading PDF...`);

        let opt = {
            margin: [5, 5, 5, 5],
            filename: `Final_Dispatch_Schedule${statusFilePart}_A4_${weekVal}.pdf`,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, useCORS: true, logging: false, backgroundColor: '#ffffff' },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' },
            pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
        };

        if (typeof html2pdf === 'undefined') {
            btn.prop("disabled", false).html(origHtml);
            printDispatchPdf();
            return;
        }

        html2pdf().set(opt).from(element).save().then(function () {
            btn.prop("disabled", false).html(origHtml);
            Swal.fire({
                title: "A4 PDF Downloaded",
                text: "Final Dispatch Schedule report downloaded successfully.",
                icon: "success",
                timer: 2000,
                showConfirmButton: false
            });
        }).catch(function (err) {
            btn.prop("disabled", false).html(origHtml);
            console.error("PDF generation failed:", err);
            printDispatchPdf();
        });
    }

    function buildDispatchPdfHtml(orders) {
        let weekVal = $("#dispatchWeekSelector").val() || 'Current Week';
        let monthText = $("#dispatchMonthFilter option:selected").text() || '';
        let yearVal = $("#dispatchYearFilter").val() || new Date().getFullYear();
        let generatedDate = new Date().toLocaleString('en-IN', {
            dateStyle: 'medium',
            timeStyle: 'short'
        });

        let krfCompanyLogo = window.KRF_COMPANY_LOGO || '';

        // Detect Active Status Title & Theme from status cards
        let activeStatusTitle = "Total Orders";
        let activeStatusTheme = { bg: "#eff6ff", color: "#1e40af", border: "#bfdbfe" };

        if (typeof currentRawDataIsOldPending !== 'undefined' && currentRawDataIsOldPending) {
            activeStatusTitle = "Old Pending Allocation";
            activeStatusTheme = { bg: "#f3e8ff", color: "#6b21a8", border: "#d8b4fe" };
        } else {
            let activeCard = $(".status-filter-card.active");
            if (activeCard.length > 0) {
                let cardTitle = activeCard.find(".card-status-title").text().trim();
                if (cardTitle) {
                    activeStatusTitle = cardTitle;
                }
                let filterVal = activeCard.data("filter") || (typeof currentFilter !== 'undefined' ? currentFilter : "all");
                if (filterVal === "pending") {
                    activeStatusTheme = { bg: "#fee2e2", color: "#991b1b", border: "#fecaca" };
                } else if (filterVal === "allocated") {
                    activeStatusTheme = { bg: "#d1fae5", color: "#065f46", border: "#a7f3d0" };
                } else if (filterVal === "readytoload") {
                    activeStatusTheme = { bg: "#fef3c7", color: "#92400e", border: "#fde68a" };
                } else if (filterVal === "readytodispatch") {
                    activeStatusTheme = { bg: "#ccfbf1", color: "#0f766e", border: "#99f6e4" };
                } else if (filterVal === "dispatched") {
                    activeStatusTheme = { bg: "#f1f5f9", color: "#334155", border: "#cbd5e1" };
                } else if (filterVal === "oldpending") {
                    activeStatusTheme = { bg: "#f3e8ff", color: "#6b21a8", border: "#d8b4fe" };
                } else {
                    activeStatusTheme = { bg: "#eff6ff", color: "#1e40af", border: "#bfdbfe" };
                }
            } else if (typeof currentFilter !== 'undefined') {
                const statusMap = {
                    "pending": { title: "Pending Allocation", theme: { bg: "#fee2e2", color: "#991b1b", border: "#fecaca" } },
                    "allocated": { title: "Allocated (For Prepared Invoice)", theme: { bg: "#d1fae5", color: "#065f46", border: "#a7f3d0" } },
                    "readytoload": { title: "Ready to Loading", theme: { bg: "#fef3c7", color: "#92400e", border: "#fde68a" } },
                    "readytodispatch": { title: "Ready to Dispatch", theme: { bg: "#ccfbf1", color: "#0f766e", border: "#99f6e4" } },
                    "dispatched": { title: "Final Dispatched", theme: { bg: "#f1f5f9", color: "#334155", border: "#cbd5e1" } },
                    "cancelled": { title: "Cancelled Orders", theme: { bg: "#fee2e2", color: "#991b1b", border: "#fecaca" } },
                    "oldpending": { title: "Old Pending Allocation", theme: { bg: "#f3e8ff", color: "#6b21a8", border: "#d8b4fe" } },
                    "all": { title: "Total Orders", theme: { bg: "#eff6ff", color: "#1e40af", border: "#bfdbfe" } }
                };
                if (statusMap[currentFilter]) {
                    activeStatusTitle = statusMap[currentFilter].title;
                    activeStatusTheme = statusMap[currentFilter].theme;
                }
            }
        }

        // Calculate Totals
        let totalOrders = orders.length;
        let totalOrderQty = 0;
        let totalAllocQty = 0;
        let totalDispQty = 0;

        orders.forEach(r => {
            totalOrderQty += parseInt(r.order_qty || 0);
            let reserved = parseInt(r.reserved_qty || 0);
            let consumed = parseInt(r.consumed_qty || 0);
            totalAllocQty += (reserved > 0 ? reserved : consumed);
            totalDispQty += parseInt(r.dispatched_qty || r.dispatchedqty || 0);
        });

        let tableRowsHtml = '';
        orders.forEach((row, idx) => {
            let orderNo = (row.effective_stockorderno && row.effective_stockorderno !== 'Not Allocated' && row.effective_stockorderno !== '0') ? row.effective_stockorderno : (row.soi_stockorderno || row.psono || '-');
            let orderQty = parseInt(row.order_qty || 0);
            let reservedPlants = parseInt(row.reserved_qty || 0);
            let consumedPlants = parseInt(row.consumed_qty || 0);
            let allocQty = (reservedPlants > 0 ? reservedPlants : consumedPlants);
            let dispQty = parseInt(row.dispatched_qty || row.dispatchedqty || 0);

            let polyhouseNo = (row.polyhouse_names && row.polyhouse_names !== 'N/A') ? row.polyhouse_names : '-';
            let bedNo = (row.bed_numbers && row.bed_numbers !== 'N/A') ? row.bed_numbers : '-';

            let rawProduct = row.clean_product_name || (typeof formatProductNameDisplay === 'function' ? formatProductNameDisplay(row.product_name || 'Crop') : (row.product_name || 'Crop'));
            let isGrafted = (row.is_grafted === 1);

            let destAddr = (row.delivery_address || row.dl_deliveryaddress || [row.districtname, row.statename].filter(Boolean).join(', ') || '-').trim();

            tableRowsHtml += `
                <tr style="border-bottom: 1px solid #cbd5e1; ${idx % 2 === 1 ? 'background-color: #f8fafc;' : 'background-color: #ffffff;'}">
                    <td style="padding: 5px 3px; text-align: center; font-weight: 700; color: #64748b; font-size: 10px;">${idx + 1}</td>
                    
                    <td style="padding: 5px 4px; vertical-align: middle;">
                        <div style="font-weight: 700; color: #0f172a; font-size: 10.5px;">${row.formatted_delivery_date || row.delivery_date || '-'}</div>
                        <div style="font-size: 8.5px; color: #64748b; margin-top: 2px;">Sowing: ${row.formatted_sowing_date || row.pso_sowingdate || '-'}</div>
                    </td>

                    <td style="padding: 5px 4px; vertical-align: middle;">
                        <div style="font-weight: 800; color: #1e3a8a; font-size: 11px;">SO: ${row.sono || '-'}</div>
                        <div style="font-size: 9px; color: #0284c7; font-weight: 600;">PSONO: ${row.psono || '-'}</div>
                    </td>

                    <td style="padding: 5px 4px; vertical-align: middle;">
                        <div style="font-weight: 700; color: #0f172a; font-size: 10.5px;">${row.clientname || 'N/A'}</div>
                        <div style="font-size: 9px; color: #334155; margin-top: 1px;">📞 ${row.mobileno || '-'}</div>
                        <div style="font-size: 8.5px; color: #64748b; margin-top: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 155px;">📍 ${destAddr}</div>
                    </td>

                    <td style="padding: 5px 4px; vertical-align: middle;">
                        <div style="font-weight: 700; color: #0f172a; font-size: 10.5px;">${rawProduct}</div>
                        <div style="margin-top: 2px;">
                            <span style="display: inline-block; padding: 1px 4px; font-size: 8.5px; font-weight: 600; border-radius: 3px; ${isGrafted ? 'background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca;' : 'background-color: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;'}">${isGrafted ? 'Grafted' : 'Non-Grafted'}</span>
                        </div>
                    </td>

                    <td style="padding: 5px 4px; vertical-align: middle;">
                        <div style="background-color: #ffffff; border: 1px solid #cbd5e1; border-radius: 4px; padding: 3px 5px; box-sizing: border-box; width: 100%;">
                            <table class="pdf-qty-table" style="width: 100% !important; border-collapse: collapse !important; border: 0 !important; margin: 0 !important; padding: 0 !important; background: transparent !important;">
                                <tbody>
                                    <tr style="border: 0 !important; background: transparent !important;">
                                        <td style="border: 0 !important; padding: 1px 0 !important; color: #64748b; font-size: 8.5px; font-weight: 600; text-align: left; vertical-align: middle; background: transparent !important; line-height: 1.2;">Order:</td>
                                        <td style="border: 0 !important; padding: 1px 0 !important; font-weight: 700; color: #0284c7; font-size: 9.5px; text-align: right; vertical-align: middle; background: transparent !important; line-height: 1.2;">${orderQty.toLocaleString()}</td>
                                    </tr>
                                    <tr style="border: 0 !important; background: transparent !important;">
                                        <td style="border: 0 !important; padding: 1px 0 !important; color: #64748b; font-size: 8.5px; font-weight: 600; text-align: left; vertical-align: middle; background: transparent !important; line-height: 1.2;">Alloc:</td>
                                        <td style="border: 0 !important; padding: 1px 0 !important; font-weight: 700; color: #16a34a; font-size: 9.5px; text-align: right; vertical-align: middle; background: transparent !important; line-height: 1.2;">${allocQty.toLocaleString()}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </td>

                    <td style="padding: 5px 4px; vertical-align: middle;">
                        <div style="font-size: 9.5px; font-weight: 700; color: #1e3a8a;">Pro: ${orderNo}</div>
                        <div style="font-size: 9px; color: #0d9488; margin-top: 1px; font-weight: 600;">🏠 ${polyhouseNo}</div>
                        <div style="font-size: 8.5px; color: #64748b; margin-top: 1px;">🛏️ Beds: ${bedNo}</div>
                    </td>
                </tr>
            `;
        });

        let docHtml = `
            <div style="padding: 0; color: #0f172a; width: 100%;">
                <!-- Header -->
                <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #0d9488; padding-bottom: 10px; margin-bottom: 12px;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            ${krfCompanyLogo ? `
                                <img src="${krfCompanyLogo}" alt="Karjee Farm" style="height: 46px; width: auto; max-width: 65px; object-fit: contain; vertical-align: middle;" />
                            ` : `
                                <img src="../img/logo/logo.png" alt="Karjee Farm" style="height: 46px; width: auto; max-width: 65px; object-fit: contain; vertical-align: middle;" />
                            `}
                            <div>
                                <h2 style="margin: 0; font-size: 21px; font-weight: 900; color: #0f172a; letter-spacing: 0.5px; text-transform: uppercase; line-height: 1.1;">KARJEE FARM</h2>
                                <div style="display: flex; align-items: center; gap: 8px; margin-top: 3px; flex-wrap: wrap;">
                                    <span style="font-size: 12.5px; font-weight: 700; color: #0d9488;">Final Dispatch Timetable Schedule Report</span>
                                    <span id="dispatchPdfActiveStatusBadge" style="display: inline-block; padding: 2px 8px; font-size: 10.5px; font-weight: 800; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.3px; background-color: ${activeStatusTheme.bg}; color: ${activeStatusTheme.color}; border: 1px solid ${activeStatusTheme.border};">
                                        ${activeStatusTitle}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div style="text-align: right; font-size: 11px; color: #475569;">
                        <div><b>Week Period:</b> <span style="font-weight: 700; color: #0f172a;">${weekVal}</span></div>
                        <div><b>Month:</b> ${monthText} (${yearVal})</div>
                        <div><b>Generated On:</b> ${generatedDate}</div>
                    </div>
                </div>

                <!-- Schedule Summary Cards -->
                <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                    <div style="flex: 1; border: 1px solid #cbd5e1; border-radius: 4px; padding: 5px 8px; background: #f8fafc; text-align: center;">
                        <div style="font-size: 9.5px; color: #64748b; font-weight: 600; text-transform: uppercase;">Total Orders</div>
                        <div style="font-size: 14px; font-weight: 800; color: #0f172a;">${totalOrders}</div>
                    </div>
                    <div style="flex: 1; border: 1px solid #bfdbfe; border-radius: 4px; padding: 5px 8px; background: #eff6ff; text-align: center;">
                        <div style="font-size: 9.5px; color: #1e40af; font-weight: 600; text-transform: uppercase;">Total Order Qty</div>
                        <div style="font-size: 14px; font-weight: 800; color: #1d4ed8;">${totalOrderQty.toLocaleString()}</div>
                    </div>
                    <div style="flex: 1; border: 1px solid #bbf7d0; border-radius: 4px; padding: 5px 8px; background: #f0fdf4; text-align: center;">
                        <div style="font-size: 9.5px; color: #166534; font-weight: 600; text-transform: uppercase;">Total Allocated</div>
                        <div style="font-size: 14px; font-weight: 800; color: #15803d;">${totalAllocQty.toLocaleString()}</div>
                    </div>
                    <div style="flex: 1; border: 1px solid #cbd5e1; border-radius: 4px; padding: 5px 8px; background: #f8fafc; text-align: center;">
                        <div style="font-size: 9.5px; color: #475569; font-weight: 600; text-transform: uppercase;">Total Dispatched</div>
                        <div style="font-size: 14px; font-weight: 800; color: #334155;">${totalDispQty.toLocaleString()}</div>
                    </div>
                </div>

                <!-- Schedule Table -->
                <table style="width: 100%; table-layout: fixed; border-collapse: collapse; margin-bottom: 12px; border: 1px solid #cbd5e1;">
                    <colgroup>
                        <col style="width: 30px;">
                        <col style="width: 80px;">
                        <col style="width: 95px;">
                        <col style="width: 160px;">
                        <col style="width: 120px;">
                        <col style="width: 90px;">
                        <col style="width: 135px;">
                    </colgroup>
                    <thead>
                        <tr style="background-color: #0d9488; color: #ffffff; font-size: 9.5px; text-transform: uppercase;">
                            <th style="padding: 6px 3px; text-align: center; border: 1px solid #0f766e;">#</th>
                            <th style="padding: 6px 4px; text-align: left; border: 1px solid #0f766e;">Delivery Date</th>
                            <th style="padding: 6px 4px; text-align: left; border: 1px solid #0f766e;">Order Info</th>
                            <th style="padding: 6px 4px; text-align: left; border: 1px solid #0f766e;">Client Details</th>
                            <th style="padding: 6px 4px; text-align: left; border: 1px solid #0f766e;">Product</th>
                            <th style="padding: 6px 4px; text-align: center; border: 1px solid #0f766e;">Quantities</th>
                            <th style="padding: 6px 4px; text-align: left; border: 1px solid #0f766e;">Production & Location</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${tableRowsHtml}
                    </tbody>
                </table>

                <!-- Footer Note -->
                <div style="border-top: 1px solid #e2e8f0; padding-top: 6px; display: flex; justify-content: space-between; font-size: 9px; color: #64748b;">
                    <div>Karjee Farm - Confidential Operational Dispatch Schedule</div>
                    <div>Page Generated From Dispatch Management Hub</div>
                </div>
            </div>
        `;

        return docHtml;
    }
});
</script>

