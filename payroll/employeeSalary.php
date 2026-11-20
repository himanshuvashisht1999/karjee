<?php
$empid = "";
if(isset($_GET['vw'])==1){
    $key=$_GET['k'];
    $rooturl="";
}else{
    require('header.php');
    if(isset($_GET['empid'])){
        $empid = $_GET['empid'];
        $eep=fetchEmployeeData($hrmconn, $companyid, $subcompanyid, $projectid, "EmployeeID", $empid);
        // TitleName,FirstName,MiddleName,LastName,Gender
        $employeeName = str_replace("  ", "", $eep[0]['TitleName'].' '.$eep[0]['FirstName'].' '.$eep[0]['MiddleName'].' '.$eep[0]['LastName']);
    }
    
}
?>
<style>
.custom-toggle-switch {
    position: relative;
    display: inline-block;
    width: 52px;
    height: 28px;
    flex-shrink: 0;
}
.custom-toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}
.custom-toggle-switch .slider {
    position: absolute;
    cursor: pointer;
    top: 0; left: 0; right: 0; bottom: 0;
    background-color: #e2e8f0;
    transition: .25s ease;
    border-radius: 28px;
    border: 1.5px solid #cbd5e1;
}
.custom-toggle-switch .slider:before {
    position: absolute;
    content: "";
    height: 20px;
    width: 20px;
    left: 3px;
    bottom: 2.5px;
    background-color: #ffffff;
    transition: .25s ease;
    border-radius: 50%;
    box-shadow: 0 2px 5px rgba(0,0,0,0.25);
}
.custom-toggle-switch input:checked + .slider {
    background-color: #10b981;
    border-color: #059669;
}
.custom-toggle-switch input:checked + .slider:before {
    transform: translateX(23px);
}
.switch-status-tag {
    font-size: 11px !important;
    font-weight: 700 !important;
    min-width: 42px;
    text-align: center;
    border-radius: 6px;
}
</style>
<div class="container mt-2" id="employeesalarycontainer">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <a href="employees.php?k=<?php echo $key; ?>&mdid=<?php echo isset($mdid)?$mdid:''; ?>" class="btn btn-outline-success btn-sm fw-semibold shadow-sm d-inline-flex align-items-center gap-1" style="border-radius: 8px; padding: 6px 14px; font-size: 13.5px;">
            <i class="fa fa-arrow-left me-1"></i> Back to Employee Master
        </a>
    </div>
    <h4 class="text-center" id="headingsalcal"><?php echo $employeeName != "" ? "Salary of " . $employeeName : "Employee Salary"; ?></h4>
    <!-- Employee Name Select -->
    <div class="row mb-3">
        <div class="col-12">
            <label class="fw-semibold">Employee Name</label>
            <select class="form-select select2" id="employeelistfordeduction"></select>
        </div>
    </div>
    <!-- Card 1: Deduction Details -->
    <div class="card shadow-sm border-0 mb-4" id="card1">
        <div class="card-header bg-white py-3 border-bottom">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold text-dark mb-1">Statutory Compliances of Employee Salary</h5>
                    <span class="text-muted small">Which of these statutory compliances are applicable for employee</span>
                </div>
                <button class="btn btn-primary btn-sm fw-semibold addDeductiontoEmployee" id="addDeductionbtn">
                    <i class="fa fa-plus-circle me-1"></i> Add statutory compliances
                </button>
            </div>
        </div>
        <div class="card-body p-4" id="deductioncontainerid">
            <div id="deductionhtmlid"></div>
            <div class="row mt-4">
                <div class="col-12 text-center">
                    <button class="btn btn-primary px-4 py-2 fw-semibold shadow-sm" data-employeeid='<?php echo $empid; ?>'
                        id="savtodbEmpDeductions"
                        onclick="savetodb_EmpDeductions('deductioncontainerid','savtodbEmpDeductions');">
                        <i class="fa fa-save me-2"></i> Save statutory compliances & Next
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- Card 2: Earning & Salary -->
    <div class="card mt-4 shadow-sm border-primary" id="card2">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0 text-white"><i class="fa fa-money me-2"></i> Monthly Salary Breakup & CTC Details</h5>
                    <small class="text-white-50">Enter Monthly Gross or Component Amounts. Special Allowance balances automatically.</small>
                </div>
                <button class="btn btn-sm btn-light text-primary fw-bold addEarningtoEmployee" id="addEarningbtn">
                    <i class="fa fa-plus-circle me-1"></i> Add Earning
                </button>
            </div>
        </div>
        <div class="card-body" id="earningcomplonentcont">
            <div class="row mt-2 g-3 align-items-center bg-light p-3 rounded border mb-3">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Currency</label>
                    <select id="currencyofempsalary" class="form-select"></select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold text-primary">
                        Monthly Gross Salary <span class="symbollabel"></span>
                    </label>
                    <input type="number" step="0.01" class="form-control fw-bold border-primary shadow-sm" id="monthlygrosssalary" placeholder="0.00" />
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-bold">
                        CTC Pay (Annually) <span class="symbollabel"></span>
                    </label>
                    <input type="number" step="0.01" class="form-control fw-bold bg-white" id="ctcsalary" data-componentname="ctc" data-componentpercent="" placeholder="0.00" />
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-12">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="isbasicasctc" id="isbasicasctc" />
                        <label class="form-check-label fw-semibold" for="isbasicasctc">Is the Basic salary 100% of the CTC?</label>
                    </div>
                </div>
                <div class="col-12 mt-2">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0" id="salarycomponenttable">
                            <thead class="table-light">
                                <tr>
                                    <th>Salary Component</th>
                                    <th>Calculation Type</th>
                                    <th class="text-end" style="width: 220px;">Monthly Amount (<span class="symbollabel">₹</span>)</th>
                                    <th class="text-end" style="width: 220px;">Annual Amount (<span class="symbollabel">₹</span>)</th>
                                </tr>
                            </thead>
                            <tbody id="earningcomponentstbody"></tbody>
                            <tbody id="deductioncomponentstbody"></tbody>
                            <tfoot id="salarycomponenttfoot"></tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12 text-center">
                    <button class="btn btn-primary btn-lg px-4 shadow" data-employeeid='<?php echo $empid; ?>'
                        id="savtodbEmpEarningComponents"
                        onclick="savetodb_EmpEarningComponentsnew('earningcomplonentcont','savtodbEmpEarningComponents');">
                        <i class="fa fa-save me-2"></i> Save Salary Breakup & Earning Details
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Salary & Deduction Net Payable Summary -->
    <div class="card mt-4 border-success shadow-sm" id="card3">
        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
            <strong class="fs-6"><i class="fa fa-calculator me-2"></i> Salary & Net Payable Summary</strong>
            <span class="badge bg-light text-success fw-bold px-3 py-2" id="summary_employee_tag">Real-Time Calculation</span>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="p-3 bg-light rounded text-center border h-100 d-flex flex-column justify-content-center">
                        <span class="text-muted d-block mb-1 font-weight-bold" style="font-size: 13px;">Gross Monthly Salary</span>
                        <h3 class="text-success fw-bold mb-1" id="summary_monthly_gross">₹ 0.00</h3>
                        <span class="text-muted small" id="summary_annual_gross">Annual Gross: ₹ 0.00</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 bg-light rounded text-center border h-100 d-flex flex-column justify-content-center">
                        <span class="text-muted d-block mb-1 font-weight-bold" style="font-size: 13px;">Total Monthly Deductions</span>
                        <h3 class="text-danger fw-bold mb-1" id="summary_monthly_deductions">₹ 0.00</h3>
                        <span class="text-muted small" id="summary_deductions_breakdown">PF: ₹0 | ESI: ₹0 | PT: ₹0</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 bg-success bg-opacity-10 rounded text-center border border-success h-100 d-flex flex-column justify-content-center">
                        <span class="text-success d-block mb-1 font-weight-bold" style="font-size: 13px;">Net Payable Salary (Monthly)</span>
                        <h3 class="text-dark fw-bold mb-1" id="summary_monthly_netpayable">₹ 0.00</h3>
                        <span class="text-success font-weight-bold small" id="summary_annual_netpayable">Annual Net: ₹ 0.00</span>
                    </div>
                </div>
            </div>
            
            <!-- Detailed Deductions List Table -->
            <div class="table-responsive mt-3">
                <table class="table table-bordered table-sm align-middle text-center mb-0" id="deductionbreakdowntable">
                    <thead class="table-light">
                        <tr>
                            <th class="text-start">Deduction Component</th>
                            <th>Status</th>
                            <th class="text-end">Monthly Amount (₹)</th>
                            <th class="text-end">Annual Amount (₹)</th>
                        </tr>
                    </thead>
                    <tbody id="deductionbreakdowntbody">
                        <tr><td colspan="4" class="text-center text-muted">No statutory deductions applied</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Navigation Buttons -->
    <div class="row mt-4 mb-4">
        <div class="col-6">
            <button type="button" class="btn btn-primary px-4 fw-bold" id="prevButton"
                onclick="handleSalaryPrevNav()">
                <i class="fa fa-arrow-left me-1" aria-hidden="true"></i> Back
            </button>
        </div>
        <div class="col-6 text-end">
            <button type="button" class="btn btn-primary px-4 fw-bold" id="nextButton"
                onclick="handleSalaryNextNav()">
                Next <i class="fa fa-arrow-right ms-1" aria-hidden="true"></i>
            </button>
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
let companyid = $("body").data("companyid");
let subcompanyid = $("body").data("subcompanyid");
let projectid = $("body").data("projectid");
let employeeid = "<?php echo $empid; ?>";

