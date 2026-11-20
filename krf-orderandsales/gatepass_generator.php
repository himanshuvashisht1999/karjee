<?php
/**
 * Outward Dispatch Gate Pass Generator
 * Path: /krf-orderandsales/gatepass_generator.php
 */

include('../header.php');
date_default_timezone_set("Asia/Kolkata");

$pageTitle = "Gate Pass Generator";
?>

<!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
<!-- FontAwesome 6 -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
  :root {
    --primary: #059669;
    --primary-dark: #047857;
    --primary-light: #ecfdf5;
    --accent: #2563eb;
    --accent-light: #eff6ff;
    --dark: #0f172a;
    --slate: #475569;
    --border-color: #e2e8f0;
    --bg-light: #f8fafc;
    --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
    --hover-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -4px rgba(0, 0, 0, 0.05);
  }

  body {
    font-family: 'Plus Jakarta Sans', sans-serif !important;
    background-color: #f1f5f9;
    color: #1e293b;
  }

  .font-mono {
    font-family: 'JetBrains Mono', monospace !important;
  }

  .gp-container {
    padding: 1.5rem 1.75rem 4rem;
  }

  /* Page Header Banner */
  .gp-header-banner {
    background: linear-gradient(135deg, #064e3b 0%, #065f46 50%, #047857 100%);
    border-radius: 16px;
    padding: 1.75rem 2rem;
    color: #ffffff;
    box-shadow: 0 10px 25px -5px rgba(5, 150, 105, 0.3);
    margin-bottom: 1.75rem;
    position: relative;
    overflow: hidden;
  }

  .gp-header-banner::after {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 320px;
    height: 320px;
    background: radial-gradient(circle, rgba(255,255,255,0.12) 0%, rgba(255,255,255,0) 70%);
    border-radius: 50%;
    pointer-events: none;
  }

  .gp-title {
    font-size: 1.65rem;
    font-weight: 800;
    letter-spacing: -0.02em;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }

  .gp-subtitle {
    font-size: 0.92rem;
    color: #a7f3d0;
    margin-top: 0.35rem;
    margin-bottom: 0;
  }

  /* KPI Cards */
  .kpi-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 1.25rem 1.5rem;
    border: 2px solid var(--border-color);
    box-shadow: var(--card-shadow);
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 100%;
    cursor: pointer;
    position: relative;
    user-select: none;
  }

  .kpi-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--hover-shadow);
    border-color: #cbd5e1;
  }

  .kpi-card:active {
    transform: scale(0.98);
  }

  .kpi-card.active-kpi-emerald {
    border-color: #059669 !important;
    background: #f0fdf4 !important;
    box-shadow: 0 10px 20px -3px rgba(5, 150, 105, 0.2) !important;
  }

  .kpi-card.active-kpi-blue {
    border-color: #2563eb !important;
    background: #eff6ff !important;
    box-shadow: 0 10px 20px -3px rgba(37, 99, 235, 0.2) !important;
  }

  .kpi-card.active-kpi-amber {
    border-color: #d97706 !important;
    background: #fffbeb !important;
    box-shadow: 0 10px 20px -3px rgba(217, 119, 6, 0.2) !important;
  }

  .kpi-card.active-kpi-indigo {
    border-color: #4f46e5 !important;
    background: #eef2ff !important;
    box-shadow: 0 10px 20px -3px rgba(79, 70, 229, 0.2) !important;
  }

  .kpi-click-hint {
    font-size: 0.72rem;
    color: #94a3b8;
    margin-top: 0.35rem;
    display: flex;
    align-items: center;
    gap: 0.3rem;
    font-weight: 500;
    transition: color 0.2s;
  }

  .kpi-card:hover .kpi-click-hint {
    color: #334155;
  }

  .kpi-icon-box {
    width: 52px;
    height: 52px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    transition: transform 0.25s ease;
  }

  .kpi-card:hover .kpi-icon-box {
    transform: scale(1.1);
  }

  .kpi-icon-emerald {
    background: #ecfdf5;
    color: #059669;
  }

  .kpi-icon-blue {
    background: #eff6ff;
    color: #2563eb;
  }

  .kpi-icon-amber {
    background: #fffbeb;
    color: #d97706;
  }

  .kpi-icon-indigo {
    background: #eef2ff;
    color: #4f46e5;
  }

  .kpi-value {
    font-size: 1.65rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.2;
    margin: 0;
  }

  .kpi-label {
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #64748b;
    margin-bottom: 0.25rem;
  }

  /* Filter Panel */
  .filter-panel {
    background: #ffffff;
    border-radius: 16px;
    padding: 1.25rem 1.5rem;
    border: 1px solid var(--border-color);
    box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.05), 0 2px 6px -1px rgba(0, 0, 0, 0.03);
    margin-bottom: 1.75rem;
  }

  /* Segmented Status Tabs */
  .status-tabs-wrapper {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.75rem;
    padding-bottom: 1.15rem;
    margin-bottom: 1.15rem;
    border-bottom: 1px solid #e2e8f0;
  }

  .status-tabs-container {
    display: inline-flex;
    background: #f1f5f9;
    padding: 5px;
    border-radius: 12px;
    gap: 4px;
    flex-wrap: wrap;
  }

  .status-tab-btn {
    padding: 0.55rem 1.15rem;
    border-radius: 9px;
    font-size: 0.85rem;
    font-weight: 700;
    border: none;
    background: transparent;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    user-select: none;
    line-height: 1.2;
  }

  .status-tab-btn:hover {
    background: #e2e8f0;
    color: #0f172a;
  }

  .status-tab-btn.active {
    background: #059669;
    color: #ffffff;
    box-shadow: 0 4px 10px rgba(5, 150, 105, 0.25);
  }

  .status-tab-btn.active[data-status="dispatched"] {
    background: #2563eb;
    box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);
  }

  .status-tab-btn.active[data-status="pending"] {
    background: #d97706;
    box-shadow: 0 4px 10px rgba(217, 119, 6, 0.25);
  }

  .status-tab-btn.active[data-status="all"] {
    background: #334155;
    box-shadow: 0 4px 10px rgba(51, 65, 85, 0.25);
  }

  .badge-tab {
    background: #cbd5e1;
    color: #334155;
    padding: 0.15rem 0.55rem;
    border-radius: 9999px;
    font-size: 0.72rem;
    font-weight: 800;
    line-height: 1.2;
    transition: all 0.2s ease;
  }

  .status-tab-btn.active .badge-tab {
    background: rgba(255, 255, 255, 0.25);
    color: #ffffff;
  }

  /* Filter Controls (Search + Date + Buttons) */
  .filter-controls-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.75rem;
  }

  .filter-search-box {
    position: relative;
    flex: 1 1 280px;
    min-width: 240px;
  }

  .filter-search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 0.95rem;
    pointer-events: none;
  }

  .filter-search-input {
    width: 100% !important;
    height: 42px !important;
    padding: 0.55rem 1rem 0.55rem 38px !important;
    background: #f8fafc !important;
    border: 1.5px solid #cbd5e1 !important;
    border-radius: 10px !important;
    font-size: 0.88rem !important;
    color: #0f172a !important;
    transition: all 0.2s ease !important;
    box-sizing: border-box !important;
  }

  .filter-search-input:focus {
    background: #ffffff !important;
    border-color: #059669 !important;
    box-shadow: 0 0 0 3.5px rgba(5, 150, 105, 0.15) !important;
    outline: none !important;
  }

  .filter-search-input::placeholder {
    color: #94a3b8;
  }

  /* Date Group */
  .filter-date-group {
    display: inline-flex;
    align-items: center;
    background: #f8fafc;
    border: 1.5px solid #cbd5e1;
    border-radius: 10px;
    height: 42px;
    overflow: hidden;
    transition: all 0.2s ease;
    box-sizing: border-box;
  }

  .filter-date-group:focus-within {
    background: #ffffff;
    border-color: #059669;
    box-shadow: 0 0 0 3.5px rgba(5, 150, 105, 0.15);
  }

  .filter-date-addon {
    padding: 0 0.75rem;
    font-size: 0.8rem;
    font-weight: 700;
    color: #64748b;
    background: #f1f5f9;
    height: 100%;
    display: flex;
    align-items: center;
    gap: 0.4rem;
    border-right: 1px solid #cbd5e1;
    user-select: none;
    white-space: nowrap;
  }

  .filter-date-input {
    border: none !important;
    background: transparent !important;
    height: 100% !important;
    padding: 0 0.75rem !important;
    font-size: 0.85rem !important;
    color: #0f172a !important;
    font-weight: 500 !important;
    outline: none !important;
    box-shadow: none !important;
  }

  /* Filter Action Buttons */
  .filter-actions-group {
    display: inline-flex;
    gap: 0.5rem;
    align-items: center;
  }

  .btn-filter-apply {
    height: 42px;
    padding: 0 1.25rem;
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
    color: #ffffff !important;
    border: none;
    border-radius: 10px;
    font-size: 0.86rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    cursor: pointer;
    box-shadow: 0 4px 10px rgba(5, 150, 105, 0.25);
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    white-space: nowrap;
  }

  .btn-filter-apply:hover {
    background: linear-gradient(135deg, #047857 0%, #065f46 100%);
    transform: translateY(-1px);
    box-shadow: 0 6px 14px rgba(5, 150, 105, 0.35);
  }

  .btn-filter-apply:active {
    transform: scale(0.98);
  }

  .btn-filter-reset {
    height: 42px;
    padding: 0 1.1rem;
    background: #ffffff;
    color: #64748b;
    border: 1.5px solid #cbd5e1;
    border-radius: 10px;
    font-size: 0.86rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
  }

  .btn-filter-reset:hover {
    background: #f8fafc;
    color: #0f172a;
    border-color: #94a3b8;
  }

  .btn-filter-reset:active {
    transform: scale(0.98);
  }

  /* Orders Table */
  .table-card {
    background: #ffffff;
    border-radius: 14px;
    border: 1px solid var(--border-color);
    box-shadow: var(--card-shadow);
    overflow: hidden;
  }

  .gp-table {
    width: 100%;
    margin: 0;
    border-collapse: separate;
    border-spacing: 0;
  }

  .gp-table thead th {
    background: #f8fafc;
    color: #475569;
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 0.85rem 0.9rem;
    border-bottom: 1px solid var(--border-color);
    vertical-align: middle;
  }

  .gp-table tbody td {
    padding: 0.85rem 0.9rem;
    vertical-align: top !important;
    border-bottom: 1px solid #f1f5f9;
    font-size: 0.88rem;
    color: #1e293b;
  }

  .gp-table tbody tr:hover {
    background: #f8fafc;
  }

  /* Status Badges */
  .badge-status {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    text-transform: uppercase;
  }

  .badge-ready-dispatch {
    background: #dcfce7;
    color: #15803d;
    border: 1px solid #bbf7d0;
  }

  .badge-dispatched {
    background: #dbeafe;
    color: #1e40af;
    border: 1px solid #bfdbfe;
  }

  .badge-pending {
    background: #fef3c7;
    color: #b45309;
    border: 1px solid #fde68a;
  }

  .badge-allocated {
    background: #f3e8ff;
    color: #7e22ce;
    border: 1px solid #e9d5ff;
  }

  /* Pulse indicator for readytodispatch */
  .pulse-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background-color: #16a34a;
    box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.7);
    animation: pulseDot 1.5s infinite;
  }

  @keyframes pulseDot {
    0% {
      transform: scale(0.95);
      box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.7);
    }
    70% {
      transform: scale(1);
      box-shadow: 0 0 0 6px rgba(22, 163, 74, 0);
    }
    100% {
      transform: scale(0.95);
      box-shadow: 0 0 0 0 rgba(22, 163, 74, 0);
    }
  }

  /* Buttons */
  .btn-gp-generate {
    background: #059669;
    color: #ffffff;
    border: none;
    font-weight: 600;
    font-size: 0.82rem;
    padding: 0.45rem 0.9rem;
    border-radius: 8px;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
  }

  .btn-gp-generate:hover {
    background: #047857;
    color: #ffffff;
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(5, 150, 105, 0.3);
  }

  .btn-gp-view {
    background: #2563eb;
    color: #ffffff;
    border: none;
    font-weight: 600;
    font-size: 0.82rem;
    padding: 0.45rem 0.9rem;
    border-radius: 8px;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
  }

  .btn-gp-view:hover {
    background: #1d4ed8;
    color: #ffffff;
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.3);
  }

  .btn-gp-locked {
    background: #f1f5f9;
    color: #94a3b8;
    border: 1px dashed #cbd5e1;
    font-weight: 600;
    font-size: 0.8rem;
    padding: 0.45rem 0.85rem;
    border-radius: 8px;
    cursor: not-allowed;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
  }

  /* Product Items Grid & Pills */
  .product-items-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(215px, 1fr));
    gap: 6px;
    max-height: 220px;
    overflow-y: auto;
    padding-right: 3px;
  }

  .product-items-grid::-webkit-scrollbar {
    width: 5px;
  }
  .product-items-grid::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
  }
  .product-items-grid::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
  }
  .product-items-grid::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
  }

  .product-pill {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 0.4rem 0.6rem;
    font-size: 0.8rem;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 0.5rem;
    transition: all 0.15s ease;
  }

  .product-pill:hover {
    background: #ffffff;
    border-color: #cbd5e1;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.04);
  }

  /* Customer / Consignee Card */
  .client-info-card {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
  }

  .client-info-name {
    font-weight: 700;
    color: #0f172a;
    font-size: 0.92rem;
    line-height: 1.25;
  }

  .client-info-contact {
    font-size: 0.8rem;
    color: #475569;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
  }

  .client-info-address {
    font-size: 0.76rem;
    color: #64748b;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  .client-vehicle-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    background: #f1f5f9;
    color: #0f172a;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    padding: 0.2rem 0.55rem;
    font-size: 0.74rem;
    font-family: 'JetBrains Mono', monospace;
    font-weight: 600;
    margin-top: 0.2rem;
    max-width: 100%;
    word-break: break-word;
  }

  /* Quantity Stats Box */
  .gp-stat-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.4rem;
    padding: 0.2rem 0;
  }

  .gp-stat-plants {
    background: #dcfce7;
    color: #15803d;
    border: 1px solid #bbf7d0;
    border-radius: 8px;
    padding: 0.35rem 0.65rem;
    font-weight: 800;
    font-size: 0.9rem;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    white-space: nowrap;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
  }

  .gp-stat-trays {
    background: #e0e7ff;
    color: #3730a3;
    border: 1px solid #c7d2fe;
    border-radius: 6px;
    padding: 0.2rem 0.55rem;
    font-weight: 700;
    font-size: 0.78rem;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    white-space: nowrap;
  }

  /* Vehicle Preset Chip in Modal */
  .vehicle-preset-chip {
    cursor: pointer;
    transition: all 0.15s ease;
    font-size: 0.74rem !important;
    padding: 0.22rem 0.55rem !important;
    border-radius: 6px !important;
    font-weight: 600 !important;
  }

  .vehicle-preset-chip:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
  }

  /* Modal Styling */
  .modal-dialog-custom {
    max-width: 820px;
    margin: 1rem auto !important;
    height: calc(100vh - 2rem) !important;
    max-height: calc(100vh - 2rem) !important;
    display: flex !important;
    flex-direction: column !important;
  }

  .modal-dialog-custom::before {
    display: none !important;
  }

  .modal-custom-content {
    background: #ffffff;
    border-radius: 16px !important;
    border: none !important;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
    overflow: hidden;
    height: 100% !important;
    max-height: 100% !important;
    display: flex !important;
    flex-direction: column !important;
  }

  .modal-custom-header {
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%) !important;
    color: #065f46 !important;
    padding: 1rem 1.75rem !important;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #a7f3d0 !important;
    flex-shrink: 0 !important;
  }

  .modal-custom-header .modal-title {
    font-size: 1.2rem;
    font-weight: 800;
    letter-spacing: -0.01em;
    color: #065f46 !important;
    display: flex;
    align-items: center;
    gap: 0.65rem;
    margin: 0;
  }

  .gp-modal-icon-badge {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: #ffffff !important;
    border: 1px solid #a7f3d0 !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    color: #059669 !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05) !important;
  }

  /* Universal Close Button */
  .gp-modal-close-btn {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: #ffffff;
    border: 1px solid #a7f3d0;
    color: #065f46 !important;
    font-size: 1rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    outline: none;
    padding: 0;
    line-height: 1;
  }

  .gp-modal-close-btn:hover {
    background: #d1fae5;
    transform: rotate(90deg);
    color: #047857 !important;
  }

  .gp-modal-close-btn:active {
    transform: scale(0.92);
  }

  #generateGatePassModal .modal-body {
    flex: 1 1 auto !important;
    min-height: 0 !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
    -webkit-overflow-scrolling: touch;
  }

  /* Order Meta Summary Card */
  .order-meta-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.15rem 1.25rem;
    margin-bottom: 1.25rem;
  }

  .order-meta-label {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #64748b;
    margin-bottom: 0.25rem;
  }

  .order-meta-val-highlight {
    font-size: 1.05rem;
    font-weight: 800;
    color: #059669;
    font-family: 'JetBrains Mono', monospace;
  }

  /* Product Items in Modal */
  .modal-items-container {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 1.25rem;
  }

  .modal-items-header {
    background: #f1f5f9;
    padding: 0.65rem 1rem;
    font-size: 0.76rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #475569;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  #modalItemsList {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 8px;
    max-height: 200px;
    overflow-y: auto;
    padding: 0.75rem;
    background: #f8fafc;
  }

  #modalItemsList::-webkit-scrollbar {
    width: 5px;
  }
  #modalItemsList::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
  }
  #modalItemsList::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
  }
  #modalItemsList::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
  }

  .modal-item-row {
    padding: 0.65rem 0.85rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #ffffff;
    transition: all 0.15s ease;
  }

  .modal-item-row:hover {
    background: #ffffff;
    border-color: #cbd5e1;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
  }

  .modal-item-title {
    font-size: 0.92rem;
    font-weight: 700;
    color: #0f172a;
  }

  .modal-item-stk {
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.75rem;
    background: #eef2ff;
    color: #4f46e5;
    padding: 0.15rem 0.5rem;
    border-radius: 4px;
    font-weight: 600;
    display: inline-block;
    margin-top: 0.25rem;
  }

  .modal-items-summary-bar {
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    padding: 0.85rem 1.15rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.9rem;
    font-weight: 700;
  }

  .modal-total-trays-badge {
    background: #e0e7ff !important;
    color: #3730a3 !important;
    border: 1.5px solid #c7d2fe !important;
    border-radius: 8px !important;
    padding: 0.35rem 0.85rem !important;
    font-size: 0.88rem !important;
    font-weight: 800 !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 0.4rem !important;
    letter-spacing: 0.02em !important;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
  }

  .modal-total-plants-badge {
    background: #dcfce7 !important;
    color: #15803d !important;
    border: 1.5px solid #86efac !important;
    border-radius: 8px !important;
    padding: 0.35rem 0.95rem !important;
    font-size: 0.92rem !important;
    font-weight: 800 !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 0.4rem !important;
    letter-spacing: 0.02em !important;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
  }

  .modal-item-tray-badge {
    background: #f1f5f9 !important;
    color: #1e293b !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 6px !important;
    padding: 0.2rem 0.6rem !important;
    font-size: 0.78rem !important;
    font-weight: 700 !important;
    display: inline-block !important;
  }

  /* Form Fields in Modal */
  .gp-form-section-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #e2e8f0;
  }

  .gp-form-label {
    font-size: 0.82rem;
    font-weight: 600;
    color: #334155;
    margin-bottom: 0.35rem;
    display: block;
  }

  .gp-form-control, .gp-form-select {
    width: 100% !important;
    padding: 0.6rem 0.85rem !important;
    font-size: 0.88rem !important;
    font-weight: 500 !important;
    color: #0f172a !important;
    background-color: #ffffff !important;
    border: 1.5px solid #cbd5e1 !important;
    border-radius: 10px !important;
    transition: all 0.2s ease !important;
    box-sizing: border-box !important;
    display: block !important;
    height: 42px !important;
  }

  .gp-form-control:focus, .gp-form-select:focus {
    border-color: #059669 !important;
    box-shadow: 0 0 0 3.5px rgba(5, 150, 105, 0.15) !important;
    outline: none !important;
    background-color: #ffffff !important;
  }

  .gp-form-control::placeholder {
    color: #94a3b8;
    font-weight: 400;
  }

  .gp-form-control-mono {
    font-family: 'JetBrains Mono', monospace !important;
    text-transform: uppercase;
    font-weight: 600;
    letter-spacing: 0.05em;
  }

  /* Modal Footer */
  .modal-custom-footer {
    background: #ffffff !important;
    border-top: 1px solid #e2e8f0;
    padding: 0.9rem 1.75rem;
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    border-radius: 0 0 16px 16px;
    flex-shrink: 0 !important;
    position: sticky !important;
    bottom: 0 !important;
    z-index: 1055 !important;
    box-shadow: 0 -3px 12px rgba(0, 0, 0, 0.06) !important;
    padding-bottom: max(12px, env(safe-area-inset-bottom, 12px)) !important;
  }

  .btn-gp-modal-cancel {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #cbd5e1;
    font-weight: 600;
    font-size: 0.88rem;
    padding: 0.6rem 1.25rem;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
  }

  .btn-gp-modal-cancel:hover {
    background: #e2e8f0;
    color: #0f172a;
  }

  .btn-gp-modal-submit {
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
    color: #ffffff !important;
    border: none;
    font-weight: 700;
    font-size: 0.88rem;
    padding: 0.6rem 1.5rem;
    border-radius: 10px;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
  }

  .btn-gp-modal-submit:hover {
    background: linear-gradient(135deg, #047857 0%, #065f46 100%);
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(5, 150, 105, 0.4);
    color: #ffffff !important;
  }

  .btn-gp-modal-submit:active {
    transform: scale(0.98);
  }

  /* PDF Preview Modal Styling - Expanded Full Height (92vh) */
  #pdfPreviewModal .modal-dialog {
    max-width: 94vw !important;
    width: 94vw !important;
    height: 92vh !important;
    min-height: 92vh !important;
    max-height: 92vh !important;
    margin: 4vh auto !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: center !important;
  }

  #pdfPreviewModal .modal-content {
    height: 92vh !important;
    min-height: 92vh !important;
    max-height: 92vh !important;
    border-radius: 14px !important;
    overflow: hidden !important;
    display: flex !important;
    flex-direction: column !important;
    border: none !important;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.45) !important;
    background: #1e293b !important;
  }

  #pdfPreviewModal .modal-header {
    height: 52px !important;
    min-height: 52px !important;
    max-height: 52px !important;
    flex-shrink: 0 !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
    border-radius: 14px 14px 0 0 !important;
    background: #111827 !important;
    padding: 0.5rem 1.25rem !important;
  }

  #pdfPreviewModal .modal-body {
    height: calc(92vh - 52px) !important;
    min-height: calc(92vh - 52px) !important;
    max-height: calc(92vh - 52px) !important;
    flex: 1 1 auto !important;
    padding: 0 !important;
    margin: 0 !important;
    background: #2b2b2b !important;
    overflow: hidden !important;
    display: block !important;
  }

  #pdfPreviewModal iframe,
  #pdfPreviewModal #pdfIframe {
    width: 100% !important;
    height: 100% !important;
    min-height: 100% !important;
    border: none !important;
    display: block !important;
  }

  @media (max-width: 767.98px) {
    .modal-dialog-custom {
      max-width: 100vw !important;
      width: 100vw !important;
      margin: 0 !important;
      height: 100% !important;
      min-height: 100dvh !important;
      max-height: 100dvh !important;
    }

    .modal-custom-content {
      height: 100% !important;
      max-height: 100dvh !important;
      border-radius: 0 !important;
    }

    .modal-custom-header {
      padding: 1rem 1.25rem !important;
    }

    #generateGatePassModal .modal-body {
      padding: 1rem !important;
    }

    .modal-custom-footer {
      padding: 0.85rem 1rem !important;
      padding-bottom: max(14px, env(safe-area-inset-bottom, 14px)) !important;
      display: flex !important;
      gap: 0.5rem !important;
      border-radius: 0 !important;
    }

    .btn-gp-modal-cancel,
    .btn-gp-modal-submit {
      flex: 1 1 50% !important;
      justify-content: center !important;
      font-size: 0.82rem !important;
      padding: 0.65rem 0.6rem !important;
      white-space: nowrap !important;
      text-align: center !important;
    }

    #pdfPreviewModal .modal-dialog {
      max-width: 100vw !important;
      width: 100vw !important;
      height: 100dvh !important;
      min-height: 100dvh !important;
      max-height: 100dvh !important;
      margin: 0 !important;
    }

    #pdfPreviewModal .modal-content {
      height: 100dvh !important;
      min-height: 100dvh !important;
      max-height: 100dvh !important;
      border-radius: 0 !important;
    }

    #pdfPreviewModal .modal-body {
      height: calc(100dvh - 52px) !important;
      min-height: calc(100dvh - 52px) !important;
      max-height: calc(100dvh - 52px) !important;
    }
  }

  .loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #ffffff;
  }
