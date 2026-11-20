// Location Inventory & Transaction Ledger - JS Logic

// Global Variables
let transferItems = [];
let cachedTransferItems = [];
let transferItemFocusIdx = -1;
let searchTimeout = null;

// --- Load Consolidated & Location-Specific Inventory & Transactions ---

function loadLocationData() {
    const locId = $('#filterLocation').val() || 'all';
    const transType = $('#filterTransType').val() || 'all';
    const search = $('#tableSearch').val() || '';

    // Update display label
    const locName = $('#filterLocation option:selected').text().trim();
    $('#activeLocationDisplay').html(`<i class="fas fa-warehouse text-success me-1"></i> Showing: ${locName}`);

    // Show loading states
    $('#stockTableBody').html('<tr><td colspan="5" class="text-center py-5 text-muted"><i class="fas fa-spinner fa-spin me-2"></i> Loading stock inventory...</td></tr>');
    $('#transactionsTableBody').html('<tr><td colspan="8" class="text-center py-5 text-muted"><i class="fas fa-spinner fa-spin me-2"></i> Loading transactions ledger...</td></tr>');

    $.post('ajax_po.php', {
        action: 'list_location_inventory_and_transactions',
        location_id: locId,
        trans_type_filter: transType,
        search: search
    }, function (res) {
        if (res.status === 'success') {
            $('#stockTableBody').html(res.stock_rows);
            $('#transactionsTableBody').html(res.tx_rows);

            // Update KPI Stats
            if (res.stats) {
                $('#statStockQty').text(res.stats.total_stock_qty);
                $('#statItemsCount').text(res.stats.total_items_count + ' Stocked Items');

                $('#statInQty').text('+' + res.stats.total_in_qty);
                $('#statInCount').text(res.stats.total_in_count + ' Inward Logs');

                $('#statOutQty').text('-' + res.stats.total_out_qty);
                $('#statOutCount').text(res.stats.total_out_count + ' Outward Logs');

                const pending = parseInt(res.stats.pending_transfers) || 0;
                const dispatched = parseInt(res.stats.dispatched_transfers) || 0;
                $('#statPendingTransfers').text(pending + dispatched);
                $('#statTransfersSub').text(`${pending} Pending / ${dispatched} In Transit`);
            }
        } else {
            $('#stockTableBody').html('<tr><td colspan="5" class="text-center py-4 text-danger"><i class="fas fa-exclamation-triangle me-2"></i> Failed to load stock inventory.</td></tr>');
            $('#transactionsTableBody').html('<tr><td colspan="8" class="text-center py-4 text-danger"><i class="fas fa-exclamation-triangle me-2"></i> Failed to load transactions ledger.</td></tr>');
        }
    }, 'json').fail(function() {
        $('#stockTableBody').html('<tr><td colspan="5" class="text-center py-4 text-danger"><i class="fas fa-exclamation-triangle me-2"></i> Server communication error.</td></tr>');
        $('#transactionsTableBody').html('<tr><td colspan="8" class="text-center py-4 text-danger"><i class="fas fa-exclamation-triangle me-2"></i> Server communication error.</td></tr>');
    });
}

function loadTransfers() {
    const locId = $('#filterLocation').val() || 'all';
    const search = $('#tableSearch').val() || '';

    $('#transferTableBody').html('<tr><td colspan="6" class="text-center py-5 text-muted"><i class="fas fa-spinner fa-spin me-2"></i> Loading transfer requests...</td></tr>');

    $.post('ajax_po.php', {
        action: 'list_transfers',
        location_id: locId,
        search: search
    }, function (res) {
        if (res && res.rows) {
            $('#transferTableBody').html(res.rows);
        }
    }, 'json');
}

function refreshAllData() {
    loadLocationData();
    loadTransfers();
}

function loadLocations() {
    $.post('ajax_po.php', { action: 'list_locations' }, function (res) {
        if (res && res.options) {
            $('#transferFrom').html(res.options);
            $('#transferTo').html(res.options);
            $('#transferFrom, #transferTo').trigger('change');
        }
    }, 'json');
}

// --- Item Stock Breakdown & In/Out History Modal ---

