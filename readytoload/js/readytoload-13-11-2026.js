/**
 * Ready To Load - Frontend Logic & Scanner Controller
 * Path: /readytoload/js/readytoload.js
 */

(function ($) {
  'use strict';

  // State Management
  let currentActivePsono = '';
  let currentActiveSono = '';
  let currentActiveItemId = 0;
  let currentActiveProductId = '';
  let currentActiveInvoiceNo = '';
  let currentActiveProduct = '';
  let currentActiveVariety = '';
  let currentActiveStockorder = '';
  let currentActiveClientId = '';
  let currentActiveClientName = '';
  let currentTargetRequiredTrays = 0;
  let currentTargetRequiredPlants = 0;
  let currentActiveReadyToSellTrays = 0;
  let currentActiveReadyToSellPlants = 0;
  let currentActiveLoadedTrays = 0;
  let currentActiveLoadedPlants = 0;
  let currentActivePendingTrays = 0;
  let currentActivePendingPlants = 0;
  let currentActiveIsShifted = false;
  let currentAllocatedBedsDetail = [];
  let currentBonusPlantsRequired = 0;
  let currentBonusPlantsVerified = 0;
  let currentBonusTraysList = [];
  let currentScanTargetMode = 'billed'; // 'billed' | 'bonus'

  let scannedTraysList = [];
  let isCameraScanning = false;
  let html5QrCode = null;
  let isAlertModalOpen = false;

  // Audio Context Synthesizer (Zero external file dependencies)
  function playScanBeep() {
    try {
      const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
      const osc = audioCtx.createOscillator();
      const gain = audioCtx.createGain();
      osc.connect(gain);
      gain.connect(audioCtx.destination);
      osc.type = "sine";
      osc.frequency.setValueAtTime(880, audioCtx.currentTime);
      gain.gain.setValueAtTime(0.25, audioCtx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + 0.14);
      osc.start();
      osc.stop(audioCtx.currentTime + 0.14);
    } catch (e) { }
  }

  function playSuccessTone() {
    try {
      const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
      const osc = audioCtx.createOscillator();
      const gain = audioCtx.createGain();
      osc.connect(gain);
      gain.connect(audioCtx.destination);
      osc.type = "sine";
      osc.frequency.setValueAtTime(587.33, audioCtx.currentTime); // D5
      osc.frequency.setValueAtTime(880, audioCtx.currentTime + 0.1); // A5
      gain.gain.setValueAtTime(0.25, audioCtx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + 0.25);
      osc.start();
      osc.stop(audioCtx.currentTime + 0.25);
    } catch (e) { }
  }

  function playWarningTone() {
    try {
      const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
      const osc = audioCtx.createOscillator();
      const gain = audioCtx.createGain();
      osc.connect(gain);
      gain.connect(audioCtx.destination);
      osc.type = "sawtooth";
      osc.frequency.setValueAtTime(360, audioCtx.currentTime);
      osc.frequency.setValueAtTime(260, audioCtx.currentTime + 0.12);
      gain.gain.setValueAtTime(0.3, audioCtx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + 0.3);
      osc.start();
      osc.stop(audioCtx.currentTime + 0.3);
    } catch (e) { }
  }

  window.playScanBeep = playScanBeep;
  window.playSuccessTone = playSuccessTone;
  window.playWarningTone = playWarningTone;

  function esc(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function formatDisplayDate(dateStr) {
    if (!dateStr || dateStr === '-' || dateStr === 'As scheduled') return dateStr || 'As scheduled';
    const trimmed = String(dateStr).trim();
    if (/^\d{1,2}-[A-Za-z]{3}-\d{4}$/.test(trimmed)) {
      return trimmed;
    }
    const parts = trimmed.split(' ')[0].split('-');
    if (parts.length === 3 && parts[0].length === 4) {
      const year = parts[0];
      const monthIdx = parseInt(parts[1], 10) - 1;
      const day = parseInt(parts[2], 10);
      const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
      if (monthIdx >= 0 && monthIdx < 12 && !isNaN(day)) {
        const paddedDay = day < 10 ? '0' + day : day;
        return `${paddedDay}-${monthNames[monthIdx]}-${year}`;
      }
    }
    const d = new Date(dateStr);
    if (!isNaN(d.getTime())) {
      const day = d.getDate() < 10 ? '0' + d.getDate() : d.getDate();
      const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
      return `${day}-${monthNames[d.getMonth()]}-${d.getFullYear()}`;
    }
    return dateStr;
  }

  // -------------------------------------------------------------
  // 1. Fetch & Render Orders (Grouped by PSO)
  // -------------------------------------------------------------
  let activeOrderListXhr = null;
  let searchDebounceTimer = null;
  window.filterMismatchActive = false;

  // Toggle Compact / Small Cards View Mode
  window.toggleCompactCardsView = function () {
    const $c = $('#ordersContainer');
    $c.toggleClass('compact-cards');
    const isCompact = $c.hasClass('compact-cards');
    try {
      localStorage.setItem('rtl_compact_view', isCompact ? '1' : '0');
    } catch (e) {}
    $('#btnToggleCompactView').toggleClass('active', isCompact);
    $('#lblCompactView').text(isCompact ? 'Standard View' : 'Small Cards');
  };

  // Toggle Mismatches Quick Filter
  window.toggleMismatchFilter = function () {
    window.filterMismatchActive = !window.filterMismatchActive;
    $('#btnFilterMismatch').toggleClass('active', window.filterMismatchActive);
    loadOrdersList(true);
  };

  // Apply filter changes
  window.applyFilters = function () {
    loadOrdersList(true);
  };

  // Reset all filters to defaults
  window.resetAllFilters = function () {
    $('#filterSearch').val('');
    $('#btnClearSearch').hide();
    $('#filterStatus').val('readytoload');
    $('#filterYear').val('all');
    $('#filterPolyhouse').val('');
    $('#filterCrop').val('');
    window.filterMismatchActive = false;
    $('#btnFilterMismatch').removeClass('active');
    loadOrdersList(true);
  };

  window.loadOrdersList = function (showFullLoader = true, callback = null) {
    const currentScrollPos = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
    const $container = $('#ordersContainer');
    const filterStatus = $('#filterStatus').val() || 'readytoload';
    const filterYear = $('#filterYear').val() || 'all';
    const filterPolyhouse = $('#filterPolyhouse').val() || '';
    const filterCrop = $('#filterCrop').val() || '';
    const searchQuery = ($('#filterSearch').val() || '').trim();
    const mismatchOnly = window.filterMismatchActive ? 1 : 0;

    let loaderTitle = 'Loading Ready to Load Orders...';
    if (filterStatus === 'loaded') loaderTitle = 'Loading Loaded Orders...';
    else if (filterStatus === 'dispatched') loaderTitle = 'Loading Dispatched Orders...';
    else if (filterStatus === 'all') loaderTitle = 'Loading All Orders...';

    if (showFullLoader) {
      $container.html(`
        <div class="simple-loader-box">
          <div class="simple-loader-spinner"></div>
          <div class="simple-loader-title">${loaderTitle}</div>
          <div class="simple-loader-sub">Please wait while order details and plant trays are fetched</div>
        </div>
      `);
    } else {
      $container.css({ 'opacity': '0.7', 'transition': 'opacity 0.15s ease' });
    }

    if (activeOrderListXhr) {
      activeOrderListXhr.abort();
    }

    activeOrderListXhr = $.ajax({
      url: 'model/readytoload_model.php',
      type: 'GET',
      dataType: 'json',
      data: {
        action: 'get_orders',
        status: filterStatus,
        filter_status: filterStatus,
        year: filterYear,
        polyhouse: filterPolyhouse,
        crop: filterCrop,
        mismatch_only: mismatchOnly,
        search: searchQuery
      },
      complete: function () {
        $container.css({ 'opacity': '1' });
        if (typeof callback === 'function') {
          callback();
        }
      },
      success: function (res) {
        if (res.status !== 'success') {
          $container.html(`
            <div class="alert alert-danger m-3">
              <i class="fa fa-exclamation-triangle mr-2"></i> ${esc(res.message || 'Error loading records.')}
            </div>
          `);
          return;
        }

        // Update KPI metrics
        const m = res.metrics || {};
        $('#kpiTotalOrders').text(m.total_orders || 0);
        $('#kpiTotalPlants').text(Number(m.total_plants || 0).toLocaleString());
        $('#kpiTotalTrays').text(Number(m.total_trays || 0).toLocaleString());
        $('#kpiLoadedTrays').text(Number(m.total_loaded_trays || 0).toLocaleString());

        // Update mismatch counter badge
        const mismatchCnt = Number(m.total_mismatched_orders || 0);
        if (mismatchCnt > 0) {
          $('#lblMismatchCount').text(mismatchCnt).show();
        } else {
          $('#lblMismatchCount').hide();
        }

        // Dynamically populate Crop filter options and index items
        const currentCropVal = $('#filterCrop').val();
        const orders = res.data || [];
        window._ordersDataCache = orders;
        window._itemsLookup = {};
        const cropSet = new Set();
        orders.forEach(ord => {
          if (ord.items && Array.isArray(ord.items)) {
            ord.items.forEach(it => {
              window._itemsLookup[ord.psono + '_' + (it.item_id || it.id || 0)] = { item: it, order: ord };
              if (!window._itemsLookup[ord.psono]) {
                window._itemsLookup[ord.psono] = { item: it, order: ord };
              }
              if (it.product_name) {
                // Get clean base crop name
                const cropName = it.product_name.split(',')[0].split('-')[0].trim();
                if (cropName) cropSet.add(cropName);
              }
            });
          }
        });
        if (!currentCropVal && cropSet.size > 0) {
          let cropOptions = '<option value="">All Crops</option>';
          Array.from(cropSet).sort().forEach(c => {
            cropOptions += `<option value="${esc(c)}">${esc(c)}</option>`;
          });
          $('#filterCrop').html(cropOptions);
        }

        if (orders.length === 0) {
          if (searchQuery || window.filterMismatchActive || filterYear !== 'all' || filterPolyhouse || filterCrop) {
            $container.html(`
              <div class="empty-state-card">
                <div class="empty-state-icon">
                  <i class="fa fa-filter"></i>
                </div>
                <h3>No Matching Orders Found</h3>
                <p>No orders match the selected filters or search parameters.</p>
                <button type="button" class="btn btn-sm btn-outline-primary font-weight-bold" onclick="resetAllFilters();">
                  <i class="fa fa-rotate mr-1"></i> Reset All Filters
                </button>
              </div>
            `);
          } else {
            let emptyStatusTitle = "No Orders in 'Ready to Load' Status";
            let emptyStatusMsg = "There are currently no sales order items marked as 'readytoload'.";
            if (filterStatus === 'loaded') {
              emptyStatusTitle = "No Orders in 'Loaded' Status";
              emptyStatusMsg = "There are currently no orders in 'Loaded / Ready to Dispatch' status.";
            } else if (filterStatus === 'dispatched') {
              emptyStatusTitle = "No Orders in 'Dispatched' Status";
              emptyStatusMsg = "There are currently no orders in 'Dispatched' status.";
            } else if (filterStatus === 'all') {
              emptyStatusTitle = "No Orders Found";
              emptyStatusMsg = "No orders found for the selected criteria.";
            }

            $container.html(`
              <div class="empty-state-card">
                <div class="empty-state-icon">
                  <i class="fa fa-boxes-packing"></i>
                </div>
                <h3>${esc(emptyStatusTitle)}</h3>
                <p>${esc(emptyStatusMsg)}</p>
              </div>
            `);
          }
          return;
        }

        let html = '';
        window.rtlItemRegistry = window.rtlItemRegistry || {};
        const isRoleAdmin = (typeof window.loginRoleId !== 'undefined' && Number(window.loginRoleId) < 2);
        orders.forEach(function (ord) {
          const psoNo = ord.psono || 'PSO-N/A';
          const soNo = ord.sono || '';
          const stockOrderNo = ord.stockorder_no || (ord.items && ord.items[0] && ord.items[0].stockorder_no !== '-' ? ord.items[0].stockorder_no : '');
          const clientName = ord.clientname || 'Valued Client';
          const clientPhone = ord.phone ? ` &bull; <i class="fa fa-phone text-muted ml-1"></i> ${esc(ord.phone)}` : '';
          const clientAddr = ord.address ? `<div class="meta-item"><i class="fa fa-map-marker-alt text-danger"></i> <span>${esc(ord.address)}</span></div>` : '';
          const delDate = formatDisplayDate(ord.deliverydate);

          // Progress calculation based on allocated trays to load
          const totalTrays = ord.total_allocated_trays > 0 ? ord.total_allocated_trays : (ord.total_order_trays || 0);
          const loadedTrays = ord.total_loaded_trays || 0;
          const totalPlants = ord.total_allocated_plants > 0 ? ord.total_allocated_plants : (ord.total_order_plants || 0);
          const loadedPlants = ord.total_loaded_plants || 0;
          const pct = totalTrays > 0 ? Math.min(100, Math.round((loadedTrays / totalTrays) * 100)) : 0;

          let statusBadgeClass = 'badge-readytoload';
          let statusLabel = 'Ready to Load';
          const dispTraysTotal = Number(ord.total_dispatched_trays || 0);
          const allItemsDispatched = (ord.items && ord.items.length > 0 && ord.items.every(it =>
            Boolean(it.is_dispatched) || ['dispatched', 'fulldispatch', 'final dispatch'].includes(String(it.status || '').toLowerCase()) || ['dispatched', 'fulldispatch', 'final dispatch'].includes(String(it.sii_status || '').toLowerCase())
          ));
          if (allItemsDispatched) {
            statusBadgeClass = 'badge-dispatched';
            statusLabel = 'Dispatched';
          } else if (ord.is_all_loaded || (loadedTrays >= totalTrays && totalTrays > 0)) {
            statusBadgeClass = 'badge-loaded';
            statusLabel = 'Fully Loaded';
          } else if (loadedTrays > 0) {
            statusBadgeClass = 'badge-partial';
            statusLabel = 'Partially Loaded';
          }

          html += `
            <div class="order-box" id="orderBox_${esc(ord.group_key || psoNo)}" onclick="selectActiveOrderBox(this)">
              <!-- 1. Order Box Header -->
              <div class="order-box-header">
                <div class="order-header-left">
                  <!-- Order Badges Group -->
                  <div class="order-numbers-group">
                    <span class="pso-pill" title="Provisional Sales Order">
                      <i class="fa fa-file-invoice"></i> ${esc(psoNo)}
                    </span>
                    ${stockOrderNo ? (
                      ((ord.allocated_batches && ord.allocated_batches.length > 1) || stockOrderNo.includes(',')) ? `
                        <span class="sono-badge" style="background:#1e40af; color:#fff;" title="Multi-Batch Allocation: ${esc(stockOrderNo)}">
                          <i class="fa fa-layer-group mr-1" style="opacity:0.85;"></i> Batches: ${esc(stockOrderNo)}
                        </span>` : `
                        <span class="sono-badge" title="Stock Order Number">
                          <i class="fa fa-barcode mr-1" style="opacity:0.85;"></i> Stock: ${esc(stockOrderNo)}
                        </span>`
                    ) : ''}
                    ${ord.is_swapped ? `
                      <span class="badge bg-warning-subtle text-warning-emphasis border border-warning d-inline-flex align-items-center" style="font-size: 11px; padding: 4px 8px; font-weight: 600; border-radius: 6px; background-color: #fef3c7; color: #92400e; border-color: #f59e0b !important;" title="Swapped from original batch ${esc(ord.original_stockorder_no || '')}">
                        <i class="fa fa-exchange-alt mr-1"></i> Swapped
                      </span>` : ''}
                    ${ord.has_mismatch ? `
                      <span class="badge bg-danger-subtle text-danger border border-danger d-inline-flex align-items-center" style="font-size: 11px; padding: 4px 8px; font-weight: 700; border-radius: 6px; background-color: #fef2f2; color: #dc2626; border-color: #fca5a5 !important;" title="${esc(ord.mismatch_summary || 'Quantity Mismatch')}">
                        <i class="fa fa-triangle-exclamation mr-1"></i> Mismatch Warning
                      </span>` : ''}
                    ${(ord.box_packing_count && ord.box_packing_count > 0) ? `
                      <span class="badge bg-amber-100 text-amber-900 border border-amber-300 d-inline-flex align-items-center" style="font-size: 11px; padding: 4px 8px; font-weight: 600; border-radius: 6px; background-color: #ffedd5; color: #9a3412; border-color: #fdba74 !important;" title="Box Packing Required">
                        <i class="fa fa-box mr-1"></i> Box Packing: ${ord.box_packing_count} Box${ord.box_packing_count > 1 ? 'es' : ''}
                      </span>` : ''}
                    ${(ord.total_dispatched_trays > 0) ? `
                      <span class="badge d-inline-flex align-items-center" style="font-size: 11px; padding: 4px 8px; font-weight: 600; border-radius: 6px; background-color: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0 !important;" title="Dispatched: ${ord.total_dispatched_trays} Trays (${Number(ord.total_dispatched_plants).toLocaleString()} Plants)">
                        <i class="fa fa-truck mr-1"></i> Dispatched: ${ord.total_dispatched_trays} Trays
                      </span>` : ((ord.total_prior_dispatched_trays > 0) ? `
                      <span class="badge d-inline-flex align-items-center" style="font-size: 11px; padding: 4px 8px; font-weight: 600; border-radius: 6px; background-color: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0 !important;" title="Dispatched in Previous Trips: ${ord.total_prior_dispatched_trays} Trays (${Number(ord.total_prior_dispatched_plants || 0).toLocaleString()} Plants)">
                        <i class="fa fa-truck mr-1"></i> Prior Dispatched: ${ord.total_prior_dispatched_trays} Trays
                      </span>` : '')}
                  </div>

                  <!-- Client Card Pill -->
                  <div class="client-pill-card">
                    <div class="client-avatar">
                      <i class="fa fa-user"></i>
                    </div>
                    <div class="client-details-col">
                      <span class="client-name-text">${esc(clientName)}</span>
                      ${ord.phone ? `
                        <a href="tel:${esc(ord.phone)}" class="client-phone-link" title="Call Client">
                          <i class="fa fa-phone-alt"></i> ${esc(ord.phone)}
                        </a>` : ''}
                    </div>
                  </div>
                </div>

                <!-- Right Header Details -->
                <div class="order-header-right">
                  <div class="delivery-chip" title="Expected Delivery Date">
                    <i class="fa fa-calendar-day text-primary mr-1"></i>
                    <span>Delivery: <strong>${esc(delDate)}</strong></span>
                  </div>

                  <span class="order-status-badge ${statusBadgeClass}">
                    <span class="pulse-dot"></span> ${statusLabel}
                  </span>
                  ${(isRoleAdmin && ord.status && ord.status !== 'readytoload') ? `
                    <button type="button" class="btn-revert-order" onclick="revertOrderAction('${esc(ord.invoiceno || '')}', '${esc(psoNo)}')" title="Revert order back to Ready to Load status">
                      <i class="fa fa-undo mr-1"></i> Revert Status
                    </button>` : ''}
                  <button type="button" class="btn-refresh-order" onclick="refreshSingleOrder('${esc(psoNo)}', this)" title="Refresh data for this order">
                    <i class="fa fa-sync-alt mr-1"></i> Refresh data
                  </button>
                </div>
              </div>

              <!-- Crops Strip List -->
              <div class="order-crops-list">
          `;

          ord.items.forEach(function (item, idx) {
            const itemKey = 'item_' + (item.item_id || '0') + '_' + (item.sii_id || '0') + '_' + idx + '_' + Math.random().toString(36).substr(2, 6);
            window.rtlItemRegistry[itemKey] = item;
            let allocatedBedTags = '';
            if (item.allocated_beds_detail && Array.isArray(item.allocated_beds_detail) && item.allocated_beds_detail.length > 0) {
              const seenBeds = new Set();
              const uniqueBeds = [];
              item.allocated_beds_detail.forEach(b => {
                const k = `${b.stockorder_no || ''}_${b.polyhouse_id || b.polyhouse_name || ''}_${b.bed_name || ''}`;
                if (!seenBeds.has(k)) {
                  seenBeds.add(k);
                  uniqueBeds.push(b);
                }
              });
              allocatedBedTags = uniqueBeds.map(b => {
                const bPlants = Number(b.plants || b.allocated_qty || 0);
                const bTrays = Number(b.tray_count || 0);
                return `
                <span class="bed-tag-pill" style="background:#ecfdf5; color:#065f46; border:1.5px solid #10b981; font-weight:600; padding:2.5px 8px; border-radius:5px; box-shadow:0 1px 2px rgba(16,185,129,0.08); display:inline-flex; align-items:center; gap:4px; margin:1px 0;" title="Allocated Bed: ${esc(b.polyhouse_name || '')} - ${esc(b.bed_name || '')} (${bPlants.toLocaleString()} Plants, ${bTrays} Trays) Batch: ${esc(b.stockorder_no || '')}">
                  <i class="fa fa-th-large text-success"></i>
                  <span class="bed-loc-name" style="font-weight:700;">${esc(b.polyhouse_name || '')}</span>
                  <span class="bed-loc-sep">&bull;</span>
                  <span class="bed-loc-num" style="font-weight:800; color:#047857;">${esc(b.bed_name || '')}:</span>
                  <span class="bed-loc-qty" style="font-weight:700; color:#065f46; margin-left:2px;">${bPlants.toLocaleString()} Plants <small style="opacity:0.85;">(${bTrays}T)</small></span>
                  ${b.stockorder_no ? `<span class="badge" style="background:#059669; color:#fff; font-size:10px; margin-left:5px; padding:1.5px 5px; border-radius:4px; font-weight:600;">${esc(b.stockorder_no)}</span>` : ''}
                </span>`;
              }).join(' ');
            }

            let bedTags = '';
            // Only show generic active_beds if no allocated_beds_detail exists
            if ((!item.allocated_beds_detail || item.allocated_beds_detail.length === 0) && item.active_beds && item.active_beds.length > 0) {
              bedTags = item.active_beds.map(b => `
                <span class="bed-tag-pill" title="Location: ${esc(b.polyhouse)} - ${esc(b.bed_no)} (${b.tray_count} Trays)">
                  <i class="fa fa-warehouse"></i>
                  <span class="bed-loc-name">${esc(b.polyhouse)}</span>
                  <span class="bed-loc-sep">&bull;</span>
                  <span class="bed-loc-num">${esc(b.bed_no)}</span>
                  <span class="bed-loc-qty">(${b.tray_count})</span>
                </span>
              `).join(' ');
            }

            // Shifting Status Tag (Strictly from Ready Stock Allocation)
            let shiftTag = '';
            if (item.isshifted === 'yes' || (item.shifted_plants && item.shifted_plants > 0)) {
              const shiftCount = item.quantity || item.allocated_qty || item.healthyplant || 0;
              const extraCount = item.shiftingqty > 0 ? item.shiftingqty : (item.est_trays || Math.ceil(shiftCount / 125));
              const extraText = extraCount > 0 ? ` (+${Number(extraCount).toLocaleString()} Free Bonus)` : '';
              shiftTag = `<span class="shift-tag-pill shift-yes" title="Shifted Order: ${Number(shiftCount).toLocaleString()} Billed + ${Number(extraCount).toLocaleString()} Free Bonus Plants"><i class="fa fa-exchange-alt mr-1"></i>Shifted ${Number(shiftCount).toLocaleString()}${extraText}</span>`;
            } else {
              const unshiftCount = item.unshifted_plants || item.quantity || 0;
              const extraText = item.shiftingqty > 0 ? ` (+${Number(item.shiftingqty).toLocaleString()} Extra)` : '';
              shiftTag = `<span class="shift-tag-pill shift-no" title="Unshifted Plants (from Ready Stock Allocation): ${Number(unshiftCount).toLocaleString()} Plants"><i class="fa fa-ban mr-1"></i>Unshifted (${Number(unshiftCount).toLocaleString()}${extraText})</span>`;
            }

            // Healthy & Unhealthy Plants
            let healthTags = '';
            if (item.healthyplant !== null && item.healthyplant !== undefined) {
              healthTags += `<span class="health-tag-pill health-good" title="Healthy Plants"><i class="fa fa-heart text-danger mr-1"></i>Healthy: <strong>${Number(item.healthyplant).toLocaleString()}</strong></span>`;
            }
            if (item.unhealthyplant !== null && item.unhealthyplant !== undefined && item.unhealthyplant > 0) {
              healthTags += `<span class="health-tag-pill health-bad" title="Unhealthy Plants"><i class="fa fa-biohazard mr-1"></i>Unhealthy: <strong>${Number(item.unhealthyplant).toLocaleString()}</strong></span>`;
            }

            // Ready to Sale Tag Pill (suppress when 0 to avoid clutter)
            const rtsTraysCount = item.ready_to_sell_trays || 0;
            const rtsTag = (rtsTraysCount > 0) ? `<span class="rts-tag-pill" title="Ready to Sale (${rtsTraysCount} Trays)"><i class="fa fa-boxes mr-1"></i>Ready to Sale: <strong>${rtsTraysCount}</strong></span>` : '';

            const targetTrays = (item.allocated_trays > 0) ? item.allocated_trays : (item.est_trays || 0);
            const isItemDispatched = Boolean(item.is_dispatched) || ['dispatched', 'fulldispatch', 'final dispatch'].includes(String(item.status || '').toLowerCase()) || ['dispatched', 'fulldispatch', 'final dispatch'].includes(String(item.sii_status || '').toLowerCase());
            const isDone = (item.loaded_trays >= targetTrays && targetTrays > 0);
            const hasAlloc = (item.allocated_trays || 0) > 0 || (item.allocated_qty || 0) > 0;
            const hasLoadedTrays = (item.loaded_trays || 0) > 0;

            let actionBtn = '';
            if (isItemDispatched) {
              actionBtn = `<span class="badge" style="background: #059669; color: #fff; font-size: 11.5px; padding: 4.5px 10px; border-radius: 4px; font-weight: 600;"><i class="fa fa-truck mr-1"></i> Dispatched</span>`;
            } else if (isDone && !item.is_mismatch) {
              actionBtn = `<span class="btn-loaded-done"><i class="fa fa-check-circle"></i> Loaded</span>`;
            } else if (item.is_stock_available === false) {
              actionBtn = `<button type="button" class="btn-open-load btn-load-disabled" disabled style="background: #f1f5f9 !important; color: #94a3b8 !important; border: 1.5px dashed #cbd5e1 !important; cursor: not-allowed; box-shadow: none;" title="${esc(item.stock_warning || 'Re-allocate it due to qty not available in this polyhouse')}">
                             <i class="fa fa-ban mr-1"></i> <span>Load Trays</span>
                           </button>`;
            } else if (!hasAlloc) {
              actionBtn = `<button type="button" class="btn-open-load btn-load-unallocated" onclick="openScannerModal('${itemKey}')" title="Allocated quantity is 0 Trays. Click for details.">
                             <i class="fa fa-lock"></i> <span>Load Trays</span>
                           </button>`;
            } else {
              actionBtn = `<button type="button" class="btn-open-load" onclick="openScannerModal('${itemKey}')">
                             <i class="fa fa-camera"></i> <span>${(item.is_mismatch || isDone) ? 'Reload Trays' : 'Load Trays'}</span>
                           </button>`;
            }

            let undoBtn = '';
            const isRoleAdmin = (typeof window.loginRoleId !== 'undefined' && Number(window.loginRoleId) < 2);
            if (!isItemDispatched && isRoleAdmin && hasLoadedTrays) {
              undoBtn = `<button type="button" class="btn-undo-trays" onclick="openLoadedTraysModal('${itemKey}')" title="View & Undo Loaded Trays (${item.loaded_trays} Trays Loaded)">
                          <i class="fa fa-rotate-left mr-1"></i> Undo (${item.loaded_trays})
                        </button>`;
            }

            let rsoAutoBtn = '';
            if (!isItemDispatched && isRoleAdmin && item.is_rso && Number(item.loaded_trays || 0) === 0) {
              rsoAutoBtn = `<button type="button" class="btn btn-xs btn-outline-primary font-weight-bold ml-1" onclick="autoLoadRsoTrays('${esc(psoNo)}', '${esc(item.allocated_stockorder_no || item.stockorder_no || '')}', ${item.quantity || 0}, '${esc(item.clientid || '')}', '${esc(item.invoiceno || '')}')" style="height: 32px; border-radius: 6px; padding: 0 10px; display: inline-flex; align-items: center;" title="Auto-load active trays from RSO batch ${esc(item.allocated_stockorder_no || item.stockorder_no || '')}">
                              <i class="fa fa-magic mr-1"></i> Auto-Load
                            </button>`;
            }

            // Stock order badge group (Multi-Batch vs Swapped vs Own Sown)
            let stockBadgeHtml = '';
            const allocBatch = (item.allocated_stockorder_no || item.stockorder_no || '').trim();
            const origBatch = (item.original_stockorder_no || '').trim();
            const hasMultiBatch = (item.allocated_batches && item.allocated_batches.length > 1) || allocBatch.includes(',');

            if (hasMultiBatch) {
              let batchPills = '';
              let batchList = [];
              if (item.allocated_batches && item.allocated_batches.length > 0) {
                // Deduplicate by stockorder_no and aggregate qty
                const bMap = new Map();
                item.allocated_batches.forEach(b => {
                  const s = $.trim(b.stockorder_no || '');
                  if (!s) return;
                  const q = Number(b.allocated_qty || b.consumed_qty || 0);
                  if (bMap.has(s)) {
                    bMap.get(s).allocated_qty += q;
                  } else {
                    bMap.set(s, { stockorder_no: s, allocated_qty: q });
                  }
                });
                batchList = Array.from(bMap.values());
                batchPills = batchList.map(b => 
                  `<span class="badge" style="background:#2563eb; color:#fff; font-size:11px; padding:2.5px 7px; border-radius:4px; font-weight:600;" title="Batch ${esc(b.stockorder_no)}: ${Number(b.allocated_qty).toLocaleString()} plants">
                    <i class="fa fa-boxes mr-1" style="opacity:0.85;"></i>${esc(b.stockorder_no)} <small style="opacity:0.9;">(${Number(b.allocated_qty).toLocaleString()})</small>
                  </span>`
                ).join(' ');
              } else {
                const parts = Array.from(new Set(allocBatch.split(',').map(s => s.trim()).filter(Boolean)));
                batchPills = parts.map(p => 
                  `<span class="badge" style="background:#2563eb; color:#fff; font-size:11px; padding:2.5px 7px; border-radius:4px; font-weight:600;">
                    <i class="fa fa-boxes mr-1" style="opacity:0.85;"></i>${esc(p)}
                  </span>`
                ).join(' ');
                batchList = parts.map(p => ({ stockorder_no: p }));
              }
              const countBadge = batchList.length;
              stockBadgeHtml = `
                <span class="stock-multibatch-group d-inline-flex align-items-center flex-wrap" style="gap:4px 5px; margin:1px 0;">
                  <span class="badge" style="background:#1e3a8a; color:#fff; font-size:11px; padding:2.5px 7px; border-radius:4px; font-weight:700;" title="Allocated across ${countBadge} batches">
                    <i class="fa fa-layer-group mr-1"></i>Multi-Batch (${countBadge}):
                  </span>
                  ${batchPills}
                  ${item.is_swapped ? `
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning px-1.5 py-0.5" style="font-size: 10px; background-color: #fef3c7; color: #92400e; border-color: #f59e0b !important; padding:2.5px 6px; border-radius:4px;" title="Swapped / Re-allocated from batch ${esc(origBatch)}">
                      <i class="fa fa-exchange-alt mr-1"></i>Swapped
                    </span>` : ''}
                </span>
              `;
            } else if (item.is_swapped) {
              stockBadgeHtml = `
                <span class="stock-swap-group d-inline-flex align-items-center flex-wrap" style="gap:4px 5px; margin:1px 0;">
                  <span class="badge bg-primary text-white font-weight-bold px-2 py-1" style="font-size: 11px; background-color: #2563eb; padding:2.5px 7px; border-radius:4px;" title="Allocated Batch">
                    Allocated: <strong>${esc(allocBatch)}</strong>
                  </span>
                  <span class="badge bg-warning-subtle text-warning-emphasis border border-warning px-1.5 py-0.5" style="font-size: 10px; background-color: #fef3c7; color: #92400e; border-color: #f59e0b !important; padding:2.5px 6px; border-radius:4px;" title="Swapped / Re-allocated from batch ${esc(origBatch)}">
                    <i class="fa fa-exchange-alt mr-1"></i>Swapped
                  </span>
                  <span class="badge bg-light text-secondary border font-monospace px-1.5 py-0.5" style="font-size: 10px; padding:2.5px 6px; border-radius:4px;" title="Original Sown Batch">
                    Original Sown: <strong>${esc(origBatch)}</strong>
                  </span>
                </span>
              `;
            } else if (allocBatch && allocBatch !== '-' && allocBatch !== 'N/A') {
              stockBadgeHtml = `
                <span class="stock-own-group d-inline-flex align-items-center flex-wrap" style="gap:4px 5px; margin:1px 0;">
                  <span class="badge bg-success-subtle text-success border border-success-subtle px-1.5 py-0.5" style="font-size: 10px; background-color: #dcfce7; color: #166534; border-color: #86efac !important; padding:2.5px 6px; border-radius:4px;">
                    <i class="fa fa-check-circle mr-1"></i>Own Sown
                  </span>
                </span>
              `;
            }

            let allocByHtml = '';
            if (item.allocated_by_name && item.allocated_by_name !== '-' && item.allocated_by_name !== '0') {
              allocByHtml = `<span class="alloc-by-badge text-muted" style="font-size: 10.5px; padding: 2px 7px; margin: 1px 0; background: #f1f5f9; border-radius: 4px; border: 1px solid #e2e8f0; display: inline-flex; align-items: center;" title="Allocated By"><i class="fa fa-user-check mr-1 text-primary"></i>Allocated by: <strong>${esc(item.allocated_by_name)}</strong>${item.formatted_allocated_at ? ` (${esc(item.formatted_allocated_at)})` : ''}</span>`;
            }

            // Already Dispatched Quantity Tag
            let dispTag = '';
            const dispTrays = Number(item.dispatched_trays || 0);
            const dispPlants = Number(item.dispatched_plants || 0);
            const priorDispTrays = Number(item.prior_dispatched_trays || 0);
            const priorDispPlants = Number(item.prior_dispatched_plants || 0);
            if (dispPlants > 0 || dispTrays > 0) {
              dispTag = `<span class="disp-tag-pill" title="Already Dispatched: ${dispTrays} Trays (${Number(dispPlants).toLocaleString()} Plants)"><i class="fa fa-truck mr-1"></i>Dispatched: <strong>${dispTrays} Trays</strong> <small>(${Number(dispPlants).toLocaleString()} Plants)</small></span>`;
            } else if (priorDispPlants > 0 || priorDispTrays > 0) {
              dispTag = `<span class="disp-tag-pill" title="Dispatched in Previous Trips: ${priorDispTrays} Trays (${Number(priorDispPlants).toLocaleString()} Plants)"><i class="fa fa-truck mr-1"></i>Prior Dispatched: <strong>${priorDispTrays} Trays</strong> <small>(${Number(priorDispPlants).toLocaleString()} Plants)</small></span>`;
            }

            // Mismatch Alert & Action Buttons
            let mismatchAlertHtml = '';
            if (item.is_stock_available === false) {
              mismatchAlertHtml = `
                <div class="mismatch-warning-alert" style="background:#fff1f2; border-left:4px solid #ef4444; color:#991b1b; padding:8px 12px; border-radius:6px; margin-bottom:8px;">
                  <div class="d-flex align-items-center flex-wrap" style="gap:6px;">
                    <i class="fa fa-triangle-exclamation text-danger" style="font-size:1.05rem;"></i>
                    <strong style="color:#b91c1c;">Warning: Re-allocate it due to qty not available in this polyhouse!</strong>
                    <span style="font-size:12px; color:#7f1d1d;">
                      (Allocated: <b>${item.allocated_trays || 0} Trays</b> / <b>${Number(item.allocated_qty || 0).toLocaleString()} Plants</b> &bull; Active in Polyhouse: <b>${Number(item.active_ph_trays || 0)} Trays</b> / <b>${Number(item.active_ph_plants || 0).toLocaleString()} Plants</b>${!item.has_polyhouse_bed ? ' &bull; <b style="color:#b91c1c;">Polyhouse & Bed not assigned</b>' : ''})
                    </span>
                  </div>
                  <div class="mismatch-action-btns" style="margin-top:4px;">
                    <span class="badge" style="background:#dc2626; color:#fff; font-size:11px; padding:3px 8px; border-radius:4px; font-weight:600;">
                      <i class="fa fa-exchange-alt mr-1"></i>Re-allocation Required in Sales Order
                    </span>
                  </div>
                </div>`;
            } else if (item.is_mismatch) {
              mismatchAlertHtml = `
                <div class="mismatch-warning-alert">
                  <div>
                    <i class="fa fa-triangle-exclamation text-danger mr-1"></i>
                    <strong>Warning: Mismatch Detected!</strong> ${esc(item.mismatch_summary || 'Allocated, loaded, or dispatched counts do not match.')}
                  </div>
                  <div class="mismatch-action-btns">
                    <button type="button" class="btn btn-xs btn-primary font-weight-bold" onclick="openScannerModal('${itemKey}')" title="Scan & Reload Trays">
                      <i class="fa fa-camera mr-1"></i> Reload Trays
                    </button>
                    ${(isRoleAdmin && hasLoadedTrays) ? `
                      <button type="button" class="btn btn-xs btn-outline-danger font-weight-bold" onclick="openLoadedTraysModal('${itemKey}')" title="Inspect & Undo Loaded Trays">
                        <i class="fa fa-rotate-left mr-1"></i> Undo Loaded Trays (${item.loaded_trays})
                      </button>` : ''}
                    ${(isRoleAdmin && ord.status && ord.status !== 'readytoload') ? `
                      <button type="button" class="btn btn-xs btn-danger font-weight-bold" onclick="revertOrderAction('${esc(item.invoiceno || ord.invoiceno || '')}', '${esc(item.psono || psoNo)}')" title="Revert order status back to Ready to Load">
                        <i class="fa fa-undo mr-1"></i> Revert Status to Ready to Load
                      </button>` : ''}
                  </div>
                </div>`;
            }

            html += `
              ${mismatchAlertHtml}
              <div class="crop-strip-row ${isDone ? 'is-completed' : ''}">
                <!-- Left: Index + Crop Details + Meta Tags -->
                <div class="crop-strip-main">
                  <div class="crop-strip-idx">${idx + 1}</div>
                  <div class="crop-strip-info">
                    <div class="crop-strip-title d-flex align-items-center flex-wrap" style="gap: 6px;">
                      <span>${esc(item.product_name)}</span>
                    </div>
                    <div class="crop-tags-row d-flex align-items-center flex-wrap" style="gap: 4px 6px; margin-top: 3px;">
                      ${item.invoiceno ? `
                        <span class="stock-order-tag inv-tag" style="background-color: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; font-weight: 600; margin: 1px 0;" title="Invoice No: ${esc(item.invoiceno)}">
                          <i class="fa fa-receipt mr-1 text-success"></i>Inv: <strong>${esc(item.invoiceno)}</strong>${item.formatted_invoicedate && item.formatted_invoicedate !== '-' ? ' <span style="font-size:10px; opacity:0.85; margin-left:3px;">(' + esc(item.formatted_invoicedate) + ')</span>' : ''}
                        </span>` : ''}
                      ${(item.psono && item.psono !== psoNo) ? `<span class="stock-order-tag pso-tag" style="margin: 1px 0;" title="Provisional Sales Order"><i class="fa fa-file-invoice mr-1 text-info"></i>PSO: ${esc(item.psono)}</span>` : ''}
                      ${stockBadgeHtml}
                      ${(item.is_stock_available === false) ? `
                        <span class="stock-order-tag realloc-tag" style="background-color: #fef2f2; color: #b91c1c; border: 1.5px solid #f87171; font-weight: 700; margin: 1px 0; padding: 2.5px 8px; border-radius: 4px;" title="${esc(item.stock_warning || 'Re-allocate it due to qty not available in this polyhouse')}">
                          <i class="fa fa-triangle-exclamation text-danger mr-1"></i>Re-allocate: Qty not available in polyhouse
                        </span>
                        ${!item.has_polyhouse_bed ? `
                          <span class="stock-order-tag realloc-tag" style="background-color: #fff7ed; color: #c2410c; border: 1.5px dashed #ea580c; font-weight: 700; margin: 1px 0; padding: 2.5px 8px; border-radius: 4px;" title="Polyhouse & Bed not assigned. Re-allocate this order in Sales Order / Production Allocation.">
                            <i class="fa fa-warehouse text-warning mr-1"></i>No Bed - Re-allocate
                          </span>` : ''}
                      ` : ''}
                      ${allocatedBedTags}
                      ${bedTags}
                      ${rtsTag}
                      ${shiftTag}
                      ${healthTags}
                      ${dispTag}
                      ${allocByHtml}
                    </div>
                  </div>
                </div>

                <!-- Right: Metrics & Actions -->
                <div class="crop-strip-side">
                  <!-- Order & Allocated Qty Combo -->
                  <div class="qty-combo-wrap">
                    ${(item.original_order_qty && item.original_order_qty > item.quantity) ? `
                    <div class="qty-combo-row" title="Original Booked Sales Order: ${Number(item.original_order_qty).toLocaleString()} Plants (${item.original_order_trays || Math.ceil(item.original_order_qty / 126)} Trays)">
                      <span class="qty-combo-tag tag-order" style="background:#e0e7ff; color:#3730a3;">Order</span>
                      <strong class="qty-combo-val text-indigo">${item.original_order_trays || Math.ceil(item.original_order_qty / 126)}</strong>
                      <span class="qty-combo-unit">Trays <small class="text-muted">(${Number(item.original_order_qty).toLocaleString()} Plants)</small></span>
                    </div>
                    <div class="qty-combo-row" title="Invoice Loading Target: ${Number(item.quantity).toLocaleString()} Plants (${item.est_trays} Trays)">
                      <span class="qty-combo-tag tag-inv" style="background:#fef3c7; color:#92400e;">Invoice</span>
                      <strong class="qty-combo-val text-amber">${item.est_trays}</strong>
                      <span class="qty-combo-unit">Trays <small class="text-muted">(${Number(item.quantity).toLocaleString()} Plants)</small></span>
                    </div>` : `
                    <div class="qty-combo-row">
                      <span class="qty-combo-tag tag-order">Order</span>
                      <strong class="qty-combo-val">${item.est_trays}</strong>
                      <span class="qty-combo-unit">Trays <small class="text-muted">(${Number(item.quantity).toLocaleString()} Plants)</small></span>
                    </div>`}
                    ${(dispPlants > 0 || dispTrays > 0) ? `
                    <div class="qty-combo-row">
                      <span class="qty-combo-tag tag-disp" title="Already Dispatched Quantity">Disp</span>
                      <strong class="qty-combo-val text-emerald">${dispTrays}</strong>
                      <span class="qty-combo-unit">Trays <small class="text-muted">(${Number(dispPlants).toLocaleString()} Plants)</small></span>
                    </div>` : ((priorDispPlants > 0 || priorDispTrays > 0) ? `
                    <div class="qty-combo-row">
                      <span class="qty-combo-tag tag-disp" title="Dispatched in Previous Trips" style="background:#e0f2fe; color:#0369a1;">Prior</span>
                      <strong class="qty-combo-val text-emerald">${priorDispTrays}</strong>
                      <span class="qty-combo-unit">Trays <small class="text-muted">(${Number(priorDispPlants).toLocaleString()} Plants)</small></span>
                    </div>` : '')}
                    <div class="qty-combo-row">
                      <span class="qty-combo-tag tag-alloc">Alloc</span>
                      <strong class="qty-combo-val text-purple">${item.allocated_trays || 0}</strong>
                      <span class="qty-combo-unit">Trays <small class="text-muted">(${Number(item.allocated_qty || 0).toLocaleString()} Plants)</small></span>
                    </div>
                  </div>

                  <!-- Loaded & Pending Stat Counters -->
                  <div class="crop-strip-stats-group">
                    <div class="crop-strip-stat ${(isRoleAdmin && hasLoadedTrays) ? 'cursor-pointer' : ''}" title="${(isRoleAdmin && hasLoadedTrays) ? 'Click to view & undo loaded trays' : 'Trays Loaded'}" ${(isRoleAdmin && hasLoadedTrays) ? `onclick="openLoadedTraysModal('${itemKey}')" style='cursor:pointer;'` : ''}>
                      <span class="strip-stat-title">Loaded</span>
                      <span class="trays-loaded-badge"><i class="fa fa-check mr-1"></i> ${item.loaded_trays}</span>
                    </div>

                    <div class="crop-strip-stat" title="Pending Trays to Load">
                      <span class="strip-stat-title">Pending</span>
                      <span class="trays-pending-badge">${item.pending_trays}</span>
                    </div>
                  </div>

                  <!-- Action Buttons -->
                  <div class="crop-strip-action">
                    ${actionBtn}
                    ${undoBtn}
                    ${rsoAutoBtn}
                    ${(function() {
                      const itemInvNo = item.invoiceno || ord.invoiceno || '';
                      const itemAllocTrays = Number(item.allocated_trays || 0);
                      const itemLoadedTrays = Number(item.loaded_trays || 0);
                      const isItemExactMatched = (itemAllocTrays > 0 && itemLoadedTrays === itemAllocTrays);
                      const isOrderDispatchedOrLoaded = ['dispatched', 'fulldispatch', 'final dispatch', 'loaded', 'fully loaded'].includes(String(ord.status || '').toLowerCase())
                        || ['dispatched', 'fulldispatch', 'final dispatch', 'loaded', 'fully loaded'].includes(String(item.status || '').toLowerCase())
                        || ['dispatched', 'fulldispatch', 'final dispatch', 'loaded', 'fully loaded'].includes(String(item.sii_status || '').toLowerCase());

                      if (isItemDispatched) {
                        return `
                          <button type="button" class="btn-update-dispatch btn-dispatch-locked" disabled style="background: #f0fdf4 !important; color: #047857 !important; border: 1.5px solid #a7f3d0 !important; cursor: default; font-weight: 600;" title="Item is already Dispatched">
                            <i class="fa fa-circle-check mr-1 text-success"></i> Dispatched
                          </button>
                        `;
                      } else if (isItemExactMatched) {
                        return `
                          <button type="button" class="btn-update-dispatch btn-dispatch-ready" onclick="handleUpdateStatusClick('${esc(psoNo)}', ${item.item_id || 0}, '${esc(itemInvNo)}')" style="background: linear-gradient(135deg, #059669 0%, #047857 100%) !important; color: #fff !important; font-weight: 600; box-shadow: 0 2px 4px rgba(5,150,105,0.25);" title="Allocated (${itemAllocTrays} Trays) matches Loaded (${itemLoadedTrays} Trays). Click to update status to Dispatched">
                            <i class="fa fa-truck-fast mr-1"></i> Update status
                          </button>
                        `;
                      } else if (item.is_mismatch || isOrderDispatchedOrLoaded) {
                        return `
                          <button type="button" class="btn-update-dispatch btn-dispatch-reload" onclick="handleUpdateStatusClick('${esc(psoNo)}', ${item.item_id || 0}, '${esc(itemInvNo)}')" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important; color: #fff !important; font-weight: 600; box-shadow: 0 2px 4px rgba(245,158,11,0.25); cursor: pointer;" title="Mismatch Detected (Loaded: ${itemLoadedTrays}, Allocated: ${itemAllocTrays}): Click to re-verify, reload trays, or resolve mismatch">
                            <i class="fa fa-rotate mr-1"></i> Reload / Update Trays
                          </button>
                        `;
                      } else {
                        return `
                          <button type="button" class="btn-update-dispatch btn-dispatch-locked" onclick="handleUpdateStatusClick('${esc(psoNo)}', ${item.item_id || 0}, '${esc(itemInvNo)}')" style="background: #f1f5f9 !important; color: #64748b !important; border: 1.5px dashed #cbd5e1 !important; cursor: pointer; font-weight: 600;" title="Loaded (${itemLoadedTrays}) != Allocated (${itemAllocTrays}). Click to re-verify / load again / undo">
                            <i class="fa fa-lock mr-1 text-muted"></i> Update status
                          </button>
                        `;
                      }
                    })()}
                  </div>
                </div>
              </div>
            `;
          });

          html += `
              </div>
            </div>
          `;
        });

        $container.html(html);
        if (!showFullLoader) {
          window.scrollTo(0, (typeof currentScrollPos !== 'undefined' ? currentScrollPos : 0));
        }
      },
      error: function (xhr, status, err) {
        $container.html(`
          <div class="alert alert-danger m-3">
            <i class="fa fa-exclamation-triangle mr-2"></i> Failed to communicate with server: ${esc(err)}
          </div>
        `);
      }
    });
  };

  // Dedicated single order / soft refresh handler
  window.refreshSingleOrder = function (psoNo, btn) {
    const $btn = $(btn);
    const $icon = $btn.find('i');
    $icon.addClass('fa-spin');
    $btn.prop('disabled', true);

    loadOrdersList(false, function () {
      setTimeout(() => {
        $icon.removeClass('fa-spin');
        $btn.prop('disabled', false);
      }, 250);
    });
  };

  // -------------------------------------------------------------
  // 2. Open Scanner Modal
  // -------------------------------------------------------------
  window.openScannerModal = function (itemData) {
    if (typeof itemData === 'string' && window.rtlItemRegistry && window.rtlItemRegistry[itemData]) {
      itemData = window.rtlItemRegistry[itemData];
    }
    try {
      if (itemData && itemData.psono) {
        $('.order-box').removeClass('active-order-box');
        $(`#orderBox_${esc(itemData.psono)}`).addClass('active-order-box');
      }
      const allocTrays = Number(itemData.allocated_trays || 0);
      const allocQty = Number(itemData.allocated_qty || 0);
    const rtsTrays = Number(itemData.ready_to_sell_trays || 0);
    const rtsPlants = Number(itemData.ready_to_sell_plants || 0);

    // If stock is not available in polyhouse or bed is not assigned, show warning popup and block loading
    if (itemData && itemData.is_stock_available === false) {
      playWarningTone();
      Swal.fire({
        icon: 'warning',
        title: 'Stock Not Available in Polyhouse',
        html: `
          <div style="text-align: left; font-size: 0.92rem; color: #334155;">
            <div class="alert alert-danger p-2.5 mb-3" style="font-size: 0.86rem; border-left: 4px solid #ef4444; background: #fef2f2; border-radius: 6px; color: #991b1b;">
              <strong><i class="fa fa-triangle-exclamation mr-1"></i> Cannot Load Trays!</strong><br>
              ${esc(itemData.stock_warning || 'Re-allocate it due to qty not available in this polyhouse.')}
            </div>
            <p style="font-size: 0.85rem; color: #475569;">
              The allocated production batch has no active trays or plants in status <code>(Polyhouse, Readytosale, Partial Dispatched)</code>.
              Please re-allocate this order in Sales Order / Production Allocation before loading trays.
            </p>
          </div>
        `,
        confirmButtonColor: '#dc2626',
        confirmButtonText: '<i class="fa fa-check mr-1"></i> Understood'
      });
      return;
    }

    // If allocated tray is 0, user cannot load trays - show warning popup
    if (allocTrays <= 0 && allocQty <= 0) {
      playWarningTone();
      Swal.fire({
        icon: 'warning',
        title: 'Cannot Load Trays',
        html: `
          <div style="text-align: left; font-size: 0.92rem; color: #334155;">
            <p>Allocated quantity for <b>${esc(itemData.product_name)}</b> is currently <b>0 Trays (0 Plants)</b>.</p>
            <p class="mb-0 text-muted" style="font-size: 0.85rem;">
              <i class="fa fa-info-circle text-primary mr-1"></i> Trays must be allocated before loading.
            </p>
          </div>
        `,
        confirmButtonColor: '#4f46e5',
        confirmButtonText: '<i class="fa fa-check mr-1"></i> Understood'
      });
      return;
    }

    // If 0 trays are marked Ready to Sell, show alert that Mark Ready to Sell is required
    // if (rtsTrays <= 0) {
    //   playWarningTone();
    //   Swal.fire({
    //     icon: 'warning',
    //     title: 'Mark Ready to Sell Required',
    //     html: `
    //       <div style="text-align: left; font-size: 0.92rem; color: #334155;">
    //         <p>No trays for <b>${esc(itemData.product_name)}</b> have been marked as <b>Ready to Sell</b> (0 Trays Ready).</p>
    //         <p class="mb-0 text-danger font-weight-bold" style="font-size: 0.88rem;">
    //           <i class="fa fa-exclamation-triangle mr-1"></i> Mark Ready to Sell is required to load tray.
    //         </p>
    //       </div>
    //     `,
    //     confirmButtonColor: '#4f46e5',
    //     confirmButtonText: 'Understood'
    //   });
    //   return;
    // }

    currentActivePsono = itemData.psono || '';
    currentActiveSono = itemData.sono || '';
    currentActiveItemId = itemData.item_id || itemData.id || 0;
    currentActiveProductId = itemData.product_id ? String(itemData.product_id).trim() : '';
    currentActiveInvoiceNo = itemData.invoiceno || '';
    currentActiveProduct = itemData.product_name || '';
    currentActiveVariety = itemData.variety || '';
    currentActiveStockorder = itemData.stockorder_no !== '-' ? itemData.stockorder_no : '';
    currentActiveClientId = itemData.clientid || '';
    currentActiveClientName = itemData.display_client_name || itemData.clientname || '';
    currentTargetRequiredTrays = allocTrays > 0 ? allocTrays : (itemData.est_trays || 0);
    currentTargetRequiredPlants = allocQty > 0 ? allocQty : (itemData.quantity || 0);
    currentActiveReadyToSellTrays = rtsTrays;
    currentActiveReadyToSellPlants = rtsPlants;
    currentActiveLoadedTrays = Number(itemData.loaded_trays || 0);
    currentActiveLoadedPlants = Number(itemData.loaded_plants || 0);
    currentActivePendingTrays = Number(itemData.pending_trays || 0);
    currentActivePendingPlants = Number(itemData.pending_plants || 0);
    currentActiveIsShifted = (itemData.isshifted === 'yes' || itemData.is_shifted || (itemData.shifted_plants && itemData.shifted_plants > 0));

    // Populate Context Header
    if (itemData.allocated_batches && itemData.allocated_batches.length > 1) {
      const batchListStr = itemData.allocated_batches.map(b => `${b.stockorder_no} (${Number(b.allocated_qty || b.consumed_qty || 0).toLocaleString()})`).join(', ');
      $('#lblBatchProduct').html(`${esc(currentActiveProduct)} <span class="badge" style="background:#1e40af; color:#fff; font-size:11px; padding:3px 8px; border-radius:4px; margin-left:6px;"><i class="fa fa-layer-group me-1"></i>Multi-Batch (${itemData.allocated_batches.length}): ${esc(batchListStr)}</span>`);
    } else if (itemData.is_swapped) {
      $('#lblBatchProduct').html(`${esc(currentActiveProduct)} <span class="badge bg-warning-subtle text-warning-emphasis border border-warning ms-1" style="font-size: 11px; background-color: #fef3c7; color: #92400e; border-color: #f59e0b !important;"><i class="fa fa-exchange-alt me-1"></i>Allocated Swapped: ${esc(currentActiveStockorder)} (Orig: ${esc(itemData.original_stockorder_no || '-')})</span>`);
    } else {
      $('#lblBatchProduct').text(currentActiveProduct);
    }

    // Initialize Allocated Beds Detail for Multi-Bed 1-Tray Verification
    currentAllocatedBedsDetail = (itemData.allocated_beds_detail && Array.isArray(itemData.allocated_beds_detail) && itemData.allocated_beds_detail.length > 0)
      ? JSON.parse(JSON.stringify(itemData.allocated_beds_detail))
      : [];

    if (currentAllocatedBedsDetail.length === 0) {
      const defaultBedName = (itemData.active_beds && itemData.active_beds[0]) ? itemData.active_beds[0].bed_no : 'Bed-Allocated';
      const defaultPhName = (itemData.active_beds && itemData.active_beds[0]) ? itemData.active_beds[0].polyhouse : 'Greenhouse';
      currentAllocatedBedsDetail = [{
        stockorder_no: currentActiveStockorder || '-',
        polyhouse_name: defaultPhName,
        bed_name: defaultBedName,
        plants: currentTargetRequiredPlants,
        tray_count: currentTargetRequiredTrays,
        is_verified: Number(itemData.loaded_trays || 0) >= 1,
        verified_barcode: ''
      }];
    }

    let locationStr = '';
    if (currentAllocatedBedsDetail.length > 0) {
      locationStr = currentAllocatedBedsDetail.map(b => `${esc(b.polyhouse_name || 'PH')} • ${esc(b.bed_name || 'Bed')} (${Number(b.plants || 0).toLocaleString()} Plants, ${b.tray_count || 0}T)`).join(', ');
    } else if (itemData.active_beds && itemData.active_beds.length > 0) {
      locationStr = itemData.active_beds.map(b => b.polyhouse + ' • ' + b.bed_no + ' (' + b.tray_count + ' Trays)').join(', ');
    } else {
      locationStr = allocTrays + ' Trays Allocated';
    }
    $('#lblBatchLocation').text(locationStr);
    
    // Hide Ready to Sell pill in scanner modal to avoid clutter / confusion
    $('#lblBatchRtsTag').hide();

    // Shifted vs Unshifted plant details from krf_readystock_allocation
    const isShifted = (itemData.isshifted === 'yes' || itemData.isshifted === 1 || itemData.isshifted === '1' || itemData.is_shifted || (itemData.shifted_plants && itemData.shifted_plants > 0));
    let extraBonusQty = 0;
    if (itemData.shiftingqty > 0) {
      extraBonusQty = Number(itemData.shiftingqty);
    } else if (isShifted) {
      const shiftCount = itemData.quantity || itemData.allocated_qty || itemData.healthyplant || 0;
      extraBonusQty = itemData.est_trays || Math.ceil(shiftCount / 125);
    }

    if (itemData.isshifted === 'yes' || (itemData.shifted_plants && itemData.shifted_plants > 0)) {
      const shiftCount = itemData.quantity || itemData.allocated_qty || itemData.healthyplant || 0;
      const extraCount = extraBonusQty;
      const extraText = extraCount > 0 ? ` (+${Number(extraCount).toLocaleString()} Free Bonus)` : '';
      $('#lblBatchShift').html(`Shifted: <strong>${Number(shiftCount).toLocaleString()}</strong> Billed Plants${extraText}`);
      $('#lblBatchShiftTag').show();
    } else {
      const unshiftCount = itemData.unshifted_plants || itemData.quantity || 0;
      const extraText = extraBonusQty > 0 ? ` (+${Number(extraBonusQty).toLocaleString()} Extra)` : '';
      $('#lblBatchShift').html(`Unshifted: <strong>${Number(unshiftCount).toLocaleString()}</strong> Plants${extraText}`);
      $('#lblBatchShiftTag').show();
    }

    // Already Dispatched context badge in scanner modal
    const modalDispTrays = Number(itemData.dispatched_trays || 0);
    const modalDispPlants = Number(itemData.dispatched_plants || 0);
    const modalPriorDispTrays = Number(itemData.prior_dispatched_trays || 0);
    const modalPriorDispPlants = Number(itemData.prior_dispatched_plants || 0);
    if (modalDispTrays > 0 || modalDispPlants > 0) {
      $('#lblBatchDisp').html(`Dispatched: <strong>${modalDispTrays}</strong> Trays (<strong class="text-success">${Number(modalDispPlants).toLocaleString()}</strong> Plants)`);
      $('#lblBatchDispTag').show();
    } else if (modalPriorDispTrays > 0 || modalPriorDispPlants > 0) {
      $('#lblBatchDisp').html(`Prior Dispatched: <strong>${modalPriorDispTrays}</strong> Trays (<strong class="text-success">${Number(modalPriorDispPlants).toLocaleString()}</strong> Plants)`);
      $('#lblBatchDispTag').show();
    } else {
      $('#lblBatchDispTag').hide();
    }

    // Set Loaded & Required Left context badges
    $('#lblBatchLoaded').html(
      `<strong>${currentActiveLoadedTrays}</strong> Loaded (<strong class="text-primary">${Number(currentActiveLoadedPlants).toLocaleString()}</strong> Plants)`
    );

    $('#lblBatchPendingTag').hide();
    $('#pillRemainingToLoad').hide();

    // Initialize Free Bonus Plants Verification
    currentBonusPlantsRequired = Number(extraBonusQty || 0);
    currentBonusPlantsVerified = 0;
    currentBonusTraysList = [];
    currentScanTargetMode = 'billed';

    if (currentBonusPlantsRequired > 0) {
      $('#bonusVerificationCard').show();
      $('#btnScanModeBonus').show();
      $('#lblBonusTargetQty').text(currentBonusPlantsRequired.toLocaleString());
      recalcBonusPlants();
      renderBonusVerificationChecklist();
    } else {
      $('#bonusVerificationCard').hide();
      $('#btnScanModeBonus').hide();
    }
    switchScanTargetMode('billed');

    // Reset scanned grid for new session
    scannedTraysList = [];
    inFlightScans.clear();
    renderScannedGrid();
    renderBedVerificationChecklist();

    // If trays are already loaded, fetch and populate into the grid immediately
    if (currentActiveLoadedTrays > 0) {
      loadExistingLoadedTraysSilently();
    }

    // Switch to manual mode by default
    switchScannerTab('manual');

    // Show Modal & Lock Background Scroll
    $('body, html').addClass('modal-open-lock');
    $('#scannerModalOverlay').addClass('active');
    $('.scan-modal-body').scrollTop(0);
    setTimeout(() => {
      $('#inputBarcodeQuery').val('').focus();
    }, 200);
    } catch (err) {
      console.error('Error opening scanner modal:', err);
      if (window.Swal) {
        Swal.fire({
          icon: 'error',
          title: 'Could Not Open Scanner',
          text: err.message || 'An unexpected error occurred while opening the scanner.'
        });
      }
    }
  };

  window.closeScannerModal = function () {
    clearAutoCommit(true);
    stopCamera();
    inFlightScans.clear();
    $('body, html').removeClass('modal-open-lock');
    $('#scannerModalOverlay').removeClass('active');
  };

  // -------------------------------------------------------------
  // 3. Tab Switching: Manual / Gun vs Camera Scanner
  // -------------------------------------------------------------
  window.switchScannerTab = function (mode) {
    if (mode === 'manual') {
      $('#tabManual').addClass('active');
      $('#tabCamera').removeClass('active');
      $('#sectionManual').show();
      $('#sectionCamera').hide();
      stopCamera();
      $('#inputBarcodeQuery').val('').focus();
    } else {
      $('#tabCamera').addClass('active');
      $('#tabManual').removeClass('active');
      $('#sectionManual').hide();
      $('#sectionCamera').show();
      startCamera();
    }
  };

  // -------------------------------------------------------------
  // 4. Barcode Input & Lookup
  // -------------------------------------------------------------
  let isScanProcessing = false;
  const inFlightScans = new Set();
  let lastScanTimestamp = 0;
  let lastScannedText = '';

  function pauseScanner() {
    isScanProcessing = true;
    $('#inputBarcodeQuery').val(''); // Clear immediately after scan
    $('#inputBarcodeQuery').prop('disabled', true).addClass('scan-input-paused');
    $('#inputBarcodeQuery').attr('placeholder', 'Verifying tray on server... Please wait');
    $('#btnAddTrayAction').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> <span>Verifying...</span>');
  }

  function unpauseScanner() {
    isScanProcessing = false;
    const pendingToLoad = scannedTraysList.filter(t => t.current_status !== 'Loaded' && t.current_status !== 'ReadyToDispatch');
    const remainingTraysAfterBatch = Math.max(0, currentActivePendingTrays - pendingToLoad.length);

    if (remainingTraysAfterBatch <= 0 && currentTargetRequiredTrays > 0) {
      $('#inputBarcodeQuery').prop('disabled', true).removeClass('scan-input-paused').addClass('scan-input-completed');
      $('#inputBarcodeQuery').attr('placeholder', '✔ All required trays scanned (Limit reached)');
      $('#btnAddTrayAction').prop('disabled', true).html('<i class="fa fa-check-circle"></i> <span>Completed</span>');
    } else {
      $('#inputBarcodeQuery').prop('disabled', false).removeClass('scan-input-paused scan-input-completed');
      $('#inputBarcodeQuery').attr('placeholder', 'Scan or enter Tray Barcode');
      $('#btnAddTrayAction').prop('disabled', false).html('<i class="fa fa-plus-circle"></i> <span>Add Tray</span>');
      $('#inputBarcodeQuery').val('').focus();
    }
  }

  window.triggerManualScan = function () {
    if (isScanProcessing) {
      return;
    }

    const now = Date.now();
    const raw = $.trim($('#inputBarcodeQuery').val() || '');
    if (!raw) {
      // If a rapid double trigger cleared the input within 400ms, ignore silently
      if (now - lastScanTimestamp < 400) {
        return;
      }
      Swal.fire({
        icon: 'info',
        title: 'Barcode Required',
        text: 'Please scan or enter a Tray Barcode.',
        confirmButtonColor: '#4f46e5'
      });
      $('#inputBarcodeQuery').focus();
      return;
    }

    const clean = raw.replace(/[\x00-\x1F\x7F\xC2\xA0]/g, '').trim();
    if (!clean) return;

    // Debounce gun double-keystroke bounce
    if (now - lastScanTimestamp < 350 && lastScannedText.toUpperCase() === clean.toUpperCase()) {
      return;
    }

    lastScanTimestamp = now;
    lastScannedText = clean;

    // Clear the input box right after reading the barcode
    $('#inputBarcodeQuery').val('');
    lookupAndAddTray(clean);
  };

  window.clearScanInput = function () {
    if (isScanProcessing) return;
    $('#inputBarcodeQuery').val('').focus();
  };

  function lookupAndAddTray(barcode) {
    if (isScanProcessing) return;
    if (!barcode) return;
    const cleanBarcode = barcode.replace(/[\x00-\x1F\x7F\xC2\xA0]/g, '').trim();
    if (!cleanBarcode) return;
    const cleanUpper = cleanBarcode.toUpperCase();

    // Prevent duplicate in-flight lookup while server is processing this barcode
    if (inFlightScans.has(cleanUpper)) {
      return;
    }

    // Immediately clear input box as requested
    $('#inputBarcodeQuery').val('');

    // 1. Allow tray barcodes, stock order numbers, and bed codes (e.g. TRY..., KFP..., RSO-..., SO-..., Bed...)
    const isAllowedBatchOrBed = /^(TRY|RSO|SO|KFP|KF|BED)/i.test(cleanBarcode) || /^BED[-\s]?\d+/i.test(cleanBarcode) || /^\d+$/i.test(cleanBarcode);
    const isPureOrderId = !isAllowedBatchOrBed && /^(ORD|PSO|PO|KF-CS)[-\s]?/i.test(cleanBarcode);
    if (isPureOrderId && !cleanBarcode.startsWith('TRY')) {
      playWarningTone();
      Swal.fire({
        icon: 'warning',
        title: 'Order ID Not Allowed',
        html: `Query <b>"${esc(cleanBarcode)}"</b> appears to be an Order ID.<br>This scanner accepts <b>individual Tray Barcodes</b> (e.g. <code>TRY...</code>), <b>Batch QRs</b> (e.g. <code>KFP...</code>, <code>RSO-...</code>), or <b>Bed QRs</b> (e.g. <code>Bed-8</code>).`,
        confirmButtonColor: '#4f46e5'
      });
      $('#inputBarcodeQuery').val('').focus();
      return;
    }

    // 2. Check if already in scanned list (normalized)
    const existing = scannedTraysList.find(t => $.trim(t.barcode || '').toUpperCase() === cleanUpper);
    if (existing) {
      playScanBeep();
      Swal.fire({
        icon: 'info',
        title: 'Already in Batch',
        text: `Tray "${cleanBarcode}" is already added in the scanned grid.`,
        timer: 1600,
        showConfirmButton: false
      });
      $('#inputBarcodeQuery').val('').focus();
      return;
    }

    // 2.5 Prevent over-scanning: Check if all pending trays are already scanned / in grid
    const pendingToLoad = scannedTraysList.filter(t => t.current_status !== 'Loaded' && t.current_status !== 'ReadyToDispatch');
    const remainingAllowed = Math.max(0, currentActivePendingTrays - pendingToLoad.length);

    if (remainingAllowed <= 0 && currentTargetRequiredTrays > 0) {
      playWarningTone();
      Swal.fire({
        icon: 'warning',
        title: 'Loading Limit Reached!',
        html: `This item requires <b>${currentTargetRequiredTrays} tray(s)</b> total (${currentActivePendingTrays} pending).<br>` +
              `All required trays are already accounted for in the scanned grid/loaded status.<br><br>` +
              `<span class="text-danger font-weight-bold" style="font-size: 0.95rem;">Cannot scan or add more trays!</span>`,
        confirmButtonColor: '#e11d48'
      });
      $('#inputBarcodeQuery').val('');
      return;
    }

    // 3. Pause scanner while waiting for server response
    pauseScanner();
    inFlightScans.add(cleanUpper);

    // 4. Query Server for tray details
    $.ajax({
      url: 'model/readytoload_model.php',
      type: 'POST',
      dataType: 'json',
      data: {
        action: 'scan_tray',
        barcode: cleanBarcode,
        psono: currentActivePsono,
        product_name: currentActiveProduct,
        stockorder_no: currentActiveStockorder,
        variety: currentActiveVariety,
        sono: currentActiveSono,
        clientid: currentActiveClientId,
        clientname: currentActiveClientName,
        item_id: currentActiveItemId,
        required_trays: currentTargetRequiredTrays,
        required_plants: currentTargetRequiredPlants,
        allocated_beds_detail: JSON.stringify(currentAllocatedBedsDetail || [])
      },
      success: function (res) {
        if (res.status === 'already_loaded') {
          playWarningTone();
          const loadedTrayData = res.data || { barcode: barcode, current_status: 'Loaded' };
          const loadedOrder = loadedTrayData.psono || currentActivePsono || '-';
          Swal.fire({
            icon: 'warning',
            title: 'Tray Already Loaded',
            html: `Tray <b>${esc(loadedTrayData.barcode)}</b> is ALREADY loaded (Order: <b>${esc(loadedOrder)}</b>).<br><br>` +
                  `<span style="color:#e11d48; font-weight:700;">A tray cannot be loaded or allocated multiple times.</span>`,
            confirmButtonText: '<i class="fa fa-check mr-1"></i> Understood',
            confirmButtonColor: '#4f46e5'
          }).then(() => {
            unpauseScanner();
            $('#inputBarcodeQuery').val('').focus();
          });
          return;
        }

        // Helper to deduplicate repeated variety names in crop titles
        function cleanCropTitle(prod, variety) {
          let p = $.trim(prod || '');
          let v = $.trim(variety || '');
          if (!v) return p;
          if (p.toLowerCase().includes(v.toLowerCase())) return p;
          return `${p} (${v})`;
        }

        // Case A: Variety Mismatch — Manager Approval Required
        if (res.status === 'variety_mismatch_approval_required') {
          playWarningTone();
          const d = res.data;
          const targetCrop = cleanCropTitle(d.target_productname, d.target_variety);
          const scannedCrop = cleanCropTitle(d.scanned_productname, d.scanned_variety);

          Swal.fire({
            width: '490px',
            padding: '1.25rem 1.4rem',
            html: `
              <div style="text-align: center; margin-bottom: 12px;">
                <div style="width: 52px; height: 52px; border-radius: 50%; background: #fff1f2; border: 2px solid #ffe4e6; color: #e11d48; display: inline-flex; align-items: center; justify-content: center; font-size: 22px; margin-bottom: 6px; box-shadow: 0 4px 12px rgba(225, 29, 72, 0.12);">
                  <i class="fa fa-shuffle"></i>
                </div>
                <h3 style="font-size: 1.25rem; font-weight: 800; color: #0f172a; margin: 0 0 2px 0;">Variety Mismatch</h3>
                <p style="font-size: 0.81rem; color: #64748b; margin: 0;">Manager approval is required to swap this crop variety</p>
              </div>

              <!-- Crop Comparison Card -->
              <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 10px 12px; margin-bottom: 10px; text-align: left;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; margin-bottom: 7px; padding-bottom: 7px; border-bottom: 1px dashed #e2e8f0;">
                  <span style="font-size: 0.73rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; padding-top: 2px; white-space: nowrap;">
                    <i class="fa fa-clipboard-check text-primary mr-1"></i> Expected:
                  </span>
                  <span style="font-size: 0.82rem; font-weight: 700; color: #1e3a8a; background: #dbeafe; padding: 2px 8px; border-radius: 5px; text-align: right; max-width: 72%; line-height: 1.35; word-break: break-word;">
                    ${esc(targetCrop)}
                  </span>
                </div>
                <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 8px;">
                  <span style="font-size: 0.73rem; font-weight: 800; color: #e11d48; text-transform: uppercase; letter-spacing: 0.5px; padding-top: 2px; white-space: nowrap;">
                    <i class="fa fa-triangle-exclamation mr-1"></i> Scanned:
                  </span>
                  <span style="font-size: 0.82rem; font-weight: 700; color: #9f1239; background: #ffe4e6; border: 1px solid #fecdd3; padding: 2px 8px; border-radius: 5px; text-align: right; max-width: 72%; line-height: 1.35; word-break: break-word;">
                    ${esc(scannedCrop)}
                  </span>
                </div>
              </div>

              <!-- Tray & Order Meta Strip -->
              <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 7px 10px; margin-bottom: 8px; font-size: 0.79rem; color: #475569; text-align: left;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                  <span><i class="fa fa-barcode mr-1 text-muted"></i><code>${esc(d.barcode)}</code></span>
                  <span><i class="fa fa-box mr-1 text-muted"></i><b>Batch: ${esc(d.scanned_stockorderno || '-')}</b></span>
                  <span><i class="fa fa-location-dot mr-1 text-muted"></i>${esc(d.tray_polyhouse_name || 'PH')} • ${esc(d.tray_bed_no || '-')}</span>
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 4px; border-top: 1px dashed #f1f5f9; font-size: 0.77rem;">
                  <span><i class="fa fa-crosshairs text-primary mr-1"></i>Target Delivery: <b style="color: #1e40af;">${esc(d.target_deliverydate || '-')}</b></span>
                  <span><i class="fa fa-truck text-amber-600 mr-1"></i>Scanned Order Delivery: <b style="color: #b45309;">${esc(d.scanned_deliverydate || '-')}</b></span>
                </div>
              </div>

              <!-- Reason Input -->
              <div style="text-align: left;">
                <label style="font-weight: 700; font-size: 0.8rem; color: #334155; margin-bottom: 4px; display: flex; align-items: center; justify-content: space-between;">
                  <span>Reason for Request <span class="text-danger">*</span></span>
                  <span style="font-size: 0.72rem; font-weight: 500; color: #64748b;">Sent to manager</span>
                </label>
                <textarea id="txtApprovalReason" class="form-control" rows="2" placeholder="e.g. Target variety out of stock; client agreed to alternative..." style="width: 100%; border: 1.5px solid #cbd5e1; border-radius: 8px; padding: 7px 9px; font-size: 0.82rem; resize: vertical; box-sizing: border-box;"></textarea>
              </div>
            `,
            showCancelButton: true,
            confirmButtonText: '<i class="fa fa-paper-plane mr-1"></i> Send Request',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#64748b',
            focusConfirm: false,
            preConfirm: () => {
              const reason = $.trim($('#txtApprovalReason').val() || '');
              if (!reason) {
                Swal.showValidationMessage('Please provide a reason for the request.');
                return false;
              }
              return reason;
            },
            didClose: () => { unpauseScanner(); }
          }).then((result) => {
            if (result.isConfirmed) {
              const reason = result.value;
              sendVarietyApprovalRequest(d, reason);
            }
            unpauseScanner();
          });
          return;
        }

        // Case B: Approval Request is Currently Pending
        if (res.status === 'approval_pending') {
          playWarningTone();
          const d = res.data;
          Swal.fire({
            width: '470px',
            padding: '1.25rem',
            html: `
              <div style="text-align: center; margin-bottom: 12px;">
                <div style="width: 50px; height: 50px; border-radius: 50%; background: #fffbeb; border: 2px solid #fef3c7; color: #d97706; display: inline-flex; align-items: center; justify-content: center; font-size: 22px; margin-bottom: 6px; box-shadow: 0 4px 12px rgba(217, 119, 6, 0.12);">
                  <i class="fa fa-clock"></i>
                </div>
                <h3 style="font-size: 1.2rem; font-weight: 800; color: #0f172a; margin: 0 0 2px 0;">Approval Pending</h3>
                <p style="font-size: 0.8rem; color: #64748b; margin: 0;">This batch / tray is waiting for manager review</p>
              </div>

              <div style="text-align: left; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; font-size: 0.82rem; color: #334155; line-height: 1.6;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
                  <span class="text-muted">Request No:</span>
                  <code class="font-weight-bold text-dark">${esc(d.request_no)}</code>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
                  <span class="text-muted">Tray Barcode:</span>
                  <code>${esc(d.barcode)}</code>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
                  <span class="text-muted">Target Delivery Date:</span>
                  <span style="color: #1e40af; font-weight: 700;"><i class="fa fa-calendar-alt mr-1"></i>${esc(d.target_deliverydate || '-')}</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
                  <span class="text-muted">Scanned Order (${esc(d.scanned_stockorderno || d.scanned_psono || '-')}):</span>
                  <span style="color: #b45309; font-weight: 700;"><i class="fa fa-calendar-alt mr-1"></i>${esc(d.scanned_deliverydate || '-')}</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
                  <span class="text-muted">Requested By:</span>
                  <span>${esc(d.requested_by)} <small class="text-muted">(${esc(d.requested_at)})</small></span>
                </div>
                ${d.reason ? `
                <div style="margin-top: 5px; padding-top: 5px; border-top: 1px dashed #cbd5e1;">
                  <span class="text-muted">Reason:</span> <em>${esc(d.reason)}</em>
                </div>` : ''}
              </div>
            `,
            confirmButtonColor: '#4f46e5',
            confirmButtonText: 'Understood',
            didClose: () => { unpauseScanner(); }
          }).then(() => { unpauseScanner(); });
          return;
        }

        // Case C: Approval Request Was Rejected
        if (res.status === 'approval_rejected') {
          playWarningTone();
          const d = res.data;
          Swal.fire({
            width: '460px',
            padding: '1.25rem',
            html: `
              <div style="text-align: center; margin-bottom: 12px;">
                <div style="width: 50px; height: 50px; border-radius: 50%; background: #fef2f2; border: 2px solid #fee2e2; color: #dc2626; display: inline-flex; align-items: center; justify-content: center; font-size: 22px; margin-bottom: 6px; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.12);">
                  <i class="fa fa-times"></i>
                </div>
                <h3 style="font-size: 1.2rem; font-weight: 800; color: #0f172a; margin: 0 0 2px 0;">Request Rejected</h3>
                <p style="font-size: 0.8rem; color: #dc2626; margin: 0; font-weight: 600;">Manager rejected loading this tray for Order ${esc(currentActivePsono)}</p>
              </div>

              <div style="text-align: left; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; font-size: 0.82rem; color: #334155; line-height: 1.6;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
                  <span class="text-muted">Request No:</span>
                  <code>${esc(d.request_no)}</code>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
                  <span class="text-muted">Rejected By:</span>
                  <span>${esc(d.approved_by || 'Manager')}</span>
                </div>
                <div style="margin-top: 5px; padding-top: 5px; border-top: 1px dashed #cbd5e1;">
                  <span class="text-muted">Remarks:</span> <span class="text-danger font-weight-bold">${esc(d.approval_remarks || 'Not approved')}</span>
                </div>
              </div>
            `,
            confirmButtonColor: '#64748b',
            confirmButtonText: 'Close',
            didClose: () => { unpauseScanner(); }
          }).then(() => { unpauseScanner(); });
          return;
        }

        // Case D: Same Variety, but Different Client / Batch (Display Farmer info & batch dispatches with confirmation)
        if (res.status === 'order_mismatch_same_variety') {
          playWarningTone();
          const trayData = res.data;

          let dispatchesHtml = '';
          if (trayData.dispatches && trayData.dispatches.length > 0) {
            const dispRows = trayData.dispatches.map(d => `
              <div style="display:flex; justify-content:space-between; align-items:center; padding:4px 0; border-bottom:1px dashed #e2e8f0; font-size:0.77rem;">
                <div>
                  <span style="font-weight:700; color:#1e293b;">${esc(d.client_name || 'Client')}</span>
                  <span style="color:#64748b; font-size:0.72rem; margin-left:4px;">(${esc(d.psono || '-')})</span>
                </div>
                <div>
                  <span class="badge" style="font-size:0.7rem; font-weight:700; padding:2px 6px; border-radius:4px; background:${d.status === 'Dispatched' ? '#dcfce7; color:#15803d; border:1px solid #86efac;' : '#dbeafe; color:#1e40af; border:1px solid #93c5fd;'}">
                    ${esc(d.status)}
                  </span>
                  <b style="color:#0f172a; margin-left:5px;">${Number(d.tray_count || 0)} Trays</b>
                </div>
              </div>
            `).join('');

            dispatchesHtml = `
              <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:8px 12px; margin-bottom:10px; text-align:left;">
                <div style="font-size:0.76rem; font-weight:700; color:#475569; margin-bottom:4px; display:flex; align-items:center; justify-content:space-between;">
                  <span><i class="fa fa-truck-moving text-secondary mr-1"></i> Trays Dispatched / Loaded from this Batch:</span>
                  <span class="badge badge-light" style="font-size:0.7rem; border:1px solid #cbd5e1;">${trayData.dispatches.length} client(s)</span>
                </div>
                <div style="max-height:100px; overflow-y:auto; padding-right:2px;">
                  ${dispRows}
                </div>
              </div>
            `;
          }

          Swal.fire({
            width: '510px',
            padding: '1.25rem',
            html: `
              <div style="text-align: center; margin-bottom: 12px;">
                <div style="width: 48px; height: 48px; border-radius: 50%; background: #eff6ff; border: 2px solid #bfdbfe; color: #2563eb; display: inline-flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 6px; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.12);">
                  <i class="fa fa-boxes"></i>
                </div>
                <h3 style="font-size: 1.15rem; font-weight: 800; color: #0f172a; margin: 0 0 2px 0;">Load Tray from Other Client's Batch</h3>
                <p style="font-size: 0.78rem; color: #64748b; margin: 0;">Same crop variety from another client's batch / order</p>
              </div>

              <!-- Origin Batch & Crop Variety -->
              <div style="text-align: left; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 12px; margin-bottom: 10px; font-size: 0.82rem; line-height: 1.5;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2px;">
                  <span style="color: #64748b; font-weight: 600;">Origin Batch / Order:</span>
                  <span><b style="color: #0f172a;">${esc(trayData.origin_stockorderno || '-')}</b> <small class="text-muted">(${esc(trayData.origin_psono || '-')})</small></span>
                </div>
                <div style="color: #059669; font-weight: 700; font-size: 0.84rem;">
                  <i class="fa fa-check-circle mr-1"></i>${esc(trayData.productname)}
                </div>
              </div>

              <!-- Original Farmer Details Card -->
              <div style="background: #ffffff; border: 1px solid #cbd5e1; border-left: 4px solid #3b82f6; border-radius: 8px; padding: 10px 12px; margin-bottom: 10px; text-align: left; font-size: 0.81rem;">
                <div style="font-weight: 700; color: #1e293b; margin-bottom: 6px; display: flex; align-items: center; justify-content: space-between;">
                  <span><i class="fa fa-user mr-1 text-primary"></i> Original Farmer Details</span>
                  ${trayData.origin_delivery_date ? `<span class="badge" style="background:#e0f2fe; color:#0369a1; font-weight:700; font-size:0.74rem; border:1px solid #bae6fd;"><i class="fa fa-calendar-alt mr-1"></i>Deliv: ${esc(trayData.origin_delivery_date)}</span>` : ''}
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                  <div>
                    <span style="color: #64748b; font-size: 0.74rem;">Farmer Name:</span><br>
                    <b style="color: #0f172a; font-size: 0.84rem;">${esc(trayData.origin_client_name || '-')}</b>
                  </div>
                  <div>
                    <span style="color: #64748b; font-size: 0.74rem;">Mobile No:</span><br>
                    <b style="color: #0f172a; font-size: 0.84rem;"><i class="fa fa-phone text-muted mr-1"></i>${esc(trayData.origin_client_phone || '-')}</b>
                  </div>
                </div>
              </div>

              <!-- Bed & Batch Stock Status Card -->
              <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 12px; margin-bottom: 10px; font-size: 0.79rem; text-align: left;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; padding-bottom: 3px; border-bottom: 1px dashed #e2e8f0;">
                  <span style="font-weight: 700; color: #334155;"><i class="fa fa-cubes text-secondary mr-1"></i> Active Trays in Bed:</span>
                  <span class="badge" style="background: #f1f5f9; color: #475569; font-weight: 700;">${esc(trayData.polyhousename)} &bull; ${esc(trayData.bed_no)}</span>
                </div>
                <div style="display: flex; justify-content: space-between; gap: 6px; text-align: center;">
                  <div style="flex: 1; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 6px; padding: 4px 6px;">
                    <div style="font-size: 0.7rem; color: #047857; font-weight: 600;">Active in Bed</div>
                    <div style="font-size: 0.95rem; font-weight: 800; color: #065f46;">${Number(trayData.bed_active_trays !== undefined ? trayData.bed_active_trays : (trayData.available_trays || 0))} Trays</div>
                  </div>
                  <div style="flex: 1; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 4px 6px;">
                    <div style="font-size: 0.7rem; color: #1d4ed8; font-weight: 600;">Batch Available</div>
                    <div style="font-size: 0.95rem; font-weight: 800; color: #1e40af;">${Number(trayData.available_trays || 0)} Trays</div>
                  </div>
                  <div style="flex: 1; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 4px 6px;">
                    <div style="font-size: 0.7rem; color: #64748b; font-weight: 600;">Total in Bed</div>
                    <div style="font-size: 0.95rem; font-weight: 800; color: #334155;">${Number(trayData.bed_total_trays || 0)} Trays</div>
                  </div>
                </div>
              </div>

              ${dispatchesHtml}

              <!-- Confirmation Checkbox -->
              <label style="display: flex; align-items: flex-start; gap: 8px; font-size: 0.84rem; font-weight: 600; color: #1e293b; cursor: pointer; text-align: left; background: #eff6ff; border: 1.5px solid #bfdbfe; border-radius: 8px; padding: 10px 12px; margin-bottom: 0;">
                <input type="checkbox" id="cbConfirmBatchSwap" style="margin-top: 2px; width: 18px; height: 18px; accent-color: #2563eb; cursor: pointer;">
                <span>I confirm loading this tray for Order <b>${esc(currentActivePsono)}</b>.</span>
              </label>
            `,
            showCancelButton: true,
            confirmButtonText: '<i class="fa fa-check mr-1"></i> Confirm & Load Tray',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#64748b',
            didOpen: () => {
              const confirmBtn = Swal.getConfirmButton();
              confirmBtn.disabled = true;
              $('#cbConfirmBatchSwap').on('change', function () {
                confirmBtn.disabled = !this.checked;
              });
            },
            didClose: () => { unpauseScanner(); }
          }).then((result) => {
            if (result.isConfirmed) {
              trayData.is_swapped = true;
              proceedWithBilledScan(trayData, barcode);
              playScanBeep();
            }
            unpauseScanner();
          });
          return;
        }

        if (res.status !== 'success') {
          playWarningTone();
          const isCapacityShortage = (res.message && res.message.toLowerCase().includes('cannot load allocated tray'));
          const isTrayDispatched = (res.error_type === 'tray_dispatched');
          const isTrayAllocatedOther = (res.error_type === 'tray_allocated_to_other');
          const isTrayInactive = (res.error_type === 'tray_inactive');

          let modalTitle = 'Lookup Failed';
          let modalIcon = 'error';
          let confirmColor = '#dc2626';

          if (isCapacityShortage) {
            modalTitle = 'Cannot Load Allocated Tray';
            modalIcon = 'warning';
            confirmColor = '#e11d48';
          } else if (isTrayDispatched) {
            modalTitle = 'Tray Already Dispatched!';
            modalIcon = 'error';
            confirmColor = '#be123c';
          } else if (isTrayAllocatedOther) {
            modalTitle = 'Tray Allocated to Other Person!';
            modalIcon = 'error';
            confirmColor = '#be123c';
          } else if (isTrayInactive) {
            modalTitle = 'Inactive Tray!';
            modalIcon = 'warning';
            confirmColor = '#d97706';
          }

          let modalHtml = '';
          if (isCapacityShortage) {
            modalHtml = `<div style="text-align: left; background: #fff1f2; border: 1.5px solid #fecdd3; border-radius: 8px; padding: 12px; color: #9f1239; font-size: 0.88rem; line-height: 1.5;">
                           <i class="fa fa-triangle-exclamation mr-1" style="color: #e11d48;"></i>
                           <b>${esc(res.message)}</b>
                         </div>`;
          } else if (isTrayDispatched || isTrayAllocatedOther) {
            const clientName = res.client_name || res.data?.client_name || 'Other Person';
            const orderNo = res.psono || res.data?.psono || '-';
            const trayBc = res.barcode || res.data?.barcode || cleanBarcode;
            modalHtml = `
              <div style="text-align: left; background: #fff1f2; border: 1.5px solid #fecdd3; border-radius: 10px; padding: 14px; color: #9f1239; font-size: 0.88rem; line-height: 1.6;">
                <div style="font-weight: 800; font-size: 1rem; margin-bottom: 6px; color: #be123c; display: flex; align-items: center; gap: 6px;">
                  <i class="fa fa-circle-xmark" style="font-size: 1.15rem;"></i>
                  <span>${isTrayDispatched ? 'Dispatched Tray Cannot Be Loaded' : 'Tray Already Allocated / Loaded'}</span>
                </div>
                <div style="color: #334155; margin-bottom: 10px; font-weight: 500;">${esc(res.message)}</div>
                <div style="background: #ffffff; border: 1px solid #fed7aa; border-radius: 8px; padding: 10px 12px; font-size: 0.83rem; color: #475569;">
                  <div style="margin-bottom: 4px; display: flex; justify-content: space-between;">
                    <span style="color: #64748b;">Person / Client:</span>
                    <strong style="color: #0f172a;">${esc(clientName)}</strong>
                  </div>
                  <div style="margin-bottom: 4px; display: flex; justify-content: space-between;">
                    <span style="color: #64748b;">Order No (PSONO):</span>
                    <strong style="color: #2563eb;">${esc(orderNo)}</strong>
                  </div>
                  <div style="display: flex; justify-content: space-between;">
                    <span style="color: #64748b;">Tray Barcode:</span>
                    <code style="color: #0f172a; font-weight: 700;">${esc(trayBc)}</code>
                  </div>
                </div>
                <div style="margin-top: 8px; font-size: 0.78rem; color: #991b1b; font-weight: 600;">
                  <i class="fa fa-info-circle mr-1"></i> Only active, unallocated polyhouse trays can be loaded.
                </div>
              </div>`;
          } else {
            modalHtml = esc(res.message || 'Tray record could not be found.');
          }

          Swal.fire({
            icon: modalIcon,
            title: modalTitle,
            html: modalHtml,
            confirmButtonColor: confirmColor,
            confirmButtonText: isCapacityShortage ? '<i class="fa fa-check mr-1"></i> Understood - Re-allocate' : 'OK',
            didClose: () => { unpauseScanner(); }
          }).then(() => {
            unpauseScanner();
            $('#inputBarcodeQuery').val('').focus();
          });
          return;
        }

        // Successfully found tray!
        playScanBeep();
        const trayData = res.data;

        // If it was an approved variety change, show toast confirmation
        if (trayData.is_approved_variety_change) {
          const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3500,
            timerProgressBar: true
          });
          Toast.fire({
            icon: 'success',
            title: `Approved Variety Change (${esc(trayData.approval_request_no || 'Approved')}) verified & added!`
          });
        }

        // Handle Free Bonus Scan Mode
        if (currentScanTargetMode === 'bonus') {
          promptBonusPlantCount(trayData);
          return;
        }

        // If in billed mode, but all beds are already verified AND bonus is needed:
        recalcBonusPlants();
        const totalBeds = (currentAllocatedBedsDetail ? currentAllocatedBedsDetail.length : 0);
        const verifiedBeds = (currentAllocatedBedsDetail ? currentAllocatedBedsDetail.filter(b => b.is_verified).length : 0);
        const allBedsDone = (totalBeds > 0 && verifiedBeds >= totalBeds);
        const bonusPending = (currentBonusPlantsRequired > 0 && currentBonusPlantsVerified < currentBonusPlantsRequired);

        if (allBedsDone && bonusPending) {
          Swal.fire({
            icon: 'question',
            title: 'Use for Free Bonus Plants?',
            html: `All allocated beds are already verified!<br><br>` +
                  `Order still requires <b>${currentBonusPlantsRequired - currentBonusPlantsVerified} Free Bonus plants</b>.<br>` +
                  `Do you want to allocate tray <b>"${esc(barcode)}"</b> for Free Bonus?`,
            showCancelButton: true,
            confirmButtonText: '<i class="fa fa-gift mr-1"></i> Yes, Allocate for Bonus',
            cancelButtonText: 'No, Regular Scan',
            confirmButtonColor: '#059669',
            cancelButtonColor: '#64748b'
          }).then((r) => {
            if (r.isConfirmed) {
              promptBonusPlantCount(trayData);
            } else {
              proceedWithBilledScan(trayData, barcode);
            }
          });
          return;
        }

        proceedWithBilledScan(trayData, barcode);
      },
      error: function (xhr, status, err) {
        playWarningTone();
        Swal.fire({
          icon: 'error',
          title: 'Lookup Error',
          text: 'Server communication error: ' + err,
          confirmButtonColor: '#dc2626',
          didClose: () => { unpauseScanner(); }
        }).then(() => { unpauseScanner(); });
      },
      complete: function () {
        inFlightScans.delete(cleanUpper);
      }
    });
  }

  // -------------------------------------------------------------
  // Bed Verification Checklist (1 Tray Scan Per Allocated Bed & Order ID)
  // -------------------------------------------------------------
  function doesTrayMatchBed(t, b) {
    if (!t || !b) return false;
    if (b.is_verified && b.verified_barcode && (t.barcode === b.verified_barcode || t.scanned_code === b.verified_barcode)) {
      return true;
    }
    const tBatch = $.trim(t.stockorder_no || t.origin_stockorderno || t.dispd_agst_stockorderno || '').toLowerCase();
    const tBed = $.trim(t.bed_no || '').toLowerCase();
    const bBatch = $.trim(b.actual_stockorder_no || b.stockorder_no || '').toLowerCase();
    const bBed = $.trim(b.actual_bed_name || b.bed_name || '').toLowerCase();
    const tCode = $.trim(t.scanned_code || t.barcode || '').toLowerCase();

    const numT = tBatch.replace(/\D/g, '');
    const numB = bBatch.replace(/\D/g, '');
    const numCode = tCode.replace(/\D/g, '');

    const batchMatches = (!bBatch || !tBatch || tBatch === bBatch || tCode === bBatch || (numB && (numB === numT || numB === numCode)));
    const bedMatches = (!bBed || !tBed || tBed === bBed || tCode === bBed || (tCode && tCode.replace(/\D/g, '') === bBed.replace(/\D/g, '')));

    if (bBatch && bBed) {
      // Must match BOTH Order ID/Batch AND Bed (or direct batch/bed QR match)
      return (batchMatches && bedMatches) || (tCode && (tCode === bBatch || (numB && numB === numCode))) || (tCode && (tCode === bBed || (numCode && numCode === bBed.replace(/\D/g, ''))));
    } else if (bBatch) {
      return batchMatches;
    } else {
      return bedMatches;
    }
  }

  function checkAndVerifyBedForTray(trayData) {
    if (!currentAllocatedBedsDetail || currentAllocatedBedsDetail.length === 0) return;

    // 1. First, search for an UNVERIFIED bed entry that matches BOTH batch and bed
    let matchedBed = currentAllocatedBedsDetail.find(b => !b.is_verified && doesTrayMatchBed(trayData, b));

    // 2. If no unverified match, check already verified entries matching this tray
    if (!matchedBed) {
      matchedBed = currentAllocatedBedsDetail.find(b => doesTrayMatchBed(trayData, b));
    }

    // 3. Fallback only if there is exactly 1 bed entry in the entire order
    if (!matchedBed && currentAllocatedBedsDetail.length === 1) {
      matchedBed = currentAllocatedBedsDetail[0];
    }

    // 4. Fallback: match by bed name/number or update generic bed entry
    if (!matchedBed && trayData.bed_no) {
      const cleanTrayBed = $.trim(trayData.bed_no).toLowerCase();
      const numTrayBed = cleanTrayBed.replace(/\D/g, '');
      matchedBed = currentAllocatedBedsDetail.find(b => {
        const bName = $.trim(b.bed_name || '').toLowerCase();
        const numB = bName.replace(/\D/g, '');
        return !b.is_verified && (bName === cleanTrayBed || (numTrayBed && numTrayBed === numB) || bName === 'bed-allocated' || bName === 'bed-?');
      });
      if (matchedBed && (matchedBed.bed_name === 'Bed-Allocated' || matchedBed.bed_name === 'Bed-?')) {
        matchedBed.bed_name = trayData.bed_no;
        if (trayData.polyhousename) matchedBed.polyhouse_name = trayData.polyhousename;
        if (trayData.stockorder_no) matchedBed.stockorder_no = trayData.stockorder_no;
      }
    }

    if (matchedBed) {
      const wasAlreadyVerified = matchedBed.is_verified;
      matchedBed.is_verified = true;
      matchedBed.verified_barcode = trayData.barcode;
      if (trayData.is_swapped) {
        matchedBed.actual_bed_name = trayData.bed_no;
        matchedBed.actual_stockorder_no = trayData.stockorder_no;
        matchedBed.actual_polyhouse_name = trayData.polyhousename;
      }

      const verifiedCount = currentAllocatedBedsDetail.filter(b => b.is_verified).length;
      const totalCount = currentAllocatedBedsDetail.length;

      if (!wasAlreadyVerified) {
        const Toast = Swal.mixin({
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 3200,
          timerProgressBar: true
        });
        Toast.fire({
          icon: 'success',
          title: `Bed ${esc(matchedBed.bed_name || '')} (${esc(matchedBed.stockorder_no || '')}) Verified! (${verifiedCount} of ${totalCount} complete)`
        });
      }
    }
  }

  function renderBedVerificationChecklist() {
    const $container = $('#bedVerificationListContainer');
    const $card = $('#bedVerificationCard');
    const $progress = $('#lblBedVerificationProgress');

    if (!currentAllocatedBedsDetail || currentAllocatedBedsDetail.length === 0) {
      $card.hide();
      return;
    }

    $card.show();
    const totalBeds = currentAllocatedBedsDetail.length;
    let verifiedBeds = 0;

    let html = '';
    currentAllocatedBedsDetail.forEach((b, idx) => {
      // Find matching scanned tray from grid
      const matchingScanned = scannedTraysList.find(t => doesTrayMatchBed(t, b));

      if (matchingScanned) {
        b.is_verified = true;
        if (!b.verified_barcode) {
          b.verified_barcode = matchingScanned.barcode;
        }
      }

      const isVer = !!b.is_verified;
      if (isVer) {
        verifiedBeds++;
      }

      const bPlants = Number(b.plants || b.allocated_qty || 0);
      const bTrays = Number(b.tray_count || 0);

      html += `
        <div class="bed-verify-item d-flex align-items-center justify-content-between p-2 rounded" 
             style="background: ${isVer ? '#ecfdf5' : '#ffffff'}; border: 1.5px solid ${isVer ? '#10b981' : '#e2e8f0'}; transition: all 0.2s ease;">
          <div class="d-flex align-items-center gap-2">
            <div style="width: 28px; height: 28px; border-radius: 50%; background: ${isVer ? '#10b981' : '#f1f5f9'}; color: ${isVer ? '#fff' : '#64748b'}; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700;">
              ${isVer ? '<i class="fa fa-check"></i>' : (idx + 1)}
            </div>
            <div>
              <div class="d-flex align-items-center gap-2">
                <strong style="color: #0f172a; font-size: 0.88rem;">${esc(b.actual_polyhouse_name || b.polyhouse_name || 'PH')} &bull; ${esc(b.actual_bed_name || b.bed_name || 'Bed')}</strong>
                ${(b.actual_stockorder_no || b.stockorder_no) ? `<span class="badge" style="background:#2563eb; color:#fff; font-size:10px; padding:2px 6px; border-radius:4px;"><i class="fa fa-boxes mr-1"></i>Batch: ${esc(b.actual_stockorder_no || b.stockorder_no)}</span>` : ''}
                ${(b.actual_bed_name && b.actual_bed_name !== b.bed_name) ? `<span class="badge" style="background:#059669; color:#fff; font-size:9px; padding:2px 5px; border-radius:4px;"><i class="fa fa-check mr-1"></i>Swapped</span>` : ''}
              </div>
              <div class="text-muted" style="font-size: 0.77rem;">
                Allocated: <b class="text-dark">${bPlants.toLocaleString()} Plants</b> (${bTrays} Trays)
              </div>
            </div>
          </div>
          <div class="text-end">
            ${isVer ? `
              <span class="badge" style="background:#dcfce7; color:#15803d; border:1px solid #86efac; font-size:11px; padding:4px 8px; font-weight:700;">
                <i class="fa fa-check-circle mr-1"></i> Verified ${b.verified_barcode ? `<small class="font-monospace">(${esc(b.verified_barcode)})</small>` : ''}
              </span>
            ` : `
              <span class="badge" style="background:#fffbeb; color:#b45309; border:1px solid #fcd34d; font-size:11px; padding:4px 8px; font-weight:600;">
                <i class="fa fa-hourglass-start mr-1 text-warning"></i> 1 Tray Scan Required
              </span>
            `}
          </div>
        </div>
      `;
    });

    $container.html(html);

    if (verifiedBeds >= totalBeds) {
      $progress.css({ background: '#dcfce7', color: '#15803d', borderColor: '#86efac' })
        .html(`<i class="fa fa-check-circle mr-1"></i> All ${totalBeds} Beds Verified`);
    } else {
      $progress.css({ background: '#fef3c7', color: '#92400e', borderColor: '#f59e0b' })
        .html(`<i class="fa fa-clock mr-1"></i> ${verifiedBeds} of ${totalBeds} Beds Verified`);
    }

    recalcBonusPlants();
    renderBonusVerificationChecklist();
    updateConfirmLoadButtonState(verifiedBeds, totalBeds);
  }

  // Helper to complete billed tray addition (with Whole Bed Auto-Load)
  function proceedWithBilledScan(trayData, barcode) {
    const traysToAdd = (trayData.autoloaded_trays && Array.isArray(trayData.autoloaded_trays) && trayData.autoloaded_trays.length > 0)
      ? trayData.autoloaded_trays
      : [trayData];

    // Mark matching bed as verified
    checkAndVerifyBedForTray(trayData);

    let addedCount = 0;
    traysToAdd.forEach(t => {
      const cleanBCode = $.trim(t.barcode || '').toUpperCase();
      if (!cleanBCode) return;

      const alreadyExists = scannedTraysList.some(item => $.trim(item.barcode || '').toUpperCase() === cleanBCode);
      if (!alreadyExists) {
        if (trayData.is_swapped) t.is_swapped = true;
        checkAndVerifyBedForTray(t);
        scannedTraysList.push(t);
        addedCount++;
      }
    });

    renderScannedGrid();
    renderBedVerificationChecklist();

    const pendingTrays = scannedTraysList.filter(t => t.current_status !== 'Loaded' && t.current_status !== 'ReadyToDispatch');
    if (pendingTrays.length > 0) {
      scheduleAutoCommit();
    }

    if (addedCount > 1) {
      const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
      });
      Toast.fire({
        icon: 'success',
        title: `Auto-loaded ${addedCount} required trays from Bed ${esc(trayData.bed_no || '')}!`
      });
    }

    unpauseScanner();
  }

  // -------------------------------------------------------------
  // Scan Target Mode Switcher (Billed vs Bonus)
  // -------------------------------------------------------------
  window.switchScanTargetMode = function (mode) {
    currentScanTargetMode = mode;
    if (mode === 'bonus') {
      $('#btnScanModeBonus').css({ background: '#15803d', color: '#ffffff', borderColor: '#15803d' });
      $('#btnScanModeBilled').css({ background: '#ffffff', color: '#2563eb', border: '1.5px solid #93c5fd' });
      $('#inputBarcodeQuery').attr('placeholder', 'Scan or enter Tray Barcode for Free Bonus');
      $('#btnAddTrayAction').html('<i class="fa fa-gift mr-1"></i> <span>Add Bonus Tray</span>');
    } else {
      $('#btnScanModeBilled').css({ background: '#2563eb', color: '#ffffff', border: 'none' });
      $('#btnScanModeBonus').css({ background: '#ffffff', color: '#15803d', border: '1.5px solid #86efac' });
      $('#inputBarcodeQuery').attr('placeholder', 'Scan or enter Tray Barcode');
      $('#btnAddTrayAction').html('<i class="fa fa-plus-circle mr-1"></i> <span>Add Tray</span>');
    }
    $('#inputBarcodeQuery').focus();
  };

  // -------------------------------------------------------------
  // Free Bonus Plant Allocation Prompt & Checklist
  // -------------------------------------------------------------
  function promptBonusPlantCount(trayData) {
    const cleanBarcode = $.trim(trayData.barcode || '').toUpperCase();
    const alreadyInBonus = currentBonusTraysList.some(t => $.trim(t.barcode || '').toUpperCase() === cleanBarcode);
    if (alreadyInBonus) {
      playWarningTone();
      Swal.fire({
        icon: 'warning',
        title: 'Already Added to Bonus!',
        html: `Tray <b>"${esc(cleanBarcode)}"</b> has already been allocated for Free Bonus plants.`,
        confirmButtonColor: '#e11d48',
        didClose: () => { unpauseScanner(); }
      }).then(() => { unpauseScanner(); });
      return;
    }

    const trayCap = Number(trayData.qtypertray || 126);
    const remainingBonus = Math.max(0, currentBonusPlantsRequired - currentBonusPlantsVerified);
    const defaultCount = Math.min(trayCap, remainingBonus > 0 ? remainingBonus : trayCap);

    Swal.fire({
      title: '🎁 Free Bonus Plant Allocation',
      html: `
        <div class="text-start p-2 rounded mb-3" style="background:#f0fdf4; border:1px solid #86efac; font-size:13px; text-align:left;">
          <div class="mb-1"><strong>Tray Barcode:</strong> <span class="badge font-monospace" style="background:#0f172a; color:#fff; font-size:12px;">${esc(cleanBarcode)}</span></div>
          <div class="mb-1"><strong>Location:</strong> ${esc(trayData.polyhousename || 'Polyhouse')} &bull; ${esc(trayData.bed_no || 'Bed')}</div>
          <div class="mb-1"><strong>Tray Capacity:</strong> ${trayCap} Plants</div>
          <div class="mb-1"><strong>Total Bonus Required:</strong> ${currentBonusPlantsRequired} Plants</div>
          <div><strong>Remaining Needed:</strong> <b style="color:#b91c1c; font-size:14px;">${remainingBonus} Plants</b></div>
        </div>
        <label for="swalInputBonusQty" class="form-label font-weight-bold text-dark mb-1" style="font-size:13px;">
          Enter plant count to take from this tray for Free Bonus:
        </label>
        <input type="number" id="swalInputBonusQty" class="form-control form-control-lg text-center font-weight-bold text-success" 
               value="${defaultCount}" min="1" max="${trayCap}" autofocus style="font-size:22px; border:2px solid #10b981;">
      `,
      showCancelButton: true,
      confirmButtonText: '<i class="fa fa-check mr-1"></i> Confirm Bonus Plants',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#059669',
      cancelButtonColor: '#64748b',
      didOpen: () => {
        const inp = document.getElementById('swalInputBonusQty');
        if (inp) {
          inp.focus();
          inp.select();
        }
      },
      preConfirm: () => {
        const val = parseInt($('#swalInputBonusQty').val(), 10);
        if (isNaN(val) || val <= 0) {
          Swal.showValidationMessage('Please enter a valid plant count greater than 0.');
          return false;
        }
        if (val > trayCap) {
          Swal.showValidationMessage(`Count cannot exceed tray capacity of ${trayCap} plants.`);
          return false;
        }
        return val;
      }
    }).then((result) => {
      unpauseScanner();
      if (!result.isConfirmed) return;

      const count = result.value;
      const bonusItem = {
        barcode: cleanBarcode,
        polyhouse: trayData.polyhousename || '',
        bed_no: trayData.bed_no || '',
        qty_taken: count,
        capacity: trayCap,
        stockorder_no: trayData.stockorder_no || trayData.origin_stockorderno || ''
      };

      currentBonusTraysList.push(bonusItem);
      recalcBonusPlants();
      renderBonusVerificationChecklist();

      // Also add trayData to scannedTraysList so it appears in live grid with bonus badge
      trayData.is_bonus = true;
      trayData.bonus_plants = count;
      const existingIdx = scannedTraysList.findIndex(t => $.trim(t.barcode || '').toUpperCase() === cleanBarcode);
      if (existingIdx !== -1) {
        scannedTraysList[existingIdx].is_bonus = true;
        scannedTraysList[existingIdx].bonus_plants = count;
      } else {
        scannedTraysList.push(trayData);
      }
      renderScannedGrid();

      playScanBeep();
      const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3200,
        timerProgressBar: true
      });
      Toast.fire({
        icon: 'success',
        title: `+${count} Free Bonus Plants Verified! (${currentBonusPlantsVerified}/${currentBonusPlantsRequired})`
      });

      // Auto-switch back to billed if all bonus fulfilled
      if (currentBonusPlantsVerified >= currentBonusPlantsRequired) {
        switchScanTargetMode('billed');
      }
    });
  }

  function recalcBonusPlants() {
    const manualBonusPlants = (currentBonusTraysList || []).reduce((sum, t) => sum + Number(t.qty_taken || 0), 0);
    
    // Auto-calculate bonus plants from verified beds / trays:
    // In nursery orders for shifted plants (126-cell tray = 125 billed + 1 extra free bonus plant),
    // verifying the trays/beds inherently accounts for the extra bonus plants (+1 per tray).
    // Workers should not be forced to scan extra trays for bonus plants.
    let autoBonusPlants = 0;
    if (currentBonusPlantsRequired > 0) {
      let verifiedTrays = 0;
      if (currentAllocatedBedsDetail && currentAllocatedBedsDetail.length > 0) {
        currentAllocatedBedsDetail.forEach(b => {
          if (b.is_verified) {
            verifiedTrays += Number(b.tray_count || 0);
          }
        });
      }

      // Also count directly scanned billed trays if greater
      const scannedBilledCount = (scannedTraysList || []).filter(t => !t.is_bonus).length;
      if (scannedBilledCount > verifiedTrays) {
        verifiedTrays = scannedBilledCount;
      }

      // If all allocated beds are verified, all required bonus plants are inherently fulfilled with the verified trays
      const totalBeds = currentAllocatedBedsDetail ? currentAllocatedBedsDetail.length : 0;
      const verifiedBeds = currentAllocatedBedsDetail ? currentAllocatedBedsDetail.filter(b => b.is_verified).length : 0;
      if (totalBeds > 0 && verifiedBeds >= totalBeds) {
        autoBonusPlants = currentBonusPlantsRequired;
      } else {
        autoBonusPlants = Math.min(currentBonusPlantsRequired, verifiedTrays);
      }
    }

    currentBonusPlantsVerified = Math.max(manualBonusPlants, Math.min(currentBonusPlantsRequired, manualBonusPlants + autoBonusPlants));

    const rem = Math.max(0, currentBonusPlantsRequired - currentBonusPlantsVerified);
    if (rem === 0 && currentBonusPlantsRequired > 0) {
      $('#lblScanModeBonusBadge').css({ background: '#059669' }).text('✔ Complete');
    } else {
      $('#lblScanModeBonusBadge').css({ background: '#15803d' }).text(`${rem} Left`);
    }
  }

  function renderBonusVerificationChecklist() {
    const $container = $('#bonusTraysListContainer');
    const $card = $('#bonusVerificationCard');
    const $progress = $('#lblBonusVerificationProgress');

    if (!currentBonusPlantsRequired || currentBonusPlantsRequired <= 0) {
      $card.hide();
      return;
    }

    $card.show();
    $('#lblBonusTargetQty').text(currentBonusPlantsRequired.toLocaleString());

    const isComplete = (currentBonusPlantsVerified >= currentBonusPlantsRequired);
    const rem = Math.max(0, currentBonusPlantsRequired - currentBonusPlantsVerified);

    if (isComplete) {
      $progress.css({ background: '#dcfce7', color: '#15803d', borderColor: '#86efac' })
        .html(`<i class="fa fa-check-circle mr-1"></i> ${currentBonusPlantsVerified} of ${currentBonusPlantsRequired} Plants Verified`);
    } else {
      $progress.css({ background: '#fef3c7', color: '#92400e', borderColor: '#f59e0b' })
        .html(`<i class="fa fa-hourglass-half mr-1"></i> ${currentBonusPlantsVerified} of ${currentBonusPlantsRequired} Plants (${rem} Left)`);
    }

    if (!currentBonusTraysList || currentBonusTraysList.length === 0) {
      if (isComplete) {
        $container.html(`
          <div class="p-2 rounded d-flex align-items-center justify-content-between" style="background:#ecfdf5; border:1.5px solid #10b981; font-size:0.84rem;">
            <div class="d-flex align-items-center gap-2">
              <div style="width:28px; height:28px; border-radius:50%; background:#10b981; color:#fff; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700;">
                <i class="fa fa-check"></i>
              </div>
              <div>
                <strong style="color:#065f46; font-size:0.88rem;">+${currentBonusPlantsVerified} Free Bonus Plants Included</strong>
                <div class="text-muted" style="font-size:0.77rem; color:#047857 !important;">
                  Automatically added from verified trays (+1 extra plant per 126-cell tray). No extra tray scan needed.
                </div>
              </div>
            </div>
            <span class="badge" style="background:#059669; color:#fff; font-size:11px; padding:4px 8px; border-radius:4px; font-weight:700;">
              <i class="fa fa-check-circle mr-1"></i> Auto-Verified
            </span>
          </div>
        `);
      } else {
        $container.html(`
          <div class="p-2 text-center text-muted" style="background:#ffffff; border:1px dashed #86efac; border-radius:6px; font-size:0.83rem;">
            <i class="fa fa-info-circle mr-1 text-success"></i> ${currentBonusPlantsVerified > 0 ? `+${currentBonusPlantsVerified} of ${currentBonusPlantsRequired} bonus plants auto-added from verified trays.` : 'Bonus plants will be automatically added (+1/tray) as allocated beds are verified.'} 
            <button type="button" class="btn btn-sm btn-success ms-2" onclick="switchScanTargetMode('bonus')" style="font-size:11px; padding:2px 8px;">
              <i class="fa fa-gift mr-1"></i>Scan Free Bonus Tray
            </button>
          </div>
        `);
      }
    } else {
      let html = '';
      currentBonusTraysList.forEach((t) => {
        html += `
          <div class="bed-verify-item d-flex align-items-center justify-content-between p-2 rounded" 
               style="background: #ffffff; border: 1.5px solid #10b981; transition: all 0.2s ease;">
            <div class="d-flex align-items-center gap-2">
              <div style="width: 28px; height: 28px; border-radius: 50%; background: #10b981; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700;">
                <i class="fa fa-check"></i>
              </div>
              <div>
                <div class="d-flex align-items-center gap-2">
                  <strong style="color: #0f172a; font-size: 0.88rem;">Tray: ${esc(t.barcode)}</strong>
                  <span class="badge" style="background:#059669; color:#fff; font-size:10px; padding:2px 7px; border-radius:4px;">
                    <i class="fa fa-gift mr-1"></i>Bonus: ${t.qty_taken} Plants
                  </span>
                  ${t.stockorder_no ? `<span class="badge" style="background:#2563eb; color:#fff; font-size:10px; padding:2px 6px; border-radius:4px;"><i class="fa fa-boxes mr-1"></i>Batch: ${esc(t.stockorder_no)}</span>` : ''}
                </div>
                <div class="text-muted" style="font-size: 0.77rem;">
                  Location: <b>${esc(t.polyhouse || 'Polyhouse')} &bull; ${esc(t.bed_no || 'Bed')}</b> (Tray Capacity: ${t.capacity || 126} Plants)
                </div>
              </div>
            </div>
            <div>
              <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeBonusTray('${esc(t.barcode)}')" title="Remove this Bonus Tray" style="padding:2px 8px; font-size:11px; border-radius:4px;">
                <i class="fa fa-times"></i>
              </button>
            </div>
          </div>
        `;
      });
      $container.html(html);
    }

    const totalBeds = currentAllocatedBedsDetail ? currentAllocatedBedsDetail.length : 0;
    const verifiedBeds = currentAllocatedBedsDetail ? currentAllocatedBedsDetail.filter(b => b.is_verified).length : 0;
    updateConfirmLoadButtonState(verifiedBeds, totalBeds);
  }

  window.removeBonusTray = function (barcode) {
    const cleanB = $.trim(barcode || '').toUpperCase();
    const idx = currentBonusTraysList.findIndex(t => $.trim(t.barcode || '').toUpperCase() === cleanB);
    if (idx !== -1) {
      currentBonusTraysList.splice(idx, 1);
      recalcBonusPlants();
      renderBonusVerificationChecklist();
    }
    // Also remove from scannedTraysList if present
    const sIdx = scannedTraysList.findIndex(t => $.trim(t.barcode || '').toUpperCase() === cleanB && t.is_bonus);
    if (sIdx !== -1) {
      scannedTraysList.splice(sIdx, 1);
      renderScannedGrid();
    }
  };

  function updateConfirmLoadButtonState(verifiedBeds, totalBeds) {
    const $btnConfirm = $('#btnConfirmLoadAction');
    const $btnStatus = $('#btnModalUpdateStatusAction');

    // While auto-updating database in the background, hide action buttons
    if (isAutoCommitting) {
      $btnConfirm.hide();
      $btnStatus.hide();
      return;
    }

    const pendingToLoad = scannedTraysList.filter(t => t.current_status !== 'Loaded' && t.current_status !== 'ReadyToDispatch');
    const isAllBedsVerified = (totalBeds === 0 || verifiedBeds >= totalBeds);
    const isBonusVerified = (currentBonusPlantsRequired === 0 || currentBonusPlantsVerified >= currentBonusPlantsRequired);
    const count = scannedTraysList.length;

    const isOrderFullyLoaded = (currentActiveLoadedTrays >= currentTargetRequiredTrays && currentTargetRequiredTrays > 0) || (currentActiveLoadedTrays >= totalBeds && pendingToLoad.length === 0 && isBonusVerified);
    if (isOrderFullyLoaded && pendingToLoad.length === 0) {
      $btnConfirm.hide();
      $btnStatus.show();
      return;
    }

    $btnStatus.hide();
    $btnConfirm.show();

    if (!isAllBedsVerified) {
      $btnConfirm.prop('disabled', true)
        .css({ background: '#94a3b8', cursor: 'not-allowed', opacity: '0.7' })
        .html(`<span class="d-none d-sm-inline"><i class="fa fa-lock mr-1"></i> Scan All Beds to Confirm (${verifiedBeds}/${totalBeds} Beds Verified)</span><span class="d-inline d-sm-none"><i class="fa fa-lock mr-1"></i> Scan Beds (${verifiedBeds}/${totalBeds})</span>`);
    } else if (!isBonusVerified) {
      const remBonus = Math.max(0, currentBonusPlantsRequired - currentBonusPlantsVerified);
      $btnConfirm.prop('disabled', true)
        .css({ background: '#d97706', cursor: 'not-allowed', opacity: '0.85' })
        .html(`<span class="d-none d-sm-inline"><i class="fa fa-gift mr-1"></i> Verify Free Bonus (${currentBonusPlantsVerified}/${currentBonusPlantsRequired} Plants, ${remBonus} Left)</span><span class="d-inline d-sm-none"><i class="fa fa-gift mr-1"></i> Bonus (${currentBonusPlantsVerified}/${currentBonusPlantsRequired})</span>`);
    } else {
      $btnConfirm.prop('disabled', count === 0 || pendingToLoad.length === 0)
        .css({ background: 'linear-gradient(135deg, #059669 0%, #047857 100%)', cursor: 'pointer', opacity: '1' })
        .html(`<span class="d-none d-sm-inline"><i class="fa fa-truck-fast mr-1"></i> Confirm & Update status (Beds Verified)</span><span class="d-inline d-sm-none"><i class="fa fa-truck-fast mr-1"></i> Update status</span>`);
    }
  }

  // -------------------------------------------------------------
  // Auto-Commit Live Loaded Trays (5-Second Countdown)
  // -------------------------------------------------------------
  let autoCommitInterval = null;
  let autoCommitCountdown = 5;
  let isAutoCommitting = false;

  function updateAutoCommitIndicator() {
    const $badge = $('#autoSaveStatusBadge');
    const pendingTrays = scannedTraysList.filter(t => t.current_status !== 'Loaded' && t.current_status !== 'ReadyToDispatch');

    if (isAutoCommitting) {
      $badge.removeClass('pill-saved').addClass('pill-saving')
        .html('<i class="fa fa-spinner fa-spin text-primary mr-1"></i> Auto-updating database...')
        .show();
      $('#btnConfirmLoadAction').hide();
      $('#btnModalUpdateStatusAction').hide();
      return;
    }

    if (pendingTrays.length === 0 || !autoCommitInterval) {
      $badge.hide();
      return;
    }

    $badge.removeClass('pill-saving pill-saved')
      .html(`<i class="fa fa-clock text-amber-600 mr-1"></i> Auto-loading in <b>${autoCommitCountdown}s</b>...`)
      .show();
  }

  function scheduleAutoCommit() {
    clearAutoCommit(false);

    const pendingTrays = scannedTraysList.filter(t => t.current_status !== 'Loaded' && t.current_status !== 'ReadyToDispatch');
    if (pendingTrays.length === 0) return;

    // If multiple beds exist and not all are verified yet, do NOT auto-commit
    if (currentAllocatedBedsDetail && currentAllocatedBedsDetail.length > 1) {
      const pendingBeds = currentAllocatedBedsDetail.filter(b => !b.is_verified);
      if (pendingBeds.length > 0) {
        return;
      }
    }

    recalcBonusPlants();
    // If bonus plants are required and not fully verified yet, do NOT auto-commit
    if (currentBonusPlantsRequired > 0 && currentBonusPlantsVerified < currentBonusPlantsRequired) {
      return;
    }

    autoCommitCountdown = 1;
    updateAutoCommitIndicator();

    autoCommitInterval = setInterval(function () {
      autoCommitCountdown--;
      updateAutoCommitIndicator();
      if (autoCommitCountdown <= 0) {
        clearInterval(autoCommitInterval);
        autoCommitInterval = null;
        executeAutoCommit();
      }
    }, 1000);
  }

  function clearAutoCommit(hideBadge = true) {
    if (autoCommitInterval) {
      clearInterval(autoCommitInterval);
      autoCommitInterval = null;
    }
    autoCommitCountdown = 1;
    if (hideBadge) {
      isAutoCommitting = false;
      $('#autoSaveStatusBadge').hide();
    }
  }

  function executeAutoCommit() {
    if (isAutoCommitting) return;

    const pendingTrays = scannedTraysList.filter(t => t.current_status !== 'Loaded' && t.current_status !== 'ReadyToDispatch');
    if (pendingTrays.length === 0) {
      clearAutoCommit(true);
      return;
    }

    isAutoCommitting = true;
    updateAutoCommitIndicator();
    $('#btnConfirmLoadAction').hide();
    $('#btnModalUpdateStatusAction').hide();

    const trayPayload = pendingTrays.map(t => ({
      barcode: t.barcode,
      is_swapped: !!t.is_swapped,
      origin_stockorderno: t.origin_stockorderno || '',
      origin_psono: t.origin_psono || '',
      is_approved_variety_change: !!t.is_approved_variety_change,
      approval_request_no: t.approval_request_no || ''
    }));

    $.ajax({
      url: 'model/readytoload_model.php',
      type: 'POST',
      dataType: 'json',
      data: {
        action: 'confirm_load',
        psono: currentActivePsono,
        item_id: currentActiveItemId,
        product_id: currentActiveProductId,
        product_name: currentActiveProduct,
        single_tray_verification: 1,
        trays: JSON.stringify(trayPayload),
        bonus_trays: JSON.stringify(currentBonusTraysList),
        bonus_plants: currentBonusPlantsVerified,
        vehicle_no: '',
        loading_notes: 'Auto-confirmed after scan'
      },
      success: function (res) {
        isAutoCommitting = false;

        if (res.status !== 'success') {
          playWarningTone();
          $('#autoSaveStatusBadge').removeClass('pill-saving').addClass('bg-danger text-white border-0')
            .html('<i class="fa fa-exclamation-triangle mr-1"></i> Auto-save failed: ' + esc(res.message || 'error'))
            .show();
          setTimeout(() => { clearAutoCommit(true); }, 5000);

          if (res.error_type === 'tray_dispatched' || res.error_type === 'tray_allocated_to_other') {
            if (res.barcode) {
              const badBc = String(res.barcode).toUpperCase();
              scannedTraysList = scannedTraysList.filter(t => String(t.barcode || '').toUpperCase() !== badBc);
            }
            renderScannedGrid();
            if (currentAllocatedBedsDetail && currentAllocatedBedsDetail.length > 0) {
              renderBedVerificationChecklist();
            }
            Swal.fire({
              icon: 'error',
              title: res.error_type === 'tray_dispatched' ? 'Tray Already Dispatched!' : 'Tray Already Allocated!',
              html: `
                <div style="text-align: left; background: #fff1f2; border: 1.5px solid #fecdd3; border-radius: 10px; padding: 14px; color: #9f1239; font-size: 0.88rem; line-height: 1.6;">
                  <div style="font-weight: 800; font-size: 1rem; margin-bottom: 6px; color: #be123c; display: flex; align-items: center; gap: 6px;">
                    <i class="fa fa-circle-xmark" style="font-size: 1.15rem;"></i>
                    <span>Cannot Auto-Load Tray</span>
                  </div>
                  <div style="color: #334155; margin-bottom: 10px; font-weight: 500;">${esc(res.message)}</div>
                  <div style="background: #ffffff; border: 1px solid #fed7aa; border-radius: 8px; padding: 10px 12px; font-size: 0.83rem; color: #475569;">
                    <div style="margin-bottom: 4px; display: flex; justify-content: space-between;">
                      <span style="color: #64748b;">Person / Client:</span>
                      <strong style="color: #0f172a;">${esc(res.client_name || 'Other Person')}</strong>
                    </div>
                    <div style="margin-bottom: 4px; display: flex; justify-content: space-between;">
                      <span style="color: #64748b;">Order No (PSONO):</span>
                      <strong style="color: #2563eb;">${esc(res.psono || '-')}</strong>
                    </div>
                    ${res.barcode ? `
                    <div style="display: flex; justify-content: space-between;">
                      <span style="color: #64748b;">Tray Barcode:</span>
                      <code style="color: #0f172a; font-weight: 700;">${esc(res.barcode)}</code>
                    </div>` : ''}
                  </div>
                  <div style="margin-top: 8px; font-size: 0.78rem; color: #991b1b; font-weight: 600;">
                    <i class="fa fa-info-circle mr-1"></i> Dispatched/allocated trays cannot be loaded. Removed from scanned list.
                  </div>
                </div>`,
              confirmButtonColor: '#dc2626',
              confirmButtonText: 'OK'
            });
          } else {
            // Restore grid and buttons so user can see and manually click Confirm / Update
            renderScannedGrid();
            if (currentAllocatedBedsDetail && currentAllocatedBedsDetail.length > 0) {
              renderBedVerificationChecklist();
            } else {
              $('#btnConfirmLoadAction').show();
            }
          }
          return;
        }

        // Successfully updated in database!
        playScanBeep();

        let newlyLoadedPlants = 0;
        pendingTrays.forEach(function (pt) {
          pt.current_status = 'Loaded';
          const p = (pt.is_bonus && (pt.bonus_plants || pt.qty_taken || pt.plants))
            ? Number(pt.bonus_plants || pt.qty_taken || pt.plants)
            : Number(pt.qtypertray || 126);
          newlyLoadedPlants += p;
        });

        // 1. Update active context counters
        currentActiveLoadedTrays += pendingTrays.length;
        currentActiveLoadedPlants += newlyLoadedPlants;
        currentActivePendingTrays = Math.max(0, currentActivePendingTrays - pendingTrays.length);
        currentActivePendingPlants = Math.max(0, currentActivePendingPlants - newlyLoadedPlants);

        $('#lblBatchLoaded').html(
          `<strong>${currentActiveLoadedTrays}</strong> Loaded (<strong class="text-primary">${Number(currentActiveLoadedPlants).toLocaleString()}</strong> Plants)`
        );
        $('#lblBatchPending').html(
          `<strong>${currentActivePendingTrays}</strong> Required Left (<strong class="text-amber-700">${Number(currentActivePendingPlants).toLocaleString()}</strong> Plants)`
        );

        // 2. Automatically update grid rows to Loaded status
        renderScannedGrid();

        // 3. Flash auto-saved status badge
        const $badge = $('#autoSaveStatusBadge');
        $badge.removeClass('pill-saving').addClass('pill-saved')
          .html(`<i class="fa fa-check-circle text-success mr-1"></i> ${pendingTrays.length} Tray(s) Updated to Loaded!`)
          .show();

        setTimeout(() => {
          $badge.fadeOut(400);
        }, 3500);

        // 4. Soft-refresh background order list so cards stay live
        loadOrdersList(false);
      },
      error: function (xhr, status, err) {
        isAutoCommitting = false;
        playWarningTone();
        $('#autoSaveStatusBadge').removeClass('pill-saving').addClass('bg-danger text-white border-0')
          .html('<i class="fa fa-exclamation-triangle mr-1"></i> Communication error')
          .show();
        setTimeout(() => { clearAutoCommit(true); }, 5000);

        // Restore buttons on communication failure
        renderScannedGrid();
        if (currentAllocatedBedsDetail && currentAllocatedBedsDetail.length > 0) {
          renderBedVerificationChecklist();
        } else {
          $('#btnConfirmLoadAction').show();
        }
      }
    });
  }

  function addTrayToGrid(tray) {
    if (!tray || !tray.barcode) return;
    const cleanBCode = $.trim(tray.barcode).toUpperCase();

    // Strict duplicate check before appending to array
    const alreadyExists = scannedTraysList.some(t => $.trim(t.barcode || '').toUpperCase() === cleanBCode);
    if (alreadyExists) {
      console.warn('Prevented duplicate tray insertion for barcode:', cleanBCode);
      return;
    }

    checkAndVerifyBedForTray(tray);
    scannedTraysList.push(tray);
    renderScannedGrid();

    // When row appended, start 5-second auto-commit timer if tray is not yet Loaded
    if (tray.current_status !== 'Loaded' && tray.current_status !== 'ReadyToDispatch') {
      scheduleAutoCommit();
    }
  }

  // Smart Row Action:
  // - If tray status is 'Loaded': prompt confirmation and call API to undo loaded status
  // - If tray status is NOT 'Loaded': simply remove from local queue
  window.handleRowAction = function (index) {
    if (index < 0 || index >= scannedTraysList.length) return;
    const tray = scannedTraysList[index];

    if (tray.current_status === 'Loaded' || tray.current_status === 'ReadyToDispatch') {
      // Prompt confirmation to undo loaded status from database
      Swal.fire({
        title: 'Undo Loaded Status?',
        html: `Tray <b>${esc(tray.barcode)}</b> is currently recorded as <b>Loaded</b>.<br><br>` +
          `Do you want to undo its load status and revert it back to <b>Ready to Sale</b>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '<i class="fa fa-undo mr-1"></i> Yes, Undo Load',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b'
      }).then((result) => {
        if (!result.isConfirmed) return;

        Swal.fire({
          title: 'Reverting Tray...',
          text: 'Updating database to revert tray status...',
          allowOutsideClick: false,
          allowEscapeKey: false,
          didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
          url: 'model/readytoload_model.php',
          type: 'POST',
          dataType: 'json',
          data: {
            action: 'undo_tray_load',
            barcode: tray.barcode,
            psono: currentActivePsono
          },
          success: function (res) {
            if (res.status === 'success') {
              playScanBeep();
              // Remove from scanned list
              scannedTraysList.splice(index, 1);

              // Unverify bed if removed tray was its verified barcode
              const removedBarcode = $.trim(tray.barcode || '').toUpperCase();
              if (currentAllocatedBedsDetail) {
                currentAllocatedBedsDetail.forEach(b => {
                  if ($.trim(b.verified_barcode || '').toUpperCase() === removedBarcode) {
                    const otherTray = scannedTraysList.find(t => doesTrayMatchBed(t, b));
                    if (otherTray) {
                      b.verified_barcode = otherTray.barcode;
                    } else {
                      b.is_verified = false;
                      b.verified_barcode = '';
                    }
                  }
                });
              }

              // Decrement active loaded counts
              const trayPlants = (tray.is_bonus && (tray.bonus_plants || tray.qty_taken || tray.plants))
                ? Number(tray.bonus_plants || tray.qty_taken || tray.plants)
                : Number(tray.qtypertray || 126);
              currentActiveLoadedTrays = Math.max(0, currentActiveLoadedTrays - 1);
              currentActiveLoadedPlants = Math.max(0, currentActiveLoadedPlants - trayPlants);
              currentActivePendingTrays += 1;
              currentActivePendingPlants += trayPlants;

              // Update modal header counters
              $('#lblBatchLoaded').html(
                `<strong>${currentActiveLoadedTrays}</strong> Loaded (<strong class="text-primary">${Number(currentActiveLoadedPlants).toLocaleString()}</strong> Plants)`
              );
              $('#lblBatchPending').html(
                `<strong>${currentActivePendingTrays}</strong> Required Left (<strong class="text-amber-700">${Number(currentActivePendingPlants).toLocaleString()}</strong> Plants)`
              );

              renderScannedGrid();

              Swal.fire({
                icon: 'success',
                title: 'Load Status Undone',
                text: res.message || 'Tray has been reverted successfully.',
                timer: 2000,
                showConfirmButton: false
              });

              // Soft refresh order list in background
              loadOrdersList(false);
            } else {
              playWarningTone();
              Swal.fire('Undo Failed', res.message || 'Could not undo tray load.', 'error');
            }
          },
          error: function (xhr, status, err) {
            playWarningTone();
            Swal.fire('Server Error', 'Failed to communicate with server: ' + err, 'error');
          }
        });
      });
    } else {
      // Unloaded tray: simply remove from client-side grid
      const removedBarcode = $.trim(tray.barcode || '').toUpperCase();
      if (currentAllocatedBedsDetail) {
        currentAllocatedBedsDetail.forEach(b => {
          if ($.trim(b.verified_barcode || '').toUpperCase() === removedBarcode) {
            const otherTray = scannedTraysList.find((t, i) => i !== index && doesTrayMatchBed(t, b));
            if (otherTray) {
              b.verified_barcode = otherTray.barcode;
            } else {
              b.is_verified = false;
              b.verified_barcode = '';
            }
          }
        });
      }

      scannedTraysList.splice(index, 1);
      renderScannedGrid();
      $('#inputBarcodeQuery').focus();

      // Check remaining pending trays for auto-save schedule
      const remainingPending = scannedTraysList.filter(t => t.current_status !== 'Loaded' && t.current_status !== 'ReadyToDispatch');
      if (remainingPending.length === 0) {
        clearAutoCommit(true);
      } else {
        scheduleAutoCommit();
      }
    }
  };

  // Alias removeScannedTray to handleRowAction so existing calls work seamlessly
  window.removeScannedTray = window.handleRowAction;

  window.clearAllScanned = function () {
    if (scannedTraysList.length === 0) return;
    Swal.fire({
      title: 'Clear Scanned Grid?',
      text: 'Remove all ' + scannedTraysList.length + ' scanned trays from this batch?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Yes, Clear All',
      confirmButtonColor: '#dc2626'
    }).then((res) => {
      if (res.isConfirmed) {
        clearAutoCommit(true);
        if (currentAllocatedBedsDetail) {
          currentAllocatedBedsDetail.forEach(b => {
            b.is_verified = false;
            b.verified_barcode = '';
          });
        }
        scannedTraysList = [];
        renderScannedGrid();
        $('#inputBarcodeQuery').focus();
      }
    });
  };

  // Helper to load existing loaded trays silently on modal open
  function loadExistingLoadedTraysSilently() {
    if (!currentActivePsono) return;
    $.ajax({
      url: 'model/readytoload_model.php',
      type: 'GET',
      dataType: 'json',
      data: {
        action: 'get_item_loaded_trays',
        psono: currentActivePsono,
        product_name: currentActiveProduct,
        clientid: currentActiveClientId,
        stockorder_no: currentActiveStockorder
      },
      success: function (res) {
        if (res.status === 'success' && res.trays && res.trays.length > 0) {
          res.trays.forEach(function (t) {
            const tBarcode = $.trim(t.barcode || '').toUpperCase();
            if (!tBarcode) return;
            const exists = scannedTraysList.some(item => $.trim(item.barcode || '').toUpperCase() === tBarcode);
            if (!exists) {
              checkAndVerifyBedForTray(t);
              scannedTraysList.push(t);
            }
          });
          renderScannedGrid();
          renderBedVerificationChecklist();
        }
      }
    });
  }

  // Helper to load existing loaded trays into grid for inspection / undo
  window.loadExistingLoadedTrays = function () {
    if (!currentActivePsono) return;

    Swal.fire({
      title: 'Fetching Loaded Trays...',
      text: 'Retrieving trays already loaded for this order...',
      allowOutsideClick: false,
      didOpen: () => { Swal.showLoading(); }
    });

    $.ajax({
      url: 'model/readytoload_model.php',
      type: 'GET',
      dataType: 'json',
      data: {
        action: 'get_item_loaded_trays',
        psono: currentActivePsono,
        product_name: currentActiveProduct,
        clientid: currentActiveClientId,
        stockorder_no: currentActiveStockorder
      },
      success: function (res) {
        Swal.close();
        if (res.status === 'success' && res.trays && res.trays.length > 0) {
          let addedCount = 0;
          res.trays.forEach(function (t) {
            const tBarcode = $.trim(t.barcode || '').toUpperCase();
            if (!tBarcode) return;
            const exists = scannedTraysList.some(item => $.trim(item.barcode || '').toUpperCase() === tBarcode);
            if (!exists) {
              scannedTraysList.push(t);
              addedCount++;
            }
          });
          renderScannedGrid();
          Swal.fire({
            icon: 'success',
            title: 'Loaded Trays Added',
            text: `Added ${addedCount} loaded tray(s) into the grid for inspection or undo.`,
            timer: 2000,
            showConfirmButton: false
          });
        } else {
          Swal.fire('No Loaded Trays', 'No loaded trays found for this order item.', 'info');
        }
      },
      error: function (xhr, status, err) {
        Swal.fire('Error', 'Failed to retrieve loaded trays: ' + err, 'error');
      }
    });
  };

  // -------------------------------------------------------------
  // 5. Render Scanned Grid (Matching Screenshot)
  // -------------------------------------------------------------
  function renderScannedGrid() {
    const count = scannedTraysList.length;
    let totalPlants = 0;

    scannedTraysList.forEach(t => {
      const p = (t.is_bonus && (t.bonus_plants || t.qty_taken || t.plants))
        ? Number(t.bonus_plants || t.qty_taken || t.plants)
        : Number(t.qtypertray || 126);
      totalPlants += p;
    });

    // Update Live Stats Badges (Pills)
    $('#lblCountScannedTrays').text(count);
    $('#lblCountScannedPlants').text(Number(totalPlants).toLocaleString());

    // Render dynamic high-visibility badge for Scanned Trays Grid counter
    const isTargetMet = (currentTargetRequiredTrays > 0 && count >= currentTargetRequiredTrays);
    const counterBg = isTargetMet ? '#ecfdf5' : (count > 0 ? '#eff6ff' : '#f8fafc');
    const counterBorder = isTargetMet ? '#a7f3d0' : (count > 0 ? '#bfdbfe' : '#e2e8f0');
    const numColor = isTargetMet ? '#047857' : (count > 0 ? '#1d4ed8' : '#475569');
    const textColor = isTargetMet ? '#065f46' : (count > 0 ? '#1e40af' : '#64748b');
    const iconColor = isTargetMet ? '#10b981' : (count > 0 ? '#3b82f6' : '#94a3b8');

    $('#lblGridCounter')
      .css({
        'display': 'inline-flex',
        'align-items': 'center',
        'gap': '6px',
        'background': counterBg,
        'border': `1.5px solid ${counterBorder}`,
        'padding': '6px 14px',
        'border-radius': '20px',
        'box-shadow': count > 0 ? '0 2px 4px rgba(0,0,0,0.06)' : 'none'
      })
      .html(`
        <i class="fa fa-layer-group" style="color: ${iconColor}; font-size: 1.05rem;"></i>
        <strong style="font-size: 1.25rem; font-weight: 800; color: ${numColor}; line-height: 1;">${count}</strong>
        <span style="font-size: 0.95rem; font-weight: 700; color: ${textColor};">tray${count === 1 ? '' : 's'} scanned</span>
      `);

    // Identify pending (unloaded) trays in scanned grid
    const pendingToLoad = scannedTraysList.filter(t => t.current_status !== 'Loaded' && t.current_status !== 'ReadyToDispatch');
    const pendingPlants = pendingToLoad.reduce((acc, t) => {
      const p = (t.is_bonus && (t.bonus_plants || t.qty_taken || t.plants))
        ? Number(t.bonus_plants || t.qty_taken || t.plants)
        : Number(t.qtypertray || 126);
      return acc + p;
    }, 0);

    // Dynamic Required Left calculation: subtract only uncommitted/pending scans
    const remainingTraysAfterBatch = Math.max(0, currentActivePendingTrays - pendingToLoad.length);
    const remainingPlantsAfterBatch = Math.max(0, currentActivePendingPlants - pendingPlants);

    $('#lblBatchPendingTag').hide();
    $('#pillRemainingToLoad').hide();

    // Disable scanner input when all required pending trays are scanned
    if (remainingTraysAfterBatch <= 0 && currentTargetRequiredTrays > 0) {
      $('#inputBarcodeQuery').prop('disabled', true).addClass('scan-input-completed');
      $('#inputBarcodeQuery').attr('placeholder', '✔ All required trays scanned (Limit reached)');
      $('#btnAddTrayAction').prop('disabled', true).html('<i class="fa fa-check-circle"></i> <span>Completed</span>');
    } else if (!isScanProcessing) {
      $('#inputBarcodeQuery').prop('disabled', false).removeClass('scan-input-completed');
      $('#inputBarcodeQuery').attr('placeholder', 'Scan or enter Tray Barcode');
      $('#btnAddTrayAction').prop('disabled', false).html('<i class="fa fa-plus-circle"></i> <span>Add Tray</span>');
    }

    // Render Bed Verification Checklist & dynamically toggle modal footer button
    if (currentAllocatedBedsDetail && currentAllocatedBedsDetail.length > 0) {
      renderBedVerificationChecklist();
    } else {
      if (isAutoCommitting) {
        $('#btnConfirmLoadAction').hide();
        $('#btnModalUpdateStatusAction').hide();
      } else {
        const allGridTraysLoaded = (count > 0 && pendingToLoad.length === 0);
        const isOrderFullyLoaded = (currentActiveLoadedTrays >= currentTargetRequiredTrays && currentTargetRequiredTrays > 0) || (currentActivePendingTrays <= 0 && currentActiveLoadedTrays > 0) || (currentActiveLoadedTrays >= 1);

        if (allGridTraysLoaded || (isOrderFullyLoaded && pendingToLoad.length === 0)) {
          $('#btnConfirmLoadAction').hide();
          $('#btnModalUpdateStatusAction').show();
        } else {
          $('#btnModalUpdateStatusAction').hide();
          $('#btnConfirmLoadAction').show().prop('disabled', count === 0 || pendingToLoad.length === 0);
          if (count > 0 && pendingToLoad.length > 0) {
            $('#btnConfirmLoadAction').html('<span class="d-none d-sm-inline"><i class="fa fa-truck-fast mr-1"></i> Confirm & Update status (1-Tray Verified)</span><span class="d-inline d-sm-none"><i class="fa fa-truck-fast mr-1"></i> Update status</span>');
          }
        }
      }
    }

    const $tbody = $('#tblScannedTraysBody');
    const $tableContainer = $('#traysTableContainer');
    const $cardsContainer = $('#traysMobileCardsContainer');
    const $emptyBox = $('#emptyGridPlaceholder');

    if (count === 0) {
      $tbody.html('');
      $cardsContainer.html('');
      $tableContainer.hide();
      $cardsContainer.hide();
      $emptyBox.show();
      return;
    }

    $emptyBox.hide();
    $tableContainer.show();
    $cardsContainer.show();

    let html = '';
    let cardsHtml = '';
    scannedTraysList.forEach((t, idx) => {
      const barcode = t.barcode || '-';
      const order = t.stockorder_no || t.psono || currentActivePsono || '-';
      const product = (t.productname || currentActiveProduct) + (t.variety ? `, ${t.variety}` : '');
      const bed = t.bed_no || '-';
      const polyhouse = t.polyhousename || 'Polyhouse';
      const exactPlants = (t.is_bonus && (t.bonus_plants || t.qty_taken || t.plants))
        ? Number(t.bonus_plants || t.qty_taken || t.plants)
        : Number(t.qtypertray || 126);
      const plants = exactPlants.toLocaleString() + ' Plants';
      const status = t.current_status || 'Polyhouse';
      const isTrayLoaded = (status === 'Loaded' || status === 'ReadyToDispatch');

      let badgesHtml = '';
      if (t.is_bonus) {
        badgesHtml += `<span class="badge" style="font-size:10px; background:#dcfce7; color:#15803d; border:1px solid #86efac; padding:2px 6px; border-radius:4px; margin-left:5px;"><i class="fa fa-gift mr-1"></i>Free Bonus: ${exactPlants} Plants</span>`;
      }
      if (t.is_swapped) {
        const farmerTip = t.origin_client_name ? ` (Farmer: ${esc(t.origin_client_name)})` : '';
        badgesHtml += `<span class="badge" style="font-size:10px; background:#fef3c7; color:#92400e; border:1px solid #f59e0b; padding:2px 6px; border-radius:4px; margin-left:5px;" title="Loaded from Batch ${esc(t.origin_stockorderno || '-')}${farmerTip}"><i class="fa fa-boxes mr-1"></i>Other Batch: ${esc(t.origin_stockorderno || '-')}</span>`;
      }
      if (t.is_approved_variety_change) {
        badgesHtml += `<span class="badge" style="font-size:10px; background:#dcfce7; color:#166534; border:1px solid #22c55e; padding:2px 6px; border-radius:4px; margin-left:5px;" title="Approved Variety: ${esc(t.approval_request_no || 'Approved')}"><i class="fa fa-check-double mr-1"></i>Approved Variety</span>`;
      }

      const statusBadge = isTrayLoaded
        ? `<span class="tray-status-badge badge-status-loaded"><i class="fa fa-check-circle mr-1"></i>Loaded</span>`
        : `<span class="tray-status-badge">${esc(status)}</span>`;

      const removeTitle = isTrayLoaded ? 'Undo Loaded Status & Remove' : 'Remove Tray from Batch';

      // Desktop Table Row
      html += `
        <tr class="${isTrayLoaded ? 'row-loaded-tray' : ''}">
          <td class="text-muted font-weight-bold">${idx + 1}</td>
          <td><span class="tray-barcode-badge">${esc(barcode)}</span></td>
          <td><span class="order-link-blue">${esc(order)}</span></td>
          <td class="font-weight-bold">${esc(product)} ${badgesHtml}</td>
          <td>
            <span class="bed-dark-pill">${esc(bed)}</span>
            <span class="polyhouse-subtext">${esc(polyhouse)}</span>
          </td>
          <td class="font-weight-bold ${t.is_bonus ? 'text-success' : ''}" title="${t.is_bonus ? 'Free Bonus: ' + exactPlants + ' Plants (from ' + (t.qtypertray || 126) + ' capacity tray)' : ''}">${esc(plants)}</td>
          <td>${statusBadge}</td>
          <td class="text-center">
            <button type="button" class="btn-remove-tray ${isTrayLoaded ? 'btn-undo-loaded' : ''}" onclick="handleRowAction(${idx})" title="${removeTitle}">
              <i class="fa fa-times"></i>
            </button>
          </td>
        </tr>
      `;

      // Mobile App Preview Card (Touch-friendly, no horizontal scrolling)
      cardsHtml += `
        <div class="tray-mobile-card ${isTrayLoaded ? 'card-loaded' : ''}">
          <div class="tray-card-header">
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <span class="tray-card-idx">#${idx + 1}</span>
              <span class="tray-barcode-badge"><i class="fa fa-barcode mr-1"></i>${esc(barcode)}</span>
              ${badgesHtml}
            </div>
            <button type="button" class="btn-remove-tray ${isTrayLoaded ? 'btn-undo-loaded' : ''}" onclick="handleRowAction(${idx})" title="${removeTitle}">
              <i class="fa fa-times"></i>
            </button>
          </div>
          <div class="tray-card-title">${esc(product)}</div>
          <div class="tray-card-chips">
            <span class="tray-chip-loc"><i class="fa fa-warehouse mr-1 text-muted"></i>${esc(polyhouse)}</span>
            <span class="bed-dark-pill">${esc(bed)}</span>
            <span class="tray-chip-plants ${t.is_bonus ? 'text-success' : ''}"><i class="fa fa-seedling mr-1"></i>${esc(plants)}</span>
          </div>
          <div class="tray-card-footer">
            <span class="tray-card-order-tag"><i class="fa fa-file-invoice mr-1 text-primary"></i>${esc(order)}</span>
            <div>${statusBadge}</div>
          </div>
        </div>
      `;
    });

    $tbody.html(html);
    $cardsContainer.html(cardsHtml);
  }

  // -------------------------------------------------------------
  // 6. Camera Scanner (Html5Qrcode)
  // -------------------------------------------------------------
  async function startCamera() {
    if (isCameraScanning) return;
    if (typeof Html5Qrcode === 'undefined') {
      Swal.fire('Error', 'QR Scanner library not loaded. Check internet connection.', 'error');
      return;
    }

    $('#cameraStatusMsg').html('<i class="fa fa-spinner fa-spin mr-1"></i> Starting camera viewfinder...');
    try {
      if (html5QrCode) {
        try {
          if (html5QrCode.isScanning) await html5QrCode.stop();
          html5QrCode.clear();
        } catch (e) { }
        html5QrCode = null;
      }

      html5QrCode = new Html5Qrcode("qrCameraViewport");
      const scanConfig = {
        fps: 15,
        qrbox: { width: 260, height: 260 },
        aspectRatio: 1.333333
      };

      await html5QrCode.start(
        { facingMode: "environment" },
        scanConfig,
        (decodedText) => {
          if (isScanProcessing) return;
          const clean = decodedText.replace(/[\x00-\x1F\x7F\xC2\xA0]/g, '').trim();
          if (!clean) return;
          const cleanUpper = clean.toUpperCase();
          if (inFlightScans.has(cleanUpper)) return;
          const alreadyInGrid = scannedTraysList.some(t => $.trim(t.barcode || '').toUpperCase() === cleanUpper);
          if (alreadyInGrid) return;
          playScanBeep();
          lookupAndAddTray(clean);
        },
        () => { }
      );

      isCameraScanning = true;
      $('#btnToggleCamera').html('<i class="fa fa-stop mr-1"></i> Stop Camera').removeClass('btn-primary').addClass('btn-danger');
      $('#cameraStatusMsg').html('<span class="text-success"><i class="fa fa-check-circle mr-1"></i> Camera active. Aim at Tray Barcode tag.</span>');
    } catch (err) {
      isCameraScanning = false;
      $('#cameraStatusMsg').html('<span class="text-danger"><i class="fa fa-exclamation-circle mr-1"></i> Camera access denied or not available. Please use Manual / Gun tab.</span>');
    }
  }

  async function stopCamera() {
    if (html5QrCode && isCameraScanning) {
      try {
        await html5QrCode.stop();
        html5QrCode.clear();
      } catch (e) { }
      isCameraScanning = false;
      $('#btnToggleCamera').html('<i class="fa fa-play mr-1"></i> Start Camera').removeClass('btn-danger').addClass('btn-primary');
      $('#cameraStatusMsg').html('<i class="fa fa-info-circle mr-1"></i> Camera stopped.');
    }
  }

  window.toggleCamera = function () {
    if (isCameraScanning) {
      stopCamera();
    } else {
      startCamera();
    }
  };

  // -------------------------------------------------------------
  // 7. Modal Update Status Action (When all trays are loaded)
  // -------------------------------------------------------------
  window.triggerModalUpdateStatus = function () {
    if (!currentActivePsono) {
      Swal.fire('Error', 'Provisional Order Number is missing.', 'error');
      return;
    }
    clearAutoCommit(true);
    closeScannerModal();
    window.confirmFinalDispatch(
      currentActivePsono,
      currentActiveLoadedTrays,
      currentTargetRequiredTrays,
      currentActiveLoadedPlants,
      currentTargetRequiredPlants
    );
  };

  // -------------------------------------------------------------
  // 7.1 Execute Confirm & Update Load
  // -------------------------------------------------------------
  window.submitLoadedTrays = function () {
    if (isAutoCommitting) return;
    clearAutoCommit(true);
    if (scannedTraysList.length === 0) {
      Swal.fire('No Trays Scanned', 'Please scan at least one plant tray barcode first.', 'warning');
      return;
    }

    // STRICT CHECK: Ensure 1 tray is scanned and verified for EACH allocated order ID and bed!
    if (currentAllocatedBedsDetail && currentAllocatedBedsDetail.length > 0) {
      // Re-sync verification state from scannedTraysList to guarantee consistency
      currentAllocatedBedsDetail.forEach(b => {
        const matchingScanned = scannedTraysList.find(t => doesTrayMatchBed(t, b));
        if (matchingScanned) {
          b.is_verified = true;
          if (!b.verified_barcode) b.verified_barcode = matchingScanned.barcode;
        }
      });

      const pendingBeds = currentAllocatedBedsDetail.filter(b => !b.is_verified);
      if (pendingBeds.length > 0) {
        playWarningTone();
        Swal.fire({
          icon: 'warning',
          title: 'Bed Verification Incomplete',
          html: `
            <div style="text-align:left; font-size:0.9rem;">
              <p>You must scan at least <b>1 verification tray for EACH allocated order ID and bed</b> before confirming.</p>
              <div style="background:#fff1f2; border:1.5px solid #fecdd3; border-radius:8px; padding:10px 12px; margin-bottom:10px; color:#9f1239;">
                <div style="font-weight:700; margin-bottom:4px;"><i class="fa fa-triangle-exclamation mr-1"></i> Pending Beds (${pendingBeds.length} left):</div>
                ${pendingBeds.map(b => `• <b>${esc(b.polyhouse_name || 'PH')} - ${esc(b.bed_name || 'Bed')}</b> (${Number(b.plants || 0).toLocaleString()} Plants, Batch: <code>${esc(b.stockorder_no)}</code>)`).join('<br>')}
              </div>
              <p class="text-muted mb-0" style="font-size:0.82rem;">
                <i class="fa fa-info-circle text-primary mr-1"></i> Please scan 1 tray from each pending bed/batch to complete verification.
              </p>
            </div>
          `,
          confirmButtonColor: '#4f46e5',
          confirmButtonText: 'Understood'
        });
        return;
      }
    }

    recalcBonusPlants();
    // STRICT CHECK: Ensure Free Bonus Plants are verified if required!
    if (currentBonusPlantsRequired > 0 && currentBonusPlantsVerified < currentBonusPlantsRequired) {
      const remBonus = currentBonusPlantsRequired - currentBonusPlantsVerified;
      playWarningTone();
      Swal.fire({
        icon: 'warning',
        title: 'Free Bonus Verification Incomplete',
        html: `
          <div style="text-align:left; font-size:0.9rem;">
            <p>Order requires <b>${currentBonusPlantsRequired} Free Bonus plants</b>, but only <b>${currentBonusPlantsVerified} plants</b> have been verified.</p>
            <div style="background:#fffbeb; border:1.5px solid #fcd34d; border-radius:8px; padding:10px 12px; margin-bottom:10px; color:#92400e;">
              <div style="font-weight:700; margin-bottom:4px;"><i class="fa fa-gift mr-1"></i> Bonus Plants Still Needed: <b>${remBonus} Plants</b></div>
              Please switch to <b>Free Bonus Tray mode</b> and scan the tray to allocate the remaining ${remBonus} bonus plants.
            </div>
          </div>
        `,
        confirmButtonColor: '#d97706',
        confirmButtonText: 'Understood'
      });
      return;
    }

    // Separate pending trays from already loaded trays
    const pendingTrays = scannedTraysList.filter(t => t.current_status !== 'Loaded' && t.current_status !== 'ReadyToDispatch');
    const alreadyLoadedTrays = scannedTraysList.filter(t => t.current_status === 'Loaded' || t.current_status === 'ReadyToDispatch');

    if (pendingTrays.length === 0 && alreadyLoadedTrays.length > 0) {
      window.triggerModalUpdateStatus();
      return;
    }

    const totalPlants = pendingTrays.reduce((acc, t) => {
      const p = (t.is_bonus && (t.bonus_plants || t.qty_taken || t.plants))
        ? Number(t.bonus_plants || t.qty_taken || t.plants)
        : Number(t.qtypertray || 126);
      return acc + p;
    }, 0);
    const trayPayload = pendingTrays.map(t => ({
      barcode: t.barcode,
      is_swapped: !!t.is_swapped,
      origin_stockorderno: t.origin_stockorderno || '',
      origin_psono: t.origin_psono || '',
      is_approved_variety_change: !!t.is_approved_variety_change,
      approval_request_no: t.approval_request_no || ''
    }));

    let bedsVerifiedInfo = '';
    if (currentAllocatedBedsDetail && currentAllocatedBedsDetail.length > 0) {
      bedsVerifiedInfo = `
        <div class="mb-2 p-2 rounded" style="background:#f0fdf4; border:1px solid #bbf7d0;">
          <div style="font-size:0.82rem; font-weight:700; color:#166534; margin-bottom:3px;">
            <i class="fa fa-check-circle mr-1"></i> Verified Beds (${currentAllocatedBedsDetail.length}):
          </div>
          <div style="font-size:0.78rem; color:#15803d; line-height: 1.5;">
            ${currentAllocatedBedsDetail.map(b => `• <b>${esc(b.polyhouse_name || 'PH')} - ${esc(b.bed_name || 'Bed')}</b> (${Number(b.plants || 0).toLocaleString()} Plants, Batch: ${esc(b.stockorder_no)}) ${b.verified_barcode ? `<code>${esc(b.verified_barcode)}</code>` : ''}`).join('<br>')}
          </div>
        </div>
      `;
    }

    let infoHtml = `<div class="mb-2">Total Trays to Load: <b class="text-primary">${pendingTrays.length} Trays</b></div>`;
    if (alreadyLoadedTrays.length > 0) {
      infoHtml += `<div class="mb-2 text-muted" style="font-size:0.83rem;"><i class="fa fa-info-circle mr-1 text-primary"></i>Skipping ${alreadyLoadedTrays.length} already loaded tray(s).</div>`;
    }

    Swal.fire({
      title: 'Confirm Loading?',
      html: `
        <div class="text-start p-2" style="font-size:0.92rem;">
          <div class="mb-2">Order: <b>${esc(currentActivePsono)}</b></div>
          <div class="mb-2">Crop: <b>${esc(currentActiveProduct)}</b></div>
          ${bedsVerifiedInfo}
          ${infoHtml}
          <div class="mb-3">Total Plants: <b class="text-success">${Number(totalPlants).toLocaleString()} Plants</b></div>
          <div class="form-group mb-2">
            <label class="font-weight-bold" style="font-size:0.82rem;">Vehicle Number (Optional):</label>
            <input type="text" id="swalVehicleNo" class="form-control" placeholder="e.g. MH-12-AB-1234">
          </div>
          <div class="form-group mb-0">
            <label class="font-weight-bold" style="font-size:0.82rem;">Dispatch / Loading Notes (Optional):</label>
            <input type="text" id="swalNotes" class="form-control" placeholder="e.g. Loaded in good condition">
          </div>
        </div>
      `,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: '<i class="fa fa-check-circle mr-1"></i> Update & Confirm Load',
      confirmButtonColor: '#059669',
      cancelButtonText: 'Cancel',
      focusConfirm: false,
      preConfirm: () => {
        return {
          vehicle_no: $('#swalVehicleNo').val(),
          notes: $('#swalNotes').val()
        };
      }
    }).then((result) => {
      if (!result.isConfirmed) return;

      const vehicleNo = result.value.vehicle_no || '';
      const notes = result.value.notes || '';

      const $btn = $('#btnConfirmLoadAction');
      const origHtml = $btn.html();
      $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i> Updating Server...');

      $.ajax({
        url: 'model/readytoload_model.php',
        type: 'POST',
        dataType: 'json',
        data: {
          action: 'confirm_load',
          psono: currentActivePsono,
          invoiceno: currentActiveInvoiceNo,
          item_id: currentActiveItemId,
          product_id: currentActiveProductId,
          product_name: currentActiveProduct,
          single_tray_verification: 1,
          trays: JSON.stringify(trayPayload),
          bonus_trays: JSON.stringify(currentBonusTraysList),
          bonus_plants: currentBonusPlantsVerified,
          vehicle_no: vehicleNo,
          loading_notes: notes
        },
        success: function (res) {
          $btn.prop('disabled', false).html(origHtml);

          if (res.status !== 'success') {
            const isDispatchedOrAllocated = (res.error_type === 'tray_dispatched' || res.error_type === 'tray_allocated_to_other');
            if (isDispatchedOrAllocated) {
              const clientName = res.client_name || 'Other Person';
              const orderNo = res.psono || '-';
              const trayBc = res.barcode || '';
              Swal.fire({
                icon: 'error',
                title: res.error_type === 'tray_dispatched' ? 'Tray Already Dispatched!' : 'Tray Already Allocated!',
                html: `
                  <div style="text-align: left; background: #fff1f2; border: 1.5px solid #fecdd3; border-radius: 10px; padding: 14px; color: #9f1239; font-size: 0.88rem; line-height: 1.6;">
                    <div style="font-weight: 800; font-size: 1rem; margin-bottom: 6px; color: #be123c; display: flex; align-items: center; gap: 6px;">
                      <i class="fa fa-circle-xmark" style="font-size: 1.15rem;"></i>
                      <span>Cannot Save Loaded Trays</span>
                    </div>
                    <div style="color: #334155; margin-bottom: 10px; font-weight: 500;">${esc(res.message)}</div>
                    <div style="background: #ffffff; border: 1px solid #fed7aa; border-radius: 8px; padding: 10px 12px; font-size: 0.83rem; color: #475569;">
                      <div style="margin-bottom: 4px; display: flex; justify-content: space-between;">
                        <span style="color: #64748b;">Person / Client:</span>
                        <strong style="color: #0f172a;">${esc(clientName)}</strong>
                      </div>
                      <div style="margin-bottom: 4px; display: flex; justify-content: space-between;">
                        <span style="color: #64748b;">Order No (PSONO):</span>
                        <strong style="color: #2563eb;">${esc(orderNo)}</strong>
                      </div>
                      ${trayBc ? `
                      <div style="display: flex; justify-content: space-between;">
                        <span style="color: #64748b;">Tray Barcode:</span>
                        <code style="color: #0f172a; font-weight: 700;">${esc(trayBc)}</code>
                      </div>` : ''}
                    </div>
                    <div style="margin-top: 8px; font-size: 0.78rem; color: #991b1b; font-weight: 600;">
                      <i class="fa fa-info-circle mr-1"></i> Dispatched/allocated trays cannot be loaded again.
                    </div>
                  </div>`,
                confirmButtonColor: '#dc2626',
                confirmButtonText: 'OK'
              });
            } else {
              Swal.fire('Error', res.message || 'Failed to update trays.', 'error');
            }
            return;
          }

          const psoToUpdate = currentActivePsono;
          const itemToUpdate = currentActiveItemId;
          const invToUpdate = currentActiveInvoiceNo;
          closeScannerModal();
          playScanBeep();

          Swal.fire({
            icon: 'success',
            title: 'Trays Loaded Successfully!',
            html: `
              <p>${esc(res.message)}</p>
              <div class="badge bg-light text-dark border p-2 mb-2 font-mono">Batch ID: ${esc(res.batch_id)}</div>
            `,
            showCancelButton: true,
            confirmButtonText: '<i class="fa fa-truck-fast mr-1"></i> Update Status to Dispatched',
            cancelButtonText: 'Done / View List',
            confirmButtonColor: '#059669',
            cancelButtonColor: '#64748b'
          }).then((sRes) => {
            loadOrdersList(false);
            if (sRes.isConfirmed && psoToUpdate) {
              handleUpdateStatusClick(psoToUpdate, itemToUpdate, invToUpdate);
            }
          });
        },
        error: function (xhr, status, err) {
          $btn.prop('disabled', false).html(origHtml);
          Swal.fire('Error', 'Server update failed: ' + err, 'error');
        }
      });
    });
  };

  // -------------------------------------------------------------
  // 7.5 Loaded Trays Management & Undo Modal (Single Tray & All Trays)
  // -------------------------------------------------------------
  let currentUndoItem = null;
  let currentUndoLoadedTrays = [];

  window.openLoadedTraysModal = function (itemData) {
    if (typeof itemData === 'string' && window.rtlItemRegistry && window.rtlItemRegistry[itemData]) {
      itemData = window.rtlItemRegistry[itemData];
    }
    if (!itemData) return;
    if (itemData.psono) {
      $('.order-box').removeClass('active-order-box');
      $(`#orderBox_${esc(itemData.psono)}`).addClass('active-order-box');
    }
    const isRoleAdmin = (typeof window.loginRoleId !== 'undefined' && Number(window.loginRoleId) < 2);
    if (!isRoleAdmin) {
      if (window.Swal) {
        Swal.fire('Access Denied', 'Only Administrator can view loaded trays details or undo loading.', 'warning');
      }
      return;
    }

    currentUndoItem = itemData;
    currentUndoLoadedTrays = [];

    // Set Context Header
    $('#lblUndoModalProduct').text(itemData.product_name || '-');
    $('#lblUndoModalPsono').text(itemData.psono || '-');
    $('#lblUndoModalClient').text(itemData.client_name || '-');
    $('#lblUndoModalRts').text((itemData.ready_to_sell_trays || 0) + ' RTS Trays');

    const isShifted = itemData.isshifted === 'yes' || itemData.is_shifted || (itemData.shifted_plants && itemData.shifted_plants > 0);
    if (isShifted) {
      $('#lblUndoModalShift').text('Shifted (125 Plants/Tray)');
      $('#lblUndoModalShiftTag').show();
    } else {
      $('#lblUndoModalShiftTag').hide();
    }

    // Dispatched tag in undo modal
    const undoDispTrays = Number(itemData.dispatched_trays || 0);
    const undoDispPlants = Number(itemData.dispatched_plants || 0);
    const undoPriorDispTrays = Number(itemData.prior_dispatched_trays || 0);
    const undoPriorDispPlants = Number(itemData.prior_dispatched_plants || 0);
    if (undoDispTrays > 0 || undoDispPlants > 0) {
      $('#lblUndoModalDisp').html(`Dispatched: <strong>${undoDispTrays}</strong> Trays (<strong class="text-success">${Number(undoDispPlants).toLocaleString()}</strong> Plants)`);
      $('#lblUndoModalDispTag').show();
    } else if (undoPriorDispTrays > 0 || undoPriorDispPlants > 0) {
      $('#lblUndoModalDisp').html(`Prior Dispatched: <strong>${undoPriorDispTrays}</strong> Trays (<strong class="text-success">${Number(undoPriorDispPlants).toLocaleString()}</strong> Plants)`);
      $('#lblUndoModalDispTag').show();
    } else {
      $('#lblUndoModalDispTag').hide();
    }

    $('#lblUndoModalLoadedTrays').text(itemData.loaded_trays || 0);
    $('#lblUndoModalLoadedPlants').text(Number(itemData.loaded_plants || 0).toLocaleString());

    const colCount = isRoleAdmin ? 8 : 7;

    // Show loading state in grid
    $('#undoModalEmptyPlaceholder').hide();
    $('#undoModalTableContainer').show();
    $('#lblUndoGridCounter').text('Loading trays...');
    $('#tblUndoModalTraysBody').html(`
      <tr>
        <td colspan="${colCount}" class="text-center py-4 text-muted">
          <i class="fa fa-spinner fa-spin fa-2x mb-2 text-primary"></i><br>
          Retrieving loaded trays from server...
        </td>
      </tr>
    `);

    // Lock background scroll & show modal
    $('body, html').addClass('modal-open-lock');
    $('#loadedTraysModalOverlay').addClass('active');

    // Fetch loaded trays via AJAX
    $.ajax({
      url: 'model/readytoload_model.php',
      type: 'GET',
      dataType: 'json',
      data: {
        action: 'get_item_loaded_trays',
        psono: itemData.psono,
        stockorder_no: itemData.allocated_stockorder_no || itemData.stockorder_no || '',
        product_name: itemData.product_name || '',
        product_id: itemData.product_id ? String(itemData.product_id).trim() : '',
        item_id: itemData.item_id || itemData.id || 0,
        clientid: itemData.clientid || ''
      },
      success: function (res) {
        if (res.status === 'success') {
          currentUndoLoadedTrays = res.trays || [];
          renderLoadedTraysModalGrid();
        } else {
          Swal.fire('Error', res.message || 'Failed to load trays.', 'error');
          $('#tblUndoModalTraysBody').html(`
            <tr>
              <td colspan="${colCount}" class="text-center py-4 text-danger">
                <i class="fa fa-exclamation-triangle fa-2x mb-2"></i><br>
                ${esc(res.message || 'Failed to fetch loaded trays')}
              </td>
            </tr>
          `);
        }
      },
      error: function (xhr, status, err) {
        Swal.fire('Error', 'Communication error: ' + err, 'error');
        $('#tblUndoModalTraysBody').html(`
          <tr>
            <td colspan="${colCount}" class="text-center py-4 text-danger">
              <i class="fa fa-exclamation-triangle fa-2x mb-2"></i><br>
              Server communication error
            </td>
          </tr>
        `);
      }
    });
  };

  function renderLoadedTraysModalGrid() {
    const isRoleAdmin = (typeof window.loginRoleId !== 'undefined' && Number(window.loginRoleId) < 2);
    const trays = currentUndoLoadedTrays || [];
    const count = trays.length;
    let totalPlants = 0;

    const isShifted = currentUndoItem && (currentUndoItem.isshifted === 'yes' || currentUndoItem.is_shifted || (currentUndoItem.shifted_plants && currentUndoItem.shifted_plants > 0));

    trays.forEach(t => {
      totalPlants += Number(t.qtypertray || 126);
    });

    $('#lblUndoModalLoadedTrays').text(count);
    $('#lblUndoModalLoadedPlants').text(Number(totalPlants).toLocaleString());
    $('#lblUndoGridCounter').text(count + ' tray' + (count === 1 ? '' : 's') + ' loaded');

    if (count === 0) {
      $('#undoModalTableContainer').hide();
      $('#undoModalEmptyPlaceholder').show();
      $('#btnModalUndoAllTrays').prop('disabled', true);
      return;
    }

    $('#undoModalEmptyPlaceholder').hide();
    $('#undoModalTableContainer').show();
    $('#btnModalUndoAllTrays').prop('disabled', false);

    const rowsHtml = trays.map((tray, idx) => {
      const barcode = tray.barcode || '-';
      const loc = `${tray.polyhousename || 'PH'} • ${tray.bed_no || 'Bed'}`;
      const plants = tray.qtypertray || 126;
      const loadedInfo = `${tray.dispatchat || '-'} <br><small class="text-muted">by ${esc(tray.dispatchby || 'Admin')}</small>`;

      // Determine Updated Table Badge (krf_polyhouse_plant_details vs krf_readytosale_stocks vs both)
      let sourceBadge = '';
      if (tray.source_table === 'both') {
        sourceBadge = `
          <span class="badge border px-2 py-1 text-start" style="font-size:0.75rem; background-color:#f5f3ff; color:#6d28d9; border-color:#ddd6fe;" title="Updated in krf_polyhouse_plant_details AND krf_readytosale_stocks">
            <i class="fa fa-layer-group mr-1"></i> Both Tables
            <div style="font-size:0.68rem; color:#7c3aed;">polyhouse & ready-to-sale</div>
          </span>`;
      } else if (tray.source_table === 'krf_readytosale_stocks') {
        sourceBadge = `
          <span class="badge border px-2 py-1" style="font-size:0.75rem; background-color:#fffbeb; color:#b45309; border-color:#fde68a;" title="Updated in krf_readytosale_stocks">
            <i class="fa fa-boxes-stacked mr-1"></i> krf_readytosale_stocks
          </span>`;
      } else {
        sourceBadge = `
          <span class="badge border px-2 py-1" style="font-size:0.75rem; background-color:#eff6ff; color:#1d4ed8; border-color:#bfdbfe;" title="Updated in krf_polyhouse_plant_details">
            <i class="fa fa-seedling mr-1"></i> krf_polyhouse_plant_details
          </span>`;
      }

      const actionTd = isRoleAdmin ? `
          <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger px-2 py-1" onclick="undoSingleTray('${esc(barcode)}')" title="Undo this loaded tray" style="font-size:0.8rem; font-weight:700;">
              <i class="fa fa-rotate-left mr-1"></i> Undo
            </button>
          </td>` : '';

      return `
        <tr>
          <td class="font-weight-bold text-muted text-center" style="font-size:0.85rem;">${idx + 1}</td>
          <td>
            <span class="badge bg-light text-dark border font-monospace px-2 py-1" style="font-size:0.88rem;">
              <i class="fa fa-barcode text-primary mr-1"></i> ${esc(barcode)}
            </span>
          </td>
          <td>
            ${sourceBadge}
          </td>
          <td>
            <span class="badge bg-info-subtle text-info border px-2 py-1" style="font-size:0.82rem; background-color:#e0f2fe; color:#0369a1; border-color:#bae6fd;">
              <i class="fa fa-warehouse mr-1"></i> ${esc(loc)}
            </span>
          </td>
          <td class="font-weight-bold text-success" style="font-size:0.88rem;">
            <i class="fa fa-seedling mr-1"></i> ${plants}
          </td>
          <td style="font-size:0.82rem; line-height:1.25;">
            ${loadedInfo}
          </td>
          <td>
            <span class="badge bg-success-subtle text-success border px-2 py-1" style="font-size:0.82rem; background-color:#dcfce7; color:#15803d; border-color:#bbf7d0;">
              <i class="fa fa-check-circle mr-1"></i> Loaded
            </span>
          </td>
          ${actionTd}
        </tr>
      `;
    }).join('');

    $('#tblUndoModalTraysBody').html(rowsHtml);
  }

  window.undoSingleTray = function (barcode) {
    if (!barcode) return;
    if (!currentUndoItem) return;
    const isRoleAdmin = (typeof window.loginRoleId !== 'undefined' && Number(window.loginRoleId) < 2);
    if (!isRoleAdmin) {
      if (window.Swal) Swal.fire('Access Denied', 'Only Administrator can undo loaded trays.', 'warning');
      return;
    }

    Swal.fire({
      title: 'Undo Loaded Tray?',
      html: `Do you want to revert tray <b>${esc(barcode)}</b> back to <b>Ready to Sale</b>?`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: '<i class="fa fa-rotate-left mr-1"></i> Yes, Undo Load',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#dc2626',
      cancelButtonColor: '#64748b'
    }).then((result) => {
      if (!result.isConfirmed) return;

      Swal.fire({
        title: 'Reverting Tray...',
        text: 'Updating database to revert tray status...',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => { Swal.showLoading(); }
      });

      $.ajax({
        url: 'model/readytoload_model.php',
        type: 'POST',
        dataType: 'json',
        data: {
          action: 'undo_tray_load',
          barcode: barcode,
          psono: currentUndoItem.psono
        },
        success: function (res) {
          if (res.status === 'success') {
            playScanBeep();
            currentUndoLoadedTrays = currentUndoLoadedTrays.filter(t => t.barcode !== barcode);
            renderLoadedTraysModalGrid();
            loadOrdersList(false);

            Swal.fire({
              icon: 'success',
              title: 'Tray Load Undone',
              text: res.message || 'Tray reverted successfully.',
              timer: 1600,
              showConfirmButton: false
            });
          } else {
            playWarningTone();
            Swal.fire('Undo Failed', res.message || 'Could not undo tray load.', 'error');
          }
        },
        error: function (xhr, status, err) {
          playWarningTone();
          Swal.fire('Server Error', 'Failed to communicate with server: ' + err, 'error');
        }
      });
    });
  };

  window.undoAllLoadedTrays = function () {
    if (!currentUndoItem) return;
    const isRoleAdmin = (typeof window.loginRoleId !== 'undefined' && Number(window.loginRoleId) < 2);
    if (!isRoleAdmin) {
      if (window.Swal) Swal.fire('Access Denied', 'Only Administrator can undo loaded trays.', 'warning');
      return;
    }
    const count = currentUndoLoadedTrays.length;
    if (count === 0) return;

    Swal.fire({
      title: `Undo All ${count} Loaded Trays?`,
      html: `Are you sure you want to revert <b>all ${count} loaded tray(s)</b> for <b>${esc(currentUndoItem.product_name)}</b> back to <b>Ready to Sale</b>?<br><br><span class="text-danger font-weight-bold">This will reverse all loaded records and shifting quantities for this item.</span>`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: '<i class="fa fa-rotate-left mr-1"></i> Yes, Undo All Trays',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#dc2626',
      cancelButtonColor: '#64748b'
    }).then((result) => {
      if (!result.isConfirmed) return;

      Swal.fire({
        title: 'Reverting All Trays...',
        text: 'Updating database...',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => { Swal.showLoading(); }
      });

      const barcodeList = (currentUndoLoadedTrays || []).map(t => t.barcode).filter(Boolean);

      $.ajax({
        url: 'model/readytoload_model.php',
        type: 'POST',
        dataType: 'json',
        data: {
          action: 'undo_all_item_trays',
          psono: currentUndoItem.psono,
          stockorder_no: currentUndoItem.allocated_stockorder_no || currentUndoItem.stockorder_no || '',
          clientid: currentUndoItem.clientid || '',
          item_id: currentUndoItem.item_id || currentUndoItem.id || 0,
          product_id: currentUndoItem.product_id ? String(currentUndoItem.product_id).trim() : '',
          product_name: currentUndoItem.product_name || '',
          barcodes: JSON.stringify(barcodeList)
        },
        success: function (res) {
          if (res.status === 'success') {
            playScanBeep();
            currentUndoLoadedTrays = [];
            renderLoadedTraysModalGrid();
            loadOrdersList(false);

            Swal.fire({
              icon: 'success',
              title: 'All Trays Undone',
              text: res.message || 'All loaded trays have been successfully reverted.',
              timer: 2000,
              showConfirmButton: false
            });

            setTimeout(() => {
              closeLoadedTraysModal();
            }, 1200);
          } else {
            playWarningTone();
            Swal.fire('Undo Failed', res.message || 'Could not undo trays.', 'error');
          }
        },
        error: function (xhr, status, err) {
          playWarningTone();
          Swal.fire('Server Error', 'Failed to communicate with server: ' + err, 'error');
        }
      });
    });
  };

  window.closeLoadedTraysModal = function () {
    $('body, html').removeClass('modal-open-lock');
    $('#loadedTraysModalOverlay').removeClass('active');
    currentUndoItem = null;
    currentUndoLoadedTrays = [];
  };

  // -------------------------------------------------------------
  // 7.1 Auto-Load RSO Trays (Manual User Trigger)
  // -------------------------------------------------------------
  window.autoLoadRsoTrays = function (psono, stockorder_no, quantity, clientid, invoiceno) {
    const isRoleAdmin = (typeof window.loginRoleId !== 'undefined' && Number(window.loginRoleId) < 2);
    if (!isRoleAdmin) {
      if (window.Swal) Swal.fire('Access Denied', 'Only Administrator can auto-load trays.', 'warning');
      return;
    }

    Swal.fire({
      title: 'Auto-Load RSO Trays?',
      html: `Do you want to automatically assign and mark active trays from ready stock batch <b>${esc(stockorder_no)}</b> as <b>Loaded</b> for order <b>${esc(psono)}</b>?`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: '<i class="fa fa-magic mr-1"></i> Yes, Auto-Load Trays',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#2563eb',
      cancelButtonColor: '#64748b'
    }).then((result) => {
      if (!result.isConfirmed) return;

      Swal.fire({
        title: 'Auto-Loading Trays...',
        text: 'Assigning ready stock trays from database...',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => { Swal.showLoading(); }
      });

      $.ajax({
        url: 'model/readytoload_model.php',
        type: 'POST',
        dataType: 'json',
        data: {
          action: 'auto_load_rso_trays',
          psono: psono,
          stockorder_no: stockorder_no,
          quantity: quantity,
          clientid: clientid,
          invoiceno: invoiceno
        },
        success: function (res) {
          if (res.status === 'success') {
            playScanBeep();
            Swal.fire({
              icon: 'success',
              title: 'Trays Loaded Successfully',
              text: res.message || 'Ready stock trays have been marked as loaded.',
              timer: 2000,
              showConfirmButton: false
            });
            loadOrdersList(false);
          } else {
            playWarningTone();
            Swal.fire('Auto-Load Failed', res.message || 'Could not auto-load trays.', 'error');
          }
        },
        error: function (xhr, status, err) {
          playWarningTone();
          Swal.fire('Server Error', 'Failed to communicate with server: ' + err, 'error');
        }
      });
    });
  };

  // -------------------------------------------------------------
  // 8. Confirm Final Dispatch for Order
  // Updates:
  //   - krf_polyhouse_plant_details (Loaded -> Final Dispatch)
  //   - krf_productionorder_consumed (status -> dispatched)
  //   - krf_sales_order_items (readytoload -> Final Dispatch)
  // -------------------------------------------------------------
  window.selectPartialAction = function (val) {
    $('input[name="partial_action"][value="' + val + '"]').prop('checked', true);
    if (val === 'keep_in_readytoload') {
      $('#choiceWrap_keep').css({ 'border-color': '#2563eb', 'background-color': '#eff6ff' }).addClass('active');
      $('#choiceWrap_release').css({ 'border-color': '#cbd5e1', 'background-color': '#ffffff' }).removeClass('active');
    } else {
      $('#choiceWrap_release').css({ 'border-color': '#059669', 'background-color': '#f0fdf4' }).addClass('active');
      $('#choiceWrap_keep').css({ 'border-color': '#cbd5e1', 'background-color': '#ffffff' }).removeClass('active');
    }
  };

  // -------------------------------------------------------------
  // 8. Handle Update Status Click with Real-time Re-verification
  // Re-verifies allocated trays (krf_readystock_allocation.allocatedbed / allocate_ordqty)
  // vs loaded trays in krf_polyhouse_plant_details
  // If equal: save data / update to Dispatched
  // If not equal: block save, show discrepancy, offer Undo vs Try Loading Again
  // -------------------------------------------------------------
  window.handleUpdateStatusClick = function (psoNo, itemId = 0, invoiceNo = '') {
    if (!psoNo && !invoiceNo) {
      Swal.fire('Error', 'Missing order details for verification.', 'error');
      return;
    }

    const lookupKey = psoNo + '_' + itemId;
    let lookupData = (window._itemsLookup && window._itemsLookup[lookupKey]) || null;
    if (!lookupData && window._ordersDataCache && itemId > 0) {
      for (const ord of window._ordersDataCache) {
        if (ord.psono === psoNo && ord.items) {
          const found = ord.items.find(it => (it.item_id === itemId || it.id === itemId || it.sii_id === itemId));
          if (found) {
            lookupData = { item: found, order: ord };
            break;
          }
        }
      }
    }
    if (!lookupData && window._itemsLookup && itemId === 0) {
      lookupData = window._itemsLookup[psoNo] || null;
    }
    const itemObj = lookupData ? lookupData.item : null;
    const ordObj = lookupData ? lookupData.order : null;

    Swal.fire({
      title: 'Verifying Allocation Status...',
      text: `Re-verifying allocated trays in krf_readystock_allocation vs loaded trays in krf_polyhouse_plant_details for Order ${psoNo}...`,
      allowOutsideClick: false,
      allowEscapeKey: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });

    $.ajax({
      url: 'model/readytoload_model.php',
      type: 'POST',
      dataType: 'json',
      data: {
        action: 'reverify_allocation_loading',
        psono: psoNo,
        invoiceno: invoiceNo || (itemObj ? itemObj.invoiceno : ''),
        stockorder_no: (itemObj && (itemObj.allocated_stockorder_no || itemObj.stockorder_no)) ? (itemObj.allocated_stockorder_no || itemObj.stockorder_no) : '',
        product_id: (itemObj && itemObj.product_id) ? String(itemObj.product_id).trim() : '',
        product_name: (itemObj && itemObj.product_name) ? itemObj.product_name : '',
        item_id: (itemObj && (itemObj.item_id || itemObj.id)) ? (itemObj.item_id || itemObj.id) : (itemId || 0)
      },
      success: function (res) {
        Swal.close();
        if (!res || res.status !== 'success') {
          playWarningTone();
          Swal.fire('Verification Error', (res && res.message) || 'Failed to verify allocation status.', 'error');
          return;
        }

        const allocTrays = Number(res.allocated_trays || 0);
        const loadedTrays = Number(res.loaded_trays || 0);
        const isMatched = Boolean(res.is_matched);
        const sourceDesc = res.allocatedbed_source === 'allocatedbed_json'
          ? 'Allocated bed array in <code>krf_readystock_allocation.allocatedbed</code>'
          : 'Calculated from <code>krf_readystock_allocation.allocate_ordqty</code>';

        if (isMatched && allocTrays > 0) {
          // EQUAL: Save data / Update status to Dispatched
          Swal.fire({
            title: invoiceNo ? `Update Invoice ${invoiceNo} to Dispatched?` : 'Update Status to Dispatched?',
            html: `
              <div class="text-left" style="font-size: 0.92rem; color: #334155;">
                <div class="alert alert-success text-left p-2.5 mb-3" style="font-size: 0.85rem; border-left: 4px solid #059669; background: #ecfdf5; border-radius: 6px;">
                  <div class="d-flex align-items-center mb-1">
                    <i class="fa fa-circle-check text-success mr-2" style="font-size: 1.15rem;"></i>
                    <strong style="color: #065f46;">All Allocated Trays Loaded & Verified!</strong>
                  </div>
                  <div style="color: #065f46;">
                    Total <b>${allocTrays} Trays</b> (${Number(res.allocated_plants || 0).toLocaleString()} plants) match loaded trays in polyhouse records.
                  </div>
                </div>

                <div class="mb-2"><strong>Order:</strong> <span class="badge bg-primary text-white font-mono px-2 py-0.5">${esc(psoNo)}</span></div>
                ${invoiceNo ? `<div class="mb-2"><strong>Invoice:</strong> <span class="badge" style="background:#ecfdf5; color:#047857; border:1px solid #10b981; font-weight:700;">Inv: ${esc(invoiceNo)}</span></div>` : ''}
                ${itemObj && itemObj.product_name ? `<div class="mb-2"><strong>Product:</strong> ${esc(itemObj.product_name)}</div>` : ''}
                <div class="mb-3" style="font-size: 0.82rem; color: #64748b;">
                  <i class="fa fa-database mr-1"></i> Allocation Source: ${sourceDesc}
                </div>

                <div class="form-group mb-0">
                  <label class="font-weight-bold" style="font-size: 0.82rem; color: #475569;">Dispatch Remarks / Notes (Optional):</label>
                  <input type="text" id="swalFinalNotes" class="form-control" placeholder="e.g. Verified and dispatched">
                </div>
              </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="fa fa-truck mr-1"></i> Save Data & Update to Dispatched',
            confirmButtonColor: '#059669',
            cancelButtonText: 'Cancel',
            cancelButtonColor: '#64748b',
            focusConfirm: false,
            preConfirm: () => {
              return {
                notes: $('#swalFinalNotes').val()
              };
            }
          }).then((result) => {
            if (!result.isConfirmed) return;
            const notes = (result.value && result.value.notes) || '';
            executeFinalDispatch(psoNo, invoiceNo, notes, itemObj);
          });

        } else {
          // NOT EQUAL: Cannot save data! Offer Undo Loaded Trays OR Try Loading Again
          playWarningTone();
          const diffTrays = Math.abs(allocTrays - loadedTrays);
          const isUnder = (loadedTrays < allocTrays);

          Swal.fire({
            title: '<span style="color:#b91c1c;"><i class="fa fa-triangle-exclamation mr-2"></i>Tray Allocation Mismatch!</span>',
            html: `
              <div class="text-left" style="font-size: 0.92rem; color: #334155; line-height: 1.5;">
                <div class="alert alert-danger p-2.5 mb-3" style="font-size: 0.86rem; border-left: 4px solid #ef4444; background: #fef2f2; border-radius: 6px;">
                  <div class="d-flex align-items-center mb-1">
                    <strong style="color: #991b1b;"><i class="fa fa-circle-xmark mr-1"></i> Cannot save status to Dispatched!</strong>
                  </div>
                  <div style="color: #991b1b; margin-top: 4px;">
                    Allocated trays (<b>${allocTrays}</b>) do not match loaded trays in polyhouse (<b>${loadedTrays}</b>).
                  </div>
                </div>

                <div class="p-2 mb-3" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px;">
                  <div class="row no-gutters text-center">
                    <div class="col-4 border-right">
                      <small class="text-muted d-block font-weight-bold">ALLOCATED</small>
                      <b style="font-size: 1.3rem; color: #2563eb;">${allocTrays}</b> <small class="text-muted">Trays</small>
                    </div>
                    <div class="col-4 border-right">
                      <small class="text-muted d-block font-weight-bold">LOADED</small>
                      <b style="font-size: 1.3rem; color: ${loadedTrays === 0 ? '#ef4444' : '#f59e0b'};">${loadedTrays}</b> <small class="text-muted">Trays</small>
                    </div>
                    <div class="col-4">
                      <small class="text-muted d-block font-weight-bold">${isUnder ? 'PENDING' : 'EXCESS'}</small>
                      <b style="font-size: 1.3rem; color: #dc2626;">${diffTrays}</b> <small class="text-muted">Trays</small>
                    </div>
                  </div>
                </div>

                <div class="mb-2" style="font-size:0.83rem;">
                  <strong>Order:</strong> <span class="badge bg-primary text-white font-mono px-2 py-0.5">${esc(psoNo)}</span>
                  ${invoiceNo ? `<span class="badge ml-1" style="background:#ecfdf5; color:#047857; border:1px solid #10b981;">Inv: ${esc(invoiceNo)}</span>` : ''}
                </div>
                ${itemObj && itemObj.product_name ? `<div class="mb-2" style="font-size:0.83rem;"><strong>Crop:</strong> ${esc(itemObj.product_name)}</div>` : ''}
                <div class="mb-2" style="font-size:0.8rem; color:#64748b;">
                  <i class="fa fa-database mr-1"></i> Allocation Source: ${sourceDesc}
                </div>
                ${(itemObj && itemObj.is_stock_available === false) ? `
                  <div class="alert alert-danger p-2 mb-2" style="font-size:0.83rem; background:#fff1f2; border:1px solid #fecdd3; color:#9f1239; border-radius:6px;">
                    <i class="fa fa-triangle-exclamation text-danger mr-1"></i> <b>Re-allocation Required:</b> ${esc(itemObj.stock_warning || 'Re-allocate it due to qty not available in this polyhouse.')}
                  </div>` : ''}

                <div class="mt-3 p-2.5 text-center" style="background: #fffbeb; border: 1px dashed #f59e0b; border-radius: 6px; font-size: 0.83rem; color: #92400e;">
                  <i class="fa fa-info-circle mr-1"></i> <b>Choose Next Step:</b>
                  <div class="mt-1">
                    ${(itemObj && itemObj.is_stock_available === false)
                      ? `Stock is unavailable in polyhouse. Please <b>Re-allocate this order</b> in Sales Order / Production Allocation.`
                      : `Click <b>'Try Loading Trays Again'</b> to scan and load the remaining trays, or click <b>'Undo Loaded Trays'</b> to review and revert loaded trays.`
                    }
                  </div>
                </div>
              </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            showDenyButton: true,
            confirmButtonText: (itemObj && itemObj.is_stock_available === false) ? '<i class="fa fa-circle-exclamation mr-1"></i> Re-allocate Required' : '<i class="fa fa-camera mr-1"></i> Try Loading Trays Again',
            confirmButtonColor: (itemObj && itemObj.is_stock_available === false) ? '#dc2626' : '#2563eb',
            denyButtonText: '<i class="fa fa-rotate-left mr-1"></i> Undo Loaded Trays',
            denyButtonColor: '#dc2626',
            cancelButtonText: 'Cancel',
            cancelButtonColor: '#64748b'
          }).then((dlgRes) => {
            if (dlgRes.isConfirmed) {
              if (itemObj && itemObj.is_stock_available === false) {
                Swal.fire({
                  icon: 'info',
                  title: 'Re-allocation Required',
                  html: `
                    <div style="text-align: left; font-size: 0.9rem; color: #334155;">
                      <p><b>${esc(itemObj.product_name)}</b> has no active stock available in the polyhouse for batch <code>${esc(itemObj.stockorder_no)}</code>.</p>
                      <p>Please open <b>Sales Order</b> or <b>Ready Stock Allocation</b> to assign an active batch with sufficient polyhouse stock.</p>
                    </div>
                  `,
                  confirmButtonColor: '#2563eb',
                  confirmButtonText: 'OK'
                });
                return;
              }
              if (itemObj) {
                openScannerModal(itemObj);
              } else {
                openScannerModal({ psono: psoNo, invoiceno: invoiceNo });
              }
            } else if (dlgRes.isDenied) {
              if (itemObj) {
                openLoadedTraysModal(itemObj);
              } else {
                openLoadedTraysModal({ psono: psoNo, invoiceno: invoiceNo });
              }
            }
          });
        }
      },
      error: function (xhr, status, err) {
        Swal.close();
        playWarningTone();
        Swal.fire('Connection Error', 'Could not verify tray status with server: ' + err, 'error');
      }
    });
  };

  window.executeFinalDispatch = function (psoNo, invoiceNo, notes, itemObj = null) {
    Swal.fire({
      title: 'Updating Status...',
      text: 'Executing Dispatched updates...',
      allowOutsideClick: false,
      allowEscapeKey: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });

    $.ajax({
      url: 'model/readytoload_model.php',
      type: 'POST',
      dataType: 'json',
      data: {
        action: 'confirm_final_dispatch',
        psono: psoNo,
        invoiceno: invoiceNo,
        notes: notes,
        partial_action: 'full',
        item_id: (itemObj && (itemObj.item_id || itemObj.id)) ? (itemObj.item_id || itemObj.id) : 0,
        product_id: (itemObj && itemObj.product_id) ? String(itemObj.product_id).trim() : '',
        product_name: (itemObj && itemObj.product_name) ? itemObj.product_name : '',
        stockorder_no: (itemObj && (itemObj.allocated_stockorder_no || itemObj.stockorder_no)) ? (itemObj.allocated_stockorder_no || itemObj.stockorder_no) : ''
      },
      success: function (res) {
        if (res.status === 'success') {
          playScanBeep();
          Swal.fire({
            icon: 'success',
            title: 'Dispatched Successfully!',
            html: `
              <div class="text-left" style="font-size: 0.92rem;">
                <p class="mb-2">${esc(res.message)}</p>
                ${invoiceNo ? `<div class="mb-1"><strong>Invoice:</strong> <span class="badge" style="background:#ecfdf5; color:#047857; border:1px solid #10b981;">${esc(invoiceNo)}</span></div>` : ''}
                ${res.sales_order_status ? `<div class="mb-2"><strong>Order Status:</strong> <span class="badge bg-success text-white px-2 py-1">${esc(res.sales_order_status)}</span></div>` : ''}
              </div>
            `,
            confirmButtonText: '<i class="fa fa-thumbs-up mr-1"></i> Done',
            confirmButtonColor: '#059669'
          }).then(() => {
            loadOrdersList(false);
          });
        } else if (res.status === 'mismatch_error') {
          playWarningTone();
          Swal.fire({
            title: '<span style="color:#b91c1c;"><i class="fa fa-triangle-exclamation mr-2"></i>Status Update Blocked!</span>',
            html: `
              <div class="text-left" style="font-size: 0.92rem; color: #334155; line-height: 1.5;">
                <div class="alert alert-danger p-2.5 mb-3" style="font-size: 0.86rem; border-left: 4px solid #ef4444; background: #fef2f2; border-radius: 6px;">
                  <strong>${esc(res.message)}</strong>
                </div>
                <div class="p-2 mb-3" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px;">
                  <div class="row no-gutters text-center">
                    <div class="col-6 border-right">
                      <small class="text-muted d-block font-weight-bold">ALLOCATED</small>
                      <b style="font-size: 1.25rem; color: #2563eb;">${res.allocated_trays} Trays</b>
                    </div>
                    <div class="col-6">
                      <small class="text-muted d-block font-weight-bold">LOADED IN POLYHOUSE</small>
                      <b style="font-size: 1.25rem; color: #ef4444;">${res.loaded_trays} Trays</b>
                    </div>
                  </div>
                </div>
                <p class="mb-0 text-muted" style="font-size: 0.82rem;">
                  <i class="fa fa-info-circle text-primary mr-1"></i> You can try loading the remaining trays or undo previously loaded trays.
                </p>
              </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            showDenyButton: true,
            confirmButtonText: '<i class="fa fa-camera mr-1"></i> Try Loading Trays Again',
            confirmButtonColor: '#2563eb',
            denyButtonText: '<i class="fa fa-rotate-left mr-1"></i> Undo Loaded Trays',
            denyButtonColor: '#dc2626',
            cancelButtonText: 'Cancel',
            cancelButtonColor: '#64748b'
          }).then((dlgRes) => {
            if (dlgRes.isConfirmed) {
              if (itemObj) openScannerModal(itemObj);
            } else if (dlgRes.isDenied) {
              if (itemObj) openLoadedTraysModal(itemObj);
            }
          });
        } else {
          playWarningTone();
          Swal.fire({
            icon: 'error',
            title: 'Update Failed',
            text: res.message || 'Could not update order status.',
            confirmButtonColor: '#dc2626'
          });
        }
      },
      error: function (xhr, status, err) {
        playWarningTone();
        Swal.fire({
          icon: 'error',
          title: 'Server Error',
          text: 'Communication failed: ' + err,
          confirmButtonColor: '#dc2626'
        });
      }
    });
  };

  // Backward compatibility wrapper for confirmFinalDispatch
  window.confirmFinalDispatch = function (psoNo, loadedTrays = 0, totalTrays = 0, loadedPlants = 0, totalPlants = 0, invoiceNo = '', pendingCount = 0) {
    window.handleUpdateStatusClick(psoNo, 0, invoiceNo);
  };

  // -------------------------------------------------------------
  // 8.1 Variety Change Approval Submission & Live Counter
  // -------------------------------------------------------------
  function sendVarietyApprovalRequest(reqData, reason) {
    Swal.fire({
      title: 'Submitting Request...',
      text: 'Please wait while approval request is recorded.',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });

    $.ajax({
      url: 'model/readytoload_model.php',
      type: 'POST',
      dataType: 'json',
      data: {
        action: 'create_variety_approval_request',
        barcode: reqData.barcode,
        polyhouseplantid: reqData.polyhouseplantid,
        target_psono: reqData.target_psono,
        target_sono: reqData.target_sono,
        target_clientid: reqData.target_clientid,
        target_clientname: reqData.target_clientname,
        target_stockorderno: reqData.target_stockorderno,
        target_productname: reqData.target_productname,
        target_variety: reqData.target_variety,
        target_deliverydate: reqData.target_deliverydate || '',
        scanned_psono: reqData.scanned_psono || '',
        scanned_sono: reqData.scanned_sono || '',
        scanned_stockorderno: reqData.scanned_stockorderno,
        scanned_productname: reqData.scanned_productname,
        scanned_variety: reqData.scanned_variety,
        scanned_deliverydate: reqData.scanned_deliverydate || '',
        tray_polyhouse_id: reqData.tray_polyhouse_id,
        tray_polyhouse_name: reqData.tray_polyhouse_name,
        tray_bed_no: reqData.tray_bed_no,
        qtypertray: reqData.qtypertray,
        reason: reason
      },
      success: function (res) {
        if (res.status === 'success') {
          playScanBeep();
          Swal.fire({
            width: '450px',
            padding: '1.25rem',
            html: `
              <div style="text-align: center; margin-bottom: 12px;">
                <div style="width: 50px; height: 50px; border-radius: 50%; background: #ecfdf5; border: 2px solid #a7f3d0; color: #059669; display: inline-flex; align-items: center; justify-content: center; font-size: 22px; margin-bottom: 6px; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.12);">
                  <i class="fa fa-check"></i>
                </div>
                <h3 style="font-size: 1.2rem; font-weight: 800; color: #0f172a; margin: 0 0 2px 0;">Request Submitted</h3>
                <p style="font-size: 0.8rem; color: #059669; margin: 0; font-weight: 600;">Request No: ${esc(res.request_no)}</p>
              </div>
              <div style="text-align: left; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; margin-bottom: 10px; font-size: 0.82rem; line-height: 1.5; color: #475569;">
                <div><b>Barcode:</b> <code>${esc(reqData.barcode)}</code> &bull; <b>Order:</b> ${esc(reqData.target_psono)}</div>
                <div class="text-truncate"><b>Expected:</b> ${esc(reqData.target_productname)}</div>
                <div class="text-truncate"><b>Scanned:</b> ${esc(reqData.scanned_productname)}</div>
              </div>
              <p class="mb-0 text-muted" style="font-size: 0.78rem;">
                <i class="fa fa-info-circle text-primary mr-1"></i> Once manager approves from <b>Variety Approvals</b>, scan this tray to load.
              </p>
            `,
            confirmButtonColor: '#059669',
            confirmButtonText: 'Done'
          });
          loadPendingApprovalCount();
        } else {
          playWarningTone();
          Swal.fire({
            icon: 'error',
            title: 'Submission Failed',
            text: res.message || 'Could not submit approval request.',
            confirmButtonColor: '#dc2626'
          });
        }
      },
      error: function (xhr, status, err) {
        playWarningTone();
        Swal.fire('Error', 'Communication error: ' + err, 'error');
      }
    });
  }

  function loadPendingApprovalCount() {
    $.ajax({
      url: 'model/readytoload_model.php',
      type: 'GET',
      dataType: 'json',
      data: { action: 'get_pending_approval_count' },
      success: function (res) {
        if (res.status === 'success') {
          const cnt = Number(res.pending_count || 0);
          const $badge = $('#badgePendingApprovalCount');
          if (cnt > 0) {
            $badge.text(cnt).show();
          } else {
            $badge.text('0').hide();
          }
        }
      }
    });
  }
  window.loadPendingApprovalCount = loadPendingApprovalCount;

  // -------------------------------------------------------------
  // 9. Card Selection & Revert Action
  // -------------------------------------------------------------
  window.selectActiveOrderBox = function (el) {
    if (!el) return;
    $('.order-box').removeClass('active-order-box');
    $(el).addClass('active-order-box');
  };

  window.revertOrderAction = function (invoiceno, psono) {
    if (typeof window.loginRoleId !== 'undefined' && Number(window.loginRoleId) >= 2) {
      if (window.Swal) Swal.fire('Access Denied', 'Only Administrator (loginroleid < 2) can revert order actions.', 'warning');
      return;
    }

    const targetLabel = invoiceno || psono || 'this order';

    Swal.fire({
      title: 'Revert Order Status?',
      html: `Do you want to revert <b>${esc(targetLabel)}</b> back to <b>Ready to Load</b>?<br><br><span class="text-danger font-weight-bold">This will reset invoice and order status to 'readytoload' and revert any dispatched trays so you can correct the data.</span>`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: '<i class="fa fa-rotate-left mr-1"></i> Yes, Revert Status',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#dc2626',
      cancelButtonColor: '#64748b'
    }).then((result) => {
      if (!result.isConfirmed) return;

      Swal.fire({
        title: 'Reverting Order...',
        text: 'Updating database to revert status back to Ready to Load...',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => { Swal.showLoading(); }
      });

      $.ajax({
        url: 'model/readytoload_model.php',
        type: 'POST',
        dataType: 'json',
        data: {
          action: 'revert_order_action',
          psono: psono || '',
          invoiceno: invoiceno || ''
        },
        success: function (res) {
          if (res.status === 'success') {
            playScanBeep();
            Swal.fire({
              icon: 'success',
              title: 'Order Status Reverted',
              text: res.message || 'Order reverted to Ready to Load successfully.',
              timer: 1800,
              showConfirmButton: false
            });
            loadOrdersList(false);
          } else {
            playWarningTone();
            Swal.fire('Revert Failed', res.message || 'Could not revert order status.', 'error');
          }
        },
        error: function (xhr, status, err) {
          playWarningTone();
          Swal.fire('Server Error', 'Failed to communicate with server: ' + err, 'error');
        }
      });
    });
  };

  // -------------------------------------------------------------
  // 10. Initialization
  // -------------------------------------------------------------
  $(document).ready(function () {
    try {
      if (localStorage.getItem('rtl_compact_view') === '1') {
        $('#ordersContainer').addClass('compact-cards');
        $('#btnToggleCompactView').addClass('active');
        $('#lblCompactView').text('Standard View');
      }
    } catch (e) {}

    loadOrdersList();
    loadPendingApprovalCount();
    setInterval(loadPendingApprovalCount, 45000);

    // Live filter as user types into search input
    $('#filterSearch').on('input', function () {
      const val = $(this).val();
      if (val.trim().length > 0) {
        $('#btnClearSearch').show();
      } else {
        $('#btnClearSearch').hide();
      }

      clearTimeout(searchDebounceTimer);
      searchDebounceTimer = setTimeout(function () {
        loadOrdersList(false);
      }, 250);
    });

    // Clear search button
    $('#btnClearSearch').on('click', function () {
      $('#filterSearch').val('').focus();
      $(this).hide();
      clearTimeout(searchDebounceTimer);
      loadOrdersList(true);
    });

    // Immediate filter on Enter key
    $('#filterSearch').on('keydown', function (e) {
      if (e.which === 13) {
        e.preventDefault();
        clearTimeout(searchDebounceTimer);
        loadOrdersList(false);
      }
    });

    // Manual Barcode Scanner Gun and Form submission
    $('#formManualScan').off('submit').on('submit', function (e) {
      e.preventDefault();
      triggerManualScan();
    });

    $('#inputBarcodeQuery').off('keydown').on('keydown', function (e) {
      if (e.which === 13 || e.keyCode === 13) {
        e.preventDefault();
        triggerManualScan();
      }
    });

    // Close modal on Escape key
    $(document).on('keydown', function (e) {
      if (e.key === 'Escape') {
        if ($('#loadedTraysModalOverlay').hasClass('active')) {
          closeLoadedTraysModal();
        } else if ($('#scannerModalOverlay').hasClass('active')) {
          closeScannerModal();
        }
      }
    });

    // Close modal on backdrop click
    $('#scannerModalOverlay').on('click', function (e) {
      if ($(e.target).is('#scannerModalOverlay')) {
        closeScannerModal();
      }
    });

    $('#loadedTraysModalOverlay').on('click', function (e) {
      if ($(e.target).is('#loadedTraysModalOverlay')) {
        closeLoadedTraysModal();
      }
    });
  });

})(jQuery);