</style>

<div class="gp-container">

  <!-- Header Banner -->
  <div class="gp-header-banner">
    <div class="d-flex flex-wrap justify-content-between align-items-center">
      <div>
        <h1 class="gp-title">
          <i class="fa-solid fa-truck-ramp-box"></i> Gate Pass Generator
        </h1>
        <p class="gp-subtitle">
          Generate official Outward Dispatch Gate Passes for vehicle-allocated and ready-to-dispatch orders.
        </p>
      </div>
      <div class="mt-3 mt-md-0 d-flex gap-2">
        <a href="scan_gatepass.php" class="btn btn-warning fw-bold px-3 py-2 text-dark shadow-sm">
          <i class="fa-solid fa-qrcode me-1"></i> Scan Gate Pass QR
        </a>
        <button type="button" class="btn btn-light fw-bold px-3 py-2" id="refreshBtn" onclick="loadGatepassOrders()">
          <i class="fa-solid fa-rotate me-1" id="refreshIcon"></i> Refresh Orders
        </button>
      </div>
    </div>
  </div>

  <!-- KPI Metric Cards (Clickable to Filter) -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="kpi-card active-kpi-emerald" id="kpiCardReady" onclick="filterByKpi('readytodispatch')" title="Click to show only Ready To Dispatch orders">
        <div>
          <div class="kpi-label">Ready To Gatepass</div>
          <div class="kpi-value text-success" id="kpiReadyCount">0</div>
          <small class="text-muted"><span id="kpiReadyPlants">0</span> Plants Awaiting Pass</small>
          <div class="kpi-click-hint"><i class="fa-solid fa-arrow-pointer"></i> Click to filter</div>
        </div>
        <div class="kpi-icon-box kpi-icon-emerald">
          <i class="fa-solid fa-truck-fast"></i>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="kpi-card" id="kpiCardTotal" onclick="filterByKpi('all')" title="Click to show all orders">
        <div>
          <div class="kpi-label">Total Orders</div>
          <div class="kpi-value text-primary" id="kpiTotalOrders">0</div>
          <small class="text-muted">In Nursery Queue</small>
          <div class="kpi-click-hint"><i class="fa-solid fa-arrow-pointer"></i> Click to filter</div>
        </div>
        <div class="kpi-icon-box kpi-icon-blue">
          <i class="fa-solid fa-boxes-stacked"></i>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="kpi-card" id="kpiCardToday" onclick="filterByKpi('dispatched_today')" title="Click to show orders dispatched today">
        <div>
          <div class="kpi-label">Dispatched Today</div>
          <div class="kpi-value text-amber" id="kpiDispatchedToday">0</div>
          <small class="text-muted">Gate Out Completed Today</small>
          <div class="kpi-click-hint"><i class="fa-solid fa-arrow-pointer"></i> Click to filter</div>
        </div>
        <div class="kpi-icon-box kpi-icon-amber">
          <i class="fa-solid fa-calendar-check"></i>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="kpi-card" id="kpiCardDispatched" onclick="filterByKpi('dispatched')" title="Click to show all historical dispatched orders">
        <div>
          <div class="kpi-label">Total Dispatched</div>
          <div class="kpi-value text-indigo" id="kpiTotalDispatched">0</div>
          <small class="text-muted">Historical Outward Records</small>
          <div class="kpi-click-hint"><i class="fa-solid fa-arrow-pointer"></i> Click to filter</div>
        </div>
        <div class="kpi-icon-box kpi-icon-indigo">
          <i class="fa-solid fa-circle-check"></i>
        </div>
      </div>
    </div>
  </div>

  <!-- Filter Panel -->
  <div class="filter-panel">
    <!-- Status Tabs Bar -->
    <div class="status-tabs-wrapper">
      <div class="status-tabs-container">
        <button type="button" class="status-tab-btn active" data-status="readytodispatch" onclick="setStatusFilter('readytodispatch', this)">
          <i class="fa-solid fa-truck-fast"></i> Ready To Dispatch <span class="badge-tab" id="tabBadgeReady">0</span>
        </button>
        <button type="button" class="status-tab-btn" data-status="dispatched" onclick="setStatusFilter('dispatched', this)">
          <i class="fa-solid fa-circle-check"></i> Dispatched (Gate Pass Done) <span class="badge-tab" id="tabBadgeDispatched">0</span>
        </button>
        <button type="button" class="status-tab-btn" data-status="pending" onclick="setStatusFilter('pending', this)">
          <i class="fa-solid fa-clock"></i> Pending / In Nursery <span class="badge-tab" id="tabBadgePending">0</span>
        </button>
        <button type="button" class="status-tab-btn" data-status="all" onclick="setStatusFilter('all', this)">
          <i class="fa-solid fa-list"></i> All Orders <span class="badge-tab" id="tabBadgeAll">0</span>
        </button>
      </div>
    </div>

    <!-- Search and Date Filter Controls Row -->
    <div class="filter-controls-row">
      <!-- Search Box with integrated icon -->
      <div class="filter-search-box">
        <i class="fa-solid fa-magnifying-glass filter-search-icon"></i>
        <input type="text" id="searchInput" class="filter-search-input" placeholder="Search Order No, Client Name, Phone, Vehicle..." onkeyup="handleSearchInput(event)">
      </div>

      <!-- From Date Group -->
      <div class="filter-date-group">
        <span class="filter-date-addon"><i class="fa-regular fa-calendar"></i> From</span>
        <input type="date" id="fromDateInput" class="filter-date-input" title="From Date">
      </div>

      <!-- To Date Group -->
      <div class="filter-date-group">
        <span class="filter-date-addon"><i class="fa-regular fa-calendar"></i> To</span>
        <input type="date" id="toDateInput" class="filter-date-input" title="To Date">
      </div>

      <!-- Buttons Group -->
      <div class="filter-actions-group">
        <button type="button" class="btn-filter-apply" onclick="applyFilters()">
          <i class="fa-solid fa-filter"></i> Apply
        </button>
        <button type="button" class="btn-filter-reset" onclick="resetFilters()">
          <i class="fa-solid fa-rotate-left"></i> Reset
        </button>
      </div>
    </div>
  </div>

  <!-- Orders Table -->
  <div class="table-card">
    <div class="table-responsive">
      <table class="gp-table" id="gatepassTable">
        <thead>
          <tr>
            <th style="width: 11%;">Order No</th>
            <th style="width: 20%;">Customer / Consignee</th>
            <th style="width: 39%;">Product Breakdown</th>
            <th style="width: 10%;" class="text-center">Plants & Trays</th>
            <th style="width: 10%;" class="text-center">Consumed Status</th>
            <th style="width: 10%;" class="text-center">Gate Pass Action</th>
          </tr>
        </thead>
        <tbody id="ordersTableBody">
          <tr>
            <td colspan="6" class="text-center py-5 text-muted">
              <i class="fa fa-spinner fa-spin fa-2x mb-2 text-success"></i><br>
              Loading orders...
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- Generate Gate Pass Modal -->
<div class="modal fade" id="generateGatePassModal" tabindex="-1" role="dialog" aria-labelledby="generateGatePassModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-custom" role="document">
    <div class="modal-content modal-custom-content">
      
      <div class="modal-custom-header">
        <h5 class="modal-title" id="generateGatePassModalLabel">
          <span class="gp-modal-icon-badge"><i class="fa-solid fa-truck-ramp-box"></i></span>
          <span>Generate Outward Gate Pass</span>
        </h5>
        <button type="button" class="gp-modal-close-btn" data-dismiss="modal" data-bs-dismiss="modal" onclick="closeGatePassModal()" aria-label="Close" title="Close">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <div class="modal-body p-4">
        
        <!-- Order Details Header Box -->
        <div class="order-meta-box">
          <div class="row g-3">
            <div class="col-6 col-md-3">
              <div class="order-meta-label">Provisional Order</div>
              <div class="order-meta-val-highlight" id="modalPsoNo">-</div>
            </div>
            <div class="col-6 col-md-3">
              <div class="order-meta-label">Sales Order</div>
              <div class="font-mono text-dark fw-bold fs-6" id="modalSoNo">-</div>
            </div>
            <div class="col-12 col-md-6">
              <div class="order-meta-label">Customer / Consignee</div>
              <div class="fw-bold text-dark fs-6" id="modalClientInfo">-</div>
            </div>
            <div class="col-12 mt-1 pt-2 border-top">
              <div class="order-meta-label"><i class="fa-solid fa-location-dot me-1 text-danger"></i> Delivery Address</div>
              <div class="text-dark small" id="modalDeliveryAddress">-</div>
            </div>
          </div>
        </div>

        <!-- Items Summary List -->
        <div class="mb-4">
          <div class="gp-form-section-title">
            <i class="fa-solid fa-boxes-stacked text-success"></i> Dispatched Products & Live Quantities
          </div>
          <div class="modal-items-container">
            <div class="modal-items-header">
              <span>Product & Variety</span>
              <span>Allocated Plants & Trays</span>
            </div>
            <div id="modalItemsList">
              <!-- Populated dynamically -->
            </div>
            <div class="modal-items-summary-bar">
              <span class="d-flex align-items-center gap-2">
                <span class="text-dark fw-bold">Total Trays:</span> 
                <span class="modal-total-trays-badge" id="modalTotalTrays"><i class="fa-solid fa-layer-group"></i> 0 Trays</span>
              </span>
              <span class="d-flex align-items-center gap-2">
                <span class="text-dark fw-bold">Total Live Plants:</span> 
                <span class="modal-total-plants-badge" id="modalTotalPlants"><i class="fa-solid fa-seedling"></i> 0 Plants</span>
              </span>
            </div>
          </div>
        </div>

        <!-- Transport & Vehicle Form -->
        <form id="gatePassForm" onsubmit="event.preventDefault(); submitGenerateGatePass();">
          <input type="hidden" id="formPsoNo" name="psono">

          <div class="gp-form-section-title">
            <i class="fa-solid fa-truck-fast text-success"></i> Transport & Driver Information
          </div>

          <div class="row g-3">
            <!-- Vehicle Selection / Custom Name -->
            <div class="col-12 col-md-6">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <label class="gp-form-label mb-0" for="vehicleMasterInput">
                  <i class="fa-solid fa-truck-front text-success me-1"></i> Select Registered Vehicle (or Enter Custom):
                </label>
                <small class="text-muted" style="font-size: 0.72rem;">(Pick or Type Any Name)</small>
              </div>
              <div class="position-relative">
                <input type="text" 
                       id="vehicleMasterInput" 
                       class="gp-form-control" 
                       list="vehicleMasterDatalist" 
                       placeholder="-- Choose from Vehicle Master or type custom name --" 
                       autocomplete="off" 
                       oninput="handleVehicleCustomInput(this.value)" 
                       onchange="handleVehicleCustomInput(this.value)">
                <datalist id="vehicleMasterDatalist">
                  <!-- Populated from Vehicle Master -->
                </datalist>
              </div>
              <!-- Quick 1-Click Preset Chips -->
              <div class="d-flex flex-wrap gap-1 mt-1 pt-1">
                <button type="button" class="btn btn-outline-success btn-sm vehicle-preset-chip py-0 px-2" onclick="setVehicleQuickPreset('SELF PICKUP', 'Self (Farmer/Client)', 'Self (Farmer/Client)')">
                  <i class="fa-solid fa-user-check me-1"></i> Self Pickup
                </button>
                <button type="button" class="btn btn-outline-primary btn-sm vehicle-preset-chip py-0 px-2" onclick="setVehicleQuickPreset('FARMER VEHICLE', 'Farmer Vehicle / Self', 'Farmer / Driver')">
                  <i class="fa-solid fa-tractor me-1"></i> Farmer Vehicle
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm vehicle-preset-chip py-0 px-2" onclick="setVehicleQuickPreset('LOCAL PICKUP', 'Local Transport', 'Local Driver')">
                  <i class="fa-solid fa-truck-pickup me-1"></i> Local Auto/Pickup
                </button>
              </div>
            </div>

            <div class="col-12 col-md-6">
              <label class="gp-form-label" for="formVehicleNo">Vehicle Number / Name <span class="text-danger">*</span>:</label>
              <input type="text" class="gp-form-control gp-form-control-mono" id="formVehicleNo" name="vehicleno" placeholder="e.g. CG-15-EE-1461 or SELF PICKUP" required oninput="handleVehicleNoDirectInput(this.value)">
              <small class="text-muted" style="font-size: 0.72rem;">This name/number will appear directly on the Gate Pass.</small>
            </div>

            <!-- Transporter / Owner -->
            <div class="col-12 col-md-6">
              <label class="gp-form-label" for="formTransporterName">Transporter / Owner Name:</label>
              <input type="text" class="gp-form-control" id="formTransporterName" name="transporter_name" placeholder="e.g. Karjee Transport / Self">
            </div>

            <!-- Driver Name -->
            <div class="col-12 col-md-6">
              <label class="gp-form-label" for="formDriverName">Driver Name:</label>
              <input type="text" class="gp-form-control" id="formDriverName" name="drivername" placeholder="Driver Full Name">
            </div>

            <!-- Driver Mobile -->
            <div class="col-12 col-md-6">
              <label class="gp-form-label" for="formDriverMobile">Driver Mobile Number:</label>
              <input type="text" class="gp-form-control" id="formDriverMobile" name="drivermobile" placeholder="10-digit mobile number">
            </div>

            <!-- Remarks -->
            <div class="col-12 col-md-6">
              <label class="gp-form-label" for="formRemarks">Gatepass / Dispatch Remarks:</label>
              <input type="text" class="gp-form-control" id="formRemarks" name="remarks" placeholder="Optional notes for gate release">
            </div>
          </div>
        </form>

      </div>

      <div class="modal-custom-footer">
        <button type="button" class="btn-gp-modal-cancel" data-dismiss="modal" data-bs-dismiss="modal" onclick="closeGatePassModal()">
          <i class="fa-solid fa-xmark"></i> Cancel
        </button>
        <button type="button" class="btn-gp-modal-submit" id="submitGatePassBtn" onclick="submitGenerateGatePass()">
          <i class="fa-solid fa-circle-check"></i> Generate & Confirm Gate Pass
        </button>
      </div>

    </div>
  </div>
