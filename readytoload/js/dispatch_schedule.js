/**
 * Dispatch Schedule by Client & Date - Frontend Logic
 * Path: /readytoload/js/dispatch_schedule.js
 */

(function ($) {
  'use strict';

  let currentScope = 'current_week';       // Default as requested: Current Week
  let currentStatusFilter = 'readytoload'; // Default as requested: Ready to Load
  let locationsData = [];                  // Cached locations and polyhouses

  function esc(str) {
    if (str === null || str === undefined) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function getStatusBadge(status, actionstatus) {
    const st = (status || '').toLowerCase().trim();
    const act = (actionstatus || '').toLowerCase().trim();

    // 1. Ready to Dispatch
    if (st === 'readytodispatch' || act === 'readytodispatch' || st === 'ready to dispatch' || act === 'ready to dispatch') {
      return '<span class="item-status-pill st-readytodispatch"><i class="fa fa-truck-fast mr-1"></i> Ready To Dispatch</span>';
    }

    // 2. Dispatched / Final Dispatch / Full Dispatch
    if (st === 'dispatched' || act === 'dispatched') {
      return '<span class="item-status-pill st-dispatched"><i class="fa fa-check-double mr-1"></i> Dispatched</span>';
    }
    if (st === 'fulldispatch' || act === 'fulldispatch') {
      return '<span class="item-status-pill st-dispatched"><i class="fa fa-check-double mr-1"></i> Full Dispatch</span>';
    }
    if (st === 'final dispatch' || act === 'final dispatch') {
      return '<span class="item-status-pill st-final-dispatch"><i class="fa fa-truck mr-1"></i> Final Dispatch</span>';
    }

    // 3. Loaded / Partially Loaded
    if (act === 'loaded' || st === 'loaded') {
      return '<span class="item-status-pill st-loaded"><i class="fa fa-check-circle mr-1"></i> Loaded</span>';
    }
    if (act === 'partially loaded' || act === 'partial') {
      return '<span class="item-status-pill st-partially-loaded"><i class="fa fa-truck-loading mr-1"></i> Partially Loaded</span>';
    }

    // 4. Ready To Load
    if (st === 'readytoload' || st === 'ready to load' || act === 'readytoload' || act === 'ready to load') {
      return '<span class="item-status-pill st-readytoload"><i class="fa fa-clock mr-1"></i> Ready To Load</span>';
    }

    // 5. Allocated
    if (st === 'allocated' || act === 'allocated') {
      return '<span class="item-status-pill st-allocated"><i class="fa fa-tasks mr-1"></i> Allocated</span>';
    }

    return `<span class="item-status-pill st-default">${esc(status || 'Pending')}</span>`;
  }

  // -------------------------------------------------------------
  // Polyhouse Dropdown Population Helper
  // -------------------------------------------------------------
  function populatePolyhouseDropdown(locationId, selectedPhId) {
    const $ph = $('#filterPolyhouse');
    if (!locationId) {
      $ph.html('<option value="">-- Choose Location First --</option>').prop('disabled', true);
      return;
    }

    const loc = locationsData.find(l => String(l.location_id) === String(locationId));
    if (!loc || !loc.polyhouses || loc.polyhouses.length === 0) {
      $ph.html('<option value="">No polyhouses found</option>').prop('disabled', true);
      return;
    }

    let html = '<option value="">-- Choose Polyhouse --</option>';
    html += `<option value="all">All Polyhouses (${esc(loc.location_name)})</option>`;
    loc.polyhouses.forEach(function (p) {
      html += `<option value="${p.polyhouse_id}">${esc(p.polyhouse_name)}</option>`;
    });

    $ph.html(html).prop('disabled', false);
    if (selectedPhId !== undefined && selectedPhId !== null && selectedPhId !== '') {
      $ph.val(selectedPhId);
    }
  }

  // -------------------------------------------------------------
  // Location & Polyhouse Event Handlers
  // -------------------------------------------------------------
  window.onLocationChange = function () {
    const locId = $('#filterLocation').val() || '';
    if (!locId) {
      localStorage.removeItem('dispatch_sched_loc');
      localStorage.removeItem('dispatch_sched_ph');
      populatePolyhouseDropdown('');
      loadDispatchSchedule();
      return;
    }

    localStorage.setItem('dispatch_sched_loc', locId);
    localStorage.removeItem('dispatch_sched_ph');
    populatePolyhouseDropdown(locId);
    loadDispatchSchedule();
  };

  window.onPolyhouseChange = function () {
    const phId = $('#filterPolyhouse').val() || '';
    if (phId) {
      localStorage.setItem('dispatch_sched_ph', phId);
    } else {
      localStorage.removeItem('dispatch_sched_ph');
    }
    loadDispatchSchedule();
  };

  // -------------------------------------------------------------
  // Fetch Locations & Polyhouses from Backend
  // -------------------------------------------------------------
  function loadLocationsAndPolyhouses(callback) {
    $.ajax({
      url: 'model/dispatch_schedule_model.php',
      type: 'GET',
      dataType: 'json',
      data: { action: 'get_locations_and_polyhouses' },
      success: function (res) {
        if (res && res.status === 'success') {
          locationsData = res.locations || [];
          const $loc = $('#filterLocation');
          let html = '<option value="">-- Choose Location --</option>';
          locationsData.forEach(function (l) {
            html += `<option value="${esc(l.location_id)}">${esc(l.location_name)}</option>`;
          });
          $loc.html(html);

          // Restore saved selection from localStorage if present
          const savedLoc = localStorage.getItem('dispatch_sched_loc');
          const savedPh = localStorage.getItem('dispatch_sched_ph');
          if (savedLoc && locationsData.some(l => String(l.location_id) === String(savedLoc))) {
            $loc.val(savedLoc);
            populatePolyhouseDropdown(savedLoc, savedPh);
          }
        }
        if (typeof callback === 'function') {
          callback();
        }
      },
      error: function () {
        if (typeof callback === 'function') {
          callback();
        }
      }
    });
  }

  // -------------------------------------------------------------
  // 1. Set Status Filter (Summary Card Click)
  // -------------------------------------------------------------
  window.setStatusFilter = function (status) {
    currentStatusFilter = status || 'all';

    // Update active class on summary cards
    $('.summary-card').removeClass('active');
    $(`.summary-card[data-status="${currentStatusFilter}"]`).addClass('active');

    loadDispatchSchedule();
  };

  // -------------------------------------------------------------
  // 2. Load Dispatch Schedule List
  // -------------------------------------------------------------
  window.loadDispatchSchedule = function () {
    const $container = $('#scheduleContainer');
    const searchVal = $.trim($('#filterSearch').val() || '');
    const fromDate = $('#filterFromDate').val() || '';
    const toDate = $('#filterToDate').val() || '';
    const locationId = $('#filterLocation').val() || '';
    const polyhouseId = $('#filterPolyhouse').val() || '';

    $container.html(`
      <div class="simple-loader-box">
        <div class="simple-loader-spinner"></div>
        <div class="simple-loader-title">Loading Dispatch Schedule...</div>
        <div class="simple-loader-sub">Please wait while allocated orders are fetched</div>
      </div>
    `);

    $.ajax({
      url: 'model/dispatch_schedule_model.php',
      type: 'GET',
      dataType: 'json',
      data: {
        action: 'get_dispatch_schedule',
        search: searchVal,
        from_date: fromDate,
        to_date: toDate,
        scope: currentScope,
        status: currentStatusFilter,
        location_id: locationId,
        polyhouse_id: polyhouseId
      },
      success: function (res) {
        // If user has not chosen Location or Polyhouse yet, show clean selection prompt
        if (res.status === 'prompt_selection') {
          $('#sumAllOrders, #sumReadyToLoadOrders, #sumReadyToDispatchOrders, #sumDispatchedOrders').text('0');
          $('#sumAllPlants, #sumReadyToLoadPlants, #sumReadyToDispatchPlants, #sumDispatchedPlants').text('0');
          $('#sumAllTrays, #sumReadyToLoadTrays, #sumReadyToDispatchTrays, #sumDispatchedTrays').text('0');
          $('#sumAllClients, #sumReadyToLoadClients, #sumReadyToDispatchClients, #sumDispatchedClients').text('0');
          $('#activeFilterNotice').stop(true, true).hide();
          $('#heroScopeSubtitle').text('Choose Location & Polyhouse to load dispatches');

          $container.html(`
            <div class="empty-schedule-card select-prompt-box">
              <div class="empty-schedule-icon"><i class="fa fa-map-marked-alt"></i></div>
              <h4>Select Location &amp; Polyhouse</h4>
              <p>Please choose a <strong>Location</strong> and <strong>Polyhouse</strong> from the filter bar above to display the allocated dispatch schedule.</p>
            </div>
          `);
          return;
        }

        if (res.status !== 'success') {
          $container.html(`
            <div class="alert alert-danger m-3">
              <i class="fa fa-exclamation-circle mr-2"></i> ${esc(res.message || 'Error loading data.')}
            </div>
          `);
          return;
        }

        // 1. Update Summary Cards with Live Metrics
        const s = res.summary || {};

        // All Allocated
        const sAll = s.all || {};
        $('#sumAllOrders').text(Number(sAll.total_orders || 0).toLocaleString());
        $('#sumAllPlants').text(Number(sAll.total_plants || 0).toLocaleString());
        $('#sumAllTrays').text(Number(sAll.total_trays || 0).toLocaleString());
        $('#sumAllClients').text(Number(sAll.total_clients || 0).toLocaleString());

        // Ready to Load (allocated, loaded, readytoload)
        const sRtl = s.readytoload || {};
        $('#sumReadyToLoadOrders').text(Number(sRtl.total_orders || 0).toLocaleString());
        $('#sumReadyToLoadPlants').text(Number(sRtl.total_plants || 0).toLocaleString());
        $('#sumReadyToLoadTrays').text(Number(sRtl.total_trays || 0).toLocaleString());
        $('#sumReadyToLoadClients').text(Number(sRtl.total_clients || 0).toLocaleString());

        // Ready to Dispatch (readytodispatch)
        const sRtd = s.readytodispatch || {};
        $('#sumReadyToDispatchOrders').text(Number(sRtd.total_orders || 0).toLocaleString());
        $('#sumReadyToDispatchPlants').text(Number(sRtd.total_plants || 0).toLocaleString());
        $('#sumReadyToDispatchTrays').text(Number(sRtd.total_trays || 0).toLocaleString());
        $('#sumReadyToDispatchClients').text(Number(sRtd.total_clients || 0).toLocaleString());

        // Dispatched
        const sDisp = s.dispatched || {};
        $('#sumDispatchedOrders').text(Number(sDisp.total_orders || 0).toLocaleString());
        $('#sumDispatchedPlants').text(Number(sDisp.total_plants || 0).toLocaleString());
        $('#sumDispatchedTrays').text(Number(sDisp.total_trays || 0).toLocaleString());
        $('#sumDispatchedClients').text(Number(sDisp.total_clients || 0).toLocaleString());

        // Ensure active card is styled
        $('.summary-card').removeClass('active');
        $(`.summary-card[data-status="${currentStatusFilter}"]`).addClass('active');

        // 2. Update Active Filter Notice Bar (Shows when filtering by non-default status)
        const $notice = $('#activeFilterNotice');
        if (currentStatusFilter && currentStatusFilter !== 'all' && currentStatusFilter !== 'readytoload') {
          let labelText = '';
          let countVal = 0;
          if (currentStatusFilter === 'readytodispatch') {
            labelText = 'Ready to Dispatch Order (Loaded / ReadyToDispatch)';
            countVal = sRtd.total_orders || 0;
          } else if (currentStatusFilter === 'dispatched') {
            labelText = 'Dispatched Order';
            countVal = sDisp.total_orders || 0;
          }
          $('#activeFilterName').text(labelText);
          $('#activeFilterCountBadge').text(`${countVal} ${countVal === 1 ? 'Order' : 'Orders'}`);
          $notice.stop(true, true).fadeIn(150);
        } else {
          $notice.stop(true, true).fadeOut(150);
        }

        // 3. Update Hero Subtitle based on scope / dates
        const m = res.metrics || {};
        let subText = 'Current Week Allocated Orders';
        if (m.week_range && m.week_range.label && currentScope === 'current_week') {
          subText = `Current Week: ${m.week_range.label}`;
        } else if (currentScope === 'today') {
          subText = `Today's Schedule: ${m.current_date || 'Today'}`;
        } else if (currentScope === 'upcoming') {
          subText = 'Upcoming Dispatches';
        } else if (currentScope === 'present_to_past') {
          subText = 'Present Date to Past Overview';
        } else if (fromDate || toDate) {
          subText = `Custom Date Range: ${fromDate || 'Start'} to ${toDate || 'End'}`;
        } else if (currentScope === 'all') {
          subText = 'All Dates Overview';
        }
        $('#heroScopeSubtitle').text(subText);

        // 4. Render Grouped Schedule Records
        const groups = res.data || [];
        if (groups.length === 0) {
          let emptyTitle = 'No Dispatch Records Found';
          let emptyDesc = 'No client dispatches matching your current scope, search, or status filter.';
          if (currentStatusFilter && currentStatusFilter !== 'all') {
            emptyDesc = `No orders with status matching "${esc(currentStatusFilter)}" found in this date scope.`;
          }

          $container.html(`
            <div class="empty-schedule-card">
              <div class="empty-schedule-icon"><i class="fa fa-calendar-times"></i></div>
              <h4>${emptyTitle}</h4>
              <p>${emptyDesc}</p>
              <div class="mt-3 d-flex justify-content-center" style="gap: 0.5rem;">
                ${currentStatusFilter !== 'all' ? `
                  <button type="button" class="btn btn-sm btn-outline-primary" onclick="setStatusFilter('all');">
                    <i class="fa fa-eye mr-1"></i> View All Statuses
                  </button>
                ` : ''}
                <button type="button" class="btn btn-sm btn-primary" onclick="resetFilters();">
                  <i class="fa fa-sync-alt mr-1"></i> Reset Filters
                </button>
              </div>
            </div>
          `);
          return;
        }

        let html = '';
        groups.forEach(function (grp) {
          const clientName = grp.client_name || 'Unknown Client';
          const clientId = grp.client_id || '-';
          const phone = grp.client_phone || '';
          const location = [grp.district, grp.state].filter(x => x && x !== '-').join(', ');

          html += `
            <div class="client-schedule-box">
              <!-- 1. Box Header: Date + Client + Group KPIs -->
              <div class="schedule-box-header">
                <div class="header-left-meta">
                  <!-- Date Badge & Relative Tag Row -->
                  <div class="header-date-row">
                    <div class="dispatch-date-badge" title="Dispatch / Delivery Date">
                      <i class="fa fa-calendar-day date-icon"></i>
                      <div class="date-text-col">
                        <span class="date-main">${esc(grp.delivery_date_display)}</span>
                        <span class="date-sub">${esc(grp.day_of_week)}</span>
                      </div>
                    </div>
                    <span class="rel-pill ${grp.relative_class || 'rel-past'}">${esc(grp.relative_label)}</span>
                  </div>

                  <!-- Client Card -->
                  <div class="client-info-card">
                    <div class="client-avatar"><i class="fa fa-user"></i></div>
                    <div class="client-details-wrap">
                      <div class="d-flex align-items-center flex-wrap" style="gap: 0.4rem;">
                        <span class="client-name-bold">${esc(clientName)}</span>
                        <span class="client-id-tag">${esc(clientId)}</span>
                      </div>
                      <div class="d-flex align-items-center flex-wrap mt-1" style="gap: 0.4rem;">
                        ${phone ? `
                          <a href="tel:${esc(phone)}" class="client-phone-btn" title="Call Client">
                            <i class="fa fa-phone-alt"></i> ${esc(phone)}
                          </a>
                        ` : ''}
                        ${location ? `
                          <span class="client-loc-tag" title="Delivery Location">
                            <i class="fa fa-map-marker-alt text-danger"></i> ${esc(location)}
                          </span>
                        ` : ''}
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Right Summary Chips -->
                <div class="header-right-summary">
                  <div class="summary-stat-pill pill-orders" title="Total Provisional Orders">
                    <span>Orders:</span>
                    <strong>${grp.total_orders || 1}</strong>
                  </div>
                  <div class="summary-stat-pill pill-trays" title="Total Order Trays">
                    <span>Trays:</span>
                    <strong>${grp.total_order_trays || 0}</strong>
                  </div>
                  <div class="summary-stat-pill pill-plants" title="Total Order Plants">
                    <span>Plants:</span>
                    <strong>${Number(grp.total_order_plants || 0).toLocaleString()}</strong>
                  </div>
                  ${grp.total_allocated_plants > 0 ? `
                    <div class="summary-stat-pill pill-alloc" title="Allocated Plants">
                      <span>Alloc:</span>
                      <strong class="text-purple">${Number(grp.total_allocated_plants).toLocaleString()} (${grp.total_allocated_trays} Trays)</strong>
                    </div>
                  ` : ''}
                  ${grp.total_ready_to_sell_plants > 0 ? `
                    <div class="summary-stat-pill pill-rts" title="Ready to Sell Plants">
                      <span>Ready:</span>
                      <strong class="text-emerald">${Number(grp.total_ready_to_sell_plants).toLocaleString()} (${grp.total_ready_to_sell_trays} Trays)</strong>
                    </div>
                  ` : ''}
                </div>
              </div>

              <!-- 2. Crops Strip Items List -->
              <div class="schedule-crops-list">
          `;

          grp.items.forEach(function (item, idx) {
            let bedTags = '';
            if (item.active_beds && item.active_beds.length > 0) {
              bedTags = item.active_beds.map(b => `
                <span class="bed-tag-pill" title="Location: ${esc(b.polyhouse)} - ${esc(b.bed_no)} (${b.tray_count} Trays)">
                  <i class="fa fa-warehouse"></i> ${esc(b.polyhouse)} &bull; ${esc(b.bed_no)} (${b.tray_count})
                </span>
              `).join(' ');
            }

            // Shifting Tag
            let shiftTag = '';
            if (item.isshifted === 'yes') {
              const shiftText = item.shiftingqty > 0 ? ` (${Number(item.shiftingqty).toLocaleString()})` : '';
              shiftTag = `<span class="shift-tag shift-yes" title="Shifted"><i class="fa fa-exchange-alt mr-1"></i>Shifted${shiftText}</span>`;
            } else {
              shiftTag = `<span class="shift-tag shift-no" title="Not Shifted"><i class="fa fa-ban mr-1"></i>Not Shifted</span>`;
            }

            // Health Tags
            let healthTags = '';
            if (item.healthyplant !== null && item.healthyplant !== undefined) {
              healthTags += `<span class="health-tag health-good" title="Healthy Plants"><i class="fa fa-heart text-danger mr-1"></i>Healthy: ${Number(item.healthyplant).toLocaleString()}</span>`;
            }
            if (item.unhealthyplant !== null && item.unhealthyplant !== undefined && item.unhealthyplant > 0) {
              healthTags += `<span class="health-tag health-bad" title="Unhealthy Plants"><i class="fa fa-biohazard mr-1"></i>Unhealthy: ${Number(item.unhealthyplant).toLocaleString()}</span>`;
            }

            const statusBadge = getStatusBadge(item.status, item.actionstatus);

            html += `
              <div class="schedule-strip-row">
                <!-- Left: Index + Crop Details + Meta Tags -->
                <div class="strip-left-col">
                  <div class="strip-idx">${idx + 1}</div>
                  <div class="strip-crop-info">
                    <div class="crop-name-title">${esc(item.product_name)}</div>
                    <div class="crop-tags-row">
                      <span class="pso-tag-pill" title="Provisional Sales Order">${esc(item.psono || 'No PSO')}</span>
                      ${item.sono ? `<span class="so-tag-pill" title="Confirmed Sales Order">SO: ${esc(item.sono)}</span>` : ''}
                      ${item.stockorder_no && item.stockorder_no !== '-' ? `<span class="stock-tag-pill" title="Stock Order No"><i class="fa fa-barcode mr-1"></i>${esc(item.stockorder_no)}</span>` : ''}
                      ${bedTags}
                      ${shiftTag}
                      ${healthTags}
                    </div>
                  </div>
                </div>

                <!-- Right: Quantities, Status & Action -->
                <div class="strip-right-col">
                  <div class="qty-boxes-wrap">
                    <div class="qty-info-box">
                      <span class="qty-info-label">Order Qty</span>
                      <span class="qty-info-val">${item.est_trays} <small>Trays (${Number(item.quantity).toLocaleString()})</small></span>
                    </div>

                    <div class="qty-info-box">
                      <span class="qty-info-label">Allocated</span>
                      <span class="qty-info-val text-purple">${item.allocated_trays || 0} <small>Trays (${Number(item.allocated_qty || 0).toLocaleString()})</small></span>
                    </div>

                    <div class="qty-info-box">
                      <span class="qty-info-label">Ready To Sell</span>
                      <span class="qty-info-val text-emerald">${item.ready_to_sell_trays || 0} <small>Trays (${Number(item.ready_to_sell_qty || 0).toLocaleString()})</small></span>
                    </div>
                  </div>

                  <div class="strip-status-wrap">
                    ${statusBadge}
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
      },
      error: function (xhr, status, err) {
        $container.html(`
          <div class="alert alert-danger m-3">
            <i class="fa fa-exclamation-triangle mr-2"></i> Server communication error: ${esc(err)}
          </div>
        `);
      }
    });
  };

  // -------------------------------------------------------------
  // 3. Scope Filter Switcher (Date View)
  // -------------------------------------------------------------
  window.setScope = function (scope) {
    currentScope = scope;
    $('.scope-pill-btn').removeClass('active');
    $(`.scope-pill-btn[data-scope="${scope}"]`).addClass('active');

    // Clear custom date inputs if picking standard preset
    if (scope !== 'custom') {
      $('#filterFromDate').val('');
      $('#filterToDate').val('');
    }
    loadDispatchSchedule();
  };

  // -------------------------------------------------------------
  // 4. Reset Filters
  // -------------------------------------------------------------
  window.resetFilters = function () {
    $('#filterSearch').val('');
    $('#filterFromDate').val('');
    $('#filterToDate').val('');
    $('#filterLocation').val('');
    $('#filterPolyhouse').html('<option value="">-- Choose Location First --</option>').prop('disabled', true);
    localStorage.removeItem('dispatch_sched_loc');
    localStorage.removeItem('dispatch_sched_ph');

    currentStatusFilter = 'readytoload';
    $('.summary-card').removeClass('active');
    $('.summary-card[data-status="readytoload"]').addClass('active');
    setScope('current_week');
  };

  // -------------------------------------------------------------
  // 5. Initialization
  // -------------------------------------------------------------
  $(document).ready(function () {
    loadLocationsAndPolyhouses(function () {
      loadDispatchSchedule();
    });

    // Search on enter key
    $('#filterSearch').on('keypress', function (e) {
      if (e.which === 13) {
        e.preventDefault();
        loadDispatchSchedule();
      }
    });

    // Keyboard accessibility for summary cards
    $(document).on('keydown', '.summary-card', function (e) {
      if (e.which === 13 || e.which === 32) {
        e.preventDefault();
        $(this).trigger('click');
      }
    });
  });

})(jQuery);
