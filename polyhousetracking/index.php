<?php
include('../header.php');
?>
<!-- QZ Tray & QRCode.js Libraries -->
<script src="../krf-orderandsales/js/qz-tray.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<style>
:root {
    --ph-border: #dbe3ef;
    --ph-muted: #64748b;
    --ph-blue: #4f46e5;
    --ph-text: #1f2937;
    --ph-bg: #f4f7fb;
}

/* =========================================================
   MAIN REPORT
========================================================= */

.ph-report {
    padding: 1.35rem;
    background: var(--ph-bg);
    min-height: 100vh;
    font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont,
                 "Segoe UI", Roboto, sans-serif;
    color: var(--ph-text);
}


/* =========================================================
   COMMON CARDS
========================================================= */

.ph-title,
.ph-filter-card,
.ph-bed-card {
    background: #ffffff;
    border: 1px solid var(--ph-border);
    border-radius: 14px;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.06);
}


/* =========================================================
   TITLE
========================================================= */

.ph-title {
    padding: 1.15rem 1.4rem;
    margin-bottom: 1.1rem;
    border-left: 6px solid var(--ph-blue);

    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.ph-title h4 {
    margin: 0;
    font-size: 1.45rem;
    font-weight: 800;
    color: #1e293b;
}

.ph-title p {
    margin: 0.25rem 0 0;
    color: var(--ph-muted);
    font-size: 1rem;
}

.ph-title-meta {
    min-width: 235px;
    text-align: right;
    font-size: 0.84rem;
    line-height: 1.55;
    color: #64748b;
}

.ph-title-meta .meta-label {
    display: block;
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.35px;
    color: #475569;
}

.ph-title-meta .meta-value {
    font-weight: 800;
    color: #1e293b;
}


/* =========================================================
   FILTER CARD
========================================================= */

.ph-filter-card {
    padding: 1.15rem;
    margin-bottom: 1.1rem;
}

.ph-filter-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(150px, 1fr));
    gap: 0.85rem;
}

.ph-filter-group label {
    display: block;
    margin: 0 0 0.35rem;
    font-size: 0.82rem;
    font-weight: 800;
    text-transform: uppercase;
    color: #475569;
    letter-spacing: 0.25px;
}

.ph-control {
    width: 100%;
    height: 42px;
    border: 1px solid #cfd8e6;
    border-radius: 8px;
    background: #f8fafc;
    padding: 0.5rem 0.75rem;
    font-size: 0.98rem;
    color: #1f2937;
}

.ph-control:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.10);
    background: #ffffff;
}


/* =========================================================
   SEARCH / BUTTONS
========================================================= */

.ph-search-row {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 0.85rem;
    margin-top: 0.85rem;
    align-items: end;
}

.ph-actions {
    display: flex;
    gap: 0.55rem;
}

.ph-btn {
    height: 42px;
    border-radius: 8px;
    padding: 0 1rem;
    font-size: 0.92rem;
    font-weight: 700;
    border: 1px solid transparent;
    cursor: pointer;
}

.ph-btn-light {
    background: #eef2f7;
    border-color: #dbe3ef;
    color: #334155;
}

.ph-btn-light:hover {
    background: #e2e8f0;
}

.ph-btn-success {
    background: #198754;
    color: #ffffff;
}

.ph-btn-success:hover {
    background: #157347;
}

.ph-btn-danger {
    background: #dc3545;
    color: #ffffff;
}

.ph-btn-danger:hover {
    background: #bb2d3b;
}


/* =========================================================
   KPI SUMMARY
========================================================= */

.ph-summary {
    display: grid;
    grid-template-columns: repeat(9, 1fr);
    gap: 0.75rem;
    margin-bottom: 1.1rem;
}

.ph-kpi {
    border: 1px solid var(--ph-border);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 7px rgba(15, 23, 42, 0.05);
    background: #ffffff;
}

/* KPI label/header */
.ph-kpi .label {
    padding: 0.55rem 0.85rem;
    font-size: 0.79rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.35px;
    border-bottom: 1px solid rgba(148, 163, 184, 0.22);
}

/* KPI value */
.ph-kpi .value {
    padding: 0.45rem 0.85rem 0.7rem;
    font-size: 1.55rem;
    line-height: 1.1;
    font-weight: 900;
    color: #111827;
}


/* 1. Total Beds */
.ph-kpi:nth-child(1) {
    background: #f5f7ff;
}
.ph-kpi:nth-child(1) .label {
    background: #e8efff;
    color: #1d4ed8;
}

/* 2. Occupied Beds */
.ph-kpi:nth-child(2) {
    background: #f1fdfc;
}
.ph-kpi:nth-child(2) .label {
    background: #ddf7f4;
    color: #0f766e;
}

/* 3. Unoccupied Beds */
.ph-kpi:nth-child(3) {
    background: #f8fafc;
}
.ph-kpi:nth-child(3) .label {
    background: #eef2f7;
    color: #475569;
}

/* 4. Plant Count */
.ph-kpi:nth-child(4) {
    background: #f5f3ff;
}
.ph-kpi:nth-child(4) .label {
    background: #ede9fe;
    color: #6d28d9;
}

/* 5. Total Trays */
.ph-kpi:nth-child(5) {
    background: #fff9f1;
}
.ph-kpi:nth-child(5) .label {
    background: #fff0dc;
    color: #b45309;
}

/* 6. Ready to Sale */
.ph-kpi:nth-child(6) {
    background: #f0fdf4;
}
.ph-kpi:nth-child(6) .label {
    background: #dcfce7;
    color: #15803d;
}

/* 7. Verified */
.ph-kpi:nth-child(7) {
    background: #f0f9ff;
}
.ph-kpi:nth-child(7) .label {
    background: #e0f2fe;
    color: #0369a1;
}

/* 8. Unverified */
.ph-kpi:nth-child(8) {
    background: #fff7ed;
}
.ph-kpi:nth-child(8) .label {
    background: #ffedd5;
    color: #c2410c;
}

/* 9. Germination Failure */
.ph-kpi:nth-child(9) {
    background: #fff5f5;
}
.ph-kpi:nth-child(9) .label {
    background: #fde5e5;
    color: #c62828;
}

/* Card header shifted/non-shifted pills */
.ph-bed-pill-shifted {
    background: #ecfdf5 !important;
    color: #047857 !important;
    border: 1px solid #a7f3d0 !important;
    padding: 3px 9px;
    border-radius: 6px;
    font-size: 0.78rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
}
.ph-bed-pill-nonshifted {
    background: #fffbeb !important;
    color: #b45309 !important;
    border: 1px solid #fde68a !important;
    padding: 3px 9px;
    border-radius: 6px;
    font-size: 0.78rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
}

/* Common badge styles in order line */
.ph-badge-status {
    display: inline-flex;
    align-items: center;
    padding: 2px 7px;
    border-radius: 6px;
    font-size: 0.74rem;
    font-weight: 700;
    vertical-align: middle;
    white-space: nowrap;
    gap: 3px;
}
.ph-badge-ready {
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #a7f3d0;
}
.ph-badge-shifted {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #6ee7b7;
}
.ph-badge-nonshifted {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fcd34d;
}
.ph-badge-healthy {
    background: #f0fdf4;
    color: #15803d;
    border: 1px solid #bbf7d0;
}
.ph-badge-unhealthy {
    background: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fecaca;
}
.ph-badge-polyhouse {
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
}
.ph-badge-openstock {
    background: #faf5ff;
    color: #6b21a8;
    border: 1px solid #e9d5ff;
}
.ph-badge-grafting {
    background: #fff7ed;
    color: #c2410c;
    border: 1px solid #fed7aa;
}
.ph-badge-healing {
    background: #fdf2f8;
    color: #9d174d;
    border: 1px solid #fbcfe8;
}
.ph-badge-hardening {
    background: #fefce8;
    color: #854d0e;
    border: 1px solid #fef08a;
}
.ph-badge-clip {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #cbd5e1;
}
.ph-badge-internal {
    background: #ecfeff;
    color: #0e7490;
    border: 1px solid #a5f3fc;
}
.ph-badge-final {
    background: #eef2ff;
    color: #4338ca;
    border: 1px solid #c7d2fe;
}
.ph-badge-grafted {
    background: #f0fdfa;
    color: #0f766e;
    border: 1px solid #99f6e4;
}
.ph-order-ready {
    background: #f0fdf4 !important;
    border-left: 3px solid #10b981;
}

/* QR Code Print Button & Modal Styling */
.ph-btn-print-qr {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #ffffff;
    color: #4f46e5;
    border: 1px solid #c7d2fe;
    padding: 3px 10px;
    border-radius: 6px;
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
}
.ph-btn-print-qr:hover {
    background: #4f46e5;
    color: #ffffff;
    border-color: #4338ca;
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(79, 70, 229, 0.2);
}

.ph-btn-update-status {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #ffffff;
    color: #059669;
    border: 1px solid #a7f3d0;
    padding: 3px 10px;
    border-radius: 6px;
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
}
.ph-btn-update-status:hover {
    background: #059669;
    color: #ffffff;
    border-color: #047857;
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(5, 150, 105, 0.25);
}

.ph-btn-edit-qty {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #ffffff;
    color: #0284c7;
    border: 1px solid #bae6fd;
    padding: 3px 10px;
    border-radius: 6px;
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
}
.ph-btn-edit-qty:hover {
    background: #0284c7;
    color: #ffffff;
    border-color: #0369a1;
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(2, 132, 199, 0.25);
}

.ph-btn-remove-order {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #ffffff;
    color: #dc2626;
    border: 1px solid #fecaca;
    padding: 3px 10px;
    border-radius: 6px;
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
}
.ph-btn-remove-order:hover {
    background: #dc2626;
    color: #ffffff;
    border-color: #b91c1c;
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(220, 38, 38, 0.25);
}

.qr-stickers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 12px;
}

.qr-sticker-item {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.03);
}

.qr-sticker-box {
    width: 65px;
    height: 65px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #ffffff;
}
.qr-sticker-box img, .qr-sticker-box canvas {
    width: 62px !important;
    height: 62px !important;
}

.qr-sticker-info {
    flex: 1;
    min-width: 0;
}
.qr-st-title {
    font-size: 0.82rem;
    font-weight: 800;
    color: #0f172a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.qr-st-prod {
    font-size: 0.74rem;
    font-weight: 700;
    color: #475569;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.qr-st-meta {
    font-size: 0.7rem;
    color: #64748b;
    margin-top: 2px;
}
.qr-st-code {
    font-family: monospace;
    font-size: 0.72rem;
    font-weight: 700;
    color: #4f46e5;
    background: #eef2ff;
    padding: 1px 4px;
    border-radius: 3px;
    display: inline-block;
    margin-top: 3px;
}


/* =========================================================
   BED CARD
========================================================= */

.ph-bed-card {
    overflow: hidden;
    margin-bottom: 1rem;
    background: #ffffff;
    border-left: 5px solid #20c997;
}

.ph-bed-head {
    padding: 0.85rem 1.05rem;
    background: linear-gradient(
        90deg,
        #e8fff7 0%,
        #f0f7ff 100%
    );

    border-bottom: 1px solid #cfe9df;

    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.ph-bed-name {
    font-size: 1.18rem;
    font-weight: 900;
    color: #0f172a;
}

.ph-bed-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.45rem;
}


/* =========================================================
   BED PILLS
========================================================= */

.ph-pill {
    font-size: 0.84rem;
    font-weight: 700;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    border-radius: 20px;
    padding: 0.3rem 0.65rem;
    color: #334155;
}

.ph-pill.good {
    background: #dcfce7;
    border-color: #86efac;
    color: #166534;
}

.ph-pill.warn {
    background: #ffedd5;
    border-color: #fdba74;
    color: #9a3412;
}

.ph-pill.bad {
    background: #fee2e2;
    border-color: #fca5a5;
    color: #b91c1c;
}


/* =========================================================
   ORDER DETAILS
========================================================= */

.ph-order {
    padding: 0.78rem 1.05rem;
    border-bottom: 1px solid #e8edf4;
    background: #ffffff;
}

.ph-order:nth-child(even) {
    background: #f8fbff;
}

.ph-order:last-child {
    border-bottom: none;
}

.ph-order-row {
    display: grid;
    grid-template-columns: 1.4fr 2fr 1.65fr;
    gap: 1rem;
    align-items: center;
}

.ph-order-row + .ph-order-row {
    margin-top: 0.42rem;
}

.ph-order-no {
    font-weight: 900;
    color: #2563eb;
    font-size: 0.98rem;
}

.ph-pso {
    color: #64748b;
    font-size: 0.88rem;
    margin-left: 0.35rem;
}

.ph-product {
    font-weight: 800;
    color: #111827;
    font-size: 0.98rem;
}

.ph-order-client {
    font-size: 0.82rem;
    font-weight: 600;
    color: #475569;
    margin-top: 2px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.ph-order-client strong {
    color: #0f172a;
    font-weight: 700;
}


/* Metrics and Dates */

.ph-metrics,
.ph-dates {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    font-size: 0.90rem;
    color: #475569;
    line-height: 1.5;
}

.ph-metrics strong,
.ph-dates strong {
    color: #111827;
    font-weight: 800;
}

.ph-fail {
    color: #b91c1c !important;
}

/* Status-wise Breakdown Row */
.ph-status-breakdown-row {
    margin-top: 0.5rem !important;
    padding-top: 0.5rem;
    border-top: 1px dashed #e2e8f0;
}

.ph-status-counts-wrap {
    grid-column: 1 / -1;
    display: flex;
    align-items: center;
    gap: 0.65rem;
    flex-wrap: wrap;
}

.ph-status-counts-label {
    font-size: 0.78rem;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    display: inline-flex;
    align-items: center;
}

.ph-status-pills-list {
    display: flex;
    gap: 0.4rem;
    flex-wrap: wrap;
    align-items: center;
}

.ph-status-pill {
    display: inline-flex;
    align-items: center;
    padding: 2.5px 8.5px;
    border-radius: 6px;
    font-size: 0.78rem;
    font-weight: 600;
    line-height: 1.35;
    border: 1px solid transparent;
    cursor: pointer;
    transition: all 0.18s ease;
    user-select: none;
}

.ph-status-pill:hover {
    transform: translateY(-1.5px);
    box-shadow: 0 3px 8px rgba(0,0,0,0.12);
    filter: brightness(0.95);
}

.ph-status-pill:active {
    transform: translateY(0);
}

.ph-status-pill strong {
    margin-left: 4px;
    font-weight: 800;
}

.ph-sc-polyhouse { background: #f1f5f9; color: #334155; border-color: #cbd5e1; }
.ph-sc-ready { background: #ecfdf5; color: #065f46; border-color: #a7f3d0; }
.ph-sc-open { background: #f0fdfa; color: #0f766e; border-color: #99f6e4; }
.ph-sc-internal { background: #fffbeb; color: #92400e; border-color: #fde68a; }
.ph-sc-loaded { background: #ede9fe; color: #5b21b6; border-color: #ddd6fe; }
.ph-sc-final { background: #eff6ff; color: #1e40af; border-color: #bfdbfe; }
.ph-sc-opensale { background: #fdf2f8; color: #9d174d; border-color: #fbcfe8; }
.ph-sc-grafting { background: #fff1f2; color: #9f1239; border-color: #fecdd3; }
.ph-sc-healing { background: #fdf4ff; color: #86198f; border-color: #f5d0fe; }
.ph-sc-hardening { background: #f0f9ff; color: #0369a1; border-color: #bae6fd; }
.ph-sc-clip { background: #f8fafc; color: #475569; border-color: #cbd5e1; }
.ph-sc-emptyshifting { background: #f1f5f9; color: #334155; border-color: #94a3b8; }
.ph-sc-emptygrafting { background: #fef3c7; color: #92400e; border-color: #fde68a; }
.ph-sc-removed { background: #fef2f2; color: #991b1b; border-color: #fecaca; }
.ph-sc-grafted { background: #f0fdf4; color: #166534; border-color: #bbf7d0; }
.ph-sc-shifted { background: #d1fae5; color: #065f46; border-color: #6ee7b7; }
.ph-sc-nonshifted { background: #fffbeb; color: #92400e; border-color: #fde68a; }
.ph-sc-healthy { background: #f0fdf4; color: #15803d; border-color: #bbf7d0; }
.ph-sc-unhealthy { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }
.ph-sc-germfail { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }
.ph-sc-default { background: #f8fafc; color: #334155; border-color: #cbd5e1; }


/* =========================================================
   EMPTY RESULT
========================================================= */

.ph-empty {
    padding: 2rem;
    text-align: center;
    color: #64748b;
    background: #ffffff;
    border: 1px dashed #cbd5e1;
    border-radius: 12px;
    font-size: 1rem;
}


/* =========================================================
   TABLET
========================================================= */

@media (max-width: 1100px) {

    .ph-filter-grid {
        grid-template-columns: repeat(3, 1fr);
    }

    .ph-summary {
        grid-template-columns: repeat(3, 1fr);
    }

    .ph-order-row {
        grid-template-columns: 1fr 2fr;
    }

    .ph-order-row > div:last-child {
        grid-column: 1 / -1;
    }
}


/* =========================================================
   SELECT2 CUSTOM STYLING (MODAL & REPORT)
========================================================= */

.select2-container {
    width: 100% !important;
    touch-action: manipulation;
}

.select2-container--default .select2-selection--single {
    height: 42px !important;
    border: 1px solid #cfd8e6 !important;
    border-radius: 8px !important;
    background-color: #ffffff !important;
    display: flex !important;
    align-items: center !important;
    padding: 0.25rem 0.5rem !important;
    box-shadow: none !important;
    transition: border-color 0.2s, box-shadow 0.2s;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
}

.select2-container--default .select2-selection--single:focus,
.select2-container--default.select2-container--open .select2-selection--single {
    border-color: #6366f1 !important;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15) !important;
    outline: none !important;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #1f2937 !important;
    font-size: 0.95rem !important;
    line-height: 38px !important;
    padding-left: 0.35rem !important;
    padding-right: 1.5rem !important;
}

.select2-container--default .select2-selection--single .select2-selection__placeholder {
    color: #64748b !important;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 40px !important;
    right: 8px !important;
}

.select2-container--default .select2-selection--single .select2-selection__arrow b {
    border-color: #64748b transparent transparent transparent !important;
}

/* =========================================================
   SELECT2 MULTI-SELECT STYLING (PRODUCT FILTER)
========================================================= */

.select2-container--default .select2-selection--multiple {
    min-height: 42px !important;
    max-height: 120px !important;
    overflow-y: auto !important;
    border: 1px solid #cfd8e6 !important;
    border-radius: 8px !important;
    background-color: #f8fafc !important;
    display: flex !important;
    align-items: center !important;
    flex-wrap: wrap !important;
    padding: 2px 6px !important;
    box-shadow: none !important;
    transition: border-color 0.2s, box-shadow 0.2s;
    touch-action: manipulation;
}

.select2-container--default.select2-container--focus .select2-selection--multiple,
.select2-container--default.select2-container--open .select2-selection--multiple {
    border-color: #6366f1 !important;
    background-color: #ffffff !important;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15) !important;
    outline: none !important;
}

.select2-container--default.select2-container--disabled .select2-selection--multiple {
    background-color: #f1f5f9 !important;
    border-color: #e2e8f0 !important;
    cursor: not-allowed !important;
    opacity: 0.85;
}

.select2-container--default .select2-selection--multiple .select2-selection__rendered {
    display: flex !important;
    flex-wrap: wrap !important;
    align-items: center !important;
    gap: 4px !important;
    padding: 0 !important;
    margin: 0 !important;
    width: 100% !important;
}

.select2-container--default .select2-selection--multiple .select2-selection__choice {
    background-color: #e0e7ff !important;
    border: 1px solid #c7d2fe !important;
    color: #3730a3 !important;
    font-size: 0.82rem !important;
    font-weight: 700 !important;
    border-radius: 6px !important;
    padding: 2px 8px !important;
    margin: 2px 2px !important;
    display: inline-flex !important;
    align-items: center !important;
    line-height: 1.4 !important;
}

.select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
    color: #4f46e5 !important;
    margin-right: 6px !important;
    font-weight: bold !important;
    cursor: pointer !important;
    border: none !important;
    background: transparent !important;
    transition: color 0.15s ease;
}

.select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
    color: #ef4444 !important;
}

.select2-container--default .select2-selection--multiple .select2-search--inline {
    margin: 0 !important;
    padding: 0 !important;
    flex-grow: 1 !important;
}

.select2-container--default .select2-selection--multiple .select2-search--inline .select2-search__field {
    height: 32px !important;
    line-height: 32px !important;
    margin: 0 !important;
    padding: 0 4px !important;
    border: none !important;
    outline: none !important;
    box-shadow: none !important;
    font-size: 0.92rem !important;
    color: #1f2937 !important;
    background: transparent !important;
}

.select2-container--default .select2-selection--multiple .select2-search--inline .select2-search__field::placeholder {
    color: #64748b !important;
}

.select2-container--default .select2-selection--multiple .select2-selection__clear {
    margin-right: 8px !important;
    font-size: 1.15rem !important;
    font-weight: bold !important;
    color: #94a3b8 !important;
    cursor: pointer !important;
}

.select2-container--default .select2-selection--multiple .select2-selection__clear:hover {
    color: #ef4444 !important;
}

.select2-dropdown {
    border: 1px solid #cbd5e1 !important;
    border-radius: 8px !important;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.18) !important;
    z-index: 99999 !important;
    overflow: hidden !important;
    background: #ffffff !important;
    touch-action: manipulation;
}

.select2-search--dropdown {
    padding: 8px !important;
    background: #f8fafc !important;
    border-bottom: 1px solid #e2e8f0 !important;
}

.select2-search--dropdown .select2-search__field {
    width: 100% !important;
    border: 1px solid #cfd8e6 !important;
    border-radius: 6px !important;
    padding: 8px 12px !important;
    font-size: 0.95rem !important;
    outline: none !important;
    background: #ffffff !important;
    touch-action: manipulation;
    -webkit-appearance: none;
}

.select2-search--dropdown .select2-search__field:focus {
    border-color: #6366f1 !important;
    box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.15) !important;
}

.select2-results {
    max-height: 260px;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
}

.select2-results__option {
    padding: 9px 12px !important;
    font-size: 0.92rem !important;
    color: #1e293b !important;
    cursor: pointer !important;
    touch-action: manipulation;
    -webkit-tap-highlight-color: rgba(99, 102, 241, 0.1);
    min-height: 38px;
    display: flex;
    align-items: center;
}

.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: #4f46e5 !important;
    color: #ffffff !important;
}

.select2-container--default .select2-results__option[aria-selected=true] {
    background-color: #e0e7ff !important;
    color: #3730a3 !important;
    font-weight: 600 !important;
}

@media (max-width: 768px) {
    .select2-search--dropdown .select2-search__field {
        font-size: 16px !important; /* Prevents auto-zoom on iOS */
    }
    .select2-results__option {
        padding: 12px 14px !important;
        font-size: 1rem !important;
    }
}

/* =========================================================
   STOCK CATEGORY RADIO PILLS (MODAL)
========================================================= */

.stock-type-group {
    display: flex;
    gap: 0.65rem;
    height: 44px;
    margin-top: 0.25rem;
}

.stock-type-pill {
    flex: 1;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    height: 44px !important;
    margin: 0 !important;
    padding: 0 14px !important;
    background: #ffffff !important;
    border: 1.5px solid #dbe3ef !important;
    border-radius: 8px !important;
    font-size: 0.92rem !important;
    font-weight: 700 !important;
    color: #475569 !important;
    cursor: pointer !important;
    transition: all 0.15s ease-in-out !important;
    user-select: none !important;
    touch-action: manipulation !important;
    -webkit-tap-highlight-color: transparent !important;
}

.stock-type-pill:hover {
    border-color: #94a3b8 !important;
    background: #f8fafc !important;
}

.stock-type-pill input[type="radio"] {
    width: 17px !important;
    height: 17px !important;
    min-width: 17px !important;
    min-height: 17px !important;
    margin: 0 8px 0 0 !important;
    padding: 0 !important;
    cursor: pointer !important;
    vertical-align: middle !important;
    display: inline-block !important;
    position: static !important;
    opacity: 1 !important;
    flex-shrink: 0 !important;
}

.stock-type-pill.active-ready,
.stock-type-pill:has(input[value="readystock"]:checked) {
    border-color: #4f46e5 !important;
    background: #eef2ff !important;
    color: #4338ca !important;
    box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.2) !important;
}

.stock-type-pill.active-ready input[type="radio"] {
    accent-color: #4f46e5 !important;
}

.stock-type-pill.active-open,
.stock-type-pill:has(input[value="openstock"]:checked) {
    border-color: #059669 !important;
    background: #ecfdf5 !important;
    color: #047857 !important;
    box-shadow: 0 0 0 2px rgba(5, 150, 105, 0.2) !important;
}

.stock-type-pill.active-open input[type="radio"] {
    accent-color: #059669 !important;
}

.ph-control.is-available {
    border-color: #10b981 !important;
    background-color: #f0fdf4 !important;
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2) !important;
}

.ph-control.is-unavailable {
    border-color: #ef4444 !important;
    background-color: #fef2f2 !important;
    box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2) !important;
}

/* =========================================================
   RADIO PILL STYLING (MODAL)
========================================================= */

.rs-radio-group {
    display: flex;
    gap: 0.5rem;
    height: 42px;
}

.rs-radio-pill {
    flex: 1;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    height: 42px !important;
    margin: 0 !important;
    padding: 0 10px !important;
    background: #ffffff !important;
    border: 1.5px solid #dbe3ef !important;
    border-radius: 8px !important;
    font-size: 0.88rem !important;
    font-weight: 600 !important;
    color: #475569 !important;
    cursor: pointer !important;
    transition: all 0.15s ease-in-out !important;
    user-select: none !important;
    line-height: 1 !important;
}

.rs-radio-pill:hover {
    border-color: #93c5fd !important;
    background: #f8fafc !important;
    color: #1e293b !important;
}

.rs-radio-pill input[type="radio"] {
    width: 16px !important;
    height: 16px !important;
    min-width: 16px !important;
    min-height: 16px !important;
    margin: 0 8px 0 0 !important;
    padding: 0 !important;
    cursor: pointer !important;
    accent-color: #4f46e5 !important;
    vertical-align: middle !important;
    display: inline-block !important;
    position: static !important;
    opacity: 1 !important;
    flex-shrink: 0 !important;
}

.rs-radio-pill.active,
.rs-radio-pill:has(input[type="radio"]:checked) {
    border-color: #4f46e5 !important;
    background: #eef2ff !important;
    color: #4f46e5 !important;
    font-weight: 700 !important;
    box-shadow: 0 0 0 1px #4f46e5 !important;
}

.rs-radio-pill span {
    display: inline-block !important;
    line-height: 1 !important;
    margin: 0 !important;
    padding: 0 !important;
}

/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 767px) {

    .ph-report {
        padding: 0.75rem;
    }

    .ph-filter-grid {
        grid-template-columns: 1fr;
    }

    .ph-search-row {
        grid-template-columns: 1fr;
    }

    .ph-summary {
        grid-template-columns: repeat(2, 1fr);
    }

    .ph-bed-head {
        align-items: flex-start;
        flex-direction: column;
    }

    .ph-order-row {
        display: block;
    }

    .ph-order-row > div {
        margin-bottom: 0.35rem;
    }

    .ph-actions {
        flex-wrap: wrap;
    }

    /* Mobile modal bottom spacing & sticky footer */
    #modalAddReadyStock {
        padding-bottom: 3.5rem !important;
    }

    #modalAddReadyStock .modal-dialog {
        margin: 0.5rem auto 6rem auto !important;
        width: 95% !important;
        max-width: 95% !important;
    }

    #modalAddReadyStock .modal-content {
        max-height: 88vh !important;
        display: flex !important;
        flex-direction: column !important;
        border-radius: 12px !important;
    }

    #modalAddReadyStock .modal-body {
        max-height: calc(88vh - 130px) !important;
        overflow-y: auto !important;
        padding: 1rem !important;
        padding-bottom: 3rem !important;
        -webkit-overflow-scrolling: touch !important;
    }

    #modalAddReadyStock .modal-footer {
        position: sticky !important;
        bottom: 0 !important;
        background: #ffffff !important;
        z-index: 100 !important;
        padding: 0.85rem 1rem !important;
        border-top: 1px solid #e2e8f0 !important;
        box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.08) !important;
    }

    #modalAddReadyStock .modal-footer button {
        height: 44px !important;
        font-size: 0.95rem !important;
    }
}


/* =========================================================
   PRINT / PDF HEADING
========================================================= */

.ph-print-heading {
    display: none;
}

@media print {

    @page {
        size: A4 landscape;
        margin: 8mm;
    }

    body * {
        visibility: hidden;
    }

    .ph-report,
    .ph-report * {
        visibility: visible;
    }

    /*
       IMPORTANT: keep the report in normal document flow.
       position:absolute can make Chrome push complete cards
       to the next page even when page 1 still has space.
    */
    .ph-report {
        position: static !important;
        left: auto !important;
        top: auto !important;
        width: 100% !important;
        min-height: auto !important;
        background: #ffffff !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    .ph-filter-card,
    .ph-actions,
    .ph-title {
        display: none !important;
    }

    /* Formal PDF heading */
    .ph-print-heading {
        display: block !important;
        visibility: visible !important;
        margin: 0 0 7px 0 !important;
        padding: 0 0 7px 0 !important;
        border-bottom: 2px solid #334155;
        text-align: center;
        page-break-after: avoid !important;
        break-after: avoid-page !important;
    }

    .ph-print-heading * {
        visibility: visible !important;
    }

    .ph-print-heading .print-title {
        margin: 0 !important;
        font-size: 18px !important;
        line-height: 1.2 !important;
        font-weight: 800 !important;
        color: #111827 !important;
    }

    .ph-print-heading .print-subtitle {
        margin: 2px 0 6px !important;
        font-size: 9px !important;
        line-height: 1.25 !important;
        color: #475569 !important;
    }

    .ph-print-info {
        display: grid !important;
        grid-template-columns: 1fr 1fr 1fr !important;
        gap: 6px !important;
        padding: 5px 7px !important;
        background: #f8fafc !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 4px !important;
        text-align: left !important;
        font-size: 9px !important;
        color: #334155 !important;
    }

    .ph-print-info strong {
        color: #0f172a !important;
    }

    /* KPI summary stays together */
    .ph-summary {
        display: grid !important;
        grid-template-columns: repeat(9, 1fr) !important;
        gap: 6px !important;
        margin: 7px 0 8px !important;
        page-break-inside: avoid !important;
        break-inside: avoid-page !important;
    }

    .ph-kpi {
        box-shadow: none !important;
    }

    .ph-kpi .label {
        padding: 4px 7px !important;
        font-size: 8px !important;
    }

    .ph-kpi .value {
        padding: 4px 7px 6px !important;
        font-size: 15px !important;
    }

    /*
       A full bed is allowed to continue on the next page.
       This prevents large unused blank areas on page 1.
    */
    .ph-bed-card {
        overflow: visible !important;
        break-inside: auto !important;
        page-break-inside: auto !important;
        margin: 0 0 7px 0 !important;
        box-shadow: none !important;
        border-radius: 7px !important;
    }

    /* Keep bed heading with the first following order where possible */
    .ph-bed-head {
        break-after: avoid-page !important;
        page-break-after: avoid !important;
        padding: 7px 10px !important;

        /* Reset mobile/tablet responsive rules while printing.
           Chrome uses the print viewport for media-query evaluation,
           so max-width rules can otherwise make this vertical and much taller. */
        display: flex !important;
        flex-direction: row !important;
        align-items: center !important;
        justify-content: space-between !important;
    }


    .ph-bed-meta {
        display: flex !important;
        flex-direction: row !important;
        flex-wrap: wrap !important;
        gap: 4px !important;
    }
    .ph-bed-name {
        font-size: 14px !important;
    }

    .ph-pill {
        font-size: 8px !important;
        padding: 3px 7px !important;
    }

    /* Never split one order block across two pages */
    .ph-order {
        break-inside: avoid !important;
        page-break-inside: avoid !important;
        padding: 7px 10px !important;
    }

    .ph-order-row {
        break-inside: avoid !important;
        page-break-inside: avoid !important;

        /* IMPORTANT: override @media(max-width:1100px/767px) for print.
           Without this Chrome changes the 3-column order row to 2 columns/block,
           increasing each order's height and pushing the next bed to page 2. */
        display: grid !important;
        grid-template-columns: 1.2fr 2.2fr 1.65fr !important;
        gap: 8px !important;
        align-items: center !important;
    }

    .ph-order-row > div {
        grid-column: auto !important;
        margin-bottom: 0 !important;
    }

    .ph-order-row + .ph-order-row {
        margin-top: 3px !important;
    }

    .ph-order-no,
    .ph-product {
        font-size: 9px !important;
    }

    .ph-order-client {
        font-size: 8px !important;
        line-height: 1.25 !important;
    }

    .ph-pso {
        font-size: 8px !important;
    }

    .ph-metrics,
    .ph-dates {
        font-size: 8px !important;
        line-height: 1.35 !important;
        gap: 7px !important;
    }
}

/* =========================================================
   OCCUPIED TRAYS MODAL (#modalEditOrderQty) TABLE & CHECKBOXES
========================================================= */

#modalEditOrderQty .table {
    table-layout: fixed;
    width: 100%;
    margin-bottom: 0;
}

#modalEditOrderQty th,
#modalEditOrderQty td {
    vertical-align: middle !important;
}

#modalEditOrderQty th.col-edit-ord-chk,
#modalEditOrderQty td.col-edit-ord-chk {
    width: 44px !important;
    min-width: 44px !important;
    max-width: 44px !important;
    text-align: center !important;
    padding: 6px 2px !important;
}

#modalEditOrderQty th.col-edit-ord-idx,
#modalEditOrderQty td.col-edit-ord-idx {
    width: 50px !important;
    min-width: 50px !important;
    max-width: 50px !important;
    text-align: center !important;
    padding: 6px 4px !important;
}

#modalEditOrderQty input[type="checkbox"],
#modalEditOrderQty .edit-ord-chk {
    position: static !important;
    display: inline-block !important;
    vertical-align: middle !important;
    margin: 0 auto !important;
    padding: 0 !important;
    width: 17px !important;
    height: 17px !important;
    min-width: 17px !important;
    min-height: 17px !important;
    max-width: 17px !important;
    max-height: 17px !important;
    line-height: 1 !important;
    cursor: pointer !important;
    box-shadow: none !important;
    outline: none !important;
    border-radius: 4px !important;
    appearance: auto !important;
    -webkit-appearance: checkbox !important;
    -moz-appearance: checkbox !important;
    accent-color: #0284c7 !important;
}