</div>

<!-- PDF Preview Modal -->
<div class="modal fade" id="pdfPreviewModal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 1080;">
  <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
    <div class="modal-content shadow-lg">
      <div class="modal-header d-flex justify-content-between align-items-center">
        <h6 class="modal-title fw-bold m-0" id="pdfModalTitle"><i class="fa fa-file-pdf me-2 text-danger"></i> Outward Gate Pass Document</h6>
        <div class="d-flex gap-2 align-items-center">
          <a href="#" id="pdfDownloadLink" target="_blank" download class="btn btn-sm btn-outline-light px-3"><i class="fa fa-download me-1"></i> Download</a>
          <button type="button" class="gp-modal-close-btn" data-dismiss="modal" data-bs-dismiss="modal" onclick="closePdfPreviewModal()" aria-label="Close" title="Close">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
      </div>
      <div class="modal-body p-0">
        <iframe id="pdfIframe" src=""></iframe>
      </div>
    </div>
  </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay d-none">
  <div class="spinner-border text-light mb-3" style="width: 3rem; height: 3rem;" role="status"></div>
  <h5 class="fw-bold" id="loadingText">Processing Gate Pass...</h5>
</div>

<script>
let currentStatusFilter = 'readytodispatch';
let currentSearchQuery = '';
let activeVehiclesList = [];
let allLoadedOrders = [];

