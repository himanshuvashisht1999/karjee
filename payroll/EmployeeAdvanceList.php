<?php
include('header.php');
?>
<div class="content container-fluid">
  <!-- Page Header -->
  <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-4">
    <div>
      <h4 class="mb-1 text-dark fw-bold"><i class="ti ti-cash me-2 text-primary"></i>Report of Salary Advances & Pending Returns</h4>
      <p class="text-muted mb-0 fs-13">Manage employee salary advance allocations, monthly deductions, and balance amounts.</p>
    </div>
    <div>
      <button class="btn btn-primary shadow-sm rounded-2 px-3 py-2" id="gotoregisterform">
        <i class="ti ti-plus me-1"></i> Add To Advance
      </button>
    </div>
  </div>

  <!-- Advance KPI Summary Cards (Before Grid) -->
  <div id="advanceSummaryDiv" class="mb-4">
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 g-3">
      <!-- Total Employees Card -->
      <div class="col">
        <div class="card border-0 shadow-sm rounded-3 bg-white h-100">
          <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <small class="text-uppercase text-muted fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Employees</small>
                <h4 class="fw-bold text-dark mb-0 mt-1" id="kpiTotalEmp">0</h4>
              </div>
              <div class="avatar avatar-md bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center p-2">
                <i class="ti ti-users fs-3"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Total Advance Amount Card -->
      <div class="col">
        <div class="card border-0 shadow-sm rounded-3 bg-white h-100">
          <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <small class="text-uppercase text-muted fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Total Advance</small>
                <h5 class="fw-bold text-primary mb-0 mt-1" id="kpiTotalAdvance">₹ 0.00</h5>
              </div>
              <div class="avatar avatar-md bg-info-subtle text-info rounded-circle d-flex align-items-center justify-content-center p-2">
                <i class="ti ti-cash fs-3"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Deduction Per Month Card -->
      <div class="col">
        <div class="card border-0 shadow-sm rounded-3 bg-white h-100">
          <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <small class="text-uppercase text-muted fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Deduction / Month</small>
                <h5 class="fw-bold text-warning mb-0 mt-1" id="kpiDeductionMonth">₹ 0.00</h5>
              </div>
              <div class="avatar avatar-md bg-warning-subtle text-warning rounded-circle d-flex align-items-center justify-content-center p-2">
                <i class="ti ti-calculator fs-3"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Total Deducted Card -->
      <div class="col">
        <div class="card border-0 shadow-sm rounded-3 bg-white h-100">
          <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <small class="text-uppercase text-muted fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Total Deducted</small>
                <h5 class="fw-bold text-secondary mb-0 mt-1" id="kpiTotalDeducted">₹ 0.00</h5>
              </div>
              <div class="avatar avatar-md bg-secondary-subtle text-secondary rounded-circle d-flex align-items-center justify-content-center p-2">
                <i class="ti ti-minus fs-3"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Balance Amount Card -->
      <div class="col">
        <div class="card border-0 shadow-sm rounded-3 bg-white h-100">
          <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <small class="text-uppercase text-muted fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Balance Amount</small>
                <h5 class="fw-bold text-success mb-0 mt-1" id="kpiTotalBalance">₹ 0.00</h5>
              </div>
              <div class="avatar avatar-md bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center p-2">
                <i class="ti ti-wallet fs-3"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Advance Report Table Card -->
  <div class="card shadow-sm border-0 rounded-3">
    <!-- Search Input & Export Header Bar Above Grid -->
    <div class="p-3 bg-light border-bottom rounded-top-3">
      <div class="row align-items-center justify-content-between g-2">
        <div class="col-md-5">
          <div class="input-group shadow-sm rounded-2">
            <span class="input-group-text bg-white border-end-0"><i class="ti ti-search text-muted"></i></span>
            <input type="search" class="form-control border-start-0 ps-0" placeholder="Search by Employee ID, Name..." id="searchadvancetbl" oninput="filterEmployeeAdvanceTable();">
          </div>
        </div>
        <div class="col-md-auto ms-auto d-flex align-items-center gap-2">
          <button class="btn btn-outline-success btn-sm shadow-sm rounded-2 px-3 py-2" onclick="exportSalaryToExcel('emplistAdvancetbl');">
            <i class="ti ti-file-spreadsheet me-1"></i> Export Excel
          </button>
        </div>
      </div>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="emplistAdvancetbl">
          <thead class="bg-light text-dark fw-semibold" style="border-bottom: 2px solid #e2e8f0;">
            <tr>
              <th style="width: 130px;">Employee ID</th>
              <th>Employee Name</th>
              <th>Total Advance Amount</th>
              <th>Deduction / Month</th>
              <th>Deducted Amount</th>
              <th class="text-end" style="width: 170px;">Balance Amount</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td colspan="6" class="text-center text-muted py-4">Loading advance records...</td>
            </tr>
          </tbody>
          <tfoot class="bg-light fw-bold text-dark" style="border-top: 2px solid #cbd5e1;">
            <tr>
              <td colspan="2" class="text-end fw-bold ps-3">TOTAL:</td>
              <td id="tblTotalAdvAmt">₹ 0.00</td>
              <td id="tblTotalDedMonth">₹ 0.00</td>
              <td id="tblTotalDeducted">₹ 0.00</td>
              <td id="tblTotalBalance" class="text-end text-success fw-bold">₹ 0.00</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Add Advance Employee Modal -->