function openItemStockDetailModal(seedId, itemName) {
    if (!seedId) return;

    $('#itemStockDetailModalTitle').text(itemName || 'Item Stock Breakdown');
    $('#itemStockDetailModalSub').html('<i class="fas fa-spinner fa-spin me-1"></i> Loading complete 360-degree stock data...');
    $('#modalKpiPurchased').text('...');
    $('#modalKpiUsed').text('...');
    $('#modalKpiStock').text('...');
    $('#modalKpiBalance').text('...');
    $('#modalLocationTableBody').html('<tr><td colspan="3" class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin me-2"></i> Loading location distribution...</td></tr>');
    $('#modalTransactionTableBody').html('<tr><td colspan="6" class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin me-2"></i> Loading transaction history...</td></tr>');

    const modalEl = document.getElementById('itemStockDetailModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();

    $.post('ajax_po.php', {
        action: 'get_item_location_breakdown',
        seed_id: seedId
    }, function (res) {
        if (res.status === 'success') {
            const item = res.item || {};
            const summary = res.summary || {};
            const locations = res.locations || [];
            const txs = res.transactions || [];

            const skuBadge = item.sku ? `<span class="stock-sku ms-2">${item.sku}</span>` : '';
            $('#itemStockDetailModalTitle').html(`${item.item_name || itemName} ${skuBadge}`);
            $('#itemStockDetailModalSub').text('Item Stock Balance Across All Warehouse Locations');

            // KPI Cards
            const purVal = parseFloat(summary.total_purchased ?? summary.total_purchase ?? 0);
            const usedVal = parseFloat(summary.total_used ?? 0);
            const stockVal = parseFloat(summary.current_stock ?? summary.stock_quantity ?? 0);
            const balVal = parseFloat(summary.balance_qty ?? summary.balance_quantity ?? stockVal);

            $('#modalKpiPurchased').text(isNaN(purVal) ? '0.00' : purVal.toFixed(2));
            $('#modalKpiUsed').text(isNaN(usedVal) ? '0.00' : usedVal.toFixed(2));
            $('#modalKpiStock').text(isNaN(stockVal) ? '0.00' : stockVal.toFixed(2));
            $('#modalKpiBalance').text(isNaN(balVal) ? '0.00' : balVal.toFixed(2));

            // Location Breakdown Table
            $('#modalLocationCountBadge').text(`${locations.length} Locations with Stock`);
            let locHtml = "";
            if (locations.length > 0) {
                locations.forEach(loc => {
                    const qtyNum = parseFloat(loc.quantity_formatted ?? loc.stock_quantity ?? loc.quantity ?? 0);
                    const qtyDisp = isNaN(qtyNum) ? '0.00' : qtyNum.toFixed(2);
                    const lastUp = loc.last_updated || '-';

                    locHtml += `<tr>
                        <td class="ps-3 fw-semibold text-dark"><i class="fas fa-map-marker-alt text-success me-2"></i>${loc.location_name}</td>
                        <td class="text-end"><span class="stock-num stock-num-success fs-6">${qtyDisp}</span></td>
                        <td class="text-end pe-3 text-muted small">${lastUp}</td>
                    </tr>`;
                });
            } else {
                locHtml = `<tr><td colspan="3" class="text-center py-4 text-muted"><i class="fas fa-info-circle me-1"></i> No location-specific stock found for this item.</td></tr>`;
            }
            $('#modalLocationTableBody').html(locHtml);

            // Transactions Table
            let txHtml = "";
            if (txs.length > 0) {
                txs.forEach(tx => {
                    const qty = parseFloat(tx.qty_change) || 0;
                    const isIn = (qty > 0);
                    const qtyClass = isIn ? 'text-success fw-bold' : 'text-danger fw-bold';
                    const qtySign = isIn ? '+' : '';
                    const badgeClass = isIn ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle';
                    const typeLabel = tx.trans_type || tx.reference_type || (isIn ? 'IN' : 'OUT');
                    const refNote = tx.notes || '-';
                    const refId = tx.reference_id ? '#' + tx.reference_id : '-';

                    txHtml += `<tr>
                        <td class="ps-3 text-nowrap small text-muted"><i class="far fa-clock me-1 text-primary"></i>${tx.created_at}</td>
                        <td><span class="badge badge-single-loc"><i class="fas fa-map-marker-alt me-1"></i>${tx.location_name}</span></td>
                        <td><span class="badge ${badgeClass} px-2 py-1">${typeLabel}</span></td>
                        <td class="text-end ${qtyClass} font-monospace">${qtySign}${qty.toFixed(2)}</td>
                        <td><span class="font-monospace small text-dark">${refId}</span></td>
                        <td class="pe-3 small text-muted text-break">${refNote}</td>
                    </tr>`;
                });
            } else {
                txHtml = `<tr><td colspan="6" class="text-center py-4 text-muted"><i class="fas fa-receipt me-1"></i> No transactions logged for this item.</td></tr>`;
            }
            $('#modalTransactionTableBody').html(txHtml);

        } else {
            $('#itemStockDetailModalSub').html('<span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> Failed to load item details.</span>');
        }
    }, 'json').fail(function() {
        $('#itemStockDetailModalSub').html('<span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> Server error fetching breakdown.</span>');
    });
}

// --- View & Receive Transfer Actions ---

function viewTransfer(id) {
    $.post('ajax_po.php', { action: 'get_transfer_details', id: id }, function (res) {
        if (res.status == 'success') {
            let d = res.data;
            $('#trRoute').html(`${d.FromName} <i class="fas fa-arrow-right text-success mx-2"></i> ${d.ToName}`);
            $('#trDate').html(`<i class="far fa-calendar-alt me-1"></i> Date: ${d.request_date} | <span class="badge bg-primary ms-1">${d.status}</span>`);
            $('#trItemsBody').html(d.items_html);
            new bootstrap.Modal('#transferModal').show();
        }
    }, 'json');
}

function receiveTransfer(reqId) {
    Swal.fire({
        title: 'Confirm Stock Receipt?',
        text: "Confirm that you have physically received and verified these items at this warehouse.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#16a34a',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fas fa-check me-1"></i> Yes, Receive Stock'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('ajax_po.php', { action: 'receive_transfer', request_id: reqId }, function (res) {
                if (res.status == 'success') {
                    Swal.fire('Stock Received!', res.message, 'success');
                    refreshAllData();
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            }, 'json');
        }
    });
}