// Initialize Page
$(document).ready(function() {
  loadVehiclesMaster();
  loadGatepassOrders();
});

// Load Vehicles Master for Combobox Autocomplete
function loadVehiclesMaster() {
  $.ajax({
    url: 'modal/gatepass_modal.php?action=get_vehicles',
    type: 'GET',
    dataType: 'json',
    success: function(res) {
      if (res.status === 'success') {
        activeVehiclesList = res.vehicles || [];
        let $datalist = $('#vehicleMasterDatalist');
        $datalist.empty();
        activeVehiclesList.forEach(v => {
          if (v.vehicleno) {
            let label = `${v.vehicleno} - ${v.vehiclename || ''} (${v.vehicleownername || 'N/A'})`.trim();
            $datalist.append(`<option value="${escapeHtml(v.vehicleno)}">${escapeHtml(label)}</option>`);
          }
        });
      }
    }
  });
}

// Handle Custom Input or Selection in Vehicle Combobox
function handleVehicleCustomInput(val) {
  val = (val || '').trim();
  if (!val) {
    return;
  }

  // Check if value matches any vehicle in activeVehiclesList
  let cleanVal = val.toUpperCase().replace(/[\s\-\/]/g, '');
  let matched = activeVehiclesList.find(v => {
    let cleanNo = (v.vehicleno || '').toUpperCase().replace(/[\s\-\/]/g, '');
    return cleanNo === cleanVal || (v.vehicleno && val.toLowerCase().includes(v.vehicleno.toLowerCase()));
  });

  if (matched) {
    $('#formVehicleNo').val(matched.vehicleno);
    if (matched.vehicleownername && !$('#formTransporterName').val()) {
      $('#formTransporterName').val(matched.vehicleownername);
    }
  } else {
    // Custom name or custom vehicle number entered by user!
    $('#formVehicleNo').val(val);
  }
}