function goToEmployeeWork() {
    var selEmp = $('#employeelistfordeduction').val() || "<?php echo $empid; ?>";
    var urlParams = new URLSearchParams(window.location.search);
    var k = urlParams.get('k') || '';
    var vw = urlParams.get('vw') || '';
    
    var targetUrl = 'employeeWork.php?';
    if (k) targetUrl += 'k=' + encodeURIComponent(k) + '&';
    if (vw) targetUrl += 'vw=' + encodeURIComponent(vw) + '&';
    if (selEmp) targetUrl += 'empid=' + encodeURIComponent(selEmp);
    
    window.location.href = targetUrl;
}

function handleSalaryPrevNav() {
    if (typeof currentCard !== 'undefined' && currentCard > 1) {
        navigateCardWithContainerid('prev','prevButton','nextButton','employeesalarycontainer');
    } else {
        goToEmployeeWork();
    }
}

function handleSalaryNextNav() {
    navigateCardWithContainerid('next','prevButton','nextButton','employeesalarycontainer');
}

// Override navigateCardWithContainerid behavior to keep Back button always visible
var _origNavigateCard = window.navigateCardWithContainerid;
window.navigateCardWithContainerid = function(direction, prevButtonId, nextButtonId, cardContainerId) {
    if (typeof _origNavigateCard === 'function') {
        _origNavigateCard(direction, prevButtonId, nextButtonId, cardContainerId);
    }
    // Always show Back button so user can go back to employeeWork page
    $('#' + prevButtonId).removeClass('d-none').show();
    
    // Manage Next button on last card
    const $cards = $("#" + cardContainerId + " .card");
    if (typeof currentCard !== 'undefined' && currentCard >= $cards.length) {
        $('#' + nextButtonId).addClass('d-none');
    } else {
        $('#' + nextButtonId).removeClass('d-none').show();
    }
};