<div class="modal fade" id="addAdvanceEmployeeFormModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg rounded-3">
      <form id="employeeAdvanceForm" method="post" action="save_employee_advance.php">
        <div class="modal-header bg-primary text-white py-3">
          <div class="d-flex align-items-center">
            <i class="ti ti-user-plus me-2 fs-4"></i>
            <h5 class="modal-title mb-0 text-white fw-bold">Employee Advance Details</h5>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body p-4">
          <!-- Hidden ID for update case -->
          <input type="hidden" name="AdvanceID" id="AdvanceID" value="">

          <div class="row g-3">
            <!-- Select Employee with Search (Select2) -->
            <div class="col-md-6 mb-2">
              <label class="form-label fw-bold text-dark fs-13 mb-1">
                <i class="ti ti-user me-1 text-primary"></i> Select Employee <span class="text-danger">*</span>
              </label>
              <select class="form-select select2 w-100" name="EmployeeID" id="EmployeeID" required>
                <option value="">-- Select Employee --</option>
              </select>
            </div>

            <!-- Advance Payment Date -->
            <div class="col-md-6 mb-2">
              <label class="form-label fw-bold text-dark fs-13 mb-1">
                <i class="ti ti-calendar me-1 text-primary"></i> Advance Payment Date <span class="text-danger">*</span>
              </label>
              <input type="date" class="form-control" name="AdvanceDate" id="AdvanceDate" value="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <!-- Advance Amount -->
            <div class="col-md-6 mb-2">
              <label class="form-label fw-bold text-dark fs-13 mb-1">
                <i class="ti ti-currency-rupee me-1 text-success"></i> Advance Amount (₹) <span class="text-danger">*</span>
              </label>
              <div class="input-group">
                <span class="input-group-text bg-light fw-bold">₹</span>
                <input type="number" class="form-control" name="AdvanceAmount" id="AdvanceAmount" placeholder="Enter total advance amount" step="0.01" min="0" required>
              </div>
            </div>

            <!-- Monthly Advance Deduction -->
            <div class="col-md-6 mb-2">
              <label class="form-label fw-bold text-dark fs-13 mb-1">
                <i class="ti ti-calculator me-1 text-info"></i> Monthly Deduction (₹) <span class="text-danger">*</span>
              </label>
              <div class="input-group">
                <span class="input-group-text bg-light fw-bold">₹</span>
                <input type="number" class="form-control" name="DeductionPerMonth" id="DeductionPerMonth" placeholder="Enter monthly deduction amount" step="0.01" min="0" required>
              </div>
            </div>

            <!-- Remark -->
            <div class="col-md-12 mb-2">
              <label class="form-label fw-bold text-dark fs-13 mb-1">
                <i class="ti ti-notes me-1 text-secondary"></i> Remark (if any)
              </label>
              <textarea class="form-control" rows="3" name="AdvanceRemark" id="AdvanceRemark" placeholder="Enter purpose or additional notes..."></textarea>
            </div>
          </div>
        </div>

        <div class="modal-footer bg-light py-3 border-top">
          <input type="hidden" name="companyid" value="<?php echo $companyid; ?>" />
          <input type="hidden" name="subcompanyid" value="<?php echo $subcompanyid; ?>" />
          <input type="hidden" name="projectid" value="<?php echo $projectid; ?>" />
          <input type="hidden" name="loginid" value="<?php echo $loginid; ?>" />
          <input type="hidden" name="loginname" value="<?php echo $loginname; ?>" />
          <button type="button" class="btn btn-outline-secondary px-4 me-2" data-bs-dismiss="modal">
            <i class="ti ti-x me-1"></i> Cancel
          </button>
          <button type="submit" class="btn btn-primary px-4 shadow-sm" id="btnSaveAdvance">
            <i class="ti ti-device-floppy me-1"></i> Save Advance
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- /Add Advance Employee Modal -->

