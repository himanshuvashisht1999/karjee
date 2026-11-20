// Global Cache for Dropdown Options and Name Lookups
/**
 * Generates an SVG Data URI avatar based on first letter of first name and last name.
 */
function generateSelfAvatarJS(firstName, lastName, bgColor, textColor, size) {
    bgColor   = bgColor   || '#1b7a48';
    textColor = textColor || '#ffffff';
    size      = size      || 128;

    var fn = (firstName || '').trim();
    var ln = (lastName || '').trim();

    if (!ln && fn.indexOf(' ') !== -1) {
        var parts = fn.split(/\s+/).filter(Boolean);
        fn = parts[0] || '';
        ln = parts.length > 1 ? parts[parts.length - 1] : '';
    }

    var firstInitial = fn ? fn.charAt(0).toUpperCase() : '';
    var lastInitial  = ln ? ln.charAt(0).toUpperCase() : '';
    var initials     = (firstInitial + lastInitial) || 'L';

    var fontSize = Math.round(size * 0.42);
    var rx       = Math.round(size / 2);

    var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' + size + '" height="' + size + '" viewBox="0 0 ' + size + ' ' + size + '">' +
              '<rect width="100%" height="100%" fill="' + bgColor + '" rx="' + rx + '"/>' +
              '<text x="50%" y="54%" dominant-baseline="middle" text-anchor="middle" fill="' + textColor + '" font-family="system-ui, -apple-system, sans-serif" font-size="' + fontSize + '" font-weight="700">' + initials + '</text>' +
              '</svg>';

    return 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg);
}

var deptOptionsHtml = "";
var desigOptionsHtml = "";
var locOptionsHtml = "";
var deptMap = {};
var desigMap = {};
var locMap = {};

// Global Data Store for Sorting
var globalLabourData = [];
var currentFilteredData = [];
var currentTab = 'active'; // Default active labours tab card

// Static Grafting Options
const graftingOptionsHtml = `
    <option value="">Select</option>
    <option value="yes">YES</option>
    <option value="no">NO</option>
`;

function loadLabours() {
    var companyid = $("body").data("companyid")
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");

    // Show loading state
    $("#labourMastertbl tbody").html("<tr><td colspan='8' class='text-center'>Loading Labour Data...</td></tr>");

    $.post(routurl + "modal/hrm_laboursalarymodal.php?action=LoadLabourlist", {
        companyid: companyid,
        subcompanyid: subcompanyid,
        projectid: projectid,
        shortby: "all",
        shortbyvalue: "all"
    }, function (res) {
        let data = (typeof res === 'object') ? res : JSON.parse(res);

        // 1. Build Global Cache (HTML Strings & Name Maps)
        deptOptionsHtml = buildBaseOptions(data.departments, "DepartmentID", "DepartmentName", deptMap);
        desigOptionsHtml = buildBaseOptions(data.designations, "DesignationID", "DesignationName", desigMap);
        locOptionsHtml = buildBaseOptions(data.locations, "LocationId", "LocationName", locMap, "Select Location");

        // 2. Store Data Globally
        globalLabourData = data.labours;

        // 3. Update summary card numbers
        updateSummaryCardCounts();
        // Also load incomplete details count asynchronously
        loadIncompleteCount();

        // 4. Render Table by applying filters for the default tab
        applyFilters();

        // 5. Populate Filters Dropdowns
        syncFilterDropdowns('init');
    });
}

function isFemale(gender) {
    var g = (gender || '').toString().trim().toLowerCase();
    return (g === 'female' || g === 'f' || g === 'woman' || g === 'women' || g === 'w');
}

function updateSummaryCardCounts() {
    var searchInput = $("#searchmasterPaytbl");
    var searchTxt = searchInput.length && searchInput.val() ? searchInput.val().toLowerCase().trim() : "";
    var locFilter = $("#filter-loc").val();
    var wageFilter = $("#filter-wage").val();
    var genderFilter = $("#filter-gender").val();

    // 1. Filter main global pool by active dropdown / search filters
    let filterMatchingPool = globalLabourData.filter(labour => {
        let matchesSearch = !searchTxt ||
            (labour.FullName && labour.FullName.toLowerCase().includes(searchTxt)) ||
            (labour.EmployeeID && labour.EmployeeID.toLowerCase().includes(searchTxt)) ||
            (labour.MobileNo && labour.MobileNo.includes(searchTxt));
        
        let matchesLoc = !locFilter || locFilter === 'all' || String(labour.locationid) === String(locFilter);

        let matchesWage = true;
        if (wageFilter && wageFilter !== 'all') {
            let labWage = parseFloat(labour.PerDayPayment || 0);
            matchesWage = (labWage === parseFloat(wageFilter));
        }

        let matchesGender = true;
        if (genderFilter && genderFilter !== 'all') {
            if (genderFilter === 'Female') {
                matchesGender = isFemale(labour.Gender);
            } else if (genderFilter === 'Male') {
                matchesGender = !isFemale(labour.Gender) && (labour.Gender || '').toLowerCase() !== 'other';
            } else {
                matchesGender = (labour.Gender || '').toLowerCase() === genderFilter.toLowerCase();
            }
        }

        return matchesSearch && matchesLoc && matchesWage && matchesGender;
    });

    // 2. Compute All Labours card counts
    let allTotal = filterMatchingPool.length;
    let allWoman = filterMatchingPool.filter(l => isFemale(l.Gender)).length;
    let allMan   = allTotal - allWoman;

    // 3. Compute Active Labours card counts
    let activeLabours = filterMatchingPool.filter(l => l.Status === 'Active');
    let activeTotal   = activeLabours.length;
    let activeWoman   = activeLabours.filter(l => isFemale(l.Gender)).length;
    let activeMan     = activeTotal - activeWoman;

    // 4. Compute In-Active Labours card counts
    let inactiveLabours = filterMatchingPool.filter(l => l.Status !== 'Active');
    let inactiveTotal   = inactiveLabours.length;
    let inactiveWoman   = inactiveLabours.filter(l => isFemale(l.Gender)).length;
    let inactiveMan     = inactiveTotal - inactiveWoman;

    // Update DOM counters for 3 main cards
    $("#stat-all-labours").text(allTotal);
    $("#stat-all-man").text(allMan);
    $("#stat-all-woman").text(allWoman);

    $("#stat-active-labours").text(activeTotal);
    $("#stat-active-man").text(activeMan);
    $("#stat-active-woman").text(activeWoman);

    $("#stat-inactive-labours").text(inactiveTotal);
    $("#stat-inactive-man").text(inactiveMan);
    $("#stat-inactive-woman").text(inactiveWoman);

    // 5. Compute Incomplete Details card count with matching filters
    if (Array.isArray(incompleteData)) {
        let incompleteMatching = incompleteData.filter(item => {
            let matchesSearch = !searchTxt ||
                (item.FullName && item.FullName.toLowerCase().includes(searchTxt)) ||
                (item.EmployeeID && item.EmployeeID.toLowerCase().includes(searchTxt)) ||
                (item.MobileNo && item.MobileNo.includes(searchTxt));

            let matchesLoc = !locFilter || locFilter === 'all' || String(item.locationid) === String(locFilter);

            let matchesGender = true;
            if (genderFilter && genderFilter !== 'all') {
                if (genderFilter === 'Female') {
                    matchesGender = isFemale(item.Gender);
                } else if (genderFilter === 'Male') {
                    matchesGender = !isFemale(item.Gender) && (item.Gender || '').toLowerCase() !== 'other';
                } else {
                    matchesGender = (item.Gender || '').toLowerCase() === genderFilter.toLowerCase();
                }
            }
            return matchesSearch && matchesLoc && matchesGender;
        });
        $("#stat-incomplete").text(incompleteMatching.length);
    }
}

function handleFilterChange(source) {
    syncFilterDropdowns(source);
    applyFilters();
}

