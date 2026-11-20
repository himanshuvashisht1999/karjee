/**
 * Daily Routine Plan Master JavaScript
 * Handles multi-subcategory selection, dynamic Seed ID binding, 
 * customizable 25-day routine matrix, AJAX CRUD, dynamic dropdown options manager, and timeline view.
 */

var allSubCategoriesCache = [];
var currentPlansList = [];
var loadedSeedsCache = [];
var dropdownOptionsCache = {
    growth_stage: [],
    activity_type: [],
    application_method: [],
    time_slot: []
};
var activeDropdownGroupTab = 'growth_stage';

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
    initDailyRoutineModule();
});

function initDailyRoutineModule() {
    loadDropdownOptions(function () {
        loadSubCategoriesAndSeeds(function () {
            loadRoutinePlans();
        });
    });
}

function getApiBaseUrl() {
    var routurl = $(".daily-routine-container").data("routurl") || "";
    return routurl + "krf-orderandsales/modal/dailyroutine_modal.php";
}

function getProjectId() {
    return $(".daily-routine-container").data("projectid") || "";
}

function getLoginId() {
    return $(".daily-routine-container").data("loginid") || "admin";
}

/**
 * 0. Fetch Dynamic Dropdown Options from Single MySQL Table
 */
function loadDropdownOptions(callback) {
    $.ajax({
        url: getApiBaseUrl() + "?action=get_dropdown_options&projectid=" + encodeURIComponent(getProjectId()),
        type: "GET",
        dataType: "json",
        success: function (res) {
            if (res && res.status && res.data) {
                dropdownOptionsCache = Object.assign({
                    growth_stage: [],
                    activity_type: [],
                    application_method: [],
                    time_slot: []
                }, res.data);
            }
            if (typeof callback === "function") callback();
        },
        error: function () {
            if (typeof callback === "function") callback();
        }
    });
}

/**
 * 1. Fetch Subcategories and Seeds from Backend
 */
function loadSubCategoriesAndSeeds(callback) {
    $.ajax({
        url: getApiBaseUrl() + "?action=get_subcategories_and_seeds&projectid=" + encodeURIComponent(getProjectId()),
        type: "GET",
        dataType: "json",
        success: function (res) {
            if (res && res.status) {
                allSubCategoriesCache = res.data || [];
                populateSubcategoryDropdowns();
            }
            if (typeof callback === "function") callback();
        },
        error: function () {
            if (typeof callback === "function") callback();
        }
    });
}

function populateSubcategoryDropdowns() {
    var $filterSelect = $("#filterSubCategorySelect");
    var $cloneSelect = $("#cloneTargetSubCategory");

    $filterSelect.find("option:not(:first)").remove();
    $cloneSelect.empty();

    allSubCategoriesCache.forEach(function (sub) {
        $filterSelect.append(`<option value="${sub.SubCategoryID}">${escapeHtml(sub.SubCategoryName)}</option>`);
        $cloneSelect.append(`<option value="${sub.SubCategoryID}">${escapeHtml(sub.SubCategoryName)}</option>`);
    });
}

/**
 * 2. Fetch and Render Routine Plans Grid
 */
function loadRoutinePlans() {
    var spin = $("#refreshSpin");
    spin.addClass("fa-spin");

    var filterSubCat = $("#filterSubCategorySelect").val() || "";
    var filterStatus = $("#filterStatusSelect").val() || "";
    var search = $("#filterSearchInput").val() || "";

    var url = getApiBaseUrl() + "?action=get_routine_plans&projectid=" + encodeURIComponent(getProjectId()) +
              "&subcategoryid=" + encodeURIComponent(filterSubCat) +
              "&status=" + encodeURIComponent(filterStatus) +
              "&search=" + encodeURIComponent(search);

    $.ajax({
        url: url,
        type: "GET",
        dataType: "json",
        success: function (res) {
            spin.removeClass("fa-spin");
            if (res && res.status) {
                currentPlansList = res.data || [];
                renderRoutinePlansTable(currentPlansList);
                updateKPICards(currentPlansList);
            } else {
                $("#dailyRoutinePlanTbody").html(`<tr><td colspan="9" class="text-center py-4 text-danger">Failed to load routine plans.</td></tr>`);
            }
        },
        error: function () {
            spin.removeClass("fa-spin");
            $("#dailyRoutinePlanTbody").html(`<tr><td colspan="9" class="text-center py-4 text-danger">Error connecting to server.</td></tr>`);
        }
    });
}