<!-- Employee Advance Breakdown & History Details Modal -->
<div class="modal fade" id="advanceDetailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content border-0 shadow-lg rounded-3">
      <div class="modal-header bg-primary text-white py-3">
        <div class="d-flex align-items-center justify-content-between w-100 me-2">
          <div class="d-flex align-items-center">
            <i class="ti ti-receipt-tax me-2 fs-4 text-warning"></i>
            <h5 class="modal-title mb-0 text-white fw-bold">
              Advance Details — <span id="advDetailModalEmpName" class="text-warning">-</span>
            </h5>
          </div>
          <button class="btn btn-light btn-sm shadow-sm text-success fw-bold px-3 py-1 me-2" onclick="exportAdvanceDetailsToExcel('modalAdvanceDetailTbl');">
            <i class="ti ti-file-spreadsheet me-1"></i> Export Excel
          </button>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body p-4" style="background-color: #f8f9fa;">
        <!-- KPI Summary Cards in Modal -->
        <div class="row g-3 mb-4 text-center">
          <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 bg-white">
              <div class="card-body p-2">
                <small class="text-muted d-block text-truncate fw-bold" style="font-size: 0.72rem;">TOTAL ADVANCE GIVEN</small>
                <h5 class="text-primary mb-0 fw-bold mt-1" id="mKpiAdvAmt">₹ 0.00</h5>
              </div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 bg-white">
              <div class="card-body p-2">
                <small class="text-muted d-block text-truncate fw-bold" style="font-size: 0.72rem;">MONTHLY DEDUCTION</small>
                <h5 class="text-warning mb-0 fw-bold mt-1" id="mKpiDedMonth">₹ 0.00</h5>
              </div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 bg-white">
              <div class="card-body p-2">
                <small class="text-muted d-block text-truncate fw-bold" style="font-size: 0.72rem;">TOTAL DEDUCTED</small>
                <h5 class="text-secondary mb-0 fw-bold mt-1" id="mKpiDedTotal">₹ 0.00</h5>
              </div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 bg-white">
              <div class="card-body p-2">
                <small class="text-muted d-block text-truncate fw-bold" style="font-size: 0.72rem;">OUTSTANDING BALANCE</small>
                <h5 class="text-success mb-0 fw-bold mt-1" id="mKpiNetAdv">₹ 0.00</h5>
              </div>
            </div>
          </div>
        </div>

        <!-- Transactions History Table -->
        <div class="card border-0 shadow-sm rounded-3">
          <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between border-bottom">
            <h6 class="mb-0 text-dark fw-bold"><i class="ti ti-list-details me-2 text-primary"></i>Advance Allocation & Deduction Breakdown Ledger</h6>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
              <table class="table table-hover align-middle mb-0" id="modalAdvanceDetailTbl">
                <thead class="table-light sticky-top">
                  <tr>
                    <th class="text-center" style="width: 50px;">S.No</th>
                    <th style="width: 140px;">Date / Period</th>
                    <th style="width: 160px;">Entry Type</th>
                    <th>Description / Notes</th>
                    <th class="text-end" style="width: 140px;">Advance (+)</th>
                    <th class="text-end" style="width: 140px;">Deducted (-)</th>
                    <th class="text-end" style="width: 150px;">Running Balance</th>
                  </tr>
                </thead>
                <tbody>
                  <!-- Populated dynamically -->
                </tbody>
                <tfoot class="bg-light fw-bold text-dark">
                  <!-- Summary row -->
                </tfoot>
              </table>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer bg-light py-2 border-top">
        <button type="button" class="btn btn-secondary px-4 btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<?php include('footer.php'); ?>