function approveTransfer(id) {
    Swal.fire({
        title: 'Dispatch Stock',
        text: 'To dispatch and approve stock transfers, please use the Main Warehouse Dispatch Panel.',
        icon: 'info',
        confirmButtonColor: '#16a34a'
    });
}

// --- Request Creation Logic ---

function openRequestModal() {
    var myModal = new bootstrap.Modal(document.getElementById('requestModal'));
    myModal.show();
    $('#transferForm')[0].reset();
    $('#transferItemSearch').val('');
    $('#btnClearTransferItemSearch').hide();
    $('#transferItemSuggestions').empty().hide();
    transferItemFocusIdx = -1;
    transferItems = [];
    renderTransferItems();

    setTimeout(function () {
        $('#transferItemSearch').focus();
    }, 400);
}

function renderTransferItemSuggestions(query) {
    const $list = $('#transferItemSuggestions');
    $list.empty();
    transferItemFocusIdx = -1;

    if (!Array.isArray(cachedTransferItems) || cachedTransferItems.length === 0) {
        $.post('ajax_po.php', { action: 'list_items' }, function (res) {
            if (res && res.items) {
                cachedTransferItems = res.items;
                renderTransferItemSuggestions(query);
            }
        }, 'json');
        $list.html('<div class="p-2 text-center text-muted small"><i class="fas fa-spinner fa-spin me-1"></i>Loading items...</div>').show();
        return;
    }

    const q = (query || '').trim().toLowerCase();
    const matches = cachedTransferItems.filter(item => {
        if (!q) return true;
        const name = (item.name || '').toLowerCase();
        const sku = (item.sku || '').toLowerCase();
        return name.indexOf(q) !== -1 || sku.indexOf(q) !== -1;
    }).slice(0, 30);

    if (matches.length === 0) {
        $list.html('<div class="p-2 text-center text-muted small"><i class="fas fa-info-circle me-1"></i>No items match search</div>').show();
        return;
    }

    matches.forEach(item => {
        const skuBadge = item.sku ? `<span class="stock-sku ms-2">${item.sku}</span>` : '';
        const $item = $(`
            <a href="javascript:void(0);" class="list-group-item list-group-item-action transfer-item-suggest py-2 px-3 border-bottom d-flex justify-content-between align-items-center" style="cursor: pointer;">
                <div>
                    <span class="fw-semibold text-dark item-text-name">${item.name}</span>
                    ${skuBadge}
                </div>
                <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fas fa-plus me-1"></i>Add</span>
            </a>
        `);
        $item.data('item', item);
        $list.append($item);
    });

    $list.show();
}

