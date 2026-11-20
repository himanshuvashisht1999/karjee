<?php
if(isset($_GET['vw']) && $_GET['vw'] == 1){
    include("../connection.php");
    include("functions/hrm_FunctionMaster.php");
    include("functions/admin_functionmaster.php");
    include("functions/comanphp_functions.php");
    include("functions/hrm_loginFunctions.php");
    $key = $_GET['k'];
    $key_decoded = base64url_decode($key);
    $key_parts = explode("|", $key_decoded);
    $loginid = $key_parts[0];
    $loginroleid = $key_parts[1];
    $companyid = $key_parts[2];
    $subcompanyid = $key_parts[3];
    $projectid = $key_parts[4];
    $logintype = $key_parts[5];

    $protocol = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https' : 'http';
    $url = basename($_SERVER["PHP_SELF"]);
    $rooturl = $protocol . '://' . $_SERVER['HTTP_HOST'];
    $baseurl = $rooturl . str_replace($url, "", $_SERVER["PHP_SELF"]);
    if ($_SERVER['HTTP_HOST'] == "localhost" || $_SERVER['HTTP_HOST'] == "192.168.1.65") {
        $prefix = $rooturl . "/krf_amanaccounting/labourpayment/";
    } else {
        $prefix = $rooturl . "/labourpayment/";
    }
    $rooturl = $prefix;

    $mdid = "";
    if(isset($_GET['mdid'])){
      $mdid = $_GET['mdid'];
    }
    $dddr = loginmodulesdetails_frommaster($authconn,$companyid,$subcompanyid,$projectid,"moduleid",$mdid);
    $groupid = "";
    if(count($dddr) > 0){
      $groupid = $dddr[0]['groupid'];
    }
}else{
    require('header.php');
}
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');

#card1 {
    font-family: 'Outfit', sans-serif !important;
    background: #ffffff !important;
    border-radius: 16px !important;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03) !important;
    border: 1px solid rgba(226, 232, 240, 0.8) !important;
    overflow: hidden;
}

#card1 .card-header {
    background: linear-gradient(90deg, #f8fafc 0%, #f1f5f9 100%) !important;
    border-bottom: 1px solid #e2e8f0 !important;
    padding: 1.5rem !important;
}

#card1 .card-header h3 {
    font-weight: 700 !important;
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

/* --- Filter & Cards Layout --- */
.filter-collapse-wrapper {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.25rem;
    margin-bottom: 0.75rem;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.01);
}

.filter-toggle-btn {
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    color: #2563eb;
    border: 1px solid #bfdbfe;
    border-radius: 8px;
    padding: 0.5rem 1.25rem;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    box-shadow: 0 2px 4px rgba(37, 99, 235, 0.05);
}

.filter-toggle-btn:hover {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: #fff;
    border-color: #2563eb;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
}

.filter-toggle-btn .toggle-icon {
    transition: transform 0.3s ease;
}

.filter-toggle-btn.collapsed .toggle-icon {
    transform: rotate(-90deg);
}

.search-export-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    align-items: center;
}

/* --- Premium KPI Styling --- */
.kpi-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 1.5rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 1.25rem;
    border: 1px solid rgba(226, 232, 240, 0.8);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: transparent;
    transition: background 0.3s ease;
}

.kpi-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px -10px rgba(0, 0, 0, 0.08);
    border-color: rgba(203, 213, 225, 0.8);
}