// Keep combobox in sync if user edits Vehicle Number directly
function handleVehicleNoDirectInput(val) {
  val = (val || '').trim();
  let currentCombo = ($('#vehicleMasterInput').val() || '').trim();
  if (!currentCombo || currentCombo !== val) {
    $('#vehicleMasterInput').val(val);
  }
}

// 1-Click Quick Preset Helper for Common Vehicle Types
function setVehicleQuickPreset(vehNo, transporter, driver) {
  $('#vehicleMasterInput').val(vehNo);
  $('#formVehicleNo').val(vehNo);
  if (transporter) {
    $('#formTransporterName').val(transporter);
  }
  if (driver) {
    $('#formDriverName').val(driver);
  }
}

// Load Orders from Server
function loadGatepassOrders() {
  $('#refreshIcon').addClass('fa-spin');
  $('#ordersTableBody').html(`
    <tr>
      <td colspan="6" class="text-center py-5 text-muted">
        <i class="fa fa-spinner fa-spin fa-2x mb-2 text-success"></i><br>
        Loading orders...
      </td>
    </tr>
  `);

  let params = {
    action: 'get_orders',
    status: currentStatusFilter,
    search: currentSearchQuery,
    from_date: $('#fromDateInput').val() || '',
    to_date: $('#toDateInput').val() || ''
  };

  $.ajax({
    url: 'modal/gatepass_modal.php',
    type: 'GET',
    data: params,
    dataType: 'json',
    success: function(res) {
      $('#refreshIcon').removeClass('fa-spin');
      if (res.status === 'success') {
        updateKpis(res.kpi);
        allLoadedOrders = res.orders || [];
        renderOrdersTable(allLoadedOrders);
      } else {
        $('#ordersTableBody').html(`
          <tr>
            <td colspan="6" class="text-center py-4 text-danger">
              <i class="fa fa-exclamation-triangle fa-2x mb-2"></i><br>
              ${escapeHtml(res.message || 'Error loading orders.')}
            </td>
          </tr>
        `);
      }
    },
    error: function() {
      $('#refreshIcon').removeClass('fa-spin');
      $('#ordersTableBody').html(`
        <tr>
          <td colspan="6" class="text-center py-4 text-danger">
            <i class="fa fa-exclamation-triangle fa-2x mb-2"></i><br>
            Failed to connect to server. Please try again.
          </td>
        </tr>
      `);
    }
  });
}

