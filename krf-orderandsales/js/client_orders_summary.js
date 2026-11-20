/**
 * Client Orders & Pending Payment Summary Module
 * Fetches and displays Draft & Confirmed order summaries for selected client
 * Provides View-Only modal for Confirmed Orders and Payment Modal for Draft Orders
 */

let currentClientOrdersData = {
    draft: { count: 0, total_amount: 0, paid_amount: 0, pending_balance: 0, orders: [] },
    confirmed: { count: 0, total_amount: 0, paid_amount: 0, pending_balance: 0, orders: [] },
    total_pending_balance: 0
};

function fetchClientOrdersPendingSummary(clientId, clientMobile, clientName) {
    var routurl = $('body').data('routurl') || '';
    var companyid = $('body').data('companyid') || '';
    var subcompanyid = $('body').data('subcompanyid') || '';
    var projectid = $('body').data('projectid') || '';

    if (!clientId && (!clientMobile || clientMobile.length < 10) && !clientName) {
        $("#clientOrdersSummaryWidget").addClass("d-none");
        return;
    }

    const formData = new FormData();
    formData.append('companyid', companyid);
    formData.append('subcompanyid', subcompanyid);
    formData.append('projectid', projectid);
    formData.append('clientid', clientId || '');
    formData.append('mobileno', clientMobile || '');
    formData.append('clientname', clientName || '');

    $.ajax({
        type: 'POST',
        url: `${routurl}krf-orderandsales/modal/client_provisional_summary_modal.php?action=GetClientOrdersPendingSummary`,
        data: formData,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'JSON',
        success: function (res) {
            if (res && res.status === 'success') {
                currentClientOrdersData = res;
                renderClientOrdersSummaryWidget(res);
            } else {
                $("#clientOrdersSummaryWidget").addClass("d-none");
            }
        },
        error: function (xhr) {
            console.error("Error fetching client orders summary:", xhr);
            $("#clientOrdersSummaryWidget").addClass("d-none");
        }
    });
}

function renderClientOrdersSummaryWidget(data) {
    const draft = data.draft || { count: 0, pending_balance: 0 };
    const confirmed = data.confirmed || { count: 0, pending_balance: 0 };
    const totalPending = data.total_pending_balance || 0;

    $("#totalClientPendingBadge").html(`Total Pending: ₹${parseFloat(totalPending).toFixed(2)}`);
    $("#draftOrdersCountText").text(`${draft.count} Draft / Un-Confirm`);
    $("#draftOrdersPendingText").text(`₹${parseFloat(draft.pending_balance).toFixed(2)} Pending`);

    $("#confirmedOrdersCountText").text(`${confirmed.count} Confirmed`);
    $("#confirmedOrdersPendingText").text(`₹${parseFloat(confirmed.pending_balance).toFixed(2)} Pending`);

    if (draft.count > 0 || confirmed.count > 0 || totalPending > 0) {
        $("#clientOrdersSummaryWidget").removeClass("d-none").fadeIn(200);
    } else {
        $("#clientOrdersSummaryWidget").addClass("d-none");
    }

    if (typeof window.onClientOrdersSummaryLoaded === 'function') {
        window.onClientOrdersSummaryLoaded(data);
    }
}

// Click Handler: Open Draft Orders Modal for Payment
$(document).on("click", "#btnViewDraftOrdersModal", function (e) {
    e.preventDefault();
    if (typeof getProvisionalOrderhtmlwithModal === "function") {
        // Switch to advance payment radio if needed and trigger modal
        $("#purpose_advance").prop("checked", true).trigger("change");
        getProvisionalOrderhtmlwithModal();
    }
});

// Click Handler: Open Confirmed Orders View-Only Modal
$(document).on("click", "#btnViewConfirmedOrdersModal", function (e) {
    e.preventDefault();
    renderConfirmedOrdersViewOnlyModal(currentClientOrdersData.confirmed);
});

