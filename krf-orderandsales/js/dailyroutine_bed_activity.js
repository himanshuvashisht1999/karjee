/**
 * Greenhouse Bed-Wise Daily Routine Activity Monitor JavaScript
 * Handles polyhouse bed querying grouped by lot, order, roottype, location, and bed,
 * seed-to-routine linking, day age calculation, pagination (20 per page), order color clustering, 
 * action decision handling (Completed, Reschedule, Not Required), action logging, and timeline viewing.
 */

var currentBedRoutinesList = [];
var filteredBedList = [];
var currentPage = 1;
var pageSize = 20;
var currentEditingBedRow = null;
var currentEditingBedIndex = -1;

function getLocalTodayYMD() {
    var d = new Date();
    var year = d.getFullYear();
    var month = String(d.getMonth() + 1).padStart(2, '0');
    var day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function setQuickRescheduleDate(offsetDays) {
    var d = new Date();
    d.setDate(d.getDate() + offsetDays);
    var year = d.getFullYear();
    var month = String(d.getMonth() + 1).padStart(2, '0');
    var day = String(d.getDate()).padStart(2, '0');
    $("#logRescheduleDate").val(`${year}-${month}-${day}`);
}

function showAppModal(modalId) {
    var $el = $('#' + modalId);
    if ($el.length && typeof $el.modal === 'function') {
        $el.modal('show');
    } else if (window.bootstrap && bootstrap.Modal) {
        var el = document.getElementById(modalId);
        if (el) {
            var m = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
            m.show();
        }
    } else {
        var el = document.getElementById(modalId);
        if (el) {
            el.classList.add('show');
            el.style.display = 'block';
            $('body').addClass('modal-open').append('<div class="modal-backdrop fade show app-backdrop"></div>');
        }
    }
}

function hideAppModal(modalId) {
    var $el = $('#' + modalId);
    if ($el.length && typeof $el.modal === 'function') {
        $el.modal('hide');
    } else if (window.bootstrap && bootstrap.Modal) {
        var el = document.getElementById(modalId);
        if (el) {
            var m = bootstrap.Modal.getInstance(el);
            if (m) m.hide();
        }
    } else {
        var el = document.getElementById(modalId);
        if (el) {
            el.classList.remove('show');
            el.style.display = 'none';
            $('body').removeClass('modal-open');
            $('.app-backdrop').remove();
        }
    }
}

$(document).ready(function () {
    initBedRoutineMonitor();
});

function initBedRoutineMonitor() {
    loadPolyhouseBedRoutines();
}

function getApiBaseUrl() {
    var routurl = $(".bed-routine-container").data("routurl") || "";
    return routurl + "krf-orderandsales/modal/dailyroutine_modal.php";
}

function getProjectId() {
    return $(".bed-routine-container").data("projectid") || "";
}

function getLoginId() {
    return $(".bed-routine-container").data("loginid") || "admin";
}

/**
 * 1. Fetch Polyhouse Bed Routines from Backend
 */
function loadPolyhouseBedRoutines(keepCurrentPageAndFilters = false) {
    var spin = $("#refreshBedSpin");
    spin.addClass("fa-spin");

    var routineDate = $("#filterRoutineDate").val() || getLocalTodayYMD();

    var url = getApiBaseUrl() + "?action=get_polyhouse_bed_routines&projectid=" + encodeURIComponent(getProjectId()) +
        "&routine_date=" + encodeURIComponent(routineDate);

    $.ajax({
        url: url,
        type: "GET",
        dataType: "json",
        success: function (res) {
            spin.removeClass("fa-spin");
            if (res && res.status) {
                currentBedRoutinesList = res.data || [];
                populatePolyhouseAndRootTypeFilters(currentBedRoutinesList);
                updateBedDropdownForSelectedPolyhouse();

                if (keepCurrentPageAndFilters) {
                    filterBedGridRows(true);
                } else {
                    filterBedGridRows(false);
                }
            } else {
                $("#polyhouseBedRoutinesTbody").html(`<tr><td colspan="10" class="text-center py-4 text-danger">Failed to load bed routines.</td></tr>`);
            }
        },
        error: function () {
            spin.removeClass("fa-spin");
            $("#polyhouseBedRoutinesTbody").html(`<tr><td colspan="10" class="text-center py-4 text-danger">Error connecting to server.</td></tr>`);
        }
    });
}

function populatePolyhouseAndRootTypeFilters(dataList) {
    var $polySelect = $("#filterPolyhouseSelect");
    var $rootSelect = $("#filterRootTypeSelect");

    var currentPoly = $polySelect.val();
    var currentRoot = $rootSelect.val();

    var uniquePolys = new Set();
    var uniqueRoots = new Set();

    dataList.forEach(function (row) {
        if (row.polyhouseid) uniquePolys.add(row.polyhouseid);
        if (row.roottype && row.roottype.trim() !== "") uniqueRoots.add(row.roottype.trim());
    });

    // 1. Polyhouses dropdown
    $polySelect.find("option:not(:first)").remove();
    Array.from(uniquePolys).sort(function (a, b) { return a - b; }).forEach(function (poly) {
        $polySelect.append(`<option value="${escapeHtml(poly)}">Polyhouse #${escapeHtml(poly)}</option>`);
    });
    if (currentPoly && (uniquePolys.has(Number(currentPoly)) || uniquePolys.has(String(currentPoly)))) {
        $polySelect.val(currentPoly);
    }

    // 2. Root Types dropdown (dynamically populated from dataset)
    $rootSelect.find("option:not(:first)").remove();
    Array.from(uniqueRoots).sort().forEach(function (root) {
        $rootSelect.append(`<option value="${escapeHtml(root)}">${escapeHtml(root)}</option>`);
    });
    if (currentRoot && uniqueRoots.has(currentRoot)) {
        $rootSelect.val(currentRoot);
    }
}

function updateBedDropdownForSelectedPolyhouse() {
    var $bedSelect = $("#filterBedNoSelect");
    var currentBed = $bedSelect.val();
    var selectedPoly = ($("#filterPolyhouseSelect").val() || "").trim();

    var uniqueBeds = new Set();
    currentBedRoutinesList.forEach(function (row) {
        if (!selectedPoly || String(row.polyhouseid) === String(selectedPoly)) {
            if (row.bed_no && row.bed_no.trim() !== "") {
                uniqueBeds.add(row.bed_no.trim());
            }
        }
    });

    $bedSelect.find("option:not(:first)").remove();
    Array.from(uniqueBeds).sort().forEach(function (bed) {
        $bedSelect.append(`<option value="${escapeHtml(bed)}">${escapeHtml(bed)}</option>`);
    });

    if (currentBed && uniqueBeds.has(currentBed)) {
        $bedSelect.val(currentBed);
    } else {
        $bedSelect.val("");
    }
}

function onPolyhouseFilterChange() {
    updateBedDropdownForSelectedPolyhouse();
    filterBedGridRows();
}

function onBedFilterChange() {
    filterBedGridRows();
}

function onRoutineDateFilterChange() {
    loadPolyhouseBedRoutines(true);
}

/**
 * 2. Pagination Logic
 */
function renderPagedBedGrid() {
    var totalItems = filteredBedList.length;
    $("#pageTotalItems").text(totalItems);
    $("#showingBedsCount").text(totalItems);

    if (totalItems === 0) {
        $("#polyhouseBedRoutinesTbody").html(`
            <tr>
                <td colspan="10" class="text-center py-5 text-muted">
                    <i class="fa-solid fa-seedling fs-1 text-black-50 mb-2 d-block"></i>
                    No active polyhouse bed lots found for current filters.
                </td>
            </tr>
        `);
        $("#pageStartItem").text(0);
        $("#pageEndItem").text(0);
        $("#paginationControls").empty();
        return;
    }

    var effectivePageSize = (pageSize === 'all') ? totalItems : intval(pageSize);
    var totalPages = Math.ceil(totalItems / effectivePageSize);
    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;

    var startIdx = (currentPage - 1) * effectivePageSize;
    var endIdx = Math.min(startIdx + effectivePageSize, totalItems);
    var slicedData = filteredBedList.slice(startIdx, endIdx);

    $("#pageStartItem").text(startIdx + 1);
    $("#pageEndItem").text(endIdx);

    renderPolyhouseBedGridTable(slicedData, startIdx);
    renderPaginationControls(totalPages);
}

function renderPaginationControls(totalPages) {
    var $container = $("#paginationControls");
    $container.empty();

    if (totalPages <= 1) return;

    // Previous Button
    var prevDisabled = (currentPage === 1) ? 'disabled' : '';
    $container.append(`<button type="button" class="dr-page-btn" ${prevDisabled} onclick="goToPage(${currentPage - 1})"><i class="fa-solid fa-chevron-left"></i></button>`);

    // Page Number Buttons (smart ellipsis)
    var startP = Math.max(1, currentPage - 2);
    var endP = Math.min(totalPages, currentPage + 2);

    if (startP > 1) {
        $container.append(`<button type="button" class="dr-page-btn" onclick="goToPage(1)">1</button>`);
        if (startP > 2) $container.append(`<span class="px-1 text-muted">...</span>`);
    }

    for (var p = startP; p <= endP; p++) {
        var activeClass = (p === currentPage) ? 'active' : '';
        $container.append(`<button type="button" class="dr-page-btn ${activeClass}" onclick="goToPage(${p})">${p}</button>`);
    }

    if (endP < totalPages) {
        if (endP < totalPages - 1) $container.append(`<span class="px-1 text-muted">...</span>`);
        $container.append(`<button type="button" class="dr-page-btn" onclick="goToPage(${totalPages})">${totalPages}</button>`);
    }

    // Next Button
    var nextDisabled = (currentPage === totalPages) ? 'disabled' : '';
    $container.append(`<button type="button" class="dr-page-btn" ${nextDisabled} onclick="goToPage(${currentPage + 1})"><i class="fa-solid fa-chevron-right"></i></button>`);
}

function goToPage(page) {
    currentPage = page;
    renderPagedBedGrid();
}

function onPageSizeChange() {
    pageSize = $("#pageSizeSelect").val();
    currentPage = 1;
    renderPagedBedGrid();
}

/**
 * 3. Render Bed Grid Table Rows
 */
function renderPolyhouseBedGridTable(dataList, startIndex = 0) {
    var $tbody = $("#polyhouseBedRoutinesTbody");
    $tbody.empty();

    // ── Build Production Order Color Cluster Map ──
    var orderColorMap = {};
    var orderPalette = [
        { bg: '#f0fdf4', border: '#22c55e' }, // Soft Emerald
        { bg: '#eff6ff', border: '#3b82f6' }, // Soft Blue
        { bg: '#faf5ff', border: '#a855f7' }, // Soft Lavender
        { bg: '#fffbeb', border: '#f59e0b' }, // Soft Amber
        { bg: '#fdf2f8', border: '#ec4899' }, // Soft Rose
        { bg: '#ecfeff', border: '#06b6d4' }, // Soft Cyan
        { bg: '#f8fafc', border: '#64748b' }  // Soft Slate
    ];
    var colorIdx = 0;

    // Count order frequency across entire filtered list
    var orderCounts = {};
    filteredBedList.forEach(function (r) {
        var key = r.stockorder_no || r.sowinglotno_internal || 'single';
        orderCounts[key] = (orderCounts[key] || 0) + 1;
    });

    filteredBedList.forEach(function (r) {
        var key = r.stockorder_no || r.sowinglotno_internal || 'single';
        if (!orderColorMap[key]) {
            if (orderCounts[key] > 1) {
                orderColorMap[key] = orderPalette[colorIdx % orderPalette.length];
                colorIdx++;
            } else {
                orderColorMap[key] = { bg: '#ffffff', border: 'transparent' };
            }
        }
    });

    dataList.forEach(function (row, idx) {
        var realIndex = startIndex + idx;
        var bedName = row.bed_no || row.bedrackname || 'Unassigned Bed';
        var polyName = row.polyhouseid ? `Polyhouse #${row.polyhouseid}` : '';

        // Location Name
        var locDisplayName = row.location_name || (row.locationid ? row.locationid : 'Greenhouse Main');

        var lotNo = row.sowinglotno_internal || '-';
        var orderNo = row.stockorder_no || '-';
        var sowingDate = row.sowingdate_internal || row.sowing_date || '';

        // Root Type Tag
        var rootTypeBadge = "";
        if (row.roottype === "Rootstock") {
            rootTypeBadge = `<span class="badge text-white px-2 py-0" style="background-color: #7c3aed; font-size: 0.72rem;">Rootstock</span>`;
        } else if (row.roottype === "Scion") {
            rootTypeBadge = `<span class="badge text-white px-2 py-0" style="background-color: #0284c7; font-size: 0.72rem;">Scion</span>`;
        } else {
            rootTypeBadge = `<span class="badge bg-secondary text-white px-2 py-0" style="font-size: 0.72rem;">${escapeHtml(row.roottype || 'Standard')}</span>`;
        }

        // Mapped Seed (clean name without Seed ID tag)
        var seedInfoHtml = "";
        if (row.SeedName) {
            seedInfoHtml = `
                <div class="fw-bold text-dark text-truncate" style="max-width: 140px; font-size: 0.86rem;" title="${escapeHtml(row.SeedName)}">${escapeHtml(row.SeedName)}</div>
                <small class="text-muted text-truncate d-block" style="font-size: 0.74rem;">${row.VarietyofSeed ? escapeHtml(row.VarietyofSeed) : ''} ${row.BrandName ? ' • ' + escapeHtml(row.BrandName) : ''}</small>
            `;
        } else {
            seedInfoHtml = `<span class="text-muted small">Unmapped Seed</span>`;
        }

        // Trays & Plants
        var traysBadge = `<span class="badge-trays"><i class="fa-solid fa-boxes-stacked text-success"></i><strong>${row.total_trays}</strong> Trays</span>`;
        var plantsCount = Number(row.total_plants || 0).toLocaleString();
        var failedCount = Number(row.total_failed || 0);
        var plantsHtml = `
            <div class="text-center">
                ${traysBadge}
                <div class="small fw-semibold text-dark mt-1" style="font-size: 0.78rem;">${plantsCount} Plants</div>
                ${failedCount > 0 ? `<small class="text-danger" style="font-size: 0.70rem;">(${failedCount} fail)</small>` : ''}
            </div>
        `;

        // Routine Progress - Row-Unique & Compact
        var curDay = row.CurrentDayNumber || 1;
        var totalDays = row.TotalPlanDays || 25;
        var planTitle = row.MatchedPlan ? row.MatchedPlan.PlanName : 'Standard Routine';
        var currentStageName = (row.TodayActivity && row.TodayActivity.StageName) ? row.TodayActivity.StageName : (row.growth_stage || 'Routine Stage');

        var progressHtml = "";
        if (curDay <= totalDays) {
            var pct = Math.min(100, Math.round((curDay / totalDays) * 100));
            progressHtml = `
                <div class="text-center" style="min-width: 120px;">
                    <span class="badge text-white px-2 py-0 mb-1 text-truncate d-inline-block" style="background-color: #0f766e; font-size: 0.70rem; max-width: 120px;" title="${escapeHtml(currentStageName)}">
                        ${escapeHtml(currentStageName)}
                    </span>
                    <div class="d-flex justify-content-between align-items-center small mb-1" style="font-size: 0.76rem;">
                        <span class="fw-bold text-dark">Day ${curDay}<span class="text-muted fw-normal">/${totalDays}</span></span>
                        <span class="badge bg-light text-teal border" style="font-size: 0.68rem; padding: 1px 4px;">${pct}%</span>
                    </div>
                    <div class="progress-day-bar">
                        <div class="progress-day-fill" style="width: ${pct}%;"></div>
                    </div>
                </div>
            `;
        } else {
            // Completed Routine
            progressHtml = `
                <div class="text-center" style="min-width: 120px;">
                    <span class="badge bg-success text-white px-2 py-0 mb-1" style="font-size: 0.70rem;">
                        <i class="fa-solid fa-circle-check me-1"></i>Completed
                    </span>
                    <div class="fw-bold text-success small mb-1" style="font-size: 0.76rem;">
                        Day ${totalDays}/${totalDays} <span class="badge bg-success text-white" style="font-size: 0.64rem; padding: 1px 3px;">100%</span>
                    </div>
                    <div class="progress-day-bar">
                        <div class="progress-day-fill bg-success" style="width: 100%;"></div>
                    </div>
                </div>
            `;
        }

        // Today's Scheduled Activity
        var todayActHtml = "";
        if (row.TodayActivity) {
            var act = row.TodayActivity;
            todayActHtml = `
                <div>
                    <div class="d-flex align-items-center gap-1 mb-1">
                        <span class="badge bg-info text-white px-2 py-0" style="font-size: 0.72rem;">${escapeHtml(act.ActivityType || 'Task')}</span>
                        <span class="badge bg-light border text-dark px-1 py-0" style="font-size: 0.68rem;">${escapeHtml(act.TimeSlot || 'Morning')}</span>
                    </div>
                    <div class="fw-bold text-dark text-truncate" style="max-width: 170px; font-size: 0.84rem;" title="${escapeHtml(act.TaskTitle)}">${escapeHtml(act.TaskTitle || 'Scheduled Task')}</div>
                    ${act.ItemDetails ? `<small class="text-primary text-truncate d-block" style="font-size: 0.74rem; max-width: 170px;" title="${escapeHtml(act.ItemDetails)}"><i class="fa-solid fa-flask me-1"></i>${escapeHtml(act.ItemDetails)}</small>` : ''}
                </div>
            `;
        } else {
            todayActHtml = `
                <div class="text-muted small" style="font-size: 0.78rem;">
                    <i class="fa-solid fa-circle-check text-success me-1"></i> Routine Monitoring
                </div>
            `;
        }

        // Last Action Taken with Execution Status distinction
        var lastActionHtml = "";
        if (row.LastAction) {
            var la = row.LastAction;
            var localToday = getLocalTodayYMD();
            var isToday = (la.ActionTakenDate === localToday);
            var execStatus = la.ExecutionStatus || 'Completed';

            var statusBadgeHtml = "";
            if (execStatus === 'Not Required') {
                statusBadgeHtml = `<span class="badge bg-danger text-white" style="font-size: 0.72rem; padding: 3px 8px; border-radius: 12px;"><i class="fa-solid fa-ban me-1"></i>Not Required</span>`;
            } else if (execStatus === 'Rescheduled') {
                var rDate = la.RescheduleDate ? ` (${escapeHtml(la.RescheduleDate)})` : '';
                statusBadgeHtml = `<span class="badge bg-warning text-dark border border-warning" style="font-size: 0.72rem; padding: 3px 8px; border-radius: 12px;"><i class="fa-solid fa-calendar-days me-1"></i>Rescheduled${rDate}</span>`;
            } else {
                if (isToday) {
                    statusBadgeHtml = `<span class="badge-action-done"><i class="fa-solid fa-circle-check me-1"></i>Done Today</span>`;
                } else {
                    statusBadgeHtml = `<span class="badge-action-done" style="background-color: #ecfdf5 !important; color: #047857 !important; border: 1px solid #6ee7b7 !important;"><i class="fa-solid fa-check me-1"></i>${escapeHtml(la.ActionTakenDate)}</span>`;
                }
            }

            lastActionHtml = `
                <div class="text-center">
                    ${statusBadgeHtml}
                    <div class="small fw-semibold text-dark text-truncate mt-1" style="max-width: 130px; font-size: 0.76rem;" title="${escapeHtml(la.ActionTitle)}">${escapeHtml(la.ActionTitle)}</div>
                    <small class="text-muted d-block" style="font-size: 0.68rem;">by ${escapeHtml(la.ActionTakenBy || 'User')}</small>
                </div>
            `;
        } else {
            lastActionHtml = `
                <div class="text-center">
                    <span class="badge-action-pending"><i class="fa-solid fa-clock-rotate-left me-1"></i>Pending</span>
                </div>
            `;
        }

        // Order Grouping Background Color
        var orderKey = row.stockorder_no || row.sowinglotno_internal || 'single';
        var orderStyle = orderColorMap[orderKey] || { bg: '#ffffff', border: 'transparent' };
        var rowBgStyle = `background-color: ${orderStyle.bg} !important;`;
        var firstTdStyle = (orderStyle.border !== 'transparent') ? `border-left: 5px solid ${orderStyle.border} !important;` : '';

        var isDoneToday = (row.LastAction && row.LastAction.ActionTakenDate === getLocalTodayYMD());
        var actionBtnHtml = isDoneToday
            ? `<button type="button" class="dr-action-btn dr-action-btn-locked" disabled title="Action already completed for today (Locked until tomorrow)">
                   <i class="fa-solid fa-circle-check"></i>
               </button>`
            : `<button type="button" class="dr-action-btn dr-action-btn-done" onclick="openMarkActionModal(${realIndex})" title="Record Action as Done">
                   <i class="fa-solid fa-check"></i>
               </button>`;

        var rowHtml = `
            <tr data-seedid="${escapeHtml(row.SeedID || row.SeedId || '')}" data-roottype="${escapeHtml(row.roottype || '')}" data-order="${escapeHtml(orderNo)}" style="${rowBgStyle}">
                <td class="text-center fw-bold text-muted" style="${firstTdStyle}">${realIndex + 1}</td>
                <td>
                    <span class="badge-bed"><i class="fa-solid fa-layer-group me-1"></i>${escapeHtml(bedName)}</span>
                    ${polyName ? `<div class="text-muted fw-semibold" style="font-size: 0.72rem;">${escapeHtml(polyName)}</div>` : ''}
                    <div class="text-secondary fw-semibold mt-1" style="font-size: 0.74rem;">
                        <i class="fa-solid fa-location-dot me-1 text-danger"></i>${escapeHtml(locDisplayName)}
                    </div>
                </td>
                <td>
                    <div class="badge-lot mb-1"><i class="fa-solid fa-barcode me-1 text-muted"></i>${escapeHtml(lotNo)}</div>
                    <div class="text-muted font-monospace" style="font-size: 0.74rem;">${escapeHtml(orderNo)}</div>
                    ${sowingDate ? `<div class="text-secondary fw-semibold mt-1" style="font-size: 0.72rem;"><i class="fa-regular fa-calendar text-muted me-1"></i>Sown: ${escapeHtml(sowingDate)}</div>` : ''}
                </td>
                <td>
                    <div class="fw-bold text-dark text-truncate mb-1" style="max-width: 140px; font-size: 0.86rem;" title="${escapeHtml(row.productname || '')}">${escapeHtml(row.productname || 'Product #' + row.productid)}</div>
                    ${rootTypeBadge}
                </td>
                <td>${seedInfoHtml}</td>
                <td>${plantsHtml}</td>
                <td>${progressHtml}</td>
                <td>${todayActHtml}</td>
                <td>${lastActionHtml}</td>
                <td class="text-center text-nowrap">
                    <div class="d-inline-flex align-items-center justify-content-center">
                        ${actionBtnHtml}
                        <button type="button" class="dr-action-btn dr-action-btn-view" onclick="viewBedFullTimeline(${realIndex})" title="Full Routine Timeline">
                            <i class="fa-solid fa-timeline"></i>
                        </button>
                        <button type="button" class="dr-action-btn dr-action-btn-history" onclick="viewBedActionHistory(${realIndex})" title="History Logs">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;

        $tbody.append(rowHtml);
    });
}

function updateBedKPICards(dataList) {
    var totalBeds = dataList.length;
    var totalTrays = 0;
    var totalPlants = 0;

    dataList.forEach(function (row) {
        totalTrays += intval(row.total_trays || 0);
        totalPlants += intval(row.total_plants || 0);
    });

    $("#kpiTotalBeds").text(totalBeds);
    $("#kpiTotalTrays").text(totalTrays.toLocaleString());
    $("#kpiTotalPlants").text(totalPlants.toLocaleString());
}

function filterBedGridRows(preserveCurrentPage = false) {
    var searchVal = ($("#filterBedSearchInput").val() || "").toLowerCase().trim();
    var polyVal = ($("#filterPolyhouseSelect").val() || "").trim();
    var bedVal = ($("#filterBedNoSelect").val() || "").trim();
    var rootTypeVal = ($("#filterRootTypeSelect").val() || "").trim().toLowerCase();

    filteredBedList = currentBedRoutinesList.filter(function (row) {
        var text = (
            (row.bed_no || "") + " " +
            (row.polyhouseid ? "polyhouse #" + row.polyhouseid : "") + " " +
            (row.location_name || "") + " " +
            (row.sowinglotno_internal || "") + " " +
            (row.stockorder_no || "") + " " +
            (row.productname || "") + " " +
            (row.SeedName || "") + " " +
            (row.VarietyofSeed || "") + " " +
            (row.BrandName || "") + " " +
            (row.growth_stage || "") + " " +
            (row.roottype || "")
        ).toLowerCase();

        var matchesSearch = (!searchVal || text.indexOf(searchVal) > -1);
        var matchesPoly = (!polyVal || String(row.polyhouseid) === String(polyVal));
        var matchesBed = (!bedVal || String(row.bed_no) === String(bedVal));
        var matchesRoot = (!rootTypeVal || String(row.roottype || "").toLowerCase() === rootTypeVal);

        return matchesSearch && matchesPoly && matchesBed && matchesRoot;
    });

    updateBedKPICards(filteredBedList);

    if (!preserveCurrentPage) {
        currentPage = 1;
    }
    renderPagedBedGrid();
}

/**
 * 4. Action Decision Switcher (Completed, Reschedule, Not Required)
 */
function setActionDecision(decision) {
    $("#logModalExecutionStatus").val(decision);
    $("#btnChoiceCompleted, #btnChoiceRescheduled, #btnChoiceNotRequired").removeClass("active");

    var $submitBtn = $("#submitActionBtn");

    if (decision === 'Completed') {
        $("#btnChoiceCompleted").addClass("active");
        $("#rescheduleDateGroup").slideUp(150);
        $("#remarksMandatoryBadge").hide();
        $("#logRemarks").removeClass("border-danger").attr("placeholder", "Enter any crop health observation, moisture level, or plant height...");
        $submitBtn.html('<i class="fa-solid fa-check" id="submitActionSpin"></i> Confirm Action Done')
            .removeClass("btn-warning btn-danger").addClass("btn-success")
            .css({ "background-color": "#0f766e", "border-color": "#0f766e", "color": "#ffffff" });
    } else if (decision === 'Rescheduled') {
        $("#btnChoiceRescheduled").addClass("active");
        $("#rescheduleDateGroup").slideDown(150);
        $("#remarksMandatoryBadge").hide();
        $("#logRemarks").removeClass("border-danger").attr("placeholder", "Enter reason for rescheduling...");
        $submitBtn.html('<i class="fa-solid fa-calendar-check" id="submitActionSpin"></i> Confirm Reschedule')
            .removeClass("btn-success btn-danger").addClass("btn-warning")
            .css({ "background-color": "#eab308", "border-color": "#ca8a04", "color": "#000000" });
    } else if (decision === 'Not Required') {
        $("#btnChoiceNotRequired").addClass("active");
        $("#rescheduleDateGroup").slideUp(150);
        $("#remarksMandatoryBadge").show();
        $("#logRemarks").addClass("border-danger").attr("placeholder", "REQUIRED: Enter specific reason why this activity is not needed on this bed (e.g. crop already treated, moisture high)...").focus();
        $submitBtn.html('<i class="fa-solid fa-ban" id="submitActionSpin"></i> Confirm Skip / Not Required')
            .removeClass("btn-success btn-warning").addClass("btn-danger")
            .css({ "background-color": "#dc2626", "border-color": "#dc2626", "color": "#ffffff" });
    }
}

/**
 * 5. Mark Routine Action
 */
function openMarkActionModal(index) {
    var row = filteredBedList[index];
    if (!row) return;

    var isDoneToday = (row.LastAction && row.LastAction.ActionTakenDate === getLocalTodayYMD());
    if (isDoneToday) {
        Swal.fire({
            icon: "info",
            title: "Already Completed Today",
            text: "Today's routine task has already been logged for this bed. It will unlock automatically tomorrow for the next scheduled activity.",
            confirmButtonColor: "#0f766e"
        });
        return;
    }

    currentEditingBedRow = row;
    currentEditingBedIndex = index;

    var curDay = row.CurrentDayNumber || 1;
    var totalDays = row.TotalPlanDays || 25;
    var planDay = Math.min(totalDays, curDay);
    var act = row.TodayActivity || {};

    $("#logModalLotNo").val(row.sowinglotno_internal || "");
    $("#logModalStockOrderNo").val(row.stockorder_no || "");
    $("#logModalRootType").val(row.roottype || "Scion");
    $("#logModalLocationId").val(row.locationid || "");
    $("#logModalBedNo").val(row.bed_no || "");
    $("#logModalPolyhouseId").val(row.polyhouseid || "");
    $("#logModalRoutinePlanId").val(row.RoutinePlanID || 0);
    $("#logModalDayNumber").val(planDay);

    // Summary Card
    $("#logSummaryBedNo").html(`<i class="fa-solid fa-layer-group me-1"></i>${escapeHtml(row.bed_no || 'Bed')}`);
    $("#logSummaryPolyhouse").text(row.polyhouseid ? `Polyhouse #${row.polyhouseid}` : 'Polyhouse');

    var rootBadge = (row.roottype === 'Rootstock')
        ? `<span class="badge text-white" style="background-color: #7c3aed; font-size: 0.72rem;">Rootstock</span>`
        : `<span class="badge text-white" style="background-color: #0284c7; font-size: 0.72rem;">Scion</span>`;
    $("#logSummaryRootType").html(rootBadge);

    $("#logSummaryDayAge").text(`Routine Day ${planDay}/${totalDays}${curDay > totalDays ? ` (Plant Age: ${curDay}d)` : ''}`);
    $("#logSummaryProductName").text(row.productname || "Standard Crop");
    $("#logSummarySeedName").text(row.SeedName ? `${row.SeedName} (${row.VarietyofSeed || ''}) ${row.BrandName ? ' • ' + row.BrandName : ''}` : "Unmapped Seed");
    var sowingDate = row.sowingdate_internal || row.sowing_date || '';
    $("#logSummaryLotNo").html(`${escapeHtml(row.sowinglotno_internal || "-")} ${sowingDate ? `<span class="badge bg-light text-secondary border fw-normal ms-1" style="font-size: 0.70rem;"><i class="fa-regular fa-calendar me-1"></i>Sown: ${escapeHtml(sowingDate)}</span>` : ''}`);
    $("#logSummaryOrderNo").text(row.stockorder_no ? `Order: ${row.stockorder_no}` : "");

    // Pre-fill hidden action details from today's plan for backend submission
    $("#logActionType").val(act.ActivityType || "Watering/Irrigation");
    $("#logActionDate").val(getLocalTodayYMD());
    $("#logActionTitle").val(act.TaskTitle || `Day ${curDay} Routine Care Completed`);
    $("#logItemDetails").val(act.ItemDetails || "");
    $("#logApplicationMethod").val(act.ApplicationMethod || "Foliar Spray");
    $("#logTimeSlot").val(act.TimeSlot || "Morning");
    $("#logRemarks").val("");

    // Populate Read-Only Scheduled Operation Display Card
    $("#logCardActivityType").text(act.ActivityType || "Routine Activity");
    $("#logCardStageName").html(`<i class="fa-solid fa-seedling me-1 text-success"></i>${escapeHtml(act.StageName || row.growth_stage || 'Routine Stage')}`);
    $("#logCardTaskTitle").text(act.TaskTitle || `Day ${curDay} Routine Care Operation`);

    $("#logCardItemDetails").text(act.ItemDetails || "Standard Water / General Care");
    $("#logCardAppMethod").text(act.ApplicationMethod || "Standard Application");

    if (act.TargetPlantHeight && act.TargetPlantHeight.trim() !== '') {
        $("#logCardTargetHeight").text(act.TargetPlantHeight);
        $("#logCardHeightCol").show();
    } else {
        $("#logCardHeightCol").hide();
    }

    if (act.TargetHealthStatus && act.TargetHealthStatus.trim() !== '' && act.TargetHealthStatus !== 'Healthy') {
        $("#logCardTargetHealth").text(act.TargetHealthStatus);
        $("#logCardHealthCol").show();
    } else {
        $("#logCardHealthCol").hide();
    }

    if (act.SpecialInstructions && act.SpecialInstructions.trim() !== '') {
        $("#logCardSpecialNotes").text(act.SpecialInstructions);
        $("#logCardSpecialNotesWrapper").show();
    } else {
        $("#logCardSpecialNotesWrapper").hide();
    }

    // FRESH MODE: First time recording today's action
    $("#markActionModalLabel").text("Record Routine Action Execution");
    $("#markActionSubHeader").text("Log execution, reschedule or mark routine task for this bed");
    $("#submitActionBtn").html('<i class="fa-solid fa-check" id="submitActionSpin"></i> Confirm Action Done');

    setQuickRescheduleDate(1);
    setActionDecision('Completed');

    showAppModal("markActionModal");
}

var isSubmittingBedAction = false;
function submitMarkBedAction() {
    if (isSubmittingBedAction) return;

    var actionType = $.trim($("#logActionType").val());
    var actionTitle = $.trim($("#logActionTitle").val());
    var actionDate = $("#logActionDate").val() || getLocalTodayYMD();
    var executionStatus = $("#logModalExecutionStatus").val() || 'Completed';
    var remarks = $.trim($("#logRemarks").val());
    var rescheduleDate = $("#logRescheduleDate").val();

    if (!actionType || !actionTitle) {
        Swal.fire("Required", "Please provide Activity Type and Action Title.", "warning");
        return;
    }

    if (executionStatus === 'Not Required' && !remarks) {
        Swal.fire({
            icon: "warning",
            title: "Mandatory Remark Required",
            text: "Please provide a reason / remark explaining why this activity is Not Required."
        });
        $("#logRemarks").focus();
        return;
    }

    if (executionStatus === 'Rescheduled' && !rescheduleDate) {
        Swal.fire({
            icon: "warning",
            title: "Reschedule Date Required",
            text: "Please select the date to reschedule this activity to."
        });
        $("#logRescheduleDate").focus();
        return;
    }

    var payload = {
        sowinglotno_internal: $("#logModalLotNo").val(),
        stockorder_no: $("#logModalStockOrderNo").val(),
        roottype: $("#logModalRootType").val(),
        locationid: $("#logModalLocationId").val(),
        bed_no: $("#logModalBedNo").val(),
        polyhouseid: $("#logModalPolyhouseId").val(),
        projectid: getProjectId(),
        RoutinePlanID: intval($("#logModalRoutinePlanId").val()),
        DayNumber: intval($("#logModalDayNumber").val()),
        ExecutionStatus: executionStatus,
        RescheduleDate: (executionStatus === 'Rescheduled') ? rescheduleDate : null,
        ActionType: actionType,
        ActionTitle: (executionStatus === 'Not Required') ? `${actionTitle} (Skipped)` : actionTitle,
        ItemDetails: $.trim($("#logItemDetails").val()),
        ApplicationMethod: $.trim($("#logApplicationMethod").val()),
        TimeSlot: $("#logTimeSlot").val(),
        Remarks: remarks,
        ActionTakenDate: actionDate,
        ActionTakenBy: $.trim($("#logActionTakenBy").val()) || getLoginId()
    };

    isSubmittingBedAction = true;
    var $submitBtn = $("#submitActionBtn");
    $submitBtn.prop("disabled", true);
    var spin = $("#submitActionSpin");
    spin.addClass("fa-spin");

    $.ajax({
        url: getApiBaseUrl() + "?action=mark_bed_action_done",
        type: "POST",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify(payload),
        complete: function () {
            isSubmittingBedAction = false;
            $submitBtn.prop("disabled", false);
            spin.removeClass("fa-spin");
        },
        success: function (res) {
            if (res && res.status) {
                hideAppModal("markActionModal");

                // Immediately update local model object in memory
                if (currentEditingBedRow) {
                    currentEditingBedRow.LastAction = (res.log) ? res.log : {
                        LogID: res.log_id || 0,
                        sowinglotno_internal: payload.sowinglotno_internal,
                        stockorder_no: payload.stockorder_no,
                        roottype: payload.roottype,
                        locationid: payload.locationid,
                        bed_no: payload.bed_no,
                        polyhouseid: payload.polyhouseid,
                        projectid: payload.projectid,
                        RoutinePlanID: payload.RoutinePlanID,
                        DayNumber: payload.DayNumber,
                        ExecutionStatus: payload.ExecutionStatus,
                        RescheduleDate: payload.RescheduleDate,
                        ActionType: payload.ActionType,
                        ActionTitle: payload.ActionTitle,
                        ItemDetails: payload.ItemDetails,
                        ApplicationMethod: payload.ApplicationMethod,
                        TimeSlot: payload.TimeSlot,
                        Remarks: payload.Remarks,
                        ActionTakenDate: payload.ActionTakenDate,
                        ActionTakenAt: new Date().toISOString().replace('T', ' ').slice(0, 19),
                        ActionTakenBy: payload.ActionTakenBy
                    };
                    renderPagedBedGrid();
                }

                Swal.fire({
                    icon: "success",
                    title: "Status Saved",
                    text: res.message || "Routine action logged successfully!",
                    timer: 1400,
                    showConfirmButton: false
                });

                // Fetch fresh records while preserving active page and filters
                loadPolyhouseBedRoutines(true);
            } else {
                Swal.fire("Error", res.message || "Failed to record action.", "error");
            }
        },
        error: function () {
            Swal.fire("Error", "Server error while logging action.", "error");
        }
    });
}

var currentModalBedRow = null;
var currentModalLogs = [];
var currentModalPlan = null;
var currentModalType = 'history'; // 'history' or 'timeline'

/**
 * 6. View Full Bed Routine Timeline Modal
 */
function viewBedFullTimeline(index) {
    var row = filteredBedList[index];
    if (!row) return;

    currentModalBedRow = row;
    currentModalType = 'timeline';
    currentModalLogs = [];
    currentModalPlan = null;

    var planId = row.RoutinePlanID || 0;
    var curDay = row.CurrentDayNumber || 1;

    $("#bedTimelineSubHeader").html(`Bed: <strong>${escapeHtml(row.bed_no)}</strong> | Lot: <strong>${escapeHtml(row.sowinglotno_internal)}</strong> | Crop: <strong>${escapeHtml(row.productname)}</strong> | Seed: <strong>${escapeHtml(row.SeedName || 'Unmapped')}</strong>`);
    $("#btnDownloadActionHistoryPdf").html('<i class="fa-solid fa-file-pdf fs-6"></i> <span>Download PDF</span>');

    if (planId > 0) {
        $.ajax({
            url: getApiBaseUrl() + "?action=get_routine_plan_details&routine_plan_id=" + planId,
            type: "GET",
            dataType: "json",
            success: function (res) {
                if (res && res.status && res.data) {
                    var p = res.data;
                    currentModalPlan = p;
                    renderBedTimelineContent(row, p, curDay);
                    showAppModal("bedTimelineModal");
                } else {
                    Swal.fire("Notice", "No routine plan details configured for this seed.", "info");
                }
            },
            error: function () {
                Swal.fire("Error", "Failed to load routine plan timeline.", "error");
            }
        });
    } else {
        Swal.fire("Notice", "No routine plan linked to this bed crop.", "info");
    }
}

function renderBedTimelineContent(row, plan, curDay) {
    var dayDetails = plan.DayDetails || [];
    var totalDays = plan.TotalPlanDays || 25;

    var html = `
        <div class="table-responsive bg-white rounded-3 shadow-sm border">
            <table class="table table-bordered table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 80px;" class="text-center">Day</th>
                        <th style="width: 140px;">Growth Stage</th>
                        <th style="width: 130px;">Activity Type</th>
                        <th style="width: 200px;">Task Title</th>
                        <th style="width: 200px;">Item / Chemical & Dosage</th>
                        <th style="width: 120px;">Method</th>
                        <th style="width: 100px;">Time Slot</th>
                    </tr>
                </thead>
                <tbody>
    `;

    if (dayDetails.length > 0) {
        dayDetails.forEach(function (d) {
            var isCurrent = (intval(d.DayNumber) === curDay);
            var rowClass = isCurrent ? 'table-warning fw-bold' : '';
            var dayBadge = isCurrent
                ? `<span class="badge badge-warning bg-warning text-dark border border-dark"><i class="fa-solid fa-arrow-right me-1"></i>Day ${d.DayNumber} (TODAY)</span>`
                : `<span class="badge badge-secondary bg-light text-dark border">Day ${d.DayNumber}</span>`;

            html += `
                <tr class="${rowClass}">
                    <td class="text-center">${dayBadge}</td>
                    <td>${escapeHtml(d.StageName || '')}</td>
                    <td><span class="badge badge-info bg-info text-white">${escapeHtml(d.ActivityType || '')}</span></td>
                    <td>${escapeHtml(d.TaskTitle || '')}</td>
                    <td class="text-primary">${escapeHtml(d.ItemDetails || '')}</td>
                    <td>${escapeHtml(d.ApplicationMethod || '')}</td>
                    <td>${escapeHtml(d.TimeSlot || '')}</td>
                </tr>
            `;
        });
    } else {
        html += `<tr><td colspan="7" class="text-center py-4 text-muted">No day-wise routine plan items configured for this seed.</td></tr>`;
    }

    html += `
                </tbody>
            </table>
        </div>
    `;

    $("#bedTimelineContentBody").html(html);
}

/**
 * 7. View Bed Action History Logs Modal
 */
function viewBedActionHistory(index) {
    var row = filteredBedList[index];
    if (!row) return;

    currentModalBedRow = row;
    currentModalType = 'history';
    currentModalLogs = [];
    currentModalPlan = null;

    var lotNo = row.sowinglotno_internal || "";
    var bedNo = row.bed_no || "";
    var stockNo = row.stockorder_no || "";

    $("#bedTimelineSubHeader").html(`Action History Logs for Bed: <strong>${escapeHtml(bedNo)}</strong> | Lot: <strong>${escapeHtml(lotNo)}</strong>`);
    $("#btnDownloadActionHistoryPdf").html('<i class="fa-solid fa-file-pdf fs-6"></i> <span>Download PDF</span>');

    $.ajax({
        url: getApiBaseUrl() + "?action=get_bed_action_history&sowinglotno_internal=" + encodeURIComponent(lotNo) + "&bed_no=" + encodeURIComponent(bedNo) + "&stockorder_no=" + encodeURIComponent(stockNo),
        type: "GET",
        dataType: "json",
        success: function (res) {
            var logs = (res && res.status) ? res.data : [];
            currentModalLogs = logs;

            var html = `
                <div class="table-responsive bg-white rounded-3 shadow-sm border">
                    <table class="table table-bordered table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 50px;" class="text-center">#</th>
                                <th style="width: 130px;">Date & Time</th>
                                <th style="width: 90px;" class="text-center">Routine Day</th>
                                <th style="width: 110px;">Status</th>
                                <th style="width: 130px;">Activity Type</th>
                                <th style="width: 200px;">Action Title</th>
                                <th style="width: 180px;">Item & Method</th>
                                <th style="width: 110px;">Executed By</th>
                                <th>Supervisor Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            if (logs.length > 0) {
                logs.forEach(function (l, i) {
                    var st = l.ExecutionStatus || 'Completed';
                    var stBadge = "";
                    if (st === 'Not Required') {
                        stBadge = `<span class="badge bg-danger text-white">Not Required</span>`;
                    } else if (st === 'Rescheduled') {
                        var rDate = l.RescheduleDate ? ` (${escapeHtml(l.RescheduleDate)})` : '';
                        stBadge = `<span class="badge bg-warning text-dark border border-warning">Rescheduled${rDate}</span>`;
                    } else {
                        stBadge = `<span class="badge bg-success text-white">Completed</span>`;
                    }

                    html += `
                        <tr>
                            <td class="text-center text-muted fw-bold">${i + 1}</td>
                            <td>
                                <div class="fw-bold text-dark">${escapeHtml(l.ActionTakenDate)}</div>
                                <small class="text-muted" style="font-size: 0.72rem;"><i class="fa-regular fa-clock me-1"></i>${escapeHtml(l.ActionTakenAt ? l.ActionTakenAt.slice(11) : '')}</small>
                            </td>
                            <td class="text-center">
                                <span class="badge text-white px-2 py-1" style="background-color: #0f766e; font-size: 0.80rem; border-radius: 6px;">Day ${l.DayNumber}</span>
                            </td>
                            <td>${stBadge}</td>
                            <td><span class="badge badge-info bg-info text-white">${escapeHtml(l.ActionType)}</span></td>
                            <td class="fw-semibold text-dark">${escapeHtml(l.ActionTitle)}</td>
                            <td>
                                ${l.ItemDetails ? `<div class="text-primary small fw-semibold">${escapeHtml(l.ItemDetails)}</div>` : ''}
                                ${l.ApplicationMethod ? `<small class="text-muted d-block">${escapeHtml(l.ApplicationMethod)}</small>` : ''}
                            </td>
                            <td><span class="badge bg-light text-dark border">${escapeHtml(l.ActionTakenBy || 'User')}</span></td>
                            <td class="small ${st === 'Not Required' ? 'fw-bold text-danger' : 'text-muted'}">${escapeHtml(l.Remarks || '-')}</td>
                        </tr>
                    `;
                });
            } else {
                html += `<tr><td colspan="9" class="text-center py-4 text-muted">No action logs recorded yet for this bed.</td></tr>`;
            }

            html += `
                        </tbody>
                    </table>
                </div>
            `;

            $("#bedTimelineContentBody").html(html);
            showAppModal("bedTimelineModal");
        }
    });
}

/**
 * Download Action History or Routine Timeline as PDF
 */
function downloadBedActionHistoryPdf() {
    var row = currentModalBedRow;
    if (!row) {
        Swal.fire("Notice", "No bed record selected for PDF export.", "info");
        return;
    }

    var bedNo = row.bed_no || 'Bed';
    var lotNo = row.sowinglotno_internal || 'Lot';
    var orderNo = row.stockorder_no || '-';
    var sowingDate = row.sowingdate_internal || row.sowing_date || '-';
    var productName = row.productname || '-';
    var rootType = row.roottype || 'Standard';
    var seedName = row.SeedName ? `${row.SeedName} (${row.VarietyofSeed || ''}) ${row.BrandName ? ' • ' + row.BrandName : ''}` : 'Unmapped';
    var totalTrays = row.total_trays || 0;
    var totalPlants = Number(row.total_plants || 0).toLocaleString();
    var curDay = row.CurrentDayNumber || 1;
    var polyhouse = row.polyhouseid ? `Polyhouse #${row.polyhouseid}` : 'Polyhouse Main';
    var printDate = new Date().toLocaleString();
    var loginUser = getLoginId() || 'Administrator';

    var isHistory = (currentModalType === 'history');
    var docTitle = isHistory
        ? `Action_History_${bedNo}_${lotNo}`
        : `Routine_Schedule_${bedNo}_${lotNo}`;

    var printWindow = window.open('', '', 'height=850,width=1150');
    if (!printWindow) {
        Swal.fire("Popup Blocked", "Please allow popups in your browser to download/print the PDF.", "warning");
        return;
    }

    var html = `
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>${escapeHtml(docTitle)}</title>
            <style>
                @page { size: A4 landscape; margin: 10mm 10mm; }
                * { box-sizing: border-box; }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                    font-size: 11px;
                    color: #1e293b;
                    margin: 0;
                    padding: 16px;
                    background: #fff;
                }
                .pdf-header {
                    border-bottom: 2px solid #0f766e;
                    padding-bottom: 10px;
                    margin-bottom: 12px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                .pdf-title {
                    font-size: 18px;
                    font-weight: 800;
                    color: #0f766e;
                    margin: 0;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                .pdf-subtitle {
                    font-size: 11px;
                    color: #64748b;
                    margin-top: 2px;
                }
                .pdf-meta {
                    text-align: right;
                    font-size: 10px;
                    color: #64748b;
                }
                .summary-box {
                    background: #f8fafc;
                    border: 1px solid #cbd5e1;
                    border-radius: 6px;
                    padding: 10px 14px;
                    margin-bottom: 14px;
                    display: grid;
                    grid-template-columns: repeat(4, 1fr);
                    gap: 10px;
                }
                .summary-item {
                    font-size: 10.5px;
                }
                .summary-label {
                    font-weight: 700;
                    color: #475569;
                    font-size: 9px;
                    text-transform: uppercase;
                    letter-spacing: 0.3px;
                }
                .summary-value {
                    font-weight: 600;
                    color: #0f172a;
                    margin-top: 2px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 10.5px;
                    margin-bottom: 14px;
                }
                th {
                    background-color: #0f766e !important;
                    color: #ffffff !important;
                    font-weight: 700;
                    text-align: left;
                    padding: 7px 9px;
                    border: 1px solid #0d655e;
                    font-size: 10px;
                    text-transform: uppercase;
                    letter-spacing: 0.3px;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
                td {
                    padding: 6px 9px;
                    border: 1px solid #cbd5e1;
                    vertical-align: top;
                }
                tr:nth-child(even) td {
                    background-color: #f8fafc;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
                .badge {
                    display: inline-block;
                    padding: 2px 6px;
                    border-radius: 4px;
                    font-size: 9.5px;
                    font-weight: 700;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
                .badge-completed { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
                .badge-rescheduled { background: #fef9c3; color: #854d0e; border: 1px solid #fde047; }
                .badge-notreq { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
                .badge-teal { background: #ccfbf1; color: #0f766e; border: 1px solid #99f6e4; }
                .pdf-footer {
                    margin-top: 14px;
                    padding-top: 8px;
                    border-top: 1px solid #e2e8f0;
                    display: flex;
                    justify-content: space-between;
                    font-size: 9.5px;
                    color: #64748b;
                }
                @media print {
                    body { padding: 0; }
                    .no-print { display: none !important; }
                }
            </style>
        </head>
        <body>
            <div class="pdf-header">
                <div>
                    <h1 class="pdf-title">${isHistory ? 'Bed Daily Routine - Action Execution History' : 'Bed Daily Routine - Schedule & Timeline'}</h1>
                    <div class="pdf-subtitle">KRF Nursery & Greenhouse Smart Management System</div>
                </div>
                <div class="pdf-meta">
                    <div><strong>Generated:</strong> ${escapeHtml(printDate)}</div>
                    <div><strong>Generated By:</strong> ${escapeHtml(loginUser)}</div>
                </div>
            </div>

            <div class="summary-box">
                <div class="summary-item">
                    <div class="summary-label">Bed & Polyhouse</div>
                    <div class="summary-value">${escapeHtml(bedNo)} (${escapeHtml(polyhouse)})</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Sowing Lot & Date</div>
                    <div class="summary-value">${escapeHtml(lotNo)} <span style="color:#64748b;font-weight:normal;">(Sown: ${escapeHtml(sowingDate)})</span></div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Stock Order No</div>
                    <div class="summary-value">${escapeHtml(orderNo)}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Trays / Plants</div>
                    <div class="summary-value">${totalTrays} Trays / ${totalPlants} Plants</div>
                </div>
                <div class="summary-item" style="grid-column: span 2;">
                    <div class="summary-label">Product / Crop</div>
                    <div class="summary-value">${escapeHtml(productName)} <span class="badge badge-teal">${escapeHtml(rootType)}</span></div>
                </div>
                <div class="summary-item" style="grid-column: span 2;">
                    <div class="summary-label">Mapped Seed Variety</div>
                    <div class="summary-value">${escapeHtml(seedName)}</div>
                </div>
            </div>
    `;

    if (isHistory) {
        var logs = currentModalLogs || [];
        html += `
            <table>
                <thead>
                    <tr>
                        <th style="width: 30px; text-align: center;">#</th>
                        <th style="width: 95px;">Date & Time</th>
                        <th style="width: 65px; text-align: center;">Day Age</th>
                        <th style="width: 90px; text-align: center;">Status</th>
                        <th style="width: 110px;">Activity Type</th>
                        <th style="width: 150px;">Action Title</th>
                        <th style="width: 150px;">Item Details & Method</th>
                        <th style="width: 100px;">Executed By</th>
                        <th>Supervisor Remarks</th>
                    </tr>
                </thead>
                <tbody>
        `;

        if (logs.length > 0) {
            logs.forEach(function (l, i) {
                var st = l.ExecutionStatus || 'Completed';
                var badgeClass = (st === 'Not Required') ? 'badge-notreq' : ((st === 'Rescheduled') ? 'badge-rescheduled' : 'badge-completed');
                var rDateText = (st === 'Rescheduled' && l.RescheduleDate) ? `<div style="font-size:9px;color:#854d0e;">Target: ${escapeHtml(l.RescheduleDate)}</div>` : '';

                html += `
                    <tr>
                        <td style="text-align: center; color: #64748b;">${i + 1}</td>
                        <td>
                            <strong>${escapeHtml(l.ActionTakenDate)}</strong>
                            <div style="font-size: 9px; color: #64748b;">${escapeHtml(l.ActionTakenAt ? l.ActionTakenAt.slice(11) : '')}</div>
                        </td>
                        <td style="text-align: center;"><span class="badge badge-teal">Day ${l.DayNumber}</span></td>
                        <td style="text-align: center;"><span class="badge ${badgeClass}">${escapeHtml(st)}</span>${rDateText}</td>
                        <td><strong>${escapeHtml(l.ActionType || '-')}</strong></td>
                        <td>${escapeHtml(l.ActionTitle || '-')}</td>
                        <td>
                            <div>${escapeHtml(l.ItemDetails || '-')}</div>
                            ${l.ApplicationMethod ? `<div style="font-size: 9px; color: #64748b;">Method: ${escapeHtml(l.ApplicationMethod)}</div>` : ''}
                        </td>
                        <td><strong>${escapeHtml(l.ActionTakenBy || '-')}</strong></td>
                        <td style="color: ${st === 'Not Required' ? '#b91c1c' : '#334155'}; font-style: ${l.Remarks ? 'normal' : 'italic'};">${escapeHtml(l.Remarks || '-')}</td>
                    </tr>
                `;
            });
        } else {
            html += `<tr><td colspan="9" style="text-align: center; padding: 20px; color: #64748b;">No routine execution logs recorded for this bed yet.</td></tr>`;
        }

        html += `</tbody></table>`;
    } else {
        // Timeline Schedule Table
        var p = currentModalPlan;
        var dayDetails = (p && p.DayDetails) ? p.DayDetails : [];
        html += `
            <table>
                <thead>
                    <tr>
                        <th style="width: 55px; text-align: center;">Day</th>
                        <th style="width: 115px;">Growth Stage</th>
                        <th style="width: 115px;">Activity Type</th>
                        <th style="width: 170px;">Task Title</th>
                        <th style="width: 150px;">Item / Chemical & Dosage</th>
                        <th style="width: 100px;">Method</th>
                        <th style="width: 80px;">Time Slot</th>
                        <th>Special Notes</th>
                    </tr>
                </thead>
                <tbody>
        `;

        if (dayDetails.length > 0) {
            dayDetails.forEach(function (d) {
                var isCurrent = (intval(d.DayNumber) === curDay);
                var rowBg = isCurrent ? 'style="background-color:#fef08a!important;-webkit-print-color-adjust:exact;print-color-adjust:exact;"' : '';
                html += `
                    <tr ${rowBg}>
                        <td style="text-align: center;"><strong>Day ${d.DayNumber}</strong>${isCurrent ? ' (TODAY)' : ''}</td>
                        <td>${escapeHtml(d.StageName || '-')}</td>
                        <td><span class="badge badge-teal">${escapeHtml(d.ActivityType || '-')}</span></td>
                        <td><strong>${escapeHtml(d.TaskTitle || '-')}</strong></td>
                        <td>${escapeHtml(d.ItemDetails || '-')}</td>
                        <td>${escapeHtml(d.ApplicationMethod || '-')}</td>
                        <td>${escapeHtml(d.TimeSlot || '-')}</td>
                        <td style="font-size: 9.5px; color: #475569;">${escapeHtml(d.SpecialInstructions || '-')}</td>
                    </tr>
                `;
            });
        } else {
            html += `<tr><td colspan="8" style="text-align: center; padding: 20px; color: #64748b;">No schedule items configured for this plan.</td></tr>`;
        }

        html += `</tbody></table>`;
    }

    html += `
            <div class="pdf-footer">
                <div>Document: <strong>${escapeHtml(docTitle)}</strong> | Bed: ${escapeHtml(bedNo)}</div>
                <div>Page 1 of 1 (Official Greenhouse Log)</div>
            </div>
        </body>
        </html>
    `;

    printWindow.document.open();
    printWindow.document.write(html);
    printWindow.document.close();
    printWindow.focus();

    setTimeout(function () {
        printWindow.print();
    }, 400);
}

/**
 * 8. Export Bed Grid to Excel
 */
function exportBedRoutinesToExcel() {
    var filename = "Polyhouse_Bed_Daily_Routines_" + new Date().toISOString().slice(0, 10) + ".xls";

    var excelContent = `
        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
        <head>
            <meta charset="utf-8">
            <style>
                th { background-color: #0f766e; color: #ffffff; font-weight: bold; border: 1px solid #cbd5e1; text-align: center; height: 30px; font-family: Arial; }
                td { border: 1px solid #e2e8f0; font-family: Arial; font-size: 10pt; vertical-align: middle; padding: 6px; }
            </style>
        </head>
        <body>
            <table border="1">
                <thead>
                    <tr style="background-color: #0f766e; color: #ffffff;">
                        <th>S.No</th>
                        <th>Bed No</th>
                        <th>Polyhouse</th>
                        <th>Location</th>
                        <th>Sowing Lot No</th>
                        <th>Sowing Date</th>
                        <th>Stock Order No</th>
                        <th>Product Name</th>
                        <th>Root Type</th>
                        <th>Seed Name</th>
                        <th>Total Trays</th>
                        <th>Total Plants</th>
                        <th>Routine Progress</th>
                        <th>Today Scheduled Task</th>
                        <th>Last Action Taken</th>
                    </tr>
                </thead>
                <tbody>
    `;

    filteredBedList.forEach(function (r, idx) {
        var todayTask = r.TodayActivity ? (r.TodayActivity.TaskTitle + (r.TodayActivity.ItemDetails ? ' - ' + r.TodayActivity.ItemDetails : '')) : 'General Care';
        var lastAct = r.LastAction ? (r.LastAction.ActionTitle + ' on ' + r.LastAction.ActionTakenDate + ' by ' + (r.LastAction.ActionTakenBy || '')) : 'Pending';
        var progressText = (r.CurrentDayNumber <= r.TotalPlanDays)
            ? `Day ${r.CurrentDayNumber} of ${r.TotalPlanDays} Days`
            : `Completed (Day ${r.TotalPlanDays}/${r.TotalPlanDays})`;
        var sowingDate = r.sowingdate_internal || r.sowing_date || '';

        excelContent += `
            <tr>
                <td style="text-align: center;">${idx + 1}</td>
                <td>${escapeHtml(r.bed_no || '')}</td>
                <td>${escapeHtml(r.polyhouseid ? 'Polyhouse #' + r.polyhouseid : '')}</td>
                <td>${escapeHtml(r.location_name || r.locationid || '')}</td>
                <td>${escapeHtml(r.sowinglotno_internal || '')}</td>
                <td>${escapeHtml(sowingDate)}</td>
                <td>${escapeHtml(r.stockorder_no || '')}</td>
                <td>${escapeHtml(r.productname || '')}</td>
                <td>${escapeHtml(r.roottype || '')}</td>
                <td>${escapeHtml(r.SeedName || '')}</td>
                <td style="text-align: center;">${r.total_trays || 0}</td>
                <td style="text-align: center;">${r.total_plants || 0}</td>
                <td style="text-align: center;">${escapeHtml(progressText)}</td>
                <td>${escapeHtml(todayTask)}</td>
                <td>${escapeHtml(lastAct)}</td>
            </tr>
        `;
    });

    excelContent += `
                </tbody>
            </table>
        </body>
        </html>
    `;

    var blob = new Blob(["\uFEFF" + excelContent], { type: "application/vnd.ms-excel;charset=utf-8" });
    var downloadUrl = URL.createObjectURL(blob);
    var a = document.createElement("a");
    a.href = downloadUrl;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(downloadUrl);
}

/**
 * Utility helpers
 */
function escapeHtml(text) {
    if (text === null || text === undefined) return "";
    return String(text)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function intval(val) {
    var n = parseInt(val, 10);
    return isNaN(n) ? 0 : n;
}

// Global window mappings for onclick inline triggers
window.showAppModal = showAppModal;
window.hideAppModal = hideAppModal;
window.loadPolyhouseBedRoutines = loadPolyhouseBedRoutines;
window.filterBedGridRows = filterBedGridRows;
window.onPolyhouseFilterChange = onPolyhouseFilterChange;
window.onBedFilterChange = onBedFilterChange;
window.onRoutineDateFilterChange = onRoutineDateFilterChange;
window.setActionDecision = setActionDecision;
window.openMarkActionModal = openMarkActionModal;
window.submitMarkBedAction = submitMarkBedAction;
window.viewBedFullTimeline = viewBedFullTimeline;
window.viewBedActionHistory = viewBedActionHistory;
window.exportBedRoutinesToExcel = exportBedRoutinesToExcel;
window.goToPage = goToPage;
window.onPageSizeChange = onPageSizeChange;
window.setQuickRescheduleDate = setQuickRescheduleDate;
window.downloadBedActionHistoryPdf = downloadBedActionHistoryPdf;