// Update KPI Badges & Cards
function updateKpis(kpi) {
  if (!kpi) return;
  $('#kpiReadyCount').text(Number(kpi.ready_to_dispatch || 0).toLocaleString());
  $('#kpiReadyPlants').text(Number(kpi.ready_plants || 0).toLocaleString());
  $('#kpiTotalOrders').text(Number(kpi.total_orders || 0).toLocaleString());
  $('#kpiDispatchedToday').text(Number(kpi.dispatched_today || 0).toLocaleString());
  $('#kpiTotalDispatched').text(Number(kpi.dispatched || 0).toLocaleString());

  $('#tabBadgeReady').text(kpi.ready_to_dispatch || 0);
  $('#tabBadgeDispatched').text(kpi.dispatched || 0);
  $('#tabBadgePending').text(kpi.pending_allocated || 0);
  $('#tabBadgeAll').text(kpi.total_orders || 0);
}

// Render Orders in Table
function renderOrdersTable(orders) {
  if (!orders || orders.length === 0) {
    $('#ordersTableBody').html(`
      <tr>
        <td colspan="6" class="text-center py-5 text-muted">
          <i class="fa fa-clipboard-check fa-3x mb-2 text-secondary opacity-50"></i><br>
          <span class="fw-bold fs-6">No orders found matching the filter criteria.</span><br>
          <small class="text-muted">Try changing the status tab or adjusting your search term.</small>
        </td>
      </tr>
    `);
    return;
  }

  let rowsHtml = '';

  orders.forEach(order => {
    let pso = escapeHtml(order.psono || '-');
    let sono = escapeHtml(order.sono || '-');
    let clientName = escapeHtml(order.client_name || 'N/A');
    let clientPhone = escapeHtml(order.client_phone || 'N/A');
    let clientAddr = escapeHtml(order.delivery_address || '');
    let status = order.status || 'pending';
    let totalPlants = Number(order.total_plants || 0).toLocaleString();
    let totalTrays = Number(order.total_trays || 0).toLocaleString();

    // Status Badge & Vehicle Allocation Detection
    let isPlannedVehicle = (order.trip_status === 'Planned' || (order.allocated_vehicleno && order.allocated_vehicleno.trim() !== '') || order.has_vehicle_allocated);
    let cleanStatus = (order.status || '').toLowerCase();
    let isStockReady = (cleanStatus === 'readytodispatch' || cleanStatus === 'fulldispatch' || cleanStatus === 'partialdispatch' || cleanStatus === 'dispatched');

    let statusBadge = '';
    if (order.gatepass_no) {
      statusBadge = `<span class="badge-status badge-dispatched"><i class="fa-solid fa-circle-check"></i> Dispatched</span>`;
    } else if (isStockReady && isPlannedVehicle) {
      if (cleanStatus === 'readytodispatch') {
        statusBadge = `<span class="badge-status badge-ready-dispatch"><span class="pulse-dot"></span> Ready To Dispatch</span>`;
      } else if (cleanStatus === 'fulldispatch' || cleanStatus === 'dispatched') {
        statusBadge = `<span class="badge-status badge-ready-dispatch"><span class="pulse-dot"></span> Fully Loaded</span>`;
      } else if (cleanStatus === 'partialdispatch') {
        statusBadge = `<span class="badge-status" style="background:#fef3c7;color:#b45309;border:1px solid #fde68a;"><span class="pulse-dot" style="background-color:#d97706;box-shadow:0 0 0 0 rgba(217, 119, 6, 0.7);"></span> Partially Loaded</span>`;
      } else {
        statusBadge = `<span class="badge-status badge-ready-dispatch"><span class="pulse-dot"></span> Vehicle Ready</span>`;
      }
    } else if (isStockReady && !isPlannedVehicle) {
      statusBadge = `<span class="badge-status" style="background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;"><i class="fa-solid fa-clock me-1 text-primary"></i> Awaiting Vehicle</span>`;
    } else if (cleanStatus === 'allocated') {
      statusBadge = `<span class="badge-status badge-allocated"><i class="fa-solid fa-boxes-packing"></i> Allocated</span>`;
    } else {
      statusBadge = `<span class="badge-status badge-pending"><i class="fa-solid fa-clock"></i> ${escapeHtml(order.status || 'Pending')}</span>`;
    }

    // Action Button
    let actionBtn = '';
    if (order.gatepass_no) {
      let pdfUrl = order.gatepass_filepath ? ('../' + order.gatepass_filepath) : '';
      let gpNo = escapeHtml(order.gatepass_no || 'Pass');
      actionBtn = `
        <div class="d-flex flex-column gap-1 align-items-center">
          ${pdfUrl ? `
            <button type="button" class="btn-gp-view" onclick="previewGatePassPdf('${escapeAttr(pdfUrl)}', '${escapeAttr(gpNo)}')">
              <i class="fa-solid fa-file-pdf"></i> View Gate Pass
            </button>
          ` : `
            <span class="badge bg-secondary">Dispatched</span>
          `}
          ${order.gatepass_no ? `<small class="font-mono text-muted" style="font-size:0.75rem;">${gpNo}</small>` : ''}
        </div>
      `;
    } else if (isStockReady && isPlannedVehicle) {
      actionBtn = `
        <button type="button" class="btn-gp-generate" onclick="openGenerateGatePassModal('${escapeAttr(order.psono)}')">
          <i class="fa-solid fa-truck-ramp-box"></i> Generate Pass
        </button>
      `;
    } else if (isStockReady && !isPlannedVehicle) {
      actionBtn = `
        <a href="vehicle_allocation.php?psono=${encodeURIComponent(order.psono)}" class="btn-gp-allocate" target="_blank" style="background:#2563eb;color:#ffffff;text-decoration:none;font-size:0.82rem;font-weight:600;padding:0.45rem 0.85rem;border-radius:8px;display:inline-flex;align-items:center;gap:0.4rem;box-shadow:0 2px 4px rgba(37,99,235,0.25);" title="Assign a vehicle or Self Pickup in Vehicle Allocation">
          <i class="fa-solid fa-truck"></i> Allocate Vehicle
        </a>
      `;
    } else {
      actionBtn = `
        <button type="button" class="btn-gp-locked" title="Trays must be loaded in Ready to Load and vehicle allocated">
          <i class="fa-solid fa-lock"></i> Locked (${escapeHtml(order.status || 'Pending')})
        </button>
      `;
    }

    // Items Breakdown Pills (2-Column Responsive Grid + Batch Count)
    let itemsHtml = '';
    if (order.items && order.items.length > 0) {
      let pills = '';
      order.items.forEach(it => {
        let pName = escapeHtml(it.product_name || 'Item');
        let stk = escapeHtml(it.stockorderno || '-');
        let pQty = Number(it.plants || 0).toLocaleString();
        let tQty = Number(it.trays || 0).toLocaleString();
        pills += `
          <div class="product-pill" title="${pName} | Stock: ${stk}">
            <div class="flex-grow-1 min-w-0" style="overflow: hidden;">
              <div class="fw-bold text-dark text-truncate" style="font-size: 0.8rem; line-height: 1.25;" title="${pName}">${pName}</div>
              <div class="text-muted font-mono" style="font-size: 0.72rem; margin-top: 2px;">
                <i class="fa fa-barcode me-1 text-secondary opacity-75"></i>${stk}
              </div>
            </div>
            <div class="text-end flex-shrink-0 ms-2">
              <div class="text-success fw-bold" style="font-size: 0.82rem; line-height: 1.2;">${pQty} <span style="font-size:0.7rem; font-weight:500;">Pl</span></div>
              <div class="text-muted" style="font-size: 0.72rem;">${tQty} Trays</div>
            </div>
          </div>
        `;
      });
      itemsHtml = `
        <div class="d-flex justify-content-between align-items-center mb-1">
          <small class="text-muted fw-bold" style="font-size: 0.72rem;">
            <i class="fa-solid fa-boxes-stacked me-1 text-success"></i> ${order.items.length} ${order.items.length === 1 ? 'Batch / Item' : 'Batches / Lots'}
          </small>
        </div>
        <div class="product-items-grid">
          ${pills}
        </div>
      `;
    }

    let allocVeh = escapeHtml(order.allocated_vehicleno || '');
    let allocDriver = escapeHtml(order.allocated_drivername || '');

    rowsHtml += `
      <tr>
        <!-- 1. Order No -->
        <td>
          <div class="font-mono fw-bold text-primary fs-6 mb-1">${pso}</div>
          <div class="small text-muted font-mono mb-1"><span class="badge bg-light text-secondary border">SO: ${sono}</span></div>
          ${order.invoiceno ? `<div class="small text-success font-mono mb-1"><i class="fa-solid fa-file-invoice me-1"></i>${escapeHtml(order.invoiceno)}</div>` : ''}
          <div class="text-muted font-mono" style="font-size:0.72rem;"><i class="fa-regular fa-calendar me-1"></i>${order.created_at ? order.created_at.substring(0, 10) : ''}</div>
        </td>

        <!-- 2. Customer / Consignee -->
        <td>
          <div class="client-info-card">
            <div class="client-info-name">${clientName}</div>
            <div class="client-info-contact">
              <i class="fa-solid fa-phone text-success"></i>
              <a href="tel:${clientPhone}" class="text-decoration-none text-secondary fw-semibold">${clientPhone}</a>
            </div>
            ${clientAddr ? `
              <div class="client-info-address" title="${clientAddr}">
                <i class="fa-solid fa-location-dot me-1 text-danger"></i>${clientAddr}
              </div>
            ` : ''}
            ${allocVeh ? `
              <div class="mt-1">
                <span class="client-vehicle-badge" title="Allocated Vehicle">
                  <i class="fa-solid fa-truck text-success"></i>
                  <span>${allocVeh}</span>
                  ${allocDriver ? `<span class="text-secondary fw-normal ms-1">(${allocDriver})</span>` : ''}
                </span>
              </div>
            ` : ''}
          </div>
        </td>

        <!-- 3. Product Breakdown (Grid Layout) -->
        <td>
          ${itemsHtml}
        </td>

        <!-- 4. Plants & Trays (Stat Box) -->
        <td class="text-center">
          <div class="gp-stat-box">
            <div class="gp-stat-plants" title="Total Allocated Plants">
              <i class="fa-solid fa-seedling"></i> ${totalPlants}
            </div>
            <div class="gp-stat-trays" title="Total Trays">
              <i class="fa-solid fa-layer-group"></i> ${totalTrays} Trays
            </div>
          </div>
        </td>

        <!-- 5. Consumed Status -->
        <td class="text-center pt-3">
          ${statusBadge}
        </td>

        <!-- 6. Gate Pass Action -->
        <td class="text-center pt-3">
          ${actionBtn}
        </td>
      </tr>
    `;
  });

  $('#ordersTableBody').html(rowsHtml);
}