function syncFilterDropdowns(sourceChanged) {
    let locVal    = $("#filter-loc").val() || 'all';
    let wageVal   = $("#filter-wage").val() || 'all';
    let genderVal = $("#filter-gender").val() || 'all';
    let searchTxt = ($("#searchmasterPaytbl").val() || '').toLowerCase().trim();

    // 1. Highlight active filter controls with distinct background color
    if (locVal !== 'all') {
        $("#filter-loc").addClass("filter-active-highlight");
    } else {
        $("#filter-loc").removeClass("filter-active-highlight");
    }

    if (wageVal !== 'all') {
        $("#filter-wage").addClass("filter-active-highlight");
    } else {
        $("#filter-wage").removeClass("filter-active-highlight");
    }

    if (genderVal !== 'all') {
        $("#filter-gender").addClass("filter-active-highlight");
    } else {
        $("#filter-gender").removeClass("filter-active-highlight");
    }

    if (searchTxt !== '') {
        $("#search-box-wrap").addClass("filter-active-highlight");
    } else {
        $("#search-box-wrap").removeClass("filter-active-highlight");
    }

    // Base pool for current tab
    let basePool = globalLabourData.filter(labour => {
        if (currentTab === 'active' && labour.Status !== 'Active') return false;
        if (currentTab === 'inactive' && labour.Status === 'Active') return false;
        if (searchTxt) {
            let mSearch = (labour.FullName && labour.FullName.toLowerCase().includes(searchTxt)) ||
                          (labour.EmployeeID && labour.EmployeeID.toLowerCase().includes(searchTxt)) ||
                          (labour.MobileNo && labour.MobileNo.includes(searchTxt));
            if (!mSearch) return false;
        }
        return true;
    });

    // Helper filter function excluding specific dimension
    function getFilteredPool(excludeField) {
        return basePool.filter(l => {
            if (excludeField !== 'loc' && locVal !== 'all' && l.locationid != locVal) return false;
            if (excludeField !== 'wage' && wageVal !== 'all' && parseFloat(l.PerDayPayment || 0) != parseFloat(wageVal)) return false;
            if (excludeField !== 'gender' && genderVal !== 'all') {
                if (genderVal === 'Female' && !isFemale(l.Gender)) return false;
                if (genderVal === 'Male' && (isFemale(l.Gender) || (l.Gender || '').toLowerCase() === 'other')) return false;
                if (genderVal === 'Other' && (l.Gender || '').toLowerCase() !== 'other') return false;
            }
            return true;
        });
    }

    // 2. Update Location dropdown options if not the changed trigger
    if (sourceChanged !== 'loc') {
        let poolForLoc = getFilteredPool('loc');
        let availLocs = new Set(poolForLoc.map(l => String(l.locationid)).filter(Boolean));
        let locOpts = '<option value="all">All Locations</option>';
        Object.keys(locMap).forEach(locId => {
            if (availLocs.has(String(locId)) || sourceChanged === 'init') {
                let sel = (String(locId) === String(locVal)) ? 'selected' : '';
                locOpts += `<option value="${locId}" ${sel}>${locMap[locId]}</option>`;
            }
        });
        $("#filter-loc").html(locOpts);
    }

    // 3. Update Wage dropdown options if not the changed trigger
    if (sourceChanged !== 'wage') {
        let poolForWage = getFilteredPool('wage');
        let availWages = new Set(poolForWage.map(l => parseFloat(l.PerDayPayment || 0)).filter(w => !isNaN(w) && w > 0));
        let sortedWages = Array.from(availWages).sort((a, b) => a - b);
        let wageOpts = '<option value="all">All Wages</option>';
        sortedWages.forEach(w => {
            let sel = (wageVal !== 'all' && parseFloat(wageVal) == w) ? 'selected' : '';
            wageOpts += `<option value="${w}" ${sel}>₹ ${w.toFixed(2)}</option>`;
        });
        $("#filter-wage").html(wageOpts);
    }

    // 4. Update Gender dropdown options if not the changed trigger
    if (sourceChanged !== 'gender') {
        let poolForGender = getFilteredPool('gender');
        let hasMale   = poolForGender.some(l => !isFemale(l.Gender) && (l.Gender || '').toLowerCase() !== 'other');
        let hasFemale = poolForGender.some(l => isFemale(l.Gender));
        let hasOther  = poolForGender.some(l => (l.Gender || '').toLowerCase() === 'other');

        let genderOpts = '<option value="all">All Genders</option>';
        if (hasMale || sourceChanged === 'init') {
            genderOpts += `<option value="Male" ${genderVal === 'Male' ? 'selected' : ''}>Male</option>`;
        }
        if (hasFemale || sourceChanged === 'init') {
            genderOpts += `<option value="Female" ${genderVal === 'Female' ? 'selected' : ''}>Female</option>`;
        }
        if (hasOther) {
            genderOpts += `<option value="Other" ${genderVal === 'Other' ? 'selected' : ''}>Other</option>`;
        }
        $("#filter-gender").html(genderOpts);
    }
}

function clearAllFilters() {
    $("#filter-loc").val("all").removeClass("filter-active-highlight");
    $("#filter-wage").val("all").removeClass("filter-active-highlight");
    $("#filter-gender").val("all").removeClass("filter-active-highlight");
    $("#searchmasterPaytbl").val("");
    $("#search-box-wrap").removeClass("filter-active-highlight");

    syncFilterDropdowns('init');
    applyFilters();
}

function applyFilters() {
    var searchInput = $("#searchmasterPaytbl");
    var searchTxt = searchInput.length && searchInput.val() ? searchInput.val().toLowerCase().trim() : "";
    var locFilter = $("#filter-loc").val();
    var wageFilter = $("#filter-wage").val();
    var genderFilter = $("#filter-gender").val();

    var filteredData = globalLabourData.filter(labour => {
        // Tab specific filtering
        if (currentTab === 'active' && labour.Status !== 'Active') {
            return false;
        }
        if (currentTab === 'inactive' && labour.Status === 'Active') {
            return false;
        }

        let matchesSearch = labour.FullName.toLowerCase().includes(searchTxt) ||
            labour.EmployeeID.toLowerCase().includes(searchTxt) ||
            (labour.MobileNo && labour.MobileNo.includes(searchTxt));
        
        let matchesLoc = !locFilter || locFilter === 'all' || labour.locationid == locFilter;

        let matchesWage = true;
        if (wageFilter && wageFilter !== 'all') {
            let labWage = parseFloat(labour.PerDayPayment || 0);
            matchesWage = (labWage == parseFloat(wageFilter));
        }

        let matchesGender = true;
        if (genderFilter && genderFilter !== 'all') {
            if (genderFilter === 'Female') {
                matchesGender = isFemale(labour.Gender);
            } else if (genderFilter === 'Male') {
                matchesGender = !isFemale(labour.Gender) && (labour.Gender || '').toLowerCase() !== 'other';
            } else {
                matchesGender = (labour.Gender || '').toLowerCase() === genderFilter.toLowerCase();
            }
        }

        return matchesSearch && matchesLoc && matchesWage && matchesGender;
    });

    currentFilteredData = filteredData;
    renderTable(filteredData);
    updateSummaryCardCounts();
}