.kpi-card.pending-card::before { background: #dc2626; }
.kpi-card.paid-card::before { background: #22c55e; }
.kpi-card.labour-card::before { background: #06b6d4; }
.kpi-card.days-card::before { background: #f59e0b; }

.kpi-icon {
    width: 52px;
    height: 52px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.kpi-card:hover .kpi-icon {
    transform: scale(1.05) rotate(3deg);
}

.kpi-label {
    font-size: 0.72rem;
    color: #64748b;
    text-transform: uppercase;
    font-weight: 700;
    letter-spacing: 0.5px;
    display: block;
    margin-bottom: 0.25rem;
}

.kpi-value {
    font-size: 1.5rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.5px;
}

/* --- Total Sum Strip Layout --- */
.strips-total-wrapper {
    background: linear-gradient(90deg, #f8fafc 0%, #f1f5f9 100%);
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 0.75rem;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02);
}

.strips-total-grid {
    display: grid;
    grid-template-columns: 50px 1.8fr 1.8fr 70px 1fr 1fr 1fr 1fr 1fr 1.2fr;
    padding: 0.85rem 1.25rem;
    align-items: center;
    text-align: center;
    font-size: 0.875rem;
}

/* --- Strip Items Layout --- */
.strips-header-wrapper {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    margin-bottom: 0.5rem;
}

.strips-header {
    display: grid;
    grid-template-columns: 50px 1.8fr 1.8fr 70px 1fr 1fr 1fr 1fr 1fr 1.2fr;
    padding: 0.85rem 1.25rem;
    color: #475569;
    font-size: 0.8rem;
    text-transform: uppercase;
    font-weight: 700;
    letter-spacing: 0.75px;
    align-items: center;
    text-align: center;
}

.wages-strips-container {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    max-height: 600px;
    overflow-y: auto;
    padding-right: 6px;
}

/* Custom Scrollbar */
.wages-strips-container::-webkit-scrollbar {
    width: 6px;
}
.wages-strips-container::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 10px;
}
.wages-strips-container::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}
.wages-strips-container::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

.wages-strip-item {
    background: #ffffff;
    border-radius: 10px;
    padding: 0.85rem 1.25rem;
    display: grid;
    grid-template-columns: 50px 1.8fr 1.8fr 70px 1fr 1fr 1fr 1fr 1fr 1.2fr;
    align-items: center;
    border: 1px solid #e2e8f0;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    text-align: center;
    font-size: 0.875rem;
}

.wages-strip-item:hover {
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.04);
    border-color: #cbd5e1;
    transform: translateY(-2px);
    background-color: #fafbfd;
}

.view-remark-badge, .badge-info.view-attfile {
    background-color: #eff6ff;
    color: #2563eb;
    border: 1px solid #bfdbfe;
    padding: 0.25rem 0.75rem;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 50rem;
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    cursor: pointer;
    box-shadow: 0 1px 2px rgba(37, 99, 235, 0.03);
}

.view-remark-badge:hover, .badge-info.view-attfile:hover {
    background-color: #2563eb;
    color: #ffffff;
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(37, 99, 235, 0.15);
}

/* --- Filter Inputs Styling --- */
#card1 .pendingreportdata {
    height: 44px !important;
    border-radius: 8px !important;
    border: 1px solid #cbd5e1 !important;
    padding: 0.375rem 0.75rem !important;
    font-size: 0.9rem !important;
    color: #334155 !important;
    background-color: #ffffff !important;
    transition: all 0.2s ease;
}
#card1 .pendingreportdata:focus {
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
    outline: 0 !important;
}
#card1 .form-label {
    font-weight: 600 !important;
    color: #475569 !important;
    margin-bottom: 0.4rem !important;
    font-size: 0.85rem !important;
}

/* Select2 overrides */
.select2-container--default .select2-selection--multiple {
    border-radius: 8px !important;
    border: 1px solid #cbd5e1 !important;
    min-height: 44px !important;
    padding: 4px 8px !important;
    transition: all 0.2s ease;
}
.select2-container--default.select2-container--focus .select2-selection--multiple {
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice {
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%) !important;
    border: 1px solid #bfdbfe !important;
    color: #1e40af !important;
    border-radius: 6px !important;
    padding: 2px 8px !important;
    font-size: 0.8rem !important;
    font-weight: 600 !important;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
    color: #1e40af !important;
    margin-right: 4px !important;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
    color: #ef4444 !important;
    background: transparent !important;
}
</style>