function selectTransferItem(item) {
    if (!item) return;
    let fromLoc = $('#transferFrom').val();

    if (!fromLoc) {
        Swal.fire({
            icon: 'warning',
            title: 'Select Source First',
            text: 'Please select the Source Warehouse (From Location) before selecting items to check stock.'
        });
        $('#transferFrom').select2('open');
        return;
    }

    // Check stock
    $.post('ajax_po.php', { action: 'get_stock', seed_id: item.id, location_id: fromLoc || 0 }, function (res) {
        let stock = parseFloat(res.stock) || 0;
        addTransferItem(item.id, item.text || (item.name + (item.sku ? ' (' + item.sku + ')' : '')), stock);
        $('#transferItemSearch').val('').focus();
        $('#btnClearTransferItemSearch').hide();
        $('#transferItemSuggestions').empty().hide();
    }, 'json');
}

function initTransferSelect2() {
    $('#transferFrom, #transferTo').select2({
        dropdownParent: $('#requestModal'),
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Select Location...'
    });

    $('#transferFrom').on('change', function () {
        let fromLoc = $(this).val();
        if (transferItems.length > 0 && fromLoc) {
            transferItems.forEach(item => {
                $.post('ajax_po.php', { action: 'get_stock', seed_id: item.seed_id, location_id: fromLoc }, function (res) {
                    item.stock = parseFloat(res.stock) || 0;
                    renderTransferItems();
                }, 'json');
            });
        }
    });

    // Live typing in item search input
    $(document).on('input focus', '#transferItemSearch', function () {
        const val = $(this).val();
        if (val) {
            $('#btnClearTransferItemSearch').show();
        } else {
            $('#btnClearTransferItemSearch').hide();
        }
        renderTransferItemSuggestions(val);
    });

    // Clear search button
    $(document).on('click', '#btnClearTransferItemSearch', function () {
        $('#transferItemSearch').val('').focus();
        $('#btnClearTransferItemSearch').hide();
        renderTransferItemSuggestions('');
    });

    // Arrow keys & Enter key navigation for autocomplete
    $(document).on('keydown', '#transferItemSearch', function (e) {
        const $list = $('#transferItemSuggestions');
        const $items = $list.find('.transfer-item-suggest');
        const count = $items.length;

        if (!$list.is(':visible') || count === 0) {
            if (e.which === 40 || e.which === 38) {
                renderTransferItemSuggestions($(this).val());
            }
            return;
        }

        if (e.which === 40) { // Arrow Down
            e.preventDefault();
            transferItemFocusIdx++;
            if (transferItemFocusIdx >= count) transferItemFocusIdx = 0;
            $items.removeClass('active bg-success text-white').find('.text-dark').removeClass('text-white');
            const $target = $items.eq(transferItemFocusIdx);
            $target.addClass('active bg-success text-white').find('.text-dark').addClass('text-white');
            $target[0].scrollIntoView({ block: 'nearest' });
        } else if (e.which === 38) { // Arrow Up
            e.preventDefault();
            transferItemFocusIdx--;
            if (transferItemFocusIdx < 0) transferItemFocusIdx = count - 1;
            $items.removeClass('active bg-success text-white').find('.text-dark').removeClass('text-white');
            const $target = $items.eq(transferItemFocusIdx);
            $target.addClass('active bg-success text-white').find('.text-dark').addClass('text-white');
            $target[0].scrollIntoView({ block: 'nearest' });
        } else if (e.which === 13) { // Enter Key
            e.preventDefault();
            if (transferItemFocusIdx >= 0 && transferItemFocusIdx < count) {
                const item = $items.eq(transferItemFocusIdx).data('item');
                selectTransferItem(item);
            } else if (count > 0) {
                const item = $items.eq(0).data('item');
                selectTransferItem(item);
            }
        } else if (e.which === 27) { // Escape Key
            $list.hide();
        }
    });

    $(document).on('click', '.transfer-item-suggest', function () {
        const item = $(this).data('item');
        selectTransferItem(item);
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('#transferItemSearch, #transferItemSuggestions, #btnClearTransferItemSearch').length) {
            $('#transferItemSuggestions').hide();
        }
    });
}