// Filter Actions
function updateActiveKpiCard(type) {
  $('.kpi-card').removeClass('active-kpi-emerald active-kpi-blue active-kpi-amber active-kpi-indigo');
  if (type === 'readytodispatch') {
    $('#kpiCardReady').addClass('active-kpi-emerald');
  } else if (type === 'all') {
    $('#kpiCardTotal').addClass('active-kpi-blue');
  } else if (type === 'dispatched_today') {
    $('#kpiCardToday').addClass('active-kpi-amber');
  } else if (type === 'dispatched') {
    $('#kpiCardDispatched').addClass('active-kpi-indigo');
  }
}

// Click Handler for KPI Cards
function filterByKpi(type) {
  if (type === 'dispatched_today') {
    let today = new Date().toISOString().split('T')[0];
    $('#fromDateInput').val(today);
    $('#toDateInput').val(today);
    currentStatusFilter = 'dispatched';
    $('.status-tab-btn').removeClass('active');
    $('.status-tab-btn[data-status="dispatched"]').addClass('active');
    updateActiveKpiCard('dispatched_today');
    loadGatepassOrders();
  } else {
    // Clear date filters if clicking standard KPI
    if (type !== 'dispatched_today' && ($('#fromDateInput').val() || $('#toDateInput').val())) {
      $('#fromDateInput').val('');
      $('#toDateInput').val('');
    }
    currentStatusFilter = type;
    $('.status-tab-btn').removeClass('active');
    let $tab = $(`.status-tab-btn[data-status="${type}"]`);
    if ($tab.length) {
      $tab.addClass('active');
    }
    updateActiveKpiCard(type);
    loadGatepassOrders();
  }
}