<div class="container-fluid px-4 mt-3">
    <!-- Main Card -->
    <div class="card border-0 shadow-sm" id="card1">
        <div class="card-header bg-white py-3 text-center border-bottom">
            <h3 class="mb-0 text-dark fw-bold"><i class="ti ti-report-money me-2 text-primary"></i>Pending Labour Wages Report</h3>
        </div>
        <div class="card-body p-4">
            <!-- Filter Bar -->
            <div class="row justify-content-center g-3">
                <div class="col-md-3">
                    <label for="rmdatefrom" class="form-label">Date From</label>
                    <input type="date" class="form-control pendingreportdata" id="rmdatefrom" />
                </div>
                <div class="col-md-3">
                    <label for="rmdateto" class="form-label">Date To</label>
                    <input type="date" class="form-control pendingreportdata" id="rmdateto" />
                </div>
                <div class="col-md-3">
                    <label for="wageslocationlist" class="form-label">Wages Location</label>
                    <select class="form-select pendingreportdata" id="wageslocationlist"></select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-primary w-100 fw-bold shadow-sm" id="btnGetPendingData" style="height:44px; border-radius:8px;">
                        <i class="fa fa-database me-1"></i> Get Pending Report
                    </button>
                </div>
            </div>

            <!-- KPI Summary Cards -->
            <div class="row g-3 mt-3 mb-3" id="kpiSummaryCards" style="display:none;">
                <div class="col-12 col-sm-6 col-md-3 d-flex">
                    <div class="kpi-card pending-card shadow-sm w-100">
                        <div class="kpi-icon text-danger" style="background: rgba(220,38,38,0.12); color: #dc2626;">
                            <i class="ti ti-currency-rupee fs-2"></i>
                        </div>
                        <div class="kpi-content">
                            <span class="kpi-label">Pending Wages</span>
                            <h3 class="kpi-value mb-0"><span class="text-danger">₹<span class="kpi-pending-amount">0.00</span></span> <small class="text-primary" style="font-size:0.65em;">/ ₹<span class="kpi-total-wages">0.00</span></small></h3>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-3 d-flex">
                    <div class="kpi-card paid-card shadow-sm w-100">
                        <div class="kpi-icon bg-soft-success text-success" style="background: rgba(16,185,129,0.1);">
                            <i class="ti ti-currency-rupee fs-2"></i>
                        </div>
                        <div class="kpi-content">
                            <span class="kpi-label">Paid Amount</span>
                            <h3 class="kpi-value mb-0" style="color:#22c55e;">₹<span class="kpi-paid-amount">0.00</span></h3>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-3 d-flex">
                    <div class="kpi-card labour-card shadow-sm w-100">
                        <div class="kpi-icon bg-soft-info text-info" style="background: rgba(6,182,212,0.1);">
                            <i class="ti ti-users fs-2"></i>
                        </div>
                        <div class="kpi-content">
                            <span class="kpi-label">Labour Count</span>
                            <h3 class="kpi-value mb-0"><span class="text-danger"><span class="kpi-pending-labours">0</span></span> <small class="text-info" style="font-size:0.65em;">/ <span class="kpi-total-labours">0</span></small></h3>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-3 d-flex">
                    <div class="kpi-card days-card shadow-sm w-100">
                        <div class="kpi-icon bg-soft-warning text-warning" style="background: rgba(245,158,11,0.1);">
                            <i class="ti ti-calendar fs-2"></i>
                        </div>
                        <div class="kpi-content">
                            <span class="kpi-label">Worked Days</span>
                            <h3 class="kpi-value mb-0"><span class="text-danger"><span class="kpi-pending-days">0</span></span> <small class="text-warning" style="font-size:0.65em;">/ <span class="kpi-total-days">0</span></small></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filter Area -->
            <div class="row mb-3 mt-4 g-2" id="searchFilterRow" style="display:none;">
                <div class="col-md-12 mb-2">
                    <h5 class="text-muted mb-0">Period: <span class="wagesperiod fw-semibold text-dark"></span></h5>
                </div>
                <div class="col-md-12">
                    <div class="search-export-row">
                        <div class="input-group search-input-group" style="width: 260px;">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa fa-search"></i></span>
                            <input type="search" class="form-control border-start-0" placeholder="Search labour details..." id="pendingsearchsalaryPaytbl">
                        </div>
                        <button class="filter-toggle-btn" type="button" data-bs-toggle="collapse" data-bs-target="#pendingFilterCollapse" aria-expanded="true" aria-controls="pendingFilterCollapse">
                            <i class="fa fa-filter"></i> Filters <i class="fa fa-chevron-down toggle-icon"></i>
                        </button>
                        <div class="ms-auto">
                            <button class="btn btn-sm" id="exportlbpaymentexcel" onclick="gridtoexcel_withfilter_chunk('pendinglabourwagespayment_tbl', 'Pending Labour Wages', 'landscape',function(res){})" style="display:none; background-color: #f26f21; border-color: #f26f21; color: white; font-weight: 600; height: 38px;"><i class="fa fa-download" aria-hidden="true"></i> EXCEL</button>
                        </div>
                    </div>
                </div>
                <!-- Collapsible Filter Section -->
                <div class="col-md-12">
                    <div class="collapse show" id="pendingFilterCollapse">
                        <div class="filter-collapse-wrapper">
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <div class="select2-wrapper" style="width: 260px; min-width: 200px;">
                                    <select class="form-select" id="labourFilterSelect" multiple="multiple" style="display:none; width: 100%;"></select>
                                </div>
                                <div class="select2-wrapper" style="width: 260px; min-width: 200px;">
                                    <select class="form-select" id="periodFilterSelect" multiple="multiple" style="display:none; width: 100%;"></select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Sum Row -->
            <div class="strips-total-wrapper" id="stripsTotalRow" style="display:none;">
                <div class="strips-total-grid">
                    <div></div>
                    <div class="text-start" style="font-weight: 700;">TOTAL SUM</div>
                    <div></div>
                    <div></div>
                    <div>₹<span class="total-wages-sum">0.00</span></div>
                    <div>₹<span class="total-grafting-sum">0.00</span></div>
                    <div>₹<span class="total-seasonal-sum">0.00</span></div>
                    <div>₹<span class="total-overtime-sum">0.00</span></div>
                    <div>₹<span class="total-other-sum">0.00</span></div>
                    <div class="text-end" style="color: #dc2626; font-weight: 800;">₹<span class="total-payable-sum">0.00</span></div>
                </div>
            </div>

            <!-- Responsive Strip Layout -->
            <div class="strips-header-wrapper" id="stripsHeader" style="display:none;">
                <div class="strips-header">
                    <div>S.No</div>
                    <div class="text-start">Labour Name</div>
                    <div>Date Range</div>
                    <div>Days</div>
                    <div>Wages Amount</div>
                    <div>Grafting Inc.</div>
                    <div>Seasonal Inc.</div>
                    <div>Overtime</div>
                    <div>Other Pay</div>
                    <div class="text-end">Payable Amount</div>
                </div>
            </div>
            <div class="wages-strips-container mb-4" id="pendingwagesstripsList" style="display:none;"></div>

            <!-- Hidden Excel Export Helper Table -->
            <table class="table table-bordered d-none" id="pendinglabourwagespayment_tbl">
              <thead class="thead-light">
                <tr class="noexport">
                  <th colspan="14" class="noexport">
                    <h4>Title : Pending Labour Wages</h4>    
                    <h5>Period : <span class="wagesperiod"></span></h5>
                    <h5>Total Wages Amount : <span class ="ttllabourwagesamountlbl"></span></h5>
                    <h5>Total Labours : <span class ="ttllabourcount"></span></h5>
                    <h5>Total Days : <span class ="ttlworkeddayscount"></span></h5>
                  </th>
                </tr>
                <tr>
                    <th>S.No</th>
                    <th>Labour ID</th>
                    <th>Labour Name</th>
                    <th>Date Range</th>
                    <th>Day</th>
                    <th>Wages Amount</th>
                    <th>Incentive Grafting</th>
                    <th>Incentive Seasional</th>
                    <th>Overtime Amount</th>
                    <th>Other Payment</th>
                    <th>Net Amount</th>
                    <th>Paid Amount</th>
                    <th>Balance Amount</th>
                    <th>Payment Status</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
        </div>
    </div>    
