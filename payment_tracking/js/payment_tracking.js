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
            url: "modal/payment_tracking_modal.php",
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

    /**
     * Fetch client 360 history via AJAX
     */
    function loadClientHistory(searchKey) {
        let projectid = $("body").data("projectid") || 0;

        $("#emptySearchState").hide();
        $("#clientResultsContainer").hide();
        $("#searchLoadingState").fadeIn(150);

        $.ajax({
            url: "modal/payment_tracking_modal.php?action=GetClientFullHistory",
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
                    <td colspan="11" class="text-center py-5 text-muted">
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

            // Polyhouse Summary Text
            let polySummary = '<span class="text-muted small">-</span>';
            if (order.polyhouses && order.polyhouses.length > 0) {
                polySummary = order.polyhouses.map(p => `<span class="badge bg-teal-subtle text-teal border" style="color:#0d9488; background:#ecfdf5;"><i class="fa fa-warehouse me-1"></i>${escapeHtml(p.polyhousename || 'Polyhouse')} (${escapeHtml(p.beds || '-')})</span>`).join('<br>');
            }

            // Allocation Summary Text
            let allocSummary = `<span class="text-muted small">Alloc: <b>${formatNumber(order.allocated_qty)}</b><br>Disp: <b>${formatNumber(order.dispatched_qty)}</b></span>`;

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
                        <table class="table table-sm table-bordered mb-0" style="font-size: 11.5px;">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Crop & Variety</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end">Rate</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-center">Delivery Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${order.items.map((it, iIdx) => `
                                    <tr>
                                        <td class="text-center text-muted">${iIdx + 1}</td>
                                        <td class="fw-semibold text-dark">${escapeHtml(it.product_name)}</td>
                                        <td class="text-end fw-bold text-primary">${formatNumber(it.quantity)}</td>
                                        <td class="text-end text-muted">₹${Number(it.rate || 0).toFixed(2)}</td>
                                        <td class="text-end fw-bold text-dark">₹${Number(it.amount || 0).toLocaleString()}</td>
                                        <td class="text-center text-secondary">${escapeHtml(it.formatted_delivery_date || '-')}</td>
                                    </tr>
                                `).join('')}
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

            let urlParams = new URLSearchParams(window.location.search);
            let kParam = urlParams.get('k') || '';
            let clientObj = currentClientData ? currentClientData.client : {};
            let clid = clientObj ? (clientObj.clientid || '') : '';
            let cname = clientObj ? (clientObj.clientname || '') : '';
            let mob = clientObj ? (clientObj.mobileno || '') : '';

            let recordPaymentUrl = `../krf-orderandsales/paymentadvicemanual.php?k=${encodeURIComponent(kParam)}&psono=${encodeURIComponent(order.psono || '')}&clid=${encodeURIComponent(clid)}&cname=${encodeURIComponent(cname)}&mob=${encodeURIComponent(mob)}`;

            let actionColHtml = '';
            if (order.balance_due > 0) {
                actionColHtml = `
                    <a href="${recordPaymentUrl}" target="_blank" class="btn btn-sm btn-success py-1 px-2.5 fw-bold text-nowrap shadow-sm btn-record-payment" title="Record Payment for ${escapeHtml(psono)}" style="font-size: 11.5px; background-color: #059669; border-color: #059669;">
                        <i class="fa fa-money-bill-wave me-1"></i> Record Payment
                    </a>
                `;
            } else {
                actionColHtml = `
                    <span class="badge bg-success-subtle text-success border border-success-subtle py-1.5 px-2.5 fw-bold text-uppercase" style="font-size: 11px;">
                        <i class="fa fa-check-circle me-1"></i> Confirmed
                    </span>
                `;
            }

            let mainRowHtml = `
                <tr class="order-main-row" data-order-idx="${idx}" data-status="${status.toLowerCase()}">
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
                    <td>${polySummary}</td>
                    <td>${allocSummary}</td>
                    <td class="text-center">
                        <span class="badge ${statusBadgeClass} text-uppercase" style="font-size: 10.5px;">${escapeHtml(status)}</span>
                    </td>
                    <td class="text-center no-print">
                        ${actionColHtml}
                    </td>
                </tr>

                <!-- Expandable Sub-Row -->
                <tr class="order-detail-subrow" id="orderDetailSubrow_${idx}" style="display: none !important;">
                    <td colspan="11" class="p-3">
                        <div class="row g-3">
                            <!-- Sub Panel 1: Advance Payments Breakdown -->
                            <div class="col-12 col-lg-6">
                                <div class="order-subpanel-card">
                                    <div class="order-subpanel-title text-success justify-content-between">
                                        <span><i class="fa fa-money-bill-wave"></i> Advance Payments Recorded</span>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">${formatCurrency(order.advance_paid)} Received</span>
                                    </div>
                                    ${paymentsDetailsHtml}
                                </div>
                            </div>

                            <!-- Sub Panel 2: Ordered Items & Varieties -->
                            <div class="col-12 col-lg-6">
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

});