function setStatusFilter(status, btnElement) {
  let today = new Date().toISOString().split('T')[0];
  if ($('#fromDateInput').val() === today && $('#toDateInput').val() === today && status !== 'dispatched') {
    $('#fromDateInput').val('');
    $('#toDateInput').val('');
  }
  currentStatusFilter = status;
  $('.status-tab-btn').removeClass('active');
  $(btnElement).addClass('active');
  updateActiveKpiCard(status);
  loadGatepassOrders();
}

function handleSearchInput(e) {
  if (e.key === 'Enter') {
    applyFilters();
  }
}

function applyFilters() {
  currentSearchQuery = $('#searchInput').val().trim();
  loadGatepassOrders();
}

function resetFilters() {
  $('#searchInput').val('');
  $('#fromDateInput').val('');
  $('#toDateInput').val('');
  currentSearchQuery = '';
  currentStatusFilter = 'readytodispatch';
  $('.status-tab-btn').removeClass('active');
  $('.status-tab-btn[data-status="readytodispatch"]').addClass('active');
  updateActiveKpiCard('readytodispatch');
  loadGatepassOrders();
}

// Open Generate Gate Pass Modal
function openGenerateGatePassModal(psono) {
  if (!psono) return;

  $('#loadingText').text('Fetching order details & vehicle allocation...');
  $('#loadingOverlay').removeClass('d-none');

  $.ajax({
    url: 'modal/gatepass_modal.php',
    type: 'GET',
    data: { action: 'get_order_details', psono: psono },
    dataType: 'json',
    success: function(res) {
      $('#loadingOverlay').addClass('d-none');
      if (res.status === 'success') {
        if (!res.can_generate) {
          Swal.fire({
            title: 'Cannot Generate Gate Pass',
            html: `This order is not ready for gate pass generation.<br><br>` + 
                  (res.blocking_reasons ? res.blocking_reasons.join('<br>') : ''),
            icon: 'warning',
            confirmButtonColor: '#059669'
          });
          return;
        }

        let o = res.order;
        $('#formPsoNo').val(o.psono);
        $('#modalPsoNo').text(o.psono || '-');
        $('#modalSoNo').text(o.sono || '-');
        $('#modalClientInfo').text((o.client_name || 'Customer') + ' (' + (o.client_phone || 'No Phone') + ')');
        $('#modalDeliveryAddress').text(o.delivery_address || 'N/A');
        $('#modalTotalTrays').html(`<i class="fa-solid fa-layer-group"></i> ${Number(res.total_trays || 0).toLocaleString()} Trays`);
        $('#modalTotalPlants').html(`<i class="fa-solid fa-seedling"></i> ${Number(res.total_plants || 0).toLocaleString()} Plants`);

        // Render Items List
        let itemsHtml = '';
        res.items.forEach(it => {
          itemsHtml += `
            <div class="modal-item-row">
              <div>
                <div class="modal-item-title">${escapeHtml(it.product_name)}</div>
                <div class="d-flex align-items-center gap-1 mt-1">
                  ${it.stockorderno ? `<span class="modal-item-stk"><i class="fa fa-barcode me-1"></i>${escapeHtml(it.stockorderno)}</span>` : ''}
                  <span class="badge bg-light text-secondary border" style="font-size: 0.72rem;"><i class="fa fa-th me-1 text-primary"></i>${escapeHtml(it.tray_cavity || '126 Cavity')}</span>
                </div>
              </div>
              <div class="text-end">
                <div class="text-success fw-bold" style="font-size: 0.95rem;">+${Number(it.plants).toLocaleString()} Plants</div>
                <small class="modal-item-tray-badge">${it.trays} Trays</small>
              </div>
            </div>
          `;
        });
        $('#modalItemsList').html(itemsHtml);

        // Auto-fill Transport & Driver details from Allocated Trip
        let v = res.allocated_vehicle || {};
        $('#formVehicleNo').val(v.vehicleno || '');
        $('#formTransporterName').val(v.transporter_name || '');
        $('#formDriverName').val(v.drivername || '');
        $('#formDriverMobile').val(v.drivermobile || '');
        $('#formRemarks').val(v.remarks || '');

        // Auto-populate Vehicle Input (Master or Custom)
        if (v.vehicleno) {
          $('#vehicleMasterInput').val(v.vehicleno);
        } else {
          $('#vehicleMasterInput').val('');
        }

        $('#generateGatePassModal').modal('show');

      } else {
        Swal.fire('Error', res.message || 'Failed to fetch order details.', 'error');
      }
    },
    error: function() {
      $('#loadingOverlay').addClass('d-none');
      Swal.fire('Error', 'Server connection error.', 'error');
    }
  });
}

// Submit Generate Gate Pass Form
function submitGenerateGatePass() {
  let vehicleNo = $('#formVehicleNo').val().trim();
  if (!vehicleNo) {
    Swal.fire('Vehicle Number Required', 'Please enter a valid vehicle number for this gate pass.', 'warning');
    $('#formVehicleNo').focus();
    return;
  }

  let psono = $('#formPsoNo').val();

  Swal.fire({
    title: 'Generate Gate Pass?',
    html: `Are you sure you want to generate the <strong>Outward Gate Pass</strong> for order <b>${escapeHtml(psono)}</b> with Vehicle <b>${escapeHtml(vehicleNo)}</b>?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#059669',
    cancelButtonColor: '#64748b',
    confirmButtonText: '<i class="fa fa-check-circle me-1"></i> Yes, Generate Gate Pass'
  }).then((result) => {
    if (result.isConfirmed) {
      $('#generateGatePassModal').modal('hide');
      $('#loadingText').text('Generating Gate Pass PDF & Updating Status...');
      $('#loadingOverlay').removeClass('d-none');

      let formData = $('#gatePassForm').serialize();

      $.ajax({
        url: 'modal/gatepass_modal.php?action=generate_gatepass',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(res) {
          $('#loadingOverlay').addClass('d-none');
          if (res.status === 'success') {
            Swal.fire({
              title: 'Gate Pass Generated!',
              html: `Gate Pass <strong>${escapeHtml(res.gatepass_no)}</strong> has been generated successfully.<br>Order marked as <strong>Dispatched</strong>.<br><br>
                     <button class="btn btn-primary fw-bold px-3 py-2" onclick="previewGatePassPdf('${escapeAttr(res.pdf_url)}', '${escapeAttr(res.gatepass_no)}')">
                       <i class="fa fa-file-pdf me-1"></i> View / Download Gate Pass PDF
                     </button>`,
              icon: 'success',
              confirmButtonColor: '#059669',
              confirmButtonText: 'Done'
            });
            loadGatepassOrders();
          } else {
            Swal.fire('Gate Pass Generation Failed', res.message || 'Error occurred.', 'error');
          }
        },
        error: function() {
          $('#loadingOverlay').addClass('d-none');
          Swal.fire('Server Error', 'Failed to communicate with server while generating gate pass.', 'error');
        }
      });
    }
  });
}

// Preview Gate Pass PDF Modal (Always renders fresh latest 2-copy layout with cache-busting)
function previewGatePassPdf(pdfUrl, gpNo) {
  let viewUrl = '';
  if (gpNo) {
    viewUrl = `modal/gatepass_modal.php?action=view_pdf&gatepass_no=${encodeURIComponent(gpNo)}&t=${Date.now()}`;
  } else if (pdfUrl) {
    viewUrl = pdfUrl + (pdfUrl.indexOf('?') === -1 ? '?' : '&') + 't=' + Date.now();
  }
  if (!viewUrl) return;

  $('#pdfModalTitle').html(`<i class="fa fa-file-pdf me-2 text-danger"></i> Outward Gate Pass - ${escapeHtml(gpNo || '')}`);
  $('#pdfDownloadLink').attr('href', viewUrl);
  $('#pdfIframe').attr('src', viewUrl);
  $('#pdfPreviewModal').modal('show');
}

// Close Modal Handlers
function closeGatePassModal() {
  $('#generateGatePassModal').modal('hide');
}

function closePdfPreviewModal() {
  $('#pdfPreviewModal').modal('hide');
}

// Helper: Escape HTML
function escapeHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function escapeAttr(str) {
  if (!str) return '';
  return String(str).replace(/'/g, "\\'");
}
</script>

<?php include('../footer.php'); ?>
