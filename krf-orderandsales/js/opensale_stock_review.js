/**
 * Open Sale Stock Review JavaScript
 * Dynamic Data Fetching, Tab Switching & Search Filtering
 */

$(document).ready(function() {
  'use strict';

  let currentTab = 'summary'; // 'summary' or 'trays'
  let searchDebounceTimer = null;
  let cachedGroupedData = [];
  let cachedTraysData = [];

  // Helper escape function
  function esc(str) {
    if (str == null) return '';
    return $('<div>').text(String(str)).html();
  }

  // Helper format numbers with commas
  function fmtNum(n) {
    const num = Number(n) || 0;
    return num.toLocaleString('en-IN');
  }

  // 1. Initialize Filters & Centers
  function initFilters() {
    $.getJSON('modal/opensale_stock_review_modal.php?action=get_filters', function(res) {
      if (res && res.status === 'success' && res.data) {
        // Populate Location Filter
        const $loc = $('#filterLocation');
        let locOpts = '<option value="">-- All Open Sale Centers --</option>';
        if (res.data.locations) {
          res.data.locations.forEach(l => {
            locOpts += `<option value="${esc(l.enum_location)}" data-polyhouseid="${esc(l.polyhouseid)}" data-locid="${esc(l.locationid)}">${esc(l.polyhousename)}</option>`;
          });
        }
        $loc.html(locOpts);

        // Populate Product Filter
        const $prod = $('#filterProduct');
        let prodOpts = '<option value="">-- All Products --</option>';
        if (res.data.products) {
          res.data.products.forEach(p => {
            prodOpts += `<option value="${esc(p.productid)}">${esc(p.productname)}</option>`;
          });
        }
        $prod.html(prodOpts);
      }
    });
  }

  // 2. Load KPIs
  function loadKPIs() {
    const params = {
      location: $('#filterLocation').val() || '',
      productid: $('#filterProduct').val() || '',
      date_from: $('#filterDateFrom').val() || '',
      date_to: $('#filterDateTo').val() || ''
    };

    $.getJSON('modal/opensale_stock_review_modal.php?action=get_kpis', params, function(res) {
      if (res && res.status === 'success' && res.data) {
        const d = res.data;
        $('#kpiActiveTrays').text(fmtNum(d.active_trays));
        $('#kpiActivePlants').text(fmtNum(d.active_plants));
        $('#kpiActiveProducts').text(fmtNum(d.active_products));
        $('#kpiSoldPlants').text(fmtNum(d.sold_plants) + ' (' + fmtNum(d.sold_trays) + ' Trays)');

        // Center Pills
        $('#badgeKarjeeStats').text(`${fmtNum(d.karjee_plants)} plants (${fmtNum(d.karjee_trays)} trays)`);
        $('#badgeSargawaStats').text(`${fmtNum(d.sargawa_plants)} plants (${fmtNum(d.sargawa_trays)} trays)`);
        $('#badgeMansoonStats').text(`${fmtNum(d.mansoon_plants)} plants (${fmtNum(d.mansoon_trays)} trays)`);
      }
    });
  }

  // 3. Load Grouped Stock Summary
  function loadGroupedStock() {
    const params = {
      status: $('#filterStatus').val() || 'OpenSale',
      location: $('#filterLocation').val() || '',
      productid: $('#filterProduct').val() || '',
      search: $('#filterSearch').val() || '',
      date_from: $('#filterDateFrom').val() || '',
      date_to: $('#filterDateTo').val() || ''
    };

    $('#tblGroupedBody').html('<tr><td colspan="10" class="osr-loading"><i class="fa fa-spinner fa-spin text-primary"></i>Loading Open Sale Products...</td></tr>');

    $.getJSON('modal/opensale_stock_review_modal.php?action=get_grouped_stock', params, function(res) {
      if (res && res.status === 'success' && res.data) {
        cachedGroupedData = res.data;
        $('#tabSummaryCount').text(res.count);
        renderGroupedTable(res.data);
      } else {
        $('#tblGroupedBody').html('<tr><td colspan="10" class="osr-empty"><i class="fa fa-boxes text-muted"></i>No open sale products found.</td></tr>');
      }
    }).fail(function() {
      $('#tblGroupedBody').html('<tr><td colspan="10" class="osr-empty text-danger"><i class="fa fa-exclamation-triangle"></i>Failed to load product data.</td></tr>');
    });
  }

  function renderGroupedTable(rows) {
    if (!rows || !rows.length) {
      $('#tblGroupedBody').html('<tr><td colspan="10" class="osr-empty"><i class="fa fa-inbox text-muted"></i>No products match the selected criteria.</td></tr>');
      return;
    }

    let html = '';
    let totalTrays = 0;
    let totalPlants = 0;
    let totalKarjee = 0;
    let totalSargawa = 0;
    let totalMansoon = 0;

    rows.forEach((r, idx) => {
      totalTrays += r.total_trays;
      totalPlants += r.total_plants;
      totalKarjee += r.plants_karjee;
      totalSargawa += r.plants_sargawa;
      totalMansoon += r.plants_mansoon;

      html += `
        <tr>
          <td class="text-center font-weight-bold text-muted">${idx + 1}</td>
          <td>
            <div class="font-weight-bold text-dark" style="font-size:0.92rem;">${esc(r.productname)}</div>
            <small class="text-muted"><i class="fa fa-seedling mr-1 text-success"></i>${esc(r.variety)}</small>
          </td>
          <td>
            <span class="osr-barcode-badge">${esc(r.tray_code || r.qtypertray || '-')}</span>
          </td>
          <td class="text-right font-weight-bold" style="font-size:0.92rem; color:#047857;">
            ₹${r.standard_rate.toFixed(2)}
          </td>
          <td class="text-center">
            <span class="badge bg-primary text-white" style="font-size:0.85rem; padding:4px 8px;">
              ${fmtNum(r.total_trays)} Trays
            </span>
          </td>
          <td class="text-right font-weight-bold text-primary" style="font-size:1.02rem;">
            ${fmtNum(r.total_plants)}
          </td>
          <td class="text-center" style="font-size:0.82rem;">
            ${r.plants_karjee > 0 ? `<span class="badge bg-success text-white mr-1">${fmtNum(r.plants_karjee)}</span>` : '<span class="text-muted">-</span>'}
          </td>
          <td class="text-center" style="font-size:0.82rem;">
            ${r.plants_sargawa > 0 ? `<span class="badge bg-info text-white mr-1">${fmtNum(r.plants_sargawa)}</span>` : '<span class="text-muted">-</span>'}
          </td>
          <td class="text-center" style="font-size:0.82rem;">
            ${r.plants_mansoon > 0 ? `<span class="badge bg-warning text-dark mr-1">${fmtNum(r.plants_mansoon)}</span>` : '<span class="text-muted">-</span>'}
          </td>
          <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-primary btn-view-product-trays" data-productid="${esc(r.productid)}" data-productname="${esc(r.productname)}" title="View individual trays">
              <i class="fa fa-list mr-1"></i> Trays
            </button>
          </td>
        </tr>
      `;
    });

    // Summary Footer
    html += `
      <tr style="background:#f1f5f9; font-weight:800; border-top:2px solid #cbd5e1;">
        <td colspan="4" class="text-right text-uppercase" style="letter-spacing:0.5px;">Grand Total:</td>
        <td class="text-center font-weight-bold" style="font-size:0.95rem; color:#1e1b4b;">${fmtNum(totalTrays)} Trays</td>
        <td class="text-right font-weight-bold" style="font-size:1.08rem; color:#1d4ed8;">${fmtNum(totalPlants)} Plants</td>
        <td class="text-center font-weight-bold text-success">${fmtNum(totalKarjee)}</td>
        <td class="text-center font-weight-bold text-info">${fmtNum(totalSargawa)}</td>
        <td class="text-center font-weight-bold text-warning">${fmtNum(totalMansoon)}</td>
        <td></td>
      </tr>
    `;

    $('#tblGroupedBody').html(html);
  }

  // 4. Load Detailed Trays List
  function loadTraysList() {
    const params = {
      status: $('#filterStatus').val() || 'OpenSale',
      location: $('#filterLocation').val() || '',
      productid: $('#filterProduct').val() || '',
      search: $('#filterSearch').val() || '',
      date_from: $('#filterDateFrom').val() || '',
      date_to: $('#filterDateTo').val() || '',
      limit: 1000,
      offset: 0
    };

    $('#tblTraysBody').html('<tr><td colspan="11" class="osr-loading"><i class="fa fa-spinner fa-spin text-primary"></i>Loading Individual Trays...</td></tr>');

    $.getJSON('modal/opensale_stock_review_modal.php?action=get_trays_list', params, function(res) {
      if (res && res.status === 'success' && res.items) {
        cachedTraysData = res.items;
        $('#tabTraysCount').text(res.total);
        renderTraysTable(res.items);
      } else {
        $('#tblTraysBody').html('<tr><td colspan="11" class="osr-empty"><i class="fa fa-qrcode text-muted"></i>No tray records found.</td></tr>');
      }
    }).fail(function() {
      $('#tblTraysBody').html('<tr><td colspan="11" class="osr-empty text-danger"><i class="fa fa-exclamation-triangle"></i>Failed to load trays.</td></tr>');
    });
  }

  function renderTraysTable(items) {
    if (!items || !items.length) {
      $('#tblTraysBody').html('<tr><td colspan="11" class="osr-empty"><i class="fa fa-inbox text-muted"></i>No tray records found matching current filters.</td></tr>');
      return;
    }

    let html = '';
    items.forEach((it, idx) => {
      const isSold = (it.status === 'Sold');
      const badgeCls = isSold ? 'osr-badge-secondary' : 'osr-badge-success';
      const locName = it.open_sale_location || 'Open Sale Center';

      html += `
        <tr>
          <td class="text-center text-muted font-weight-bold">${idx + 1}</td>
          <td>
            <span class="osr-barcode-badge"><i class="fa fa-barcode mr-1 text-primary"></i>${esc(it.barcode)}</span>
          </td>
          <td>
            <div class="font-weight-bold text-dark">${esc(it.productname)}</div>
            <small class="text-muted">${esc(it.variety)}</small>
          </td>
          <td class="text-right font-weight-bold text-primary" style="font-size:0.95rem;">
            ${fmtNum(it.actual_plant_count)} / <small class="text-muted font-weight-normal">${esc(it.qtypertray)}</small>
          </td>
          <td>
            <span class="badge ${locName === 'Karjee' ? 'bg-success' : (locName === 'Sargawa' ? 'bg-info' : 'bg-warning')} text-white font-weight-bold">
              <i class="fa fa-store mr-1"></i>${esc(locName)}
            </span>
          </td>
          <td>
            <div style="font-size:0.82rem;">${esc(it.from_polyhousename || '-')}</div>
            <small class="text-muted">${esc(it.from_bed_no || '-')}</small>
          </td>
          <td>
            <div style="font-size:0.82rem; font-family:var(--osr-mono); font-weight:700;">${esc(it.stockorder_no || '-')}</div>
            <small class="text-muted">${esc(it.psono || '-')}</small>
          </td>
          <td class="text-center">
            <span class="osr-badge ${badgeCls}">
              <i class="fa ${isSold ? 'fa-check' : 'fa-circle-dot'}"></i> ${esc(it.status)}
            </span>
            ${it.invoiceno ? `<div class="mt-1"><small class="text-info font-weight-bold">Inv: ${esc(it.invoiceno)}</small></div>` : ''}
          </td>
          <td>
            <div style="font-size:0.78rem;">${esc(it.created_at_fmt)}</div>
            <small class="text-muted">${esc(it.created_by || '-')}</small>
          </td>
          <td style="max-width:200px;">
            <small class="text-muted text-truncate d-block" title="${esc(it.remarks)}">${esc(it.remarks || '-')}</small>
          </td>
        </tr>
      `;
    });

    $('#tblTraysBody').html(html);
  }

  // 5. Tab Switching
  $('.osr-tab-btn').on('click', function() {
    const target = $(this).data('tab');
    if (target === currentTab) return;

    currentTab = target;
    $('.osr-tab-btn').removeClass('active');
    $(this).addClass('active');

    if (currentTab === 'summary') {
      $('#paneSummary').show();
      $('#paneTrays').hide();
      if (!cachedGroupedData.length) loadGroupedStock();
    } else {
      $('#paneSummary').hide();
      $('#paneTrays').show();
      if (!cachedTraysData.length) loadTraysList();
    }
  });

  // 6. Center Pill Quick Filter
  $(document).on('click', '.osr-center-pill', function() {
    const loc = $(this).data('location');
    $('#filterLocation').val(loc).trigger('change');
  });

  // 7. View Trays for specific Product from Summary
  $(document).on('click', '.btn-view-product-trays', function() {
    const pId = $(this).data('productid');
    $('#filterProduct').val(pId);
    // Switch to Detailed Trays tab
    $('.osr-tab-btn[data-tab="trays"]').trigger('click');
    loadTraysList();
  });

  // 8. Filters & Search Handlers
  $('#filterLocation, #filterProduct, #filterStatus, #filterDateFrom, #filterDateTo').on('change', function() {
    refreshAll();
  });

  $('#filterSearch').on('input', function() {
    clearTimeout(searchDebounceTimer);
    searchDebounceTimer = setTimeout(function() {
      if (currentTab === 'summary') {
        loadGroupedStock();
      } else {
        loadTraysList();
      }
    }, 350);
  });

  $('#btnResetFilters').on('click', function() {
    $('#filterLocation').val('');
    $('#filterProduct').val('');
    $('#filterStatus').val('OpenSale');
    $('#filterSearch').val('');
    $('#filterDateFrom').val('');
    $('#filterDateTo').val('');
    refreshAll();
  });

  $('#btnRefreshAll').on('click', function() {
    refreshAll();
  });

  function refreshAll() {
    loadKPIs();
    if (currentTab === 'summary') {
      loadGroupedStock();
    } else {
      loadTraysList();
    }
  }

  // 9. CSV Export Handler
  $('#btnExportCSV').on('click', function(e) {
    e.preventDefault();
    const params = $.param({
      action: 'export_csv',
      view: currentTab,
      status: $('#filterStatus').val() || 'All',
      location: $('#filterLocation').val() || '',
      productid: $('#filterProduct').val() || '',
      search: $('#filterSearch').val() || '',
      date_from: $('#filterDateFrom').val() || '',
      date_to: $('#filterDateTo').val() || ''
    });
    window.location.href = 'modal/opensale_stock_review_modal.php?' + params;
  });

  // Initial Load
  initFilters();
  loadKPIs();
  loadGroupedStock();
});