navigateCardWithContainerid('next','prevButton','nextButton','employeesalarycontainer');
$('#prevButton').removeClass('d-none').show();

// Currency list
courrencylist_callback('all', 'all', function(res) {
    if (res && res.length > 0) {
        var crlist = "";
        if (res.length > 1) {
            crlist += "<option value='' disabled>Choose Currency Name</option>";
        }
        for (var i = 0; i < res.length; i++) {
            crlist += "<option value='" + res[i].CurrencyCode + "' " +
                      "data-currencyname='" + res[i].CurrencyName + "' " +
                      "data-currencycode='" + res[i].CurrencyCode + "' " +
                      "data-currencysymbol='" + res[i].CurrencySymbol + "'>" + 
                      res[i].CurrencyCode + "</option>";
        }
        if (crlist) {
            $("#currencyofempsalary").html(crlist);
            $("#currencyofempsalary").val("INR").trigger("change");
        }
    } else {
        $("#currencyofempsalary").html("<option value='' disabled>No data available</option>");
    }
});

$(document).on("change", "#currencyofempsalary", function(){
    var crsymbol=$("#currencyofempsalary option:selected").data("currencysymbol") || "₹";
    $(".symbollabel").html(crsymbol);
});

fetchEmployeeData_callback('allactive','allactive',function(res){
    var empoption="";
    if(res.length>0){
        if(res.length>1){
            empoption +="<option value='' disabled selected>Select employee</option>";
        }
        for (var i = 0; i < res.length; i++) {
            var empCode = res[i].EmployeeID;
            var empLabel = res[i].FullName + (empCode ? " (" + empCode + ")" : "");
            empoption +="<option value='"+res[i].EmployeeID+"'>"+empLabel+"</option>";
        }
        if(empoption){
            $("#employeelistfordeduction").html(empoption);
            if(employeeid){
                $("#employeelistfordeduction").val(employeeid).trigger("change");
            }
        }
    }
});