#modalEditOrderQty .chk-cell-wrap {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 100% !important;
    height: 100% !important;
    min-height: 22px !important;
}

#modalEditOrderQty #editOrdTraysTbody tr {
    cursor: pointer;
    transition: background-color 0.15s ease;
}

#modalEditOrderQty #editOrdTraysTbody tr:hover {
    background-color: #f1f5f9 !important;
}
</style>

<div class="ph-report" id="polyhouseBedReportArea">

  <!-- Formal heading used only for PDF / browser print -->
  <div class="ph-print-heading" id="phPrintHeading">
    <div class="print-title">Polyhouse Bed-wise Stock &amp; Verification Report</div>
    <div class="print-subtitle">Bed occupancy, order/product allocation, verification and germination failure status.</div>
    <div class="ph-print-info">
      <div><strong>Location:</strong> <span id="phPrintLocation">-</span></div>
      <div><strong>Polyhouse:</strong> <span id="phPrintPolyhouse">-</span></div>
      <div><strong>Generated On:</strong> <span id="phPrintGeneratedOn">-</span></div>
    </div>
  </div>

  <div class="ph-title">
    <div>
      <h4><i class="fa fa-th-large mr-2"></i>Polyhouse Bed-wise Stock & Verification Report</h4>
      <p>Bed occupancy, order/product allocation, verification and germination failure status.</p>
    </div>
    <div class="ph-title-meta" id="phReportMeta" style="display:none">
      <span class="meta-label">Data as of</span>
      <div class="meta-value" id="phGeneratedOn"></div>
      <div id="phReportScope"></div>
    </div>
  </div>

  <div class="ph-filter-card">
    <div class="ph-filter-grid">
      <div class="ph-filter-group"><label>Location *</label><select id="phLocation" class="ph-control"><option value="">Select Location</option></select></div>
      <div class="ph-filter-group"><label>Polyhouse *</label><select id="phPolyhouse" class="ph-control" disabled><option value="">Select Polyhouse</option></select></div>
      <div class="ph-filter-group"><label>Bed</label><select id="phBed" class="ph-control" disabled><option value="">All Beds</option></select></div>
      <div class="ph-filter-group"><label>Product</label><select id="phProduct" class="ph-control" multiple="multiple" disabled data-placeholder="All Products (Select Multiple)"></select></div>
      <div class="ph-filter-group"><label>Verification</label><select id="phVerify" class="ph-control"><option value="">All</option><option value="verified">Verified</option><option value="unverified">Unverified</option><option value="readystock">Ready to Sale</option></select></div>
    </div>
    <div class="ph-search-row">
      <div class="ph-filter-group"><label>Search Stock Order / PSONO / Product</label><input type="text" id="phSearch" class="ph-control" placeholder="Quick search..."></div>
      <div class="ph-actions">
        <?php 
        $currentRoleId = isset($loginroleid) && $loginroleid !== '' ? (int)$loginroleid : (isset($_SESSION['loginroleid']) ? (int)$_SESSION['loginroleid'] : 99);
        if ($currentRoleId <= 3): 
        ?>
        <button type="button" class="ph-btn ph-btn-primary" id="btnOpenAddReadyStock" style="background:#4f46e5;color:#ffffff;border-color:#4338ca;"><i class="fa fa-plus-circle mr-1"></i>Add Ready Stock</button>
        <button type="button" class="ph-btn ph-btn-warning" id="btnOpenDupRsoFinder" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: #ffffff; border-color: #d97706; font-weight: 700; box-shadow: 0 2px 6px rgba(245, 158, 11, 0.25);">
          <i class="fa fa-clone mr-1"></i>Duplicate RSO Finder 
          <span class="badge badge-light ml-1" id="badgeDupRsoCount" style="background:#ffffff; color:#b45309; font-size:0.75rem; border-radius:10px; padding:2px 7px; display:none;">0</span>
        </button>
        <?php endif; ?>
        
        <button type="button" class="ph-btn ph-btn-light" id="btnResetReport"><i class="fa fa-undo mr-1"></i>Reset</button>
        <button type="button" class="ph-btn ph-btn-success" id="btnExcel"><i class="fa fa-file-excel mr-1"></i>Excel</button>
        <button type="button" class="ph-btn ph-btn-danger" id="btnPdf"><i class="fa fa-file-pdf mr-1"></i>PDF</button>
      </div>
    </div>
  </div>

  <div class="ph-summary" id="phSummary" style="display:none"></div>
  <div id="phReportResult"><div class="ph-empty">Select a location and polyhouse to view bed-wise stock.</div></div>