</div>

<?php
if(isset($_GET['vw'])==1){

}else{
  require('footer.php'); 
}
?>

<script>
let companyid = $("body").data("companyid");
let subcompanyid = $("body").data("subcompanyid");
let projectid = $("body").data("projectid");

$(document).ready(function() {
    var date = new Date();
    var firstDay = new Date(date.getFullYear(), date.getMonth(), 1);
    var lastDay = new Date(date.getFullYear(), date.getMonth() + 1, 0);
    
    var firstDayStr = firstDay.getFullYear() + "-" + String(firstDay.getMonth() + 1).padStart(2, '0') + "-01";
    var lastDayStr = lastDay.getFullYear() + "-" + String(lastDay.getMonth() + 1).padStart(2, '0') + "-" + String(lastDay.getDate()).padStart(2, '0');
    
    $("#rmdatefrom").val(firstDayStr);
    $("#rmdateto").val(lastDayStr);

    getLabourSalaryLocationList("", "", function(res) {
        var lctlist = "";
        $("#wageslocationlist").empty();
        if (res && res.length > 0) {
            if (res.length > 1) {
                lctlist += "<option value='all'>All Location</option>";
            }
            for (var i = 0; i < res.length; i++) {
                let locName = (res[i].LocationName || '').toLowerCase().trim();
                let locId = (res[i].LocationId || '').toString().toLowerCase().trim();
                if (locName === "all" || locId === "all" || locName === "all location") {
                    continue;
                }
                lctlist += "<option value='" + res[i].LocationId + "'>" + res[i].LocationName + "</option>";
            }
        }
        if (lctlist) {
            $("#wageslocationlist").append(lctlist);
        }
        if (res && res.length == 1) {
            $("#wageslocationlist").prop("selectedIndex", 0);
        } else if (res && res.length > 1) {
            $("#wageslocationlist").val("all");
        }
    });
});