hrm_deductioncomponent('defaultforemployee','defaultforemployee',function(res){
    if(res.length>0){
        var htmrow="";
        for (var i = 0; i < res.length; i++) {
            htmrow += '<div class="row g-2 mt-2">';
            htmrow += '  <div class="col-12">';
            htmrow += '    <div class="p-3 bg-light rounded border mb-2 d-flex align-items-center justify-content-between shadow-sm">';
            htmrow += '      <label class="fw-semibold text-dark fs-14 mb-0 cursor-pointer me-3" for="component_' + res[i].DeductionId + '">';
            htmrow += '        Is applicable for ' + res[i].DeductionName + ' ('+ res[i].sortname +')?';
            htmrow += '      </label>';
            htmrow += '      <div class="d-flex align-items-center gap-2">';
            htmrow += '        <span class="badge bg-secondary text-white px-2 py-1 switch-status-tag" id="tag_component_' + res[i].DeductionId + '">OFF</span>';
            htmrow += '        <label class="custom-toggle-switch mb-0">';
            htmrow += '          <input class="deductioncomponent" type="checkbox"';
            htmrow += '                 name="deductioncomponent" id="component_' + res[i].DeductionId + '"';
            htmrow += '                 data-componentid="' + res[i].DeductionId + '" value="' + res[i].DeductionId + '">';
            htmrow += '          <span class="slider round"></span>';
            htmrow += '        </label>';
            htmrow += '      </div>';
            htmrow += '    </div>';
            htmrow += '  </div>';
            htmrow += '</div>';
        }
        $("#deductionhtmlid").html(htmrow);
    }
});

function syncSwitchTags() {
    $("input[name='deductioncomponent']").each(function() {
        var id = $(this).attr('id');
        var isChecked = $(this).is(':checked');
        if (isChecked) {
            $('#tag_' + id).removeClass('bg-secondary').addClass('bg-success').text('ON');
        } else {
            $('#tag_' + id).removeClass('bg-success').addClass('bg-secondary').text('OFF');
        }
    });
}

$(document).on("change", "input[name='deductioncomponent']", function() {
    syncSwitchTags();
    calculateSalaryBreakupAndNetPayable('deduction');
});

