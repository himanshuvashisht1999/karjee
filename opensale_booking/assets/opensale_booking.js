/**
 * Open Sale POS Order Booking Terminal
 * Karjee Farm - Nursery & Plant Management System
 */

const OpenSalePOS = (function () {
  'use strict';

  // State
  let state = {
    branch: '',            // '' = All, or 'Sargawa', 'Karjee', 'Mansoon'
    viewMode: 'product',   // 'product' or 'tray'
    selectedProductKey: '',// Filter by product key
    searchQuery: '',
    stockTrays: [],
    allStockTrays: [],
    suggestFilter: 'all',  // 'all' or 'pending'
    selectedSuggestBarcodes: new Set(),
    lastSuggestMatches: [],
    lastSuggestQuery: '',
    groupedProducts: [],
    cart: [],              // items in cart
    selectedClient: null,
    paymentMode: 'Cash',
    isMultiMode: false,
    activePaymentModes: ['Cash'],
    singlePaymentDoc: null,
    singleUtrDuplicate: false,
    splitCounter: 1,
    splitPaymentRows: [
      { id: 'split_1', mode: 'Cash', amount: 0, utr: '', file: null, isDuplicate: false, isValidUtr: false, warning: '' }
    ],
    splitPayments: {
      'Cash': { amount: 0, utr: '', file: null },
      'UPI': { amount: 0, utr: '', file: null },
      'Bank': { amount: 0, utr: '', file: null },
      'Cheque': { amount: 0, utr: '', file: null }
    },
    amountPaid: 0,
    specialDiscount: 0,
    isCustomPaid: false,
    pendingOrders: [],
    selectedPendingOrders: {},
    boxPacking: {
      enabled: false,
      capacity: 500,
      rate: 100.00,
      boxCount: 0,
      totalCharge: 0
    },
    transport: {
      pickupfrom: 'pickupbyclient',
      vehicleno: '',
      drivername: '',
      driverphone: '',
      remarks: ''
    }
  };

  // Cached product rates to avoid redundant AJAX calls
  const rateCache = {};

  // Audio Beep for Barcode Scanning (Web Audio API)
  function playBeep(success = true) {
    try {
      const AudioCtx = window.AudioContext || window.webkitAudioContext;
      if (!AudioCtx) return;
      const ctx = new AudioCtx();
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.type = success ? 'sine' : 'sawtooth';
      osc.frequency.setValueAtTime(success ? 880 : 300, ctx.currentTime);
      gain.gain.setValueAtTime(0.12, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.15);
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.start();
      osc.stop(ctx.currentTime + 0.15);
    } catch (e) {
      // Audio might be blocked until user interacts
    }
  }

  // Toast Notification
  function showToast(icon, title) {
    if (typeof Swal !== 'undefined') {
      const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2200,
        timerProgressBar: true
      });
      Toast.fire({ icon: icon, title: title });
    } else {
      console.log(`[${icon.toUpperCase()}] ${title}`);
    }
  }

  // -------------------------------------------------------------
  // API URL RESOLVER & ROBUST RESPONSE PARSER
  // -------------------------------------------------------------
  function getApiEndpoint(query = '') {
    let base = window.APP_CONFIG?.apiUrl;
    if (!base) {
      const loc = window.location.pathname;
      let dir = loc.substring(0, loc.lastIndexOf('/') + 1);
      if (!dir.includes('/opensale_booking/')) {
        const idx = loc.indexOf('/opensale_booking');
        if (idx !== -1) {
          dir = loc.substring(0, idx) + '/opensale_booking/';
        }
      }
      base = dir + 'ajax/opensale_api.php';
    }
    if (!query) return base;
    return base + (base.includes('?') ? '&' : '?') + (query.startsWith('?') ? query.substring(1) : query);
  }

  async function parseApiResponse(res) {
    const httpStatus = res.status;
    const text = await res.text();
    let json;
    try {
      json = JSON.parse(text);
    } catch (e) {
      // Non-JSON response received (HTML error page, PHP notice/error, etc.)
      let cleanText = text.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
      if (!cleanText) {
        cleanText = `Empty response received (HTTP ${httpStatus})`;
      } else if (cleanText.length > 300) {
        cleanText = cleanText.substring(0, 300) + '...';
      }
      throw new Error(`Server returned HTTP ${httpStatus} (Non-JSON response): ${cleanText}`);
    }
    return json;
  }

  // -------------------------------------------------------------
  // 1. INITIALIZATION
  // -------------------------------------------------------------
  function init() {
    bindEvents();
    loadStock();
    initClientAutocomplete();
    loadStates();
  }

  function bindEvents() {
    // Open Sale Center Dropdown Filter
    const oscSel = document.getElementById('openSaleCenterDropdown');
    if (oscSel) {
      oscSel.addEventListener('change', function () {
        state.branch = this.value || '';
        state.selectedProductKey = '';
        state.selectedSuggestBarcodes = new Set();

        // Reset the Sales Order & Billing Dock (Cart, summaries, payment fields)
        resetBillingDock();

        const scanInput = document.getElementById('scanBarcodeInput');
        if (scanInput) scanInput.value = '';

        const suggestBox = document.getElementById('scanSuggestResults');
        if (suggestBox) suggestBox.style.display = 'none';

        loadStock();
      });
    }

    // Branch Filter Pills (if any)
    document.querySelectorAll('.branch-pill').forEach(btn => {
      btn.addEventListener('click', function () {
        document.querySelectorAll('.branch-pill').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        state.branch = this.getAttribute('data-branch') || '';
        loadStock();
      });
    });

    // View Mode Toggle (Product Group vs Tray)
    const viewProductBtn = document.getElementById('btnViewProduct');
    const viewTrayBtn = document.getElementById('btnViewTray');
    if (viewProductBtn && viewTrayBtn) {
      viewProductBtn.addEventListener('click', function () {
        state.viewMode = 'product';
        viewProductBtn.classList.add('active');
        viewTrayBtn.classList.remove('active');
        renderCatalog();
      });
      viewTrayBtn.addEventListener('click', function () {
        state.viewMode = 'tray';
        viewTrayBtn.classList.add('active');
        viewProductBtn.classList.remove('active');
        renderCatalog();
      });
    }

    // Barcode & Product Search Autocomplete with Arrow Navigation
    initScanBarcodeAutocomplete();

    // Payment Mode Pills (Single mode select vs Multi-Mode toggle / add split)
    document.querySelectorAll('.pay-mode-btn').forEach(btn => {
      btn.addEventListener('click', function () {
        const mode = this.getAttribute('data-mode') || 'Cash';
        if (state.isMultiMode) {
          // In multi-mode: add split row of this mode
          addSplitRow(mode);
          showToast('info', `Added ${mode} payment split`);
        } else {
          // Single-mode selection
          document.querySelectorAll('.pay-mode-btn').forEach(b => b.classList.remove('active'));
          this.classList.add('active');
          state.paymentMode = mode;
          state.activePaymentModes = [mode];
          const utrBox = document.getElementById('utrFieldWrap');
          if (utrBox) {
            if (state.paymentMode === 'Cash') {
              utrBox.style.display = 'none';
            } else {
              utrBox.style.display = 'block';
              const label = utrBox.querySelector('label');
              if (label) {
                label.textContent = state.paymentMode === 'Cheque' ? 'Cheque No. / Date' : 'UTR / Reference No.';
              }
              const utrInput = document.getElementById('cartUtrNo');
              if (utrInput && utrInput.value.trim()) {
                checkDuplicateUtr('single', utrInput.value.trim(), true);
              }
            }
          }
        }
      });
    });

    // Multi-Payment Mode Checkbox
    const chkMultiPay = document.getElementById('chkMultiPaymentMode');
    if (chkMultiPay) {
      chkMultiPay.addEventListener('change', function () {
        toggleMultiPaymentMode(this.checked);
      });
    }

    // Modal Order PDF Close on overlay click
    const modalPdfOverlay = document.getElementById('modalOrderPdfPreview');
    if (modalPdfOverlay) {
      modalPdfOverlay.addEventListener('click', function (e) {
        if (e.target === this) {
          closeOrderPdfModal();
        }
      });
    }

    // Modal Document Preview Close on overlay click
    const modalDocOverlay = document.getElementById('modalDocPreview');
    if (modalDocOverlay) {
      modalDocOverlay.addEventListener('click', function (e) {
        if (e.target === this) {
          closeDocPreviewModal();
        }
      });
    }

    // Paid Amount Input
    const paidInput = document.getElementById('cartPaidAmount');
    if (paidInput) {
      paidInput.addEventListener('input', function () {
        this.dataset.touched = 'true';
        state.isCustomPaid = true;
        state.amountPaid = parseFloat(this.value) || 0;
        calculateBillSummary();
      });
    }

    // Special Discount Input
    const spDiscInput = document.getElementById('cartSpecialDiscount');
    if (spDiscInput) {
      spDiscInput.addEventListener('input', function () {
        state.specialDiscount = Math.max(0, parseFloat(this.value) || 0);
        calculateBillSummary();
      });
    }

    // Full Payment Button
    const btnFullPaid = document.getElementById('btnFullPaid');
    if (btnFullPaid) {
      btnFullPaid.addEventListener('click', function () {
        if (paidInput) {
          paidInput.dataset.touched = 'false';
          state.isCustomPaid = false;
          calculateBillSummary();
        }
      });
    }

    // Select All Pending Orders Checkbox
    const chkAllPending = document.getElementById('chkSelectAllPendingOrders');
    if (chkAllPending) {
      chkAllPending.addEventListener('change', function () {
        onToggleAllPendingOrders(this.checked);
      });
    }

    // Box Packing Charges Toggle
    const chkBoxPacking = document.getElementById('chkEnableBoxPacking');
    if (chkBoxPacking) {
      chkBoxPacking.addEventListener('change', function () {
        state.boxPacking.enabled = this.checked;
        const body = document.getElementById('boxPackingBody');
        if (body) {
          body.style.display = this.checked ? 'block' : 'none';
        }
        if (this.checked) {
          const boxInput = document.getElementById('cartBoxCount');
          if (boxInput && (parseInt(boxInput.value) || 0) <= 0) {
            autoCalculateBoxes();
          }
        }
        calculateBillSummary();
      });
    }

    // Box Packing Auto Calculate Button
    const btnAutoCalcBoxes = document.getElementById('btnAutoCalcBoxes');
    if (btnAutoCalcBoxes) {
      btnAutoCalcBoxes.addEventListener('click', function () {
        autoCalculateBoxes();
      });
    }

    // Box Packing No. of Boxes Input
    const boxCountInput = document.getElementById('cartBoxCount');
    if (boxCountInput) {
      boxCountInput.addEventListener('input', function () {
        const val = Math.max(0, parseInt(this.value) || 0);
        state.boxPacking.boxCount = val;
        calculateBillSummary();
      });
    }

    // Box Packing Rate Input
    const boxRateInput = document.getElementById('cartBoxRate');
    if (boxRateInput) {
      boxRateInput.addEventListener('input', function () {
        const val = Math.max(0, parseFloat(this.value) || 0);
        state.boxPacking.rate = val;
        const badge = document.getElementById('boxRateBadge');
        if (badge) badge.textContent = `₹ ${val.toFixed(2)} / Box`;
        const hint = document.getElementById('cartBoxFixedRateHint');
        if (hint) hint.textContent = `Fixed charge: ₹${val.toFixed(0)} per box`;
        calculateBillSummary();
      });
    }

    // Clear Cart Button
    const btnClearCart = document.getElementById('btnClearCart');
    if (btnClearCart) {
      btnClearCart.addEventListener('click', function () {
        if (state.cart.length === 0) return;
        if (confirm('Are you sure you want to clear all items from the cart?')) {
          state.cart = [];
          renderCartTable();
          calculateBillSummary();
          refreshCurrentSuggestions();
          showToast('info', 'Cart cleared');
        }
      });
    }

    // Checkout / Book Order Button
    const btnBookOrder = document.getElementById('btnBookOrder');
    if (btnBookOrder) {
      btnBookOrder.addEventListener('click', function () {
        submitOrderBooking();
      });
    }

    // New Client Modal triggers
    const btnOpenNewClient = document.getElementById('btnOpenNewClient');
    if (btnOpenNewClient) {
      btnOpenNewClient.addEventListener('click', openNewClientModal);
    }
    const btnCloseNewClient = document.getElementById('btnCloseNewClient');
    if (btnCloseNewClient) {
      btnCloseNewClient.addEventListener('click', closeNewClientModal);
    }
    const formNewClient = document.getElementById('formNewClient');
    if (formNewClient) {
      formNewClient.addEventListener('submit', handleNewClientSubmit);
    }

    // Remove Selected Client
    const btnRemoveClient = document.getElementById('btnRemoveClient');
    if (btnRemoveClient) {
      btnRemoveClient.addEventListener('click', clearSelectedClient);
    }
  }

  // -------------------------------------------------------------
  // 2. FETCH AVAILABLE STOCK
  // -------------------------------------------------------------
  // Robust matcher to test if a tray belongs to the selected Open Sale Center
  function isTrayInOpenSaleCenter(tray, selectedCenterName, selectedCenterLocId) {
    if (!selectedCenterName && !selectedCenterLocId) return false;
    const tLoc   = (tray.open_sale_location || '').toLowerCase().trim();
    const tLocId = (tray.opensale_locationid || '').toLowerCase().trim();
    const cName  = (selectedCenterName || '').toLowerCase().trim();
    const cLocId = (selectedCenterLocId || '').toLowerCase().trim();

    // 1. Exact locationid match
    if (tLocId && cLocId && tLocId === cLocId) return true;

    // 2. Sargwan / Sargawa center match
    const isCenterSargawa = cName.includes('sargw') || cName.includes('sargaw') || cLocId === 'loc696b280faf104';
    const isTraySargawa   = tLoc.includes('sargw')  || tLoc.includes('sargaw')  || tLocId === 'loc696b280faf104';
    if (isCenterSargawa && isTraySargawa) return true;

    // 3. Karjee center match
    const isCenterKarjee = cName.includes('karjee') || cLocId === 'loc6905e0fd5ab05';
    const isTrayKarjee   = tLoc.includes('karjee')  || tLocId === 'loc6905e0fd5ab05';
    if (isCenterKarjee && isTrayKarjee) return true;

    // 4. Mansoon Agro center match
    const isCenterMansoon = cName.includes('mansoon') || cLocId === 'loc6a4f396f08173';
    const isTrayMansoon   = tLoc.includes('mansoon')  || tLocId === 'loc6a4f396f08173';
    if (isCenterMansoon && isTrayMansoon) return true;

    // 5. Fallback non-empty substring match (never matching empty strings)
    if (tLoc && cName && (tLoc.includes(cName) || cName.includes(tLoc))) return true;

    return false;
  }

  function loadStock(callback) {
    const catalogGrid = document.getElementById('stockCatalogGrid');
    if (catalogGrid) {
      catalogGrid.innerHTML = `
        <div style="grid-column: 1/-1; text-align: center; padding: 3rem 1rem; color: #64748b;">
          <i class="fa fa-spinner fa-spin fa-2x" style="color: var(--primary); margin-bottom: 0.8rem;"></i>
          <div>Loading open sale inventory...</div>
        </div>
      `;
    }

    const params = new URLSearchParams({
      action: 'list_stock',
      location: state.branch,
      search: state.searchQuery
    });

    fetch(getApiEndpoint(params.toString()))
      .then(parseApiResponse)
      .then(data => {
        if (data.status === 'success') {
          state.stockTrays = data.trays || [];
          state.allStockTrays = data.trays || [];
          state.groupedProducts = data.grouped_products || [];

          // Pre-cache rates from returned trays
          (data.trays || []).forEach(t => {
            if (t.productid && t.standard_rate && !rateCache[t.productid]) {
              rateCache[t.productid] = parseFloat(t.standard_rate);
            }
          });

          // Update KPI counters based on selected center
          const kpiTrays = document.getElementById('kpiAvailTrays');
          const kpiPlants = document.getElementById('kpiAvailPlants');
          if (state.branch) {
            const b = state.branch.toLowerCase();
            const oscOpt = document.querySelector(`#openSaleCenterDropdown option[value="${CSS.escape(state.branch)}"]`);
            const locId = oscOpt ? (oscOpt.getAttribute('data-locationid') || '').toLowerCase() : '';

            const centerTrays = (data.trays || []).filter(t => isTrayInOpenSaleCenter(t, state.branch, locId));
            if (kpiTrays) kpiTrays.textContent = centerTrays.length.toLocaleString();
            if (kpiPlants) kpiPlants.textContent = centerTrays.reduce((s, t) => s + (parseInt(t.actual_plant_count) || 0), 0).toLocaleString();
          } else {
            if (kpiTrays) kpiTrays.textContent = '0';
            if (kpiPlants) kpiPlants.textContent = '0';
          }

          // Populate Open Sale Center Dropdown if returned
          if (data.opensale_centers && data.opensale_centers.length) {
            populateOpenSaleCenterDropdown(data.opensale_centers);
          }

          populateProductSelectors();
          renderCatalog();

          // Refresh suggestions if search input currently has a search term or is open
          const scanInput = document.getElementById('scanBarcodeInput');
          const suggestBox = document.getElementById('scanSuggestResults');
          if (scanInput && (scanInput.value.trim() || (suggestBox && suggestBox.style.display !== 'none'))) {
            filterAndRenderSuggestions(scanInput.value.trim());
          }

          if (typeof callback === 'function') callback();
        } else {
          if (catalogGrid) {
            catalogGrid.innerHTML = `<div style="grid-column:1/-1; color:#dc2626; padding:1.5rem; text-align:center;">Failed to load stock: ${data.message}</div>`;
          }
        }
      })
      .catch(err => {
        if (catalogGrid) {
          catalogGrid.innerHTML = `<div style="grid-column:1/-1; color:#dc2626; padding:1.5rem; text-align:center;">Connection error: ${err.message}</div>`;
        }
      });
  }

  // -------------------------------------------------------------
  // 3. PRODUCT SELECTOR & SEARCH SUGGESTIONS (NO PERMANENT CARDS)
  // -------------------------------------------------------------
  function populateOpenSaleCenterDropdown(centers) {
    const sel = document.getElementById('openSaleCenterDropdown');
    if (!sel || !centers || !centers.length) return;
    const curVal = sel.value || state.branch || '';
    if (sel.options.length <= 1) {
      let opts = '<option value="">-- Select Open Sale Center --</option>';
      centers.forEach(c => {
        const name = c.polyhousename || '';
        const isSel = (name === curVal);
        opts += `<option value="${escapeHtml(name)}" data-polyhouseid="${escapeHtml(c.polyhouseid || '')}" data-locationid="${escapeHtml(c.locationid || '')}" ${isSel ? 'selected' : ''}>${escapeHtml(name)}</option>`;
      });
      sel.innerHTML = opts;
      if (curVal) sel.value = curVal;
    }
  }

  function populateProductSelectors() {
    // Product dropdown removed as per user requirement: plant search now shows grouped products with sum of qty
  }

  function filterByProduct(groupKey) {
    // Backward compatibility helper
  }

  // Get stock trays filtered by currently selected open sale center
  function getFilteredStockTrays() {
    const selectedCenter = (state.branch || '').trim();
    if (!selectedCenter) {
      // If no center selected, return empty list!
      return [];
    }
    const oscOpt = document.querySelector(`#openSaleCenterDropdown option[value="${CSS.escape(state.branch)}"]`);
    const locId = oscOpt ? (oscOpt.getAttribute('data-locationid') || '').toLowerCase() : '';

    const sourceTrays = (state.stockTrays && state.stockTrays.length > 0) ? state.stockTrays : (state.allStockTrays || []);
    return sourceTrays.filter(t => isTrayInOpenSaleCenter(t, state.branch, locId));
  }

  // Filter in memory across open sale trays in selected center and render product suggestions with sum of qty
  function filterAndRenderSuggestions(query) {
    const suggestBox = document.getElementById('scanSuggestResults');
    if (!suggestBox) return;

    // Check if Open Sale Center is selected
    if (!state.branch) {
      suggestBox.innerHTML = `
        <div class="scan-suggest-empty" style="padding:1.4rem; text-align:center;">
          <i class="fa fa-hand-point-left fa-2x" style="color:var(--primary); margin-bottom:0.5rem; display:block;"></i>
          <strong style="color:#0f172a; font-size:0.92rem;">Please Select Open Sale Center First</strong>
          <div style="font-size:0.8rem; color:#64748b; margin-top:4px;">
            Select an Open Sale Center from the dropdown on the left to view available products and order quantities.
          </div>
        </div>
      `;
      suggestBox.style.display = 'block';
      return;
    }

    const sourceTrays = getFilteredStockTrays();

    if (!query || !query.trim()) {
      // If query is empty, show all available products in selected center!
      renderScanSuggestions(sourceTrays, '');
      return;
    }

    const qLower = query.trim().toLowerCase();

    const matches = sourceTrays.filter(t => {
      return (t.barcode || '').toLowerCase().includes(qLower) ||
        (t.productname || '').toLowerCase().includes(qLower) ||
        (t.variety || '').toLowerCase().includes(qLower) ||
        (t.stockorder_no || '').toLowerCase().includes(qLower) ||
        (t.from_polyhousename || '').toLowerCase().includes(qLower) ||
        (t.from_bed_no || '').toLowerCase().includes(qLower) ||
        (t.open_sale_location || '').toLowerCase().includes(qLower);
    });

    renderScanSuggestions(matches, query.trim());
  }

  // No permanent card display on page - only suggestion box while searching
  function renderCatalog() {
    const catalogGrid = document.getElementById('stockCatalogGrid');
    if (catalogGrid) {
      catalogGrid.innerHTML = '';
      catalogGrid.style.display = 'none';
    }
  }

  // -------------------------------------------------------------
  // 4. BARCODE SCAN & CART MANAGEMENT (PRODUCT GROUPED WITH SUM OF QTY)
  // -------------------------------------------------------------
  let selectedSuggestIndex = 0;

  function renderScanSuggestions(matches, query) {
    const suggestBox = document.getElementById('scanSuggestResults');
    if (!suggestBox) return;

    state.lastSuggestMatches = matches || [];
    state.lastSuggestQuery = query || '';

    const centerName = state.branch ? state.branch : 'All Open Sale Centers';

    if (!matches || matches.length === 0) {
      suggestBox.innerHTML = `
        <div class="scan-suggest-empty">
          <i class="fa fa-circle-exclamation" style="color:#f59e0b; margin-right:6px;"></i>
          ${query ? `No plants found matching "<strong>${escapeHtml(query)}</strong>" in <strong>${escapeHtml(centerName)}</strong>` : `No plants currently available in <strong>${escapeHtml(centerName)}</strong>`}
        </div>
      `;
      suggestBox.style.display = 'block';
      selectedSuggestIndex = -1;
      return;
    }

    // Set of barcodes currently in cart
    const inCartSet = new Set(state.cart.map(c => c.barcode).filter(Boolean));

    // Group matching trays by productid
    const productGroups = {};
    state.lastProductGroups = {};

    matches.forEach(t => {
      const pid = t.productid || 0;
      const pName = t.productname || 'Unknown Product';
      const pVar = t.variety || '';
      const grpKey = `${pid}___${pName}___${pVar}`;

      if (!productGroups[grpKey]) {
        productGroups[grpKey] = {
          groupKey: grpKey,
          productId: pid,
          productName: pName,
          variety: pVar,
          location: state.branch || t.open_sale_location || 'Open Sale Center',
          totalPlants: 0,
          totalTrays: 0,
          pendingPlants: 0,
          pendingTrays: 0,
          inCartPlants: 0,
          inCartTrays: 0,
          barcodes: [],
          pendingBarcodes: [],
          trays: []
        };
      }

      const plants = parseInt(t.actual_plant_count) || 0;
      const inCart = inCartSet.has(t.barcode);

      productGroups[grpKey].totalPlants += plants;
      productGroups[grpKey].totalTrays += 1;
      productGroups[grpKey].barcodes.push(t.barcode);
      productGroups[grpKey].trays.push(t);

      if (inCart) {
        productGroups[grpKey].inCartPlants += plants;
        productGroups[grpKey].inCartTrays += 1;
      } else {
        productGroups[grpKey].pendingPlants += plants;
        productGroups[grpKey].pendingTrays += 1;
        productGroups[grpKey].pendingBarcodes.push(t.barcode);
      }
    });

    state.lastProductGroups = productGroups;
    const groupsList = Object.values(productGroups);

    // Check if query is an exact match for a barcode
    const qLower = (query || '').toLowerCase();
    const exactBarcodeTray = qLower ? matches.find(t => (t.barcode || '').toLowerCase() === qLower) : null;

    // Total available pending plants across all matching groups
    const totalPendingPlants = groupsList.reduce((acc, g) => acc + g.pendingPlants, 0);
    const totalPendingTrays = groupsList.reduce((acc, g) => acc + g.pendingTrays, 0);

    let html = `
      <div class="scan-suggest-header">
        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
          <span style="font-size:0.8rem; font-weight:700; color:#1e293b;">
            <i class="fa fa-boxes-stacked" style="color:var(--primary);"></i> 
            <strong>${groupsList.length}</strong> Product${groupsList.length === 1 ? '' : 's'} in <strong>${escapeHtml(centerName)}</strong>
          </span>
          <span style="font-size:0.75rem; color:#64748b;">
            (${totalPendingPlants.toLocaleString()} Plants / ${totalPendingTrays} Trays Available)
          </span>
        </div>
        <div>
          <button type="button" class="btn-close-suggest" onclick="OpenSalePOS.closeSuggestBox()" title="Close suggestions">&times;</button>
        </div>
      </div>
    `;

    // If exact barcode matched, show an instant direct-tray card on top
    if (exactBarcodeTray) {
      const loc = exactBarcodeTray.open_sale_location || state.branch || 'Open Sale Center';
      const isBcInCart = state.cart.some(c => c.productid == exactBarcodeTray.productid && (c.open_sale_location === loc || !loc || !c.open_sale_location));
      html += `
        <div style="padding:10px 14px; background:#eff6ff; border-bottom:1px solid #bfdbfe; display:flex; justify-content:space-between; align-items:center;">
          <div>
            <span style="font-weight:700; color:#1d4ed8; font-size:0.8rem;">
              <i class="fa fa-barcode"></i> Exact Tray Barcode Matched: <code>${escapeHtml(exactBarcodeTray.barcode)}</code>
            </span>
            <div style="font-size:0.75rem; color:#475569;">
              ${escapeHtml(exactBarcodeTray.productname)} &bull; <strong>${exactBarcodeTray.actual_plant_count} plants</strong> &bull; Location: ${escapeHtml(exactBarcodeTray.open_sale_location || '-')}
            </div>
          </div>
          <div>
            ${isBcInCart ? `
              <button type="button" class="btn-suggest-added" disabled><i class="fa fa-check"></i> In Cart</button>
            ` : `
              <button type="button" class="btn-suggest-add" onclick="event.stopPropagation(); OpenSalePOS.addSingleSuggestTray('${escapeHtml(exactBarcodeTray.barcode)}')">
                <i class="fa fa-plus"></i> Add Product
              </button>
            `}
          </div>
        </div>
      `;
    }

    // Render each product group with sum of available qty in selected open center
    groupsList.forEach((grp, idx) => {
      const loc = grp.location || state.branch || 'Open Sale Center';
      const existingInCart = state.cart.find(c => c.productid == grp.productId && (c.open_sale_location === loc || !loc || !c.open_sale_location));
      const isAlreadyInCart = !!existingInCart;
      const isActive = (idx === 0) ? 'active' : '';

      html += `
        <div class="suggest-product-card ${isActive}" data-index="${idx}" data-group-key="${escapeHtml(grp.groupKey)}">
          <div class="suggest-prod-main">
            
            <div class="suggest-prod-left">
              <div class="suggest-prod-title">
                <i class="fa fa-seedling" style="color:var(--primary); font-size:1.05rem;"></i>
                <span>${escapeHtml(grp.productName)}</span>
                ${grp.variety && grp.variety !== grp.productName ? `<span class="suggest-variety" style="font-size:0.8rem; color:#64748b; font-weight:500;">(${escapeHtml(grp.variety)})</span>` : ''}
              </div>
              <div class="suggest-prod-sub">
                <span class="suggest-loc-badge" style="background:#f1f5f9; color:#334155; padding:2px 8px; border-radius:4px; font-weight:600;">
                  <i class="fa fa-store" style="color:var(--primary);"></i> ${escapeHtml(grp.location)}
                </span>
                <span class="suggest-count-badge">
                  <i class="fa fa-layer-group"></i> Total Trays: <strong>${grp.totalTrays}</strong>
                </span>
                ${isAlreadyInCart && existingInCart.quantity ? `
                  <span class="suggest-incart-badge">
                    <i class="fa fa-check"></i> ${existingInCart.quantity} Plants in Cart
                  </span>
                ` : (isAlreadyInCart ? `
                  <span class="suggest-incart-badge">
                    <i class="fa fa-check"></i> Added in Cart (Enter Qty)
                  </span>
                ` : '')}
              </div>
            </div>

            <!-- SUM OF QUANTITY (PROMINENT DISPLAY) -->
            <div class="suggest-prod-qty-box">
              <div class="suggest-qty-label">Available In Center</div>
              <div class="suggest-qty-val">
                <span class="sum-plants">${grp.totalPlants.toLocaleString()}</span> <small style="font-size:0.72rem;">Plants</small>
              </div>
              <div class="suggest-qty-trays">(${grp.totalTrays} Trays Available)</div>
            </div>

            <!-- ACTION BUTTONS -->
            <div class="suggest-prod-action">
              ${!isAlreadyInCart ? `
                <button type="button" class="btn-add-product-group" 
                        onclick="event.stopPropagation(); OpenSalePOS.addProductGroupToCart('${escapeHtml(grp.groupKey)}')">
                  <i class="fa fa-cart-plus"></i> + Add Product
                </button>
              ` : `
                <button type="button" class="btn-suggest-added" 
                        onclick="event.stopPropagation(); OpenSalePOS.focusCartRow('${escapeHtml(existingInCart.cartKey)}')"
                        style="background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd; padding:8px 14px; border-radius:8px; font-weight:700; cursor:pointer;"
                        title="Click to enter/edit quantity in cart">
                  <i class="fa fa-check"></i> In Cart (Enter Qty)
                </button>
              `}
              <button type="button" class="btn-toggle-trays" 
                      onclick="event.stopPropagation(); OpenSalePOS.toggleGroupTrays('${escapeHtml(grp.groupKey)}')">
                <i class="fa fa-chevron-down tray-chevron" data-group-key="${escapeHtml(grp.groupKey)}"></i> Trays (${grp.totalTrays})
              </button>
            </div>

          </div>

          <!-- Expandable Individual Trays List -->
          <div class="suggest-group-trays-list" data-group-key="${escapeHtml(grp.groupKey)}" style="display:none;">
            <div style="font-size:0.75rem; font-weight:700; color:#64748b; margin-bottom:6px; display:flex; justify-content:space-between; align-items:center;">
              <span>Individual Trays in ${escapeHtml(grp.location)} (${grp.trays.length}):</span>
              ${!isAlreadyInCart ? `
                <a href="javascript:void(0)" style="font-size:0.75rem; color:var(--primary); font-weight:600; text-decoration:none;" 
                   onclick="OpenSalePOS.addProductGroupToCart('${escapeHtml(grp.groupKey)}')">
                  + Add Product to Cart
                </a>
              ` : ''}
            </div>
            ${grp.trays.map(tray => {
        return `
                <div class="suggest-tray-chip-row">
                  <div style="display:flex; align-items:center; gap:8px;">
                    <span class="suggest-barcode-badge"><i class="fa fa-barcode"></i> ${escapeHtml(tray.barcode)}</span>
                    <span style="color:#64748b; font-size:0.75rem;">Bed ${escapeHtml(tray.from_bed_no || '-')} &bull; PH: ${escapeHtml(tray.from_polyhousename || '-')}</span>
                  </div>
                  <div style="display:flex; align-items:center; gap:10px;">
                    <strong style="color:var(--success-dark); font-size:0.82rem;">${tray.actual_plant_count} plants</strong>
                  </div>
                </div>
              `;
      }).join('')}
          </div>

        </div>
      `;
    });

    suggestBox.innerHTML = html;
    suggestBox.style.display = 'block';
    selectedSuggestIndex = 0;
  }

  // Add product to cart - STRICTLY ONLY 1 ROW PER PRODUCT IN SELECTED CENTER!
  // Quantity is entered manually by the user (NOT auto-filled), with Max quantity displayed!
  function addProductGroupToCart(groupKey) {
    let grp = null;
    if (state.lastProductGroups && state.lastProductGroups[groupKey]) {
      grp = state.lastProductGroups[groupKey];
    } else {
      const sourceTrays = getFilteredStockTrays();
      const matchingTrays = sourceTrays.filter(t => {
        const k = `${t.productid || 0}___${t.productname || ''}___${t.variety || ''}`;
        return k === groupKey;
      });
      if (matchingTrays.length > 0) {
        const first = matchingTrays[0];
        const totalAvail = matchingTrays.reduce((sum, t) => sum + (parseInt(t.actual_plant_count) || 0), 0);
        grp = {
          productId: first.productid,
          productName: first.productname,
          variety: first.variety,
          location: state.branch || first.open_sale_location || 'Open Sale Center',
          totalPlants: totalAvail,
          trays: matchingTrays
        };
      }
    }

    if (!grp) {
      showToast('error', 'Product not found in current inventory.');
      return;
    }

    const loc = grp.location || state.branch || 'Open Sale Center';
    const cartKey = `prod_${grp.productId}_${loc}`;
    const existing = state.cart.find(c => c.cartKey === cartKey || (c.productid == grp.productId && c.open_sale_location === loc));

    if (existing) {
      showToast('info', `"${grp.productName}" is already in the cart. Please enter quantity.`);
      focusCartRow(existing.cartKey);
      return;
    }

    lookupProductRate(grp.productId, (rate) => {
      const finalRate = rate || (grp.rate ? parseFloat(grp.rate) : 0);
      state.cart.push({
        cartKey: cartKey,
        mode: 'product_qty',
        stock_id: 0,
        barcode: '',
        productid: grp.productId,
        productname: grp.productName,
        variety: grp.variety,
        open_sale_location: loc,
        available: grp.totalPlants, // Max available quantity in selected center
        quantity: '', // NOT auto filled! Entered manually by user
        foc: 0,
        rate: finalRate,
        discount_percent: 0,
        tax_percent: 0,
        taxableamount: 0,
        tax_amount: 0,
        amount: 0
      });

      renderCartTable();
      calculateBillSummary();
      playBeep(true);
      showToast('success', `Added "${grp.productName}" to cart. Please enter quantity (Max: ${grp.totalPlants}).`);

      focusCartRow(cartKey);
      refreshCurrentSuggestions();
    });
  }

  function focusCartRow(cartKey) {
    setTimeout(() => {
      const row = document.querySelector(`tr[data-key="${CSS.escape(cartKey)}"]`);
      if (row) {
        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
        const qIn = row.querySelector('.cart-qty-input');
        if (qIn) {
          qIn.focus();
          qIn.select();
        }
      }
    }, 60);
  }

  // Toggle individual tray view for a product group
  function toggleGroupTrays(groupKey) {
    const listEl = document.querySelector(`.suggest-group-trays-list[data-group-key="${CSS.escape(groupKey)}"]`);
    if (!listEl) return;
    const isHidden = (listEl.style.display === 'none' || !listEl.style.display);
    listEl.style.display = isHidden ? 'block' : 'none';

    const chevron = document.querySelector(`.tray-chevron[data-group-key="${CSS.escape(groupKey)}"]`);
    if (chevron) {
      chevron.className = isHidden ? 'fa fa-chevron-up tray-chevron' : 'fa fa-chevron-down tray-chevron';
    }
  }

  function updateActiveSuggestion(items) {
    items.forEach((el, idx) => {
      if (idx === selectedSuggestIndex) {
        el.classList.add('active');
        el.scrollIntoView({ block: 'nearest' });
      } else {
        el.classList.remove('active');
      }
    });
  }

  // Row click toggles selection if tray is pending
  function onSuggestItemRowClick(e, barcode) {
    const inCartSet = new Set(state.cart.map(c => c.barcode).filter(Boolean));
    if (inCartSet.has(barcode)) return;

    if (!state.selectedSuggestBarcodes) state.selectedSuggestBarcodes = new Set();
    if (state.selectedSuggestBarcodes.has(barcode)) {
      state.selectedSuggestBarcodes.delete(barcode);
    } else {
      state.selectedSuggestBarcodes.add(barcode);
    }

    const chk = document.querySelector(`.chk-suggest-tray[data-barcode="${CSS.escape(barcode)}"]`);
    if (chk) chk.checked = state.selectedSuggestBarcodes.has(barcode);

    updateSuggestHeaderCounts();
  }

  // Checkbox toggle event
  function onToggleSuggestTray(chk, barcode) {
    if (!state.selectedSuggestBarcodes) state.selectedSuggestBarcodes = new Set();
    if (chk.checked) {
      state.selectedSuggestBarcodes.add(barcode);
    } else {
      state.selectedSuggestBarcodes.delete(barcode);
    }
    updateSuggestHeaderCounts();
  }

  // Select all pending trays in current view
  function onToggleSelectAllSuggest(isChecked) {
    const inCartSet = new Set(state.cart.map(c => c.barcode).filter(Boolean));
    const displayTrays = (state.suggestFilter === 'pending')
      ? (state.lastSuggestMatches || []).filter(t => !inCartSet.has(t.barcode))
      : (state.lastSuggestMatches || []);

    if (!state.selectedSuggestBarcodes) state.selectedSuggestBarcodes = new Set();

    displayTrays.forEach(t => {
      if (!inCartSet.has(t.barcode)) {
        if (isChecked) {
          state.selectedSuggestBarcodes.add(t.barcode);
        } else {
          state.selectedSuggestBarcodes.delete(t.barcode);
        }
      }
    });

    document.querySelectorAll('.chk-suggest-tray:not(:disabled)').forEach(chk => {
      chk.checked = isChecked;
    });

    updateSuggestHeaderCounts();
  }

  // Live count update for "Add Selected (X)" button and "Select All" checkbox
  function updateSuggestHeaderCounts() {
    const inCartSet = new Set(state.cart.map(c => c.barcode).filter(Boolean));
    const pendingTrays = (state.lastSuggestMatches || []).filter(t => !inCartSet.has(t.barcode));
    const validSelected = Array.from(state.selectedSuggestBarcodes || [])
      .filter(bc => pendingTrays.some(t => t.barcode === bc));

    const btnAdd = document.getElementById('btnAddSelectedSuggest');
    if (btnAdd) {
      btnAdd.disabled = validSelected.length === 0;
      btnAdd.innerHTML = `<i class="fa fa-cart-plus"></i> Add Selected (${validSelected.length})`;
    }

    const chkAll = document.getElementById('chkSuggestSelectAll');
    if (chkAll) {
      const displayTrays = (state.suggestFilter === 'pending') ? pendingTrays : (state.lastSuggestMatches || []);
      const pendingInDisplay = displayTrays.filter(t => !inCartSet.has(t.barcode));
      chkAll.checked = pendingInDisplay.length > 0 &&
        pendingInDisplay.every(t => state.selectedSuggestBarcodes.has(t.barcode));
    }
  }

  // Filter tabs: 'all' vs 'pending'
  function setSuggestFilter(filter) {
    state.suggestFilter = filter || 'all';
    renderScanSuggestions(state.lastSuggestMatches, state.lastSuggestQuery);
  }

  function closeSuggestBox() {
    const suggestBox = document.getElementById('scanSuggestResults');
    if (suggestBox) suggestBox.style.display = 'none';
  }

  // Refresh current suggestion box if open
  function refreshCurrentSuggestions() {
    const suggestBox = document.getElementById('scanSuggestResults');
    if (suggestBox && suggestBox.style.display !== 'none' && state.lastSuggestMatches && state.lastSuggestMatches.length > 0) {
      renderScanSuggestions(state.lastSuggestMatches, state.lastSuggestQuery);
    }
  }

  // Add multiple selected trays in batch to cart
  function addSelectedSuggestTrays() {
    const inCartSet = new Set(state.cart.map(c => c.barcode).filter(Boolean));
    const barcodes = Array.from(state.selectedSuggestBarcodes || []).filter(bc => !inCartSet.has(bc));

    if (barcodes.length === 0) {
      showToast('info', 'No pending trays selected.');
      return;
    }

    addMultipleTraysToCart(barcodes);
    state.selectedSuggestBarcodes.clear();
  }

  // Add a single tray from suggestion list -> routes to product group (1 row per product)
  function addSingleSuggestTray(barcode) {
    const sourceTrays = getFilteredStockTrays();
    const tray = sourceTrays.find(t => t.barcode === barcode) || (state.allStockTrays || []).find(t => t.barcode === barcode);
    if (!tray) {
      showToast('error', `Tray ${barcode} not found.`);
      return;
    }
    const grpKey = `${tray.productid || 0}___${tray.productname || ''}___${tray.variety || ''}`;
    addProductGroupToCart(grpKey);
  }

  // Backward compatible alias
  function selectSuggestTray(barcode) {
    addSingleSuggestTray(barcode);
  }

  function addTrayToCart(barcode) {
    addSingleSuggestTray(barcode);
  }

  // Backward-compatible multi-tray addition routing to product groups
  function addMultipleTraysToCart(barcodes) {
    if (!barcodes || barcodes.length === 0) return;
    const sourceTrays = getFilteredStockTrays();
    barcodes.forEach(bc => {
      const tray = sourceTrays.find(t => t.barcode === bc) || (state.allStockTrays || []).find(t => t.barcode === bc);
      if (tray) {
        const grpKey = `${tray.productid || 0}___${tray.productname || ''}___${tray.variety || ''}`;
        addProductGroupToCart(grpKey);
      }
    });
  }

  function initScanBarcodeAutocomplete() {
    const scanInput = document.getElementById('scanBarcodeInput');
    const suggestBox = document.getElementById('scanSuggestResults');
    const clearScanBtn = document.getElementById('btnClearScan');
    if (!scanInput || !suggestBox) return;

    let debounceTimer = null;

    // Input typing & search
    scanInput.addEventListener('input', function () {
      const q = this.value.trim();
      clearTimeout(debounceTimer);
      // Immediately filter and show matching plants grouped by productid with sum of qty
      filterAndRenderSuggestions(q);
    });

    // Keyboard navigation: Up, Down, Enter, Escape
    scanInput.addEventListener('keydown', function (e) {
      const isVisible = suggestBox.style.display === 'block';

      if (e.key === 'Enter') {
        e.preventDefault();
        const val = this.value.trim();
        if (!val) return;

        // 1. Check if val matches an exact tray barcode in stock
        const sourceTrays = getFilteredStockTrays();
        const exactMatch = sourceTrays.find(t => (t.barcode || '').toLowerCase() === val.toLowerCase());
        if (exactMatch) {
          addSingleSuggestTray(exactMatch.barcode);
          this.value = '';
          return;
        }

        // 2. If suggestions are open, add first available product group
        if (isVisible && state.lastProductGroups) {
          const groups = Object.values(state.lastProductGroups);
          const firstWithPending = groups.find(g => g.pendingPlants > 0);
          if (firstWithPending) {
            addProductGroupToCart(firstWithPending.groupKey);
            return;
          }
        }

        // 3. Fallback: Hardware barcode gun scanner scan
        handleBarcodeScan(val);
        this.value = '';
        suggestBox.style.display = 'none';
        return;
      }

      if (e.key === 'Escape') {
        suggestBox.style.display = 'none';
      }
    });

    scanInput.addEventListener('focus', function () {
      filterAndRenderSuggestions(this.value.trim());
    });

    document.addEventListener('click', function (e) {
      if (!scanInput.contains(e.target) && !suggestBox.contains(e.target)) {
        suggestBox.style.display = 'none';
      }
    });

    if (clearScanBtn) {
      clearScanBtn.addEventListener('click', function () {
        scanInput.value = '';
        state.searchQuery = '';
        state.selectedSuggestBarcodes = new Set();
        state.selectedProductKey = '';
        filterAndRenderSuggestions('');
        scanInput.focus();
      });
    }
  }

  function handleBarcodeScan(barcode) {
    barcode = barcode.trim();
    if (!barcode) return;

    // Check if tray exists in loaded stock
    const sourceTrays = getFilteredStockTrays();
    const tray = sourceTrays.find(t => t.barcode.toLowerCase() === barcode.toLowerCase()) ||
      (state.allStockTrays || []).find(t => t.barcode.toLowerCase() === barcode.toLowerCase());

    if (tray) {
      const grpKey = `${tray.productid || 0}___${tray.productname || ''}___${tray.variety || ''}`;
      addProductGroupToCart(grpKey);
    } else {
      // Fetch specifically from server in case not in current view
      fetch(getApiEndpoint(`action=list_stock&search=${encodeURIComponent(barcode)}`))
        .then(parseApiResponse)
        .then(data => {
          if (data.status === 'success' && data.trays && data.trays.length > 0) {
            const found = data.trays.find(t => t.barcode.toLowerCase() === barcode.toLowerCase());
            if (found) {
              if (found.productid && found.standard_rate && !rateCache[found.productid]) {
                rateCache[found.productid] = parseFloat(found.standard_rate);
              }
              state.stockTrays.push(found);
              if (state.allStockTrays) state.allStockTrays.push(found);
              const grpKey = `${found.productid || 0}___${found.productname || ''}___${found.variety || ''}`;
              addProductGroupToCart(grpKey);
              return;
            }
          }
          playBeep(false);
          showToast('error', `Tray ${barcode} not found in Open Sale stock!`);
        })
        .catch(() => {
          playBeep(false);
          showToast('error', `Error checking barcode ${barcode}`);
        });
    }
  }

  // Add product group to cart (routes to unified 1-row product handler)
  function addProductToCart(groupKey) {
    addProductGroupToCart(groupKey);
  }

  // Lookup product standard rate
  function lookupProductRate(productId, callback) {
    if (rateCache[productId] !== undefined) {
      callback(rateCache[productId]);
      return;
    }

    fetch(getApiEndpoint(), {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ action: 'product_rate', productid: productId })
    })
      .then(parseApiResponse)
      .then(data => {
        const rate = (data.status === 'success' && data.rate) ? parseFloat(data.rate) : 0;
        rateCache[productId] = rate;
        callback(rate);
      })
      .catch(() => {
        rateCache[productId] = 0;
        callback(0);
      });
  }

  // -------------------------------------------------------------
  // 5. RENDER CART TABLE & CALCULATE BILL
  // -------------------------------------------------------------
  function renderCartTable() {
    const tbody = document.getElementById('cartTableBody');
    const emptyRow = document.getElementById('cartEmptyRow');
    const btnBook = document.getElementById('btnBookOrder');

    if (!tbody) return;

    if (state.cart.length === 0) {
      tbody.innerHTML = `
        <tr id="cartEmptyRow">
          <td colspan="7" style="text-align: center; padding: 2.5rem 1rem; color: #94a3b8;">
            <i class="fa fa-cart-shopping fa-2x" style="opacity: 0.3; margin-bottom: 0.5rem; display:block;"></i>
            Cart is empty. Scan a barcode or select plants from catalog to begin.
          </td>
        </tr>
      `;
      if (btnBook) btnBook.disabled = true;
      return;
    }

    if (btnBook) btnBook.disabled = false;

    let html = '';
    state.cart.forEach((item, index) => {
      html += `
        <tr data-key="${escapeHtml(item.cartKey)}">
          <td>
            <div style="font-weight:700; color:#1e293b; display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
              <span>${escapeHtml(item.productname)}</span>
              ${item.barcode ? `<span class="cart-item-barcode-badge"><i class="fa fa-barcode"></i> ${escapeHtml(item.barcode)}</span>` : ''}
            </div>
            <div style="font-size:0.75rem; color:#64748b;">
              ${escapeHtml(item.variety || '-')} 
              <span style="font-size:0.68rem; background:#f1f5f9; padding:1px 4px; border-radius:4px;">${escapeHtml(item.open_sale_location)}</span>
            </div>
          </td>
          <td>
            <div class="cart-qty-ctrl-wrap">
              <button type="button" class="btn-qty-step" title="Decrease"
                      onclick="OpenSalePOS.stepCartQty('${escapeHtml(item.cartKey)}', -1)">
                <i class="fa fa-minus"></i>
              </button>
              <input type="number" class="cart-qty-input" 
                     value="${item.quantity !== '' && item.quantity !== null && item.quantity !== undefined ? item.quantity : ''}" 
                     placeholder="Qty"
                     min="1" max="${item.available}" step="1"
                     oninput="OpenSalePOS.onCartQtyInput('${escapeHtml(item.cartKey)}', this.value)"
                     onchange="OpenSalePOS.onCartQtyChange('${escapeHtml(item.cartKey)}', this.value)" />
              <button type="button" class="btn-qty-step" title="Increase"
                      onclick="OpenSalePOS.stepCartQty('${escapeHtml(item.cartKey)}', 1)">
                <i class="fa fa-plus"></i>
              </button>
            </div>
            <div style="font-size:0.72rem; color:#64748b; font-weight:700; text-align:center; margin-top:2px;">
              Max: ${item.available}
            </div>
          </td>
          <td>
            <div style="display:flex; align-items:center; justify-content:center;">
              <input type="number" class="cart-foc-input" 
                     value="${item.foc || 0}" min="0" max="${Math.max(0, (parseInt(item.quantity) || 0) - 1)}" step="1"
                     title="Free of cost (FOC) plants in this tray - not charged for payment"
                     oninput="OpenSalePOS.onCartFocInput('${escapeHtml(item.cartKey)}', this.value)"
                     onchange="OpenSalePOS.onCartFocChange('${escapeHtml(item.cartKey)}', this.value)" />
            </div>
            <div style="font-size:0.68rem; color:#059669; text-align:center; font-weight:600; margin-top:2px;">Free Plant</div>
          </td>
          <td>
            <input type="number" class="cart-rate-input" 
                   value="${item.rate}" min="0" step="0.05"
                   oninput="OpenSalePOS.onCartRateInput('${escapeHtml(item.cartKey)}', this.value)"
                   onchange="OpenSalePOS.onCartRateChange('${escapeHtml(item.cartKey)}', this.value)" />
          </td>
          <td>
            <input type="number" class="cart-disc-input" 
                   value="${item.discount_percent}" min="0" max="100" step="0.5"
                   oninput="OpenSalePOS.onCartDiscInput('${escapeHtml(item.cartKey)}', this.value)"
                   onchange="OpenSalePOS.onCartDiscChange('${escapeHtml(item.cartKey)}', this.value)" />
          </td>
          <td class="cart-row-total" style="text-align:right; font-weight:800; color:#0f172a;">
            ₹${item.amount.toFixed(2)}
          </td>
          <td style="text-align:center;">
            <button type="button" class="btn-remove-client" style="padding:2px 6px;" 
                    onclick="OpenSalePOS.removeCartItem('${escapeHtml(item.cartKey)}')">
              <i class="fa fa-trash"></i>
            </button>
          </td>
        </tr>
      `;
    });

    tbody.innerHTML = html;
  }

  // Live row total cell updater without losing input focus
  function updateRowTotalCell(cartKey, amount) {
    const row = document.querySelector(`tr[data-key="${CSS.escape(cartKey)}"]`);
    if (row) {
      const totalCell = row.querySelector('.cart-row-total');
      if (totalCell) {
        totalCell.textContent = `₹${amount.toFixed(2)}`;
      }
    }
  }

  // Live Qty input event - allows manual entry, caps at max available
  function onCartQtyInput(cartKey, newQty) {
    const item = state.cart.find(c => c.cartKey === cartKey);
    if (!item) return;

    if (newQty === '' || newQty === null || newQty === undefined) {
      item.quantity = '';
      item.foc = 0;
      updateCartItemTotals(item);
      updateRowTotalCell(cartKey, 0);
      calculateBillSummary();
      return;
    }

    let qty = parseInt(newQty);
    if (isNaN(qty) || qty < 0) qty = 0;
    if (qty > item.available) {
      qty = item.available;
      showToast('warning', `Maximum available stock is ${item.available} plants.`);
      const row = document.querySelector(`tr[data-key="${CSS.escape(cartKey)}"]`);
      if (row) {
        const input = row.querySelector('.cart-qty-input');
        if (input) input.value = qty;
      }
    }

    item.quantity = qty;
    if (qty >= 126) {
      item.foc = Math.floor(qty / 126);
    } else {
      item.foc = 0;
    }

    const row = document.querySelector(`tr[data-key="${CSS.escape(cartKey)}"]`);
    if (row) {
      const focInput = row.querySelector('.cart-foc-input');
      if (focInput && document.activeElement !== focInput) {
        focInput.value = item.foc;
      }
    }

    updateCartItemTotals(item);
    updateRowTotalCell(cartKey, item.amount);
    calculateBillSummary();
  }

  function onCartQtyChange(cartKey, newQty) {
    const item = state.cart.find(c => c.cartKey === cartKey);
    if (!item) return;

    if (newQty === '' || newQty === null || newQty === undefined) {
      item.quantity = '';
      item.foc = 0;
      updateCartItemTotals(item);
      renderCartTable();
      calculateBillSummary();
      return;
    }

    let qty = parseInt(newQty);
    if (isNaN(qty) || qty <= 0) {
      item.quantity = '';
      item.foc = 0;
    } else {
      if (qty > item.available) {
        qty = item.available;
        showToast('warning', `Quantity adjusted to maximum available (${item.available}).`);
      }
      item.quantity = qty;
      if (qty >= 126) {
        item.foc = Math.floor(qty / 126);
      }
    }
    updateCartItemTotals(item);
    renderCartTable();
    calculateBillSummary();
  }

  // Live FOC (Free of Cost) input event
  function onCartFocInput(cartKey, newFoc) {
    const item = state.cart.find(c => c.cartKey === cartKey);
    if (!item) return;

    const qty = parseInt(item.quantity) || 0;
    let foc = parseInt(newFoc);
    if (isNaN(foc) || foc < 0) foc = 0;
    if (foc >= qty && qty > 0) foc = qty - 1;
    item.foc = foc;

    updateCartItemTotals(item);
    updateRowTotalCell(cartKey, item.amount);
    calculateBillSummary();
  }

  function onCartFocChange(cartKey, newFoc) {
    const item = state.cart.find(c => c.cartKey === cartKey);
    if (!item) return;

    const qty = parseInt(item.quantity) || 0;
    let foc = parseInt(newFoc) || 0;
    if (foc < 0) foc = 0;
    if (foc >= qty && qty > 0) {
      foc = Math.max(0, qty - 1);
      showToast('warning', `FOC count cannot equal or exceed total quantity.`);
    }
    item.foc = foc;
    updateCartItemTotals(item);
    renderCartTable();
    calculateBillSummary();
  }

  // + and - step buttons for Qty
  function stepCartQty(cartKey, delta) {
    const item = state.cart.find(c => c.cartKey === cartKey);
    if (!item) return;

    let cur = parseInt(item.quantity);
    let nextQty = 0;
    if (isNaN(cur) || cur <= 0) {
      nextQty = delta > 0 ? 1 : 0;
    } else {
      nextQty = cur + delta;
    }

    if (nextQty < 1) nextQty = 1;
    if (nextQty > item.available) {
      nextQty = item.available;
      showToast('warning', `Maximum available stock is ${item.available}`);
    }
    item.quantity = nextQty;
    if (nextQty >= 126) {
      item.foc = Math.floor(nextQty / 126);
    } else {
      item.foc = 0;
    }

    updateCartItemTotals(item);
    renderCartTable();
    calculateBillSummary();
  }

  // Live Rate input event
  function onCartRateInput(cartKey, newRate) {
    const item = state.cart.find(c => c.cartKey === cartKey);
    if (!item) return;

    let rate = parseFloat(newRate);
    if (isNaN(rate) || rate < 0) rate = 0;
    item.rate = rate;

    updateCartItemTotals(item);
    updateRowTotalCell(cartKey, item.amount);
    calculateBillSummary();
  }

  function onCartRateChange(cartKey, newRate) {
    const item = state.cart.find(c => c.cartKey === cartKey);
    if (!item) return;

    const rate = Math.max(0, parseFloat(newRate) || 0);
    item.rate = rate;
    updateCartItemTotals(item);
    renderCartTable();
    calculateBillSummary();
  }

  // Live Discount% input event
  function onCartDiscInput(cartKey, newDisc) {
    const item = state.cart.find(c => c.cartKey === cartKey);
    if (!item) return;

    let disc = parseFloat(newDisc);
    if (isNaN(disc) || disc < 0) disc = 0;
    if (disc > 100) disc = 100;
    item.discount_percent = disc;

    updateCartItemTotals(item);
    updateRowTotalCell(cartKey, item.amount);
    calculateBillSummary();
  }

  function onCartDiscChange(cartKey, newDisc) {
    const item = state.cart.find(c => c.cartKey === cartKey);
    if (!item) return;

    const disc = Math.min(100, Math.max(0, parseFloat(newDisc) || 0));
    item.discount_percent = disc;
    updateCartItemTotals(item);
    renderCartTable();
    calculateBillSummary();
  }

  function removeCartItem(cartKey) {
    state.cart = state.cart.filter(c => c.cartKey !== cartKey);
    renderCartTable();
    calculateBillSummary();
    renderCatalog();
    refreshCurrentSuggestions();
    showToast('info', 'Item removed from cart');
  }

  function updateCartItemTotals(item) {
    const qty = parseInt(item.quantity) || 0;
    const foc = Math.max(0, parseInt(item.foc) || 0);
    const billableQty = Math.max(0, qty - foc);
    const gross = round(billableQty * (parseFloat(item.rate) || 0));
    const discAmt = round(gross * ((parseFloat(item.discount_percent) || 0) / 100));
    const taxable = round(gross - discAmt);
    const taxAmt = round(taxable * ((parseFloat(item.tax_percent) || 0) / 100));
    item.taxableamount = taxable;
    item.tax_amount = taxAmt;
    item.amount = round(taxable + taxAmt);
  }

  function autoCalculateBoxes() {
    const chk = document.getElementById('chkEnableBoxPacking');
    if (chk && !chk.checked) {
      chk.checked = true;
      state.boxPacking.enabled = true;
      const body = document.getElementById('boxPackingBody');
      if (body) body.style.display = 'block';
    }
    const totalPlants = state.cart.reduce((sum, item) => sum + (parseInt(item.quantity) || 0), 0);
    const cap = state.boxPacking.capacity || 500;
    const boxes = totalPlants > 0 ? Math.ceil(totalPlants / cap) : 0;
    const boxInput = document.getElementById('cartBoxCount');
    if (boxInput) {
      boxInput.value = boxes;
    }
    state.boxPacking.boxCount = boxes;
    calculateBillSummary();
  }

  function calculateBillSummary() {
    let subtotal = 0;
    let totalDiscount = 0;
    let totalTax = 0;
    let totalPlants = 0;
    let totalFocPlants = 0;
    let totalBillablePlants = 0;

    state.cart.forEach(item => {
      const qty = parseInt(item.quantity) || 0;
      const foc = Math.max(0, parseInt(item.foc) || 0);
      const billableQty = Math.max(0, qty - foc);
      const gross = round(billableQty * (parseFloat(item.rate) || 0));
      const disc = round(gross * ((parseFloat(item.discount_percent) || 0) / 100));
      subtotal += (gross - disc);
      totalDiscount += disc;
      totalTax += item.tax_amount;
      totalPlants += qty;
      totalFocPlants += foc;
      totalBillablePlants += billableQty;
    });

    // Update Box Packing Total Plants Display
    const boxPlantsDisp = document.getElementById('cartBoxDispatchPlantsDisplay');
    if (boxPlantsDisp) {
      boxPlantsDisp.value = `${totalPlants.toLocaleString()} Plants`;
    }

    // Box Packing Charge calculation
    let boxTotal = 0;
    const chkBox = document.getElementById('chkEnableBoxPacking');
    const isBoxEnabled = chkBox ? chkBox.checked : false;
    state.boxPacking.enabled = isBoxEnabled;

    if (isBoxEnabled) {
      const bCountInput = document.getElementById('cartBoxCount');
      const bRateInput = document.getElementById('cartBoxRate');
      const boxCount = bCountInput ? Math.max(0, parseInt(bCountInput.value) || 0) : 0;
      const boxRate = bRateInput ? Math.max(0, parseFloat(bRateInput.value) || 0) : (state.boxPacking.rate || 100);
      boxTotal = round(boxCount * boxRate);
      state.boxPacking.boxCount = boxCount;
      state.boxPacking.rate = boxRate;
      state.boxPacking.totalCharge = boxTotal;

      const totChargeDisp = document.getElementById('cartBoxTotalCharge');
      if (totChargeDisp) totChargeDisp.value = boxTotal.toFixed(2);

      const formulaDisp = document.getElementById('cartBoxFormulaText');
      if (formulaDisp) {
        formulaDisp.textContent = `${boxCount} boxes × ₹ ${boxRate.toFixed(2)} = ₹ ${boxTotal.toFixed(2)}`;
      }
    } else {
      state.boxPacking.totalCharge = 0;
      const totChargeDisp = document.getElementById('cartBoxTotalCharge');
      if (totChargeDisp) totChargeDisp.value = '0.00';
      const formulaDisp = document.getElementById('cartBoxFormulaText');
      if (formulaDisp) {
        const rate = state.boxPacking.rate || 100;
        formulaDisp.textContent = `0 boxes × ₹ ${rate.toFixed(2)} = ₹ 0.00`;
      }
    }

    // Add box packing to subtotal / taxable total
    subtotal += boxTotal;

    const grossTotal = subtotal + totalTax;
    const roundedTotal = Math.round(grossTotal);
    const roundOff = round(roundedTotal - grossTotal);
    const grandTotal = roundedTotal;

    // Special Discount calculation
    const spDiscInput = document.getElementById('cartSpecialDiscount');
    const specialDiscount = spDiscInput ? Math.max(0, parseFloat(spDiscInput.value) || 0) : 0;
    state.specialDiscount = specialDiscount;
    const currentSaleNet = Math.max(0, round(grandTotal - specialDiscount));

    // Calculate total of selected pending orders
    let pendingSelectedTotal = 0;
    if (state.selectedPendingOrders) {
      Object.values(state.selectedPendingOrders).forEach(p => {
        pendingSelectedTotal += (parseFloat(p.pay_amount) || 0);
      });
    }
    pendingSelectedTotal = round(pendingSelectedTotal);

    // Total Payable Amount = Current Sale Net + Selected Pending Orders Total
    const totalPayable = round(currentSaleNet + pendingSelectedTotal);
    state.totalPayable = totalPayable;
    state.currentSaleNet = currentSaleNet;
    state.pendingSelectedTotal = pendingSelectedTotal;

    // Balance calculation
    const paidInput = document.getElementById('cartPaidAmount');
    if (state.isMultiMode) {
      let multiTotal = 0;
      if (state.splitPaymentRows && state.splitPaymentRows.length > 0) {
        state.splitPaymentRows.forEach(r => {
          multiTotal += (parseFloat(r.amount) || 0);
        });
      } else {
        state.activePaymentModes.forEach(m => {
          multiTotal += (state.splitPayments[m]?.amount || 0);
        });
      }
      multiTotal = round(multiTotal);
      state.amountPaid = multiTotal;
      if (paidInput && document.activeElement !== paidInput) {
        paidInput.value = multiTotal > 0 ? (Number.isInteger(multiTotal) ? multiTotal : multiTotal.toFixed(2)) : 0;
      }
      updateMultiPayFooter();
    } else {
      if (paidInput) {
        if (!state.isCustomPaid || paidInput.dataset.touched !== 'true') {
          // Auto-sync Amount Paid with Total Payable whenever cart items, special discount or pending orders change
          state.amountPaid = totalPayable;
          if (document.activeElement !== paidInput) {
            paidInput.value = totalPayable > 0 ? (Number.isInteger(totalPayable) ? totalPayable : totalPayable.toFixed(2)) : 0;
          }
        } else {
          // User manually entered a custom partial payment
          state.amountPaid = parseFloat(paidInput.value) || 0;
          if (totalPayable === 0 && !state.amountPaid && document.activeElement !== paidInput) {
            paidInput.value = 0;
            state.isCustomPaid = false;
            paidInput.dataset.touched = 'false';
          }
        }
      }
    }

    const balance = Math.max(0, round(totalPayable - state.amountPaid));

    // Update DOM
    const elSubtotal = document.getElementById('billSubtotal');
    const elDisc = document.getElementById('billTotalDiscount');
    const elTax = document.getElementById('billTaxAmount');
    const elRound = document.getElementById('billRoundOff');
    const elGrand = document.getElementById('billGrandTotal');
    const elBalance = document.getElementById('billBalanceAmount');
    const elPlantsCount = document.getElementById('cartTotalPlantsCount');
    const elSpDiscRow = document.getElementById('billSpecialDiscountRow');
    const elSpDisc = document.getElementById('billSpecialDiscount');
    const elBoxRow = document.getElementById('billBoxPackingRow');
    const elBoxAmt = document.getElementById('billBoxPackingAmount');
    const elBoxCountLbl = document.getElementById('billBoxCountLabel');
    const elPendingRow = document.getElementById('billPendingOrdersRow');
    const elPendingTotal = document.getElementById('billPendingOrdersTotal');
    const elTotalPayable = document.getElementById('billTotalPayableAmount');
    const elPayFigureTotalPayable = document.getElementById('payFigureTotalPayable');

    if (elSubtotal) elSubtotal.textContent = `₹${subtotal.toFixed(2)}`;
    if (elDisc) elDisc.textContent = `-₹${totalDiscount.toFixed(2)}`;
    if (elTax) elTax.textContent = `₹${totalTax.toFixed(2)}`;
    if (elRound) elRound.textContent = `${roundOff >= 0 ? '+' : ''}₹${roundOff.toFixed(2)}`;
    if (elGrand) elGrand.textContent = `₹${grandTotal.toFixed(2)}`;

    // Box Packing Charges Row in summary
    if (elBoxRow && elBoxAmt) {
      if (isBoxEnabled && boxTotal > 0) {
        elBoxRow.style.display = 'flex';
        elBoxAmt.textContent = `+₹${boxTotal.toFixed(2)}`;
        if (elBoxCountLbl) elBoxCountLbl.textContent = state.boxPacking.boxCount;
      } else {
        elBoxRow.style.display = 'none';
      }
    }
    if (elSpDiscRow && elSpDisc) {
      if (specialDiscount > 0) {
        elSpDiscRow.style.display = 'flex';
        elSpDisc.textContent = `-₹${specialDiscount.toFixed(2)}`;
      } else {
        elSpDiscRow.style.display = 'none';
      }
    }

    // Pending Orders Row in summary
    if (elPendingRow && elPendingTotal) {
      if (pendingSelectedTotal > 0) {
        elPendingRow.style.display = 'flex';
        elPendingTotal.textContent = `+₹${pendingSelectedTotal.toFixed(2)}`;
      } else {
        elPendingRow.style.display = 'none';
      }
    }

    // Total Payable Amount in summary and payment figure
    if (elTotalPayable) elTotalPayable.textContent = `₹${totalPayable.toFixed(2)}`;
    if (elPayFigureTotalPayable) elPayFigureTotalPayable.textContent = `₹${totalPayable.toFixed(2)}`;

    if (elBalance) {
      elBalance.textContent = `₹${balance.toFixed(2)}`;
      if (balance > 0) {
        elBalance.style.color = '#dc2626';
        elBalance.style.background = '#fef2f2';
        elBalance.style.borderColor = '#fecaca';
      } else {
        elBalance.style.color = '#15803d';
        elBalance.style.background = '#f0fdf4';
        elBalance.style.borderColor = '#bbf7d0';
      }
    }
    if (elPlantsCount) {
      if (totalFocPlants > 0) {
        elPlantsCount.innerHTML = `<strong>${totalPlants.toLocaleString()}</strong> <span style="font-size:0.72rem; color:#059669; font-weight:700;">(Paid: ${totalBillablePlants.toLocaleString()} &bull; FOC: ${totalFocPlants.toLocaleString()})</span>`;
      } else {
        elPlantsCount.textContent = totalPlants.toLocaleString();
      }
    }
  }

  function getGrandTotal() {
    let subtotal = 0;
    let totalTax = 0;
    state.cart.forEach(item => {
      const foc = Math.max(0, parseInt(item.foc) || 0);
      const billableQty = Math.max(0, item.quantity - foc);
      const gross = round(billableQty * item.rate);
      const disc = round(gross * (item.discount_percent / 100));
      subtotal += (gross - disc);
      totalTax += item.tax_amount;
    });
    if (state.boxPacking && state.boxPacking.enabled && state.boxPacking.totalCharge > 0) {
      subtotal += state.boxPacking.totalCharge;
    }
    return Math.round(subtotal + totalTax);
  }

  // -------------------------------------------------------------
  // 6. CLIENT SEARCH AUTOCOMPLETE & MANAGEMENT (BY MOBILE OR NAME)
  // -------------------------------------------------------------
  function initClientAutocomplete() {
    const input = document.getElementById('clientSearchInput');
    const resultsBox = document.getElementById('clientSuggestResults');
    const btnRefresh = document.getElementById('btnRefreshClient');
    if (!input || !resultsBox) return;

    let debounceTimer = null;
    let selectedClientIndex = -1;
    let currentClientsList = [];

    function renderClientsList(clients, query) {
      currentClientsList = clients || [];
      selectedClientIndex = -1;

      if (clients && clients.length > 0) {
        let html = `
          <div class="scan-suggest-header">
            <span><i class="fa fa-users"></i> Available Clients (${clients.length})</span>
            <span>&uarr; &darr; to navigate &bull; Enter to select</span>
          </div>
        `;
        clients.forEach((c, idx) => {
          html += `
            <div class="client-suggest-item" data-index="${idx}" onclick='OpenSalePOS.selectClient(${JSON.stringify(c).replace(/'/g, "&#39;")})'>
              <div>
                <div class="client-suggest-name">
                  <i class="fa fa-user" style="color:var(--primary); font-size:0.8rem; margin-right:4px;"></i>
                  ${escapeHtml(c.name)}
                </div>
                <div class="client-suggest-sub">
                  <i class="fa fa-phone" style="color:#059669;"></i> <strong>${escapeHtml(c.phone || '-')}</strong>
                  ${c.district ? ` &bull; <i class="fa fa-location-dot"></i> ${escapeHtml(c.district)}${c.state ? ', ' + escapeHtml(c.state) : ''}` : ''}
                </div>
              </div>
              <span style="font-size:0.75rem; font-weight:700; color:var(--primary);">Select &rarr;</span>
            </div>
          `;
        });
        html += `
          <div class="client-suggest-footer" onclick="OpenSalePOS.openNewClientModalWithQuery('${escapeHtml(query)}')">
            <i class="fa fa-user-plus" style="color:var(--primary);"></i>
            <span>Not listed? <strong>+ Add "${escapeHtml(query || 'New Client')}" as New Client</strong></span>
          </div>
        `;
        resultsBox.innerHTML = html;
        resultsBox.style.display = 'block';
      } else {
        resultsBox.innerHTML = `
          <div class="client-not-found-card" onclick="OpenSalePOS.openNewClientModalWithQuery('${escapeHtml(query)}')">
            <div style="font-weight:700; color:#334155; margin-bottom:4px;">
              <i class="fa fa-user-xmark" style="color:#ef4444; margin-right:6px;"></i> No client found matching "<strong>${escapeHtml(query)}</strong>"
            </div>
            <div style="font-size:0.8rem; color:#64748b; margin-bottom:8px;">Search by mobile number or client name, or add as a new client:</div>
            <button type="button" class="btn-add-new-client-inline">
              <i class="fa fa-user-plus"></i> + Add "${escapeHtml(query)}" as New Client
            </button>
          </div>
        `;
        resultsBox.style.display = 'block';
      }
    }

    function updateActiveClient(items) {
      items.forEach((el, idx) => {
        if (idx === selectedClientIndex) {
          el.classList.add('active');
          el.scrollIntoView({ block: 'nearest' });
        } else {
          el.classList.remove('active');
        }
      });
    }

    function searchClients(q) {
      if (!q || q.length < 3) {
        resultsBox.style.display = 'none';
        resultsBox.innerHTML = '';
        currentClientsList = [];
        return;
      }
      fetch(getApiEndpoint(`action=search_clients&q=${encodeURIComponent(q)}`))
        .then(parseApiResponse)
        .then(data => {
          if (data.status === 'success') {
            const curVal = input.value.trim();
            if (curVal.length >= 3) {
              renderClientsList(data.clients || [], q);
            }
          }
        })
        .catch(() => {
          resultsBox.style.display = 'none';
        });
    }

    input.addEventListener('input', function () {
      const q = this.value.trim();
      clearTimeout(debounceTimer);
      if (q.length < 3) {
        resultsBox.style.display = 'none';
        resultsBox.innerHTML = '';
        currentClientsList = [];
        return;
      }
      debounceTimer = setTimeout(() => {
        searchClients(q);
      }, 200);
    });

    // Keyboard navigation: Up, Down, Enter, Escape
    input.addEventListener('keydown', function (e) {
      const isVisible = resultsBox.style.display === 'block';
      const items = resultsBox.querySelectorAll('.client-suggest-item');

      if (e.key === 'ArrowDown') {
        if (!isVisible || items.length === 0) {
          if (this.value.trim().length >= 3) {
            searchClients(this.value.trim());
          }
          return;
        }
        e.preventDefault();
        selectedClientIndex = (selectedClientIndex + 1) % items.length;
        updateActiveClient(items);
        return;
      }

      if (e.key === 'ArrowUp') {
        if (!isVisible || items.length === 0) return;
        e.preventDefault();
        selectedClientIndex = (selectedClientIndex - 1 + items.length) % items.length;
        updateActiveClient(items);
        return;
      }

      if (e.key === 'Enter') {
        e.preventDefault();
        if (isVisible && currentClientsList.length > 0 && selectedClientIndex >= 0 && currentClientsList[selectedClientIndex]) {
          selectClient(currentClientsList[selectedClientIndex]);
          return;
        }
        if (isVisible && currentClientsList.length > 0 && selectedClientIndex === -1) {
          selectClient(currentClientsList[0]);
          return;
        }
        const val = this.value.trim();
        if (val && val.length >= 3) {
          openNewClientModalWithQuery(val);
        }
        return;
      }

      if (e.key === 'Escape') {
        resultsBox.style.display = 'none';
      }
    });

    input.addEventListener('focus', function () {
      const q = this.value.trim();
      if (q.length >= 3) {
        searchClients(q);
      } else {
        resultsBox.style.display = 'none';
      }
    });

    // Dismiss on outside click
    document.addEventListener('click', function (e) {
      if (!input.contains(e.target) && !resultsBox.contains(e.target)) {
        resultsBox.style.display = 'none';
      }
    });

    // Manual Refresh button
    if (btnRefresh) {
      btnRefresh.addEventListener('click', function () {
        const icon = this.querySelector('i');
        if (icon) icon.classList.add('fa-spin');
        input.value = '';
        resultsBox.style.display = 'none';
        searchClients('');
        setTimeout(() => {
          if (icon) icon.classList.remove('fa-spin');
          showToast('info', 'Client list refreshed');
          input.focus();
        }, 350);
      });
    }
  }

  function openNewClientModalWithQuery(query) {
    openNewClientModal();
    const nameInput = document.getElementById('newClientName');
    const phoneInput = document.getElementById('newClientPhone');

    // Check if query is primarily digits (phone number)
    const isPhone = /^[0-9+\s-]{4,}$/.test(query);
    if (isPhone) {
      if (phoneInput) phoneInput.value = query.replace(/\s+/g, '');
      if (nameInput) {
        nameInput.value = '';
        nameInput.focus();
      }
    } else {
      if (nameInput) nameInput.value = query || '';
      if (phoneInput) phoneInput.focus();
    }

    const resultsBox = document.getElementById('clientSuggestResults');
    if (resultsBox) resultsBox.style.display = 'none';
  }

  function selectClient(c) {
    state.selectedClient = c;
    const input = document.getElementById('clientSearchInput');
    const resultsBox = document.getElementById('clientSuggestResults');
    const badge = document.getElementById('clientSelectedBadge');
    const nameEl = document.getElementById('selectedClientName');
    const subEl = document.getElementById('selectedClientSub');

    if (resultsBox) resultsBox.style.display = 'none';
    if (input) input.value = '';

    if (badge && nameEl && subEl) {
      nameEl.textContent = c.name;
      subEl.textContent = `ID: ${c.id} | Phone: ${c.phone || '-'} | ${c.district || ''}, ${c.state || ''}`;
      badge.style.display = 'flex';
    }

    const searchWrap = document.getElementById('clientSearchWrap');
    if (searchWrap) searchWrap.style.display = 'none';

    showToast('success', `Selected: ${c.name}`);

    // Update box packing rate/capacity from client master if configured
    if (c.boxperpack && parseInt(c.boxperpack) > 0) {
      state.boxPacking.capacity = parseInt(c.boxperpack);
      const capHint = document.getElementById('cartBoxCapacityHint');
      if (capHint) capHint.textContent = `Capacity: ${c.boxperpack} plants / box`;
    }
    if (c.chargesperbox && parseFloat(c.chargesperbox) > 0) {
      state.boxPacking.rate = parseFloat(c.chargesperbox);
      const rateInput = document.getElementById('cartBoxRate');
      if (rateInput) rateInput.value = state.boxPacking.rate.toFixed(2);
      const badge = document.getElementById('boxRateBadge');
      if (badge) badge.textContent = `₹ ${state.boxPacking.rate.toFixed(2)} / Box`;
      const hint = document.getElementById('cartBoxFixedRateHint');
      if (hint) hint.textContent = `Fixed charge: ₹${state.boxPacking.rate.toFixed(0)} per box`;
    }

    // Load pending orders for this selected client
    loadClientPendingOrders(c.id);
  }

  function clearSelectedClient() {
    state.selectedClient = null;
    state.pendingOrders = [];
    state.selectedPendingOrders = {};
    const badge = document.getElementById('clientSelectedBadge');
    const searchWrap = document.getElementById('clientSearchWrap');
    const input = document.getElementById('clientSearchInput');
    const pendingSection = document.getElementById('pendingOrdersSection');
    const pendingBody = document.getElementById('pendingOrdersTableBody');
    const foot = document.getElementById('pendingOrdersTableFoot');

    if (badge) badge.style.display = 'none';
    if (searchWrap) searchWrap.style.display = 'flex';
    if (input) {
      input.value = '';
      input.focus();
    }
    if (pendingSection) pendingSection.style.display = 'none';
    if (pendingBody) pendingBody.innerHTML = '';
    if (foot) foot.style.display = 'none';
    updatePendingSelectedTotal();
  }

  // -------------------------------------------------------------
  // CLIENT PENDING ORDERS FOR PAYMENT COLLECTION
  // -------------------------------------------------------------
  function loadClientPendingOrders(clientId) {
    const section = document.getElementById('pendingOrdersSection');
    const tbody = document.getElementById('pendingOrdersTableBody');
    const badge = document.getElementById('pendingOrdersCountBadge');
    const foot = document.getElementById('pendingOrdersTableFoot');
    const chkAll = document.getElementById('chkSelectAllPendingOrders');

    if (!section || !tbody) return;

    state.pendingOrders = [];
    state.selectedPendingOrders = {};
    if (chkAll) chkAll.checked = false;
    updatePendingSelectedTotal();

    tbody.innerHTML = `<tr><td colspan="9" style="text-align:center; padding:14px; color:#64748b;"><i class="fa fa-spinner fa-spin"></i> Checking pending orders for payment...</td></tr>`;
    section.style.display = 'block';

    fetch(getApiEndpoint(`action=get_client_pending_orders&clientid=${encodeURIComponent(clientId)}`))
      .then(parseApiResponse)
      .then(data => {
        if (data.status === 'success' && data.orders && data.orders.length > 0) {
          state.pendingOrders = data.orders;
          if (badge) badge.textContent = `${data.orders.length} Pending`;
          renderPendingOrdersTable(data.orders);
        } else {
          state.pendingOrders = [];
          if (badge) badge.textContent = '0 Pending';
          tbody.innerHTML = `
            <tr>
              <td colspan="9" style="text-align:center; padding:14px; color:#15803d; font-weight:600; font-size:0.83rem;">
                <i class="fa fa-circle-check"></i> No pending order balance found for this client.
              </td>
            </tr>
          `;
          if (foot) foot.style.display = 'none';
        }
      })
      .catch(() => {
        tbody.innerHTML = `<tr><td colspan="9" style="text-align:center; padding:14px; color:#ef4444;"><i class="fa fa-triangle-exclamation"></i> Failed to retrieve pending orders.</td></tr>`;
      });
  }

  function renderPendingOrdersTable(orders) {
    const tbody = document.getElementById('pendingOrdersTableBody');
    if (!tbody) return;

    let html = '';
    orders.forEach(ord => {
      const isChecked = !!state.selectedPendingOrders[ord.psono];
      const payVal = isChecked ? (state.selectedPendingOrders[ord.psono].pay_amount ?? ord.balance_amount) : ord.balance_amount;
      const hasSO = (ord.sono && String(ord.sono).trim() !== '');

      html += `
        <tr class="pending-order-row ${isChecked ? 'row-selected' : ''}" id="pso_row_${escapeHtml(ord.psono)}">
          <td style="text-align:center;">
            <input type="checkbox" class="chk-pending-order" data-psono="${escapeHtml(ord.psono)}" ${isChecked ? 'checked' : ''} onchange="OpenSalePOS.onTogglePendingOrder('${escapeHtml(ord.psono)}', this.checked)" />
          </td>
          <td>
            <div style="font-family:'JetBrains Mono',monospace; font-weight:700; color:#1e293b; font-size:0.83rem;">
              ${escapeHtml(ord.psono)}
            </div>
            ${ord.sono ? `<div style="font-size:0.73rem; color:#059669; font-weight:700;">SO: ${escapeHtml(ord.sono)}</div>` : ''}

            <!-- Order PDF View buttons: Provisional or Final Sales Order -->
            <div style="display:flex; align-items:center; gap:4px; margin-top:5px; flex-wrap:wrap;">
              ${hasSO ? `
                <button type="button" class="btn-order-pdf-pill btn-so-pdf-pill" 
                        onclick="OpenSalePOS.openOrderPdfModal('${escapeHtml(ord.psono)}', '${escapeHtml(ord.sono || '')}', 'so', '${escapeHtml(ord.companyid || '')}', '${escapeHtml(ord.subcompanyid || '')}', '${escapeHtml(ord.projectid || '')}')" 
                        title="Preview Final Sales Order PDF">
                  <i class="fa fa-file-pdf"></i> Final SO PDF
                </button>
                <button type="button" class="btn-order-pdf-pill btn-pso-mini-pill" 
                        onclick="OpenSalePOS.openOrderPdfModal('${escapeHtml(ord.psono)}', '', 'pso', '${escapeHtml(ord.companyid || '')}', '${escapeHtml(ord.subcompanyid || '')}', '${escapeHtml(ord.projectid || '')}')" 
                        title="Preview Provisional Order PDF">
                  <i class="fa fa-file-lines"></i> Provisional
                </button>
              ` : `
                <button type="button" class="btn-order-pdf-pill btn-pso-pdf-pill" 
                        onclick="OpenSalePOS.openOrderPdfModal('${escapeHtml(ord.psono)}', '', 'pso', '${escapeHtml(ord.companyid || '')}', '${escapeHtml(ord.subcompanyid || '')}', '${escapeHtml(ord.projectid || '')}')" 
                        title="Preview Provisional Order PDF">
                  <i class="fa fa-file-invoice"></i> Provisional PDF
                </button>
              `}
            </div>
          </td>
          <td>
            <div style="font-weight:600; color:#334155; font-size:0.82rem; max-width:280px; word-break:break-word;">
              ${escapeHtml(ord.product_name)}
            </div>
          </td>
          <td style="white-space:nowrap; font-size:0.8rem; color:#475569;">
            <i class="fa fa-calendar-day" style="color:var(--primary); font-size:0.75rem;"></i> ${escapeHtml(ord.expecteddeliverydate)}
          </td>
          <td style="text-align:right; font-weight:700; color:#334155;">
            ${ord.qty.toLocaleString()}
          </td>
          <td style="text-align:right; color:#64748b; font-size:0.82rem;">
            ₹${ord.order_amount.toFixed(2)}
          </td>
          <td style="text-align:right; color:#059669; font-size:0.82rem;">
            ₹${ord.paid_amount.toFixed(2)}
          </td>
          <td style="text-align:right; font-weight:800; color:#dc2626; font-size:0.88rem;">
            ₹${ord.balance_amount.toFixed(2)}
          </td>
          <td style="text-align:right;">
            <div style="display:inline-flex; align-items:center; position:relative; width:120px;">
              <span style="position:absolute; left:6px; color:#64748b; font-weight:700; font-size:0.8rem;">₹</span>
              <input type="number" 
                     id="pay_input_${escapeHtml(ord.psono)}" 
                     class="pending-pay-amt-input" 
                     value="${payVal}" 
                     min="0.01" 
                     max="${ord.balance_amount}" 
                     step="1" 
                     ${isChecked ? '' : 'disabled'}
                     oninput="OpenSalePOS.onPendingPayAmountInput('${escapeHtml(ord.psono)}', this.value)"
                     style="width:100%; padding-left:18px; padding-right:6px; height:28px; font-size:0.84rem; font-weight:700; text-align:right; border:1px solid ${isChecked ? '#2563eb' : '#cbd5e1'}; border-radius:6px; background:${isChecked ? '#fff' : '#f8fafc'}; color:${isChecked ? '#0f172a' : '#94a3b8'};" />
            </div>
          </td>
        </tr>
      `;
    });

    tbody.innerHTML = html;
    updatePendingSelectedTotal();
  }

  function onTogglePendingOrder(psono, isChecked) {
    const ord = state.pendingOrders.find(o => o.psono === psono);
    if (!ord) return;

    const row = document.getElementById(`pso_row_${psono}`);
    const input = document.getElementById(`pay_input_${psono}`);

    if (isChecked) {
      const payVal = input ? (parseFloat(input.value) || ord.balance_amount) : ord.balance_amount;
      state.selectedPendingOrders[psono] = {
        psono: ord.psono,
        sono: ord.sono || '',
        pending_amount: ord.balance_amount,
        pay_amount: payVal
      };
      if (input) {
        input.disabled = false;
        input.style.border = '1px solid #2563eb';
        input.style.background = '#ffffff';
        input.style.color = '#0f172a';
      }
      if (row) row.classList.add('row-selected');
    } else {
      delete state.selectedPendingOrders[psono];
      if (input) {
        input.disabled = true;
        input.style.border = '1px solid #cbd5e1';
        input.style.background = '#f8fafc';
        input.style.color = '#94a3b8';
      }
      if (row) row.classList.remove('row-selected');
    }

    const chkAll = document.getElementById('chkSelectAllPendingOrders');
    if (chkAll) {
      const allPsono = state.pendingOrders.map(o => o.psono);
      chkAll.checked = allPsono.length > 0 && allPsono.every(p => !!state.selectedPendingOrders[p]);
    }

    updatePendingSelectedTotal();
  }

  function onPendingPayAmountInput(psono, val) {
    if (state.selectedPendingOrders[psono]) {
      const amt = parseFloat(val) || 0;
      state.selectedPendingOrders[psono].pay_amount = amt;
      updatePendingSelectedTotal();
    }
  }

  function onToggleAllPendingOrders(isChecked) {
    state.pendingOrders.forEach(ord => {
      const chk = document.querySelector(`.chk-pending-order[data-psono="${ord.psono}"]`);
      if (chk) chk.checked = isChecked;
      onTogglePendingOrder(ord.psono, isChecked);
    });
  }

  function updatePendingSelectedTotal() {
    let total = 0;
    let count = 0;
    Object.values(state.selectedPendingOrders).forEach(p => {
      total += (parseFloat(p.pay_amount) || 0);
      count++;
    });

    const display = document.getElementById('totalSelectedPendingPayDisplay');
    if (display) {
      display.textContent = `₹${total.toFixed(2)}`;
    }

    const foot = document.getElementById('pendingOrdersTableFoot');
    if (foot && state.pendingOrders.length > 0) {
      foot.style.display = count > 0 ? '' : 'none';
    }

    // Recalculate bill summary so checked checkbox amount adds in payable amount
    calculateBillSummary();
  }

  // -------------------------------------------------------------
  // 7. NEW CLIENT MODAL & CASCADING STATES/DISTRICTS
  // -------------------------------------------------------------
  function loadStates() {
    const sel = document.getElementById('newClientState');
    if (!sel) return;

    fetch(getApiEndpoint('action=states'))
      .then(parseApiResponse)
      .then(data => {
        if (data.status === 'success' && data.states) {
          let opts = '<option value="">-- Select State --</option>';
          data.states.forEach(s => {
            opts += `<option value="${escapeHtml(s.stateid)}" data-name="${escapeHtml(s.statename)}">${escapeHtml(s.statename)}</option>`;
          });
          sel.innerHTML = opts;
        }
      });

    sel.addEventListener('change', function () {
      const stateId = this.value;
      const distSel = document.getElementById('newClientDistrict');
      if (!distSel) return;

      if (!stateId) {
        distSel.innerHTML = '<option value="">-- Select District --</option>';
        return;
      }

      distSel.innerHTML = '<option value="">Loading districts...</option>';

      fetch(getApiEndpoint(), {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action: 'districts', stateid: stateId })
      })
        .then(parseApiResponse)
        .then(data => {
          let opts = '<option value="">-- Select District --</option>';
          if (data.status === 'success' && data.districts) {
            data.districts.forEach(d => {
              opts += `<option value="${escapeHtml(d.districtid)}" data-name="${escapeHtml(d.districtname)}">${escapeHtml(d.districtname)}</option>`;
            });
          }
          distSel.innerHTML = opts;
        });
    });
  }

  function openNewClientModal() {
    const modal = document.getElementById('modalNewClient');
    if (modal) modal.classList.add('active');
  }

  function closeNewClientModal() {
    const modal = document.getElementById('modalNewClient');
    if (modal) modal.classList.remove('active');
  }

  function handleNewClientSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const name = form.querySelector('#newClientName').value.trim();
    const phone = form.querySelector('#newClientPhone').value.trim();
    const email = form.querySelector('#newClientEmail').value.trim();
    const address = form.querySelector('#newClientAddress').value.trim();
    const stateSel = form.querySelector('#newClientState');
    const distSel = form.querySelector('#newClientDistrict');

    const stateid = stateSel.value;
    const statename = stateSel.options[stateSel.selectedIndex]?.getAttribute('data-name') || '';
    const districtid = distSel.value;
    const districtname = distSel.options[distSel.selectedIndex]?.getAttribute('data-name') || '';

    if (!name || !phone) {
      alert('Client Name and Mobile Number are required.');
      return;
    }

    const btnSubmit = form.querySelector('button[type="submit"]');
    if (btnSubmit) {
      btnSubmit.disabled = true;
      btnSubmit.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Saving...';
    }

    const tahseel = form.querySelector('#newClientTahseel')?.value.trim() || '';
    const cityname = form.querySelector('#newClientCity')?.value.trim() || '';

    const params = new URLSearchParams({
      action: 'create_client',
      client_name: name,
      phone: phone,
      email: email,
      address: address,
      stateid: stateid,
      statename: statename,
      districtid: districtid,
      districtname: districtname,
      tahseel: tahseel,
      cityname: cityname,
      companyid: window.APP_CONFIG?.companyid || 19,
      subcompanyid: window.APP_CONFIG?.subcompanyid || 12,
      projectid: window.APP_CONFIG?.projectid || 17
    });

    fetch(getApiEndpoint(), {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: params
    })
      .then(parseApiResponse)
      .then(data => {
        if (btnSubmit) {
          btnSubmit.disabled = false;
          btnSubmit.innerHTML = '<i class="fa fa-check"></i> Save Client';
        }
        if (data.status === 'success' && data.client) {
          selectClient(data.client);
          closeNewClientModal();
          form.reset();
          showToast('success', 'Client created and selected!');
        } else {
          alert('Error: ' + data.message);
        }
      })
      .catch(err => {
        if (btnSubmit) {
          btnSubmit.disabled = false;
          btnSubmit.innerHTML = '<i class="fa fa-check"></i> Save Client';
        }
        alert('Network error: ' + err.message);
      });
  }

  // -------------------------------------------------------------
  // 7.5. MULTI-PAYMENT MODE & ORDER PDF PREVIEW HANDLERS
  // -------------------------------------------------------------
  function toggleMultiPaymentMode(isEnabled) {
    state.isMultiMode = !!isEnabled;
    const breakdown = document.getElementById('multiPaymentBreakdown');
    const utrBox = document.getElementById('utrFieldWrap');
    const paidInput = document.getElementById('cartPaidAmount');

    if (state.isMultiMode) {
      if (utrBox) utrBox.style.display = 'none';
      if (breakdown) breakdown.style.display = 'block';
      if (paidInput) {
        paidInput.readOnly = true;
        paidInput.style.backgroundColor = '#f1f5f9';
      }

      if (!state.splitPaymentRows || state.splitPaymentRows.length === 0) {
        state.splitCounter = 1;
        state.splitPaymentRows = [
          { id: 'split_1', mode: state.paymentMode || 'Cash', amount: 0, utr: '', file: null, isDuplicate: false, isValidUtr: false, warning: '' }
        ];
      }

      // If total split amount is 0, auto-fill first row with totalPayable
      const totalSplit = state.splitPaymentRows.reduce((sum, r) => sum + (parseFloat(r.amount) || 0), 0);
      if (totalSplit === 0 && state.splitPaymentRows.length > 0) {
        const initialAmt = (state.amountPaid > 0) ? state.amountPaid : (state.totalPayable || 0);
        state.splitPaymentRows[0].amount = initialAmt;
        const singleUtr = document.getElementById('cartUtrNo')?.value.trim() || '';
        if (singleUtr) state.splitPaymentRows[0].utr = singleUtr;
      }

      const singleDocBox = document.getElementById('singleDocAttachWrap');
      if (singleDocBox) singleDocBox.style.display = 'none';

      syncTopPaymentModeButtons();
      renderMultiPayRows();
      calculateBillSummary();
    } else {
      if (breakdown) breakdown.style.display = 'none';
      if (paidInput) {
        paidInput.readOnly = false;
        paidInput.style.backgroundColor = '#fff';
      }

      const singleDocBox = document.getElementById('singleDocAttachWrap');
      if (singleDocBox) singleDocBox.style.display = 'block';

      const primaryRow = (state.splitPaymentRows && state.splitPaymentRows.length > 0) ? state.splitPaymentRows[0] : null;
      const primaryMode = primaryRow ? primaryRow.mode : (state.paymentMode || 'Cash');
      state.paymentMode = primaryMode;
      state.activePaymentModes = [primaryMode];

      document.querySelectorAll('.pay-mode-btn').forEach(b => {
        if (b.getAttribute('data-mode') === primaryMode) {
          b.classList.add('active');
        } else {
          b.classList.remove('active');
        }
      });

      if (utrBox) {
        if (primaryMode === 'Cash') {
          utrBox.style.display = 'none';
        } else {
          utrBox.style.display = 'block';
          const label = utrBox.querySelector('label');
          if (label) {
            label.textContent = primaryMode === 'Cheque' ? 'Cheque No. / Date' : 'UTR / Reference No.';
          }
          const utrInput = document.getElementById('cartUtrNo');
          if (utrInput && primaryRow && primaryRow.utr) {
            utrInput.value = primaryRow.utr;
            checkDuplicateUtr('single', primaryRow.utr, true);
          }
        }
      }

      calculateBillSummary();
    }
  }

  function syncTopPaymentModeButtons() {
    if (!state.isMultiMode) return;
    const activeModes = new Set((state.splitPaymentRows || []).map(r => r.mode));
    document.querySelectorAll('.pay-mode-btn').forEach(b => {
      const m = b.getAttribute('data-mode');
      if (activeModes.has(m)) {
        b.classList.add('active');
      } else {
        b.classList.remove('active');
      }
    });
  }

  function addSplitRow(mode, initialAmt = 0, initialUtr = '') {
    if (!state.splitPaymentRows) state.splitPaymentRows = [];
    state.splitCounter = (state.splitCounter || 0) + 1;
    const rowId = 'split_' + state.splitCounter;
    state.splitPaymentRows.push({
      id: rowId,
      mode: mode || 'UPI',
      amount: initialAmt,
      utr: initialUtr,
      file: null,
      isDuplicate: false,
      isValidUtr: false,
      warning: ''
    });
    syncTopPaymentModeButtons();
    renderMultiPayRows();
    calculateBillSummary();
  }

  function removeSplitRow(rowId) {
    if (!state.splitPaymentRows || state.splitPaymentRows.length <= 1) {
      showToast('warning', 'At least one payment split row is required.');
      return;
    }
    state.splitPaymentRows = state.splitPaymentRows.filter(r => r.id !== rowId);
    recheckAllSplitUtrs();
    syncTopPaymentModeButtons();
    renderMultiPayRows();
    calculateBillSummary();
    showToast('info', 'Payment split removed.');
  }

  function deactivateMultiPayMode(mode) {
    // Backward compatibility helper
    const target = (state.splitPaymentRows || []).find(r => r.mode === mode);
    if (target) {
      removeSplitRow(target.id);
    }
  }

  function recheckAllSplitUtrs() {
    (state.splitPaymentRows || []).forEach(r => {
      if (r.mode !== 'Cash' && r.utr) {
        checkDuplicateUtr(r.id, r.utr, false);
      }
    });
  }

  function renderMultiPayRows() {
    const container = document.getElementById('multiPayRowsContainer');
    if (!container) return;

    const modeIcons = {
      'Cash': 'fa-money-bill-wave',
      'UPI': 'fa-qrcode',
      'Bank': 'fa-building-columns',
      'Cheque': 'fa-money-check'
    };

    const modeColors = {
      'Cash': '#15803d',
      'UPI': '#4f46e5',
      'Bank': '#0284c7',
      'Cheque': '#b45309'
    };

    // Count occurrences of each mode to label duplicates as UPI #1, UPI #2
    const modeCounts = {};
    (state.splitPaymentRows || []).forEach(r => {
      modeCounts[r.mode] = (modeCounts[r.mode] || 0) + 1;
    });

    const currentModeTracker = {};

    let html = '';
    (state.splitPaymentRows || []).forEach(row => {
      currentModeTracker[row.mode] = (currentModeTracker[row.mode] || 0) + 1;
      const instanceLabel = (modeCounts[row.mode] > 1) ? ` #${currentModeTracker[row.mode]}` : '';

      const icon = modeIcons[row.mode] || 'fa-credit-card';
      const color = modeColors[row.mode] || '#2563eb';
      const hasFile = !!row.file;
      const fileName = hasFile ? row.file.name : '';
      const isPdf = hasFile && (row.file.type === 'application/pdf' || fileName.toLowerCase().endsWith('.pdf'));

      html += `
        <div class="multi-pay-row" id="multipay_row_${row.id}">
          <div class="multi-pay-mode-label" style="color:${color};">
            <i class="fa ${icon}"></i>
            <span>${escapeHtml(row.mode)}${instanceLabel}</span>
          </div>

          <div style="display:flex; align-items:center; gap:4px; position:relative;">
            <span style="position:absolute; left:7px; font-weight:800; color:#64748b; font-size:0.8rem; pointer-events:none;">₹</span>
            <input type="number" 
                   id="multipay_amt_${row.id}" 
                   class="multi-pay-amt-input" 
                   value="${row.amount > 0 ? row.amount : ''}" 
                   min="0" 
                   step="any" 
                   placeholder="0.00"
                   style="padding-left:18px; text-align:right;"
                   oninput="OpenSalePOS.onMultiPayAmtInput('${row.id}', this.value)" />
            <button type="button" class="btn-multi-pay-fill" title="Auto-fill remaining payable amount into this row" 
                    onclick="OpenSalePOS.fillMultiPayBalance('${row.id}')">
              Fill
            </button>
          </div>

          <div>
            ${row.mode === 'Cash' ? `
              <span style="font-size:0.75rem; color:#94a3b8; font-style:italic;">No reference required</span>
            ` : `
              <div style="position:relative; width:100%;">
                <input type="text" 
                       id="multipay_utr_${row.id}" 
                       class="multi-pay-utr-input ${row.isDuplicate ? 'is-invalid' : (row.isValidUtr ? 'is-valid' : '')}" 
                       value="${escapeHtml(row.utr || '')}" 
                       placeholder="${row.mode === 'Cheque' ? 'Cheque No. & Date' : (row.mode === 'Bank' ? 'NEFT / RTGS Ref No.' : 'UPI UTR / Ref No.')}" 
                       oninput="OpenSalePOS.onMultiPayUtrInput('${row.id}', this.value)"
                       onchange="OpenSalePOS.onMultiPayUtrChange('${row.id}', this.value)" />
                <div id="utr_status_${row.id}" class="utr-status-badge ${row.isDuplicate ? 'error' : (row.isValidUtr ? 'success' : '')}" style="${row.warning ? 'display:block;' : 'display:none;'}">
                  ${row.warning || ''}
                </div>
              </div>
            `}
          </div>

          <!-- Document Attachment & Preview Column -->
          <div style="display:flex; align-items:center; gap:5px; min-width:0;">
            <input type="file" id="multipay_doc_${row.id}" accept="image/*,.pdf" style="display:none;" onchange="OpenSalePOS.onMultiPayDocSelected('${row.id}', this)" />
            <label class="btn-attach-doc" id="lbl_multipay_doc_${row.id}" for="multipay_doc_${row.id}" style="${hasFile ? 'display:none;' : 'display:inline-flex;'}" title="Attach payment proof for this payment">
              <i class="fa fa-paperclip"></i> <span class="attach-doc-text">Attach Proof</span>
            </label>
            <div class="attached-doc-badge" id="badge_multipay_doc_${row.id}" style="${hasFile ? 'display:inline-flex;' : 'display:none;'}">
              <i class="fa ${isPdf ? 'fa-file-pdf' : 'fa-file-image'}" style="color:${isPdf ? '#ef4444' : '#2563eb'};"></i>
              <span class="doc-name" title="${escapeHtml(fileName)}">${escapeHtml(fileName)}</span>
              <button type="button" class="btn-doc-preview-pill" onclick="OpenSalePOS.previewMultiDoc('${row.id}')" title="Preview Attached Document">
                <i class="fa fa-eye"></i> Preview
              </button>
              <button type="button" class="btn-doc-clear-pill" onclick="OpenSalePOS.clearMultiDoc('${row.id}')" title="Remove Document">&times;</button>
            </div>
          </div>

          <div>
            <button type="button" class="btn-remove-client" style="padding:2px 6px; font-size:0.75rem;" title="Remove this payment split" 
                    onclick="OpenSalePOS.removeSplitRow('${row.id}')">
              <i class="fa fa-times"></i>
            </button>
          </div>
        </div>
      `;
    });

    let multiTotal = 0;
    (state.splitPaymentRows || []).forEach(r => {
      multiTotal += (parseFloat(r.amount) || 0);
    });
    multiTotal = round(multiTotal);

    const totalPayable = state.totalPayable || 0;
    const diff = round(totalPayable - multiTotal);

    html += `
      <div id="multiPaySummaryFooter" style="display:flex; justify-content:space-between; align-items:center; margin-top:8px; padding-top:6px; border-top:1.5px dashed #cbd5e1; font-size:0.8rem; flex-wrap:wrap; gap:6px;">
        <div>
          <span style="color:#64748b; font-weight:600;">Total Multi-Pay:</span>
          <strong id="multiPayTotalDisplay" style="color:var(--primary); font-size:0.92rem; margin-left:4px;">₹${multiTotal.toFixed(2)}</strong>
        </div>
        <div id="multiPayDiffDisplay">
          ${diff > 0
        ? `<span style="color:#dc2626; font-weight:700;"><i class="fa fa-circle-exclamation"></i> Remaining: ₹${diff.toFixed(2)}</span>`
        : (diff < 0
          ? `<span style="color:#d97706; font-weight:700;"><i class="fa fa-triangle-exclamation"></i> Excess: ₹${Math.abs(diff).toFixed(2)}</span>`
          : `<span style="color:#15803d; font-weight:700;"><i class="fa fa-circle-check"></i> Exact Match</span>`
        )
      }
        </div>
      </div>
    `;

    container.innerHTML = html;
  }

  function updateMultiPayFooter() {
    let multiTotal = 0;
    (state.splitPaymentRows || []).forEach(r => {
      multiTotal += (parseFloat(r.amount) || 0);
    });
    multiTotal = round(multiTotal);

    const totalPayable = state.totalPayable || 0;
    const diff = round(totalPayable - multiTotal);

    const multiTotDisp = document.getElementById('multiPayTotalDisplay');
    if (multiTotDisp) multiTotDisp.textContent = `₹${multiTotal.toFixed(2)}`;

    const diffDisp = document.getElementById('multiPayDiffDisplay');
    if (diffDisp) {
      diffDisp.innerHTML = diff > 0
        ? `<span style="color:#dc2626; font-weight:700;"><i class="fa fa-circle-exclamation"></i> Remaining: ₹${diff.toFixed(2)}</span>`
        : (diff < 0
          ? `<span style="color:#d97706; font-weight:700;"><i class="fa fa-triangle-exclamation"></i> Excess: ₹${Math.abs(diff).toFixed(2)}</span>`
          : `<span style="color:#15803d; font-weight:700;"><i class="fa fa-circle-check"></i> Exact Match</span>`
        );
    }
  }

  function onMultiPayAmtInput(rowId, val) {
    const row = (state.splitPaymentRows || []).find(r => r.id === rowId);
    if (!row) return;
    row.amount = Math.max(0, parseFloat(val) || 0);
    state.isCustomPaid = true;
    calculateBillSummary();
    updateMultiPayFooter();
  }

  function fillMultiPayBalance(targetRowId) {
    const totalPayable = state.totalPayable || 0;
    const otherSum = (state.splitPaymentRows || [])
      .filter(r => r.id !== targetRowId)
      .reduce((sum, r) => sum + (parseFloat(r.amount) || 0), 0);

    const needed = Math.max(0, round(totalPayable - otherSum));
    const row = (state.splitPaymentRows || []).find(r => r.id === targetRowId);
    if (row) {
      row.amount = needed;
    }
    const input = document.getElementById(`multipay_amt_${targetRowId}`);
    if (input) {
      input.value = needed > 0 ? (Number.isInteger(needed) ? needed : needed.toFixed(2)) : '';
    }
    state.isCustomPaid = true;
    calculateBillSummary();
    updateMultiPayFooter();
  }

  // -------------------------------------------------------------
  // DUPLICATE TRANSACTION / UTR CHECK ENGINE (LIVE ON INPUT)
  // -------------------------------------------------------------
  const utrCheckTimers = {};

  function updateUtrRowUI(rowId, statusClass, htmlMsg) {
    const input = document.getElementById(`multipay_utr_${rowId}`);
    const badge = document.getElementById(`utr_status_${rowId}`);
    if (input) {
      input.classList.remove('is-invalid', 'is-valid');
      if (statusClass === 'error') input.classList.add('is-invalid');
      if (statusClass === 'success') input.classList.add('is-valid');
    }
    if (badge) {
      badge.className = `utr-status-badge ${statusClass}`;
      badge.innerHTML = htmlMsg || '';
      badge.style.display = htmlMsg ? 'block' : 'none';
    }
  }

  function updateSingleUtrUI(statusClass, htmlMsg) {
    const input = document.getElementById('cartUtrNo');
    const badge = document.getElementById('singleUtrStatus');
    if (input) {
      input.classList.remove('is-invalid', 'is-valid');
      if (statusClass === 'error') input.classList.add('is-invalid');
      if (statusClass === 'success') input.classList.add('is-valid');
    }
    if (badge) {
      badge.className = `utr-status-badge ${statusClass}`;
      badge.innerHTML = htmlMsg || '';
      badge.style.display = htmlMsg ? 'block' : 'none';
    }
  }

  function checkDuplicateUtr(rowId, val, isSingleMode) {
    const cleanVal = (val || '').trim();
    const row = isSingleMode ? null : (state.splitPaymentRows || []).find(r => r.id === rowId);

    if (!cleanVal) {
      if (row) {
        row.isDuplicate = false;
        row.isValidUtr = false;
        row.warning = '';
        updateUtrRowUI(rowId, '', '');
      } else if (isSingleMode) {
        state.singleUtrDuplicate = false;
        updateSingleUtrUI('', '');
      }
      return;
    }

    // 1. Client-Side Check: Is this UTR duplicated in another active input on screen?
    if (!isSingleMode) {
      const otherRow = (state.splitPaymentRows || []).find(r => r.id !== rowId && r.utr && r.utr.toLowerCase() === cleanVal.toLowerCase());
      if (otherRow) {
        if (row) {
          row.isDuplicate = true;
          row.isValidUtr = false;
          row.warning = `<span style="color:#dc2626;"><i class="fa fa-triangle-exclamation"></i> Duplicate! Same reference entered in another split.</span>`;
          updateUtrRowUI(rowId, 'error', row.warning);
        }
        return;
      }
    }

    // Indicate checking
    if (row) {
      row.isDuplicate = false;
      row.isValidUtr = false;
      updateUtrRowUI(rowId, 'checking', `<span style="color:#2563eb;"><i class="fa fa-spinner fa-spin"></i> Checking reference...</span>`);
    } else if (isSingleMode) {
      state.singleUtrDuplicate = false;
      updateSingleUtrUI('checking', `<span style="color:#2563eb;"><i class="fa fa-spinner fa-spin"></i> Checking reference...</span>`);
    }

    // 2. Debounced Server-Side Check
    clearTimeout(utrCheckTimers[rowId || 'single']);
    utrCheckTimers[rowId || 'single'] = setTimeout(() => {
      fetch(getApiEndpoint(`action=check_duplicate_utr&utr=${encodeURIComponent(cleanVal)}`))
        .then(parseApiResponse)
        .then(data => {
          if (data.status === 'success' && data.is_duplicate) {
            const m = data.match;
            const warn = `<span style="color:#dc2626;"><i class="fa fa-circle-exclamation"></i> Duplicate Ref! Already used in Inv: <strong>${escapeHtml(m.invoiceno)}</strong> (${escapeHtml(m.date)}, ₹${m.amount.toFixed(2)} via ${escapeHtml(m.mode)})</span>`;
            if (row) {
              row.isDuplicate = true;
              row.isValidUtr = false;
              row.warning = warn;
              updateUtrRowUI(rowId, 'error', warn);
            } else if (isSingleMode) {
              state.singleUtrDuplicate = true;
              updateSingleUtrUI('error', warn);
            }
          } else {
            const successMsg = `<span style="color:#059669;"><i class="fa fa-circle-check"></i> Valid & unique reference number</span>`;
            if (row) {
              row.isDuplicate = false;
              row.isValidUtr = true;
              row.warning = successMsg;
              updateUtrRowUI(rowId, 'success', successMsg);
            } else if (isSingleMode) {
              state.singleUtrDuplicate = false;
              updateSingleUtrUI('success', successMsg);
            }
          }
        })
        .catch(() => {
          // In case of network error, do not hard block
        });
    }, 280);
  }

  function onMultiPayUtrInput(rowId, val) {
    const row = (state.splitPaymentRows || []).find(r => r.id === rowId);
    if (!row) return;
    row.utr = (val || '').trim();
    checkDuplicateUtr(rowId, row.utr, false);
  }

  function onMultiPayUtrChange(rowId, val) {
    onMultiPayUtrInput(rowId, val);
  }

  function onSingleUtrInput(val) {
    checkDuplicateUtr('single', val, true);
  }

  // -------------------------------------------------------------
  // DOCUMENT ATTACHMENT & PREVIEW HANDLERS (SINGLE & MULTI-MODE)
  // -------------------------------------------------------------
  function onSingleDocFileSelected(input) {
    if (input.files && input.files[0]) {
      state.singlePaymentDoc = input.files[0];
      const nameEl = document.getElementById('singleDocName');
      const badge = document.getElementById('singleDocPreviewBadge');
      const lbl = document.getElementById('lblSingleDocAttach');
      if (nameEl) {
        nameEl.textContent = state.singlePaymentDoc.name;
        nameEl.title = state.singlePaymentDoc.name;
      }
      if (badge) badge.style.display = 'inline-flex';
      if (lbl) lbl.style.display = 'none';
      showToast('success', 'Document attached: ' + state.singlePaymentDoc.name);
    }
  }

  function previewSingleDoc() {
    if (!state.singlePaymentDoc) {
      showToast('warning', 'No document attached yet.');
      return;
    }
    openDocPreview(state.singlePaymentDoc, (state.paymentMode || 'Payment') + ' Proof');
  }

  function clearSingleDoc() {
    state.singlePaymentDoc = null;
    const inp = document.getElementById('fileSingleDoc');
    if (inp) inp.value = '';
    const badge = document.getElementById('singleDocPreviewBadge');
    const lbl = document.getElementById('lblSingleDocAttach');
    if (badge) badge.style.display = 'none';
    if (lbl) lbl.style.display = 'inline-flex';
    showToast('info', 'Attached document removed');
  }

  function onMultiPayDocSelected(targetIdOrMode, input) {
    if (input.files && input.files[0]) {
      const row = (state.splitPaymentRows || []).find(r => r.id === targetIdOrMode || r.mode === targetIdOrMode);
      if (row) {
        row.file = input.files[0];
        renderMultiPayRows();
        showToast('success', `Document attached for ${row.mode}: ${input.files[0].name}`);
      }
    }
  }

  function previewMultiDoc(targetIdOrMode) {
    const row = (state.splitPaymentRows || []).find(r => r.id === targetIdOrMode || r.mode === targetIdOrMode);
    const f = row?.file;
    if (!f) {
      showToast('warning', `No document attached for this payment.`);
      return;
    }
    openDocPreview(f, `${row.mode} Payment Proof`);
  }

  function clearMultiDoc(targetIdOrMode) {
    const row = (state.splitPaymentRows || []).find(r => r.id === targetIdOrMode || r.mode === targetIdOrMode);
    if (row) {
      row.file = null;
    }
    renderMultiPayRows();
    showToast('info', `Removed payment proof document`);
  }

  let currentPreviewObjUrl = null;
  function openDocPreview(fileOrUrl, title) {
    const modal = document.getElementById('modalDocPreview');
    const titleText = document.getElementById('docPreviewTitleText');
    const btnDownload = document.getElementById('btnDocDownload');
    const btnOpenTab = document.getElementById('btnDocOpenTab');
    const body = document.getElementById('docPreviewModalBody');

    if (!modal || !body) return;

    if (currentPreviewObjUrl) {
      URL.revokeObjectURL(currentPreviewObjUrl);
      currentPreviewObjUrl = null;
    }

    if (titleText) titleText.textContent = title || 'Payment Document Preview';

    if (fileOrUrl instanceof File) {
      const url = URL.createObjectURL(fileOrUrl);
      currentPreviewObjUrl = url;
      const isPdf = fileOrUrl.type === 'application/pdf' || fileOrUrl.name.toLowerCase().endsWith('.pdf');

      if (btnDownload) {
        btnDownload.href = url;
        btnDownload.download = fileOrUrl.name;
      }
      if (btnOpenTab) {
        btnOpenTab.href = url;
      }

      if (isPdf) {
        body.innerHTML = `<iframe src="${url}" style="width:100%; height:75vh; border:none; border-radius:8px; background:#fff;"></iframe>`;
      } else {
        body.innerHTML = `
          <div style="text-align:center; max-height:75vh;">
            <img src="${url}" style="max-width:100%; max-height:72vh; object-fit:contain; border-radius:8px; box-shadow:0 4px 20px rgba(0,0,0,0.5);" alt="Payment Proof" />
            <div style="font-size:0.75rem; color:#94a3b8; margin-top:8px;">${escapeHtml(fileOrUrl.name)} (${(fileOrUrl.size / 1024).toFixed(1)} KB)</div>
          </div>
        `;
      }
    } else if (typeof fileOrUrl === 'string') {
      const url = fileOrUrl;
      const isPdf = url.toLowerCase().endsWith('.pdf');

      if (btnDownload) {
        btnDownload.href = url;
        btnDownload.download = 'payment_proof';
      }
      if (btnOpenTab) {
        btnOpenTab.href = url;
      }

      if (isPdf) {
        body.innerHTML = `<iframe src="${url}" style="width:100%; height:75vh; border:none; border-radius:8px; background:#fff;"></iframe>`;
      } else {
        body.innerHTML = `
          <div style="text-align:center; max-height:75vh;">
            <img src="${url}" style="max-width:100%; max-height:72vh; object-fit:contain; border-radius:8px; box-shadow:0 4px 20px rgba(0,0,0,0.5);" alt="Payment Proof" />
          </div>
        `;
      }
    }

    modal.classList.add('active');
  }

  function closeDocPreviewModal() {
    const modal = document.getElementById('modalDocPreview');
    const body = document.getElementById('docPreviewModalBody');
    if (modal) modal.classList.remove('active');
    if (body) body.innerHTML = '';
    if (currentPreviewObjUrl) {
      URL.revokeObjectURL(currentPreviewObjUrl);
      currentPreviewObjUrl = null;
    }
  }

  // Order PDF Preview Modal Handler (Provisional / Final Sales Order)
  function openOrderPdfModal(psono, sono, type, companyid, subcompanyid, projectid) {
    const modal = document.getElementById('modalOrderPdfPreview');
    const titleEl = document.getElementById('orderPdfModalTitle');
    const newTabBtn = document.getElementById('btnOrderPdfOpenNewTab');
    const iframe = document.getElementById('orderPdfIframe');
    const loader = document.getElementById('orderPdfLoader');

    if (!modal || !iframe) return;

    const cmp = companyid || window.APP_CONFIG?.companyid || 19;
    const sub = subcompanyid || window.APP_CONFIG?.subcompanyid || 12;
    const prj = projectid || window.APP_CONFIG?.projectid || 17;

    let url = '';
    let titleHtml = '';

    if (type === 'so' && sono) {
      url = `../krf-orderandsales/modal/finalsalesorderPDF.php?sono=${encodeURIComponent(sono)}&psono=${encodeURIComponent(psono)}&companyid=${cmp}&subcompanyid=${sub}&projectid=${prj}&mode=I`;
      titleHtml = `<i class="fa fa-file-pdf" style="color:#059669;"></i> Final Sales Order PDF &bull; <span style="font-family:'JetBrains Mono',monospace;">${escapeHtml(sono)}</span> <span style="font-size:0.8rem; color:#64748b;">(${escapeHtml(psono)})</span>`;
    } else {
      url = `../krf-orderandsales/modal/provisionalnvoicePDF.php?psono=${encodeURIComponent(psono)}&companyid=${cmp}&subcompanyid=${sub}&projectid=${prj}&mode=I`;
      titleHtml = `<i class="fa fa-file-invoice" style="color:var(--primary);"></i> Provisional Order PDF &bull; <span style="font-family:'JetBrains Mono',monospace;">${escapeHtml(psono)}</span>`;
    }

    if (titleEl) titleEl.innerHTML = titleHtml;
    if (newTabBtn) newTabBtn.href = url;

    if (loader) loader.style.display = 'flex';
    iframe.src = url;

    modal.classList.add('active');
  }

  function closeOrderPdfModal() {
    const modal = document.getElementById('modalOrderPdfPreview');
    const iframe = document.getElementById('orderPdfIframe');
    if (iframe) iframe.src = 'about:blank';
    if (modal) modal.classList.remove('active');
  }

  // -------------------------------------------------------------
  // 8. ORDER BOOKING & INVOICE GENERATION
  // -------------------------------------------------------------
  function submitOrderBooking() {
    if (!state.selectedClient) {
      showToast('error', 'Please select or add a client first.');
      const clientInput = document.getElementById('clientSearchInput');
      if (clientInput) clientInput.focus();
      return;
    }

    if (state.cart.length === 0) {
      showToast('error', 'Cart is empty. Add at least one open sale plant item.');
      return;
    }

    // Check for empty or 0 quantities (manual entry required)
    const unassignedQty = state.cart.filter(c => (parseInt(c.quantity) || 0) <= 0);
    if (unassignedQty.length > 0) {
      showToast('error', `Please enter a valid quantity for "${unassignedQty[0].productname}".`);
      focusCartRow(unassignedQty[0].cartKey);
      return;
    }

    // Check for 0 rates
    const zeroRates = state.cart.filter(c => c.rate <= 0);
    if (zeroRates.length > 0) {
      if (!confirm(`Warning: ${zeroRates.length} item(s) have a rate of ₹0.00. Do you wish to continue?`)) {
        return;
      }
    }

    const grandTotal = getGrandTotal();
    const specialDiscount = Math.max(0, parseFloat(document.getElementById('cartSpecialDiscount')?.value) || 0);
    const netPayable = Math.max(0, grandTotal - specialDiscount);
    const paidAmt = parseFloat(document.getElementById('cartPaidAmount')?.value) || 0;
    const utrNo = document.getElementById('cartUtrNo')?.value.trim() || '';
    const remarks = document.getElementById('cartRemarks')?.value.trim() || '';
    const vehicleno = document.getElementById('cartVehicleNo')?.value.trim() || '';
    const drivername = document.getElementById('cartDriverName')?.value.trim() || '';
    const driverphone = document.getElementById('cartDriverPhone')?.value.trim() || '';

    // Selected pending orders
    const selectedPending = Object.values(state.selectedPendingOrders).filter(p => (parseFloat(p.pay_amount) || 0) > 0);
    const totalPendingPayment = selectedPending.reduce((sum, p) => sum + (parseFloat(p.pay_amount) || 0), 0);
    const totalPayableAll = round(netPayable + totalPendingPayment);

    // Calculate portion of payment for current sale vs pending orders
    const currentSalePaid = Math.max(0, Math.min(netPayable, round(paidAmt - totalPendingPayment)));

    // Determine primary open sale location
    const openSaleLoc = state.branch || (state.cart.length > 0 ? state.cart[0].open_sale_location : 'Karjee');

    // Payment splits calculation & validations
    let paymentSplits = [];
    if (state.isMultiMode) {
      for (let r of (state.splitPaymentRows || [])) {
        const amt = parseFloat(r.amount) || 0;
        const utr = (r.utr || '').trim();
        if (amt > 0) {
          if (r.mode !== 'Cash' && !utr) {
            showToast('error', `Please enter Transaction Reference / UTR for ${r.mode} payment.`);
            const input = document.getElementById(`multipay_utr_${r.id}`);
            if (input) input.focus();
            return;
          }
          if (r.isDuplicate) {
            showToast('error', `Duplicate Transaction Reference detected in ${r.mode} payment. Please correct before submitting.`);
            const input = document.getElementById(`multipay_utr_${r.id}`);
            if (input) input.focus();
            return;
          }
          paymentSplits.push({ id: r.id, mode: r.mode, amount: amt, utr: utr });
        }
      }
      if (paymentSplits.length === 0) {
        showToast('error', 'Please enter payment amount for at least one active payment split.');
        return;
      }
    } else {
      if (state.singleUtrDuplicate) {
        showToast('error', `Duplicate Transaction Reference detected. Please enter a unique reference number.`);
        const input = document.getElementById('cartUtrNo');
        if (input) input.focus();
        return;
      }
      if (paidAmt > 0 && state.paymentMode !== 'Cash' && !utrNo) {
        showToast('error', `Please enter UTR / Reference No. for ${state.paymentMode} payment.`);
        const input = document.getElementById('cartUtrNo');
        if (input) input.focus();
        return;
      }
      paymentSplits = [{
        id: 'single',
        mode: state.paymentMode,
        amount: paidAmt,
        utr: utrNo
      }];
    }

    const confirmMsg = `
      <div style="text-align:left; font-size:0.88rem;">
        <p><strong>Client:</strong> ${escapeHtml(state.selectedClient.name)}</p>
        <p><strong>Total Items:</strong> ${state.cart.length} line item(s)</p>
        <p><strong>Total Plants:</strong> ${state.cart.reduce((a, b) => a + b.quantity, 0).toLocaleString()}</p>
        ${(state.boxPacking && state.boxPacking.enabled && state.boxPacking.boxCount > 0) ? `
          <p style="color:#0369a1;"><strong>Box Packing Charges:</strong> ${state.boxPacking.boxCount} Boxes @ ₹${state.boxPacking.rate.toFixed(2)} = ₹${state.boxPacking.totalCharge.toFixed(2)}</p>
        ` : ''}
        <p><strong>Grand Total (Current Sale):</strong> ₹${grandTotal.toFixed(2)}</p>
        ${specialDiscount > 0 ? `<p style="color:#ea580c;"><strong>Special Discount:</strong> -₹${specialDiscount.toFixed(2)}</p><p><strong>Current Sale Net:</strong> ₹${netPayable.toFixed(2)}</p>` : ''}
        ${selectedPending.length > 0 ? `
          <div style="background:#f0fdf4; border:1.5px solid #bbf7d0; border-radius:6px; padding:8px 10px; margin-top:8px;">
            <div style="color:#15803d; font-weight:700;"><i class="fa fa-list-check"></i> Pending Orders Included (${selectedPending.length}):</div>
            <ul style="margin:4px 0 4px 18px; padding:0; font-size:0.8rem; color:#166534;">
              ${selectedPending.map(p => `<li><strong>${escapeHtml(p.psono)}</strong>${p.sono ? ` (${escapeHtml(p.sono)})` : ''}: ₹${parseFloat(p.pay_amount).toFixed(2)}</li>`).join('')}
            </ul>
            <div style="font-weight:800; color:#15803d; font-size:0.85rem; border-top:1px dashed #86efac; padding-top:4px;">
              Pending Orders Total: ₹${totalPendingPayment.toFixed(2)}
            </div>
          </div>
        ` : ''}
        <div style="font-size:1rem; font-weight:800; color:#1e3a8a; margin-top:8px; border-top:1px solid #cbd5e1; padding-top:6px;">
          Total Payable: ₹${totalPayableAll.toFixed(2)}
        </div>
        ${state.isMultiMode ? `
          <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:8px 10px; margin-top:8px;">
            <div style="color:#1e293b; font-weight:700; font-size:0.82rem; margin-bottom:4px;">
              <i class="fa fa-layer-group" style="color:var(--primary);"></i> Multiple Payment Modes Active (${paymentSplits.length}):
            </div>
            <ul style="margin:0 0 0 16px; padding:0; font-size:0.8rem; color:#334155;">
              ${paymentSplits.map(s => {
      const docFile = (state.splitPaymentRows || []).find(r => r.id === s.id)?.file;
      return `<li><strong>${escapeHtml(s.mode)}:</strong> ₹${s.amount.toFixed(2)}${s.utr ? ` (Ref: <code>${escapeHtml(s.utr)}</code>)` : ''}${docFile ? ` <span style="color:#059669; font-weight:600;"><i class="fa fa-paperclip"></i> ${escapeHtml(docFile.name)}</span>` : ''}</li>`;
    }).join('')}
            </ul>
          </div>
        ` : `
          <p style="margin-top:6px;"><strong>Amount Paid:</strong> ₹${paidAmt.toFixed(2)} via <strong>${escapeHtml(state.paymentMode)}</strong>${utrNo ? ` (Ref: ${escapeHtml(utrNo)})` : ''}${state.singlePaymentDoc ? ` <span style="color:#059669; font-weight:600;"><i class="fa fa-paperclip"></i> ${escapeHtml(state.singlePaymentDoc.name)}</span>` : ''}</p>
        `}
        ${paidAmt < totalPayableAll ? `<p style="color:#dc2626;"><strong>Total Balance Due:</strong> ₹${(totalPayableAll - paidAmt).toFixed(2)}</p>` : '<p style="color:#15803d;"><strong>Status:</strong> FULLY PAID</p>'}
      </div>
    `;

    Swal.fire({
      title: 'Confirm Open Sale Order?',
      html: confirmMsg,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#10b981',
      cancelButtonColor: '#64748b',
      confirmButtonText: '<i class="fa fa-check"></i> Yes, Generate Invoice',
      cancelButtonText: 'Cancel'
    }).then(result => {
      if (result.isConfirmed) {
        processOrderSubmission({
          clientid: state.selectedClient.id,
          open_sale_location: openSaleLoc,
          amount_paid: currentSalePaid,
          special_discount: specialDiscount,
          is_box_packing: (state.boxPacking && state.boxPacking.enabled && state.boxPacking.boxCount > 0) ? 1 : 0,
          box_count: state.boxPacking ? state.boxPacking.boxCount : 0,
          box_rate: state.boxPacking ? state.boxPacking.rate : 100,
          box_total: state.boxPacking ? state.boxPacking.totalCharge : 0,
          payment_mode: state.isMultiMode ? 'Multiple' : state.paymentMode,
          payment_splits: paymentSplits,
          utr_number: utrNo,
          order_remarks: remarks,
          vehicleno: vehicleno,
          drivername: drivername,
          driverphone: driverphone,
          items: state.cart,
          pending_orders_payments: selectedPending
        });
      }
    });
  }

  function processOrderSubmission(payload) {
    Swal.fire({
      title: 'Generating Invoice & Processing Stock...',
      text: 'Please wait a moment',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });

    const formData = new FormData();
    formData.append('action', 'book_opensale_order');
    formData.append('clientid', payload.clientid);
    formData.append('open_sale_location', payload.open_sale_location);
    formData.append('amount_paid', payload.amount_paid);
    formData.append('special_discount', payload.special_discount || 0);
    formData.append('is_box_packing', payload.is_box_packing ? '1' : '0');
    formData.append('box_count', payload.box_count || 0);
    formData.append('box_rate', payload.box_rate || 100);
    formData.append('box_total', payload.box_total || 0);
    formData.append('pending_orders_payments', JSON.stringify(payload.pending_orders_payments || []));
    formData.append('payment_mode', payload.payment_mode);
    formData.append('is_multi_payment', state.isMultiMode ? '1' : '0');
    formData.append('payment_splits', JSON.stringify(payload.payment_splits || []));
    formData.append('utr_number', payload.utr_number);
    formData.append('order_remarks', payload.order_remarks);
    formData.append('vehicleno', payload.vehicleno);
    formData.append('drivername', payload.drivername);
    formData.append('driverphone', payload.driverphone);
    formData.append('companyid', window.APP_CONFIG?.companyid || 19);
    formData.append('subcompanyid', window.APP_CONFIG?.subcompanyid || 12);
    formData.append('projectid', window.APP_CONFIG?.projectid || 17);
    formData.append('loginid', window.APP_CONFIG?.loginid || 'Admin');
    formData.append('items', JSON.stringify(payload.items));

    // Append attached payment receipt/proof documents
    if (state.isMultiMode) {
      (state.splitPaymentRows || []).forEach((r, idx) => {
        if (r.file) {
          formData.append('payment_document_' + r.id, r.file);
          formData.append('payment_document_' + idx, r.file);
          formData.append('payment_document_' + r.mode, r.file);
        }
      });
    } else {
      if (state.singlePaymentDoc) {
        formData.append('single_payment_document', state.singlePaymentDoc);
      }
    }

    fetch(getApiEndpoint(), {
      method: 'POST',
      body: formData
    })
      .then(parseApiResponse)
      .then(data => {
        if (data.status === 'success') {
          // Play success beep
          playBeep(true);

          Swal.fire({
            icon: 'success',
            title: 'Order Booked & Dispatched!',
            html: `
              <div style="font-size: 0.92rem; text-align: left; padding: 0.5rem 0.5rem;">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:8px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px; margin-bottom:12px;">
                  <div><strong style="color:#64748b; font-size:0.75rem; text-transform:uppercase;">Invoice No:</strong><br><span style="font-family:'JetBrains Mono',monospace; color:var(--primary); font-weight:800;">${data.invoiceno}</span></div>
                  <div><strong style="color:#64748b; font-size:0.75rem; text-transform:uppercase;">Gate Pass No:</strong><br><span style="font-family:'JetBrains Mono',monospace; color:#b45309; font-weight:800;">${data.gatepass_no || '-'}</span></div>
                  <div><strong style="color:#64748b; font-size:0.75rem; text-transform:uppercase;">Provisional (PSO):</strong><br><span style="font-family:'JetBrains Mono',monospace; font-weight:700;">${data.psono}</span></div>
                  <div><strong style="color:#64748b; font-size:0.75rem; text-transform:uppercase;">Sales Order (SO):</strong><br><span style="font-family:'JetBrains Mono',monospace; font-weight:700; color:#059669;">${data.sono}</span></div>
                </div>

                <div style="margin-bottom: 5px;"><strong>Client:</strong> ${escapeHtml(data.client_name)}</div>
                <div style="margin-bottom: 5px;"><strong>Total Plants:</strong> ${data.total_plants.toLocaleString()}</div>
                <div style="margin-bottom: 5px;"><strong>Invoice Amount:</strong> ₹${parseFloat(data.invoice_amount).toFixed(2)}</div>
                ${data.special_discount && parseFloat(data.special_discount) > 0 ? `<div style="margin-bottom: 5px; color:#ea580c;"><strong>Special Discount:</strong> -₹${parseFloat(data.special_discount).toFixed(2)}</div>` : ''}
                <div style="margin-bottom: 5px;"><strong>Paid Amount:</strong> <span style="color:#15803d; font-weight:700;">₹${parseFloat(data.paid_amount).toFixed(2)}</span></div>
                <div style="margin-bottom: 5px;"><strong>Balance Due:</strong> <span style="color:${parseFloat(data.balance_amount) > 0 ? '#b91c1c' : '#15803d'}; font-weight:700;">₹${parseFloat(data.balance_amount).toFixed(2)}</span></div>
                ${data.pending_orders_paid && data.pending_orders_paid.length > 0 ? `
                  <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:6px; padding:8px 10px; margin-top:8px; margin-bottom:10px;">
                    <div style="color:#15803d; font-weight:700; font-size:0.8rem;"><i class="fa fa-circle-check"></i> Payments recorded against ${data.pending_orders_paid.length} pending order(s):</div>
                    <div style="font-size:0.77rem; color:#166534; font-family:'JetBrains Mono',monospace; margin-top:4px;">
                      ${data.pending_orders_paid.map(p => `${escapeHtml(p.psono)}: ₹${parseFloat(p.pay_amount).toFixed(2)}`).join(' &bull; ')}
                    </div>
                  </div>
                ` : ''}

                <!-- Preview Action Buttons in Modal Body -->
                <div style="display:flex; gap:8px; justify-content:center; flex-wrap:wrap; padding-top:10px; border-top:1px dashed #cbd5e1;">
                  <button type="button" id="btnPopPreviewInvoice" class="btn-osb-nav" style="background:#4f46e5; color:#fff; border:none; padding:8px 14px; font-size:0.85rem; font-weight:700; border-radius:6px; cursor:pointer;">
                    <i class="fa fa-file-invoice"></i> Preview Invoice
                  </button>
                  <button type="button" id="btnPopPreviewGatepass" class="btn-osb-nav" style="background:#b45309; color:#fff; border:none; padding:8px 14px; font-size:0.85rem; font-weight:700; border-radius:6px; cursor:pointer;">
                    <i class="fa fa-id-badge"></i> Preview Gate Pass
                  </button>
                </div>
              </div>
            `,
            didOpen: () => {
              const bInv = document.getElementById('btnPopPreviewInvoice');
              if (bInv) {
                bInv.addEventListener('click', () => {
                  window.open(data.print_url, '_blank');
                });
              }
              const bGp = document.getElementById('btnPopPreviewGatepass');
              if (bGp) {
                bGp.addEventListener('click', () => {
                  window.open(data.gatepass_url, '_blank');
                });
              }
            },
            showDenyButton: true,
            showCancelButton: false,
            confirmButtonColor: '#10b981',
            denyButtonColor: '#64748b',
            confirmButtonText: '<i class="fa fa-plus"></i> Start New Sale',
            denyButtonText: '<i class="fa fa-list"></i> View All Invoices'
          }).then(choice => {
            if (choice.isConfirmed) {
              resetCartAndState();
            } else if (choice.isDenied) {
              window.location.href = 'invoices.php';
            } else {
              resetCartAndState();
            }
          });
        } else {
          // Booking Failed & Rollback Prompt with Valid Reason
          Swal.fire({
            icon: 'error',
            title: 'Sale Booking Failed & Changes Rolled Back',
            html: `
              <div style="text-align: left; font-size: 0.88rem; padding: 0.25rem 0.5rem;">
                <p style="color:#b91c1c; font-weight:700; margin-bottom:8px;">
                  <i class="fa fa-triangle-exclamation"></i> Transaction Aborted:
                </p>
                <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:6px; padding:10px; color:#991b1b; font-family:'JetBrains Mono',monospace; font-size:0.82rem; margin-bottom:12px; word-break:break-word;">
                  ${escapeHtml(data.message || 'Unknown database error.')}
                </div>
                <p style="color:#475569; font-size:0.8rem; margin:0;">
                  <i class="fa fa-rotate-left"></i> <strong>Rollback Verified:</strong> All database operations across all tables (Provisional Order, Sales Order, Stock Allocation, Invoice, Gatepass) have been cleanly rolled back. No duplicate or partial data was saved.
                </p>
              </div>
            `,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'OK, Review & Retry'
          });
        }
      })
      .catch(err => {
        Swal.fire({
          icon: 'error',
          title: 'System / Network Error & Rolled Back',
          html: `
            <div style="text-align: left; font-size: 0.88rem; padding: 0.25rem 0.5rem;">
              <p style="color:#b91c1c; font-weight:700; margin-bottom:8px;">
                <i class="fa fa-circle-exclamation"></i> Communication Error:
              </p>
              <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:6px; padding:10px; color:#991b1b; font-family:'JetBrains Mono',monospace; font-size:0.82rem; margin-bottom:12px; word-break:break-word;">
                ${escapeHtml(err.message)}
              </div>
              <p style="color:#475569; font-size:0.8rem; margin:0;">
                <i class="fa fa-rotate-left"></i> Any open database transaction has been safely aborted.
              </p>
            </div>
          `,
          confirmButtonColor: '#ef4444'
        });
      });
  }

  function resetCartAndState() {
    state.cart = [];
    state.selectedClient = null;
    state.amountPaid = 0;
    state.specialDiscount = 0;
    state.isCustomPaid = false;
    state.pendingOrders = [];
    state.selectedPendingOrders = {};
    state.isMultiMode = false;
    state.activePaymentModes = ['Cash'];
    state.paymentMode = 'Cash';
    state.singlePaymentDoc = null;
    state.singleUtrDuplicate = false;
    state.splitCounter = 1;
    state.splitPaymentRows = [
      { id: 'split_1', mode: 'Cash', amount: 0, utr: '', file: null, isDuplicate: false, isValidUtr: false, warning: '' }
    ];
    state.splitPayments = {
      'Cash': { amount: 0, utr: '', file: null },
      'UPI': { amount: 0, utr: '', file: null },
      'Bank': { amount: 0, utr: '', file: null },
      'Cheque': { amount: 0, utr: '', file: null }
    };
    const chkMulti = document.getElementById('chkMultiPaymentMode');
    if (chkMulti) chkMulti.checked = false;
    const breakdown = document.getElementById('multiPaymentBreakdown');
    if (breakdown) breakdown.style.display = 'none';
    const utrBox = document.getElementById('utrFieldWrap');
    if (utrBox) utrBox.style.display = 'none';
    const singleDocWrap = document.getElementById('singleDocAttachWrap');
    if (singleDocWrap) singleDocWrap.style.display = 'block';
    const fileSingleInp = document.getElementById('fileSingleDoc');
    if (fileSingleInp) fileSingleInp.value = '';
    const singleBadge = document.getElementById('singleDocPreviewBadge');
    const singleLbl = document.getElementById('lblSingleDocAttach');
    if (singleBadge) singleBadge.style.display = 'none';
    if (singleLbl) singleLbl.style.display = 'inline-flex';

    const paidInput = document.getElementById('cartPaidAmount');
    if (paidInput) {
      paidInput.readOnly = false;
      paidInput.style.backgroundColor = '#fff';
      paidInput.value = '0';
      paidInput.dataset.touched = 'false';
    }
    document.querySelectorAll('.pay-mode-btn').forEach(b => {
      if (b.getAttribute('data-mode') === 'Cash') b.classList.add('active');
      else b.classList.remove('active');
    });
    clearSelectedClient();
    renderCartTable();
    calculateBillSummary();
    loadStock();
    const spDiscInput = document.getElementById('cartSpecialDiscount');
    if (spDiscInput) spDiscInput.value = '0';
    const remarks = document.getElementById('cartRemarks');
    const utr = document.getElementById('cartUtrNo');
    const veh = document.getElementById('cartVehicleNo');
    const dname = document.getElementById('cartDriverName');
    const dphone = document.getElementById('cartDriverPhone');
    if (remarks) remarks.value = '';
    if (utr) utr.value = '';
    if (veh) veh.value = '';
    if (dname) dname.value = '';
    if (dphone) dphone.value = '';

    // Reset Box Packing Charges
    state.boxPacking = {
      enabled: false,
      capacity: 500,
      rate: 100.00,
      boxCount: 0,
      totalCharge: 0
    };
    const chkBox = document.getElementById('chkEnableBoxPacking');
    if (chkBox) chkBox.checked = false;
    const boxBody = document.getElementById('boxPackingBody');
    if (boxBody) boxBody.style.display = 'none';
    const boxCountInput = document.getElementById('cartBoxCount');
    if (boxCountInput) boxCountInput.value = '0';
    const boxRateInput = document.getElementById('cartBoxRate');
    if (boxRateInput) boxRateInput.value = '100.00';
    const boxTotInput = document.getElementById('cartBoxTotalCharge');
    if (boxTotInput) boxTotInput.value = '0.00';
    const badgeBox = document.getElementById('boxRateBadge');
    if (badgeBox) badgeBox.textContent = '₹ 100.00 / Box';
  }

  // Reset Sales Order & Billing Dock only (cart, pricing summaries, payment fields) without clearing selected client
  function resetBillingDock() {
    state.cart = [];
    state.amountPaid = 0;
    state.specialDiscount = 0;
    state.isCustomPaid = false;
    state.isMultiMode = false;
    state.activePaymentModes = ['Cash'];
    state.paymentMode = 'Cash';
    state.singlePaymentDoc = null;
    state.singleUtrDuplicate = false;
    state.splitCounter = 1;
    state.splitPaymentRows = [
      { id: 'split_1', mode: 'Cash', amount: 0, utr: '', file: null, isDuplicate: false, isValidUtr: false, warning: '' }
    ];
    state.splitPayments = {
      'Cash': { amount: 0, utr: '', file: null },
      'UPI': { amount: 0, utr: '', file: null },
      'Bank': { amount: 0, utr: '', file: null },
      'Cheque': { amount: 0, utr: '', file: null }
    };

    const chkMulti = document.getElementById('chkMultiPaymentMode');
    if (chkMulti) chkMulti.checked = false;
    const breakdown = document.getElementById('multiPaymentBreakdown');
    if (breakdown) breakdown.style.display = 'none';
    const utrBox = document.getElementById('utrFieldWrap');
    if (utrBox) utrBox.style.display = 'none';
    const singleDocWrap = document.getElementById('singleDocAttachWrap');
    if (singleDocWrap) singleDocWrap.style.display = 'block';
    const fileSingleInp = document.getElementById('fileSingleDoc');
    if (fileSingleInp) fileSingleInp.value = '';
    const singleBadge = document.getElementById('singleDocPreviewBadge');
    const singleLbl = document.getElementById('lblSingleDocAttach');
    if (singleBadge) singleBadge.style.display = 'none';
    if (singleLbl) singleLbl.style.display = 'inline-flex';

    const paidInput = document.getElementById('cartPaidAmount');
    if (paidInput) {
      paidInput.readOnly = false;
      paidInput.style.backgroundColor = '#fff';
      paidInput.value = '0';
      paidInput.dataset.touched = 'false';
    }
    document.querySelectorAll('.pay-mode-btn').forEach(b => {
      if (b.getAttribute('data-mode') === 'Cash') b.classList.add('active');
      else b.classList.remove('active');
    });

    const spDiscInput = document.getElementById('cartSpecialDiscount');
    if (spDiscInput) spDiscInput.value = '0';
    const remarks = document.getElementById('cartRemarks');
    const utr = document.getElementById('cartUtrNo');
    const veh = document.getElementById('cartVehicleNo');
    const dname = document.getElementById('cartDriverName');
    const dphone = document.getElementById('cartDriverPhone');
    if (remarks) remarks.value = '';
    if (utr) utr.value = '';
    if (veh) veh.value = '';
    if (dname) dname.value = '';
    if (dphone) dphone.value = '';

    // Reset Box Packing Charges
    state.boxPacking = {
      enabled: false,
      capacity: 500,
      rate: 100.00,
      boxCount: 0,
      totalCharge: 0
    };
    const chkBox = document.getElementById('chkEnableBoxPacking');
    if (chkBox) chkBox.checked = false;
    const boxBody = document.getElementById('boxPackingBody');
    if (boxBody) boxBody.style.display = 'none';
    const boxCountInput = document.getElementById('cartBoxCount');
    if (boxCountInput) boxCountInput.value = '0';
    const boxRateInput = document.getElementById('cartBoxRate');
    if (boxRateInput) boxRateInput.value = '100.00';
    const boxTotInput = document.getElementById('cartBoxTotalCharge');
    if (boxTotInput) boxTotInput.value = '0.00';
    const badgeBox = document.getElementById('boxRateBadge');
    if (badgeBox) badgeBox.textContent = '₹ 100.00 / Box';

    renderCartTable();
    calculateBillSummary();
  }

  // Helper: Round to 2 decimals
  function round(val) {
    return Math.round((val + Number.EPSILON) * 100) / 100;
  }

  // Helper: Escape HTML
  function escapeHtml(text) {
    if (!text) return '';
    return String(text)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  // View / Download Dispatched Invoice PDF
  function openDispatchedInvoicePdf(invoiceNo) {
    if (!invoiceNo) {
      Swal.fire("Invoice Not Found", "No valid invoice number found.", "warning");
      return;
    }
    const cmp = window.APP_CONFIG?.companyid || 19;
    const sub = window.APP_CONFIG?.subcompanyid || 12;
    const prj = window.APP_CONFIG?.projectid || 17;
    const url = `../krf-orderandsales/modal/genrateInvoicePDF.php?invno=${encodeURIComponent(invoiceNo)}&companyid=${cmp}&subcompanyid=${sub}&projectid=${prj}&mode=I`;
    window.open(url, '_blank');
  }

  // View / Download Dispatched Gatepass PDF
  function openDispatchedGatepassPdf(psono, gatepassNo) {
    let url = `../krf-orderandsales/modal/gatepass_modal.php?action=view_pdf`;
    if (gatepassNo) url += `&gatepass_no=${encodeURIComponent(gatepassNo)}`;
    if (psono) url += `&psono=${encodeURIComponent(psono)}`;
    window.open(url, '_blank');
  }

  // Public API
  return {
    init: init,
    addProductGroupToCart: addProductGroupToCart,
    toggleGroupTrays: toggleGroupTrays,
    focusCartRow: focusCartRow,
    addProductToCart: addProductToCart,
    addTrayToCart: addTrayToCart,
    filterByProduct: filterByProduct,
    onCartQtyInput: onCartQtyInput,
    onCartQtyChange: onCartQtyChange,
    stepCartQty: stepCartQty,
    onCartRateInput: onCartRateInput,
    onCartRateChange: onCartRateChange,
    onCartDiscInput: onCartDiscInput,
    onCartDiscChange: onCartDiscChange,
    removeCartItem: removeCartItem,
    selectClient: selectClient,
    openNewClientModalWithQuery: openNewClientModalWithQuery,
    selectSuggestTray: selectSuggestTray,
    addSingleSuggestTray: addSingleSuggestTray,
    addSelectedSuggestTrays: addSelectedSuggestTrays,
    addMultipleTraysToCart: addMultipleTraysToCart,
    onToggleSuggestTray: onToggleSuggestTray,
    onToggleSelectAllSuggest: onToggleSelectAllSuggest,
    setSuggestFilter: setSuggestFilter,
    closeSuggestBox: closeSuggestBox,
    onSuggestItemRowClick: onSuggestItemRowClick,
    openDispatchedInvoicePdf: openDispatchedInvoicePdf,
    openDispatchedGatepassPdf: openDispatchedGatepassPdf,
    onTogglePendingOrder: onTogglePendingOrder,
    onPendingPayAmountInput: onPendingPayAmountInput,
    onToggleAllPendingOrders: onToggleAllPendingOrders,
    autoCalculateBoxes: autoCalculateBoxes,
    toggleMultiPaymentMode: toggleMultiPaymentMode,
    renderMultiPayRows: renderMultiPayRows,
    updateMultiPayFooter: updateMultiPayFooter,
    onCartFocInput: onCartFocInput,
    onCartFocChange: onCartFocChange,
    addSplitRow: addSplitRow,
    removeSplitRow: removeSplitRow,
    onSingleUtrInput: onSingleUtrInput,
    onMultiPayUtrChange: onMultiPayUtrChange,
    onMultiPayAmtInput: onMultiPayAmtInput,
    onMultiPayUtrInput: onMultiPayUtrInput,
    fillMultiPayBalance: fillMultiPayBalance,
    deactivateMultiPayMode: deactivateMultiPayMode,
    openOrderPdfModal: openOrderPdfModal,
    closeOrderPdfModal: closeOrderPdfModal,
    onSingleDocFileSelected: onSingleDocFileSelected,
    previewSingleDoc: previewSingleDoc,
    clearSingleDoc: clearSingleDoc,
    onMultiPayDocSelected: onMultiPayDocSelected,
    previewMultiDoc: previewMultiDoc,
    clearMultiDoc: clearMultiDoc,
    openDocPreview: openDocPreview,
    closeDocPreviewModal: closeDocPreviewModal
  };
})();

// Also expose globally for easy access
window.openDispatchedInvoicePdf = OpenSalePOS.openDispatchedInvoicePdf;
window.openDispatchedGatepassPdf = OpenSalePOS.openDispatchedGatepassPdf;

// Auto-boot on DOM ready
document.addEventListener('DOMContentLoaded', function () {
  OpenSalePOS.init();
});