function renderConfirmedOrdersViewOnlyModal(confirmedData) {
    const orders = confirmedData.orders || [];
    const clientName = $("#clientname").val() || "Client";
    const totalAmt = confirmedData.total_amount || 0;
    const paidAmt = confirmedData.paid_amount || 0;
    const balAmt = confirmedData.pending_balance || 0;

    let modalHtml = '';
    
    // Header summary strip
    modalHtml += `
        <div class="alert alert-success d-flex justify-content-between align-items-center py-2 px-3 mb-3 border-0 rounded shadow-xs" style="background-color: #e8f5e9;">
            <div>
                <strong class="text-success"><i class="fa fa-user-check mr-1"></i> Client:</strong> <span class="text-dark font-weight-bold">${clientName}</span>
                <span class="badge badge-success ml-2 px-2 py-1">${orders.length} Confirmed Orders</span>
            </div>
            <div class="text-right text-end small">
                <span class="text-muted mr-2">Total: <b>₹${parseFloat(totalAmt).toFixed(2)}</b></span>
                <span class="text-success mr-2">Paid: <b>₹${parseFloat(paidAmt).toFixed(2)}</b></span>
                <span class="text-danger font-weight-bold">Balance: <b>₹${parseFloat(balAmt).toFixed(2)}</b></span>
            </div>
        </div>
    `;

    if (orders.length === 0) {
        modalHtml += `
            <div class="text-center p-4 text-muted bg-light rounded">
                <i class="fa fa-check-circle fa-3x text-muted mb-2 opacity-50"></i>
                <h6>No confirmed orders found for this client.</h6>
            </div>
        `;
    } else {
        modalHtml += '<div class="list-group gap-2">';
        orders.forEach(function (ord, idx) {
            const ordTotal = parseFloat(ord.total_amount || 0);
            const ordPaid = parseFloat(ord.paid_amount || 0);
            const ordBal = Math.max(0, ordTotal - ordPaid);

            let itemsHtml = '';
            if (ord.items && ord.items.length > 0) {
                itemsHtml += '<div class="mt-2 bg-light p-2 rounded border">';
                ord.items.forEach(function (it, itIdx) {
                    itemsHtml += `
                        <div class="small text-dark ${itIdx > 0 ? 'border-top pt-1 mt-1' : ''}">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="font-weight-bold text-dark"><i class="fa fa-seedling text-success mr-1"></i>${it.product_name}</span>
                                <span class="badge badge-secondary px-2 py-1">Qty: ${it.quantity}</span>
                            </div>
                            <div class="d-flex justify-content-between text-muted mt-1" style="font-size: 0.8rem;">
                                <span>Rate: ₹${parseFloat(it.rate || 0).toFixed(2)} | Total: <b class="text-dark">₹${parseFloat(it.amount || 0).toFixed(2)}</b></span>
                                <span><i class="fa fa-calendar-alt text-muted mr-1"></i>Expected: <b>${typeof changeDateFormatYmd_dMY === 'function' ? changeDateFormatYmd_dMY(it.expecteddeliverydate) : it.expecteddeliverydate}</b></span>
                            </div>
                        </div>
                    `;
                });
                if (parseFloat(ord.freight_amount || 0) > 0) {
                    itemsHtml += `
                        <div class="small text-muted border-top pt-1 mt-1 d-flex justify-content-between align-items-center" style="font-size: 0.8rem;">
                            <span><i class="fa fa-truck text-secondary mr-1"></i>Freight Charges:</span>
                            <span class="font-weight-bold text-dark">₹${parseFloat(ord.freight_amount).toFixed(2)}</span>
                        </div>
                    `;
                }
                itemsHtml += '</div>';
            } else if (ord.product_name) {
                itemsHtml += `
                    <div class="mt-2 small text-muted">
                        <div><b>Product:</b> ${ord.product_name} (Qty: ${ord.quantity})</div>
                        <div><b>Expected Delivery:</b> ${ord.expecteddeliverydate}</div>
                    </div>
                `;
            }

            modalHtml += `
                <div class="list-group-item list-group-item-action flex-column align-items-start border rounded p-3 mb-2 shadow-xs">
                    <div class="d-flex w-100 justify-content-between align-items-start">
                        <div style="flex: 1;">
                            <div class="d-flex align-items-center">
                                <h6 class="font-weight-bold text-primary mb-0 mr-2">
                                    <i class="fa fa-receipt mr-1"></i> Order No: ${ord.psono}
                                </h6>
                                <span class="badge badge-success px-2 py-1">
                                    <i class="fa fa-check mr-1"></i>Confirmed
                                </span>
                            </div>
                            ${itemsHtml}
                        </div>
                        <div class="text-right text-end ml-3 bg-light p-2 rounded border" style="min-width: 190px;">
                            <div class="small text-dark mb-1"><b>Total Order:</b> <span class="text-primary font-weight-bold">₹${ordTotal.toFixed(2)}</span></div>
                            <div class="small text-dark mb-1"><b>Paid Amount:</b> <span class="text-success font-weight-bold">₹${ordPaid.toFixed(2)}</span></div>
                            <div class="small text-dark"><b>Balance:</b> <span class="text-danger font-weight-bold">₹${ordBal.toFixed(2)}</span></div>
                        </div>
                    </div>
                    <div class="d-flex w-100 justify-content-between mt-2 pt-2 border-top align-items-center small text-muted">
                        <span><i class="fa fa-calendar-day mr-1"></i> Order Date: <b>${ord.created_at}</b></span>
                        ${ord.payment_terms ? `<span><i class="fa fa-credit-card mr-1"></i> Terms: <b>${ord.payment_terms}</b></span>` : ''}
                    </div>
                </div>
            `;
        });
        modalHtml += '</div>';
    }

    $("#confirmedOrdersModalContent").html(modalHtml);
    $("#confirmedOrdersViewModal").modal("show");
}

