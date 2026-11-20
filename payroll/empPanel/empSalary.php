<?php
$empid = "";
if (isset($_GET['vw']) == 1) {
    if (isset($_GET['empid'])) {
        $empid = $_GET['empid'];
    }
} else {
    require('header.php');
}

?>
<div class="content">
    <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-4">
        <div class="my-auto mb-2">
            <h2 class="mb-1 fw-bold">My Salary & Payments</h2>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a
                            href="<?php echo $rooturl; ?>empPanel/index.php?k=<?php echo $key; ?>&empid=<?php echo $empid; ?>&accessby=<?php echo $accessby; ?>"><i
                                class="ti ti-smart-home"></i></a>
                    </li>
                    <li class="breadcrumb-item">Employee</li>
                    <li class="breadcrumb-item active text-primary" aria-current="page">Salary</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex my-xl-auto right-content align-items-center flex-wrap gap-2">
            <div class="mb-2">
                <label class="form-label small fw-bold text-muted mb-1">Filter by Year</label>
                <select class="form-select select2 border-primary" id="salaryyearlist"></select>
            </div>
        </div>
    </div>

    <!-- Salary Strip Container -->
    <div id="salaryStripsWrapper" class="salary-strip-container">
        <!-- Dynamic loading of salary cards -->
        <div class="text-center py-5" id="salaryLoader">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Fetching salary records...</p>
        </div>
    </div>
</div>

<?php
if (isset($_GET['vw']) == 1) {

} else {
    require('footer.php');
}
?>

<script type="text/javascript">
    var EmpId = "<?php echo $empid; ?>";
    var currectyear = "<?php echo date('Y'); ?>";

    SalaryYear_callback(function (res) {
        var syear = "";
        var currectyearnew = "";
        if (res.length > 0) {
            syear += "<option value='' selected disabled>Select Year</option>";
            for (var i = 0; i < res.length; i++) {
                syear += "<option value='" + res[i] + "'>" + res[i] + "</option>";
                currectyearnew = res[i];
            }
            $("#salaryyearlist").html(syear);
            if (currectyearnew) {
                $("#salaryyearlist").val(currectyearnew).trigger("change");
            }
        }
    });

    $(document).on("change", "#salaryyearlist", function () {
        var selectedYear = $(this).val();
        $("#salaryStripsWrapper").html('<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>');

        fetchEmployeeSalarywithYear(selectedYear, 'EmployeeID', EmpId, function (res) {
            var sslhtml = "";
            if (res && res.length > 0) {
                res.forEach(function (row) {
                    var monthShort = row.SalaryMonthYear.substring(0, 3).toUpperCase();
                    sslhtml += `
                    <div class="info-strip shadow-sm mb-3">
                        <div class="row align-items-center g-3">
                            <!-- Month Info -->
                            <div class="col-auto">
                                <div class="info-strip-icon">${monthShort}</div>
                            </div>
                            
                            <!-- Main Info -->
                            <div class="col">
                                <div class="row g-3">
                                    <div class="col-md-3 col-6">
                                        <div class="info-strip-label">Month/Year</div>
                                        <div class="info-strip-value">${row.SalaryMonthYear}</div>
                                    </div>
                                    <div class="col-md-3 col-6 text-center">
                                        <div class="info-strip-label">Paid Days</div>
                                        <div class="info-strip-value bg-light rounded-pill px-2 d-inline-block">${row.CalculatedPresentDays}</div>
                                    </div>
                                    
                                    <!-- Earnings -->
                                    <div class="col-md-6 d-none d-md-block">
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="info-strip-label">Basic + HRA</div>
                                                <div class="info-strip-value text-success">₹${(parseFloat(row.BasicSalary) + parseFloat(row.HRA)).toFixed(2)}</div>
                                            </div>
                                            <div class="col-6 text-end">
                                                <div class="info-strip-label">Allowances</div>
                                                <div class="info-strip-value text-success">₹${row.Allowances}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Net Section -->
                            <div class="col-auto text-end border-start ps-4">
                                <div class="strip-net-box">
                                    <div class="info-strip-label">Net Payable</div>
                                    <div class="strip-net-value">₹${row.NetSalary}</div>
                                </div>
                            </div>
                            
                            <!-- Actions -->
                            <div class="col-auto">
                                <button class="strip-action-btn shadow-sm text-danger" onclick="EmployeeSalarySlipCreat(${row.SalaryID});" title="Download Slip">
                                    <i class="ti ti-file-download fs-20"></i>
                                </button>
                            </div>
                        </div>
                    </div>`;
                });
                $("#salaryStripsWrapper").html(sslhtml || '<div class="alert alert-info">No salary records found for this year.</div>');
            } else {
                $("#salaryStripsWrapper").html('<div class="alert alert-info">No salary records found for this year.</div>');
            }
        });
    });
</script>