<script type="text/javascript">
var routurl = $("body").data("routurl") || "../";
var key = $("body").data("key");

$(document).ready(function() {
    // Initialize Select2 on Employee ID dropdown inside modal
    if ($.fn.select2) {
        $('#EmployeeID').select2({
            dropdownParent: $('#addAdvanceEmployeeFormModal'),
            placeholder: '-- Select Employee --',
            allowClear: true,
            width: '100%'
        });
    }

    // Load initial table data
    loadEmployeeAdvanceData();
});

// Open Add Advance Modal
$(document).on("click", "#gotoregisterform", function() {
    $("#AdvanceID").val("");
    $("#employeeAdvanceForm")[0].reset();
    let today = new Date().toISOString().split('T')[0];
    $("#AdvanceDate").val(today);
    $("#EmployeeID").val("").trigger("change");
    $("#addAdvanceEmployeeFormModal").modal("show");

    // Fetch active employees for dropdown
    fetchEmployeeDatawithOtherifno_callback('allactive', 'allactive', function(res) {
        var empoption = `<option value="">-- Select Employee --</option>`;
        if (res && res.length > 0) {
            for (var i = 0; i < res.length; i++) {
                let empName = (res[i].FullName || (res[i].TitleName + ' ' + res[i].FirstName + ' ' + res[i].MiddleName + ' ' + res[i].LastName)).replace(/\s+/g, ' ').trim();
                empoption += `<option value="${res[i].EmployeeID}">${res[i].EmployeeID} - ${empName}</option>`;
            }
        } else {
            empoption = `<option value="">-- No active employees available --</option>`;
        }
        $("#EmployeeID").html(empoption).trigger("change");
    });
});