<!-- Modal Update Tray & Health Status -->
<div class="modal fade" id="modalUpdateTrayStatus" tabindex="-1" role="dialog" aria-labelledby="modalUpdateTrayStatusLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 540px; width: 92%;">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
      <div class="modal-header py-3 px-4" style="background: linear-gradient(135deg, #059669 0%, #047857 100%); color: #ffffff;">
        <div class="d-flex align-items-center gap-2.5">
          <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background: rgba(255,255,255,0.2); color: #ffffff; flex-shrink: 0;">
            <i class="fa fa-clipboard-check" style="font-size: 1.15rem;"></i>
          </div>
          <div>
            <h5 class="modal-title font-weight-bold mb-0 text-white" id="modalUpdateTrayStatusLabel" style="font-size: 1.1rem; line-height: 1.2;">
              Update Tray Status
            </h5>
            <small class="text-white-50" style="font-size: 0.8rem;">Update Plant Shifting & Health Details</small>
          </div>
        </div>
        <button type="button" class="btn-close text-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close" style="filter: brightness(0) invert(1); opacity: 0.85; background: none; border: none; font-size: 1.4rem; cursor: pointer;">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <form id="formUpdateTrayStatus" autocomplete="off">
        <input type="hidden" name="projectid" id="statusOrdProjectId" value="">
        <input type="hidden" name="locationid" id="statusOrdLocationId" value="">
        <input type="hidden" name="polyhouseid" id="statusOrdPolyhouseId" value="">
        <input type="hidden" name="bed_no" id="statusOrdBedNo" value="">
        <input type="hidden" name="stockorder_no" id="statusOrdStockOrderNo" value="">
        <input type="hidden" name="psono" id="statusOrdPsoNo" value="">
        <input type="hidden" name="productid" id="statusOrdProductId" value="">
        <input type="hidden" name="tray_id" id="statusOrdTrayId" value="">
        <input type="hidden" id="statusOrdTotalTrays" value="0">
        <input type="hidden" id="statusOrdQtyPerTray" value="209">

        <div class="modal-body p-4" style="background: #f8fafc;">
          <!-- Order Info Summary Card -->
          <div class="bg-white border rounded-3 p-3 mb-3 shadow-2xs" style="border-color: #e2e8f0 !important;">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div>
                <span class="badge bg-primary text-white font-monospace px-2 py-1" id="statusOrdBadgeSo" style="font-size: 0.82rem; background: #059669 !important;">RSO-0000</span>
                <span class="text-muted small ms-1" id="statusOrdBadgePso">/ PSO-0000</span>
              </div>
              <span class="badge bg-light text-dark border px-2 py-1" id="statusOrdBadgeBed" style="font-size: 0.8rem; background: #f1f5f9; border-color: #cbd5e1 !important; color: #334155; font-weight: 600;">Bed-1</span>
            </div>
            <div class="font-weight-bold fw-bold text-dark mb-1" id="statusOrdProductName" style="font-size: 0.95rem; color: #0f172a;">Product Name</div>
            <div class="small text-muted mb-1" style="font-size: 0.82rem;"><i class="fa fa-user text-primary mr-1"></i>Client: <strong id="statusOrdClientName" class="text-dark">N/A</strong></div>
            <div class="text-muted small d-flex align-items-center gap-2 flex-wrap" style="font-size: 0.82rem;">
              <span><i class="fa fa-boxes text-secondary mr-1"></i>Total Occupied: <strong id="statusOrdTotalDisp" class="text-dark">0</strong> Trays</span>
              <span>•</span>
              <span><i class="fa fa-warehouse text-secondary mr-1"></i><span id="statusOrdPolyhouseName">Polyhouse</span></span>
            </div>
          </div>

          <!-- QUESTION 1: Shifting Status -->
          <div class="mb-3 p-3 bg-white rounded-3 border" style="border-color: #e2e8f0 !important;">
            <label class="form-label font-weight-bold fw-bold text-dark mb-2 d-flex justify-content-between align-items-center" style="font-size: 0.9rem;">
              <span><span class="badge bg-dark rounded-circle me-1" style="font-size: 0.72rem; padding: 4px 7px;">1</span> What is plant shifting status? <span class="text-danger">*</span></span>
              <span class="badge" id="statusShiftBadge" style="background: #fffbeb; color: #92400e; font-size: 0.75rem;">Non-Shifted</span>
            </label>
            <div class="stock-type-group" style="height: 42px;">
              <label class="stock-type-pill" id="pillShifted" style="flex:1;">
                <input type="radio" name="shiftingstatus" value="shifted">
                <i class="fa fa-truck-moving mr-1"></i> Shifted
              </label>
              <label class="stock-type-pill active-ready" id="pillNonShifted" style="flex:1;">
                <input type="radio" name="shiftingstatus" value="nonshifted" checked>
                <i class="fa fa-ban mr-1"></i> Non-Shifted
              </label>
            </div>

            <!-- Conditional Germination Fail % for Non-Shifted -->
            <div id="germinationFailSection" class="mt-3 p-2.5 rounded-3 border" style="background: #fffbeb; border-color: #fde68a !important;">
              <label class="form-label font-weight-bold fw-bold text-dark mb-1" style="font-size: 0.84rem; color: #92400e !important;">
                <i class="fa fa-percentage mr-1 text-warning"></i> What is the germination fail % (Aprox)? <span class="text-danger">*</span>
              </label>
              <div class="input-group">
                <input type="number" class="form-control font-weight-bold" id="statusFailPercentInput" name="failerpercent" min="0" max="100" step="0.5" value="0" placeholder="e.g. 10" style="border-color: #fcd34d; font-size: 1rem;">
                <span class="input-group-text bg-white font-weight-bold" style="border-color: #fcd34d; color: #92400e;">%</span>
              </div>
              <div class="d-flex gap-1.5 flex-wrap mt-2 align-items-center">
                <span class="small text-muted" style="font-size: 0.75rem;">Quick Select:</span>
                <button type="button" class="btn btn-xs btn-outline-warning py-0.5 px-1.5 quick-fail-pct" data-pct="5" style="font-size: 0.75rem; border-radius: 4px;">5%</button>
                <button type="button" class="btn btn-xs btn-outline-warning py-0.5 px-1.5 quick-fail-pct" data-pct="10" style="font-size: 0.75rem; border-radius: 4px;">10%</button>
                <button type="button" class="btn btn-xs btn-outline-warning py-0.5 px-1.5 quick-fail-pct" data-pct="15" style="font-size: 0.75rem; border-radius: 4px;">15%</button>
                <button type="button" class="btn btn-xs btn-outline-warning py-0.5 px-1.5 quick-fail-pct" data-pct="20" style="font-size: 0.75rem; border-radius: 4px;">20%</button>
                <button type="button" class="btn btn-xs btn-outline-warning py-0.5 px-1.5 quick-fail-pct" data-pct="25" style="font-size: 0.75rem; border-radius: 4px;">25%</button>
                <button type="button" class="btn btn-xs btn-outline-warning py-0.5 px-1.5 quick-fail-pct" data-pct="30" style="font-size: 0.75rem; border-radius: 4px;">30%</button>
                <button type="button" class="btn btn-xs btn-outline-warning py-0.5 px-1.5 quick-fail-pct" data-pct="50" style="font-size: 0.75rem; border-radius: 4px;">50%</button>
              </div>
            </div>
          </div>

          <!-- QUESTION 2: Health Status -->
          <div class="mb-3 p-3 bg-white rounded-3 border" style="border-color: #e2e8f0 !important;">
            <label class="form-label font-weight-bold fw-bold text-dark mb-2" style="font-size: 0.9rem;">
              <span class="badge bg-dark rounded-circle me-1" style="font-size: 0.72rem; padding: 4px 7px;">2</span> Health Status <span class="text-danger">*</span>
            </label>
            
            <div class="row g-2">
              <div class="col-6">
                <div class="p-2.5 rounded-3 border" style="background: #f0fdf4; border-color: #bbf7d0;">
                  <label for="statusHealthyTraysInput" class="form-label font-weight-bold text-success mb-1" style="font-size: 0.82rem;">
                    <i class="fa fa-heartbeat mr-1"></i> Healthy Tray Count
                  </label>
                  <input type="number" class="form-control font-weight-bold text-success" id="statusHealthyTraysInput" name="healthy_trays" min="0" step="1" value="0" required style="border-color: #86efac; font-size: 1.15rem; background: #ffffff;">
                </div>
              </div>
              <div class="col-6">
                <div class="p-2.5 rounded-3 border" style="background: #fef2f2; border-color: #fecaca;">
                  <label for="statusUnhealthyTraysInput" class="form-label font-weight-bold text-danger mb-1" style="font-size: 0.82rem;">
                    <i class="fa fa-exclamation-triangle mr-1"></i> Unhealthy Tray Count
                  </label>
                  <input type="number" class="form-control font-weight-bold text-danger" id="statusUnhealthyTraysInput" name="unhealthy_trays" min="0" step="1" value="0" required style="border-color: #fca5a5; font-size: 1.15rem; background: #ffffff;">
                </div>
              </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-2 px-1">
              <small class="text-muted" style="font-size: 0.78rem;">
                Total Trays: <strong id="statusHealthTotalNote" class="text-dark">0</strong>
              </small>
              <span class="badge" id="statusHealthBalanceBadge" style="background: #dcfce7; color: #166534; font-size: 0.76rem;">Matched</span>
            </div>
          </div>
        </div>

        <div class="modal-footer py-2.5 px-4 bg-white border-top d-flex justify-content-between align-items-center" style="border-color: #e2e8f0 !important;">
          <button type="button" class="btn btn-secondary px-3.5 py-2 font-weight-bold rounded-3" data-bs-dismiss="modal" data-dismiss="modal" style="font-size: 0.85rem;">
            Cancel
          </button>
          <button type="submit" class="btn btn-success px-4 py-2 font-weight-bold rounded-3 d-inline-flex align-items-center gap-2" id="btnSubmitTrayStatus" style="background: #059669; border-color: #047857; font-size: 0.88rem;">
            <i class="fa fa-save"></i> Save Status
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Edit Order Quantity -->
<div class="modal fade" id="modalEditOrderQty" tabindex="-1" role="dialog" aria-labelledby="modalEditOrderQtyLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document" style="max-width: 840px; width: 95%;">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden; max-height: 90vh; display: flex; flex-direction: column;">
      <div class="modal-header py-3 px-4" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: #ffffff;">
        <div class="d-flex align-items-center gap-2.5">
          <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background: rgba(255,255,255,0.2); color: #ffffff; flex-shrink: 0;">
            <i class="fa fa-qrcode" style="font-size: 1.25rem;"></i>
          </div>
          <div>
            <h5 class="modal-title font-weight-bold mb-0 text-white" id="modalEditOrderQtyLabel" style="font-size: 1.1rem; line-height: 1.2;">
              Occupied Trays & QR Code Management
            </h5>
            <small class="text-white-50" style="font-size: 0.8rem;">View active occupied tray QR codes & remove trays from bed</small>
          </div>
        </div>
        <button type="button" class="btn-close text-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close" style="filter: brightness(0) invert(1); opacity: 0.85; background: none; border: none; font-size: 1.4rem; cursor: pointer;">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <input type="hidden" id="editOrdProjectId" value="">
      <input type="hidden" id="editOrdLocationId" value="">
      <input type="hidden" id="editOrdPolyhouseId" value="">
      <input type="hidden" id="editOrdBedNo" value="">
      <input type="hidden" id="editOrdStockOrderNo" value="">
      <input type="hidden" id="editOrdPsoNo" value="">
      <input type="hidden" id="editOrdProductId" value="">
      <input type="hidden" id="editOrdTrayId" value="">
      <input type="hidden" id="editOrdCavityCount" value="209">

      <div class="modal-body p-3 p-md-4" style="background: #f8fafc; overflow-y: auto;">
        <!-- Order Info Summary Card -->
        <div class="bg-white border rounded-3 p-3 mb-3 shadow-2xs" style="border-color: #e2e8f0 !important;">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
              <span class="badge bg-primary text-white font-monospace px-2 py-1" id="editOrdBadgeSo" style="font-size: 0.82rem; background: #0284c7 !important;">RSO-0000</span>
              <span class="text-muted small ms-1" id="editOrdBadgePso">/ PSO-0000</span>
            </div>
            <span class="badge bg-light text-dark border px-2 py-1" id="editOrdBadgeBed" style="font-size: 0.8rem; background: #f1f5f9; border-color: #cbd5e1 !important; color: #334155; font-weight: 600;">Bed-1</span>
          </div>
          <div class="font-weight-bold fw-bold text-dark mb-1" id="editOrdProductName" style="font-size: 0.95rem; color: #0f172a;">Product Name</div>
          <div class="small text-muted mb-1" style="font-size: 0.82rem;"><i class="fa fa-user text-primary mr-1"></i>Client: <strong id="editOrdClientName" class="text-dark">N/A</strong></div>
          <div class="text-muted small d-flex align-items-center gap-2 flex-wrap" style="font-size: 0.82rem;">
            <span><i class="fa fa-layer-group text-secondary mr-1"></i><span id="editOrdTrayName">Tray (209 Cavity)</span></span>
            <span>•</span>
            <span><i class="fa fa-warehouse text-secondary mr-1"></i><span id="editOrdPolyhouseName">Polyhouse</span></span>
          </div>
        </div>

        <!-- Occupied Stats & Strict Rule Callout -->
        <div class="row g-2 mb-3">
          <div class="col-6">
            <div class="p-2.5 rounded-3 border bg-white text-center" style="border-color: #e2e8f0;">
              <span class="text-muted small d-block" style="font-size: 0.72rem; text-transform: uppercase; font-weight: 600;">Occupied Trays</span>
              <span class="font-weight-bold fw-bold text-primary" id="editOrdCurTraysDisp" style="font-size: 1.35rem; color: #0284c7;">0</span>
            </div>
          </div>
          <div class="col-6">
            <div class="p-2.5 rounded-3 border bg-white text-center" style="border-color: #e2e8f0;">
              <span class="text-muted small d-block" style="font-size: 0.72rem; text-transform: uppercase; font-weight: 600;">Occupied Plants</span>
              <span class="font-weight-bold fw-bold text-dark" id="editOrdCurPlantsDisp" style="font-size: 1.35rem;">0</span>
            </div>
          </div>
        </div>

        <div class="alert alert-warning py-2 px-3 small mb-3 border-0 d-flex align-items-center gap-2" style="background: #fffbeb; color: #92400e; border-left: 4px solid #f59e0b !important; border-radius: 8px;">
          <i class="fa fa-info-circle fa-lg text-warning" style="flex-shrink: 0;"></i>
          <div><strong>Note:</strong> Manage active occupied trays in this bed. You can add new trays to this bed or select and remove trays from active occupied stock.</div>
        </div>

        <!-- Add Tray Collapsible Panel -->
        <div id="editOrdAddTrayCard" class="card border-0 mb-3 shadow-sm" style="display: none; background: #f0fdf4; border: 1px solid #bbf7d0 !important; border-radius: 10px;">
          <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center mb-2.5 pb-2 border-bottom" style="border-color: #dcfce7 !important;">
              <h6 class="mb-0 font-weight-bold text-success d-flex align-items-center gap-2" style="font-size: 0.92rem;">
                <i class="fa fa-plus-circle"></i> Add New Trays to Bed
              </h6>
              <button type="button" class="btn-close btn-sm" id="btnEditOrdCloseAddTrayCard" aria-label="Close" style="font-size: 0.75rem;"></button>
            </div>
            
            <div class="row g-2 align-items-end">
              <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label small font-weight-bold text-dark mb-1" style="font-size: 0.78rem;">
                  Number of Trays <span class="text-danger">*</span>
                </label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text bg-white" style="border-color: #cbd5e1;"><i class="fa fa-cubes text-muted"></i></span>
                  <input type="number" class="form-control" id="editOrdNewTrayCount" min="1" max="1000" value="1" style="border-color: #cbd5e1; font-weight: 600;">
                </div>
              </div>

              <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label small font-weight-bold text-dark mb-1" style="font-size: 0.78rem;">
                  Total Plants
                </label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text bg-white" style="border-color: #cbd5e1;"><i class="fa fa-seedling text-muted"></i></span>
                  <input type="text" class="form-control bg-light font-weight-bold text-success" id="editOrdNewPlantsCalc" value="209 Plants" readonly style="border-color: #cbd5e1;">
                </div>
              </div>

              <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label small font-weight-bold text-dark mb-1" style="font-size: 0.78rem;">
                  Tray Status
                </label>
                <select class="form-select form-select-sm" id="editOrdNewTrayStatus" style="border-color: #cbd5e1; font-size: 0.82rem;">
                  <option value="Readytosale">Ready to Sale</option>
                  <option value="Polyhouse">Polyhouse</option>
                </select>
              </div>

              <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label small font-weight-bold text-dark mb-1" style="font-size: 0.78rem;">
                  Health Status
                </label>
                <select class="form-select form-select-sm" id="editOrdNewHealthStatus" style="border-color: #cbd5e1; font-size: 0.82rem;">
                  <option value="Healthy" selected>Healthy</option>
                  <option value="Unhealthy">Unhealthy</option>
                </select>
              </div>

              <div class="col-12 d-flex justify-content-end align-items-center gap-2 mt-2.5 pt-2 border-top" style="border-color: #dcfce7 !important;">
                <button type="button" class="btn btn-sm btn-outline-secondary px-3" id="btnEditOrdCancelAddTrays" style="font-size: 0.8rem; border-radius: 6px;">
                  Cancel
                </button>
                <button type="button" class="btn btn-sm btn-success font-weight-bold px-3 py-1.5" id="btnEditOrdSubmitAddTrays" style="font-size: 0.8rem; border-radius: 6px;">
                  <i class="fa fa-save mr-1"></i> Save & Add Trays
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Toolbar: Scan/Search & Bulk Actions & Add Tray Button -->
        <div class="d-flex justify-content-between align-items-center gap-2 mb-2.5 flex-wrap">
          <div class="input-group input-group-sm" style="max-width: 320px; min-width: 200px;">
            <span class="input-group-text bg-white" style="border-color: #cbd5e1;"><i class="fa fa-barcode text-muted"></i></span>
            <input type="text" class="form-control" id="editOrdSearchTrayInput" placeholder="Scan or search Tray QR Code..." style="border-color: #cbd5e1; font-size: 0.84rem;">
          </div>
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <button type="button" class="btn btn-success btn-sm font-weight-bold px-3 py-1.5" id="btnEditOrdToggleAddTrays" style="font-size: 0.8rem; border-radius: 6px;">
              <i class="fa fa-plus-circle mr-1"></i> Add Tray
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm font-weight-bold px-2.5 py-1.5" id="btnEditOrdSelectAll" style="font-size: 0.8rem; border-radius: 6px;">
              <i class="fa fa-check-square mr-1"></i><span id="btnEditOrdSelectAllText">Select All</span>
            </button>
            <button type="button" class="btn btn-danger btn-sm font-weight-bold px-3 py-1.5" id="btnEditOrdRemoveSelected" disabled style="font-size: 0.8rem; border-radius: 6px;">
              <i class="fa fa-trash-alt mr-1"></i>Remove Selected (<span id="editOrdSelectedCount">0</span>)
            </button>
          </div>
        </div>

        <!-- Occupied Trays Table Wrapper -->
        <div class="border rounded-3 bg-white shadow-2xs" style="border-color: #e2e8f0; max-height: 360px; overflow-y: auto;" id="editOrdTraysWrap">
          <div id="editOrdTraysLoading" class="text-center py-4 text-muted">
            <i class="fa fa-spinner fa-spin fa-2x text-primary mb-2"></i>
            <div>Loading occupied tray QR records...</div>
          </div>
          <div class="table-responsive m-0" id="editOrdTraysTableWrap" style="display: none;">
            <table class="table table-hover table-sm align-middle mb-0" style="font-size: 0.85rem;">
              <thead class="table-light sticky-top" style="z-index: 2; background-color: #f8fafc;">
                <tr style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.35px; color: #475569; border-bottom: 2px solid #cbd5e1;">
                  <th class="col-edit-ord-chk">
                    <div class="chk-cell-wrap">
                      <input type="checkbox" class="edit-ord-chk" id="chkEditOrdHeader" title="Select / Deselect All">
                    </div>
                  </th>
                  <th class="col-edit-ord-idx">#</th>
                  <th style="padding: 7px 10px;">Tray QR Code / Barcode</th>
                  <th style="width: 110px; text-align: center; padding: 7px 10px;">Status</th>
                  <th style="width: 100px; text-align: center; padding: 7px 10px;">Health</th>
                  <th style="width: 110px; text-align: right; padding: 7px 12px;">Action</th>
                </tr>
              </thead>
              <tbody id="editOrdTraysTbody">
                <!-- Populated via JS -->
              </tbody>
            </table>
          </div>
          <div id="editOrdTraysEmpty" class="text-center py-4 text-muted" style="display: none;">
            <i class="fa fa-boxes fa-2x text-secondary mb-2"></i>
            <div>No active occupied trays found for this order in this bed.</div>
          </div>
        </div>
      </div>

      <div class="modal-footer py-2.5 px-4 bg-white border-top d-flex justify-content-between align-items-center" style="border-color: #e2e8f0 !important;">
        <span class="small text-muted font-weight-bold" id="editOrdTrayCountBadge">
          0 Active Trays
        </span>
        <button type="button" class="btn btn-secondary px-4 py-2 font-weight-bold rounded-3" data-bs-dismiss="modal" data-dismiss="modal" style="font-size: 0.85rem;">
          Close
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Print QR Codes for Order -->
<div class="modal fade" id="modalPrintOrderQr" tabindex="-1" aria-labelledby="modalPrintOrderQrLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 820px; width: 92vw; margin: 1.25rem auto;">
    <div class="modal-content shadow-lg border-0 rounded-4 overflow-hidden" style="max-height: 92vh;">
      <div class="modal-header bg-white py-3 px-4 d-flex justify-content-between align-items-center" style="border-bottom: 1px solid #e2e8f0; background: #ffffff;">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; background: #e0e7ff; color: #4338ca; flex-shrink: 0;">
            <i class="fa fa-qrcode" style="font-size: 1.3rem;"></i>
          </div>
          <div>
            <h5 class="modal-title font-heading fw-bold mb-0 text-dark" id="modalPrintOrderQrLabel" style="font-size: 1.15rem; color: #0f172a;">
              A4 Order Dispatch QR Code
            </h5>
            <div class="small text-muted" id="qrModalSubHeader" style="color: #64748b; font-size: 0.85rem; margin-top: 2px;">Standard A4 Normal Printer Slip</div>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close" style="background: none; border: none; font-size: 1.6rem; line-height: 1; color: #64748b; cursor: pointer; padding: 4px 8px;">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body p-3 p-md-4" id="qrModalBody" style="background: #f8fafc; min-height: 440px;">
        <div class="bg-white border rounded-3 p-3 mb-3 shadow-2xs" style="border-color: #e2e8f0 !important; background: #ffffff;">
          <div class="row align-items-center g-3">
            <div class="col-lg-6 col-12">
              <span class="text-muted small text-uppercase font-weight-bold fw-bold d-block" style="font-size: 0.72rem; letter-spacing: 0.5px; color: #64748b;">Order Reference</span>
              <div id="qrModalOrderMeta" class="mt-1" style="font-size: 0.95rem; color: #1e293b;"></div>
            </div>
            <div class="col-lg-6 col-12 d-flex justify-content-lg-end justify-content-start gap-2 flex-wrap align-items-center">
              <a href="#" target="_blank" id="btnOpenFullA4Slip" class="btn btn-outline-secondary px-3 py-2 font-weight-bold fw-bold d-inline-flex align-items-center gap-1.5" style="font-size: 0.85rem; border-radius: 8px;">
                <i class="fa fa-external-link-alt"></i> Fullpage Slip
              </a>
              <button type="button" class="btn btn-primary px-3.5 py-2 font-weight-bold fw-bold d-inline-flex align-items-center gap-2" onclick="printModalQrStickers()" style="background: #2563eb; border-color: #1d4ed8; font-size: 0.88rem; border-radius: 8px;">
                <i class="fa fa-print"></i> Print (A4 Normal Printer)
              </button>
            </div>
          </div>
        </div>

        <div id="qrModalSpinner" class="text-center py-5">
          <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
          <p class="mt-2 text-muted fw-semibold">Loading order QR code details...</p>
        </div>

        <div id="qrModalSingleSlip" style="display:none;"></div>
      </div>

      <div class="modal-footer bg-white border-0 py-2.5 px-4 d-flex justify-content-between align-items-center">
        <span class="small text-muted" id="qrModalFooterCount"></span>
        <button type="button" class="btn btn-secondary btn-sm px-4 rounded" data-bs-dismiss="modal" data-dismiss="modal">
          Close
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Add Ready Stock / Open Stock -->
<div class="modal fade" id="modalAddReadyStock" tabindex="-1" role="dialog" aria-labelledby="modalAddReadyStockLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered" role="document" style="max-width: 1050px; width: 92%;">
    <div class="modal-content" style="border-radius: 14px; border: 1px solid #dbe3ef; box-shadow: 0 10px 30px rgba(0,0,0,0.15); overflow: hidden;">
      <div class="modal-header" id="modalAddStockHeader" style="background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%); color: #ffffff; padding: 1rem 1.4rem;">
        <h5 class="modal-title font-weight-bold" id="modalAddReadyStockLabel" style="font-size: 1.15rem; margin: 0;">
          <i class="fa fa-boxes mr-2"></i>Add Ready Stock / Open Stock
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" style="opacity: 0.9; text-shadow: none; border: none; background: transparent; font-size: 1.5rem; line-height: 1;">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="formAddReadyStock" novalidate>
        <div class="modal-body" style="padding: 1.4rem; background: #fafcff; max-height: 82vh; overflow-y: auto;">
          <div class="row" style="row-gap: 1rem;">
            
            <!-- Stock Type Selector -->
            <div class="col-12">
              <label class="font-weight-bold text-dark mb-1 d-flex justify-content-between align-items-center" style="font-size: 0.86rem;">
                <span>Choose Stock Category <span class="text-danger">*</span></span>
                <span class="badge" id="stockTypePillBadge" style="background: #e0e7ff; color: #3730a3; font-weight: 700; font-size: 0.76rem; border-radius: 6px; padding: 4px 8px;">Ready Stock Selected</span>
              </label>
              <div class="stock-type-group">
                <label class="stock-type-pill active-ready" id="pillStockReady">
                  <input type="radio" name="stock_type" value="readystock" checked>
                  <i class="fa fa-boxes mr-1"></i> Ready Stock
                </label>
                <label class="stock-type-pill" id="pillStockOpen">
                  <input type="radio" name="stock_type" value="openstock">
                  <i class="fa fa-seedling mr-1"></i> Open Stock
                </label>
              </div>
            </div>

            <!-- Location -->
            <div class="col-md-4">
              <label class="font-weight-bold text-dark mb-1" style="font-size: 0.85rem;">Location <span class="text-danger">*</span></label>
              <select id="rsLocation" name="locationid" class="ph-control" required style="background: #ffffff;">
                <option value="">Select Location</option>
              </select>
            </div>
            <!-- Polyhouse -->
            <div class="col-md-4">
              <label class="font-weight-bold text-dark mb-1" style="font-size: 0.85rem;">Polyhouse <span class="text-danger">*</span></label>
              <select id="rsPolyhouse" name="polyhouseid" class="ph-control" required style="background: #ffffff;">
                <option value="">Select Polyhouse</option>
              </select>
            </div>
            <!-- Bed -->
            <div class="col-md-4">
              <label class="font-weight-bold text-dark mb-1" style="font-size: 0.85rem;">Bed <span class="text-danger">*</span></label>
              <select id="rsBed" name="bed_no" class="ph-control" required style="background: #ffffff;">
                <option value="">Select Bed</option>
              </select>
            </div>

            <!-- Product -->
            <div class="col-md-8">
              <label class="font-weight-bold text-dark mb-1" style="font-size: 0.85rem;">Product <span class="text-danger">*</span></label>
              <select id="rsProduct" name="productid" class="ph-control" required style="background: #ffffff;">
                <option value="">Select Product</option>
              </select>
            </div>
            <!-- Bill No -->
            <div class="col-md-4">
              <label class="font-weight-bold text-dark mb-1" id="lblBillNo" style="font-size: 0.85rem;">Enter Bill No of Product <span class="text-danger">*</span></label>
              <input type="text" id="rsBillNo" name="bill_no" class="ph-control" placeholder="e.g. BILL-9821" required style="background: #ffffff;" autocomplete="off">
              <!-- Live Availability Status -->
              <div id="rsBillStatus" class="mt-1" style="font-size: 0.78rem; min-height: 18px; line-height: 1.3;"></div>
            </div>

            <!-- Dispatched Date -->
            <div class="col-md-6">
              <label class="font-weight-bold text-dark mb-1" style="font-size: 0.85rem;">Dispatched Date <span class="text-danger">*</span></label>
              <input type="date" id="rsDispatchDate" name="dispatch_date" class="ph-control" required style="background: #ffffff;">
            </div>

            <!-- Tray Cavity -->
            <div class="col-md-6">
              <label class="font-weight-bold text-dark mb-1" style="font-size: 0.85rem;">Tray Cavity <span class="text-danger">*</span></label>
              <select id="rsTrayId" name="tray_id" class="ph-control" required style="background: #ffffff;">
                <option value="">Select Tray Cavity</option>
              </select>
            </div>

            <!-- No of Trays -->
            <div class="col-12">
              <label class="font-weight-bold text-dark mb-1" style="font-size: 0.85rem;">No of Trays <span class="text-danger">*</span></label>
              <input type="number" id="rsNoOfTrays" name="no_of_trays" class="ph-control" min="1" value="1" required style="background: #ffffff;">
            </div>

            <!-- Tray Position (Common for both) -->
            <!-- <div class="col-12">
              <label class="font-weight-bold text-dark mb-1" style="font-size: 0.85rem;">Tray position <span class="text-danger">*</span></label>
              <div class="rs-radio-group">
                <label class="rs-radio-pill active">
                  <input type="radio" name="tray_position" value="Start" checked>
                  <span>Start</span>
                </label>
                <label class="rs-radio-pill">
                  <input type="radio" name="tray_position" value="Middle">
                  <span>Middle</span>
                </label>
                <label class="rs-radio-pill">
                  <input type="radio" name="tray_position" value="End">
                  <span>End</span>
                </label>
              </div>
            </div> -->

          </div>
        </div>
        <div class="modal-footer" style="padding: 0.85rem 1.4rem; background: #f1f5f9; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
          <button type="button" class="btn btn-secondary px-3" data-dismiss="modal" data-bs-dismiss="modal" style="font-weight: 600; border-radius: 8px;">Cancel</button>
          <button type="submit" id="btnSubmitReadyStock" class="btn btn-primary px-4" style="background: #4f46e5; border-color: #4338ca; font-weight: 700; border-radius: 8px;">
            <i class="fa fa-save mr-1"></i> Save Ready Stock
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Status Breakdown Details (Read-Only) -->
<div class="modal fade" id="modalStatusBreakdownDetails" tabindex="-1" role="dialog" aria-labelledby="modalStatusBreakdownDetailsLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered" role="document" style="max-width: 1100px; width: 95%;">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
      <div class="modal-header py-3 px-4" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #ffffff;">
        <div class="d-flex align-items-center gap-2.5">
          <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background: rgba(255,255,255,0.15); color: #ffffff; flex-shrink: 0;">
            <i class="fa fa-tags" style="font-size: 1.15rem;"></i>
          </div>
          <div>
            <h5 class="modal-title font-weight-bold mb-0 text-white" id="modalStatusBreakdownDetailsLabel" style="font-size: 1.1rem; line-height: 1.2;">
              Status Tray Details
            </h5>
            <small class="text-white-50" id="statusModalSubTitle" style="font-size: 0.8rem;">Viewing individual tray records for this status</small>
          </div>
        </div>
        <button type="button" class="btn-close text-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close" style="filter: brightness(0) invert(1); opacity: 0.85; background: none; border: none; font-size: 1.4rem; cursor: pointer;">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body p-4" style="background: #f8fafc;">
        <!-- Header Info Card -->
        <div class="bg-white border rounded-3 p-3 mb-3 shadow-2xs" style="border-color: #e2e8f0 !important;">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
              <span class="badge font-monospace px-2.5 py-1 text-white" id="statusModalBadgeSo" style="font-size: 0.85rem; background: #4f46e5;">RSO-0000</span>
              <span class="text-muted small ms-1" id="statusModalBadgePso">/ PSO-0000</span>
              <span class="mx-2 text-muted">•</span>
              <strong class="text-dark" id="statusModalProductName" style="font-size: 0.95rem;">Product</strong>
              <span class="mx-2 text-muted">•</span>
              <span class="small text-muted"><i class="fa fa-user text-primary me-1"></i>Client: <strong class="text-dark" id="statusModalClientName">-</strong></span>
            </div>
            <div class="d-flex align-items-center gap-2">
              <span class="badge bg-light text-dark border px-2.5 py-1" id="statusModalBedName" style="font-size: 0.82rem; background: #f1f5f9; border-color: #cbd5e1 !important; color: #334155; font-weight: 600;">Bed-1</span>
              <span class="badge px-3 py-1 font-weight-bold" id="statusModalStatusBadge" style="font-size: 0.85rem;">Status</span>
            </div>
          </div>
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-2 pt-2 border-top" style="border-color: #f1f5f9 !important;">
            <div class="small text-muted" id="statusModalSummaryCounts">
              Loading tray data...
            </div>
            <div>
              <input type="text" id="statusModalFilterInput" class="form-control form-control-sm" placeholder="Search barcode, dispatch by, remark..." style="width: 250px; border-radius: 6px; font-size: 0.8rem;">
            </div>
          </div>
        </div>

        <!-- Trays Table -->
        <div class="table-responsive bg-white border rounded-3 shadow-2xs" style="max-height: 440px; overflow-y: auto;">
          <table class="table table-sm table-hover mb-0 align-middle" id="statusModalTable" style="font-size: 0.82rem;">
            <thead class="table-light text-muted sticky-top" id="statusModalTableHead" style="font-size: 0.75rem; text-transform: uppercase; background: #f1f5f9;">
              <!-- Dynamic headers based on status type -->
            </thead>
            <tbody id="statusModalTableBody">
              <tr>
                <td colspan="8" class="text-center py-4 text-muted">
                  <i class="fa fa-spinner fa-spin mr-1"></i> Loading records...
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="modal-footer py-2.5 px-4" style="background: #f1f5f9; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
        <span class="small text-muted" id="statusModalFooterCount">Showing 0 trays</span>
        <button type="button" class="btn btn-secondary px-4 btn-sm" data-dismiss="modal" data-bs-dismiss="modal" style="border-radius: 6px; font-weight: 600;">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Duplicate RSO Finder & Resolver -->
<div class="modal fade" id="modalDuplicateRsoFinder" tabindex="-1" role="dialog" aria-labelledby="modalDuplicateRsoFinderLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered" role="document" style="max-width: 1150px; width: 95%;">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
      <div class="modal-header py-3 px-4" style="background: linear-gradient(135deg, #d97706 0%, #b45309 100%); color: #ffffff;">
        <div class="d-flex align-items-center gap-2.5">
          <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background: rgba(255,255,255,0.2); color: #ffffff; flex-shrink: 0;">
            <i class="fa fa-clone" style="font-size: 1.15rem;"></i>
          </div>
          <div>
            <h5 class="modal-title font-weight-bold mb-0 text-white" id="modalDuplicateRsoFinderLabel" style="font-size: 1.15rem; line-height: 1.2;">
              Duplicate RSO Finder & Resolver
            </h5>
            <small class="text-white-50" style="font-size: 0.8rem;">Identify conflicting stock numbers, assign guaranteed unique RSOs, and reprint tray labels</small>
          </div>
        </div>
        <button type="button" class="btn-close text-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close" style="filter: brightness(0) invert(1); opacity: 0.85; background: none; border: none; font-size: 1.4rem; cursor: pointer;">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body p-4" style="background: #f8fafc; max-height: 78vh; overflow-y: auto;">
        <!-- Top Nav & Filter Controls -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
          <ul class="nav nav-pills" id="dupRsoTabs" role="tablist" style="gap: 6px;">
            <li class="nav-item">
              <a class="nav-link active font-weight-bold px-3 py-1.5" id="tabDupProdLink" data-toggle="pill" data-bs-toggle="pill" href="#tabDupProd" role="tab" style="border-radius: 8px; font-size: 0.88rem;">
                <i class="fa fa-layer-group mr-1"></i> Product Conflicts <span class="badge badge-danger ml-1" id="tabDupProdBadge" style="background:#ef4444; color:#fff;">0</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link font-weight-bold px-3 py-1.5" id="tabDupBedLink" data-toggle="pill" data-bs-toggle="pill" href="#tabDupBed" role="tab" style="border-radius: 8px; font-size: 0.88rem;">
                <i class="fa fa-th mr-1"></i> Multi-Bed Splits <span class="badge badge-secondary ml-1" id="tabDupBedBadge" style="background:#64748b; color:#fff;">0</span>
              </a>
            </li>
          </ul>

          <div class="d-flex align-items-center gap-2">
            <input type="text" id="dupRsoSearchFilter" class="form-control form-control-sm" placeholder="Filter by RSO or Product..." style="max-width: 250px; border-radius: 6px; font-size: 0.84rem;">
            <button type="button" class="btn btn-sm btn-outline-secondary px-2.5" id="btnRefreshDupList" title="Refresh List" style="border-radius: 6px;">
              <i class="fa fa-sync-alt"></i> Refresh
            </button>
          </div>
        </div>

        <!-- Tab Content -->
        <div class="tab-content" id="dupRsoTabContent">
          <!-- Tab 1: Product Conflicts -->
          <div class="tab-pane fade show active" id="tabDupProd" role="tabpanel">
            <div id="dupProdListContainer">
              <div class="text-center py-5 text-muted">
                <i class="fa fa-spinner fa-spin fa-2x mb-2 text-warning"></i>
                <div>Scanning for duplicate RSO numbers...</div>
              </div>
            </div>
          </div>

          <!-- Tab 2: Multi-Bed Splits -->
          <div class="tab-pane fade" id="tabDupBed" role="tabpanel">
            <div id="dupBedListContainer">
              <div class="text-center py-5 text-muted">
                <i class="fa fa-spinner fa-spin fa-2x mb-2 text-warning"></i>
                <div>Loading multi-bed splits...</div>
              </div>
            </div>
          </div>
        </div>

      </div>

      <div class="modal-footer py-2.5 px-4" style="background: #f1f5f9; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
        <span class="small text-muted" id="dupModalFooterCount">Ready</span>
        <button type="button" class="btn btn-secondary px-4 btn-sm" data-dismiss="modal" data-bs-dismiss="modal" style="border-radius: 6px; font-weight: 600;">Close</button>
      </div>
    </div>
  </div>
</div>