function renderTable(labourList) {
    let tbody = $("#labourMastertbl tbody");
    let sno = 1;
    let rowsHtml = "";
    let key = $("body").data("key");
    let mdid = $("body").data("mdid");

    // Dynamic header change
    let headerCell = $("#labourMastertbl thead tr th").last();
    if (currentTab === 'all') {
        headerCell.text("Status").addClass("text-center").removeClass("text-end pe-4 noexport");
    } else {
        headerCell.text("Action").addClass("text-end pe-4 noexport").removeClass("text-center");
    }

    labourList.forEach(labour => {
        let desigName = desigMap[labour.DesignationID] || '';
        let locName = locMap[labour.locationid] || '';
        let graftingVal = labour.graftingskill || '';
        let graftingText = graftingVal === 'yes' ? 'YES' : (graftingVal === 'no' ? 'NO' : '');
        
        let defaultAvatar = generateSelfAvatarJS(labour.FirstName || labour.FullName, labour.LastName, '#f0f7ff', '#0d6efd', 128);
        let photoPath = labour.profile_photo ? labour.profile_photo : defaultAvatar;

        let actionColHtml = "";
        if (currentTab === 'active') {
            actionColHtml = `
                <div class="d-flex gap-2 justify-content-end">
                    <a href="../labourdash/index.php?k=${key}&mdid=${mdid}&empid=${labour.EmployeeID}" class="btn btn-sm btn-white border shadow-sm" title="View Profile">
                        <i class="ti ti-eye text-primary"></i>
                    </a>
                    <button class="btn btn-sm btn-white border shadow-sm" title="Download ID Card" onclick="downloadIDManual('${labour.EmployeeID}')">
                        <i class="ti ti-id text-success"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger removelabourbtn" title="Remove" onclick="RemoveLabourFromMasterData('${labour.EmployeeID}')">
                        <i class="ti ti-trash"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-primary editlabourdata editpaymentbtn" title="Edit" onclick="editLabourDetails('${labour.EmployeeID}')">
                        <i class="ti ti-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-success updatepaymentbtn d-none" title="Save" onclick="UpdatePayPerDayPaymentWithOtherDetails(this)" data-employeeid="${labour.EmployeeID}">
                        <i class="ti ti-device-floppy"></i>
                    </button>
                </div>
            `;
        } else if (currentTab === 'inactive') {
            actionColHtml = `
                <div class="d-flex gap-2 justify-content-end">
                    <button class="btn btn-sm btn-success d-inline-flex align-items-center gap-1 rounded-pill px-3 py-1 shadow-sm" onclick="activateLabour('${labour.EmployeeID}')">
                        <i class="ti ti-rotate-dot me-1"></i> Active to Working
                    </button>
                </div>
            `;
        } else {
            // 'all' tab shows Status column
            let statusVal = labour.Status || 'Inactive';
            let badgeClass = "bg-secondary-subtle text-secondary";
            if (statusVal === 'Active') {
                badgeClass = "bg-success-subtle text-success";
            } else if (statusVal === 'Removed') {
                badgeClass = "bg-danger-subtle text-danger";
            }
            actionColHtml = `
                <div class="text-center">
                    <span class="badge ${badgeClass} rounded-pill px-3 py-1.5 fw-semibold fs-11">${statusVal}</span>
                </div>
            `;
        }

        let isFem = isFemale(labour.Gender);
        let genderBadge = isFem ? '<span class="badge badge-gender-w ms-1">W</span>' : '<span class="badge badge-gender-m ms-1">M</span>';
        let mobileDisplay = labour.MobileNo ? `<span class="fw-medium text-dark"><i class="ti ti-phone text-muted me-1 fs-12"></i>${labour.MobileNo}</span>` : '<span class="text-muted fs-11">N/A</span>';
        let addressText = [labour.AddressLine1, labour.AddressLine2].filter(Boolean).join(', ') || labour.AddressLine1 || '';
        let addressDisplay = addressText ? `<span class="text-muted fs-11" title="${addressText}">${addressText}</span>` : '<span class="text-muted fs-11">N/A</span>';

        rowsHtml += `
        <tr data-employeeid="${labour.EmployeeID}">
            <td class="ps-4">${sno++}</td>
            <td>
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle overflow-hidden border border-2 border-white shadow-sm flex-shrink-0" style="width: 38px; height: 38px; background: #f8f9fa;">
                        <img src="${photoPath}" class="w-100 h-100 object-fit-cover" onerror="this.src='${defaultAvatar}'">
                    </div>
                    <div>
                        <div class="labournametd fw-bold text-dark mb-0">
                            <a href="../labourdash/index.php?k=${key}&mdid=${mdid}&empid=${labour.EmployeeID}" class="text-decoration-none text-dark hover-primary">${labour.FullName}</a> ${genderBadge}
                        </div>
                        <div class="fw-medium text-muted fs-11">
                            <span class="designation-view-span text-primary fw-semibold">${labour.FatherName ? 'Father: ' + labour.FatherName : 'No Father Name'}</span> • ID: ${labour.EmployeeID}
                        </div>
                        <div class="designation-edit-container d-none mt-1" style="max-width: 180px;"></div>
                        <div class="designationtd d-none" data-val="${labour.DesignationID}"></div>
                    </div>
                </div>
            </td>
            <td>
                ${mobileDisplay}
            </td>
            <td style="max-width: 220px;">
                <div class="text-truncate" style="max-width: 220px;">
                    ${addressDisplay}
                </div>
            </td>
            <td class="perdaypaymenttd" data-val="${labour.PerDayPayment}">
                <span class="view-mode">₹ ${labour.PerDayPayment}</span>
            </td>
            <td>
                <div class="locationtd fs-11 text-muted fw-medium mt-0.5" data-val="${labour.locationid}">
                    <span class="view-mode">${locName || 'No Location'}</span>
                </div>
                <div class="loc-edit-container d-none mt-1" style="max-width: 180px;"></div>
            </td>
            <td class="graftingskilltd text-center" data-val="${graftingVal}">
                <span class="view-mode badge ${graftingVal === 'yes' ? 'bg-success-subtle text-success' : 'bg-light text-muted'} rounded-pill px-3">${graftingText}</span>
            </td>
            <td class="${(currentTab === 'all') ? '' : 'pe-4 noexport'}">
                ${actionColHtml}
            </td>
        </tr>
        `;
    });
    tbody.html(rowsHtml);

    let totalFiltered = labourList.length;
    let womanFiltered = labourList.filter(l => isFemale(l.Gender)).length;
    let manFiltered   = totalFiltered - womanFiltered;

    $("#filter-total-count").text(totalFiltered);
    $("#filter-man-count").text(manFiltered);
    $("#filter-woman-count").text(womanFiltered);

    $("#current-showing-count").text(totalFiltered);
}