$(document).on("change", "#employeelistfordeduction", function(){
    var selEmpId = $(this).val();
    $("#savtodbEmpDeductions").data("employeeid", selEmpId);
    $("#savtodbEmpEarningComponents").data("employeeid", selEmpId);
    $("#headingsalcal").html("Salary of "+$("#employeelistfordeduction option:selected").text());
    
    hrm_EarningComponentWithSalaryDetails(selEmpId, function(slData){
        $("#ctcsalary").val('');
        $("#monthlygrosssalary").val('');
        $("#earningcomponentstbody").empty();
        
        if (slData && slData.length > 0) {
            let ehtmrow = ``;
            var totalMonthlyGross = 0;
            var totalAnnualCTC = 0;

            slData.forEach(item => {
                if (parseFloat(item.CTCY) > 0) {
                    totalAnnualCTC = parseFloat(item.CTCY);
                }
                
                let monthlysalary = 0;
                let yearlysalary = 0;

                switch (parseInt(item.ComponentId)) {
                    case 1: // Basic
                        monthlysalary = parseFloat(item.BasicSalary) || 0;
                        break;
                    case 2: // HRA
                        monthlysalary = parseFloat(item.HRA) || 0;
                        break;
                    case 4: // Conveyance
                        monthlysalary = parseFloat(item.ConveyanceAllowance) || 0;
                        break;
                    case 6: // Special Allowance
                        monthlysalary = parseFloat(item.SpecialAllowance) || 0;
                        break;
                    default:
                        monthlysalary = parseFloat(item.EmployeeEarningValue) || 0;
                        break;
                }
                yearlysalary = monthlysalary * 12;

                if (item.EmployeeEarningType == "Percentage") {
                    var baseLabel = (parseInt(item.ComponentId) === 1) ? "Gross" : item.EmployeeEarningBase;
                    ehtmrow += `<tr data-EmployeeEarningType="${item.EmployeeEarningType}" data-EmployeeEarningBase="${item.EmployeeEarningBase}" data-EmployeeEarningValue="${item.EmployeeEarningValue}" data-Frequency="${item.Frequency}" data-trcomponentname="${item.ComponentName}" data-trcomponentid="${item.ComponentId}" data-ispercentrow='yes'>
                        <td class="fw-bold text-dark">${item.ComponentName}</td>
                        <td>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">% of ${baseLabel}</span>
                                <input type="number" step="0.01" class="form-control earningpercent" id="earningPercent_${item.ComponentId}" data-trearningtype="${item.EmployeeEarningType}" data-trearningvalue="${item.EmployeeEarningValue}" data-trearningbase="${item.EmployeeEarningBase}" value="${item.EmployeeEarningValue}"/>
                            </div>
                        </td>
                        <td><input type="number" step="0.01" class="form-control text-end monthlycontribution fw-semibold" id="earningmonthly_${item.ComponentId}" value="${monthlysalary.toFixed(2)}"/></td>
                        <td><input type="number" step="0.01" class="form-control text-end yearlycontribution fw-semibold bg-light" data-percentinputid="earningPercent_${item.ComponentId}" id="earningyearly_${item.ComponentId}" value="${yearlysalary.toFixed(2)}" readonly /></td>
                    </tr>`;
                } else {
                    var calcTypeLabel = (parseInt(item.ComponentId) === 6) ? "Balancing Amount" : "Fixed Amount / Limit";
                    ehtmrow += `<tr class="trcomponentname" data-EmployeeEarningType="${item.EmployeeEarningType}" data-EmployeeEarningBase="${item.EmployeeEarningBase}" data-EmployeeEarningValue="${item.EmployeeEarningValue}" data-Frequency="${item.Frequency}" data-trcomponentname="${item.ComponentName}" data-trcomponentid="${item.ComponentId}" data-ispercentrow='no'>
                        <td class="fw-bold text-dark">${item.ComponentName}</td>
                        <td><span class="badge bg-light text-secondary border">${calcTypeLabel}</span></td>
                        <td><input type="number" step="0.01" class="form-control text-end monthlycontribution fw-semibold" id="earningmonthly_${item.ComponentId}" value="${monthlysalary.toFixed(2)}"/></td>
                        <td><input type="number" step="0.01" class="form-control text-end yearlycontribution fw-semibold bg-light" id="earningyearly_${item.ComponentId}" value="${yearlysalary.toFixed(2)}" readonly/></td>
                    </tr>`;
                }
            });

            $("#earningcomponentstbody").html(ehtmrow);
            
            if (totalAnnualCTC > 0) {
                $("#ctcsalary").val(totalAnnualCTC.toFixed(2));
                $("#monthlygrosssalary").val((totalAnnualCTC / 12).toFixed(2));
            }
            calculateSalaryBreakupAndNetPayable('load');
        }
    });
    
    hrm_EmployeeDeductionComponent_callback(selEmpId, function(eData) {
        $("input[name='deductioncomponent']").prop("checked", false);
        if (eData && eData.length > 0) {
            for (var i = 0; i < eData.length; i++) {
                $("input[name='deductioncomponent'][data-componentid='" + eData[i].DeductionId + "']").prop("checked", true);
            }
        }
        calculateSalaryBreakupAndNetPayable('deduction');
    });
});