<?php include('../footer.php'); ?>
<script>
$(function(){
  // Ensure trailing slash on URL for robust relative paths
  if (window.location.pathname.endsWith('/polyhousetracking')) {
    try {
      history.replaceState(null, '', window.location.pathname + '/' + window.location.search);
    } catch(e) {}
  }

  // Safe resolver for view.php modal actions regardless of base URL
  const getModalUrl = function(action) {
    let base = 'modal/view.php';
    const pathname = window.location.pathname;
    if (pathname.endsWith('/polyhousetracking')) {
      base = 'polyhousetracking/modal/view.php';
    }
    return base + (action ? ('?action=' + encodeURIComponent(action)) : '');
  };

  const projectid = parseInt($("body").data("projectid"),10) || parseInt($('#hidden_projectid').val(),10) || 17;
  const loginroleid = parseInt($("body").data("loginroleid"), 10) || parseInt($('#hidden_loginroleid').val(), 10) || <?php echo isset($loginroleid) && $loginroleid !== '' ? (int)$loginroleid : (isset($_SESSION['loginroleid']) ? (int)$_SESSION['loginroleid'] : 99); ?>;
  const canEditRemove = (loginroleid < 2);
  const canAddReadyStock = (loginroleid <= 3);

  if (!canAddReadyStock) {
    $('#btnOpenAddReadyStock').hide();
  }
  let reportData = null;

  let mainPolyhouseCache = {};

  function esc(v){return $('<div>').text(v == null ? '' : v).html();}
  function fmtDate(v){if(!v || v==='0000-00-00') return 'N/A'; return (typeof changeDateFormatYmd_dMY==='function') ? changeDateFormatYmd_dMY(v) : v;}
  function fmtDateTime(v){if(!v) return 'N/A'; const p=String(v).split(' '); return fmtDate(p[0]) + (p[1] ? ' ' + p[1].substring(0,5) : '');}

  // Initialize Multi-select Select2 on Product filter
  if (typeof $.fn.select2 !== 'undefined') {
    $('#phProduct').select2({
      placeholder: 'All Products (Select Multiple)',
      allowClear: true,
      width: '100%',
      closeOnSelect: false
    });
  }

  function loadLocations(){
    $.post(getModalUrl('getlocation'),{projectid},function(res){
      let h='<option value="">Select Location</option>';
      if(res.status==='success') (res.data||[]).forEach(x=>h+=`<option value="${esc(x.locationid)}">${esc(x.locationname)}</option>`);
      $('#phLocation').html(h);
    },'json');
  }

  $('#phLocation').on('change',function(){
    const locationid=$(this).val();
    $('#phPolyhouse').prop('disabled',!locationid).html('<option value="">Select Polyhouse</option>');
    $('#phBed').prop('disabled',true).html('<option value="">All Beds</option>');
    $('#phProduct').empty().val(null).prop('disabled',true).trigger('change.select2');
    reportData=null; $('#phSummary').hide(); $('#phReportMeta').hide(); $('#phReportResult').html('<div class="ph-empty">Select a polyhouse.</div>');
    if(!locationid) return;
    $.post(getModalUrl('polyhousemaster'),{projectid,locationid},function(res){
      let h='<option value="">Select Polyhouse</option>';
      if(res.status==='success') {
        mainPolyhouseCache[locationid] = res.data || [];
        if ((res.data || []).length > 0) {
          h += '<option value="all">All Polyhouses</option>';
        }
        (res.data||[]).forEach(x=>h+=`<option value="${esc(x.polyhouseid)}">${esc(x.polyhousename)}</option>`);
      }
      $('#phPolyhouse').html(h);
    },'json');
  });

  let forceShowAllInAllPh = false;

  $('#phPolyhouse').on('change',function(){
    const phId = $(this).val();
    const locId = $('#phLocation').val();
    forceShowAllInAllPh = false;
    if(phId && locId){
      // INSTANTLY populate Bed list so the user does not experience any delay!
      const phList = mainPolyhouseCache[locId] || [];
      let bedHtml = '<option value="">All Beds</option>';
      if (phId === 'all') {
        let allBedNames = new Set();
        phList.forEach(phObj => {
          (phObj.beds || []).forEach(b => allBedNames.add(b));
        });
        [...allBedNames].sort((a,b)=>naturalBed(a)-naturalBed(b)).forEach(b => {
          bedHtml += `<option value="${esc(b)}">${esc(b)}</option>`;
        });
      } else {
        const phObj = phList.find(x => String(x.polyhouseid) === String(phId));
        if(phObj && phObj.beds && phObj.beds.length){
          phObj.beds.sort((a,b)=>naturalBed(a)-naturalBed(b)).forEach(b => {
            bedHtml += `<option value="${esc(b)}">${esc(b)}</option>`;
          });
        }
      }
      $('#phBed').html(bedHtml).prop('disabled',false);
      loadReport();
    } else {
      $('#phBed').html('<option value="">All Beds</option>').prop('disabled',true);
      $('#phProduct').empty().val(null).prop('disabled',true).trigger('change.select2');
      clearReport();
    }
  });

  $('#phBed,#phProduct,#phVerify').on('change',renderReport);
  $('#phSearch').on('input',renderReport);

  function clearReport(){reportData=null;$('#phSummary').hide();$('#phReportMeta').hide();$('#phReportResult').html('<div class="ph-empty">Select a polyhouse.</div>');}

  function loadReport(){
    const locId = $('#phLocation').val();
    const phId = $('#phPolyhouse').val();
    if (!locId || !phId) {
      clearReport();
      return;
    }

    $('#phSummary').html(`
      ${kpi('Total Beds', '<i class="fa fa-spinner fa-spin text-muted" style="font-size:1.05rem;"></i>')}
      ${kpi('Occupied Beds', '<i class="fa fa-spinner fa-spin text-muted" style="font-size:1.05rem;"></i>')}
      ${kpi('Unoccupied Beds', '<i class="fa fa-spinner fa-spin text-muted" style="font-size:1.05rem;"></i>')}
      ${kpi('Plant Count', '<i class="fa fa-spinner fa-spin text-muted" style="font-size:1.05rem;"></i>')}
      ${kpi('Total Trays', '<i class="fa fa-spinner fa-spin text-muted" style="font-size:1.05rem;"></i>')}
      ${kpi('Ready to Sale', '<i class="fa fa-spinner fa-spin text-muted" style="font-size:1.05rem;"></i>')}
      ${kpi('Verified', '<i class="fa fa-spinner fa-spin text-muted" style="font-size:1.05rem;"></i>')}
      ${kpi('Unverified', '<i class="fa fa-spinner fa-spin text-muted" style="font-size:1.05rem;"></i>')}
      ${kpi('Germ. Failure', '<i class="fa fa-spinner fa-spin text-muted" style="font-size:1.05rem;"></i>')}
    `).show();
    $('#phReportResult').html('<div class="ph-empty"><i class="fa fa-spinner fa-spin mr-1"></i>Loading bed-wise report...</div>');

    $.ajax({
      url: getModalUrl('bedwisereport'),
      method: 'POST',
      dataType: 'json',
      data: {
        projectid,
        locationid: locId,
        polyhouseid: phId
      },
      timeout: 60000,
      success: function(res) {
        if(res.status !== 'success'){ 
          $('#phSummary').hide();
          $('#phReportResult').html(`
            <div class="ph-empty text-danger text-center py-4">
              <i class="fa fa-exclamation-triangle fa-2x mb-2"></i>
              <div>${esc(res.message || 'Unable to load report.')}</div>
              <button class="btn btn-sm btn-outline-primary mt-3" onclick="loadReport();">
                <i class="fa fa-sync-alt mr-1"></i> Retry
              </button>
            </div>
          `); 
          return; 
        }
        reportData = res;
        $('#phGeneratedOn').text(res.generated_on || '');
        $('#phReportScope').text((res.location?.locationname || '') + ' • ' + (res.polyhouse?.polyhousename || ''));
        $('#phReportMeta').toggle(!!res.generated_on);

        // Dedicated PDF/print heading values
        $('#phPrintLocation').text(res.location?.locationname || '');
        $('#phPrintPolyhouse').text(res.polyhouse?.polyhousename || '');
        $('#phPrintGeneratedOn').text(res.generated_on || '');
        fillLocalFilters(res.beds || []);
        renderReport();
      },
      error: function(xhr, status, errorThrown) {
        $('#phSummary').hide();
        let msg = 'Unable to load report.';
        if (status === 'timeout') {
          msg = 'Server took too long to respond (Request Timeout).';
        } else if (xhr.responseJSON && xhr.responseJSON.message) {
          msg = xhr.responseJSON.message;
        } else if (errorThrown) {
          msg = 'Error loading report: ' + errorThrown;
        }
        $('#phReportResult').html(`
          <div class="ph-empty text-danger text-center py-4">
            <i class="fa fa-exclamation-circle fa-2x mb-2 text-danger"></i>
            <div>${esc(msg)}</div>
            <button class="btn btn-sm btn-outline-primary mt-3" onclick="loadReport();">
              <i class="fa fa-sync-alt mr-1"></i> Retry Loading Report
            </button>
          </div>
        `);
      }
    });
  }

  function fillLocalFilters(beds){
    let bedSet=new Set(), productMap=new Map();
    $('#phBed option').each(function(){
      const v = $(this).val();
      if(v) bedSet.add(v);
    });
    beds.forEach(b=>{
      if(b.bed_no) bedSet.add(b.bed_no);
      (b.orders||[]).forEach(o=>productMap.set(String(o.productid),o.productname));
    });
    const selectedBed = $('#phBed').val();
    let bh='<option value="">All Beds</option>';
    [...bedSet].sort((a,b)=>naturalBed(a)-naturalBed(b)).forEach(v=>{
      const isSel = (String(v) === String(selectedBed)) ? ' selected' : '';
      bh+=`<option value="${esc(v)}"${isSel}>${esc(v)}</option>`;
    });
    let ph='';
    const rawProd = $('#phProduct').val();
    const prevSelected = Array.isArray(rawProd) ? rawProd.map(String) : (rawProd ? [String(rawProd)] : []);
    [...productMap.entries()].sort((a,b)=>String(a[1]).localeCompare(String(b[1]))).forEach(([id,n])=>{
      const isSel = prevSelected.includes(String(id)) ? ' selected' : '';
      ph+=`<option value="${esc(id)}"${isSel}>${esc(n)}</option>`;
    });
    $('#phBed').html(bh).prop('disabled',false);
    $('#phProduct').html(ph).prop('disabled',false).trigger('change.select2');
  }

  function naturalBed(v){const m=String(v||'').match(/\d+/); return m?parseInt(m[0],10):999999;}

  function filteredBeds(){
    if(!reportData) return [];
    const bed=$('#phBed').val();
    const rawProd=$('#phProduct').val();
    const selectedProds = Array.isArray(rawProd) ? rawProd.map(String) : (rawProd ? [String(rawProd)] : []);
    const verify=$('#phVerify').val();
    const q=$('#phSearch').val().toLowerCase().trim();
    return (reportData.beds||[]).map(b=>{
      if(bed && b.bed_no!==bed) return null;
      const orders=(b.orders||[]).filter(o=>{
        const occTrays = (o.occupied_trays !== undefined) ? Number(o.occupied_trays) || 0 : Number(o.total_trays) || 0;
        if(occTrays <= 0) return false;
        if(selectedProds.length > 0 && !selectedProds.includes(String(o.productid))) return false;
        if(verify==='verified' && parseInt(o.verified_trays||0,10)<=0) return false;
        if(verify==='unverified' && parseInt(o.unverified_trays||0,10)<=0) return false;
        if(verify==='readystock' && parseInt(o.ready_to_sale_trays||0,10)<=0) return false;
        const hay=[o.stockorder_no,o.psono,o.client_name,o.productname,b.bed_no, (+o.ready_to_sale_trays>0 ? 'ready to sale readytosale' : ''), Object.keys(o.status_counts || {}).filter(k => (o.status_counts[k] || 0) > 0).join(' ')].join(' ').toLowerCase();
        return !q || hay.includes(q);
      });
      if(!orders.length) return null;
      return Object.assign({},b,{orders});
    }).filter(Boolean);
  }

  function renderReport(){
    if(!reportData) return;
    const isAllPh = ($('#phPolyhouse').val() === 'all');
    const selectedBed = $('#phBed').val();
    const rawProd = $('#phProduct').val();
    const selectedProds = Array.isArray(rawProd) ? rawProd.filter(Boolean) : (rawProd ? [rawProd] : []);
    const selectedVerify = $('#phVerify').val();
    const searchQuery = $('#phSearch').val().trim();
    const isFiltered = !!(selectedBed || selectedProds.length > 0 || selectedVerify || searchQuery);

    // If All Polyhouses is selected and no product is filtered, prompt user to select a product (with option to view all)
    if (isAllPh && selectedProds.length === 0 && !selectedBed && !searchQuery && !selectedVerify && !forceShowAllInAllPh) {
      const sm = reportData.summary || {};
      const rtsSummaryHtml = Number(sm.healthy_trays || sm.unhealthy_trays) 
        ? `<span>${Number(sm.ready_to_sale_trays) || 0}</span><div style="font-size:0.72rem;font-weight:700;color:#047857;margin-top:2px;">Healthy: ${Number(sm.healthy_trays)||0} | Unhealthy: ${Number(sm.unhealthy_trays)||0}</div>`
        : `${Number(sm.ready_to_sale_trays) || 0}`;

      $('#phSummary').html(`
        ${kpi('Total Beds', Number(sm.total_beds) || 0)}
        ${kpi('Occupied Beds', Number(sm.occupied_beds) || 0)}
        ${kpi('Unoccupied Beds', Number(sm.unoccupied_beds) || 0)}
        ${kpi('Plant Count', Number(sm.plant_count) || 0)}
        ${kpi('Occupied Trays', Number(sm.occupied_trays !== undefined ? sm.occupied_trays : sm.total_trays) || 0)}
        ${kpi('Ready to Sale', rtsSummaryHtml)}
        ${kpi('Verified', Number(sm.verified_trays) || 0)}
        ${kpi('Unverified', Number(sm.unverified_trays) || 0)}
        ${kpi('Germ. Failure', Number(sm.germination_failure_trays) || 0)}
      `).show();

      $('#phReportResult').html(`
        <div class="ph-empty text-center py-5">
          <div style="font-size:1.2rem;font-weight:700;color:#1e293b;margin-bottom:8px;">
            <i class="fa fa-layer-group text-primary mr-2"></i>All Polyhouses Selected
          </div>
          <div class="text-muted" style="font-size:0.95rem;max-width:580px;margin:0 auto 16px;">
            Please select any product from the <strong>Product</strong> filter above to view its production open stock and bed allocations across all polyhouses.
          </div>
          <button type="button" class="btn btn-sm btn-outline-primary" id="btnPhShowAll" style="font-weight:600;padding:0.4rem 1.1rem;border-radius:8px;">
            <i class="fa fa-th-list mr-1"></i>View All Beds &amp; Products (${(reportData.beds||[]).length} beds)
          </button>
        </div>
      `);
      $('#btnPhShowAll').off('click').on('click', function() {
        forceShowAllInAllPh = true;
        renderReport();
      });
      return;
    }

    const beds=filteredBeds();

    let sm;
    if (!isFiltered && reportData.summary) {
      // Full Polyhouse summary
      sm = reportData.summary;
    } else {
      // Dynamic summary based on filtered view (e.g. specific Bed or Product)
      const occBedSet = new Set();
      const prodSet = new Set();
      let totTrays = 0, rtsTrays = 0, verTrays = 0, unverTrays = 0, germTrays = 0, hlthTrays = 0, unhlthTrays = 0, shftTrays = 0, nonShftTrays = 0;
      
      beds.forEach(b => {
        let bedHasOccupied = false;
        (b.orders || []).forEach(o => {
          const occTrays = (o.occupied_trays !== undefined) ? Number(o.occupied_trays) || 0 : Number(o.total_trays) || 0;
          const hlth = (occTrays > 0) ? Math.min(occTrays, (o.healthy_trays !== undefined && o.healthy_trays !== null) ? Number(o.healthy_trays) : Math.max(0, occTrays - (Number(o.unhealthy_trays) || 0))) : 0;
          const unhlth = (occTrays > 0) ? Math.min(occTrays, Number(o.unhealthy_trays) || 0) : 0;
          const rts = Math.min(Number(o.ready_to_sale_trays) || 0, hlth, occTrays);

          if (occTrays > 0) bedHasOccupied = true;
          if (occTrays > 0 && o.productid) prodSet.add(String(o.productid));
          totTrays += occTrays;
          rtsTrays += rts;
          verTrays += Math.min(occTrays, Number(o.verified_trays) || 0);
          unverTrays += Math.min(occTrays, Number(o.unverified_trays) || 0);
          germTrays += Math.min(occTrays, Number(o.germination_failure_trays) || unhlth);
          hlthTrays += hlth;
          unhlthTrays += unhlth;
          shftTrays += Math.min(occTrays, Number(o.shifted_trays) || 0);
          nonShftTrays += Math.min(occTrays, Number(o.nonshifted_trays) || 0);
        });
        if (bedHasOccupied && b.bed_no) occBedSet.add((b.polyhouseid || '') + '|' + b.bed_no);
      });

      const totalBedsCount = selectedBed ? 1 : (beds.length || Number(reportData.summary?.total_beds) || 0);
      const occBedsCount = occBedSet.size;
      const unoccBedsCount = Math.max(0, totalBedsCount - occBedsCount);

      sm = {
        total_beds: totalBedsCount,
        occupied_beds: occBedsCount,
        unoccupied_beds: unoccBedsCount,
        plant_count: prodSet.size,
        total_trays: totTrays,
        occupied_trays: totTrays,
        ready_to_sale_trays: rtsTrays,
        verified_trays: verTrays,
        unverified_trays: unverTrays,
        germination_failure_trays: germTrays,
        healthy_trays: hlthTrays,
        unhealthy_trays: unhlthTrays,
        shifted_trays: shftTrays,
        nonshifted_trays: nonShftTrays
      };
    }

    const rtsSummaryHtml = Number(sm.healthy_trays || sm.unhealthy_trays) 
      ? `<span>${Number(sm.ready_to_sale_trays) || 0}</span><div style="font-size:0.72rem;font-weight:700;color:#047857;margin-top:2px;">Healthy: ${Number(sm.healthy_trays)||0} | Unhealthy: ${Number(sm.unhealthy_trays)||0}</div>`
      : `${Number(sm.ready_to_sale_trays) || 0}`;

    $('#phSummary').html(`
      ${kpi('Total Beds', Number(sm.total_beds) || 0)}
      ${kpi('Occupied Beds', Number(sm.occupied_beds) || 0)}
      ${kpi('Unoccupied Beds', Number(sm.unoccupied_beds) || 0)}
      ${kpi('Plant Count', Number(sm.plant_count) || 0)}
      ${kpi('Occupied Trays', Number(sm.occupied_trays !== undefined ? sm.occupied_trays : sm.total_trays) || 0)}
      ${kpi('Ready to Sale', rtsSummaryHtml)}
      ${kpi('Verified', Number(sm.verified_trays) || 0)}
      ${kpi('Unverified', Number(sm.unverified_trays) || 0)}
      ${kpi('Germ. Failure', Number(sm.germination_failure_trays) || 0)}
    `).show();

    if(!beds.length){$('#phReportResult').html('<div class="ph-empty">No records found for selected filters.</div>');return;}
    let html='';
    beds.sort((a,b)=>{
      if (isAllPh) {
        const phCmp = String(a.polyhousename || '').localeCompare(String(b.polyhousename || ''));
        if (phCmp !== 0) return phCmp;
      }
      return naturalBed(a.bed_no)-naturalBed(b.bed_no);
    }).forEach(b=>{
      const occupied=(b.orders||[]).reduce((s,o)=>s+(o.occupied_trays !== undefined ? (+o.occupied_trays || 0) : (+o.total_trays || 0)),0);
      const cap=+b.capacity||0, available=cap>0?Math.max(0,cap-occupied):0;
      const capClass=cap>0 && occupied>cap?'bad':(cap>0 && occupied/cap>=.9?'warn':'good');
      
      const bedShifted = (b.orders || []).reduce((s, o) => s + Math.min((+o.occupied_trays || 0), (+o.shifted_trays || (o.shiftingstatus === 'shifted' ? (+o.occupied_trays || 0) : 0))), 0);
      const bedNonShifted = (b.orders || []).reduce((s, o) => s + Math.min((+o.occupied_trays || 0), (+o.nonshifted_trays || (o.shiftingstatus !== 'shifted' ? (+o.occupied_trays || 0) : 0))), 0);

      const polyBadge = b.polyhousename 
        ? `<span class="ph-pso" style="font-weight:700;color:#3730a3;background:#e0e7ff;padding:2px 8px;border-radius:6px;border:1px solid #c7d2fe;" title="Polyhouse: ${esc(b.polyhousename)}"><i class="fa fa-warehouse mr-1" style="font-size:0.8rem;"></i>${esc(b.polyhousename)}</span>`
        : `<span class="ph-pso">${esc(reportData.polyhouse.polyhousename||'')}</span>`;

      html+=`<div class="ph-bed-card" data-bed="${esc(b.bed_no)}" data-polyhouse="${esc(b.polyhouseid||'')}">
        <div class="ph-bed-head">
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
            <span class="ph-bed-name">${esc(b.bed_no)}</span> 
            ${polyBadge}
            ${bedShifted > 0 ? `<span class="ph-bed-pill-shifted" title="${bedShifted} Shifted Trays in this bed"><i class="fa fa-truck-moving mr-1"></i>Shifted: <strong>${bedShifted}</strong></span>` : ''}
            ${bedNonShifted > 0 ? `<span class="ph-bed-pill-nonshifted" title="${bedNonShifted} Non-Shifted Trays in this bed"><i class="fa fa-ban mr-1"></i>Non-Shifted: <strong>${bedNonShifted}</strong></span>` : ''}
          </div>
          <div class="ph-bed-meta">
            <span class="ph-pill">${esc(b.tray_name||'Tray')}</span>
            <span class="ph-pill">Capacity: <strong>${cap||'N/A'}</strong></span>
            <span class="ph-pill ${capClass}">Occupied: <strong>${occupied}</strong></span>
            <span class="ph-pill">Available: <strong>${cap?available:'N/A'}</strong></span>
          </div>
        </div>`;
      if(!(b.orders||[]).length){
        html+=`<div class="ph-order text-muted">No stock matching the selected filters.</div>`;
      }else{
        b.orders.forEach(o=>{
          const occTrays = (o.occupied_trays !== undefined) ? +o.occupied_trays : +o.total_trays || 0;
          const healthyTrays = (occTrays > 0) ? Math.min(occTrays, (o.healthy_trays !== undefined && o.healthy_trays !== null) ? +o.healthy_trays : Math.max(0, occTrays - (+o.unhealthy_trays || 0))) : 0;
          const unhealthyTrays = (occTrays > 0) ? Math.min(occTrays, +o.unhealthy_trays || 0) : 0;
          const shiftedTrays = (occTrays > 0) ? Math.min(occTrays, +o.shifted_trays || (o.shiftingstatus === 'shifted' ? occTrays : 0)) : 0;
          const nonshiftedTrays = (occTrays > 0) ? Math.min(occTrays, +o.nonshifted_trays || (o.shiftingstatus !== 'shifted' ? occTrays : 0)) : 0;
          
          // STRICT RULE: Only healthy trays can move to / be in Ready to Sale, bounded by occupied trays!
          const rawRts = +o.ready_to_sale_trays || 0;
          const rtsTrays = Math.min(rawRts, healthyTrays, occTrays);
          const isReady = rtsTrays > 0;

          // Prepare status breakdown with bounded Ready to Sale
          const sc = Object.assign({}, o.status_counts || {});
          if (sc['Ready to Sale'] !== undefined) {
            sc['Ready to Sale'] = rtsTrays;
          }
          if (occTrays === 0) {
            ['Polyhouse', 'Open Stock', 'Ready to Sale'].forEach(k => {
              if (sc[k] !== undefined) sc[k] = 0;
            });
          }
          if (unhealthyTrays > 0 && !sc['Germination Failure']) {
            sc['Germination Failure'] = unhealthyTrays;
          }

          let healthPillsHtml = '';
          if (healthyTrays > 0) {
            healthPillsHtml += `<span class="ph-status-pill ph-sc-healthy" title="Healthy Trays"><i class="fa fa-heartbeat mr-1"></i>Healthy: <strong>${healthyTrays}</strong></span>`;
          }
          if (unhealthyTrays > 0) {
            healthPillsHtml += `<span class="ph-status-pill ph-sc-unhealthy" title="Unhealthy / Germination Failure Trays"><i class="fa fa-exclamation-triangle mr-1"></i>Unhealthy: <strong>${unhealthyTrays}</strong></span>`;
          }

          let shiftingPillsHtml = '';
          if (occTrays > 0) {
            if (shiftedTrays > 0 && nonshiftedTrays > 0) {
              shiftingPillsHtml += `<span class="ph-status-pill ph-sc-shifted" title="Shifted Trays"><i class="fa fa-truck-moving mr-1"></i>Shifted: <strong>${shiftedTrays}</strong></span>`;
              const failSuffix = +o.failerpercent > 0 ? ` [${o.failerpercent}% Fail]` : '';
              shiftingPillsHtml += `<span class="ph-status-pill ph-sc-nonshifted" title="Non-Shifted Trays"><i class="fa fa-ban mr-1"></i>Non-Shifted: <strong>${nonshiftedTrays}</strong>${failSuffix}</span>`;
            } else if (o.shiftingstatus === 'shifted' || (shiftedTrays > 0 && nonshiftedTrays === 0)) {
              const cnt = shiftedTrays || occTrays;
              if (cnt > 0) {
                shiftingPillsHtml += `<span class="ph-status-pill ph-sc-shifted" title="Shifted Trays"><i class="fa fa-truck-moving mr-1"></i>Shifted: <strong>${cnt}</strong></span>`;
              }
            } else {
              const cnt = nonshiftedTrays || occTrays;
              if (cnt > 0) {
                const failSuffix = +o.failerpercent > 0 ? ` [${o.failerpercent}% Fail]` : '';
                shiftingPillsHtml += `<span class="ph-status-pill ph-sc-nonshifted" title="Non-Shifted Trays"><i class="fa fa-ban mr-1"></i>Non-Shifted: <strong>${cnt}</strong>${failSuffix}</span>`;
              }
            }
          }

          html+=`<div class="ph-order ${isReady ? 'ph-order-ready' : ''}">
            <div class="ph-order-row">
              <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                <div><span class="ph-order-no">${esc(o.stockorder_no||'N/A')}</span><span class="ph-pso">/ ${esc(o.psono||'N/A')}</span></div>
                ${healthPillsHtml}
                ${shiftingPillsHtml}
              </div>
              <div class="ph-product-cell" style="min-width: 0;">
                <div class="ph-product">${esc(o.productname||'N/A')}</div>
                <div class="ph-order-client" title="Client: ${esc(o.client_name || 'N/A')}">
                  <i class="fa fa-user text-primary" style="font-size: 0.78rem;"></i>
                  <span>Client: <strong>${esc(o.client_name || 'N/A')}</strong></span>
                </div>
              </div>
              <div class="ph-metrics">
                <span>Occupied: <strong class="text-primary">${occTrays}</strong></span>
                <span>Total: <strong>${+o.total_trays||0}</strong></span>
                ${isReady ? `<span style="color:#059669;">Ready: <strong>${rtsTrays}</strong></span>` : ''}
                <span>Healthy: <strong class="text-success">${healthyTrays}</strong></span>
                ${unhealthyTrays > 0 ? `<span>Unhealthy: <strong class="text-danger">${unhealthyTrays}</strong></span>` : ''}
                <span>Verified: <strong>${occTrays > 0 ? (+o.verified_trays||0) : 0}</strong></span>
                <span>Unverified: <strong>${occTrays > 0 ? (+o.unverified_trays||0) : 0}</strong></span>
              </div>
            </div>
            <div class="ph-order-row">
              <div class="ph-dates" style="display:flex;align-items:center;justify-content:space-between;gap:6px;flex-wrap:wrap;grid-column:1/-1;">
                <div style="display:flex;gap:1rem;flex-wrap:wrap;align-items:center;">
                  <span>Internal Dispatch: <strong>${fmtDate(o.dispatchdate_internal)}</strong></span>
                  <span>Sowing: <strong>${fmtDate(o.sowingdate)}</strong></span>
                  <span>Delivery: <strong>${fmtDate(o.expected_delivery_date)}</strong></span>
                  <span>Last Verify: <strong>${fmtDateTime(o.verification_date)}</strong></span>
                  <span class="ph-fail">Germ. Failure: <strong>${+o.germination_failure_trays||0}</strong></span>
                </div>
                <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                  <button type="button" class="ph-btn-update-status" onclick="openTrayStatusModal('${esc(b.bed_no)}', '${esc(o.stockorder_no)}', '${esc(o.psono)}', '${esc(o.productid)}', '${esc(b.tray_id)}', '${esc(o.productname)}', ${occTrays}, ${+b.capacity||0}, '${esc(b.tray_name||'')}', '${esc(b.polyhouseid||'')}', '${esc(b.polyhousename||'')}', '${esc(o.client_name||'')}')" title="Update Shifting & Health Status for ${esc(o.stockorder_no)} in ${esc(b.bed_no)}">
                    <i class="fa fa-clipboard-check mr-1"></i> Update Status
                  </button>
                  ${canEditRemove ? `
                    <button type="button" class="ph-btn-edit-qty" onclick="openEditOrderQtyModal('${esc(b.bed_no)}', '${esc(o.stockorder_no)}', '${esc(o.psono)}', '${esc(o.productid)}', '${esc(b.tray_id)}', '${esc(o.productname)}', ${occTrays}, ${+b.capacity||0}, '${esc(b.tray_name||'')}', '${esc(b.polyhouseid||'')}', '${esc(b.polyhousename||'')}', '${esc(o.client_name||'')}')" title="Edit Order Quantity for ${esc(o.stockorder_no)} in ${esc(b.bed_no)}">
                      <i class="fa fa-edit mr-1"></i> Edit Qty
                    </button>
                    <button type="button" class="ph-btn-remove-order" onclick="confirmRemoveOrderFromBed('${esc(b.bed_no)}', '${esc(o.stockorder_no)}', '${esc(o.psono)}', '${esc(o.productid)}', '${esc(b.tray_id)}', '${esc(o.productname)}', ${occTrays}, '${esc(b.polyhouseid||'')}', '${esc(o.client_name||'')}')" title="Remove ${esc(o.stockorder_no)} from ${esc(b.bed_no)}">
                      <i class="fa fa-trash-alt mr-1"></i> Remove
                    </button>
                  ` : ''}
                  ${/^(RSO-|OS-)/i.test(String(o.stockorder_no || '').trim()) ? `
                    <button type="button" class="ph-btn-print-qr" onclick="openOrderQrPrintModal('${esc(b.bed_no)}', '${esc(o.stockorder_no)}', '${esc(o.psono)}', '${esc(o.productid)}', '${esc(b.tray_id)}', '${esc(o.productname)}', '${esc(b.polyhouseid||'')}', '${esc(b.polyhousename||'')}')" title="Print QR Code Labels for ${esc(o.stockorder_no)} in ${esc(b.bed_no)}">
                      <i class="fa fa-qrcode mr-1"></i> Print QR
                    </button>
                  ` : ''}
                </div>
              </div>
            </div>
            ${renderStatusCountsRow(o, b.bed_no, sc, occTrays, healthyTrays, unhealthyTrays, shiftedTrays, nonshiftedTrays, rtsTrays, b.polyhouseid, b.polyhousename)}
          </div>`;
        });
      }
      html+='</div>';
    });
    $('#phReportResult').html(html);
  }

  function renderStatusCountsRow(o, bedNo, sc, occTrays, healthyTrays, unhealthyTrays, shiftedTrays, nonshiftedTrays, rtsTrays, polyhouseId, polyhouseName) {
    let pills = '';

    // 1. Ready to Sale
    if (rtsTrays > 0) {
      pills += `<span class="ph-status-pill ph-sc-ready" onclick="openStatusBreakdownModal('${esc(bedNo)}', '${esc(o.stockorder_no)}', '${esc(o.psono)}', '${esc(o.productid)}', '${esc(o.productname)}', 'Ready to Sale', ${rtsTrays}, '${esc(polyhouseId||'')}', '${esc(polyhouseName||'')}', '${esc(o.client_name||'')}')" title="Click to view ${rtsTrays} Ready to Sale trays"><i class="fa fa-boxes mr-1"></i>Ready to Sale: <strong>${rtsTrays}</strong></span>`;
    }

    // 2. Shifting status (Shifted or Non-Shifted) - displayed in top row after Healthy status
    // 3. Health status (Healthy and Unhealthy) - displayed in top row after Order ID

    // 4. Other lifecycle / tracking statuses from status_counts / sc
    const statusDefs = [
      { key: 'Polyhouse', label: 'Polyhouse', cls: 'ph-sc-polyhouse', icon: 'fa-seedling' },
      { key: 'Open Stock', label: 'Open Stock', cls: 'ph-sc-open', icon: 'fa-box-open' },
      { key: 'Internal Dispatch', label: 'Internal Dispatch', cls: 'ph-sc-internal', icon: 'fa-exchange-alt' },
      { key: 'Loaded', label: 'Loaded', cls: 'ph-sc-loaded', icon: 'fa-dolly-flatbed' },
      { key: 'Final Dispatch', label: 'Final Dispatch', cls: 'ph-sc-final', icon: 'fa-truck' },
      { key: 'Move for Opensale', label: 'Move for Opensale', cls: 'ph-sc-opensale', icon: 'fa-store' },
      { key: 'Move for Grafting', label: 'Move for Grafting', cls: 'ph-sc-grafting', icon: 'fa-cut' },
      { key: 'Grafting', label: 'Grafting', cls: 'ph-sc-grafting', icon: 'fa-cut' },
      { key: 'Healing', label: 'Healing', cls: 'ph-sc-healing', icon: 'fa-heartbeat' },
      { key: 'Hardening', label: 'Hardening', cls: 'ph-sc-hardening', icon: 'fa-sun' },
      { key: 'Clip Stick Removal', label: 'Clip Stick Removal', cls: 'ph-sc-clip', icon: 'fa-paperclip' },
      { key: 'Empty After Shifting', label: 'Empty After Shifting', cls: 'ph-sc-emptyshifting', icon: 'fa-dolly-flatbed' },
      { key: 'Empty After Grafting', label: 'Empty After Grafting', cls: 'ph-sc-emptygrafting', icon: 'fa-dolly' },
      { key: 'Germination Failure', label: 'Germ. Failure', cls: 'ph-sc-germfail', icon: 'fa-exclamation-triangle' },
      { key: 'Removed', label: 'Removed', cls: 'ph-sc-removed', icon: 'fa-trash-alt' },
      { key: 'Grafted', label: 'Grafted', cls: 'ph-sc-grafted', icon: 'fa-dna' }
    ];

    const handledKeys = new Set(['Ready to Sale', 'Shifted', 'Non-Shifted', 'Healthy', 'Unhealthy']);
    if (unhealthyTrays > 0) {
      handledKeys.add('Germination Failure');
    }

    if (sc && typeof sc === 'object') {
      statusDefs.forEach(def => {
        if (handledKeys.has(def.key)) return;
        const count = Number(sc[def.key]) || 0;
        if (count > 0) {
          pills += `<span class="ph-status-pill ${def.cls}" onclick="openStatusBreakdownModal('${esc(bedNo)}', '${esc(o.stockorder_no)}', '${esc(o.psono)}', '${esc(o.productid)}', '${esc(o.productname)}', '${esc(def.key)}', ${count}, '${esc(polyhouseId||'')}', '${esc(polyhouseName||'')}', '${esc(o.client_name||'')}')" title="Click to view ${count} ${def.label} trays"><i class="fa ${def.icon} mr-1"></i>${def.label}: <strong>${count}</strong></span>`;
          handledKeys.add(def.key);
        }
      });

      Object.keys(sc).forEach(k => {
        if (handledKeys.has(k)) return;
        const count = Number(sc[k]) || 0;
        if (count > 0) {
          pills += `<span class="ph-status-pill ph-sc-default" onclick="openStatusBreakdownModal('${esc(bedNo)}', '${esc(o.stockorder_no)}', '${esc(o.psono)}', '${esc(o.productid)}', '${esc(o.productname)}', '${esc(k)}', ${count}, '${esc(polyhouseId||'')}', '${esc(polyhouseName||'')}', '${esc(o.client_name||'')}')" title="Click to view ${count} ${esc(k)} trays"><i class="fa fa-tag mr-1"></i>${esc(k)}: <strong>${count}</strong></span>`;
        }
      });
    }

    if (!pills) return '';

    return `
      <div class="ph-order-row ph-status-breakdown-row">
        <div class="ph-status-counts-wrap">
          <span class="ph-status-counts-label"><i class="fa fa-tags mr-1"></i> Status Breakdown:</span>
          <div class="ph-status-pills-list">${pills}</div>
        </div>
      </div>
    `;
  }

  function kpi(label,value){return `<div class="ph-kpi"><div class="label">${label}</div><div class="value">${value}</div></div>`;}

  $('#btnResetReport').on('click',function(){
    reportData = null;

    $('#phLocation').val('');

    $('#phPolyhouse')
      .html('<option value="">Select Polyhouse</option>')
      .val('')
      .prop('disabled', true);

    $('#phBed')
      .html('<option value="">All Beds</option>')
      .val('')
      .prop('disabled', true);

    $('#phProduct')
      .empty()
      .val(null)
      .prop('disabled', true)
      .trigger('change.select2');

    $('#phVerify').val('');
    $('#phSearch').val('');

    $('#phSummary').hide().empty();
    $('#phReportMeta').hide();
    $('#phGeneratedOn,#phReportScope').text('');
    $('#phPrintLocation,#phPrintPolyhouse,#phPrintGeneratedOn').text('-');

    $('#phReportResult').html(
      '<div class="ph-empty">Select a location and polyhouse to view bed-wise stock.</div>'
    );

    $('#phLocation').focus();
  });

  $('#btnExcel').on('click',function(){
    const beds=filteredBeds();
    if(!beds.length){alert('No data to export.');return;}

    const colCount = 24;
    const title = 'Polyhouse Bed-wise Stock & Verification Report';
    const locationName = reportData.location?.locationname || '';
    const polyhouseName = reportData.polyhouse?.polyhousename || '';
    const generatedOn = reportData.generated_on || '';

    let tableHtml = `
      <table border="1">
        <tr>
          <th colspan="${colCount}" style="font-size:18px;font-weight:bold;text-align:center;padding:8px;">
            ${esc(title)}
          </th>
        </tr>
        <tr>
          <td colspan="12"><strong>Location:</strong> ${esc(locationName)}</td>
          <td colspan="12"><strong>Polyhouse:</strong> ${esc(polyhouseName)}</td>
        </tr>
        <tr>
          <td colspan="${colCount}"><strong>Generated On:</strong> ${esc(generatedOn)}</td>
        </tr>
        <tr></tr>
        <tr>
          <th>Location</th>
          <th>Polyhouse</th>
          <th>Bed No</th>
          <th>Tray Type</th>
          <th>Capacity</th>
          <th>Stock Order No</th>
          <th>PSONO</th>
          <th>Client Name</th>
          <th>Product Name</th>
          <th>Shifting Status</th>
          <th>Germ. Fail %</th>
          <th>Internal Dispatch Date</th>
          <th>Sowing Date</th>
          <th>Expected Delivery Date</th>
          <th>Occupied Trays</th>
          <th>Total Trays</th>
          <th>Ready to Sale</th>
          <th>Healthy Trays</th>
          <th>Unhealthy Trays</th>
          <th>Status Breakdown</th>
          <th>Verified</th>
          <th>Unverified</th>
          <th>Last Verification</th>
          <th>Germination Failure</th>
        </tr>`;

    beds.forEach(b => {
      (b.orders || []).forEach(o => {
        const statusBreakdownStr = Object.entries(o.status_counts || {})
          .filter(([k, v]) => Number(v) > 0)
          .map(([k, v]) => `${k}: ${v}`)
          .join(', ');

        const occTrays = (o.occupied_trays !== undefined) ? (+o.occupied_trays || 0) : (+o.total_trays || 0);
        const healthyTrays = (occTrays > 0) ? Math.min(occTrays, (o.healthy_trays !== undefined && o.healthy_trays !== null) ? +o.healthy_trays : Math.max(0, occTrays - (+o.unhealthy_trays || 0))) : 0;
        const rtsTrays = Math.min(+o.ready_to_sale_trays || 0, healthyTrays, occTrays);

        const row = [
          locationName,
          b.polyhousename || polyhouseName,
          b.bed_no,
          b.tray_name,
          b.capacity,
          o.stockorder_no,
          o.psono,
          o.client_name || '',
          o.productname,
          o.shiftingstatus || 'nonshifted',
          o.failerpercent || 0,
          o.dispatchdate_internal,
          o.sowingdate,
          o.expected_delivery_date,
          occTrays,
          o.total_trays,
          rtsTrays,
          healthyTrays,
          occTrays > 0 ? (+o.unhealthy_trays || 0) : 0,
          statusBreakdownStr || 'Active',
          occTrays > 0 ? (+o.verified_trays || 0) : 0,
          occTrays > 0 ? (+o.unverified_trays || 0) : 0,
          o.verification_date,
          occTrays > 0 ? (+o.germination_failure_trays || 0) : 0
        ];
        tableHtml += '<tr>' + row.map(c => '<td>' + esc(c == null ? '' : c) + '</td>').join('') + '</tr>';
      });
    });

    tableHtml += '</table>';

    const blob = new Blob(['\ufeff', tableHtml], {type:'application/vnd.ms-excel'});
    const a = document.createElement('a');
    const url = URL.createObjectURL(blob);
    a.href = url;
    a.download = 'Polyhouse_Bed_Wise_Report.xls';
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
  });

  $('#btnPdf').on('click',function(){ if(!reportData){alert('Load report first.');return;} window.print(); });

  // =========================================================
  // ADD READY STOCK / OPEN STOCK MODAL LOGIC (WITH SEARCHABLE SELECT2)
  // =========================================================
  let readyStockMasters = null;
  let polyhouseMasterCache = {};

  // Fix Bootstrap modal focus trapping that blocks Select2 search input on mobile & desktop
  if ($.fn.modal && $.fn.modal.Constructor) {
    if ($.fn.modal.Constructor.prototype._enforceFocus) {
      $.fn.modal.Constructor.prototype._enforceFocus = function() {};
    }
    if ($.fn.modal.Constructor.prototype.enforceFocus) {
      $.fn.modal.Constructor.prototype.enforceFocus = function() {};
    }
  }

  // Auto-focus search input whenever Select2 dropdown opens (works reliably on mobile & desktop)
  $(document).on('select2:open', function(e) {
    const focusSearchField = function() {
      const searchField = document.querySelector('.select2-container--open .select2-search__field');
      if (searchField) {
        searchField.focus();
      }
    };
    focusSearchField();
    setTimeout(focusSearchField, 25);
    setTimeout(focusSearchField, 70);
    setTimeout(focusSearchField, 150);
  });

  function initModalSelect2() {
    if (typeof $.fn.select2 !== 'undefined') {
      $('#rsProduct, #rsLocation, #rsPolyhouse, #rsBed, #rsTrayId').each(function() {
        if (!$(this).hasClass('select2-hidden-accessible')) {
          $(this).select2({
            dropdownParent: $('#modalAddReadyStock'),
            width: '100%',
            placeholder: $(this).find('option:first').text() || 'Select Option'
          });
        }
      });
    }
  }

  function loadReadyStockMasters(cb) {
    if (readyStockMasters) {
      if (typeof cb === 'function') cb(readyStockMasters);
      return;
    }
    $.post(getModalUrl('getreadystockmasters'), { projectid }, function(res) {
      if (res.status === 'success') {
        readyStockMasters = res;
        if (typeof cb === 'function') cb(readyStockMasters);
      } else {
        alert(res.message || 'Failed to load master data for ready stock.');
      }
    }, 'json').fail(function() {
      alert('Network error loading ready stock masters.');
    });
  }

  function setStockType(type) {
    if (type === 'openstock') {
      $('input[name="stock_type"][value="openstock"]').prop('checked', true);
      $('#pillStockOpen').addClass('active-open').removeClass('active-ready');
      $('#pillStockReady').removeClass('active-ready').removeClass('active-open');
      $('#stockTypePillBadge').text('Open Stock Selected').css({'background': '#d1fae5', 'color': '#065f46'});
      $('#lblBillNo').html('Enter Bill / Reference No <span class="text-danger">*</span>');
      $('#rsBillNo').attr('placeholder', 'e.g. OS-' + new Date().getFullYear() + ' or Bill No');
      $('#btnSubmitReadyStock').html('<i class="fa fa-save mr-1"></i> Save Open Stock').css({'background': '#059669', 'border-color': '#047857'});
      $('#modalAddStockHeader').css('background', 'linear-gradient(135deg, #059669 0%, #10b981 100%)');
      $('#modalAddReadyStockLabel').html('<i class="fa fa-seedling mr-2"></i>Add Open Stock');
    } else {
      $('input[name="stock_type"][value="readystock"]').prop('checked', true);
      $('#pillStockReady').addClass('active-ready').removeClass('active-open');
      $('#pillStockOpen').removeClass('active-open').removeClass('active-ready');
      $('#stockTypePillBadge').text('Ready Stock Selected').css({'background': '#e0e7ff', 'color': '#3730a3'});
      $('#lblBillNo').html('Enter Bill No of Product <span class="text-danger">*</span>');
      $('#rsBillNo').attr('placeholder', 'e.g. BILL-9821');
      $('#btnSubmitReadyStock').html('<i class="fa fa-save mr-1"></i> Save Ready Stock').css({'background': '#4f46e5', 'border-color': '#4338ca'});
      $('#modalAddStockHeader').css('background', 'linear-gradient(135deg, #4f46e5 0%, #6366f1 100%)');
      $('#modalAddReadyStockLabel').html('<i class="fa fa-boxes mr-2"></i>Add Ready Stock');
    }

    const curBill = $('#rsBillNo').val() ? $('#rsBillNo').val().trim() : '';
    if (curBill) {
      checkBillNoExists(curBill, type);
    } else {
      resetBillNoValidation();
    }
  }

  $('#pillStockReady').on('click', function() {
    setStockType('readystock');
  });

  $('#pillStockOpen').on('click', function() {
    setStockType('openstock');
  });

  $('input[name="stock_type"]').on('change', function() {
    setStockType($(this).val());
  });

  $('#btnOpenAddReadyStock').on('click', function() {
    if (!canAddReadyStock) {
      alert('You do not have permission to add ready stock.');
      return;
    }
    loadReadyStockMasters(function(masters) {
      // 1. Populate Locations
      let locHtml = '<option value="">Select Location</option>';
      (masters.locations || []).forEach(l => {
        locHtml += `<option value="${esc(l.locationid)}">${esc(l.locationname)}</option>`;
      });
      $('#rsLocation').html(locHtml);

      // Auto-select location from main filter if active
      const currentLoc = $('#phLocation').val();
      if (currentLoc) {
        $('#rsLocation').val(currentLoc);
      }
      $('#rsLocation').trigger('change.select2');

      // 2. Populate Products
      let prodHtml = '<option value="">Select Product</option>';
      (masters.products || []).forEach(p => {
        const graftTag = p.IsGrafted ? ` [${p.IsGrafted}]` : '';
        prodHtml += `<option value="${esc(p.ProductID)}" data-isgrafted="${esc(p.IsGrafted || '')}">${esc(p.ProductName)}${graftTag}</option>`;
      });
      $('#rsProduct').html(prodHtml);

      // Auto-select product if selected in main filter
      const currentProd = $('#phProduct').val();
      if (currentProd) {
        const prodId = Array.isArray(currentProd) ? (currentProd.length === 1 ? currentProd[0] : '') : currentProd;
        if (prodId) {
          $('#rsProduct').val(prodId);
        }
      }
      $('#rsProduct').trigger('change.select2');

      // 3. Populate Trays
      let trayHtml = '<option value="">Select Tray Cavity</option>';
      (masters.trays || []).forEach(t => {
        trayHtml += `<option value="${esc(t.tray_id)}" data-cavity="${esc(t.cavity_count)}">${esc(t.tray_name)} (${esc(t.cavity_count)} Cavity)</option>`;
      });
      $('#rsTrayId').html(trayHtml).trigger('change.select2');

      // Default dates
      const todayStr = new Date().toISOString().slice(0, 10);
      if (!$('#rsDispatchDate').val()) $('#rsDispatchDate').val(todayStr);

      setStockType('readystock');

      // Load Polyhouses & Beds for the selected location
      syncModalPolyhouses(function() {
        const currentPh = $('#phPolyhouse').val();
        if (currentPh) {
          $('#rsPolyhouse').val(currentPh).trigger('change.select2');
          syncModalBeds(function() {
            const currentBed = $('#phBed').val();
            if (currentBed) {
              $('#rsBed').val(currentBed).trigger('change.select2');
            }
          });
        }
      });

      $('#modalAddReadyStock').modal('show');
      setTimeout(initModalSelect2, 150);
    });
  });

  $('#modalAddReadyStock').on('shown.bs.modal', function() {
    initModalSelect2();
  });

  let billCheckTimer = null;
  let billCheckXhr = null;
  let isBillNoValid = false;
  let lastCheckedBillNo = '';

  function resetBillNoValidation() {
    clearTimeout(billCheckTimer);
    if (billCheckXhr && typeof billCheckXhr.abort === 'function') {
      try { billCheckXhr.abort(); } catch(e) {}
    }
    isBillNoValid = false;
    lastCheckedBillNo = '';
    $('#rsBillNo').removeClass('is-available is-unavailable');
    $('#rsBillStatus').empty();
    $('#btnSubmitReadyStock').prop('disabled', false);
  }

  $('#modalAddReadyStock').on('hidden.bs.modal', function() {
    $('#formAddReadyStock')[0].reset();
    resetBillNoValidation();
    $('#rsProduct, #rsLocation, #rsPolyhouse, #rsBed, #rsTrayId').val('').trigger('change.select2');
    $('.rs-radio-pill').removeClass('active');
    $('input[name="tray_position"][value="Start"]').prop('checked', true).closest('.rs-radio-pill').addClass('active');
    setStockType('readystock');
    checkProductGrafted();
  });

  $(document).on('change', 'input[name="tray_position"]', function() {
    $('.rs-radio-pill').removeClass('active');
    $(this).closest('.rs-radio-pill').addClass('active');
  });

  $('#rsBillNo').on('input change blur', function(e) {
    const stockType = $('input[name="stock_type"]:checked').val() || 'readystock';
    const billNo = $(this).val().trim();
    if (!billNo) {
      resetBillNoValidation();
      return;
    }
    if (e.type === 'blur' && lastCheckedBillNo === billNo && $('#rsBillStatus').children().length > 0) {
      return;
    }
    clearTimeout(billCheckTimer);
    const delay = (e.type === 'blur' || e.type === 'change') ? 50 : 350;

    $('#rsBillStatus').html('<span class="text-secondary"><i class="fa fa-spinner fa-spin mr-1"></i> Checking availability...</span>');
    $('#rsBillNo').removeClass('is-available is-unavailable');

    billCheckTimer = setTimeout(function() {
      checkBillNoExists(billNo, stockType);
    }, delay);
  });

  function checkBillNoExists(billNo, stockType) {
    if (!billNo) {
      resetBillNoValidation();
      return;
    }
    stockType = stockType || ($('input[name="stock_type"]:checked').val() || 'readystock');

    if (billCheckXhr && typeof billCheckXhr.abort === 'function') {
      try { billCheckXhr.abort(); } catch(e) {}
    }

    billCheckXhr = $.post(getModalUrl('checkbillno'), { projectid, bill_no: billNo, stock_type: stockType }, function(res) {
      lastCheckedBillNo = billNo;
      if (res && res.status === 'success') {
        if (res.exists || !res.available) {
          isBillNoValid = false;
          $('#rsBillNo').removeClass('is-available').addClass('is-unavailable');
          const cleanBill = billNo.replace(/^(RSO|OS)-/i, '');
          const fullOrderNo = res.stockorder_no || ((stockType === 'openstock' ? 'OS-' : 'RSO-') + cleanBill);
          $('#rsBillStatus').html(`
            <span class="text-danger font-weight-bold d-inline-flex align-items-center">
              <i class="fa fa-times-circle mr-1 text-danger"></i> Already exists (${esc(fullOrderNo)}) — Cannot use this stock number!
            </span>
          `);
          $('#btnSubmitReadyStock').prop('disabled', true);
        } else {
          isBillNoValid = true;
          $('#rsBillNo').removeClass('is-unavailable').addClass('is-available');
          const cleanBill = billNo.replace(/^(RSO|OS)-/i, '');
          const fullOrderNo = res.stockorder_no || ((stockType === 'openstock' ? 'OS-' : 'RSO-') + cleanBill);
          $('#rsBillStatus').html(`
            <span class="text-success font-weight-bold d-inline-flex align-items-center">
              <i class="fa fa-check-circle mr-1 text-success"></i> Available (${esc(fullOrderNo)}) — You can add this stock
            </span>
          `);
          $('#btnSubmitReadyStock').prop('disabled', false);
        }
      } else {
        isBillNoValid = false;
        $('#rsBillNo').removeClass('is-available is-unavailable');
        $('#rsBillStatus').html(`
          <span class="text-warning font-weight-bold d-inline-flex align-items-center">
            <i class="fa fa-exclamation-triangle mr-1"></i> ${esc((res && res.message) || 'Could not verify stock number')}
          </span>
        `);
      }
    }, 'json').fail(function(xhr, textStatus) {
      if (textStatus === 'abort') {
        return; // Superseded by a newer keystroke
      }
      isBillNoValid = false;
      $('#rsBillNo').removeClass('is-available is-unavailable');
      let errMsg = 'Network error checking availability';
      if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
        errMsg = xhr.responseJSON.message;
      } else if (xhr && xhr.status === 404) {
        errMsg = 'Endpoint not found (404)';
      } else if (xhr && xhr.status === 500) {
        errMsg = 'Server database error checking stock';
      }
      $('#rsBillStatus').html(`
        <span class="text-warning d-inline-flex align-items-center">
          <i class="fa fa-exclamation-triangle mr-1"></i> ${esc(errMsg)}
        </span>
      `);
    });
  }

  function syncModalPolyhouses(cb) {
    const locId = $('#rsLocation').val();
    $('#rsPolyhouse').html('<option value="">Loading Polyhouses...</option>').trigger('change.select2');
    $('#rsBed').html('<option value="">Select Polyhouse first</option>').trigger('change.select2');
    if (!locId) {
      $('#rsPolyhouse').html('<option value="">Select Location first</option>').trigger('change.select2');
      if (typeof cb === 'function') cb();
      return;
    }
    $.post(getModalUrl('getpolyhouses'), { projectid, locationid: locId }, function(res) {
      let phHtml = '<option value="">Select Polyhouse</option>';
      if (res.status === 'success') {
        polyhouseMasterCache[locId] = res.data || [];
        (res.data || []).forEach(ph => {
          phHtml += `<option value="${esc(ph.polyhouseid)}">${esc(ph.polyhousename)}</option>`;
        });
      }
      $('#rsPolyhouse').html(phHtml).trigger('change.select2');
      syncModalBeds(cb);
    }, 'json').fail(function() {
      $('#rsPolyhouse').html('<option value="">Error loading polyhouses</option>').trigger('change.select2');
      if (typeof cb === 'function') cb();
    });
  }

  function syncModalBeds(cb) {
    const locId = $('#rsLocation').val();
    const phId = $('#rsPolyhouse').val();
    $('#rsBed').html('<option value="">Select Bed</option>').trigger('change.select2');
    if (!locId || !phId) {
      if (typeof cb === 'function') cb();
      return;
    }
    const phList = polyhouseMasterCache[locId] || [];
    const ph = phList.find(x => String(x.polyhouseid) === String(phId));
    let bedHtml = '<option value="">Select Bed</option>';
    if (ph && ph.beds && ph.beds.length) {
      ph.beds.forEach(b => {
        bedHtml += `<option value="${esc(b)}">${esc(b)}</option>`;
      });
    } else {
      for (let i = 1; i <= 20; i++) {
        bedHtml += `<option value="Bed-${i}">Bed-${i}</option>`;
      }
    }
    $('#rsBed').html(bedHtml).trigger('change.select2');
    if (typeof cb === 'function') cb();
  }

  $('#rsLocation').on('change', function() {
    syncModalPolyhouses();
  });

  $('#rsPolyhouse').on('change', function() {
    syncModalBeds();
  });


  // Submit Ready Stock / Open Stock
  $('#formAddReadyStock').on('submit', function(e) {
    e.preventDefault();
    const stock_type = $('input[name="stock_type"]:checked').val() || 'readystock';
    const locationid = $('#rsLocation').val();
    const polyhouseid = $('#rsPolyhouse').val();
    const bed_no = $('#rsBed').val();
    const productid = $('#rsProduct').val();
    const bill_no = $('#rsBillNo').val().trim();
    const dispatch_date = $('#rsDispatchDate').val();
    const tray_id = $('#rsTrayId').val();
    const no_of_trays = parseInt($('#rsNoOfTrays').val(), 10) || 0;
    const tray_position = $('input[name="tray_position"]:checked').val() || 'Start';
    const loginid = $('body').data('loginid') || $('#hidden_loginid').val() || '';
    const companyid = $('body').data('companyid') || $('#hidden_companyid').val() || 0;
    const subcompanyid = $('body').data('subcompanyid') || $('#hidden_subcompanyid').val() || 0;

    if (!locationid) { alert('Please select Location.'); $('#rsLocation').focus(); return; }
    if (!polyhouseid) { alert('Please select Polyhouse.'); $('#rsPolyhouse').focus(); return; }
    if (!bed_no) { alert('Please select Bed.'); $('#rsBed').focus(); return; }
    if (!productid) { alert('Please select Product.'); $('#rsProduct').focus(); return; }
    if (!bill_no) { 
      alert(stock_type === 'openstock' ? 'Please enter Bill / Reference No.' : 'Please enter Bill No of Product.'); 
      $('#rsBillNo').focus(); 
      return; 
    }
    if (!isBillNoValid) {
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'error',
          title: 'Stock Number Unavailable',
          text: 'This Stock Number / Bill No already exists or has not been verified. Please enter a unique, available Stock Number.'
        });
      } else {
        alert('This Stock Number / Bill No already exists or has not been verified. Please enter a unique, available Stock Number.');
      }
      $('#rsBillNo').focus();
      return;
    }
    if (!dispatch_date) { alert('Please choose Dispatched Date.'); $('#rsDispatchDate').focus(); return; }
    if (!tray_id) { alert('Please select Tray Cavity.'); $('#rsTrayId').focus(); return; }
    if (no_of_trays <= 0) { alert('No of Trays must be at least 1.'); $('#rsNoOfTrays').focus(); return; }


    const postData = {
      stock_type,
      projectid,
      companyid,
      subcompanyid,
      locationid,
      polyhouseid,
      bed_no,
      productid,
      bill_no,
      dispatch_date,
      tray_id,
      no_of_trays,
      tray_position,
      loginid,
      loginroleid
    };

    const $btn = $('#btnSubmitReadyStock');
    const oldHtml = $btn.html();
    $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i> Processing...');

    $.post(getModalUrl('addreadystock'), postData, function(res) {
      $btn.prop('disabled', false).html(oldHtml);
      if (res.status === 'success') {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'success',
            title: (stock_type === 'openstock') ? 'Open Stock Added!' : 'Ready Stock Added!',
            text: res.message,
            timer: 2500,
            showConfirmButton: false
          });
        } else {
          alert(res.message);
        }
        $('#modalAddReadyStock').modal('hide');
        $('#formAddReadyStock')[0].reset();
        resetBillNoValidation();
        $('#rsProduct, #rsLocation, #rsPolyhouse, #rsBed, #rsTrayId').val('').trigger('change.select2');

        // If currently viewing this location & polyhouse, reload report
        if ($('#phLocation').val() === locationid && String($('#phPolyhouse').val()) === String(polyhouseid)) {
          loadReport();
        }
      } else {
        if (typeof Swal !== 'undefined') {
          Swal.fire('Error', res.message || 'Failed to save stock.', 'error');
        } else {
          alert('Error: ' + (res.message || 'Failed to save stock.'));
        }
      }
    }, 'json').fail(function() {
      $btn.prop('disabled', false).html(oldHtml);
      if (typeof Swal !== 'undefined') {
        Swal.fire('Error', 'Server or network error while saving stock.', 'error');
      } else {
        alert('Server or network error while saving stock.');
      }
    });
  });

  // =========================================================
  // PRINT ORDER QR CODE MODAL LOGIC
  // =========================================================
  window.openOrderQrPrintModal = function(bedNo, stockOrderNo, psoNo, productId, trayId, productName, specificPhId, specificPhName) {
    if (!/^(RSO-|OS-)/i.test(String(stockOrderNo || '').trim())) {
      return;
    }
    const locId = $('#phLocation').val();
    const phId = specificPhId || $('#phPolyhouse').val();
    const phName = specificPhName || reportData?.polyhouse?.polyhousename || '';

    $('#modalPrintOrderQrLabel').html(`Print QR Codes: <span class="font-monospace text-primary font-weight-bold fw-bold" style="color: #4f46e5;">${esc(stockOrderNo)}</span> <span class="text-secondary" style="font-size: 0.95rem; font-weight: 600;">(${esc(bedNo)})</span>`);
    $('#qrModalSubHeader').text('Loading tray barcode records...');
    $('#qrModalOrderMeta').html(`
      <span class="text-primary font-weight-bold fw-bold" style="color: #4f46e5;">${esc(stockOrderNo)}</span> <span class="text-muted">/ ${esc(psoNo)}</span> • 
      <span class="text-dark font-weight-bold fw-bold" style="color: #0f172a;">${esc(productName || 'Plant')}</span> • 
      <span class="badge bg-light text-dark border px-2 py-1" style="background:#f1f5f9;border-color:#cbd5e1!important;color:#334155!important;font-weight:600;">${esc(bedNo)} (${esc(phName)})</span>
    `);
    $('#qrModalSpinner').show();
    $('#qrModalGrid').hide().empty();
    $('#qrModalFooterCount').text('');

    const directPrintUrl = `modal/print_qrcode.php?projectid=${encodeURIComponent(projectid)}&locationid=${encodeURIComponent(locId)}&polyhouseid=${encodeURIComponent(phId)}&bed_no=${encodeURIComponent(bedNo)}&stockorder_no=${encodeURIComponent(stockOrderNo)}&productid=${encodeURIComponent(productId)}`;

    $('#modalPrintOrderQr').data('current-order', {
      projectid,
      locationid: locId,
      polyhouseid: phId,
      bed_no: bedNo,
      stockorder_no: stockOrderNo,
      psono: psoNo,
      productid: productId,
      printUrl: directPrintUrl
    });

    $('#modalPrintOrderQr').modal('show');

    $.post(getModalUrl('getorderqrcodes'), {
      projectid,
      locationid: locId,
      polyhouseid: phId,
      bed_no: bedNo,
      stockorder_no: stockOrderNo,
      psono: psoNo,
      productid: productId,
      product_name: productName
    }, function(res) {
        console.log(res);
      $('#qrModalSpinner').hide();
      if (res.status !== 'success' || !res.order_summary) {
        $('#qrModalSingleSlip').html(`
          <div class="col-12 text-center py-4 text-muted">
            <i class="fa fa-info-circle fa-2x text-warning mb-2"></i>
            <div>No QR Code records found for this order in ${esc(bedNo)}.</div>
          </div>
        `).show();
        $('#qrModalSubHeader').text('0 records found');
        return;
      }

      const summary = res.order_summary;
      $('#btnOpenFullA4Slip').attr('href', directPrintUrl);
      $('#qrModalSubHeader').text('1 Centered QR Code • Ready for A4 Normal Printer');
      $('#qrModalFooterCount').html(`Total Trays: <strong>${summary.total_trays}</strong> (${(summary.total_plants || 0).toLocaleString()} Plants)`);

      const slipHtml = `
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden" style="background:#ffffff; border: 1.5px solid #e2e8f0 !important;">
          <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
              <div>
                <h4 class="fw-bold mb-0 text-dark" style="letter-spacing: -0.5px; text-transform: uppercase;">KARJEE FARM</h4>
                <div class="small text-primary fw-bold"><i class="fa fa-seedling me-1"></i> Order Dispatch Tag (Normal A4 Printer)</div>
              </div>
              <div class="text-end">
                <span class="badge bg-dark text-white px-3 py-1.5 fw-bold" style="font-size: 0.8rem;">A4 SINGLE QR SLIP</span>
              </div>
            </div>

            <!-- Centered QR Code -->
            <div class="text-center my-3">
              <div class="d-inline-flex p-2 bg-white rounded-3 shadow-xs" style="border: 2px solid #0f172a;" id="modalSlipQrCenter"></div>
              <div class="small fw-bold text-dark mt-2"><i class="fa fa-qrcode me-1"></i> Order Dispatch QR Code</div>
              <div class="text-muted" style="font-size: 0.76rem;">Scan for full order, product & dispatch verification</div>
            </div>

            <!-- Required Fields Table -->
            <div class="table-responsive mt-3">
              <table class="table table-bordered table-sm mb-0 align-middle" style="border-color: #cbd5e1;">
                <tbody>
                  <tr>
                    <th class="bg-light text-muted fw-bold" style="width: 32%; font-size: 0.82rem; text-transform: uppercase;"><i class="fa fa-qrcode text-primary me-1"></i> Bed QR Code</th>
                    <td class="fw-bold font-monospace text-primary" style="font-size: 1.15rem; letter-spacing: 1px;">${esc(summary.bedqrcode || summary.qr_text)}</td>
                  </tr>
                  <tr>
                    <th class="bg-light text-muted fw-bold" style="font-size: 0.82rem; text-transform: uppercase;"><i class="fa fa-leaf text-primary me-1"></i> Product Name</th>
                    <td class="fw-bold text-dark" style="font-size: 1rem;">${esc(summary.productname)}</td>
                  </tr>
                  <tr>
                    <th class="bg-light text-muted fw-bold" style="font-size: 0.82rem; text-transform: uppercase;"><i class="fa fa-dna text-primary me-1"></i> Variety</th>
                    <td class="fw-bold text-primary" style="font-size: 1rem;">${esc(summary.variety)}</td>
                  </tr>
                  <tr>
                    <th class="bg-light text-muted fw-bold" style="font-size: 0.82rem; text-transform: uppercase;"><i class="fa fa-warehouse text-primary me-1"></i> Polyhouse No</th>
                    <td class="fw-bold text-dark" style="font-size: 1rem;">
                      ${esc(summary.polyhousename || phName || 'Polyhouse')} 
                      <span class="text-muted fw-normal" style="font-size: 0.85rem;">(${esc(summary.locationname || locName || '')})</span>
                    </td>
                  </tr>
                  <tr>
                    <th class="bg-light text-muted fw-bold" style="font-size: 0.82rem; text-transform: uppercase;"><i class="fa fa-layer-group text-primary me-1"></i> Bed No</th>
                    <td class="fw-bold text-dark" style="font-size: 1rem;">${esc(summary.bed_no || bedNo)}</td>
                  </tr>
                  <tr>
                    <th class="bg-light text-muted fw-bold" style="font-size: 0.82rem; text-transform: uppercase;"><i class="fa fa-file-invoice text-primary me-1"></i> Bill NO</th>
                    <td class="fw-bold font-monospace text-dark" style="font-size: 0.95rem;">${esc(summary.billno)}</td>
                  </tr>
                  <tr>
                    <th class="bg-light text-muted fw-bold" style="font-size: 0.82rem; text-transform: uppercase;"><i class="fa fa-hashtag text-primary me-1"></i> Seed Lot NO</th>
                    <td class="fw-bold font-monospace text-danger" style="font-size: 0.95rem;">${esc(summary.seed_lot_no || 'N/A')}</td>
                  </tr>
                  <tr>
                    <th class="bg-light text-muted fw-bold" style="font-size: 0.82rem; text-transform: uppercase;"><i class="fa fa-calendar-check text-primary me-1"></i> Sowing Date</th>
                    <td class="fw-semibold text-dark">${esc(summary.sowing_date || 'N/A')}</td>
                  </tr>
                  <tr>
                    <th class="bg-light text-muted fw-bold" style="font-size: 0.82rem; text-transform: uppercase;"><i class="fa fa-truck text-primary me-1"></i> Dispatch Date</th>
                    <td class="fw-bold text-success">${esc(summary.dispatch_date || 'N/A')}</td>
                  </tr>
                  <tr>
                    <th class="bg-light text-muted fw-bold" style="font-size: 0.82rem; text-transform: uppercase;"><i class="fa fa-boxes-stacked text-primary me-1"></i> No of Trays with QTY</th>
                    <td class="fw-bold text-success" style="font-size: 0.95rem;">
                      ${summary.total_trays} Trays <span class="text-muted fw-normal" style="font-size: 0.85rem;">(${(summary.total_plants || 0).toLocaleString()} Total Plants @ ${summary.qtypertray} Plants/Tray)</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

          </div>
        </div>
      `;

      $('#qrModalSingleSlip').html(slipHtml).show();

      // Render the single centered QR code safely
      try {
        const qrBox = document.getElementById('modalSlipQrCenter');
        if (qrBox) {
          qrBox.innerHTML = '';
          const qrTextValue = summary.qr_text || summary.bedqrcode || summary.stockorder_no || 'QR';
          if (typeof QRCode !== 'undefined') {
            new QRCode(qrBox, {
              text: qrTextValue,
              width: 170,
              height: 170,
              correctLevel: QRCode.CorrectLevel.M
            });
          } else {
            // High-resolution API Fallback if QRCode CDN is blocked or unavailable
            const fallbackQrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=170x170&margin=0&data=' + encodeURIComponent(qrTextValue);
            qrBox.innerHTML = `<img src="${fallbackQrUrl}" alt="QR Code" style="width:170px;height:170px;display:block;margin:auto;" />`;
          }
        }
      } catch (qrErr) {
        console.warn('QRCode JS render error:', qrErr);
        const qrBox = document.getElementById('modalSlipQrCenter');
        if (qrBox) {
          const qrTextValue = summary.qr_text || summary.bedqrcode || summary.stockorder_no || 'QR';
          const fallbackQrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=170x170&margin=0&data=' + encodeURIComponent(qrTextValue);
          qrBox.innerHTML = `<img src="${fallbackQrUrl}" alt="QR Code" style="width:170px;height:170px;display:block;margin:auto;" />`;
        }
      }

    }, 'json').fail(function(jqXHR, textStatus, errorThrown) {
      $('#qrModalSpinner').hide();
      console.error('getorderqrcodes failed:', textStatus, errorThrown, jqXHR.responseText);
      let errMsg = 'Failed to fetch QR code details from server.';
      if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
        errMsg = jqXHR.responseJSON.message;
      }
      $('#qrModalSingleSlip').html(`<div class="col-12 text-center text-danger py-4"><i class="fa fa-exclamation-triangle mr-1"></i> ${esc(errMsg)}</div>`).show();
    });
  };

  window.printModalQrStickers = function() {
    const orderData = $('#modalPrintOrderQr').data('current-order');
    let url = orderData?.printUrl;
    if (url) {
      if (!url.includes('autoprint=1')) {
        url += (url.includes('?') ? '&' : '?') + 'autoprint=1';
      }
      const win = window.open(url, '_blank');
      if (win) {
        win.focus();
      }
      $('#modalPrintOrderQr').modal('hide');
    }
  };

  $('#btnOpenFullA4Slip').on('click', function() {
    $('#modalPrintOrderQr').modal('hide');
  });

  // Direct Thermal Printing via QZ Tray (TSC / Barcode Label Printer)
  window.printModalThermalQz = async function() {
    const btn = $('#btnQzThermalModal');
    const origHtml = btn.html();
    const orderData = $('#modalPrintOrderQr').data('current-order');

    if (!orderData) {
      alert('No active order selected.');
      return;
    }

    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i> Printing...');

    try {
      if (typeof qz === 'undefined') {
        throw new Error("QZ Tray library is not loaded. Please ensure QZ Tray application is running on this computer.");
      }

      // 1. Fetch TSPL data from standalone print_qrcode.php
      const tsplRes = await $.ajax({
        url: 'modal/print_qrcode.php',
        method: 'POST',
        data: {
          projectid: orderData.projectid,
          locationid: orderData.locationid,
          polyhouseid: orderData.polyhouseid,
          bed_no: orderData.bed_no,
          stockorder_no: orderData.stockorder_no,
          psono: orderData.psono,
          productid: orderData.productid,
          format: 'tspl'
        },
        dataType: 'text'
      });

      if (!tsplRes || !tsplRes.trim()) {
        throw new Error("No TSPL label data returned from server.");
      }

      // 2. Ensure QZ Tray connection
      if (!qz.security.certHandler) {
        qz.security.setCertificatePromise(function(resolve, reject) {
          $.ajax({ 
            url: "../krf-orderandsales/qz_certificate.txt", 
            cache: false, 
            dataType: "text" 
          }).then(resolve, reject);
        });

        qz.security.setSignaturePromise(function(toSign) {
          return function(resolve, reject) {
            $.post("../krf-orderandsales/qz_signer.php", { request: toSign })
             .then(resolve, reject);
          };
        });
      }

      const connectOpts = {
        usingSecure: window.location.protocol === 'https:',
        port: {
          secure: [8181],
          insecure: [8182]
        }
      };

      if (!qz.websocket.isActive()) {
        await qz.websocket.connect(connectOpts);
      }

      // 3. Discover Thermal Printer
      let printer = null;
      const printerSearchTerms = ["TSC", "LP 46", "LP46", "LP", "Neo", "Thermal", "Xprinter", "Zebra", "Barcode"];
      for (const term of printerSearchTerms) {
        try {
          let found = await qz.printers.find(term);
          if (Array.isArray(found) && found.length > 0) {
            printer = found[0];
            break;
          } else if (found && typeof found === 'string') {
            printer = found;
            break;
          }
        } catch (e) {}
      }

      if (!printer) {
        printer = await qz.printers.getDefault();
      }

      if (!printer) {
        throw new Error("No thermal label printer detected. Please verify your TSC / thermal printer is plugged in and turned on.");
      }

      // 4. Send RAW TSPL to printer
      const config = qz.configs.create(printer, { encoding: 'UTF-8', raw: true });
      const printData = [{
        type: 'raw',
        format: 'command',
        flavor: 'plain',
        data: tsplRes
      }];

      await qz.print(config, printData);

      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'success',
          title: 'Direct Thermal Print Complete',
          text: `TSPL labels sent successfully to ${printer}.`,
          timer: 3000
        });
      } else {
        alert(`Printed successfully on ${printer}!`);
      }

    } catch (err) {
      console.error("QZ Print error:", err);
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'error',
          title: 'Thermal Print Notice',
          text: err.message || 'Could not connect to QZ Tray. You can also click "Print Sheet" to print via browser dialog.',
          footer: '<small class="text-muted">Ensure QZ Tray is running on your machine.</small>'
        });
      } else {
        alert(err.message || 'Thermal printing failed.');
      }
    } finally {
      btn.prop('disabled', false).html(origHtml);
    }
  };

  // =========================================================
  // EDIT ORDER QUANTITY & REMOVE ORDER MODAL LOGIC
  // =========================================================
  // =========================================================
  // EDIT ORDER QUANTITY & OCCUPIED TRAY QR MANAGEMENT
  // =========================================================
  let editOrdCurrentTraysList = [];
  let editOrdCavity = 209;
  let editOrdBedNo = '';
  let editOrdStockOrderNo = '';
  let editOrdPsoNo = '';
  let editOrdProductId = 0;
  let editOrdTrayIdVal = 0;
  let editOrdAnyRemoved = false;

  window.openEditOrderQtyModal = function(bedNo, stockOrderNo, psoNo, productId, trayId, productName, currentTrays, bedCap, trayName, specificPhId, specificPhName, clientName) {
    const locId = $('#phLocation').val();
    const phId = specificPhId || $('#phPolyhouse').val();
    const phName = specificPhName || reportData?.polyhouse?.polyhousename || '';
    
    let cavity = 209;
    if (trayName) {
      const match = trayName.match(/(\d+)\s*(?:cavity|cav)/i) || trayName.match(/(\d+)/);
      if (match) cavity = parseInt(match[1], 10) || 209;
    }
    const curTrays = parseInt(currentTrays, 10) || 0;
    const curPlants = curTrays * cavity;

    editOrdBedNo = bedNo;
    editOrdStockOrderNo = stockOrderNo;
    editOrdPsoNo = psoNo;
    editOrdProductId = productId;
    editOrdTrayIdVal = trayId;
    editOrdCavity = cavity;
    editOrdCurrentTraysList = [];
    editOrdAnyRemoved = false;

    $('#editOrdProjectId').val(projectid);
    $('#editOrdLocationId').val(locId);
    $('#editOrdPolyhouseId').val(phId);
    $('#editOrdBedNo').val(bedNo);
    $('#editOrdStockOrderNo').val(stockOrderNo);
    $('#editOrdPsoNo').val(psoNo);
    $('#editOrdProductId').val(productId);
    $('#editOrdTrayId').val(trayId);
    $('#editOrdCavityCount').val(cavity);

    $('#editOrdBadgeSo').text(stockOrderNo || 'N/A');
    $('#editOrdBadgePso').text(psoNo ? `/ ${psoNo}` : '');
    $('#editOrdBadgeBed').text(`${bedNo} (${phName})`);
    $('#editOrdProductName').text(productName || 'Plant');
    $('#editOrdClientName').text(clientName || 'N/A');
    $('#editOrdTrayName').text(trayName || `Tray (${cavity} Cavity)`);
    $('#editOrdPolyhouseName').text(phName || 'Polyhouse');

    $('#editOrdCurTraysDisp').text(curTrays);
    $('#editOrdCurPlantsDisp').text(curPlants.toLocaleString());
    $('#editOrdTrayCountBadge').text(`${curTrays} Active Trays`);

    $('#editOrdSearchTrayInput').val('');
    $('#chkEditOrdHeader').prop('checked', false);
    $('#btnEditOrdRemoveSelected').prop('disabled', true);
    $('#editOrdSelectedCount').text('0');

    // Reset Add Tray Card
    $('#editOrdAddTrayCard').hide();
    $('#editOrdNewTrayCount').val(1);
    const isRsoOrder = (stockOrderNo && stockOrderNo.toUpperCase().indexOf('RSO-') === 0);
    $('#editOrdNewTrayStatus').val(isRsoOrder ? 'Readytosale' : 'Polyhouse');
    $('#editOrdNewHealthStatus').val('Healthy');
    $('#editOrdNewPlantsCalc').val((1 * cavity).toLocaleString() + ' Plants');

    $('#editOrdTraysLoading').show();
    $('#editOrdTraysTableWrap').hide();
    $('#editOrdTraysEmpty').hide();

    $('#modalEditOrderQty').modal('show');

    // Fetch active occupied trays with QR codes
    $.post(getModalUrl('getoccupiedtrays'), {
      projectid,
      locationid: locId,
      polyhouseid: phId,
      bed_no: bedNo,
      stockorder_no: stockOrderNo,
      psono: psoNo,
      productid: productId
    }, function(res) {
      $('#editOrdTraysLoading').hide();
      if (res.status === 'success' && res.data) {
        editOrdCurrentTraysList = res.data.trays || [];
        if (res.data.qtypertray) editOrdCavity = parseInt(res.data.qtypertray, 10) || cavity;

        const curAddCount = parseInt($('#editOrdNewTrayCount').val(), 10) || 1;
        $('#editOrdNewPlantsCalc').val((curAddCount * editOrdCavity).toLocaleString() + ' Plants');

        const count = editOrdCurrentTraysList.length;
        const totalPlants = count * editOrdCavity;
        $('#editOrdCurTraysDisp').text(count);
        $('#editOrdCurPlantsDisp').text(totalPlants.toLocaleString());
        $('#editOrdTrayCountBadge').text(`${count} Active Trays`);

        renderEditOrdTraysTable();
      } else {
        $('#editOrdTraysEmpty').html(`
          <i class="fa fa-exclamation-circle fa-2x text-warning mb-2"></i>
          <div>${esc(res.message || 'No active occupied trays found.')}</div>
        `).show();
      }
    }, 'json').fail(function() {
      $('#editOrdTraysLoading').hide();
      $('#editOrdTraysEmpty').html(`
        <i class="fa fa-exclamation-triangle fa-2x text-danger mb-2"></i>
        <div>Failed to load tray details. Please try again.</div>
      `).show();
    });
  };

  function renderEditOrdTraysTable() {
    const q = ($('#editOrdSearchTrayInput').val() || '').toLowerCase().trim();
    const filtered = editOrdCurrentTraysList.filter(t => {
      if (!q) return true;
      const hay = [t.barcode, t.status, t.health_status, t.shiftingstatus].join(' ').toLowerCase();
      return hay.includes(q);
    });

    if (!filtered.length) {
      $('#editOrdTraysTableWrap').hide();
      $('#editOrdTraysEmpty').show();
      $('#chkEditOrdHeader').prop('checked', false);
      updateEditOrdSelectionCount();
      return;
    }

    $('#editOrdTraysEmpty').hide();
    $('#editOrdTraysTableWrap').show();

    let html = '';
    filtered.forEach((t, idx) => {
      const isHealthy = (t.health_status === 'Healthy' || !t.health_status);
      const isRts = (t.status === 'Readytosale' || t.status === 'Ready to sale' || t.status === 'ReadyToSale');
      html += `
        <tr data-plant-id="${t.polyhouseplantid}" data-barcode="${esc(t.barcode)}">
          <td class="col-edit-ord-chk">
            <div class="chk-cell-wrap">
              <input type="checkbox" class="edit-ord-chk edit-ord-tray-chk" value="${t.polyhouseplantid}" data-barcode="${esc(t.barcode)}" title="Select tray ${esc(t.barcode)}">
            </div>
          </td>
          <td class="col-edit-ord-idx text-muted font-weight-bold" style="font-size: 0.82rem;">#${idx + 1}</td>
          <td style="padding: 7px 10px;">
            <span class="badge bg-white text-dark border font-monospace px-2.5 py-1.5" style="font-size: 0.85rem; letter-spacing: 0.5px; border-color: #cbd5e1 !important; color: #0f172a; font-weight: 600;">
              <i class="fa fa-qrcode text-primary mr-1.5"></i>${esc(t.barcode)}
            </span>
          </td>
          <td style="width: 110px; text-align: center; padding: 7px 10px;">
            <span class="badge ${isRts ? 'bg-success' : 'bg-primary'} text-white px-2 py-1 font-weight-bold" style="font-size: 0.76rem; border-radius: 5px;">
              ${esc(t.status)}
            </span>
          </td>
          <td style="width: 100px; text-align: center; padding: 7px 10px;">
            <span class="badge ${isHealthy ? 'bg-success' : 'bg-warning'} text-white px-2 py-1 font-weight-bold" style="font-size: 0.76rem; border-radius: 5px;">
              ${esc(t.health_status || 'Healthy')}
            </span>
          </td>
          <td style="width: 110px; text-align: right; padding: 7px 12px;">
            <button type="button" class="btn btn-outline-danger btn-sm py-1 px-2.5 font-weight-bold" onclick="removeSingleOccupiedTray(${t.polyhouseplantid}, '${esc(t.barcode)}')" title="Remove this tray from occupied bed" style="border-radius: 6px; font-size: 0.78rem;">
              <i class="fa fa-trash-alt mr-1"></i>Remove
            </button>
          </td>
        </tr>
      `;
    });

    $('#editOrdTraysTbody').html(html);
    updateEditOrdSelectionCount();
  }

  function updateEditOrdSelectionCount() {
    const selectedCount = $('.edit-ord-tray-chk:checked').length;
    $('#editOrdSelectedCount').text(selectedCount);
    $('#btnEditOrdRemoveSelected').prop('disabled', selectedCount === 0);

    const totalVisible = $('.edit-ord-tray-chk').length;
    if (totalVisible > 0 && selectedCount === totalVisible) {
      $('#chkEditOrdHeader').prop('checked', true);
      $('#btnEditOrdSelectAllText').text('Deselect All');
    } else {
      $('#chkEditOrdHeader').prop('checked', false);
      $('#btnEditOrdSelectAllText').text('Select All');
    }
  }

  // Search/Scan Tray input event
  $(document).on('input', '#editOrdSearchTrayInput', function() {
    renderEditOrdTraysTable();
  });

  // Table header checkbox event
  $(document).on('change', '#chkEditOrdHeader', function() {
    const isChecked = $(this).is(':checked');
    $('.edit-ord-tray-chk').prop('checked', isChecked);
    updateEditOrdSelectionCount();
  });

  // Individual checkbox event
  $(document).on('change', '.edit-ord-tray-chk', function() {
    updateEditOrdSelectionCount();
  });

  // Row click toggles checkbox (ignores clicks on buttons, inputs, links)
  $(document).on('click', '#editOrdTraysTbody tr', function(e) {
    if ($(e.target).closest('button, input, a').length) return;
    const $chk = $(this).find('.edit-ord-tray-chk');
    $chk.prop('checked', !$chk.is(':checked')).trigger('change');
  });

  // Select/Deselect All button
  $(document).on('click', '#btnEditOrdSelectAll', function() {
    const totalVisible = $('.edit-ord-tray-chk').length;
    const selectedCount = $('.edit-ord-tray-chk:checked').length;
    const checkAll = (selectedCount < totalVisible);
    $('.edit-ord-tray-chk').prop('checked', checkAll);
    $('#chkEditOrdHeader').prop('checked', checkAll);
    updateEditOrdSelectionCount();
  });

  // Single Tray Removal Handler
  window.removeSingleOccupiedTray = function(plantId, barcode) {
    const locId = $('#editOrdLocationId').val();
    const phId = $('#editOrdPolyhouseId').val();
    const bedNo = editOrdBedNo;
    const soNo = editOrdStockOrderNo;
    const psoNo = editOrdPsoNo;
    const prodId = editOrdProductId;
    const plants = editOrdCavity;

    const executeRemoval = function(removalRemark) {
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: 'Removing Tray...',
          allowOutsideClick: false,
          didOpen: () => { Swal.showLoading(); }
        });
      }

      $.post(getModalUrl('removeselectedtrays'), {
        projectid,
        locationid: locId,
        polyhouseid: phId,
        bed_no: bedNo,
        stockorder_no: soNo,
        psono: psoNo,
        productid: prodId,
        plant_ids: [plantId],
        removal_remark: removalRemark || ''
      }, function(res) {
        if (res.status === 'success') {
          editOrdAnyRemoved = true;
          // Remove from local list
          editOrdCurrentTraysList = editOrdCurrentTraysList.filter(t => t.polyhouseplantid !== plantId);
          const count = editOrdCurrentTraysList.length;
          const totalPlants = count * editOrdCavity;

          $('#editOrdCurTraysDisp').text(count);
          $('#editOrdCurPlantsDisp').text(totalPlants.toLocaleString());
          $('#editOrdTrayCountBadge').text(`${count} Active Trays`);

          renderEditOrdTraysTable();

          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'success',
              title: 'Tray Removed',
              text: res.message || 'Tray removed successfully!',
              timer: 2000
            });
          }
        } else {
          if (typeof Swal !== 'undefined') {
            Swal.fire('Error', res.message || 'Failed to remove tray.', 'error');
          } else {
            alert(res.message || 'Failed to remove tray.');
          }
        }
      }, 'json').fail(function() {
        if (typeof Swal !== 'undefined') {
          Swal.fire('Error', 'Server or network error while removing tray.', 'error');
        } else {
          alert('Server error.');
        }
      });
    };

    if (typeof Swal !== 'undefined') {
      Swal.fire({
        title: 'Remove Tray?',
        html: `
          <div class="text-left mb-2">
            Are you sure you want to remove tray <strong class="text-primary font-monospace">${esc(barcode)}</strong> (${plants} Plants) from <strong>${esc(bedNo)}</strong>?
          </div>
          <div class="alert alert-danger py-2 px-3 small text-left mb-2">
            <i class="fa fa-exclamation-triangle mr-1"></i> This will mark this tray as Removed and deduct ${plants} plants from the production stock order.
          </div>
          <div class="form-group text-left mb-0">
            <label class="font-weight-bold small text-muted mb-1">Reason / Remark (Optional):</label>
            <input type="text" id="swalSingleTrayRemark" class="form-control form-control-sm" placeholder="e.g. Damaged tray, Dead, Infection...">
          </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fa fa-trash-alt mr-1"></i> Yes, Remove Tray',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
          return document.getElementById('swalSingleTrayRemark')?.value?.trim() || '';
        }
      }).then((result) => {
        if (result.isConfirmed) {
          executeRemoval(result.value || '');
        }
      });
    } else {
      const pRemark = prompt(`Remove tray ${barcode} (${plants} plants) from ${bedNo}? Enter reason (or click OK):`, "");
      if (pRemark !== null) {
        executeRemoval(pRemark);
      }
    }
  };

  // Bulk Selected Trays Removal Handler
  $(document).on('click', '#btnEditOrdRemoveSelected', function() {
    const selectedCheckboxes = $('.edit-ord-tray-chk:checked');
    const plantIds = [];
    selectedCheckboxes.each(function() {
      const id = parseInt($(this).val(), 10);
      if (id > 0) plantIds.push(id);
    });

    if (!plantIds.length) {
      if (typeof Swal !== 'undefined') Swal.fire('Notice', 'Please select at least one tray.', 'warning');
      else alert('Please select at least one tray.');
      return;
    }

    const locId = $('#editOrdLocationId').val();
    const phId = $('#editOrdPolyhouseId').val();
    const bedNo = editOrdBedNo;
    const soNo = editOrdStockOrderNo;
    const psoNo = editOrdPsoNo;
    const prodId = editOrdProductId;
    const count = plantIds.length;
    const plants = count * editOrdCavity;

    const executeBulkRemoval = function(removalRemark) {
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: `Removing ${count} Trays...`,
          allowOutsideClick: false,
          didOpen: () => { Swal.showLoading(); }
        });
      }

      $.post(getModalUrl('removeselectedtrays'), {
        projectid,
        locationid: locId,
        polyhouseid: phId,
        bed_no: bedNo,
        stockorder_no: soNo,
        psono: psoNo,
        productid: prodId,
        plant_ids: plantIds,
        removal_remark: removalRemark || ''
      }, function(res) {
        if (res.status === 'success') {
          editOrdAnyRemoved = true;
          const removedSet = new Set(res.removed_ids || plantIds);
          editOrdCurrentTraysList = editOrdCurrentTraysList.filter(t => !removedSet.has(t.polyhouseplantid));
          const remCount = editOrdCurrentTraysList.length;
          const totalPlants = remCount * editOrdCavity;

          $('#editOrdCurTraysDisp').text(remCount);
          $('#editOrdCurPlantsDisp').text(totalPlants.toLocaleString());
          $('#editOrdTrayCountBadge').text(`${remCount} Active Trays`);

          renderEditOrdTraysTable();

          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'success',
              title: `${count} Trays Removed`,
              text: res.message || 'Trays removed successfully!',
              timer: 2000
            });
          }
        } else {
          if (typeof Swal !== 'undefined') {
            Swal.fire('Error', res.message || 'Failed to remove trays.', 'error');
          } else {
            alert(res.message || 'Failed to remove trays.');
          }
        }
      }, 'json').fail(function() {
        if (typeof Swal !== 'undefined') {
          Swal.fire('Error', 'Server or network error while removing trays.', 'error');
        } else {
          alert('Server error.');
        }
      });
    };

    if (typeof Swal !== 'undefined') {
      Swal.fire({
        title: `Remove ${count} Selected Trays?`,
        html: `
          <div class="text-left mb-2">
            Are you sure you want to remove <strong>${count} trays</strong> (${plants.toLocaleString()} Plants) from <strong>${esc(bedNo)}</strong>?
          </div>
          <div class="alert alert-danger py-2 px-3 small text-left mb-2">
            <i class="fa fa-exclamation-triangle mr-1"></i> This will mark these ${count} trays as Removed and deduct ${plants.toLocaleString()} plants from the production stock order.
          </div>
          <div class="form-group text-left mb-0">
            <label class="font-weight-bold small text-muted mb-1">Reason / Remark (Optional):</label>
            <input type="text" id="swalBulkTraysRemark" class="form-control form-control-sm" placeholder="e.g. Germination failure, Over-aged, Damaged...">
          </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: `<i class="fa fa-trash-alt mr-1"></i> Yes, Remove ${count} Trays`,
        cancelButtonText: 'Cancel',
        preConfirm: () => {
          return document.getElementById('swalBulkTraysRemark')?.value?.trim() || '';
        }
      }).then((result) => {
        if (result.isConfirmed) {
          executeBulkRemoval(result.value || '');
        }
      });
    } else {
      const bRemark = prompt(`Remove ${count} selected trays (${plants} plants) from ${bedNo}? Enter reason (or click OK):`, "");
      if (bRemark !== null) {
        executeBulkRemoval(bRemark);
      }
    }
  });

  // Toggle Add Tray Panel
  $(document).on('click', '#btnEditOrdToggleAddTrays', function() {
    const $card = $('#editOrdAddTrayCard');
    if ($card.is(':visible')) {
      $card.slideUp(200);
    } else {
      const curCount = parseInt($('#editOrdNewTrayCount').val(), 10) || 1;
      $('#editOrdNewPlantsCalc').val((curCount * editOrdCavity).toLocaleString() + ' Plants');
      $card.slideDown(200, function() {
        $('#editOrdNewTrayCount').focus().select();
      });
    }
  });

  // Close / Cancel Add Tray Panel
  $(document).on('click', '#btnEditOrdCloseAddTrayCard, #btnEditOrdCancelAddTrays', function() {
    $('#editOrdAddTrayCard').slideUp(200);
  });

  // Calculate Plants dynamically on tray count change
  $(document).on('input change', '#editOrdNewTrayCount', function() {
    let val = parseInt($(this).val(), 10);
    if (isNaN(val) || val < 1) val = 1;
    $('#editOrdNewPlantsCalc').val((val * editOrdCavity).toLocaleString() + ' Plants');
  });

  // Submit Save & Add Trays Handler
  $(document).on('click', '#btnEditOrdSubmitAddTrays', function() {
    const numTrays = parseInt($('#editOrdNewTrayCount').val(), 10);
    if (!numTrays || numTrays < 1) {
      if (typeof Swal !== 'undefined') Swal.fire('Invalid Quantity', 'Please enter a valid number of trays (at least 1).', 'warning');
      else alert('Please enter a valid number of trays (at least 1).');
      return;
    }

    const locId = $('#editOrdLocationId').val();
    const phId = $('#editOrdPolyhouseId').val();
    const bedNo = editOrdBedNo;
    const soNo = editOrdStockOrderNo;
    const psoNo = editOrdPsoNo;
    const prodId = editOrdProductId;
    const trayId = editOrdTrayIdVal;
    const trayStatus = $('#editOrdNewTrayStatus').val();
    const healthStatus = $('#editOrdNewHealthStatus').val();

    const $btn = $(this);
    const origHtml = $btn.html();
    $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i> Saving...');

    $.post(getModalUrl('addtrays'), {
      projectid,
      locationid: locId,
      polyhouseid: phId,
      bed_no: bedNo,
      stockorder_no: soNo,
      psono: psoNo,
      productid: prodId,
      tray_id: trayId,
      num_trays: numTrays,
      tray_status: trayStatus,
      health_status: healthStatus
    }, function(res) {
      $btn.prop('disabled', false).html(origHtml);
      if (res.status === 'success') {
        editOrdAnyRemoved = true; // Signals report reload when modal closes
        if (res.new_trays && res.new_trays.length) {
          // Add new trays to front of table so user immediately sees their new QR barcodes
          editOrdCurrentTraysList = res.new_trays.concat(editOrdCurrentTraysList);
        }
        const totalTrays = res.total_trays || editOrdCurrentTraysList.length;
        const totalPlants = res.total_plants || (totalTrays * editOrdCavity);

        $('#editOrdCurTraysDisp').text(totalTrays);
        $('#editOrdCurPlantsDisp').text(totalPlants.toLocaleString());
        $('#editOrdTrayCountBadge').text(`${totalTrays} Active Trays`);

        $('#editOrdAddTrayCard').slideUp(200);
        $('#editOrdNewTrayCount').val(1);
        $('#editOrdNewPlantsCalc').val((1 * editOrdCavity).toLocaleString() + ' Plants');

        renderEditOrdTraysTable();

        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'success',
            title: 'Trays Added!',
            text: res.message || `${numTrays} tray(s) added successfully!`,
            timer: 2000
          });
        }
      } else {
        if (typeof Swal !== 'undefined') {
          Swal.fire('Error', res.message || 'Failed to add trays.', 'error');
        } else {
          alert(res.message || 'Failed to add trays.');
        }
      }
    }, 'json').fail(function() {
      $btn.prop('disabled', false).html(origHtml);
      if (typeof Swal !== 'undefined') {
        Swal.fire('Error', 'Server or network error while adding trays.', 'error');
      } else {
        alert('Server error.');
      }
    });
  });

  // Modal hidden event - refresh report if any trays were removed or added
  $('#modalEditOrderQty').on('hidden.bs.modal hidden', function() {
    if (editOrdAnyRemoved) {
      editOrdAnyRemoved = false;
      loadReport();
    }
  });

  // Confirm and Remove Order from Bed
  window.confirmRemoveOrderFromBed = function(bedNo, stockOrderNo, psoNo, productId, trayId, productName, currentTrays, specificPhId, clientName) {
    const locId = $('#phLocation').val();
    const phId = specificPhId || $('#phPolyhouse').val();
    const curTrays = parseInt(currentTrays, 10) || 0;

    const doRemove = function(removalRemark) {
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: 'Removing...',
          text: `Removing ${stockOrderNo} from ${bedNo}...`,
          allowOutsideClick: false,
          didOpen: () => { Swal.showLoading(); }
        });
      }

      $.post(getModalUrl('removeorderfrombed'), {
        projectid,
        locationid: locId,
        polyhouseid: phId,
        bed_no: bedNo,
        stockorder_no: stockOrderNo,
        psono: psoNo,
        productid: productId,
        tray_id: trayId,
        removal_remark: removalRemark || ''
      }, function(res) {
        if (res.status === 'success') {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'success',
              title: 'Order Removed',
              text: res.message || 'Removed successfully and Production Stock Order updated!',
              timer: 2500
            });
          }
          loadReport();
        } else {
          if (typeof Swal !== 'undefined') {
            Swal.fire('Error', res.message || 'Failed to remove order.', 'error');
          } else {
            alert(res.message || 'Failed to remove order.');
          }
        }
      }, 'json').fail(function() {
        if (typeof Swal !== 'undefined') {
          Swal.fire('Error', 'Server or network error while removing order.', 'error');
        } else {
          alert('Server error.');
        }
      });
    };

    if (typeof Swal !== 'undefined') {
      Swal.fire({
        title: 'Remove Order from Bed?',
        html: `
          <div class="text-left text-dark mb-2" style="font-size:0.92rem; line-height:1.5;">
            Are you sure you want to remove <strong class="text-danger font-monospace">${esc(stockOrderNo)}</strong> 
            (<em>${esc(productName || 'Plant')}</em>${clientName ? ` &bull; <i class="fa fa-user text-primary mr-1"></i>${esc(clientName)}` : ''}) from <strong>${esc(bedNo)}</strong>?
          </div>
          <div class="alert alert-danger py-2 px-3 small text-left mb-3" style="font-size:0.82rem; border-left:4px solid #dc2626;">
            <i class="fa fa-exclamation-triangle mr-1"></i> This will mark all <strong>${curTrays} tray(s)</strong> as <strong>Removed</strong> in connected tables and update production stock order.
          </div>
          <div class="form-group text-left mb-0">
            <label class="font-weight-bold small text-muted mb-1"><i class="fa fa-comment-alt mr-1"></i> Removal Reason / Remark (Optional):</label>
            <textarea id="swalRemoveOrderRemark" class="form-control form-control-sm" rows="2" placeholder="e.g. Germination failure, Damaged trays, Wrong Bed Entry, Over-aged, Order Cancelled..."></textarea>
          </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa fa-trash-alt mr-1"></i> Yes, Remove Order',
        cancelButtonText: 'Cancel',
        focusCancel: true,
        preConfirm: () => {
          return document.getElementById('swalRemoveOrderRemark')?.value?.trim() || '';
        }
      }).then((result) => {
        if (result.isConfirmed) {
          const userRemark = (typeof result.value === 'string') ? result.value : '';
          doRemove(userRemark);
        }
      });
    } else {
      const promptRemark = prompt(`Are you sure you want to remove ${stockOrderNo} (${curTrays} trays) from ${bedNo}? Enter removal reason (or click OK):`, "");
      if (promptRemark !== null) {
        doRemove(promptRemark);
      }
    }
  };

  // =========================================================
  // UPDATE TRAY STATUS MODAL LOGIC
  // =========================================================
  window.openTrayStatusModal = function(bedNo, stockOrderNo, psoNo, productId, trayId, productName, currentTrays, bedCap, trayName, specificPhId, specificPhName, clientName) {
    const locId = $('#phLocation').val();
    const phId = specificPhId || $('#phPolyhouse').val();
    const phName = specificPhName || reportData?.polyhouse?.polyhousename || '';
    const curTrays = parseInt(currentTrays, 10) || 0;

    let cavity = 209;
    if (trayName) {
      const match = trayName.match(/(\d+)\s*(?:cavity|cav)/i) || trayName.match(/(\d+)/);
      if (match) cavity = parseInt(match[1], 10) || 209;
    }

    $('#statusOrdProjectId').val(projectid);
    $('#statusOrdLocationId').val(locId);
    $('#statusOrdPolyhouseId').val(phId);
    $('#statusOrdBedNo').val(bedNo);
    $('#statusOrdStockOrderNo').val(stockOrderNo);
    $('#statusOrdPsoNo').val(psoNo);
    $('#statusOrdProductId').val(productId);
    $('#statusOrdTrayId').val(trayId);
    $('#statusOrdTotalTrays').val(curTrays);
    $('#statusOrdQtyPerTray').val(cavity);

    $('#statusOrdBadgeSo').text(stockOrderNo || 'N/A');
    $('#statusOrdBadgePso').text(psoNo ? `/ ${psoNo}` : '');
    $('#statusOrdBadgeBed').text(`${bedNo} (${phName})`);
    $('#statusOrdProductName').text(productName || 'Plant');
    $('#statusOrdClientName').text(clientName || 'N/A');
    $('#statusOrdTotalDisp').text(curTrays);
    $('#statusOrdPolyhouseName').text(phName || 'Polyhouse');
    $('#statusHealthTotalNote').text(`${curTrays} Trays`);

    // Default pre-fill
    $('input[name="shiftingstatus"][value="nonshifted"]').prop('checked', true);
    $('#pillNonShifted').addClass('active-ready');
    $('#pillShifted').removeClass('active-ready');
    $('#statusShiftBadge').text('Non-Shifted').css({ background: '#fffbeb', color: '#92400e' });
    $('#germinationFailSection').show();
    $('#statusFailPercentInput').val(0);

    $('#statusHealthyTraysInput').val(curTrays);
    $('#statusUnhealthyTraysInput').val(0);

    // Fetch existing saved status via AJAX
    $.post(getModalUrl('gettraystatus'), {
      projectid,
      locationid: locId,
      polyhouseid: phId,
      bed_no: bedNo,
      stockorder_no: stockOrderNo,
      psono: psoNo,
      productid: productId
    }, function(res) {
      if (res && res.status === 'success' && res.data) {
        const d = res.data;
        const total = d.total_trays > 0 ? d.total_trays : curTrays;
        $('#statusOrdTotalTrays').val(total);
        $('#statusOrdTotalDisp').text(total);
        $('#statusHealthTotalNote').text(`${total} Trays`);

        if (d.shiftingstatus === 'shifted') {
          $('input[name="shiftingstatus"][value="shifted"]').prop('checked', true);
          $('#pillShifted').addClass('active-ready');
          $('#pillNonShifted').removeClass('active-ready');
          $('#statusShiftBadge').text('Shifted').css({ background: '#dcfce7', color: '#166534' });
          $('#germinationFailSection').hide();
        } else {
          $('input[name="shiftingstatus"][value="nonshifted"]').prop('checked', true);
          $('#pillNonShifted').addClass('active-ready');
          $('#pillShifted').removeClass('active-ready');
          $('#statusShiftBadge').text('Non-Shifted').css({ background: '#fffbeb', color: '#92400e' });
          $('#germinationFailSection').show();
        }

        $('#statusFailPercentInput').val(d.failerpercent || 0);

        const unhealth = d.unhealthy_trays || 0;
        const health = (d.healthy_trays !== undefined && d.healthy_trays > 0) ? d.healthy_trays : Math.max(0, total - unhealth);
        $('#statusHealthyTraysInput').val(health);
        $('#statusUnhealthyTraysInput').val(unhealth);
        updateHealthCalculation();
      }
    }, 'json');

    updateHealthCalculation();
    $('#modalUpdateTrayStatus').modal('show');
  };

  // Toggle Shifting Status radio pills
  $('input[name="shiftingstatus"]').on('change', function() {
    const val = $(this).val();
    if (val === 'shifted') {
      $('#pillShifted').addClass('active-ready');
      $('#pillNonShifted').removeClass('active-ready');
      $('#statusShiftBadge').text('Shifted').css({ background: '#dcfce7', color: '#166534' });
      $('#germinationFailSection').slideUp(200);
    } else {
      $('#pillNonShifted').addClass('active-ready');
      $('#pillShifted').removeClass('active-ready');
      $('#statusShiftBadge').text('Non-Shifted').css({ background: '#fffbeb', color: '#92400e' });
      $('#germinationFailSection').slideDown(200);
    }
  });

  // Quick Fail % badges click
  $(document).on('click', '.quick-fail-pct', function() {
    const pct = $(this).data('pct');
    $('#statusFailPercentInput').val(pct);
  });

  // Health count live synchronization
  function updateHealthCalculation(source) {
    const total = parseInt($('#statusOrdTotalTrays').val(), 10) || 0;
    let healthy = parseInt($('#statusHealthyTraysInput').val(), 10);
    let unhealthy = parseInt($('#statusUnhealthyTraysInput').val(), 10);

    if (isNaN(healthy)) healthy = 0;
    if (isNaN(unhealthy)) unhealthy = 0;

    if (source === 'healthy') {
      if (healthy > total) healthy = total;
      unhealthy = Math.max(0, total - healthy);
      $('#statusUnhealthyTraysInput').val(unhealthy);
    } else if (source === 'unhealthy') {
      if (unhealthy > total) unhealthy = total;
      healthy = Math.max(0, total - unhealthy);
      $('#statusHealthyTraysInput').val(healthy);
    }

    const badge = $('#statusHealthBalanceBadge');
    if (healthy + unhealthy === total) {
      badge.css({ background: '#dcfce7', color: '#166534' }).text('Count Matched');
    } else {
      badge.css({ background: '#fee2e2', color: '#991b1b' }).text(`Diff: ${(healthy + unhealthy) - total}`);
    }
  }

  $('#statusHealthyTraysInput').on('input', function() { updateHealthCalculation('healthy'); });
  $('#statusUnhealthyTraysInput').on('input', function() { updateHealthCalculation('unhealthy'); });

  // Form Submit for Update Tray Status
  $('#formUpdateTrayStatus').on('submit', function(e) {
    e.preventDefault();
    const btn = $('#btnSubmitTrayStatus');
    const origHtml = btn.html();

    const shiftingstatus = $('input[name="shiftingstatus"]:checked').val() || 'nonshifted';
    const failerpercent = (shiftingstatus === 'nonshifted') ? (parseFloat($('#statusFailPercentInput').val()) || 0) : 0;
    const healthy_trays = parseInt($('#statusHealthyTraysInput').val(), 10) || 0;
    const unhealthy_trays = parseInt($('#statusUnhealthyTraysInput').val(), 10) || 0;
    const total = parseInt($('#statusOrdTotalTrays').val(), 10) || 0;

    if (healthy_trays < 0 || unhealthy_trays < 0) {
      if (typeof Swal !== 'undefined') {
        Swal.fire('Notice', 'Tray counts cannot be negative.', 'warning');
      } else {
        alert('Tray counts cannot be negative.');
      }
      return;
    }

    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i> Saving...');

    const formData = {
      projectid: $('#statusOrdProjectId').val(),
      locationid: $('#statusOrdLocationId').val(),
      polyhouseid: $('#statusOrdPolyhouseId').val(),
      bed_no: $('#statusOrdBedNo').val(),
      stockorder_no: $('#statusOrdStockOrderNo').val(),
      psono: $('#statusOrdPsoNo').val(),
      productid: $('#statusOrdProductId').val(),
      tray_id: $('#statusOrdTrayId').val(),
      shiftingstatus: shiftingstatus,
      failerpercent: failerpercent,
      healthy_trays: healthy_trays,
      unhealthy_trays: unhealthy_trays
    };

    $.post(getModalUrl('updatetraystatus'), formData, function(res) {
      btn.prop('disabled', false).html(origHtml);
      if (res.status === 'success') {
        $('#modalUpdateTrayStatus').modal('hide');
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'success',
            title: 'Tray Status Updated',
            text: res.message || 'Status updated successfully!',
            timer: 2500
          });
        }
        loadReport();
      } else {
        if (typeof Swal !== 'undefined') {
          Swal.fire('Error', res.message || 'Failed to update tray status.', 'error');
        } else {
          alert(res.message || 'Failed to update tray status.');
        }
      }
    }, 'json').fail(function() {
      btn.prop('disabled', false).html(origHtml);
      if (typeof Swal !== 'undefined') {
        Swal.fire('Error', 'Server error while updating status.', 'error');
      } else {
        alert('Server error.');
      }
    });
  });

  // =========================================================
  // STATUS BREAKDOWN DETAILS MODAL LOGIC (READ-ONLY)
  // =========================================================
  let currentStatusBreakdownTrays = [];
  let currentIsMovementStatus = false;

  window.openStatusBreakdownModal = function(bedNo, stockOrderNo, psoNo, productId, productName, statusKey, count, specificPhId, specificPhName, clientName) {
    const locId = $('#phLocation').val();
    const phId = specificPhId || $('#phPolyhouse').val();
    const phName = specificPhName || reportData?.polyhouse?.polyhousename || '';

    $('#statusModalBadgeSo').text(stockOrderNo || 'N/A');
    $('#statusModalBadgePso').text(psoNo ? `/ ${psoNo}` : '');
    $('#statusModalProductName').text(productName || 'Plant');
    $('#statusModalClientName').text(clientName || '-');
    $('#statusModalBedName').text(`${bedNo} (${phName})`);

    const $stBadge = $('#statusModalStatusBadge');
    $stBadge.text(statusKey);
    $stBadge.attr('class', 'badge px-3 py-1 font-weight-bold');
    if (/Ready to Sale/i.test(statusKey)) {
      $stBadge.css({ background: '#ecfdf5', color: '#065f46', border: '1px solid #a7f3d0' });
    } else if (/Final Dispatch/i.test(statusKey)) {
      $stBadge.css({ background: '#eff6ff', color: '#1e40af', border: '1px solid #bfdbfe' });
    } else if (/Loaded|Loading/i.test(statusKey)) {
      $stBadge.css({ background: '#ede9fe', color: '#5b21b6', border: '1px solid #ddd6fe' });
    } else if (/Internal Dispatch/i.test(statusKey)) {
      $stBadge.css({ background: '#fffbeb', color: '#92400e', border: '1px solid #fde68a' });
    } else if (/Move for Opensale/i.test(statusKey)) {
      $stBadge.css({ background: '#fdf2f8', color: '#9d174d', border: '1px solid #fbcfe8' });
    } else if (/Move for Grafting|Grafting/i.test(statusKey)) {
      $stBadge.css({ background: '#fff1f2', color: '#9f1239', border: '1px solid #fecdd3' });
    } else if (/Empty After/i.test(statusKey)) {
      $stBadge.css({ background: '#f1f5f9', color: '#334155', border: '1px solid #94a3b8' });
    } else {
      $stBadge.css({ background: '#f8fafc', color: '#334155', border: '1px solid #cbd5e1' });
    }

    currentIsMovementStatus = !/^(Ready to Sale|ReadytoSale|Polyhouse|Open Stock|OpenStock)$/i.test(String(statusKey || '').trim());
    const colCount = currentIsMovementStatus ? 9 : 6;

    // Dynamically generate table header:
    // Option 1: Clean structured columns for movement statuses
    const theadHtml = `
      <tr>
        <th style="width: 45px; text-align: center;">#</th>
        <th>Tray Barcode</th>
        <th>Health</th>
        <th>Shifting</th>
        <th style="text-align: right;">Plants / Cavity</th>
        <th>${currentIsMovementStatus ? 'Moved Date & Time' : 'Date'}</th>
        ${currentIsMovementStatus ? `
          <th>Moved By</th>
          <th style="text-align: center;">Batch Qty</th>
          <th>Movement Remark / Note</th>
        ` : ''}
      </tr>
    `;
    $('#statusModalTableHead').html(theadHtml);

    if (currentIsMovementStatus) {
      $('#statusModalFilterInput').attr('placeholder', 'Search barcode, moved by, note...');
    } else {
      $('#statusModalFilterInput').attr('placeholder', 'Search barcode, date...');
    }

    $('#statusModalSummaryCounts').html(`Loading tray records for <strong>${esc(statusKey)}</strong>...`);
    $('#statusModalTableBody').html(`
      <tr>
        <td colspan="${colCount}" class="text-center py-4 text-muted">
          <i class="fa fa-spinner fa-spin fa-2x text-primary mb-2"></i>
          <div>Loading individual tray records...</div>
        </td>
      </tr>
    `);
    $('#statusModalFilterInput').val('');
    $('#statusModalFooterCount').text(`Expected Trays: ${count}`);

    $('#modalStatusBreakdownDetails').modal('show');

    $.post(getModalUrl('getstatusbreakdowndetails'), {
      projectid,
      locationid: locId,
      polyhouseid: phId,
      bed_no: bedNo,
      stockorder_no: stockOrderNo,
      psono: psoNo,
      productid: productId,
      status_key: statusKey
    }, function(res) {
      if (res && res.status === 'success') {
        currentStatusBreakdownTrays = res.trays || [];
        $('#statusModalSummaryCounts').html(`Total Trays: <strong>${res.total_trays}</strong> • Estimated Plants: <strong>${(res.total_plants || 0).toLocaleString()}</strong>`);
        $('#statusModalFooterCount').text(`Showing ${res.total_trays} tray(s)`);
        renderStatusBreakdownTable(currentStatusBreakdownTrays);
      } else {
        $('#statusModalTableBody').html(`
          <tr>
            <td colspan="${colCount}" class="text-center py-4 text-danger">
              <i class="fa fa-exclamation-triangle fa-2x mb-2"></i>
              <div>${esc(res?.message || 'Failed to load status details.')}</div>
            </td>
          </tr>
        `);
      }
    }, 'json').fail(function() {
      $('#statusModalTableBody').html(`
        <tr>
          <td colspan="${colCount}" class="text-center py-4 text-danger">
            <i class="fa fa-exclamation-triangle fa-2x mb-2"></i>
            <div>Server error while loading tray details.</div>
          </td>
        </tr>
      `);
    });
  };

  function renderStatusBreakdownTable(trays) {
    const colCount = currentIsMovementStatus ? 9 : 6;
    if (!trays || trays.length === 0) {
      $('#statusModalTableBody').html(`
        <tr>
          <td colspan="${colCount}" class="text-center py-4 text-muted">
            <i class="fa fa-inbox fa-2x mb-2 text-secondary"></i>
            <div>No matching tray records found for this status.</div>
          </td>
        </tr>
      `);
      return;
    }

    let rowsHtml = '';
    trays.forEach((t, i) => {
      const isHealthFail = /Germination Failure|Unhealthy|Weak|Diseased|Dead|Infested/i.test(t.health_status);
      const healthBadge = isHealthFail 
        ? `<span class="badge px-2 py-0.5 border" style="background:#fee2e2;color:#991b1b;border-color:#fca5a5!important;"><i class="fa fa-exclamation-triangle mr-1"></i>${esc(t.health_status)}</span>`
        : `<span class="badge px-2 py-0.5 border" style="background:#dcfce7;color:#166534;border-color:#86efac!important;"><i class="fa fa-heart mr-1"></i>Healthy</span>`;

      const shiftBadge = (t.shiftingstatus === 'shifted')
        ? `<span class="badge text-white px-2 py-0.5" style="background:#059669!important;"><i class="fa fa-check mr-1"></i>Shifted</span>`
        : `<span class="badge text-dark px-2 py-0.5" style="background:#fef3c7!important;color:#92400e!important;border:1px solid #fde68a;"><i class="fa fa-clock mr-1"></i>Non-Shifted</span>`;

      let dateTimeHtml = '';
      if (currentIsMovementStatus && t.moved_datetime) {
        const dtParts = String(t.moved_datetime).trim().split(/\s+/);
        if (dtParts.length >= 3) {
          dateTimeHtml = `
            <div class="fw-bold text-dark" style="font-size: 0.82rem;">${esc(dtParts[0])}</div>
            <div class="text-muted" style="font-size: 0.74rem;"><i class="fa fa-clock mr-1 text-secondary"></i>${esc(dtParts.slice(1).join(' '))}</div>
          `;
        } else {
          dateTimeHtml = `<div class="fw-bold text-dark" style="font-size: 0.82rem;">${fmtDate(t.moved_datetime)}</div>`;
        }
      } else {
        dateTimeHtml = `<span class="text-muted">${fmtDate(t.move_at || t.dispatchat || t.shiftingat || t.updated_at)}</span>`;
      }

      rowsHtml += `
        <tr>
          <td style="text-align: center; color: #94a3b8; font-weight: 600;">${i + 1}</td>
          <td>
            <span class="font-monospace fw-bold text-primary" style="letter-spacing: 0.5px;">${esc(t.barcode)}</span>
          </td>
          <td>${healthBadge}</td>
          <td>${shiftBadge}</td>
          <td style="text-align: right; font-weight: 700; color: #0f172a;">${t.qtypertray}</td>
          <td>${dateTimeHtml}</td>
          ${currentIsMovementStatus ? `
            <td style="white-space: nowrap;">
              <i class="fa fa-user-circle text-primary mr-1"></i><strong>${esc(t.dispatch_by || '-')}</strong>
            </td>
            <td style="text-align: center; white-space: nowrap;">
              ${t.batch_trays ? `
                <span class="badge px-2 py-1 border" style="background: #f8fafc; color: #334155; border-color: #cbd5e1 !important; font-size: 0.75rem; font-weight: 600;">
                  <i class="fa fa-layer-group text-primary mr-1"></i>${esc(t.batch_trays)}
                </span>
              ` : '<span class="text-muted">-</span>'}
            </td>
            <td class="small" style="max-width: 220px;">
              <span class="text-dark d-inline-block text-truncate fw-semibold" style="max-width: 215px;" title="${esc(t.full_movementremark || t.remark || '')}">
                <i class="fa fa-comment-alt text-muted mr-1"></i>${esc(t.remark || '-')}
              </span>
            </td>
          ` : ''}
        </tr>
      `;
    });
    $('#statusModalTableBody').html(rowsHtml);
  }

  // Live filter inside the modal
  $('#statusModalFilterInput').on('input', function() {
    const q = $(this).val().toLowerCase().trim();
    if (!q) {
      renderStatusBreakdownTable(currentStatusBreakdownTrays);
      $('#statusModalFooterCount').text(`Showing ${currentStatusBreakdownTrays.length} tray(s)`);
      return;
    }
    const filtered = currentStatusBreakdownTrays.filter(t => {
      let match = String(t.barcode || '').toLowerCase().includes(q) ||
                  String(t.health_status || '').toLowerCase().includes(q) ||
                  String(t.shiftingstatus || '').toLowerCase().includes(q) ||
                  String(t.moved_datetime || t.move_at || '').toLowerCase().includes(q);
      if (currentIsMovementStatus) {
        match = match ||
                String(t.dispatch_by || '').toLowerCase().includes(q) ||
                String(t.batch_trays || '').toLowerCase().includes(q) ||
                String(t.remark || '').toLowerCase().includes(q) ||
                String(t.full_movementremark || '').toLowerCase().includes(q);
      }
      return match;
    });
    renderStatusBreakdownTable(filtered);
    $('#statusModalFooterCount').text(`Showing ${filtered.length} of ${currentStatusBreakdownTrays.length} tray(s)`);
  });

  // =========================================================
  // DUPLICATE RSO FINDER & RESOLVER LOGIC
  // =========================================================
  let duplicateRsoData = { product_conflicts: [], bed_conflicts: [] };

  function checkDuplicateRsoCount() {
    $.post(getModalUrl('get_duplicate_rsos'), { projectid }, function(res) {
      if (res.status === 'success') {
        duplicateRsoData = res;
        const prodCount = (res.product_conflicts || []).length;
        const bedCount = (res.bed_conflicts || []).length;
        const total = prodCount + bedCount;
        
        if (total > 0) {
          $('#badgeDupRsoCount').text(total).show();
        } else {
          $('#badgeDupRsoCount').text('0').hide();
        }
        $('#tabDupProdBadge').text(prodCount);
        $('#tabDupBedBadge').text(bedCount);
      }
    }, 'json');
  }

  // Load count on init
  checkDuplicateRsoCount();

  $('#btnOpenDupRsoFinder').on('click', function() {
    $('#modalDuplicateRsoFinder').modal('show');
    loadDuplicateRsos();
  });

  $('#btnRefreshDupList').on('click', function() {
    loadDuplicateRsos();
  });

  function loadDuplicateRsos() {
    $('#dupProdListContainer').html(`
      <div class="text-center py-5 text-muted">
        <i class="fa fa-spinner fa-spin fa-2x mb-2 text-warning"></i>
        <div>Scanning for duplicate RSO numbers...</div>
      </div>
    `);
    $('#dupBedListContainer').html(`
      <div class="text-center py-5 text-muted">
        <i class="fa fa-spinner fa-spin fa-2x mb-2 text-warning"></i>
        <div>Loading multi-bed splits...</div>
      </div>
    `);

    $.post(getModalUrl('get_duplicate_rsos'), { projectid }, function(res) {
      if (res.status === 'success') {
        duplicateRsoData = res;
        const prodCount = (res.product_conflicts || []).length;
        const bedCount = (res.bed_conflicts || []).length;
        const total = prodCount + bedCount;
        
        if (total > 0) {
          $('#badgeDupRsoCount').text(total).show();
        } else {
          $('#badgeDupRsoCount').text('0').hide();
        }
        $('#tabDupProdBadge').text(prodCount);
        $('#tabDupBedBadge').text(bedCount);

        renderProductConflicts(res.product_conflicts || []);
        renderBedConflicts(res.bed_conflicts || []);
        $('#dupModalFooterCount').text(`Found ${prodCount} product conflict(s) and ${bedCount} multi-bed split(s).`);
      } else {
        $('#dupProdListContainer').html(`<div class="alert alert-danger py-3 px-4">${esc(res.message || 'Failed to scan for duplicate RSOs.')}</div>`);
        $('#dupBedListContainer').html('');
      }
    }, 'json').fail(function() {
      $('#dupProdListContainer').html(`<div class="alert alert-danger py-3 px-4">Server or network error loading duplicates.</div>`);
      $('#dupBedListContainer').html('');
    });
  }

  function renderProductConflicts(list) {
    if (!list || list.length === 0) {
      $('#dupProdListContainer').html(`
        <div class="alert alert-success d-flex align-items-center py-3 px-4" style="border-radius: 10px; background: #ecfdf5; border-color: #a7f3d0; color: #065f46;">
          <i class="fa fa-check-circle fa-2x mr-3 text-success"></i>
          <div>
            <h6 class="font-weight-bold mb-1" style="color: #065f46;">No Product Conflicts Found</h6>
            <p class="mb-0 small" style="color: #047857;">All active RSO stock orders have consistent, single-product associations.</p>
          </div>
        </div>
      `);
      return;
    }

    let html = '';
    list.forEach((item, idx) => {
      html += `
        <div class="card border mb-3 shadow-2xs dup-item-card" data-rso="${esc(item.stockorder_no)}" style="border-radius: 12px; border-color: #fcd34d !important; overflow: hidden;">
          <div class="card-header py-2.5 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2" style="background: #fffbeb; border-bottom: 1px solid #fef3c7;">
            <div class="d-flex align-items-center gap-2">
              <span class="badge font-monospace px-2.5 py-1 text-white font-weight-bold" style="font-size: 0.9rem; background: #d97706;">
                ${esc(item.stockorder_no)}
              </span>
              <span class="badge badge-warning text-dark font-weight-bold" style="background: #fef08a; font-size: 0.78rem;">
                <i class="fa fa-exclamation-triangle mr-1 text-warning"></i> ${item.product_count} Different Products
              </span>
              <span class="text-muted small ml-1">• Total ${item.total_trays} Trays</span>
            </div>
          </div>
          <div class="card-body p-3 bg-white">
            <div class="table-responsive">
              <table class="table table-sm table-bordered mb-0 align-middle" style="font-size: 0.86rem;">
                <thead class="table-light" style="font-size: 0.8rem; text-transform: uppercase;">
                  <tr>
                    <th style="width: 26%;">Product Name</th>
                    <th style="width: 14%;">Bed</th>
                    <th style="width: 13%;">Trays / Plants</th>
                    <th style="width: 17%;">Status</th>
                    <th style="width: 30%;">Fix / Reassign Action</th>
                  </tr>
                </thead>
                <tbody>
      `;

      (item.entries || []).forEach((entry, pIdx) => {
        const isOrig = entry.is_original;
        const suggRso = entry.suggested ? entry.suggested.suggested_rso : '';
        const rowId = `prod_${idx}_${pIdx}`;

        html += `
          <tr class="${isOrig ? 'table-light' : 'table-warning'}" style="${isOrig ? 'background:#fafafa;' : 'background:#fffdf5;'}">
            <td>
              <strong class="text-dark">${esc(entry.productname)}</strong>
              <div class="small text-muted font-monospace" style="font-size:0.75rem;">ID: ${entry.productid} | Cavity: ${entry.qtypertray}</div>
            </td>
            <td>
              ${isOrig ? `
                <span class="badge bg-light text-dark border px-2 py-1" style="font-size:0.8rem; background:#f1f5f9; border-color:#cbd5e1!important;">
                  ${esc(entry.bed_no || 'N/A')}
                </span>
              ` : `
                <span class="badge bg-light text-dark border px-2 py-1 font-weight-bold" style="font-size:0.82rem; background:#fef3c7; border-color:#f59e0b!important; color:#92400e;">
                  <i class="fa fa-th-large mr-1 text-warning"></i>${esc(entry.bed_no || 'N/A')}
                </span>
              `}
            </td>
            <td>
              <strong>${entry.tray_count}</strong> trays
              <div class="small text-muted">${Number(entry.plant_count).toLocaleString()} plants</div>
            </td>
            <td>
              ${isOrig ? `
                <span class="badge badge-success px-2 py-1" style="background:#10b981; color:#fff; font-size:0.78rem;">
                  <i class="fa fa-check mr-1"></i> Original Entry (Keeps RSO)
                </span>
              ` : `
                <span class="badge badge-danger px-2 py-1" style="background:#ef4444; color:#fff; font-size:0.78rem;">
                  <i class="fa fa-times-circle mr-1"></i> Conflicting Addition
                </span>
              `}
            </td>
            <td>
              ${isOrig ? `
                <span class="text-muted small"><i class="fa fa-shield-alt text-success mr-1"></i> Preserved as primary order</span>
              ` : `
                <div class="d-flex align-items-center gap-1.5 flex-wrap">
                  <div style="flex: 1; min-width: 140px;">
                    <input type="text" class="form-control form-control-sm font-monospace input-resolve-rso" 
                           id="inputNewRso_${rowId}" 
                           value="${esc(suggRso)}" 
                           placeholder="e.g. RSO-NEW..." 
                           data-status-id="statusNewRso_${rowId}"
                           style="font-size:0.82rem; font-weight:700; border-color:#d97706;">
                    <div class="resolve-rso-status small mt-0.5" id="statusNewRso_${rowId}" style="font-size:0.74rem; min-height:16px;">
                      <span class="text-success font-weight-bold"><i class="fa fa-check-circle mr-1"></i> Suggested & Available</span>
                    </div>
                  </div>
                  <button type="button" class="btn btn-sm btn-primary font-weight-bold btn-apply-resolve" 
                          data-original-rso="${esc(item.stockorder_no)}" 
                          data-product-id="${entry.productid}" 
                          data-product-name="${esc(entry.productname)}" 
                          data-tray-id="${entry.tray_id}"
                          data-tray-count="${entry.tray_count}" 
                          data-bed-no="${esc(entry.bed_no)}" 
                          data-input-id="inputNewRso_${rowId}"
                          style="background:#4f46e5; border-color:#4338ca; border-radius:6px; font-size:0.82rem; white-space:nowrap;">
                    <i class="fa fa-wrench mr-1"></i> Update RSO
                  </button>
                </div>
              `}
            </td>
          </tr>
        `;
      });

      html += `
                </tbody>
              </table>
            </div>
          </div>
        </div>
      `;
    });

    $('#dupProdListContainer').html(html);
  }

  function renderBedConflicts(list) {
    if (!list || list.length === 0) {
      $('#dupBedListContainer').html(`
        <div class="alert alert-info d-flex align-items-center py-3 px-4" style="border-radius: 10px; background: #f0f9ff; border-color: #bae6fd; color: #0369a1;">
          <i class="fa fa-info-circle fa-2x mr-3 text-info"></i>
          <div>
            <h6 class="font-weight-bold mb-1" style="color: #0369a1;">No Multi-Bed Splits</h6>
            <p class="mb-0 small" style="color: #0284c7;">No RSO stock orders are split across multiple physical beds.</p>
          </div>
        </div>
      `);
      return;
    }

    let html = '';
    list.forEach((item, idx) => {
      html += `
        <div class="card border mb-3 shadow-2xs dup-item-card" data-rso="${esc(item.stockorder_no)}" style="border-radius: 12px; border-color: #cbd5e1 !important; overflow: hidden;">
          <div class="card-header py-2.5 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
            <div class="d-flex align-items-center gap-2">
              <span class="badge font-monospace px-2.5 py-1 text-white font-weight-bold" style="font-size: 0.9rem; background: #475569;">
                ${esc(item.stockorder_no)}
              </span>
              <strong class="text-dark small">${esc(item.productname)}</strong>
              <span class="badge badge-secondary" style="font-size: 0.78rem;">
                <i class="fa fa-th mr-1"></i> Split Across ${item.bed_count} Beds (${item.total_trays} Trays)
              </span>
            </div>
          </div>
          <div class="card-body p-3 bg-white">
            <div class="table-responsive">
              <table class="table table-sm table-bordered mb-0 align-middle" style="font-size: 0.86rem;">
                <thead class="table-light" style="font-size: 0.8rem; text-transform: uppercase;">
                  <tr>
                    <th style="width: 20%;">Bed Number</th>
                    <th style="width: 20%;">Trays / Plants</th>
                    <th style="width: 20%;">Status</th>
                    <th style="width: 40%;">Separate Bed to Unique RSO</th>
                  </tr>
                </thead>
                <tbody>
      `;

      (item.beds || []).forEach((bed, bIdx) => {
        const isOrig = bed.is_original;
        const suggRso = bed.suggested ? bed.suggested.suggested_rso : '';
        const rowId = `bed_${idx}_${bIdx}`;

        html += `
          <tr class="${isOrig ? 'table-light' : ''}">
            <td>
              <span class="badge bg-light text-dark border px-2.5 py-1" style="font-size:0.85rem; font-weight:700;">
                ${esc(bed.bed_no)}
              </span>
            </td>
            <td>
              <strong>${bed.tray_count}</strong> trays
              <div class="small text-muted">${Number(bed.plant_count).toLocaleString()} plants</div>
            </td>
            <td>
              ${isOrig ? `
                <span class="badge badge-secondary px-2 py-1" style="font-size:0.78rem;">Primary Bed (Keeps RSO)</span>
              ` : `
                <span class="badge badge-warning text-dark px-2 py-1" style="background:#fef08a; font-size:0.78rem;">Additional Bed</span>
              `}
            </td>
            <td>
              ${isOrig ? `
                <span class="text-muted small"><i class="fa fa-shield-alt mr-1"></i> Stays with original RSO</span>
              ` : `
                <div class="d-flex align-items-center gap-1.5 flex-wrap">
                  <div style="flex: 1; min-width: 140px;">
                    <input type="text" class="form-control form-control-sm font-monospace input-resolve-rso" 
                           id="inputNewRso_${rowId}" 
                           value="${esc(suggRso)}" 
                           placeholder="e.g. RSO-NEW..." 
                           data-status-id="statusNewRso_${rowId}"
                           style="font-size:0.82rem; font-weight:700; border-color:#d97706;">
                    <div class="resolve-rso-status small mt-0.5" id="statusNewRso_${rowId}" style="font-size:0.74rem; min-height:16px;">
                      <span class="text-success font-weight-bold"><i class="fa fa-check-circle mr-1"></i> Available</span>
                    </div>
                  </div>
                  <button type="button" class="btn btn-sm btn-outline-primary font-weight-bold btn-apply-resolve" 
                          data-original-rso="${esc(item.stockorder_no)}" 
                          data-product-id="${bed.productid}" 
                          data-product-name="${esc(bed.productname)}" 
                          data-tray-id="${bed.tray_id}"
                          data-tray-count="${bed.tray_count}" 
                          data-bed-no="${esc(bed.bed_no)}" 
                          data-input-id="inputNewRso_${rowId}"
                          style="border-radius:6px; font-size:0.82rem; white-space:nowrap;">
                    <i class="fa fa-cut mr-1"></i> Split to New RSO
                  </button>
                </div>
              `}
            </td>
          </tr>
        `;
      });

      html += `
                </tbody>
              </table>
            </div>
          </div>
        </div>
      `;
    });

    $('#dupBedListContainer').html(html);
  }

  // Live filter for duplicate list
  $('#dupRsoSearchFilter').on('input', function() {
    const q = $(this).val().toLowerCase().trim();
    if (!q) {
      $('.dup-item-card').show();
      return;
    }
    $('.dup-item-card').each(function() {
      const text = $(this).text().toLowerCase();
      $(this).toggle(text.includes(q));
    });
  });

  // Debounced availability check when user modifies suggested RSO in the modal
  let inlineRsoTimer = null;
  $(document).on('input', '.input-resolve-rso', function() {
    const $input = $(this);
    const val = $input.val().trim();
    const statusId = $input.data('status-id');
    const $status = $('#' + statusId);

    if (!val) {
      $status.html('<span class="text-danger">Enter an RSO number</span>');
      return;
    }

    clearTimeout(inlineRsoTimer);
    $status.html('<span class="text-secondary"><i class="fa fa-spinner fa-spin mr-1"></i> Checking...</span>');

    inlineRsoTimer = setTimeout(function() {
      const cleanVal = val.replace(/^(RSO|OS)-/i, '');
      $.post(getModalUrl('checkbillno'), { projectid, bill_no: cleanVal, stock_type: 'readystock' }, function(res) {
        if (res && res.status === 'success') {
          if (res.exists || !res.available) {
            $input.css('border-color', '#ef4444');
            $status.html('<span class="text-danger font-weight-bold"><i class="fa fa-times-circle mr-1"></i> Already exists! Not available</span>');
          } else {
            $input.css('border-color', '#10b981');
            $status.html('<span class="text-success font-weight-bold"><i class="fa fa-check-circle mr-1"></i> Available</span>');
          }
        }
      }, 'json');
    }, 300);
  });

  // Handle Update RSO Click
  $(document).on('click', '.btn-apply-resolve', function() {
    const $btn = $(this);
    const origRso = $btn.data('original-rso');
    const productId = $btn.data('product-id');
    const productName = $btn.data('product-name');
    const trayId = $btn.data('tray-id');
    const trayCount = $btn.data('tray-count');
    const bedNo = String($btn.data('bed-no') || '');
    const inputId = $btn.data('input-id');
    const newRso = $('#' + inputId).val().trim();
    const loginid = $('body').data('loginid') || $('#hidden_loginid').val() || '';

    if (!newRso) {
      alert('Please enter a new unique RSO number.');
      $('#' + inputId).focus();
      return;
    }

    if (newRso.toLowerCase() === origRso.toLowerCase()) {
      alert('New RSO must be different from the original RSO.');
      return;
    }

    const confirmHtml = `
      <div class="text-left" style="font-size: 0.92rem;">
        <p>You are about to reassign <b>${trayCount} trays</b> of <b>${esc(productName)}</b>:</p>
        <div class="p-2.5 rounded border mb-2" style="background:#f8fafc;">
          <div><b>From Original RSO:</b> <span class="badge bg-danger text-white">${esc(origRso)}</span></div>
          <div class="mt-1"><b>To New Unique RSO:</b> <span class="badge bg-success text-white">${esc(newRso)}</span></div>
          <div class="mt-1"><b>Bed:</b> ${esc(bedNo)}</div>
        </div>
        <p class="small text-muted mb-0"><i class="fa fa-info-circle mr-1 text-primary"></i> Polyhouse plant trays and ready stock will be updated, and a clean production order will be created. No data will be deleted.</p>
      </div>
    `;

    const doResolve = function() {
      const oldBtnHtml = $btn.html();
      $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i> Updating...');

      $.post(getModalUrl('resolve_duplicate_rso'), {
        projectid,
        original_rso: origRso,
        new_rso: newRso,
        target_productid: productId,
        target_bed_no: bedNo.includes(',') ? '' : bedNo,
        loginid
      }, function(res) {
        $btn.prop('disabled', false).html(oldBtnHtml);
        if (res.status === 'success') {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              title: 'RSO Number Updated!',
              html: `
                <div class="text-center">
                  <div class="text-success mb-2" style="font-size: 1.05rem; font-weight: 700;">
                    <i class="fa fa-check-circle mr-1"></i> ${esc(res.message)}
                  </div>
                  <p class="small text-muted">Trays have been assigned to <span class="font-monospace font-weight-bold text-dark">${esc(res.new_rso)}</span>.</p>
                  <p class="small text-dark font-weight-bold mt-2">Do you want to reprint QR code labels for this new RSO now?</p>
                </div>
              `,
              icon: 'success',
              showCancelButton: true,
              confirmButtonColor: '#059669',
              cancelButtonColor: '#64748b',
              confirmButtonText: '<i class="fa fa-qrcode mr-1"></i> Reprint QR Codes',
              cancelButtonText: 'Done'
            }).then((qrChoice) => {
              if (qrChoice.isConfirmed) {
                if (typeof window.openOrderQrPrintModal === 'function') {
                  window.openOrderQrPrintModal(
                    res.bed_no || bedNo,
                    res.new_rso,
                    res.new_rso,
                    res.productid || productId,
                    res.tray_id || trayId,
                    res.productname || productName,
                    res.polyhouseid || '',
                    ''
                  );
                }
              }
            });
          } else {
            alert(res.message);
          }

          // Refresh duplicate lists
          loadDuplicateRsos();

          // Refresh main board report if open
          if ($('#phLocation').val() && $('#phPolyhouse').val()) {
            loadReport();
          }
        } else {
          if (typeof Swal !== 'undefined') {
            Swal.fire('Update Failed', res.message || 'Could not update RSO number.', 'error');
          } else {
            alert('Update Failed: ' + (res.message || 'Could not update RSO number.'));
          }
        }
      }, 'json').fail(function() {
        $btn.prop('disabled', false).html(oldBtnHtml);
        alert('Server or network error updating RSO.');
      });
    };

    if (typeof Swal !== 'undefined') {
      Swal.fire({
        title: 'Reassign Duplicate Trays?',
        html: confirmHtml,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa fa-check mr-1"></i> Yes, Update RSO',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          doResolve();
        }
      });
    } else {
      if (confirm(`Reassign ${trayCount} trays to ${newRso}?`)) {
        doResolve();
      }
    }
  });

  window.loadBedWiseReport = loadReport;
  loadLocations();
});
</script>