// Load Advance List Data function
function loadEmployeeAdvanceData() {
    fetchEmployeeAdvanceFromMaster_callback('all', 'all', function(res) {
        if (res && res.length > 0) {
            var emplist = ``;
            let sumAdvAmt = 0;
            let sumDedMonth = 0;
            let sumDedTotal = 0;
            let sumNetAdv = 0;

            for (var i = 0; i < res.length; i++) {
                let rawAdvAmt = parseFloat(res[i].AdvanceAmount || 0);
                let rawDedMonth = parseFloat(res[i].DeductionPerMonth || 0);
                let rawDedTotal = parseFloat(res[i].totaldebitadvance || 0);
                let rawNetAdv = parseFloat(res[i].NetAdvance || 0);

                sumAdvAmt += rawAdvAmt;
                sumDedMonth += rawDedMonth;
                sumDedTotal += rawDedTotal;
                sumNetAdv += rawNetAdv;

                let empDashUrl = `${routurl}empPanel/index.php?k=${key}&empid=${res[i].EmployeeID}&accessby=adminu`;
                let empIdLink = `<a href="${empDashUrl}" target="_blank" class="fw-bold text-primary text-decoration-none" title="Click to open Employee Dashboard">${res[i].EmployeeID}</a>`;
                let advAmt = rawAdvAmt.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                let dedMonth = rawDedMonth.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                let dedTotal = rawDedTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                let netAdv = rawNetAdv.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});

                let balanceBtn = `<a href="javascript:void(0)" 
                    class="badge bg-success-subtle text-success border border-success-subtle fw-bold fs-13 px-2.5 py-1 text-decoration-none view-advance-details" 
                    data-empid="${res[i].EmployeeID}" 
                    data-empname="${res[i].FullName}"
                    data-advamt="${rawAdvAmt}"
                    data-dedmonth="${rawDedMonth}"
                    data-dedtotal="${rawDedTotal}"
                    data-netadv="${rawNetAdv}"
                    title="Click to view detailed advance breakdown history">
                    ₹ ${netAdv} <i class="ti ti-eye ms-1"></i>
                </a>`;

                emplist += `<tr data-labourid="${res[i].EmployeeID}" data-advamt="${rawAdvAmt}" data-dedmonth="${rawDedMonth}" data-dedtotal="${rawDedTotal}" data-netadv="${rawNetAdv}">
                    <td class="fw-bold">${empIdLink}</td>
                    <td class="fw-semibold text-dark">${res[i].FullName}</td>
                    <td>₹ ${advAmt}</td>
                    <td>₹ ${dedMonth}</td>
                    <td>₹ ${dedTotal}</td>
                    <td class="text-end fw-bold">${balanceBtn}</td>
                </tr>`;
            }
            $("#emplistAdvancetbl tbody").html(emplist);

            // Update KPI Summary Cards
            $("#kpiTotalEmp").text(res.length);
            $("#kpiTotalAdvance").text('₹ ' + sumAdvAmt.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $("#kpiDeductionMonth").text('₹ ' + sumDedMonth.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $("#kpiTotalDeducted").text('₹ ' + sumDedTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $("#kpiTotalBalance").text('₹ ' + sumNetAdv.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));

            // Update Table Total Row (tfoot)
            $("#tblTotalAdvAmt").text('₹ ' + sumAdvAmt.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $("#tblTotalDedMonth").text('₹ ' + sumDedMonth.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $("#tblTotalDeducted").text('₹ ' + sumDedTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $("#tblTotalBalance").text('₹ ' + sumNetAdv.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));

            // Re-apply filter if search input has value
            filterEmployeeAdvanceTable();
        } else {
            $("#emplistAdvancetbl tbody").html('<tr><td colspan="6" class="text-center text-muted py-4">No advance records available</td></tr>');

            // Reset KPI Summary Cards
            $("#kpiTotalEmp").text("0");
            $("#kpiTotalAdvance").text("₹ 0.00");
            $("#kpiDeductionMonth").text("₹ 0.00");
            $("#kpiTotalDeducted").text("₹ 0.00");
            $("#kpiTotalBalance").text("₹ 0.00");

            // Reset Table Total Row
            $("#tblTotalAdvAmt").text("₹ 0.00");
            $("#tblTotalDedMonth").text("₹ 0.00");
            $("#tblTotalDeducted").text("₹ 0.00");
            $("#tblTotalBalance").text("₹ 0.00");
        }
    });
}

// Search Filter Function
function filterEmployeeAdvanceTable() {
    let q = $("#searchadvancetbl").val().toLowerCase().trim();
    let rows = $("#emplistAdvancetbl tbody tr");
    
    let visibleCount = 0;
    let sumAdvAmt = 0;
    let sumDedMonth = 0;
    let sumDedTotal = 0;
    let sumNetAdv = 0;

    rows.each(function() {
        let $tr = $(this);
        let empId = $tr.data("labourid") ? $tr.data("labourid").toString().toLowerCase() : "";
        let empName = $tr.find("td:nth-child(2)").text().toLowerCase();
        let fullText = empId + " " + empName;

        if (fullText.indexOf(q) !== -1 || q === "") {
            $tr.show();
            visibleCount++;

            let advAmt = parseFloat($tr.attr("data-advamt") || 0);
            let dedMonth = parseFloat($tr.attr("data-dedmonth") || 0);
            let dedTotal = parseFloat($tr.attr("data-dedtotal") || 0);
            let netAdv = parseFloat($tr.attr("data-netadv") || 0);

            sumAdvAmt += advAmt;
            sumDedMonth += dedMonth;
            sumDedTotal += dedTotal;
            sumNetAdv += netAdv;
        } else {
            $tr.hide();
        }
    });

    // Update KPI Summary Cards for filtered results
    $("#kpiTotalEmp").text(visibleCount);
    $("#kpiTotalAdvance").text('₹ ' + sumAdvAmt.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $("#kpiDeductionMonth").text('₹ ' + sumDedMonth.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $("#kpiTotalDeducted").text('₹ ' + sumDedTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $("#kpiTotalBalance").text('₹ ' + sumNetAdv.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));

    // Update Table Total Row (tfoot)
    $("#tblTotalAdvAmt").text('₹ ' + sumAdvAmt.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $("#tblTotalDedMonth").text('₹ ' + sumDedMonth.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $("#tblTotalDeducted").text('₹ ' + sumDedTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $("#tblTotalBalance").text('₹ ' + sumNetAdv.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
}

// Click Balance Column -> Open Detailed Advance History Modal
$(document).on("click", ".view-advance-details", function() {
    let empId = $(this).data("empid");
    let empName = $(this).data("empname");
    let advAmt = parseFloat($(this).data("advamt") || 0);
    let dedMonth = parseFloat($(this).data("dedmonth") || 0);
    let dedTotal = parseFloat($(this).data("dedtotal") || 0);
    let netAdv = parseFloat($(this).data("netadv") || 0);

    $("#advDetailModalEmpName").text(`${empId} - ${empName}`);
    $("#mKpiAdvAmt").text('₹ ' + advAmt.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $("#mKpiDedMonth").text('₹ ' + dedMonth.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $("#mKpiDedTotal").text('₹ ' + dedTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $("#mKpiNetAdv").text('₹ ' + netAdv.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));

    $("#modalAdvanceDetailTbl tbody").html('<tr><td colspan="7" class="text-center text-muted py-4"><i class="ti ti-spinner fa-spin me-1"></i> Loading transaction history...</td></tr>');
    $("#advanceDetailsModal").modal("show");

    var form_data = new FormData();
    form_data.append("companyid", $("body").data("companyid"));
    form_data.append("subcompanyid", $("body").data("subcompanyid"));
    form_data.append("projectid", $("body").data("projectid"));
    form_data.append("EmployeeID", empId);

    var baseRoutUrl = $("body").data("routurl") || $("#hidden_routurl").val() || (typeof routurl !== 'undefined' ? routurl : '') || "../";
    if (baseRoutUrl && !baseRoutUrl.endsWith('/')) {
        baseRoutUrl += '/';
    }

    $.ajax({
        url: baseRoutUrl + 'modal/hrm_salarymodal.php?action=fetchEmployeeAdvanceDetailsHistory',
        type: 'POST',
        data: form_data,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function(res) {
            if (res && res.status === "success") {
                let html = '';
                let srNo = 1;
                let runningBal = 0;
                let totalAdvGiven = 0;
                let totalDedDone = 0;

                let events = [];
                if (res.allocations && res.allocations.length > 0) {
                    for (let a of res.allocations) {
                        events.push({
                            type: 'allocation',
                            date: a.FormattedDate || '-',
                            rawDate: a.created_at || a.CreatedAt || '',
                            desc: a.AdvanceRemark ? `Advance Allocated (${a.AdvanceRemark})` : 'Salary Advance Allocated',
                            advAmt: parseFloat(a.AdvanceAmount || 0),
                            dedAmt: 0
                        });
                    }
                }
                if (res.deductions && res.deductions.length > 0) {
                    for (let d of res.deductions) {
                        let monthStr = d.MonthYearSalary ? ` (${d.MonthYearSalary})` : '';
                        events.push({
                            type: 'deduction',
                            date: d.FormattedDate || '-',
                            rawDate: d.created_at || d.CreatedAt || d.TransactionDate || '',
                            desc: d.Description ? d.Description : `Monthly Salary Deduction${monthStr}`,
                            advAmt: 0,
                            dedAmt: parseFloat(d.DebitAmount || 0)
                        });
                    }
                }

                // If no detail rows in DB, construct fallback rows from master summary
                if (events.length === 0) {
                    events.push({
                        type: 'allocation',
                        date: 'Initial Record',
                        desc: 'Salary Advance Allocation',
                        advAmt: advAmt,
                        dedAmt: 0
                    });
                    if (dedTotal > 0) {
                        events.push({
                            type: 'deduction',
                            date: 'Deducted To Date',
                            desc: 'Cumulative Monthly Deductions',
                            advAmt: 0,
                            dedAmt: dedTotal
                        });
                    }
                }

                for (let ev of events) {
                    runningBal = runningBal + ev.advAmt - ev.dedAmt;
                    totalAdvGiven += ev.advAmt;
                    totalDedDone += ev.dedAmt;

                    let badgeType = ev.type === 'allocation' 
                        ? `<span class="badge bg-primary-subtle text-primary fw-semibold"><i class="ti ti-plus me-1"></i>Advance Given</span>`
                        : `<span class="badge bg-warning-subtle text-warning-emphasis fw-semibold"><i class="ti ti-minus me-1"></i>Monthly Deduction</span>`;

                    let advText = ev.advAmt > 0 ? `₹ ${ev.advAmt.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}` : '-';
                    let dedText = ev.dedAmt > 0 ? `₹ ${ev.dedAmt.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}` : '-';
                    let balText = `₹ ${runningBal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;

                    html += `<tr>
                        <td class="text-center">${srNo++}</td>
                        <td class="fw-semibold text-dark">${ev.date}</td>
                        <td>${badgeType}</td>
                        <td>${ev.desc}</td>
                        <td class="text-end text-primary fw-semibold">${advText}</td>
                        <td class="text-end text-danger fw-semibold">${dedText}</td>
                        <td class="text-end text-success fw-bold">${balText}</td>
                    </tr>`;
                }

                $("#modalAdvanceDetailTbl tbody").html(html);

                // Table Footer Summary
                let tfootHtml = `<tr>
                    <td colspan="4" class="text-end fw-bold">TOTAL:</td>
                    <td class="text-end text-primary fw-bold">₹ ${totalAdvGiven.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    <td class="text-end text-danger fw-bold">₹ ${totalDedDone.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    <td class="text-end text-success fw-bold">₹ ${runningBal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                </tr>`;
                $("#modalAdvanceDetailTbl tfoot").html(tfootHtml);
            } else {
                let errMsg = (res && res.message) ? res.message : 'No detailed transaction records available';
                $("#modalAdvanceDetailTbl tbody").html(`<tr><td colspan="7" class="text-center text-muted py-4">${errMsg}</td></tr>`);
            }
        },
        error: function(xhr, status, error) {
            console.error("Advance history error:", error, xhr.responseText);
            let errMsg = 'Failed to load transaction history';
            try {
                let parsed = JSON.parse(xhr.responseText);
                if (parsed && parsed.message) errMsg += ': ' + parsed.message;
            } catch(e){}
            $("#modalAdvanceDetailTbl tbody").html(`<tr><td colspan="7" class="text-center text-danger py-4">${errMsg}</td></tr>`);
        }
    });
});

// Export Modal Table to Excel
function exportAdvanceDetailsToExcel(tblid) {
    let empTitle = $("#advDetailModalEmpName").text().replace(/[^a-zA-Z0-9]/g, '_');
    let filename = `Advance_Details_${empTitle}.xlsx`;
    if (typeof XLSX !== 'undefined') {
        let table = document.getElementById(tblid);
        let wb = XLSX.utils.table_to_book(table);
        XLSX.writeFile(wb, filename);
    } else if (typeof exportSalaryToExcel === 'function') {
        exportSalaryToExcel(tblid);
    } else {
        alert("Excel export library not loaded.");
    }
}

// Submit Form Handler
$("#employeeAdvanceForm").submit(function(e) {
    e.preventDefault();
    var routurl = $("body").data("routurl") || "../";

    let $btn = $("#btnSaveAdvance");
    $btn.prop("disabled", true).html('<i class="ti ti-spinner fa-spin me-1"></i> Saving...');

    $.ajax({
        url: routurl + 'modal/hrm_salarymodal.php?action=AddUpdateAdvnceApprovalDetails',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(res) {
            $btn.prop("disabled", false).html('<i class="ti ti-device-floppy me-1"></i> Save Advance');
            if (res.status == "success" || res.status == "Success") {
                if (typeof alertmessage === 'function') {
                    alertmessage("success", res.message || "Advance saved successfully!");
                } else if (typeof Swal !== 'undefined') {
                    Swal.fire("Success", res.message || "Advance saved successfully!", "success");
                } else {
                    alert(res.message || "Advance saved successfully!");
                }
                $("#addAdvanceEmployeeFormModal").modal('hide');
                $("#employeeAdvanceForm")[0].reset();
                let today = new Date().toISOString().split('T')[0];
                $("#AdvanceDate").val(today);
                $("#EmployeeID").val("").trigger("change");

                // Auto reload data instantly without full page refresh!
                loadEmployeeAdvanceData();
            } else {
                if (typeof alertmessage === 'function') {
                    alertmessage("error", res.message || "Error saving advance.");
                } else if (typeof Swal !== 'undefined') {
                    Swal.fire("Error", res.message || "Error saving advance.", "error");
                } else {
                    alert(res.message || "Error saving advance.");
                }
            }
        },
        error: function(err) {
            $btn.prop("disabled", false).html('<i class="ti ti-device-floppy me-1"></i> Save Advance');
            if (typeof alertmessage === 'function') {
                alertmessage("error", 'Error while saving advance.');
            } else {
                alert('Error while saving advance.');
            }
        }
    });
});
</script>
