$(document).ready(function () {
    let globalWeekMap = {};
    let globalSortedWeeks = [];
    let currentRawOrders = [];
    let currentFilteredOrders = [];
    let currentFilter = "all";
    let cachedVehicles = [];

    let projectid = window.VA_PROJECT_ID || $('[data-projectid]').data('projectid') || 0;
    let loginid = window.VA_LOGIN_ID || $('[data-loginid]').data('loginid') || '';
    let krfCompanyLogo = window.VA_COMPANY_LOGO || '';

    function openModal(id) {
        let el = document.getElementById(id);
        if (!el) return;
        try {
            if (window.bootstrap && bootstrap.Modal) {
                let bsModal = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
                bsModal.show();
                return;
            }
        } catch (e) { }
        if ($.fn.modal) {
            $(`#${id}`).modal("show");
        }
    }

    function closeModal(id) {
        let el = document.getElementById(id);
        if (!el) return;
        try {
            if (window.bootstrap && bootstrap.Modal) {
                let bsModal = bootstrap.Modal.getInstance(el);
                if (bsModal) { bsModal.hide(); return; }
            }
        } catch (e) { }
        if ($.fn.modal) {
            $(`#${id}`).modal("hide");
        }
    }

    // Modal Dismiss Handler scoped to target modal
    $(document).on("click", '[data-bs-dismiss="modal"], [data-dismiss="modal"], .close-modal-btn', function (e) {
        let $m = $(this).closest('.modal');
        if ($m.length) {
            let mid = $m.attr('id');
            if (mid === 'vehicleAllocModal') {
                e.preventDefault();
                hideAllocModal();
            } else if (mid === 'vaPdfPreviewModal') {
                e.preventDefault();
                hidePdfPreviewModal();
            } else if (mid === 'manualDeliveryAddressModal') {
                e.preventDefault();
                closeModal('manualDeliveryAddressModal');
            } else if (mid === 'adddeliveryaddressModal') {
                e.preventDefault();
                closeModal('adddeliveryaddressModal');
            }
        }
    });

    $(document).on("shown.bs.modal", "#manualDeliveryAddressModal, #adddeliveryaddressModal", function () {
        $('.modal-backdrop').not(':first').css('z-index', 1070);
    });

    $(document).on("hidden.bs.modal", "#manualDeliveryAddressModal, #adddeliveryaddressModal", function () {
        if ($("#vehicleAllocModal").hasClass("show") || $("#vehicleAllocModal").is(":visible")) {
            $("body").addClass("modal-open");
            if (!$(".modal-backdrop").length) {
                $("body").append('<div class="modal-backdrop fade show"></div>');
            }
            $('.modal-backdrop').first().css('z-index', '');
        }
    });

    function showAllocModal() {
        try {
            let el = document.getElementById('vehicleAllocModal');
            if (window.bootstrap && bootstrap.Modal) {
                let bsModal = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
                bsModal.show();
                return;
            }
        } catch (e) { }
        $("#vehicleAllocModal").modal("show");
    }

    function hideAllocModal() {
        try {
            let el = document.getElementById('vehicleAllocModal');
            if (window.bootstrap && bootstrap.Modal) {
                let bsModal = bootstrap.Modal.getInstance(el);
                if (bsModal) { bsModal.hide(); }
            }
        } catch (e) { }
        $("#vehicleAllocModal").modal("hide");
        setTimeout(function () {
            if (!$(".modal.show").length) {
                $(".modal-backdrop").remove();
                $("body").removeClass("modal-open").css("padding-right", "");
            }
        }, 200);
    }

    function showPdfPreviewModal() {
        try {
            let el = document.getElementById('vaPdfPreviewModal');
            if (window.bootstrap && bootstrap.Modal) {
                let bsModal = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
                bsModal.show();
                return;
            }
        } catch (e) { }
        $("#vaPdfPreviewModal").modal("show");
    }

    function hidePdfPreviewModal() {
        try {
            let el = document.getElementById('vaPdfPreviewModal');
            if (window.bootstrap && bootstrap.Modal) {
                let bsModal = bootstrap.Modal.getInstance(el);
                if (bsModal) { bsModal.hide(); }
            }
        } catch (e) { }
        $("#vaPdfPreviewModal").modal("hide");
        setTimeout(function () {
            if (!$(".modal.show").length) {
                $(".modal-backdrop").remove();
                $("body").removeClass("modal-open").css("padding-right", "");
            }
        }, 200);
    }

    // 1. Initialize Dates & Weeks
    loadActiveDates();
    loadAllSuggestions();

    function loadActiveDates() {
        $.ajax({
            url: "modal/vehicle_allocation_modal.php?action=GetActiveFinalDispatchDates",
            type: "POST",
            data: { projectid: projectid },
            dataType: "json",
            success: function (dates) {
                if (!Array.isArray(dates)) dates = [];
                let weekMap = {};
                dates.forEach(item => {
                    if (!item.dispatch_date) return;
                    let d = new Date(item.dispatch_date);
                    if (isNaN(d.getTime())) return;
                    let day = d.getDay();
                    let diffToMonday = (day === 0) ? -6 : (1 - day);
                    let monday = new Date(d);
                    monday.setDate(d.getDate() + diffToMonday);
                    monday.setHours(0, 0, 0, 0);

                    let sunday = new Date(monday);
                    sunday.setDate(monday.getDate() + 6);
                    sunday.setHours(23, 59, 59, 999);

                    let ws = monday.toISOString().split("T")[0];
                    let we = sunday.toISOString().split("T")[0];
                    weekMap[ws] = we;
                });

                let today = new Date();
                let tDay = today.getDay();
                let tDiff = (tDay === 0) ? -6 : (1 - tDay);
                let tMon = new Date(today);
                tMon.setDate(today.getDate() + tDiff);
                let tSun = new Date(tMon);
                tSun.setDate(tMon.getDate() + 6);
                let tWS = tMon.toISOString().split("T")[0];
                let tWE = tSun.toISOString().split("T")[0];

                if (!weekMap[tWS]) {
                    weekMap[tWS] = tWE;
                }

                globalWeekMap = weekMap;
                globalSortedWeeks = Object.keys(weekMap).sort();

                // Populate Years & Months
                let years = new Set();
                globalSortedWeeks.forEach(ws => years.add(ws.substring(0, 4)));
                years.add(today.getFullYear().toString());

                let yearSel = $("#vaYearFilter");
                yearSel.empty();
                Array.from(years).sort().reverse().forEach(y => {
                    yearSel.append(`<option value="${y}">${y}</option>`);
                });
                yearSel.val(today.getFullYear().toString());

                populateMonths(today.getFullYear().toString());
            }
        });
    }

    function populateMonths(selectedYear) {
        let monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
        let monthSel = $("#vaMonthFilter");
        monthSel.empty();
        monthSel.append(`<option value="all">All Months (${selectedYear})</option>`);

        let curMonth = ("0" + (new Date().getMonth() + 1)).slice(-2);
        for (let m = 1; m <= 12; m++) {
            let mStr = ("0" + m).slice(-2);
            let val = `${selectedYear}-${mStr}`;
            monthSel.append(`<option value="${val}">${monthNames[m - 1]} ${selectedYear}</option>`);
        }
        let initialMonth = `${selectedYear}-${curMonth}`;
        monthSel.val(initialMonth);

        loadOrdersForSelectedMonth();
    }

    function formatDateDisplay(dStr) {
        if (!dStr) return '';
        let parts = dStr.split('-');
        if (parts.length === 3) {
            let months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
            return `${parts[2]}-${months[parseInt(parts[1]) - 1]}-${parts[0]}`;
        }
        return dStr;
    }

    $("#vaYearFilter").on("change", function () {
        populateMonths($(this).val());
    });

    $("#vaMonthFilter").on("change", function () {
        loadOrdersForSelectedMonth();
    });

    $("#btnRefreshData").on("click", function () {
        loadOrdersForSelectedMonth();
    });

    // 2. Fetch Orders from Backend by Month
    function loadOrdersForSelectedMonth() {
        let selectedYear = $("#vaYearFilter").val() || new Date().getFullYear().toString();
        let monthVal = $("#vaMonthFilter").val();
        let dateFrom = '';
        let dateTo = '';

        if (!monthVal || monthVal === 'all') {
            dateFrom = `${selectedYear}-01-01`;
            dateTo = `${selectedYear}-12-31`;
        } else {
            let parts = monthVal.split('-');
            let year = parseInt(parts[0], 10);
            let month = parseInt(parts[1], 10);
            let lastDay = new Date(year, month, 0).getDate();
            let lastDayStr = ("0" + lastDay).slice(-2);
            dateFrom = `${monthVal}-01`;
            dateTo = `${monthVal}-${lastDayStr}`;
        }

        $("#vaOrdersTbody").html(`
            <tr>
                <td colspan="7" class="text-center py-4 text-muted">
                    <i class="fa fa-spinner fa-spin fa-2x text-success mb-2"></i>
                    <div class="fw-bold">Fetching allocated sales orders...</div>
                </td>
            </tr>
        `);

        $.ajax({
            url: "modal/vehicle_allocation_modal.php?action=GetVehicleAllocationOrders",
            type: "POST",
            data: {
                projectid: projectid,
                date_from: dateFrom,
                date_to: dateTo,
                filter_status: 'all'
            },
            dataType: "json",
            success: function (res) {
                if (res.status === "success") {
                    currentRawOrders = res.data || [];
                    renderKPICards(res.counts || {});
                    applyFiltersAndRender(true);
                } else {
                    $("#vaOrdersTbody").html(`<tr><td colspan="7" class="text-center text-danger py-4">${res.message || 'Failed to load orders'}</td></tr>`);
                }
            },
            error: function () {
                $("#vaOrdersTbody").html(`<tr><td colspan="7" class="text-center text-danger py-4">Error fetching orders from server.</td></tr>`);
            }
        });
    }

    function renderKPICards(counts) {
        let total = counts.total || currentRawOrders.length;
        let pending = counts.pending || 0;
        let allocated = counts.allocated || 0;

        let totalPlants = currentRawOrders.reduce((sum, ord) => sum + parseInt(ord.total_billable_qty || 0), 0);

        $("#kpiTotalOrders").text(total);
        $("#kpiTotalPlants").text(`${totalPlants.toLocaleString()} Plants Total`);
        $("#kpiPendingOrders").text(pending);
        $("#kpiAllocatedOrders").text(allocated);
    }

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatProductNameClean(rawName) {
        if (!rawName) return '';
        return rawName.replace(/^(NonGrafted|Non-Grafted)\s*[-:]?\s*/i, '');
    }

    function isProductGrafted(rawName) {
        if (!rawName) return false;
        return (/grafted/i.test(rawName) && !/nongrafted|non-grafted/i.test(rawName));
    }

    function getDayName(dateStr) {
        if (!dateStr) return '';
        let d = new Date(dateStr);
        if (isNaN(d.getTime())) return '';
        let days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        return days[d.getDay()];
    }

    function formatFreightAndDeliveryColumn(row) {
        if (!row) return '-';
        let rawType = (row.dl_pickuptype || '').trim();
        let normType = rawType.toLowerCase().replace(/[-_ ]/g, '');

        let baseFreight = parseFloat(row.dl_base_freight || row.dl_deliverycharges || 0);
        let payableFreight = parseFloat(row.dl_payable_freight || row.dl_freightamt || 0);
        let discAmt = parseFloat(row.dl_discount_amount || 0);
        let discPct = parseFloat(row.dl_discount_percent || 0);
        let totalAmt = parseFloat(row.dl_totalamount || 0);
        let totalDist = parseFloat(row.dl_effective_distance || row.dl_distance || row.dl_totaldistance || 0);
        let freeDist = parseFloat(row.dl_freedistance || 0);

        // 1. Pickup / Delivery Type Badge
        let typeBadge = '';
        if (normType === 'pickup' || normType === 'pickbyclient' || normType === 'selfpickup') {
            typeBadge = `<span class="badge bg-secondary text-white px-1.5 py-0.5" style="font-size: 10px;"><i class="fa fa-user-check me-1"></i>Self Pickup</span>`;
        } else if (normType === 'boxpacking' || normType === 'box') {
            typeBadge = `<span class="badge bg-warning text-dark px-1.5 py-0.5" style="font-size: 10px;"><i class="fa fa-box me-1"></i>Box Packing</span>`;
        } else {
            // Farm Delivery (both explicit 'delivered' and unassigned/default farm deliveries)
            typeBadge = `<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-1.5 py-0.5" style="font-size: 10px;"><i class="fa fa-truck me-1"></i>Farm Delivery</span>`;
        }

        // 2. Freight & Delivery Charges badges (with discount breakdown)
        let chargesHtml = '';
        if (discAmt > 0 && baseFreight > 0) {
            chargesHtml = `
                <div class="d-flex align-items-center gap-1 flex-wrap">
                    <span class="text-decoration-line-through text-muted small" style="font-size: 10.5px;" title="Base Freight: ₹${baseFreight.toLocaleString()}">₹${baseFreight.toLocaleString()}</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-1 py-0.5" style="font-size: 10px;" title="Discount: -₹${discAmt.toLocaleString()} (${discPct}%)">-${discPct}%</span>
                    <span class="badge bg-primary text-white fw-bold px-1.5 py-0.5 shadow-2xs" style="font-size: 11px;"><i class="fa fa-rupee-sign me-0.5"></i>${payableFreight.toLocaleString()}</span>
                </div>
            `;
        } else if (payableFreight > 0) {
            chargesHtml = `<span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold px-1.5 py-0.5" style="font-size: 11px;"><i class="fa fa-rupee-sign me-0.5"></i>Freight: ₹${payableFreight.toLocaleString()}</span>`;
        } else if (totalAmt > 0) {
            chargesHtml = `<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fw-bold px-1.5 py-0.5" style="font-size: 11px;"><i class="fa fa-rupee-sign me-0.5"></i>Packing: ₹${totalAmt.toLocaleString()}</span>`;
        } else {
            chargesHtml = `<span class="text-muted small" style="font-size: 10.5px;">₹0 (Free / Included)</span>`;
        }

        // 3. Distance
        let distStr = '';
        if (totalDist > 0) {
            distStr = `<span class="text-secondary small fw-bold font-monospace" style="font-size: 11px;"><i class="fa fa-route text-muted me-0.5"></i>${totalDist} km${freeDist > 0 ? ` (${freeDist} free)` : ''}</span>`;
        }

        // 4. Delivery address / Destination landmark (compact, truncated)
        let addr = row.dl_deliveryaddress || row.delivery_address || '';
        if (!addr && (row.dl_dest_city || row.dl_dest_district)) {
            addr = [row.dl_dest_city, row.dl_dest_district, row.dl_dest_state].filter(Boolean).join(', ');
        }
        let addrHtml = addr ? `<div class="text-muted text-truncate small" style="font-size: 10.5px; max-width: 250px;" title="${escapeHtml(addr)}"><i class="fa fa-map-pin text-danger me-0.5"></i>${escapeHtml(addr)}</div>` : '';

        // Point or remark
        if (row.dl_pickuppoint) {
            addrHtml += `<div class="text-muted text-truncate small" style="font-size: 10px; max-width: 250px;" title="${escapeHtml(row.dl_pickuppoint)}"><b>Point:</b> ${escapeHtml(row.dl_pickuppoint)}</div>`;
        } else if (row.dl_remark) {
            addrHtml += `<div class="text-muted fst-italic text-truncate small" style="font-size: 10px; max-width: 250px;" title="${escapeHtml(row.dl_remark)}"><i class="fa fa-comment text-info me-0.5"></i>${escapeHtml(row.dl_remark)}</div>`;
        }

        return `
            <div class="d-flex align-items-center justify-content-between gap-1 mb-0.5 flex-wrap">
                ${typeBadge}
                ${distStr}
            </div>
            <div class="mb-0.5">
                ${chargesHtml}
            </div>
            ${addrHtml}
        `;
    }

    // Active visual indicators on filters
    function updateFilterActiveVisuals() {
        const filters = [
            '#tableDeliveryDateFilter',
            '#tableClientFilter',
            '#tableProductFilter',
            '#tableVehicleStatusFilter'
        ];

        filters.forEach(selId => {
            let $el = $(selId);
            let val = $el.val();
            let isActive = val && val !== 'all' && val !== '';
            let $select2Container = $el.next('.select2-container');
            let $selection = $select2Container.find('.select2-selection');

            if (isActive) {
                $el.addClass('filter-applied');
                $select2Container.addClass('filter-applied');
                $selection.addClass('filter-applied');
            } else {
                $el.removeClass('filter-applied');
                $select2Container.removeClass('filter-applied');
                $selection.removeClass('filter-applied');
            }
        });

        // Search Input
        let searchVal = ($('#vaSearchInput').val() || '').trim();
        let $searchGroup = $('#vaSearchInput').closest('.input-group');
        if (searchVal !== '') {
            $searchGroup.addClass('filter-applied');
        } else {
            $searchGroup.removeClass('filter-applied');
        }
    }

    // Initialize Select2 on dropdowns
    function initSelect2Filters() {
        if ($.fn.select2) {
            $(".select2-filter").each(function () {
                if ($(this).hasClass("select2-hidden-accessible")) {
                    $(this).select2("destroy");
                }
                $(this).select2({
                    theme: "bootstrap-5",
                    width: "100%",
                    placeholder: $(this).find("option:first").text()
                });
            });
        }
        updateFilterActiveVisuals();
    }

    let isUpdatingFilters = false;

    // Filter computation
    function getFilteredDataset(excludeType = null) {
        let list = currentRawOrders;

        // 1. Delivery Date filter
        if (excludeType !== 'delivery_date') {
            let selDate = $("#tableDeliveryDateFilter").val();
            if (selDate && selDate !== "all") {
                list = list.filter(ord => (ord.deliverydate === selDate || ord.formatted_delivery_date === selDate));
            }
        }

        // 2. Client / Sales Order filter
        if (excludeType !== 'client') {
            let selClient = $("#tableClientFilter").val();
            if (selClient && selClient !== "all") {
                let target = selClient.trim().toLowerCase();
                list = list.filter(ord => {
                    let cName = (ord.clientname || '').trim().toLowerCase();
                    let sono = (ord.sono || '').trim().toLowerCase();
                    let psono = (ord.psono || '').trim().toLowerCase();
                    return (cName === target || cName.includes(target) || sono.includes(target) || psono.includes(target));
                });
            }
        }

        // 3. Product filter
        if (excludeType !== 'product') {
            let selProduct = $("#tableProductFilter").val();
            if (selProduct && selProduct !== "all") {
                let target = selProduct.trim().toLowerCase();
                list = list.filter(ord => {
                    let cleanP = formatProductNameClean(ord.clean_product_name || ord.product_names || '').trim().toLowerCase();
                    let rawP = (ord.product_names || '').trim().toLowerCase();
                    return (cleanP === target || cleanP.includes(target) || rawP.includes(target));
                });
            }
        }

        // 4. Vehicle Status filter
        if (excludeType !== 'status') {
            let statusVal = $("#tableVehicleStatusFilter").val();
            let effectiveStatus = (currentFilter !== "all") ? currentFilter : statusVal;
            if (effectiveStatus === 'pending_vehicle') {
                list = list.filter(ord => !ord.is_vehicle_allocated);
            } else if (effectiveStatus === 'allocated_vehicle') {
                list = list.filter(ord => ord.is_vehicle_allocated);
            }
        }

        // 5. Search Keyword
        if (excludeType !== 'search') {
            let search = ($("#vaSearchInput").val() || '').trim().toLowerCase();
            if (search) {
                list = list.filter(ord => {
                    let cleanProd = formatProductNameClean(ord.clean_product_name || ord.product_names || '');
                    let dlText = `${ord.dl_pickuptype || ''} ${ord.dl_deliveryaddress || ''} ${ord.dl_dest_state || ''} ${ord.dl_dest_district || ''} ${ord.dl_dest_city || ''} ${ord.dl_pickuppoint || ''} ${ord.dl_remark || ''}`;
                    let haystack = [
                        ord.sono,
                        ord.psono,
                        ord.clientname,
                        ord.mobileno,
                        ord.districtname,
                        ord.statename,
                        ord.clean_product_name,
                        ord.product_names,
                        cleanProd,
                        ord.stock_order_nos,
                        ord.effective_vehicleno,
                        ord.effective_vehiclename,
                        ord.effective_drivername,
                        ord.effective_drivermobile,
                        ord.allocated_by_name,
                        ord.vehicle_remarks,
                        ord.formatted_delivery_date,
                        dlText
                    ].filter(Boolean).join(' ').toLowerCase();
                    return haystack.includes(search);
                });
            }
        }

        return list;
    }

    function populateDynamicFilters() {
        isUpdatingFilters = true;

        let curDate = $("#tableDeliveryDateFilter").val() || "all";
        let curClient = $("#tableClientFilter").val() || "all";
        let curProduct = $("#tableProductFilter").val() || "all";
        let curStatus = $("#tableVehicleStatusFilter").val() || "all";

        // 1. Delivery Dates
        let dateData = getFilteredDataset('delivery_date');
        let dateMap = {};
        dateData.forEach(ord => {
            let d = ord.deliverydate;
            let fd = ord.formatted_delivery_date || formatDateDisplay(d);
            if (d && fd) {
                dateMap[d] = { formatted: fd, count: (dateMap[d] ? dateMap[d].count + 1 : 1) };
            }
        });
        let sortedDates = Object.keys(dateMap).sort();
        let dateHtml = `<option value="all">All Dates (${sortedDates.length})</option>`;
        sortedDates.forEach(d => {
            let isSel = (curDate === d) ? 'selected' : '';
            dateHtml += `<option value="${d}" ${isSel}>${dateMap[d].formatted} (${dateMap[d].count})</option>`;
        });
        $("#tableDeliveryDateFilter").html(dateHtml);
        if (!dateMap[curDate] && curDate !== "all") {
            $("#tableDeliveryDateFilter").val("all");
        }

        // 2. Clients & Orders
        let clientData = getFilteredDataset('client');
        let clientMap = {};
        clientData.forEach(ord => {
            let cName = (ord.clientname || '').trim();
            let sono = (ord.sono || '').trim();
            if (cName) {
                if (!clientMap[cName]) {
                    clientMap[cName] = { name: cName, sonos: new Set(), count: 0 };
                }
                clientMap[cName].count++;
                if (sono) clientMap[cName].sonos.add(sono);
            }
        });
        let sortedClients = Object.keys(clientMap).sort();
        let clientHtml = `<option value="all">All Clients & Orders (${sortedClients.length})</option>`;
        sortedClients.forEach(c => {
            let obj = clientMap[c];
            let sonoList = Array.from(obj.sonos).join(', ');
            let label = sonoList ? `${obj.name} (SO: ${sonoList})` : obj.name;
            let isSel = (curClient === c) ? 'selected' : '';
            clientHtml += `<option value="${escapeHtml(c)}" ${isSel}>${escapeHtml(label)}</option>`;
        });
        $("#tableClientFilter").html(clientHtml);
        if (!clientMap[curClient] && curClient !== "all") {
            $("#tableClientFilter").val("all");
        }

        // 3. Products
        let prodData = getFilteredDataset('product');
        let prodMap = {};
        prodData.forEach(ord => {
            let pName = formatProductNameClean(ord.clean_product_name || ord.product_names || '').trim();
            if (pName) {
                prodMap[pName] = (prodMap[pName] || 0) + 1;
            }
        });
        let sortedProds = Object.keys(prodMap).sort();
        let prodHtml = `<option value="all">All Products (${sortedProds.length})</option>`;
        sortedProds.forEach(p => {
            let isSel = (curProduct === p) ? 'selected' : '';
            prodHtml += `<option value="${escapeHtml(p)}" ${isSel}>${escapeHtml(p)} (${prodMap[p]})</option>`;
        });
        $("#tableProductFilter").html(prodHtml);
        if (!prodMap[curProduct] && curProduct !== "all") {
            $("#tableProductFilter").val("all");
        }

        // 4. Vehicle Status counts
        let statusData = getFilteredDataset('status');
        let totalStat = statusData.length;
        let pendingStat = statusData.filter(o => !o.is_vehicle_allocated).length;
        let allocStat = statusData.filter(o => o.is_vehicle_allocated).length;

        let effStatusVal = (currentFilter !== "all") ? currentFilter : curStatus;
        let statusHtml = `
            <option value="all" ${effStatusVal === 'all' ? 'selected' : ''}>All Vehicle Statuses (${totalStat})</option>
            <option value="pending_vehicle" ${effStatusVal === 'pending_vehicle' ? 'selected' : ''}>Pending Vehicle Assignment (${pendingStat})</option>
            <option value="allocated_vehicle" ${effStatusVal === 'allocated_vehicle' ? 'selected' : ''}>Vehicle Allocated (${allocStat})</option>
        `;
        $("#tableVehicleStatusFilter").html(statusHtml);

        initSelect2Filters();
        isUpdatingFilters = false;
    }

    function applyFiltersAndRender(updateDropdowns = true) {
        let filtered = getFilteredDataset();

        if (updateDropdowns) {
            populateDynamicFilters();
        }

        updateFilterActiveVisuals();

        // Toggle clear search button [x]
        let searchVal = ($("#vaSearchInput").val() || '').trim();
        if (searchVal !== '') {
            $("#btnVaClearSearch").show();
        } else {
            $("#btnVaClearSearch").hide();
        }

        $("#showingCount").text(filtered.length);
        $("#totalCount").text(currentRawOrders.length);

        renderOrdersTableRows(filtered);
    }

    // Dynamic Filter Change Handlers
    $("#tableDeliveryDateFilter, #tableClientFilter, #tableProductFilter").on("change", function () {
        if (isUpdatingFilters) return;
        applyFiltersAndRender(true);
    });

    // Vehicle Status dropdown change
    $("#tableVehicleStatusFilter").on("change", function () {
        if (isUpdatingFilters) return;
        let val = $(this).val();
        currentFilter = val;
        $(".status-filter-card").removeClass("active");
        if (val === 'pending_vehicle') {
            $("#cardFilterPending").addClass("active");
        } else if (val === 'allocated_vehicle') {
            $("#cardFilterAllocated").addClass("active");
        } else {
            $("#cardFilterAll").addClass("active");
        }
        applyFiltersAndRender(true);
    });

    // KPI Card Click Filter
    $(".status-filter-card").on("click", function () {
        $(".status-filter-card").removeClass("active");
        $(this).addClass("active");
        currentFilter = $(this).data("filter") || "all";

        isUpdatingFilters = true;
        $("#tableVehicleStatusFilter").val(currentFilter);
        if ($.fn.select2 && $("#tableVehicleStatusFilter").hasClass("select2-hidden-accessible")) {
            $("#tableVehicleStatusFilter").trigger("change.select2");
        }
        isUpdatingFilters = false;

        applyFiltersAndRender(true);
    });

    // Real-time search filter
    $("#vaSearchInput").on("input keyup", function () {
        applyFiltersAndRender(true);
    });

    // Clear search button [x]
    $("#btnVaClearSearch").on("click", function () {
        $("#vaSearchInput").val('');
        $(this).hide();
        applyFiltersAndRender(true);
    });

    // Reset Filters button
    $("#btnResetFilters").on("click", function () {
        isUpdatingFilters = true;
        $("#tableDeliveryDateFilter").val("all");
        $("#tableClientFilter").val("all");
        $("#tableProductFilter").val("all");
        $("#tableVehicleStatusFilter").val("all");

        if ($.fn.select2) {
            $("#tableDeliveryDateFilter, #tableClientFilter, #tableProductFilter, #tableVehicleStatusFilter").trigger("change.select2");
        }

        $("#vaSearchInput").val("");
        $("#btnVaClearSearch").hide();

        currentFilter = "all";
        $(".status-filter-card").removeClass("active");
        $("#cardFilterAll").addClass("active");
        isUpdatingFilters = false;

        applyFiltersAndRender(true);
    });

    // Render Table Rows
    function renderOrdersTableRows(filtered) {
        currentFilteredOrders = filtered;
        let tbody = $("#vaOrdersTbody");
        tbody.empty();

        if (filtered.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        <i class="fa fa-info-circle fa-2x mb-2 text-secondary opacity-50"></i>
                        <h6 class="fw-bold text-dark">No Orders Found</h6>
                        <div class="small">No orders match the selected week and filter criteria.</div>
                    </td>
                </tr>
            `);
            return;
        }

        filtered.forEach((ord, idx) => {
            let isAllocated = ord.is_vehicle_allocated;

            // 1. Delivery Date column
            let dayName = getDayName(ord.deliverydate);
            let dateCol = `
                <div class="fw-bold text-dark font-monospace" style="font-size: 12.5px;">
                    <i class="fa fa-calendar-day text-success me-1"></i>${ord.formatted_delivery_date}
                </div>
                ${dayName ? `<span class="badge bg-light text-secondary border px-1.5 py-0" style="font-size: 10.5px;">${dayName}</span>` : ''}
            `;

            // 2. Client & Sales Order Details column (Compact, 3 lines)
            let clientCol = `
                <div class="fw-bold text-dark text-truncate" style="font-size: 13.5px;" title="${escapeHtml(ord.clientname || 'N/A')}">
                    ${escapeHtml(ord.clientname || 'N/A')}
                </div>
                <div class="d-flex align-items-center gap-1 my-0.5 flex-wrap">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-1.5 py-0.5" style="font-size: 11px;">SO: ${escapeHtml(ord.sono || '-')}</span>
                    ${ord.psono ? `<span class="badge bg-light text-secondary border font-monospace px-1.5 py-0.5" style="font-size: 10px;">PSO: ${escapeHtml(ord.psono)}</span>` : ''}
                </div>
                <div class="text-secondary small d-flex align-items-center gap-2 flex-wrap" style="font-size: 11px;">
                    <span><i class="fa fa-phone text-primary me-0.5"></i><a href="tel:${escapeHtml(ord.mobileno)}" class="text-decoration-none text-secondary">${escapeHtml(ord.mobileno || '-')}</a></span>
                    ${ord.districtname ? `<span><i class="fa fa-map-marker-alt text-danger me-0.5"></i>${escapeHtml(ord.districtname)}</span>` : ''}
                </div>
            `;

            // 3. Order & Plants Details column (Compact, 3 lines)
            let billableQty = parseInt(ord.total_billable_qty || 0);
            let orderQty = parseInt(ord.total_order_qty || billableQty);
            let dispQty = parseInt(ord.total_dispatched_qty || 0);
            let cleanProd = formatProductNameClean(ord.clean_product_name || ord.product_names || 'Crop');
            let isGrafted = isProductGrafted(ord.product_names || '');
            let orderCol = `
                <div class="d-flex align-items-center gap-1 mb-0.5 flex-wrap">
                    <span class="badge bg-success fw-bold px-2 py-0.5 shadow-sm" style="font-size: 12px;">
                        <i class="fa fa-leaf me-1"></i>${billableQty.toLocaleString()} Plants
                    </span>
                    ${isGrafted ?
                    '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-1 py-0.5" style="font-size: 10px;">Grafted</span>' :
                    '<span class="badge bg-secondary-subtle text-secondary border px-1 py-0.5" style="font-size: 10px;">Non-Grafted</span>'
                }
                </div>
                ${dispQty > 0 ? `
                    <div class="text-secondary small font-monospace mb-0.5" style="font-size: 10.5px;">
                        <span title="Original Order Quantity">Order: <b>${orderQty.toLocaleString()}</b></span> | 
                        <span class="text-danger" title="Already Dispatched Quantity">Disp: <b>${dispQty.toLocaleString()}</b></span>
                    </div>
                ` : ''}
                <div class="fw-bold text-dark text-truncate" style="font-size: 12px;" title="${escapeHtml(cleanProd)}">
                    <i class="fa fa-seedling text-success me-1"></i>${escapeHtml(cleanProd)}
                </div>
                ${ord.stock_order_nos ? `<div class="text-muted small" style="font-size: 10.5px;">Stock: <b class="text-primary font-monospace">${escapeHtml(ord.stock_order_nos)}</b></div>` : ''}
            `;

            // 4. Delivery & Freight Charges column (NEW)
            let freightCol = formatFreightAndDeliveryColumn(ord);

            // 5. Vehicle & Driver Assignment column (Supports Multi-Vehicle Badges)
            let vehicleCol = '';
            if (isAllocated) {
                let vehNo = escapeHtml(ord.effective_vehicleno);
                let vehName = ord.effective_vehiclename ? ` — ${escapeHtml(ord.effective_vehiclename)}` : '';
                let driver = ord.effective_drivername ? `<b>Driver:</b> ${escapeHtml(ord.effective_drivername)}` : '';
                let mobile = ord.effective_drivermobile ? ` (<a href="tel:${escapeHtml(ord.effective_drivermobile)}" class="text-decoration-none fw-semibold">${escapeHtml(ord.effective_drivermobile)}</a>)` : '';
                let schedDate = ord.scheduled_dispatch_date ? formatDateDisplay(ord.scheduled_dispatch_date) : '';
                let allocBy = escapeHtml(ord.allocated_by_name || ord.allocated_by || 'Logistics');
                let allocAt = ord.allocated_at_formatted ? `(${escapeHtml(ord.allocated_at_formatted)})` : '';
                let remarks = ord.vehicle_remarks ? `<div class="text-muted fst-italic text-truncate small" style="font-size: 10px; max-width: 250px;" title="${escapeHtml(ord.vehicle_remarks)}"><i class="fa fa-comment-dots text-primary me-0.5"></i>${escapeHtml(ord.vehicle_remarks)}</div>` : '';

                let badgesHtml = '';
                if (Array.isArray(ord.allocated_vehicles) && ord.allocated_vehicles.length > 1) {
                    badgesHtml = `<div class="d-flex flex-wrap gap-1 mb-1">`;
                    ord.allocated_vehicles.forEach((v, vIdx) => {
                        let p = escapeHtml(v.vehicleno || '');
                        let plants = v.allocated_plants > 0 ? ` (${v.allocated_plants.toLocaleString()} pl)` : '';
                        badgesHtml += `<span class="badge bg-success-subtle text-success border border-success-subtle plate-badge py-0.5 px-1.5" style="font-size: 10.5px;" title="${escapeHtml(v.vehiclename || '')}"><i class="fa fa-truck me-0.5"></i>#${vIdx + 1}: ${p}${plants}</span>`;
                    });
                    badgesHtml += `</div>`;
                } else {
                    badgesHtml = `
                    <div class="mb-0.5">
                        <span class="badge bg-success-subtle text-success border border-success-subtle plate-badge py-0.5 px-2" style="font-size: 11.5px;">
                            <i class="fa fa-truck me-1"></i> ${vehNo}${vehName}
                        </span>
                    </div>`;
                }

                vehicleCol = `
                    ${badgesHtml}
                    <div class="small text-dark text-truncate" style="font-size: 11.5px;">
                        ${driver}${mobile}
                    </div>
                    <div class="text-muted small d-flex align-items-center gap-1 flex-wrap" style="font-size: 10.5px;">
                        ${schedDate ? `<span><i class="fa fa-clock text-info me-0.5"></i>${schedDate}</span>` : ''}
                        <span>By: <b class="text-dark">${allocBy}</b> ${allocAt}</span>
                    </div>
                    ${remarks}
                `;
            } else {
                vehicleCol = `
                    <div class="d-inline-flex align-items-center gap-1.5 px-2 py-0.5 rounded border border-warning-subtle bg-warning-subtle text-warning-emphasis">
                        <i class="fa fa-exclamation-triangle text-warning" style="font-size: 12px;"></i>
                        <span class="fw-bold" style="font-size: 11.5px;">Pending Vehicle</span>
                    </div>
                    <div class="text-muted small mt-0.5" style="font-size: 10.5px;">Awaiting logistics allocation</div>
                `;
            }

            // 6. Action Buttons
            let actionBtn = '';
            if (isAllocated) {
                actionBtn = `
                    <div class="d-flex flex-column gap-1 align-items-center">
                        <button type="button" class="btn btn-sm btn-outline-primary fw-bold open-vehicle-alloc-modal-btn w-100 py-0.5 px-1 shadow-sm" style="font-size: 11.5px;" data-index="${idx}">
                            <i class="fa fa-edit me-0.5"></i> Edit
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-vehicle-alloc-btn w-100 py-0.5 px-1" style="font-size: 11px;" data-sono="${escapeHtml(ord.sono || '')}" data-psono="${escapeHtml(ord.psono || '')}" title="Remove vehicle assignment">
                            <i class="fa fa-trash-alt me-0.5"></i> Remove
                        </button>
                    </div>
                `;
            } else {
                actionBtn = `
                    <button type="button" class="btn btn-sm btn-primary fw-bold open-vehicle-alloc-modal-btn w-100 py-1 px-1.5 shadow-sm" style="font-size: 11.5px;" data-index="${idx}">
                        <i class="fa fa-truck me-1"></i> Allocate
                    </button>
                `;
            }

            tbody.append(`
                <tr>
                    <td class="text-center fw-bold text-secondary py-2">${idx + 1}</td>
                    <td class="py-2">${dateCol}</td>
                    <td class="py-2">${clientCol}</td>
                    <td class="py-2">${orderCol}</td>
                    <td class="py-2">${freightCol}</td>
                    <td class="py-2">${vehicleCol}</td>
                    <td class="text-center py-2">${actionBtn}</td>
                </tr>
            `);
        });
    }

    // 3. Autocomplete Master Suggestions Cache & Multi-Vehicle Groups Manager
    let suggestionVehiclesMap = {};
    let suggestionDriversMap = {};
    let suggestionDriversList = [];
    let suggestionVehicleTypesList = [];
    let suggestionVehicleTypesMap = {};
    allVehiclesList = [];
    let suggestionTransportersList = [];
    let vehicleGroupCounter = 0;

    function loadAllSuggestions() {
        $.ajax({
            url: "modal/vehicle_allocation_modal.php?action=GetVehicleAllocationSuggestions",
            type: "POST",
            data: { projectid: projectid },
            dataType: "json",
            success: function (res) {
                if (res.status === "success" && res.data) {
                    populateSuggestionDatalists(res.data);
                }
            }
        });
    }

    function populateSuggestionDatalists(data) {
        suggestionVehiclesMap = {};
        suggestionDriversMap = {};
        suggestionVehicleTypesMap = {};
        suggestionDriversList = Array.isArray(data.drivers) ? data.drivers : [];
        suggestionVehicleTypesList = Array.isArray(data.vehicle_types) ? data.vehicle_types : [];
        allVehiclesList = Array.isArray(data.vehicles) ? data.vehicles : [];
        suggestionTransportersList = Array.isArray(data.transporters) ? data.transporters : [];

        // Cache vehicle types in map (supports both vehicletypename and vehiclename)
        suggestionVehicleTypesList.forEach(vt => {
            let name = (vt.vehicletypename || vt.vehiclename || '').trim();
            if (name) {
                suggestionVehicleTypesMap[name.toLowerCase()] = vt;
            }
        });

        // Cache vehicles in map
        allVehiclesList.forEach(v => {
            let plate = (v.vehicleno || '').trim().toUpperCase();
            if (plate) {
                suggestionVehiclesMap[plate] = v;
                let cleanPlate = plate.replace(/[\s\-\/]/g, '');
                if (cleanPlate) {
                    suggestionVehiclesMap[cleanPlate] = v;
                }
            }
        });

        // Populate Drivers Datalist & cache map
        let dList = $("#vamDriverDataList");
        dList.empty();
        suggestionDriversList.forEach(d => {
            let dName = (d.drivername || '').trim();
            if (dName) {
                suggestionDriversMap[dName.toLowerCase()] = d;
                let mobileText = d.drivermobileno ? ` (${d.drivermobileno})` : '';
                dList.append(`<option value="${dName}">${dName}${mobileText}</option>`);
            }
        });

        // Populate Transporters Datalist
        let tList = $("#vamTransporterDataList");
        tList.empty();
        if (Array.isArray(suggestionTransportersList)) {
            suggestionTransportersList.forEach(t => {
                tList.append(`<option value="${t}">`);
            });
        }

        // If any vehicle group cards are currently open, refresh their datalists
        $(".vehicle-group-card").each(function () {
            let card = $(this);
            let selType = card.find(".vam-group-vehicletype").val();
            filterGroupPlates(card, selType);
        });
    }

    // Generate HTML template for a Vehicle Group card
    function buildVehicleGroupHtml(idx, data) {
        data = data || {};
        let plantsVal = data.allocated_plants !== undefined ? data.allocated_plants : '';
        let vehType = (data.vehiclename || '').trim();
        let capVal = data.plantscapacity > 0 ? data.plantscapacity : (data.vehicle_capacity || '');
        let plateVal = (data.vehicleno || '').toUpperCase().trim();
        let ownerVal = data.vehicleownername || '';
        let driverVal = data.drivername || '';
        let mobileVal = data.drivermobileno || '';

        // Options for vehicle type dropdown
        let vtOptions = `<option value="">-- Choose Vehicle Type --</option>`;
        let isCustomType = false;
        let matchedType = false;
        if (Array.isArray(suggestionVehicleTypesList)) {
            suggestionVehicleTypesList.forEach(vt => {
                let name = (vt.vehicletypename || vt.vehiclename || '').trim();
                let cap = parseInt(vt.plantscapacity) || 0;
                let capText = cap > 0 ? ` (Cap: ${cap.toLocaleString()} plants)` : '';
                let sel = (vehType && name.toLowerCase() === vehType.toLowerCase()) ? 'selected' : '';
                if (sel) matchedType = true;
                vtOptions += `<option value="${escapeHtml(name)}" data-id="${vt.id || 0}" data-capacity="${cap}" ${sel}>${escapeHtml(name)}${capText}</option>`;
            });
        }
        if (vehType && !matchedType) {
            isCustomType = true;
        }
        let customSel = isCustomType ? 'selected' : '';
        vtOptions += `<option value="custom" ${customSel}>[+] Other / Custom Vehicle Type</option>`;

        return `
        <div class="card border rounded-3 bg-white shadow-sm vehicle-group-card" id="vam_group_${idx}" data-index="${idx}">
            <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge group-title-badge py-1 px-2.5 fw-bold shadow-none" style="color: #0b5ed7 !important; background-color: #e7f1ff !important; border: 1px solid #b6d4fe !important; font-size: 12px; letter-spacing: 0.2px;">
                        <i class="fa fa-truck me-1" style="color: #0b5ed7 !important;"></i> <span class="group-title-text">Vehicle #1</span>
                    </span>
                    <span class="badge group-plate-preview py-1 px-2 font-monospace" style="color: #495057 !important; background-color: #f8f9fa !important; border: 1px solid #dee2e6 !important; font-size: 11.5px;">
                        ${plateVal ? plateVal : 'Vehicle Unassigned'}
                    </span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2 btn-remove-vehicle-group" title="Remove this vehicle group" style="font-size: 11.5px;">
                        <i class="fa fa-trash-alt me-1"></i> Remove
                    </button>
                </div>
            </div>
            <div class="card-body p-3">
                <div class="row g-3">
                    <!-- 1. Vehicle Type Dropdown -->
                    <div class="col-md-5">
                        <label class="form-label text-dark small fw-bold mb-1">
                            Select Vehicle Type <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-primary"><i class="fa fa-truck-moving"></i></span>
                            <select class="form-select form-select-sm fw-bold vam-group-vehicletype" required>
                                ${vtOptions}
                            </select>
                        </div>
                        <div class="vam-custom-type-box mt-1" style="display: ${isCustomType ? 'block' : 'none'};">
                            <input type="text" class="form-control form-control-sm vam-custom-type-input" placeholder="Enter custom vehicle type / model" value="${isCustomType ? vehType : ''}">
                        </div>
                        <div class="text-muted small mt-1" style="font-size: 11px;">Select vehicle type to check capacity & filter plates.</div>
                    </div>

                    <!-- 2. Allocated Plants for this Vehicle -->
                    <div class="col-md-4">
                        <label class="form-label text-dark small fw-bold mb-1">
                            Allocated Load (Plants) <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-success fw-bold"><i class="fa fa-leaf"></i></span>
                            <input type="number" class="form-control form-control-sm fw-bold vam-group-plants" min="1" placeholder="e.g. 50000" value="${plantsVal}" required>
                        </div>
                        <div class="vam-group-plants-helper" style="display: none;"></div>
                        <div class="text-muted small mt-1" style="font-size: 11px;">Plants carried by this specific vehicle.</div>
                    </div>

                    <!-- 3. Vehicle Plant Capacity -->
                    <div class="col-md-3">
                        <label class="form-label text-dark small fw-bold mb-1">
                            Vehicle Capacity <span class="text-muted fw-normal">(Plants)</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-muted"><i class="fa fa-tachometer-alt"></i></span>
                            <input type="number" class="form-control form-control-sm fw-bold vam-group-capacity" placeholder="e.g. 50000" min="0" value="${capVal}">
                        </div>
                        <div class="text-muted small mt-1" style="font-size: 11px;">Standard rated capacity.</div>
                    </div>

                    <!-- 4. Live Vehicle Capacity Checker (Per-Vehicle Isolation) -->
                    <div class="col-12 vam-group-capacity-notice-box" style="display: none;">
                        <div class="vam-capacity-card">
                            <div class="vam-capacity-header">
                                <div class="vam-cap-title">
                                    <i class="fa fa-tachometer-alt text-primary"></i>
                                    <span>Capacity & Daily Schedule Evaluation</span>
                                </div>
                                <div class="vam-cap-status-pill">
                                    <!-- Dynamic badge: e.g. Overcapacity or Available -->
                                </div>
                            </div>
                            
                            <div class="vam-capacity-progress-wrapper">
                                <div class="progress vam-capacity-progress-bar-container">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated vam-group-capacity-bar" role="progressbar" style="width: 0%;"></div>
                                </div>
                            </div>

                            <div class="vam-capacity-tiles-grid">
                                <!-- Tile 1: Rated Capacity -->
                                <div class="vam-cap-tile">
                                    <div class="vam-cap-tile-label"><i class="fa fa-tachometer-alt me-1 text-secondary"></i> Rated Capacity</div>
                                    <div class="vam-cap-tile-value text-dark vam-group-cap-rated">-</div>
                                </div>

                                <!-- Tile 2: Already Scheduled -->
                                <div class="vam-cap-tile">
                                    <div class="vam-cap-tile-label"><i class="fa fa-calendar-check me-1 text-primary"></i> Already Scheduled</div>
                                    <div class="vam-cap-tile-value text-primary vam-group-cap-already">0 plants</div>
                                </div>

                                <!-- Tile 3: This Order Load -->
                                <div class="vam-cap-tile">
                                    <div class="vam-cap-tile-label"><i class="fa fa-truck-loading me-1 text-info"></i> This Vehicle Load</div>
                                    <div class="vam-cap-tile-value text-dark vam-group-cap-this">0 plants</div>
                                </div>

                                <!-- Tile 4: Total Day Load -->
                                <div class="vam-cap-tile">
                                    <div class="vam-cap-tile-label"><i class="fa fa-layer-group me-1 text-warning"></i> Total Day Load</div>
                                    <div class="vam-cap-tile-value text-dark vam-group-cap-total">0 plants</div>
                                </div>

                                <!-- Tile 5: Remaining Space -->
                                <div class="vam-cap-tile vam-cap-tile-remain">
                                    <div class="vam-cap-tile-label"><i class="fa fa-balance-scale me-1"></i> Remaining Space</div>
                                    <div class="vam-cap-tile-value vam-group-cap-remain">-</div>
                                </div>
                            </div>

                            <div class="vam-group-capacity-advice mt-2" style="display:none;"></div>
                        </div>
                    </div>

                    <!-- 5. Vehicle Registration Number -->
                    <div class="col-md-3 col-sm-6 position-relative vam-vehicle-plate-wrapper">
                        <label class="form-label text-dark small fw-bold mb-1">
                            Vehicle Registration No <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-dark font-monospace"><i class="fa fa-id-card text-primary"></i></span>
                            <input type="text" class="form-control form-control-sm fw-bold text-uppercase vam-group-vehicleno" placeholder="e.g. UP64BT7828" value="${plateVal}" autocomplete="off" required>
                            <button type="button" class="btn btn-outline-secondary btn-sm btn-group-clear-vehicle" title="Clear plate">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                        <div class="vam-vehicle-autocomplete-menu shadow border rounded-2 bg-white" style="display: none; position: absolute; z-index: 1060; left: 12px; right: 12px; top: calc(100% + 2px); max-height: 230px; overflow-y: auto;"></div>
                        <div class="text-muted small mt-1" style="font-size: 11px;">Select plate or enter registration.</div>
                    </div>

                    <!-- 6. Owner / Transporter Name -->
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label text-dark small fw-bold mb-1">
                            Owner / Transporter Name <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-muted"><i class="fa fa-user-tie text-secondary"></i></span>
                            <input type="text" class="form-control form-control-sm vam-group-owner" list="vamTransporterDataList" placeholder="Search transporter or enter new" value="${ownerVal}" autocomplete="off" required>
                            <button type="button" class="btn btn-outline-secondary btn-sm btn-clear-owner" title="Clear transporter">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                        <div class="text-muted small mt-1" style="font-size: 11px;">Vehicle owner / transporter.</div>
                    </div>

                    <!-- 7. Driver Name -->
                    <div class="col-md-3 col-sm-6 position-relative vam-driver-name-wrapper">
                        <label class="form-label text-dark small fw-bold mb-1">
                            Driver Full Name <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-muted"><i class="fa fa-user text-primary"></i></span>
                            <input type="text" class="form-control form-control-sm vam-group-driver" placeholder="Search driver or enter new" value="${driverVal}" autocomplete="off" required>
                            <button type="button" class="btn btn-outline-secondary btn-sm btn-clear-driver" title="Clear driver">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                        <div class="vam-driver-autocomplete-menu shadow border rounded-2 bg-white" style="display: none; position: absolute; z-index: 1060; left: 12px; right: 12px; top: calc(100% + 2px); max-height: 220px; overflow-y: auto;"></div>
                        <div class="text-muted small mt-1" style="font-size: 11px;">Search or enter driver name (letters only).</div>
                    </div>

                    <!-- 8. Driver Mobile No -->
                    <div class="col-md-3 col-sm-6 position-relative vam-driver-mobile-wrapper">
                        <label class="form-label text-dark small fw-bold mb-1">
                            Driver Mobile No <span class="text-danger">*</span> <span class="text-muted fw-normal">(10 digits)</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-muted"><i class="fa fa-phone text-success"></i></span>
                            <input type="tel" class="form-control form-control-sm vam-group-mobile" maxlength="10" placeholder="e.g. 9876543210" value="${mobileVal}" autocomplete="off" required pattern="[6-9][0-9]{9}">
                            <button type="button" class="btn btn-outline-secondary btn-sm btn-clear-mobile" title="Clear mobile">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                        <div class="vam-mobile-autocomplete-menu shadow border rounded-2 bg-white" style="display: none; position: absolute; z-index: 1060; left: 12px; right: 12px; top: calc(100% + 2px); max-height: 220px; overflow-y: auto;"></div>
                        <div class="text-danger vam-group-mobile-err" style="display:none; font-size: 11px; margin-top: 2px;">
                            <i class="fa fa-exclamation-circle me-1"></i>Must be a valid 10-digit mobile.
                        </div>
                        <div class="text-muted small mt-1" style="font-size: 11px;">10-digit mobile number.</div>
                    </div>

                    <!-- 9. Live Vehicle Schedule & Matched Type Notice Box -->
                    <div class="col-12 vam-group-schedule-notice-box" style="display: none;">
                        <div class="vam-schedule-card">
                            <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="vam-schedule-badge"></span>
                                    <span class="vam-schedule-trip-ref small text-muted"></span>
                                </div>
                                <div class="vam-schedule-date-ref small text-muted"></div>
                            </div>
                            <div class="vam-schedule-summary-text small"></div>
                            
                            <!-- Confirmation Prompt to update Vehicle Type -->
                            <div class="vam-schedule-type-prompt mt-2 p-2 px-3 rounded d-flex justify-content-between align-items-center flex-wrap gap-2" style="display: none;"></div>

                            <!-- Success status message after updating Vehicle Type -->
                            <div class="vam-schedule-type-status mt-2 p-2 px-3 rounded" style="display: none;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        `;
    }

    // Filter plates for a specific group card's datalist
    function filterGroupPlates(card, selectedType) {
        let idx = card.data("index");
        let vList = $(`#vamVehicleDataList_${idx}`);
        vList.empty();
        let typeLower = (selectedType || '').trim().toLowerCase();

        let matched = allVehiclesList;
        if (typeLower && typeLower !== 'custom') {
            matched = allVehiclesList.filter(v => (v.vehiclename || '').trim().toLowerCase() === typeLower);
        }

        matched.forEach(v => {
            let plate = (v.vehicleno || '').trim().toUpperCase();
            if (plate) {
                let cap = parseInt(v.plantscapacity) || 0;
                let capText = cap > 0 ? ` [Cap: ${cap.toLocaleString()}]` : '';
                let ownerText = v.vehicleownername ? ` - ${v.vehicleownername}` : '';
                vList.append(`<option value="${plate}">${plate}${ownerText}${capText}</option>`);
            }
        });
    }

    // Add a new Vehicle Group card
    function addVehicleGroup(data) {
        vehicleGroupCounter++;
        let html = buildVehicleGroupHtml(vehicleGroupCounter, data);
        $("#vamVehicleGroupsContainer").append(html);
        let card = $(`#vam_group_${vehicleGroupCounter}`);

        let selType = card.find(".vam-group-vehicletype").val();
        filterGroupPlates(card, selType);

        renumberVehicleGroups();
        recalcAllocationBalance();

        if (data && (data.vehicleno || data.vehiclename || data.allocated_plants)) {
            recalcGroupCapacity(card);
        }
        return card;
    }

    // Renumber groups (Vehicle #1 (Primary), Vehicle #2, ...) & toggle Remove buttons
    function renumberVehicleGroups() {
        let cards = $(".vehicle-group-card");
        let count = cards.length;
        cards.each(function (i) {
            let num = i + 1;
            let title = num === 1 ? `Vehicle #1 (Primary)` : `Vehicle #${num}`;
            $(this).find(".group-title-text").text(title);
            if (count === 1) {
                $(this).find(".btn-remove-vehicle-group").hide();
            } else {
                $(this).find(".btn-remove-vehicle-group").show();
            }
        });
    }

    // Recalculate allocation balance across all vehicle groups
    function recalcAllocationBalance() {
        let totalOrderPlants = parseInt($("#vam_total_plants").val() || 0);
        let sumAllocated = 0;
        $(".vam-group-plants").each(function () {
            let v = parseInt($(this).val() || 0);
            if (v > 0) sumAllocated += v;
        });

        let fullOrderQty = totalOrderPlants;
        let dispQty = 0;
        if (currentVamSelectedOrder) {
            fullOrderQty = parseInt(currentVamSelectedOrder.total_order_qty || totalOrderPlants);
            dispQty = parseInt(currentVamSelectedOrder.total_dispatched_qty || 0);
        }

        if (dispQty > 0) {
            $("#vamSummaryTotalPlants").text(fullOrderQty.toLocaleString());
            $("#vamSummaryDispatchedPlants").text(dispQty.toLocaleString());
            $("#vamSummaryDispatchedBox").show();
            $("#vamSummaryToAllocatePlants").text(totalOrderPlants.toLocaleString());
            $("#vamSummaryToAllocateBox").show();
        } else {
            $("#vamSummaryTotalPlants").text(totalOrderPlants.toLocaleString());
            $("#vamSummaryDispatchedBox").hide();
            $("#vamSummaryToAllocateBox").hide();
        }
        $("#vamSummaryAllocatedPlants").text(sumAllocated.toLocaleString());

        let badge = $("#vamBalanceStatusBadge");
        let btnAdd = $("#btnAddVehicleGroup");
        let remain = Math.max(0, totalOrderPlants - sumAllocated);

        if (sumAllocated === totalOrderPlants && totalOrderPlants > 0) {
            badge.attr("class", "badge bg-success py-1.5 px-2.5").html(`<i class="fa fa-check-circle me-1"></i> Balanced (100% Assigned)`);
            btnAdd.html(`<i class="fa fa-plus-circle me-1"></i> Add Another Vehicle / Group`);
        } else if (sumAllocated < totalOrderPlants) {
            badge.attr("class", "badge bg-warning text-dark py-1.5 px-2.5").html(`<i class="fa fa-clock me-1"></i> Incomplete (${remain.toLocaleString()} plants unassigned)`);
            btnAdd.html(`<i class="fa fa-plus-circle me-1"></i> Add Another Vehicle (${remain.toLocaleString()} plants remaining)`);
        } else {
            let over = sumAllocated - totalOrderPlants;
            badge.attr("class", "badge bg-danger py-1.5 px-2.5").html(`<i class="fa fa-exclamation-triangle me-1"></i> Over-Allocated (+${over.toLocaleString()} plants above order)`);
            btnAdd.html(`<i class="fa fa-plus-circle me-1"></i> Add Another Vehicle / Group`);
        }

        // Keep every vehicle card's remaining unassigned split banner in sync with live balance
        $(".vehicle-group-card").each(function () {
            let cardRemain = $(this).find(".vam-split-remainder-notice");
            if (cardRemain.length > 0) {
                if (remain > 0) {
                    cardRemain.html(`
                        <div class="small text-dark">
                            <i class="fa fa-info-circle text-primary me-1"></i> <b>${remain.toLocaleString()} plants</b> still unassigned for this order.
                        </div>
                        <button type="button" class="btn btn-sm btn-primary py-0.5 px-2 shadow-sm btn-add-vehicle-split" style="font-size: 11px;">
                            <i class="fa fa-plus-circle me-1"></i> Add Vehicle for Remaining ${remain.toLocaleString()} Plants
                        </button>
                    `).show();
                } else {
                    cardRemain.hide().empty();
                }
            }
        });
    }

    // Recalculate live capacity for an individual Vehicle Group card (Debounced & Cancellable)
    function recalcGroupCapacity(card) {
        if (!card || card.length === 0) return;

        // Cancel any pending timer for this card
        let existingTimer = card.data("recalcTimer");
        if (existingTimer) {
            clearTimeout(existingTimer);
        }

        // Cancel any in-flight AJAX request for this card
        let existingXhr = card.data("pendingXhr");
        if (existingXhr && existingXhr.readyState !== 4) {
            existingXhr.abort();
        }

        let timer = setTimeout(function () {
            executeGroupCapacityCheck(card);
        }, 80);
        card.data("recalcTimer", timer);
    }

    function executeGroupCapacityCheck(card) {
        if (!card || card.length === 0) return;

        let selectedType = card.find(".vam-group-vehicletype").val();
        let typeName = selectedType;
        if (selectedType === 'custom') {
            typeName = card.find(".vam-custom-type-input").val().trim() || 'Custom';
        }
        let vehNo = card.find(".vam-group-vehicleno").val().trim().toUpperCase();
        let manualCap = parseInt(card.find(".vam-group-capacity").val() || 0);
        let groupPlants = parseInt(card.find(".vam-group-plants").val() || 0);
        let schedDate = $("#vam_scheduled_dispatch_date").val() || new Date().toISOString().split('T')[0];
        let sono = $("#vam_sono").val() || '';
        let psono = $("#vam_psono").val() || '';

        // If neither vehicle plate nor vehicle type with capacity is chosen, hide gauge
        if (!vehNo && !selectedType && manualCap <= 0) {
            card.find(".vam-group-capacity-notice-box").hide();
            return;
        }

        // If vehicle type is chosen (or manual capacity entered), but plate is not yet entered:
        if (!vehNo) {
            card.find(".vam-group-schedule-notice-box").hide();
            card.removeData("matchedVehicle");
            let ratedCap = manualCap;
            if (ratedCap <= 0 && selectedType) {
                let opt = card.find(".vam-group-vehicletype option:selected");
                ratedCap = parseInt(opt.data("capacity") || 0);
                if (ratedCap > 0) {
                    card.find(".vam-group-capacity").val(ratedCap);
                }
            }
            let instantData = {
                plantscapacity: ratedCap,
                already_allocated: 0,
                allocated_orders_count: 0
            };
            renderGroupCapacityAnalysis(card, instantData, groupPlants, ratedCap, typeName || 'Selected Vehicle Type');
            return;
        }

        // If we have cached matchedVehicle data for this plate, update notice and prompt instantly
        let cached = card.data("matchedVehicle");
        if (cached && (cached.vehicleno || '').replace(/[\s\-\/]/g, '').toUpperCase() === vehNo) {
            renderVehicleScheduleNotice(card, cached, schedDate);
        }

        // Plate is entered -> Call server to check date capacity & multi-order load on that day
        let xhr = $.ajax({
            url: "modal/vehicle_allocation_modal.php?action=CheckVehicleDateCapacity",
            type: "POST",
            data: {
                vehicleno: vehNo,
                deliverydate: schedDate,
                exclude_sono: sono,
                exclude_psono: psono
            },
            dataType: "json",
            success: function (res) {
                // Ensure card still exists and plate has not changed while request was in-flight
                let currentPlate = card.find(".vam-group-vehicleno").val().trim().toUpperCase();
                if (currentPlate !== vehNo) {
                    return; // Discard stale response
                }

                if (res.status === "success") {
                    card.data("matchedVehicle", res);

                    // Autofill Owner / Transporter Name from server if available
                    if (res.vehicleownername) {
                        card.find(".vam-group-owner").val(res.vehicleownername);
                    }

                    // Render Dedicated Already Scheduled Notice Box
                    renderVehicleScheduleNotice(card, res, schedDate);

                    // Read latest input values from DOM without auto-overwriting user inputs
                    let curPlants = parseInt(card.find(".vam-group-plants").val() || 0);
                    let curCap = parseInt(card.find(".vam-group-capacity").val() || 0);
                    if (curCap <= 0 && selectedType) {
                        let opt = card.find(".vam-group-vehicletype option:selected");
                        curCap = parseInt(opt.data("capacity") || 0);
                    }
                    let curType = card.find(".vam-group-vehicletype").val() || typeName || 'Vehicle';

                    renderGroupCapacityAnalysis(card, res, curPlants, curCap, curType);
                }
            }
        });
        card.data("pendingXhr", xhr);
    }

    function renderVehicleScheduleNotice(card, data, schedDate) {
        let box = card.find(".vam-group-schedule-notice-box");
        let schedCard = box.find(".vam-schedule-card");
        let badgeEl = box.find(".vam-schedule-badge");
        let tripRefEl = box.find(".vam-schedule-trip-ref");
        let dateRefEl = box.find(".vam-schedule-date-ref");
        let summaryEl = box.find(".vam-schedule-summary-text");
        let typePrompt = box.find(".vam-schedule-type-prompt");

        let vehNo = (card.find(".vam-group-vehicleno").val() || '').replace(/[\s\-\/]/g, '').toUpperCase();
        if (!vehNo || vehNo.length < 3) {
            box.hide();
            return;
        }

        let alreadyAlloc = parseInt(data.already_allocated || 0);
        let orderCount = parseInt(data.allocated_orders_count || 0);
        let tripNos = data.trip_nos || '';
        let regType = data.vehiclename || '';
        let regCap = parseInt(data.plantscapacity || 0);
        let currentType = card.find(".vam-group-vehicletype").val() || '';

        dateRefEl.html(`<i class="fa fa-calendar-day me-1"></i>Date: <b>${schedDate || 'Selected Date'}</b>`);

        if (alreadyAlloc > 0) {
            schedCard.removeClass("vam-schedule-free").addClass("vam-schedule-busy");
            badgeEl.html(`
                <span class="badge bg-warning text-dark px-2 py-1 border border-warning-subtle" style="font-size: 11.5px;">
                    <i class="fa fa-exclamation-triangle me-1 text-danger"></i> Already Scheduled on this Date
                </span>
            `);
            let tripText = tripNos ? `Trip: <b class="text-dark">${tripNos}</b>` : (orderCount > 0 ? `<b class="text-dark">${orderCount} order(s)</b>` : '');
            tripRefEl.html(tripText);
            summaryEl.html(`
                <span class="text-dark">Vehicle <b class="font-monospace text-primary">${vehNo}</b> has already been assigned <b class="text-danger">${alreadyAlloc.toLocaleString()} plants</b> ${orderCount > 0 ? `across <b>${orderCount} orders</b> ` : ''}for <b>${schedDate}</b>.</span>
            `);
        } else {
            schedCard.removeClass("vam-schedule-busy").addClass("vam-schedule-free");
            badgeEl.html(`
                <span class="badge bg-success bg-opacity-10 text-success px-2 py-1 border border-success-subtle" style="font-size: 11.5px;">
                    <i class="fa fa-check-circle me-1"></i> Vehicle Free (Not Scheduled on this Date)
                </span>
            `);
            tripRefEl.html(`<span class="text-muted"><i class="fa fa-info-circle me-1"></i>0 prior loads</span>`);
            summaryEl.html(`
                <span class="text-muted">Vehicle <b class="font-monospace text-dark">${vehNo}</b> is completely available on <b>${schedDate}</b>.</span>
            `);
        }

        // Check if vehicle has a registered type that differs from current selection
        let isDifferentType = regType && (!currentType || currentType.toLowerCase() !== regType.toLowerCase());
        if (isDifferentType) {
            let capText = regCap > 0 ? `${regCap.toLocaleString()} plants` : 'Not Set';
            typePrompt.html(`
                <div class="small text-dark">
                    <i class="fa fa-info-circle text-primary me-1"></i>
                    Registered vehicle type is <b class="vam-matched-type-name text-dark">${regType}</b> <span class="text-muted">(Rated: <span class="vam-matched-type-cap">${capText}</span>)</span>.
                    Do you want to update <b>Select Vehicle Type</b> to match?
                </div>
                <div class="d-flex gap-1">
                    <button type="button" class="btn btn-sm btn-primary py-0.5 px-2 btn-apply-matched-vehicle" style="font-size: 11px;">
                        <i class="fa fa-check me-1"></i> Yes, Change Vehicle Type
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary py-0.5 px-2 btn-dismiss-matched-vehicle" style="font-size: 11px;">
                        Keep Current
                    </button>
                </div>
            `).show();
        } else {
            typePrompt.hide().empty();
        }

        box.slideDown(150);
    }

    function renderGroupCapacityAnalysis(card, data, groupPlants, ratedCap, typeName) {
        let currentInputCap = parseInt(card.find(".vam-group-capacity").val() || 0);
        ratedCap = currentInputCap > 0 ? currentInputCap : parseInt(ratedCap || data.plantscapacity || 0);

        let alreadyAlloc = parseInt(data.already_allocated || 0);
        let orderCount = parseInt(data.allocated_orders_count || 0);
        let tripCount = parseInt(data.trips_count || 0);
        let tripNos = data.trip_nos || '';
        let totalLoad = alreadyAlloc + groupPlants;
        let box = card.find(".vam-group-capacity-notice-box");
        box.show();

        let statusPill = card.find(".vam-cap-status-pill");
        let remainTile = card.find(".vam-cap-tile-remain");

        card.find(".vam-group-cap-rated").html(ratedCap > 0 ? `${ratedCap.toLocaleString()} <small class="text-muted fw-normal">plants</small>` : "Unknown");

        if (alreadyAlloc > 0) {
            let tripSub = tripNos ? `Trip: ${tripNos}` : (orderCount > 0 ? `${orderCount} ord` : 'Scheduled');
            card.find(".vam-group-cap-already")
                .html(`${alreadyAlloc.toLocaleString()} <small class="text-muted fw-normal">plants</small> <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle py-0.5 px-1 ms-auto" style="font-size: 9.5px;">${tripSub}</span>`);
        } else {
            card.find(".vam-group-cap-already")
                .html(`0 <small class="text-muted fw-normal">plants</small> <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle py-0.5 px-1 ms-auto" style="font-size: 9.5px;">Not Scheduled</span>`);
        }

        card.find(".vam-group-cap-this").html(`${groupPlants.toLocaleString()} <small class="text-muted fw-normal">plants</small>`);
        card.find(".vam-group-cap-total").html(`${totalLoad.toLocaleString()} <small class="text-muted fw-normal">plants</small>`);

        let pBar = card.find(".vam-group-capacity-bar");
        let advice = card.find(".vam-group-capacity-advice");
        let remainText = card.find(".vam-group-cap-remain");

        if (ratedCap <= 0) {
            pBar.css({ width: "100%", "background-color": "#6c757d" });
            remainText.html(`<span class="text-secondary">-</span>`);
            remainTile.css({ "background-color": "#ffffff", "border-color": "#e2e8f0" });
            statusPill.html(`<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">Capacity Unrated</span>`);
            advice.hide();
            return;
        }

        let pct = Math.round((totalLoad / ratedCap) * 100);
        let barPct = Math.min(100, pct);
        pBar.css("width", barPct + "%");

        let scheduledContext = alreadyAlloc > 0
            ? ` (includes <b>${alreadyAlloc.toLocaleString()} plants</b> already scheduled on ${tripNos ? 'Trip: ' + tripNos : 'this date'})`
            : ` (Vehicle is completely free on this date)`;

        let totalOrderPlants = parseInt($("#vam_total_plants").val() || 0);
        let sumAllocated = 0;
        $(".vam-group-plants").each(function () {
            let v = parseInt($(this).val() || 0);
            if (v > 0) sumAllocated += v;
        });
        let unassigned = Math.max(0, totalOrderPlants - sumAllocated);

        if (totalLoad <= ratedCap) {
            let remain = ratedCap - totalLoad;
            let barColor = pct > 85 ? "#f59e0b" : "#10b981";
            pBar.css("background-color", barColor);

            remainText.html(`<span class="text-success fw-bold">${remain.toLocaleString()} <small class="text-success fw-normal">free</small></span>`);
            remainTile.css({ "background-color": "#f0fdf4", "border-color": "#bbf7d0" });
            statusPill.html(`<span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-2 py-1" style="font-size: 11px;"><i class="fa fa-check-circle me-1"></i> ${pct}% Utilized (OK)</span>`);

            let unassignedBanner = unassigned > 0 ? `
                <div class="vam-split-remainder-notice mt-2 pt-2 border-top border-success-subtle d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="small text-dark">
                        <i class="fa fa-info-circle text-primary me-1"></i> <b>${unassigned.toLocaleString()} plants</b> still unassigned for this order.
                    </div>
                    <button type="button" class="btn btn-sm btn-primary py-0.5 px-2 shadow-sm btn-add-vehicle-split" style="font-size: 11px;">
                        <i class="fa fa-plus-circle me-1"></i> Add Vehicle for Remaining ${unassigned.toLocaleString()} Plants
                    </button>
                </div>
            ` : '';

            advice.attr("class", "vam-group-capacity-advice mt-2 small py-2 px-3 rounded bg-success bg-opacity-10 text-success border border-success-subtle").html(`
                <div><i class="fa fa-check-circle me-1"></i> <b>Capacity OK:</b> ${typeName || 'Vehicle'} can carry this load (<b>${totalLoad.toLocaleString()} / ${ratedCap.toLocaleString()} plants</b>, <b>${pct}%</b> filled)${scheduledContext}.</div>
                ${unassignedBanner}
            `).show();
        } else {
            let over = totalLoad - ratedCap;
            pBar.css("background-color", "#ef4444");

            remainText.html(`<span class="text-danger fw-bold">-${over.toLocaleString()} <small class="text-danger fw-normal">(Over)</small></span>`);
            remainTile.css({ "background-color": "#fef2f2", "border-color": "#fecaca" });
            statusPill.html(`<span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle px-2 py-1" style="font-size: 11px;"><i class="fa fa-exclamation-triangle me-1"></i> ${pct}% Over Capacity</span>`);

            advice.attr("class", "vam-group-capacity-advice mt-2 small py-2 px-3 rounded bg-danger bg-opacity-10 text-danger border border-danger-subtle").html(`
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <i class="fa fa-exclamation-circle me-1"></i> <b>Overcapacity Warning:</b> Total load (<b>${totalLoad.toLocaleString()} plants</b>) exceeds rated capacity (<b>${ratedCap.toLocaleString()} plants</b>) by <b class="text-danger">+${over.toLocaleString()} plants</b>${scheduledContext}.
                    </div>
                    <button type="button" class="btn btn-sm btn-danger py-0.5 px-2 shadow-sm btn-quick-cap-load" data-cap="${ratedCap}" style="font-size: 11px;">
                        <i class="fa fa-cut me-1"></i> Cap load to ${ratedCap.toLocaleString()} plants & split rest
                    </button>
                </div>
            `).show();
        }
    }

    // --- Dynamic Event Listeners for Vehicle Groups ---

    // 1. Allocated plants input change
    $(document).on("input change", ".vam-group-plants", function () {
        let card = $(this).closest(".vehicle-group-card");
        let val = parseInt($(this).val() || 0);
        let cap = parseInt(card.find(".vam-group-capacity").val() || 0);
        let helper = card.find(".vam-group-plants-helper");

        if (cap > 0 && val > cap) {
            helper.html(`
                <div class="text-danger small mt-1" style="font-size: 11px;">
                    <i class="fa fa-exclamation-triangle me-1"></i>Exceeds vehicle capacity (${cap.toLocaleString()} plants). 
                    <a href="#" class="text-danger fw-bold text-decoration-underline btn-quick-cap-load" data-cap="${cap}">Cap at ${cap.toLocaleString()}</a>
                </div>
            `).show();
        } else {
            helper.empty().hide();
        }

        recalcAllocationBalance();
        recalcGroupCapacity(card);
    });

    // 2. Vehicle type dropdown change
    $(document).on("change", ".vam-group-vehicletype", function () {
        let card = $(this).closest(".vehicle-group-card");
        let selectedType = $(this).val();
        let selectedOption = $(this).find("option:selected");

        // Clear data in second image fields (Registration No, Owner, Driver Name, Driver Mobile)
        card.find(".vam-group-vehicleno").val('');
        card.find(".group-plate-preview").text('Plate Unassigned');
        card.find(".vam-group-owner").val('').removeClass("is-invalid is-valid");
        card.find(".vam-group-driver").val('').removeClass("is-invalid is-valid");
        card.find(".vam-group-mobile").val('').removeClass("is-invalid is-valid");
        card.find(".vam-group-mobile-err").hide();
        card.find(".vam-vehicle-autocomplete-menu, .vam-driver-autocomplete-menu, .vam-mobile-autocomplete-menu").hide().empty();
        card.find(".vam-group-schedule-notice-box").hide();
        card.find(".vam-schedule-type-prompt").hide().empty();
        card.find(".vam-schedule-type-status").hide().empty();
        card.removeData("matchedVehicle");

        if (selectedType === 'custom') {
            card.find(".vam-custom-type-box").slideDown(150);
            card.find(".vam-custom-type-input").focus();
            filterGroupPlates(card, '');
            card.find(".vam-vehicle-autocomplete-menu").hide().empty();
        } else {
            card.find(".vam-custom-type-box").slideUp(150);
            card.find(".vam-custom-type-input").val('');

            if (selectedType) {
                let cap = parseInt(selectedOption.data("capacity") || 0);
                if (cap > 0) {
                    card.find(".vam-group-capacity").val(cap);

                    // Update Allocated Load to vehicle capacity or remaining order plants
                    let totalOrderPlants = parseInt($("#vam_total_plants").val() || 0);
                    let otherAllocated = 0;
                    $(".vehicle-group-card").not(card).find(".vam-group-plants").each(function () {
                        otherAllocated += parseInt($(this).val() || 0);
                    });
                    let availableForThisCard = Math.max(0, totalOrderPlants - otherAllocated);
                    let newLoad = Math.min(cap, availableForThisCard);
                    if (newLoad <= 0 && cap > 0) newLoad = cap;

                    card.find(".vam-group-plants").val(newLoad);
                    card.find(".vam-group-plants-helper").html(`
                        <div class="text-success small mt-1" style="font-size: 11px;">
                            <i class="fa fa-check-circle me-1"></i> Allocated load set to ${newLoad.toLocaleString()} plants (rated capacity: ${cap.toLocaleString()}).
                        </div>
                    `).show();
                    setTimeout(() => { card.find(".vam-group-plants-helper").slideUp(200); }, 3500);

                    recalcAllocationBalance();
                } else {
                    card.find(".vam-group-capacity").val('');
                }
                filterGroupPlates(card, selectedType);

                // Immediately show matching plates dropdown for this selected vehicle type
                let plateInput = card.find(".vam-group-vehicleno");
                renderVehiclePlateSuggestions(plateInput, plateInput.val());
            } else {
                card.find(".vam-group-capacity").val('');
                filterGroupPlates(card, '');
                card.find(".vam-vehicle-autocomplete-menu").hide().empty();
            }
        }

        // Clear previous update vehicle type notice when changing vehicle type from top
        card.find(".vam-schedule-type-status").hide().empty();

        recalcGroupCapacity(card);
        syncVamFreightVisibility();
        recalcFreightForVehicleType(card);
    });

    // 3. Custom vehicle type input
    $(document).on("input change", ".vam-custom-type-input", function () {
        let card = $(this).closest(".vehicle-group-card");
        recalcGroupCapacity(card);
        syncVamFreightVisibility();
        recalcFreightForVehicleType(card);
    });

    // 4. Vehicle Capacity manual input change
    $(document).on("input change", ".vam-group-capacity", function () {
        recalcGroupCapacity($(this).closest(".vehicle-group-card"));
    });

    // 5. Vehicle Registration Plate Live Suggestions & Auto-Save
    function renderVehiclePlateSuggestions(input, query) {
        let card = input.closest(".vehicle-group-card");
        let wrapper = input.closest(".vam-vehicle-plate-wrapper");
        let menu = wrapper.find(".vam-vehicle-autocomplete-menu");
        let cleanQ = (query || '').replace(/[\s\-\/]/g, '').toUpperCase();

        let selType = (card.find(".vam-group-vehicletype").val() || '').trim().toLowerCase();
        let selTypeName = (card.find(".vam-group-vehicletype option:selected").text() || '').trim();
        let pool = allVehiclesList || [];

        // If neither query nor vehicle type is present, don't open
        if (cleanQ.length < 1 && (!selType || selType === 'custom')) {
            menu.hide().empty();
            return;
        }

        menu.empty();

        let matches = [];
        let isFilteredByType = false;

        if (selType && selType !== 'custom') {
            isFilteredByType = true;
            // Strictly filter vehicles belonging to this vehicle type
            let typePool = pool.filter(v => (v.vehiclename || '').trim().toLowerCase() === selType);

            if (cleanQ.length > 0) {
                matches = typePool.filter(v => {
                    let pClean = (v.vehicleno || '').replace(/[\s\-\/]/g, '').toUpperCase();
                    let ownerMatch = (v.vehicleownername || '').toUpperCase().includes(cleanQ);
                    return pClean.includes(cleanQ) || ownerMatch;
                });
            } else {
                matches = typePool;
            }

            // If no vehicles registered under this vehicle type at all
            if (typePool.length === 0) {
                let emptyMsg = $(`
                    <div class="p-2.5 px-3 text-muted small bg-light d-flex align-items-center gap-2">
                        <i class="fa fa-info-circle text-primary"></i>
                        <span>No saved vehicles found under <b>${escapeHtml(selTypeName || selType)}</b>. Type to enter a new plate.</span>
                    </div>
                `);
                menu.append(emptyMsg).show();
                return;
            }
        } else {
            // No vehicle type selected: search across all vehicles by query
            matches = pool.filter(v => {
                let pClean = (v.vehicleno || '').replace(/[\s\-\/]/g, '').toUpperCase();
                let nameMatch = (v.vehiclename || '').toUpperCase().includes(cleanQ);
                let ownerMatch = (v.vehicleownername || '').toUpperCase().includes(cleanQ);
                let plateMatch = pClean.includes(cleanQ);
                return plateMatch || nameMatch || ownerMatch;
            });
        }

        // Limit results to 20
        let listToShow = matches.slice(0, 20);
        let exactMatch = pool.some(v => (v.vehicleno || '').replace(/[\s\-\/]/g, '').toUpperCase() === cleanQ);

        // Header for list if filtering by vehicle type
        if (isFilteredByType && listToShow.length > 0) {
            menu.append(`
                <div class="px-3 py-1.5 bg-light border-bottom text-muted small fw-bold d-flex justify-content-between align-items-center" style="font-size: 11px;">
                    <span><i class="fa fa-filter text-primary me-1"></i> Registered for ${escapeHtml(selTypeName || selType)}</span>
                    <span class="badge bg-white text-secondary border">${listToShow.length} plate${listToShow.length > 1 ? 's' : ''}</span>
                </div>
            `);
        }

        if (listToShow.length > 0) {
            listToShow.forEach((v, idx) => {
                let pClean = (v.vehicleno || '').replace(/[\s\-\/]/g, '').toUpperCase();
                let activeClass = idx === 0 ? 'vam-suggest-active' : '';
                let item = $(`
                    <div class="p-2 px-3 border-bottom vehicle-suggest-item ${activeClass}" style="cursor: pointer;"
                         data-plate="${escapeHtml(pClean)}"
                         data-type="${escapeHtml(v.vehiclename || '')}"
                         data-owner="${escapeHtml(v.vehicleownername || '')}"
                         data-cap="${v.plantscapacity || 0}">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fa fa-id-card text-primary me-1.5"></i>
                                <span class="font-monospace fw-bold text-dark fs-6">${escapeHtml(pClean)}</span>
                                ${v.vehiclename ? `<span class="badge bg-light text-primary border border-primary-subtle ms-2">${escapeHtml(v.vehiclename)}</span>` : ''}
                            </div>
                            <div class="text-end">
                                ${v.plantscapacity > 0 ? `<span class="badge bg-success bg-opacity-10 text-success border border-success-subtle">${parseInt(v.plantscapacity).toLocaleString()} cap</span>` : ''}
                            </div>
                        </div>
                        ${v.vehicleownername ? `<div class="text-muted small mt-0.5" style="font-size: 11px;"><i class="fa fa-user-tie text-secondary me-1"></i>${escapeHtml(v.vehicleownername)}</div>` : ''}
                    </div>
                `);

                item.on("mousedown", function (e) {
                    e.preventDefault();
                    input.val(pClean);
                    card.find(".group-plate-preview").text(pClean);
                    card.data("matchedVehicle", v);

                    // Autofill Owner / Transporter Name
                    if (v.vehicleownername) {
                        card.find(".vam-group-owner").val(v.vehicleownername);
                    }

                    // Auto-select vehicle type in dropdown if not yet selected
                    if (!card.find(".vam-group-vehicletype").val() && v.vehiclename) {
                        let opt = card.find(`.vam-group-vehicletype option[value="${v.vehiclename}"]`);
                        if (opt.length > 0) {
                            card.find(".vam-group-vehicletype").val(v.vehiclename);
                        }
                    }

                    // Auto-fill capacity if empty or zero
                    let curCap = parseInt(card.find(".vam-group-capacity").val() || 0);
                    if (curCap <= 0 && v.plantscapacity > 0) {
                        card.find(".vam-group-capacity").val(parseInt(v.plantscapacity));
                    }

                    menu.hide().empty();
                    recalcGroupCapacity(card);
                    syncVamFreightVisibility();
                    recalcFreightForVehicleType(card);
                });

                menu.append(item);
            });
        }

        // Show "New Registration" item if not existing and at least 3 chars
        if (!exactMatch && cleanQ.length >= 3) {
            let activeClass = listToShow.length === 0 ? 'vam-suggest-active' : '';
            let newItem = $(`
                <div class="p-2 px-3 text-muted small bg-light d-flex align-items-center justify-content-between vehicle-suggest-item vehicle-suggest-new ${activeClass}" style="cursor: pointer;">
                    <div>
                        <i class="fa fa-plus-circle text-primary me-1.5"></i>
                        New Registration: <b class="text-dark font-monospace">"${escapeHtml(cleanQ)}"</b>
                    </div>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle">
                        New Registration
                    </span>
                </div>
            `);

            newItem.on("mousedown", function (e) {
                e.preventDefault();
                input.val(cleanQ).trigger("input");
                card.find(".group-plate-preview").text(cleanQ);
                menu.hide().empty();
                recalcGroupCapacity(card);
                syncVamFreightVisibility();
            });

            menu.append(newItem);
        }

        if (menu.children().length > 0) {
            menu.show();
        } else {
            menu.hide();
        }
    }

    function quickSaveVehicleToMaster(card, plate) {
        // No-op: Vehicles are saved to Vehicle Master when user clicks "Confirm Vehicle Allocation"
    }

    // Vehicle Registration Plate focus/click event (immediately show suggestions if vehicle type selected)
    $(document).on("focus click", ".vam-group-vehicleno", function () {
        renderVehiclePlateSuggestions($(this), $(this).val());
    });

    // Vehicle Registration Plate input event (filters suggestions live)
    $(document).on("input", ".vam-group-vehicleno", function () {
        let card = $(this).closest(".vehicle-group-card");
        let raw = this.value;
        // Automatically ignore hyphens, slashes, and whitespace
        let clean = raw.replace(/[\s\-\/]/g, '').toUpperCase();
        if (raw !== clean) {
            this.value = clean;
        }

        card.find(".group-plate-preview").text(clean ? clean : 'Plate Unassigned');

        let wrapper = $(this).closest(".vam-vehicle-plate-wrapper");

        if (!clean) {
            let opt = card.find(".vam-group-vehicletype option:selected");
            let cap = parseInt(opt.data("capacity") || 0);
            card.find(".vam-group-capacity").val(cap > 0 ? cap : '');
            card.find(".vam-group-owner").val('');
            card.find(".vam-schedule-type-prompt").hide().empty();
            card.find(".vam-schedule-type-status").hide().empty();
            recalcGroupCapacity(card);
            renderVehiclePlateSuggestions($(this), '');
        } else {
            card.find(".vam-schedule-type-status").hide().empty();
            // If plate matches known vehicle, autofill Owner / Transporter Name & vehicle type if empty
            let match = suggestionVehiclesMap[clean] || (allVehiclesList || []).find(v => (v.vehicleno || '').replace(/[\s\-\/]/g, '').toUpperCase() === clean);
            if (match) {
                if (match.vehicleownername && !card.find(".vam-group-owner").val()) {
                    card.find(".vam-group-owner").val(match.vehicleownername);
                }
                if (!card.find(".vam-group-vehicletype").val() && match.vehiclename) {
                    let opt = card.find(`.vam-group-vehicletype option[value="${match.vehiclename}"]`);
                    if (opt.length > 0) {
                        card.find(".vam-group-vehicletype").val(match.vehiclename);
                    }
                }
            }
            recalcGroupCapacity(card);
            syncVamFreightVisibility();
            renderVehiclePlateSuggestions($(this), clean);
        }
    });

    // Format vehicle plate on blur without inserting into DB
    $(document).on("blur change", ".vam-group-vehicleno", function () {
        let plate = this.value.replace(/[\s\-\/]/g, '').toUpperCase();
        this.value = plate;
        let card = $(this).closest(".vehicle-group-card");
        if (plate) {
            let match = suggestionVehiclesMap[plate] || (allVehiclesList || []).find(v => (v.vehicleno || '').replace(/[\s\-\/]/g, '').toUpperCase() === plate);
            if (match) {
                if (match.vehicleownername && !card.find(".vam-group-owner").val()) {
                    card.find(".vam-group-owner").val(match.vehicleownername);
                }
                if (!card.find(".vam-group-vehicletype").val() && match.vehiclename) {
                    let opt = card.find(`.vam-group-vehicletype option[value="${match.vehiclename}"]`);
                    if (opt.length > 0) {
                        card.find(".vam-group-vehicletype").val(match.vehiclename);
                    }
                }
            }
            syncVamFreightVisibility();
            recalcFreightForVehicleType(card);
        } else {
            syncVamFreightVisibility();
        }
    });

    // Clear vehicle plate button
    $(document).on("click", ".btn-group-clear-vehicle", function () {
        let card = $(this).closest(".vehicle-group-card");
        let wrapper = $(this).closest(".vam-vehicle-plate-wrapper");
        wrapper.find(".vam-group-vehicleno").val('').focus();
        wrapper.find(".vam-vehicle-autocomplete-menu").hide().empty();
        card.find(".group-plate-preview").text('Plate Unassigned');
        card.find(".vam-group-schedule-notice-box").hide();
        card.removeData("matchedVehicle");
        card.find(".vam-group-owner").val('');
        card.find(".vam-schedule-type-prompt").hide().empty();
        card.find(".vam-schedule-type-status").hide().empty();
        let opt = card.find(".vam-group-vehicletype option:selected");
        let cap = parseInt(opt.data("capacity") || 0);
        card.find(".vam-group-capacity").val(cap > 0 ? cap : '');
        recalcGroupCapacity(card);
        syncVamFreightVisibility();
    });

    // User agrees to apply matched vehicle type & capacity
    $(document).on("click", ".btn-apply-matched-vehicle", function (e) {
        e.preventDefault();
        let card = $(this).closest(".vehicle-group-card");
        let matched = card.data("matchedVehicle") || {};
        let regType = matched.vehiclename || '';
        let regCap = parseInt(matched.plantscapacity || 0);
        let regOwner = matched.vehicleownername || '';

        if (regType) {
            let opt = card.find(`.vam-group-vehicletype option[value="${regType}"]`);
            if (opt.length > 0) {
                card.find(".vam-group-vehicletype").val(regType);
                card.find(".vam-custom-type-box").slideUp(100);
            } else {
                card.find(".vam-group-vehicletype").val('custom');
                card.find(".vam-custom-type-box").slideDown(100);
                card.find(".vam-custom-type-input").val(regType);
            }
        }
        if (regCap > 0) {
            card.find(".vam-group-capacity").val(regCap);

            let totalOrderPlants = parseInt($("#vam_total_plants").val() || 0);
            let otherAllocated = 0;
            $(".vehicle-group-card").not(card).find(".vam-group-plants").each(function () {
                otherAllocated += parseInt($(this).val() || 0);
            });
            let availableForThisCard = Math.max(0, totalOrderPlants - otherAllocated);
            let newLoad = Math.min(regCap, availableForThisCard);
            if (newLoad <= 0 && regCap > 0) newLoad = regCap;

            card.find(".vam-group-plants").val(newLoad);
            card.find(".vam-group-plants-helper").html(`
                <div class="text-success small mt-1" style="font-size: 11px;">
                    <i class="fa fa-check-circle me-1"></i> Allocated load updated to ${newLoad.toLocaleString()} plants.
                </div>
            `).show();
            setTimeout(() => { card.find(".vam-group-plants-helper").slideUp(200); }, 3500);
            recalcAllocationBalance();
        }
        if (regOwner && !card.find(".vam-group-owner").val().trim()) {
            card.find(".vam-group-owner").val(regOwner);
        }

        // Hide prompt cleanly
        card.find(".vam-schedule-type-prompt").hide().empty();

        // Show update confirmation in status box
        let statusBox = card.find(".vam-schedule-type-status");
        statusBox.html(`
            <div class="small text-success fw-bold py-1">
                <i class="fa fa-check-circle me-1"></i> Updated Vehicle Type to <u>${regType}</u> (${regCap.toLocaleString()} plants capacity).
            </div>
        `).slideDown(150);

        setTimeout(() => {
            statusBox.slideUp(200, function () { $(this).empty(); });
        }, 2500);

        recalcGroupCapacity(card);
        syncVamFreightVisibility();
        recalcFreightForVehicleType(card);
    });

    // User dismisses matched vehicle type prompt
    $(document).on("click", ".btn-dismiss-matched-vehicle", function (e) {
        e.preventDefault();
        $(this).closest(".vam-schedule-type-prompt").slideUp(150);
    });

    // Quick-cap load button click
    $(document).on("click", ".btn-quick-cap-load", function (e) {
        e.preventDefault();
        let card = $(this).closest(".vehicle-group-card");
        let cap = parseInt($(this).data("cap") || 0);
        if (cap > 0) {
            card.find(".vam-group-plants").val(cap).trigger("input");
        }
    });

    // Add vehicle for split remainder button click
    $(document).on("click", ".btn-add-vehicle-split", function (e) {
        e.preventDefault();
        $("#btnAddVehicleGroup").trigger("click");
    });

    // 7. Driver Name & Mobile Live Suggestions & Auto-Save
    function renderDriverNameSuggestions(input, query) {
        let card = input.closest(".vehicle-group-card");
        let wrapper = input.closest(".vam-driver-name-wrapper");
        let menu = wrapper.find(".vam-driver-autocomplete-menu");
        let qLower = (query || '').trim().toLowerCase();

        if (qLower.length < 1) {
            menu.hide().empty();
            return;
        }

        // Filter from in-memory cache of drivers (matches name or mobile)
        let matches = suggestionDriversList.filter(d => {
            let n = (d.drivername || '').toLowerCase();
            let m = (d.drivermobileno || '').toLowerCase();
            return n.includes(qLower) || m.includes(qLower);
        });

        menu.empty();

        if (matches.length > 0) {
            matches.slice(0, 10).forEach((d, idx) => {
                let dName = (d.drivername || '').trim();
                let dMobile = (d.drivermobileno || '').trim();
                let activeClass = idx === 0 ? 'vam-suggest-active' : '';
                let item = $(`
                    <div class="driver-suggest-item p-2 px-3 border-bottom d-flex justify-content-between align-items-center ${activeClass}" role="button" style="cursor: pointer; transition: background 0.15s;">
                        <div>
                            <span class="fw-bold text-dark"><i class="fa fa-user text-primary me-2"></i>${dName}</span>
                        </div>
                        <span class="badge bg-light text-secondary border font-monospace">
                            <i class="fa fa-phone me-1 text-muted"></i>${dMobile ? dMobile : 'No Mobile'}
                        </span>
                    </div>
                `);
                item.on("mousedown", function (e) {
                    e.preventDefault();
                    input.val(dName);
                    wrapper.find(".btn-clear-driver").show();
                    menu.hide().empty();

                    let mobInput = card.find(".vam-group-mobile");
                    let mobWrapper = card.find(".vam-driver-mobile-wrapper");
                    if (dMobile) {
                        mobInput.val(dMobile).trigger("input");
                        mobWrapper.find(".btn-clear-mobile").show();
                    } else {
                        mobInput.focus();
                    }
                });
                menu.append(item);
            });
        }

        // If query does not match any existing driver, show prompt that it will be saved
        let exactMatch = suggestionDriversList.some(d => (d.drivername || '').trim().toLowerCase() === qLower);
        if (!exactMatch && query.trim().length >= 1) {
            let activeClass = matches.length === 0 ? 'vam-suggest-active' : '';
            let newItem = $(`
                <div class="driver-suggest-item driver-suggest-new p-2 px-3 text-muted small bg-light d-flex align-items-center justify-content-between ${activeClass}" role="button" style="cursor: pointer;">
                    <div>
                        <i class="fa fa-user-plus text-success me-1.5"></i>
                        New Driver: <b class="text-dark">"${query.trim()}"</b>
                    </div>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle">
                        Will save to Driver Master
                    </span>
                </div>
            `);
            newItem.on("mousedown", function (e) {
                e.preventDefault();
                input.val(query.trim());
                wrapper.find(".btn-clear-driver").show();
                menu.hide().empty();
                card.find(".vam-group-mobile").focus();
            });
            menu.append(newItem);
        }

        menu.show();
    }

    function renderDriverMobileSuggestions(input, query) {
        let card = input.closest(".vehicle-group-card");
        let wrapper = input.closest(".vam-driver-mobile-wrapper");
        let menu = wrapper.find(".vam-mobile-autocomplete-menu");
        let qDigits = (query || '').replace(/\D/g, '');

        if (qDigits.length < 1) {
            menu.hide().empty();
            return;
        }

        // Filter drivers who have mobile numbers containing the typed digits
        let matches = suggestionDriversList.filter(d => {
            let m = (d.drivermobileno || '').replace(/\D/g, '');
            return m.includes(qDigits) && m.length > 0;
        });

        menu.empty();

        if (matches.length > 0) {
            matches.slice(0, 8).forEach((d, idx) => {
                let dName = (d.drivername || '').trim();
                let dMobile = (d.drivermobileno || '').trim();
                let activeClass = idx === 0 ? 'vam-suggest-active' : '';
                let item = $(`
                    <div class="mobile-suggest-item p-2 px-3 border-bottom d-flex justify-content-between align-items-center ${activeClass}" role="button" style="cursor: pointer; transition: background 0.15s;">
                        <div>
                            <span class="fw-bold text-dark font-monospace"><i class="fa fa-phone text-success me-2"></i>${dMobile}</span>
                        </div>
                        <span class="badge bg-light text-primary border">
                            <i class="fa fa-user me-1 text-muted"></i>${dName}
                        </span>
                    </div>
                `);
                item.on("mousedown", function (e) {
                    e.preventDefault();
                    input.val(dMobile).trigger("input");
                    wrapper.find(".btn-clear-mobile").show();
                    menu.hide().empty();

                    let nameInput = card.find(".vam-group-driver");
                    let nameWrapper = card.find(".vam-driver-name-wrapper");
                    if (dName) {
                        nameInput.val(dName);
                        nameWrapper.find(".btn-clear-driver").show();
                    }
                });
                menu.append(item);
            });
            menu.show();
        } else {
            menu.hide().empty();
        }
    }

    function quickSaveDriverToMaster(name, mobile) {
        // No-op: Drivers are saved to Driver Master when user clicks "Confirm Vehicle Allocation"
    }

    // Driver Name input event (only text, no numbers, and show dropdown on typing)
    $(document).on("input", ".vam-group-driver", function () {
        this.value = this.value.replace(/[0-9]/g, '');
        let val = $(this).val();
        renderDriverNameSuggestions($(this), val);
    });

    $(document).on("keypress", ".vam-group-driver", function (e) {
        if (/[0-9]/.test(e.key)) {
            e.preventDefault();
            return false;
        }
    });

    $(document).on("paste", ".vam-group-driver", function (e) {
        let clipboardData = e.originalEvent.clipboardData || window.clipboardData;
        if (clipboardData) {
            let pasted = clipboardData.getData('text');
            if (/[0-9]/.test(pasted)) {
                e.preventDefault();
                let clean = pasted.replace(/[0-9]/g, '');
                document.execCommand('insertText', false, clean);
            }
        }
    });

    // Driver Mobile input event (only show dropdown on typing)
    $(document).on("input", ".vam-group-mobile", function () {
        let card = $(this).closest(".vehicle-group-card");
        this.value = this.value.replace(/\D/g, '').slice(0, 10);
        let val = this.value.trim();

        let err = card.find(".vam-group-mobile-err");
        if (val.length === 10) {
            if (/^[6-9]\d{9}$/.test(val)) {
                $(this).removeClass("is-invalid").addClass("is-valid");
                err.hide();
            } else {
                $(this).removeClass("is-valid").addClass("is-invalid");
                err.show();
            }
        } else if (val.length > 0) {
            $(this).removeClass("is-valid").addClass("is-invalid");
            err.show();
        } else {
            $(this).removeClass("is-invalid is-valid");
            err.hide();
        }

        renderDriverMobileSuggestions($(this), val);
    });

    // Helper to handle keyboard navigation (ArrowUp, ArrowDown, Enter, Escape) for autocomplete menus
    function handleAutocompleteKeydown(e, inputEl, wrapperSelector, menuSelector, itemSelector) {
        let wrapper = inputEl.closest(wrapperSelector);
        let menu = wrapper.find(menuSelector);
        let items = menu.find(itemSelector);

        if (!menu.is(":visible") || items.length === 0) {
            if (e.key === "Enter") {
                e.preventDefault();
            }
            return;
        }

        if (e.key === "ArrowDown") {
            e.preventDefault();
            let cur = items.filter(".vam-suggest-active");
            let next = cur.length ? cur.nextAll(itemSelector).first() : items.first();
            if (!next.length) next = items.first();
            items.removeClass("vam-suggest-active");
            next.addClass("vam-suggest-active");
            if (next[0]) next[0].scrollIntoView({ block: "nearest" });
        } else if (e.key === "ArrowUp") {
            e.preventDefault();
            let cur = items.filter(".vam-suggest-active");
            let prev = cur.length ? cur.prevAll(itemSelector).first() : items.last();
            if (!prev.length) prev = items.last();
            items.removeClass("vam-suggest-active");
            prev.addClass("vam-suggest-active");
            if (prev[0]) prev[0].scrollIntoView({ block: "nearest" });
        } else if (e.key === "Enter") {
            e.preventDefault();
            e.stopPropagation();
            let active = items.filter(".vam-suggest-active");
            if (!active.length) {
                active = items.first();
            }
            if (active.length) {
                active.trigger("mousedown");
            }
        } else if (e.key === "Escape") {
            menu.hide().empty();
        }
    }

    // Driver Full Name keyboard navigation
    $(document).on("keydown", ".vam-group-driver", function (e) {
        handleAutocompleteKeydown(e, $(this), ".vam-driver-name-wrapper", ".vam-driver-autocomplete-menu", ".driver-suggest-item");
    });

    // Driver Mobile No keyboard navigation
    $(document).on("keydown", ".vam-group-mobile", function (e) {
        handleAutocompleteKeydown(e, $(this), ".vam-driver-mobile-wrapper", ".vam-mobile-autocomplete-menu", ".mobile-suggest-item");
    });

    // Vehicle Registration No keyboard navigation
    $(document).on("keydown", ".vam-group-vehicleno", function (e) {
        handleAutocompleteKeydown(e, $(this), ".vam-vehicle-plate-wrapper", ".vam-vehicle-autocomplete-menu", ".vehicle-suggest-item");
    });

    // Hover sync with keyboard active highlight
    $(document).on("mouseenter", ".driver-suggest-item, .vehicle-suggest-item, .mobile-suggest-item", function () {
        $(this).siblings().removeClass("vam-suggest-active");
        $(this).addClass("vam-suggest-active");
    });

    // Clear owner button
    $(document).on("click", ".btn-clear-owner", function () {
        let card = $(this).closest(".vehicle-group-card");
        card.find(".vam-group-owner").val('').trigger("input").focus();
    });

    // Clear driver name button
    $(document).on("click", ".btn-clear-driver", function () {
        let wrapper = $(this).closest(".vam-driver-name-wrapper");
        wrapper.find(".vam-group-driver").val('').trigger("input").focus();
        wrapper.find(".vam-driver-autocomplete-menu").hide().empty();
    });

    // Clear driver mobile button
    $(document).on("click", ".btn-clear-mobile", function () {
        let card = $(this).closest(".vehicle-group-card");
        let wrapper = $(this).closest(".vam-driver-mobile-wrapper");
        let mobInput = wrapper.find(".vam-group-mobile");
        mobInput.val('').removeClass("is-valid is-invalid").trigger("input").focus();
        wrapper.find(".vam-mobile-autocomplete-menu").hide().empty();
        card.find(".vam-group-mobile-err").hide();
    });

    // Close autocomplete menus when clicking outside
    $(document).on("click", function (e) {
        if (!$(e.target).closest(".vam-vehicle-plate-wrapper").length) {
            $(".vam-vehicle-autocomplete-menu").hide();
        }
        if (!$(e.target).closest(".vam-driver-name-wrapper").length) {
            $(".vam-driver-autocomplete-menu").hide();
        }
        if (!$(e.target).closest(".vam-driver-mobile-wrapper").length) {
            $(".vam-mobile-autocomplete-menu").hide();
        }
    });

    // 8. Remove Vehicle Group button
    $(document).on("click", ".btn-remove-vehicle-group", function () {
        let card = $(this).closest(".vehicle-group-card");
        let title = card.find(".group-title-text").text();
        card.remove();
        renumberVehicleGroups();
        recalcAllocationBalance();
        syncVamFreightVisibility();
        recalcFreightForVehicleType();
    });

    // 9. Add Another Vehicle Group button
    $("#btnAddVehicleGroup").on("click", function () {
        let totalOrderPlants = parseInt($("#vam_total_plants").val() || 0);
        let sumAllocated = 0;
        $(".vam-group-plants").each(function () {
            let v = parseInt($(this).val() || 0);
            if (v > 0) sumAllocated += v;
        });

        let remaining = Math.max(0, totalOrderPlants - sumAllocated);
        let newCard = addVehicleGroup({ allocated_plants: remaining > 0 ? remaining : '' });

        let modalBody = $("#vehicleAllocModal .modal-body");
        if (modalBody.length > 0 && newCard.length > 0) {
            modalBody.animate({
                scrollTop: modalBody.scrollTop() + (newCard.position() ? newCard.position().top : 200)
            }, 250);
        }
        newCard.find(".vam-group-vehicletype").focus();
    });

    // 10. Scheduled dispatch date changes update all cards' capacity calculations
    $("#vam_scheduled_dispatch_date").on("change", function () {
        $(".vehicle-group-card").each(function () {
            recalcGroupCapacity($(this));
        });
    });

    // Open Allocation Modal using array index
    $(document).on("click", ".open-vehicle-alloc-modal-btn", function (e) {
        e.preventDefault();
        let idx = $(this).data("index");
        let row = currentFilteredOrders[idx];
        if (!row) {
            Swal.fire("Error", "Order details could not be retrieved. Please refresh.", "error");
            return;
        }

        $("#vehicleAllocForm")[0].reset();
        $("#vamVehicleGroupsContainer").empty();
        vehicleGroupCounter = 0;

        $("#vam_projectid").val(projectid);
        $("#vam_sono").val(ordVal(row.sono));
        $("#vam_psono").val(ordVal(row.psono));
        $("#vam_clientid").val(ordVal(row.clientid));

        let totalPlants = parseInt(row.total_billable_qty || 0);
        let orderQty = parseInt(row.total_order_qty || totalPlants);
        let dispQty = parseInt(row.total_dispatched_qty || 0);
        $("#vam_total_plants").val(totalPlants);
        $("#vam_existing_trip_id").val(row.trip_id || row.trip_no || '');

        // Display Header Box
        $("#vam_display_client").text(row.clientname || 'Client');
        let destText = `${row.districtname || ''}, ${row.statename || ''} ${row.delivery_address ? '(' + row.delivery_address + ')' : ''}`.trim();
        if (row.mobileno) {
            destText += ` | Tel: ${row.mobileno}`;
        }
        $("#vam_display_destination").text(destText || '-');

        // Display Plant / Product Name & Badges
        let cleanProd = formatProductNameClean(row.clean_product_name || row.product_names || 'Crop');
        let isGrafted = isProductGrafted(row.product_names || '');
        $("#vam_display_product").html(`<i class="fa fa-seedling text-success me-1"></i><span title="${escapeHtml(cleanProd)}">${escapeHtml(cleanProd)}</span>`);

        let metaHtml = isGrafted ?
            '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-1.5 py-0.5" style="font-size: 10px;">Grafted</span>' :
            '<span class="badge bg-secondary-subtle text-secondary border px-1.5 py-0.5" style="font-size: 10px;">Non-Grafted</span>';
        if (row.stock_order_nos) {
            metaHtml += `<span class="text-muted small ms-1" style="font-size: 10.5px;">Stock: <b class="text-primary font-monospace">${escapeHtml(row.stock_order_nos)}</b></span>`;
        }
        $("#vam_display_product_meta").html(metaHtml);

        $("#vam_display_sono").text(`SO: ${row.sono || '-'} | PSONO: ${row.psono || '-'}`);
        let qtyHtml = `${totalPlants.toLocaleString()} Plants <span class="badge bg-success-subtle text-success ms-1">Ready for Vehicle</span>`;
        if (dispQty > 0) {
            qtyHtml += `<div class="small text-muted mt-0.5" style="font-size: 11px;">(Total Order: <b>${orderQty.toLocaleString()}</b> | Dispatched: <b class="text-danger">${dispQty.toLocaleString()}</b>)</div>`;
        }
        $("#vam_display_qty").html(qtyHtml);

        $("#vam_scheduled_dispatch_date").val(row.scheduled_dispatch_date || new Date().toISOString().split('T')[0]);
        $("#vam_remarks").val(row.vehicle_remarks || '');

        // Populate groups from existing multi-vehicle allocations or single allocation
        let allocatedList = Array.isArray(row.allocated_vehicles) && row.allocated_vehicles.length > 0 ? row.allocated_vehicles : null;
        if (!allocatedList && row.effective_vehicleno) {
            allocatedList = [{
                vehicleno: row.effective_vehicleno,
                vehiclename: row.effective_vehiclename || row.vehiclename || '',
                plantscapacity: row.plantscapacity || 0,
                vehicleownername: row.vehicleownername || '',
                drivername: row.effective_drivername || '',
                drivermobileno: row.effective_drivermobile || '',
                allocated_plants: totalPlants
            }];
        }

        if (allocatedList && allocatedList.length > 0) {
            let seenPlates = new Set();
            allocatedList.forEach(item => {
                let cleanP = (item.vehicleno || '').replace(/[^A-Za-z0-9]/g, '').toUpperCase();
                if (cleanP && seenPlates.has(cleanP)) {
                    return; // Skip duplicate vehicle card for identical vehicle
                }
                if (cleanP) seenPlates.add(cleanP);
                addVehicleGroup(item);
            });
        } else {
            // Fresh allocation: 1 group with total billable plants
            addVehicleGroup({ allocated_plants: totalPlants });
        }

        renderVamFreightRouteDetails(row);
        recalcAllocationBalance();
        syncVamFreightVisibility();
        recalcFreightForVehicleType();
        showAllocModal();
    });

    let currentVamSelectedOrder = null;

    /**
     * Check if vehicle details are filled (Vehicle Type or Registration Plate)
     * and toggle the visibility of the Freight Charge Details card accordingly.
     */
    function syncVamFreightVisibility() {
        let hasDetails = false;
        $(".vehicle-group-card").each(function () {
            let vType = ($(this).find(".vam-group-vehicletype").val() || '').trim();
            let customType = ($(this).find(".vam-custom-type-input").val() || '').trim();
            let plate = ($(this).find(".vam-group-vehicleno").val() || '').trim();
            if ((vType && vType !== 'custom') || (vType === 'custom' && customType) || plate) {
                hasDetails = true;
                return false;
            }
        });

        let freightSec = $("#vamFreightRouteSection");
        if (hasDetails) {
            if (!freightSec.is(":visible")) {
                freightSec.slideDown(250);
            }
            if (typeof updateVamFreightVehicleCell === 'function') {
                updateVamFreightVehicleCell();
            }
        } else {
            freightSec.hide();
        }
    }

    /**
     * Dynamically calculate freight charge from krf_freight_master based on selected vehicle type & distance
     */
    function recalcFreightForVehicleType(triggerCard) {
        if (!currentVamSelectedOrder) return;
        let rawType = (currentVamSelectedOrder.dl_pickuptype || '').trim();
        let normType = rawType.toLowerCase().replace(/[-_ ]/g, '');
        let isPickup = (normType === 'pickup' || normType === 'selfpickup' || normType === 'pickbyclient');
        if (isPickup) return;

        let card = (triggerCard && triggerCard.length) ? triggerCard : $(".vehicle-group-card:first");
        if (!card || card.length === 0) return;

        let selOpt = card.find(".vam-group-vehicletype option:selected");
        let vtName = (card.find(".vam-group-vehicletype").val() || '').trim();
        let vtId = parseInt(selOpt.data("id") || selOpt.attr("data-id") || 0);

        if (vtName === 'custom') {
            vtName = card.find(".vam-custom-type-input").val().trim();
        }
        if (!vtId && vtName && suggestionVehicleTypesMap[vtName.toLowerCase()]) {
            vtId = parseInt(suggestionVehicleTypesMap[vtName.toLowerCase()].id || 0);
        }

        let km = parseFloat(currentVamSelectedOrder.dl_effective_distance || currentVamSelectedOrder.dl_distance || currentVamSelectedOrder.dl_totaldistance || 0);
        if (km <= 0 || (!vtId && !vtName)) return;

        let projVal = (currentVamSelectedOrder && currentVamSelectedOrder.projectid)
            ? parseInt(currentVamSelectedOrder.projectid)
            : (parseInt(window.VA_PROJECT_ID) || parseInt(projectid) || 17);
        if (!projVal || projVal <= 0) projVal = 17;

        $.ajax({
            url: "modal/vehicle_allocation_modal.php?action=GetFreightByVehicleType",
            type: "POST",
            data: {
                vehicle_type_id: vtId,
                vehicle_type_name: vtName,
                distance: km,
                projectid: projVal,
                psono: currentVamSelectedOrder.psono || '',
                sono: currentVamSelectedOrder.sono || '',
                total_qty: currentVamSelectedOrder.total_billable_qty || currentVamSelectedOrder.total_order_qty || 0,
                pickuptype: currentVamSelectedOrder.dl_pickuptype || 'delivered'
            },
            dataType: "json",
            success: function (res) {
                if (res && res.status === "success" && res.base_freight !== undefined) {
                    let newBase = parseFloat(res.base_freight || 0);

                    // Rate doubling notice check
                    let isRateDbl = (res.is_return_journey || (res.calculationtype && res.calculationtype.toLowerCase() === 'rate'));
                    if (isRateDbl) {
                        $("#vam_spanratedbl").text("Freight is calculated for both onward and return journeys.");
                        $("#vam_spanratedbl_alert, #vam_spanratedbl_tfoot").removeClass("d-none");
                        $("#vam_spanratedbl_tfoot").text("Freight is calculated for both onward and return journeys.");
                    } else {
                        $("#vam_spanratedbl").text("");
                        $("#vam_spanratedbl_alert, #vam_spanratedbl_tfoot").addClass("d-none");
                        $("#vam_spanratedbl_tfoot").text("");
                    }

                    $(".vam_f_amt_input").val(newBase.toFixed(2));
                    $("#vam_tbl_base_freight_display").text(newBase.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

                    let prevDisc = parseFloat($(".vam_f_disc_amt_input").val() || 0);
                    let payable = Math.max(0, newBase - prevDisc);
                    $(".vam_f_payable_input").val(payable.toFixed(2));
                    $("#vam_tbl_payable_freight_display").text(payable.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

                    currentVamSelectedOrder.dl_base_freight = newBase;
                    currentVamSelectedOrder.dl_payable_freight = payable;
                    if (vtName && vtName !== 'custom') {
                        currentVamSelectedOrder.effective_vehiclename = vtName;
                        currentVamSelectedOrder.vehiclename = vtName;
                    }

                    // Evaluate automatic freight discount plan from freight_discount_module
                    applyVamFreightDiscount(newBase);

                    // Update vehicle cell in the freight details table live
                    if (typeof updateVamFreightVehicleCell === 'function') {
                        updateVamFreightVehicleCell();
                    }
                }
            }
        });
    }

    /**
     * Evaluate and apply freight discount matching addNewProvisionalsaleorder.php
     * Calls freight_discount_module/modal/freight_discount_modal.php?action=calculate_discount
     */
    function applyVamFreightDiscount(baseFreight, manualDiscPct, manualDiscAmt) {
        if (!currentVamSelectedOrder) return;

        let base = (baseFreight !== undefined && baseFreight !== null) ? parseFloat(baseFreight) : (parseFloat($(".vam_f_amt_input").val()) || 0);
        let km = parseFloat(currentVamSelectedOrder.dl_effective_distance || currentVamSelectedOrder.dl_distance || currentVamSelectedOrder.dl_totaldistance || 0);
        let totalQty = parseFloat(currentVamSelectedOrder.total_billable_qty || currentVamSelectedOrder.total_order_qty || 0);

        if (base <= 0) {
            $(".vam_f_disc_input").val("0");
            $(".vam_f_disc_amt_input").val("0.00");
            $(".vam_f_payable_input").val("0.00");
            $("#vam_tbl_base_freight_display").text("0.00");
            $("#vam_tbl_disc_freight_display").text("0.00");
            $("#vam_tbl_payable_freight_display").text("0.00");
            $("#vam_f_discount_banner").slideUp(200);
            return;
        }

        // If user manually entered discount percentage
        if (manualDiscPct !== undefined && manualDiscPct !== null) {
            let discPct = Math.min(100, Math.max(0, parseFloat(manualDiscPct) || 0));
            let discAmt = Math.round((base * (discPct / 100)) * 100) / 100;
            let payable = Math.max(0, Math.round((base - discAmt) * 100) / 100);

            $(".vam_f_disc_input").val(discPct);
            $(".vam_f_disc_amt_input").val(discAmt > 0 ? discAmt.toFixed(2) : "0.00");
            $(".vam_f_payable_input").val(payable.toFixed(2));

            $("#vam_tbl_base_freight_display").text(base.toFixed(2));
            $("#vam_tbl_disc_freight_display").text(discAmt > 0 ? ('- ' + discAmt.toFixed(2)) : '0.00');
            $("#vam_tbl_payable_freight_display").text(payable.toFixed(2));

            currentVamSelectedOrder.dl_base_freight = base;
            currentVamSelectedOrder.dl_payable_freight = payable;
            currentVamSelectedOrder.dl_discount_amount = discAmt;
            currentVamSelectedOrder.dl_discount_percent = discPct;

            if (discAmt > 0) {
                let discTitle = `Manual Freight Discount Applied: -₹${discAmt.toFixed(2)} (${discPct}% OFF)`;
                let discDesc = `Base freight ₹${base.toFixed(2)} discounted to net payable ₹${payable.toFixed(2)}.`;
                $("#vam_f_discount_title").text(discTitle);
                $("#vam_f_discount_desc").text(discDesc);
                $("#vam_f_discount_chip").text(`- ₹${discAmt.toFixed(2)}`);
                $("#vam_f_discount_banner").slideDown(200);
            } else {
                $("#vam_f_discount_banner").slideUp(200);
            }
            return;
        }

        // If user manually entered discount amount
        if (manualDiscAmt !== undefined && manualDiscAmt !== null) {
            let discAmt = Math.min(base, Math.max(0, parseFloat(manualDiscAmt) || 0));
            let discPct = base > 0 ? Math.round(((discAmt / base) * 100) * 100) / 100 : 0;
            let payable = Math.max(0, Math.round((base - discAmt) * 100) / 100);

            $(".vam_f_disc_input").val(discPct);
            $(".vam_f_disc_amt_input").val(discAmt > 0 ? discAmt.toFixed(2) : "0.00");
            $(".vam_f_payable_input").val(payable.toFixed(2));

            $("#vam_tbl_base_freight_display").text(base.toFixed(2));
            $("#vam_tbl_disc_freight_display").text(discAmt > 0 ? ('- ' + discAmt.toFixed(2)) : '0.00');
            $("#vam_tbl_payable_freight_display").text(payable.toFixed(2));

            currentVamSelectedOrder.dl_base_freight = base;
            currentVamSelectedOrder.dl_payable_freight = payable;
            currentVamSelectedOrder.dl_discount_amount = discAmt;
            currentVamSelectedOrder.dl_discount_percent = discPct;

            if (discAmt > 0) {
                let discTitle = `Manual Freight Discount Applied: -₹${discAmt.toFixed(2)} (${discPct}% OFF)`;
                let discDesc = `Base freight ₹${base.toFixed(2)} discounted to net payable ₹${payable.toFixed(2)}.`;
                $("#vam_f_discount_title").text(discTitle);
                $("#vam_f_discount_desc").text(discDesc);
                $("#vam_f_discount_chip").text(`- ₹${discAmt.toFixed(2)}`);
                $("#vam_f_discount_banner").slideDown(200);
            } else {
                $("#vam_f_discount_banner").slideUp(200);
            }
            return;
        }

        // Determine plant type
        let plantType = 'Grafted';
        if (Array.isArray(currentVamSelectedOrder.product_items) && currentVamSelectedOrder.product_items.length > 0) {
            let hasG = currentVamSelectedOrder.product_items.some(p => p.is_grafted);
            let hasNG = currentVamSelectedOrder.product_items.some(p => !p.is_grafted);
            if (hasG && hasNG) plantType = 'Both';
            else if (hasG) plantType = 'Grafted';
            else plantType = 'Nongrafted';
        } else if (currentVamSelectedOrder.is_grafted) {
            plantType = 'Grafted';
        } else {
            plantType = 'Nongrafted';
        }

        let customerType = currentVamSelectedOrder.customertype || currentVamSelectedOrder.customer_type || 'Farmer';
        let stateId = currentVamSelectedOrder.dl_stateid || currentVamSelectedOrder.stateid || 'ALL';
        let districtId = currentVamSelectedOrder.dl_districtid || currentVamSelectedOrder.districtid || 'ALL';
        let projId = (currentVamSelectedOrder && currentVamSelectedOrder.projectid)
            ? parseInt(currentVamSelectedOrder.projectid)
            : (parseInt(window.VA_PROJECT_ID) || parseInt(projectid) || 17);
        if (!projId || projId <= 0) projId = 17;

        // Call Freight Discount Module calculate_discount
        $.ajax({
            url: "../../freight_discount_module/modal/freight_discount_modal.php?action=calculate_discount",
            type: "POST",
            data: {
                customer_type: customerType,
                plant_type: plantType,
                order_qty: totalQty,
                freight_amount: base,
                distance_km: km,
                stateid: stateId,
                districtid: districtId,
                projectid: projId
            },
            dataType: "json",
            success: function (res) {
                if (res && res.status && res.data && res.data.has_discount) {
                    let discPct = parseFloat(res.data.discount_percent) || 0;
                    let discAmt = parseFloat(res.data.discount_amount) || 0;
                    let payable = parseFloat(res.data.payable_freight) || 0;

                    $(".vam_f_disc_input").val(discPct.toFixed(2));
                    $(".vam_f_disc_amt_input").val(discAmt > 0 ? discAmt.toFixed(2) : "0.00");
                    $(".vam_f_payable_input").val(payable.toFixed(2));

                    $("#vam_tbl_base_freight_display").text(base.toFixed(2));
                    $("#vam_tbl_disc_freight_display").text(discAmt > 0 ? ('- ' + discAmt.toFixed(2)) : '0.00');
                    $("#vam_tbl_payable_freight_display").text(payable.toFixed(2));

                    currentVamSelectedOrder.dl_base_freight = base;
                    currentVamSelectedOrder.dl_payable_freight = payable;
                    currentVamSelectedOrder.dl_discount_amount = discAmt;
                    currentVamSelectedOrder.dl_discount_percent = discPct;

                    let plan = res.data.applied_plan || {};
                    let planName = plan.plan_name || 'Freight Discount Plan';
                    let discTitle = `Freight Discount Plan Applied: ${planName} (-₹${discAmt.toFixed(2)})`;
                    let discDesc = `Base freight of ₹${base.toFixed(2)} discounted by ${discPct}% (-₹${discAmt.toFixed(2)}) to net payable ₹${payable.toFixed(2)}.`;
                    if (plan.min_distance_km && parseFloat(plan.min_distance_km) > 0) {
                        discDesc += ` (Free up to ${plan.min_distance_km} KM)`;
                    }

                    $("#vam_f_discount_title").text(discTitle);
                    $("#vam_f_discount_desc").text(discDesc);
                    $("#vam_f_discount_chip").text(`- ₹${discAmt.toFixed(2)} (${discPct}%)`);
                    $("#vam_f_discount_banner").slideDown(200);
                } else {
                    // Check if current order already has a saved discount percent/amount
                    let savedPct = parseFloat(currentVamSelectedOrder.dl_discount_percent || 0);
                    let savedAmt = parseFloat(currentVamSelectedOrder.dl_discount_amount || 0);
                    if (savedPct > 0 || savedAmt > 0) {
                        let discPct = savedPct;
                        let discAmt = savedAmt > 0 ? savedAmt : Math.round((base * (discPct / 100)) * 100) / 100;
                        let payable = Math.max(0, Math.round((base - discAmt) * 100) / 100);

                        $(".vam_f_disc_input").val(discPct.toFixed(2));
                        $(".vam_f_disc_amt_input").val(discAmt > 0 ? discAmt.toFixed(2) : "0.00");
                        $(".vam_f_payable_input").val(payable.toFixed(2));

                        $("#vam_tbl_base_freight_display").text(base.toFixed(2));
                        $("#vam_tbl_disc_freight_display").text(discAmt > 0 ? ('- ' + discAmt.toFixed(2)) : '0.00');
                        $("#vam_tbl_payable_freight_display").text(payable.toFixed(2));

                        currentVamSelectedOrder.dl_base_freight = base;
                        currentVamSelectedOrder.dl_payable_freight = payable;
                        currentVamSelectedOrder.dl_discount_amount = discAmt;
                        currentVamSelectedOrder.dl_discount_percent = discPct;

                        if (discAmt > 0) {
                            $("#vam_f_discount_title").text(`Freight Discount Applied: -₹${discAmt.toFixed(2)} (${discPct}%)`);
                            $("#vam_f_discount_desc").text(`Base freight of ₹${base.toFixed(2)} discounted to net payable ₹${payable.toFixed(2)}.`);
                            $("#vam_f_discount_chip").text(`- ₹${discAmt.toFixed(2)}`);
                            $("#vam_f_discount_banner").slideDown(200);
                        } else {
                            $("#vam_f_discount_banner").slideUp(200);
                        }
                    } else {
                        // No discount
                        $(".vam_f_disc_input").val("0");
                        $(".vam_f_disc_amt_input").val("0.00");
                        $(".vam_f_payable_input").val(base.toFixed(2));

                        $("#vam_tbl_base_freight_display").text(base.toFixed(2));
                        $("#vam_tbl_disc_freight_display").text("0.00");
                        $("#vam_tbl_payable_freight_display").text(base.toFixed(2));

                        currentVamSelectedOrder.dl_base_freight = base;
                        currentVamSelectedOrder.dl_payable_freight = base;
                        currentVamSelectedOrder.dl_discount_amount = 0;
                        currentVamSelectedOrder.dl_discount_percent = 0;

                        $("#vam_f_discount_banner").slideUp(200);
                    }
                }
            },
            error: function () {
                $(".vam_f_payable_input").val(base.toFixed(2));
                $("#vam_tbl_base_freight_display").text(base.toFixed(2));
                $("#vam_tbl_payable_freight_display").text(base.toFixed(2));
            }
        });
    }

    /**
     * Build HTML for the VEHICLE column in Freight Charge Details table matching user screenshot
     */
    function buildVamVehicleCellHtml(info) {
        if (info.isAllocated) {
            return `
                <div class="d-flex flex-column align-items-center gap-1 py-1">
                    <button type="button" class="btn btn-primary btn-sm rounded-circle vam-open-freight-calculator-btn shadow-xs mb-0.5" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;" title="Open Vehicle Freight Calculator">
                        <i class="fa fa-calculator text-white"></i>
                    </button>
                    ${info.vehType ? `<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5 fw-semibold" style="font-size: 11px;"><i class="fa fa-truck me-1"></i>${escapeHtml(info.vehType)}</span>` : ''}
                    ${info.vehNo ? `<span class="badge bg-white text-dark border font-monospace px-2 py-0.5 shadow-2xs" style="font-size: 11px; letter-spacing: 0.5px;">${escapeHtml(info.vehNo)}</span>` : ''}
                    ${info.owner ? `<div class="small text-truncate text-primary fw-semibold" style="font-size: 11px; max-width: 130px;" title="${escapeHtml(info.owner)}"><i class="fa fa-bookmark me-1 text-primary"></i>${escapeHtml(info.owner)}</div>` : ''}
                    ${info.driver ? `<div class="small text-truncate text-dark fw-semibold" style="font-size: 11px; max-width: 130px;" title="${escapeHtml(info.driver)}"><i class="fa fa-user me-1 text-secondary"></i>${escapeHtml(info.driver)}</div>` : ''}
                    ${info.tripId ? `<span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-1.5 py-0.5" style="font-size: 10px;" title="Trip ID"><i class="fa fa-route me-1"></i>${escapeHtml(info.tripId)}</span>` : ''}
                    <button type="button" class="btn btn-xs btn-outline-primary vam-scroll-to-assign-btn px-2.5 py-0.5 shadow-2xs mt-1" title="Edit Vehicle Allocation">
                        <i class="fa fa-edit me-1"></i> Edit
                    </button>
                </div>
            `;
        } else {
            return `
                <div class="d-flex flex-column align-items-center gap-1.5 py-1">
                    <button type="button" class="btn btn-outline-danger btn-sm rounded-circle vam-open-freight-calculator-btn shadow-xs mb-1" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;" title="Open Vehicle Freight Calculator">
                        <i class="fa fa-calculator"></i>
                    </button>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 mb-1" title="Vehicle not assigned yet">
                        <i class="fa fa-exclamation-triangle me-1"></i>Missing Vehicle
                    </span>
                    <button type="button" class="btn btn-xs btn-outline-secondary vam-scroll-to-assign-btn px-2.5 py-0.5 shadow-2xs" title="Assign Vehicle">
                        <i class="fa fa-truck me-1"></i> Assign
                    </button>
                </div>
            `;
        }
    }

    /**
     * Live sync VEHICLE table cell with vehicle details card inputs
     */
    function updateVamFreightVehicleCell() {
        let cell = $("#vamFreightTableBody .vam-vehicle-cell");
        if (!cell.length) return;

        let firstCard = $(".vehicle-group-card:first");
        let curVehType = '';
        if (firstCard.length > 0) {
            curVehType = (firstCard.find(".vam-group-vehicletype").val() || '').trim();
            if (curVehType === 'custom') {
                curVehType = (firstCard.find(".vam-custom-type-input").val() || '').trim();
            }
        }
        let curVehNo = firstCard.length ? (firstCard.find(".vam-group-vehicleno").val() || '').trim() : '';
        let curOwner = firstCard.length ? (firstCard.find(".vam-group-owner").val() || '').trim() : '';
        let curDriver = firstCard.length ? (firstCard.find(".vam-group-driver").val() || '').trim() : '';

        let row = currentVamSelectedOrder || {};
        let vehType = curVehType || row.effective_vehiclename || row.vehiclename || '';
        let vehNo = curVehNo || row.effective_vehicleno || row.vehicleno || '';
        let owner = curOwner || row.vehicleownername || row.owner_name || '';
        let driver = curDriver || row.effective_drivername || row.drivername || '';
        let tripId = row.trip_id || row.trip_no || '';

        if (!tripId && vehNo) {
            let dispDate = $("#vam_scheduled_dispatch_date").val() || row.scheduled_dispatch_date || new Date().toISOString().split('T')[0];
            let dClean = dispDate.replace(/[^0-9]/g, '');
            let cleanPlate = vehNo.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
            let plateSuffix = cleanPlate.length > 4 ? cleanPlate.slice(-4) : (cleanPlate || 'VEH');
            tripId = `TRIP-${dClean}-${plateSuffix}-1`;
        }

        let isVehicleAssigned = Boolean(vehNo || vehType || row.is_vehicle_allocated);
        let html = buildVamVehicleCellHtml({
            isAllocated: isVehicleAssigned,
            vehType: vehType,
            vehNo: vehNo,
            owner: owner,
            driver: driver,
            tripId: tripId
        });

        cell.html(html);
    }

    /**
     * Render Step 2-style Freight & Delivery Route Details in Vehicle Allocation Modal
     */
    function renderVamFreightRouteDetails(row) {
        if (!row) return;
        currentVamSelectedOrder = row;

        let rawType = (row.dl_pickuptype || '').trim();
        let normType = rawType.toLowerCase().replace(/[-_ ]/g, '');
        let isPickup = (normType === 'pickup' || normType === 'selfpickup' || normType === 'pickbyclient');
        let isBoxPacking = (normType === 'boxpacking' || normType === 'box');
        let isDelivered = !isPickup && !isBoxPacking;

        // 1. Delivery Mode: Always Farm Delivery in top header
        let modeBadgeHtml = `<span class="badge bg-primary text-white px-3 py-1.5 rounded-2 fw-bold shadow-2xs" style="font-size: 11.5px;"><i class="fa fa-truck me-1"></i> Farm Delivery</span>`;
        $("#vam_route_delivery_badge").html(modeBadgeHtml);

        // 3. Location Availability Check
        let locCity = row.dl_dest_city || '';
        let locDistrict = row.dl_dest_district || '';
        let locState = row.dl_dest_state || '';
        let locPincode = row.dl_dest_pincode || '';
        let locAddr = row.dl_deliveryaddress || '';
        let isLocAvailable = Boolean(locAddr || locCity || locDistrict || locState);

        // 4. Financials
        let baseFreight = 0;
        let payableFreight = 0;
        let discAmt = 0;
        let discPct = 0;
        let km = 0;

        if (isPickup) {
            baseFreight = 0;
            payableFreight = 0;
            discAmt = 0;
            discPct = 0;
            km = 0;
        } else if (isBoxPacking) {
            let boxPerPack = parseInt(row.dl_boxperpack || 1000);
            let chargesPerBox = parseFloat(row.dl_chargesperbox || 70.00);
            let noOfBoxes = parseInt(row.dl_noofbox || 1);
            baseFreight = Math.round((noOfBoxes * chargesPerBox) * 100) / 100;
            payableFreight = baseFreight;
            discAmt = 0;
            discPct = 0;
            km = 0;
        } else {
            if (isLocAvailable) {
                baseFreight = parseFloat(row.dl_base_freight || row.dl_deliverycharges || 0);
                payableFreight = parseFloat(row.dl_payable_freight || row.dl_freightamt || baseFreight);
                discAmt = parseFloat(row.dl_discount_amount || 0);
                discPct = parseFloat(row.dl_discount_percent || 0);
                km = parseFloat(row.dl_effective_distance || row.dl_distance || row.dl_totaldistance || 0);

                if (baseFreight <= 0 && km > 0 && !isPickup) {
                    if (row.recommended_freight && parseFloat(row.recommended_freight) > 0) {
                        baseFreight = parseFloat(row.recommended_freight);
                        payableFreight = baseFreight;
                    }
                }
            } else {
                baseFreight = 0;
                payableFreight = 0;
                discAmt = 0;
                discPct = 0;
                km = 0;
            }
        }

        // 5. Discount Banner
        if (discAmt > 0) {
            let discTitle = `Freight Discount Plan Applied: -₹${discAmt.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} (${discPct}% OFF)`;
            let discDesc = row.dl_remark ? escapeHtml(row.dl_remark) : `Base freight of ₹${baseFreight.toLocaleString(undefined, { minimumFractionDigits: 2 })} discounted to net payable ₹${payableFreight.toLocaleString(undefined, { minimumFractionDigits: 2 })}.`;
            $("#vam_f_discount_title").text(discTitle);
            $("#vam_f_discount_desc").html(discDesc);
            $("#vam_f_discount_chip").text(`- ₹${discAmt.toLocaleString(undefined, { minimumFractionDigits: 2 })}`);
            $("#vam_f_discount_banner").show();
        } else {
            $("#vam_f_discount_banner").hide();
        }

        // 6. Crop Items Column (Stacked Cards matching screenshot)
        let cropsHtml = '';
        if (Array.isArray(row.product_items) && row.product_items.length > 0) {
            cropsHtml = row.product_items.map(p => {
                let plants = parseInt(p.remaining_plants !== undefined ? p.remaining_plants : (p.order_qty || row.total_billable_qty || 0));
                let orderQ = parseInt(p.order_qty || plants);
                let dispQ = parseInt(p.dispatched_qty || 0);

                let plantBadge = plants > 0 ? `
                    <span class="badge bg-success fw-bold px-2 py-0.5 shadow-2xs" style="font-size: 10.5px;">
                        <i class="fa fa-leaf me-1"></i>${plants.toLocaleString()} Plants
                    </span>
                ` : '';

                let dispInfo = dispQ > 0 ? `
                    <span class="text-secondary small font-monospace" style="font-size: 9.5px;">
                        (Ord: <b>${orderQ.toLocaleString()}</b> | Disp: <b class="text-danger">${dispQ.toLocaleString()}</b>)
                    </span>
                ` : '';

                return `
                <div class="p-2 px-2.5 rounded-2 border bg-light d-flex flex-column align-items-start gap-1 shadow-2xs mb-1.5 w-100">
                    <div class="d-flex justify-content-between align-items-start w-100 gap-1 flex-wrap">
                        <span class="fw-bold text-dark" style="font-size: 11.5px; white-space: normal; word-break: break-word;">${escapeHtml(p.clean_name || p.name)}</span>
                        ${plantBadge}
                    </div>
                    <div class="d-flex align-items-center gap-1.5 flex-wrap">
                        ${p.is_grafted
                        ? '<span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:0.65rem;"><i class="fa fa-seedling me-1"></i>Grafted</span>'
                        : '<span class="badge bg-warning-subtle text-dark border border-warning-subtle" style="font-size:0.65rem;"><i class="fa fa-leaf me-1"></i>Non-Grafted</span>'}
                        ${dispInfo}
                    </div>
                </div>
                `;
            }).join('');
        } else {
            let pName = row.clean_product_name || row.product_names || 'Crop';
            let plants = parseInt(row.total_billable_qty || 0);
            let orderQ = parseInt(row.total_order_qty || plants);
            let dispQ = parseInt(row.total_dispatched_qty || 0);
            let plantBadge = plants > 0 ? `
                <span class="badge bg-success fw-bold px-2 py-0.5 shadow-2xs" style="font-size: 10.5px;">
                    <i class="fa fa-leaf me-1"></i>${plants.toLocaleString()} Plants
                </span>
            ` : '';
            let dispInfo = dispQ > 0 ? `
                <span class="text-secondary small font-monospace" style="font-size: 9.5px;">
                    (Ord: <b>${orderQ.toLocaleString()}</b> | Disp: <b class="text-danger">${dispQ.toLocaleString()}</b>)
                </span>
            ` : '';
            cropsHtml = `
                <div class="p-2 px-2.5 rounded-2 border bg-light d-flex flex-column align-items-start gap-1 shadow-2xs mb-1.5 w-100">
                    <div class="d-flex justify-content-between align-items-start w-100 gap-1 flex-wrap">
                        <span class="fw-bold text-dark" style="font-size: 11.5px; white-space: normal; word-break: break-word;">${escapeHtml(pName)}</span>
                        ${plantBadge}
                    </div>
                    <div class="d-flex align-items-center gap-1.5 flex-wrap">
                        ${row.is_grafted
                        ? '<span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:0.65rem;"><i class="fa fa-seedling me-1"></i>Grafted</span>'
                        : '<span class="badge bg-warning-subtle text-dark border border-warning-subtle" style="font-size:0.65rem;"><i class="fa fa-leaf me-1"></i>Non-Grafted</span>'}
                        ${dispInfo}
                    </div>
                </div>
            `;
        }

        // 7. Delivery Location Column (Matching exact red box when missing)
        let lat = parseFloat(row.dl_dest_latitude || 0);
        let lng = parseFloat(row.dl_dest_longitude || 0);
        let hasCoords = (!isNaN(lat) && !isNaN(lng) && lat !== 0 && lng !== 0);

        let locHtml = '';
        if (isPickup) {
            locHtml = `
                <div class="p-2 rounded-2 border border-secondary-subtle bg-secondary-subtle bg-opacity-10 shadow-2xs">
                    <div class="d-flex align-items-center gap-1.5 text-secondary fw-bold" style="font-size: 11.5px;">
                        <i class="fa fa-user-check"></i>
                        <span>1. Self Pickup By Farmer/Client</span>
                    </div>
                    <div class="text-muted small mt-1" style="font-size: 11px;">
                        <i class="fa fa-store text-secondary me-1"></i>Ex-Nursery / Farm Gate Pickup (Freight: ₹ 0.00)
                    </div>
                </div>
            `;
        } else if (isBoxPacking) {
            locHtml = `
                <div class="p-2 rounded-2 border border-warning-subtle bg-warning-subtle bg-opacity-10 shadow-2xs">
                    <div class="d-flex align-items-center gap-1.5 text-dark fw-bold mb-1" style="font-size: 11.5px;">
                        <i class="fa fa-box text-warning"></i>
                        <span>3. Box Packing</span>
                    </div>
                    <div class="small text-dark fw-semibold" style="font-size: 11px;">
                        <b>Destination:</b> ${escapeHtml([locCity, locDistrict, locState].filter(Boolean).join(', ') || 'Not Selected')}
                    </div>
                    ${locAddr ? `<div class="small text-secondary mt-0.5" style="font-size: 10.5px;">${escapeHtml(locAddr)}</div>` : ''}
                </div>
            `;
        } else if (isLocAvailable) {
            locHtml = `
                <div class="d-flex flex-column gap-1.5">
                    <div class="d-flex align-items-start gap-2">
                        <i class="fa fa-map-marker-alt text-danger mt-1"></i>
                        <div>
                            <div class="fw-bold text-dark" style="font-size: 13px;">${escapeHtml(locCity || locDistrict || 'Delivery Point')}</div>
                            ${locDistrict && locDistrict !== locCity ? `<div class="small text-muted" style="font-size: 11.5px;">${escapeHtml(locDistrict)}</div>` : ''}
                            ${locAddr ? `<div class="small text-secondary" style="max-width: 240px; white-space: normal; word-break: break-word; font-size: 11px;">${escapeHtml(locAddr)}</div>` : ''}
                            ${locState ? `<div class="small text-muted" style="font-size: 11px;">${escapeHtml(locState)} ${escapeHtml(locPincode)}</div>` : ''}
                        </div>
                    </div>
                    <div class="mt-1 pt-1 border-top border-light-subtle d-flex flex-wrap gap-1">
                        <button type="button" class="btn btn-xs btn-outline-primary fw-bold dinv-open-map-modal-btn vam-open-coords-modal-btn px-2 py-0.5 d-inline-flex align-items-center gap-1 shadow-2xs" style="font-size: 11px;" title="1. Location Coordinate (Map / GPS)">
                            <i class="fa fa-map-marker-alt text-primary"></i> 1. Location Coordinate
                        </button>
                        <button type="button" class="btn btn-xs btn-outline-secondary fw-bold dinv-open-manual-addr-modal-btn vam-open-manual-addr-modal-btn px-2 py-0.5 d-inline-flex align-items-center gap-1 shadow-2xs" style="font-size: 11px;" title="2. Manual Address (State, District, Village, Distance)">
                            <i class="fa fa-edit text-secondary"></i> 2. Manual Address
                        </button>
                    </div>
                </div>
            `;
        } else {
            // Missing Delivery Address container matching exact screenshot
            locHtml = `
                <div class="d-flex flex-column gap-1.5 p-2 rounded-2 border border-danger-subtle bg-danger-subtle bg-opacity-10 shadow-2xs">
                    <div class="d-flex align-items-center gap-1.5 text-danger fw-bold" style="font-size: 11.5px;">
                        <i class="fa fa-exclamation-triangle"></i>
                        <span>Missing Delivery Address</span>
                    </div>
                    <div class="text-danger small fst-italic" style="font-size: 11px; line-height: 1.3;">
                        Missing delivery address. Please update first.
                    </div>
                    <div class="d-flex flex-column gap-1 mt-1">
                        <button type="button" class="btn btn-xs btn-danger fw-bold dinv-open-map-modal-btn vam-open-coords-modal-btn px-2 py-1 d-inline-flex align-items-center gap-1 shadow-xs text-start" style="font-size: 11px;" title="1. Location Coordinate (Map / GPS)">
                            <i class="fa fa-map-marker-alt"></i> 1. Location Coordinate
                        </button>
                        <button type="button" class="btn btn-xs btn-outline-danger fw-bold dinv-open-manual-addr-modal-btn vam-open-manual-addr-modal-btn px-2 py-1 d-inline-flex align-items-center gap-1 shadow-xs text-start" style="font-size: 11px;" title="2. Manual Address (State, District, Village, Distance)">
                            <i class="fa fa-edit"></i> 2. Manual Address
                        </button>
                    </div>
                </div>
            `;
        }

        // 8. Vehicle Column (Matching user screenshot: calculator button, vehicle type badge, plate badge, owner, driver, trip badge, edit button)
        let firstCard = $(".vehicle-group-card:first");
        let curVehType = '';
        if (firstCard.length > 0) {
            curVehType = (firstCard.find(".vam-group-vehicletype").val() || '').trim();
            if (curVehType === 'custom') {
                curVehType = (firstCard.find(".vam-custom-type-input").val() || '').trim();
            }
        }
        let curVehNo = firstCard.length ? (firstCard.find(".vam-group-vehicleno").val() || '').trim() : '';
        let curOwner = firstCard.length ? (firstCard.find(".vam-group-owner").val() || '').trim() : '';
        let curDriver = firstCard.length ? (firstCard.find(".vam-group-driver").val() || '').trim() : '';

        let vehType = curVehType || row.effective_vehiclename || row.vehiclename || '';
        let vehNo = curVehNo || row.effective_vehicleno || row.vehicleno || '';
        let owner = curOwner || row.vehicleownername || row.owner_name || '';
        let driver = curDriver || row.effective_drivername || row.drivername || '';
        let tripId = row.trip_id || row.trip_no || '';

        if (!tripId && vehNo) {
            let dispDate = $("#vam_scheduled_dispatch_date").val() || row.scheduled_dispatch_date || new Date().toISOString().split('T')[0];
            let dClean = dispDate.replace(/[^0-9]/g, '');
            let cleanPlate = vehNo.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
            let plateSuffix = cleanPlate.length > 4 ? cleanPlate.slice(-4) : (cleanPlate || 'VEH');
            tripId = `TRIP-${dClean}-${plateSuffix}-1`;
        }

        let isVehicleAssigned = Boolean(vehNo || vehType || row.is_vehicle_allocated);
        let vehHtml = buildVamVehicleCellHtml({
            isAllocated: isVehicleAssigned,
            vehType: vehType,
            vehNo: vehNo,
            owner: owner,
            driver: driver,
            tripId: tripId
        });

        let dateDisplay = row.formatted_delivery_date || formatDateDisplay(row.deliverydate) || '-';

        // 9. Build Table Row
        let rowHtml = `
            <tr class="align-middle">
                <!-- 1. CROP NAME / DESCRIPTION -->
                <td style="min-width: 220px; max-width: 320px;">
                    <div class="d-flex flex-column gap-1">
                        ${cropsHtml}
                    </div>
                </td>

                <!-- 2. DELIVERY DATE -->
                <td class="text-center align-middle" style="width: 120px;">
                    <div class="fw-bold text-primary fs-6">${escapeHtml(dateDisplay)}</div>
                </td>

                <!-- 3. DELIVERY LOCATION -->
                <td class="align-middle" style="min-width: 240px;">
                    ${locHtml}
                </td>

                <!-- 4. DISTANCE (KM) -->
                <td class="text-center align-middle" style="width: 105px;">
                    <span class="fw-bold text-dark fs-5">${isPickup ? '0' : (km > 0 ? km : '--')}</span><br>
                    <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">KM</small>
                </td>

                <!-- 5. FREIGHT AMOUNT -->
                <td class="align-middle" style="min-width: 135px; width: 145px;">
                    <div class="input-group input-group-sm shadow-2xs">
                        <span class="input-group-text bg-white border-end-0 fw-bold text-muted" style="font-size: 11px;">Rs</span>
                        <input type="text" class="form-control vam_f_amt_input border-start-0 fw-bold text-dark bg-white font-monospace text-end pe-2" value="${baseFreight.toFixed(2)}" ${isPickup ? 'readonly' : ''} style="font-size: 12.5px;" />
                    </div>
                </td>

                <!-- 6. DISCOUNT (% & AMOUNT) -->
                <td class="align-middle" style="min-width: 200px; width: 220px;">
                    <div class="d-flex flex-column gap-1">
                        <div class="d-flex align-items-center gap-1">
                            <div class="input-group input-group-sm shadow-2xs" style="flex: 1 1 42%;">
                                <input type="number" step="0.01" min="0" max="100" class="form-control vam_f_disc_input text-center fw-bold bg-white" value="${discPct > 0 ? discPct.toFixed(2) : '0'}" ${isPickup ? 'readonly' : ''} style="font-size: 11.5px;" title="Discount Percentage (%)" />
                                <span class="input-group-text bg-light border-start-0 text-muted px-1 fw-bold" style="font-size: 11px;">%</span>
                            </div>
                            <div class="input-group input-group-sm shadow-2xs" style="flex: 1 1 58%;">
                                <span class="input-group-text bg-light text-success border-end-0 px-1 fw-bold" style="font-size: 11px;">-₹</span>
                                <input type="number" step="0.01" min="0" class="form-control vam_f_disc_amt_input text-end pe-1.5 fw-bold text-success bg-white border-start-0 font-monospace" value="${discAmt > 0 ? discAmt.toFixed(2) : '0.00'}" ${isPickup ? 'readonly' : ''} style="font-size: 11.5px;" title="Discount Amount (₹)" />
                            </div>
                        </div>
                        ${discAmt > 0 ? `<div class="small text-success text-center" style="font-size: 0.72rem;"><i class="fa fa-tag me-0.5"></i>Eligible Plan Applied</div>` : ''}
                    </div>
                </td>

                <!-- 7. PAYABLE FREIGHT -->
                <td class="align-middle" style="min-width: 135px; width: 145px;">
                    <div class="input-group input-group-sm shadow-2xs">
                        <span class="input-group-text bg-light border-end-0 fw-bold text-primary" style="font-size: 11px;">Rs</span>
                        <input type="text" class="form-control vam_f_payable_input bg-light fw-bold text-primary border-start-0 font-monospace text-end pe-2" readonly value="${payableFreight.toFixed(2)}" style="font-size: 13px;" />
                    </div>
                </td>

                <!-- 8. VEHICLE -->
                <td class="text-center align-middle vam-vehicle-cell" style="width: 140px; min-width: 140px;">
                    ${vehHtml}
                </td>
            </tr>
        `;

        $("#vamFreightTableBody").html(rowHtml);

        // 10. Update Footer Totals
        $("#vam_tbl_base_freight_display").text(baseFreight.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        $("#vam_tbl_disc_freight_display").text(discAmt > 0 ? ('- ' + discAmt.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })) : '0.00');
        $("#vam_tbl_payable_freight_display").text(payableFreight.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

        // 11. Delivery Remarks Box
        if (row.dl_remark) {
            $("#vam_route_remarks_text").text(row.dl_remark);
            $("#vam_route_remarks_box").show();
        } else {
            $("#vam_route_remarks_box").hide();
        }

        // 12. Auto-evaluate freight discount if delivered and has base freight but no saved discount
        if (isDelivered && baseFreight > 0 && discAmt <= 0) {
            try {
                applyVamFreightDiscount(baseFreight);
            } catch (err) {
                console.error("Error evaluating freight discount in VAM modal:", err);
            }
        }
    }

    // -------------------------------------------------------------
    // Delivery Type Switcher Event Handler
    // -------------------------------------------------------------
    $(document).on("click", ".vam-delivery-type-btn", function (e) {
        e.preventDefault();
        let type = $(this).data("type");
        let row = currentVamSelectedOrder;
        if (!row) return;

        row.dl_pickuptype = type;

        // Send AJAX to UpdateDeliveryType
        $.ajax({
            url: "modal/vehicle_allocation_modal.php?action=UpdateDeliveryType",
            type: "POST",
            data: {
                sono: row.sono || '',
                psono: row.psono || '',
                clientid: row.clientid || row.client_id || '',
                projectid: row.projectid || window.VA_PROJECT_ID || 0,
                pickuptype: type
            },
            dataType: "json",
            success: function (res) {
                if (res && res.status === "success") {
                    Swal.fire({
                        title: "Delivery Type Updated!",
                        text: res.message || `Mode updated to ${type}`,
                        icon: "success",
                        timer: 1500,
                        showConfirmButton: false,
                        toast: true,
                        position: "top-end"
                    });
                }
            }
        });

        renderVamFreightRouteDetails(row);
    });

    // -------------------------------------------------------------
    // Scroll to Vehicle Assignment Section
    // -------------------------------------------------------------
    $(document).on("click", ".vam-scroll-to-assign-btn", function (e) {
        e.preventDefault();
        let container = $("#vamVehicleGroupsContainer");
        if (container.length) {
            let modalBody = $("#vehicleAllocModal .modal-body, #vehicleAllocationModal .modal-body");
            if (modalBody.length) {
                modalBody.animate({
                    scrollTop: 0
                }, 300);
            }

            if ($(".vehicle-group-card").length === 0) {
                $("#btnAddVehicleGroup").trigger("click");
            }
            setTimeout(function () {
                let firstInput = $(".vehicle-group-card:first").find(".vam-group-vehicletype, .vam-group-vehicleno");
                if (firstInput.length) firstInput.first().focus();
            }, 350);
        }
    });

    // -------------------------------------------------------------
    // Open Vehicle Freight Calculator Modal
    // -------------------------------------------------------------
    $(document).on("click", ".vam-open-freight-calculator-btn", function (e) {
        e.preventDefault();
        if (typeof openFreightCalculator !== 'function') {
            console.error("openFreightCalculator function is not loaded");
            return;
        }

        let km = 0;
        if (currentVamSelectedOrder) {
            km = parseFloat(currentVamSelectedOrder.dl_effective_distance || currentVamSelectedOrder.dl_distance || currentVamSelectedOrder.dl_totaldistance || 0);
        }
        let projId = projectid || window.VA_PROJECT_ID || 1;
        let rootUrl = '../../';

        openFreightCalculator(km, projId, rootUrl, function (data) {
            if (!data) return;
            let chargeVal = parseFloat(data.charge || 0);
            let isRate = (data.calculationtype && data.calculationtype.toLowerCase() === 'rate');
            let isDelivered = true;
            if (currentVamSelectedOrder) {
                let rawType = (currentVamSelectedOrder.dl_pickuptype || '').trim().toLowerCase().replace(/[-_ ]/g, '');
                if (rawType === 'pickup' || rawType === 'selfpickup' || rawType === 'pickbyclient') {
                    isDelivered = false;
                }
            }

            if (isDelivered && isRate) {
                $("#vam_spanratedbl").text("Freight is calculated for both onward and return journeys.");
                $("#vam_spanratedbl_alert, #vam_spanratedbl_tfoot").removeClass("d-none");
                $("#vam_spanratedbl_tfoot").text("Freight is calculated for both onward and return journeys.");
            } else {
                $("#vam_spanratedbl").text("");
                $("#vam_spanratedbl_alert, #vam_spanratedbl_tfoot").addClass("d-none");
                $("#vam_spanratedbl_tfoot").text("");
            }

            // Sync with first vehicle group card or add one if missing
            let firstCard = $(".vehicle-group-card:first");
            if (firstCard.length === 0) {
                $("#btnAddVehicleGroup").trigger("click");
                firstCard = $(".vehicle-group-card:first");
            }

            if (firstCard.length > 0 && data.vehiclename) {
                let matchedOpt = firstCard.find(`.vam-group-vehicletype option`).filter(function () {
                    return $(this).text().trim().toLowerCase() === data.vehiclename.trim().toLowerCase() ||
                           $(this).val().trim().toLowerCase() === data.vehiclename.trim().toLowerCase();
                });

                if (matchedOpt.length > 0) {
                    firstCard.find(".vam-group-vehicletype").val(matchedOpt.val()).trigger("change");
                } else {
                    firstCard.find(".vam-group-vehicletype").val("custom").trigger("change");
                    firstCard.find(".vam-custom-type-input").val(data.vehiclename).trigger("input");
                }

                if (data.vehicleno && data.vehicleno !== 'No Plate') {
                    firstCard.find(".vam-group-vehicleno").val(data.vehicleno).trigger("input");
                }
            }

            // Update freight amount input
            $(".vam_f_amt_input").val(chargeVal.toFixed(2));

            // Re-apply discount module rules
            applyVamFreightDiscount(chargeVal);

            // Update table vehicle cell display
            if (typeof updateVamFreightVehicleCell === 'function') {
                updateVamFreightVehicleCell();
            }
        });
    });

    // Real-time synchronization of VEHICLE cell in Freight table
    $(document).on("input change", ".vam-group-vehicletype, .vam-custom-type-input, .vam-group-vehicleno, .vam-group-owner, .vam-group-driver, #vam_scheduled_dispatch_date", function () {
        if (typeof updateVamFreightVehicleCell === 'function') {
            updateVamFreightVehicleCell();
        }
    });

    // -------------------------------------------------------------
    // Master States & Districts Cache and Cascade for Manual Address
    // -------------------------------------------------------------
    let cachedMasterStates = null;
    let cachedMasterDistricts = {};

    function loadMasterStates(selectedState, selectedDistrict) {
        let $st = $("#manual_addr_state");
        selectedState = selectedState || 'Chhattisgarh';
        selectedDistrict = selectedDistrict || '';

        let populate = function (states) {
            $st.empty().append('<option value="">-- Select State --</option>');
            let matched = false;
            let target = String(selectedState).trim().toLowerCase();
            states.forEach(function (s) {
                let sName = s.statename || '';
                let isSel = (sName.toLowerCase() === target);
                if (isSel) matched = true;
                $st.append(`<option value="${escapeHtml(sName)}" ${isSel ? 'selected' : ''}>${escapeHtml(sName)}</option>`);
            });
            if (!matched) {
                $st.find("option").filter(function () { return $(this).text().trim().toLowerCase() === "chhattisgarh"; }).prop("selected", true);
            }
            loadMasterDistricts($st.val() || 'Chhattisgarh', selectedDistrict);
        };

        if (cachedMasterStates) {
            populate(cachedMasterStates);
        } else {
            $st.html('<option value="">Loading States...</option>');
            $.ajax({
                url: "modal/vehicle_allocation_modal.php?action=GetMasterStates",
                type: "GET",
                dataType: "json",
                success: function (res) {
                    if (res && res.status === "success" && Array.isArray(res.data)) {
                        cachedMasterStates = res.data;
                        populate(res.data);
                    } else {
                        $st.html('<option value="Chhattisgarh" selected>Chhattisgarh</option>');
                        loadMasterDistricts("Chhattisgarh", selectedDistrict);
                    }
                },
                error: function () {
                    $st.html('<option value="Chhattisgarh" selected>Chhattisgarh</option>');
                    loadMasterDistricts("Chhattisgarh", selectedDistrict);
                }
            });
        }
    }

    function loadMasterDistricts(stateVal, selectedDistrict) {
        let $dt = $("#manual_addr_district");
        if (!stateVal) {
            $dt.empty().append('<option value="">-- Select District --</option>');
            return;
        }
        selectedDistrict = selectedDistrict || '';
        let cacheKey = String(stateVal).trim().toLowerCase();

        let populate = function (districts) {
            $dt.empty().append('<option value="">-- Select District --</option>');
            let target = String(selectedDistrict).trim().toLowerCase();
            districts.forEach(function (d) {
                let dName = d.districtname || '';
                let isSel = (dName.toLowerCase() === target);
                $dt.append(`<option value="${escapeHtml(dName)}" ${isSel ? 'selected' : ''}>${escapeHtml(dName)}</option>`);
            });
        };

        if (cachedMasterDistricts[cacheKey]) {
            populate(cachedMasterDistricts[cacheKey]);
        } else {
            $dt.html('<option value="">Loading Districts...</option>');
            $.ajax({
                url: "modal/vehicle_allocation_modal.php?action=GetMasterDistricts",
                type: "POST",
                data: { state: stateVal },
                dataType: "json",
                success: function (res) {
                    if (res && res.status === "success" && Array.isArray(res.data)) {
                        cachedMasterDistricts[cacheKey] = res.data;
                        populate(res.data);
                    } else {
                        $dt.empty().append('<option value="">-- Select District --</option>');
                    }
                },
                error: function () {
                    $dt.empty().append('<option value="">-- Select District --</option>');
                }
            });
        }
    }

    $(document).on("change", "#manual_addr_state", function () {
        loadMasterDistricts($(this).val(), '');
    });

    // -------------------------------------------------------------
    // Open Manual Delivery Address Modal (2. Manual Address)
    // -------------------------------------------------------------
    $(document).on("click", ".vam-open-manual-addr-modal-btn, .dinv-open-manual-addr-modal-btn", function (e) {
        e.preventDefault();
        let row = currentVamSelectedOrder;
        if (!row) return;

        let psono = row.psono || row.sono || '';
        let clientId = row.clientid || row.client_id || '';
        let projectId = row.projectid || window.VA_PROJECT_ID || 0;

        $("#manual_addr_group_index").val(0);
        $("#manual_addr_psono").val(psono);
        $("#manual_addr_clientid").val(clientId);
        $("#manual_addr_projectid").val(projectId);

        let curState = row.dl_dest_state || row.statename || 'Chhattisgarh';
        let curDistrict = row.dl_dest_district || row.districtname || '';
        let curBlock = row.dl_dest_block || '';
        let curCity = row.dl_dest_city || '';
        let curPincode = row.dl_dest_pincode || '';
        let curKm = parseFloat(row.dl_effective_distance || row.dl_distance || row.dl_totaldistance || 0);

        $("#manual_addr_block").val(curBlock);
        $("#manual_addr_city").val(curCity);
        $("#manual_addr_pincode").val(curPincode);
        $("#manual_addr_distance").val(curKm > 0 ? curKm : '');

        loadMasterStates(curState, curDistrict);
        openModal("manualDeliveryAddressModal");
    });

    // -------------------------------------------------------------
    // Save Manual Delivery Address Form Submit
    // -------------------------------------------------------------
    $(document).on("submit", "#manualDeliveryAddressForm", function (e) {
        e.preventDefault();
        let row = currentVamSelectedOrder;
        if (!row) return;

        let psono = $("#manual_addr_psono").val() || row.psono || row.sono || '';
        let clientId = $("#manual_addr_clientid").val() || row.clientid || row.client_id || '';
        let projectId = $("#manual_addr_projectid").val() || row.projectid || window.VA_PROJECT_ID || 0;

        let state = $("#manual_addr_state").val() || 'Chhattisgarh';
        let district = $("#manual_addr_district").val() || '';
        let block = $("#manual_addr_block").val().trim();
        let city = $("#manual_addr_city").val().trim();
        let pincode = $("#manual_addr_pincode").val().trim();
        let distanceKm = parseFloat($("#manual_addr_distance").val()) || 0;

        if (!district) {
            Swal.fire('Validation Error', 'Please select a District from Master.', 'warning');
            return false;
        }
        if (!city) {
            Swal.fire('Validation Error', 'Please enter City / Village.', 'warning');
            return false;
        }
        if (distanceKm <= 0 || isNaN(distanceKm)) {
            Swal.fire('Validation Error', 'Please enter a valid Approx Distance (KM).', 'warning');
            return false;
        }

        let $btnSubmit = $("#btn_save_manual_address");
        $btnSubmit.prop("disabled", true).html('<i class="fa fa-spinner fa-spin me-1"></i> Saving...');

        $.ajax({
            url: "modal/vehicle_allocation_modal.php?action=SaveManualDeliveryAddress",
            type: "POST",
            data: {
                orderid: psono,
                psono: psono,
                sono: row.sono || '',
                clientid: clientId,
                projectid: projectId,
                state: state,
                district: district,
                block: block,
                city: city,
                pincode: pincode,
                distance_km: distanceKm
            },
            dataType: "json",
            success: function (res) {
                $btnSubmit.prop("disabled", false).html('<i class="fa fa-check-circle me-1"></i> Save &amp; Refresh Distance');
                if (res && res.status === "success") {
                    let d = res.data || {};
                    row.dl_dest_city = d.dest_city || city;
                    row.dl_dest_district = d.dest_district || district;
                    row.dl_dest_state = d.dest_state || state;
                    row.dl_dest_pincode = d.dest_pincode || pincode;
                    row.dl_deliveryaddress = d.deliveryaddress || [city, block, district, state, pincode ? 'PIN: ' + pincode : ''].filter(Boolean).join(', ');
                    row.dl_effective_distance = parseFloat(d.distance || distanceKm);
                    row.dl_distance = row.dl_effective_distance;
                    row.dl_pickuptype = 'delivered';

                    if (d.base_freight && parseFloat(d.base_freight) > 0) {
                        row.dl_base_freight = parseFloat(d.base_freight);
                        row.dl_payable_freight = parseFloat(d.payable_freight || d.base_freight);
                    }

                    renderVamFreightRouteDetails(row);
                    closeModal("manualDeliveryAddressModal");

                    Swal.fire({
                        title: "Delivery Address & Distance Saved!",
                        html: `<b>Destination:</b> ${escapeHtml(district)}, ${escapeHtml(state)}<br><b>Approx Distance:</b> ${row.dl_effective_distance} KM<br><small class="text-success fw-bold">Distance & freight updated dynamically!</small>`,
                        icon: "success",
                        timer: 2500,
                        showConfirmButton: false
                    });
                } else {
                    $btnSubmit.prop("disabled", false).html('<i class="fa fa-check-circle me-1"></i> Save &amp; Refresh Distance');
                    Swal.fire("Save Error", res.message || "Failed to save address", "error");
                }
            },
            error: function () {
                $btnSubmit.prop("disabled", false).html('<i class="fa fa-check-circle me-1"></i> Save &amp; Refresh Distance');
                Swal.fire("Error", "Server communication error.", "error");
            }
        });
    });

    // -------------------------------------------------------------
    // Location Coordinates Button (1. Location Coordinate Map / GPS)
    // -------------------------------------------------------------
    function syncLatLongInputsFromCombined() {
        let val = $("#latlonginput").val() ? $("#latlonginput").val().trim() : '';
        if (val) {
            let parts = val.split(',');
            if (parts.length >= 2) {
                let lat = parts[0].trim();
                let lon = parts[1].trim();
                $("#latinput").val(lat);
                $("#longinput").val(lon);
                updateMapIframeIfValid(lat, lon);
            }
        }
    }

    function syncCombinedFromIndividualLatLong() {
        let lat = $("#latinput").val() ? $("#latinput").val().trim() : '';
        let lon = $("#longinput").val() ? $("#longinput").val().trim() : '';
        if (lat || lon) {
            $("#latlonginput").val(`${lat}${lon ? ', ' + lon : ''}`);
        } else {
            $("#latlonginput").val('');
        }
        if (lat && lon) {
            updateMapIframeIfValid(lat, lon);
        }
    }

    function updateMapIframeIfValid(lat, lon) {
        if (lat && lon && !isNaN(parseFloat(lat)) && !isNaN(parseFloat(lon))) {
            let row = currentVamSelectedOrder;
            let clientId = row ? (row.clientid || row.client_id || '') : '';
            let psono = row ? (row.psono || row.sono || '') : '';
            let url = '../locatioinaddressget_api.php?lat=' + encodeURIComponent(lat) +
                '&lon=' + encodeURIComponent(lon) +
                '&clid=' + encodeURIComponent(clientId) +
                '&orderid=' + encodeURIComponent(psono) +
                '&bindbtnid=latlonginput';
            let currentSrc = $('#appenddeliveryaddressdiv iframe').attr('src');
            if (currentSrc !== url && $('#adddeliveryaddressModal').hasClass('show')) {
                $('#appenddeliveryaddressdiv').html('<iframe src="' + url + '" id="location-iframe" style="width:100%; height:640px; border:none; border-radius:12px; overflow:hidden;" scrolling="no"></iframe>');
            }
        }
    }

    window.initLatLongSplit = syncLatLongInputsFromCombined;

    $(document).on("input paste change keyup", "#latlonginput", function () {
        setTimeout(syncLatLongInputsFromCombined, 20);
    });

    $(document).on("input paste change keyup", "#latinput, #longinput", function () {
        setTimeout(syncCombinedFromIndividualLatLong, 20);
    });

    $(document).on("click", ".vam-open-coords-modal-btn, .dinv-open-map-modal-btn", function (e) {
        e.preventDefault();
        let row = currentVamSelectedOrder;
        if (!row) return;

        let clientId = row.clientid || row.client_id || '';
        let psono = row.psono || row.sono || '';
        let currentLat = row.dl_dest_latitude || '';
        let currentLon = row.dl_dest_longitude || '';

        if (currentLat && currentLon) {
            $("#latinput").val(currentLat);
            $("#longinput").val(currentLon);
            $("#latlonginput").val(`${currentLat}, ${currentLon}`);
        } else {
            let existingVal = $("#latlonginput").val();
            if (existingVal) {
                syncLatLongInputsFromCombined();
                currentLat = $("#latinput").val() || '';
                currentLon = $("#longinput").val() || '';
            } else {
                $("#latinput").val('');
                $("#longinput").val('');
            }
        }

        let url = '../locatioinaddressget_api.php?lat=' + encodeURIComponent(currentLat) +
            '&lon=' + encodeURIComponent(currentLon) +
            '&clid=' + encodeURIComponent(clientId) +
            '&orderid=' + encodeURIComponent(psono) +
            '&bindbtnid=latlonginput';

        let $container = $('#appenddeliveryaddressdiv');
        $container.html('<div class="text-center py-5" style="min-height:400px; display:flex; flex-direction:column; justify-content:center; align-items:center;"><div class="spinner-border text-primary" role="status"></div><p class="mt-3 text-muted fw-bold">Loading map & location locator...</p></div>');

        setTimeout(function () {
            $container.html('<iframe src="' + url + '" id="location-iframe" style="width:100%; height:640px; border:none; border-radius:12px; overflow:hidden;" scrolling="no"></iframe>');
        }, 150);

        openModal("adddeliveryaddressModal");
    });

    // Global callback invoked by the map iframe inside locatioinaddressget_api.php
    window.appendAddressRow = function (data) {
        if (!data || !data.full_address) {
            Swal.fire('Incomplete Data', 'Delivery address is required', 'warning');
            return false;
        }

        let row = currentVamSelectedOrder;
        if (!row) return;

        let lat = data.lat || "";
        let lon = data.lon || "";
        let state = data.state || "";
        let district = data.district || "";
        let city = data.city || "";
        let pincode = data.pincode || "";
        let fullAddress = data.full_address || "";
        let calcKm = parseFloat(data.calc_km || data.distance_km || 0);

        if (lat && lon) {
            $("#latinput").val(lat);
            $("#longinput").val(lon);
            $("#latlonginput").val(`${lat}, ${lon}`);
        }

        let psono = row.psono || row.sono || '';
        let clientId = row.clientid || row.client_id || '';
        let projectId = row.projectid || window.VA_PROJECT_ID || 0;

        $.ajax({
            url: "modal/vehicle_allocation_modal.php?action=SaveManualDeliveryAddress",
            type: "POST",
            data: {
                orderid: psono,
                psono: psono,
                sono: row.sono || '',
                clientid: clientId,
                projectid: projectId,
                state: state,
                district: district,
                city: city,
                pincode: pincode,
                address: fullAddress,
                distance_km: calcKm,
                latitude: lat,
                longitude: lon
            },
            dataType: "json",
            success: function (res) {
                row.dl_dest_city = city;
                row.dl_dest_district = district;
                row.dl_dest_state = state;
                row.dl_dest_pincode = pincode;
                row.dl_deliveryaddress = fullAddress;
                row.dl_effective_distance = calcKm;
                row.dl_distance = calcKm;
                row.dl_dest_latitude = lat;
                row.dl_dest_longitude = lon;
                row.dl_pickuptype = 'delivered';

                if (res && res.data && res.data.base_freight && parseFloat(res.data.base_freight) > 0) {
                    row.dl_base_freight = parseFloat(res.data.base_freight);
                    row.dl_payable_freight = parseFloat(res.data.payable_freight || res.data.base_freight);
                }

                renderVamFreightRouteDetails(row);
                closeModal("adddeliveryaddressModal");

                Swal.fire({
                    title: "Location Coordinates Saved!",
                    html: `<b>Address:</b> ${escapeHtml(city || district)}, ${escapeHtml(state)}<br><b>Distance:</b> ${calcKm} KM<br><small class="text-success fw-bold">Location & distance updated dynamically!</small>`,
                    icon: "success",
                    timer: 2500,
                    showConfirmButton: false
                });
            },
            error: function () {
                closeModal("adddeliveryaddressModal");
                Swal.fire("Error", "Failed to save address coordinates to database.", "error");
            }
        });
    };

    // -------------------------------------------------------------
    // Live Freight Amount & Discount calculation in Table Row
    // -------------------------------------------------------------
    $(document).on("input change", ".vam_f_amt_input", function () {
        let base = parseFloat($(this).val()) || 0;
        let discPct = parseFloat($(".vam_f_disc_input").val()) || 0;
        applyVamFreightDiscount(base, discPct);
    });

    $(document).on("input change", ".vam_f_disc_input", function () {
        let base = parseFloat($(".vam_f_amt_input").val()) || 0;
        let discPct = parseFloat($(this).val()) || 0;
        applyVamFreightDiscount(base, discPct);
    });

    $(document).on("input change", ".vam_f_disc_amt_input", function () {
        let base = parseFloat($(".vam_f_amt_input").val()) || 0;
        let discAmt = parseFloat($(this).val()) || 0;
        applyVamFreightDiscount(base, null, discAmt);
    });

    function ordVal(val) {
        return (val && val !== 'null' && val !== 'undefined') ? val : '';
    }

    // Save Vehicle Allocation (Multi-Vehicle Groups Support)
    $("#vamSaveBtn").on("click", function () {
        let btn = $(this);
        let cards = $(".vehicle-group-card");
        if (cards.length === 0) {
            Swal.fire("No Vehicles", "Please add at least one vehicle group to allocate.", "warning");
            return;
        }

        let vehicles = [];
        let totalAllocatedSum = 0;
        let validationError = null;

        cards.each(function (i) {
            if (validationError) return;
            let card = $(this);
            let num = i + 1;

            let groupPlants = parseInt(card.find(".vam-group-plants").val() || 0);
            if (groupPlants <= 0) {
                validationError = `Please enter a valid allocated plant count (> 0) for Vehicle #${num}.`;
                card.find(".vam-group-plants").focus();
                return;
            }
            totalAllocatedSum += groupPlants;

            let vehType = card.find(".vam-group-vehicletype").val().trim();
            if (vehType === 'custom') {
                vehType = card.find(".vam-custom-type-input").val().trim();
                if (!vehType) {
                    validationError = `Please enter the custom vehicle type for Vehicle #${num}.`;
                    card.find(".vam-custom-type-input").focus();
                    return;
                }
            }
            if (!vehType) {
                validationError = `Please select a Vehicle Type for Vehicle #${num}.`;
                card.find(".vam-group-vehicletype").focus();
                return;
            }

            let vehNo = card.find(".vam-group-vehicleno").val().trim().toUpperCase();
            if (!vehNo) {
                validationError = `Please enter or select a Registration Number for Vehicle #${num}.`;
                card.find(".vam-group-vehicleno").addClass("is-invalid").focus();
                return;
            } else {
                card.find(".vam-group-vehicleno").removeClass("is-invalid");
            }

            let vehOwner = card.find(".vam-group-owner").val().trim();
            if (!vehOwner) {
                validationError = `Please enter Owner / Transporter Name for Vehicle #${num}.`;
                card.find(".vam-group-owner").addClass("is-invalid").focus();
                return;
            } else {
                card.find(".vam-group-owner").removeClass("is-invalid");
            }

            let drvName = card.find(".vam-group-driver").val().trim();
            if (!drvName) {
                validationError = `Please enter Driver Full Name for Vehicle #${num}.`;
                card.find(".vam-group-driver").addClass("is-invalid").focus();
                return;
            } else if (/[0-9]/.test(drvName)) {
                validationError = `Driver Full Name cannot contain numbers for Vehicle #${num}. Please enter text only.`;
                card.find(".vam-group-driver").addClass("is-invalid").focus();
                return;
            } else {
                card.find(".vam-group-driver").removeClass("is-invalid");
            }

            let dMobile = card.find(".vam-group-mobile").val().trim();
            if (!dMobile) {
                validationError = `Please enter Driver Mobile No (10 digits) for Vehicle #${num}.`;
                card.find(".vam-group-mobile").addClass("is-invalid").focus();
                card.find(".vam-group-mobile-err").html('<i class="fa fa-exclamation-circle me-1"></i>Driver mobile number is required.').show();
                return;
            } else if (!/^[6-9]\d{9}$/.test(dMobile)) {
                validationError = `Vehicle #${num} has an invalid mobile number. Must be a valid 10-digit number starting with 6, 7, 8, or 9.`;
                card.find(".vam-group-mobile").addClass("is-invalid").focus();
                card.find(".vam-group-mobile-err").html('<i class="fa fa-exclamation-circle me-1"></i>Must be a valid 10-digit mobile.').show();
                return;
            } else {
                card.find(".vam-group-mobile").removeClass("is-invalid").addClass("is-valid");
                card.find(".vam-group-mobile-err").hide();
            }

            vehicles.push({
                allocated_plants: groupPlants,
                vehiclename: vehType,
                vehicle_capacity: parseInt(card.find(".vam-group-capacity").val() || 0),
                vehicleno: vehNo,
                vehicleownername: vehOwner,
                drivername: drvName,
                drivermobileno: dMobile
            });
        });

        if (validationError) {
            Swal.fire("Validation Error", validationError, "warning");
            return;
        }

        let totalOrderPlants = parseInt($("#vam_total_plants").val() || 0);
        if (totalAllocatedSum !== totalOrderPlants) {
            let diff = totalAllocatedSum - totalOrderPlants;
            let diffText = diff > 0 ? `+${diff.toLocaleString()} plants over order quantity` : `${Math.abs(diff).toLocaleString()} plants less than order quantity`;
            Swal.fire({
                title: "Allocation Mismatch",
                text: `Total allocated plants across vehicles (${totalAllocatedSum.toLocaleString()}) does not match order load (${totalOrderPlants.toLocaleString()}) [${diffText}]. Do you want to proceed anyway?`,
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#0d6efd",
                cancelButtonColor: "#6c757d",
                confirmButtonText: "Yes, Confirm Allocation"
            }).then((res) => {
                if (res.isConfirmed) {
                    executeSaveAllocation(btn, vehicles);
                }
            });
        } else {
            executeSaveAllocation(btn, vehicles);
        }
    });

    function executeSaveAllocation(btn, vehicles) {
        let schedDate = $("#vam_scheduled_dispatch_date").val();
        if (!schedDate) {
            Swal.fire("Required Field", "Please select a Scheduled Dispatch Date.", "warning");
            $("#vam_scheduled_dispatch_date").focus();
            return;
        }

        btn.prop("disabled", true).html(`<i class="fa fa-spinner fa-spin me-1"></i> Saving...`);

        let baseFreight = parseFloat($(".vam_f_amt_input").val() || 0);
        let payableFreight = parseFloat($(".vam_f_payable_input").val() || 0);
        let discAmt = parseFloat($(".vam_f_disc_amt_input").val() || 0);
        let discPct = parseFloat($(".vam_f_disc_input").val() || 0);
        let pickuptype = currentVamSelectedOrder ? (currentVamSelectedOrder.dl_pickuptype || 'delivered') : 'delivered';
        let distance = parseFloat(currentVamSelectedOrder ? (currentVamSelectedOrder.dl_effective_distance || currentVamSelectedOrder.dl_distance || currentVamSelectedOrder.dl_totaldistance || 0) : 0);

        let payload = {
            projectid: $("#vam_projectid").val(),
            sono: $("#vam_sono").val(),
            psono: $("#vam_psono").val(),
            clientid: $("#vam_clientid").val(),
            total_allocated_plants: $("#vam_total_plants").val(),
            scheduled_dispatch_date: schedDate,
            remarks: $("#vam_remarks").val(),
            loginid: loginid,
            distance: distance,
            base_freight: baseFreight,
            payable_freight: payableFreight,
            discount_amount: discAmt,
            discount_percent: discPct,
            pickuptype: pickuptype,
            existing_trip_id: $("#vam_existing_trip_id").val() || '',
            vehicles: JSON.stringify(vehicles)
        };

        $.ajax({
            url: "modal/vehicle_allocation_modal.php?action=SaveOrderVehicleAllocation",
            type: "POST",
            data: payload,
            dataType: "json",
            success: function (res) {
                btn.prop("disabled", false).html(`<i class="fa fa-check-circle me-1"></i> Confirm Vehicle Allocation`);
                if (res.status === "success") {
                    hideAllocModal();
                    Swal.fire({
                        title: "Vehicle Allocated!",
                        text: res.message || "Vehicles allocated successfully.",
                        icon: "success",
                        timer: 2000,
                        showConfirmButton: false
                    });
                    loadOrdersForSelectedWeek();
                    loadAllSuggestions();
                } else {
                    Swal.fire("Error", res.message || "Failed to save allocation.", "error");
                }
            },
            error: function (xhr) {
                btn.prop("disabled", false).html(`<i class="fa fa-check-circle me-1"></i> Confirm Vehicle Allocation`);
                let errMsg = "Server error while saving vehicle allocation.";
                try {
                    let errObj = JSON.parse(xhr.responseText);
                    if (errObj && errObj.message) errMsg = errObj.message;
                } catch (e) { }
                Swal.fire("Error", errMsg, "error");
            }
        });
    }

    // Remove Vehicle Allocation
    $(document).on("click", ".remove-vehicle-alloc-btn", function () {
        let sono = $(this).data("sono");
        let psono = $(this).data("psono");

        Swal.fire({
            title: "Remove Vehicle Allocation?",
            text: `Are you sure you want to remove the allocated vehicle from order ${sono}?`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#dc3545",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "Yes, Remove"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "modal/vehicle_allocation_modal.php?action=DeleteOrderVehicleAllocation",
                    type: "POST",
                    data: {
                        projectid: projectid,
                        sono: sono,
                        psono: psono,
                        loginid: loginid
                    },
                    dataType: "json",
                    success: function (res) {
                        if (res.status === "success") {
                            Swal.fire("Removed", res.message, "success");
                            loadOrdersForSelectedWeek();
                            loadAllSuggestions();
                        } else {
                            Swal.fire("Error", res.message, "error");
                        }
                    },
                    error: function () {
                        Swal.fire("Error", "Server error while removing allocation.", "error");
                    }
                });
            }
        });
    });

    // ==========================================
    // EXPORT TO EXCEL FUNCTIONALITY
    // ==========================================
    $("#btnExportExcel, #btnExportExcelWeekBar").on("click", function () {
        exportOrdersToExcel();
    });

    function exportOrdersToExcel() {
        if (!currentFilteredOrders || currentFilteredOrders.length === 0) {
            Swal.fire("No Data", "No orders available in current view to export.", "warning");
            return;
        }

        let excelRows = [];
        currentFilteredOrders.forEach((ord, index) => {
            let isAlloc = ord.is_vehicle_allocated;
            let dayName = getDayName(ord.deliverydate);
            let billableQty = parseInt(ord.total_billable_qty || 0);
            let isGrafted = isProductGrafted(ord.product_names || '');
            let cleanProd = formatProductNameClean(ord.clean_product_name || ord.product_names || '');
            let pickupType = (ord.dl_pickuptype === 'client_pickup') ? 'Client Pickup' : ((ord.dl_pickuptype === 'boxpacking' || ord.dl_pickuptype === 'box') ? 'Box Packing' : 'Delivered by Farm');
            let freightAmt = parseFloat(ord.dl_freightamt || ord.dl_deliverycharges || 0);
            let totalDist = parseFloat(ord.dl_totaldistance || 0);
            let delAddr = ord.dl_deliveryaddress || ord.delivery_address || '';

            excelRows.push({
                "S.No": index + 1,
                "Delivery Date": ord.formatted_delivery_date || ord.deliverydate || '',
                "Day": dayName || '',
                "Client Name": ord.clientname || '',
                "Mobile Number": ord.mobileno || '',
                "District": ord.districtname || ord.dl_dest_district || '',
                "State": ord.statename || ord.dl_dest_state || '',
                "SO Number": ord.sono || '',
                "PSO Number": ord.psono || '',
                "Stock Order No": ord.stock_order_nos || '',
                "Product Name": cleanProd || '',
                "Crop Type": isGrafted ? 'Grafted' : 'Non-Grafted',
                "Order Qty (Plants)": billableQty,
                "Delivery Mode": pickupType,
                "Freight Charges (₹)": freightAmt,
                "Total Distance (km)": totalDist,
                "Delivery Address": delAddr,
                "Vehicle Registration No": isAlloc ? (ord.effective_vehicleno || '') : 'Pending',
                "Vehicle Type": ord.effective_vehiclename || '',
                "Owner / Transporter": ord.vehicleownername || '',
                "Driver Name": ord.effective_drivername || '',
                "Driver Contact": ord.effective_drivermobile || '',
                "Scheduled Dispatch Date": ord.scheduled_dispatch_date || '',
                "Vehicle Status": isAlloc ? 'Allocated' : 'Pending Vehicle',
                "Allocated By": ord.allocated_by_name || ord.allocated_by || '',
                "Allocated At": ord.allocated_at_formatted || '',
                "Logistics Remarks": ord.vehicle_remarks || ord.dl_remark || ''
            });
        });

        if (typeof XLSX === 'undefined') {
            Swal.fire("Error", "Excel export library not loaded. Please refresh the page.", "error");
            return;
        }

        let ws = XLSX.utils.json_to_sheet(excelRows);
        let colWidths = [
            { wch: 6 },  // S.No
            { wch: 14 }, // Delivery Date
            { wch: 11 }, // Day
            { wch: 22 }, // Client Name
            { wch: 14 }, // Mobile Number
            { wch: 16 }, // District
            { wch: 14 }, // State
            { wch: 14 }, // SO
            { wch: 14 }, // PSO
            { wch: 14 }, // Stock
            { wch: 28 }, // Product
            { wch: 13 }, // Crop Type
            { wch: 16 }, // Order Qty
            { wch: 18 }, // Delivery Mode
            { wch: 16 }, // Freight
            { wch: 16 }, // Distance
            { wch: 30 }, // Delivery Address
            { wch: 18 }, // Vehicle No
            { wch: 16 }, // Vehicle Type
            { wch: 18 }, // Owner
            { wch: 18 }, // Driver
            { wch: 15 }, // Driver Mobile
            { wch: 16 }, // Scheduled Date
            { wch: 16 }, // Status
            { wch: 18 }, // Allocated By
            { wch: 20 }, // Allocated At
            { wch: 25 }  // Remarks
        ];
        ws['!cols'] = colWidths;

        let wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Vehicle Allocations");

        let monthVal = ($("#vaMonthFilter option:selected").text() || 'Schedule').replace(/[^a-zA-Z0-9_-]/g, '_');
        let fileName = `Vehicle_Allocation_Schedule_${monthVal}.xlsx`;
        XLSX.writeFile(wb, fileName);

        Swal.fire({
            title: "Excel Exported!",
            text: `${excelRows.length} order(s) successfully exported to ${fileName}`,
            icon: "success",
            timer: 2000,
            showConfirmButton: false
        });
    }

    // ==========================================
    // PDF REPORT & PRINT PREVIEW FUNCTIONALITY
    // ==========================================
    $("#btnExportPdf, #btnExportPdfWeekBar").on("click", function () {
        if (!currentFilteredOrders || currentFilteredOrders.length === 0) {
            Swal.fire("No Data", "No orders available in current view to generate report.", "warning");
            return;
        }

        let docHtml = buildPdfPrintableDocumentHtml();
        $("#vaPdfPrintableDocument").html(docHtml);
        $("#vaPdfFooterCount").text(`Total orders in report: ${currentFilteredOrders.length}`);

        showPdfPreviewModal();
    });

    // Print PDF Document
    $("#btnPdfModalPrint, #btnPdfModalPrintBottom").on("click", function () {
        printPdfDocument();
    });

    function printPdfDocument() {
        let content = document.getElementById('vaPdfPrintableDocument');
        if (!content) return;

        let printWin = window.open('', '_blank', 'width=1120,height=800');
        if (!printWin) {
            window.print();
            return;
        }

        printWin.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Vehicle Allocation Report - Karjee Farm</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                <style>
                    @page { 
                        size: A4 landscape; 
                        margin: 6mm 6mm 6mm 6mm; 
                    }
                    * {
                        box-sizing: border-box;
                    }
                    html, body { 
                        width: 100% !important;
                        height: auto !important;
                        background: #ffffff !important; 
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
                        padding: 0 !important; 
                        margin: 0 !important;
                        color: #0f172a; 
                        -webkit-print-color-adjust: exact !important;
                        print-color-adjust: exact !important;
                    }
                    table { 
                        border-collapse: collapse; 
                        width: 100% !important; 
                        table-layout: fixed;
                        page-break-inside: auto; 
                    }
                    thead { 
                        display: table-header-group; 
                    }
                    tr { 
                        page-break-inside: avoid; 
                        page-break-after: auto; 
                    }
                    th, td { 
                        border: 1px solid #cbd5e1 !important; 
                    }
                </style>
            </head>
            <body>
                <div style="width: 100%; margin: 0; padding: 0;">
                    ${content.innerHTML}
                </div>
                    window.onload = function() {
                        window.focus();
                        window.print();
                        setTimeout(function() { window.close(); }, 1000);
                    };
                <\/script>
            </body>
            </html>
        `);
        printWin.document.close();
    }

    // Download PDF Document
    $("#btnPdfModalDownload, #btnPdfModalDownloadBottom").on("click", function () {
        downloadPdfDocument();
    });

    function downloadPdfDocument() {
        let element = document.getElementById('vaPdfPrintableDocument');
        if (!element) return;

        let monthVal = ($("#vaMonthFilter option:selected").text() || 'Schedule').replace(/[^a-zA-Z0-9_-]/g, '_');

        let btn = $("#btnPdfModalDownload, #btnPdfModalDownloadBottom");
        let origHtml = btn.html();
        btn.prop("disabled", true).html(`<i class="fa fa-spinner fa-spin me-1"></i> Downloading PDF...`);

        let opt = {
            margin: [5, 5, 5, 5],
            filename: `Vehicle_Allocation_A4_${monthVal}.pdf`,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: {
                scale: 2,
                useCORS: true,
                logging: false,
                backgroundColor: '#ffffff'
            },
            jsPDF: {
                unit: 'mm',
                format: 'a4',
                orientation: 'landscape'
            },
            pagebreak: {
                mode: ['avoid-all', 'css', 'legacy']
            }
        };

        if (typeof html2pdf === 'undefined') {
            btn.prop("disabled", false).html(origHtml);
            Swal.fire("Note", "Opening standard print dialog. Please choose 'Save as PDF'.", "info");
            printPdfDocument();
            return;
        }

        html2pdf().set(opt).from(element).save().then(function () {
            btn.prop("disabled", false).html(origHtml);
            Swal.fire({
                title: "A4 PDF Downloaded",
                text: "Vehicle Allocation Report downloaded successfully in A4 landscape format.",
                icon: "success",
                timer: 2000,
                showConfirmButton: false
            });
        }).catch(function (err) {
            console.error("PDF generation error:", err);
            btn.prop("disabled", false).html(origHtml);
            Swal.fire({
                title: "Print / Save PDF",
                text: "Direct download encountered an issue. Opening print dialog to Save as PDF.",
                icon: "info",
                confirmButtonText: "Open Print Dialog"
            }).then(() => {
                printPdfDocument();
            });
        });
    }

    function buildPdfPrintableDocumentHtml() {
        let orders = currentFilteredOrders || [];
        let monthText = $("#vaMonthFilter option:selected").text() || 'All Months';
        let yearVal = $("#vaYearFilter").val() || '';
        let generatedDate = new Date().toLocaleString('en-IN', { dateStyle: 'medium', timeStyle: 'short' });

        let totalOrders = orders.length;
        let totalPlants = orders.reduce((sum, o) => sum + parseInt(o.total_billable_qty || 0), 0);
        let allocatedCount = orders.filter(o => o.is_vehicle_allocated).length;
        let pendingCount = totalOrders - allocatedCount;

        let tableRowsHtml = '';
        if (orders.length === 0) {
            tableRowsHtml = `<tr><td colspan="7" style="text-align: center; padding: 24px; color: #64748b; font-weight: bold;">No orders found for the selected filter criteria.</td></tr>`;
        } else {
            orders.forEach((ord, index) => {
                let isAlloc = ord.is_vehicle_allocated;
                let dayName = getDayName(ord.deliverydate);
                let billableQty = parseInt(ord.total_billable_qty || 0);
                let isGrafted = isProductGrafted(ord.product_names || '');
                let cleanProd = formatProductNameClean(ord.clean_product_name || ord.product_names || '');

                // Delivery mode & freight
                let pickupType = (ord.dl_pickuptype === 'client_pickup') ? 'Client Pickup' : ((ord.dl_pickuptype === 'boxpacking' || ord.dl_pickuptype === 'box') ? 'Box Packing' : 'Delivered by Farm');
                let freightAmt = parseFloat(ord.dl_freightamt || ord.dl_deliverycharges || 0);
                let freightDisplay = (freightAmt > 0) ? `₹ ${freightAmt.toLocaleString()}` : 'Free / Included';
                let totalDist = parseFloat(ord.dl_totaldistance || 0);
                let distDisplay = totalDist > 0 ? ` (${totalDist} km)` : '';

                // Full Complete Address without truncation
                let fullAddr = (ord.dl_deliveryaddress || ord.delivery_address || '').trim();
                if (!fullAddr && (ord.dl_dest_city || ord.dl_dest_district)) {
                    fullAddr = [ord.dl_dest_city, ord.dl_dest_district, ord.dl_dest_state, ord.dl_dest_pincode].filter(Boolean).join(', ');
                }
                let pointHtml = ord.dl_pickuppoint ? `<div style="font-size: 9px; color: #475569; margin-top: 2px;"><b>Point:</b> ${escapeHtml(ord.dl_pickuppoint)}</div>` : '';
                let remarkHtml = ord.dl_remark ? `<div style="font-size: 9px; color: #64748b; font-style: italic; margin-top: 2px;">💬 ${escapeHtml(ord.dl_remark)}</div>` : '';
                let addrHtml = fullAddr ? `<div style="font-size: 9.5px; color: #334155; margin-top: 3px; line-height: 1.35; word-break: break-word; white-space: normal;"><span style="color: #ef4444;">📍</span> ${escapeHtml(fullAddr)}</div>` : '';

                // Vehicle details
                let vehHtml = '';
                if (isAlloc) {
                    let vehNo = escapeHtml(ord.effective_vehicleno || '');
                    let vehName = ord.effective_vehiclename ? ` (${escapeHtml(ord.effective_vehiclename)})` : '';
                    let driver = ord.effective_drivername ? `${escapeHtml(ord.effective_drivername)}` : '';
                    let mobile = ord.effective_drivermobile ? ` - ${escapeHtml(ord.effective_drivermobile)}` : '';
                    let schedDate = ord.scheduled_dispatch_date ? `<div style="font-size: 9.5px; color: #475569;"><b>Dispatch:</b> ${formatDateDisplay(ord.scheduled_dispatch_date)}</div>` : '';
                    let allocBy = ord.allocated_by_name || ord.allocated_by || '';

                    vehHtml = `
                        <div style="font-weight: 700; color: #166534; font-size: 11px;">
                            🚛 ${vehNo}${vehName}
                        </div>
                        <div style="font-size: 10.5px; color: #1e293b;">
                            <b>Driver:</b> ${driver}${mobile}
                        </div>
                        ${schedDate}
                        ${allocBy ? `<div style="font-size: 9px; color: #64748b;">By: ${escapeHtml(allocBy)}</div>` : ''}
                    `;
                } else {
                    vehHtml = `<span style="display: inline-block; padding: 2px 6px; font-size: 10px; font-weight: 600; color: #b45309; background-color: #fef3c7; border: 1px solid #fde68a; border-radius: 4px;">⚠️ Pending Vehicle</span>`;
                }

                let destLocation = [ord.districtname || ord.dl_dest_district, ord.statename || ord.dl_dest_state].filter(Boolean).join(', ');

                tableRowsHtml += `
                    <tr style="page-break-inside: avoid; border-bottom: 1px solid #cbd5e1; font-size: 11px;">
                        <td style="text-align: center; font-weight: bold; color: #64748b; padding: 6px 4px; vertical-align: top; width: 3.5%;">${index + 1}</td>
                        <td style="padding: 6px 6px; vertical-align: top; white-space: nowrap; width: 9.5%;">
                            <div style="font-weight: 700; color: #0f172a; font-size: 11.5px;">${ord.formatted_delivery_date}</div>
                            <div style="font-size: 10px; color: #64748b;">${dayName}</div>
                        </td>
                        <td style="padding: 6px 6px; vertical-align: top; width: 20%;">
                            <div style="font-weight: 700; color: #0f172a; font-size: 12px;">${escapeHtml(ord.clientname || 'N/A')}</div>
                            <div style="font-size: 10.5px; color: #475569; margin-top: 1px;">
                                <span style="font-weight: 600; color: #0284c7;">SO: ${escapeHtml(ord.sono || '-')}</span> ${ord.psono ? `| <span style="color: #64748b;">PSO: ${escapeHtml(ord.psono)}</span>` : ''}
                            </div>
                            <div style="font-size: 10px; color: #64748b; margin-top: 1px;">
                                📞 ${escapeHtml(ord.mobileno || '-')} ${destLocation ? `| 📍 ${escapeHtml(destLocation)}` : ''}
                            </div>
                        </td>
                        <td style="padding: 6px 6px; vertical-align: top; width: 18%;">
                            <div style="font-weight: 700; color: #0f172a; font-size: 11.5px;">${escapeHtml(cleanProd)}</div>
                            <div style="font-size: 10px; margin-top: 1px;">
                                <span style="display: inline-block; padding: 1px 5px; font-size: 9.5px; font-weight: 600; border-radius: 3px; ${isGrafted ? 'background-color: #fee2e2; color: #991b1b;' : 'background-color: #f1f5f9; color: #475569;'}">${isGrafted ? 'Grafted' : 'Non-Grafted'}</span>
                                ${ord.stock_order_nos ? `<span style="color: #0284c7; margin-left: 4px; font-family: monospace;">Stock: ${escapeHtml(ord.stock_order_nos)}</span>` : ''}
                            </div>
                        </td>
                        <td style="text-align: right; padding: 6px 6px; vertical-align: top; white-space: nowrap; width: 8%;">
                            <span style="font-weight: 800; font-size: 13px; color: #166534;">${billableQty.toLocaleString()}</span>
                            <div style="font-size: 9.5px; color: #64748b;">Plants</div>
                        </td>
                        <td style="padding: 6px 6px; vertical-align: top; width: 21%; word-break: break-word;">
                            <div style="font-weight: 600; color: #334155; font-size: 11px;">${pickupType}</div>
                            <div style="font-size: 10.5px; color: #166534; font-weight: 600;">${freightDisplay}${distDisplay}</div>
                            ${addrHtml}
                            ${pointHtml}
                            ${remarkHtml}
                        </td>
                        <td style="padding: 6px 6px; vertical-align: top; width: 20%;">
                            ${vehHtml}
                        </td>
                    </tr>
                `;
            });
        }

        let docHtml = `
            <div style="padding: 0; color: #0f172a; width: 100%;">
                <!-- Header -->
                <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #23b185; padding-bottom: 10px; margin-bottom: 10px;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            ${krfCompanyLogo ? `
                                <img src="${krfCompanyLogo}" alt="Karjee Farm" style="height: 46px; width: auto; max-width: 65px; object-fit: contain; vertical-align: middle;" />
                            ` : `
                                <img src="../img/logo/logo.png" alt="Karjee Farm" style="height: 46px; width: auto; max-width: 65px; object-fit: contain; vertical-align: middle;" />
                            `}
                            <div>
                                <h2 style="margin: 0; font-size: 21px; font-weight: 900; color: #0f172a; letter-spacing: 0.5px; text-transform: uppercase; line-height: 1.1;">KARJEE FARM</h2>
                                <div style="font-size: 12.5px; font-weight: 600; color: #047857; margin-top: 2px;">Logistics & Vehicle Allocation Dispatch Schedule</div>
                            </div>
                        </div>
                    </div>
                    <div style="text-align: right; font-size: 11px; color: #475569;">
                        <div><b>Schedule Period:</b> <span style="font-weight: 700; color: #0f172a;">${monthText}</span></div>
                        <div><b>Generated On:</b> ${generatedDate}</div>
                    </div>
                </div>

                <!-- Schedule Table -->
                <table style="width: 100%; table-layout: fixed; border-collapse: collapse; margin-bottom: 12px; border: 1px solid #cbd5e1;">
                    <thead style="display: table-header-group;">
                        <tr style="background-color: #23b185; color: white; font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.5px;">
                            <th style="padding: 7px 4px; text-align: center; width: 3.5%; border: 1px solid #23b185;">#</th>
                            <th style="padding: 7px 6px; text-align: left; width: 9.5%; border: 1px solid #23b185;">Delivery Date</th>
                            <th style="padding: 7px 6px; text-align: left; width: 20%; border: 1px solid #23b185;">Client & Sales Order</th>
                            <th style="padding: 7px 6px; text-align: left; width: 18%; border: 1px solid #23b185;">Product & Variety</th>
                            <th style="padding: 7px 6px; text-align: right; width: 8%; border: 1px solid #23b185;">Qty (Plants)</th>
                            <th style="padding: 7px 6px; text-align: left; width: 21%; border: 1px solid #23b185;">Delivery & Freight</th>
                            <th style="padding: 7px 6px; text-align: left; width: 20%; border: 1px solid #23b185;">Vehicle & Driver Assignment</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${tableRowsHtml}
                    </tbody>
                </table>
            </div>
        `;

        return docHtml;
    }

});