function addTransferItem(id, name, stock) {
    if (transferItems.find(i => i.seed_id == id)) return;
    transferItems.push({ seed_id: id, name: name, qty: 1, stock: stock });
    renderTransferItems();
}

function renderTransferItems() {
    let html = "";
    if (transferItems.length === 0) {
        html = '<small class="text-muted d-block text-center mt-3">No items added yet. Search above to add items.</small>';
    } else {
        transferItems.forEach((item, idx) => {
            let badge = item.stock > 0 
                ? `<span class="badge bg-success-subtle text-success border border-success-subtle float-end">${item.stock} Avail</span>` 
                : `<span class="badge bg-danger-subtle text-danger border border-danger-subtle float-end">Out of Stock</span>`;

            html += `<div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-white border rounded shadow-sm">
                <div style="width: 50%">
                    <small class="fw-bold d-block text-truncate text-dark" title="${item.name}">${item.name}</small>
                    ${badge}
                </div>
                <div style="width: 35%">
                    <input type="number" class="form-control form-control-sm font-monospace" value="${item.qty}" min="1" max="${item.stock}" onchange="transferItems[${idx}].qty = this.value">
                </div>
                <div style="width: 10%" class="text-end">
                     <button type="button" class="btn btn-sm text-danger" onclick="transferItems.splice(${idx}, 1); renderTransferItems();" title="Remove"><i class="fas fa-trash-alt"></i></button>
                </div>
             </div>`;
        });
    }
    $('#transferItemsList').html(html);
}

function submitTransferRequest() {
    let from = $('#transferFrom').val();
    let to = $('#transferTo').val();

    if (!from || !to) { Swal.fire('Error', 'Please select both source and destination warehouse.', 'warning'); return; }
    if (from == to) { Swal.fire('Error', 'Source and Destination warehouses cannot be the same.', 'warning'); return; }
    if (transferItems.length == 0) { Swal.fire('Error', 'Please add at least one item to request.', 'warning'); return; }

    let exceed = transferItems.some(i => parseFloat(i.qty) > parseFloat(i.stock));
    if (exceed) {
        Swal.fire('Stock Limit Exceeded', 'One or more items exceed the available stock at the source warehouse.', 'error');
        return;
    }

    $.post('ajax_po.php', {
        action: 'save_transfer_request',
        from_location: from,
        to_location: to,
        remarks: $('[name="remarks"]').val(),
        items: JSON.stringify(transferItems)
    }, function (res) {
        if (res.status == 'success') {
            Swal.fire('Submitted!', 'Stock request has been created successfully.', 'success');
            $('#requestModal').modal('hide');
            refreshAllData();
        } else {
            Swal.fire('Error', res.message, 'error');
        }
    }, 'json');
}

// --- Initialize when DOM is ready ---
$(document).ready(function () {
    // Initial data load
    refreshAllData();
    loadLocations();

    // Event listeners for filters
    $('#filterLocation, #filterTransType').on('change', function () {
        refreshAllData();
    });

    $('#tableSearch').on('input', function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function () {
            refreshAllData();
        }, 300);
    });

    // Initialize request autocomplete & select2
    $.post('ajax_po.php', { action: 'list_items' }, function (res) {
        initTransferSelect2();
    }, 'json');

    // Attach global functions for inline onclicks
    window.openRequestModal = openRequestModal;
    window.submitTransferRequest = submitTransferRequest;
    window.viewTransfer = viewTransfer;
    window.receiveTransfer = receiveTransfer;
    window.approveTransfer = approveTransfer;
    window.refreshAllData = refreshAllData;
    window.openItemStockDetailModal = openItemStockDetailModal;
});
