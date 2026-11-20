/**
 * Post-Dispatch Residual Polyhouse Stock Tracker - Frontend Application Logic
 */

$(document).ready(function () {
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

    let krfCompanyLogo = (typeof window.KRF_COMPANY_LOGO !== 'undefined') ? window.KRF_COMPANY_LOGO : '';
    let globalWeekMap = {};
    let globalSortedWeeks = [];
    let globalMonths = [];
    let currentRawData = [];
    let currentRenderedData = [];
    let currentWeekRange = "";
    let isUpdatingFilters = false;

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatProductNameDisplay(rawName) {
        if (!rawName) return '';
        return rawName.replace(/^(NonGrafted|Non-Grafted)\s*[-:]?\s*/i, '');
    }

    function isProductGrafted(rawName) {
        if (!rawName) return false;
        return (/grafted/i.test(rawName) && !/nongrafted|non-grafted/i.test(rawName));
    }

    loadActiveSurplusDates();

    function loadActiveSurplusDates() {
        let projectid = $("body").data("projectid") || 0;

        $.ajax({
            url: "modal/postdispatch_polyhouse_modal.php?action=GetActivePostDispatchDates",
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
                let curYearStr = today.getFullYear().toString();
                let curMonthStr = today.toISOString().substring(0, 7);

                let tDay = today.getDay();
                let tDiff = (tDay === 0) ? -6 : (1 - tDay);
                let tMon = new Date(today);
                tMon.setDate(today.getDate() + tDiff);
                let tSun = new Date(tMon);
                tSun.setDate(tMon.getDate() + 6);
                let tWS = tMon.toISOString().split("T")[0];
                let tWE = tSun.toISOString().split("T")[0];
                let currentWeekRangeStr = `${tWS} to ${tWE}`;

                if (!weekMap[tWS]) {
                    weekMap[tWS] = tWE;
                }

                let sortedWeeks = Object.keys(weekMap).sort();
                globalWeekMap = weekMap;
                globalSortedWeeks = sortedWeeks;

                // Unique Years
                let years = new Set();
                sortedWeeks.forEach(ws => years.add(ws.substring(0, 4)));
                years.add(curYearStr);

                $("#surplusYearFilter").html("");
                Array.from(years).sort().forEach(y => {
                    $("#surplusYearFilter").append(`<option value="${y}">${y}</option>`);
                });
                $("#surplusYearFilter").val(curYearStr);

                // Unique Months
                let months = new Set();
                sortedWeeks.forEach(ws => months.add(ws.substring(0, 7)));
                months.add(curMonthStr);
                globalMonths = Array.from(months).sort();

                populateMonths(curYearStr, curMonthStr, currentWeekRangeStr);

                // Year change
                $("#surplusYearFilter").off("change").on("change", function () {
                    let selYear = $(this).val();
                    let todayM = new Date().toISOString().substring(0, 7);
                    let defMonth = (selYear === curYearStr) ? todayM : "first";
                    populateMonths(selYear, defMonth, "first");
                });

                // Month change
                $("#surplusMonthFilter").off("change").on("change", function () {
                    populateWeeks(globalWeekMap, globalSortedWeeks, "first");
                });
            }
        });
    }

    function populateMonths(selectedYear, defaultMonth, defaultWeek) {
        let monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
        let filteredMonths = globalMonths.filter(m => m.startsWith(selectedYear));

        if (filteredMonths.length === 0) {
            for (let i = 1; i <= 12; i++) {
                let miStr = (i < 10 ? '0' + i : '' + i);
                filteredMonths.push(`${selectedYear}-${miStr}`);
            }
        }

        $("#surplusMonthFilter").html("");
        filteredMonths.forEach(m => {
            let [y, mi] = m.split("-");
            let mName = monthNames[parseInt(mi) - 1];
            let display = `${mName}-${y}`;
            $("#surplusMonthFilter").append(`<option value="${m}">${display}</option>`);
        });

        let selMonth = defaultMonth;
        if (selMonth === "first" && filteredMonths.length > 0) {
            selMonth = filteredMonths[0];
        } else if (selMonth === "last" && filteredMonths.length > 0) {
            selMonth = filteredMonths[filteredMonths.length - 1];
        } else if ($("#surplusMonthFilter").find(`option[value="${selMonth}"]`).length === 0) {
            selMonth = filteredMonths[0];
        }

        $("#surplusMonthFilter").val(selMonth);
        populateWeeks(globalWeekMap, globalSortedWeeks, defaultWeek);
    }

    function populateWeeks(weekMap, sortedWeeks, defaultWeek) {
        let selectedMonth = $("#surplusMonthFilter").val();
        let displayWeeks = sortedWeeks.filter(ws => {
            if (selectedMonth === "all") return true;
            return ws.substring(0, 7) === selectedMonth;
        });

        let $week = $("#surplusWeekSelector");
        $week.html("");
        displayWeeks.forEach(ws => {
            let range = `${ws} to ${weekMap[ws]}`;
            $week.append(`<option value="${range}">${range}</option>`);
        });

        let selectedWeek = defaultWeek;
        if (selectedWeek === "current" || selectedWeek === "first" || selectedWeek === "last") {
            let today = new Date();
            let tDay = today.getDay();
            let tDiff = (tDay === 0) ? -6 : (1 - tDay);
            let tMon = new Date(today);
            tMon.setDate(today.getDate() + tDiff);
            let tSun = new Date(tMon);
            tSun.setDate(tMon.getDate() + 6);
            let tWS = tMon.toISOString().split("T")[0];
            let tWE = tSun.toISOString().split("T")[0];
            let currentWeekRange = `${tWS} to ${tWE}`;

            if (selectedWeek === "current") {
                selectedWeek = currentWeekRange;
            } else if (selectedWeek === "first" && displayWeeks.length > 0) {
                selectedWeek = `${displayWeeks[0]} to ${weekMap[displayWeeks[0]]}`;
            } else if (selectedWeek === "last" && displayWeeks.length > 0) {
                let lastIdx = displayWeeks.length - 1;
                selectedWeek = `${displayWeeks[lastIdx]} to ${weekMap[displayWeeks[lastIdx]]}`;
            }
        }

        if ($week.find(`option[value="${selectedWeek}"]`).length === 0) {
            if (displayWeeks.length > 0) {
                selectedWeek = `${displayWeeks[0]} to ${weekMap[displayWeeks[0]]}`;
            } else {
                let today = new Date();
                let tDay = today.getDay();
                let tDiff = (tDay === 0) ? -6 : (1 - tDay);
                let tMon = new Date(today);
                tMon.setDate(today.getDate() + tDiff);
                let tSun = new Date(tMon);
                tSun.setDate(tMon.getDate() + 6);
                let tWS = tMon.toISOString().split("T")[0];
                let tWE = tSun.toISOString().split("T")[0];
                let currentWeekRange = `${tWS} to ${tWE}`;

                $week.append(`<option value="${currentWeekRange}">${currentWeekRange}</option>`);
                selectedWeek = currentWeekRange;
            }
        }

        $week.val(selectedWeek);
        loadSurplusStockSchedule(selectedWeek);
    }

    // Previous Week Navigation
    $("#surplusPrevWeekBtn").off("click").on("click", function () {
        let select = document.getElementById("surplusWeekSelector");
        if (select && select.selectedIndex > 0) {
            select.selectedIndex = select.selectedIndex - 1;
            $(select).trigger("change");
        } else {
            let monthSelect = document.getElementById("surplusMonthFilter");
            if (monthSelect && monthSelect.selectedIndex > 0) {
                monthSelect.selectedIndex = monthSelect.selectedIndex - 1;
                populateWeeks(globalWeekMap, globalSortedWeeks, "last");
            } else {
                let yearSelect = document.getElementById("surplusYearFilter");
                if (yearSelect && yearSelect.selectedIndex > 0) {
                    yearSelect.selectedIndex = yearSelect.selectedIndex - 1;
                    populateMonths($(yearSelect).val(), "last", "last");
                }
            }
        }
    });

    // Next Week Navigation
    $("#surplusNextWeekBtn").off("click").on("click", function () {
        let select = document.getElementById("surplusWeekSelector");
        if (select && select.selectedIndex < select.options.length - 1) {
            select.selectedIndex = select.selectedIndex + 1;
            $(select).trigger("change");
        } else {
            let monthSelect = document.getElementById("surplusMonthFilter");
            if (monthSelect && monthSelect.selectedIndex < monthSelect.options.length - 1) {
                monthSelect.selectedIndex = monthSelect.selectedIndex + 1;
                populateWeeks(globalWeekMap, globalSortedWeeks, "first");
            } else {
                let yearSelect = document.getElementById("surplusYearFilter");
                if (yearSelect && yearSelect.selectedIndex < yearSelect.options.length - 1) {
                    yearSelect.selectedIndex = yearSelect.selectedIndex + 1;
                    populateMonths($(yearSelect).val(), "first", "first");
                }
            }
        }
    });

    $("#surplusWeekSelector").off("change").on("change", function () {
        loadSurplusStockSchedule($(this).val());
    });

    function loadSurplusStockSchedule(weekRange) {
        let projectid = $("body").data("projectid") || 0;
        let [datefrom, dateto] = (weekRange || "").split(" to ");

        let tbody = $("#surplusStockTableBody");
        tbody.html(`<tr><td colspan="11" class="text-center py-4 text-muted"><i class="fa fa-spinner fa-spin me-2"></i>Loading Residual Polyhouse Stock for ${weekRange}...</td></tr>`);

        $.ajax({
            url: "modal/postdispatch_polyhouse_modal.php?action=GetPostDispatchSurplusStockData",
            type: "POST",
            data: {
                projectid: projectid,
                datefrom: datefrom,
                dateto: dateto
            },
            dataType: "json",
            success: function (res) {
                currentRawData = res.data ? res.data : (Array.isArray(res) ? res : []);
                currentWeekRange = weekRange;

                $("#totalRowCountDisplay").text(currentRawData.length);
                updateTopKpis(currentRawData);
                applyFilterAndRender(true);
            },
            error: function () {
                tbody.html(`<tr><td colspan="11" class="text-center py-4 text-danger fw-bold"><i class="fa fa-exclamation-triangle me-2"></i>Failed to load residual stock data.</td></tr>`);
            }
        });
    }

    function updateTopKpis(dataset) {
        let totalBatches = dataset.length;
        let totalSowingPlants = 0;
        let totalSowingTrays = 0;
        let totalDispatchedPlants = 0;
        let totalDispatchedTrays = 0;
        let totalSurplusPlants = 0;
        let totalSurplusTrays = 0;

        dataset.forEach(row => {
            let sq = parseInt(row.sowing_qty || 0);
            let polyTrays = parseInt(row.polyhouse_tray_count || 0);
            let cavity = (sq > 0 && polyTrays > 0) ? Math.round(sq / polyTrays) : 126;
            if (!cavity || cavity <= 0) cavity = 126;

            let sTrays = polyTrays > 0 ? polyTrays : Math.ceil(sq / cavity);
            let dq = parseInt(row.dispatched_qty || 0);
            let dTrays = Math.ceil(dq / cavity);
            let surpQ = parseInt(row.estimate_open_stock || 0);
            let surpTrays = Math.ceil(surpQ / cavity);

            totalSowingPlants += sq;
            totalSowingTrays += sTrays;
            totalDispatchedPlants += dq;
            totalDispatchedTrays += dTrays;
            totalSurplusPlants += surpQ;
            totalSurplusTrays += surpTrays;
        });

        $("#kpiSurplusOrdersCount").text(totalBatches);
        $("#kpiTotalSowingPlants").html(`${totalSowingTrays} Trays <span style="font-size: 14.5px; font-weight: 600; opacity: 0.9;">(${totalSowingPlants.toLocaleString()})</span>`);
        $("#kpiTotalDispatchedPlants").html(`${totalDispatchedTrays} Trays <span style="font-size: 14.5px; font-weight: 600; opacity: 0.9;">(${totalDispatchedPlants.toLocaleString()})</span>`);
        $("#kpiTotalSurplusPlants").html(`${totalSurplusTrays} Trays <span style="font-size: 14.5px; font-weight: 600; opacity: 0.9;">(${totalSurplusPlants.toLocaleString()})</span>`);
    }

    function applyFilterAndRender(updateDropdowns = true) {
        let filtered = getFilteredDataset();

        if (updateDropdowns) {
            populateDynamicFilters();
        }

        updateFilterActiveVisuals();
        $("#filteredRowCountDisplay").text(filtered.length);
        updateTopKpis(filtered);
        renderSurplusStockTable(filtered, currentWeekRange);
    }

    // Dynamic Filter Change Handlers
    $("#filterDeliveryDate, #filterClient, #filterType, #filterProduct, #filterPolyhouse").on("change", function () {
        if (isUpdatingFilters) return;
        applyFilterAndRender(true);
    });

    $("#surplusSearchInput").on("input keyup", function () {
        applyFilterAndRender(true);
    });

    $("#btnResetFilters").on("click", function () {
        isUpdatingFilters = true;
        $("#filterDeliveryDate").val("all").trigger("change.select2");
        $("#filterClient").val("all").trigger("change.select2");
        $("#filterType").val("all").trigger("change.select2");
        $("#filterProduct").val("all").trigger("change.select2");
        $("#filterPolyhouse").val("all").trigger("change.select2");
        $("#surplusSearchInput").val("");
        isUpdatingFilters = false;
        applyFilterAndRender(true);
    });

    function getFilteredDataset(excludeFilterType = null) {
        let list = currentRawData;

        // 1. Delivery Date filter
        if (excludeFilterType !== 'delivery_date') {
            let selDate = $("#filterDeliveryDate").val();
            if (selDate && selDate !== "all") {
                list = list.filter(r => r.delivery_date === selDate || r.formatted_delivery_date === selDate);
            }
        }

        // 2. Client Name / SO filter
        if (excludeFilterType !== 'client') {
            let selClient = $("#filterClient").val();
            if (selClient && selClient !== "all") {
                let target = selClient.trim().toLowerCase();
                list = list.filter(r => {
                    let clientName = (r.clientname || '').trim().toLowerCase();
                    let sono = (r.sono || '').trim().toLowerCase();
                    let psono = (r.psono || '').trim().toLowerCase();
                    return clientName === target || clientName.includes(target) || sono.includes(target) || psono.includes(target);
                });
            }
        }

        // 3. Type (Grafted / Non-Grafted) filter
        if (excludeFilterType !== 'grafted_type') {
            let selType = $("#filterType").val();
            if (selType && selType !== "all") {
                if (selType === "grafted") {
                    list = list.filter(r => isProductGrafted(r.product_name));
                } else if (selType === "nongrafted") {
                    list = list.filter(r => !isProductGrafted(r.product_name));
                }
            }
        }

        // 4. Product filter
        if (excludeFilterType !== 'product') {
            let selProduct = $("#filterProduct").val();
            if (selProduct && selProduct !== "all") {
                let target = selProduct.trim().toLowerCase();
                list = list.filter(r => (r.product_name || '').trim().toLowerCase() === target);
            }
        }

        // 5. Polyhouse filter
        if (excludeFilterType !== 'polyhouse') {
            let selPolyhouse = $("#filterPolyhouse").val();
            if (selPolyhouse && selPolyhouse !== "all") {
                list = list.filter(r => (r.polyhouse_names || '').includes(selPolyhouse));
            }
        }

        // 6. Global Search keyword
        if (excludeFilterType !== 'search') {
            let searchKeyword = ($("#surplusSearchInput").val() || "").trim().toLowerCase();
            if (searchKeyword !== "") {
                list = list.filter(r => {
                    let cleanProd = formatProductNameDisplay(r.product_name || '');
                    let haystack = `${r.clientname || ''} ${r.sono || ''} ${r.psono || ''} ${r.product_name || ''} ${cleanProd} ${r.effective_stockorderno || ''} ${r.polyhouse_names || ''} ${r.bed_numbers || ''} ${r.mobileno || ''} ${r.districtname || ''} ${r.statename || ''} ${r.delivery_address || ''}`.toLowerCase();
                    return haystack.includes(searchKeyword);
                });
            }
        }

        return list;
    }

    function populateDynamicFilters() {
        isUpdatingFilters = true;

        let curDate = $("#filterDeliveryDate").val() || "all";
        let curClient = $("#filterClient").val() || "all";
        let curType = $("#filterType").val() || "all";
        let curProduct = $("#filterProduct").val() || "all";
        let curPolyhouse = $("#filterPolyhouse").val() || "all";

        // 1. Delivery Dates dropdown
        let dateData = getFilteredDataset('delivery_date');
        let uniqueDates = {};
        dateData.forEach(item => {
            if (item.delivery_date && item.formatted_delivery_date) {
                uniqueDates[item.delivery_date] = item.formatted_delivery_date;
            }
        });
        let sortedDateKeys = Object.keys(uniqueDates).sort();
        let dateHtml = `<option value="all">All Dates (${sortedDateKeys.length})</option>`;
        sortedDateKeys.forEach(dk => {
            let isSelected = (curDate === dk) ? 'selected' : '';
            dateHtml += `<option value="${dk}" ${isSelected}>${uniqueDates[dk]}</option>`;
        });
        $("#filterDeliveryDate").html(dateHtml);
        if (!uniqueDates[curDate] && curDate !== "all") {
            $("#filterDeliveryDate").val("all");
        }

        // 2. Client dropdown
        let clientData = getFilteredDataset('client');
        let clientMap = {};
        clientData.forEach(item => {
            let clientName = (item.clientname || '').trim();
            let sono = (item.sono || '').trim();
            if (clientName !== '') {
                if (!clientMap[clientName]) clientMap[clientName] = new Set();
                if (sono) clientMap[clientName].add(sono);
            }
        });
        let clientHtml = `<option value="all">All Clients & Orders (${Object.keys(clientMap).length})</option>`;
        Object.keys(clientMap).sort().forEach(cName => {
            let sonoList = Array.from(clientMap[cName]).join(', ');
            let displayText = sonoList ? `${cName} (SO: ${sonoList})` : cName;
            let isSelected = (curClient === cName) ? 'selected' : '';
            clientHtml += `<option value="${cName}" ${isSelected}>${displayText}</option>`;
        });
        $("#filterClient").html(clientHtml);
        if (!clientMap[curClient] && curClient !== "all") {
            $("#filterClient").val("all");
        }

        // 3. Type dropdown
        let typeData = getFilteredDataset('grafted_type');
        let graftedCount = 0;
        let nonGraftedCount = 0;
        typeData.forEach(item => {
            if (isProductGrafted(item.product_name)) graftedCount++;
            else nonGraftedCount++;
        });
        let typeHtml = `
            <option value="all" ${curType === 'all' ? 'selected' : ''}>All Types (${typeData.length})</option>
            <option value="grafted" ${curType === 'grafted' ? 'selected' : ''}>Grafted (${graftedCount})</option>
            <option value="nongrafted" ${curType === 'nongrafted' ? 'selected' : ''}>Non-Grafted (${nonGraftedCount})</option>
        `;
        $("#filterType").html(typeHtml);

        // 4. Product dropdown
        let productData = getFilteredDataset('product');
        let productMap = {};
        productData.forEach(item => {
            let pName = (item.product_name || '').trim();
            if (pName !== '') productMap[pName] = true;
        });
        let productHtml = `<option value="all">All Products (${Object.keys(productMap).length})</option>`;
        Object.keys(productMap).sort().forEach(pName => {
            let isSelected = (curProduct === pName) ? 'selected' : '';
            let formattedName = formatProductNameDisplay(pName);
            productHtml += `<option value="${pName}" ${isSelected}>${formattedName}</option>`;
        });
        $("#filterProduct").html(productHtml);
        if (!productMap[curProduct] && curProduct !== "all") {
            $("#filterProduct").val("all");
        }

        // 5. Polyhouse dropdown
        let polyhouseData = getFilteredDataset('polyhouse');
        let polyhouseSet = new Set();
        polyhouseData.forEach(item => {
            let pNames = item.polyhouse_names || '';
            if (pNames && pNames !== 'N/A') {
                pNames.split(',').forEach(ph => {
                    let cleanPh = ph.trim();
                    if (cleanPh && cleanPh !== 'N/A') polyhouseSet.add(cleanPh);
                });
            }
        });
        let polyhouseHtml = `<option value="all">All Polyhouses (${polyhouseSet.size})</option>`;
        Array.from(polyhouseSet).sort().forEach(ph => {
            let isSelected = (curPolyhouse === ph) ? 'selected' : '';
            polyhouseHtml += `<option value="${ph}" ${isSelected}>${ph}</option>`;
        });
        $("#filterPolyhouse").html(polyhouseHtml);
        if (!polyhouseSet.has(curPolyhouse) && curPolyhouse !== "all") {
            $("#filterPolyhouse").val("all");
        }

        initSelect2Filters();
        isUpdatingFilters = false;
    }

    function updateFilterActiveVisuals() {
        const filters = ['#filterDeliveryDate', '#filterClient', '#filterType', '#filterProduct', '#filterPolyhouse'];
        filters.forEach(id => {
            let val = $(id).val();
            let $container = $(id).next('.select2-container');
            if (val && val !== 'all') {
                $(id).addClass('filter-applied');
                if ($container.length) $container.addClass('filter-applied');
            } else {
                $(id).removeClass('filter-applied');
                if ($container.length) $container.removeClass('filter-applied');
            }
        });

        let searchVal = ($("#surplusSearchInput").val() || "").trim();
        if (searchVal !== '') {
            $("#surplusSearchInput").closest('.input-group').addClass('filter-applied');
        } else {
            $("#surplusSearchInput").closest('.input-group').removeClass('filter-applied');
        }
    }

    function initSelect2Filters() {
        if ($.fn.select2) {
            $('.select2-filter').each(function () {
                if ($(this).hasClass("select2-hidden-accessible")) {
                    $(this).select2('destroy');
                }
                $(this).select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    dropdownAutoWidth: true
                });
            });
        }
    }

    function renderSurplusStockTable(data, weekRange) {
        currentRenderedData = data;
        let tbody = $("#surplusStockTableBody");
        tbody.empty();

        if (!data || data.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="12" class="text-center py-4 text-muted fw-bold">
                        <i class="fa fa-info-circle me-2"></i>No post-dispatch surplus orders found for: ${weekRange}
                    </td>
                </tr>
            `);
            return;
        }

        data.forEach((row, index) => {
            let rowDataJson = JSON.stringify(row).replace(/"/g, '&quot;');
            let formattedProdName = formatProductNameDisplay(row.product_name);
            let isGrafted = row.is_grafted === 1;

            let sowingQty = parseInt(row.sowing_qty || 0);
            let orderQty = parseInt(row.sp_orderqty || row.order_qty || 0);
            let readyQty = parseInt(row.ready_to_sell_qty || 0);
            let allocatedQty = parseInt(row.allocated_qty || 0);
            let dispQty = parseInt(row.dispatched_qty || 0);
            let estimateOpenStock = parseInt(row.estimate_open_stock || 0);
            let surplusQty = parseInt(row.surplus_plants_qty || 0);
            let remainingTrays = parseInt(row.remaining_trays_count || 0);
            let polyTrayCount = parseInt(row.polyhouse_tray_count || remainingTrays || 0);

            // Compute cavity and tray counts
            let cavity = 126;
            if (sowingQty > 0 && polyTrayCount > 0) {
                cavity = Math.round(sowingQty / polyTrayCount);
            }
            if (!cavity || cavity <= 0) cavity = 126;

            let orderTrays = Math.ceil(orderQty / cavity);
            let sowingTrays = polyTrayCount > 0 ? polyTrayCount : Math.ceil(sowingQty / cavity);
            let allocatedTrays = Math.ceil(allocatedQty / cavity);
            let dispTrays = Math.ceil(dispQty / cavity);
            let estOpenStockTrays = Math.ceil(estimateOpenStock / cavity);

            // Client & Contact details
            let destLoc = [row.districtname || row.dl_dest_district, row.statename || row.dl_dest_state].filter(Boolean).join(', ');
            let addr = (row.dl_deliveryaddress || row.delivery_address || '').trim();

            let clientCol = `
                <div class="fw-bold text-dark fs-6">${escapeHtml(row.clientname || 'N/A')}</div>
                <div class="text-muted small"><i class="fa fa-phone me-1 text-primary"></i>${escapeHtml(row.mobileno || '-')}</div>
                ${destLoc ? `<div class="text-secondary small"><i class="fa fa-map-marker-alt text-danger me-1"></i>${escapeHtml(destLoc)}</div>` : ''}
                ${addr ? `<div class="text-muted fst-italic small" style="font-size: 11px;">${escapeHtml(addr)}</div>` : ''}
            `;

            // Order / SO & Stock Order info
            let stockOrderText = row.effective_stockorderno || '-';
            let soCol = `
                <div class="fw-bold text-dark">${row.formatted_delivery_date || '-'}</div>
                <div class="text-muted small" style="font-size: 11.5px;">
                    <span class="fw-bold text-primary">SO: ${escapeHtml(row.sono || '-')}</span>
                    ${row.psono ? `<br><span class="text-secondary">PSO: ${escapeHtml(row.psono)}</span>` : ''}
                </div>
                <div class="mt-1 font-monospace fw-bold text-primary" style="font-size: 12px;">
                    ${escapeHtml(stockOrderText)}
                </div>
                ${row.formatted_sowing_date ? `<div class="text-muted small" style="font-size: 10.5px;"><b>Sowing:</b> ${row.formatted_sowing_date}</div>` : ''}
            `;

            // Product & Variety
            let prodCol = `
                <div class="fw-bold text-dark" style="font-size: 13px;">${escapeHtml(formattedProdName)}</div>
                <div class="mt-1">
                    <span class="badge ${isGrafted ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-secondary-subtle text-secondary border'}" style="font-size: 10px;">
                        ${isGrafted ? 'Grafted' : 'Non-Grafted'}
                    </span>
                </div>
            `;

            // Polyhouse & Bed Locations
            let polyCol = `
                <div class="fw-bold text-dark small"><i class="fa fa-warehouse text-info me-1"></i>${escapeHtml(row.polyhouse_names || 'N/A')}</div>
                <div class="text-secondary small" style="font-size: 11.5px;"><i class="fa fa-layer-group text-secondary me-1"></i><b>Beds:</b> ${escapeHtml(row.bed_numbers || '-')}</div>
            `;

            // Est. Open Stock badge
            let estOpenStockBadge;
            if (estimateOpenStock > 0) {
                estOpenStockBadge = `
                    <div class="rounded-3 text-center d-inline-block shadow-sm" style="background-color: #fff7ed; border: 1.5px solid #fdba74; min-width: 105px; padding: 7px 12px;">
                        <div class="fw-bold fs-6" style="color: #c2410c; line-height: 1.2;">${estOpenStockTrays} Trays</div>
                        <div class="fw-semibold small" style="font-size: 10px; color: #ea580c; line-height: 1; margin-top: 3px;">(${estimateOpenStock.toLocaleString()} Open Stock)</div>
                    </div>
                `;
            } else {
                estOpenStockBadge = `
                    <div class="rounded-3 text-center d-inline-block text-muted" style="background-color: #f8fafc; border: 1.5px dashed #cbd5e1; min-width: 105px; padding: 7px 12px;">
                        <div class="fw-bold fs-6" style="line-height: 1.2;">0 Trays</div>
                        <div class="small text-secondary" style="font-size: 10px; line-height: 1; margin-top: 3px;">(0 Open Stock)</div>
                    </div>
                `;
            }

            let tr = `
                <tr class="align-middle">
                    <td class="text-center fw-bold text-secondary">${index + 1}</td>
                    <td>${soCol}</td>
                    <td>${clientCol}</td>
                    <td>${prodCol}</td>
                    <td class="text-center">
                        <a href="javascript:void(0);" class="btn-col-popup text-decoration-none rounded-3 bg-white border shadow-sm d-inline-block text-start" data-col="sowing" data-row="${rowDataJson}" title="Click to view Sowing & Order Details" style="min-width: 175px; padding: 8px 12px; border-color: #cbd5e1 !important; transition: all 0.2s;">
                            <div class="d-flex justify-content-between align-items-center gap-2" style="font-size: 11.5px; white-space: nowrap;">
                                <span class="text-secondary fw-semibold">Order Qty :</span>
                                <span class="fw-bold text-primary" style="font-size: 12px;">${orderTrays} Trays (${orderQty.toLocaleString()})</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center gap-2 mt-1.5" style="font-size: 11.5px; white-space: nowrap;">
                                <span class="text-secondary fw-semibold">Sowing Qty :</span>
                                <span class="fw-bold text-success" style="font-size: 12px;">${sowingTrays} Trays (${sowingQty.toLocaleString()})</span>
                            </div>
                        </a>
                    </td>
                    <td class="text-center">
                        <a href="javascript:void(0);" class="btn-col-popup text-decoration-none rounded-3 border shadow-sm d-inline-block text-center" data-col="ready" data-row="${rowDataJson}" title="Click to view Polyhouse Trays & Status Breakdown" style="background-color: #f0fdf4; border-color: #86efac !important; color: #15803d; min-width: 105px; padding: 7px 12px; transition: all 0.2s;">
                            <div class="fw-bold fs-6" style="line-height: 1.2;">${sowingTrays} Trays</div>
                            <div class="small fw-semibold" style="font-size: 10px; opacity: 0.9; line-height: 1; margin-top: 3px;">(${(row.polyhouse_received_qty || row.total_sowing_plants_in_trays || row.sowing_qty || 0).toLocaleString()} Plants)</div>
                        </a>
                    </td>
                    <td class="text-center">
                        <a href="javascript:void(0);" class="btn-col-popup text-decoration-none rounded-3 border shadow-sm d-inline-block text-center" data-col="allocated" data-row="${rowDataJson}" title="Click to view Allocation Details" style="background-color: #f5f3ff; border-color: #c4b5fd !important; color: #7c3aed; min-width: 105px; padding: 7px 12px; transition: all 0.2s;">
                            <div class="fw-bold fs-6" style="line-height: 1.2;">${allocatedTrays} Trays</div>
                            <div class="small fw-semibold" style="font-size: 10px; opacity: 0.85; line-height: 1; margin-top: 3px;">(${allocatedQty.toLocaleString()} Allocated)</div>
                        </a>
                    </td>
                    <td class="text-center">
                        <a href="javascript:void(0);" class="btn-col-popup text-decoration-none rounded-3 border shadow-sm d-inline-block text-center" data-col="dispatched" data-row="${rowDataJson}" title="Click to view Dispatch & Delivery Details" style="background-color: #f8fafc; border-color: #cbd5e1 !important; color: #334155; min-width: 105px; padding: 7px 12px; transition: all 0.2s;">
                            <div class="fw-bold fs-6" style="line-height: 1.2;">${dispTrays} Trays</div>
                            <div class="small fw-semibold" style="font-size: 10px; opacity: 0.85; line-height: 1; margin-top: 3px;">(${dispQty.toLocaleString()} Dispatched)</div>
                        </a>
                    </td>
                    <td class="text-center">${estOpenStockBadge}</td>
                    <td>${polyCol}</td>
                    <td class="text-center" style="vertical-align: middle;">
                        <button type="button" class="btn btn-primary btn-sm btn-confirm-open-stock fw-semibold px-3 py-1.5"
                            data-consumed-id="${row.consumed_id || 0}"
                            data-allocated="${allocatedQty}"
                            data-dispatched="${dispQty}"
                            data-estimate="${estimateOpenStock}"
                            data-stockorder="${escapeHtml(row.effective_stockorderno || '')}"
                            data-client="${escapeHtml(row.clientname || '')}"
                            data-so="${escapeHtml(row.sono || '')}"
                            style="font-size: 12px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                            <i class="fa fa-edit me-1"></i> Update Qty
                        </button>
                    </td>
                </tr>
            `;
            tbody.append(tr);
        });
    }

    // Click handler for Sowing, Ready, Allocated, Dispatched column popups
    $(document).on("click", ".btn-col-popup", function (e) {
        e.preventDefault();
        let col = $(this).data("col");
        let row = $(this).data("row");
        if (!row) return;
        openColumnPopup(col, row);
    });

    // View Trays Modal Trigger
    $(document).on("click", ".btn-view-trays", function () {
        let row = $(this).data("row");
        if (!row) return;
        openColumnPopup("ready", row);
    });

    // Button to Auto-Calculate and Update Estimate Open Stock Qty in DB (Allocated - Dispatched)
    $(document).on("click", ".btn-confirm-open-stock", function (e) {
        e.preventDefault();
        let $btn = $(this);
        let consumedId = $btn.data("consumed-id");
        let allocated = parseInt($btn.data("allocated") || 0);
        let dispatched = parseInt($btn.data("dispatched") || 0);
        let autoEstOpenStock = Math.max(0, allocated - dispatched);
        let stockOrder = $btn.data("stockorder") || '';
        let client = $btn.data("client") || '';
        let so = $btn.data("so") || '';
        let projectid = $("body").data("projectid") || 0;

        Swal.fire({
            title: "Update Est. Open Stock Qty",
            html: `
                <div class="text-start mb-2">
                    <div class="p-2.5 bg-light rounded-3 border mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small"><b>Stock Order:</b></span>
                            <span class="font-monospace fw-bold text-primary">${escapeHtml(stockOrder)}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small"><b>Client:</b></span>
                            <span class="fw-semibold text-dark">${escapeHtml(client)}</span>
                        </div>
                        ${so ? `
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small"><b>Sales Order:</b></span>
                            <span class="fw-semibold text-dark">${escapeHtml(so)}</span>
                        </div>` : ''}
                    </div>

                    <!-- Auto Calculation Formula Card: Allocated - Dispatched = Est. Open Stock -->
                    <div class="card border shadow-sm mb-3">
                        <div class="card-header bg-white py-2 fw-bold text-dark small d-flex justify-content-between align-items-center">
                            <span><i class="fa fa-calculator text-primary me-1"></i> Stock Calculation</span>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 10px;">Allocated - Dispatched</span>
                        </div>
                        <div class="card-body p-2.5 bg-light">
                            <div class="row text-center g-1 align-items-center">
                                <div class="col-4">
                                    <div class="small text-secondary fw-bold text-uppercase" style="font-size: 10px;">Allocated</div>
                                    <div class="fs-6 fw-bold" style="color: #7c3aed;">${allocated.toLocaleString()}</div>
                                </div>
                                <div class="col-1 text-muted fw-bold fs-5">-</div>
                                <div class="col-3">
                                    <div class="small text-secondary fw-bold text-uppercase" style="font-size: 10px;">Dispatched</div>
                                    <div class="fs-6 fw-bold text-dark">${dispatched.toLocaleString()}</div>
                                </div>
                                <div class="col-1 text-muted fw-bold fs-5">=</div>
                                <div class="col-3">
                                    <div class="small fw-bold text-uppercase" style="font-size: 10px; color: #ea580c;">Est. Open Stock</div>
                                    <div class="fs-6 fw-bold text-danger">${autoEstOpenStock.toLocaleString()}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <label class="form-label fw-bold small text-secondary mb-1">Est. Open Stock Qty (Auto-Fetched):</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fa fa-lock text-muted"></i></span>
                        <input type="text" id="swalEstimateInput" class="form-control text-center fw-bold fs-4 text-danger bg-white border-start-0" value="${autoEstOpenStock.toLocaleString()}" readonly disabled style="letter-spacing: 0.5px;">
                    </div>
                    <div class="text-muted small mt-1.5" style="font-size: 11px;">
                        <i class="fa fa-info-circle text-primary me-1"></i> Auto-derived from <b>Allocated (${allocated.toLocaleString()}) - Dispatched (${dispatched.toLocaleString()}) = ${autoEstOpenStock.toLocaleString()}</b>.
                    </div>
                </div>`,
            icon: "info",
            showCancelButton: true,
            confirmButtonText: '<i class="fa fa-save me-1"></i> Save to DB',
            cancelButtonText: "Cancel",
            confirmButtonColor: "#0d6efd",
            cancelButtonColor: "#6c757d",
            preConfirm: () => {
                return autoEstOpenStock;
            }
        }).then(result => {
            if (!result.isConfirmed) return;

            let updatedQty = result.value;
            $btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin me-1"></i> Saving...');

            $.ajax({
                url: "modal/postdispatch_polyhouse_modal.php?action=ConfirmEstimateOpenStock",
                type: "POST",
                data: {
                    projectid: projectid,
                    consumed_id: consumedId,
                    estimate_open_stock: updatedQty
                },
                dataType: "json",
                success: function (resp) {
                    if (resp && resp.status === "success") {
                        $btn.data("estimate", updatedQty);
                        let $tr = $btn.closest("tr");
                        if (updatedQty > 0) {
                            $tr.find("td").eq(8).html(`
                                <div class="rounded-3 text-center d-inline-block shadow-sm" style="background-color: #fff7ed; border: 1.5px solid #fdba74; min-width: 100px; padding: 7px 14px;">
                                    <div class="fw-bold fs-6" style="color: #c2410c; line-height: 1.2;">${updatedQty.toLocaleString()}</div>
                                    <div class="fw-semibold small" style="font-size: 10px; color: #ea580c; line-height: 1; margin-top: 3px;">Est. Open Stock</div>
                                </div>
                            `);
                        } else {
                            $tr.find("td").eq(8).html(`
                                <div class="rounded-3 text-center d-inline-block text-muted" style="background-color: #f8fafc; border: 1.5px dashed #cbd5e1; min-width: 100px; padding: 7px 14px;">
                                    <div class="fw-bold fs-6" style="line-height: 1.2;">0</div>
                                    <div class="small text-secondary" style="font-size: 10px; line-height: 1; margin-top: 3px;">Open Stock</div>
                                </div>
                            `);
                        }

                        $btn.prop("disabled", false).html('<i class="fa fa-check me-1"></i> Updated!');
                        setTimeout(() => {
                            $btn.html('<i class="fa fa-edit me-1"></i> Update Qty');
                        }, 2000);

                        Swal.fire({
                            icon: "success",
                            title: "Updated Successfully!",
                            text: `Est. Open Stock quantity (${updatedQty.toLocaleString()}) updated in database.`,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        $btn.prop("disabled", false).html('<i class="fa fa-edit me-1"></i> Update Qty');
                        Swal.fire({ icon: "error", title: "Error", text: resp.message || "Failed to update." });
                    }
                },
                error: function () {
                    $btn.prop("disabled", false).html('<i class="fa fa-edit me-1"></i> Update Qty');
                    Swal.fire({ icon: "error", title: "Network Error", text: "Could not reach server. Please try again." });
                }
            });
        });
    });

    function openColumnPopup(activeCol, row) {
        let soiId = row.soi_id || 0;
        let stockOrderNo = row.effective_stockorderno || '';
        let projectid = $("body").data("projectid") || 0;

        let colTitles = {
            sowing: { title: "Sowing & Batch Details", icon: "fa-seedling", bg: "bg-success" },
            ready: { title: "Polyhouse Received & Status Breakdown", icon: "fa-warehouse", bg: "bg-teal", style: "background-color: #0d9488 !important;" },
            allocated: { title: "Allocation Breakdown", icon: "fa-box-archive", bg: "bg-purple", style: "background-color: #7c3aed !important;" },
            dispatched: { title: "Dispatch & Delivery Details", icon: "fa-truck-fast", bg: "bg-dark" }
        };

        let activeMeta = colTitles[activeCol] || colTitles.sowing;

        $("#growthModalIconBox").attr("class", `rounded-3 p-2 d-flex align-items-center justify-content-center text-white shadow-sm ${activeMeta.bg}`).attr("style", activeMeta.style || "width: 44px; height: 44px;");
        $("#growthModalIcon").attr("class", `fa ${activeMeta.icon} fs-5`);
        $("#orderGrowthStatusModalLabel").html(`
            ${activeMeta.title} : <span class="font-monospace text-primary fw-bold">${escapeHtml(stockOrderNo)}</span>
        `);
        $("#growthModalSubtitle").text(`Client: ${row.clientname || 'N/A'} | SO: ${row.sono || '-'} | Product: ${formatProductNameDisplay(row.product_name)}`);

        $("#orderGrowthModalBody").html(`
            <div class="text-center py-5 text-muted">
                <i class="fa fa-spinner fa-spin fa-2x me-2 text-primary"></i>
                <span class="fs-5">Loading detailed records for ${activeMeta.title}...</span>
            </div>
        `);
        $("#orderGrowthStatusModal").modal("show");

        $.ajax({
            url: "modal/postdispatch_polyhouse_modal.php?action=GetOrderGrowthAndTrayDetails",
            type: "POST",
            data: {
                projectid: projectid,
                soi_id: soiId,
                stockorder_no: stockOrderNo
            },
            dataType: "json",
            success: function (res) {
                if (res.status !== "success") {
                    $("#orderGrowthModalBody").html(`<div class="alert alert-danger">${res.message || 'Failed to load details.'}</div>`);
                    return;
                }

                let oi = res.order_item || row;
                let stkInfo = res.stock_order_info || {};
                let alloc = res.allocation_info || {};
                let trays = res.trays || [];
                let pSummary = res.polyhouse_summary || [];
                let sowingEntries = res.sowing_entries || [];
                let allAllocations = res.all_allocations || [];
                let summaryObj = res.summary || {};

                let sowingQty = parseInt(row.sowing_qty || stkInfo.quantity || oi.order_qty || 0);
                let totalReceivedPlants = parseInt(summaryObj.total_received_plants || summaryObj.total_plants_in_trays || row.polyhouse_received_qty || row.total_sowing_plants_in_trays || sowingQty);
                let totalTrays = parseInt(summaryObj.total_trays || trays.length || 0);
                let totalReadyPlants = parseInt(summaryObj.total_ready_plants || row.ready_to_sell_qty || 0);
                let readyTraysCount = parseInt(summaryObj.ready_trays_count || row.ready_trays_count || 0);
                let totalDispatchedPlants = parseInt(summaryObj.total_dispatched_plants || row.dispatched_qty || alloc.dispatchedqty || 0);
                let dispatchedTraysCount = parseInt(summaryObj.dispatched_trays_count || row.dispatched_trays_count || 0);
                let totalEmptyShiftingPlants = parseInt(summaryObj.total_empty_shifting_plants || row.empty_shifting_plant_qty || 0);
                let emptyShiftingTraysCount = parseInt(summaryObj.empty_shifting_trays_count || row.empty_shifting_trays_count || 0);
                let statusGroups = summaryObj.status_groups || [];

                let allocatedQty = parseInt(row.allocated_qty || alloc.allocate_healthyplant || 0);
                let dispQty = parseInt(row.dispatched_qty || alloc.dispatchedqty || 0);
                let estOpenStock = parseInt(row.estimate_open_stock || 0);

                let mCavity = 126;
                if (sowingQty > 0 && totalTrays > 0) {
                    mCavity = Math.round(sowingQty / totalTrays);
                }
                if (!mCavity || mCavity <= 0) mCavity = 126;

                let mSowTrays = totalTrays > 0 ? totalTrays : Math.ceil(sowingQty / mCavity);
                let mAllocTrays = Math.ceil(allocatedQty / mCavity);
                let mDispTrays = Math.ceil(dispQty / mCavity);

                // Build 4 Tabs
                let navTabsHtml = `
                    <ul class="nav nav-pills mb-3 p-2 bg-white rounded-3 shadow-sm border gap-2" id="columnDetailTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link ${activeCol === 'sowing' ? 'active' : ''}" id="tab-sowing-btn" data-bs-toggle="pill" data-toggle="pill" data-bs-target="#tab-sowing" data-target="#tab-sowing" type="button" role="tab">
                                <i class="fa fa-seedling me-1.5"></i> Sowing: ${mSowTrays} Trays (${sowingQty.toLocaleString()})
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link ${activeCol === 'ready' ? 'active' : ''}" id="tab-ready-btn" data-bs-toggle="pill" data-toggle="pill" data-bs-target="#tab-ready" data-target="#tab-ready" type="button" role="tab">
                                <i class="fa fa-warehouse me-1.5"></i> Polyhouse Received: ${totalTrays} Trays (${totalReceivedPlants.toLocaleString()})
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link ${activeCol === 'allocated' ? 'active' : ''}" id="tab-allocated-btn" data-bs-toggle="pill" data-toggle="pill" data-bs-target="#tab-allocated" data-target="#tab-allocated" type="button" role="tab">
                                <i class="fa fa-box-archive me-1.5"></i> Allocated: ${mAllocTrays} Trays (${allocatedQty.toLocaleString()})
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link ${activeCol === 'dispatched' ? 'active' : ''}" id="tab-dispatched-btn" data-bs-toggle="pill" data-toggle="pill" data-bs-target="#tab-dispatched" data-target="#tab-dispatched" type="button" role="tab">
                                <i class="fa fa-truck-fast me-1.5"></i> Dispatched: ${mDispTrays} Trays (${dispQty.toLocaleString()})
                            </button>
                        </li>
                    </ul>
                `;

                // ----------------------------------------------------
                // 1. SOWING CONTENT
                // ----------------------------------------------------
                let sowingRowsHtml = '';
                if (sowingEntries.length > 0) {
                    sowingEntries.forEach((s, idx) => {
                        let sqty = parseInt(s.totalsowingqty || 0);
                        let tcav = parseInt(s.traycavity || 0);
                        let tcount = parseInt(s.totaltrays || 0);
                        sowingRowsHtml += `
                            <tr>
                                <td class="text-center">${idx + 1}</td>
                                <td class="font-monospace fw-bold text-primary">#${s.sowingplantid || '-'}</td>
                                <td>${escapeHtml(s.formatted_date || '-')}</td>
                                <td class="text-end fw-bold text-success">${sqty.toLocaleString()}</td>
                                <td class="text-center">${tcav > 0 ? tcav : '-'}</td>
                                <td class="text-center">${tcount > 0 ? tcount : '-'}</td>
                                <td class="text-center">${escapeHtml(s.roottype || 'Root')}</td>
                                <td><span class="badge bg-light text-dark border">${escapeHtml(s.lotno || '-')}</span></td>
                                <td class="text-center"><span class="badge ${s.health_status === 'Healthy' ? 'bg-success' : 'bg-secondary'}">${escapeHtml(s.health_status || 'Healthy')}</span></td>
                                <td class="text-center"><span class="badge bg-info text-dark">${escapeHtml(s.workingstatus || s.status || 'Sown')}</span></td>
                            </tr>
                        `;
                    });
                } else {
                    sowingRowsHtml = `
                        <tr>
                            <td class="text-center">1</td>
                            <td class="font-monospace fw-bold text-primary">${escapeHtml(stockOrderNo)}</td>
                            <td>${escapeHtml(row.formatted_sowing_date || stkInfo.formatted_sowing_date || '-')}</td>
                            <td class="text-end fw-bold text-success">${sowingQty.toLocaleString()}</td>
                            <td class="text-center">-</td>
                            <td class="text-center">${trays.length || '-'}</td>
                            <td class="text-center">${row.is_grafted === 1 ? 'Grafted' : 'Non-Grafted'}</td>
                            <td><span class="badge bg-light text-dark border">-</span></td>
                            <td class="text-center"><span class="badge bg-success">Healthy</span></td>
                            <td class="text-center"><span class="badge bg-info text-dark">Sown</span></td>
                        </tr>
                    `;
                }

                let mOrderQty = parseInt(row.sp_orderqty || oi.order_qty || row.order_qty || 0);
                let mOrderTrays = Math.ceil(mOrderQty / mCavity);
                let mEstOpenStockTrays = Math.ceil(estOpenStock / mCavity);

                let sowingContentHtml = `
                    <div class="row g-3">
                        <div class="col-md-3 col-sm-6">
                            <div class="p-3 bg-white border rounded shadow-sm">
                                <span class="text-secondary small fw-bold text-uppercase">Total Sown Quantity</span>
                                <div class="fs-4 fw-bold text-success mt-1">${mSowTrays} Trays <span class="fs-6 fw-semibold text-secondary">(${sowingQty.toLocaleString()})</span></div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="p-3 bg-white border rounded shadow-sm">
                                <span class="text-secondary small fw-bold text-uppercase">Order Quantity</span>
                                <div class="fs-4 fw-bold text-primary mt-1">${mOrderTrays} Trays <span class="fs-6 fw-semibold text-secondary">(${mOrderQty.toLocaleString()})</span></div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="p-3 bg-white border rounded shadow-sm">
                                <span class="text-secondary small fw-bold text-uppercase">Sowing Date</span>
                                <div class="fs-5 fw-bold text-dark mt-1">${escapeHtml(row.formatted_sowing_date || stkInfo.formatted_sowing_date || '-')}</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="p-3 bg-white border rounded shadow-sm">
                                <span class="text-secondary small fw-bold text-uppercase">Stock Batch / Type</span>
                                <div class="fs-5 fw-bold text-info mt-1">${escapeHtml(stockOrderNo)}</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card border shadow-sm">
                                <div class="card-header bg-white py-2.5 fw-bold text-dark d-flex justify-content-between align-items-center">
                                    <span><i class="fa fa-info-circle text-success me-1"></i> Stock Order & Sowing Information</span>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1">Batch: ${escapeHtml(stockOrderNo)}</span>
                                </div>
                                <div class="card-body p-3">
                                    <table class="table table-bordered mb-0">
                                        <tbody>
                                            <tr>
                                                <th class="bg-light" style="width: 25%;">Stock Order / Batch No</th>
                                                <td class="fw-bold text-primary font-monospace">${escapeHtml(stockOrderNo)}</td>
                                                <th class="bg-light" style="width: 25%;">Product Name & Variety</th>
                                                <td class="fw-bold">${escapeHtml(formatProductNameDisplay(row.product_name))}</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">Client / Customer</th>
                                                <td>${escapeHtml(row.clientname || oi.clientname || 'N/A')} (${escapeHtml(row.mobileno || oi.mobileno || '-')})</td>
                                                <th class="bg-light">Delivery Date</th>
                                                <td>${escapeHtml(row.formatted_delivery_date || '-')}</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">Polyhouse Location</th>
                                                <td><i class="fa fa-warehouse text-info me-1"></i>${escapeHtml(row.polyhouse_names || '-')}</td>
                                                <th class="bg-light">Bed / Rack Assignment</th>
                                                <td><b>${escapeHtml(row.bed_numbers || '-')}</b></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                let totalHealthyPlants = parseInt(summaryObj.total_healthy_plants || 0);
                let healthyTraysCount = parseInt(summaryObj.healthy_trays_count || 0);
                let totalUnhealthyPlants = parseInt(summaryObj.total_unhealthy_plants || 0);
                let unhealthyTraysCount = parseInt(summaryObj.unhealthy_trays_count || 0);
                let totalShiftedPlants = parseInt(summaryObj.total_shifted_plants || 0);
                let shiftedTraysCount = parseInt(summaryObj.shifted_trays_count || 0);
                let totalNonShiftedPlants = parseInt(summaryObj.total_nonshifted_plants || 0);
                let nonshiftedTraysCount = parseInt(summaryObj.nonshifted_trays_count || 0);
                let healthGroups = summaryObj.health_groups || [];
                let shiftingGroups = summaryObj.shifting_groups || [];

                // Health Status Badges HTML
                let healthBadgesHtml = '';
                if (healthGroups.length > 0) {
                    healthGroups.forEach(hg => {
                        healthBadgesHtml += `
                            <span class="badge ${hg.badge_class || 'bg-light text-dark border'} px-2.5 py-1.5 fw-semibold" style="font-size: 11.5px;">
                                <i class="fa fa-circle me-1" style="font-size: 8px;"></i>${escapeHtml(hg.health_label)}: <b>${hg.tray_count} Trays (${hg.plant_qty.toLocaleString()} Plants)</b>
                            </span>
                        `;
                    });
                } else {
                    healthBadgesHtml = `
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 fw-semibold" style="font-size: 11.5px;">
                            <i class="fa fa-circle-check me-1"></i>Healthy: <b>${healthyTraysCount || totalTrays} Trays (${(totalHealthyPlants || totalReceivedPlants).toLocaleString()} Plants)</b>
                        </span>
                    `;
                }

                // Shifting Status Badges HTML
                let shiftingBadgesHtml = `
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <span class="badge ${totalShiftedPlants > 0 ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-light text-secondary border'} px-2.5 py-1.5 fw-semibold" style="font-size: 11.5px;">
                            <i class="fa fa-shuffle me-1"></i>Shifted: <b>${shiftedTraysCount} Trays (${totalShiftedPlants.toLocaleString()} Plants)</b>
                        </span>
                        <span class="badge ${totalNonShiftedPlants > 0 ? 'bg-info-subtle text-info border border-info-subtle' : 'bg-light text-secondary border'} px-2.5 py-1.5 fw-semibold" style="font-size: 11.5px;">
                            <i class="fa fa-arrows-to-dot me-1"></i>Non-Shifted: <b>${nonshiftedTraysCount} Trays (${totalNonShiftedPlants.toLocaleString()} Plants)</b>
                        </span>
                    </div>
                `;

                // Polyhouse Cards with Health & Shifting
                let polyCardsHtml = '';
                pSummary.forEach(p => {
                    let pTotalPlants = parseInt(p.total_plants || p.ready_plants || 0);
                    let pHealthy = parseInt(p.healthy_plants || 0);
                    let pHealthyTrays = parseInt(p.healthy_trays_count || p.tray_count || 0);
                    let pReadyTrays = parseInt(p.ready_trays_count || (p.ready_plants > 0 ? Math.ceil(p.ready_plants / 126) : 0));
                    let pShifted = parseInt(p.shifted_plants || 0);
                    let pShiftedTrays = parseInt(p.shifted_trays_count || 0);
                    let pNonShifted = parseInt(p.nonshifted_plants || 0);
                    let pNonShiftedTrays = parseInt(p.nonshifted_trays_count || p.tray_count || 0);

                    polyCardsHtml += `
                        <div class="col-md-6 col-sm-12">
                            <div class="p-3 bg-white border rounded shadow-sm">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <div class="fw-bold text-dark fs-6"><i class="fa fa-warehouse text-info me-1.5"></i>${escapeHtml(p.polyhousename)}</div>
                                    <span class="badge bg-light text-dark border font-monospace">Total: <b>${p.tray_count} Trays (${pTotalPlants.toLocaleString()} Plants)</b></span>
                                </div>
                                <div class="d-flex flex-wrap gap-2 align-items-center mt-2" style="font-size: 12px;">
                                    <span class="text-success"><i class="fa fa-check-circle me-1"></i>Ready: <b>${pReadyTrays} Trays (${p.ready_plants.toLocaleString()} Plants)</b></span>
                                    <span class="text-muted">|</span>
                                    <span class="text-primary"><i class="fa fa-heart-pulse me-1"></i>Healthy: <b>${pHealthyTrays} Trays (${(pHealthy || pTotalPlants).toLocaleString()} Plants)</b></span>
                                    <span class="text-muted">|</span>
                                    <span class="text-secondary"><i class="fa fa-shuffle me-1"></i>Shifted: <b>${pShiftedTrays} Trays (${pShifted.toLocaleString()})</b> / Non-Shifted: <b>${pNonShiftedTrays} Trays (${pNonShifted.toLocaleString()})</b></span>
                                </div>
                            </div>
                        </div>
                    `;
                });

                let readyContentHtml = `
                    <div class="row g-3">
                        <!-- Top KPI 1: Total Received Plants in Polyhouse -->
                        <div class="col-md-3 col-sm-6">
                            <div class="p-3 bg-white border rounded shadow-sm" style="border-left: 4px solid #0d9488 !important;">
                                <span class="text-secondary small fw-bold text-uppercase">Total Received in Polyhouse</span>
                                <div class="fs-4 fw-bold mt-1" style="color: #0d9488;">${totalTrays} Trays <span class="fs-6 fw-semibold text-secondary">(${totalReceivedPlants.toLocaleString()})</span></div>
                            </div>
                        </div>

                        <!-- Top KPI 2: Currently Ready to Sale -->
                        <div class="col-md-3 col-sm-6">
                            <div class="p-3 bg-white border rounded shadow-sm" style="border-left: 4px solid #059669 !important;">
                                <span class="text-secondary small fw-bold text-uppercase">Currently Ready to Sale</span>
                                <div class="fs-4 fw-bold text-success mt-1">${readyTraysCount} Trays <span class="fs-6 fw-semibold text-secondary">(${totalReadyPlants.toLocaleString()})</span></div>
                            </div>
                        </div>

                        <!-- Top KPI 3: Dispatched to Client -->
                        <div class="col-md-3 col-sm-6">
                            <div class="p-3 bg-white border rounded shadow-sm" style="border-left: 4px solid #1e293b !important;">
                                <span class="text-secondary small fw-bold text-uppercase">Dispatched to Client</span>
                                <div class="fs-4 fw-bold text-dark mt-1">${dispatchedTraysCount} Trays <span class="fs-6 fw-semibold text-secondary">(${totalDispatchedPlants.toLocaleString()})</span></div>
                            </div>
                        </div>

                        <!-- Top KPI 4: Empty After Shifting -->
                        <div class="col-md-3 col-sm-6">
                            <div class="p-3 bg-white border rounded shadow-sm" style="border-left: 4px solid #d97706 !important;">
                                <span class="text-secondary small fw-bold text-uppercase">Empty After Shifting</span>
                                <div class="fs-4 fw-bold text-warning mt-1" style="color: #d97706 !important;">${emptyShiftingTraysCount} Trays <span class="fs-6 fw-semibold text-secondary">(${totalEmptyShiftingPlants.toLocaleString()})</span></div>
                            </div>
                        </div>

                        <!-- NEW: Plant Health Status Card -->
                        <div class="col-md-6 col-sm-12">
                            <div class="card border shadow-sm h-100">
                                <div class="card-header bg-white py-2.5 fw-bold text-dark d-flex justify-content-between align-items-center">
                                    <span><i class="fa fa-heart-pulse text-danger me-1.5"></i> Plant Health Status</span>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                        <i class="fa fa-check me-1"></i>${healthyTraysCount > 0 ? `${healthyTraysCount} / ${totalTrays} Trays (${totalHealthyPlants.toLocaleString()} Plants)` : 'Healthy'}
                                    </span>
                                </div>
                                <div class="card-body p-3 bg-light">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="small text-secondary fw-semibold text-uppercase">Health Distribution:</div>
                                        <div class="small fw-bold text-success">Total: ${totalTrays} Trays (${totalReceivedPlants.toLocaleString()} Plants)</div>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2 align-items-center">
                                        ${healthBadgesHtml}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- NEW: Shifting Status Card -->
                        <div class="col-md-6 col-sm-12">
                            <div class="card border shadow-sm h-100">
                                <div class="card-header bg-white py-2.5 fw-bold text-dark d-flex justify-content-between align-items-center">
                                    <span><i class="fa fa-shuffle text-primary me-1.5"></i> Shifting Status</span>
                                    <span class="badge ${totalShiftedPlants > 0 ? 'bg-primary text-white' : 'bg-info-subtle text-info border border-info-subtle'} px-2 py-1">
                                        ${totalShiftedPlants > 0 ? `${shiftedTraysCount} Trays (${totalShiftedPlants.toLocaleString()} Plants) Shifted` : `Non-Shifted: ${nonshiftedTraysCount} Trays (${totalNonShiftedPlants.toLocaleString()} Plants)`}
                                    </span>
                                </div>
                                <div class="card-body p-3 bg-light">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="small text-secondary fw-semibold text-uppercase">Tray Shifting Posture:</div>
                                        <div class="small fw-bold text-primary">Total: ${totalTrays} Trays (${totalReceivedPlants.toLocaleString()} Plants)</div>
                                    </div>
                                    ${shiftingBadgesHtml}
                                </div>
                            </div>
                        </div>

                        <!-- Polyhouse Area & Bed Location Assignment -->
                        <div class="col-12">
                            <div class="card border shadow-sm">
                                <div class="card-header bg-white py-2.5 fw-bold text-dark d-flex justify-content-between align-items-center">
                                    <span><i class="fa fa-chart-pie text-success me-1"></i> Polyhouse Area & Bed Breakdown</span>
                                    <span class="badge bg-light text-dark border">Location: <b>${escapeHtml(row.polyhouse_names || '-')}</b> | Beds: <b>${escapeHtml(row.bed_numbers || '-')}</b></span>
                                </div>
                                <div class="card-body p-3 bg-light">
                                    <div class="row g-2">${polyCardsHtml || '<div class="col-12 text-muted">No polyhouse locations mapped.</div>'}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                // ----------------------------------------------------
                // 3. ALLOCATED CONTENT
                // ----------------------------------------------------
                let allocRowsHtml = '';
                if (allAllocations.length > 0) {
                    allAllocations.forEach((al, aIdx) => {
                        let aQty = parseInt(al.allocate_healthyplant || al.consumed_qty || 0);
                        let dQty = parseInt(al.dispatchedqty || 0);
                        let openStk = parseInt(al.estimate_open_stock || 0);
                        let alATrays = Math.ceil(aQty / mCavity);
                        let alDTrays = Math.ceil(dQty / mCavity);
                        let alOTrays = Math.ceil(openStk / mCavity);
                        allocRowsHtml += `
                            <tr>
                                <td class="text-center">${aIdx + 1}</td>
                                <td class="font-monospace fw-bold text-primary">#${al.id}</td>
                                <td><b>${escapeHtml(al.sono || row.sono || '-')}</b></td>
                                <td>${escapeHtml(al.clientname || row.clientname || '-')}</td>
                                <td class="text-end fw-bold" style="color: #7c3aed;">${alATrays} Trays (${aQty.toLocaleString()})</td>
                                <td class="text-end fw-bold text-dark">${alDTrays} Trays (${dQty.toLocaleString()})</td>
                                <td class="text-end fw-bold text-danger">${alOTrays} Trays (${openStk.toLocaleString()})</td>
                                <td class="text-center"><span class="badge ${al.confirmstatus === 'Confirmed' ? 'bg-success' : 'bg-warning text-dark'}">${escapeHtml(al.confirmstatus || 'Confirmed')}</span></td>
                                <td>${escapeHtml(al.formatted_allocated_at || '-')}</td>
                            </tr>
                        `;
                    });
                } else {
                    allocRowsHtml = `
                        <tr>
                            <td class="text-center">1</td>
                            <td class="font-monospace fw-bold text-primary">#${row.consumed_id || alloc.id || '-'}</td>
                            <td><b>${escapeHtml(row.sono || '-')}</b></td>
                            <td>${escapeHtml(row.clientname || '-')}</td>
                            <td class="text-end fw-bold" style="color: #7c3aed;">${mAllocTrays} Trays (${allocatedQty.toLocaleString()})</td>
                            <td class="text-end fw-bold text-dark">${mDispTrays} Trays (${dispQty.toLocaleString()})</td>
                            <td class="text-end fw-bold text-danger">${mEstOpenStockTrays} Trays (${estOpenStock.toLocaleString()})</td>
                            <td class="text-center"><span class="badge bg-success">Confirmed</span></td>
                            <td>${escapeHtml(alloc.formatted_allocated_at || row.allocated_at || '-')}</td>
                        </tr>
                    `;
                }

                let allocatedContentHtml = `
                    <div class="row g-3">
                        <div class="col-md-3 col-sm-6">
                            <div class="p-3 bg-white border rounded shadow-sm" style="border-left: 4px solid #7c3aed !important;">
                                <span class="text-secondary small fw-bold text-uppercase">Allocated Healthy Plants</span>
                                <div class="fs-4 fw-bold mt-1" style="color: #7c3aed;">${mAllocTrays} Trays <span class="fs-6 fw-semibold text-secondary">(${allocatedQty.toLocaleString()})</span></div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="p-3 bg-white border rounded shadow-sm">
                                <span class="text-secondary small fw-bold text-uppercase">Dispatched to Client</span>
                                <div class="fs-4 fw-bold text-dark mt-1">${mDispTrays} Trays <span class="fs-6 fw-semibold text-secondary">(${dispQty.toLocaleString()})</span></div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="p-3 bg-white border rounded shadow-sm" style="background-color: #fff7ed; border-color: #fed7aa !important;">
                                <span class="small fw-bold text-uppercase" style="color: #ea580c;">Est. Open Stock</span>
                                <div class="fs-4 fw-bold mt-1" style="color: #c2410c;">${mEstOpenStockTrays} Trays <span class="fs-6 fw-semibold text-secondary">(${estOpenStock.toLocaleString()})</span></div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="p-3 bg-white border rounded shadow-sm">
                                <span class="text-secondary small fw-bold text-uppercase">Allocation Status</span>
                                <div class="fs-5 fw-bold text-success mt-1"><i class="fa fa-check-circle me-1"></i>${escapeHtml(alloc.confirmstatus || row.consumed_confirmstatus || 'Confirmed')}</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card border shadow-sm">
                                <div class="card-header bg-white py-2.5 fw-bold text-dark"><i class="fa fa-file-invoice text-primary me-1"></i> Allocation Record Details (krf_productionorder_consumed)</div>
                                <div class="card-body p-3">
                                    <table class="table table-bordered mb-0">
                                        <tbody>
                                            <tr>
                                                <th class="bg-light" style="width: 25%;">Consumed ID</th>
                                                <td class="font-monospace">#${row.consumed_id || alloc.id || '-'}</td>
                                                <th class="bg-light" style="width: 25%;">Stock Order No</th>
                                                <td class="fw-bold text-primary font-monospace">${escapeHtml(stockOrderNo)}</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">Sales Order / PSO No</th>
                                                <td>SO: <b>${escapeHtml(row.sono || '-')}</b> | PSO: <b>${escapeHtml(row.psono || '-')}</b></td>
                                                <th class="bg-light">Client Name</th>
                                                <td class="fw-bold">${escapeHtml(row.clientname || '-')}</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">Allocated Date & Time</th>
                                                <td>${escapeHtml(alloc.formatted_allocated_at || row.allocated_at || '-')}</td>
                                                <th class="bg-light">Allocated By</th>
                                                <td>${escapeHtml(alloc.allocatedby || row.allocated_by_id || 'System')}</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">Remark / Notes</th>
                                                <td colspan="3">${escapeHtml(alloc.remark || row.consumed_remark || 'No remark')}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card border shadow-sm">
                                <div class="card-header bg-white py-2.5 fw-bold text-dark d-flex justify-content-between align-items-center">
                                    <span><i class="fa fa-list-check text-purple me-1" style="color: #7c3aed;"></i> All Allocations Associated with this Batch</span>
                                    <span class="badge bg-purple text-white" style="background-color: #7c3aed;">Total Allocated: ${mAllocTrays} Trays (${allocatedQty.toLocaleString()} Plants)</span>
                                </div>
                                <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                                    <table class="table table-sm table-bordered table-hover mb-0" style="font-size: 12.5px;">
                                        <thead class="table-light sticky-top">
                                            <tr>
                                                <th class="text-center" style="width: 45px;">#</th>
                                                <th>Alloc ID</th>
                                                <th>SO No</th>
                                                <th>Client Name</th>
                                                <th class="text-end">Allocated</th>
                                                <th class="text-end">Dispatched</th>
                                                <th class="text-end">Est. Surplus</th>
                                                <th class="text-center">Status</th>
                                                <th>Allocated Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${allocRowsHtml}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                // ----------------------------------------------------
                // 4. DISPATCHED CONTENT
                // ----------------------------------------------------
                let dispatchedContentHtml = `
                    <div class="row g-3">
                        <div class="col-md-3 col-sm-6">
                            <div class="p-3 bg-white border rounded shadow-sm" style="border-left: 4px solid #0f172a !important;">
                                <span class="text-secondary small fw-bold text-uppercase">Dispatched Quantity</span>
                                <div class="fs-4 fw-bold text-dark mt-1">${mDispTrays} Trays <span class="fs-6 fw-semibold text-secondary">(${dispQty.toLocaleString()})</span></div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="p-3 bg-white border rounded shadow-sm">
                                <span class="text-secondary small fw-bold text-uppercase">Initial Allocation</span>
                                <div class="fs-4 fw-bold mt-1" style="color: #7c3aed;">${mAllocTrays} Trays <span class="fs-6 fw-semibold text-secondary">(${allocatedQty.toLocaleString()})</span></div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="p-3 bg-white border rounded shadow-sm">
                                <span class="text-secondary small fw-bold text-uppercase">Dispatch Status</span>
                                <div class="fs-5 fw-bold text-success mt-1"><i class="fa fa-circle-check me-1"></i>${escapeHtml(row.consumed_status || alloc.status || 'Dispatched')}</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="p-3 bg-white border rounded shadow-sm">
                                <span class="text-secondary small fw-bold text-uppercase">Dispatch Date</span>
                                <div class="fs-5 fw-bold text-dark mt-1">${escapeHtml(row.formatted_delivery_date || '-')}</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card border shadow-sm">
                                <div class="card-header bg-white py-2.5 fw-bold text-dark"><i class="fa fa-truck text-dark me-1"></i> Client Delivery & Final Dispatch Summary</div>
                                <div class="card-body p-3">
                                    <table class="table table-bordered mb-0">
                                        <tbody>
                                            <tr>
                                                <th class="bg-light" style="width: 25%;">Client Name</th>
                                                <td class="fw-bold">${escapeHtml(row.clientname || oi.clientname || 'N/A')}</td>
                                                <th class="bg-light" style="width: 25%;">Contact Mobile</th>
                                                <td><i class="fa fa-phone text-primary me-1"></i>${escapeHtml(row.mobileno || oi.mobileno || '-')}</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">Delivery Destination</th>
                                                <td colspan="3"><i class="fa fa-map-marker-alt text-danger me-1"></i>${escapeHtml([row.districtname, row.statename, row.delivery_address].filter(Boolean).join(', ') || '-')}</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">Dispatched At / Date</th>
                                                <td>${escapeHtml(alloc.formatted_dispatched_at || row.dispatched_at || row.formatted_delivery_date || '-')}</td>
                                                <th class="bg-light">Dispatched By</th>
                                                <td>${escapeHtml(alloc.dispatchedby || row.dispatched_by_id || 'System')}</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">Order & PSO Ref</th>
                                                <td>SO: <b>${escapeHtml(row.sono || '-')}</b> | PSO: <b>${escapeHtml(row.psono || '-')}</b></td>
                                                <th class="bg-light">Delivered Quantity</th>
                                                <td class="fw-bold text-success fs-6">${mDispTrays} Trays (${dispQty.toLocaleString()} plants)</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                // Render Full Modal Body with dynamic tabs
                $("#orderGrowthModalBody").html(`
                    ${navTabsHtml}
                    <div class="tab-content" id="columnDetailTabsContent">
                        <div class="tab-pane fade ${activeCol === 'sowing' ? 'show active' : ''}" id="tab-sowing" role="tabpanel">
                            ${sowingContentHtml}
                        </div>
                        <div class="tab-pane fade ${activeCol === 'ready' ? 'show active' : ''}" id="tab-ready" role="tabpanel">
                            ${readyContentHtml}
                        </div>
                        <div class="tab-pane fade ${activeCol === 'allocated' ? 'show active' : ''}" id="tab-allocated" role="tabpanel">
                            ${allocatedContentHtml}
                        </div>
                        <div class="tab-pane fade ${activeCol === 'dispatched' ? 'show active' : ''}" id="tab-dispatched" role="tabpanel">
                            ${dispatchedContentHtml}
                        </div>
                    </div>
                `);

                // Handle tab switching explicitly to ensure 100% reliable navigation across all browser & Bootstrap versions
                $("#columnDetailTabs").on("click", ".nav-link", function (e) {
                    e.preventDefault();
                    let $btn = $(this);
                    let targetId = $btn.attr("data-bs-target") || $btn.attr("data-target") || $btn.attr("href");
                    if (!targetId) return;

                    // 1. Update tab button active states
                    $("#columnDetailTabs .nav-link").removeClass("active");
                    $btn.addClass("active");

                    // 2. Update tab pane visible states
                    $("#columnDetailTabsContent .tab-pane").removeClass("show active");
                    $(targetId).addClass("show active");

                    // 3. Update modal header icon, title & styles dynamically
                    let tabKey = targetId.replace("#tab-", "");
                    let meta = colTitles[tabKey] || colTitles.sowing;
                    $("#growthModalIconBox")
                        .attr("class", `rounded-3 p-2 d-flex align-items-center justify-content-center text-white shadow-sm ${meta.bg}`)
                        .attr("style", meta.style || "width: 44px; height: 44px;");
                    $("#growthModalIcon").attr("class", `fa ${meta.icon} fs-5`);
                    $("#orderGrowthStatusModalLabel").html(`
                        ${meta.title} : <span class="font-monospace text-primary fw-bold">${escapeHtml(stockOrderNo)}</span>
                    `);
                });
            }
        });
    }

    // ==========================================
    // EXCEL EXPORT
    // ==========================================
    $("#btnExportExcel").on("click", function () {
        let orders = currentRenderedData || [];
        if (!orders || orders.length === 0) {
            Swal.fire("No Data", "No residual stock orders available in current view to export.", "warning");
            return;
        }

        if (typeof XLSX === 'undefined') {
            Swal.fire("Error", "Excel library (SheetJS) is not loaded. Please refresh the page.", "error");
            return;
        }

        let excelRows = [];
        orders.forEach((row, index) => {
            let polyhouseNo = (row.polyhouse_names && row.polyhouse_names !== 'N/A') ? row.polyhouse_names : '-';
            let bedNo = (row.bed_numbers && row.bed_numbers !== 'N/A') ? row.bed_numbers : '-';
            let orderNo = (row.effective_stockorderno && row.effective_stockorderno !== 'Not Allocated') ? row.effective_stockorderno : (row.soi_stockorderno || '-');

            let clientName = row.clientname || 'N/A';
            let destLoc = [row.districtname || row.dl_dest_district, row.statename || row.dl_dest_state].filter(Boolean).join(', ');
            let clientAddr = (row.dl_deliveryaddress || row.delivery_address || '').trim();
            let contactNo = row.mobileno || '-';

            let cleanProd = formatProductNameDisplay(row.product_name || 'Crop');
            let isGrafted = (row.is_grafted === 1);

            let sowingQty = parseInt(row.sowing_qty || 0);
            let orderQty = parseInt(row.sp_orderqty || row.order_qty || 0);
            let polyhouseReceivedQty = parseInt(row.polyhouse_received_qty || row.total_sowing_plants_in_trays || row.sowing_qty || 0);
            let allocatedQty = parseInt(row.allocated_qty || 0);
            let dispQty = parseInt(row.dispatched_qty || 0);
            let estimateOpenStock = parseInt(row.estimate_open_stock || 0);
            let surplusQty = parseInt(row.surplus_plants_qty || row.ready_to_sell_qty || 0);
            let remainingTrays = parseInt(row.remaining_trays_count || 0);
            let polyTrayCount = parseInt(row.polyhouse_tray_count || remainingTrays || 0);

            let cavity = 126;
            if (sowingQty > 0 && polyTrayCount > 0) {
                cavity = Math.round(sowingQty / polyTrayCount);
            }
            if (!cavity || cavity <= 0) cavity = 126;

            let orderTrays = Math.ceil(orderQty / cavity);
            let sowingTrays = polyTrayCount > 0 ? polyTrayCount : Math.ceil(sowingQty / cavity);
            let allocatedTrays = Math.ceil(allocatedQty / cavity);
            let dispTrays = Math.ceil(dispQty / cavity);
            let estOpenStockTrays = Math.ceil(estimateOpenStock / cavity);

            let sowingDate = row.formatted_sowing_date || row.pso_sowingdate || '-';
            let dispatchDate = row.formatted_delivery_date || row.delivery_date || '-';

            excelRows.push({
                "S.No": index + 1,
                "Dispatch Date": dispatchDate,
                "Sales Order (SO)": row.sono || '-',
                "PSO No": row.psono || '-',
                "Stock Order No": orderNo,
                "Sowing Date": sowingDate,
                "Client Name": clientName,
                "Contact Number": contactNo,
                "Location": destLoc || '-',
                "Delivery Address": clientAddr || '-',
                "Product & Variety": cleanProd,
                "Type": isGrafted ? 'Grafted' : 'Non-Grafted',
                "Order Qty": `${orderTrays} Trays (${orderQty.toLocaleString()})`,
                "Sowing Qty": `${sowingTrays} Trays (${sowingQty.toLocaleString()})`,
                "Polyhouse Received": `${sowingTrays} Trays (${polyhouseReceivedQty.toLocaleString()} Plants)`,
                "Allocated": `${allocatedTrays} Trays (${allocatedQty.toLocaleString()} Allocated)`,
                "Dispatched": `${dispTrays} Trays (${dispQty.toLocaleString()} Dispatched)`,
                "Est. Open Stock": `${estOpenStockTrays} Trays (${estimateOpenStock.toLocaleString()} Open Stock)`,
                "Polyhouse": polyhouseNo,
                "Beds": bedNo,
                "Available in Polyhouse": estimateOpenStock > 0 ? `${estOpenStockTrays} Trays (${estimateOpenStock.toLocaleString()} Open Stock)` : '0 Trays (0 Open Stock)'
            });
        });

        let ws = XLSX.utils.json_to_sheet(excelRows);
        ws['!cols'] = [
            { wch: 6 },  // S.No
            { wch: 14 }, // Dispatch Date
            { wch: 15 }, // Sales Order (SO)
            { wch: 15 }, // PSO No
            { wch: 15 }, // Stock Order No
            { wch: 14 }, // Sowing Date
            { wch: 28 }, // Client Name
            { wch: 15 }, // Contact Number
            { wch: 25 }, // Location
            { wch: 35 }, // Delivery Address
            { wch: 26 }, // Product & Variety
            { wch: 14 }, // Type
            { wch: 22 }, // Order Qty
            { wch: 22 }, // Sowing Qty
            { wch: 28 }, // Polyhouse Received
            { wch: 25 }, // Allocated
            { wch: 25 }, // Dispatched
            { wch: 26 }, // Est. Open Stock
            { wch: 18 }, // Polyhouse
            { wch: 16 }, // Beds
            { wch: 28 }  // Available in Polyhouse
        ];

        let wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Residual Polyhouse Stock");

        let selectedWeek = ($("#surplusWeekSelector").val() || "Schedule").replace(/[^a-zA-Z0-9_-]/g, '_');
        let fileName = `Residual_Polyhouse_Stock_${selectedWeek}.xlsx`;
        XLSX.writeFile(wb, fileName);

        Swal.fire({
            title: "Excel Exported!",
            text: `${excelRows.length} residual stock order(s) successfully exported to ${fileName}`,
            icon: "success",
            timer: 2000,
            showConfirmButton: false
        });
    });

    // ==========================================
    // PDF REPORT & PRINT PREVIEW
    // ==========================================
    function showSurplusPdfPreviewModal() {
        try {
            let el = document.getElementById('surplusPdfPreviewModal');
            if (window.bootstrap && bootstrap.Modal) {
                let bsModal = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
                bsModal.show();
                return;
            }
        } catch (e) { }
        $("#surplusPdfPreviewModal").modal("show");
    }

    $("#btnExportPdf").on("click", function () {
        let orders = (currentRenderedData && currentRenderedData.length > 0) ? currentRenderedData : (currentRawData || []);
        if (!orders || orders.length === 0) {
            Swal.fire("No Data", "No residual stock records available in the current view to generate report.", "warning");
            return;
        }

        let docHtml = buildSurplusPdfHtml();
        $("#surplusPdfPrintableDocument").html(docHtml);
        $("#surplusPdfFooterCount").text(`Total surplus batches in report: ${orders.length}`);

        showSurplusPdfPreviewModal();
    });

    $("#btnPdfModalPrint, #btnPdfModalPrintBottom").on("click", function () {
        printSurplusPdf();
    });

    function printSurplusPdf() {
        let content = document.getElementById('surplusPdfPrintableDocument');
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
                <title>Post-Dispatch Residual Polyhouse Stock - Karjee Farm</title>
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
                <script>
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

    $("#btnPdfModalDownload, #btnPdfModalDownloadBottom").on("click", function () {
        downloadSurplusPdf();
    });

    function downloadSurplusPdf() {
        let element = document.getElementById('surplusPdfPrintableDocument');
        if (!element) return;

        let weekVal = ($("#surplusWeekSelector").val() || 'Schedule').replace(/[^a-zA-Z0-9_-]/g, '_');
        let btn = $("#btnPdfModalDownload, #btnPdfModalDownloadBottom");
        let origHtml = btn.html();
        btn.prop("disabled", true).html(`<i class="fa fa-spinner fa-spin me-1"></i> Downloading PDF...`);

        let opt = {
            margin: [5, 5, 5, 5],
            filename: `Residual_Polyhouse_Stock_A4_${weekVal}.pdf`,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, useCORS: true, logging: false, backgroundColor: '#ffffff' },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' },
            pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
        };

        if (typeof html2pdf === 'undefined') {
            btn.prop("disabled", false).html(origHtml);
            printSurplusPdf();
            return;
        }

        html2pdf().set(opt).from(element).save().then(function () {
            btn.prop("disabled", false).html(origHtml);
            Swal.fire({
                title: "A4 PDF Downloaded",
                text: "Residual Polyhouse Stock report downloaded successfully.",
                icon: "success",
                timer: 2000,
                showConfirmButton: false
            });
        }).catch(function (err) {
            btn.prop("disabled", false).html(origHtml);
            printSurplusPdf();
        });
    }

    function buildSurplusPdfHtml() {
        let orders = (currentRenderedData && currentRenderedData.length > 0) ? currentRenderedData : (currentRawData || []);
        let weekVal = $("#surplusWeekSelector option:selected").text() || $("#surplusWeekSelector").val() || 'Schedule';
        let yearVal = $("#surplusYearFilter").val() || '';
        let monthText = $("#surplusMonthFilter option:selected").text() || '';
        let generatedDate = new Date().toLocaleString('en-IN', { dateStyle: 'medium', timeStyle: 'short' });

        let tableRowsHtml = '';
        if (orders.length === 0) {
            tableRowsHtml = `<tr><td colspan="10" style="text-align: center; padding: 24px; color: #64748b; font-weight: bold;">No residual polyhouse stock found.</td></tr>`;
        } else {
            orders.forEach((row, index) => {
                let formattedProdName = formatProductNameDisplay(row.product_name || 'Crop');
                let isGrafted = row.is_grafted === 1;
                let sowingQty = parseInt(row.sowing_qty || 0);
                let orderQty = parseInt(row.sp_orderqty || row.order_qty || 0);
                let polyhouseReceivedQty = parseInt(row.polyhouse_received_qty || row.total_sowing_plants_in_trays || row.sowing_qty || 0);
                let readyQty = parseInt(row.ready_to_sell_qty || 0);
                let allocatedQty = parseInt(row.allocated_qty || 0);
                let dispQty = parseInt(row.dispatched_qty || 0);
                let estimateOpenStock = parseInt(row.estimate_open_stock || 0);
                let surplusQty = parseInt(row.surplus_plants_qty || row.ready_to_sell_qty || 0);
                let remainingTrays = parseInt(row.remaining_trays_count || 0);
                let polyTrayCount = parseInt(row.polyhouse_tray_count || remainingTrays || 0);

                let cavity = 126;
                if (sowingQty > 0 && polyTrayCount > 0) {
                    cavity = Math.round(sowingQty / polyTrayCount);
                }
                if (!cavity || cavity <= 0) cavity = 126;

                let orderTrays = Math.ceil(orderQty / cavity);
                let sowingTrays = polyTrayCount > 0 ? polyTrayCount : Math.ceil(sowingQty / cavity);
                let allocatedTrays = Math.ceil(allocatedQty / cavity);
                let dispTrays = Math.ceil(dispQty / cavity);
                let estOpenStockTrays = Math.ceil(estimateOpenStock / cavity);

                let destLoc = [row.districtname || row.dl_dest_district, row.statename || row.dl_dest_state].filter(Boolean).join(', ');
                let addr = (row.dl_deliveryaddress || row.delivery_address || '').trim();

                tableRowsHtml += `
                    <tr style="page-break-inside: avoid; border-bottom: 1px solid #cbd5e1; font-size: 10px; vertical-align: middle;">
                        <!-- 1. S.No -->
                        <td style="text-align: center; font-weight: bold; color: #64748b; padding: 6px 3px; vertical-align: middle; width: 3%;">${index + 1}</td>
                        
                        <!-- 2. Dispatch Date, SO & Stock Order No -->
                        <td style="padding: 6px 5px; vertical-align: middle; width: 14%;">
                            <div style="font-weight: 700; color: #0f172a; font-size: 11px;">${row.formatted_delivery_date || '-'}</div>
                            <div style="font-size: 10px; color: #0284c7; font-weight: 600;">SO: ${escapeHtml(row.sono || '-')}</div>
                            ${row.psono ? `<div style="font-size: 9px; color: #64748b;">PSO: ${escapeHtml(row.psono)}</div>` : ''}
                            <div style="font-weight: 700; color: #0284c7; font-size: 10.5px; font-family: monospace; margin-top: 2px;">${escapeHtml(row.effective_stockorderno || '-')}</div>
                            ${row.formatted_sowing_date ? `<div style="font-size: 8.5px; color: #64748b;"><b>Sowing:</b> ${row.formatted_sowing_date}</div>` : ''}
                        </td>

                        <!-- 3. Client & Contact Details -->
                        <td style="padding: 6px 5px; vertical-align: middle; width: 16%;">
                            <div style="font-weight: 700; color: #0f172a; font-size: 11.5px;">${escapeHtml(row.clientname || 'N/A')}</div>
                            <div style="font-size: 9.5px; color: #0284c7;">📞 ${escapeHtml(row.mobileno || '-')}</div>
                            ${destLoc ? `<div style="font-size: 9px; color: #dc2626;">📍 ${escapeHtml(destLoc)}</div>` : ''}
                            ${addr ? `<div style="font-size: 8.5px; color: #64748b; font-style: italic;">${escapeHtml(addr)}</div>` : ''}
                        </td>

                        <!-- 4. Product & Variety -->
                        <td style="padding: 6px 5px; vertical-align: middle; width: 13%;">
                            <div style="font-weight: 700; color: #0f172a; font-size: 11px;">${escapeHtml(formattedProdName)}</div>
                            <div style="margin-top: 2px;">
                                <span style="display: inline-block; padding: 1px 5px; font-size: 9px; font-weight: 600; border-radius: 3px; ${isGrafted ? 'background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca;' : 'background-color: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;'}">${isGrafted ? 'Grafted' : 'Non-Grafted'}</span>
                            </div>
                        </td>

                        <!-- 5. Sowing / Order Qty -->
                        <td style="padding: 5px 4px; vertical-align: middle; text-align: center; width: 13%;">
                            <div style="display: inline-block; background-color: #ffffff; border: 1px solid #cbd5e1; border-radius: 4px; padding: 4px 6px; text-align: left; width: 100%;">
                                <div style="display: flex; justify-content: space-between; gap: 4px; font-size: 9.5px; white-space: nowrap;">
                                    <span style="color: #64748b; font-weight: 600;">Order:</span>
                                    <span style="font-weight: 700; color: #0284c7;">${orderTrays} Trays (${orderQty.toLocaleString()})</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; gap: 4px; font-size: 9.5px; margin-top: 2px; white-space: nowrap;">
                                    <span style="color: #64748b; font-weight: 600;">Sowing:</span>
                                    <span style="font-weight: 700; color: #059669;">${sowingTrays} Trays (${sowingQty.toLocaleString()})</span>
                                </div>
                            </div>
                        </td>

                        <!-- 6. Polyhouse Received -->
                        <td style="padding: 5px 3px; vertical-align: middle; text-align: center; width: 9%;">
                            <div style="display: inline-block; background-color: #f0fdf4; border: 1px solid #86efac; border-radius: 4px; padding: 4px 5px; width: 100%;">
                                <div style="font-weight: 700; color: #15803d; font-size: 10.5px; line-height: 1.1;">${sowingTrays} Trays</div>
                                <div style="font-size: 8.5px; color: #166534; font-weight: 600; margin-top: 1px;">(${polyhouseReceivedQty.toLocaleString()} Plants)</div>
                            </div>
                        </td>

                        <!-- 7. Allocated -->
                        <td style="padding: 5px 3px; vertical-align: middle; text-align: center; width: 8%;">
                            <div style="display: inline-block; background-color: #f5f3ff; border: 1px solid #c4b5fd; border-radius: 4px; padding: 4px 5px; width: 100%;">
                                <div style="font-weight: 700; color: #7c3aed; font-size: 10.5px; line-height: 1.1;">${allocatedTrays} Trays</div>
                                <div style="font-size: 8.5px; color: #6d28d9; font-weight: 600; margin-top: 1px;">(${allocatedQty.toLocaleString()})</div>
                            </div>
                        </td>

                        <!-- 8. Dispatched -->
                        <td style="padding: 5px 3px; vertical-align: middle; text-align: center; width: 8%;">
                            <div style="display: inline-block; background-color: #f8fafc; border: 1px solid #cbd5e1; border-radius: 4px; padding: 4px 5px; width: 100%;">
                                <div style="font-weight: 700; color: #334155; font-size: 10.5px; line-height: 1.1;">${dispTrays} Trays</div>
                                <div style="font-size: 8.5px; color: #475569; font-weight: 600; margin-top: 1px;">(${dispQty.toLocaleString()})</div>
                            </div>
                        </td>

                        <!-- 9. Est. Open Stock -->
                        <td style="padding: 5px 3px; vertical-align: middle; text-align: center; width: 8%;">
                            ${estimateOpenStock > 0 ? `
                                <div style="display: inline-block; background-color: #fff7ed; border: 1px solid #fdba74; border-radius: 4px; padding: 4px 5px; width: 100%;">
                                    <div style="font-weight: 700; color: #c2410c; font-size: 10.5px; line-height: 1.1;">${estOpenStockTrays} Trays</div>
                                    <div style="font-size: 8.5px; color: #ea580c; font-weight: 600; margin-top: 1px;">(${estimateOpenStock.toLocaleString()})</div>
                                </div>
                            ` : `
                                <div style="display: inline-block; background-color: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 4px; padding: 4px 5px; width: 100%; color: #64748b;">
                                    <div style="font-weight: 700; font-size: 10.5px; line-height: 1.1;">0 Trays</div>
                                    <div style="font-size: 8.5px; color: #94a3b8; margin-top: 1px;">(0 Open Stock)</div>
                                </div>
                            `}
                        </td>

                        <!-- 10. Polyhouse & Bed Locations -->
                        <td style="padding: 6px 5px; vertical-align: middle; width: 10%;">
                            <div style="font-weight: 700; color: #0d9488; font-size: 10px;">🏠 ${escapeHtml(row.polyhouse_names || 'N/A')}</div>
                            <div style="font-size: 9px; color: #475569;">🛏️ Beds: ${escapeHtml(row.bed_numbers || '-')}</div>
                        </td>
                    </tr>
                `;
            });
        }

        let docHtml = `
            <div style="padding: 0; color: #0f172a; width: 100%;">
                <!-- Header -->
                <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #0d9488; padding-bottom: 10px; margin-bottom: 12px;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            ${krfCompanyLogo ? `
                                <img src="${krfCompanyLogo}" alt="Karjee Farm" style="height: 46px; width: auto; max-width: 65px; object-fit: contain; vertical-align: middle;" />
                            ` : `
                                <img src="../img/logo/logo.png" alt="Karjee Farm" style="height: 46px; width: auto; max-width: 65px; object-fit: contain; vertical-align: middle;" />
                            `}
                            <div>
                                <h2 style="margin: 0; font-size: 21px; font-weight: 900; color: #0f172a; letter-spacing: 0.5px; text-transform: uppercase; line-height: 1.1;">KARJEE FARM</h2>
                                <div style="font-size: 12.5px; font-weight: 600; color: #0d9488; margin-top: 2px;">Post-Dispatch Residual Polyhouse Stock Report</div>
                            </div>
                        </div>
                    </div>
                    <div style="text-align: right; font-size: 11px; color: #475569;">
                        <div><b>Week Period:</b> <span style="font-weight: 700; color: #0f172a;">${weekVal}</span></div>
                        <div><b>Month:</b> ${monthText} (${yearVal})</div>
                        <div><b>Generated On:</b> ${generatedDate}</div>
                    </div>
                </div>

                <!-- Schedule Table -->
                <table style="width: 100%; table-layout: fixed; border-collapse: collapse; margin-bottom: 12px; border: 1px solid #cbd5e1;">
                    <thead style="display: table-header-group;">
                        <tr style="background-color: #0d9488; color: white; font-size: 9.5px; text-transform: uppercase; letter-spacing: 0.5px;">
                            <th style="padding: 7px 3px; text-align: center; width: 3%; border: 1px solid #0d9488;">#</th>
                            <th style="padding: 7px 5px; text-align: left; width: 14%; border: 1px solid #0d9488;">Dispatch Date & SO</th>
                            <th style="padding: 7px 5px; text-align: left; width: 17%; border: 1px solid #0d9488;">Client & Contact</th>
                            <th style="padding: 7px 5px; text-align: left; width: 14%; border: 1px solid #0d9488;">Product & Variety</th>
                            <th style="padding: 7px 4px; text-align: center; width: 11%; border: 1px solid #0d9488;">Sowing / Order</th>
                            <th style="padding: 7px 3px; text-align: center; width: 8%; border: 1px solid #0d9488;">Polyhouse Received</th>
                            <th style="padding: 7px 3px; text-align: center; width: 8%; border: 1px solid #0d9488;">Allocated</th>
                            <th style="padding: 7px 3px; text-align: center; width: 8%; border: 1px solid #0d9488;">Dispatched</th>
                            <th style="padding: 7px 3px; text-align: center; width: 8%; border: 1px solid #0d9488;">Est. Open Stock</th>
                            <th style="padding: 7px 5px; text-align: left; width: 9%; border: 1px solid #0d9488;">Polyhouse & Beds</th>
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

    // Filter Trays in Polyhouse Received Popup Table
    $(document).on("click", ".btn-tray-status-filter", function () {
        $(".btn-tray-status-filter").removeClass("active btn-primary").addClass("btn-outline-secondary");
        $(this).addClass("active btn-primary").removeClass("btn-outline-secondary");

        let groupKey = $(this).data("status-group");
        if (groupKey === "all" || !groupKey) {
            $(".tray-table-row").show();
        } else {
            $(".tray-table-row").each(function () {
                let rowGroup = $(this).data("group-key") || '';
                if (rowGroup === groupKey) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }
    });
});