function renderRoutinePlansTable(plans) {
    var $tbody = $("#dailyRoutinePlanTbody");
    $tbody.empty();

    $("#showingPlansCount").text(plans.length);

    if (plans.length === 0) {
        $tbody.html(`
            <tr>
                <td colspan="9" class="text-center py-5 text-muted">
                    <i class="fa-solid fa-folder-open fs-1 text-black-50 mb-2 d-block"></i>
                    No daily routine plans found. Click <strong>"+ Create Routine Plan"</strong> to create one.
                </td>
            </tr>
        `);
        return;
    }

    plans.forEach(function (plan, idx) {
        // Subcategories badges
        var subCatBadges = "";
        if (plan.SubCategoryNames && Object.keys(plan.SubCategoryNames).length > 0) {
            Object.values(plan.SubCategoryNames).forEach(function (name) {
                subCatBadges += `<span class="badge-crop"><i class="fa-solid fa-leaf text-success me-1"></i>${escapeHtml(name)}</span>`;
            });
        } else if (plan.PrimarySubCategoryName) {
            subCatBadges = `<span class="badge-crop"><i class="fa-solid fa-leaf text-success me-1"></i>${escapeHtml(plan.PrimarySubCategoryName)}</span>`;
        } else {
            subCatBadges = `<span class="text-muted small">None</span>`;
        }

        // Seeds badges
        var seedBadges = "";
        var seedCount = plan.SeedCount || 0;
        if (seedCount > 0) {
            var seedNamesPreview = (plan.SeedDetails || []).map(function (s) {
                return escapeHtml(s.SeedName + (s.VarietyofSeed ? " (" + s.VarietyofSeed + ")" : ""));
            }).slice(0, 3).join(", ");
            
            if (seedCount > 3) {
                seedNamesPreview += ` + ${seedCount - 3} more`;
            }

            seedBadges = `<span class="badge-seed"><i class="fa-solid fa-seedling text-warning me-1"></i><strong>${seedCount}</strong> Seed(s)</span>
                          <div class="small text-muted" style="font-size: 0.76rem;" title="${escapeHtml(seedNamesPreview)}">${seedNamesPreview}</div>`;
        } else {
            seedBadges = `<span class="text-muted small">All Seeds / Generic</span>`;
        }

        // Status badge
        var statusBadge = "";
        if (plan.Status === "Active") {
            statusBadge = `<span class="badge-status-active"><i class="fa-solid fa-circle" style="font-size: 6px;"></i> Active</span>`;
        } else if (plan.Status === "Draft") {
            statusBadge = `<span class="badge-status-draft"><i class="fa-solid fa-circle" style="font-size: 6px;"></i> Draft</span>`;
        } else {
            statusBadge = `<span class="badge-status-inactive"><i class="fa-solid fa-circle" style="font-size: 6px;"></i> Inactive</span>`;
        }

        var totalDays = plan.TotalPlanDays || 25;
        var setupDays = plan.ConfiguredDaysCount || 0;
        var setupStatusBadge = (setupDays >= totalDays) 
            ? `<span class="badge-setup-complete"><i class="fa-solid fa-circle-check"></i> ${setupDays}/${totalDays} Complete</span>`
            : `<span class="badge-setup-pending"><i class="fa-solid fa-clock"></i> ${setupDays}/${totalDays} Days</span>`;

        var rowHtml = `
            <tr>
                <td class="text-center fw-bold text-muted">${idx + 1}</td>
                <td>
                    <span class="font-monospace fw-bold text-dark" style="letter-spacing: 0.5px;">${escapeHtml(plan.PlanCode || '')}</span>
                </td>
                <td>
                    <div class="fw-bold text-dark" style="font-size: 0.92rem;">${escapeHtml(plan.PlanName)}</div>
                    ${plan.Description ? `<small class="text-muted text-truncate d-block" style="max-width: 260px;">${escapeHtml(plan.Description)}</small>` : ''}
                </td>
                <td>${subCatBadges}</td>
                <td>${seedBadges}</td>
                <td class="text-center">
                    <span class="badge-days"><i class="fa-solid fa-calendar-days text-teal"></i> ${totalDays} Days</span>
                </td>
                <td class="text-center">${setupStatusBadge}</td>
                <td class="text-center">${statusBadge}</td>
                <td class="text-center text-nowrap">
                    <div class="d-inline-flex align-items-center justify-content-center">
                        <button type="button" class="dr-action-btn dr-action-btn-edit" onclick="openRoutinePlanModal(${plan.RoutinePlanID})" title="Edit Plan & Day Matrix">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                        <button type="button" class="dr-action-btn dr-action-btn-timeline" onclick="viewRoutineTimeline(${plan.RoutinePlanID})" title="View Schedule Timeline">
                            <i class="fa-solid fa-timeline"></i>
                        </button>
                        <button type="button" class="dr-action-btn dr-action-btn-clone" onclick="openCloneModal(${plan.RoutinePlanID})" title="Duplicate Plan">
                            <i class="fa-solid fa-copy"></i>
                        </button>
                        <button type="button" class="dr-action-btn dr-action-btn-delete" onclick="deleteRoutinePlan(${plan.RoutinePlanID})" title="Delete Plan">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;

        $tbody.append(rowHtml);
    });
}

function updateKPICards(plans) {
    $("#kpiTotalPlans").text(plans.length);

    var uniqueSubCats = new Set();
    var totalSeedsMapped = 0;

    plans.forEach(function (p) {
        if (p.SubCategoryIDs && Array.isArray(p.SubCategoryIDs)) {
            p.SubCategoryIDs.forEach(function (id) { uniqueSubCats.add(id); });
        } else if (p.SubCategoryID) {
            uniqueSubCats.add(p.SubCategoryID);
        }

        if (p.SeedIDs && Array.isArray(p.SeedIDs)) {
            totalSeedsMapped += p.SeedIDs.length;
        }
    });

    $("#kpiTotalSubCats").text(uniqueSubCats.size);
    $("#kpiTotalSeeds").text(totalSeedsMapped);
}

function filterRoutinePlans() {
    var searchVal = $("#filterSearchInput").val().toLowerCase();
    $("#dailyRoutinePlanTbody tr").each(function () {
        var rowText = $(this).text().toLowerCase();
        $(this).toggle(rowText.indexOf(searchVal) > -1);
    });
}

/**
 * 3. Modal Builder: Create / Edit Routine Plan
 */
function openRoutinePlanModal(routinePlanId = 0) {
    routinePlanId = intval(routinePlanId);

    // Reset fields
    $("#modalRoutinePlanID").val(routinePlanId);
    $("#modalPlanCode").val("");
    $("#modalPlanName").val("");
    $("#modalTotalPlanDays").val(25);
    $("#matrixTotalDaysLabel").text(25);
    $("#modalPlanStatus").val("Active");
    $("#modalPlanDescription").val("");
    $("#seedSearchFilterInput").val("");

    renderSubCategoryChips([]);
    $("#seedChipsContainer").html(`<span class="text-muted small">Please select at least one subcategory above to load seeds.</span>`);
    $("#selectedSeedsCountBadge").text("0 selected");

    if (routinePlanId > 0) {
        $("#routinePlanModalLabel").html(`Edit Daily Routine Plan`);
        // Load existing plan details
        $.ajax({
            url: getApiBaseUrl() + "?action=get_routine_plan_details&routine_plan_id=" + routinePlanId,
            type: "GET",
            dataType: "json",
            success: function (res) {
                if (res && res.status && res.data) {
                    var p = res.data;
                    $("#modalPlanCode").val(p.PlanCode || "");
                    $("#modalPlanName").val(p.PlanName || "");
                    var totalDays = intval(p.TotalPlanDays) || 25;
                    $("#modalTotalPlanDays").val(totalDays);
                    $("#matrixTotalDaysLabel").text(totalDays);
                    $("#modalPlanStatus").val(p.Status || "Active");
                    $("#modalPlanDescription").val(p.Description || "");

                    renderSubCategoryChips(p.SubCategoryIDs || []);
                    loadSeedsForSelectedSubCategories(p.SeedIDs || []);
                    generateDayMatrixRows(totalDays, p.DayDetails || []);
                    showAppModal("routinePlanModal");
                } else {
                    Swal.fire("Error", res.message || "Failed to load plan details", "error");
                }
            },
            error: function () {
                Swal.fire("Error", "Server error loading plan details", "error");
            }
        });
    } else {
        $("#routinePlanModalLabel").html(`Create New Daily Routine Plan`);
        renderSubCategoryChips([]);
        generateDayMatrixRows(25, []);
        showAppModal("routinePlanModal");
    }
}

/**
 * Subcategory Chips Rendering
 */
function renderSubCategoryChips(selectedIds = []) {
    var $container = $("#subcatChipsContainer");
    $container.empty();

    if (allSubCategoriesCache.length === 0) {
        $container.html(`<span class="text-muted small">No subcategories available.</span>`);
        return;
    }

    selectedIds = selectedIds.map(function (id) { return intval(id); });

    allSubCategoriesCache.forEach(function (sub) {
        var isSelected = selectedIds.includes(intval(sub.SubCategoryID));
        var chip = `
            <label class="subcat-check-chip ${isSelected ? 'selected' : ''}" data-subcatid="${sub.SubCategoryID}">
                <input type="checkbox" value="${sub.SubCategoryID}" ${isSelected ? 'checked' : ''} onchange="onSubCatSelectionChange(this)">
                <i class="fa-solid fa-check check-icon" style="${isSelected ? '' : 'display:none;'}"></i>
                ${escapeHtml(sub.SubCategoryName)}
            </label>
        `;
        $container.append(chip);
    });
}

function onSubCatSelectionChange(el) {
    var $chip = $(el).closest(".subcat-check-chip");
    if ($(el).is(":checked")) {
        $chip.addClass("selected");
        $chip.find(".check-icon").show();
    } else {
        $chip.removeClass("selected");
        $chip.find(".check-icon").hide();
    }

    // Refresh seeds list based on currently checked subcategories
    loadSeedsForSelectedSubCategories();
}

function selectAllSubCats(select = true) {
    $("#subcatChipsContainer input[type='checkbox']").each(function () {
        $(this).prop("checked", select);
        var $chip = $(this).closest(".subcat-check-chip");
        if (select) {
            $chip.addClass("selected");
            $chip.find(".check-icon").show();
        } else {
            $chip.removeClass("selected");
            $chip.find(".check-icon").hide();
        }
    });
    loadSeedsForSelectedSubCategories();
}

function getSelectedSubCategoryIDs() {
    var ids = [];
    $("#subcatChipsContainer input[type='checkbox']:checked").each(function () {
        ids.push(intval($(this).val()));
    });
    return ids;
}

/**
 * Dynamic Seed Chips Rendering
 */
function loadSeedsForSelectedSubCategories(preSelectedSeedIds = []) {
    var subCatIds = getSelectedSubCategoryIDs();
    var $container = $("#seedChipsContainer");

    if (subCatIds.length === 0) {
        $container.html(`<span class="text-muted small">Please select at least one subcategory above to load seeds.</span>`);
        $("#selectedSeedsCountBadge").text("0 selected");
        return;
    }

    $container.html(`<div class="text-muted small py-2"><i class="fa-solid fa-spinner fa-spin me-2"></i>Loading seeds for selected subcategories...</div>`);

    $.ajax({
        url: getApiBaseUrl(),
        type: "POST",
        dataType: "json",
        data: {
            action: "get_seeds_by_subcategories",
            projectid: getProjectId(),
            subcategory_ids: JSON.stringify(subCatIds)
        },
        success: function (res) {
            if (res && res.status) {
                loadedSeedsCache = res.data || [];
                renderSeedChips(loadedSeedsCache, preSelectedSeedIds);
            } else {
                $container.html(`<span class="text-muted small">No seeds found for selected subcategories.</span>`);
            }
        },
        error: function () {
            $container.html(`<span class="text-danger small">Error loading seeds.</span>`);
        }
    });
}

function renderSeedChips(seeds, preSelectedSeedIds = []) {
    var $container = $("#seedChipsContainer");
    $container.empty();

    if (seeds.length === 0) {
        $container.html(`<span class="text-muted small">No seeds registered under the selected subcategories.</span>`);
        $("#selectedSeedsCountBadge").text("0 selected");
        return;
    }

    // If preSelectedSeedIds is not passed, preserve currently checked seeds
    if (preSelectedSeedIds.length === 0) {
        preSelectedSeedIds = getSelectedSeedIDs();
    }
    preSelectedSeedIds = preSelectedSeedIds.map(function (id) { return intval(id); });

    seeds.forEach(function (seed) {
        var isSelected = preSelectedSeedIds.includes(intval(seed.SeedID));
        var labelText = seed.SeedName + (seed.VarietyofSeed ? " (" + seed.VarietyofSeed + ")" : "") + (seed.BrandName ? " - " + seed.BrandName : "");

        var chip = `
            <label class="seed-check-chip ${isSelected ? 'selected' : ''}" data-seedid="${seed.SeedID}" data-text="${escapeHtml(labelText.toLowerCase())}">
                <input type="checkbox" value="${seed.SeedID}" ${isSelected ? 'checked' : ''} onchange="onSeedSelectionChange(this)">
                <i class="fa-solid fa-check check-icon" style="${isSelected ? '' : 'display:none;'}"></i>
                <span>${escapeHtml(labelText)}</span>
            </label>
        `;
        $container.append(chip);
    });

    updateSelectedSeedsBadge();
}

function onSeedSelectionChange(el) {
    var $chip = $(el).closest(".seed-check-chip");
    if ($(el).is(":checked")) {
        $chip.addClass("selected");
        $chip.find(".check-icon").show();
    } else {
        $chip.removeClass("selected");
        $chip.find(".check-icon").hide();
    }
    updateSelectedSeedsBadge();
}

function selectAllSeeds(select = true) {
    $("#seedChipsContainer .seed-check-chip:visible input[type='checkbox']").each(function () {
        $(this).prop("checked", select);
        var $chip = $(this).closest(".seed-check-chip");
        if (select) {
            $chip.addClass("selected");
            $chip.find(".check-icon").show();
        } else {
            $chip.removeClass("selected");
            $chip.find(".check-icon").hide();
        }
    });
    updateSelectedSeedsBadge();
}

function updateSelectedSeedsBadge() {
    var count = $("#seedChipsContainer input[type='checkbox']:checked").length;
    var total = $("#seedChipsContainer .seed-check-chip").length;
    $("#selectedSeedsCountBadge").text(`${count} / ${total} selected`);
}

function filterSeedChips() {
    var searchVal = $("#seedSearchFilterInput").val().toLowerCase();
    $("#seedChipsContainer .seed-check-chip").each(function () {
        var text = $(this).data("text") || "";
        $(this).toggle(text.indexOf(searchVal) > -1);
    });
}

function getSelectedSeedIDs() {
    var ids = [];
    $("#seedChipsContainer input[type='checkbox']:checked").each(function () {
        ids.push(intval($(this).val()));
    });
    return ids;
}

/**
 * 4. Dynamic Day Stepper & Matrix Generation (Using Dynamic Dropdown Options from DB)
 */
function adjustDurationDays(delta) {
    var current = intval($("#modalTotalPlanDays").val()) || 25;
    var updated = Math.max(1, Math.min(90, current + delta));
    $("#modalTotalPlanDays").val(updated);
    onPlanDaysInputChange();
}

function onPlanDaysInputChange() {
    var totalDays = Math.max(1, Math.min(90, intval($("#modalTotalPlanDays").val()) || 25));
    $("#modalTotalPlanDays").val(totalDays);
    $("#matrixTotalDaysLabel").text(totalDays);

    // Collect currently typed data from existing day rows to preserve it
    var currentData = extractCurrentDayMatrixValues();
    generateDayMatrixRows(totalDays, currentData);
}

function extractCurrentDayMatrixValues() {
    var list = [];
    $("#dayMatrixRowsContainer .day-matrix-card").each(function () {
        var dayNum = intval($(this).data("daynumber"));
        list.push({
            DayNumber: dayNum,
            StageName: $(this).find(".input-stage-name").val() || "",
            ActivityType: $(this).find(".input-activity-type").val() || "",
            TaskTitle: $(this).find(".input-task-title").val() || "",
            ItemDetails: $(this).find(".input-item-details").val() || "",
            ApplicationMethod: $(this).find(".input-app-method").val() || "",
            TimeSlot: $(this).find(".input-time-slot").val() || "Morning",
            TargetPlantHeight: $(this).find(".input-target-height").val() || "",
            TargetHealthStatus: $(this).find(".input-target-health").val() || "Healthy",
            SpecialInstructions: $(this).find(".input-special-instructions").val() || "",
            Status: $(this).find(".input-day-status").val() || "Active"
        });
    });
    return list;
}

function generateDayMatrixRows(totalDays = 25, existingDayValues = []) {
    var $container = $("#dayMatrixRowsContainer");
    $container.empty();

    // Map existing days by DayNumber
    var dataByDay = {};
    if (existingDayValues && Array.isArray(existingDayValues)) {
        existingDayValues.forEach(function (d) {
            dataByDay[intval(d.DayNumber)] = d;
        });
    }

    // Dynamic options from JSON table cache
    var stageList = (dropdownOptionsCache.growth_stage || []).map(function (o) { return o.name; });
    if (stageList.length === 0) {
        stageList = ["Germination Box", "Green Room", "Primary Polyhouse", "Grafting & Healing", "Secondary Hardening", "Final Hardening", "Ready to Dispatch"];
    }

    var activityList = (dropdownOptionsCache.activity_type || []).map(function (o) { return o.name; });
    if (activityList.length === 0) {
        activityList = ["Watering/Irrigation", "Nutrition/Fertilizer", "Plant Protection/Spray", "Environment Control", "Inspection/QC", "Grafting/Trimming", "Hardening"];
    }

    var methodList = (dropdownOptionsCache.application_method || []).map(function (o) { return o.name; });
    if (methodList.length === 0) {
        methodList = ["Foliar Spray", "Drenching", "Fogging/Misting", "Soil Application", "Manual Inspection", "Automated Boom"];
    }

    var timeSlotList = (dropdownOptionsCache.time_slot || []).map(function (o) { return o.name; });
    if (timeSlotList.length === 0) {
        timeSlotList = ["Morning", "Afternoon", "Evening", "Twice Daily", "All Day"];
    }

    for (var day = 1; day <= totalDays; day++) {
        var dayVal = dataByDay[day] || {};

        var stageSelectHtml = `<select class="dr-custom-select input-stage-name">`;
        stageList.forEach(function (st) {
            var selected = (dayVal.StageName === st) ? 'selected' : '';
            stageSelectHtml += `<option value="${escapeHtml(st)}" ${selected}>${escapeHtml(st)}</option>`;
        });
        stageSelectHtml += `</select>`;

        var activitySelectHtml = `<select class="dr-custom-select input-activity-type">`;
        activityList.forEach(function (act) {
            var selected = (dayVal.ActivityType === act) ? 'selected' : '';
            activitySelectHtml += `<option value="${escapeHtml(act)}" ${selected}>${escapeHtml(act)}</option>`;
        });
        activitySelectHtml += `</select>`;

        var methodSelectHtml = `<select class="dr-custom-select input-app-method">`;
        methodList.forEach(function (m) {
            var selected = (dayVal.ApplicationMethod === m) ? 'selected' : '';
            methodSelectHtml += `<option value="${escapeHtml(m)}" ${selected}>${escapeHtml(m)}</option>`;
        });
        methodSelectHtml += `</select>`;

        var timeSlotVal = dayVal.TimeSlot || (timeSlotList[0] || "Morning");
        var timeSlotHtml = `<select class="dr-custom-select input-time-slot">`;
        timeSlotList.forEach(function (ts) {
            var selected = (timeSlotVal === ts) ? 'selected' : '';
            timeSlotHtml += `<option value="${escapeHtml(ts)}" ${selected}>${escapeHtml(ts)}</option>`;
        });
        timeSlotHtml += `</select>`;

        var cardHtml = `
            <div class="day-matrix-card" data-daynumber="${day}">
                <!-- Card Header Strip -->
                <div class="day-matrix-card-header">
                    <span class="day-badge-header"><i class="fa-solid fa-calendar-check me-1"></i> DAY ${day}</span>
                    <input type="text" class="dr-task-title-input input-task-title" placeholder="Operation / Task Title for Day ${day} (e.g. Misting & 19:19:19 Drench)" value="${escapeHtml(dayVal.TaskTitle || '')}">
                    <div class="d-flex align-items-center gap-2">
                        ${day > 1 ? `<button type="button" class="dr-btn-copy" onclick="copyFromPreviousDay(${day})" title="Copy details from Day ${day-1}"><i class="fa-solid fa-copy"></i> Copy Day ${day-1}</button>` : ''}
                        <button type="button" class="dr-btn-clear" onclick="clearSingleDayRow(${day})" title="Clear Day ${day} inputs"><i class="fa-solid fa-trash-can"></i></button>
                    </div>
                </div>

                <!-- Card Content Form Grid -->
                <div class="day-form-grid bg-white">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="dr-field-label"><i class="fa-solid fa-seedling text-teal me-1" style="color: #0f766e;"></i>Growth Stage</label>
                            ${stageSelectHtml}
                        </div>

                        <div class="col-md-3">
                            <label class="dr-field-label"><i class="fa-solid fa-bolt text-warning me-1"></i>Activity Type</label>
                            ${activitySelectHtml}
                        </div>

                        <div class="col-md-4">
                            <label class="dr-field-label"><i class="fa-solid fa-prescription-bottle-medical text-primary me-1"></i>Item & Dosage</label>
                            <input type="text" class="dr-form-control input-item-details" placeholder="e.g. 19:19:19 @ 1.5g/L + Humic 1ml" value="${escapeHtml(dayVal.ItemDetails || '')}">
                        </div>

                        <div class="col-md-2">
                            <label class="dr-field-label"><i class="fa-solid fa-clock text-info me-1"></i>Time Slot</label>
                            ${timeSlotHtml}
                        </div>

                        <div class="col-md-3">
                            <label class="dr-field-label"><i class="fa-solid fa-shower text-secondary me-1"></i>Application Method</label>
                            ${methodSelectHtml}
                        </div>

                        <div class="col-md-3">
                            <label class="dr-field-label"><i class="fa-solid fa-ruler-vertical text-teal me-1" style="color: #0f766e;"></i>Target Plant Height</label>
                            <input type="text" class="dr-form-control input-target-height" placeholder="e.g. 5-8 mm, 2 Leaves" value="${escapeHtml(dayVal.TargetPlantHeight || '')}">
                        </div>

                        <div class="col-md-6">
                            <label class="dr-field-label"><i class="fa-solid fa-temperature-three-quarters text-danger me-1"></i>Special / Climate Notes</label>
                            <input type="text" class="dr-form-control input-special-instructions" placeholder="e.g. Maintain 26°C & 85% RH, avoid direct sunlight" value="${escapeHtml(dayVal.SpecialInstructions || '')}">
                        </div>
                    </div>
                </div>
            </div>
        `;

        $container.append(cardHtml);
    }
}

function copyFromPreviousDay(currentDay) {
    if (currentDay <= 1) return;
    var $prevCard = $(`#dayMatrixRowsContainer .day-matrix-card[data-daynumber='${currentDay - 1}']`);
    var $currCard = $(`#dayMatrixRowsContainer .day-matrix-card[data-daynumber='${currentDay}']`);

    if ($prevCard.length && $currCard.length) {
        $currCard.find(".input-task-title").val($prevCard.find(".input-task-title").val());
        $currCard.find(".input-stage-name").val($prevCard.find(".input-stage-name").val());
        $currCard.find(".input-activity-type").val($prevCard.find(".input-activity-type").val());
        $currCard.find(".input-item-details").val($prevCard.find(".input-item-details").val());
        $currCard.find(".input-app-method").val($prevCard.find(".input-app-method").val());
        $currCard.find(".input-time-slot").val($prevCard.find(".input-time-slot").val());
        $currCard.find(".input-target-height").val($prevCard.find(".input-target-height").val());
        $currCard.find(".input-special-instructions").val($prevCard.find(".input-special-instructions").val());

        $currCard.css("background-color", "#f0fdf4");
        setTimeout(function () { $currCard.css("background-color", "#ffffff"); }, 400);
    }
}

function clearSingleDayRow(day) {
    var $card = $(`#dayMatrixRowsContainer .day-matrix-card[data-daynumber='${day}']`);
    if ($card.length) {
        $card.find("input").val("");
    }
}

function clearAllDaysMatrix() {
    $("#dayMatrixRowsContainer input").val("");
}

/**
 * 5. Standard 25-Day Nursery Routine Preset
 */
function loadStandard25DayPreset() {
    var preset = [
        // Days 1-3: Germination Box
        { DayNumber: 1, StageName: "Germination Box", ActivityType: "Watering/Irrigation", TaskTitle: "Sowing Tray Placement & Moisture Check", ItemDetails: "Plain RO Water misting", ApplicationMethod: "Fogging/Misting", TimeSlot: "Morning", TargetPlantHeight: "Germination Start", SpecialInstructions: "Keep in dark room / Germination box at 26-28°C and 85-90% RH" },
        { DayNumber: 2, StageName: "Germination Box", ActivityType: "Environment Control", TaskTitle: "Dark Chamber Temperature & RH Inspection", ItemDetails: "Humidity Maintenance", ApplicationMethod: "Manual Inspection", TimeSlot: "All Day", TargetPlantHeight: "Radicle Emergence", SpecialInstructions: "Ensure no drying of top coco-peat surface" },
        { DayNumber: 3, StageName: "Germination Box", ActivityType: "Inspection/QC", TaskTitle: "Sprouting Check & Germination Readiness", ItemDetails: "Visual Germination QC", ApplicationMethod: "Manual Inspection", TimeSlot: "Morning", TargetPlantHeight: "2-4 mm sprout", SpecialInstructions: "Transfer trays to green room upon 80% emergence" },
        
        // Days 4-6: Green Room / Light Adaptation
        { DayNumber: 4, StageName: "Green Room", ActivityType: "Watering/Irrigation", TaskTitle: "Green Room Shift & Gentle Morning Misting", ItemDetails: "Water + Humic Acid @ 1 ml/L", ApplicationMethod: "Fogging/Misting", TimeSlot: "Morning", TargetPlantHeight: "5-8 mm", SpecialInstructions: "Provide diffused sunlight, 24-26°C" },
        { DayNumber: 5, StageName: "Green Room", ActivityType: "Nutrition/Fertilizer", TaskTitle: "Cotyledon Opening & Light Bio-stimulant", ItemDetails: "Seaweed Extract @ 1 ml/L", ApplicationMethod: "Foliar Spray", TimeSlot: "Morning", TargetPlantHeight: "8-12 mm", SpecialInstructions: "Inspect for damping-off" },
        { DayNumber: 6, StageName: "Green Room", ActivityType: "Environment Control", TaskTitle: "Shift to Primary Polyhouse", ItemDetails: "Fresh Water Spray", ApplicationMethod: "Foliar Spray", TimeSlot: "Evening", TargetPlantHeight: "12-15 mm", SpecialInstructions: "Place on raised mesh benches" },

        // Days 7-12: Primary Polyhouse & Vegetative Growth
        { DayNumber: 7, StageName: "Primary Polyhouse", ActivityType: "Nutrition/Fertilizer", TaskTitle: "First True Leaf Nutrition Drench", ItemDetails: "NPK 19:19:19 @ 1.5 g/L", ApplicationMethod: "Drenching", TimeSlot: "Morning", TargetPlantHeight: "15-20 mm", SpecialInstructions: "Water lightly before nutrient drenching" },
        { DayNumber: 8, StageName: "Primary Polyhouse", ActivityType: "Watering/Irrigation", TaskTitle: "Morning Root Zone Watering", ItemDetails: "Plain Water", ApplicationMethod: "Automated Boom", TimeSlot: "Morning", TargetPlantHeight: "1-2 True Leaves", SpecialInstructions: "Avoid late evening watering to prevent fungal growth" },
        { DayNumber: 9, StageName: "Primary Polyhouse", ActivityType: "Plant Protection/Spray", TaskTitle: "Preventive Fungicide Treatment", ItemDetails: "Ridomil Gold / Metalaxyl @ 1.5 g/L", ApplicationMethod: "Foliar Spray", TimeSlot: "Morning", TargetPlantHeight: "2-2.5 cm", SpecialInstructions: "Spray on foliage and root collar" },
        { DayNumber: 10, StageName: "Primary Polyhouse", ActivityType: "Nutrition/Fertilizer", TaskTitle: "Root Development Feeding", ItemDetails: "NPK 12:61:00 (MAP) @ 1.5 g/L", ApplicationMethod: "Drenching", TimeSlot: "Morning", TargetPlantHeight: "2.5-3 cm", SpecialInstructions: "Enhances fibrous root system" },
        { DayNumber: 11, StageName: "Primary Polyhouse", ActivityType: "Inspection/QC", TaskTitle: "Pest & Vector Sticky Trap QC", ItemDetails: "Yellow/Blue Sticky Traps Inspection", ApplicationMethod: "Manual Inspection", TimeSlot: "Morning", TargetPlantHeight: "3 cm", SpecialInstructions: "Check for whitefly, thrips, aphid activity" },
        { DayNumber: 12, StageName: "Primary Polyhouse", ActivityType: "Nutrition/Fertilizer", TaskTitle: "Micronutrient & Calcium Spray", ItemDetails: "Chelated Micronutrients @ 1 g/L + Cal-Mag", ApplicationMethod: "Foliar Spray", TimeSlot: "Morning", TargetPlantHeight: "3.5 cm", SpecialInstructions: "Strengthens cell wall" },

        // Days 13-18: Secondary Hardening / Grafting / Growth
        { DayNumber: 13, StageName: "Secondary Hardening", ActivityType: "Watering/Irrigation", TaskTitle: "Controlled Moisture Regime", ItemDetails: "Plain Water", ApplicationMethod: "Automated Boom", TimeSlot: "Morning", TargetPlantHeight: "4 cm", SpecialInstructions: "Slight moisture stress to encourage deep root penetration" },
        { DayNumber: 14, StageName: "Secondary Hardening", ActivityType: "Nutrition/Fertilizer", TaskTitle: "Stem Girth & Foliage Boost", ItemDetails: "NPK 13:40:13 @ 2 g/L", ApplicationMethod: "Drenching", TimeSlot: "Morning", TargetPlantHeight: "4.5 cm", SpecialInstructions: "Check for uniform stem caliper" },
        { DayNumber: 15, StageName: "Secondary Hardening", ActivityType: "Plant Protection/Spray", TaskTitle: "Preventive Insecticide Spray", ItemDetails: "Neem Oil 10,000 ppm @ 2 ml/L or Confidor @ 0.5 ml/L", ApplicationMethod: "Foliar Spray", TimeSlot: "Evening", TargetPlantHeight: "5 cm", SpecialInstructions: "Target undersides of leaves" },
        { DayNumber: 16, StageName: "Secondary Hardening", ActivityType: "Environment Control", TaskTitle: "Gradual Sun Curtain Retraction", ItemDetails: "50% Shading adjustment", ApplicationMethod: "Environment Control", TimeSlot: "Morning", TargetPlantHeight: "5.5 cm", SpecialInstructions: "Expose to moderate direct sunlight for 2-3 hours" },
        { DayNumber: 17, StageName: "Secondary Hardening", ActivityType: "Nutrition/Fertilizer", TaskTitle: "Potassium & Silica Strength Spray", ItemDetails: "Potassium Silicate @ 1 ml/L + NPK 00:52:34 @ 1.5 g/L", ApplicationMethod: "Foliar Spray", TimeSlot: "Morning", TargetPlantHeight: "6 cm", SpecialInstructions: "Hardens plant stems for transport resistance" },
        { DayNumber: 18, StageName: "Secondary Hardening", ActivityType: "Inspection/QC", TaskTitle: "Tray Uniformity & Correction QC", ItemDetails: "Health & Cavity Inspection", ApplicationMethod: "Manual Inspection", TimeSlot: "Morning", TargetPlantHeight: "6.5 cm", SpecialInstructions: "Tag any non-uniform or weak seedlings" },

        // Days 19-22: Final Hardening
        { DayNumber: 19, StageName: "Final Hardening", ActivityType: "Hardening", TaskTitle: "Full Sunlight Hardening Stage 1", ItemDetails: "Restricted Morning Watering", ApplicationMethod: "Automated Boom", TimeSlot: "Morning", TargetPlantHeight: "7 cm", SpecialInstructions: "Open ventilation vents, expose to field ambient temperature" },
        { DayNumber: 20, StageName: "Final Hardening", ActivityType: "Nutrition/Fertilizer", TaskTitle: "Pre-dispatch Root Booster Drench", ItemDetails: "Humic Acid + Trichoderma viride @ 2 g/L", ApplicationMethod: "Drenching", TimeSlot: "Morning", TargetPlantHeight: "7.5 cm", SpecialInstructions: "Inoculate root ball with beneficial bio-fungi" },
        { DayNumber: 21, StageName: "Final Hardening", ActivityType: "Hardening", TaskTitle: "Full Sunlight Hardening Stage 2", ItemDetails: "Controlled Water Regime", ApplicationMethod: "Automated Boom", TimeSlot: "Morning", TargetPlantHeight: "8 cm", SpecialInstructions: "Toughens cuticles and thickens stems" },
        { DayNumber: 22, StageName: "Final Hardening", ActivityType: "Plant Protection/Spray", TaskTitle: "Pre-dispatch Broad Spectrum Protection", ItemDetails: "Saaf / Carbendazim+Mancozeb @ 1.5 g/L", ApplicationMethod: "Foliar Spray", TimeSlot: "Evening", TargetPlantHeight: "8-9 cm", SpecialInstructions: "Shields against transit pathogens" },

        // Days 23-25: Final QC & Ready for Dispatch
        { DayNumber: 23, StageName: "Ready to Dispatch", ActivityType: "Inspection/QC", TaskTitle: "Final Plant Count & Root Ball QC", ItemDetails: "Root Binding & Height Verification", ApplicationMethod: "Manual Inspection", TimeSlot: "Morning", TargetPlantHeight: "9-10 cm", SpecialInstructions: "Verify 100% plug pullability and root intactness" },
        { DayNumber: 24, StageName: "Ready to Dispatch", ActivityType: "Watering/Irrigation", TaskTitle: "Pre-Shipping Optimal Moisture Drench", ItemDetails: "Clean Water Drenching", ApplicationMethod: "Automated Boom", TimeSlot: "Morning", TargetPlantHeight: "Ready for Delivery", SpecialInstructions: "Ensure plugs are sufficiently moist for 24-48h transport" },
        { DayNumber: 25, StageName: "Ready to Dispatch", ActivityType: "Inspection/QC", TaskTitle: "Tray Tagging, Packing & Dispatch Handover", ItemDetails: "Barcode Verification & Loading", ApplicationMethod: "Manual Inspection", TimeSlot: "Morning", TargetPlantHeight: "Optimal Nursery Standard", SpecialInstructions: "Handover to vehicle loading team as per Provisional Sale Order" }
    ];

    var totalDays = intval($("#modalTotalPlanDays").val()) || 25;
    generateDayMatrixRows(totalDays, preset);

    Swal.fire({
        icon: "success",
        title: "Template Loaded",
        text: "Standard 25-day agronomic nursery routine loaded into the matrix!",
        timer: 1500,
        showConfirmButton: false
    });
}

/**
 * 6. Save Daily Routine Plan (Header + Multi-Subcategories + Seed IDs + Day Matrix)
 */
function saveDailyRoutinePlan() {
    var planName = $.trim($("#modalPlanName").val());
    var totalPlanDays = intval($("#modalTotalPlanDays").val()) || 25;
    var status = $("#modalPlanStatus").val() || "Active";
    var description = $.trim($("#modalPlanDescription").val());
    var routinePlanId = intval($("#modalRoutinePlanID").val());
    var planCode = $("#modalPlanCode").val();

    var subCategoryIds = getSelectedSubCategoryIDs();
    var seedIds = getSelectedSeedIDs();

    if (!planName) {
        Swal.fire("Required Field", "Please enter Plan Name.", "warning");
        $("#modalPlanName").focus();
        return;
    }

    if (subCategoryIds.length === 0) {
        Swal.fire("Required Field", "Please select at least one Subcategory for this plan.", "warning");
        return;
    }

    // Extract day matrix rows
    var dayDetails = extractCurrentDayMatrixValues();

    var payload = {
        RoutinePlanID: routinePlanId,
        PlanCode: planCode,
        PlanName: planName,
        CategoryID: "",
        SubCategoryIDs: subCategoryIds,
        SeedIDs: seedIds,
        TotalPlanDays: totalPlanDays,
        Description: description,
        Status: status,
        ProjectId: getProjectId(),
        CreatedBy: getLoginId(),
        DayDetails: dayDetails
    };

    var spin = $("#savePlanSpin");
    spin.addClass("fa-spin");

    $.ajax({
        url: getApiBaseUrl() + "?action=save_routine_plan",
        type: "POST",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify(payload),
        success: function (res) {
            spin.removeClass("fa-spin");
            if (res && res.status) {
                hideAppModal("routinePlanModal");
                Swal.fire({
                    icon: "success",
                    title: "Success",
                    text: res.message || "Daily Routine Plan saved successfully!",
                    timer: 1800,
                    showConfirmButton: false
                });
                loadRoutinePlans();
            } else {
                Swal.fire("Error", res.message || "Failed to save plan.", "error");
            }
        },
        error: function () {
            spin.removeClass("fa-spin");
            Swal.fire("Error", "Server error while saving Daily Routine Plan.", "error");
        }
    });
}

/**
 * 7. View Routine Timeline / Schedule Chart
 */
function viewRoutineTimeline(routinePlanId) {
    $.ajax({
        url: getApiBaseUrl() + "?action=get_routine_plan_details&routine_plan_id=" + routinePlanId,
        type: "GET",
        dataType: "json",
        success: function (res) {
            if (res && res.status && res.data) {
                var p = res.data;
                var subCatNames = (p.SubCategoryDetails || []).map(function (s) { return s.SubCategoryName; }).join(", ");
                var seedNames = (p.SeedDetails || []).map(function (s) { return s.SeedName + (s.VarietyofSeed ? " (" + s.VarietyofSeed + ")" : ""); }).join(", ");

                $("#timelineSubHeader").html(`Plan: <strong>${escapeHtml(p.PlanName)}</strong> (${escapeHtml(p.PlanCode)}) | Crops: <strong>${escapeHtml(subCatNames || 'All')}</strong> | Duration: <strong>${p.TotalPlanDays} Days</strong>`);

                var tableHtml = `
                    <div class="card p-3 mb-3 bg-white border-0 shadow-sm">
                        <div class="row g-2 small">
                            <div class="col-md-4"><strong>Plan Name:</strong> ${escapeHtml(p.PlanName)}</div>
                            <div class="col-md-4"><strong>Plan Code:</strong> <span class="badge badge-secondary bg-secondary font-monospace">${escapeHtml(p.PlanCode)}</span></div>
                            <div class="col-md-4"><strong>Duration:</strong> <span class="badge badge-success bg-success">${p.TotalPlanDays} Days</span></div>
                            <div class="col-md-6"><strong>Subcategories:</strong> ${escapeHtml(subCatNames || 'None')}</div>
                            <div class="col-md-6"><strong>Mapped Seeds (${(p.SeedDetails || []).length}):</strong> ${escapeHtml(seedNames || 'All')}</div>
                            ${p.Description ? `<div class="col-12 mt-1"><strong>Description:</strong> ${escapeHtml(p.Description)}</div>` : ''}
                        </div>
                    </div>

                    <div class="table-responsive bg-white rounded-3 shadow-sm border">
                        <table class="table table-bordered table-hover mb-0" id="printTimelineTable">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width: 70px;" class="text-center">Day</th>
                                    <th style="width: 150px;">Growth Stage</th>
                                    <th style="width: 150px;">Activity Type</th>
                                    <th style="width: 220px;">Task Title</th>
                                    <th style="width: 220px;">Item / Chemical & Dosage</th>
                                    <th style="width: 120px;">Method</th>
                                    <th style="width: 100px;">Time Slot</th>
                                    <th style="width: 140px;">Target Height/Condition</th>
                                    <th>Special Instructions</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                if (p.DayDetails && p.DayDetails.length > 0) {
                    p.DayDetails.forEach(function (d) {
                        tableHtml += `
                            <tr>
                                <td class="text-center fw-bold bg-light"><span class="badge badge-primary bg-teal" style="background-color: #0f766e; color: #fff;">Day ${d.DayNumber}</span></td>
                                <td class="fw-semibold text-dark">${escapeHtml(d.StageName || '')}</td>
                                <td><span class="badge badge-info bg-info text-white">${escapeHtml(d.ActivityType || '')}</span></td>
                                <td class="fw-bold">${escapeHtml(d.TaskTitle || '')}</td>
                                <td class="text-primary">${escapeHtml(d.ItemDetails || '')}</td>
                                <td>${escapeHtml(d.ApplicationMethod || '')}</td>
                                <td><span class="badge badge-light bg-light text-dark border">${escapeHtml(d.TimeSlot || '')}</span></td>
                                <td>${escapeHtml(d.TargetPlantHeight || '')}</td>
                                <td class="small text-muted">${escapeHtml(d.SpecialInstructions || '')}</td>
                            </tr>
                        `;
                    });
                } else {
                    tableHtml += `<tr><td colspan="9" class="text-center py-4 text-muted">No day-wise activities configured for this plan.</td></tr>`;
                }

                tableHtml += `
                            </tbody>
                        </table>
                    </div>
                `;

                $("#timelineContentBody").html(tableHtml);
                showAppModal("viewTimelineModal");
            }
        }
    });
}

function printTimelineChart() {
    var printContents = document.getElementById("timelineContentBody").innerHTML;
    var printWindow = window.open('', '', 'height=800,width=1100');
    printWindow.document.write('<html><head><title>Daily Routine Schedule Plan</title>');
    printWindow.document.write('<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">');
    printWindow.document.write('<style>body{padding:20px;font-family:Arial,sans-serif;} th{background:#0f766e!important;color:#fff!important;}</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write('<h3>Daily Routine Plan Schedule</h3>');
    printWindow.document.write(printContents);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.focus();
    setTimeout(function () {
        printWindow.print();
        printWindow.close();
    }, 500);
}

/**
 * 8. Clone / Duplicate Plan
 */
function openCloneModal(routinePlanId) {
    $("#cloneSourcePlanID").val(routinePlanId);
    var plan = currentPlansList.find(function (p) { return p.RoutinePlanID == routinePlanId; });
    if (plan) {
        $("#cloneNewPlanName").val(plan.PlanName + " (Copy)");
    }
    showAppModal("clonePlanModal");
}

function submitClonePlan() {
    var sourceId = intval($("#cloneSourcePlanID").val());
    var newName = $.trim($("#cloneNewPlanName").val());
    var targetSubCat = $("#cloneTargetSubCategory").val();

    if (!newName) {
        Swal.fire("Required", "Please enter new plan name.", "warning");
        return;
    }

    $.ajax({
        url: getApiBaseUrl(),
        type: "POST",
        dataType: "json",
        data: {
            action: "duplicate_routine_plan",
            source_plan_id: sourceId,
            new_plan_name: newName,
            target_subcategory_ids: JSON.stringify([intval(targetSubCat)]),
            projectid: getProjectId(),
            user_id: getLoginId()
        },
        success: function (res) {
            if (res && res.status) {
                hideAppModal("clonePlanModal");
                Swal.fire("Duplicated", "Routine plan copied successfully!", "success");
                loadRoutinePlans();
            } else {
                Swal.fire("Error", res.message || "Failed to duplicate plan.", "error");
            }
        }
    });
}

/**
 * 9. Delete Routine Plan
 */
function deleteRoutinePlan(routinePlanId) {
    Swal.fire({
        title: "Delete Daily Routine Plan?",
        text: "This will permanently remove this routine plan and all its day-by-day operations.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Yes, Delete It!"
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: getApiBaseUrl(),
                type: "POST",
                dataType: "json",
                data: {
                    action: "delete_routine_plan",
                    routine_plan_id: routinePlanId,
                    hard_delete: true
                },
                success: function (res) {
                    if (res && res.status) {
                        Swal.fire("Deleted!", "Routine plan has been deleted.", "success");
                        loadRoutinePlans();
                    } else {
                        Swal.fire("Error", res.message || "Failed to delete plan.", "error");
                    }
                }
            });
        }
    });
}

/**
 * 10. Dynamic Dropdown Options Manager (Single MySQL Table CRUD)
 */
function openManageDropdownsModal() {
    switchDropdownGroupTab(activeDropdownGroupTab);
    showAppModal("manageDropdownsModal");
}

function switchDropdownGroupTab(groupKey) {
    activeDropdownGroupTab = groupKey;

    $("#dropdownGroupTabs .nav-link").removeClass("active");
    $(`#tab-${groupKey}`).addClass("active");

    var groupTitles = {
        growth_stage: "Growth Stages",
        activity_type: "Activity Types",
        application_method: "Application Methods",
        time_slot: "Time Slots"
    };

    var currentTitle = groupTitles[groupKey] || "Options";
    $("#currentGroupAddLabel").text(`Add New Option to ${currentTitle}`);
    $("#currentGroupListTitle").text(`${currentTitle} (${(dropdownOptionsCache[groupKey] || []).length})`);
    $("#newOptionInput").val("").focus();

    renderCurrentGroupOptionsList();
}

function renderCurrentGroupOptionsList() {
    var $ul = $("#groupOptionsListUl");
    $ul.empty();

    var options = dropdownOptionsCache[activeDropdownGroupTab] || [];
    $("#currentGroupCountBadge").text(`${options.length} options`);

    if (options.length === 0) {
        $ul.html(`<li class="list-group-item text-center text-muted py-4">No options found. Add one above.</li>`);
        return;
    }

    options.forEach(function (opt, idx) {
        var optId = opt.id || (idx + 1);
        var optName = opt.name || "";
        var li = `
            <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-muted border">${idx + 1}</span>
                    <span class="fw-semibold text-dark">${escapeHtml(optName)}</span>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="deleteDropdownOption('${activeDropdownGroupTab}', '${optId}', '${escapeHtml(optName)}')" title="Delete this option">
                    <i class="fa-solid fa-trash-can small"></i>
                </button>
            </li>
        `;
        $ul.append(li);
    });
}

function addNewDropdownOption() {
    var name = $.trim($("#newOptionInput").val());
    if (!name) {
        Swal.fire("Required", "Please enter option name.", "warning");
        $("#newOptionInput").focus();
        return;
    }

    var spin = $("#addOptionSpin");
    spin.addClass("fa-spin");

    $.ajax({
        url: getApiBaseUrl(),
        type: "POST",
        dataType: "json",
        data: {
            action: "add_dropdown_option",
            option_group: activeDropdownGroupTab,
            option_name: name,
            projectid: getProjectId(),
            user_id: getLoginId()
        },
        success: function (res) {
            spin.removeClass("fa-spin");
            if (res && res.status) {
                dropdownOptionsCache[activeDropdownGroupTab] = res.options || [];
                $("#newOptionInput").val("");
                renderCurrentGroupOptionsList();
                // Refresh existing day matrix dropdowns live
                refreshActiveDayMatrixDropdowns();
            } else {
                Swal.fire("Notice", res.message || "Could not add option.", "info");
            }
        },
        error: function () {
            spin.removeClass("fa-spin");
            Swal.fire("Error", "Server error adding option.", "error");
        }
    });
}

function deleteDropdownOption(groupKey, optId, optName) {
    Swal.fire({
        title: "Delete Option?",
        text: `Are you sure you want to delete "${optName}" from dropdowns?`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Yes, Delete"
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: getApiBaseUrl(),
                type: "POST",
                dataType: "json",
                data: {
                    action: "delete_dropdown_option",
                    option_group: groupKey,
                    item_identifier: optId,
                    projectid: getProjectId(),
                    user_id: getLoginId()
                },
                success: function (res) {
                    if (res && res.status) {
                        dropdownOptionsCache[groupKey] = res.options || [];
                        renderCurrentGroupOptionsList();
                        // Refresh existing day matrix dropdowns live
                        refreshActiveDayMatrixDropdowns();
                    } else {
                        Swal.fire("Error", res.message || "Failed to delete option.", "error");
                    }
                }
            });
        }
    });
}

function refreshActiveDayMatrixDropdowns() {
    var currentValues = extractCurrentDayMatrixValues();
    var totalDays = intval($("#modalTotalPlanDays").val()) || 25;
    generateDayMatrixRows(totalDays, currentValues);
}

/**
 * 11. Export to Excel
 */
function exportRoutinePlansToExcel() {
    var filename = "Daily_Routine_Plans_Master_" + new Date().toISOString().slice(0, 10) + ".xls";

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
                        <th>Plan Code</th>
                        <th>Plan Name</th>
                        <th>Mapped Subcategories</th>
                        <th>Mapped Seeds Count</th>
                        <th>Plan Duration</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
    `;

    currentPlansList.forEach(function (p, idx) {
        var subCatNames = (p.SubCategoryNames && Object.keys(p.SubCategoryNames).length > 0) 
            ? Object.values(p.SubCategoryNames).join(", ") 
            : (p.PrimarySubCategoryName || '');
        
        excelContent += `
            <tr>
                <td style="text-align: center;">${idx + 1}</td>
                <td>${escapeHtml(p.PlanCode || '')}</td>
                <td>${escapeHtml(p.PlanName || '')}</td>
                <td>${escapeHtml(subCatNames)}</td>
                <td style="text-align: center;">${p.SeedCount || 0}</td>
                <td style="text-align: center;">${p.TotalPlanDays || 25} Days</td>
                <td style="text-align: center;">${p.Status || 'Active'}</td>
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
window.openRoutinePlanModal = openRoutinePlanModal;
window.saveDailyRoutinePlan = saveDailyRoutinePlan;
window.adjustDurationDays = adjustDurationDays;
window.onPlanDaysInputChange = onPlanDaysInputChange;
window.selectAllSubCats = selectAllSubCats;
window.onSubCatSelectionChange = onSubCatSelectionChange;
window.selectAllSeeds = selectAllSeeds;
window.onSeedSelectionChange = onSeedSelectionChange;
window.filterSeedChips = filterSeedChips;
window.loadStandard25DayPreset = loadStandard25DayPreset;
window.clearAllDaysMatrix = clearAllDaysMatrix;
window.clearSingleDayRow = clearSingleDayRow;
window.copyFromPreviousDay = copyFromPreviousDay;
window.viewRoutineTimeline = viewRoutineTimeline;
window.printTimelineChart = printTimelineChart;
window.openCloneModal = openCloneModal;
window.submitClonePlan = submitClonePlan;
window.deleteRoutinePlan = deleteRoutinePlan;
window.exportRoutinePlansToExcel = exportRoutinePlansToExcel;
window.loadRoutinePlans = loadRoutinePlans;
window.filterRoutinePlans = filterRoutinePlans;
window.openManageDropdownsModal = openManageDropdownsModal;
window.switchDropdownGroupTab = switchDropdownGroupTab;
window.addNewDropdownOption = addNewDropdownOption;
window.deleteDropdownOption = deleteDropdownOption;