// Automatically trigger summary fetch on client input / selection
$(document).ready(function () {
    const initClientId = $("#clientid").val();
    const initMobile = $("#clientmobileno").val();
    const initClientName = $("#clientname").val();

    if (initClientId || (initMobile && initMobile.length === 10)) {
        fetchClientOrdersPendingSummary(initClientId, initMobile, initClientName);
    }

    // Listen to client radio selection
    $(document).on("click", "input[name='existclient']", function () {
        const cId = $(this).data("clientid");
        const cMob = $(this).data("mobileno");
        const cName = $(this).data("clientname");
        fetchClientOrdersPendingSummary(cId, cMob, cName);
    });

    // Listen to mobile number input (when 10 digits completed)
    $(document).on("input change", "#clientmobileno", function () {
        const mob = $(this).val().trim();
        if (mob.length === 10) {
            fetchClientOrdersPendingSummary($("#clientid").val(), mob, $("#clientname").val());
        } else if (mob.length === 0) {
            $("#clientOrdersSummaryWidget").addClass("d-none");
        }
    });

    // When client name changes or is blurred
    $(document).on("blur change", "#clientname", function () {
        const cName = $(this).val().trim();
        const cId = $("#clientid").val();
        const mob = $("#clientmobileno").val().trim();
        if (cId || mob.length === 10 || cName.length >= 3) {
            fetchClientOrdersPendingSummary(cId, mob, cName);
        }
    });
});

/**
 * Checks if client has pending balance on other / confirmed orders and prompts user to choose:
 * - Against Invoice (allocates against existing order)
 * - Future Advance (saves as unallocated advance payment)
 */
function checkClientPendingOrdersAndPrompt(amount, onSaveAdvance, onSwitchInvoice) {
    if (
        currentClientOrdersData &&
        currentClientOrdersData.confirmed &&
        parseFloat(currentClientOrdersData.confirmed.pending_balance || 0) > 0
    ) {
        promptPaymentPurposeChoice(currentClientOrdersData.confirmed, amount, onSaveAdvance, onSwitchInvoice);
        return true;
    }
    return false;
}

function promptPaymentPurposeChoice(confirmedData, currentAmount, onSaveAdvance, onSwitchInvoice) {
    const pendingBal = confirmedData.pending_balance || 0;
    const count = confirmedData.count || 0;
    const formattedPending = parseFloat(pendingBal).toFixed(2);
    const formattedAmt = parseFloat(currentAmount || 0).toFixed(2);

    Swal.fire({
        title: "Pending Balance on Existing Orders",
        html: `
            <div class="text-left font-weight-normal" style="font-size: 0.92rem; color: #1e293b; text-align: left;">
                <div class="alert alert-warning py-2 px-3 mb-3 d-flex align-items-center" style="background-color: #fff9db; border-left: 4px solid #f59f00;">
                    <i class="fa fa-exclamation-circle text-warning fa-2x mr-2"></i>
                    <div>
                        This client already has a pending balance of <b class="text-danger font-weight-bold">₹${formattedPending}</b> across <b>${count} Confirmed Order(s)</b>.
                    </div>
                </div>
                <p class="mb-2 text-dark font-weight-bold">How would you like to apply this payment of <span class="text-success font-weight-bold">₹${formattedAmt}</span>?</p>
                <div class="list-group mb-2">
                    <div class="list-group-item p-2 mb-2 rounded border bg-light">
                        <strong class="text-primary"><i class="fa fa-file-invoice mr-1"></i> Option 1: Against Invoice / Existing Order</strong>
                        <div class="text-muted small mt-1">Change payment purpose to <b>Against Invoice</b> and allocate this payment against the pending order balance.</div>
                    </div>
                    <div class="list-group-item p-2 rounded border bg-light">
                        <strong class="text-success"><i class="fa fa-hand-holding-usd mr-1"></i> Option 2: Future Advance Deposit</strong>
                        <div class="text-muted small mt-1">Record this amount as a <b>Future Advance</b> unallocated payment deposit.</div>
                    </div>
                </div>
            </div>
        `,
        icon: "question",
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonText: '<i class="fa fa-file-invoice mr-1"></i> Against Invoice',
        confirmButtonColor: '#1e3c72',
        denyButtonText: '<i class="fa fa-hand-holding-usd mr-1"></i> Save as Future Advance',
        denyButtonColor: '#28a745',
        cancelButtonText: 'Cancel / Review',
        cancelButtonColor: '#6c757d',
        customClass: {
            confirmButton: 'px-3 py-2 mr-2',
            denyButton: 'px-3 py-2 mr-2',
            cancelButton: 'px-3 py-2'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // User selected: Against Invoice
            if (typeof onSwitchInvoice === "function") {
                onSwitchInvoice();
            }
        } else if (result.isDenied) {
            // User selected: Save as Future Advance
            if (typeof onSaveAdvance === "function") {
                onSaveAdvance();
            }
        }
    });
}