$(document).on("click", "#btnGetPendingData", function () {
    $(".ttllabourwagesamountlbl").html('');
    if ($.fn.select2) {
        if ($('#periodFilterSelect').data('select2')) $('#periodFilterSelect').select2('destroy');
        if ($('#labourFilterSelect').data('select2')) $('#labourFilterSelect').select2('destroy');
    }
    $("#periodFilterSelect").html("").hide();
    $("#labourFilterSelect").html("").hide();
    
    var datefrom = $("#rmdatefrom").val();
    var dateto = $("#rmdateto").val();
    let locationid = $("#wageslocationlist").val();
    
    if (datefrom !== "" && dateto !== "" && locationid !== "" && locationid !== null) {
        $(".wagesperiod").html(changeDateFormatYmd_dMY(datefrom) + " To " + changeDateFormatYmd_dMY(dateto));
        
        let daterange = datefrom + "|" + dateto;
        let attyearmonth = "";
        if (datefrom) {
            let parts = datefrom.split("-");
            attyearmonth = parts[0] + "-" + parts[1];
        }
        
        Swal.fire({
            title: 'Loading Data',
            html: 'Fetching pending wages records...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        fetchKPISummary(attyearmonth, daterange, locationid);

        PendingPaymentLabourWagesData(attyearmonth, daterange, locationid, function (res) {
            Swal.close();
            if (res && res.length > 0) {
                $("#kpiSummaryCards, #searchFilterRow, #exportlbpaymentexcel").show();
                $("#stripsHeader, #pendingwagesstripsList").show();

                let periods = [];
                let periodMap = {};
                let labours = [];
                let labourMap = {};
                
                for (let i = 0; i < res.length; i++) {
                    const row = res[i];
                    if (row.DateFrom && row.DateTo) {
                        let pkey = row.DateFrom + "|" + row.DateTo;
                        if (!periodMap[pkey]) {
                            periodMap[pkey] = { DateFrom: row.DateFrom, DateTo: row.DateTo };
                            periods.push(periodMap[pkey]);
                        }
                    }
                    if (row.EmployeeID && row.EmployeeName) {
                        let lkey = row.EmployeeID;
                        if (!labourMap[lkey]) {
                            labourMap[lkey] = { EmployeeID: row.EmployeeID, EmployeeName: row.EmployeeName };
                            labours.push(labourMap[lkey]);
                        }
                    }
                }

                let periodSelectHtml = "";
                periods.forEach(p => {
                    let formattedPeriod = changeDateFormatYmd_dMY(p.DateFrom) + " to " + changeDateFormatYmd_dMY(p.DateTo);
                    periodSelectHtml += `<option value="${p.DateFrom}|${p.DateTo}">${formattedPeriod}</option>`;
                });
                $("#periodFilterSelect").html(periodSelectHtml).show();

                let labourSelectHtml = "";
                labours.forEach(l => {
                    labourSelectHtml += `<option value="${l.EmployeeID}">${l.EmployeeID} - ${l.EmployeeName}</option>`;
                });
                $("#labourFilterSelect").html(labourSelectHtml).show();

                if ($.fn.select2) {
                    $('#periodFilterSelect').select2({ placeholder: "Select Date Range(s)", allowClear: true, width: '100%' });
                    $('#labourFilterSelect').select2({ placeholder: "Select Labour(s)", allowClear: true, width: '100%' });
                }

                let tblrowv = "";
                let stripsHtml = "";

                for (let i = 0; i < res.length; i++) {
                    const row = res[i];
                    const netSalary = parseFloat(row.NetSalary) || 0;
                    const paidAmount = parseFloat(row.PaidAmount) || 0;
                    const pendingAmount = netSalary - paidAmount;
                    const presentDays = parseFloat(row.CalculatedPresentDays) || 0;

                    let oind = row.otherinfo || [];
                    let uprbtn = "";
                    let attfileId = "";
                    if (Array.isArray(oind) && oind.length > 0) {
                        oind.forEach((v) => {
                            if (v.UpdateRemark && v.UpdateRemark.trim() !== "") {
                                uprbtn += `<span class="view-remark-badge view-remark ms-2" data-remark="${v.UpdateRemark}">View Remark</span>`;
                            }
                            if (v.attFileid) {
                                attfileId += `<span class="view-attfile text-success ms-2" style="cursor:pointer;" data-attfileid="${v.attFileid}" title="View Attendance Excel"><i class="fa fa-file-excel-o fa-lg"></i></span>`;
                            }
                        });
                    }

                    let dateperiodRaw = "";
                    if (row.DateFrom && row.DateTo) {
                        dateperiodRaw = `${changeDateFormatYmd_dMY(row.DateFrom)} to ${changeDateFormatYmd_dMY(row.DateTo)}`;
                    }

                    let paymentStatus = "Pending";
                    if (paidAmount > 0 && pendingAmount > 0) {
                        paymentStatus = "Partial Paid";
                    } else if (pendingAmount <= 0 && netSalary > 0) {
                        paymentStatus = "Paid";
                    } else if (row.PaymentStatus && row.PaymentStatus.trim() !== "") {
                        paymentStatus = row.PaymentStatus;
                    }

                    tblrowv += `
                        <tr data-employeeid='${row.EmployeeID}' data-employeename='${row.EmployeeName}' data-period="${row.DateFrom}|${row.DateTo}">
                            <td>${i + 1}</td>
                            <td>${row.EmployeeID}</td>
                            <td>${row.EmployeeName}</td>
                            <td>${dateperiodRaw}</td>
                            <td>${presentDays}</td>
                            <td>${parseFloat(row.basiclabourpayment || 0).toFixed(2)}</td>
                            <td>${parseFloat(row.incentivegrafting || 0).toFixed(2)}</td>
                            <td>${parseFloat(row.incentiveseasional || 0).toFixed(2)}</td>
                            <td>${parseFloat(row.overtimepayment || 0).toFixed(2)}</td>
                            <td>${parseFloat(row.otherpayment || 0).toFixed(2)}</td>
                            <td>${netSalary.toFixed(2)}</td>
                            <td>${paidAmount.toFixed(2)}</td>
                            <td>${pendingAmount.toFixed(2)}</td>
                            <td>${paymentStatus}</td>
                        </tr>`;

                    const basicWages = parseFloat(row.basiclabourpayment) || 0;
                    stripsHtml += `
                        <div class="wages-strip-item" data-employeeid="${row.EmployeeID}" data-employeename="${row.EmployeeName}" data-period="${row.DateFrom}|${row.DateTo}" data-basicwages="${basicWages}" data-netsalary="${pendingAmount}" data-presentdays="${presentDays}" data-grafting="${row.incentivegrafting || 0}" data-seasonal="${row.incentiveseasional || 0}" data-overtime="${row.overtimepayment || 0}" data-otherpay="${row.otherpayment || 0}">
                            <div class="sno-cell">${i + 1}</div>
                            <div class="labour-name-cell text-start">
                                <span class="fw-semibold text-dark">${row.EmployeeID} - ${row.EmployeeName}</span>
                                <div class="d-inline-flex gap-1">${attfileId}</div>
                            </div>
                            <div>${dateperiodRaw}</div>
                            <div>${presentDays} ${uprbtn}</div>
                            <div>₹${row.basiclabourpayment || 0}</div>
                            <div>₹${row.incentivegrafting || 0}</div>
                            <div>₹${row.incentiveseasional || 0}</div>
                            <div>₹${row.overtimepayment || 0}</div>
                            <div>₹${row.otherpayment || 0}</div>
                            <div class="text-end fw-bold">₹${pendingAmount.toFixed(2)}</div>
                        </div>`;
                }
                
                $("#pendinglabourwagespayment_tbl tbody").html(tblrowv);
                $("#pendingwagesstripsList").html(stripsHtml);
                
                filterLabourWages();
            } else {
                $("#pendinglabourwagespayment_tbl tbody").html(`<tr><td colspan="14" class="text-center text-danger">No wages data available for selected period.</td></tr>`);
                $("#pendingwagesstripsList").html(`<div class="alert alert-warning text-center p-4"><i class="ti ti-info-circle fs-1 d-block mb-2 opacity-50"></i>No wages data available for selected period.</div>`);
                $(".ttllabourwagesamountlbl, .ttllabourcount, .ttlworkeddayscount").html('0');
                $("#stripsHeader, #stripsTotalRow").hide();
                $("#kpiSummaryCards, #searchFilterRow, #pendingwagesstripsList").show();
            }
        });
    }
});

$(document).on("input", "#pendingsearchsalaryPaytbl", filterLabourWages);
$(document).on("change", "#periodFilterSelect, #labourFilterSelect", filterLabourWages);

function filterLabourWages() {
    var searchVal = $("#pendingsearchsalaryPaytbl").val().toLowerCase().trim();
    var periodVal = $("#periodFilterSelect").val();
    var labourVal = $("#labourFilterSelect").val();
    if (labourVal && Array.isArray(labourVal)) {
        labourVal = labourVal.map(v => v.toLowerCase());
    }

    let visibleCount = 0;
    let totalWagesAmount = 0;
    let totalDays = 0;
    let totalBasicWages = 0;
    let totalGrafting = 0;
    let totalSeasonal = 0;
    let totalOvertime = 0;
    let totalOtherPay = 0;

    let visibleStripIdx = 0;
    $(".wages-strip-item").each(function() {
        var empIdAttr = $(this).data("employeeid");
        if (empIdAttr === undefined || empIdAttr === null) return;

        var employeeName = ($(this).data("employeename") || "").toString().toLowerCase();
        var employeeId = empIdAttr.toString().toLowerCase();
        var period = $(this).data("period");
        var netSalary = parseFloat($(this).data("netsalary")) || 0;
        var presentDays = parseFloat($(this).data("presentdays")) || 0;

        var matchesSearch = (employeeName.indexOf(searchVal) > -1 || employeeId.indexOf(searchVal) > -1);
        var matchesPeriod = (!periodVal || periodVal.length === 0 || periodVal.indexOf(period) > -1);
        var matchesLabour = (!labourVal || labourVal.length === 0 || labourVal.indexOf(employeeId) > -1);

        if (matchesSearch && matchesPeriod && matchesLabour) {
            $(this).show();
            visibleStripIdx++;
            visibleCount++;
            totalWagesAmount += netSalary;
            totalDays += presentDays;
            totalBasicWages += parseFloat($(this).data("basicwages")) || 0;
            totalGrafting += parseFloat($(this).data("grafting")) || 0;
            totalSeasonal += parseFloat($(this).data("seasonal")) || 0;
            totalOvertime += parseFloat($(this).data("overtime")) || 0;
            totalOtherPay += parseFloat($(this).data("otherpay")) || 0;
        } else {
            $(this).hide();
        }
    });

    let visibleTableIdx = 0;
    $("#pendinglabourwagespayment_tbl tbody tr").each(function() {
        var empIdAttr = $(this).data("employeeid");
        if (empIdAttr === undefined || empIdAttr === null) return;

        var employeeName = ($(this).data("employeename") || "").toString().toLowerCase();
        var employeeId = empIdAttr.toString().toLowerCase();
        var period = $(this).data("period");

        var matchesSearch = (employeeName.indexOf(searchVal) > -1 || employeeId.indexOf(searchVal) > -1);
        var matchesPeriod = (!periodVal || periodVal.length === 0 || periodVal.indexOf(period) > -1);
        var matchesLabour = (!labourVal || labourVal.length === 0 || labourVal.indexOf(employeeId) > -1);

        if (matchesSearch && matchesPeriod && matchesLabour) {
            $(this).show();
            visibleTableIdx++;
            $(this).find("td:first").text(visibleTableIdx);
        } else {
            $(this).hide();
        }
    });

    $(".ttllabourwagesamountlbl").html(totalWagesAmount.toFixed(2));
    $(".ttllabourcount").html(visibleCount);
    $(".ttlworkeddayscount").html(totalDays);

    $(".total-wages-sum").text(totalBasicWages.toFixed(2));
    $(".total-grafting-sum").text(totalGrafting.toFixed(2));
    $(".total-seasonal-sum").text(totalSeasonal.toFixed(2));
    $(".total-overtime-sum").text(totalOvertime.toFixed(2));
    $(".total-other-sum").text(totalOtherPay.toFixed(2));
    $(".total-payable-sum").text(totalWagesAmount.toFixed(2));

    if (visibleCount > 0) {
        $("#stripsTotalRow").show();
    } else {
        $("#stripsTotalRow").hide();
    }

    let availablePeriods = new Set();
    let availableLabours = new Set();

    $(".wages-strip-item").each(function() {
        var empIdAttr = $(this).data("employeeid");
        if (empIdAttr === undefined || empIdAttr === null) return;
        var employeeName = ($(this).data("employeename") || "").toString().toLowerCase();
        var employeeId = empIdAttr.toString().toLowerCase();
        var period = $(this).data("period");

        var matchesSearch = (employeeName.indexOf(searchVal) > -1 || employeeId.indexOf(searchVal) > -1);
        var matchesPeriod = (!periodVal || periodVal.length === 0 || periodVal.indexOf(period) > -1);
        var matchesLabour = (!labourVal || labourVal.length === 0 || labourVal.indexOf(employeeId) > -1);

        if (matchesSearch && matchesLabour) availablePeriods.add(period);
        if (matchesSearch && matchesPeriod) availableLabours.add(employeeId);
    });

    let updateSelect2 = false;
    if ($("#periodFilterSelect").length > 0) {
        $("#periodFilterSelect option").each(function() {
            let isDisabled = !availablePeriods.has($(this).val());
            if ($(this).prop("disabled") !== isDisabled) {
                $(this).prop("disabled", isDisabled);
                updateSelect2 = true;
            }
        });
    }
    if ($("#labourFilterSelect").length > 0) {
        $("#labourFilterSelect option").each(function() {
            let isDisabled = !availableLabours.has($(this).val().toLowerCase());
            if ($(this).prop("disabled") !== isDisabled) {
                $(this).prop("disabled", isDisabled);
                updateSelect2 = true;
            }
        });
    }

    if (updateSelect2 && $.fn.select2) {
        if ($("#periodFilterSelect").length > 0) {
            $("#periodFilterSelect").select2({ placeholder: "Select Date Range(s)", allowClear: true, width: '100%' });
        }
        if ($("#labourFilterSelect").length > 0) {
            $("#labourFilterSelect").select2({ placeholder: "Select Labour(s)", allowClear: true, width: '100%' });
        }
    }
}

$(document).on("click", ".view-remark", function() {
    let remark = $(this).data("remark") || "No remark available";
    let empNametr = $(this).closest("tr, .wages-strip-item").data("employeename") || "Unknown";
    Swal.fire({
        title: "Attendance Edit Remark",
        html: `<div style="text-align:left;"><p><strong>Labour Name:</strong> ${empNametr}</p><p><strong>Remark:</strong> ${remark}</p></div>`,
        icon: "info",
        confirmButtonText: "Close"
    });
});

$(document).on("click", ".view-attfile", function() {
    var form_data = new FormData();
    var routurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    let attfileid = $(this).data("attfileid") || "0";
    
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("attfileid", attfileid);

    $.ajax({
        type: "POST",
        url: routurl + "modal/hrm_laboursalarymodal.php?action=GetAttFileData",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'JSON',
        success: function(res) {
            if(res && res.length > 0){
                for (var i = 0; i < res.length; i++) {
                     openPopup(routurl + "preview.php?f=" + res[i].file_path, "Labour Attendance");
                }
            }
        }
    });
});

function fetchKPISummary(attyearmonth, daterange, locationid) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");

    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("attyearmonth", attyearmonth);
    form_data.append("daterange", daterange);
    form_data.append("locationid", locationid);

    $.ajax({
        type: "POST",
        url: baseurl + "modal/hrm_laboursalarymodal.php?action=GetKPISummaryData",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'JSON',
        success: function (res) {
            if (res) {
                $(".kpi-pending-amount").html(parseFloat(res.grandTotalPending).toFixed(2));
                $(".kpi-total-wages").html(parseFloat(res.grandTotalWages).toFixed(2));
                $(".kpi-paid-amount").html(parseFloat(res.grandTotalPaid).toFixed(2));
                $(".kpi-pending-labours").html(res.pendingLabourCount);
                $(".kpi-total-labours").html(res.grandTotalLabours);
                $(".kpi-pending-days").html(res.pendingDays);
                $(".kpi-total-days").html(res.grandTotalDays);
            }
        }
    });
}

$(document).on("click", "input[type='date']", function() {
    if (typeof this.showPicker === 'function') {
        try {
            this.showPicker();
        } catch (e) {
            console.error(e);
        }
    }
});
</script>