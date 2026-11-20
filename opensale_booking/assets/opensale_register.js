/**
 * Open Sale Register & Polyhouse Return Management Scripts
 * Path: /opensale_booking/assets/opensale_register.js
 */

(function ($) {
  'use strict';

  let currentPage = 1;
  const pageLimit = 50;
  let selectedTraysMap = new Map(); // barcode -> trayObject
  let polyhouseCache = [];

  // Audio Notifications
  function playSuccessSound() {
    try {
      const ctx = new (window.AudioContext || window.webkitAudioContext)();
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.type = 'sine';
      osc.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
      osc.frequency.setValueAtTime(880, ctx.currentTime + 0.1); // A5
      gain.gain.setValueAtTime(0.12, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.35);
      osc.start();
      osc.stop(ctx.currentTime + 0.35);
    } catch (e) {}
  }

  function playWarningSound() {
    try {
      const ctx = new (window.AudioContext || window.webkitAudioContext)();
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.type = 'triangle';
      osc.frequency.setValueAtTime(329.63, ctx.currentTime); // E4
      osc.frequency.setValueAtTime(220, ctx.currentTime + 0.15); // A3
      gain.gain.setValueAtTime(0.18, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.4);
      osc.start();
      osc.stop(ctx.currentTime + 0.4);
    } catch (e) {}
  }

  function esc(str) {
    if (str === null || str === undefined) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  // -------------------------------------------------------------
  // 1. Fetch & Update KPI Metrics
  // -------------------------------------------------------------
  function loadKPIs() {
    const loc = $('#filterLocation').val();
    const df = $('#filterDateFrom').val();
    const dt = $('#filterDateTo').val();

    $.ajax({
      url: 'ajax/opensale_register_api.php',
      type: 'GET',
      dataType: 'json',
      data: {
        action: 'get_kpis',
        location: loc,
        date_from: df,
        date_to: dt
      },
      success: function (res) {
        if (res.status === 'success' && res.data) {
          const d = res.data;
          $('#kpiActiveTrays').text(Number(d.active_trays || 0).toLocaleString());
          $('#kpiActivePlants').text(Number(d.active_plants || 0).toLocaleString());
          $('#kpiTotalMovedTrays').text(Number(d.total_moved_trays || 0).toLocaleString());
          $('#kpiReturnedTrays').text(Number(d.returned_trays || 0).toLocaleString());
          $('#kpiSoldTrays').text(Number(d.sold_trays || 0).toLocaleString());

          // Center breakdown pills
          $('#badgeKarjeeStats').text(`${Number(d.karjee_trays || 0)} Trays (${Number(d.karjee_plants || 0).toLocaleString()} Plt)`);
          $('#badgeSargawaStats').text(`${Number(d.sargawa_trays || 0)} Trays (${Number(d.sargawa_plants || 0).toLocaleString()} Plt)`);
          $('#badgeMansoonStats').text(`${Number(d.mansoon_trays || 0)} Trays (${Number(d.mansoon_plants || 0).toLocaleString()} Plt)`);

          // Review Alert Badge
          const returnDueCount = (d.return_review_trays || 0) + (d.overdue_trays || 0);
          if (returnDueCount > 0) {
            $('#kpiDueBadge').text(`${returnDueCount} Trays Due for Return (2-3+ days)`).show();
          } else {
            $('#kpiDueBadge').hide();
          }
        }
      }
    });
  }

  // -------------------------------------------------------------
  // 2. Fetch & Render Register Trays Table
  // -------------------------------------------------------------
  function loadTraysTable(page) {
    currentPage = page || 1;
    const search = $.trim($('#filterSearch').val());
    const loc = $('#filterLocation').val();
    const status = $('#filterStatus').val();
    const daysFilter = $('#filterDays').val();
    const df = $('#filterDateFrom').val();
    const dt = $('#filterDateTo').val();

    $('#tblTraysBody').html(`
      <tr>
        <td colspan="12" style="text-align: center; padding: 2.5rem 1rem; color: #64748b;">
          <i class="fa fa-spinner fa-spin fa-2x text-primary mb-2"></i><br>
          <strong>Loading Open Sale Trays...</strong>
        </td>
      </tr>
    `);

    $.ajax({
      url: 'ajax/opensale_register_api.php',
      type: 'GET',
      dataType: 'json',
      data: {
        action: 'get_trays',
        search: search,
        location: loc,
        status: status,
        days_filter: daysFilter,
        date_from: df,
        date_to: dt,
        page: currentPage,
        limit: pageLimit
      },
      success: function (res) {
        if (res.status === 'success') {
          renderTraysTable(res.data || []);
          renderPagination(res.total_count || 0, currentPage, pageLimit);
          $('#totalRecordsCount').text(Number(res.total_count || 0).toLocaleString());
        } else {
          $('#tblTraysBody').html(`
            <tr>
              <td colspan="12" style="text-align:center; padding: 2rem; color:#dc2626;">
                <i class="fa fa-triangle-exclamation mr-1"></i> Failed to load trays: ${esc(res.message || 'Error')}
              </td>
            </tr>
          `);
        }
      },
      error: function (xhr, status, err) {
        $('#tblTraysBody').html(`
          <tr>
            <td colspan="12" style="text-align:center; padding: 2rem; color:#dc2626;">
              <i class="fa fa-wifi mr-1"></i> Server Connection Error: ${esc(err)}
            </td>
          </tr>
        `);
      }
    });
  }

  function renderTraysTable(items) {
    if (!items || items.length === 0) {
      $('#tblTraysBody').html(`
        <tr>
          <td colspan="12" style="text-align: center; padding: 3rem 1rem; color: #94a3b8;">
            <i class="fa fa-box-open fa-3x mb-2" style="opacity: 0.35;"></i>
            <h4 style="font-size: 1.1rem; font-weight: 700; color: #334155; margin: 0 0 0.35rem 0;">No Open Sale Trays Found</h4>
            <p style="font-size: 0.85rem; margin: 0;">No records matched your search query or filter criteria.</p>
          </td>
        </tr>
      `);
      return;
    }

    let html = '';
    const startIndex = (currentPage - 1) * pageLimit;

    items.forEach((item, idx) => {
      const isChecked = selectedTraysMap.has(item.barcode);
      const isAvailable = (item.status === 'OpenSale');
      const rowNum = startIndex + idx + 1;

      // Center Badge
      let centerBadgeClass = 'badge-center-karjee';
      if (item.resolved_center_name.includes('Sargwan') || item.resolved_center_name.includes('Sargawa')) {
        centerBadgeClass = 'badge-center-sargawa';
      } else if (item.resolved_center_name.includes('Mansoon')) {
        centerBadgeClass = 'badge-center-mansoon';
      }

      // Status Badge
      let statusBadge = '';
      if (item.status === 'OpenSale') {
        statusBadge = `<span class="badge-status badge-status-opensale"><i class="fa fa-circle-check"></i> In Open Sale</span>`;
      } else if (item.status === 'Returned') {
        statusBadge = `<span class="badge-status badge-status-returned" title="${esc(item.remarks || 'Returned')}"><i class="fa fa-rotate-left"></i> Returned to PH</span>`;
      } else if (item.status === 'Sold') {
        statusBadge = `<span class="badge-status badge-status-sold"><i class="fa fa-receipt"></i> Sold</span>`;
      } else {
        statusBadge = `<span class="badge-status badge-status-sold">${esc(item.status)}</span>`;
      }

      // Origin details
      const originPh = item.from_polyhousename || (item.from_polyhouseid ? ('PH-' + item.from_polyhouseid) : '-');
      const originBed = item.from_bed_no || '-';

      // Action Button
      let actionBtn = '';
      if (isAvailable) {
        actionBtn = `
          <button type="button" class="btn-return-action" data-barcode="${esc(item.barcode)}" title="Return this tray back to polyhouse">
            <i class="fa fa-rotate-left"></i> Return to PH
          </button>
        `;
      } else if (item.status === 'Returned') {
        actionBtn = `<span class="text-muted" style="font-size:0.75rem;"><i class="fa fa-check-double text-primary mr-1"></i>Returned</span>`;
      } else {
        actionBtn = `<span class="text-muted" style="font-size:0.75rem;"><i class="fa fa-ban text-muted mr-1"></i>Closed</span>`;
      }

      html += `
        <tr class="${isChecked ? 'row-selected' : ''}" data-barcode="${esc(item.barcode)}">
          <td class="text-center">
            ${isAvailable ? `
              <input type="checkbox" class="cb-tray-row" data-barcode="${esc(item.barcode)}" ${isChecked ? 'checked' : ''} style="cursor: pointer; width: 16px; height: 16px; accent-color: var(--osr-primary);">
            ` : `
              <input type="checkbox" disabled style="opacity: 0.25; width: 16px; height: 16px;">
            `}
          </td>
          <td class="text-center font-weight-bold" style="color: #64748b;">${rowNum}</td>
          <td>
            <div class="badge-barcode">
              <i class="fa fa-barcode text-muted"></i>
              <span>${esc(item.barcode)}</span>
              <a href="javascript:void(0)" onclick="navigator.clipboard.writeText('${esc(item.barcode)}'); Swal.fire({toast:true, position:'top-end', icon:'success', title:'Copied!', timer:1000, showConfirmButton:false}); event.stopPropagation();" title="Copy barcode" style="color:#94a3b8; margin-left:2px;">
                <i class="fa fa-copy"></i>
              </a>
            </div>
          </td>
          <td>
            <div style="font-weight: 700; color: #0f172a;">${esc(item.productname || '-')}</div>
            ${item.variety ? `<div style="font-size: 0.74rem; color: #64748b;">${esc(item.variety)}</div>` : ''}
          </td>
          <td class="text-right">
            <strong class="text-success" style="font-size: 0.95rem;">${Number(item.actual_plant_count || item.qtypertray || 0).toLocaleString()}</strong>
            <span class="text-muted" style="font-size: 0.74rem;">/ ${item.qtypertray || 0} plants</span>
          </td>
          <td>
            <span class="badge-center ${centerBadgeClass}">
              <i class="fa fa-store"></i> ${esc(item.resolved_center_name)}
            </span>
          </td>
          <td>
            <span class="badge-origin">
              <i class="fa fa-warehouse text-purple"></i> ${esc(originPh)} &bull; ${esc(originBed)}
            </span>
          </td>
          <td>
            <div style="font-size: 0.8rem; font-weight: 600; color: #1e293b;">${esc(item.stockorder_no || '-')}</div>
            ${item.psono ? `<div style="font-size: 0.72rem; color: #64748b;">PSO: ${esc(item.psono)}</div>` : ''}
          </td>
          <td>
            <div style="font-size: 0.78rem; font-weight: 600; color: #1e293b;">${esc(item.formatted_created_at)}</div>
            <div style="font-size: 0.72rem; color: #64748b;">By: ${esc(item.created_by || 'Staff')}</div>
          </td>
          <td>
            <span class="badge-age ${item.age_badge_class}">
              <i class="fa fa-clock"></i> ${esc(item.age_label)}
            </span>
          </td>
          <td>${statusBadge}</td>
          <td class="text-center" style="white-space: nowrap;">
            ${actionBtn}
          </td>
        </tr>
      `;

      // Cache item object if checked
      if (isChecked) {
        selectedTraysMap.set(item.barcode, item);
      }
    });

    $('#tblTraysBody').html(html);
    updateSelectionToolbar();
  }

  function renderPagination(totalCount, page, limit) {
    const totalPages = Math.ceil(totalCount / limit) || 1;
    $('#lblPageInfo').text(`Showing page ${page} of ${totalPages} (${Number(totalCount).toLocaleString()} total records)`);

    let btns = '';
    btns += `<button type="button" class="osr-page-btn" ${page <= 1 ? 'disabled' : ''} onclick="window.changeRegisterPage(${page - 1})"><i class="fa fa-chevron-left"></i> Prev</button>`;

    const startP = Math.max(1, page - 2);
    const endP = Math.min(totalPages, page + 2);

    for (let p = startP; p <= endP; p++) {
      btns += `<button type="button" class="osr-page-btn ${p === page ? 'active' : ''}" onclick="window.changeRegisterPage(${p})">${p}</button>`;
    }

    btns += `<button type="button" class="osr-page-btn" ${page >= totalPages ? 'disabled' : ''} onclick="window.changeRegisterPage(${page + 1})">Next <i class="fa fa-chevron-right"></i></button>`;

    $('#paginationButtons').html(btns);
  }

  window.changeRegisterPage = function (p) {
    loadTraysTable(p);
  };

  // -------------------------------------------------------------
  // 3. Multi-Select & Selection Bar
  // -------------------------------------------------------------
  function updateSelectionToolbar() {
    const count = selectedTraysMap.size;
    if (count > 0) {
      let totalPlants = 0;
      selectedTraysMap.forEach(item => {
        totalPlants += Number(item.actual_plant_count || item.qtypertray || 0);
      });
      $('#selectedCountText').text(`${count} Tray${count > 1 ? 's' : ''} Selected (${totalPlants.toLocaleString()} Plants)`);
      $('#selectionToolbar').addClass('active');
    } else {
      $('#selectionToolbar').removeClass('active');
    }

    // Update select-all checkbox state
    const allRowCbs = $('.cb-tray-row:not(:disabled)');
    if (allRowCbs.length > 0 && allRowCbs.length === allRowCbs.filter(':checked').length) {
      $('#selectAllTrays').prop('checked', true);
    } else {
      $('#selectAllTrays').prop('checked', false);
    }
  }

  $(document).on('change', '.cb-tray-row', function () {
    const bcode = $(this).data('barcode');
    const isChecked = $(this).is(':checked');
    const row = $(this).closest('tr');

    if (isChecked) {
      row.addClass('row-selected');
      // Fetch tray details from row data or DOM
      const pName = row.find('td:nth-child(4) div:first').text();
      const plants = parseInt(row.find('td:nth-child(5) strong').text().replace(/,/g, '')) || 0;
      const origin = row.find('td:nth-child(7) span').text().trim();
      const center = row.find('td:nth-child(6) span').text().trim();

      selectedTraysMap.set(bcode, {
        barcode: bcode,
        productname: pName,
        actual_plant_count: plants,
        origin_text: origin,
        center_text: center
      });
    } else {
      row.removeClass('row-selected');
      selectedTraysMap.delete(bcode);
    }

    updateSelectionToolbar();
  });

  $('#selectAllTrays').on('change', function () {
    const isChecked = $(this).is(':checked');
    $('.cb-tray-row:not(:disabled)').each(function () {
      $(this).prop('checked', isChecked).trigger('change');
    });
  });

  $('#btnClearSelection').on('click', function () {
    selectedTraysMap.clear();
    $('.cb-tray-row').prop('checked', false).closest('tr').removeClass('row-selected');
    $('#selectAllTrays').prop('checked', false);
    updateSelectionToolbar();
  });

  // -------------------------------------------------------------
  // 4. Return to Polyhouse Modal Logic
  // -------------------------------------------------------------
  function fetchPolyhouses(callback) {
    if (polyhouseCache.length > 0) {
      if (typeof callback === 'function') callback(polyhouseCache);
      return;
    }
    $.ajax({
      url: 'ajax/opensale_register_api.php',
      type: 'GET',
      dataType: 'json',
      data: { action: 'get_polyhouses' },
      success: function (res) {
        if (res.status === 'success' && res.data) {
          polyhouseCache = res.data;
          if (typeof callback === 'function') callback(polyhouseCache);
        }
      }
    });
  }

  function fetchBedsForPolyhouse(phId, callback) {
    if (!phId) {
      if (typeof callback === 'function') callback([]);
      return;
    }
    $.ajax({
      url: 'ajax/opensale_register_api.php',
      type: 'GET',
      dataType: 'json',
      data: { action: 'get_polyhouse_beds', polyhouseid: phId },
      success: function (res) {
        if (res.status === 'success' && res.beds) {
          if (typeof callback === 'function') callback(res.beds);
        } else {
          if (typeof callback === 'function') callback([]);
        }
      },
      error: function () {
        if (typeof callback === 'function') callback([]);
      }
    });
  }

  window.openReturnModal = function (traysList) {
    if (!traysList || traysList.length === 0) {
      playWarningSound();
      Swal.fire('No Trays Selected', 'Please select at least 1 tray to return to polyhouse.', 'warning');
      return;
    }

    let totalPlants = 0;
    let previewHtml = '';
    let suggestedPhId = '';
    let suggestedBedNo = '';
    let originCenterName = '';

    traysList.forEach((t, i) => {
      totalPlants += Number(t.actual_plant_count || t.qtypertray || 0);
      if (i < 5) {
        previewHtml += `
          <div class="preview-tray-item">
            <div>
              <strong>${esc(t.barcode)}</strong> &bull; <span class="text-muted">${esc(t.productname || 'Plant')}</span>
            </div>
            <div>
              <span class="badge" style="background:#e0f2fe; color:#0369a1; font-weight:700;">${Number(t.actual_plant_count || t.qtypertray || 0).toLocaleString()} Plants</span>
            </div>
          </div>
        `;
      }
      if (!suggestedPhId && (t.from_polyhouseid || t.current_plant_polyhouseid)) {
        suggestedPhId = String(t.from_polyhouseid || t.current_plant_polyhouseid);
      }
      if (!suggestedBedNo && (t.from_bed_no || t.current_plant_bed_no)) {
        suggestedBedNo = String(t.from_bed_no || t.current_plant_bed_no);
      }
      if (!originCenterName && (t.resolved_center_name || t.center_text || t.open_sale_location)) {
        originCenterName = t.resolved_center_name || t.center_text || t.open_sale_location;
      }
    });

    if (traysList.length > 5) {
      previewHtml += `<div class="text-muted text-center pt-1" style="font-size:0.75rem;">...and ${traysList.length - 5} more tray(s)</div>`;
    }

    $('#lblModalTraysCount').text(`${traysList.length} Tray${traysList.length > 1 ? 's' : ''}`);
    $('#lblModalPlantsCount').text(`${totalPlants.toLocaleString()} Plants`);
    $('#modalTraysPreviewList').html(previewHtml);

    // Store active barcodes for submit
    $('#returnModalBackdrop').data('traysList', traysList);

    // Populate Polyhouses Dropdown
    fetchPolyhouses(function (phList) {
      let phOpts = '<option value="">-- Select Target Polyhouse --</option>';
      phList.forEach(ph => {
        const isSel = (String(ph.polyhouseid) === suggestedPhId || String(ph.polyhousename) === suggestedPhId) ? 'selected' : '';
        phOpts += `<option value="${esc(ph.polyhouseid)}" ${isSel}>${esc(ph.polyhousename)}</option>`;
      });
      $('#selectTargetPolyhouse').html(phOpts);

      // Load beds for suggested polyhouse
      const curPh = $('#selectTargetPolyhouse').val();
      if (curPh) {
        loadBedsIntoDropdown(curPh, suggestedBedNo);
      } else {
        $('#selectTargetBed').html('<option value="">-- Select Bed --</option>');
      }
    });

    // Default Remark
    const centerTxt = originCenterName ? ` (${originCenterName})` : '';
    $('#inputReturnRemark').val(`Returned from Open Sale${centerTxt} within 2-3 days - unsold`);

    // Show Modal
    $('#returnModalBackdrop').addClass('active');
  };

  function loadBedsIntoDropdown(phId, defaultBed) {
    $('#selectTargetBed').html('<option value="">Loading beds...</option>');
    fetchBedsForPolyhouse(phId, function (beds) {
      let bOpts = '<option value="">-- Select Bed --</option>';
      let foundExact = false;
      beds.forEach(b => {
        const isSel = (defaultBed && b.toLowerCase() === defaultBed.toLowerCase());
        if (isSel) foundExact = true;
        bOpts += `<option value="${esc(b)}" ${isSel ? 'selected' : ''}>${esc(b)}</option>`;
      });
      if (defaultBed && !foundExact) {
        bOpts = `<option value="${esc(defaultBed)}" selected>${esc(defaultBed)} (Original Bed)</option>` + bOpts;
      }
      $('#selectTargetBed').html(bOpts);
    });
  }

  $('#selectTargetPolyhouse').on('change', function () {
    const phId = $(this).val();
    loadBedsIntoDropdown(phId, '');
  });

  window.closeReturnModal = function () {
    $('#returnModalBackdrop').removeClass('active');
    $('#returnModalBackdrop').removeData('traysList');
  };

  // Close modal on outside click or close button
  $('#btnCloseReturnModal, #btnCancelReturn').on('click', function () {
    closeReturnModal();
  });

  $('#returnModalBackdrop').on('click', function (e) {
    if ($(e.target).is('#returnModalBackdrop')) {
      closeReturnModal();
    }
  });

  // Single Row Return Click
  $(document).on('click', '.btn-return-action', function (e) {
    e.stopPropagation();
    const barcode = $(this).data('barcode');
    const row = $(this).closest('tr');
    const pName = row.find('td:nth-child(4) div:first').text();
    const plants = parseInt(row.find('td:nth-child(5) strong').text().replace(/,/g, '')) || 0;
    const origin = row.find('td:nth-child(7) span').text().trim();
    const center = row.find('td:nth-child(6) span').text().trim();

    const singleTray = [{
      barcode: barcode,
      productname: pName,
      actual_plant_count: plants,
      origin_text: origin,
      resolved_center_name: center
    }];

    openReturnModal(singleTray);
  });

  // Bulk Return Click
  $('#btnBulkReturn').on('click', function () {
    const list = Array.from(selectedTraysMap.values());
    openReturnModal(list);
  });

  // Confirm Return Submit
  $('#btnSubmitReturn').on('click', function () {
    const traysList = $('#returnModalBackdrop').data('traysList');
    if (!traysList || traysList.length === 0) {
      Swal.fire('Error', 'No trays selected for return.', 'error');
      return;
    }

    const targetPhId = $('#selectTargetPolyhouse').val();
    const targetBed = $('#selectTargetBed').val() || $('#inputCustomBed').val();
    const targetStatus = $('input[name="target_status"]:checked').val() || 'Polyhouse';
    const returnNotes = $.trim($('#inputReturnRemark').val());

    if (!targetPhId) {
      playWarningSound();
      Swal.fire('Target Polyhouse Required', 'Please select which Polyhouse to return these trays to.', 'warning');
      $('#selectTargetPolyhouse').focus();
      return;
    }

    if (!targetBed) {
      playWarningSound();
      Swal.fire('Target Bed Required', 'Please select or enter which Bed to return these trays to.', 'warning');
      $('#selectTargetBed').focus();
      return;
    }

    const phName = $('#selectTargetPolyhouse option:selected').text();
    const barcodes = traysList.map(t => t.barcode);

    Swal.fire({
      title: 'Confirm Return to Polyhouse?',
      html: `
        <div style="text-align: left; font-size: 0.92rem; color: #334155;">
          <p>Are you sure you want to return <b>${barcodes.length} tray(s)</b> from Open Sale back to production stock?</p>
          <div class="p-2 mb-2" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px;">
            <div><i class="fa fa-warehouse text-primary mr-1"></i> <b>Target Polyhouse:</b> ${esc(phName)}</div>
            <div><i class="fa fa-th text-success mr-1"></i> <b>Target Bed:</b> ${esc(targetBed)}</div>
            <div><i class="fa fa-tag text-purple mr-1"></i> <b>Restored Status:</b> ${esc(targetStatus)}</div>
          </div>
          <p class="text-muted small mb-0"><i class="fa fa-info-circle mr-1"></i> Trays will be marked as "Returned" in Open Sale register and updated in polyhouse plant records.</p>
        </div>
      `,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: '<i class="fa fa-check mr-1"></i> Yes, Return Trays',
      confirmButtonColor: '#2563eb',
      cancelButtonText: 'Cancel',
      cancelButtonColor: '#64748b'
    }).then((res) => {
      if (!res.isConfirmed) return;

      Swal.fire({
        title: 'Returning Trays...',
        text: 'Updating polyhouse and open sale stock records...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
      });

      $.ajax({
        url: 'ajax/opensale_register_api.php',
        type: 'POST',
        dataType: 'json',
        data: {
          action: 'return_trays_to_polyhouse',
          barcodes: JSON.stringify(barcodes),
          target_polyhouseid: targetPhId,
          target_bed_no: targetBed,
          target_status: targetStatus,
          return_notes: returnNotes
        },
        success: function (resp) {
          if (resp.status === 'success') {
            playSuccessSound();
            closeReturnModal();
            selectedTraysMap.clear();
            updateSelectionToolbar();

            Swal.fire({
              icon: 'success',
              title: 'Trays Returned Successfully!',
              text: resp.message || `Successfully returned ${barcodes.length} tray(s) back to ${phName}, Bed ${targetBed}.`,
              timer: 3000,
              showConfirmButton: true,
              confirmButtonColor: '#2563eb'
            });

            loadKPIs();
            loadTraysTable(currentPage);
          } else {
            playWarningSound();
            Swal.fire('Return Failed', resp.message || 'Could not return trays to polyhouse.', 'error');
          }
        },
        error: function (xhr, status, err) {
          playWarningSound();
          Swal.fire('Server Error', 'Failed to communicate with server: ' + err, 'error');
        }
      });
    });
  });

  // -------------------------------------------------------------
  // 5. Center Quick Filter Pills
  // -------------------------------------------------------------
  $(document).on('click', '.osr-center-pill', function () {
    $('.osr-center-pill').removeClass('active');
    $(this).addClass('active');

    const loc = $(this).data('location') || '';
    $('#filterLocation').val(loc);

    loadKPIs();
    loadTraysTable(1);
  });

  // -------------------------------------------------------------
  // 6. Filter Controls Events
  // -------------------------------------------------------------
  let searchTimer = null;
  $('#filterSearch').on('input', function () {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
      loadTraysTable(1);
    }, 350);
  });

  $('#filterLocation, #filterStatus, #filterDays, #filterDateFrom, #filterDateTo').on('change', function () {
    // Sync center pills if location changed
    const loc = $('#filterLocation').val();
    $('.osr-center-pill').removeClass('active');
    if (!loc) {
      $('.osr-center-pill.all-centers').addClass('active');
    } else {
      $(`.osr-center-pill[data-location="${loc}"]`).addClass('active');
    }

    loadKPIs();
    loadTraysTable(1);
  });

  $('#btnResetFilters').on('click', function () {
    $('#filterSearch').val('');
    $('#filterLocation').val('');
    $('#filterStatus').val('OpenSale');
    $('#filterDays').val('');
    $('#filterDateFrom').val('');
    $('#filterDateTo').val('');

    $('.osr-center-pill').removeClass('active');
    $('.osr-center-pill.all-centers').addClass('active');

    loadKPIs();
    loadTraysTable(1);
  });

  $('#btnRefreshAll').on('click', function () {
    loadKPIs();
    loadTraysTable(currentPage);
  });

  // -------------------------------------------------------------
  // 7. Export CSV
  // -------------------------------------------------------------
  $('#btnExportCSV').on('click', function () {
    const search = $.trim($('#filterSearch').val());
    const loc = $('#filterLocation').val();
    const status = $('#filterStatus').val();
    const daysFilter = $('#filterDays').val();
    const df = $('#filterDateFrom').val();
    const dt = $('#filterDateTo').val();

    Swal.fire({
      title: 'Exporting Data...',
      text: 'Preparing Open Sale Register CSV...',
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading()
    });

    $.ajax({
      url: 'ajax/opensale_register_api.php',
      type: 'GET',
      dataType: 'json',
      data: {
        action: 'get_trays',
        search: search,
        location: loc,
        status: status,
        days_filter: daysFilter,
        date_from: df,
        date_to: dt,
        page: 1,
        limit: 5000 // Export up to 5,000 records
      },
      success: function (res) {
        Swal.close();
        if (res.status === 'success' && res.data && res.data.length > 0) {
          downloadCSV(res.data);
        } else {
          Swal.fire('No Data', 'No records found to export.', 'info');
        }
      },
      error: function () {
        Swal.close();
        Swal.fire('Export Failed', 'Could not fetch records for export.', 'error');
      }
    });
  });

  function downloadCSV(rows) {
    const headers = [
      '#', 'Tray Barcode', 'Product Name', 'Variety', 'Plants Count',
      'Tray Cavity', 'Open Sale Center', 'Origin Polyhouse', 'Origin Bed',
      'Stock Order No', 'PSO No', 'Status', 'Days in Open Sale', 'Moved Date', 'Moved By', 'Remarks'
    ];

    let csvContent = 'data:text/csv;charset=utf-8,\uFEFF' + headers.join(',') + '\n';

    rows.forEach((r, idx) => {
      const rowData = [
        idx + 1,
        `"${(r.barcode || '').replace(/"/g, '""')}"`,
        `"${(r.productname || '').replace(/"/g, '""')}"`,
        `"${(r.variety || '').replace(/"/g, '""')}"`,
        r.actual_plant_count || r.qtypertray || 0,
        r.qtypertray || 0,
        `"${(r.resolved_center_name || '').replace(/"/g, '""')}"`,
        `"${(r.from_polyhousename || r.from_polyhouseid || '').replace(/"/g, '""')}"`,
        `"${(r.from_bed_no || '').replace(/"/g, '""')}"`,
        `"${(r.stockorder_no || '').replace(/"/g, '""')}"`,
        `"${(r.psono || '').replace(/"/g, '""')}"`,
        `"${(r.status || '').replace(/"/g, '""')}"`,
        r.days_in_opensale || 0,
        `"${(r.formatted_created_at || '').replace(/"/g, '""')}"`,
        `"${(r.created_by || '').replace(/"/g, '""')}"`,
        `"${(r.remarks || '').replace(/"/g, '""').replace(/\n/g, ' | ')}"`
      ];
      csvContent += rowData.join(',') + '\n';
    });

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', `OpenSale_Register_${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }

  // -------------------------------------------------------------
  // Initial Load
  // -------------------------------------------------------------
  $(document).ready(function () {
    loadKPIs();
    loadTraysTable(1);
    fetchPolyhouses(); // Pre-cache polyhouse list
  });

})(jQuery);
