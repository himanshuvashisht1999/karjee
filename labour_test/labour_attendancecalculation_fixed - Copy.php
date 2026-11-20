<?php
if (isset($_GET['vw']) && $_GET['vw'] == 1) {
    $key = $_GET['k'];
    $rooturl = "";
    $mdid = "";
    if (isset($_GET['mdid'])) {
        $mdid = $_GET['mdid'];
    }
} else {
    require('header.php');
}
?>
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
/* ── Page Variables ─────────────────────────────────────────────── */
:root {
    --att-green:   #1b7a48;
    --att-green-lt:#e6f4ed;
    --att-blue:    #1d4ed8;
    --att-blue-lt: #eef3fc;
    --att-amber:   #92400e;
    --att-amber-lt:#fffbeb;
    --att-red:     #c0392b;
    --att-red-lt:  #fdf0f0;
    --att-gray:    #6b7280;
    --att-border:  #e5e7eb;
    --att-card-bg: #ffffff;
    --att-page-bg: #f3f4f6;
}

/* ── Layout ─────────────────────────────────────────────────────── */
.ac-page { font-family: 'Inter', sans-serif; background: var(--att-page-bg); min-height: 100vh; padding: 0; }

/* ── Header Bar ─────────────────────────────────────────────────── */
.ac-header {
    background: linear-gradient(135deg, #111827 0%, #1f2937 50%, #374151 100%);
    color: #fff;
    padding: 20px 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    box-shadow: 0 4px 20px rgba(17, 24, 39, 0.25);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}
.ac-header-left { display: flex; align-items: center; gap: 14px; }
.ac-header-icon {
    width: 48px; height: 48px;
    background: rgba(255,255,255,.08);
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px; backdrop-filter: blur(4px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    color: #38bdf8;
}
.ac-header h2 { font-family: 'Outfit', sans-serif; font-size: 20px; font-weight: 700; margin: 0; color: #fff; }
.ac-header p  { font-size: 12px; opacity: .8; margin: 0; color: #cbd5e1; }

/* ── Filter Bar ─────────────────────────────────────────────────── */
.ac-filters {
    background: #fff;
    padding: 16px 28px;
    border-bottom: 1px solid var(--att-border);
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
    align-items: flex-end;
}
.ac-filter-group { display: flex; flex-direction: column; gap: 4px; min-width: 180px; }
.ac-filter-label {
    font-size: 11px; font-weight: 600; text-transform: uppercase;
    letter-spacing: .5px; color: var(--att-gray);
}
.ac-select-wrap { position: relative; width: 100%; }
.ac-select-wrap i.ac-icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--att-gray); font-size: 14px; pointer-events: none; }
.ac-select-wrap select {
    width: 100%; padding: 8px 10px 8px 32px;
    border: 1.5px solid var(--att-border); border-radius: 8px;
    font-size: 13px; background: #f9fafb;
    appearance: none; cursor: pointer;
    transition: border-color .2s;
}
.ac-select-wrap select:focus { outline: none; border-color: var(--att-green); background: #fff; }
.ac-filter-date {
    padding: 8px 10px 8px 32px;
    border: 1.5px solid var(--att-border); border-radius: 8px;
    font-size: 13px; background: #f9fafb;
    cursor: pointer;
    transition: border-color .2s;
    width: 100%;
}
.ac-filter-date:focus { outline: none; border-color: var(--att-green); background: #fff; }
.ac-btn-load {
    padding: 8px 22px;
    background: linear-gradient(135deg, #1b7a48, #22c55e);
    color: #fff; border: none; border-radius: 8px;
    font-size: 13px; font-weight: 600;
    cursor: pointer;
    display: flex; align-items: center; gap: 6px;
    transition: transform .15s, box-shadow .15s;
    box-shadow: 0 3px 10px rgba(27,122,72,.3);
    white-space: nowrap;
}
.ac-btn-load:hover { transform: translateY(-1px); box-shadow: 0 5px 15px rgba(27,122,72,.4); }
.ac-btn-load:disabled {
    background: #94a3b8 !important;
    box-shadow: none !important;
    cursor: not-allowed !important;
    transform: none !important;
}

.ac-btn-confirm {
    padding: 8px 22px;
    background: linear-gradient(135deg, #1d4ed8, #3b82f6);
    color: #fff; border: none; border-radius: 8px;
    font-size: 13px; font-weight: 600;
    cursor: pointer;
    display: flex; align-items: center; gap: 6px;
    transition: transform .15s, box-shadow .15s;
    box-shadow: 0 3px 10px rgba(29,78,216,.3);
    white-space: nowrap;
}
.ac-btn-confirm:hover { transform: translateY(-1px); box-shadow: 0 5px 15px rgba(29,78,216,.4); }

/* ── Main Content ───────────────────────────────────────────────── */
.ac-content { padding: 28px; }

/* ── Section Header ─────────────────────────────────────────────── */
.ac-section-head {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 12px; flex-wrap: wrap; gap: 8px;
}
.ac-section-title {
    font-size: 14px; font-weight: 700; color: #111827;
    display: flex; align-items: center; gap: 7px;
}
.ac-section-title span.badge-count {
    background: var(--att-green); color: #fff;
    border-radius: 20px; font-size: 11px;
    padding: 1px 8px; font-weight: 600;
}
.ac-search-box {
    position: relative; display: flex;
}
.ac-search-box i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--att-gray); font-size: 13px; }
.ac-search-box input {
    padding: 6px 10px 6px 30px;
    border: 1.5px solid var(--att-border); border-radius: 8px;
    font-size: 12px; width: 200px; background: #f9fafb;
    transition: border-color .2s;
}
.ac-search-box input:focus { outline: none; border-color: var(--att-green); background: #fff; }

/* ── Table Card ─────────────────────────────────────────────────── */
.ac-table-card {
    background: var(--att-card-bg);
    border-radius: 14px;
    border: 1px solid var(--att-border);
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,.06);
    margin-bottom: 24px;
}
.ac-table-wrap {
    max-height: 65vh;
    overflow-y: auto;
    overflow-x: auto;
}
.ac-table {
    width: 100%; border-collapse: collapse;
    font-size: 12.5px;
}
.ac-table thead th {
    position: sticky;
    top: 0;
    z-index: 10;
    background: #f8fafc !important;
    padding: 10px 12px;
    text-align: left;
    font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .4px;
    color: var(--att-gray);
    border-bottom: 1.5px solid var(--att-border);
    white-space: nowrap;
    box-shadow: 0 1px 2px rgba(0,0,0,0.06);
}
.ac-table tbody tr {
    border-bottom: 1px solid #f3f4f6;
    transition: background .1s;
}
.ac-table tbody tr:hover { background: #f9fafb; }
.ac-table tbody td {
    padding: 9px 12px; vertical-align: middle;
}

.ac-labour-name {
    font-weight: 600; color: #111827; font-size: 13px;
}
.ac-labour-id {
    font-size: 10px; color: var(--att-gray); margin-top: 1px;
}

/* ── Empty & Loading states ─────────────────────────────────────── */
.ac-empty {
    text-align: center; padding: 48px 16px; color: var(--att-gray);
}
.ac-empty i { font-size: 36px; margin-bottom: 12px; color: #9ca3af; display: block; }
.ac-empty p { font-size: 13px; margin: 0; }
.ac-loading {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    padding: 48px 16px; color: var(--att-gray);
}
.ac-spinner {
    width: 32px; height: 32px; border: 3px solid #e5e7eb; border-top-color: var(--att-green);
    border-radius: 50%; animation: ac-spin 0.8s linear infinite;
    margin-bottom: 12px;
}
@keyframes ac-spin { to { transform: rotate(360deg); } }

/* ── Input & Warning Styling ────────────────────────────────────── */
.ac-table-input {
    width: 60px;
    padding: 4px 6px;
    border: 1px solid var(--att-border);
    border-radius: 6px;
    font-size: 12px;
    text-align: center;
    background: #f9fafb;
    transition: border-color .15s;
}
.ac-table-input:focus {
    outline: none;
    border-color: var(--att-green);
    background: #fff;
}
.ac-warning-row {
    background-color: var(--att-red-lt) !important;
}
.ac-warning-row:hover {
    background-color: #fce4e4 !important;
}

/* ── Summary Cards ─────────────────────────────────────────────── */
.ac-summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}
.ac-summary-card {
    background: #ffffff;
    border: 1px solid var(--att-border);
    border-radius: 12px;
    padding: 14px 18px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    cursor: pointer;
    transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease, background 0.15s ease;
    user-select: none;
}
.ac-summary-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 14px rgba(0,0,0,0.08);
}
.ac-summary-card.active-card {
    border-color: #3b82f6 !important;
    background: #f0f9ff !important;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2) !important;
}

.ac-badge-filter {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
}

/* Full Present Badge - Emerald Green Theme */
.ac-badge-full {
    background-color: #dcfce7;
    color: #15803d;
    border: 1px solid #bbf7d0;
}
.ac-badge-full:hover {
    background-color: #bbf7d0;
}
.ac-badge-full.active-badge {
    background-color: #16a34a !important;
    color: #ffffff !important;
    border-color: #15803d !important;
    box-shadow: 0 3px 8px rgba(22, 163, 74, 0.35) !important;
}

/* Half Present Badge - Amber Orange Theme */
.ac-badge-half {
    background-color: #fef3c7;
    color: #b45309;
    border: 1px solid #fde68a;
}
.ac-badge-half:hover {
    background-color: #fde68a;
}
.ac-badge-half.active-badge {
    background-color: #d97706 !important;
    color: #ffffff !important;
    border-color: #b45309 !important;
    box-shadow: 0 3px 8px rgba(217, 119, 6, 0.35) !important;
}

/* Missing Bank Details Badge - Rose/Red Theme */
.ac-badge-missing-bank {
    background-color: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
    font-size: 11px;
    padding: 2px 8px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: all 0.15s ease;
}
.ac-badge-missing-bank:hover {
    background-color: #fecaca;
    color: #7f1d1d;
}
.ac-badge-missing-bank.active-badge {
    background-color: #dc2626 !important;
    color: #ffffff !important;
    border-color: #b91c1c !important;
    box-shadow: 0 3px 8px rgba(220, 38, 38, 0.35) !important;
}

/* Un-verified Bank Details Badge - Amber/Yellow Theme */
.ac-badge-unverified-bank {
    background-color: #fef3c7;
    color: #b45309;
    border: 1px solid #fde68a;
    font-size: 11px;
    padding: 2px 8px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: all 0.15s ease;
}
.ac-badge-unverified-bank:hover {
    background-color: #fde68a;
    color: #92400e;
}
.ac-badge-unverified-bank.active-badge {
    background-color: #d97706 !important;
    color: #ffffff !important;
    border-color: #b45309 !important;
    box-shadow: 0 3px 8px rgba(217, 119, 6, 0.35) !important;
}

/* Updated Bank Details Badge - Emerald/Green Theme */
.ac-badge-updated-bank {
    background-color: #dcfce7;
    color: #15803d;
    border: 1px solid #86efac;
    font-size: 11px;
    padding: 2px 8px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: all 0.15s ease;
}
.ac-badge-updated-bank:hover {
    background-color: #bbf7d0;
    color: #14532d;
}
.ac-badge-updated-bank.active-badge {
    background-color: #16a34a !important;
    color: #ffffff !important;
    border-color: #15803d !important;
    box-shadow: 0 3px 8px rgba(22, 163, 74, 0.35) !important;
}

/* Modal Bank Verification Styles */
.bank-doc-preview-box {
    min-height: 280px;
    max-height: 480px;
    background: #f8fafc;
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
}
.bank-doc-preview-box img {
    max-width: 100%;
    max-height: 460px;
    object-fit: contain;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}
.bank-doc-preview-box iframe {
    width: 100%;
    height: 460px;
    border: none;
    border-radius: 8px;
}
.bank-detail-item {
    padding: 10px 14px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    margin-bottom: 8px;
}
.bank-detail-item .label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    color: #64748b;
    margin-bottom: 2px;
}
.bank-detail-item .value {
    font-size: 13.5px;
    font-weight: 600;
    color: #0f172a;
    word-break: break-all;
}

/* ── Animated Border Action Buttons in Modal ─────────────────────── */
.btn-animated-border {
    position: relative;
    border: none;
    background: transparent;
    padding: 2.5px;
    border-radius: 30px;
    cursor: pointer;
    overflow: hidden;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.28s cubic-bezier(0.4, 0, 0.2, 1);
    text-decoration: none;
    flex: 1 1 220px;
    max-width: 340px;
}
.btn-animated-border:hover {
    transform: translateY(-2px) scale(1.02);
}
.btn-animated-border:active {
    transform: translateY(0) scale(0.98);
}
.btn-animated-border::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    animation: rotateBtnBorder 2.8s linear infinite;
}
@keyframes rotateBtnBorder {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Blue Animated Button - Save & Update */
.btn-anim-blue {
    background: linear-gradient(135deg, #1d4ed8, #3b82f6, #06b6d4, #2563eb);
    background-size: 300% 300%;
    animation: gradientShift 4s ease infinite;
    box-shadow: 0 4px 16px rgba(37, 99, 235, 0.35);
}
.btn-anim-blue::before {
    background: conic-gradient(transparent, #60a5fa, #38bdf8, #93c5fd, #2563eb, transparent 40%);
}
.btn-anim-blue:hover {
    box-shadow: 0 8px 24px rgba(37, 99, 235, 0.5);
}
.btn-anim-blue .btn-anim-inner {
    background: linear-gradient(135deg, #1e40af 0%, #1d4ed8 50%, #2563eb 100%);
    color: #ffffff;
}

/* Green Animated Button - Mark as Verified */
.btn-anim-green {
    background: linear-gradient(135deg, #059669, #10b981, #34d399, #047857);
    background-size: 300% 300%;
    animation: gradientShift 4s ease infinite;
    box-shadow: 0 4px 16px rgba(16, 185, 129, 0.35);
}
.btn-anim-green::before {
    background: conic-gradient(transparent, #6ee7b7, #34d399, #a7f3d0, #059669, transparent 40%);
}
.btn-anim-green:hover {
    box-shadow: 0 8px 24px rgba(16, 185, 129, 0.5);
}
.btn-anim-green .btn-anim-inner {
    background: linear-gradient(135deg, #065f46 0%, #059669 50%, #10b981 100%);
    color: #ffffff;
}

@keyframes gradientShift {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

.btn-anim-inner {
    position: relative;
    z-index: 1;
    width: 100%;
    height: 100%;
    padding: 8.5px 16px;
    border-radius: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    font-weight: 700;
    font-size: 13px;
    letter-spacing: 0.2px;
    transition: all 0.25s ease;
}
.btn-anim-inner i {
    font-size: 16.5px;
    transition: transform 0.25s ease;
}
.btn-animated-border:hover .btn-anim-inner i {
    transform: scale(1.18);
}

/* Labour Dashboard Link */
.ac-labour-link {
    text-decoration: none;
    color: inherit;
    display: inline-block;
}
.ac-labour-link:hover .ac-labour-name {
    color: #2563eb !important;
    text-decoration: underline;
}

/* ── Modal Attendance Details Summary Cards ─────────────────────── */
.acd-summary-card {
    padding: 10px 14px;
    border-radius: 10px;
    text-align: center;
    border: 1.5px solid #e2e8f0;
    background: #ffffff;
    cursor: pointer;
    user-select: none;
    transition: all 0.2s ease-in-out;
}
.acd-summary-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}

.acd-summary-card .acd-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748b;
    margin-bottom: 2px;
}
.acd-summary-card .acd-value {
    font-size: 20px;
    font-weight: 800;
    line-height: 1.2;
    color: #0f172a;
}

/* Card 1: Total Logs */
#acd-card-all.active-card {
    border-color: #3b82f6 !important;
    background: #eff6ff !important;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
    animation: acd-pulse 1.8s infinite;
}
#acd-card-all.active-card .acd-label { color: #1d4ed8; }
#acd-card-all.active-card .acd-value { color: #1e40af; }

/* Card 2: Full Present (Green) */
.acd-card-full {
    background: #f0fdf4;
    border-color: #bbf7d0;
}
.acd-card-full .acd-label { color: #15803d; }
.acd-card-full .acd-value { color: #16a34a; }
.acd-card-full.active-card {
    border-color: #16a34a !important;
    background: #dcfce7 !important;
    box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.25);
    animation: acd-pulse 1.8s infinite;
}

/* Card 3: Half Present (Amber) */
.acd-card-half {
    background: #fffbeb;
    border-color: #fde68a;
}
.acd-card-half .acd-label { color: #b45309; }
.acd-card-half .acd-value { color: #d97706; }
.acd-card-half.active-card {
    border-color: #d97706 !important;
    background: #fef3c7 !important;
    box-shadow: 0 0 0 3px rgba(217, 119, 6, 0.25);
    animation: acd-pulse 1.8s infinite;
}

/* Card 4: Payable Days (Purple) */
.acd-card-payable {
    background: #faf5ff;
    border-color: #e9d5ff;
}
.acd-card-payable .acd-label { color: #7e22ce; }
.acd-card-payable .acd-value { color: #9333ea; }
.acd-card-payable.active-card {
    border-color: #9333ea !important;
    background: #f3e8ff !important;
    box-shadow: 0 0 0 3px rgba(147, 51, 234, 0.25);
    animation: acd-pulse 1.8s infinite;
}

/* ── Modal Active Card Animations ───────────────────────────────── */
@keyframes acd-pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.03); }
    100% { transform: scale(1); }
}

/* ── Discrepancy & Missing Checkout Active Animated Badges ───────── */
.ac-discrepancy-active {
    background-color: #fee2e2 !important;
    border-color: #f87171 !important;
    color: #991b1b !important;
    animation: ac-pulse-red 1.5s infinite;
}

@keyframes ac-pulse-red {
    0% {
        box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.6);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(239, 68, 68, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
    }
}

.ac-missing-active {
    background-color: #fef3c7 !important;
    border-color: #f59e0b !important;
    color: #92400e !important;
    animation: ac-pulse-amber 1.5s infinite;
}

@keyframes ac-pulse-amber {
    0% {
        box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.6);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(245, 158, 11, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(245, 158, 11, 0);
    }
}
</style>

<div class="ac-page">

    <!-- Header -->
    <div class="ac-header">
        <div class="ac-header-left">
            <div class="ac-header-icon"><i class="ti ti-calculator"></i></div>
            <div>
                <h2>Attendance Calculation</h2>
                <p><i class="ti ti-calendar me-1"></i> Calculate and confirm workforce attendance for payment</p>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="ac-filters">
        <!-- Date From -->
        <div class="ac-filter-group">
            <label class="ac-filter-label"><i class="ti ti-calendar me-1"></i> Date From</label>
            <div class="ac-select-wrap">
                <i class="ac-icon ti ti-calendar"></i>
                <input type="date" id="ac-date-from" class="ac-filter-date"  />
            </div>
        </div>

        <!-- Date To -->
        <div class="ac-filter-group">
            <label class="ac-filter-label"><i class="ti ti-calendar me-1"></i> Date To</label>
            <div class="ac-select-wrap">
                <i class="ac-icon ti ti-calendar"></i>
                <input type="date" id="ac-date-to" class="ac-filter-date" disabled/>
            </div>
        </div>

        <!-- Location -->
        <div class="ac-filter-group">
            <label class="ac-filter-label"><i class="ti ti-map-pin me-1"></i> Location</label>
            <div class="ac-select-wrap">
                <i class="ac-icon ti ti-building-estate"></i>
                <select id="ac-location">
                    <option value="">Loading locations…</option>
                </select>
            </div>
        </div>

        <!-- Load & Confirm Action -->
        <div class="ac-filter-group" style="justify-content:flex-end;">
            <label class="ac-filter-label">&nbsp;</label>
            <div style="display:flex;gap:8px;">
                <button class="ac-btn-load" id="ac-btn-load" onclick="loadCalculatedAttendance()" disabled>
                    <i class="ti ti-search"></i> Load Records
                </button>
                <button class="ac-btn-confirm" id="ac-btn-confirm-save" style="display:none;" onclick="saveConfirmedAttendance()">
                    <i class="ti ti-device-floppy"></i> Confirm &amp; Save
                </button>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="ac-content">
        <div class="ac-section-head" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:12px;">
            <div class="ac-section-title" style="margin-bottom:0;">
                <i class="ti ti-list" style="color:var(--att-green);"></i>
                Attendance Calculation Sheets
                <span class="badge-count" id="ac-record-count">0</span>
            </div>
            <div style="display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
                <label style="display:inline-flex; align-items:center; gap:6px; font-size:12.5px; font-weight:600; cursor:pointer; color:var(--att-gray); margin-bottom:0; user-select:none;">
                    <input type="checkbox" id="ac-show-zero-days" checked onchange="filterCalculatedTable()" style="width:16px; height:16px; accent-color:var(--att-green); cursor:pointer;">
                    <span id="ac-show-zero-days-text">Show 0 Payable Days</span>
                </label>
                <span class="badge bg-danger-subtle text-danger" id="ac-warning-count-wrap" style="display:none; font-size:12.5px; font-weight:600; padding:6px 12px; border-radius:8px; border:1px solid #fecaca; align-items:center; gap:4px; cursor:pointer;" onclick="setCardFilter('discrepancy')" title="Click to filter discrepancy entries">
                    <i class="ti ti-alert-triangle" style="color:var(--att-red); font-size:14px;"></i> Discrepancy: <span id="ac-warning-count">0</span>
                </span>
                <span class="badge" id="ac-missing-out-wrap" style="display:none; font-size:12.5px; font-weight:600; padding:6px 12px; border-radius:8px; border:1px solid #fde047; background-color:#fef9c3; color:#854d0e; align-items:center; gap:4px; cursor:pointer;" onclick="setCardFilter('missing_checkout')" title="Click to filter missing check-out entries">
                    <i class="ti ti-clock-off" style="color:#a16207; font-size:14px;"></i> Missing Check-Out: <span id="ac-missing-out-count">0</span>
                </span>
                <button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 shadow-sm ms-1" id="ac-btn-refresh" onclick="loadCalculatedAttendance()" style="border-radius: 8px; font-weight: 600; font-size: 12.5px; padding: 5px 12px;" title="Refresh calculated attendance data">
                    <i class="ti ti-refresh"></i> Refresh Data
                </button>
            </div>
        </div>

        <!-- Calculation Rule Warning -->
        <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center gap-2 mb-3" style="border-radius: 10px; font-size: 13px; font-weight: 500; border-left: 4px solid #f59e0b; background-color: #fffbeb; color: #92400e; padding: 12px 16px;">
            <i class="ti ti-info-circle fs-5" style="color: #d97706;"></i>
            <div>
                <strong>Calculation Rules:</strong> 
                Half-Day (HD): Working Hours <strong>&gt; 2 hours and &lt; 6 hours</strong> | 
                Full-Day (P): Working Hours <strong>&ge; 6 hours</strong> | 
                Absent (A): Working Hours <strong>&le; 2 hours</strong>.
            </div>
        </div>

        <!-- Status & Discrepancy Banner Container -->
        <div id="ac-status-banner-wrap"></div>

        <!-- Summary Cards -->
        <div class="ac-summary-grid mb-3" id="ac-summary-cards" style="display:none;">
            <div class="ac-summary-card" id="card-filter-labour" onclick="setCardFilter('labour')" title="Click to show all labours">
                <div class="ac-summary-icon" style="background:#e0f2fe; color:#0284c7;">
                    <i class="ti ti-users"></i>
                </div>
                <div class="ac-summary-info">
                    <span class="ac-summary-label">Total Labour</span>
                    <div class="d-flex align-items-center gap-2 flex-wrap" style="margin-top:2px;">
                        <span class="ac-summary-value" id="ac-sum-total-labour">0</span>
                        <span class="ac-badge-filter ac-badge-missing-bank" id="badge-missing-bank" onclick="event.stopPropagation(); setCardFilter('missing_bank');" title="Click to filter labours with missing bank details" style="display:none;">
                            <i class="ti ti-building-bank"></i> <span id="ac-missing-bank-count">0</span> Missing Bank
                        </span>
                        <span class="ac-badge-filter ac-badge-unverified-bank" id="badge-unverified-bank" onclick="event.stopPropagation(); setCardFilter('unverified_bank');" title="Click to filter labours with un-verified bank details" style="display:none;">
                            <i class="ti ti-shield-alert"></i> <span id="ac-unverified-bank-count">0</span> Un-Verified Bank
                        </span>
                        <span class="ac-badge-filter ac-badge-updated-bank" id="badge-updated-bank" onclick="event.stopPropagation(); setCardFilter('updated_bank');" title="Click to filter labours with updated bank details" style="display:none;">
                            <i class="ti ti-building-bank"></i> <span id="ac-updated-bank-count">0</span> Bank Updated
                        </span>
                    </div>
                </div>
            </div>
            <div class="ac-summary-card" id="card-filter-checkins" onclick="setCardFilter('checkins')" title="Click to show labours with check-ins">
                <div class="ac-summary-icon" style="background:#fef3c7; color:#d97706;">
                    <i class="ti ti-clock-check"></i>
                </div>
                <div class="ac-summary-info">
                    <span class="ac-summary-label">Total Check-Ins</span>
                    <span class="ac-summary-value" id="ac-sum-total-checkins">0</span>
                </div>
            </div>
            <div class="ac-summary-card" id="card-filter-present" title="Click to filter present labours">
                <div class="ac-summary-icon" style="background:#dcfce7; color:#16a34a;" onclick="setCardFilter('full_present')">
                    <i class="ti ti-user-check"></i>
                </div>
                <div class="ac-summary-info">
                    <span class="ac-summary-label">Total Present</span>
                    <span class="ac-summary-value" style="font-size:13px; margin-top:4px;">
                        <span class="ac-badge-filter ac-badge-full" id="badge-filter-full" onclick="event.stopPropagation(); setCardFilter('full_present');" title="Click to show Full Present"><span id="ac-sum-full-present">0</span> Full</span> : 
                        <span class="ac-badge-filter ac-badge-half" id="badge-filter-half" onclick="event.stopPropagation(); setCardFilter('half_present');" title="Click to show Half Present"><span id="ac-sum-half-present">0</span> Half</span>
                    </span>
                </div>
            </div>
            <div class="ac-summary-card" id="card-filter-payable" onclick="setCardFilter('payable')" title="Click to show labours with payable days">
                <div class="ac-summary-icon" style="background:#f3e8ff; color:#9333ea;">
                    <i class="ti ti-calendar-event"></i>
                </div>
                <div class="ac-summary-info">
                    <span class="ac-summary-label">Payable Days</span>
                    <span class="ac-summary-value" id="ac-sum-payable-days">0.0</span>
                </div>
            </div>
        </div>

        <!-- Search Bar before Table -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3" id="ac-table-toolbar">
            <div class="ac-search-box" style="min-width:280px;">
                <i class="ti ti-search"></i>
                <input type="text" id="ac-search" placeholder="Search labour name or ID…" oninput="filterCalculatedTable()">
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <button type="button" class="btn btn-outline-danger btn-sm fw-bold d-inline-flex align-items-center gap-1 shadow-sm" id="btn-export-missing-bank" onclick="exportMissingBankDetails()" style="display:none; border-radius:8px; font-size:12.5px; padding:6px 14px;" title="Export Labours with Missing Bank Details">
                    <i class="ti ti-file-export"></i> Export Missing Bank List (<span id="btn-export-missing-count">0</span>)
                </button>
                <button type="button" class="btn btn-outline-warning btn-sm fw-bold d-inline-flex align-items-center gap-1 shadow-sm text-dark" id="btn-export-unverified-bank" onclick="exportUnverifiedBankDetails()" style="display:none; border-radius:8px; font-size:12.5px; padding:6px 14px;" title="Export Labours with Un-Verified Bank Details">
                    <i class="ti ti-shield-alert text-warning"></i> Export Un-Verified Bank List (<span id="btn-export-unverified-count">0</span>)
                </button>
                <button type="button" class="btn btn-outline-success btn-sm fw-bold d-inline-flex align-items-center gap-1 shadow-sm" id="btn-export-updated-bank" onclick="exportUpdatedBankDetails()" style="display:none; border-radius:8px; font-size:12.5px; padding:6px 14px;" title="Export Labours with Updated Bank Details">
                    <i class="ti ti-file-spreadsheet"></i> Export Bank Updated List (<span id="btn-export-updated-count">0</span>)
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm fw-bold d-inline-flex align-items-center gap-1 shadow-sm" id="btn-export-calculated" onclick="exportCalculatedAttendance()" style="display:none; border-radius:8px; font-size:12.5px; padding:6px 14px;" title="Export Attendance Table">
                    <i class="ti ti-download"></i> Export Table
                </button>
            </div>
        </div>

        <!-- Table Card -->
        <div class="ac-table-card">
            <div class="ac-table-wrap">
                <table class="ac-table" id="ac-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Labour Name</th>
                            <th>Total Check-Ins</th>
                            <th>Full Present Days</th>
                            <th>Half-Present Days</th>
                            <th>Payable Days</th>
                        </tr>
                    </thead>
                    <tbody id="ac-tbody">
                        <tr>
                            <td colspan="6">
                                <div class="ac-empty">
                                    <i class="ti ti-calendar-search"></i>
                                    <p>Select date range and location, then click <strong>Load Records</strong></p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- Attendance Details Modal -->
<div class="modal fade" id="acDetailsModal" tabindex="-1" aria-labelledby="acDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.15);">
      <div class="modal-header" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; border-top-left-radius: 12px; border-top-right-radius: 12px;">
        <h5 class="modal-title" id="acDetailsModalLabel" style="font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 8px;">
          <i class="ti ti-calendar" style="color: #1b7a48; font-size: 20px;"></i> Attendance Details
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" style="padding: 20px; max-height: 450px; overflow-y: auto;">
        <!-- Labour Info Header inside Modal -->
        <div class="mb-3 p-3 d-flex justify-content-between align-items-center flex-wrap gap-2" style="background: #f1f5f9; border-radius: 8px;">
          <div>
            <div style="font-weight: 700; font-size: 15px; color: #1e293b;" id="acd-labour-name">Loading...</div>
            <div style="font-size: 12px; color: #64748b;" id="acd-date-range">Range: - to -</div>
          </div>
          <button type="button" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 shadow-sm" id="acd-btn-refresh" onclick="refreshAttendanceDetails()" style="border-radius: 6px; font-weight: 600; font-size: 12px; padding: 4px 10px;" title="Refresh attendance details">
            <i class="ti ti-refresh"></i> Refresh Details
          </button>
        </div>

        <!-- Attendance Summary Grid inside Modal -->
        <div class="row g-2 mb-3" id="acd-summary-grid" style="display:none;">
          <div class="col-6 col-md-3">
            <div class="acd-summary-card active-card" id="acd-card-all" onclick="setModalCardFilter('all')" title="Click to show all logs">
              <div class="acd-label">Total Logs</div>
              <div class="acd-value" id="acd-sum-total-logs">0</div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="acd-summary-card acd-card-full" id="acd-card-full" onclick="setModalCardFilter('full')" title="Click to filter Full Present">
              <div class="acd-label">Full Present</div>
              <div class="acd-value" id="acd-sum-full-present">0</div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="acd-summary-card acd-card-half" id="acd-card-half" onclick="setModalCardFilter('half')" title="Click to filter Half Present">
              <div class="acd-label">Half Present</div>
              <div class="acd-value" id="acd-sum-half-present">0</div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="acd-summary-card acd-card-payable" id="acd-card-payable" onclick="setModalCardFilter('payable')" title="Click to filter Payable Days">
              <div class="acd-label">Payable Days</div>
              <div class="acd-value" id="acd-sum-payable">0.0</div>
            </div>
          </div>
        </div>
        
        <!-- Table -->
        <table class="table table-hover align-middle" style="font-size: 13px;">
          <thead class="table-light">
            <tr>
              <th>Date</th>
              <th>Shift 1 (MN)</th>
              <th>Shift 2 (DP)</th>
              <th>Max Hours</th>
              <th>Type</th>
            </tr>
          </thead>
          <tbody id="acd-tbody">
            <!-- Dynamic rows loaded by AJAX -->
          </tbody>
        </table>
      </div>
      <div class="modal-footer" style="background: #f8fafc; border-top: 1px solid #e2e8f0; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px; padding: 12px 20px;">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="font-weight: 600; font-size: 13px; border-radius: 8px;">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Discrepancy Correction Modal -->
<div class="modal fade" id="discrepancyCorrectionModal" tabindex="-1" aria-labelledby="discrepancyCorrectionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content" style="border-radius: 14px; border: none; box-shadow: 0 12px 30px rgba(0,0,0,0.2);">
      <div class="modal-header bg-light border-bottom py-3">
        <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="discrepancyCorrectionModalLabel">
          <i class="ti ti-alert-triangle text-danger fs-4"></i> Correction of Discrepancy Attendance Entries
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4" style="max-height: 550px; overflow-y: auto;">
        
        <!-- Action Toolbar inside Modal -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 p-3 bg-light rounded-3 border">
          <div class="input-group" style="max-width: 320px;">
            <span class="input-group-text bg-white border-end-0"><i class="ti ti-search text-muted"></i></span>
            <input type="text" class="form-control border-start-0 ps-0" id="disc-search-input" placeholder="Search labour name or ID..." onkeyup="filterDiscrepancyModalTable()">
          </div>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-danger btn-sm fw-bold d-inline-flex align-items-center gap-1 shadow-sm" onclick="removeSelectedAttendanceEntries()">
              <i class="ti ti-trash"></i> Remove Selected Entry
            </button>
            <button type="button" class="btn btn-primary btn-sm fw-bold d-inline-flex align-items-center gap-1 shadow-sm" onclick="openBatchCheckoutUpdateModal()">
              <i class="ti ti-clock-check"></i> Update Check-Out Time
            </button>
          </div>
        </div>

        <!-- Discrepancy Table -->
        <div class="table-responsive">
          <table class="table table-hover align-middle" id="disc-modal-table" style="font-size: 13px;">
            <thead class="table-dark">
              <tr>
                <th style="width: 40px;" class="text-center">
                  <input type="checkbox" id="disc-select-all" onclick="toggleSelectAllDiscrepancies(this)" style="cursor:pointer;">
                </th>
                <th>Date</th>
                <th>Labour Name (ID)</th>
                <th>Shift 1 (MN)</th>
                <th>Shift 2 (DP)</th>
                <th>Issue Type</th>
              </tr>
            </thead>
            <tbody id="disc-modal-tbody">
              <!-- Loaded via AJAX -->
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer bg-light border-top py-2">
        <button type="button" class="btn btn-secondary btn-sm fw-bold" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Bank Verification & Edit Details Modal -->
<div class="modal fade" id="bankVerificationModal" tabindex="-1" aria-labelledby="bankVerificationModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content" style="border-radius: 14px; border: none; box-shadow: 0 12px 35px rgba(0,0,0,0.2);">
      <div class="modal-header bg-light border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
        <div>
          <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2 mb-0" id="bankVerificationModalLabel">
            <i class="ti ti-building-bank text-primary fs-4"></i> Labour Bank Verification &amp; Details
          </h5>
          <small class="text-muted" id="bvm-labour-info">Review or edit bank details, upload documents &amp; verify</small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="bvmBankForm" enctype="multipart/form-data" onsubmit="saveBankDetailsAndVerification(event)">
        <div class="modal-body p-4" style="max-height: 75vh; overflow-y: auto;">
          
          <input type="hidden" id="bvm-bank-id" name="bank_id">
          <input type="hidden" id="bvm-labour-id" name="labourid">

          <div class="row g-4">
            <!-- Left Column: Bank Account Details & Edit Form -->
            <div class="col-lg-6">
              <div class="p-3 bg-white rounded-3 border h-100 shadow-sm d-flex flex-column justify-content-between">
                <div>
                  <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-bold text-dark mb-0"><i class="ti ti-edit text-primary me-1"></i> Edit Bank Details</h6>
                    <span id="bvm-status-badge"></span>
                  </div>

                  <div class="mb-3">
                    <label class="form-label small fw-bold text-muted mb-1">Labour Name &amp; ID</label>
                    <div class="input-group input-group-sm">
                      <span class="input-group-text bg-light border-0"><i class="ti ti-user text-muted"></i></span>
                      <input type="text" class="form-control bg-light border-0 fw-bold text-primary" id="bvm-labour-name" readonly>
                    </div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label small fw-bold text-muted mb-1">Bank Name <span class="text-danger">*</span></label>
                    <div class="input-group input-group-sm">
                      <span class="input-group-text bg-light border-0"><i class="ti ti-building-bank text-muted"></i></span>
                      <input type="text" class="form-control bg-light border-0 fw-semibold" id="bvm-bank-name" name="bankname" required placeholder="e.g. State Bank of India, Bank of Baroda">
                    </div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label small fw-bold text-muted mb-1">Account Number <span class="text-danger">*</span></label>
                    <div class="input-group input-group-sm">
                      <span class="input-group-text bg-light border-0"><i class="ti ti-credit-card text-muted"></i></span>
                      <input type="text" class="form-control bg-light border-0 font-monospace fw-bold text-dark" id="bvm-account-number" name="accountnumber" required placeholder="Enter bank account number">
                    </div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label small fw-bold text-muted mb-1">IFSC Code <span class="text-danger">*</span></label>
                    <div class="input-group input-group-sm">
                      <span class="input-group-text bg-light border-0"><i class="ti ti-hash text-muted"></i></span>
                      <input type="text" class="form-control bg-light border-0 font-monospace fw-bold text-uppercase text-dark" id="bvm-ifsc-code" name="ifsccode" required placeholder="e.g. SBIN0001234" maxlength="15">
                    </div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label small fw-bold text-muted mb-1">Verification Status <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm bg-light border-0 fw-semibold" id="bvm-verifystatus" name="verifystatus" required>
                      <option value="Pending">🟡 Pending / Un-Verified</option>
                      <option value="Verified">🟢 Verified &amp; Approved</option>
                      <option value="Rejected">🔴 Rejected / Needs Correction</option>
                    </select>
                  </div>

                  <!-- Reason of Un-Verify Section (Visible when Pending / Un-Verified is selected) -->
                  <div class="mb-3" id="bvm-unverify-reason-container" style="display: none;">
                    <div class="p-2.5 rounded-3 border" style="background-color: #fffdf5; border-color: #fde68a !important;">
                      <label class="form-label small fw-bold text-dark mb-2 d-flex align-items-center gap-1">
                        <i class="ti ti-alert-triangle text-warning fs-15"></i> Reason of Un-Verify Bank Details:
                      </label>
                      <div class="d-flex flex-column gap-2 ms-1">
                        <div class="form-check form-check-inline m-0">
                          <input class="form-check-input bvm-unverify-chk" type="checkbox" id="bvm-chk-mismatch-acc" value="Mismatch Account No">
                          <label class="form-check-label small fw-semibold text-dark cursor-pointer" for="bvm-chk-mismatch-acc">
                            1. Mismatch Account No
                          </label>
                        </div>
                        <div class="form-check form-check-inline m-0">
                          <input class="form-check-input bvm-unverify-chk" type="checkbox" id="bvm-chk-mismatch-ifsc" value="Mismatch IFSC Code">
                          <label class="form-check-label small fw-semibold text-dark cursor-pointer" for="bvm-chk-mismatch-ifsc">
                            2. Mismatch IFSC Code
                          </label>
                        </div>
                        <div class="form-check form-check-inline m-0">
                          <input class="form-check-input bvm-unverify-chk" type="checkbox" id="bvm-chk-mismatch-name" value="Mismatch Account holder Name">
                          <label class="form-check-label small fw-semibold text-dark cursor-pointer" for="bvm-chk-mismatch-name">
                            3. Mismatch Account holder Name
                          </label>
                        </div>
                      </div>

                      <!-- Display Name Input (Visible & Required when Checkbox 3 is checked) -->
                      <div class="mt-2.5 pt-2 border-top" id="bvm-display-name-wrap" style="display: none; border-color: #fef08a !important;">
                        <label class="form-label small fw-bold text-dark mb-1" for="bvm-display-name">
                          Display Name as per verified <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-sm">
                          <span class="input-group-text bg-white border-0 text-muted"><i class="ti ti-id"></i></span>
                          <input type="text" class="form-control bg-white border-0 fw-semibold text-uppercase" id="bvm-display-name" placeholder="Enter display name as per verified">
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="mb-2">
                    <label class="form-label small fw-bold text-muted mb-1">
                      <i class="ti ti-upload me-1 text-primary"></i> Upload / Replace Document (Optional)
                    </label>
                    <input type="file" class="form-control form-control-sm bg-light border-0" id="bvm-bank-doc" name="bankDoc" accept="image/jpeg,image/png,image/webp,application/pdf" onchange="previewSelectedDoc(this)">
                    <small class="text-muted d-block mt-1" style="font-size: 11px;">Supported formats: JPG, PNG, WEBP, PDF (Max 5MB)</small>
                  </div>
                </div>

                <!-- Action Button (Centered Animated Border) -->
                <div class="mt-3 pt-3 border-top">
                  <div class="d-flex align-items-center justify-content-center">
                    <button type="submit" class="btn-animated-border btn-anim-blue" id="btn-save-bank-details" title="Save &amp; Update Verification" style="max-width: 320px; width: 100%;">
                      <span class="btn-anim-inner py-2 px-4 fs-14">
                        <i class="ti ti-device-floppy fs-18"></i>
                        <span>Save &amp; Update Verification</span>
                      </span>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Right Column: Document Preview -->
            <div class="col-lg-6">
              <div class="p-3 bg-white rounded-3 border h-100 shadow-sm d-flex flex-column">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <h6 class="fw-bold text-dark mb-0"><i class="ti ti-file-certificate text-primary me-1"></i> Document Preview</h6>
                  <a href="#" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2" id="bvm-open-doc-link" style="font-size:11px; display:none;">
                    <i class="ti ti-external-link"></i> Open Original
                  </a>
                </div>
                <div class="bank-doc-preview-box flex-grow-1 p-2" id="bvm-doc-preview-container" style="min-height: 380px;">
                  <!-- Preview image / PDF / Empty message -->
                </div>
              </div>
            </div>
          </div>

        </div>
        <div class="modal-footer bg-light border-top py-2 px-4">
          <button type="button" class="btn btn-secondary btn-sm fw-bold px-3" data-bs-dismiss="modal">Close</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
if (isset($_GET['vw']) && $_GET['vw'] == 1) {
    // no footer
} else {
    require('footer.php');
}
?>

<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
var routurl      = $('body').data('routurl') || '';
var companyid    = $('body').data('companyid');
var subcompanyid = $('body').data('subcompanyid');
var projectid    = $('body').data('projectid');
var loginname    = $('body').data('loginname') || "<?php echo $_SESSION['loginname'] ?? 'Admin'; ?>";
var key          = $('body').data('key') || "<?php echo $key ?? ''; ?>";
var mdid         = $('body').data('mdid') || "<?php echo $mdid ?? ($_GET['mdid'] ?? ''); ?>";

var allCalculatedRows = [];

$(document).ready(function() {
    // Set default dates: Date From as 1st of current month, Date To as today
    var today = new Date();
    var y = today.getFullYear();
    var m = String(today.getMonth() + 1).padStart(2, '0');
    var d = String(today.getDate()).padStart(2, '0');
    
    $('#ac-date-from').val(y + '-' + m + '-01');
    $('#ac-date-to').val(y + '-' + m + '-' + d);

    // Load locations
    LabourAttendanceLocationList('all', 'all', function(res) {
        var opts = '<option value="">— Choose Location —</option>';
        opts += '<option value="all">All Location</option>';
        if (res && res.length > 0) {
            for (var i = 0; i < res.length; i++) {
                opts += '<option value="' + res[i].LocationId + '">' + res[i].LocationName + '</option>';
            }
        }
        $('#ac-location').html(opts);
    });

    // Handle change of location to enable/disable load button
    $('#ac-location').on('change', function() {
        var val = $(this).val();
        if (val) {
            $('#ac-btn-load').prop('disabled', false);
        } else {
            $('#ac-btn-load').prop('disabled', true);
        }
        $('#ac-btn-confirm-save').hide();
    });

    // Bank Verification Modal: toggle un-verify reason section and update status badge
    $('#bvm-verifystatus').on('change', function() {
        var v = $(this).val();
        updateBvmStatusBadge(v);
        if (v === 'Pending') {
            $('#bvm-unverify-reason-container').slideDown(200);
        } else {
            $('#bvm-unverify-reason-container').slideUp(200);
        }
    });

    // Toggle Display Name input when Checkbox 3 is toggled
    $('#bvm-chk-mismatch-name').on('change', function() {
        if ($(this).is(':checked')) {
            $('#bvm-display-name-wrap').slideDown(200);
            $('#bvm-display-name').focus();
        } else {
            $('#bvm-display-name-wrap').slideUp(200);
        }
    });
});

function updateBvmStatusBadge(vStat) {
    if (vStat === 'Verified') {
        $('#bvm-status-badge').html('<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="ti ti-circle-check"></i> Verified &amp; Approved</span>');
    } else if (vStat === 'Rejected') {
        $('#bvm-status-badge').html('<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><i class="ti ti-circle-x"></i> Rejected</span>');
    } else {
        $('#bvm-status-badge').html('<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1"><i class="ti ti-alert-triangle"></i> Pending / Un-Verified</span>');
    }
}

var currentCardFilter = 'all';

function setCardFilter(filterName) {
    if (currentCardFilter === filterName && filterName !== 'all') {
        currentCardFilter = 'all';
    } else {
        currentCardFilter = filterName;
    }
    updateActiveFilterUI();
    renderCalculatedTable();
}

function updateActiveFilterUI() {
    $('.ac-summary-card').removeClass('active-card');
    $('#ac-warning-count-wrap').removeClass('ac-discrepancy-active');
    $('#ac-missing-out-wrap').removeClass('ac-missing-active');
    $('#badge-filter-full').removeClass('active-badge');
    $('#badge-filter-half').removeClass('active-badge');
    $('#badge-missing-bank').removeClass('active-badge');
    $('#badge-unverified-bank').removeClass('active-badge');
    $('#badge-updated-bank').removeClass('active-badge');

    if (currentCardFilter === 'discrepancy') {
        $('#ac-warning-count-wrap').addClass('ac-discrepancy-active');
    } else if (currentCardFilter === 'missing_checkout') {
        $('#ac-missing-out-wrap').addClass('ac-missing-active');
    } else if (currentCardFilter === 'missing_bank') {
        $('#card-filter-labour').addClass('active-card');
        $('#badge-missing-bank').addClass('active-badge');
    } else if (currentCardFilter === 'unverified_bank') {
        $('#card-filter-labour').addClass('active-card');
        $('#badge-unverified-bank').addClass('active-badge');
    } else if (currentCardFilter === 'updated_bank') {
        $('#card-filter-labour').addClass('active-card');
        $('#badge-updated-bank').addClass('active-badge');
    } else if (currentCardFilter === 'labour') {
        $('#card-filter-labour').addClass('active-card');
    } else if (currentCardFilter === 'checkins') {
        $('#card-filter-checkins').addClass('active-card');
    } else if (currentCardFilter === 'full_present') {
        $('#card-filter-present').addClass('active-card');
        $('#badge-filter-full').addClass('active-badge');
    } else if (currentCardFilter === 'half_present') {
        $('#card-filter-present').addClass('active-card');
        $('#badge-filter-half').addClass('active-badge');
    } else if (currentCardFilter === 'payable') {
        $('#card-filter-payable').addClass('active-card');
    }
}

function loadCalculatedAttendance() {
    var dateFrom   = $('#ac-date-from').val();
    var dateTo     = $('#ac-date-to').val();
    var locationid = $('#ac-location').val() || 'all';
    $("#btn-export-missing-count").text(0);
    $("#btn-export-unverified-count").text(0);
    $("#btn-export-updated-count").text(0);
    if (!dateFrom || !dateTo) {
        Swal.fire({ icon: 'warning', title: 'Required Fields', text: 'Please select both Date From and Date To.', confirmButtonColor: '#1b7a48' });
        return;
    }
    $('#ac-tbody').html('<tr><td colspan="9"><div class="ac-loading"><div class="ac-spinner"></div><p>Calculating attendance sheets…</p></div></td></tr>');
    $('#ac-btn-confirm-save').hide();
    currentCardFilter = 'all';
    updateActiveFilterUI();
    $.ajax({
        type: 'POST',
        url: routurl + 'modal/hrm_laboursalarymodal_fixed.php?action=CalculateWagesAttendance',
        data: {
            companyid:    companyid,
            subcompanyid: subcompanyid,
            projectid:    projectid,
            locationid:   locationid,
            dateFrom:     dateFrom,
            dateTo:       dateTo
        },
        success: function(res) {
            console.log(res);
            var data = [];
            try { data = (typeof res === 'string') ? JSON.parse(res) : res; } catch(e) {
                console.error('Parse error', e, res);
            }
            if (data.status && data.status === 'error') {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                resetCalcTable();
                return;
            }
            allCalculatedRows = data;
            renderCalculatedTable();
        },
        error: function(xhr, status, error) {
            console.error('CalculateWagesAttendance failed:', status, error, xhr.responseText);
            var errMsg = 'Could not calculate attendance sheets.';
            try {
                if (xhr.responseText) {
                    var parsed = JSON.parse(xhr.responseText);
                    if (parsed && parsed.message) {
                        errMsg = parsed.message;
                    }
                }
            } catch(e) {}
            Swal.fire({ icon: 'error', title: 'Request Failed', text: errMsg });
            resetCalcTable();
        }
    });
}

function renderCalculatedTable() {
    var searchVal = ($('#ac-search').val() || '').toLowerCase().trim();
    var showZeroDays = $('#ac-show-zero-days').is(':checked');
    var selectedLocation = $('#ac-location').val();
    
    // Update label text dynamically
    $('#ac-show-zero-days-text').text(showZeroDays ? 'Show 0 Payable Days' : 'Hide 0 Payable Days');

    var baseData = allCalculatedRows;

    if (searchVal) {
        baseData = baseData.filter(function(r) {
            return (r.FullName || '').toLowerCase().includes(searchVal) ||
                   (r.EmployeeID || '').toLowerCase().includes(searchVal);
        });
    }

    if (!showZeroDays) {
        baseData = baseData.filter(function(r) {
            var payable = parseFloat(r.PresentDays || 0) + (parseFloat(r.HalfPresentDays || 0) * 0.5);
            return payable > 0;
        });
    }

    // Calculate Summary Card Metrics from baseData
    var sumTotalLabour = baseData.length;
    var sumCheckins = 0;
    var sumFullPresent = 0;
    var sumHalfPresent = 0;
    var sumPayableDays = 0.0;
    var warningCount = 0;
    var sumMissingCheckouts = 0;
    var sumMissingBank = 0;
    var sumUnverifiedBank = 0;
    var sumUpdatedBank = 0;

    baseData.forEach(function(r) {
        var pDays = parseFloat(r.PresentDays || 0);
        var hDays = parseFloat(r.HalfPresentDays || 0);
        var payable = pDays + (hDays * 0.5);
        var totalCheckins = parseInt(r.TotalCheckins || 0);
        var missingOut = parseInt(r.MissingCheckouts || 0);

        sumCheckins += totalCheckins;
        sumFullPresent += pDays;
        sumHalfPresent += hDays;
        sumPayableDays += payable;
        sumMissingCheckouts += missingOut;

        var isBankVerified = r.HasBankDetails && (r.VerifyStatus || '').toLowerCase() === 'verified';
        var isBankUnverified = r.HasBankDetails && !isBankVerified;

        if (!r.HasBankDetails) {
            sumMissingBank++;
        } else {
            sumUpdatedBank++;
            if (isBankUnverified) {
                sumUnverifiedBank++;
            }
        }

        if (Math.abs(totalCheckins - payable) > 0.001) {
            warningCount++;
        }
    });

    // Update Summary Cards UI
    $('#ac-sum-total-labour').text(sumTotalLabour);
    $('#ac-sum-total-checkins').text(sumCheckins);
    $('#ac-sum-full-present').text(sumFullPresent);
    $('#ac-sum-half-present').text(sumHalfPresent);
    $('#ac-sum-payable-days').text(sumPayableDays.toFixed(1));

    if (sumTotalLabour > 0) {
        $('#ac-summary-cards').show();
        $('#btn-export-calculated').show();
    } else {
        $('#ac-summary-cards').hide();
        $('#btn-export-calculated').hide();
    }

    if (sumMissingBank > 0) {
        $('#ac-missing-bank-count').text(sumMissingBank);
        $('#btn-export-missing-count').text(sumMissingBank);
        $('#badge-missing-bank').show();
        $('#btn-export-missing-bank').show();
    } else {
        $('#badge-missing-bank').hide();
        $('#btn-export-missing-bank').hide();
    }

    if (sumUnverifiedBank > 0) {
        $('#ac-unverified-bank-count').text(sumUnverifiedBank);
        $('#btn-export-unverified-count').text(sumUnverifiedBank);
        $('#badge-unverified-bank').show();
        $('#btn-export-unverified-bank').show();
    } else {
        $('#badge-unverified-bank').hide();
        $('#btn-export-unverified-bank').hide();
    }

    if (sumUpdatedBank > 0) {
        $('#ac-updated-bank-count').text(sumUpdatedBank);
        $('#btn-export-updated-count').text(sumUpdatedBank);
        $('#badge-updated-bank').show();
        $('#btn-export-updated-bank').show();
    } else {
        $('#badge-updated-bank').hide();
        $('#btn-export-updated-bank').hide();
    }

    if (warningCount > 0) {
        $('#ac-warning-count').text(warningCount);
        $('#ac-warning-count-wrap').css('display', 'inline-flex');
    } else {
        $('#ac-warning-count-wrap').hide();
    }

    if (sumMissingCheckouts > 0) {
        $('#ac-missing-out-count').text(sumMissingCheckouts);
        $('#ac-missing-out-wrap').css('display', 'inline-flex');
    } else {
        $('#ac-missing-out-wrap').hide();
    }

    // Filter table dataset based on active card filter
    var filteredData = baseData;
    if (currentCardFilter === 'discrepancy') {
        filteredData = baseData.filter(function(r) {
            var payable = parseFloat(r.PresentDays || 0) + (parseFloat(r.HalfPresentDays || 0) * 0.5);
            var totalCheckins = parseInt(r.TotalCheckins || 0);
            return Math.abs(totalCheckins - payable) > 0.001;
        });
    } else if (currentCardFilter === 'missing_checkout') {
        filteredData = baseData.filter(function(r) {
            return parseInt(r.MissingCheckouts || 0) > 0;
        });
    } else if (currentCardFilter === 'missing_bank') {
        filteredData = baseData.filter(function(r) {
            return !r.HasBankDetails;
        });
    } else if (currentCardFilter === 'unverified_bank') {
        filteredData = baseData.filter(function(r) {
            return r.HasBankDetails && (r.VerifyStatus || '').toLowerCase() !== 'verified';
        });
    } else if (currentCardFilter === 'updated_bank') {
        filteredData = baseData.filter(function(r) {
            return !!r.HasBankDetails;
        });
    } else if (currentCardFilter === 'checkins') {
        filteredData = baseData.filter(function(r) {
            return parseInt(r.TotalCheckins || 0) > 0;
        });
    } else if (currentCardFilter === 'full_present') {
        filteredData = baseData.filter(function(r) {
            return parseFloat(r.PresentDays || 0) > 0;
        });
    } else if (currentCardFilter === 'half_present') {
        filteredData = baseData.filter(function(r) {
            return parseFloat(r.HalfPresentDays || 0) > 0;
        });
    } else if (currentCardFilter === 'payable') {
        filteredData = baseData.filter(function(r) {
            var payable = parseFloat(r.PresentDays || 0) + (parseFloat(r.HalfPresentDays || 0) * 0.5);
            return payable > 0;
        });
    }

    var tbody = '';
    var count = 0;

    filteredData.forEach(function(r, i) {
        var pDays = parseFloat(r.PresentDays || 0);
        var hDays = parseFloat(r.HalfPresentDays || 0);
        var payable = pDays + (hDays * 0.5);
        var totalCheckins = parseInt(r.TotalCheckins || 0);
        var missingOut = parseInt(r.MissingCheckouts || 0);

        var isDiff = Math.abs(totalCheckins - payable) > 0.001;
        var rowClass = isDiff ? 'class="ac-warning-row"' : '';
        var missingBadge = missingOut > 0 ? ' <span class="badge bg-warning text-dark ms-1" style="font-size:10px; font-weight:600;" title="' + missingOut + ' Missing Check-Out(s)"><i class="ti ti-clock-off"></i> Missing Out (' + missingOut + ')</span>' : '';
        
        var bankBadge = '';
        if (!r.HasBankDetails) {
            bankBadge = ' <span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1" style="font-size:10px; font-weight:600; padding:2px 6px; border-radius:4px;" title="Missing Bank Account Details"><i class="ti ti-building-bank"></i> No Bank</span>';
        } else {
            var isVerified = (r.VerifyStatus || '').toLowerCase() === 'verified';
            if (isVerified) {
                bankBadge = ' <span class="badge bg-success-subtle text-success border border-success-subtle ms-1" role="button" onclick="event.stopPropagation(); event.preventDefault(); openBankVerifyModal(\'' + r.EmployeeID + '\');" style="font-size:10px; font-weight:600; padding:2px 6px; border-radius:4px; cursor:pointer;" title="Bank Verified: ' + htmlEscape(r.BankName || '') + ' | A/C: ' + htmlEscape(r.AccountNumber || '') + ' (Click to review/edit)"><i class="ti ti-circle-check"></i> Bank Verified</span>';
            } else {
                var unverifyRemark = (r.VerifyRemark || r.verifyremark || '').trim();
                var unverifyLabel = unverifyRemark ? 'Un-Verified (' + htmlEscape(unverifyRemark) + ')' : 'Un-Verified';
                bankBadge = ' <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle ms-1" role="button" onclick="event.stopPropagation(); event.preventDefault(); openBankVerifyModal(\'' + r.EmployeeID + '\');" style="font-size:10px; font-weight:600; padding:2px 6px; border-radius:4px; cursor:pointer;" title="Bank Un-Verified: ' + htmlEscape(r.BankName || '') + ' | A/C: ' + htmlEscape(r.AccountNumber || '') + (unverifyRemark ? ' | Remark: ' + htmlEscape(unverifyRemark) : '') + ' (Click to verify/edit)"><i class="ti ti-alert-triangle"></i> ' + unverifyLabel + '</span>';
            }
        }

        var labourDashUrl = '../labourdash/index.php?empid=' + encodeURIComponent(r.EmployeeID || '') + '&k=' + encodeURIComponent(key) + '&mdid=' + encodeURIComponent(mdid);

        tbody += '<tr data-labourid="' + r.labourid + '" ' + rowClass + '>' +
            '<td style="color:var(--att-gray);font-size:11px;">' + (i + 1) + '</td>' +
            '<td><a href="' + labourDashUrl + '" target="_blank" class="ac-labour-link" title="Click to view or correct in Labour Dashboard"><div class="ac-labour-name">' + htmlEscape(r.FullName || '—') + missingBadge + bankBadge + ' <i class="ti ti-external-link fs-12 ms-1 text-primary opacity-75"></i></div><div class="ac-labour-id">' + htmlEscape(r.EmployeeID || '') + '</div></a></td>' +
            '<td style="font-weight:600;">' + totalCheckins + '</td>' +
            '<td><span class="val-present-days" style="font-weight:600;color:#333;">' + (r.PresentDays || 0) + '</span></td>' +
            '<td><span class="val-half-days" style="font-weight:600;color:#333;">' + (r.HalfPresentDays || 0) + '</span></td>' +
            '<td><span class="val-payable-days" onclick="showAttendanceDetails(\'' + r.EmployeeID + '\', \'' + htmlEscape(r.FullName || '') + '\', \'' + htmlEscape(r.EmployeeID || '') + '\')" style="font-weight:700;color:var(--att-green);cursor:pointer;text-decoration:underline;" title="Click to view details">' + payable.toFixed(1) + '</span></td>' +
            '</tr>';
        count++;
    });

    $('#ac-tbody').html(count > 0 ? tbody : '<tr><td colspan="6"><div class="ac-empty"><i class="ti ti-user-off"></i><p>No matching labour records found.</p></div></td></tr>');
    $('#ac-record-count').text(count);

    // Discrepancy & Confirm & Save Button Visibility Logic
    var $statusBanner = $('#ac-status-banner-wrap');
    if (sumTotalLabour > 0) {
        if (!selectedLocation || selectedLocation === 'all') {
            $('#ac-btn-confirm-save').hide();
            $statusBanner.html(
                '<div class="alert alert-info border-0 shadow-sm d-flex align-items-center gap-2 mb-3" style="border-radius: 10px; font-size: 13px; font-weight: 600; border-left: 5px solid #0284c7; background-color: #f0f9ff; color: #075985; padding: 12px 16px;">' +
                    '<i class="ti ti-info-circle fs-5" style="color: #0284c7;"></i>' +
                    '<div><strong>All Location View (Read-Only):</strong> Displaying attendance across all locations. Select a specific location to Confirm &amp; Save records.</div>' +
                '</div>'
            ).show();
        } else if (warningCount > 0) {
            // $('#ac-btn-confirm-save').hide();
            $('#ac-btn-confirm-save').show();
            $statusBanner.html(
                '<div class="alert alert-danger border-0 shadow-sm d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3" style="border-radius: 10px; font-size: 13px; font-weight: 600; border-left: 5px solid #dc2626; background-color: #fef2f2; color: #991b1b; padding: 12px 16px;">' +
                    '<div class="d-flex align-items-center gap-2">' +
                        '<i class="ti ti-alert-triangle fs-5" style="color: #dc2626;"></i>' +
                        '<div><strong>Discrepancy Detected (' + warningCount + '):</strong> Check-Ins and Payable Days differ for <strong>' + warningCount + '</strong> record(s). <span class="fw-normal text-dark">Confirm &amp; Save is hidden.</span></div>' +
                    '</div>' +
                    '<button type="button" class="btn btn-danger btn-sm fw-bold shadow-sm d-inline-flex align-items-center gap-1" onclick="openDiscrepancyCorrectionModal()" style="border-radius:8px; font-size:12.5px; padding:6px 14px;">' +
                        '<i class="ti ti-edit"></i> Correction of Discrepancy Entry' +
                    '</button>' +
                '</div>'
            ).show();
        } else {
            $('#ac-btn-confirm-save').show();
            $statusBanner.html(
                '<div class="alert alert-success border-0 shadow-sm d-flex align-items-center gap-2 mb-3" style="border-radius: 10px; font-size: 13px; font-weight: 600; border-left: 5px solid #16a34a; background-color: #f0fdf4; color: #166534; padding: 12px 16px;">' +
                    '<i class="ti ti-circle-check fs-5" style="color: #16a34a;"></i>' +
                    '<div><strong>0 Discrepancies Found:</strong> All attendance records match cleanly. You can now click <strong>Confirm &amp; Save</strong>.</div>' +
                '</div>'
            ).show();
        }
    } else {
        $('#ac-btn-confirm-save').hide();
        $statusBanner.hide().empty();
    }

    updateActiveFilterUI();
}

function recalculateRow(labourid, inputEl) {
    var $row = $(inputEl).closest('tr');
    var pDays = parseFloat($row.find('.val-present-days').val()) || 0;
    var hDays = parseFloat($row.find('.val-half-days').val()) || 0;
    var payable = pDays + (hDays * 0.5);
    $row.find('.val-payable-days').text(payable.toFixed(1));
}
$('#ac-date-from').on('change', function () {
    const value = this.value;
    $('#ac-date-to').val('');

    if (!value) return;

    const [year, month, day] = value.split('-').map(Number);

    if (day !== 1 && day !== 16) {
        alertmessage('warning','Start date must be the 1st or 16th of the month.');
        this.value = '';
        return;
    }

    const endDay = day === 1
        ? 15
        : new Date(year, month, 0).getDate();

    const d2 = `${year}-${String(month).padStart(2, '0')}-${String(endDay).padStart(2, '0')}`;

    $('#ac-date-to').val(d2).attr({
        min: d2,
        max: d2
    });
});
$('#ac-date-from').on('input change', function () {
    const value = this.value;
    $('#ac-date-to').val('').removeAttr('min max');

    if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) return;

    const [year, month, day] = value.split('-').map(Number);

    if (day !== 1 && day !== 16) {
        this.setCustomValidity('Start date must be the 1st or 16th.');
        return;
    }

    this.setCustomValidity('');

    const endDay = day === 1
        ? 15
        : new Date(year, month, 0).getDate();

    const d2 = `${year}-${String(month).padStart(2, '0')}-${String(endDay).padStart(2, '0')}`;

    $('#ac-date-to').val(d2).attr({
        min: d2,
        max: d2
    });
});
function saveConfirmedAttendance() {
    const dateFrom   = $('#ac-date-from').val();
    const dateTo     = $('#ac-date-to').val();
    const locationid = $('#ac-location').val();
    const  locationname =$('#ac-location option:selected').text();

    if (!locationid || locationid === 'all') {
        Swal.fire({
            icon: 'warning',
            title: 'Location Mandatory',
            text: 'Please select a specific location to Confirm & Save attendance.'
        });
        return;
    }

    if (!dateFrom || !dateTo) { 
        Swal.fire({
            icon: 'warning',
            title: 'Date Mandatory',
            text: 'Please select both start and end dates'
        });
        $( !dateFrom ? '#ac-date-from' : '#ac-date-to' ).focus();
        return;
    }

    const [year, month, day] = dateFrom.split('-').map(Number);

    if (day !== 1 && day !== 16) { 
        Swal.fire({
            icon: 'warning',
            title: 'Start Date',
            text: 'Attendance Calculation Start date must be the 1st or 16th of the month.'
        });
        $('#ac-date-from').focus();
        return;
    }

    const endDay = day === 1
        ? 15
        : new Date(year, month, 0).getDate();

    const expectedDateTo =
        `${year}-${String(month).padStart(2, '0')}-${String(endDay).padStart(2, '0')}`;

    if (dateTo !== expectedDateTo) {
        alertmessage(
            'warning',
            day === 1
                ? 'End date must be the 15th of the same month.'
                : 'End date must be the last day of the same month.'
        );
        $('#ac-date-to').focus();
        return;
    }
    
    var items = [];
    $('#ac-tbody tr').each(function() {
        var $row = $(this);
        var labourid = $row.data('labourid');
        if (!labourid) return;

        items.push({
            labourid:    labourid,
            presentDays: parseFloat($row.find('.val-present-days').text()) || 0,
            halfDays:    parseFloat($row.find('.val-half-days').text()) || 0,
            remark:      ''
        });
    });

    if (items.length === 0) return;

const indianDate = date => date.split('-').reverse().join('-');

const locationText = locationid === 'all'
    ? 'all locations'
    : `location "${locationname}"`;

const message =`Are you sure you want to save the calculated attendance ` +`from ${indianDate(dateFrom)} to ${indianDate(dateTo)} ` +`for ${locationText}?`;

    Swal.fire({
        title: 'Confirm Attendance?',
        text:message,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#1b7a48',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, Save'
    }).then((result) => {
        if (result.isConfirmed) {
            showSavingState();
            $.ajax({
                type: 'POST',
                url: routurl + 'modal/hrm_laboursalarymodal_fixed.php?action=saveconfirmedcalculatedattendance',
                data: {
                    companyid:    companyid,
                    subcompanyid: subcompanyid,
                    projectid:    projectid,
                    locationid:   $('#ac-location').val() || '',
                    dateFrom:     dateFrom,
                    dateTo:       dateTo,
                    calculatedattdata:       JSON.stringify(items)
                },
                success: function(raw) {
                    var res = {};
                    try { res = JSON.parse(raw); } catch(e){}
                    if (res.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Saved',
                            text: 'Calculated Attendance saved successfully!'
                        }).then(() => {
                            Swal.fire({
                                title: 'Wages Calculation',
                                text: 'Want to Proceed for Wages Calculation?',
                                icon: 'question',
                                showCancelButton: true,
                                confirmButtonColor: '#1b7a48',
                                cancelButtonColor: '#d33',
                                confirmButtonText: 'Yes, Proceed',
                                cancelButtonText: 'No'
                            }).then((wagesResult) => {
                                if (wagesResult.isConfirmed) {
                                    var loc = $('#ac-location').val() || 'all';
                                    window.location.href = `labour_calculatewages.php?k=${key}&locationid=${loc}&dateFrom=${dateFrom}&dateTo=${dateTo}`;
                                } else {
                                    loadCalculatedAttendance();
                                }
                            });
                        });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Save Failed', text: res.message || 'Error saving details' });
                    }
                },
                error: function() {
                    Swal.fire({ icon: 'error', title: 'Network Error', text: 'Request failed. Please try again.' });
                }
            });
        }
    });
}

function showSavingState() {
    Swal.fire({
        title: 'Saving...',
        text: 'Please wait while we record attendance sheets.',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
}

function filterCalculatedTable() {
    renderCalculatedTable();
}

function resetCalcTable() {
    $('#ac-tbody').html('<tr><td colspan="7"><div class="ac-empty"><i class="ti ti-calendar-off"></i><p>Failed to load data. Please try again.</p></div></td></tr>');
    $('#ac-record-count').text(0);
    currentCardFilter = 'all';
    updateActiveFilterUI();
    $('#ac-summary-cards').hide();
    $('#ac-btn-confirm-save').hide();
    $('#ac-status-banner-wrap').hide().empty();
}

function htmlEscape(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function downloadLabourAttendanceExport(filename, reportTitle, sheetName, dataset) {
    var dateFrom = $('#ac-date-from').val() || '';
    var dateTo   = $('#ac-date-to').val() || '';
    var locText  = $('#ac-location option:selected').text() || 'All Locations';

    var aoa = [
        [reportTitle],
        ["Date Range: " + dateFrom + " to " + dateTo, "Location: " + locText, "Total Records: " + dataset.length],
        [],
        ["S.No", "Labour ID", "Labour Name", "Total Check-Ins", "Full Present Days", "Half-Present Days", "Payable Days", "Bank Name", "Account No", "IFSC Code", "Bank Verification Status"]
    ];

    dataset.forEach(function(r, idx) {
        var pDays = parseFloat(r.PresentDays || 0);
        var hDays = parseFloat(r.HalfPresentDays || 0);
        var payable = pDays + (hDays * 0.5);
        var accNo = (r.AccountNumber !== undefined && r.AccountNumber !== null) ? String(r.AccountNumber).trim() : '';
        var unvRem = (r.VerifyRemark || r.verifyremark || '').trim();
        var verifyStat = !r.HasBankDetails ? 'No Bank' : ((r.VerifyStatus || '').toLowerCase() === 'verified' ? 'Verified' : (unvRem ? 'Un-Verify (' + unvRem + ')' : 'Un-Verify'));

        aoa.push([
            idx + 1,
            String(r.EmployeeID || ''),
            String(r.FullName || ''),
            parseInt(r.TotalCheckins || 0),
            pDays,
            hDays,
            payable.toFixed(1),
            String(r.BankName || ''),
            accNo,
            String(r.IFSCCode || ''),
            verifyStat
        ]);
    });

    if (typeof XLSX !== 'undefined') {
        var wb = XLSX.utils.book_new();
        var ws = XLSX.utils.aoa_to_sheet(aoa, { raw: true });

        // Set column widths
        ws['!cols'] = [
            { wch: 6 },  // S.No
            { wch: 14 }, // Labour ID
            { wch: 26 }, // Labour Name
            { wch: 16 }, // Total Check-Ins
            { wch: 18 }, // Full Present Days
            { wch: 18 }, // Half-Present Days
            { wch: 14 }, // Payable Days
            { wch: 22 }, // Bank Name
            { wch: 22 }, // Account No
            { wch: 15 }, // IFSC Code
            { wch: 24 }  // Bank Verification Status
        ];

        // Format data rows explicitly as text for Account No (col index 8), Labour ID (col index 1), IFSC (col index 9)
        if (ws['!ref']) {
            var range = XLSX.utils.decode_range(ws['!ref']);
            for (var R = 3; R <= range.e.r; ++R) {
                var cellRefEmp = XLSX.utils.encode_cell({ c: 1, r: R });
                if (ws[cellRefEmp]) {
                    ws[cellRefEmp].t = 's';
                    ws[cellRefEmp].z = '@';
                }
                var cellRefAcc = XLSX.utils.encode_cell({ c: 8, r: R });
                if (ws[cellRefAcc]) {
                    ws[cellRefAcc].t = 's';
                    ws[cellRefAcc].z = '@';
                    if (ws[cellRefAcc].v !== undefined && ws[cellRefAcc].v !== null) {
                        ws[cellRefAcc].v = String(ws[cellRefAcc].v);
                    }
                }
                var cellRefIfsc = XLSX.utils.encode_cell({ c: 9, r: R });
                if (ws[cellRefIfsc]) {
                    ws[cellRefIfsc].t = 's';
                    ws[cellRefIfsc].z = '@';
                }
            }
        }

        XLSX.utils.book_append_sheet(wb, ws, sheetName);
        XLSX.writeFile(wb, filename + '.xlsx');
    } else {
        var csvContent = "\uFEFF";
        aoa.forEach(function(row) {
            var escaped = row.map(function(field) {
                var str = (field === null || field === undefined) ? '' : String(field);
                return '"' + str.replace(/"/g, '""') + '"';
            }).join(',');
            csvContent += escaped + "\r\n";
        });
        var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement("a");
        var url = URL.createObjectURL(blob);
        link.setAttribute("href", url);
        link.setAttribute("download", filename + '.csv');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

function exportMissingBankDetails() {
    var missingRows = allCalculatedRows.filter(function(r) {
        return !r.HasBankDetails;
    });

    if (missingRows.length === 0) {
        Swal.fire({
            icon: 'info',
            title: 'No Missing Bank Details',
            text: 'All labours in this attendance period have valid bank details.'
        });
        return;
    }

    var dateFrom = $('#ac-date-from').val() || '';
    var dateTo = $('#ac-date-to').val() || '';
    var locText = ($('#ac-location option:selected').text() || 'All_Locations').trim().replace(/[^a-zA-Z0-9_-]/g, '_');
    var filename = 'Missing_Bank_Details_Labours_' + locText + '_' + dateFrom + '_to_' + dateTo;

    downloadLabourAttendanceExport(filename, "Missing Bank Details - Labour List", "Missing Bank Labours", missingRows);

    Swal.fire({
        icon: 'success',
        title: 'Export Complete',
        text: 'Downloaded list of ' + missingRows.length + ' labours with missing bank details.',
        timer: 2000,
        showConfirmButton: false
    });
}

function exportUnverifiedBankDetails() {
    var unverifiedRows = allCalculatedRows.filter(function(r) {
        return r.HasBankDetails && (r.VerifyStatus || '').toLowerCase() !== 'verified';
    });

    if (unverifiedRows.length === 0) {
        Swal.fire({
            icon: 'info',
            title: 'No Un-Verified Bank Details',
            text: 'All labours with bank details in this attendance period are verified.'
        });
        return;
    }

    var dateFrom = $('#ac-date-from').val() || '';
    var dateTo = $('#ac-date-to').val() || '';
    var locText = ($('#ac-location option:selected').text() || 'All_Locations').trim().replace(/[^a-zA-Z0-9_-]/g, '_');
    var filename = 'Unverified_Bank_Labours_' + locText + '_' + dateFrom + '_to_' + dateTo;

    downloadLabourAttendanceExport(filename, "Un-Verified Bank Details - Labour List", "Unverified Bank Labours", unverifiedRows);

    Swal.fire({
        icon: 'success',
        title: 'Export Complete',
        text: 'Downloaded list of ' + unverifiedRows.length + ' labours with un-verified bank details.',
        timer: 2000,
        showConfirmButton: false
    });
}

function exportUpdatedBankDetails() {
    var updatedRows = allCalculatedRows.filter(function(r) {
        return !!r.HasBankDetails;
    });

    if (updatedRows.length === 0) {
        Swal.fire({
            icon: 'info',
            title: 'No Bank Updated Labours',
            text: 'No labours with updated bank details found in this attendance period.'
        });
        return;
    }

    var dateFrom = $('#ac-date-from').val() || '';
    var dateTo = $('#ac-date-to').val() || '';
    var locText = ($('#ac-location option:selected').text() || 'All_Locations').trim().replace(/[^a-zA-Z0-9_-]/g, '_');
    var filename = 'Bank_Updated_Labours_' + locText + '_' + dateFrom + '_to_' + dateTo;

    downloadLabourAttendanceExport(filename, "Bank Details Updated - Labour List", "Bank Updated Labours", updatedRows);

    Swal.fire({
        icon: 'success',
        title: 'Export Complete',
        text: 'Downloaded list of ' + updatedRows.length + ' labours with bank details.',
        timer: 2000,
        showConfirmButton: false
    });
}

function openBankVerifyModal(empId) {
    var r = allCalculatedRows.find(function(item) {
        return String(item.EmployeeID) === String(empId) || String(item.labourid) === String(empId);
    });
    if (!r) {
        Swal.fire({ icon: 'error', title: 'Not Found', text: 'Labour record not found.' });
        return;
    }

    // Reset file input
    $('#bvm-bank-doc').val('');

    $('#bvm-labour-id').val(r.EmployeeID || r.labourid || '');
    $('#bvm-bank-id').val(r.BankId || '');
    $('#bvm-labour-info').text('Labour ID: ' + (r.EmployeeID || '-') + ' | ' + (r.FullName || '-'));
    $('#bvm-labour-name').val((r.FullName || '-') + ' (' + (r.EmployeeID || '-') + ')');
    $('#bvm-bank-name').val(r.BankName || '');
    $('#bvm-account-number').val(r.AccountNumber || '');
    $('#bvm-ifsc-code').val(r.IFSCCode || '');

    var vStat = (r.VerifyStatus || 'Pending');
    if (vStat.toLowerCase() === 'verified') vStat = 'Verified';
    else if (vStat.toLowerCase() === 'rejected') vStat = 'Rejected';
    else vStat = 'Pending';
    $('#bvm-verifystatus').val(vStat);

    // Reset un-verify reason inputs
    $('#bvm-chk-mismatch-acc').prop('checked', false);
    $('#bvm-chk-mismatch-ifsc').prop('checked', false);
    $('#bvm-chk-mismatch-name').prop('checked', false);
    $('#bvm-display-name').val('');
    $('#bvm-display-name-wrap').hide();

    var curRemark = String(r.VerifyRemark || r.verifyremark || '');
    if (curRemark) {
        if (curRemark.indexOf('Mismatch Account No') !== -1) {
            $('#bvm-chk-mismatch-acc').prop('checked', true);
        }
        if (curRemark.indexOf('Mismatch IFSC Code') !== -1) {
            $('#bvm-chk-mismatch-ifsc').prop('checked', true);
        }
        if (curRemark.indexOf('Mismatch Account holder Name') !== -1) {
            $('#bvm-chk-mismatch-name').prop('checked', true);
            $('#bvm-display-name-wrap').show();
            var matchName = curRemark.match(/Mismatch Account holder Name\s*\(([^)]+)\)/i);
            if (matchName && matchName[1]) {
                $('#bvm-display-name').val(matchName[1].trim());
            }
        }
    }

    if (vStat === 'Pending') {
        $('#bvm-unverify-reason-container').show();
    } else {
        $('#bvm-unverify-reason-container').hide();
    }

    updateBvmStatusBadge(vStat);

    // Document Preview Handling
    var docPath = (r.CancelChequePath || '').trim();
    if (docPath) {
        var fullDocUrl = docPath.startsWith('http') ? docPath : (docPath.startsWith('../') ? docPath : '../' + docPath);
        var isPdf = docPath.toLowerCase().endsWith('.pdf');
        $('#bvm-open-doc-link').attr('href', fullDocUrl).show();

        if (isPdf) {
            $('#bvm-doc-preview-container').html('<iframe src="' + fullDocUrl + '#toolbar=0"></iframe>');
        } else {
            $('#bvm-doc-preview-container').html('<img src="' + fullDocUrl + '" alt="Bank Document" onerror="this.onerror=null; $(this).replaceWith(\'<div class=\\\'text-center text-muted p-4\\\'><i class=\\\'ti ti-file-broken fs-36 text-danger d-block mb-2\\\'></i>Failed to load document preview.</div>\');">');
        }
    } else {
        $('#bvm-open-doc-link').hide();
        $('#bvm-doc-preview-container').html('<div class="text-center text-muted p-4"><i class="ti ti-file-off fs-36 text-secondary d-block mb-2"></i><div class="fw-semibold">No Document Attached</div><div class="small text-muted">Cheque or passbook document has not been uploaded for this labour.</div></div>');
    }

    $('#bankVerificationModal').modal('show');
}

function previewSelectedDoc(input) {
    if (input.files && input.files[0]) {
        var file = input.files[0];
        var fileType = file.type || '';
        var fileName = file.name || '';
        var isPdf = fileType === 'application/pdf' || fileName.toLowerCase().endsWith('.pdf');

        if (isPdf) {
            var blobUrl = URL.createObjectURL(file);
            $('#bvm-open-doc-link').attr('href', blobUrl).show();
            $('#bvm-doc-preview-container').html(
                '<div class="w-100 h-100 d-flex flex-column">' +
                '<div class="badge bg-primary-subtle text-primary mb-2 align-self-start"><i class="ti ti-file-text me-1"></i>New PDF Selected (Click Save to Upload)</div>' +
                '<iframe src="' + blobUrl + '" class="flex-grow-1" style="min-height:340px; border-radius:8px;"></iframe>' +
                '</div>'
            );
        } else if (fileType.startsWith('image/')) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#bvm-open-doc-link').attr('href', e.target.result).show();
                $('#bvm-doc-preview-container').html(
                    '<div class="w-100 h-100 d-flex flex-column align-items-center">' +
                    '<div class="badge bg-primary-subtle text-primary mb-2 align-self-start"><i class="ti ti-photo me-1"></i>New Image Selected (Click Save to Upload)</div>' +
                    '<img src="' + e.target.result + '" alt="Preview" style="max-height:340px; max-width:100%; object-fit:contain; border-radius:8px;">' +
                    '</div>'
                );
            };
            reader.readAsDataURL(file);
        }
    }
}

function saveBankDetailsAndVerification(e) {
    if (e) e.preventDefault();

    var formEl = document.getElementById('bvmBankForm');
    var formData = new FormData(formEl);
    
    var bankId   = $('#bvm-bank-id').val();
    var labourId = $('#bvm-labour-id').val();
    var bankName = $('#bvm-bank-name').val().trim();
    var accNo    = $('#bvm-account-number').val().trim();
    var ifsc     = $('#bvm-ifsc-code').val().trim().toUpperCase();
    var vStatus  = $('#bvm-verifystatus').val();

    if (!bankName || !accNo || !ifsc) {
        Swal.fire({ icon: 'warning', title: 'Required Fields', text: 'Please fill in Bank Name, Account Number, and IFSC Code.' });
        return;
    }

    var verifyRemark = '';
    if (vStatus === 'Pending') {
        var isMismatchAcc  = $('#bvm-chk-mismatch-acc').is(':checked');
        var isMismatchIfsc = $('#bvm-chk-mismatch-ifsc').is(':checked');
        var isMismatchName = $('#bvm-chk-mismatch-name').is(':checked');

        if (!isMismatchAcc && !isMismatchIfsc && !isMismatchName) {
            Swal.fire({
                icon: 'warning',
                title: 'Reason Required',
                text: 'Please select at least 1 reason for Un-Verified Bank Details.'
            });
            $('#bvm-chk-mismatch-acc').focus();
            return;
        }

        var remarks = [];
        if (isMismatchAcc) {
            remarks.push('Mismatch Account No');
        }
        if (isMismatchIfsc) {
            remarks.push('Mismatch IFSC Code');
        }
        if (isMismatchName) {
            var displayName = $('#bvm-display-name').val().trim();
            if (!displayName) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Display Name Required',
                    text: 'Please enter "Display Name as per verified" when Mismatch Account holder Name is selected.'
                });
                $('#bvm-display-name').focus();
                return;
            }
            remarks.push('Mismatch Account holder Name (' + displayName + ')');
        }
        verifyRemark = remarks.join(', ');
    }

    formData.set('companyid', companyid || '');
    formData.set('subcompanyid', subcompanyid || '');
    formData.set('projectid', projectid || '');
    formData.set('verifyby', loginname || 'Admin');
    formData.set('currencycode', 'INR');
    formData.set('verifyremark', verifyRemark);

    Swal.fire({
        title: 'Saving Bank Details...',
        text: 'Please wait while details and document are being updated.',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    $.ajax({
        type: 'POST',
        url: routurl + 'modal/hrm_laboursalarymodal_fixed.php?action=SaveLabourBankDetailsFromVerification',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res) {
            if (res && res.status === 'success') {
                // Update in-memory record in allCalculatedRows
                allCalculatedRows.forEach(function(item) {
                    if (String(item.EmployeeID) === String(labourId) || String(item.labourid) === String(labourId) || (bankId && String(item.BankId) === String(bankId))) {
                        item.BankName = bankName;
                        item.AccountNumber = accNo;
                        item.IFSCCode = ifsc;
                        item.VerifyStatus = vStatus;
                        item.VerifyRemark = verifyRemark;
                        item.HasBankDetails = true;
                        if (res.bank_id) {
                            item.BankId = res.bank_id;
                        }
                        if (res.docPath) {
                            item.CancelChequePath = res.docPath;
                        }
                    }
                });

                $('#bankVerificationModal').modal('hide');
                renderCalculatedTable();

                Swal.fire({
                    icon: 'success',
                    title: 'Saved Successfully',
                    text: res.message || 'Bank details and document updated successfully.',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Save Failed',
                    text: (res && res.message) ? res.message : 'Could not save bank details.'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Save error:', error, xhr.responseText);
            Swal.fire({
                icon: 'error',
                title: 'Network Error',
                text: 'Failed to connect to server. Please try again.'
            });
        }
    });
}

function submitBankVerification(newStatus) {
    $('#bvm-verifystatus').val(newStatus).trigger('change');
    saveBankDetailsAndVerification();
}

function exportCalculatedAttendance() {
    if (allCalculatedRows.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No Data',
            text: 'Please load calculated attendance records first.'
        });
        return;
    }

    var dateFrom = $('#ac-date-from').val() || '';
    var dateTo = $('#ac-date-to').val() || '';
    var locText = ($('#ac-location option:selected').text() || 'All_Locations').trim().replace(/[^a-zA-Z0-9_-]/g, '_');
    var filename = 'Calculated_Attendance_' + locText + '_' + dateFrom + '_to_' + dateTo;

    downloadLabourAttendanceExport(filename, "Attendance Calculation Report", "Attendance Calculation", allCalculatedRows);
}

var currentDetailsParams = null;
var currentModalCardFilter = 'all';
var modalRawData = [];

function showAttendanceDetails(labourid, fullName, employeeId) {
    currentDetailsParams = { labourid: labourid, fullName: fullName, employeeId: employeeId };
    currentModalCardFilter = 'all';
    updateActiveModalCardUI();
    $('#acDetailsModal').modal('show');
    fetchAttendanceDetailsData();
}

function refreshAttendanceDetails() {
    if (currentDetailsParams) {
        fetchAttendanceDetailsData();
    }
}

function setModalCardFilter(filterName) {
    if (currentModalCardFilter === filterName && filterName !== 'all') {
        currentModalCardFilter = 'all';
    } else {
        currentModalCardFilter = filterName;
    }
    updateActiveModalCardUI();
    renderModalAttendanceTable();
}

function updateActiveModalCardUI() {
    $('.acd-summary-card').removeClass('active-card');
    if (currentModalCardFilter === 'all') {
        $('#acd-card-all').addClass('active-card');
    } else if (currentModalCardFilter === 'full') {
        $('#acd-card-full').addClass('active-card');
    } else if (currentModalCardFilter === 'half') {
        $('#acd-card-half').addClass('active-card');
    } else if (currentModalCardFilter === 'payable') {
        $('#acd-card-payable').addClass('active-card');
    }
}

function renderModalAttendanceTable() {
    if (!modalRawData) return;

    var filtered = modalRawData;
    if (currentModalCardFilter === 'full') {
        filtered = modalRawData.filter(function(r) { return r.type === 'Full Present'; });
    } else if (currentModalCardFilter === 'half') {
        filtered = modalRawData.filter(function(r) { return r.type === 'Half Present'; });
    } else if (currentModalCardFilter === 'payable') {
        filtered = modalRawData.filter(function(r) { return r.type === 'Full Present' || r.type === 'Half Present'; });
    }

    var html = '';
    if (filtered.length === 0) {
        html = '<tr><td colspan="5" class="text-center text-muted py-3">No matching attendance records found.</td></tr>';
    } else {
        filtered.forEach(function(r) {
            var typeBadge = '';
            if (r.type === 'Full Present') {
                typeBadge = '<span class="badge bg-success" style="font-size:11px;font-weight:600;">Full Present</span>';
            } else if (r.type === 'Half Present') {
                typeBadge = '<span class="badge bg-warning text-dark" style="font-size:11px;font-weight:600;">Half Present</span>';
            } else if (r.type === 'Mark-out Missing') {
                typeBadge = '<span class="badge bg-danger" style="font-size:11px;font-weight:600;">Mark-out Missing</span>';
            } else {
                typeBadge = '<span class="badge bg-secondary" style="font-size:11px;font-weight:600;">Absent</span>';
            }

            var outMnDisp = (!r.checkouttime_mn || r.checkouttime_mn === '—' || r.checkouttime_mn === '0000-00-00 00:00:00') 
                ? (r.checkintime_mn && r.checkintime_mn !== '—' ? '<span class="badge bg-danger-subtle text-danger" style="font-size:10px;font-weight:700;padding:1px 5px;border:1px solid #fecaca;" title="Punchout Missing">MIS</span>' : '—') 
                : r.checkouttime_mn;

            var outDpDisp = (!r.checkouttime_dp || r.checkouttime_dp === '—' || r.checkouttime_dp === '0000-00-00 00:00:00') 
                ? (r.checkintime_dp && r.checkintime_dp !== '—' ? '<span class="badge bg-danger-subtle text-danger" style="font-size:10px;font-weight:700;padding:1px 5px;border:1px solid #fecaca;" title="Punchout Missing">MIS</span>' : '—') 
                : r.checkouttime_dp;

            html += '<tr>' +
                '<td><strong>' + formatDateString(r.attendancedate) + '</strong></td>' +
                '<td><div style="font-size:11px;color:#64748b;">In: ' + r.checkintime_mn + ' | Out: ' + outMnDisp + '</div><div style="font-size:10px;font-weight:600;">Hrs: ' + r.workinghours_mn + '</div></td>' +
                '<td><div style="font-size:11px;color:#64748b;">In: ' + r.checkintime_dp + ' | Out: ' + outDpDisp + '</div><div style="font-size:10px;font-weight:600;">Hrs: ' + r.workinghours_dp + '</div></td>' +
                '<td style="font-weight:600;">' + r.max_hours.toFixed(1) + ' hrs</td>' +
                '<td>' + typeBadge + '</td>' +
                '</tr>';
        });
    }
    $('#acd-tbody').html(html);
}

function fetchAttendanceDetailsData() {
    if (!currentDetailsParams) return;
    var labourid   = currentDetailsParams.labourid;
    var fullName   = currentDetailsParams.fullName;
    var employeeId = currentDetailsParams.employeeId;
    var dateFrom   = $('#ac-date-from').val();
    var dateTo     = $('#ac-date-to').val();

    var labourDashUrl = '../labourdash/index.php?empid=' + encodeURIComponent(employeeId || '') + '&k=' + encodeURIComponent(key) + '&mdid=' + encodeURIComponent(mdid);
    var linkHtml = '<a href="' + labourDashUrl + '" target="_blank" class="text-primary text-decoration-none hover-underline" title="Click to view or correct in Labour Dashboard">' + htmlEscape(fullName) + ' (ID: ' + htmlEscape(employeeId) + ') <i class="ti ti-external-link fs-14 ms-1 opacity-75"></i></a>';

    $('#acd-labour-name').html(linkHtml);
    $('#acd-date-range').text('Range: ' + formatDateString(dateFrom) + ' to ' + formatDateString(dateTo));

    $('#acd-tbody').html('<tr><td colspan="5" class="text-center py-4"><span class="spinner-border spinner-border-sm me-2 text-primary"></span>Loading attendance details...</td></tr>');

    $.ajax({
        type: 'POST',
        url: routurl + 'modal/hrm_laboursalarymodal_fixed.php?action=GetLabourAttendanceDetails',
        data: {
            projectid:  projectid,
            employeeID: labourid,
            dateFrom:   dateFrom,
            dateTo:     dateTo
        },
        success: function(res) {
            var dataObj = {};
            try { dataObj = (typeof res === 'string') ? JSON.parse(res) : res; } catch(e){}
            if (dataObj.status === 'success' && dataObj.data) {
                modalRawData = dataObj.data;
                var totalLogs = modalRawData.length;
                var fullCount = 0;
                var halfCount = 0;
                var missingCount = 0;

                if (modalRawData.length === 0) {
                    $('#acd-summary-grid').hide();
                } else {
                    modalRawData.forEach(function(r) {
                        if (r.type === 'Full Present') {
                            fullCount++;
                        } else if (r.type === 'Half Present') {
                            halfCount++;
                        } else if (r.type === 'Mark-out Missing') {
                            missingCount++;
                        }
                    });

                    var payable = (fullCount * 1.0) + (halfCount * 0.5);
                    $('#acd-sum-total-logs').text(totalLogs);
                    $('#acd-sum-full-present').text(fullCount);
                    $('#acd-sum-half-present').text(halfCount);
                    $('#acd-sum-payable').text(payable.toFixed(1));
                    $('#acd-summary-grid').show();
                }
                renderModalAttendanceTable();
            } else {
                $('#acd-tbody').html('<tr><td colspan="5" class="text-center text-danger py-3">Failed to load data: ' + (dataObj.message || 'Unknown error') + '</td></tr>');
            }
        },
        error: function() {
            $('#acd-tbody').html('<tr><td colspan="5" class="text-center text-danger py-3">Connection error. Please try again.</td></tr>');
        }
    });
}

function formatDateString(dateStr) {
    if (!dateStr) return '';
    var parts = dateStr.split('-');
    if (parts.length === 3) {
        return parts[2] + '-' + parts[1] + '-' + parts[0];
    }
    return dateStr;
}

/* ── Discrepancy Correction Modal Logic ───────────────────────────── */
var discrepancyRawList = [];

function openDiscrepancyCorrectionModal() {
    var dateFrom = $('#ac-date-from').val();
    var dateTo   = $('#ac-date-to').val();
    var locationid = $('#ac-location').val() || 'all';

    $('#disc-search-input').val('');
    $('#disc-select-all').prop('checked', false);
    $('#disc-modal-tbody').html('<tr><td colspan="6" class="text-center py-4"><span class="spinner-border spinner-border-sm me-2 text-danger"></span>Loading discrepancy records...</td></tr>');
    $('#discrepancyCorrectionModal').modal('show');

    $.ajax({
        type: 'POST',
        url: routurl + 'modal/hrm_laboursalarymodal_fixed.php?action=GetDiscrepancyAttendanceList',
        data: {
            projectid:  projectid,
            locationid: locationid,
            dateFrom:   dateFrom,
            dateTo:     dateTo
        },
        success: function(res) {
            var dataObj = {};
            try { dataObj = (typeof res === 'string') ? JSON.parse(res) : res; } catch(e){}
            if (dataObj.status === 'success' && dataObj.data) {
                discrepancyRawList = dataObj.data;
                renderDiscrepancyModalTable(discrepancyRawList);
            } else {
                $('#disc-modal-tbody').html('<tr><td colspan="6" class="text-center text-danger py-3">Failed to load discrepancies: ' + (dataObj.message || 'Unknown error') + '</td></tr>');
            }
        },
        error: function() {
            $('#disc-modal-tbody').html('<tr><td colspan="6" class="text-center text-danger py-3">Connection error. Please try again.</td></tr>');
        }
    });
}

function renderDiscrepancyModalTable(list) {
    if (!list || list.length === 0) {
        $('#disc-modal-tbody').html('<tr><td colspan="6" class="text-center text-muted py-4"><i class="ti ti-circle-check text-success fs-3 d-block mb-1"></i>No discrepancy records found!</td></tr>');
        return;
    }

    var html = '';
    list.forEach(function(r) {
        var issueBadge = '';
        if (r.type === 'Mark-out Missing') {
            issueBadge = '<span class="badge bg-danger">Mark-out Missing</span>';
        } else if (r.max_hours === 0) {
            issueBadge = '<span class="badge bg-warning text-dark">0 Working Hours</span>';
        } else {
            issueBadge = '<span class="badge bg-secondary">' + htmlEscape(r.type) + '</span>';
        }

        var labourDashUrl = '../labourdash/index.php?empid=' + encodeURIComponent(r.EmployeeID || '') + '&k=' + encodeURIComponent(key) + '&mdid=' + encodeURIComponent(mdid);

            var outMnDisp = (!r.checkouttime_mn || r.checkouttime_mn === '—' || r.checkouttime_mn === '0000-00-00 00:00:00') 
                ? (r.checkintime_mn && r.checkintime_mn !== '—' ? '<span class="badge bg-danger-subtle text-danger" style="font-size:10px;font-weight:700;padding:1px 5px;border:1px solid #fecaca;" title="Punchout Missing">MIS</span>' : '—') 
                : r.checkouttime_mn;

            var outDpDisp = (!r.checkouttime_dp || r.checkouttime_dp === '—' || r.checkouttime_dp === '0000-00-00 00:00:00') 
                ? (r.checkintime_dp && r.checkintime_dp !== '—' ? '<span class="badge bg-danger-subtle text-danger" style="font-size:10px;font-weight:700;padding:1px 5px;border:1px solid #fecaca;" title="Punchout Missing">MIS</span>' : '—') 
                : r.checkouttime_dp;

            html += '<tr>' +
                '<td class="text-center"><input type="checkbox" class="disc-item-chk" value="' + r.attendanceid + '" data-labourname="' + htmlEscape(r.FullName) + '" data-date="' + r.attendancedate + '" style="cursor:pointer; width:16px; height:16px;"></td>' +
                '<td><strong>' + formatDateString(r.attendancedate) + '</strong></td>' +
                '<td><a href="' + labourDashUrl + '" target="_blank" class="text-primary text-decoration-none fw-bold">' + htmlEscape(r.FullName) + ' <i class="ti ti-external-link fs-12"></i></a><div class="text-muted small">ID: ' + htmlEscape(r.EmployeeID) + '</div></td>' +
                '<td><div style="font-size:11px;color:#64748b;">In: ' + r.checkintime_mn + ' | Out: ' + outMnDisp + '</div><div style="font-size:10px;font-weight:600;">Hrs: ' + r.workinghours_mn + '</div></td>' +
                '<td><div style="font-size:11px;color:#64748b;">In: ' + r.checkintime_dp + ' | Out: ' + outDpDisp + '</div><div style="font-size:10px;font-weight:600;">Hrs: ' + r.workinghours_dp + '</div></td>' +
                '<td>' + issueBadge + '</td>' +
                '</tr>';
    });

    $('#disc-modal-tbody').html(html);
}

function filterDiscrepancyModalTable() {
    var searchVal = ($('#disc-search-input').val() || '').toLowerCase().trim();
    if (!searchVal) {
        renderDiscrepancyModalTable(discrepancyRawList);
        return;
    }
    var filtered = discrepancyRawList.filter(function(r) {
        return (r.FullName || '').toLowerCase().includes(searchVal) ||
               (r.EmployeeID || '').toLowerCase().includes(searchVal) ||
               (r.attendancedate || '').toLowerCase().includes(searchVal);
    });
    renderDiscrepancyModalTable(filtered);
}

function toggleSelectAllDiscrepancies(masterChk) {
    var isChecked = $(masterChk).is(':checked');
    $('#disc-modal-tbody .disc-item-chk').prop('checked', isChecked);
}

function getSelectedDiscrepancyIDs() {
    var selected = [];
    $('#disc-modal-tbody .disc-item-chk:checked').each(function() {
        selected.push($(this).val());
    });
    return selected;
}

function removeSelectedAttendanceEntries() {
    var selected = getSelectedDiscrepancyIDs();
    if (selected.length === 0) {
        Swal.fire({ icon: 'warning', title: 'No Selection', text: 'Please select at least 1 attendance entry to remove.' });
        return;
    }

    $(document).off('focusin.bs.modal');

    Swal.fire({
        title: 'Remove ' + selected.length + ' Entry(s)?',
        text: 'Enter mandatory remark/reason for removing selected attendance record(s):',
        input: 'textarea',
        inputPlaceholder: 'Reason for removal (required)...',
        inputAttributes: { 'aria-label': 'Reason for removal' },
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        confirmButtonText: 'Yes, Remove Entries',
        cancelButtonText: 'Cancel',
        target: '#discrepancyCorrectionModal',
        didOpen: () => {
            $(document).off('focusin.bs.modal');
            setTimeout(function() {
                $('.swal2-textarea').focus();
            }, 100);
        },
        inputValidator: (value) => {
            if (!value || !value.trim()) {
                return 'Remark is required to remove attendance entries!';
            }
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            Swal.fire({ title: 'Removing...', allowOutsideClick: false, target: '#discrepancyCorrectionModal', didOpen: () => Swal.showLoading() });
            $.ajax({
                type: 'POST',
                url: routurl + 'modal/hrm_laboursalarymodal_fixed.php?action=BatchRemoveAttendanceEntries',
                data: {
                    attendanceids: selected,
                    remark:        result.value.trim(),
                    loginname:     (typeof loginname !== 'undefined' && loginname ? loginname : 'Admin')
                },
                success: function(res) {
                    var dataObj = {};
                    try { dataObj = (typeof res === 'string') ? JSON.parse(res) : res; } catch(e){}
                    if (dataObj.status === 'success') {
                        Swal.fire({ icon: 'success', title: 'Removed', text: dataObj.message || 'Entries removed successfully.' });
                        openDiscrepancyCorrectionModal();
                        loadCalculatedAttendance();
                    } else {
                        Swal.fire({ icon: 'error', title: 'Removal Failed', text: dataObj.message || 'Could not remove entries.' });
                    }
                },
                error: function() {
                    Swal.fire({ icon: 'error', title: 'Network Error', text: 'Request failed. Please try again.' });
                }
            });
        }
    });
}

function openBatchCheckoutUpdateModal() {
    var selected = getSelectedDiscrepancyIDs();
    if (selected.length === 0) {
        Swal.fire({ icon: 'warning', title: 'No Selection', text: 'Please select at least 1 attendance entry to update check-out time.' });
        return;
    }

    $(document).off('focusin.bs.modal');

    Swal.fire({
        title: 'Update Check-Out Time',
        html:
            '<div class="text-start mb-2"><label class="fw-bold small text-muted">Selected Entries: ' + selected.length + '</label></div>' +
            '<div class="mb-3 text-start">' +
                '<label class="form-label fw-bold small text-muted">Select Check-Out Time <span class="text-danger">*</span></label>' +
                '<input type="time" id="swal-checkout-time-val" class="form-control" value="18:00">' +
            '</div>' +
            '<div class="text-start">' +
                '<label class="form-label fw-bold small text-muted">Remark <span class="text-danger">*</span></label>' +
                '<textarea id="swal-checkout-remark-val" class="form-control" rows="3" placeholder="Reason for checkout update (required)..." style="resize:vertical;"></textarea>' +
            '</div>',
        showCancelButton: true,
        confirmButtonColor: '#2563eb',
        confirmButtonText: 'Update Check-Out',
        cancelButtonText: 'Cancel',
        target: '#discrepancyCorrectionModal',
        didOpen: () => {
            $(document).off('focusin.bs.modal');
            setTimeout(function() {
                $('#swal-checkout-remark-val').focus();
            }, 100);
        },
        preConfirm: () => {
            var checkoutTime = $('#swal-checkout-time-val').val();
            var remark       = $('#swal-checkout-remark-val').val();
            if (!checkoutTime) {
                Swal.showValidationMessage('Please choose a valid check-out time.');
                return false;
            }
            if (!remark || !remark.trim()) {
                Swal.showValidationMessage('Please enter a remark for updating check-out time.');
                return false;
            }
            return { checkoutTime: checkoutTime, remark: remark.trim() };
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            Swal.fire({ title: 'Updating Check-Out...', allowOutsideClick: false, target: '#discrepancyCorrectionModal', didOpen: () => Swal.showLoading() });
            $.ajax({
                type: 'POST',
                url: routurl + 'modal/hrm_laboursalarymodal_fixed.php?action=BatchUpdateCheckoutTime',
                data: {
                    attendanceids: selected,
                    checkouttime:  result.value.checkoutTime,
                    remark:        result.value.remark,
                    loginname:     (typeof loginname !== 'undefined' && loginname ? loginname : 'Admin')
                },
                success: function(res) {
                    var dataObj = {};
                    try { dataObj = (typeof res === 'string') ? JSON.parse(res) : res; } catch(e){}
                    if (dataObj.status === 'success') {
                        Swal.fire({ icon: 'success', title: 'Updated', text: dataObj.message || 'Check-out updated successfully!' });
                        openDiscrepancyCorrectionModal();
                        loadCalculatedAttendance();
                    } else {
                        Swal.fire({ icon: 'error', title: 'Update Failed', text: dataObj.message || 'Could not update check-out time.' });
                    }
                },
                error: function() {
                    Swal.fire({ icon: 'error', title: 'Network Error', text: 'Request failed. Please try again.' });
                }
            });
        }
    });
}
</script>
