<?php
if(isset($_GET['vw'])==1){

}else{
  require('header.php');
}
?>
<style>
.search-autocomplete-wrapper {
    position: relative;
}
.search-autocomplete-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 1050;
    max-height: 380px;
    overflow-y: auto;
    background: #ffffff !important;
    border: 2px solid #3b82f6 !important;
    border-radius: 0 0 10px 10px;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2) !important;
    display: none;
}
.search-result-item {
    padding: 12px 18px !important;
    border-bottom: 1px solid #e2e8f0 !important;
    cursor: pointer !important;
    transition: all 0.2s ease !important;
    background: #ffffff !important;
    font-size: 15px !important;
    line-height: 1.5 !important;
}
.search-result-item:hover, .search-result-item.active-item {
    background-color: #f0f9ff !important;
    border-left: 4px solid #2563eb !important;
}
.search-result-item:last-child {
    border-bottom: none !important;
}
.emp-title-name {
    font-size: 16px !important;
    font-weight: 700 !important;
    color: #0f172a !important;
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
}
.emp-meta-badge {
    font-size: 13px !important;
    font-weight: 500 !important;
    color: #475569 !important;
    margin-top: 5px !important;
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 14px !important;
}
.emp-meta-badge strong {
    color: #0f172a !important;
    font-weight: 600 !important;
}
.emp-id-tag {
    font-size: 12px !important;
    padding: 2px 8px !important;
    border-radius: 4px !important;
    background-color: #f1f5f9 !important;
    color: #334155 !important;
    border: 1px solid #cbd5e1 !important;
    font-weight: 600 !important;
}
#employeeincrementcontainer table tfoot tr td,
.inc-summary-total-row,
.inc-summary-total-row td {
    font-size: 16px !important;
    line-height: 1.6 !important;
    padding: 12px 16px !important;
    height: auto !important;
    vertical-align: middle !important;
    background-color: #d1fae5 !important;
}
</style>