$(document).on("click", "#isbasicasctc", function () {
    if ($(this).is(":checked")) {
        $("#salarycomponenttable tbody tr").each(function () {
            var componentid = $(this).data("trcomponentid");
            if (componentid == 1) {
                $(this).find("input.earningpercent").val(100);
            } else {
                $(this).find("input.earningpercent").val(0);
            }
        });
    } else {
        $("#salarycomponenttable tbody tr").each(function () {
            var ispercentrowval = $(this).data("employeeearningvalue");
            $(this).find("input.earningpercent").val(ispercentrowval);
        });
    }
    calculateSalaryBreakupAndNetPayable('percent');
});

/* Input Event Handlers for Real-Time Recalculation */
$(document).on("input change", "#monthlygrosssalary", function() {
    calculateSalaryBreakupAndNetPayable('gross');
});

$(document).on("input change", "#ctcsalary", function() {
    calculateSalaryBreakupAndNetPayable('ctc');
});

$(document).on("input change", "input.earningpercent", function () {
    calculateSalaryBreakupAndNetPayable('percent');
});

$(document).on("input change", "input.monthlycontribution", function() {
    var cId = parseInt($(this).closest("tr").data("trcomponentid"));
    if (cId === 1) {
        calculateSalaryBreakupAndNetPayable('basic');
    } else if (cId === 2) {
        calculateSalaryBreakupAndNetPayable('hra');
    } else if (cId === 4) {
        calculateSalaryBreakupAndNetPayable('conveyance');
    } else if (cId === 6) {
        calculateSalaryBreakupAndNetPayable('special');
    } else {
        calculateSalaryBreakupAndNetPayable('manual_component');
    }
});

/* Master Recalculation & Net Payable Function */
function formatNum(val) {
    var num = parseFloat(val);
    return isNaN(num) ? '0.00' : num.toFixed(2);
}

function getNum(val, defaultVal) {
    if (val === undefined || val === null || val === '') return defaultVal;
    var n = parseFloat(val);
    return isNaN(n) ? defaultVal : n;
}

