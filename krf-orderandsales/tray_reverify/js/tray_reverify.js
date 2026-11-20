/**
 * Bed Tray Re-Verification & Resolution Management
 * Karjee Farm Polyhouse Management System
 */

(function($) {
  'use strict';

  // State Management
  const state = {
    filters: {
      locations: [],
      polyhouses: []
    },
    currentLocationId: '',
    currentPolyhouseId: '',
    currentBedNo: '',
    occupiedTrays: [],
    reverifyTrays: [],
    ordersSummary: [],
    selectedOrderFilter: '',
    inBedSearchQuery: '',
    selectedReverifyIds: new Set(),
    activeTab: 'tabOccupied',
    html5QrScanner: null,
    isCameraRunning: false,
    cameraFacingMode: 'environment',

    // Physical Audit Sub-State
    audit: {
      isActive: false,
      orderNo: '',
      totalTrays: [],
      presentMap: new Map(),
      missingMap: new Map(),
      cameraScanner: null,
      isCameraRunning: false,
      cameraFacingMode: 'environment'
    }
  };

  const API_URL = 'modal/tray_reverify_modal.php';

  // Document Ready
  $(function() {
    initFilters();
    bindEvents();
    initScannerTabs();
  });

  // -------------------------------------------------------------
  // 1. Filter Initialization & Population
  // -------------------------------------------------------------
  function initFilters() {
    $.getJSON(API_URL, { action: 'get_filters' }, function(res) {
      if (res && res.status === 'success') {
        state.filters.locations = res.locations || [];
        state.filters.polyhouses = res.polyhouses || [];
        populateLocationDropdown();
        populatePolyhouseDropdown();
      } else {
        Swal.fire('Error', res.message || 'Failed to load filter options.', 'error');
      }
    }).fail(function() {
      Swal.fire('Network Error', 'Could not reach server to load filter options.', 'error');
    });
  }

  function populateLocationDropdown() {
    const $loc = $('#selectFilterLocation');
    $loc.empty().append('<option value="">-- All Locations --</option>');
    state.filters.locations.forEach(function(l) {
      $loc.append(`<option value="${esc(l.id)}">${esc(l.name)}</option>`);
    });
  }

  function populatePolyhouseDropdown() {
    const $ph = $('#selectFilterPolyhouse');
    const locId = state.currentLocationId;
    $ph.empty().append('<option value="">-- Select Polyhouse --</option>');

    state.filters.polyhouses.forEach(function(p) {
      if (!locId || String(p.locationid) === String(locId)) {
        $ph.append(`<option value="${esc(p.id)}">${esc(p.name)}</option>`);
      }
    });

    populateBedDropdown();
  }

  function populateBedDropdown() {
    const $bed = $('#selectFilterBed');
    const phId = state.currentPolyhouseId;
    $bed.empty().append('<option value="">-- Select Bed --</option>');

    if (!phId) {
      $bed.prop('disabled', true);
      return;
    }

    const phObj = state.filters.polyhouses.find(p => String(p.id) === String(phId));
    if (phObj && phObj.beds && phObj.beds.length > 0) {
      $bed.prop('disabled', false);
      phObj.beds.forEach(function(b) {
        $bed.append(`<option value="${esc(b)}">${esc(b)}</option>`);
      });
    } else {
      $bed.prop('disabled', false);
      $bed.append('<option value="" disabled>No configured beds found</option>');
    }
  }

  // -------------------------------------------------------------
  // 2. Event Listeners
  // -------------------------------------------------------------
  function bindEvents() {
    // Location Change
    $('#selectFilterLocation').on('change', function() {
      state.currentLocationId = $(this).val();
      state.currentPolyhouseId = '';
      state.currentBedNo = '';
      state.selectedOrderFilter = '';
      populatePolyhouseDropdown();
      resetDataView();
    });

    // Polyhouse Change
    $('#selectFilterPolyhouse').on('change', function() {
      state.currentPolyhouseId = $(this).val();
      state.currentBedNo = '';
      state.selectedOrderFilter = '';
      populateBedDropdown();
      resetDataView();
    });

    // Bed Change
    $('#selectFilterBed').on('change', function() {
      state.currentBedNo = $(this).val();
      state.selectedOrderFilter = '';
      if (state.currentPolyhouseId && state.currentBedNo) {
        loadBedTrays();
      } else {
        resetDataView();
      }
    });

    // Tab Navigation
    $('.nav-tab-link').on('click', function() {
      $('.nav-tab-link').removeClass('active');
      $(this).addClass('active');
      const target = $(this).data('tab');
      state.activeTab = target;
      if (target === 'tabOccupied') {
        $('#panelOccupiedView').show();
        $('#panelReverifyView').hide();
      } else {
        $('#panelOccupiedView').hide();
        $('#panelReverifyView').show();
      }
    });

    // In-Bed Order Filter Dropdown
    $('#selectInBedOrderFilter').on('change', function() {
      state.selectedOrderFilter = $(this).val();
      applyInBedFilters();
    });

    // In-Bed Search Text Input
    $('#inputInBedSearch').on('input', function() {
      state.inBedSearchQuery = $.trim($(this).val()).toLowerCase();
      applyInBedFilters();
    });

    // Clear In-Bed Search
    $('#btnClearInBedSearch').on('click', function() {
      $('#inputInBedSearch').val('');
      state.inBedSearchQuery = '';
      applyInBedFilters();
      $('#inputInBedSearch').focus();
    });

    // Button: Mark All Trays of Selected Order as Re-verify
    $('#btnMarkOrderReverify').on('click', function() {
      if (!state.selectedOrderFilter) {
        Swal.fire('Select Order', 'Please select an order above or click an order card to mark its trays as re-verify.', 'info');
        return;
      }
      const ordObj = state.ordersSummary.find(o => o.order_no === state.selectedOrderFilter);
      const count = ordObj ? ordObj.occupied_count : 0;
      window.markWholeOrderReverify(state.selectedOrderFilter, count);
    });

    // Barcode / Order Search Submit
    $('#formManualScan').on('submit', function(e) {
      e.preventDefault();
      triggerScanLookup();
    });

    // Resolution Triggers in Re-verify Queue
    $('#btnResEmptyAfterShifting').on('click', handleResolveEmptyAfterShifting);
    $('#btnResMoveGrafting').on('click', handleResolveMoveGrafting);
    $('#btnResDispatch').on('click', handleResolveDispatch);
    $('#btnResRestore').on('click', handleResolveRestore);

    // Physical Scan Audit Events
    $('#btnStartPhysicalAudit').on('click', function() {
      window.startPhysicalAudit(state.selectedOrderFilter);
    });

    $('#formAuditScanner').on('submit', function(e) {
      e.preventDefault();
      handleAuditBarcodeScan($('#inputAuditScanBarcode').val());
    });

    $('#btnManualAuditSubmit').on('click', function() {
      handleAuditBarcodeScan($('#inputAuditScanBarcode').val());
    });

    $('#btnFinishAuditReconcile').on('click', handleFinishAuditReconcile);

    $('#btnToggleAuditCamera').on('click', toggleAuditCamera);

    $('#modalPhysicalAudit').on('hidden.bs.modal', function() {
      stopAuditCamera();
      state.audit.isActive = false;
    });

    // Camera Controls
    $('#btnToggleCamera').on('click', toggleCameraScanner);
    $('#btnSwitchCamera').on('click', switchCameraFacing);

    // Single Tray Action Menu Clicks (Allowed for Role < 3)
    $(document).on('click', '.btn-tray-act-empty', function(e) {
      e.preventDefault();
      const id = $(this).data('id');
      handleSingleEmptyAfterShifting(id);
    });

    $(document).on('click', '.btn-tray-act-grafting', function(e) {
      e.preventDefault();
      const id = $(this).data('id');
      handleSingleMoveForGrafting(id);
    });

    $(document).on('click', '.btn-tray-act-dispatch', function(e) {
      e.preventDefault();
      const id = $(this).data('id');
      handleSingleDispatch(id);
    });

    $(document).on('click', '.btn-tray-act-restore', function(e) {
      e.preventDefault();
      const id = $(this).data('id');
      handleSingleRestore(id);
    });
  }

  // -------------------------------------------------------------
  // 3. Load & Render Bed Trays
  // -------------------------------------------------------------
  function loadBedTrays(onComplete) {
    if (!state.currentPolyhouseId || !state.currentBedNo) return;

    $('#loadingIndicator').show();
    $('#panelContentWrap').hide();
    $('#emptyStatePrompt').hide();

    $.getJSON(API_URL, {
      action: 'get_bed_trays',
      polyhouseid: state.currentPolyhouseId,
      bed_no: state.currentBedNo,
      locationid: state.currentLocationId
    }, function(res) {
      $('#loadingIndicator').hide();
      if (res && res.status === 'success') {
        const d = res.data;
        state.occupiedTrays = d.occupied_trays || [];
        state.reverifyTrays = d.reverify_trays || [];
        state.ordersSummary = d.orders_summary || [];

        state.selectedReverifyIds.clear();

        renderSummaryBar(d);
        renderOrderCards(d);
        populateInBedOrderFilter();
        renderOccupiedTable();
        renderReverifyTable();
        applyInBedFilters();
        updateSelectionBadges();

        $('#panelContentWrap').fadeIn(200);

        if (typeof onComplete === 'function') onComplete();
      } else {
        Swal.fire('Error', res.message || 'Failed to fetch bed trays.', 'error');
        resetDataView();
      }
    }).fail(function() {
      $('#loadingIndicator').hide();
      Swal.fire('Network Error', 'Failed to communicate with server.', 'error');
      resetDataView();
    });
  }

  function renderSummaryBar(d) {
    const phObj = state.filters.polyhouses.find(p => String(p.id) === String(state.currentPolyhouseId));
    const phName = phObj ? phObj.name : `PH-${state.currentPolyhouseId}`;

    $('#lblBedLocation').text(phName);
    $('#lblBedNumber').text(d.bed_no);
    $('#bedSummaryStrip').show();
  }

  /**
   * Render Order-Level Cards: allows marking ALL occupied trays of an order in one click
   */
  function renderOrderCards(d) {
    const $container = $('#boxBedOrdersList');
    $container.empty();

    const orders = state.ordersSummary || [];
    let activeOrders = orders.filter(o => o.occupied_count > 0 || o.reverify_count > 0);

    // CRITICAL: When an order is searched / selected, ONLY render that searched order card!
    if (state.selectedOrderFilter) {
      const targetOrd = state.selectedOrderFilter.toLowerCase().trim();
      activeOrders = activeOrders.filter(o => (o.order_no || '').toLowerCase().trim() === targetOrd);
    }

    if (activeOrders.length === 0) {
      $container.html(`
        <div class="w-100 text-muted small p-3 text-center rounded bg-light border">
          <i class="fa fa-info-circle text-primary mr-1"></i> ${state.selectedOrderFilter ? `No active trays found for Order <b>${esc(state.selectedOrderFilter)}</b> in this bed.` : 'No active orders found in this bed.'}
        </div>
      `);
      return;
    }

    activeOrders.forEach(function(ord) {
      const isSelected = (state.selectedOrderFilter === ord.order_no);
      const badges = [];
      if (ord.occupied_count > 0) {
        badges.push(`<span class="chip-badge chip-occupied"><i class="fa fa-boxes"></i> ${ord.occupied_count} Occupied</span>`);
      }
      if (ord.reverify_count > 0) {
        badges.push(`<span class="chip-badge chip-reverify"><i class="fa fa-clipboard-check"></i> ${ord.reverify_count} in Re-verify</span>`);
      }
      if (isSelected) {
        badges.push(`<span class="chip-selected-tag"><i class="fa fa-check mr-1"></i>Selected</span>`);
      }

      let actionButtons = '';
      if (ord.occupied_count > 0) {
        actionButtons += `
          <button type="button" class="btn-card-action btn-card-amber" onclick="event.stopPropagation(); window.markWholeOrderReverify('${esc(ord.order_no)}', ${ord.occupied_count});">
            <i class="fa fa-clipboard-check"></i> Mark All ${ord.occupied_count} Trays as Re-verify
          </button>
        `;
      }
      if (ord.reverify_count > 0) {
        actionButtons += `
          <button type="button" class="btn-card-action btn-card-indigo" onclick="event.stopPropagation(); window.startPhysicalAudit('${esc(ord.order_no)}');">
            <i class="fa fa-qrcode"></i> Start Physical Audit (${ord.reverify_count} Trays)
          </button>
        `;
      }
      if (ord.occupied_count === 0 && ord.reverify_count > 0) {
        actionButtons += `
          <div class="order-reverify-complete-note">
            <i class="fa fa-check-circle text-warning"></i> All trays are in Re-verify queue. Click <b>Start Physical Audit</b> above to verify.
          </div>
        `;
      }

      const totalPlants = ord.occupied_plants || ord.reverify_plants || 0;
      const clientName = ord.client_name ? esc(ord.client_name) : 'General Stock';

      const cardHtml = `
        <div class="order-reverify-card ${isSelected ? 'selected' : ''}" data-order="${esc(ord.order_no)}" onclick="window.filterByOrder('${esc(ord.order_no)}')">
          <div>
            <div class="order-card-header">
              <span class="order-code-badge"><i class="fa fa-receipt text-primary"></i> ${esc(ord.order_no)}</span>
              <div class="order-card-chips">
                ${badges.join(' ')}
              </div>
            </div>
            <div class="order-card-body">
              <div class="order-card-product" title="${esc(ord.productname || '')}">
                <i class="fa fa-seedling text-success mt-1"></i>
                <span>${esc(ord.productname || 'N/A')}</span>
              </div>
              <div class="order-card-meta">
                <span class="meta-item"><i class="fa fa-leaf text-muted"></i> Plants: <b>${totalPlants.toLocaleString()}</b></span>
                <span class="meta-item"><i class="fa fa-user text-muted"></i> Client: <b>${clientName}</b></span>
              </div>
            </div>
          </div>
          <div class="order-card-actions">
            ${actionButtons}
          </div>
        </div>
      `;
      $container.append(cardHtml);
    });
  }

  function populateInBedOrderFilter() {
    const $sel = $('#selectInBedOrderFilter');
    $sel.empty();
    const totalOcc = state.occupiedTrays.length;
    $sel.append(`<option value="">-- All Orders in Bed (${totalOcc}) --</option>`);

    if (state.ordersSummary && state.ordersSummary.length > 0) {
      state.ordersSummary.forEach(function(ord) {
        const countTxt = (ord.occupied_count > 0) ? `${ord.occupied_count} occupied` : `${ord.reverify_count} in re-verify`;
        $sel.append(`<option value="${esc(ord.order_no)}">${esc(ord.order_no)} (${countTxt})</option>`);
      });
    }

    if (state.selectedOrderFilter) {
      $sel.val(state.selectedOrderFilter);
    }
  }

  function applyInBedFilters() {
    const selectedOrder = (state.selectedOrderFilter || '').toLowerCase().trim();
    const searchQ = (state.inBedSearchQuery || '').toLowerCase().trim();

    // 1. FILTER ORDER CARDS: Only show searched/filtered order card!
    let visibleCardsCount = 0;
    $('.order-reverify-card').each(function() {
      const cardOrd = String($(this).data('order') || '').toLowerCase().trim();
      let matchesOrder = true;
      if (selectedOrder) {
        matchesOrder = (cardOrd === selectedOrder);
      }
      let matchesSearch = true;
      if (searchQ) {
        const cardText = $(this).text().toLowerCase();
        matchesSearch = (cardOrd.indexOf(searchQ) !== -1 || cardText.indexOf(searchQ) !== -1);
      }

      if (matchesOrder && matchesSearch) {
        $(this).show();
        visibleCardsCount++;
        if (selectedOrder && cardOrd === selectedOrder) {
          $(this).addClass('selected');
        } else {
          $(this).removeClass('selected');
        }
      } else {
        $(this).hide();
        $(this).removeClass('selected');
      }
    });

    // Update Section Header Title & Order Chip
    if (selectedOrder) {
      $('#lblOrdersSectionTitle').html(`
        <i class="fa fa-layer-group text-primary mr-1"></i> Searched Order: <b class="text-primary font-monospace" style="font-size:1.05rem;">${esc(state.selectedOrderFilter)}</b>
      `);
      $('#chipSearchedOrder').show();
      $('#lblSearchedOrderTag').text(state.selectedOrderFilter);
    } else if (searchQ) {
      const totalOrdersCount = (state.ordersSummary || []).filter(o => o.occupied_count > 0 || o.reverify_count > 0).length;
      $('#lblOrdersSectionTitle').html(`
        <i class="fa fa-layer-group text-primary mr-1"></i> Orders Matching "${esc(state.inBedSearchQuery)}" (${visibleCardsCount} of ${totalOrdersCount})
      `);
      $('#chipSearchedOrder').hide();
    } else {
      const totalOrdersCount = (state.ordersSummary || []).filter(o => o.occupied_count > 0 || o.reverify_count > 0).length;
      $('#lblOrdersSectionTitle').html(`
        <i class="fa fa-layer-group text-primary mr-1"></i> Orders in this Bed (${totalOrdersCount} Orders):
      `);
      $('#chipSearchedOrder').hide();
    }

    // 2. FILTER OCCUPIED TRAYS TABLE
    let visibleOccupiedCount = 0;
    let visibleOccupiedPlants = 0;
    $('#tblOccupiedTraysBody tr.occupied-data-row').each(function() {
      const $row = $(this);
      const rowOrder = ($row.data('order') || '').toString().toLowerCase().trim();
      const rowBarcode = ($row.data('barcode') || '').toString().toLowerCase().trim();
      const rowProduct = ($row.data('product') || '').toString().toLowerCase().trim();
      const plants = Number($row.data('plants')) || 0;

      let matchesOrder = true;
      if (selectedOrder) {
        matchesOrder = (rowOrder === selectedOrder);
      }

      let matchesSearch = true;
      if (searchQ) {
        matchesSearch = (rowOrder.indexOf(searchQ) !== -1 || rowBarcode.indexOf(searchQ) !== -1 || rowProduct.indexOf(searchQ) !== -1);
      }

      if (matchesOrder && matchesSearch) {
        $row.show();
        visibleOccupiedCount++;
        visibleOccupiedPlants += plants;
      } else {
        $row.hide();
      }
    });

    const filterColspan = (Number(window.LOGIN_ROLE_ID) < 3) ? 7 : 6;
    if (visibleOccupiedCount === 0 && (state.occupiedTrays.length > 0)) {
      if ($('#rowEmptyOccupiedFilter').length === 0) {
        $('#tblOccupiedTraysBody').append(`
          <tr id="rowEmptyOccupiedFilter">
            <td colspan="${filterColspan}" class="text-center py-4 text-muted">
              <i class="fa fa-info-circle text-info mr-1"></i> 
              ${selectedOrder ? `No active occupied trays for Order <b>${esc(state.selectedOrderFilter)}</b> in this bed.` : `No occupied trays match the filter.`}
            </td>
          </tr>
        `);
      } else {
        $('#rowEmptyOccupiedFilter').show();
      }
    } else {
      $('#rowEmptyOccupiedFilter').hide();
    }

    // 3. FILTER RE-VERIFY QUEUE TABLE: ONLY SHOW SEARCHED ORDER PENDING QUEUE
    let visibleReverifyCount = 0;
    let visibleReverifyPlants = 0;
    $('#tblReverifyTraysBody tr.reverify-data-row').each(function() {
      const $row = $(this);
      const rowOrder = ($row.data('order') || '').toString().toLowerCase().trim();
      const rowBarcode = ($row.data('barcode') || '').toString().toLowerCase().trim();
      const rowProduct = ($row.data('product') || '').toString().toLowerCase().trim();
      const plants = Number($row.data('plants')) || 0;

      let matchesOrder = true;
      if (selectedOrder) {
        matchesOrder = (rowOrder === selectedOrder);
      }

      let matchesSearch = true;
      if (searchQ) {
        matchesSearch = (rowOrder.indexOf(searchQ) !== -1 || rowBarcode.indexOf(searchQ) !== -1 || rowProduct.indexOf(searchQ) !== -1);
      }

      if (matchesOrder && matchesSearch) {
        $row.show();
        visibleReverifyCount++;
        visibleReverifyPlants += plants;
      } else {
        $row.hide();
      }
    });

    if (visibleReverifyCount === 0 && (state.reverifyTrays.length > 0)) {
      if ($('#rowEmptyReverifyFilter').length === 0) {
        $('#tblReverifyTraysBody').append(`
          <tr id="rowEmptyReverifyFilter">
            <td colspan="${filterColspan}" class="text-center py-4 text-muted">
              <i class="fa fa-info-circle text-warning mr-1"></i> 
              ${selectedOrder ? `No trays pending re-verification for Order <b>${esc(state.selectedOrderFilter)}</b>.` : `No trays match the search filter.`}
            </td>
          </tr>
        `);
      } else {
        $('#rowEmptyReverifyFilter').show();
      }
    } else {
      $('#rowEmptyReverifyFilter').hide();
    }

    // 4. UPDATE TOP KPI STRIP & TAB BADGES: STRICTLY SCOPED TO SEARCHED ORDER
    if (selectedOrder) {
      const ordObj = state.ordersSummary.find(o => o.order_no.toLowerCase().trim() === selectedOrder);
      const occTrays = ordObj ? ordObj.occupied_count : visibleOccupiedCount;
      const occPlants = ordObj ? ordObj.occupied_plants : visibleOccupiedPlants;
      const revTrays = ordObj ? ordObj.reverify_count : visibleReverifyCount;
      const revPlants = ordObj ? ordObj.reverify_plants : visibleReverifyPlants;

      // Update Occupied stats & Tab 1 badge
      $('#lblOccupiedTraysCount').text(occTrays);
      $('#lblOccupiedPlantsCount').text(occPlants.toLocaleString());
      $('#badgeOccupiedTabCount').text(occTrays);

      // Update Re-verify stats & Tab 2 badge: ONLY show searched order count!
      $('#lblReverifyTraysCount').text(revTrays);
      $('#lblReverifyPlantsCount').text(revPlants.toLocaleString());
      $('#badgeReverifyTabCount').text(revTrays);

      // "and occupied tray count only show"
      // If searched order has 0 reverify trays, hide the Re-verify pill so ONLY occupied count shows!
      if (revTrays > 0) {
        $('#pillReverifyStats').show();
      } else {
        $('#pillReverifyStats').hide();
      }
    } else {
      // Bed totals (when viewing all orders in bed)
      let totOccPlants = 0;
      state.occupiedTrays.forEach(t => totOccPlants += (t.actual_plants || 0));
      let totRevPlants = 0;
      state.reverifyTrays.forEach(t => totRevPlants += (t.actual_plants || 0));

      $('#lblOccupiedTraysCount').text(state.occupiedTrays.length);
      $('#lblOccupiedPlantsCount').text(totOccPlants.toLocaleString());
      $('#badgeOccupiedTabCount').text(state.occupiedTrays.length);

      $('#lblReverifyTraysCount').text(state.reverifyTrays.length);
      $('#lblReverifyPlantsCount').text(totRevPlants.toLocaleString());
      $('#badgeReverifyTabCount').text(state.reverifyTrays.length);

      $('#pillReverifyStats').show();
    }

    // 5. UPDATE ACTION BAR STATE
    const $banner = $('#barOrderAction');
    const $bLeft = $('#boxOrderBannerLeft');
    const $bRight = $('#boxOrderBannerRight');

    if (state.selectedOrderFilter) {
      const ordObj = state.ordersSummary.find(o => o.order_no.toLowerCase().trim() === selectedOrder);
      const count = ordObj ? ordObj.occupied_count : visibleOccupiedCount;
      const revCount = ordObj ? ordObj.reverify_count : visibleReverifyCount;

      if (count > 0) {
        $banner.removeClass('state-idle state-complete').addClass('state-ready');
        $bLeft.html(`
          <div class="d-flex align-items-center gap-2">
            <i class="fa fa-clipboard-check text-warning fa-lg"></i>
            <span>Order <b>${esc(state.selectedOrderFilter)}</b> has <b>${count}</b> active occupied tray(s) in this bed.</span>
          </div>
        `);
        $bRight.html(`
          <button type="button" class="btn-mark-reverify" id="btnMarkOrderReverify" onclick="window.markWholeOrderReverify('${esc(state.selectedOrderFilter)}', ${count})">
            <i class="fa fa-clipboard-check mr-1"></i> Mark All ${count} Trays of Order as Re-verify
          </button>
        `);
      } else {
        // 0 occupied trays remaining (all in reverify)
        $banner.removeClass('state-idle state-ready').addClass('state-complete');
        $bLeft.html(`
          <div class="d-flex align-items-center gap-2">
            <i class="fa fa-info-circle text-primary fa-lg"></i>
            <span>Order <b>${esc(state.selectedOrderFilter)}</b> has <b>0 occupied trays</b> remaining in bed (<b>${revCount}</b> trays are in the <b>Pending Re-verify Queue</b>).</span>
          </div>
        `);
        $bRight.html(`
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-primary font-weight-bold px-3 py-1.5" onclick="window.startPhysicalAudit('${esc(state.selectedOrderFilter)}')">
              <i class="fa fa-qrcode mr-1"></i> Start Physical Audit (${revCount})
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary font-weight-bold px-3 py-1.5" onclick="window.switchToReverifyTab('${esc(state.selectedOrderFilter)}')">
              <i class="fa fa-clipboard-check mr-1"></i> Open Re-verify Queue
            </button>
          </div>
        `);
      }
    } else {
      $banner.removeClass('state-ready state-complete').addClass('state-idle');
      $bLeft.html(`
        <span class="selected-count" id="lblOccupiedSelectedCount">
          <i class="fa fa-info-circle text-primary mr-1"></i> Showing all <b>${state.occupiedTrays.length}</b> occupied trays in this bed. Select an order above or click an order card to mark its trays.
        </span>
      `);
      $bRight.html(`
        <button type="button" class="btn-mark-reverify" id="btnMarkOrderReverify" disabled style="opacity:0.6; cursor:not-allowed;">
          <i class="fa fa-clipboard-check mr-1"></i> Mark All Trays of Order as Re-verify
        </button>
      `);
    }

    if (searchQ) {
      $('#btnClearInBedSearch').show();
    } else {
      $('#btnClearInBedSearch').hide();
    }
  }

  window.filterByOrder = function(orderNo) {
    state.selectedOrderFilter = orderNo || '';
    $('#selectInBedOrderFilter').val(orderNo || '');
    if (!orderNo) {
      $('#inputInBedSearch').val('');
      state.inBedSearchQuery = '';
    }
    renderOrderCards();
    renderOccupiedTable();
    renderReverifyTable();
    populateInBedOrderFilter();
    applyInBedFilters();
  };

  window.switchToReverifyTab = function(orderNo) {
    $('.nav-tab-link[data-tab="tabReverify"]').trigger('click');
  };

  /**
   * Render Occupied Trays Table: Action column shown for role < 3
   */
  function renderOccupiedTable() {
    const $tbody = $('#tblOccupiedTraysBody');
    $tbody.empty();
    const canAction = (Number(window.LOGIN_ROLE_ID) < 3);
    const colSpan = canAction ? 7 : 6;

    let list = state.occupiedTrays || [];
    if (state.selectedOrderFilter) {
      const ordTarget = state.selectedOrderFilter.toLowerCase().trim();
      list = list.filter(t => (t.stockorder_no || t.psono || '').toLowerCase().trim() === ordTarget);
    }

    if (list.length === 0) {
      $tbody.append(`
        <tr>
          <td colspan="${colSpan}" class="text-center py-4 text-muted">
            <i class="fa fa-info-circle mr-1"></i> ${state.selectedOrderFilter ? `No active occupied trays for Order <b>${esc(state.selectedOrderFilter)}</b> in this bed.` : 'No active occupied trays currently found in this bed.'}
          </td>
        </tr>
      `);
      return;
    }

    list.forEach(function(t, idx) {
      const statusClass = (t.status === 'Readytosale') ? 'status-rts' : 'status-polyhouse';
      const ordStr = (t.stockorder_no || t.psono || '');
      let actionColHtml = '';
      if (canAction) {
        actionColHtml = `
          <td class="text-center align-middle" style="white-space:nowrap;">
            <div class="dropdown">
              <button class="btn btn-sm btn-outline-secondary dropdown-toggle btn-tray-action-menu" 
                      type="button" 
                      data-bs-toggle="dropdown" 
                      data-bs-popper-config='{"strategy":"fixed"}' 
                      aria-expanded="false" 
                      title="Actions for this tray">
                <i class="fa fa-cog text-primary me-1"></i> Action
              </button>
              <ul class="dropdown-menu dropdown-menu-end shadow border-0 tray-action-dropdown-menu">
                <li>
                  <a class="dropdown-item py-2 px-3 btn-tray-act-empty" href="javascript:void(0)" data-id="${t.polyhouseplantid}">
                    <i class="fa fa-boxes text-info me-2" style="width:16px;"></i> Empty After Shifting
                  </a>
                </li>
                <li>
                  <a class="dropdown-item py-2 px-3 btn-tray-act-grafting" href="javascript:void(0)" data-id="${t.polyhouseplantid}">
                    <i class="fa fa-cut me-2" style="color:#7c3aed; width:16px;"></i> Move for Grafting
                  </a>
                </li>
                <li>
                  <a class="dropdown-item py-2 px-3 btn-tray-act-dispatch" href="javascript:void(0)" data-id="${t.polyhouseplantid}">
                    <i class="fa fa-truck text-primary me-2" style="width:16px;"></i> Dispatch
                  </a>
                </li>
              </ul>
            </div>
          </td>
        `;
      }

      const rowHtml = `
        <tr id="rowOccupied_${t.polyhouseplantid}" 
            class="occupied-data-row"
            data-order="${esc(ordStr)}"
            data-barcode="${esc(t.barcode || '')}"
            data-product="${esc(t.productname || '')}"
            data-plants="${t.actual_plants || 0}">
          <td>${idx + 1}</td>
          <td>
            <span class="tray-barcode-pill">${esc(t.barcode || 'N/A')}</span>
          </td>
          <td>
            <strong>${esc(t.productname || 'N/A')}</strong>
            <div class="small text-muted">${esc(t.variety || '')}</div>
          </td>
          <td>
            <span class="badge bg-light text-dark border font-weight-bold" style="cursor:pointer;" onclick="window.filterByOrder('${esc(ordStr)}')" title="Click to filter bed by this order">
              ${esc(ordStr || 'N/A')}
            </span>
          </td>
          <td>
            <b class="text-primary">${t.actual_plants}</b> plants
            <div class="small text-muted">(${t.qtypertray} capacity)</div>
          </td>
          <td>
            <span class="status-pill ${statusClass}">${esc(t.status)}</span>
          </td>
          ${actionColHtml}
        </tr>
      `;
      $tbody.append(rowHtml);
    });
  }

  /**
   * Render Re-verify Table: Action column shown for role < 3
   */
  function renderReverifyTable() {
    const $tbody = $('#tblReverifyTraysBody');
    $tbody.empty();
    const canAction = (Number(window.LOGIN_ROLE_ID) < 3);
    const colSpan = canAction ? 7 : 6;

    let list = state.reverifyTrays || [];
    if (state.selectedOrderFilter) {
      const ordTarget = state.selectedOrderFilter.toLowerCase().trim();
      list = list.filter(t => (t.stockorder_no || t.psono || '').toLowerCase().trim() === ordTarget);
    }

    if (list.length === 0) {
      $tbody.append(`
        <tr>
          <td colspan="${colSpan}" class="text-center py-4 text-muted">
            <i class="fa fa-info-circle text-warning mr-1"></i> ${state.selectedOrderFilter ? `No trays currently pending re-verification for Order <b>${esc(state.selectedOrderFilter)}</b>.` : 'No trays currently pending re-verification in this bed.'}
          </td>
        </tr>
      `);
      return;
    }

    list.forEach(function(t, idx) {
      const ordStr = (t.stockorder_no || t.psono || '');
      let actionColHtml = '';
      if (canAction) {
        actionColHtml = `
          <td class="text-center align-middle" style="white-space:nowrap;">
            <div class="dropdown">
              <button class="btn btn-sm btn-outline-secondary dropdown-toggle btn-tray-action-menu" 
                      type="button" 
                      data-bs-toggle="dropdown" 
                      data-bs-popper-config='{"strategy":"fixed"}' 
                      aria-expanded="false" 
                      title="Actions for this tray">
                <i class="fa fa-cog text-primary me-1"></i> Action
              </button>
              <ul class="dropdown-menu dropdown-menu-end shadow border-0 tray-action-dropdown-menu">
                <li>
                  <a class="dropdown-item py-2 px-3 btn-tray-act-empty" href="javascript:void(0)" data-id="${t.polyhouseplantid}">
                    <i class="fa fa-boxes text-info me-2" style="width:16px;"></i> Empty After Shifting
                  </a>
                </li>
                <li>
                  <a class="dropdown-item py-2 px-3 btn-tray-act-grafting" href="javascript:void(0)" data-id="${t.polyhouseplantid}">
                    <i class="fa fa-cut me-2" style="color:#7c3aed; width:16px;"></i> Move for Grafting
                  </a>
                </li>
                <li>
                  <a class="dropdown-item py-2 px-3 btn-tray-act-dispatch" href="javascript:void(0)" data-id="${t.polyhouseplantid}">
                    <i class="fa fa-truck text-primary me-2" style="width:16px;"></i> Dispatch
                  </a>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                  <a class="dropdown-item py-2 px-3 btn-tray-act-restore" href="javascript:void(0)" data-id="${t.polyhouseplantid}">
                    <i class="fa fa-check-circle text-success me-2" style="width:16px;"></i> Verify OK (Restore)
                  </a>
                </li>
              </ul>
            </div>
          </td>
        `;
      }

      const rowHtml = `
        <tr id="rowReverify_${t.polyhouseplantid}" 
            class="reverify-data-row"
            data-order="${esc(ordStr)}"
            data-barcode="${esc(t.barcode || '')}"
            data-product="${esc(t.productname || '')}"
            data-plants="${t.actual_plants || 0}">
          <td>${idx + 1}</td>
          <td>
            <span class="tray-barcode-pill" style="border-color:#fde68a; background:#fffbeb; color:#92400e;">
              ${esc(t.barcode || 'N/A')}
            </span>
          </td>
          <td>
            <strong>${esc(t.productname || 'N/A')}</strong>
            <div class="small text-muted">${esc(t.variety || '')}</div>
          </td>
          <td>
            <span class="badge bg-light text-dark border font-weight-bold" style="cursor:pointer;" onclick="window.filterByOrder('${esc(ordStr)}')" title="Click to filter by this order">
              ${esc(ordStr || 'N/A')}
            </span>
          </td>
          <td>
            <b>${t.actual_plants}</b> plants
          </td>
          <td>
            <span class="status-pill status-reverify">reverify</span>
            <div class="small text-muted mt-1" style="font-size:0.75rem;">
              By: <b>${esc(t.updated_by || 'Admin')}</b>
            </div>
          </td>
          ${actionColHtml}
        </tr>
      `;
      $tbody.append(rowHtml);
    });
  }

  function updateSelectionBadges() {
    const targetPool = getFilteredReverifyTrays();
    const totalRev = targetPool.length;
    if (totalRev > 0) {
      const ordInfo = state.selectedOrderFilter ? ` for Order <b>${esc(state.selectedOrderFilter)}</b>` : '';
      $('#lblReverifySelectedCount').html(`
        <span class="badge bg-warning text-dark font-weight-bold px-2.5 py-1.5" style="font-size:0.84rem;">
          <i class="fa fa-clock mr-1"></i> <b>${totalRev}</b> Tray(s) in Re-verify Queue${ordInfo}
        </span>
      `);
      $('.btn-res-action').removeClass('opacity-75 disabled').prop('disabled', false);
    } else {
      $('#lblReverifySelectedCount').html(`
        <span class="text-muted small">
          <i class="fa fa-check-circle text-success mr-1"></i> No trays pending re-verification in this queue.
        </span>
      `);
      $('.btn-res-action').addClass('opacity-75 disabled').prop('disabled', true);
    }
  }

  function resetDataView() {
    state.occupiedTrays = [];
    state.reverifyTrays = [];
    state.ordersSummary = [];
    state.selectedOrderFilter = '';
    state.inBedSearchQuery = '';
    state.selectedReverifyIds.clear();
    $('#panelContentWrap').hide();
    $('#bedSummaryStrip').hide();
    $('#emptyStatePrompt').show();
  }

  // -------------------------------------------------------------
  // 4. Barcode / QR / Order Scan Lookup
  // -------------------------------------------------------------
  function triggerScanLookup() {
    const query = $.trim($('#inputBarcodeQuery').val());
    if (!query) {
      Swal.fire('Empty Input', 'Please enter or scan an Order Number, Tray Barcode, or Bed QR.', 'warning');
      return;
    }

    Swal.showLoading();
    $.getJSON(API_URL, { action: 'scan_lookup', query: query }, function(res) {
      Swal.close();
      if (res && res.status === 'success') {
        
        // CASE A: Matched an Order Number (Stock Order No / PSO No)
        if (res.match_type === 'order') {
          const ordNo = res.order_no;
          const beds = res.beds || [];

          if (beds.length === 1) {
            // Order is in a single bed: automatically open that bed and filter to this order!
            const b = beds[0];
            state.currentPolyhouseId = String(b.polyhouseid);
            state.currentBedNo = String(b.bed_no);
            state.selectedOrderFilter = ordNo;

            $('#selectFilterPolyhouse').val(state.currentPolyhouseId);
            populateBedDropdown();
            $('#selectFilterBed').val(state.currentBedNo);

            loadBedTrays(function() {
              Swal.fire({
                icon: 'info',
                title: `Order: ${ordNo}`,
                text: `Found in ${b.polyhousename}, Bed ${b.bed_no} (${b.tray_count} trays). Filtered to this order.`,
                timer: 2500,
                showConfirmButton: false
              });
            });
          } else if (beds.length > 1) {
            // Order is split across multiple beds: display interactive picker modal
            let bedOptionsHtml = `<div class="list-group text-start mt-2">`;
            beds.forEach(function(b) {
              bedOptionsHtml += `
                <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center btn-select-order-bed" data-ph="${b.polyhouseid}" data-bed="${esc(b.bed_no)}" data-ord="${esc(ordNo)}">
                  <div>
                    <strong><i class="fa fa-th-large text-warning mr-1"></i> ${esc(b.bed_no)}</strong>
                    <div class="small text-muted">${esc(b.polyhousename)}</div>
                  </div>
                  <span class="badge bg-primary rounded-pill font-weight-bold">${b.tray_count} trays</span>
                </button>
              `;
            });
            bedOptionsHtml += `</div>`;

            Swal.fire({
              title: `Order: ${ordNo}`,
              html: `
                <p class="mb-1 text-muted small">This order has <b>${res.total_trays} tray(s)</b> across <b>${beds.length} beds</b>. Click a bed to view & audit:</p>
                ${bedOptionsHtml}
              `,
              showConfirmButton: false,
              showCancelButton: true,
              cancelButtonText: 'Close'
            });

            $(document).off('click', '.btn-select-order-bed').on('click', '.btn-select-order-bed', function() {
              const phId = $(this).data('ph');
              const bedNo = $(this).data('bed');
              const ord = $(this).data('ord');
              Swal.close();

              state.currentPolyhouseId = String(phId);
              state.currentBedNo = String(bedNo);
              state.selectedOrderFilter = ord;

              $('#selectFilterPolyhouse').val(state.currentPolyhouseId);
              populateBedDropdown();
              $('#selectFilterBed').val(state.currentBedNo);

              loadBedTrays();
            });
          }
          $('#inputBarcodeQuery').val('');
          return;
        }

        // CASE B: Matched a Single Tray Barcode or Bed QR
        const item = res.data;
        if (item && item.polyhouseid && item.bed_no) {
          state.currentPolyhouseId = String(item.polyhouseid);
          state.currentBedNo = String(item.bed_no);
          state.selectedOrderFilter = (item.stockorder_no || item.psono || '');

          $('#selectFilterPolyhouse').val(state.currentPolyhouseId);
          populateBedDropdown();
          $('#selectFilterBed').val(state.currentBedNo);

          loadBedTrays(function() {
            // Automatically select & scroll to matching tray
            if (res.match_type === 'tray' && item.polyhouseplantid) {
              const pid = Number(item.polyhouseplantid);
              if (item.status === 'reverify') {
                $('.nav-tab-link[data-tab="tabReverify"]').trigger('click');
                const $r = $(`#rowReverify_${pid}`);
                if ($r.length) {
                  $r.addClass('selected-row');
                  $r[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
              } else {
                $('.nav-tab-link[data-tab="tabOccupied"]').trigger('click');
                const $r = $(`#rowOccupied_${pid}`);
                if ($r.length) {
                  $r.addClass('selected-row');
                  $r[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
              }
              updateSelectionBadges();
            }
          });

          $('#inputBarcodeQuery').val('');
        } else {
          Swal.fire('Not Found', 'No location/bed details mapped for this barcode.', 'warning');
        }
      } else {
        Swal.fire('Not Found', res.message || 'No record found matching input barcode or order number.', 'error');
      }
    }).fail(function() {
      Swal.close();
      Swal.fire('Network Error', 'Failed to communicate with server.', 'error');
    });
  }

  // -------------------------------------------------------------
  // 5. Actions: Mark ALL Occupied Trays of an Order as Re-verify
  // -------------------------------------------------------------
  window.markWholeOrderReverify = function(orderNo, count) {
    if (!orderNo) {
      Swal.fire('No Order Selected', 'Please select an order to mark for re-verification.', 'warning');
      return;
    }

    Swal.fire({
      title: `Mark All Trays of ${orderNo} as Re-verify?`,
      html: `
        <div class="text-start">
          <div class="alert alert-warning py-2 px-3 small mb-2" style="border-color:#fde68a; background:#fffbeb;">
            <i class="fa fa-shield-alt text-warning mr-1"></i> <b>Order-Level Rule:</b> All <b>${count} occupied tray(s)</b> of Order <b>${esc(orderNo)}</b> in <b>Bed: ${esc(state.currentBedNo)}</b> will be marked as <code>reverify</code>.
          </div>
          <label class="form-label font-weight-bold small text-muted">Audit Remark / Reason (Optional):</label>
          <textarea id="swalInputRemark" class="form-control" rows="2" placeholder="e.g. Discrepancy during physical bed inspection..."></textarea>
        </div>
      `,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d97706',
      cancelButtonColor: '#64748b',
      confirmButtonText: `<i class="fa fa-clipboard-check mr-1"></i> Yes, Mark All ${count} Trays as Re-verify`
    }).then(function(result) {
      if (result.isConfirmed) {
        const remark = $.trim($('#swalInputRemark').val());
        executeApiPost({
          action: 'mark_reverify',
          order_no: orderNo,
          polyhouseid: state.currentPolyhouseId,
          bed_no: state.currentBedNo,
          remark: remark
        }, `Marking all trays of ${orderNo} as reverify...`);
      }
    });
  };

  // Helper: Retrieve reverify trays scoped strictly to the filtered order
  function getFilteredReverifyTrays() {
    if (!state.selectedOrderFilter) {
      return state.reverifyTrays || [];
    }
    const ord = state.selectedOrderFilter.toLowerCase().trim();
    return (state.reverifyTrays || []).filter(t => {
      const rowOrd = (t.stockorder_no || t.psono || '').toString().toLowerCase().trim();
      return rowOrd === ord;
    });
  }

  // -------------------------------------------------------------
  // 6. Resolutions: Empty After Shifting
  // -------------------------------------------------------------
  function handleResolveEmptyAfterShifting() {
    const targetPool = getFilteredReverifyTrays();
    const totalRev = targetPool.length;
    if (totalRev === 0) {
      const ordMsg = state.selectedOrderFilter ? ` for Order ${state.selectedOrderFilter}` : ' in this bed';
      Swal.fire('Queue Empty', `There are no trays pending re-verification${ordMsg}.`, 'info');
      return;
    }

    const scopeMsg = state.selectedOrderFilter ? `Order <b>${esc(state.selectedOrderFilter)}</b>` : `this bed's re-verify queue`;
    Swal.fire({
      title: 'Empty After Shifting',
      html: `
        <div class="text-start">
          <p class="mb-2">Mark <b>ALL ${totalRev} tray(s)</b> of ${scopeMsg} as <b>Empty After Shifting</b>? This will vacate their position from Bed: <b>${esc(state.currentBedNo)}</b>.</p>
          <label class="form-label font-weight-bold small text-muted">Movement Note (Optional):</label>
          <textarea id="swalEmptyNoteAll" class="form-control" rows="2" placeholder="e.g. Trays vacated after shifting..."></textarea>
        </div>
      `,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#0284c7',
      cancelButtonColor: '#64748b',
      confirmButtonText: `<i class="fa fa-boxes mr-1"></i> Yes, Mark ALL ${totalRev} Trays as Empty`,
      cancelButtonText: 'Cancel'
    }).then(function(res) {
      if (res.isConfirmed) {
        const allIds = targetPool.map(t => Number(t.polyhouseplantid));
        executeApiPost({
          action: 'resolve_empty_shifting',
          plant_ids: allIds,
          remark: $.trim($('#swalEmptyNoteAll').val())
        }, `Vacating ${totalRev} trays...`);
      }
    });
  }

  // -------------------------------------------------------------
  // 7. Resolutions: Move for Grafting
  // -------------------------------------------------------------
  function handleResolveMoveGrafting() {
    const targetPool = getFilteredReverifyTrays();
    const totalRev = targetPool.length;
    if (totalRev === 0) {
      const ordMsg = state.selectedOrderFilter ? ` for Order ${state.selectedOrderFilter}` : ' in this bed';
      Swal.fire('Queue Empty', `There are no trays pending re-verification${ordMsg}.`, 'info');
      return;
    }

    const scopeMsg = state.selectedOrderFilter ? `ALL ${totalRev} Trays of Order ${esc(state.selectedOrderFilter)}` : `ALL ${totalRev} Trays`;

    Swal.fire({
      title: `Move ${scopeMsg} for Grafting?`,
      html: `
        <div class="text-start">
          <p class="mb-2">Mark <b>ALL ${totalRev} tray(s)</b> as <b>Move for Grafting</b>.</p>
          <label class="form-label font-weight-bold small text-muted">Movement Note (Optional):</label>
          <textarea id="swalGraftingNote" class="form-control" rows="2" placeholder="e.g. Moved for grafting batch..."></textarea>
        </div>
      `,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#7c3aed',
      cancelButtonColor: '#64748b',
      confirmButtonText: `<i class="fa fa-cut mr-1"></i> Confirm Move for Grafting (${totalRev} Trays)`
    }).then(function(res) {
      if (res.isConfirmed) {
        const targetIds = targetPool.map(t => Number(t.polyhouseplantid));
        executeApiPost({
          action: 'resolve_move_grafting',
          plant_ids: targetIds,
          remark: $.trim($('#swalGraftingNote').val())
        }, 'Updating trays to Move for Grafting...');
      }
    });
  }

  // -------------------------------------------------------------
  // 8. Resolutions: Dispatch
  // -------------------------------------------------------------
  function handleResolveDispatch() {
    const targetPool = getFilteredReverifyTrays();
    const totalRev = targetPool.length;
    if (totalRev === 0) {
      const ordMsg = state.selectedOrderFilter ? ` for Order ${state.selectedOrderFilter}` : ' in this bed';
      Swal.fire('Queue Empty', `There are no trays pending re-verification${ordMsg}.`, 'info');
      return;
    }

    const scopeMsg = state.selectedOrderFilter ? `ALL ${totalRev} Trays of Order ${esc(state.selectedOrderFilter)}` : `ALL ${totalRev} Trays`;

    Swal.fire({
      title: `Dispatch ${scopeMsg}?`,
      html: `
        <div class="text-start">
          <div class="mb-3">
            <label class="form-label font-weight-bold small">Dispatch Type:</label>
            <select id="swalDispatchType" class="form-select">
              <option value="Final Dispatch">Final Dispatch (Customer Delivery)</option>
              <option value="Internal Dispatch">Internal Dispatch (Location Transfer)</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label font-weight-bold small">Customer / Target Location:</label>
            <input type="text" id="swalDispatchTarget" class="form-control" placeholder="e.g. Client Name / Vehicle No / Destination PH">
          </div>
          <div class="mb-2">
            <label class="form-label font-weight-bold small">Dispatch Remarks:</label>
            <textarea id="swalDispatchNote" class="form-control" rows="2" placeholder="e.g. Gate pass no. / delivery notes..."></textarea>
          </div>
        </div>
      `,
      showCancelButton: true,
      confirmButtonColor: '#4f46e5',
      confirmButtonText: `Confirm Dispatch (${totalRev} Trays)`
    }).then(function(res) {
      if (res.isConfirmed) {
        const targetIds = targetPool.map(t => Number(t.polyhouseplantid));
        executeApiPost({
          action: 'resolve_dispatch',
          plant_ids: targetIds,
          dispatch_type: $('#swalDispatchType').val(),
          dispatch_target: $.trim($('#swalDispatchTarget').val()),
          remark: $.trim($('#swalDispatchNote').val())
        }, 'Executing dispatch...');
      }
    });
  }

  // -------------------------------------------------------------
  // 9. Resolutions: Restore to Previous Status
  // -------------------------------------------------------------
  function handleResolveRestore() {
    const targetPool = getFilteredReverifyTrays();
    const totalRev = targetPool.length;
    if (totalRev === 0) {
      const ordMsg = state.selectedOrderFilter ? ` for Order ${state.selectedOrderFilter}` : ' in this bed';
      Swal.fire('Queue Empty', `There are no trays pending re-verification${ordMsg}.`, 'info');
      return;
    }

    const scopeMsg = state.selectedOrderFilter ? `ALL ${totalRev} Trays of Order ${esc(state.selectedOrderFilter)}` : `ALL ${totalRev} Trays`;

    Swal.fire({
      title: `Restore ${scopeMsg} to Previous Status?`,
      html: `
        <div class="text-start">
          <p class="mb-2">Verified physically present: restore <b>${totalRev} tray(s)</b> back to their <b>previous status</b> (Polyhouse / Ready to Sale) in Bed: <b>${esc(state.currentBedNo)}</b>.</p>
          <label class="form-label font-weight-bold small text-muted">Verification Note (Optional):</label>
          <textarea id="swalRestoreNote" class="form-control" rows="2" placeholder="e.g. Physical count verified OK during bed inspection..."></textarea>
        </div>
      `,
      icon: 'success',
      showCancelButton: true,
      confirmButtonColor: '#059669',
      cancelButtonColor: '#64748b',
      confirmButtonText: `<i class="fa fa-check-circle mr-1"></i> Restore ${totalRev} Trays to Previous Status`
    }).then(function(res) {
      if (res.isConfirmed) {
        const targetIds = targetPool.map(t => Number(t.polyhouseplantid));
        executeApiPost({
          action: 'resolve_restore',
          plant_ids: targetIds,
          remark: $.trim($('#swalRestoreNote').val())
        }, 'Restoring trays to previous status...');
      }
    });
  }

  // -------------------------------------------------------------
  // 9B. Single Tray Resolutions (Role < 3 Action Column)
  // -------------------------------------------------------------
  function findTrayById(plantId) {
    const id = Number(plantId);
    return (state.occupiedTrays || []).find(t => Number(t.polyhouseplantid) === id) ||
           (state.reverifyTrays || []).find(t => Number(t.polyhouseplantid) === id) ||
           null;
  }

  function handleSingleEmptyAfterShifting(plantId) {
    const tray = findTrayById(plantId);
    const barcode = tray ? (tray.barcode || 'N/A') : `ID: ${plantId}`;
    const orderNo = tray ? (tray.stockorder_no || tray.psono || 'N/A') : '';
    const prodName = tray ? `${tray.productname || ''} ${tray.variety ? '(' + tray.variety + ')' : ''}` : '';
    const plants = tray ? `${tray.actual_plants || 0} plants` : '';

    Swal.fire({
      title: 'Empty After Shifting',
      html: `
        <div class="text-start">
          <div class="p-2.5 mb-3 rounded" style="background:#f0f9ff; border:1px solid #bae6fd; font-size:0.88rem;">
            <div><b>Tray Barcode:</b> <code style="font-size:0.95rem; font-weight:700; color:#0369a1;">${esc(barcode)}</code></div>
            ${orderNo ? `<div class="mt-1"><b>Order:</b> ${esc(orderNo)} &bull; <b>Plants:</b> ${plants}</div>` : ''}
            ${prodName ? `<div><b>Product:</b> ${esc(prodName)}</div>` : ''}
            <div><b>Bed:</b> ${esc(state.currentBedNo || '-')}</div>
          </div>
          <p class="mb-2">Mark this tray as <b>Empty After Shifting</b>? This will vacate this tray from Bed: <b>${esc(state.currentBedNo)}</b>.</p>
          <label class="form-label font-weight-bold small text-muted">Movement Note (Optional):</label>
          <textarea id="swalSingleEmptyNote" class="form-control" rows="2" placeholder="e.g. Tray vacated after shifting..."></textarea>
        </div>
      `,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#0284c7',
      cancelButtonColor: '#64748b',
      confirmButtonText: '<i class="fa fa-boxes mr-1"></i> Yes, Mark Empty',
      cancelButtonText: 'Cancel'
    }).then(function(res) {
      if (res.isConfirmed) {
        executeApiPost({
          action: 'resolve_empty_shifting',
          plant_ids: [Number(plantId)],
          remark: $.trim($('#swalSingleEmptyNote').val())
        }, 'Vacating tray from bed...');
      }
    });
  }

  function handleSingleMoveForGrafting(plantId) {
    const tray = findTrayById(plantId);
    const barcode = tray ? (tray.barcode || 'N/A') : `ID: ${plantId}`;
    const orderNo = tray ? (tray.stockorder_no || tray.psono || 'N/A') : '';
    const prodName = tray ? `${tray.productname || ''} ${tray.variety ? '(' + tray.variety + ')' : ''}` : '';
    const plants = tray ? `${tray.actual_plants || 0} plants` : '';

    Swal.fire({
      title: 'Move for Grafting',
      html: `
        <div class="text-start">
          <div class="p-2.5 mb-3 rounded" style="background:#f5f3ff; border:1px solid #ddd6fe; font-size:0.88rem;">
            <div><b>Tray Barcode:</b> <code style="font-size:0.95rem; font-weight:700; color:#6d28d9;">${esc(barcode)}</code></div>
            ${orderNo ? `<div class="mt-1"><b>Order:</b> ${esc(orderNo)} &bull; <b>Plants:</b> ${plants}</div>` : ''}
            ${prodName ? `<div><b>Product:</b> ${esc(prodName)}</div>` : ''}
            <div><b>Bed:</b> ${esc(state.currentBedNo || '-')}</div>
          </div>
          <p class="mb-2">Mark this tray as <b>Move for Grafting</b>?</p>
          <label class="form-label font-weight-bold small text-muted">Movement Note (Optional):</label>
          <textarea id="swalSingleGraftingNote" class="form-control" rows="2" placeholder="e.g. Moved for grafting batch..."></textarea>
        </div>
      `,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#7c3aed',
      cancelButtonColor: '#64748b',
      confirmButtonText: '<i class="fa fa-cut mr-1"></i> Confirm Move for Grafting',
      cancelButtonText: 'Cancel'
    }).then(function(res) {
      if (res.isConfirmed) {
        executeApiPost({
          action: 'resolve_move_grafting',
          plant_ids: [Number(plantId)],
          remark: $.trim($('#swalSingleGraftingNote').val())
        }, 'Updating tray to Move for Grafting...');
      }
    });
  }

  function handleSingleDispatch(plantId) {
    const tray = findTrayById(plantId);
    const barcode = tray ? (tray.barcode || 'N/A') : `ID: ${plantId}`;
    const orderNo = tray ? (tray.stockorder_no || tray.psono || 'N/A') : '';
    const prodName = tray ? `${tray.productname || ''} ${tray.variety ? '(' + tray.variety + ')' : ''}` : '';
    const plants = tray ? `${tray.actual_plants || 0} plants` : '';

    Swal.fire({
      title: 'Dispatch Tray',
      html: `
        <div class="text-start">
          <div class="p-2.5 mb-3 rounded" style="background:#eef2ff; border:1px solid #c7d2fe; font-size:0.88rem;">
            <div><b>Tray Barcode:</b> <code style="font-size:0.95rem; font-weight:700; color:#3730a3;">${esc(barcode)}</code></div>
            ${orderNo ? `<div class="mt-1"><b>Order:</b> ${esc(orderNo)} &bull; <b>Plants:</b> ${plants}</div>` : ''}
            ${prodName ? `<div><b>Product:</b> ${esc(prodName)}</div>` : ''}
            <div><b>Bed:</b> ${esc(state.currentBedNo || '-')}</div>
          </div>
          <div class="mb-3">
            <label class="form-label font-weight-bold small">Dispatch Type:</label>
            <select id="swalSingleDispatchType" class="form-select form-select-sm">
              <option value="Final Dispatch">Final Dispatch (Customer Delivery)</option>
              <option value="Internal Dispatch">Internal Dispatch (Location Transfer)</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label font-weight-bold small">Customer / Target Location:</label>
            <input type="text" id="swalSingleDispatchTarget" class="form-control form-control-sm" placeholder="e.g. Client Name / Vehicle No / Destination PH">
          </div>
          <div class="mb-2">
            <label class="form-label font-weight-bold small">Dispatch Remarks:</label>
            <textarea id="swalSingleDispatchNote" class="form-control form-control-sm" rows="2" placeholder="e.g. Gate pass no. / delivery notes..."></textarea>
          </div>
        </div>
      `,
      showCancelButton: true,
      confirmButtonColor: '#4f46e5',
      cancelButtonColor: '#64748b',
      confirmButtonText: '<i class="fa fa-truck mr-1"></i> Confirm Dispatch',
      cancelButtonText: 'Cancel'
    }).then(function(res) {
      if (res.isConfirmed) {
        executeApiPost({
          action: 'resolve_dispatch',
          plant_ids: [Number(plantId)],
          dispatch_type: $('#swalSingleDispatchType').val(),
          dispatch_target: $.trim($('#swalSingleDispatchTarget').val()),
          remark: $.trim($('#swalSingleDispatchNote').val())
        }, 'Executing dispatch...');
      }
    });
  }

  function handleSingleRestore(plantId) {
    const tray = findTrayById(plantId);
    const barcode = tray ? (tray.barcode || 'N/A') : `ID: ${plantId}`;
    const orderNo = tray ? (tray.stockorder_no || tray.psono || 'N/A') : '';
    const prodName = tray ? `${tray.productname || ''} ${tray.variety ? '(' + tray.variety + ')' : ''}` : '';

    Swal.fire({
      title: 'Restore Tray to Previous Status',
      html: `
        <div class="text-start">
          <div class="p-2.5 mb-3 rounded" style="background:#ecfdf5; border:1px solid #a7f3d0; font-size:0.88rem;">
            <div><b>Tray Barcode:</b> <code style="font-size:0.95rem; font-weight:700; color:#047857;">${esc(barcode)}</code></div>
            ${orderNo ? `<div class="mt-1"><b>Order:</b> ${esc(orderNo)}</div>` : ''}
            ${prodName ? `<div><b>Product:</b> ${esc(prodName)}</div>` : ''}
            <div><b>Bed:</b> ${esc(state.currentBedNo || '-')}</div>
          </div>
          <p class="mb-2">Verified physically present: restore this tray back to its <b>previous status</b> (Polyhouse / Ready to Sale) in Bed: <b>${esc(state.currentBedNo)}</b>.</p>
          <label class="form-label font-weight-bold small text-muted">Verification Note (Optional):</label>
          <textarea id="swalSingleRestoreNote" class="form-control" rows="2" placeholder="e.g. Physical count verified OK..."></textarea>
        </div>
      `,
      icon: 'success',
      showCancelButton: true,
      confirmButtonColor: '#059669',
      cancelButtonColor: '#64748b',
      confirmButtonText: '<i class="fa fa-check-circle mr-1"></i> Restore Tray',
      cancelButtonText: 'Cancel'
    }).then(function(res) {
      if (res.isConfirmed) {
        executeApiPost({
          action: 'resolve_restore',
          plant_ids: [Number(plantId)],
          remark: $.trim($('#swalSingleRestoreNote').val())
        }, 'Restoring tray to previous status...');
      }
    });
  }

  window.resolveSingle = function(plantId, actionType) {
    if (actionType === 'empty') handleSingleEmptyAfterShifting(plantId);
    else if (actionType === 'grafting') handleSingleMoveForGrafting(plantId);
    else if (actionType === 'dispatch') handleSingleDispatch(plantId);
    else if (actionType === 'restore') handleSingleRestore(plantId);
  };

  // -------------------------------------------------------------
  // 10. Generic API Exec & Reload
  // -------------------------------------------------------------
  function executeApiPost(postData, loadingMsg) {
    Swal.fire({
      title: loadingMsg || 'Processing...',
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading()
    });

    $.post(API_URL, postData, function(res) {
      if (res && res.status === 'success') {
        Swal.fire({
          icon: 'success',
          title: 'Success!',
          text: res.message || 'Operation executed successfully.',
          timer: 2000,
          showConfirmButton: false
        });
        loadBedTrays();
      } else {
        Swal.fire('Error', res.message || 'Failed to complete operation.', 'error');
      }
    }, 'json').fail(function() {
      Swal.fire('Network Error', 'Failed to reach server.', 'error');
    });
  }

  // -------------------------------------------------------------
  // 10B. Physical Bed Audit & Reconciliation (Present vs. Missing)
  // -------------------------------------------------------------
  function playAuditBeep(type) {
    try {
      const AudioCtx = window.AudioContext || window.webkitAudioContext;
      if (!AudioCtx) return;
      const ctx = new AudioCtx();
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.connect(gain);
      gain.connect(ctx.destination);

      if (type === 'success') {
        osc.type = 'sine';
        osc.frequency.setValueAtTime(880, ctx.currentTime);
        osc.frequency.exponentialRampToValueAtTime(1320, ctx.currentTime + 0.12);
        gain.gain.setValueAtTime(0.3, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.12);
        osc.start(ctx.currentTime);
        osc.stop(ctx.currentTime + 0.12);
      } else if (type === 'duplicate') {
        osc.type = 'triangle';
        osc.frequency.setValueAtTime(440, ctx.currentTime);
        gain.gain.setValueAtTime(0.2, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.18);
        osc.start(ctx.currentTime);
        osc.stop(ctx.currentTime + 0.18);
      } else { // error / warning
        osc.type = 'sawtooth';
        osc.frequency.setValueAtTime(220, ctx.currentTime);
        gain.gain.setValueAtTime(0.3, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.25);
        osc.start(ctx.currentTime);
        osc.stop(ctx.currentTime + 0.25);
      }
    } catch (e) {
      // Audio not permitted or supported
    }
  }

  window.openAuditModal = function() {
    const $m = $('#modalPhysicalAudit');
    if ($m.length === 0) return;

    if (typeof $m.modal === 'function') {
      try {
        $m.modal('show');
        return;
      } catch (e) {
        console.warn('jQuery modal show error:', e);
      }
    }
    if (window.bootstrap && bootstrap.Modal) {
      try {
        const inst = bootstrap.Modal.getOrCreateInstance ? bootstrap.Modal.getOrCreateInstance($m[0]) : new bootstrap.Modal($m[0]);
        inst.show();
        return;
      } catch (e) {
        console.warn('bootstrap.Modal show error:', e);
      }
    }
    // Fallback: manually display
    $m.addClass('show').css({ display: 'block', zIndex: 1055 });
    $('body').addClass('modal-open');
    if ($('.modal-backdrop').length === 0) {
      $('body').append('<div class="modal-backdrop fade show audit-custom-backdrop"></div>');
    }
  };

  window.closeAuditModal = function() {
    const $m = $('#modalPhysicalAudit');
    if ($m.length === 0) return;

    if (typeof $m.modal === 'function') {
      try {
        $m.modal('hide');
      } catch (e) {}
    }
    if (window.bootstrap && bootstrap.Modal) {
      try {
        const inst = bootstrap.Modal.getInstance ? bootstrap.Modal.getInstance($m[0]) : null;
        if (inst) inst.hide();
      } catch (e) {}
    }
    $m.removeClass('show').css('display', 'none');
    $('.modal-backdrop, .audit-custom-backdrop').remove();
    $('body').removeClass('modal-open').css('overflow', 'auto');
    stopAuditCamera();
    state.audit.isActive = false;
  };

  window.startPhysicalAudit = function(filterOrderNo) {
    const targetOrder = filterOrderNo || state.selectedOrderFilter || '';
    if (!state.currentPolyhouseId || !state.currentBedNo) {
      Swal.fire('No Bed Selected', 'Please select a polyhouse and bed first.', 'warning');
      return;
    }

    // Determine target trays for audit from reverifyTrays
    let pool = state.reverifyTrays || [];
    if (targetOrder) {
      const ordLower = targetOrder.toLowerCase().trim();
      pool = pool.filter(t => (String(t.stockorder_no || t.psono || '').toLowerCase().trim() === ordLower));
    }

    // If no trays currently in re-verify queue for this bed/order
    if (pool.length === 0) {
      // Check if there are occupied trays of this order that can be marked for re-verify
      let occPool = state.occupiedTrays || [];
      if (targetOrder) {
        const ordLower = targetOrder.toLowerCase().trim();
        occPool = occPool.filter(t => (String(t.stockorder_no || t.psono || '').toLowerCase().trim() === ordLower));
      }
      
      if (occPool.length > 0) {
        const ordText = targetOrder ? `Order <b>${esc(targetOrder)}</b> (${occPool.length} occupied trays)` : `all <b>${occPool.length} occupied trays</b> in this bed`;
        Swal.fire({
          title: 'Mark for Re-verify & Start Audit?',
          html: `
            <p class="mb-2">There are currently 0 trays in Re-verify for this bed, but found ${ordText}.</p>
            <p class="small text-muted">To physically audit this bed, all ${occPool.length} trays will first be marked as <code>reverify</code>, then the audit scanner will open.</p>
          `,
          icon: 'question',
          showCancelButton: true,
          confirmButtonColor: '#d97706',
          confirmButtonText: 'Yes, Mark & Start Audit'
        }).then(function(result) {
          if (result.isConfirmed) {
            Swal.showLoading();
            const orderToMark = targetOrder || (occPool[0].stockorder_no || occPool[0].psono);
            $.post(API_URL, {
              action: 'mark_reverify',
              order_no: orderToMark,
              polyhouseid: state.currentPolyhouseId,
              bed_no: state.currentBedNo,
              remark: 'Marked for Physical Scan Audit'
            }, function(res) {
              if (res && res.status === 'success') {
                loadBedTrays(function() {
                  window.startPhysicalAudit(orderToMark);
                });
              } else {
                Swal.fire('Error', res.message || 'Failed to mark trays for re-verify.', 'error');
              }
            }, 'json').fail(function() {
              Swal.fire('Network Error', 'Could not reach server.', 'error');
            });
          }
        });
        return;
      } else {
        Swal.fire('No Trays Found', 'No trays currently found for re-verification in this bed.', 'info');
        return;
      }
    }

    // Set up audit state
    state.audit.isActive = true;
    state.audit.orderNo = filterOrderNo || '';
    state.audit.totalTrays = pool;
    state.audit.presentMap.clear();
    state.audit.missingMap.clear();

    pool.forEach(t => {
      state.audit.missingMap.set(Number(t.polyhouseplantid), t);
    });

    const phObj = state.filters.polyhouses.find(p => String(p.id) === String(state.currentPolyhouseId));
    $('#lblAuditPolyhouse').text(phObj ? phObj.name : `PH-${state.currentPolyhouseId}`);
    $('#lblAuditBed').text(state.currentBedNo);
    $('#lblAuditOrder').text(filterOrderNo || 'All Trays in Bed Re-verify Queue');
    $('#auditScanFeedback').html('<i class="fa fa-info-circle text-primary mr-1"></i> Auditor: Walk along the bed and scan each tray barcode you see.');

    renderAuditView();

    window.openAuditModal();

    setTimeout(() => {
      $('#inputAuditScanBarcode').val('').focus();
    }, 400);
  };

  function renderAuditView() {
    const total = state.audit.totalTrays.length;
    const presentCount = state.audit.presentMap.size;
    const missingCount = state.audit.missingMap.size;

    $('#lblAuditTotalCount').text(total);
    $('#lblAuditPresentCount').text(presentCount);
    $('#lblAuditMissingCount').text(missingCount);

    $('#lblAuditListPresentCount').text(presentCount);
    $('#lblAuditListMissingCount').text(missingCount);
    $('#lblReconcileSummaryBtn').text(`${presentCount} Present / ${missingCount} Missing`);

    // Render Present List
    const $presList = $('#listAuditPresent');
    $presList.empty();
    if (presentCount === 0) {
      $presList.html(`
        <div class="empty-box py-4 text-center my-auto" style="border:1.5px dashed var(--slate-200); background:#fcfdfd; border-radius:12px;">
          <i class="fa fa-qrcode fa-2x text-muted mb-2" style="opacity:0.4;"></i>
          <h6 class="font-weight-bold text-dark mb-1">No Trays Verified Yet</h6>
          <p class="text-muted small mb-0">Scan barcode on physical tray in bed to verify it present.</p>
        </div>
      `);
    } else {
      state.audit.presentMap.forEach((t, pid) => {
        const orderNo = esc(t.stockorder_no || t.psono || 'N/A');
        const prod = esc(t.productname || '');
        $presList.append(`
          <div class="audit-tray-card present" id="auditRowPresent_${pid}">
            <div class="audit-tray-info">
              <div class="audit-tray-top-row">
                <span class="audit-barcode-tag">${esc(t.barcode)}</span>
                <span class="audit-plants-tag">${t.actual_plants} plants</span>
              </div>
              <div class="audit-tray-subtext">
                <span class="text-dark font-weight-bold">${orderNo}</span>
                <span>&bull;</span>
                <span class="text-truncate" style="max-width:320px;" title="${prod}">${prod}</span>
              </div>
            </div>
            <span class="audit-status-badge badge-present">
              <i class="fa fa-check-circle"></i> Present
            </span>
          </div>
        `);
      });
    }
  }

  function handleAuditBarcodeScan(rawQuery) {
    const q = $.trim(rawQuery);
    if (!q) return;

    // Search barcode in missing map
    let foundPid = null;
    let foundTray = null;

    for (let [pid, t] of state.audit.missingMap.entries()) {
      if (t.barcode && t.barcode.toLowerCase() === q.toLowerCase()) {
        foundPid = pid;
        foundTray = t;
        break;
      }
    }

    if (foundTray) {
      // Valid scan! Move from missing to present
      state.audit.missingMap.delete(foundPid);
      state.audit.presentMap.set(foundPid, foundTray);
      playAuditBeep('success');

      $('#auditScanFeedback').html(`
        <span class="text-success font-weight-bold">
          <i class="fa fa-check-circle mr-1"></i> <b>${esc(foundTray.barcode)}</b> Verified Present! (${state.audit.presentMap.size} of ${state.audit.totalTrays.length})
        </span>
      `);

      renderAuditView();
      const el = document.getElementById(`auditRowPresent_${foundPid}`);
      if (el) {
        el.classList.add('audit-flash-success');
        el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }
    } else {
      // Check if already scanned present
      let isAlreadyPresent = false;
      for (let [pid, t] of state.audit.presentMap.entries()) {
        if (t.barcode && t.barcode.toLowerCase() === q.toLowerCase()) {
          isAlreadyPresent = true;
          break;
        }
      }

      if (isAlreadyPresent) {
        playAuditBeep('duplicate');
        $('#auditScanFeedback').html(`
          <span class="text-warning font-weight-bold">
            <i class="fa fa-exclamation-circle mr-1"></i> Tray <b>${esc(q)}</b> already scanned and verified present.
          </span>
        `);
      } else {
        playAuditBeep('error');
        $('#auditScanFeedback').html(`
          <span class="text-danger font-weight-bold">
            <i class="fa fa-times-circle mr-1"></i> Barcode <b>${esc(q)}</b> does not belong to this audit queue!
          </span>
        `);
      }
    }

    $('#inputAuditScanBarcode').val('').focus();
  }

  function handleFinishAuditReconcile() {
    const presentCount = state.audit.presentMap.size;
    const missingCount = state.audit.missingMap.size;
    const totalCount = state.audit.totalTrays.length;

    if (presentCount === 0 && missingCount === 0) {
      Swal.fire('Empty Audit', 'No trays were loaded in this audit session.', 'info');
      return;
    }

    const presentIds = Array.from(state.audit.presentMap.keys());
    const missingIds = Array.from(state.audit.missingMap.keys());
    const missingTrays = Array.from(state.audit.missingMap.values());
    const hasMissing = missingCount > 0;

    Swal.fire({
      width: '780px',
      showCancelButton: true,
      buttonsStyling: false,
      customClass: {
        popup: 'swal-reconcile-popup',
        confirmButton: 'btn-reconcile-confirm',
        cancelButton: 'btn-reconcile-cancel',
        actions: 'reconcile-swal-actions'
      },
      confirmButtonText: `<i class="fa fa-check-circle mr-1.5"></i> Confirm & Reconcile (${presentCount} Present / ${missingCount} Missing)`,
      cancelButtonText: `<i class="fa fa-times mr-1"></i> Cancel`,
      html: `
        <div class="reconcile-modal-wrap text-start">
          <!-- Hero Header -->
          <div class="reconcile-modal-hero">
            <div class="reconcile-hero-badge">
              <i class="fa fa-scale-balanced"></i>
            </div>
            <div class="reconcile-hero-content">
              <h3 class="reconcile-hero-title">Finish Physical Audit & Reconcile</h3>
              <p class="reconcile-hero-desc">
                Review verified counts for Bed <b>${esc(state.currentBedNo)}</b> and resolve missing trays to finalize audit.
              </p>
            </div>
          </div>

          <!-- Summary Metrics Cards Grid -->
          <div class="reconcile-stats-container">
            <div class="reconcile-stat-card card-present">
              <div class="stat-card-icon-bubble">
                <i class="fa fa-check"></i>
              </div>
              <div class="stat-card-data">
                <div class="stat-card-number-line">
                  <span class="stat-card-number">${presentCount}</span>
                  <span class="stat-card-unit">tray${presentCount === 1 ? '' : 's'}</span>
                </div>
                <div class="stat-card-label">Physically Present</div>
                <div class="stat-card-subtext">Restored to <b>previous status</b> (Polyhouse / Ready to Sale).</div>
              </div>
            </div>

            <div class="reconcile-stat-card card-missing ${hasMissing ? 'highlight-missing' : ''}">
              <div class="stat-card-icon-bubble">
                <i class="fa fa-exclamation"></i>
              </div>
              <div class="stat-card-data">
                <div class="stat-card-number-line">
                  <span class="stat-card-number">${missingCount}</span>
                  <span class="stat-card-unit">tray${missingCount === 1 ? '' : 's'}</span>
                </div>
                <div class="stat-card-label">Missing / Unscanned</div>
                <div class="stat-card-subtext">${hasMissing ? 'Requires resolution to clear bed position.' : 'All trays physically accounted for!'}</div>
              </div>
            </div>
          </div>

          ${hasMissing ? `
            <!-- Resolution Section -->
            <div class="reconcile-resolution-box">
              <div class="reconcile-question-banner">
                <span class="question-icon"><i class="fa fa-question-circle"></i></span>
                <span class="question-text">How should the <b class="missing-count-pill">${missingCount}</b> missing tray(s) be resolved?</span>
              </div>

              <!-- Segmented Tabs -->
              <div class="reconcile-tab-nav">
                <button type="button" class="reconcile-tab-btn active" id="btnModeSingleAction">
                  <i class="fa fa-layer-group"></i>
                  <span>Same Action for All (${missingCount})</span>
                </button>
                <button type="button" class="reconcile-tab-btn" id="btnModeSplitAction">
                  <i class="fa fa-code-branch"></i>
                  <span>Split / Multiple Actions</span>
                </button>
              </div>

              <!-- Panel 1: Single Action for all missing trays -->
              <div id="panelSingleAction" class="reconcile-tab-pane">
                <div class="reconcile-form-group">
                  <label class="reconcile-field-label" for="swalAuditMissingResolution">
                    <i class="fa fa-tasks text-primary mr-1"></i> Resolution Action for All ${missingCount} Missing Tray(s):
                  </label>
                  <div class="reconcile-select-wrapper">
                    <select id="swalAuditMissingResolution" class="reconcile-select-input">
                      <option value="Empty After Shifting" selected>Empty After Shifting (Vacate from Bed)</option>
                      <option value="Final Dispatch">Final Dispatch (Customer Delivery)</option>
                      <option value="Move for Grafting">Move for Grafting (Grafting Stage)</option>
                    </select>
                    <span class="reconcile-select-arrow"><i class="fa fa-chevron-down"></i></span>
                  </div>
                  <div class="reconcile-field-note">
                    <i class="fa fa-info-circle text-primary"></i>
                    <span>All <b>${missingCount} missing trays</b> will be assigned this single status and vacated from Bed <b>${esc(state.currentBedNo)}</b>.</span>
                  </div>
                </div>
              </div>

              <!-- Panel 2: Split / Multiple Actions -->
              <div id="panelSplitAction" class="reconcile-tab-pane" style="display:none;">
                <!-- Quick Split Distribution Strip -->
                <div class="reconcile-quick-split-card">
                  <div class="quick-split-top">
                    <div class="quick-split-title">
                      <i class="fa fa-sliders text-primary mr-1.5"></i> <b>Quick Quantity Split:</b>
                    </div>
                    <div id="badgeAllocationSummary" class="allocation-status-badge badge-matched">
                      <i class="fa fa-check-circle mr-1"></i> Allocated: ${missingCount} / ${missingCount}
                    </div>
                  </div>

                  <div class="quick-split-inputs-grid">
                    <div class="split-col">
                      <label class="split-col-label">Empty After Shifting</label>
                      <div class="split-input-group">
                        <input type="number" min="0" max="${missingCount}" id="inputSplitEmpty" class="split-num-input" value="${missingCount}" />
                        <span class="split-addon">trays</span>
                      </div>
                    </div>
                    <div class="split-col">
                      <label class="split-col-label">Final Dispatch</label>
                      <div class="split-input-group">
                        <input type="number" min="0" max="${missingCount}" id="inputSplitDispatch" class="split-num-input" value="0" />
                        <span class="split-addon">trays</span>
                      </div>
                    </div>
                    <div class="split-col">
                      <label class="split-col-label">Move for Grafting</label>
                      <div class="split-input-group">
                        <input type="number" min="0" max="${missingCount}" id="inputSplitGrafting" class="split-num-input" value="0" />
                        <span class="split-addon">trays</span>
                      </div>
                    </div>
                  </div>

                  <div class="quick-split-bottom-bar">
                    <span class="quick-split-helper">
                      <i class="fa fa-wand-magic-sparkles text-primary mr-1"></i> Adjust quantities above and click Auto-Distribute:
                    </span>
                    <button type="button" class="btn-auto-apply" id="btnApplySplitCounts">
                      <i class="fa fa-wand-magic-sparkles mr-1"></i> Auto-Distribute Counts
                    </button>
                  </div>
                </div>

                <!-- Scrollable Per-Tray List -->
                <div class="tray-table-card">
                  <div class="tray-table-header">
                    <div class="tray-table-title">
                      <i class="fa fa-list-check text-secondary mr-1"></i> <b>Tray-by-Tray Assignment</b> (${missingCount} trays)
                    </div>
                    <div class="tray-batch-actions">
                      <button type="button" class="btn-batch-action" id="btnQuickAllEmpty">All Empty</button>
                      <span class="action-divider">|</span>
                      <button type="button" class="btn-batch-action" id="btnQuickAllDispatch">All Dispatch</button>
                      <span class="action-divider">|</span>
                      <button type="button" class="btn-batch-action" id="btnQuickAllGrafting">All Grafting</button>
                    </div>
                  </div>

                  <div class="tray-table-scrollable">
                    <table class="tray-mini-table">
                      <thead>
                        <tr>
                          <th style="width: 36px;" class="text-center">#</th>
                          <th>Tray Barcode & Info</th>
                          <th style="width: 210px;">Assigned Action</th>
                        </tr>
                      </thead>
                      <tbody id="tbodyAuditSplitTrays">
                        ${missingTrays.map((t, idx) => `
                          <tr data-pid="${t.polyhouseplantid}">
                            <td class="text-center text-muted font-weight-bold">${idx + 1}</td>
                            <td>
                              <div class="tray-row-meta">
                                <span class="tray-barcode-pill">${esc(t.barcode)}</span>
                                <span class="tray-plants-badge">${t.actual_plants} pl</span>
                              </div>
                              <div class="tray-desc-line">
                                <span class="tray-order-text">${esc(t.stockorder_no || t.psono || '')}</span>
                                <span class="sep">&bull;</span>
                                <span class="tray-prod-text text-truncate">${esc(t.productname || '')}</span>
                              </div>
                            </td>
                            <td>
                              <div class="tray-action-select-wrap">
                                <select class="tray-action-select swal-tray-split-select" data-pid="${t.polyhouseplantid}">
                                  <option value="Empty After Shifting" selected>Empty After Shifting</option>
                                  <option value="Final Dispatch">Final Dispatch</option>
                                  <option value="Move for Grafting">Move for Grafting</option>
                                </select>
                                <i class="fa fa-chevron-down select-caret"></i>
                              </div>
                            </td>
                          </tr>
                        `).join('')}
                      </tbody>
                    </table>
                  </div>
                </div>

              </div>
            </div>
          ` : `
            <div class="alert alert-success d-flex align-items-center gap-2 py-2.5 px-3 rounded-3 mb-3" style="border: 1.5px solid #86efac; background: #f0fdf4;">
              <i class="fa fa-trophy text-success" style="font-size:1.2rem;"></i>
              <div>
                <b class="text-success">Perfect Match:</b> 100% of trays in this bed were verified present!
              </div>
            </div>
          `}

          <!-- Audit Remarks -->
          <div class="reconcile-remarks-box">
            <label class="reconcile-field-label" for="swalAuditRemark">
              <i class="fa fa-comment-dots text-secondary mr-1"></i> Audit Note / Remarks (Optional):
            </label>
            <textarea id="swalAuditRemark" class="reconcile-textarea" rows="2" placeholder="e.g. Physical audit completed; missing trays shifted without scan..."></textarea>
          </div>
        </div>
      `,
      didOpen: function(popup) {
        if (!hasMissing) return;

        const $popup = $(popup);

        function syncCountsFromTable() {
          let emptyCount = 0;
          let dispatchCount = 0;
          let graftingCount = 0;

          $popup.find('.swal-tray-split-select').each(function() {
            const val = $(this).val();
            if (val === 'Empty After Shifting') emptyCount++;
            else if (val === 'Final Dispatch') dispatchCount++;
            else if (val === 'Move for Grafting') graftingCount++;
          });

          $popup.find('#inputSplitEmpty').val(emptyCount);
          $popup.find('#inputSplitDispatch').val(dispatchCount);
          $popup.find('#inputSplitGrafting').val(graftingCount);

          const totalAllocated = emptyCount + dispatchCount + graftingCount;
          const $badge = $popup.find('#badgeAllocationSummary');
          if (totalAllocated === missingCount) {
            $badge.removeClass('bg-danger bg-warning').addClass('bg-success').html(`Allocated: ${totalAllocated} / ${missingCount} <i class="fa fa-check ml-1"></i>`);
          } else {
            $badge.removeClass('bg-success').addClass('bg-danger').html(`Allocated: ${totalAllocated} / ${missingCount} <i class="fa fa-exclamation ml-1"></i>`);
          }
        }

        // Toggle modes via segmented buttons
        $popup.find('#btnModeSingleAction').on('click', function() {
          $(this).addClass('active');
          $popup.find('#btnModeSplitAction').removeClass('active');
          $popup.find('#panelSingleAction').show();
          $popup.find('#panelSplitAction').hide();
        });

        $popup.find('#btnModeSplitAction').on('click', function() {
          $(this).addClass('active');
          $popup.find('#btnModeSingleAction').removeClass('active');
          $popup.find('#panelSingleAction').hide();
          $popup.find('#panelSplitAction').show();
          syncCountsFromTable();
        });

        // Table select change listener
        $popup.find('.swal-tray-split-select').on('change', function() {
          syncCountsFromTable();
        });

        // Quick batch buttons
        $popup.find('#btnQuickAllEmpty').on('click', function() {
          $popup.find('.swal-tray-split-select').val('Empty After Shifting');
          syncCountsFromTable();
        });
        $popup.find('#btnQuickAllDispatch').on('click', function() {
          $popup.find('.swal-tray-split-select').val('Final Dispatch');
          syncCountsFromTable();
        });
        $popup.find('#btnQuickAllGrafting').on('click', function() {
          $popup.find('.swal-tray-split-select').val('Move for Grafting');
          syncCountsFromTable();
        });

        // Apply distribution from inputs
        $popup.find('#btnApplySplitCounts').on('click', function() {
          let numEmpty = parseInt($popup.find('#inputSplitEmpty').val(), 10) || 0;
          let numDispatch = parseInt($popup.find('#inputSplitDispatch').val(), 10) || 0;
          let numGrafting = parseInt($popup.find('#inputSplitGrafting').val(), 10) || 0;

          numEmpty = Math.max(0, numEmpty);
          numDispatch = Math.max(0, numDispatch);
          numGrafting = Math.max(0, numGrafting);

          const sum = numEmpty + numDispatch + numGrafting;
          if (sum !== missingCount) {
            Swal.showValidationMessage(`The sum of counts (${sum}) must equal total missing trays (${missingCount})`);
            setTimeout(() => Swal.resetValidationMessage(), 3500);
            return;
          }

          const $selects = $popup.find('.swal-tray-split-select');
          $selects.each(function(index) {
            if (index < numEmpty) {
              $(this).val('Empty After Shifting');
            } else if (index < numEmpty + numDispatch) {
              $(this).val('Final Dispatch');
            } else {
              $(this).val('Move for Grafting');
            }
          });

          syncCountsFromTable();
        });
      },
      preConfirm: function() {
        const remark = $.trim($('#swalAuditRemark').val());

        if (!hasMissing) {
          return { isSplit: false, singleResolution: 'Empty After Shifting', groups: null, remark: remark };
        }

        const isSplitActive = $('#panelSplitAction').is(':visible');

        if (isSplitActive) {
          const groups = {
            'Empty After Shifting': [],
            'Final Dispatch': [],
            'Move for Grafting': []
          };

          $('#tbodyAuditSplitTrays tr').each(function() {
            const pid = $(this).data('pid');
            const res = $(this).find('.swal-tray-split-select').val() || 'Empty After Shifting';
            if (groups[res]) {
              groups[res].push(pid);
            } else {
              groups['Empty After Shifting'].push(pid);
            }
          });

          const totalAllocated = groups['Empty After Shifting'].length +
                                 groups['Final Dispatch'].length +
                                 groups['Move for Grafting'].length;

          if (totalAllocated !== missingCount) {
            Swal.showValidationMessage(`Please assign all ${missingCount} missing trays before proceeding (currently allocated: ${totalAllocated})`);
            return false;
          }

          return {
            isSplit: true,
            groups: groups,
            remark: remark
          };
        } else {
          const singleRes = $('#swalAuditMissingResolution').length ? $('#swalAuditMissingResolution').val() : 'Empty After Shifting';
          return {
            isSplit: false,
            singleResolution: singleRes,
            groups: null,
            remark: remark
          };
        }
      }
    }).then(function(result) {
      if (result.isConfirmed && result.value) {
        const payload = {
          action: 'reconcile_physical_audit',
          present_ids: presentIds,
          missing_ids: missingIds,
          order_no: state.audit.orderNo,
          polyhouseid: state.currentPolyhouseId,
          bed_no: state.currentBedNo,
          remark: result.value.remark
        };

        if (result.value.isSplit && result.value.groups) {
          payload.missing_groups = JSON.stringify(result.value.groups);
        } else {
          payload.missing_resolution = result.value.singleResolution || 'Empty After Shifting';
        }

        executeApiPost(payload, 'Reconciling physical audit...');
        window.closeAuditModal();
      }
    });
  }

  function toggleAuditCamera() {
    if (state.audit.isCameraRunning) {
      stopAuditCamera();
    } else {
      startAuditCamera();
    }
  }

  function startAuditCamera() {
    if (state.audit.cameraScanner) {
      stopAuditCamera();
    }

    $('#auditCameraWrapper').show();
    state.audit.cameraScanner = new Html5Qrcode('auditQrCameraViewport');
    const config = { fps: 12, qrbox: { width: 250, height: 250 } };

    state.audit.cameraScanner.start(
      { facingMode: state.audit.cameraFacingMode },
      config,
      function onScanSuccess(decodedText) {
        if (decodedText) {
          handleAuditBarcodeScan(decodedText);
        }
      },
      function onScanFailure() {}
    ).then(() => {
      state.audit.isCameraRunning = true;
      $('#btnToggleAuditCamera').html('<i class="fa fa-stop mr-1"></i> Stop Camera').removeClass('btn-outline-secondary').addClass('btn-danger');
    }).catch(err => {
      Swal.fire('Camera Error', 'Could not start camera: ' + err, 'error');
    });
  }

  function stopAuditCamera() {
    if (state.audit.cameraScanner && state.audit.isCameraRunning) {
      state.audit.cameraScanner.stop().then(() => {
        state.audit.isCameraRunning = false;
        $('#auditCameraWrapper').hide();
        $('#btnToggleAuditCamera').html('<i class="fa fa-camera mr-1"></i> Use Camera').removeClass('btn-danger').addClass('btn-outline-secondary');
      }).catch(err => {
        console.warn('Error stopping audit camera:', err);
      });
    }
  }

  // -------------------------------------------------------------
  // 11. Camera Scanner Support
  // -------------------------------------------------------------
  function initScannerTabs() {
    $('#tabManual').on('click', function() {
      $('.scanner-tab-btn').removeClass('active');
      $(this).addClass('active');
      $('#sectionManual').show();
      $('#sectionCamera').hide();
      stopCameraScanner();
    });

    $('#tabCamera').on('click', function() {
      $('.scanner-tab-btn').removeClass('active');
      $(this).addClass('active');
      $('#sectionManual').hide();
      $('#sectionCamera').show();
    });
  }

  function toggleCameraScanner() {
    if (state.isCameraRunning) {
      stopCameraScanner();
    } else {
      startCameraScanner();
    }
  }

  function startCameraScanner() {
    if (state.html5QrScanner) {
      stopCameraScanner();
    }

    state.html5QrScanner = new Html5Qrcode('qrCameraViewport');
    const config = { fps: 10, qrbox: { width: 250, height: 250 } };

    state.html5QrScanner.start(
      { facingMode: state.cameraFacingMode },
      config,
      function onScanSuccess(decodedText) {
        if (decodedText) {
          stopCameraScanner();
          $('#inputBarcodeQuery').val(decodedText);
          triggerScanLookup();
        }
      },
      function onScanFailure() {
        // Ignored frame failures
      }
    ).then(() => {
      state.isCameraRunning = true;
      $('#scannerLaserLine').show();
      $('#btnToggleCamera').html('<i class="fa fa-stop mr-1"></i> Stop Camera').removeClass('btn-primary').addClass('btn-danger');
    }).catch(err => {
      Swal.fire('Camera Error', 'Could not start camera: ' + err, 'error');
    });
  }

  function stopCameraScanner() {
    if (state.html5QrScanner && state.isCameraRunning) {
      state.html5QrScanner.stop().then(() => {
        state.isCameraRunning = false;
        $('#scannerLaserLine').hide();
        $('#btnToggleCamera').html('<i class="fa fa-play mr-1"></i> Start Camera').removeClass('btn-danger').addClass('btn-primary');
      }).catch(err => {
        console.warn('Error stopping scanner:', err);
      });
    }
  }

  function switchCameraFacing() {
    state.cameraFacingMode = (state.cameraFacingMode === 'environment') ? 'user' : 'environment';
    if (state.isCameraRunning) {
      stopCameraScanner();
      setTimeout(startCameraScanner, 300);
    }
  }

  // -------------------------------------------------------------
  // Helpers
  // -------------------------------------------------------------
  function esc(str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/[&<>"']/g, function(s) {
      return ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
      })[s];
    });
  }

  window.clearInput = function() {
    $('#inputBarcodeQuery').val('').focus();
  };

})(jQuery);