<div class="container mt-3 mb-5" id="employeeincrementcontainer">
    <h4 class="text-center fw-bold text-dark mb-3"><i class="fa fa-line-chart me-2 text-primary"></i> Employee Salary Increment</h4>

    <!-- Card 1: Search Employee -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="fw-bold text-dark mb-0"><i class="fa fa-search me-2 text-primary"></i> Search & Select Employee</h5>
        </div>
        <div class="card-body p-4">
            <div class="row">
                <div class="col-md-12 search-autocomplete-wrapper">
                    <label class="form-label fw-semibold text-dark">Search Employee (Name, Father Name, Joining Date, Contact No)</label>
                    <div class="input-group input-group-lg shadow-sm">
                        <span class="input-group-text bg-white"><i class="fa fa-search text-muted"></i></span>
                        <input type="text" id="empSearchInput" class="form-control fw-semibold" placeholder="Type Employee Name, Father Name, DOJ (YYYY-MM-DD), or Contact No..." autocomplete="off" />
                        <button class="btn btn-outline-secondary" type="button" id="clearSearchBtn"><i class="fa fa-times"></i> Clear</button>
                    </div>
                    <div id="empDropdownMenu" class="search-autocomplete-results"></div>
                </div>
            </div>

            <!-- Selected Employee Info Card (Hidden initially) -->
            <div id="selectedEmpInfoCard" class="mt-4 p-3 bg-light rounded border border-primary border-opacity-25 d-none">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="fw-bold text-primary mb-1" id="selEmpName">--</h5>
                        <div class="d-flex flex-wrap gap-3 text-muted small mt-1">
                            <span><i class="fa fa-user me-1"></i> Father: <strong class="text-dark" id="selEmpFather">--</strong></span>
                            <span><i class="fa fa-calendar me-1"></i> DOJ: <strong class="text-dark" id="selEmpDOJ">--</strong></span>
                            <span><i class="fa fa-phone me-1"></i> Contact: <strong class="text-dark" id="selEmpContact">--</strong></span>
                            <span><i class="fa fa-id-badge me-1"></i> ID: <strong class="text-dark" id="selEmpID">--</strong></span>
                        </div>
                    </div>
                    <span class="badge bg-success px-3 py-2 fs-6">Selected</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Increment Workflow Div (Hidden until employee selected) -->
    <div id="incrementSectionDiv" class="d-none">
        <div class="row g-4">
            <!-- Card 2: Old Salary Details -->
            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="fw-bold text-secondary mb-0"><i class="fa fa-history me-2"></i> Current / Old Salary Details</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="p-3 bg-light rounded border mb-3 text-center">
                            <span class="text-muted small d-block">Current Monthly Gross Salary</span>
                            <h3 class="fw-bold text-dark mb-0" id="oldGrossText">₹ 0.00</h3>
                            <span class="text-muted small d-block mt-1" id="oldCTCText">Annual CTC: ₹ 0.00</span>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Component</th>
                                        <th class="text-end">Monthly Amount (₹)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="fw-semibold">Basic Salary</td>
                                        <td class="text-end fw-bold text-dark" id="oldBasicVal">₹ 0.00</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">HRA</td>
                                        <td class="text-end fw-bold text-dark" id="oldHRAVal">₹ 0.00</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">Conveyance Allowance</td>
                                        <td class="text-end fw-bold text-dark" id="oldConveyanceVal">₹ 0.00</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">Special Allowance</td>
                                        <td class="text-end fw-bold text-dark" id="oldSpecialVal">₹ 0.00</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 3: Apply Salary Increment -->
            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-primary text-white py-3">
                        <h5 class="fw-bold mb-0 text-white"><i class="fa fa-percent me-2"></i> Apply Salary Increment</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Increment Calculation Mode</label>
                            <div class="d-flex gap-4 p-2 bg-light rounded border">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="incrementType" id="incTypePercent" value="Percentage" checked>
                                    <label class="form-check-label fw-bold text-dark" for="incTypePercent">
                                        Percentage (%)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="incrementType" id="incTypeAmount" value="Amount">
                                    <label class="form-check-label fw-bold text-dark" for="incTypeAmount">
                                        Fixed Amount (₹)
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="incrementValueInput" class="form-label fw-semibold text-dark">
                                    Increment Value <span id="incValueLabel">(%)</span> <span class="text-danger">*</span>
                                </label>
                                <input type="number" step="0.01" class="form-control fw-bold border-primary shadow-sm" id="incrementValueInput" placeholder="Enter % or Amount" value="0" />
                            </div>
                            <div class="col-md-6">
                                <label for="affectedFromDate" class="form-label fw-semibold text-dark">
                                    Affected / Effective From <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control fw-semibold" id="affectedFromDate" value="<?php echo date('Y-m-d'); ?>" />
                            </div>
                            <div class="col-md-12">
                                <label for="incrementRemarks" class="form-label fw-semibold text-dark">Remarks / Reason</label>
                                <input type="text" class="form-control" id="incrementRemarks" placeholder="e.g. Annual Appraisal 2026, Performance Review..." />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 4: New Salary Preview & Comparison -->
        <div class="card shadow-sm border-success mt-4">
            <div class="card-header bg-success text-white py-3">
                <h5 class="fw-bold mb-0 text-white"><i class="fa fa-calculator me-2"></i> New Incremented Salary Breakdown & Comparison</h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded text-center border">
                            <span class="text-muted small d-block">Old Gross Salary</span>
                            <h4 class="fw-bold text-secondary mb-0" id="previewOldGross">₹ 0.00</h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-primary bg-opacity-10 rounded text-center border border-primary">
                            <span class="text-primary small d-block fw-bold">Increment Addition</span>
                            <h4 class="fw-bold text-primary mb-0" id="previewIncAddition">+ ₹ 0.00</h4>
                            <span class="badge bg-primary mt-1" id="previewIncTag">0%</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-success bg-opacity-10 rounded text-center border border-success">
                            <span class="text-success small d-block fw-bold">New Monthly Gross Salary</span>
                            <h3 class="fw-bold text-success mb-0" id="previewNewGross">₹ 0.00</h3>
                            <span class="text-success small fw-bold mt-1 d-block" id="previewNewCTC">Annual CTC: ₹ 0.00</span>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle text-center mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-start">Salary Component</th>
                                <th class="text-end">Old Monthly (₹)</th>
                                <th class="text-end">Increment (+₹)</th>
                                <th class="text-end">New Monthly (₹)</th>
                                <th class="text-end">New Annual (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-start fw-bold">Basic Salary</td>
                                <td class="text-end text-muted" id="compOldBasic">₹ 0.00</td>
                                <td class="text-end text-primary font-weight-bold" id="compIncBasic">+ ₹ 0.00</td>
                                <td class="text-end fw-bold text-success" id="compNewBasic">₹ 0.00</td>
                                <td class="text-end text-dark" id="compNewAnnualBasic">₹ 0.00</td>
                            </tr>
                            <tr>
                                <td class="text-start fw-bold">HRA (House Rent Allowance)</td>
                                <td class="text-end text-muted" id="compOldHRA">₹ 0.00</td>
                                <td class="text-end text-primary font-weight-bold" id="compIncHRA">+ ₹ 0.00</td>
                                <td class="text-end fw-bold text-success" id="compNewHRA">₹ 0.00</td>
                                <td class="text-end text-dark" id="compNewAnnualHRA">₹ 0.00</td>
                            </tr>
                            <tr>
                                <td class="text-start fw-bold">Conveyance Allowance</td>
                                <td class="text-end text-muted" id="compOldConveyance">₹ 0.00</td>
                                <td class="text-end text-primary font-weight-bold" id="compIncConveyance">+ ₹ 0.00</td>
                                <td class="text-end fw-bold text-success" id="compNewConveyance">₹ 0.00</td>
                                <td class="text-end text-dark" id="compNewAnnualConveyance">₹ 0.00</td>
                            </tr>
                            <tr>
                                <td class="text-start fw-bold">Special Allowance</td>
                                <td class="text-end text-muted" id="compOldSpecial">₹ 0.00</td>
                                <td class="text-end text-primary font-weight-bold" id="compIncSpecial">+ ₹ 0.00</td>
                                <td class="text-end fw-bold text-success" id="compNewSpecial">₹ 0.00</td>
                                <td class="text-end text-dark" id="compNewAnnualSpecial">₹ 0.00</td>
                            </tr>
                        </tbody>
                        <tfoot class="inc-summary-total-row fw-bold" style="border-top: 2.5px solid #10b981 !important;">
                            <tr class="inc-summary-total-row">
                                <td class="text-start text-dark fw-bold" style="font-size: 16px !important; padding: 14px !important;">Total Monthly Gross:</td>
                                <td class="text-end text-muted fw-bold" style="font-size: 16px !important; padding: 14px !important;" id="footOldGross">₹ 0.00</td>
                                <td class="text-end text-primary fw-bold" style="font-size: 16px !important; padding: 14px !important;" id="footIncGross">+ ₹ 0.00</td>
                                <td class="text-end text-success fw-bold" style="font-size: 17px !important; padding: 14px !important;" id="footNewGross">₹ 0.00</td>
                                <td class="text-end text-dark fw-bold" style="font-size: 17px !important; padding: 14px !important;" id="footNewCTC">₹ 0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="row mt-4">
                    <div class="col-12 text-center">
                        <button type="button" class="btn btn-success btn-lg px-5 py-2 shadow fw-bold" id="saveIncrementBtn">
                            <i class="fa fa-check-circle me-2"></i> UPDATE & SAVE SALARY INCREMENT
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 5: Salary Increment History Log -->
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="fw-bold text-dark mb-0"><i class="fa fa-history me-2 text-primary"></i> Salary Increment History Log</h5>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle text-center mb-0" id="incrementHistoryTable">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Affected From Date</th>
                                <th>Valid Till Date</th>
                                <th>Increment Type</th>
                                <th>Value</th>
                                <th>Previous Gross (₹)</th>
                                <th>Increment Amount (₹)</th>
                                <th>New Gross (₹)</th>
                                <th>New Annual CTC (₹)</th>
                                <th>Remarks</th>
                                <th>Updated Date</th>
                            </tr>
                        </thead>
                        <tbody id="incrementHistoryTbody">
                            <tr><td colspan="11" class="text-center text-muted">No increment history found for this employee</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
if(isset($_GET['vw'])==1){

}else{
  require('footer.php');
}
?>

<script type="text/javascript">
let activeSearchIndex = -1;
let selectedEmployeeData = null;
let currentSalaryData = {
    gross: 0,
    ctc: 0,
    basic: 0,
    hra: 0,
    conveyance: 0,
    special: 0
};
let calculatedNewSalary = {
    gross: 0,
    ctc: 0,
    basic: 0,
    hra: 0,
    conveyance: 0,
    special: 0,
    incAmount: 0,
    incValue: 0
};

// Autocomplete Search Input Keydown & Input Handlers
$(document).on("input", "#empSearchInput", function() {
    let query = $(this).val().trim();
    if (query.length >= 1) {
        fetchEmployeeSearchResults(query);
    } else {
        $("#empDropdownMenu").hide().empty();
        activeSearchIndex = -1;
    }
});

$(document).on("keydown", "#empSearchInput", function(e) {
    let $items = $("#empDropdownMenu .search-result-item");
    if ($items.length === 0) return;

    if (e.key === "ArrowDown") {
        e.preventDefault();
        activeSearchIndex++;
        if (activeSearchIndex >= $items.length) activeSearchIndex = 0;
        highlightSearchResultItem($items);
    } else if (e.key === "ArrowUp") {
        e.preventDefault();
        activeSearchIndex--;
        if (activeSearchIndex < 0) activeSearchIndex = $items.length - 1;
        highlightSearchResultItem($items);
    } else if (e.key === "Enter") {
        e.preventDefault();
        if (activeSearchIndex >= 0 && activeSearchIndex < $items.length) {
            $items.eq(activeSearchIndex).click();
        }
    } else if (e.key === "Escape") {
        $("#empDropdownMenu").hide();
        activeSearchIndex = -1;
    }
});

function highlightSearchResultItem($items) {
    $items.removeClass("active-item");
    if (activeSearchIndex >= 0 && activeSearchIndex < $items.length) {
        let $active = $items.eq(activeSearchIndex);
        $active.addClass("active-item");
        $active[0].scrollIntoView({ block: "nearest", behavior: "smooth" });
    }
}

function fetchEmployeeSearchResults(query) {
    let baseurl = $("body").data("routurl") || "";
    $.ajax({
        type: "POST",
        url: baseurl + "modal/hrm_modal.php?action=SearchEmployeeForIncrement",
        data: {
            search: query,
            companyid: $("body").data("companyid"),
            subcompanyid: $("body").data("subcompanyid")
        },
        dataType: 'JSON',
        success: function(res) {
            let html = "";
            activeSearchIndex = -1;
            if (res && res.length > 0) {
                res.forEach(function(emp, index) {
                    html += `
                        <div class="search-result-item" data-empindex="${index}">
                            <div class="emp-title-name">
                                <i class="fa fa-user-circle text-primary" style="font-size: 18px !important;"></i>
                                <span>${emp.FullName}</span>
                                <span class="emp-id-tag">ID: ${emp.EmployeeID}</span>
                            </div>
                            <div class="emp-meta-badge">
                                <span><i class="fa fa-user me-1 text-secondary"></i> Father: <strong>${emp.FatherName}</strong></span>
                                <span><i class="fa fa-calendar me-1 text-secondary"></i> DOJ: <strong>${emp.DateOfJoining}</strong></span>
                                <span><i class="fa fa-phone me-1 text-secondary"></i> Contact: <strong>${emp.ContactNo}</strong></span>
                            </div>
                        </div>
                    `;
                });
                $("#empDropdownMenu").html(html).show();

                // Save current results on window object
                window._searchEmployeeResults = res;
            } else {
                $("#empDropdownMenu").html('<div class="p-3 text-center text-muted">No matching employee found</div>').show();
            }
        }
    });
}

$(document).on("click", ".search-result-item", function() {
    let index = $(this).data("empindex");
    if (window._searchEmployeeResults && window._searchEmployeeResults[index]) {
        selectEmployeeForIncrement(window._searchEmployeeResults[index]);
    }
});

$(document).on("click", "#clearSearchBtn", function() {
    $("#empSearchInput").val('');
    $("#empDropdownMenu").hide().empty();
    $("#selectedEmpInfoCard").addClass("d-none");
    $("#incrementSectionDiv").addClass("d-none");
    selectedEmployeeData = null;
    activeSearchIndex = -1;
});

function selectEmployeeForIncrement(emp) {
    selectedEmployeeData = emp;
    $("#empSearchInput").val(emp.FullName + " (" + emp.EmployeeID + ")");
    $("#empDropdownMenu").hide();

    $("#selEmpName").text(emp.FullName);
    $("#selEmpFather").text(emp.FatherName);
    $("#selEmpDOJ").text(emp.DateOfJoining);
    $("#selEmpContact").text(emp.ContactNo);
    $("#selEmpID").text(emp.EmployeeID);
    $("#selectedEmpInfoCard").removeClass("d-none");

    loadEmployeeSalaryAndHistory(emp.EmployeeID);
}

function loadEmployeeSalaryAndHistory(employeeid) {
    let baseurl = $("body").data("routurl") || "";

    // 1. Fetch current salary details
    hrm_EarningComponentWithSalaryDetails(employeeid, function(slData) {
        let monthlyGross = 0;
        let basicVal = 0;
        let hraVal = 0;
        let conveyanceVal = 0;
        let specialVal = 0;

        if (slData && slData.length > 0) {
            slData.forEach(function(item) {
                let mVal = parseFloat(item.EmployeeEarningValue) || 0;
                switch (parseInt(item.ComponentId)) {
                    case 1:
                        basicVal = parseFloat(item.BasicSalary) || mVal;
                        break;
                    case 2:
                        hraVal = parseFloat(item.HRA) || mVal;
                        break;
                    case 4:
                        conveyanceVal = parseFloat(item.ConveyanceAllowance) || mVal;
                        break;
                    case 6:
                        specialVal = parseFloat(item.SpecialAllowance) || mVal;
                        break;
                }
            });
            monthlyGross = basicVal + hraVal + conveyanceVal + specialVal;
        }

        currentSalaryData = {
            gross: monthlyGross,
            ctc: monthlyGross * 12,
            basic: basicVal,
            hra: hraVal,
            conveyance: conveyanceVal,
            special: specialVal
        };

        $("#oldGrossText").text("₹ " + formatNum(currentSalaryData.gross));
        $("#oldCTCText").text("Annual CTC: ₹ " + formatNum(currentSalaryData.ctc));
        $("#oldBasicVal").text("₹ " + formatNum(currentSalaryData.basic));
        $("#oldHRAVal").text("₹ " + formatNum(currentSalaryData.hra));
        $("#oldConveyanceVal").text("₹ " + formatNum(currentSalaryData.conveyance));
        $("#oldSpecialVal").text("₹ " + formatNum(currentSalaryData.special));

        $("#incrementSectionDiv").removeClass("d-none");
        recalculateIncrementPreview();
    });

    // 2. Fetch history log
    $.ajax({
        type: "POST",
        url: baseurl + "modal/hrm_modal.php?action=GetEmployeeIncrementHistory",
        data: { employeeid: employeeid },
        dataType: 'JSON',
        success: function(histData) {
            let hhtml = "";
            if (histData && histData.length > 0) {
                histData.forEach(function(row, idx) {
                    let validTillBadge = (row.ValidTillDateFormatted === 'Present' || row.ValidTillDateFormatted === 'Active') 
                        ? '<span class="badge bg-success">Present</span>' 
                        : '<span class="badge bg-secondary">' + row.ValidTillDateFormatted + '</span>';

                    let incValText = (row.IncrementType === 'Initial Salary' || row.IncrementValue === 0) 
                        ? '-' 
                        : (row.IncrementType === 'Percentage' ? row.IncrementValue + '%' : '₹ ' + formatNum(row.IncrementValue));

                    let incAmtText = (row.IncrementAmount > 0) 
                        ? ('+ ₹ ' + formatNum(row.IncrementAmount)) 
                        : '₹ 0.00';

                    hhtml += `
                        <tr>
                            <td>${idx + 1}</td>
                            <td><span class="badge bg-primary">${row.AffectedFromDateFormatted}</span></td>
                            <td>${validTillBadge}</td>
                            <td><span class="badge ${row.IncrementType === 'Initial Salary' ? 'bg-info text-dark' : 'bg-dark'}">${row.IncrementType}</span></td>
                            <td>${incValText}</td>
                            <td>₹ ${formatNum(row.PreviousGross)}</td>
                            <td class="${row.IncrementAmount > 0 ? 'text-success fw-bold' : 'text-muted'}">${incAmtText}</td>
                            <td class="fw-bold text-success">₹ ${formatNum(row.NewGross)}</td>
                            <td class="fw-bold text-dark">₹ ${formatNum(row.NewCTC)}</td>
                            <td>${row.Remarks || '-'}</td>
                            <td class="text-muted small">${row.CreatedDateFormatted}</td>
                        </tr>
                    `;
                });
            } else {
                hhtml = '<tr><td colspan="11" class="text-center text-muted">No increment history found for this employee</td></tr>';
            }
            $("#incrementHistoryTbody").html(hhtml);
        }
    });
}

// Increment Type Radio Change
$(document).on("change", "input[name='incrementType']", function() {
    let mode = $(this).val();
    if (mode === "Percentage") {
        $("#incValueLabel").text("(%)");
        $("#incrementValueInput").attr("placeholder", "Enter % e.g. 10");
    } else {
        $("#incValueLabel").text("(₹)");
        $("#incrementValueInput").attr("placeholder", "Enter Amount e.g. 5000");
    }
    recalculateIncrementPreview();
});

// Increment Input Change
$(document).on("input change", "#incrementValueInput", function() {
    recalculateIncrementPreview();
});

function recalculateIncrementPreview() {
    let incMode = $("input[name='incrementType']:checked").val();
    let incVal = parseFloat($("#incrementValueInput").val()) || 0;
    
    let oldGross = currentSalaryData.gross;
    let incAmount = 0;
    if (incMode === "Percentage") {
        incAmount = (oldGross * incVal) / 100;
    } else {
        incAmount = incVal;
    }
    
    let newGross = oldGross + incAmount;
    let newCTC = newGross * 12;

    // Component Breakup Logic:
    let newBasic = currentSalaryData.basic;
    let newHRA = currentSalaryData.hra;
    let newConveyance = currentSalaryData.conveyance;
    let newSpecial = currentSalaryData.special;

    if (incAmount === 0 || incVal === 0) {
        // No increment entered: maintain exact existing component amounts
        newBasic = currentSalaryData.basic;
        newHRA = currentSalaryData.hra;
        newConveyance = currentSalaryData.conveyance;
        newSpecial = currentSalaryData.special;
    } else if (oldGross > 0) {
        // Increment entered: scale components proportionally based on Old Gross
        let ratio = newGross / oldGross;
        newBasic = Math.round(currentSalaryData.basic * ratio);
        newHRA = Math.round(currentSalaryData.hra * ratio);
        newConveyance = Math.round(currentSalaryData.conveyance * ratio);
        newSpecial = Math.max(0, newGross - (newBasic + newHRA + newConveyance));
    } else if (newGross > 0) {
        // Fallback for zero base gross: Basic 50%, HRA 40% of Basic, Conveyance = 0, Special = Balance
        newBasic = Math.round(newGross * 0.50);
        newHRA = Math.round(newBasic * 0.40);
        newConveyance = 0;
        newSpecial = Math.max(0, newGross - (newBasic + newHRA + newConveyance));
    }

    let incBasic = newBasic - currentSalaryData.basic;
    let incHRA = newHRA - currentSalaryData.hra;
    let incConveyance = newConveyance - currentSalaryData.conveyance;
    let incSpecial = newSpecial - currentSalaryData.special;

    calculatedNewSalary = {
        gross: newGross,
        ctc: newCTC,
        basic: newBasic,
        hra: newHRA,
        conveyance: newConveyance,
        special: newSpecial,
        incAmount: incAmount,
        incValue: incVal,
        incMode: incMode
    };

    // Helper for formatting increment column (+ ₹ X.XX or - ₹ X.XX)
    function fmtIncCol(val) {
        if (Math.abs(val) < 0.01) {
            return "+ ₹ 0.00";
        }
        return (val >= 0 ? "+ ₹ " : "- ₹ ") + formatNum(Math.abs(val));
    }

    // UI Updates
    $("#previewOldGross").text("₹ " + formatNum(oldGross));
    $("#previewIncAddition").text("+ ₹ " + formatNum(incAmount));
    $("#previewIncTag").text(incMode === "Percentage" ? incVal + "%" : "Fixed ₹" + formatNum(incVal));
    $("#previewNewGross").text("₹ " + formatNum(newGross));
    $("#previewNewCTC").text("Annual CTC: ₹ " + formatNum(newCTC));

    $("#compOldBasic").text("₹ " + formatNum(currentSalaryData.basic));
    $("#compIncBasic").text(fmtIncCol(incBasic));
    $("#compNewBasic").text("₹ " + formatNum(newBasic));
    $("#compNewAnnualBasic").text("₹ " + formatNum(newBasic * 12));

    $("#compOldHRA").text("₹ " + formatNum(currentSalaryData.hra));
    $("#compIncHRA").text(fmtIncCol(incHRA));
    $("#compNewHRA").text("₹ " + formatNum(newHRA));
    $("#compNewAnnualHRA").text("₹ " + formatNum(newHRA * 12));

    $("#compOldConveyance").text("₹ " + formatNum(currentSalaryData.conveyance));
    $("#compIncConveyance").text(fmtIncCol(incConveyance));
    $("#compNewConveyance").text("₹ " + formatNum(newConveyance));
    $("#compNewAnnualConveyance").text("₹ " + formatNum(newConveyance * 12));

    $("#compOldSpecial").text("₹ " + formatNum(currentSalaryData.special));
    $("#compIncSpecial").text(fmtIncCol(incSpecial));
    $("#compNewSpecial").text("₹ " + formatNum(newSpecial));
    $("#compNewAnnualSpecial").text("₹ " + formatNum(newSpecial * 12));

    $("#footOldGross").text("₹ " + formatNum(oldGross));
    $("#footIncGross").text("+ ₹ " + formatNum(incAmount));
    $("#footNewGross").text("₹ " + formatNum(newGross));
    $("#footNewCTC").text("₹ " + formatNum(newCTC));
}

// Save Increment Button Click
$(document).on("click", "#saveIncrementBtn", function() {
    if (!selectedEmployeeData || !selectedEmployeeData.EmployeeID) {
        alertmessage('error', 'Please select an employee first.');
        return;
    }
    if (calculatedNewSalary.incAmount <= 0) {
        alertmessage('warning', 'Please enter a valid increment value greater than 0.');
        $('#incrementValueInput').focus();
        return;
    }
    let affectedDate = $("#affectedFromDate").val();
    if (!affectedDate) {
        alertmessage('warning', 'Please select the Affected / Effective From date.');
        $('#affectedFromDate').focus();
        return;
    }

    let baseurl = $("body").data("routurl") || "";
    let formData = new FormData();
    formData.append("companyid", $("body").data("companyid"));
    formData.append("subcompanyid", $("body").data("subcompanyid"));
    formData.append("projectid", $("body").data("projectid"));
    formData.append("loginid", $("body").data("loginid"));
    formData.append("addedby", $("body").data("loginname"));

    formData.append("employeeid", selectedEmployeeData.EmployeeID);
    formData.append("previousGross", currentSalaryData.gross);
    formData.append("previousCTC", currentSalaryData.ctc);
    formData.append("incrementType", calculatedNewSalary.incMode);
    formData.append("incrementValue", calculatedNewSalary.incValue);
    formData.append("incrementAmount", calculatedNewSalary.incAmount);
    formData.append("newGross", calculatedNewSalary.gross);
    formData.append("newCTC", calculatedNewSalary.ctc);

    formData.append("previousBasic", currentSalaryData.basic);
    formData.append("newBasic", calculatedNewSalary.basic);
    formData.append("previousHRA", currentSalaryData.hra);
    formData.append("newHRA", calculatedNewSalary.hra);
    formData.append("previousConveyance", currentSalaryData.conveyance);
    formData.append("newConveyance", calculatedNewSalary.conveyance);
    formData.append("previousSpecial", currentSalaryData.special);
    formData.append("newSpecial", calculatedNewSalary.special);

    formData.append("affectedFromDate", affectedDate);
    formData.append("remarks", $("#incrementRemarks").val());

    $.ajax({
        type: "POST",
        url: baseurl + "modal/hrm_modal.php?action=SaveEmployeeSalaryIncrement",
        data: formData,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'JSON',
        success: function(res) {
            if (res.status === "success" || res.status === "Success") {
                SweetAlertmessageFadein('success', res.message);
                loadEmployeeSalaryAndHistory(selectedEmployeeData.EmployeeID);
                $("#incrementValueInput").val(0);
                $("#incrementRemarks").val('');
            } else {
                alertmessage('error', res.message || 'Failed to save salary increment.');
            }
        },
        error: function(err) {
            alertmessage('error', 'An error occurred while saving the salary increment.');
        }
    });
});

function formatNum(num) {
    return (parseFloat(num) || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
</script>