function viewLabourDetails(empId) {
    let companyid = $("body").data("companyid");
    let subcompanyid = $("body").data("subcompanyid");
    let projectid = $("body").data("projectid");

    $("#LabourDetailsModal").modal('show');
    $("#labourDetailsContent").html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Fetching labour details...</p></div>');

    $.post(routurl + "modal/hrm_laboursalarymodal.php?action=GetLabourFullDetails", {
        employeeID: empId,
        companyid: companyid,
        subcompanyid: subcompanyid,
        projectid: projectid
    }, function (res) {
        let data = (typeof res === 'object') ? res : JSON.parse(res);
        if (data.status === "Success") {
            let labour = data.data;
            let photo = labour.profile_photo ? labour.profile_photo : generateSelfAvatarJS(labour.FirstName || labour.FullName, labour.LastName, '#f0f7ff', '#0d6efd', 256);

            let html = `
                <div class="row g-4">
                    <div class="col-md-4 text-center border-end">
                        <div class="rounded-circle overflow-hidden border border-4 border-white shadow-sm mx-auto mb-3" style="width: 150px; height: 150px;">
                            <img src="${photo}" class="w-100 h-100 object-fit-cover">
                        </div>
                        <h4 class="fw-bold text-dark mb-1">${labour.FullName}</h4>
                        <span class="badge bg-primary-subtle text-primary rounded-pill px-3">${labour.EmployeeID}</span>
                        <hr class="my-4">
                        <div class="text-start">
                            <p class="small text-muted mb-1 text-uppercase fw-bold">Mobile Number</p>
                            <p class="fw-bold text-dark mb-3"><i class="ti ti-phone me-1"></i> ${labour.MobileNo}</p>
                            <p class="small text-muted mb-1 text-uppercase fw-bold">Daily Wage</p>
                            <p class="fw-bold text-success mb-0">₹ ${labour.PerDayPayment}</p>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="bg-light rounded p-2 border">
                                    <label class="small text-muted fw-bold">Father Name</label>
                                    <p class="mb-0 text-dark">${labour.FatherName || 'N/A'}</p>
                                </div>
                            </div>
                            <div class="col-md-12"><hr></div>
                            <div class="col-md-6">
                                <label class="small text-muted fw-bold">Current Address</label>
                                <p class="mb-0 small text-dark">${labour.AddressLine1 || 'N/A'}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="small text-muted fw-bold">Permanent Address</label>
                                <p class="mb-0 small text-dark">${labour.AddressLine2 || 'N/A'}</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            $("#labourDetailsContent").html(html);
            $("#downloadIDManualBtn").attr("onclick", `downloadIDManual('${empId}')`);
        }
    });
}

function downloadIDManual(empId) {
    let companyid = $("body").data("companyid");
    let subcompanyid = $("body").data("subcompanyid");
    let projectid = $("body").data("projectid");

    $.ajax({
        type: 'POST',
        url: routurl + "modal/hrm_laboursalarymodal.php?action=GenerateIDCard",
        data: {
            employeeID: empId,
            companyid: companyid,
            subcompanyid: subcompanyid,
            projectid: projectid
        },
        beforeSend: function() {
            Swal.fire({
                title: 'Generating ID Card',
                text: 'Please wait while we prepare the document...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
        },
        success: function (res) {
            let data = (typeof res === 'object') ? res : JSON.parse(res);
            if (data.status === "Success") {
                let targetUrl = data.path;
                if (targetUrl.indexOf('?') === -1) {
                    targetUrl += "?t=" + new Date().getTime();
                } else {
                    targetUrl += "&t_client=" + new Date().getTime();
                }
                window.open(targetUrl, '_blank');
                Swal.close();
            } else {
                Swal.fire('Error', 'Could not generate ID card: ' + data.message, 'error');
            }
        },
        error: function() {
            Swal.fire('Error', 'Server connection failed.', 'error');
        }
    });
}

// Build clean options string AND populate Map
function buildBaseOptions(list, idField, textField, mapObj, defaultLabel = "Select") {
    let html = `<option value="">${defaultLabel}</option>`;
    if (list && list.length > 0) {
        list.forEach(item => {
            let id = item[idField];
            let text = item[textField];
            html += `<option value="${id}">${text}</option>`;
            if (mapObj) mapObj[id] = text; // Populate Map
        });
    }
    return html;
}

// Efficiently set 'selected' attribute in HTML string
function setSelection(baseHtml, selectedId) {
    if (!selectedId || selectedId === "null" || selectedId === "undefined" || selectedId === "") return baseHtml;
    return baseHtml.replace(`value="${selectedId}"`, `value="${selectedId}" selected`);
}

// --- Event Handlers ---

function UpdatePayPerDayPaymentWithOtherDetails(btn) {
    var $row = $(btn).closest("tr");
    var employeeId = $(btn).data("employeeid");

    // Get values from the INPUTS/SELECTS (Edit Mode)
    var payPerDay = $row.find(".labourPerDayPayment").val();
    var designationId = $row.find(".labourDesignation").val();
    var graftingSkill = $row.find(".labourGraftingSkill").val();
    var locationId = $row.find(".labourLocation").val();

    // Validations
    if (payPerDay === "" || isNaN(payPerDay)) {
        alert("Invalid Pay Per Day Amount");
        return;
    }

    // AJAX
    $.post(routurl + "modal/hrm_laboursalarymodal.php?action=UpdatePayPerDayPaymentWithOtherDetails", {
        companyid: $("body").data("companyid"),
        subcompanyid: $("body").data("subcompanyid"),
        projectid: $("body").data("projectid"),
        loginname: loginname,
        EmployeeId: employeeId,
        PayPerDayAmount: payPerDay,
        designationid: designationId,
        graftingskill: graftingSkill,
        locationid: locationId
    }, function (res) {
        var data = (typeof res === 'object') ? res : JSON.parse(res);
        if (data.status == "success" || data.status == "warning") {
            alert(data.message);

            // 1. Update data-val attributes
            $row.find(".perdaypaymenttd").data("val", payPerDay);
            $row.find(".designationtd").data("val", designationId);
            $row.find(".graftingskilltd").data("val", graftingSkill);
            $row.find(".locationtd").data("val", locationId);

            // Update Global Data Store too!
            let record = globalLabourData.find(d => d.EmployeeID == employeeId);
            if (record) {
                record.PerDayPayment = payPerDay;
                record.DesignationID = designationId;
                record.graftingskill = graftingSkill;
                record.locationid = locationId;
            }

            // 2. Resolve Names
            let desigName = desigMap[designationId] || '';
            let locName = locMap[locationId] || '';
            let graftName = graftingSkill === 'yes' ? 'YES' : (graftingSkill === 'no' ? 'NO' : '');

            // 3. Set HTML to Spans
            $row.find(".perdaypaymenttd").html(`<span class="view-mode">₹ ${payPerDay}</span>`);
            
            // Revert location view
            $row.find(".locationtd .view-mode").text(locName || 'No Location').removeClass("d-none");
            $row.find(".loc-edit-container").addClass("d-none").empty();
            
            // Revert designation view in the combined Labour cell
            $row.find(".designation-view-span").text(record.FatherName ? 'Father: ' + record.FatherName : 'No Father Name').removeClass("d-none");
            $row.find(".designation-edit-container").addClass("d-none").empty();
            
            $row.find(".graftingskilltd").html(`<span class="view-mode badge ${graftingSkill === 'yes' ? 'bg-success-subtle text-success' : 'bg-light text-muted'} rounded-pill px-3">${graftName}</span>`);

            // 4. Reset style
            $row.removeClass("row-editing");
            $row.find(".editlabourdata").removeClass("d-none");
            $row.find(".updatepaymentbtn").addClass("d-none");

        } else {
            alert(data.message);
        }
    });
}

function RemoveLabourFromMasterData(labourId) {
    if (!confirm("Are you sure you want to remove this labour?")) return;

    $.post(routurl + "modal/hrm_laboursalarymodal.php?action=RemoveLabourFromMasterData", {
        companyid: $("body").data("companyid"),
        subcompanyid: $("body").data("subcompanyid"),
        projectid: $("body").data("projectid"),
        loginname: loginname,
        Labourid: labourId,
        Remark: "Removed by user from interface"
    }, function (res) {
        var data = (typeof res === 'object') ? res : JSON.parse(res);
        if (data.status == "Success") {
            // Remove row from DOM directly instead of reloading all
            $(`tr[data-employeeid='${labourId}']`).remove();
            // Also remove from globalLabourData
            globalLabourData = globalLabourData.filter(labour => labour.EmployeeID != labourId);
            alert(data.message);
        } else {
            alert(data.message);
        }
    });
}

function isNumberKey(evt) {
    var charCode = (evt.which) ? evt.which : evt.keyCode
    if (charCode > 31 && (charCode < 48 || charCode > 57))
        return false;
    return true;
}

var currentSortCol = -1;
var sortDir = 'asc';

function sortTable(n, header) {
    if (globalLabourData.length === 0) return;

    // Toggle Direction
    if (currentSortCol === n) {
        sortDir = sortDir === 'asc' ? 'desc' : 'asc';
    } else {
        currentSortCol = n;
        sortDir = 'asc';
    }

    // Reset Header Classes
    var table = document.getElementById("labourMastertbl");
    var headers = table.querySelectorAll("th");
    headers.forEach(h => h.classList.remove("asc", "desc"));
    header.classList.add(sortDir);

    // Map Column Index to Data Key
    let key = "";
    let isNumeric = false;

    switch (n) {
        case 1: key = "EmployeeID"; isNumeric = true; break;
        case 2: key = "FullName"; break;
        case 3: key = "MobileNo"; break;
        case 4: key = "AddressLine1"; break;
        case 5: key = "PerDayPayment"; isNumeric = true; break;
        case 6: key = "locationid"; break;
        case 7: key = "graftingskill"; break;
        default: return; // No sort for other cols
    }

    // Sort Global Data
    globalLabourData.sort((a, b) => {
        let valA = a[key] || "";
        let valB = b[key] || "";

        // Special handling for mapped fields (sort by Name not ID)
        if (n === 6) { valA = locMap[valA] || ""; valB = locMap[valB] || ""; }

        if (isNumeric) {
            valA = parseFloat(valA) || 0;
            valB = parseFloat(valB) || 0;
            return sortDir === 'asc' ? valA - valB : valB - valA;
        } else {
            valA = valA.toString().toLowerCase();
            valB = valB.toString().toLowerCase();
            if (valA < valB) return sortDir === 'asc' ? -1 : 1;
            if (valA > valB) return sortDir === 'asc' ? 1 : -1;
            return 0;
        }
    });

    // Re-render
    renderTable(globalLabourData);
}

// --- Labour Registration Handler ---

$(document).on("submit", "#LabourRegistrationForm", function (e) {
    e.preventDefault();

    let loc = $("#reg_workingLocation").val();
    if (!loc) {
        Swal.fire({
            icon: 'warning',
            title: 'Location Selection Missing',
            text: 'Please select a Working Location first to get a suggested Labour ID.',
            confirmButtonColor: '#0d6efd'
        });
        $("#reg_workingLocation").addClass('is-invalid');
        return;
    }

    // 1. Suggested ID Validation
    let seriesId = $("#reg_seriesid").val().trim();
    let prefix = $("#reg_seriesid").data('fixed-prefix') || "";
    if (prefix && !seriesId.startsWith(prefix)) {
        Swal.fire({
            icon: 'warning',
            title: 'Invalid ID',
            text: `Suggested ID must start with '${prefix}'.`,
            confirmButtonColor: '#0d6efd'
        });
        $("#reg_seriesid").addClass('is-invalid');
        return;
    }
    if (seriesId.length > 6) {
        Swal.fire({
            icon: 'warning',
            title: 'Invalid ID',
            text: 'Suggested ID cannot exceed 6 digits.',
            confirmButtonColor: '#0d6efd'
        });
        $("#reg_seriesid").addClass('is-invalid');
        return;
    }

    // 2. First Name Validation
    let firstName = $("#LabourRegistrationForm input[name='firstName']").val().trim();
    if (firstName.length < 2) {
        Swal.fire({
            icon: 'warning',
            title: 'Invalid First Name',
            text: 'Please enter a valid First Name (minimum 2 characters).',
            confirmButtonColor: '#0d6efd'
        });
        $("#LabourRegistrationForm input[name='firstName']").addClass('is-invalid');
        return;
    }

    // 3. Mobile No Validation
    let mobileNo = $("#LabourRegistrationForm input[name='mobileNo']").val().trim();
    if (mobileNo.length !== 10) {
        Swal.fire({
            icon: 'warning',
            title: 'Invalid Mobile Number',
            text: 'Please enter a valid 10-digit mobile number.',
            confirmButtonColor: '#0d6efd'
        });
        $("#LabourRegistrationForm input[name='mobileNo']").addClass('is-invalid');
        return;
    }

    let formData = new FormData(this);
    formData.append("companyid", $("body").data("companyid"));
    formData.append("subcompanyid", $("body").data("subcompanyid"));
    formData.append("projectid", $("body").data("projectid"));
    formData.append("addedby", loginname);

    // Show loading state
    let submitBtn = $(this).find('button[type="submit"]');
    let originalBtnHtml = submitBtn.html();
    submitBtn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...');

    $.ajax({
        url: routurl + "modal/hrm_laboursalarymodal.php?action=LabourRegistration",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function (res) {
            submitBtn.prop("disabled", false).html(originalBtnHtml);
            let data = (typeof res === 'object') ? res : JSON.parse(res);
            if (data.status === "Success") {
                Swal.fire({
                    icon: 'success',
                    title: '<span class="text-success">Registration Successful!</span>',
                    html: `
                        <div class="p-3">
                            <p class="mb-3">Labour record has been created successfully.</p>
                            <div class="bg-light p-3 rounded-4 mb-3 border border-dashed border-success">
                                <span class="d-block small text-muted text-uppercase fw-bold mb-1">Generated ID</span>
                                <h3 class="fw-bold text-dark mb-0">${data.empid}</h3>
                            </div>
                            ${data.id_card ? `
                            <a href="${data.id_card}" target="_blank" class="btn btn-primary rounded-pill px-4 w-100 mt-2">
                                <i class="ti ti-download me-2"></i> Download ID Card
                            </a>` : `
                            <div class="alert alert-warning py-2 small mb-0 rounded-3">
                                <i class="ti ti-alert-triangle me-1"></i> ID Card generation pending.
                            </div>
                            `}
                        </div>
                    `,
                    showConfirmButton: true,
                    confirmButtonText: 'Great, Continue',
                    confirmButtonColor: '#0d6efd',
                    customClass: {
                        popup: 'rounded-4 border-0 shadow-lg'
                    }
                });

                // Reset Form
                $("#LabourRegistrationForm")[0].reset();
                $("#bankDocPreviewContainer").addClass("d-none");
                $("#labourPhotoPreview").attr('src', generateSelfAvatarJS('Labour', '', '#f8f9fa', '#adb5bd', 256));
                
                // Clear extra reference rows
                $("#referenceContainer .reference-item").not(":first").remove();
                
                $("#LabourRegistrationModal").modal('hide');

                // Reload Table
                loadLabours();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Registration Failed',
                    text: data.message
                });
            }
        },
        error: function () {
            submitBtn.prop("disabled", false).html(originalBtnHtml);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Something went wrong while connecting to the server.'
            });
        }
    });
});

function editLabourDetails(empId) {
    let companyid = $("body").data("companyid");
    let subcompanyid = $("body").data("subcompanyid");
    let projectid = $("body").data("projectid");

    // Show SweetAlert waiting message
    Swal.fire({
        title: 'Fetching Details',
        text: 'Please wait while we retrieve the labour details...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    let startTime = Date.now();
    // Fetch full details
    $.post(routurl + "modal/hrm_laboursalarymodal.php?action=GetLabourFullDetails", {
        employeeID: empId,
        companyid: companyid,
        subcompanyid: subcompanyid,
        projectid: projectid
    }, function (res) {
        Swal.close();
        let data = (typeof res === 'object') ? res : JSON.parse(res);
            if (data.status === "Success") {
                // Open the registration modal programmatically
                $("#LabourRegistrationModal").modal('show');
                
                // Change modal title and button text for edit mode
                $("#LabourRegistrationModal h4").text("Edit Labour Details");
                $("#LabourRegistrationModal .modal-header p").text("Modify workforce credentials and details");
            $("#submitRegistration").html('<i class="ti ti-check me-2"></i> Update & Save');
            
            // Set hidden employee ID field
            $("#reg_employeeID").val(empId);
            
            let labour = data.data;
            let refs = data.references;
            
            // Pre-fill form fields
            $("#reg_seriesid").val(labour.seriesid);
            $("[name='firstName']").val(labour.FirstName);
            $("[name='middleName']").val(labour.MiddleName);
            $("[name='lastName']").val(labour.LastName);
            $("[name='gender']").val(labour.Gender);
            $("[name='maritalStatus']").val(labour.MaritalStatus);
            // Father, Mother, Spouse
            $("[name='fatherName']").val(labour.FatherName || '');
            $("[name='motherName']").val(labour.MotherName || '');
            $("[name='spouseName']").val(labour.SpouseName || '');
            // Show/hide spouse, father, mother fields based on prefilled marital+gender
            var _marital = labour.MaritalStatus;
            var _gender  = labour.Gender;
            if (_gender === 'Female' && _marital === 'Married') {
                $('#reg_fatherNameWrapper').hide();
                $('#reg_motherNameWrapper').hide();
                $('#reg_spouseNameWrapper').show();
                $('#reg_spouseNameLabel').text('Husband Name');
                $('#reg_spouseName').attr('placeholder', 'Enter husband name');
            } else if (_marital === 'Married') {
                $('#reg_fatherNameWrapper').show();
                $('#reg_motherNameWrapper').show();
                $('#reg_spouseNameWrapper').show();
                if (_gender === 'Male') {
                    $('#reg_spouseNameLabel').text('Wife Name');
                    $('#reg_spouseName').attr('placeholder', 'Enter wife name');
                } else {
                    $('#reg_spouseNameLabel').text('Spouse Name');
                    $('#reg_spouseName').attr('placeholder', 'Enter spouse name');
                }
            } else {
                $('#reg_fatherNameWrapper').show();
                $('#reg_motherNameWrapper').show();
                $('#reg_spouseNameWrapper').hide();
            }
            $("[name='mobileNo']").val(labour.MobileNo);
            $("[name='tahseel']").val(labour.tahseel || '');
            $("[name='city']").val(labour.city || '');
            $("[name='current_address']").val(labour.AddressLine1 || labour.address || '');
            $("[name='permanent_address']").val(labour.AddressLine2 || '');
            $('#reg_same_as_current').prop('checked', false);

            if (typeof window.loadRegistrationStates === 'function') {
                window.loadRegistrationStates(labour.stateid || '', function() {
                    if (labour.stateid && typeof window.loadRegistrationDistricts === 'function') {
                        window.loadRegistrationDistricts(labour.stateid, labour.districtid || '');
                    }
                });
            }

            $("[name='workingLocation']").val(labour.locationid);
            
            // Pre-fill Bank details
            $("#reg_bankName").val(data.bank ? data.bank.BankName : "");
            $("#reg_accountNumber").val(data.bank ? data.bank.AccountNumber : "");
            $("#reg_ifscCode").val(data.bank ? data.bank.IFSCCode : "");
            if (data.bank && data.bank.CancelChequePath) {
                $("#bankDocPreviewContainer").removeClass("d-none");
                $("#bankDocLink").attr("href", "../" + data.bank.CancelChequePath);
            } else {
                $("#bankDocPreviewContainer").addClass("d-none");
            }
            
            // Set grafting skill radio
            if (labour.graftingskill === 'yes') {
                $("#skill_yes").prop("checked", true);
            } else {
                $("#skill_no").prop("checked", true);
            }
            
            // Handle Profile Photo preview
            if (labour.profile_photo) {
                $("#labourPhotoPreview").attr("src", labour.profile_photo);
            } else {
                $("#labourPhotoPreview").attr("src", generateSelfAvatarJS(labour.FirstName || labour.FullName, labour.LastName, '#f0f7ff', '#0d6efd', 256));
            }
            
            // Pre-fill ID Proof fields
            var idProofType = labour.IdProofType || 'Aadhar Card';
            $("[name='idProofType']").val(idProofType);
            // Trigger show/hide of idProofNo field
            if (typeof handleIdProofTypeChange === 'function') {
                handleIdProofTypeChange($("[name='idProofType']")[0]);
            }
            // Pre-fill the proof number based on type
            var proofNo = '';
            if (idProofType === 'Aadhar Card') {
                proofNo = labour.AadharNumber || '';
            } else if (idProofType === 'PAN Card') {
                proofNo = labour.PanNumber || '';
            }
            $('#reg_idProofNo').val(proofNo);
            
            // Pre-fill Emergency Contact
            let primaryRef = refs.find(r => r.IsPrimaryContact === 'yes' || r.Relation === 'Emergency Contact');
            if (primaryRef) {
                $("[name='emergencyContact']").val(primaryRef.MobileNo);
            } else if (refs.length > 0) {
                $("[name='emergencyContact']").val(refs[0].MobileNo);
            }
            
            // Pre-fill Reference rows
            $("#referenceContainer").html(""); // clear references container
            if (refs.length > 0) {
                refs.forEach((ref, index) => {
                    let isMandatory = (index === 0);
                    let rowId = isMandatory ? 'ref_mandatory' : ('ref_' + Date.now() + '_' + index);
                    let relOptions = (typeof buildRelationOptions === 'function') ? buildRelationOptions(ref.Relation || '') : `<option value="">Select Relation</option><option value="${ref.Relation || ''}" selected>${ref.Relation || 'Select Relation'}</option>`;
                    let html = `
                        <div id="${rowId}" class="col-md-6 reference-item animate__animated animate__fadeIn">
                            <div class="p-3 bg-light rounded-4 border border-light-subtle position-relative">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h7 class="fs-12 fw-bold text-muted mb-0 text-uppercase">Reference Person #${index + 1}</h7>
                                    ${!isMandatory ? `
                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-circle p-1" onclick="removeReferenceRow('${rowId}')" style="width:24px; height:24px; line-height:1;">
                                        <i class="ti ti-x fs-10"></i>
                                    </button>` : ''}
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">Full Name ${isMandatory ? '<span class="text-danger">*</span>' : ''}</label>
                                        <input type="text" name="ref_name[]" class="form-control bg-white border-0 shadow-sm ref-input-name" ${isMandatory ? 'required' : ''} value="${ref.FirstName || ''}" placeholder="Person name">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">Relation ${isMandatory ? '<span class="text-danger">*</span>' : ''}</label>
                                        <select name="ref_relation[]" class="form-select bg-white border-0 shadow-sm ref-input-relation" ${isMandatory ? 'required' : ''}>
                                            ${relOptions}
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">Mobile No. ${isMandatory ? '<span class="text-danger">*</span>' : ''}</label>
                                        <input type="text" name="ref_mobile[]" class="form-control bg-white border-0 shadow-sm ref-input-mobile" ${isMandatory ? 'required' : ''} value="${ref.MobileNo || ''}" placeholder="9876543210" maxlength="10">
                                        <div class="ref-mobile-feedback small mt-1"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">Document Type</label>
                                        <select name="ref_doc_type[]" class="form-select bg-white border-0 shadow-sm">
                                            <option value="">Select Doc Type</option>
                                            <option value="Aadhar Card" ${ref.DocType === 'Aadhar Card' ? 'selected' : ''}>Aadhar Card</option>
                                            <option value="PAN Card" ${ref.DocType === 'PAN Card' ? 'selected' : ''}>PAN Card</option>
                                            <option value="Voter ID" ${ref.DocType === 'Voter ID' ? 'selected' : ''}>Voter ID</option>
                                            <option value="Other" ${ref.DocType === 'Other' ? 'selected' : ''}>Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label small fw-bold text-muted">Upload Document</label>
                                        <input type="file" name="ref_doc_file[]" class="form-control bg-white border-0 shadow-sm">
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    $("#referenceContainer").append(html);
                });
            } else {
                if (typeof addReferenceRow === 'function') {
                    addReferenceRow();
                }
            }
            
            // Trigger validations for pre-filled values
            setTimeout(function() {
                if (typeof validateBankName === 'function') validateBankName($("#reg_bankName"), $("#reg_bankNameFeedback"), false);
                if (typeof validateAccountNumber === 'function') validateAccountNumber($("#reg_accountNumber"), $("#reg_accountNumberFeedback"), false);
                if (typeof validateIFSCCode === 'function') validateIFSCCode($("#reg_ifscCode"), $("#reg_ifscCodeFeedback"), false);
                if (typeof validateMobileInput === 'function') {
                    validateMobileInput($("#reg_mobileNo"), $("#reg_mobileNoFeedback"), false);
                    validateMobileInput($("#reg_emergencyContact"), $("#reg_emergencyContactFeedback"), false);
                }
                $("#referenceContainer .reference-item").each(function() {
                    if (typeof validateReferenceRowMobile === 'function') {
                        validateReferenceRowMobile($(this));
                    }
                });
            }, 100);
        }
    });
}

