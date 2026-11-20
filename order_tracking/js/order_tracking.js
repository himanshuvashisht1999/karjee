/**
 * Customer 360° Payment & Order Lifecycle Tracker - Frontend Application
 */

$(document).ready(function () {
    let currentClientData = null;
    let activeFilter = 'all';

    // Universal Modal Dismiss Handler
    $(document).on("click", '[data-bs-dismiss="modal"], [data-dismiss="modal"], .close-modal-btn', function (e) {
        e.preventDefault();
        let $modal = $(this).closest(".modal");
        $modal.modal("hide");
        setTimeout(function () {
            $(".modal-backdrop").remove();
            $("body").removeClass("modal-open").css("padding-right", "");
        }, 200);
    });

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatNumber(num) {
        return Number(num || 0).toLocaleString('en-IN');
    }

    function formatCurrency(amount) {
        return '₹' + Number(amount || 0).toLocaleString('en-IN', { maximumFractionDigits: 2 });
    }

    // Initialize Select2 Autocomplete for Client Search
    $("#clientSearchSelect").select2({
        theme: "bootstrap-5",
        placeholder: "Search by Mobile No, Client Name, Client ID, or Order No (PSONO/SONO)...",
        allowClear: true,
        ajax: {
            url: "modal/order_tracking_modal.php",
            type: "POST",
            dataType: "json",
            delay: 300,
            data: function (params) {
                return {
                    action: "SearchClients",
                    searchTerm: params.term || '',
                    projectid: $("body").data("projectid") || 0
                };
            },
            processResults: function (data) {
                return {
                    results: data.results || []
                };
            },
            cache: true
        },
        minimumInputLength: 0
    });

    // When client is selected from Select2 dropdown
    $("#clientSearchSelect").on("select2:select", function (e) {
        let data = e.params.data;
        let searchKey = data.mobileno || data.clientid || data.id || data.text;
        $("#clientSearchInput").val(searchKey);
        loadClientHistory(searchKey);
    });

    // Search button click
    $("#btnSearchClient").on("click", function () {
        let val = $("#clientSearchInput").val().trim();
        if (!val) {
            Swal.fire("Enter Client Info", "Please enter a mobile number, client name, or order number to search.", "info");
            return;
        }
        loadClientHistory(val);
    });

    // Enter key inside search input
    $("#clientSearchInput").on("keypress", function (e) {
        if (e.which === 13) {
            $("#btnSearchClient").click();
        }
    });

    // Quick reset button
    $("#btnResetSearch").on("click", function () {
        $("#clientSearchInput").val('');
        $("#clientSearchSelect").val(null).trigger('change');
        $("#clientResultsContainer").hide();
        $("#emptySearchState").fadeIn(200);
        currentClientData = null;
    });

    // Check URL parameters for initial load (e.g. ?client=9876543210 or ?psono=KF-PS-00123)
    let urlParams = new URLSearchParams(window.location.search);
    let initKey = urlParams.get('client') || urlParams.get('mobile') || urlParams.get('psono') || urlParams.get('sono') || urlParams.get('search');
    if (initKey) {
        $("#clientSearchInput").val(initKey);
        loadClientHistory(initKey);
    }

    let lastLoadedSearchKey = '';

    /**
     * Fetch client 360 history via AJAX
     */
    function loadClientHistory(searchKey, autoExpandPsono = null) {
        lastLoadedSearchKey = searchKey;
        let projectid = $("body").data("projectid") || 0;

        $("#emptySearchState").hide();
        $("#clientResultsContainer").hide();
        $("#searchLoadingState").fadeIn(150);

        $.ajax({
            url: "modal/order_tracking_modal.php?action=GetClientFullHistory",
            type: "POST",
            data: {
                searchKey: searchKey,
                projectid: projectid
            },
            dataType: "json",
            success: function (res) {
                $("#searchLoadingState").hide();

                if (res.status !== "success") {
                    Swal.fire({
                        icon: "warning",
                        title: "No Records Found",
                        text: res.message || "No client or payment records found for the searched query."
                    });
                    $("#emptySearchState").fadeIn(200);
                    return;
                }

                currentClientData = res;
                renderClientProfile(res.client);
                renderSummaryKPIs(res.summary_kpis);
                renderProvisionalOrdersTable(res.orders_cards, res.unallocated_payments);
                renderAllPaymentsLedger(res.all_payments);

                // Auto-expand specific order if requested (e.g. after editing item quantity)
                if (autoExpandPsono) {
                    let $targetRow = $(`.order-main-row[data-psono="${autoExpandPsono}"]`);
                    if ($targetRow.length > 0) {
                        let tIdx = $targetRow.data("order-idx");
                        let $sub = $(`#orderDetailSubrow_${tIdx}`);
                        $sub.addClass("is-open").attr("style", "display: table-row !important;");
                        $targetRow.addClass("is-expanded");
                    }
                }

                $("#clientResultsContainer").fadeIn(250);
            },
            error: function (xhr, status, error) {
                $("#searchLoadingState").hide();
                console.error("Client History Fetch Error:", status, error, xhr.responseText);
                let errMsg = "Could not connect to server to fetch client history. Please try again.";
                try {
                    let errObj = JSON.parse(xhr.responseText);
                    if (errObj && errObj.message) {
                        errMsg = errObj.message;
                    }
                } catch (e) {
                    if (xhr.status === 500) {
                        errMsg = "Server Error (500). Please check database and server error logs.";
                    }
                }
                Swal.fire("Error", errMsg, "error");
                $("#emptySearchState").fadeIn(200);
            }
        });
    }

    /**
     * Render Client Profile Banner
     */
    function renderClientProfile(c) {
        if (!c) return;
        let initial = (c.clientname || 'C').charAt(0).toUpperCase();
        $("#clientAvatarInitial").text(initial);
        $("#clientNameDisplay").text(c.clientname || 'Unknown Client');
        $("#clientMobileDisplay").text(c.mobileno || '-');
        $("#clientIdDisplay").text(c.clientid ? `#${c.clientid}` : '-');

        let locParts = [c.districtname, c.statename].filter(Boolean);
        $("#clientLocationDisplay").text(locParts.length > 0 ? locParts.join(', ') : 'Location not recorded');

        let fullAddr = (c.address || '').trim();
        if (fullAddr) {
            $("#clientAddressDisplay").text(fullAddr).show();
        } else {
            $("#clientAddressDisplay").hide();
        }
    }

    /**
     * Render Summary KPIs
     */
    function renderSummaryKPIs(kpis) {
        if (!kpis) return;
        $("#kpiTotalOrders").text(kpis.total_orders || 0);
        $("#kpiTotalBilled").text(formatCurrency(kpis.total_order_value));
        $("#kpiTotalPaid").text(formatCurrency(kpis.total_advance_paid));
        $("#kpiBalanceDue").text(formatCurrency(kpis.total_balance_due));

        $("#kpiPlantsOrdered").text(formatNumber(kpis.total_plants_ordered));
        $("#kpiPlantsSown").text(formatNumber(kpis.total_plants_sown));
        $("#kpiPlantsAllocated").text(formatNumber(kpis.total_plants_allocated));
        $("#kpiPlantsDispatched").text(formatNumber(kpis.total_plants_dispatched));
    }

    /**
     * Render Provisional Orders Data Table (Order-Wise Rows with Rest Amount)
     */
    function renderProvisionalOrdersTable(orders, unallocatedPayments) {
        let tbody = $("#provisionalOrdersTableBody");
        tbody.empty();

        if (!orders || orders.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        <i class="fa fa-folder-open fa-2x mb-2 text-secondary opacity-50 d-block"></i>
                        No Provisional Sales Orders found for this client.
                    </td>
                </tr>
            `);
            return;
        }

        orders.forEach((order, idx) => {
            let psono = order.psono || '-';
            let sono = order.sono || '';
            let status = order.status || 'Active';

            // Status Badge Colors
            let statusBadgeClass = 'bg-primary-subtle text-primary border border-primary-subtle';
            if (status.toLowerCase().includes('confirmed') || status.toLowerCase().includes('completed')) {
                statusBadgeClass = 'bg-success-subtle text-success border border-success-subtle';
            } else if (status.toLowerCase().includes('emergency')) {
                statusBadgeClass = 'bg-danger-subtle text-danger border border-danger-subtle';
            } else if (status.toLowerCase().includes('pending')) {
                statusBadgeClass = 'bg-warning-subtle text-warning border border-warning-subtle';
            }

            // Crops Summary Text
            let cropsSummary = '-';
            if (order.items && order.items.length > 0) {
                cropsSummary = order.items.map(it => `${escapeHtml(it.product_name)} <span class="badge bg-light text-dark border">(${formatNumber(it.quantity)})</span>`).join('<br>');
            }

            // Sowing Summary Text
            let sowingSummary = '<span class="text-muted small">-</span>';
            if (order.sowings && order.sowings.length > 0) {
                sowingSummary = order.sowings.map(s => `<span class="badge bg-success-subtle text-success border"><i class="fa fa-seedling me-1"></i>${formatNumber(s.totalsowingqty)}</span> <span class="font-monospace small text-primary">${escapeHtml(s.stockorderno || '')}</span>`).join('<br>');
            } else if (order.stock_order_numbers && order.stock_order_numbers.length > 0) {
                sowingSummary = order.stock_order_numbers.map(stk => `<span class="font-monospace small text-primary">${escapeHtml(stk)}</span>`).join(', ');
            }

            // Rest Amount Formatting
            let restAmountHtml = '';
            if (order.balance_due > 0) {
                restAmountHtml = `<span class="badge bg-danger fs-6 fw-bold shadow-sm px-2.5 py-1">₹${Number(order.balance_due).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>`;
            } else {
                restAmountHtml = `<span class="badge bg-success text-white fw-bold px-2.5 py-1"><i class="fa fa-check-circle me-1"></i>₹0.00 Paid</span>`;
            }

            // Advance Paid with mini progress
            let advancePaidHtml = `
                <div class="fw-bold text-success">${formatCurrency(order.advance_paid)}</div>
                <div class="small text-muted" style="font-size: 10.5px;">${order.paid_percentage}% paid</div>
            `;

            // Items Table for Details
            let itemsDetailsHtml = '';
            if (order.items && order.items.length > 0) {
                itemsDetailsHtml = `
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0 align-middle" style="font-size: 11.5px;">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 25px;" class="text-center">#</th>
                                    <th>Crop & Variety</th>
                                    <th class="text-end" style="width: 105px;">Order Qty</th>
                                    <th class="text-end" style="min-width: 130px;">Dispatched Qty</th>
                                    <th class="text-end" style="width: 75px;">Rate</th>
                                    <th class="text-end" style="width: 90px;">Amount</th>
                                    <th class="text-center" style="width: 95px;">Delivery Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${order.items.map((it, iIdx) => {
                                    let ordQty = parseInt(it.quantity) || 0;
                                    let dispQty = parseInt(it.dispatched_qty) || 0;
                                    let dispatches = it.dispatches || [];

                                    let dispHtml = '';
                                    if (dispQty === 0 && dispatches.length === 0) {
                                        dispHtml = `<span class="badge bg-light text-muted border px-2 py-1">0</span>`;
                                    } else {
                                        dispHtml = `
                                            <div class="d-flex align-items-center justify-content-end gap-1">
                                                <span class="fw-bold text-success">${formatNumber(dispQty)}</span>
                                                ${dispatches.length > 1 ? `<span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 9px;" title="${dispatches.length} separate dispatch invoices">${dispatches.length} Invoices</span>` : ''}
                                            </div>
                                            ${dispatches.length > 0 ? `
                                                <div class="mt-1 text-start" style="font-size: 10px; line-height: 1.35; max-height: 80px; overflow-y: auto;">
                                                    ${dispatches.map(d => `
                                                        <div class="text-nowrap border-bottom border-light py-0.5" title="Invoice Date: ${escapeHtml(d.formatted_dispatch_date)}">
                                                            <span class="font-monospace fw-semibold text-primary"><i class="fa fa-file-invoice me-1"></i>${escapeHtml(d.invoiceno)}</span>: 
                                                            <b class="text-dark">${formatNumber(d.quantity)}</b>
                                                            <span class="text-muted small">(${escapeHtml(d.formatted_dispatch_date || '-')})</span>
                                                        </div>
                                                    `).join('')}
                                                </div>
                                            ` : ''}
                                        `;
                                    }

                                    return `
                                        <tr>
                                            <td class="text-center text-muted">${iIdx + 1}</td>
                                            <td class="fw-semibold text-dark">${escapeHtml(it.product_name)}</td>
                                            <td class="text-end">
                                                <div class="d-flex align-items-center justify-content-end gap-1">
                                                    <span class="fw-bold text-primary">${formatNumber(ordQty)}</span>
                                                    <button type="button" 
                                                        class="btn btn-outline-primary btn-xs py-0 px-1 border-0 btn-edit-order-qty" 
                                                        data-item-id="${it.id}" 
                                                        data-psono="${escapeHtml(psono)}" 
                                                        data-product-id="${escapeHtml(it.product_id)}" 
                                                        data-product-name="${escapeHtml(it.product_name)}" 
                                                        data-quantity="${ordQty}" 
                                                        data-rate="${it.rate || 0}" 
                                                        data-amount="${it.amount || 0}" 
                                                        title="Edit Order Quantity">
                                                        <i class="fa fa-pencil-alt text-primary" style="font-size: 11px;"></i>
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="text-end">${dispHtml}</td>
                                            <td class="text-end text-muted">₹${Number(it.rate || 0).toFixed(2)}</td>
                                            <td class="text-end fw-bold text-dark">₹${Number(it.amount || 0).toLocaleString()}</td>
                                            <td class="text-center text-secondary">${escapeHtml(it.formatted_delivery_date || '-')}</td>
                                        </tr>
                                    `;
                                }).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
            } else {
                itemsDetailsHtml = `<div class="text-muted small py-2 fst-italic">No line items detailed.</div>`;
            }

            // Payments Table for Details
            let paymentsDetailsHtml = '';
            if (order.payments && order.payments.length > 0) {
                paymentsDetailsHtml = `
                    <div class="table-responsive">
                        <table class="table table-sm table-payments-inner table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Mode</th>
                                    <th>UTR / Ref No</th>
                                    <th>Received By</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Receipt</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${order.payments.map(pay => {
                                    let isVerified = (pay.verification || '').toLowerCase() === 'verified';
                                    let hasReceipt = Boolean(pay.receiptpath);
                                    let receivedByName = pay.received_by_name || pay.receivedby || pay.allocatedby || '-';
                                    return `
                                        <tr>
                                            <td class="fw-semibold text-dark">${escapeHtml(pay.formatted_payment_date || '-')}</td>
                                            <td><span class="badge bg-light text-dark border">${escapeHtml(pay.sourceofpayment || 'Advance')}</span></td>
                                            <td class="font-monospace small text-primary">${escapeHtml(pay.utrnumber || pay.paymentref_no || '-')}</td>
                                            <td><span class="badge bg-light text-dark border fw-medium" title="Recorded ID: ${escapeHtml(pay.allocatedby || pay.receivedby || '')}"><i class="fa fa-user-circle text-muted me-1"></i>${escapeHtml(receivedByName)}</span></td>
                                            <td class="text-end fw-bold text-success">${formatCurrency(pay.advanceamount)}</td>
                                            <td class="text-center">
                                                <span class="${isVerified ? 'badge-verified' : 'badge-unverified'}">
                                                    <i class="fa ${isVerified ? 'fa-check-circle' : 'fa-clock'}"></i>
                                                    ${isVerified ? 'Verified' : 'Pending'}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                ${hasReceipt ? `
                                                    <button type="button" class="btn btn-outline-primary btn-xs py-0 px-1.5 btn-view-receipt" data-path="${escapeHtml(pay.receiptpath)}" title="View Receipt">
                                                        <i class="fa fa-file-invoice"></i>
                                                    </button>
                                                ` : `<span class="text-muted small">-</span>`}
                                            </td>
                                        </tr>
                                    `;
                                }).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
            } else {
                paymentsDetailsHtml = `<div class="p-2 bg-light rounded text-center text-muted small fst-italic">No advance payments recorded against this order.</div>`;
            }

            let mainRowHtml = `
                <tr class="order-main-row" data-order-idx="${idx}" data-psono="${escapeHtml(psono)}" data-status="${status.toLowerCase()}">
                    <td class="text-center">
                        <button type="button" class="btn-expand-row" title="Toggle Details">
                            <i class="fa fa-chevron-right"></i>
                        </button>
                    </td>
                    <td>
                        <div class="fw-bold font-monospace text-primary fs-6">${escapeHtml(psono)}</div>
                        ${sono ? `<span class="badge bg-secondary font-monospace" style="font-size: 10px;">SO: ${escapeHtml(sono)}</span>` : ''}
                    </td>
                    <td class="text-nowrap" style="font-size: 11.5px;">
                        <div><i class="fa fa-calendar-alt text-primary me-1"></i><b>Booked:</b> ${escapeHtml(order.booking_date)}</div>
                        <div class="text-secondary"><i class="fa fa-truck text-success me-1"></i><b>Delivery:</b> ${escapeHtml(order.delivery_date)}</div>
                    </td>
                    <td>${cropsSummary}</td>
                    <td class="text-end fw-bold text-dark fs-6">${formatCurrency(order.total_amount)}</td>
                    <td class="text-end">${advancePaidHtml}</td>
                    <td class="text-end rest-amount-cell ${order.balance_due > 0 ? 'rest-due-positive' : 'rest-due-zero'}">
                        ${restAmountHtml}
                    </td>
                    <td class="text-center">
                        <span class="badge ${statusBadgeClass} text-uppercase" style="font-size: 10.5px;">${escapeHtml(status)}</span>
                    </td>
                </tr>

                <!-- Expandable Sub-Row -->
                <tr class="order-detail-subrow" id="orderDetailSubrow_${idx}" data-psono="${escapeHtml(psono)}" style="display: none !important;">
                    <td colspan="8" class="p-3">
                        <div class="row g-3">
                            <!-- Sub Panel 1: Advance Payments Breakdown -->
                            <div class="col-12 col-lg-5">
                                <div class="order-subpanel-card">
                                    <div class="order-subpanel-title text-success justify-content-between">
                                        <span><i class="fa fa-money-bill-wave"></i> Advance Payments Recorded</span>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">${formatCurrency(order.advance_paid)} Received</span>
                                    </div>
                                    ${paymentsDetailsHtml}
                                </div>
                            </div>

                            <!-- Sub Panel 2: Ordered Items & Varieties -->
                            <div class="col-12 col-lg-7">
                                <div class="order-subpanel-card">
                                    <div class="order-subpanel-title text-primary">
                                        <i class="fa fa-seedling"></i> Crops & Rate Breakdown
                                    </div>
                                    ${itemsDetailsHtml}
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            `;

            tbody.append(mainRowHtml);
        });
    }

    // Toggle Expandable Detail Row when row or dropdown button is clicked
    $(document).on("click", ".order-main-row", function (e) {
        // If clicking on an action button, receipt button, or link, do not toggle
        if ($(e.target).closest(".btn-record-payment, .btn-view-receipt, a, button:not(.btn-expand-row)").length > 0) return;

        let idx = $(this).data("order-idx");
        let $subrow = $(`#orderDetailSubrow_${idx}`);
        let isOpen = $subrow.hasClass("is-open");

        if (isOpen) {
            $subrow.removeClass("is-open").attr("style", "display: none !important;");
            $(this).removeClass("is-expanded");
        } else {
            $subrow.addClass("is-open").attr("style", "display: table-row !important;");
            $(this).addClass("is-expanded");
        }
    });

    /**
     * Render Complete Lifetime Payments Ledger Table
     */
    function renderAllPaymentsLedger(payments) {
        let tbody = $("#ledgerTableBody");
        tbody.empty();

        if (!payments || payments.length === 0) {
            tbody.html(`<tr><td colspan="8" class="text-center py-4 text-muted">No advance payments recorded for this client.</td></tr>`);
            return;
        }

        let totalLedger = 0;
        payments.forEach((p, idx) => {
            let amt = Number(p.advanceamount || 0);
            totalLedger += amt;
            let isVer = (p.verification || '').toLowerCase() === 'verified';

            tbody.append(`
                <tr>
                    <td class="text-center text-muted">${idx + 1}</td>
                    <td class="fw-semibold text-dark">${escapeHtml(p.formatted_payment_date || '-')}</td>
                    <td class="font-monospace fw-bold text-primary">${escapeHtml(p.invoiceno || 'Unallocated')}</td>
                    <td><span class="badge bg-light text-dark border">${escapeHtml(p.sourceofpayment || p.paymentmode || 'Advance')}</span></td>
                    <td class="font-monospace small">${escapeHtml(p.utrnumber || p.paymentref_no || '-')}</td>
                    <td class="text-end fw-bold text-success">${formatCurrency(amt)}</td>
                    <td class="text-center">
                        <span class="${isVer ? 'badge-verified' : 'badge-unverified'}">
                            <i class="fa ${isVer ? 'fa-check-circle' : 'fa-clock'}"></i>
                            ${isVer ? 'Verified' : 'Pending'}
                        </span>
                    </td>
                    <td class="text-muted small">${escapeHtml(p.remarks || p.purposeofpayment || '-')}</td>
                </tr>
            `);
        });

        $("#ledgerTotalDisplay").text(formatCurrency(totalLedger));
    }

    // View Receipt modal click handler
    $(document).on("click", ".btn-view-receipt", function () {
        let path = $(this).data("path");
        if (!path) return;

        let fullPath = path.startsWith('http') || path.startsWith('/') ? path : `../${path}`;
        $("#receiptViewerImage").attr("src", fullPath);
        $("#receiptViewerModal").modal("show");
    });

    // Filter Buttons (All, Confirmed, Pending)
    $(".btn-order-filter").on("click", function () {
        $(".btn-order-filter").removeClass("active btn-primary").addClass("btn-outline-secondary");
        $(this).addClass("active btn-primary").removeClass("btn-outline-secondary");

        let filter = $(this).data("filter");
        activeFilter = filter;

        $(".order-detail-subrow").hide();
        $(".order-main-row").removeClass("is-expanded");

        if (filter === "all") {
            $(".order-main-row").show();
        } else {
            $(".order-main-row").each(function () {
                let status = $(this).data("status") || '';
                if (status.includes(filter)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }
    });

    // Print Client Statement
    $("#btnPrintClientReport").on("click", function () {
        window.print();
    });

    // Cache for available open stock batches & products
    let cachedOpenStockBatches = null;
    let cachedAllProducts = null;

    function fetchOpenStockData(callback) {
        if (cachedOpenStockBatches && cachedAllProducts) {
            if (callback) callback(cachedOpenStockBatches, cachedAllProducts);
            return;
        }
        $.ajax({
            url: "modal/order_tracking_modal.php?action=GetOpenStockBatches",
            type: "POST",
            dataType: "json",
            data: { projectid: $("body").data("projectid") || 0 },
            success: function (res) {
                if (res.status === "success") {
                    cachedOpenStockBatches = res.batches || [];
                    cachedAllProducts = res.products || [];
                    if (callback) callback(cachedOpenStockBatches, cachedAllProducts);
                } else {
                    if (callback) callback([], []);
                }
            },
            error: function () {
                if (callback) callback([], []);
            }
        });
    }

    // Prefetch open stock data in background on ready
    fetchOpenStockData();

    // Edit Order Quantity Click Handler
    $(document).on("click", ".btn-edit-order-qty", function (e) {
        e.preventDefault();
        e.stopPropagation();

        let itemId = $(this).data("item-id");
        let psono = $(this).data("psono");
        let productId = $(this).data("product-id");
        let productName = $(this).data("product-name");
        let currentQty = parseInt($(this).data("quantity")) || 0;
        let rate = parseFloat($(this).data("rate")) || 0;

        fetchOpenStockData(function (batches, allProducts) {
            openEditQuantityModal(itemId, psono, productId, productName, currentQty, rate, null, '', 'only_edit', batches, allProducts);
        });
    });

    /**
     * Interactive Edit Order Quantity Modal with Rest Quantity & Replacement Handling
     */
    function openEditQuantityModal(itemId, psono, productId, productName, currentQty, rate, initNewQty = null, initRemark = '', initMode = 'only_edit', batches = null, allProducts = null) {
        batches = batches || cachedOpenStockBatches || [];
        allProducts = allProducts || cachedAllProducts || [];

        let currentAmount = (currentQty * rate);
        let startQty = (initNewQty !== null && initNewQty !== undefined) ? parseInt(initNewQty) : currentQty;
        let startDiff = currentQty - startQty;
        let isReduction = startDiff > 0;

        // Group products: with open stock first, then others
        let stockProducts = allProducts.filter(p => (p.total_available_qty || 0) > 0);
        let otherProducts = allProducts.filter(p => (p.total_available_qty || 0) <= 0);

        let productOptionsHtml = `<option value="">-- Choose / Search Replacement Product (Select2) --</option>`;
        if (stockProducts.length > 0) {
            productOptionsHtml += `<optgroup label="🟢 Available in Open Stock (${stockProducts.length} Products)">`;
            stockProducts.forEach(p => {
                productOptionsHtml += `
                    <option value="${escapeHtml(p.product_id)}" 
                        data-name="${escapeHtml(p.product_name)}" 
                        data-rate="${p.default_rate}" 
                        data-avail="${p.total_available_qty}"
                        data-deliv="${escapeHtml(p.earliest_delivery_date || '')}"
                        data-formatted-deliv="${escapeHtml(p.formatted_earliest_delivery_date || '')}">
                        ${escapeHtml(p.product_name)} [Avail: ${formatNumber(p.total_available_qty)} Plants in ${p.batch_count} Batches]
                    </option>
                `;
            });
            productOptionsHtml += `</optgroup>`;
        }
        if (otherProducts.length > 0) {
            productOptionsHtml += `<optgroup label="🌱 Other Products (Product Master)">`;
            otherProducts.forEach(p => {
                productOptionsHtml += `
                    <option value="${escapeHtml(p.product_id)}" 
                        data-name="${escapeHtml(p.product_name)}" 
                        data-rate="${p.default_rate}" 
                        data-avail="0"
                        data-deliv=""
                        data-formatted-deliv="-">
                        ${escapeHtml(p.product_name)} [Rate: ₹${p.default_rate.toFixed(2)}]
                    </option>
                `;
            });
            productOptionsHtml += `</optgroup>`;
        }

        Swal.fire({
            title: `
                <div class="d-flex align-items-center justify-content-between w-100 flex-wrap gap-2 text-start">
                    <div class="fs-5 text-dark fw-bold d-flex align-items-center gap-2">
                        <i class="fa fa-pencil-alt text-primary"></i>
                        <span>Edit Order Quantity & Product Allocation</span>
                    </div>
                    <span class="badge bg-light text-primary border border-primary-subtle font-monospace px-3 py-1.5 fs-6">
                        Order: <b>${escapeHtml(psono)}</b>
                    </span>
                </div>
            `,
            width: '90%',
            customClass: {
                popup: 'swal-modal-90 text-start',
                confirmButton: 'btn btn-success fw-bold px-4 py-2',
                cancelButton: 'btn btn-secondary fw-semibold px-3 py-2'
            },
            html: `
                <div class="text-start" style="font-size: 13px;">
                    <!-- Original Product Info Strip -->
                    <div class="mb-3 p-3 bg-light rounded-3 border d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <span class="text-secondary small text-uppercase fw-bold d-block" style="font-size: 11px;">Current Crop & Variety</span>
                            <div class="fw-bold text-dark fs-6">${escapeHtml(productName)}</div>
                            <div class="small text-muted font-monospace mt-0.5">
                                Product ID: <span class="fw-semibold text-dark">${escapeHtml(productId)}</span> | Target Order: <span class="text-primary fw-semibold">${escapeHtml(psono)}</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="text-end">
                                <span class="text-secondary small d-block">Current Order Qty</span>
                                <span class="fs-6 fw-bold text-dark">${formatNumber(currentQty)} Plants</span>
                            </div>
                            <div class="text-end">
                                <span class="text-secondary small d-block">Rate / Plant</span>
                                <span class="fs-6 fw-bold text-dark">₹${rate.toFixed(2)}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Quantity Revision Row -->
                    <div class="row g-3 mb-3 align-items-center">
                        <div class="col-12 col-md-6">
                            <label for="swalEditNewQty" class="form-label fw-bold text-dark small mb-1">
                                New Order Quantity (Plants) <span class="text-danger">*</span>
                            </label>
                            <input type="number" id="swalEditNewQty" class="form-control form-control-lg text-end fw-bold text-primary" min="1" step="1" value="${startQty}">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label text-muted small mb-1">Recalculated Amount for this Item</label>
                            <div class="form-control form-control-lg bg-light-subtle fw-bold text-success text-end d-flex align-items-center justify-content-between">
                                <span class="text-secondary small fw-normal">Total:</span>
                                <span id="swalRecalcAmountDisplay">₹${currentAmount.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Dynamic Rest of Quantity Question Section -->
                    <div id="swalRestQtySection" class="p-3 mb-3 rounded-3 border" style="background-color: #fffdf5; border: 1.5px solid #fde68a !important; display: ${isReduction ? 'block' : 'none'}; text-align: left;">
                        <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                            <span class="fw-bold text-dark d-flex align-items-center" style="font-size: 13.5px; gap: 6px;">
                                <i class="fa fa-question-circle text-warning fs-5"></i>
                                <span>Question for Rest of Quantity:</span>
                            </span>
                            <span class="badge bg-warning text-dark fw-bold px-3 py-1.5" id="swalRestQtyCount" style="font-size: 12px;">
                                ${formatNumber(startDiff)} Plants
                            </span>
                        </div>
                        <div class="text-secondary small mb-1" style="font-size: 12px; line-height: 1.4;">
                            What would you like to do with the remaining quantity of this order?
                        </div>

                        <!-- Action Choice Options (Redesigned Tile Cards with Top Margin) -->
                        <div class="rest-action-cards-container">
                            <!-- Option 1: Only edit quantity -->
                            <label class="rest-option-tile ${initMode === 'only_edit' ? 'active' : ''}" for="actionOnlyEdit">
                                <input type="radio" name="swal_rest_action" id="actionOnlyEdit" value="only_edit" ${initMode === 'only_edit' ? 'checked' : ''} class="rest-radio-input" style="position: static !important; width: 18px !important; height: 18px !important; min-width: 18px !important; max-width: 18px !important; margin: 2px 0 0 0 !important; cursor: pointer; flex-shrink: 0; accent-color: #0d9488;">
                                <div class="rest-option-content">
                                    <div class="rest-option-title">Only edit the quantity</div>
                                    <div class="rest-option-desc">
                                        Reduce order to <span class="badge bg-secondary fw-bold mx-1" id="swalNewQtyPill">${formatNumber(startQty)}</span> plants (Permitted if open stock allocation; blocked if dedicated client batch)
                                    </div>
                                </div>
                            </label>

                            <!-- Option 2: Add another product -->
                            <label class="rest-option-tile ${initMode === 'add_product' ? 'active' : ''}" for="actionAddProduct">
                                <input type="radio" name="swal_rest_action" id="actionAddProduct" value="add_product" ${initMode === 'add_product' ? 'checked' : ''} class="rest-radio-input" style="position: static !important; width: 18px !important; height: 18px !important; min-width: 18px !important; max-width: 18px !important; margin: 2px 0 0 0 !important; cursor: pointer; flex-shrink: 0; accent-color: #0d9488;">
                                <div class="rest-option-content">
                                    <div class="rest-option-title">Add another product for remaining quantity</div>
                                    <div class="rest-option-desc">
                                        Allocate <span class="badge bg-success fw-bold mx-1" id="swalRestQtyPill">${formatNumber(startDiff)}</span> plants from production open stock
                                    </div>
                                </div>
                            </label>
                        </div>

                        <!-- Sub-Form: Add Replacement Product from Production Open Stock (Redesigned with Select2 & Tabular Stock Details) -->
                        <div id="swalReplacementContainer" class="p-3 bg-white rounded-3 border mt-3" style="border: 1.5px solid #cbd5e1 !important; box-shadow: 0 4px 12px rgba(0,0,0,0.04); display: ${initMode === 'add_product' ? 'block' : 'none'};">
                            
                            <!-- Sub-form Header Strip -->
                            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom flex-wrap gap-2">
                                <div class="fw-bold text-primary fs-6 d-flex align-items-center gap-2">
                                    <i class="fa fa-boxes text-warning fs-5"></i>
                                    <span>Allocate Replacement Product from Production Open Stock</span>
                                </div>
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning fw-bold px-2.5 py-1">
                                    Required Allocation: <span id="swalReplReqPill">${formatNumber(startDiff)}</span> Plants
                                </span>
                            </div>

                            <!-- Row 1: Select Product Name with Select2 -->
                            <div class="row g-3 align-items-center mb-3">
                                <div class="col-12 col-md-8">
                                    <label for="swalReplacementProduct" class="form-label fw-bold text-dark small mb-1">
                                        <i class="fa fa-seedling text-success me-1"></i> Choose Product Name (Select2 Search) <span class="text-danger">*</span>
                                    </label>
                                    <select id="swalReplacementProduct" class="form-select select2-replacement-product" style="width: 100%;">
                                        ${productOptionsHtml}
                                    </select>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label text-muted small mb-1">Target PSONO Order</label>
                                    <div class="form-control form-control-sm bg-light fw-bold font-monospace text-primary text-center">
                                        ${escapeHtml(psono)}
                                    </div>
                                </div>
                            </div>

                            <!-- Row 2: Selected Product Open Stock Summary KPI Bar -->
                            <div id="swalProductStockSummary" class="p-3 mb-3 rounded-3 border" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-color: #cbd5e1 !important; display: none;">
                                <div class="row g-2 text-center align-items-center">
                                    <div class="col-6 col-md-3">
                                        <div class="text-uppercase text-secondary" style="font-size: 10.5px; font-weight: 700; letter-spacing: 0.5px;">Open Stock Total</div>
                                        <div class="fs-5 fw-bold text-dark mt-0.5" id="sumOpenTotal">0 Plants</div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="text-uppercase text-secondary" style="font-size: 10.5px; font-weight: 700; letter-spacing: 0.5px;">Consumed Qty</div>
                                        <div class="fs-5 fw-bold text-danger mt-0.5" id="sumOpenConsumed">0 Plants</div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="text-uppercase text-secondary" style="font-size: 10.5px; font-weight: 700; letter-spacing: 0.5px;">Balance Available</div>
                                        <div class="fs-5 fw-bold text-success mt-0.5" id="sumOpenBalance">0 Plants</div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="text-uppercase text-secondary" style="font-size: 10.5px; font-weight: 700; letter-spacing: 0.5px;">Delivery Date for PSONO</div>
                                        <div class="fs-6 fw-bold text-primary mt-0.5" id="sumOpenDelivery">-</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Row 3: Open Stock Entry Details in Tabular Format -->
                            <div class="mb-2 d-flex align-items-center justify-content-between">
                                <span class="fw-bold text-secondary small text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">
                                    <i class="fa fa-list-check text-primary me-1"></i> Open Stock Production Orders (Select Checkbox to Consume for ${escapeHtml(psono)}):
                                </span>
                                <span class="text-muted small" style="font-size: 11.5px;" id="swalTableBatchCount"></span>
                            </div>

                            <div id="swalBatchesTableWrapper" class="table-responsive rounded border mb-3" style="max-height: 250px; overflow-y: auto; display: none; background: #ffffff;">
                                <table class="table table-sm table-hover align-middle mb-0 text-nowrap" id="swalOpenStockTable">
                                    <thead class="sticky-top" style="z-index: 2;">
                                        <tr class="text-uppercase text-secondary" style="font-size: 11px;">
                                            <th class="text-center" style="width: 50px;">Select</th>
                                            <th>Production Order No</th>
                                            <th class="text-end">Total Stock Qty</th>
                                            <th class="text-end">Consumed Qty</th>
                                            <th class="text-end">Balance Available</th>
                                            <th class="text-center">Expected Delivery Date</th>
                                            <th class="text-center">Sowing Status</th>
                                            <th class="text-end">Rate / Plant</th>
                                            <th class="text-center" style="width: 150px;">Allocated for ${escapeHtml(psono)}</th>
                                        </tr>
                                    </thead>
                                    <tbody id="swalOpenStockTableBody" style="font-size: 12px;">
                                        <!-- Dynamically populated via Select2 change event -->
                                    </tbody>
                                </table>
                            </div>

                            <div id="swalNoBatchesAlert" class="alert alert-warning py-2.5 px-3 small rounded-3 mb-3 d-flex align-items-center gap-2" style="display: none;">
                                <i class="fa fa-info-circle fs-5 text-warning"></i>
                                <div>No open stock production orders with positive available balance found for this product. You can still proceed if placing a fresh booking.</div>
                            </div>

                            <!-- Row 4: Selected Batch Parameters & Confirmation -->
                            <div class="p-3 rounded-3 border bg-light-subtle">
                                <div class="row g-3 align-items-center">
                                    <div class="col-12 col-md-3">
                                        <label class="form-label text-dark fw-bold small mb-1">
                                            Selected Batch No <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" id="swalSelectedBatchNo" class="form-control form-control-sm bg-white fw-bold font-monospace text-primary" readonly placeholder="Click checkbox above">
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <label class="form-label text-dark fw-bold small mb-1">
                                            Rate / Plant (₹) <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" id="swalReplacementRate" class="form-control form-control-sm text-end fw-bold" step="0.05" value="0.00">
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label text-dark fw-bold small mb-1">
                                            Delivery Date against PSONO
                                        </label>
                                        <input type="date" id="swalReplacementDelivery" class="form-control form-control-sm bg-white">
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <label class="form-label text-dark fw-bold small mb-1">
                                            Allocated Qty
                                        </label>
                                        <input type="text" id="swalReplacementQty" class="form-control form-control-sm bg-white fw-bold text-end" readonly value="${formatNumber(startDiff)}">
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <label class="form-label text-dark fw-bold small mb-1">
                                            New Item Total
                                        </label>
                                        <div class="form-control form-control-sm bg-white fw-bold text-success text-end" id="swalReplacementTotal">₹0.00</div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Amended Reason / Remark (Mandatory) -->
                    <div class="mb-1">
                        <label for="swalEditRemark" class="form-label fw-bold text-dark small mb-1">
                            Amended Reason / Remark <span class="text-danger">*</span>
                        </label>
                        <textarea id="swalEditRemark" class="form-control form-control-sm" rows="2" placeholder="e.g., Client requested revision in variety / order quantity (Required)">${escapeHtml(initRemark)}</textarea>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: '<i class="fa fa-save me-1"></i> Update Quantity & Save',
            confirmButtonColor: '#10b981',
            cancelButtonText: 'Cancel',
            cancelButtonColor: '#64748b',
            focusConfirm: false,
            didOpen: () => {
                const inputQty = document.getElementById('swalEditNewQty');
                const displayAmt = document.getElementById('swalRecalcAmountDisplay');
                const restSection = document.getElementById('swalRestQtySection');
                const restCount = document.getElementById('swalRestQtyCount');
                const newQtyPill = document.getElementById('swalNewQtyPill');
                const restQtyPill = document.getElementById('swalRestQtyPill');
                const replReqPill = document.getElementById('swalReplReqPill');
                const replContainer = document.getElementById('swalReplacementContainer');
                const replQty = document.getElementById('swalReplacementQty');
                const replRate = document.getElementById('swalReplacementRate');
                const replTotal = document.getElementById('swalReplacementTotal');
                const replDeliv = document.getElementById('swalReplacementDelivery');
                const selectedBatchInput = document.getElementById('swalSelectedBatchNo');
                const $prodSelect = $('#swalReplacementProduct');

                // Initialize Select2 inside SweetAlert modal
                $prodSelect.select2({
                    dropdownParent: $(Swal.getPopup()),
                    width: '100%',
                    placeholder: '-- Choose / Search Replacement Product --',
                    allowClear: true
                });

                function updateCalculations() {
                    let q = parseInt(inputQty.value) || 0;
                    let amt = q * rate;
                    displayAmt.textContent = '₹' + amt.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});

                    let diff = currentQty - q;
                    if (diff > 0) {
                        restSection.style.display = 'block';
                        restCount.textContent = formatNumber(diff) + ' Plants';
                        newQtyPill.textContent = formatNumber(q);
                        restQtyPill.textContent = formatNumber(diff);
                        if (replReqPill) replReqPill.textContent = formatNumber(diff);
                        if (replQty) replQty.value = formatNumber(diff);

                        let rRate = parseFloat(replRate.value) || 0;
                        let rTot = diff * rRate;
                        if (replTotal) replTotal.textContent = '₹' + rTot.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});

                        // Also update allocated quantity pill in open stock table rows
                        $('.batch-alloc-pill').text(formatNumber(diff) + ' Plants');
                    } else {
                        restSection.style.display = 'none';
                    }
                }

                inputQty.addEventListener('input', updateCalculations);

                // Radio toggle & active card highlight
                $('input[name="swal_rest_action"]').on('change', function () {
                    $('.rest-option-tile').removeClass('active');
                    $(this).closest('.rest-option-tile').addClass('active');

                    if (this.value === 'add_product') {
                        $(replContainer).slideDown(150, function () {
                            $prodSelect.select2({
                                dropdownParent: $(Swal.getPopup()),
                                width: '100%'
                            });
                        });
                    } else {
                        $(replContainer).slideUp(150);
                    }
                });

                // Function to render open stock batches table for a selected product
                function renderBatchesForProduct(selectedPid) {
                    if (!selectedPid) {
                        $('#swalProductStockSummary').hide();
                        $('#swalBatchesTableWrapper').hide();
                        $('#swalNoBatchesAlert').hide();
                        $('#swalTableBatchCount').text('');
                        selectedBatchInput.value = '';
                        replRate.value = '0.00';
                        replDeliv.value = '';
                        updateCalculations();
                        return;
                    }

                    let pInfo = allProducts.find(p => String(p.product_id) === String(selectedPid));
                    let prodBatches = batches.filter(b => String(b.product_id) === String(selectedPid));

                    // 1. Update Open Stock Summary KPI Bar
                    let totOpen = pInfo ? (pInfo.total_open_qty || 0) : 0;
                    let totConsumed = pInfo ? (pInfo.total_consumed_qty || 0) : 0;
                    let totBalance = pInfo ? (pInfo.total_available_qty || 0) : 0;

                    $('#sumOpenTotal').text(formatNumber(totOpen) + ' Plants');
                    $('#sumOpenConsumed').text(formatNumber(totConsumed) + ' Plants');
                    $('#sumOpenBalance').text(formatNumber(totBalance) + ' Plants');
                    $('#sumOpenDelivery').text(pInfo && pInfo.formatted_earliest_delivery_date ? pInfo.formatted_earliest_delivery_date : '-');
                    $('#swalProductStockSummary').fadeIn(150);

                    // 2. Render Open Stock Table
                    let reqQty = parseInt(inputQty.value) ? (currentQty - parseInt(inputQty.value)) : startDiff;
                    if (reqQty <= 0) reqQty = startDiff;

                    if (prodBatches.length > 0) {
                        $('#swalNoBatchesAlert').hide();
                        $('#swalTableBatchCount').text(prodBatches.length + ' batch(es) available');

                        let tbodyHtml = '';
                        prodBatches.forEach((b, idx) => {
                            let isFirst = (idx === 0);
                            let rowClass = isFirst ? 'selected-batch-row' : '';
                            let checkedAttr = isFirst ? 'checked' : '';

                            tbodyHtml += `
                                <tr class="${rowClass}" 
                                    data-batch-no="${escapeHtml(b.stockorder_no)}" 
                                    data-rate="${b.default_rate}" 
                                    data-deliv="${escapeHtml(b.delivery_date)}" 
                                    data-formatted-deliv="${escapeHtml(b.formatted_delivery_date)}" 
                                    data-avail="${b.available_qty}" 
                                    style="cursor: pointer;">
                                    <td class="text-center">
                                        <input type="checkbox" class="openstock-batch-chk" name="swal_batch_chk_${b.id}" value="${escapeHtml(b.stockorder_no)}" ${checkedAttr} style="width: 18px !important; height: 18px !important; cursor: pointer; position: static !important; margin: 0 !important; display: inline-block !important;">
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border font-monospace fw-bold">${escapeHtml(b.stockorder_no)}</span>
                                    </td>
                                    <td class="text-end fw-bold text-dark">${formatNumber(b.total_qty)}</td>
                                    <td class="text-end text-danger fw-semibold">${formatNumber(b.consumed_qty)}</td>
                                    <td class="text-end">
                                        <span class="badge bg-success-subtle text-success fw-bold px-2 py-1">${formatNumber(b.available_qty)}</span>
                                    </td>
                                    <td class="text-center fw-semibold text-dark">
                                        <i class="fa fa-calendar-alt text-primary me-1"></i>${escapeHtml(b.formatted_delivery_date)}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">${escapeHtml(b.sowingstatus || b.status)}</span>
                                    </td>
                                    <td class="text-end fw-bold">₹${b.default_rate.toFixed(2)}</td>
                                    <td class="text-center">
                                        <span class="badge bg-primary px-2.5 py-1 fw-bold font-monospace batch-alloc-pill">${formatNumber(reqQty)} Plants</span>
                                    </td>
                                </tr>
                            `;
                        });
                        $('#swalOpenStockTableBody').html(tbodyHtml);
                        $('#swalBatchesTableWrapper').slideDown(150);

                        // Select first batch by default
                        let firstBatch = prodBatches[0];
                        selectBatchDetails(firstBatch.stockorder_no, firstBatch.default_rate, firstBatch.delivery_date, firstBatch.formatted_delivery_date);
                    } else {
                        $('#swalBatchesTableWrapper').hide();
                        $('#swalTableBatchCount').text('0 batches');
                        $('#swalNoBatchesAlert').slideDown(150);

                        let defRate = pInfo ? (pInfo.default_rate || 0) : 0;
                        selectBatchDetails('OS-AUTO-' + selectedPid, defRate, '', '-');
                    }
                }

                function selectBatchDetails(batchNo, batchRate, deliveryDate, formattedDelivery) {
                    selectedBatchInput.value = batchNo;
                    if (batchRate > 0) replRate.value = batchRate.toFixed(2);
                    if (deliveryDate) replDeliv.value = deliveryDate;
                    if (formattedDelivery && formattedDelivery !== '-') $('#sumOpenDelivery').text(formattedDelivery);
                    updateCalculations();
                }

                // Product change handler
                $prodSelect.on('change', function () {
                    renderBatchesForProduct(this.value);
                });

                // Table row / checkbox click handler
                $('#swalOpenStockTableBody').on('click', 'tr', function (e) {
                    let $row = $(this);
                    let $chk = $row.find('.openstock-batch-chk');

                    // Uncheck all other checkboxes and remove selection highlight
                    $('.openstock-batch-chk').not($chk).prop('checked', false);
                    $('#swalOpenStockTableBody tr').removeClass('selected-batch-row');

                    $chk.prop('checked', true);
                    $row.addClass('selected-batch-row');

                    let bNo = $row.data('batch-no');
                    let bRate = parseFloat($row.data('rate')) || 0;
                    let bDeliv = $row.data('deliv') || '';
                    let bFormattedDeliv = $row.data('formatted-deliv') || '';

                    selectBatchDetails(bNo, bRate, bDeliv, bFormattedDeliv);
                });

                // Checkbox direct change
                $('#swalOpenStockTableBody').on('change', '.openstock-batch-chk', function (e) {
                    e.stopPropagation();
                    let $chk = $(this);
                    let $row = $chk.closest('tr');

                    $('.openstock-batch-chk').not($chk).prop('checked', false);
                    $('#swalOpenStockTableBody tr').removeClass('selected-batch-row');

                    if ($chk.is(':checked')) {
                        $row.addClass('selected-batch-row');
                        let bNo = $row.data('batch-no');
                        let bRate = parseFloat($row.data('rate')) || 0;
                        let bDeliv = $row.data('deliv') || '';
                        let bFormattedDeliv = $row.data('formatted-deliv') || '';
                        selectBatchDetails(bNo, bRate, bDeliv, bFormattedDeliv);
                    } else {
                        // Keep at least this one checked
                        $chk.prop('checked', true);
                        $row.addClass('selected-batch-row');
                    }
                });

                $(replRate).on('input', updateCalculations);

                // Initial calculation
                updateCalculations();
                inputQty.select();
            },
            preConfirm: () => {
                const newQtyVal = document.getElementById('swalEditNewQty').value;
                const remarkVal = (document.getElementById('swalEditRemark').value || '').trim();
                const newQty = parseInt(newQtyVal, 10);

                if (isNaN(newQty) || newQty <= 0) {
                    Swal.showValidationMessage('Please enter a valid positive quantity (greater than 0).');
                    return false;
                }
                if (newQty === currentQty) {
                    Swal.showValidationMessage('The new quantity is identical to current quantity (' + formatNumber(currentQty) + ').');
                    return false;
                }
                if (!remarkVal) {
                    Swal.showValidationMessage('Amended reason / remark is required for any edit against order and product.');
                    return false;
                }

                let diff = currentQty - newQty;
                let payload = {
                    item_id: itemId,
                    psono: psono,
                    product_id: productId,
                    new_quantity: newQty,
                    remark: remarkVal,
                    action_mode: 'only_edit'
                };

                if (diff > 0) {
                    let restAction = $('input[name="swal_rest_action"]:checked').val() || 'only_edit';
                    payload.action_mode = restAction;

                    if (restAction === 'add_product') {
                        const replProdId = $('#swalReplacementProduct').val();
                        const $selectedProdOpt = $('#swalReplacementProduct').find('option:selected');
                        const replProdName = $selectedProdOpt.data('name') || $selectedProdOpt.text().trim();
                        const replStockOrderNo = ($('#swalSelectedBatchNo').val() || '').trim();
                        const replRateVal = parseFloat(document.getElementById('swalReplacementRate').value) || 0;
                        const replDelivVal = document.getElementById('swalReplacementDelivery').value;

                        if (!replProdId) {
                            Swal.showValidationMessage('Please search and select a replacement product name.');
                            return false;
                        }
                        if (!replStockOrderNo) {
                            Swal.showValidationMessage('Please select a production order batch from the open stock table.');
                            return false;
                        }
                        if (replRateVal <= 0) {
                            Swal.showValidationMessage('Please enter a valid rate per plant (greater than ₹0.00).');
                            return false;
                        }

                        payload.new_product_id = replProdId;
                        payload.new_product_name = replProdName;
                        payload.new_stockorder_no = replStockOrderNo;
                        payload.new_product_rate = replRateVal;
                        payload.new_product_qty = diff;
                        payload.new_delivery_date = replDelivVal;
                    }
                }

                return payload;
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                submitOrderItemQtyUpdate(result.value, {
                    itemId: itemId,
                    psono: psono,
                    productId: productId,
                    productName: productName,
                    currentQty: currentQty,
                    rate: rate,
                    batches: batches,
                    allProducts: allProducts
                });
            }
        });
    }

    /**
     * Submit Order Item Quantity Update / Replacement AJAX
     */
    function submitOrderItemQtyUpdate(payload, meta) {
        let loginname = $(".container-fluid").data("loginname") || "Admin";
        payload.loginname = loginname;

        Swal.fire({
            title: "Updating Order...",
            text: "Please wait while order items, totals, and production records are updated.",
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: "modal/order_tracking_modal.php?action=UpdateItemOrderQty",
            type: "POST",
            dataType: "json",
            data: payload,
            success: function (res) {
                // Scenario: Dedicated Production Order generated for client -> Show Warning Dialog!
                if (res.status === "warning_dedicated_production") {
                    Swal.fire({
                        icon: "warning",
                        title: `<div class="fs-5 text-warning fw-bold"><i class="fa fa-exclamation-triangle me-2"></i>Dedicated Production Order Generated</div>`,
                        html: `
                            <div class="text-start" style="font-size: 13px;">
                                <div class="alert alert-warning border mb-3 p-2.5">
                                    <div class="fw-bold text-dark"><i class="fa fa-industry me-1"></i> Client Production Batch: <span class="font-monospace text-primary">${escapeHtml(res.stockorder_no)}</span></div>
                                    <div class="small text-muted mt-1">Quantity in Production: <b>${formatNumber(res.quantity)} Plants</b> for Order <b>${escapeHtml(payload.psono)}</b></div>
                                </div>
                                <div class="p-2.5 mb-3 bg-light rounded border text-danger fw-bold" style="font-size: 12.5px;">
                                    <i class="fa fa-info-circle me-1"></i> Notice: Please dispatch all order quantity (${formatNumber(res.current_quantity)} plants).
                                </div>
                                <p class="text-secondary mb-2" style="font-size: 12.5px;">
                                    A dedicated production order has already been generated specifically for this client against <b>${escapeHtml(payload.psono)}</b> for <b>${escapeHtml(meta.productName)}</b>. You cannot simply reduce the quantity.
                                </p>
                                <p class="small text-muted mb-0">
                                    To proceed, please choose to <b>"Add another product"</b> to allocate the remaining <b>${formatNumber(res.rest_quantity)}</b> plants from available open stock production batches.
                                </p>
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: '<i class="fa fa-plus-circle me-1"></i> Add Replacement Product',
                        confirmButtonColor: '#3b82f6',
                        cancelButtonText: 'Cancel / Keep All Qty',
                        cancelButtonColor: '#64748b'
                    }).then((warnRes) => {
                        if (warnRes.isConfirmed) {
                            openEditQuantityModal(
                                meta.itemId, 
                                meta.psono, 
                                meta.productId, 
                                meta.productName, 
                                meta.currentQty, 
                                meta.rate, 
                                payload.new_quantity, 
                                payload.remark, 
                                'add_product', 
                                meta.batches, 
                                meta.allProducts
                            );
                        }
                    });
                    return;
                }

                if (res.status === "success") {
                    Swal.fire({
                        icon: "success",
                        title: "Quantity Updated!",
                        text: res.message || "Order quantity and allocations updated successfully.",
                        timer: 2500,
                        showConfirmButton: false
                    });

                    // Reload the client history with current search key and auto-expand this order
                    let searchVal = $("#clientSearchInput").val().trim() || lastLoadedSearchKey || payload.psono;
                    loadClientHistory(searchVal, payload.psono);
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Update Failed",
                        text: res.message || "Could not update order quantity."
                    });
                }
            },
            error: function (xhr, status, error) {
                console.error("Update quantity error:", status, error, xhr.responseText);
                let errMsg = "Server error while updating order quantity.";
                try {
                    let errObj = JSON.parse(xhr.responseText);
                    if (errObj && errObj.message) errMsg = errObj.message;
                } catch (e) {}
                Swal.fire("Error", errMsg, "error");
            }
        });
    }

});