function calculateSalaryBreakupAndNetPayable(source) {
    var monthlyGross = 0;
    var annualCTC = 0;

    if (source === 'gross') {
        monthlyGross = getNum($('#monthlygrosssalary').val(), 0);
        annualCTC = monthlyGross * 12;
        $('#ctcsalary').val(formatNum(annualCTC));
    } else if (source === 'ctc') {
        annualCTC = getNum($('#ctcsalary').val(), 0);
        monthlyGross = annualCTC / 12;
        $('#monthlygrosssalary').val(formatNum(monthlyGross));
    } else if (source === 'special' || source === 'manual_component') {
        var b = getNum($('#earningmonthly_1').val(), 0);
        var h = getNum($('#earningmonthly_2').val(), 0);
        var c = getNum($('#earningmonthly_4').val(), 0);
        var s = getNum($('#earningmonthly_6').val(), 0);
        monthlyGross = b + h + c + s;
        annualCTC = monthlyGross * 12;
        $('#monthlygrosssalary').val(formatNum(monthlyGross));
        $('#ctcsalary').val(formatNum(annualCTC));
    } else {
        monthlyGross = getNum($('#monthlygrosssalary').val(), 0);
        if (monthlyGross <= 0) {
            annualCTC = getNum($('#ctcsalary').val(), 0);
            monthlyGross = annualCTC / 12;
            if (monthlyGross > 0) {
                $('#monthlygrosssalary').val(formatNum(monthlyGross));
            }
        } else {
            annualCTC = monthlyGross * 12;
            $('#ctcsalary').val(formatNum(annualCTC));
        }
    }

    var is100Basic = $('#isbasicasctc').is(':checked');

    // 1. Basic Salary (Component 1) - Default 50% of Monthly Gross Salary
    var basicPct = is100Basic ? 100 : getNum($('#earningPercent_1').val(), 50);
    if (is100Basic) {
        $('#earningPercent_1').val(100);
    }
    
    var basicMonthly = 0;
    if (source === 'basic') {
        basicMonthly = getNum($('#earningmonthly_1').val(), 0);
    } else {
        basicMonthly = monthlyGross * (basicPct / 100);
        $('#earningmonthly_1').val(formatNum(basicMonthly));
    }
    $('#earningyearly_1').val(formatNum(basicMonthly * 12));

    // 2. House Rent Allowance - HRA (Component 2) - % of Basic Salary
    var hraPct = is100Basic ? 0 : getNum($('#earningPercent_2').val(), 50);
    if (is100Basic) {
        $('#earningPercent_2').val(0);
    }

    var hraMonthly = 0;
    if (is100Basic || hraPct === 0) {
        hraMonthly = 0;
        $('#earningmonthly_2').val(formatNum(0));
    } else if (source === 'hra') {
        hraMonthly = getNum($('#earningmonthly_2').val(), 0);
    } else {
        hraMonthly = basicMonthly * (hraPct / 100);
        $('#earningmonthly_2').val(formatNum(hraMonthly));
    }
    $('#earningyearly_2').val(formatNum(hraMonthly * 12));

    // 3. Conveyance Allowance (Component 4) - Limit up to ₹1,600 / month
    var convMonthly = 0;
    if (is100Basic) {
        convMonthly = 0;
        $('#earningmonthly_4').val(formatNum(0));
    } else if (source === 'conveyance') {
        convMonthly = getNum($('#earningmonthly_4').val(), 0);
    } else {
        var remForConv = Math.max(0, monthlyGross - basicMonthly - hraMonthly);
        convMonthly = Math.min(1600, remForConv);
        $('#earningmonthly_4').val(formatNum(convMonthly));
    }
    $('#earningyearly_4').val(formatNum(convMonthly * 12));

    // 4. Special Allowance (Component 6 - Balancing Amount)
    var splMonthly = 0;
    if (is100Basic) {
        splMonthly = 0;
        $('#earningmonthly_6').val(formatNum(0));
    } else if (source === 'special') {
        splMonthly = getNum($('#earningmonthly_6').val(), 0);
    } else {
        splMonthly = Math.max(0, monthlyGross - (basicMonthly + hraMonthly + convMonthly));
        $('#earningmonthly_6').val(formatNum(splMonthly));
    }
    $('#earningyearly_6').val(formatNum(splMonthly * 12));

    // Calculate Total Earnings (ALWAYS EQUALS monthlyGross)
    var totalEarningMonthly = basicMonthly + hraMonthly + convMonthly + splMonthly;
    var totalEarningYearly = totalEarningMonthly * 12;

    // 5. Statutory Deductions Calculation
    var epfAmount = 0, esicAmount = 0, ptAmount = 0, taxAmount = 0;
    var deductionRowsHtml = '';
    var tableDeductionRows = `<tr class="table-light"><td colspan="4" class="fw-bold text-danger"><i class="fa fa-minus-circle me-1"></i> Statutory Deductions (Applied Compliances)</td></tr>`;
    var appliedCount = 0;

    syncSwitchTags();

    $("input[name='deductioncomponent']").each(function () {
        var isChecked = $(this).is(":checked");
        var dId = parseInt($(this).data("componentid") || $(this).val());
        var dLabel = $(this).closest('.form-check').find('label').text().trim();
        var dMonthly = 0;

        if (isChecked) {
            appliedCount++;
            if (dId === 1) {
                // EPF (12% of Basic Salary)
                epfAmount = Math.round(basicMonthly * 0.12);
                dMonthly = epfAmount;
            } else if (dId === 3) {
                // ESIC (0.75% of Basic Salary if Gross <= 21,000)
                if (totalEarningMonthly <= 21000 && totalEarningMonthly > 0) {
                    esicAmount = Math.ceil(basicMonthly * 0.0075);
                } else {
                    esicAmount = 0;
                }
                dMonthly = esicAmount;
            } else if (dId === 2 || dId === 5) {
                // Professional Tax (PT)
                if (totalEarningMonthly > 15000) {
                    ptAmount = 200;
                } else {
                    ptAmount = 0;
                }
                dMonthly = ptAmount;
            } else if (dId === 4) {
                // Income Tax / TDS
                taxAmount = 0;
                dMonthly = taxAmount;
            } else {
                dMonthly = 0;
            }

            var dRow = `
                <tr>
                    <td class="ps-4 fw-semibold text-danger">${dLabel}</td>
                    <td><span class="badge bg-danger bg-opacity-10 text-danger border border-danger">Statutory Deduction</span></td>
                    <td class="text-end fw-bold text-danger">- ₹ ${formatNum(dMonthly)}</td>
                    <td class="text-end text-muted">- ₹ ${formatNum(dMonthly * 12)}</td>
                </tr>
            `;
            deductionRowsHtml += dRow;
            tableDeductionRows += dRow;
        }
    });

    if (appliedCount === 0) {
        deductionRowsHtml = '<tr><td colspan="4" class="text-center text-muted">No statutory deductions selected / applicable</td></tr>';
        tableDeductionRows += '<tr><td colspan="4" class="text-center text-muted fst-italic">No statutory deductions applied</td></tr>';
    }

    var totalMonthlyDeduction = epfAmount + esicAmount + ptAmount + taxAmount;
    var totalAnnualDeduction = totalMonthlyDeduction * 12;

    var netPayableMonthly = Math.max(0, totalEarningMonthly - totalMonthlyDeduction);
    var netPayableAnnual = netPayableMonthly * 12;

    $('#deductioncomponentstbody').html(tableDeductionRows);

    // Update main table footer with Gross, Deductions, and Net Payable Salary!
    $("#salarycomponenttfoot").html(`
        <tr class="summary-gross-row border-top border-2">
            <td colspan='2' class="text-end fw-bold" style="font-size: 15px !important; color: #15803d !important; padding: 10px 14px !important;">Total Gross Salary (Monthly & Annual CTC):</td>
            <td class="text-end fw-bold" style="font-size: 16px !important; color: #15803d !important; padding: 10px 14px !important;">₹ ${formatNum(totalEarningMonthly)}</td>
            <td class="text-end fw-bold" style="font-size: 16px !important; color: #15803d !important; padding: 10px 14px !important;">₹ ${formatNum(totalEarningYearly)}</td>
        </tr>
        <tr class="summary-deduction-row border-top">
            <td colspan='2' class="text-end fw-bold" style="font-size: 15px !important; color: #dc2626 !important; padding: 10px 14px !important;">Total Statutory Deductions:</td>
            <td class="text-end fw-bold" style="font-size: 16px !important; color: #dc2626 !important; padding: 10px 14px !important;">- ₹ ${formatNum(totalMonthlyDeduction)}</td>
            <td class="text-end fw-bold" style="font-size: 16px !important; color: #dc2626 !important; padding: 10px 14px !important;">- ₹ ${formatNum(totalAnnualDeduction)}</td>
        </tr>
        <tr class="summary-netpayable-row" style="background-color: #d1fae5 !important; border-top: 2.5px solid #10b981 !important;">
            <td colspan='2' class="text-end fw-bold" style="font-size: 17px !important; color: #047857 !important; padding: 12px 14px !important;"><i class="fa fa-check-circle me-1"></i> Net Payable Salary:</td>
            <td class="text-end fw-bold" style="font-size: 18px !important; color: #064e3b !important; padding: 12px 14px !important;">₹ ${formatNum(netPayableMonthly)}</td>
            <td class="text-end fw-bold" style="font-size: 18px !important; color: #064e3b !important; padding: 12px 14px !important;">₹ ${formatNum(netPayableAnnual)}</td>
        </tr>
    `);

    // Update UI Summary Card
    $('#summary_monthly_gross').text('₹ ' + formatNum(totalEarningMonthly));
    $('#summary_annual_gross').text('Annual Gross: ₹ ' + formatNum(totalEarningYearly));

    $('#summary_monthly_deductions').text('- ₹ ' + formatNum(totalMonthlyDeduction));
    $('#summary_deductions_breakdown').text(`PF: ₹${epfAmount} | ESI: ₹${esicAmount} | PT: ₹${ptAmount}`);

    $('#summary_monthly_netpayable').text('₹ ' + formatNum(netPayableMonthly));
    $('#summary_annual_netpayable').text('Annual Net: ₹ ' + formatNum(netPayableAnnual));

    $('#deductionbreakdowntbody').html(deductionRowsHtml);
}
</script>