function activateLabour(empId) {
    let companyid = $("body").data("companyid");
    let subcompanyid = $("body").data("subcompanyid");
    let projectid = $("body").data("projectid");

    Swal.fire({
        title: 'Activate Labour?',
        text: "Are you sure you want to set this labour status to Active?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Activate'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post(routurl + "modal/hrm_laboursalarymodal.php?action=ActivateLabourFromMasterData", {
                companyid: companyid,
                subcompanyid: subcompanyid,
                projectid: projectid,
                loginname: loginname,
                Labourid: empId,
                Remark: "Activated by user from interface"
            }, function (res) {
                var data = (typeof res === 'object') ? res : JSON.parse(res);
                if (data.status === "Success") {
                    Swal.fire({
                        icon: 'success',
                        title: 'Activated!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    loadLabours();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        }
    });
}

$(document).on("click", ".stat-card", function() {
    let cardId = $(this).attr("id");
    
    // Handle Incomplete card separately - open modal
    if (cardId === "card-incomplete") {
        $(".stat-card").removeClass("active-all active-active active-inactive active-locations active-incomplete");
        $(this).addClass("active-incomplete");
        loadIncompleteLabours();
        $("#IncompleteDetailsModal").modal("show");
        return;
    }

    // Remove active styles from all cards
    $(".stat-card").removeClass("active-all active-active active-inactive active-incomplete");
    
    // Set currentTab and add active style to clicked card
    if (cardId === "card-all-labours") {
        currentTab = 'all';
        $(this).addClass("active-all");
    } else if (cardId === "card-active-labours") {
        currentTab = 'active';
        $(this).addClass("active-active");
    } else if (cardId === "card-inactive-labours") {
        currentTab = 'inactive';
        $(this).addClass("active-inactive");
    }
    
    syncFilterDropdowns('tab');
    applyFilters();
});

// ---- Incomplete Labours ----
var incompleteData = [];
var filteredIncompleteData = [];

function getIncompleteBaseFilteredList() {
    var searchInput = $("#searchmasterPaytbl");
    var globalSearchTxt = searchInput.length && searchInput.val() ? searchInput.val().toLowerCase().trim() : "";
    var locFilter = $("#filter-loc").val();
    var wageFilter = $("#filter-wage").val();
    var genderFilter = $("#filter-gender").val();

    return (incompleteData || []).filter(function(item) {
        let matchesSearch = !globalSearchTxt ||
            (item.FullName && item.FullName.toLowerCase().includes(globalSearchTxt)) ||
            (item.EmployeeID && item.EmployeeID.toLowerCase().includes(globalSearchTxt)) ||
            (item.MobileNo && item.MobileNo.includes(globalSearchTxt));

        let matchesLoc = !locFilter || locFilter === 'all' || String(item.locationid) === String(locFilter);

        let matchesGender = true;
        if (genderFilter && genderFilter !== 'all') {
            if (genderFilter === 'Female') {
                matchesGender = isFemale(item.Gender);
            } else if (genderFilter === 'Male') {
                matchesGender = !isFemale(item.Gender) && (item.Gender || '').toLowerCase() !== 'other';
            } else {
                matchesGender = (item.Gender || '').toLowerCase() === genderFilter.toLowerCase();
            }
        }
        return matchesSearch && matchesLoc && matchesGender;
    });
}

function loadIncompleteCount() {
    var companyid    = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid    = $("body").data("projectid");

    $.post(routurl + "modal/hrm_laboursalarymodal.php?action=GetIncompleteLaboursList", {
        companyid, subcompanyid, projectid
    }, function(res) {
        let d = (typeof res === 'object') ? res : JSON.parse(res);
        if (d.status === "Success") {
            incompleteData = d.list || [];
            updateSummaryCardCounts();
        } else {
            $("#stat-incomplete").text("?");
        }
    });
}

function loadIncompleteLabours() {
    var companyid    = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid    = $("body").data("projectid");
    var $tbody       = $("#incompleteDetailsTblBody");

    // If data already cached in memory, render immediately with active filters
    if (incompleteData && incompleteData.length > 0) {
        updateSummaryCardCounts();
        buildIncompleteFieldCheckboxes();
        filterIncompleteTable();
    } else {
        $tbody.html('<tr><td colspan="5" class="text-center py-5"><div class="spinner-border text-danger spinner-border-sm me-2"></div><span class="text-muted">Loading...</span></td></tr>');
    }

    $.post(routurl + "modal/hrm_laboursalarymodal.php?action=GetIncompleteLaboursList", {
        companyid, subcompanyid, projectid
    }, function(res) {
        let d = (typeof res === 'object') ? res : JSON.parse(res);
        if (d.status !== "Success") {
            if (!incompleteData || incompleteData.length === 0) {
                $tbody.html('<tr><td colspan="5" class="text-center text-danger py-4">Failed to load data.</td></tr>');
            }
            return;
        }
        incompleteData = d.list || [];
        updateSummaryCardCounts();
        buildIncompleteFieldCheckboxes();
        filterIncompleteTable();
    });
}

function buildIncompleteFieldCheckboxes() {
    var distinctFields = [];
    var baseList = getIncompleteBaseFilteredList();
    baseList.forEach(function(item) {
        if (item.missing && item.missing.length > 0) {
            item.missing.forEach(function(f) {
                if (distinctFields.indexOf(f) === -1) {
                    distinctFields.push(f);
                }
            });
        }
    });
    distinctFields.sort();

    var html = '';
    if (distinctFields.length === 0) {
        html = '<span class="text-success small fw-semibold">No missing fields found in filtered labours!</span>';
    } else {
        distinctFields.forEach(function(f, idx) {
            var safeId = 'chk_field_' + idx;
            html += `<label class="badge filter-pill cursor-pointer border bg-danger-subtle text-danger border-danger-subtle px-2 py-1 me-1 mb-1 d-inline-flex align-items-center gap-1" for="${safeId}" style="font-size: 11.5px; font-weight: 600; cursor: pointer; user-select: none;">
                <input type="checkbox" class="form-check-input filter-field-check m-0" id="${safeId}" value="${f}" checked onchange="onIncompleteFieldCheckChange(this)" style="cursor: pointer; width: 14px; height: 14px;">
                <span>${f}</span>
            </label>`;
        });
    }
    $('#incomplete-field-checkboxes').html(html);
    $('#chk_all_fields').prop('checked', true);
}

function toggleAllIncompleteFields(masterChk) {
    var isChecked = $(masterChk).is(':checked');
    $('.filter-field-check').prop('checked', isChecked);
    $('.filter-field-check').each(function() {
        updateFilterPillStyle(this);
    });
}

function onIncompleteFieldCheckChange(inputEl) {
    if (inputEl) {
        updateFilterPillStyle(inputEl);
    }
    var allCount = $('.filter-field-check').length;
    var checkedCount = $('.filter-field-check:checked').length;
    $('#chk_all_fields').prop('checked', allCount > 0 && allCount === checkedCount);
}

function updateFilterPillStyle(inputEl) {
    var $label = $(inputEl).closest('label');
    if ($(inputEl).is(':checked')) {
        $label.removeClass('bg-light text-muted border-secondary-subtle').addClass('bg-danger-subtle text-danger border-danger-subtle');
    } else {
        $label.removeClass('bg-danger-subtle text-danger border-danger-subtle').addClass('bg-light text-muted border-secondary-subtle');
    }
}

function renderIncompleteTable(list) {
    var $tbody = $("#incompleteDetailsTblBody");
    if (!list || list.length === 0) {
        $tbody.html('<tr><td colspan="5" class="text-center py-5"><i class="ti ti-circle-check text-success fs-36 d-block mb-2"></i><span class="text-muted fw-semibold">No labours found matching the selected filters.</span></td></tr>');
        return;
    }
    var key  = $("body").data("key");
    var mdid = $("body").data("mdid");
    var rows = '';
    list.forEach(function(item, idx) {
        var photo = item.profile_photo
            ? ('../' + item.profile_photo)
            : generateSelfAvatarJS(item.FirstName || item.FullName || 'L', item.LastName || '', '#fff0eb', '#e44d26', 64);
        var missingTags = (item.missing || []).map(function(m) {
            return '<span class="missing-tag">' + m + '</span>';
        }).join('');
        var severityClass = item.missingCount >= 5 ? 'table-danger' : (item.missingCount >= 3 ? 'table-warning' : '');
        rows += `<tr class="incomplete-row ${severityClass}" data-name="${(item.FullName || '').toLowerCase()}" data-id="${item.EmployeeID.toLowerCase()}">
            <td class="ps-4 text-muted fw-bold">${idx + 1}</td>
            <td>
                <div class="d-flex align-items-center gap-2">
                    <img src="${photo}" class="rounded-circle" style="width:34px;height:34px;object-fit:cover;border:2px solid #fcdfd7;">
                    <div>
                        <div class="fw-semibold text-dark" style="font-size:13px;">${item.FullName}</div>
                        <div class="text-muted" style="font-size:11px;">ID: ${item.EmployeeID}</div>
                    </div>
                </div>
            </td>
            <td>
                <span class="badge bg-light text-dark border" style="font-size:11px;">${item.LocationName || '-'}</span>
            </td>
            <td style="max-width:400px;">
                <div class="d-flex flex-wrap">${missingTags}</div>
                <div class="mt-1">
                    <small class="text-muted fw-semibold">${item.missingCount} field${item.missingCount > 1 ? 's' : ''} missing</small>
                </div>
            </td>
            <td class="pe-4 text-end">
                <button class="btn btn-sm btn-outline-primary rounded-pill px-3"
                    onclick='$("#IncompleteDetailsModal").modal("hide"); setTimeout(function(){ editLabourDetails("${item.EmployeeID}"); }, 400);'
                    title="Edit to complete details">
                    <i class="ti ti-edit"></i>
                </button>
            </td>
        </tr>`;
    });
    $tbody.html(rows);
}

function filterIncompleteTable() {
    var q = ($("#incomplete-search").val() || "").toLowerCase().trim();

    var checkedFields = [];
    $('.filter-field-check:checked').each(function() {
        checkedFields.push($(this).val());
    });

    var baseList = getIncompleteBaseFilteredList();

    filteredIncompleteData = baseList.filter(function(item) {
        // Text Search in modal input
        var textMatch = true;
        if (q) {
            textMatch = (item.FullName || '').toLowerCase().includes(q) || (item.EmployeeID || '').toLowerCase().includes(q);
        }
        if (!textMatch) return false;

        // Checkbox field filter
        if (checkedFields.length === 0) return false;

        var fieldMatch = false;
        if (item.missing && item.missing.length > 0) {
            item.missing.forEach(function(f) {
                if (checkedFields.indexOf(f) !== -1) {
                    fieldMatch = true;
                }
            });
        }
        return fieldMatch;
    });

    $("#incomplete-modal-count").text(filteredIncompleteData.length);
    renderIncompleteTable(filteredIncompleteData);
}
function exportIncompleteToExcel() {
    if (!filteredIncompleteData || filteredIncompleteData.length === 0) {
        Swal.fire('No Data', 'There are no incomplete records matching the selected filters to export.', 'warning');
        return;
    }

    var dateStr = new Date().toISOString().slice(0, 10);
    var aoa = [];
    aoa.push(["INCOMPLETE LABOUR DETAILS REPORT"]);
    aoa.push(["Report Date:", dateStr, "Total Filtered Records:", filteredIncompleteData.length]);
    aoa.push([]);
    aoa.push(["S.No", "Labour ID", "Labour Name", "Location", "Missing Fields Count", "Missing Fields List"]);

    filteredIncompleteData.forEach(function(item, idx) {
        var missingStr = (item.missing || []).join(", ");
        aoa.push([
            idx + 1,
            item.EmployeeID,
            item.FullName,
            item.LocationName || 'N/A',
            item.missingCount || (item.missing ? item.missing.length : 0),
            missingStr
        ]);
    });

    if (typeof XLSX !== 'undefined') {
        var wb = XLSX.utils.book_new();
        var ws = XLSX.utils.aoa_to_sheet(aoa);
        XLSX.utils.book_append_sheet(wb, ws, "Incomplete_Labours");
        XLSX.writeFile(wb, "Incomplete_Labour_Details_" + dateStr + ".xlsx");
    } else if (typeof ExcelJS !== 'undefined') {
        const workbook = new ExcelJS.Workbook();
        const worksheet = workbook.addWorksheet("Incomplete_Labours");
        aoa.forEach(row => worksheet.addRow(row));
        workbook.xlsx.writeBuffer().then(buffer => {
            const blob = new Blob([buffer], { type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" });
            const link = document.createElement("a");
            link.href = URL.createObjectURL(blob);
            link.download = "Incomplete_Labour_Details_" + dateStr + ".xlsx";
            link.click();
        });
    } else {
        // Basic CSV fallback
        var csvContent = "data:text/csv;charset=utf-8,";
        aoa.forEach(function(rowArray) {
            var row = rowArray.map(v => '"' + (v + '').replace(/"/g, '""') + '"').join(",");
            csvContent += row + "\r\n";
        });
        var encodedUri = encodeURI(csvContent);
        var link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "Incomplete_Labour_Details_" + dateStr + ".csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

function exportLabourMasterToExcel() {
    let btn = $("#btnExportLabourExcel");
    let originalHtml = btn.html();
    
    // Show loading state
    btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-1"></span>Exporting...');
    
    try {
        const workbook = new ExcelJS.Workbook();
        const worksheet = workbook.addWorksheet("Workforce_Directory");
        
        // Define columns
        worksheet.columns = [
            { header: "S.No", key: "sno", width: 8 },
            { header: "Labour Id", key: "labour_id", width: 18 },
            { header: "Labour Name", key: "labour_name", width: 28 },
            { header: "Mobile No", key: "mobile_no", width: 16 },
            { header: "Address", key: "address", width: 32 },
            { header: "Daily Wages", key: "daily_wages", width: 15 },
            { header: "Location", key: "location", width: 20 },
            { header: "Grafting Skill", key: "grafting_skill", width: 15 }
        ];
        
        // Format headers
        worksheet.getRow(1).font = { bold: true, name: 'Calibri', size: 11 };
        worksheet.getRow(1).alignment = { horizontal: 'left', vertical: 'middle' };
        worksheet.getRow(1).eachCell((cell) => {
            cell.fill = {
                type: 'pattern',
                pattern: 'solid',
                fgColor: { argb: 'FFE0E0E0' } // Light gray header
            };
            cell.border = {
                top: { style: 'thin' },
                left: { style: 'thin' },
                bottom: { style: 'thin' },
                right: { style: 'thin' }
            };
        });
        
        let dataToExport = (typeof currentFilteredData !== 'undefined' && currentFilteredData.length > 0) 
            ? currentFilteredData 
            : globalLabourData;
            
        // Populate rows
        dataToExport.forEach((labour, index) => {
            let locName = locMap[labour.locationid] || '';
            let graftingVal = labour.graftingskill || '';
            let graftingText = graftingVal === 'yes' ? 'YES' : (graftingVal === 'no' ? 'NO' : '');
            let addressText = [labour.AddressLine1, labour.AddressLine2].filter(Boolean).join(', ') || labour.AddressLine1 || '';
            
            // Format daily wage as a number
            let dailyWage = parseFloat(labour.PerDayPayment) || 0.00;
            
            let row = worksheet.addRow({
                sno: index + 1,
                labour_id: labour.EmployeeID,
                labour_name: labour.FullName,
                mobile_no: labour.MobileNo || '',
                address: addressText,
                daily_wages: dailyWage,
                location: locName,
                grafting_skill: graftingText
            });
            
            // Format daily wages cell to currency format
            let wagesCell = row.getCell('daily_wages');
            wagesCell.numFmt = '[$₹-3601] #,##0.00'; // Rupee currency format in Excel
            
            // Alignments and borders
            row.eachCell((cell) => {
                cell.font = { name: 'Calibri', size: 10 };
                cell.border = {
                    top: { style: 'thin' },
                    left: { style: 'thin' },
                    bottom: { style: 'thin' },
                    right: { style: 'thin' }
                };
            });
            
            row.getCell('sno').alignment = { horizontal: 'center' };
            row.getCell('daily_wages').alignment = { horizontal: 'right' };
            row.getCell('grafting_skill').alignment = { horizontal: 'center' };
        });
        
        // Write file
        workbook.xlsx.writeBuffer().then(buffer => {
            const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = "Workforce_Directory.xlsx";
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            
            btn.prop("disabled", false).html(originalHtml);
            iziToast.success({
                title: 'Export Success',
                message: 'Workforce Directory Excel has been downloaded successfully.',
                position: 'topRight'
            });
        }).catch(err => {
            throw err;
        });
        
    } catch (error) {
        console.error("Export error:", error);
        btn.prop("disabled", false).html(originalHtml);
        iziToast.error({
            title: 'Export Failed',
            message: 'An error occurred during Excel generation.',
            position: 'topRight'
        });
    }
}
