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

.card {
    font-family: 'Outfit', sans-serif !important;
    background: #ffffff !important;
    border-radius: 16px !important;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03) !important;
    border: 1px solid rgba(226, 232, 240, 0.8) !important;
    overflow: hidden;
}

.card-header, .card-body {
    font-family: 'Outfit', sans-serif !important;
}

/* --- Collapsible Filter Section --- */
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
    height: 44px;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    box-shadow: 0 2px 4px rgba(37, 99, 235, 0.05);
}

.filter-toggle-btn:hover {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: #ffffff;
    border-color: #2563eb;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
}

.filter-toggle-btn .toggle-icon {
    font-size: 0.75rem;
    transition: transform 0.2s;
}

.filter-toggle-btn[aria-expanded="true"] .toggle-icon {
    transform: rotate(180deg);
}

.search-export-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.select2-container--default .select2-selection--multiple {
    border: 1px solid #cbd5e1 !important;
    border-radius: 8px !important;
    min-height: 44px !important;
    padding: 4px 8px !important;
    transition: all 0.2s ease;
}
.select2-container--default.select2-container--focus .select2-selection--multiple {
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
}
.select2-container--default .select2-search--inline .select2-search__field {
    text-align: center !important;
}
.select2-results__option[aria-disabled="true"] {
    display: none !important;
}

/* --- Premium Styling --- */
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

.kpi-card.paid-card::before { background: #22c55e; }
.kpi-card.pending-card::before { background: #f97316; }
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

.bg-soft-primary { background-color: rgba(59, 130, 246, 0.1); }
.bg-soft-success { background-color: rgba(16, 185, 129, 0.1); }
.bg-soft-warning { background-color: rgba(245, 158, 11, 0.1); }
.bg-soft-info { background-color: rgba(6, 182, 212, 0.1); }

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

/* --- Strips Total Sum Layout --- */
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
    grid-template-columns: 50px 1.8fr 1.6fr 1.2fr 1fr 1fr 1fr 1.1fr 1fr 0.9fr;
    padding: 0.85rem 1.25rem;
    align-items: center;
    text-align: center;
    font-size: 0.875rem;
}

@media (max-width: 991.98px) {
    .strips-total-grid {
        grid-template-columns: 1fr !important;
        text-align: center !important;
        gap: 0.5rem;
        padding: 1rem;
    }
    .strips-total-grid > div:not(.total-sum-mobile-visible) {
        display: none;
    }
    .strips-total-grid .total-sum-mobile-visible {
        display: block !important;
    }
}

/* --- Paid Wages Strip Design --- */
.strips-header-wrapper {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    margin-bottom: 0.5rem;
}

.strips-header {
    display: grid;
    grid-template-columns: 50px 1.8fr 1.6fr 1.2fr 1fr 1fr 1fr 1.1fr 1fr 0.9fr;
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
    grid-template-columns: 50px 1.8fr 1.6fr 1.2fr 1fr 1fr 1fr 1.1fr 1fr 0.9fr;
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

.wages-strip-item.highlight-diff {
    background-color: #fff5f5 !important;
    border-color: #feb2b2 !important;
}
.wages-strip-item.highlight-diff:hover {
    background-color: #fed7d7 !important;
    border-color: #fc8181 !important;
}

.wages-strip-item .labour-name-cell {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.wages-strip-item .payable-amount-cell {
    font-weight: 700;
    color: #0f172a;
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

.search-input-group {
    max-width: 350px;
}

/* Mobile responsive layout */
@media (max-width: 991.98px) {
    .strips-header-wrapper {
        display: none !important;
    }
    .wages-strip-item {
        grid-template-columns: 1fr !important;
        text-align: left !important;
        padding: 1.25rem !important;
        gap: 0.75rem !important;
    }
    .wages-strip-item .sno-cell {
        display: none;
    }
    .wages-strip-item .labour-name-cell {
        font-size: 1.1rem;
        margin-bottom: 0.25rem;
    }
    .wages-strip-item .mobile-field {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px dashed #f1f5f9;
        padding-bottom: 0.5rem;
    }
    .wages-strip-item .mobile-field:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    .wages-strip-item .mobile-label {
        font-size: 0.75rem;
        color: #64748b;
        text-transform: uppercase;
        font-weight: 700;
    }
}

/* --- Filter Inputs Styling --- */
.paidreportdata {
    height: 44px !important;
    border-radius: 8px !important;
    border: 1px solid #cbd5e1 !important;
    padding: 0.375rem 0.75rem !important;
    font-size: 0.9rem !important;
    color: #334155 !important;
    background-color: #ffffff !important;
    transition: all 0.2s ease;
}
.paidreportdata:focus {
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
    outline: 0 !important;
}
.form-label {
    font-weight: 600 !important;
    color: #475569 !important;
    margin-bottom: 0.4rem !important;
    font-size: 0.85rem !important;
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
            <h3 class="mb-0 text-dark fw-bold"><i class="ti ti-report-money me-2 text-warning"></i>Partial Paid Labour Wages Report</h3>
        </div>
        <div class="card-body p-4">
            <!-- Filter Bar -->
            <div class="row justify-content-center g-3">
                <div class="col-md-3">
                    <label for="rmdatefrom" class="form-label">Date From</label>
                    <input type="date" class="form-control paidreportdata" id="rmdatefrom" />
                </div>
                <div class="col-md-3">
                    <label for="rmdateto" class="form-label">Date To</label>
                    <input type="date" class="form-control paidreportdata" id="rmdateto" />
                </div>
                <div class="col-md-3">
                    <label for="wageslocationlist" class="form-label">Wages Location</label>
                    <select class="form-select paidreportdata" id="wageslocationlist"></select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-primary w-100 fw-bold shadow-sm" id="btnGetPaidData" style="height:44px; border-radius:8px;">
                        <i class="fa fa-database me-1"></i> Get Partial Report
                    </button>
                </div>
            </div>
                    <div class="text-center" id="loadingdiv"></div>
                    
                    <!-- KPI Summary Cards -->
                    <div class="row g-3 mt-3 mb-3" id="kpiSummaryCards" style="display:none;">
                        <div class="col-12 col-sm-6 col-md-3 d-flex">
                            <div class="kpi-card paid-card shadow-sm w-100">
                                <div class="kpi-icon bg-soft-success text-success">
                                    <i class="ti ti-currency-rupee fs-2"></i>
                                </div>
                                <div class="kpi-content">
                                    <span class="kpi-label">Paid Amount</span>
                                    <h3 class="kpi-value mb-0"><span class="text-success">₹<span class="kpi-paid-amount">0.00</span></span> <small class="text-primary" style="font-size:0.65em;">/ ₹<span class="kpi-total-wages">0.00</span></small></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-3 d-flex">
                            <div class="kpi-card pending-card shadow-sm w-100">
                                <div class="kpi-icon" style="background:rgba(249,115,22,0.12); color:#f97316;">
                                    <i class="ti ti-currency-rupee fs-2"></i>
                                </div>
                                <div class="kpi-content">
                                    <span class="kpi-label">Pending Amount</span>
                                    <h3 class="kpi-value mb-0" style="color:#f97316;">₹<span class="kpi-pending-amount">0.00</span></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-3 d-flex">
                            <div class="kpi-card labour-card shadow-sm w-100">
                                <div class="kpi-icon bg-soft-info text-info">
                                    <i class="ti ti-users fs-2"></i>
                                </div>
                                <div class="kpi-content">
                                    <span class="kpi-label">Labours</span>
                                    <h3 class="kpi-value mb-0 text-info"><span class="kpi-total-labours">0</span></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-3 d-flex">
                            <div class="kpi-card days-card shadow-sm w-100">
                                <div class="kpi-icon bg-soft-warning text-warning">
                                    <i class="ti ti-calendar fs-2"></i>
                                </div>
                                <div class="kpi-content">
                                    <span class="kpi-label">Worked Days</span>
                                    <h3 class="kpi-value mb-0 text-warning"><span class="kpi-total-days">0</span></h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Search and Filter Area -->
                    <div class="row mb-3 mt-4 g-2" id="searchFilterRow" style="display:none;">
                        <!-- Period Label + Search + Excel (always visible) -->
                        <div class="col-md-12 mb-2">
                            <h5 class="text-muted mb-0">Period: <span class="wagesperiod fw-semibold text-dark"></span></h5>
                        </div>
                        <div class="col-md-12">
                            <div class="search-export-row">
                                <div class="input-group search-input-group" style="width: 240px;">
                                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa fa-search"></i></span>
                                    <input type="search" class="form-control border-start-0" placeholder="Search labour details..." id="paidsearchsalaryPaytbl">
                                </div>
                                <button class="filter-toggle-btn" type="button" data-bs-toggle="collapse" data-bs-target="#paidFilterCollapse" aria-expanded="true" aria-controls="paidFilterCollapse">
                                    <i class="fa fa-filter"></i> Filters <i class="fa fa-chevron-down toggle-icon"></i>
                                </button>
                                <div class="ms-auto">
                                    <button class="btn btn-sm" id="exportlbpaymentexcel" onclick="gridtoexcel_withfilter_chunk('paidlabourwagespayment_tbl', 'Partial Paid Labour Wages', 'landscape',function(res){})" style="display:none; background-color: #f26f21; border-color: #f26f21; color: white; font-weight: 600; height: 44px;"><i class="fa fa-download" aria-hidden="true"></i> EXCEL</button>
                                </div>
                            </div>
                        </div>
                        <!-- Collapsible Filter Section -->
                        <div class="col-md-12">
                            <div class="collapse show" id="paidFilterCollapse">
                                <div class="filter-collapse-wrapper">
                                    <div class="d-flex flex-wrap gap-2 align-items-center">
                                        <div class="select2-wrapper" style="width: 240px; min-width: 200px;">
                                            <select class="form-select" id="labourFilterSelect" multiple="multiple" style="display:none; width: 100%;"></select>
                                        </div>
                                        <div class="select2-wrapper" style="width: 240px; min-width: 200px;">
                                            <select class="form-select" id="periodFilterSelect" multiple="multiple" style="display:none; width: 100%;"></select>
                                        </div>
                                        <div class="select2-wrapper" style="width: 240px; min-width: 200px;">
                                            <select class="form-select" id="paymentDateFilterSelect" multiple="multiple" style="display:none; width: 100%;"></select>
                                        </div>
                                        <div class="select2-wrapper" style="width: 240px; min-width: 200px;">
                                            <select class="form-select" id="updatedByFilterSelect" multiple="multiple" style="display:none; width: 100%;"></select>
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
                            <div class="text-start total-sum-mobile-visible" style="font-weight: 700;">TOTAL SUM</div>
                            <div></div>
                            <div></div>
                            <div class="total-sum-mobile-visible" style="color: #0f172a;"><span class="d-lg-none fw-bold">Net Payable: </span>₹<span class="total-net-payable-sum">0.00</span></div>
                            <div class="total-sum-mobile-visible" style="color: #10b981;"><span class="d-lg-none fw-bold">Paid Amount: </span>₹<span class="total-paid-amount-sum">0.00</span></div>
                            <div class="total-sum-mobile-visible" style="color: #dc2626; font-weight:800;"><span class="d-lg-none fw-bold">Pending: </span>₹<span class="total-pending-amount-sum">0.00</span></div>
                            <div></div>
                            <div></div>
                            <div></div>
                        </div>
                    </div>

                    <!-- Responsive Strip Layout -->
                    <div class="strips-header-wrapper" id="stripsHeader" style="display:none;">
                        <div class="strips-header">
                            <div>S.No</div>
                            <div class="text-start">Labour Name</div>
                            <div>Duration</div>
                            <div>Days/Perday</div>
                            <div>Net Payable</div>
                            <div>Paid Amount</div>
                            <div>Pending Amount</div>
                            <div>Payment Date</div>
                            <div>Updated By</div>
                            <div class="text-end">Paid Ref. File</div>
                        </div>
                    </div>
                    <div class="wages-strips-container mb-4" id="paidwagesstripsList" style="display:none;">
                        <!-- Strip list items injected here -->
                    </div>

                    <!-- Hidden Excel Export Helper Table -->
                    <table class="table table-bordered d-none" id="paidlabourwagespayment_tbl">
                      <thead class="thead-light">
                        <tr class="noexport">
                          <th colspan="10" class="noexport">
                            <h4>Title : Partial Paid Labour Wages</h4>    
                            <h5>Period : <span class="wagesperiod"></span></h5>
                            <h5>Wages Amount : <span class ="ttllabourbasicwageslbl"></span></h5>
                            <h5>Paid Amount : <span class ="ttllabourwagesamountlbl"></span></h5>
                            <h5>Labours : <span class ="ttllabourcount"></span></h5>
                            <h5>Worked Days : <span class ="ttlworkeddayscount"></span></h5>
                          </th>
                        </tr>
                        <tr class="d-none">
                          <th colspan="10">
                            <h4>Title : Partial Paid Labour Wages</h4>    
                          </th>
                        </tr>
                        <tr class="d-none">
                          <th colspan="10">
                            <h5>Period : <span class="wagesperiod"></span></h5>
                          </th>
                        </tr>
                        <tr class="d-none">
                          <th colspan="10">
                            <h5>Wages Amount : <span class ="ttllabourbasicwageslbl"></span></h5>
                          </th>
                        </tr>
                        <tr class="d-none">
                          <th colspan="10">
                            <h5>Paid Amount : <span class ="ttllabourwagesamountlbl"></span></h5>
                          </th>
                        </tr>
                        <tr class="d-none">
                          <th colspan="10">
                            <h5>Labours : <span class ="ttllabourcount"></span></h5>
                          </th>
                        </tr>
                        <tr class="d-none">
                          <th colspan="10">
                            <h5>Worked Days : <span class ="ttlworkeddayscount"></span></h5>
                          </th>
                        </tr>
                        <tr>
                          <th>S.No</th>
                          <th>Labour Name</th>
                          <th>Duration</th>
                          <th>Days/Perday</th>
                          <th>Net Payable</th>
                          <th>Paid Amount</th>
                          <th>Pending Amount</th>
                          <th>Payment Date</th>
                          <th>Updated By</th>
                          <th>Paid Ref. File</th>
                        </tr>
                      </thead>
                      <tbody>
                      </tbody>
                    </table>
        </div>
    </div>
</div>

<?php 
if(!isset($_GET['vw'])){
  require('footer.php'); 
}
?>

<script>
function formatDateDMY2Digit(dateStr) {
    if (!dateStr || dateStr === "0000-00-00" || dateStr === "null") return "";
    let parts = dateStr.split("-");
    if (parts.length === 3) {
        let year = parts[0].substring(2); // last 2 digits of year
        let month = parts[1];
        let day = parts[2];
        return `${day}-${month}-${year}`;
    }
    return dateStr;
}

// Initialize by showing the first card
let companyid =  $("body").data("companyid");
let subcompanyid =  $("body").data("subcompanyid");
let projectid =  $("body").data("projectid");

// Set default date range to the current month
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
        if (res.length > 0) {
            if (res.length > 1) {
                lctlist += "<option value='all'>All Location</option>";
            }
            for (var i = 0; i < res.length; i++) {
                let locName = res[i].LocationName.toLowerCase().trim();
                let locId = res[i].LocationId.toString().toLowerCase().trim();
                if (locName === "all" || locId === "all" || locName === "all location") {
                    continue;
                }
                lctlist += "<option value='" + res[i].LocationId + "'>" + res[i].LocationName + "</option>";
            }
        }
        if (lctlist) {
            $("#wageslocationlist").append(lctlist);
        }
        if (res.length == 1) {
            $("#wageslocationlist").prop("selectedIndex", 0);
        } else if (res.length > 1) {
            $("#wageslocationlist").val("all");
        }
    });
});

$(document).on("click", "#btnGetPaidData", function () {
    $(".kpi-paid-amount").html('');
    if ($.fn.select2) {
        if ($('#periodFilterSelect').data('select2')) {
            $('#periodFilterSelect').select2('destroy');
        }
        if ($('#labourFilterSelect').data('select2')) {
            $('#labourFilterSelect').select2('destroy');
        }
        if ($('#paymentDateFilterSelect').data('select2')) {
            $('#paymentDateFilterSelect').select2('destroy');
        }
        if ($('#updatedByFilterSelect').data('select2')) {
            $('#updatedByFilterSelect').select2('destroy');
        }
    }
    $("#periodFilterSelect").html("").hide();
    $("#labourFilterSelect").html("").hide();
    $("#paymentDateFilterSelect").html("").hide();
    $("#updatedByFilterSelect").html("").hide();
    var datefrom = $("#rmdatefrom").val();
    var dateto = $("#rmdateto").val();
    let locationid = $("#wageslocationlist").val();
    
    if (datefrom !== "" && dateto !== "" && locationid !== "" && locationid !== null) {
        $(".wagesperiod").html(changeDateFormatYmd_dMY(datefrom) + " To " + changeDateFormatYmd_dMY(dateto));
        // let loadingMsg = $("<div id='loadingMsg' class='alert alert-danger text-center p-3' style='margin-top:16px;'>Loading labour wages data...</div>");
        // $("#loadingdiv").html(loadingMsg);
        
        let daterange = datefrom + "|" + dateto;
        let attyearmonth = "";
        if (datefrom) {
            let parts = datefrom.split("-");
            attyearmonth = parts[0] + "-" + parts[1];
        }
        
        Swal.fire({
            title: 'Loading Data',
            html: 'Please wait while we fetch the partially paid wages...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Fetch KPI summary independently
        fetchKPISummary(attyearmonth, daterange, locationid);

        PaidPaymentLabourWagesData(attyearmonth, daterange, locationid, function (res) {
            console.log(res);
            Swal.close();
            
            // Filter only partially paid records (NetSalary > PaidAmount)
            let filteredRes = [];
            if (res && res.length > 0) {
                filteredRes = res.filter(row => {
                    const netSalary = parseFloat(row.NetSalary) || 0;
                    const paidAmount = parseFloat(row.PaidAmount) || 0;
                    return (netSalary - paidAmount) > 0.01;
                });
            }
            res = filteredRes;

            if (res && res.length > 0) {
                //$("#loadingdiv").html('').addClass('d-none');
                $("#kpiSummaryCards, #searchFilterRow, #exportlbpaymentexcel").show();
                $("#stripsHeader, #paidwagesstripsList").show();

                // Extract unique periods, labours, payment dates, and updated_by
                let periods = [];
                let periodMap = {};
                let labours = [];
                let labourMap = {};
                let paymentDates = [];
                let paymentDateMap = {};
                let updatedByList = [];
                let updatedByMap = {};
                
                for (let i = 0; i < res.length; i++) {
                    const row = res[i];
                    if (row.DateFrom && row.DateTo) {
                        let pkey = row.DateFrom + "|" + row.DateTo;
                        if (!periodMap[pkey]) {
                            periodMap[pkey] = {
                                DateFrom: row.DateFrom,
                                DateTo: row.DateTo
                            };
                            periods.push(periodMap[pkey]);
                        }
                    }
                    if (row.EmployeeID && row.EmployeeName) {
                        let lkey = row.EmployeeID;
                        if (!labourMap[lkey]) {
                            labourMap[lkey] = {
                                EmployeeID: row.EmployeeID,
                                EmployeeName: row.EmployeeName
                            };
                            labours.push(labourMap[lkey]);
                        }
                    }
                    if (row.PaymentDate) {
                        let pdkey = row.PaymentDate;
                        if (!paymentDateMap[pdkey]) {
                            paymentDateMap[pdkey] = true;
                            paymentDates.push(pdkey);
                        }
                    }
                    if (row.updated_by && row.updated_by.trim() !== "") {
                        let rawUb = row.updated_by.trim();
                        let ubKey = rawUb.toUpperCase();
                        if (!updatedByMap[ubKey]) {
                            let displayUb = rawUb.charAt(0).toUpperCase() + rawUb.slice(1);
                            updatedByMap[ubKey] = displayUb;
                            updatedByList.push({ key: ubKey, display: displayUb });
                        }
                    }
                }

                // Populate Period dropdown
                let periodSelectHtml = "";
                periods.forEach(p => {
                    let formattedPeriod = changeDateFormatYmd_dMY(p.DateFrom) + " to " + changeDateFormatYmd_dMY(p.DateTo);
                    periodSelectHtml += `<option value="${p.DateFrom}|${p.DateTo}">${formattedPeriod}</option>`;
                });
                $("#periodFilterSelect").html(periodSelectHtml).show();

                // Populate Labour dropdown
                let labourSelectHtml = "";
                labours.forEach(l => {
                    labourSelectHtml += `<option value="${l.EmployeeID}">${l.EmployeeID} - ${l.EmployeeName}</option>`;
                });
                $("#labourFilterSelect").html(labourSelectHtml).show();

                // Populate Payment Date dropdown
                let pdSelectHtml = "";
                paymentDates.forEach(pd => {
                    let formatted = formatDateDMY2Digit(pd);
                    pdSelectHtml += `<option value="${pd}">${formatted}</option>`;
                });
                $("#paymentDateFilterSelect").html(pdSelectHtml).show();

                // Populate Updated By dropdown
                updatedByList.sort((a, b) => a.display.localeCompare(b.display));
                let ubSelectHtml = "";
                updatedByList.forEach(ub => {
                    ubSelectHtml += `<option value="${ub.key}">${ub.display}</option>`;
                });
                $("#updatedByFilterSelect").html(ubSelectHtml).show();

                if ($.fn.select2) {
                    $('#periodFilterSelect').select2({
                        placeholder: "Select Date Range(s)",
                        allowClear: true,
                        width: '100%'
                    });
                    $('#labourFilterSelect').select2({
                        placeholder: "Select Labour(s)",
                        allowClear: true,
                        width: '100%'
                    });
                    $('#paymentDateFilterSelect').select2({
                        placeholder: "Payment Date(s)",
                        allowClear: true,
                        width: '100%'
                    });
                    $('#updatedByFilterSelect').select2({
                        placeholder: "Updated By",
                        allowClear: true,
                        width: '100%'
                    });
                }

                let tblrowv = "";
                let stripsHtml = "";

                for (let i = 0; i < res.length; i++) {
                    const row = res[i];
                    const netSalary = parseFloat(row.NetSalary) || 0;
                    const presentDays = parseFloat(row.CalculatedPresentDays) || 0;

                    let oind = row.otherinfo || [];
                    let uprbtn = "";
                    let attfileId = "";
                    if (Array.isArray(oind) && oind.length > 0) {
                        oind.forEach((v) => {
                            if (v.UpdateRemark && v.UpdateRemark.trim() !== "") {
                                uprbtn += `
                                    <span class="view-remark-badge view-remark ms-2" 
                                        data-remark="${v.UpdateRemark}">
                                        View Remark
                                    </span>`;
                            }

                            if (v.attFileid) {
                                attfileId += `
                                    <span class="view-attfile text-success ms-2" style="cursor:pointer;" 
                                        data-attfileid="${v.attFileid}" title="View Attendance Excel">
                                        <i class="fa fa-file-excel-o fa-lg"></i>
                                    </span>`;
                            }
                        });
                    }

                    // Date period formatted
                    let dateperiodRaw = "";
                    if (row.DateFrom && row.DateTo) {
                        dateperiodRaw = `${changeDateFormatYmd_dMY(row.DateFrom)} to ${changeDateFormatYmd_dMY(row.DateTo)}`;
                    }

                    const paidAmount = parseFloat(row.PaidAmount) || 0;
                    const paymentDateFormatted = formatDateDMY2Digit(row.PaymentDate);
                    
                    let rawUpdatedBy = (row.updated_by || "").trim();
                    let updatedByKey = rawUpdatedBy.toUpperCase();
                    let updatedByDisplay = rawUpdatedBy ? (rawUpdatedBy.charAt(0).toUpperCase() + rawUpdatedBy.slice(1)) : "-";

                    // Paid Receipt Green Button Badge
                    let refFileHtml = "";
                    if (row.PaymentReferenceFilepath && row.PaymentReferenceFilepath.trim() !== "") {
                        refFileHtml = `
                            <span class="viewpaidreceipt text-success" 
                                data-paidreceipt="${row.PaymentReferenceFilepath}"
                                style="cursor: pointer;" title="View Paid Receipt">
                                <i class="fa fa-file fa-lg"></i>
                            </span>`;
                    } else {
                        refFileHtml = `
                            <span class="text-muted" title="Paid (No Ref. File)">
                                <i class="fa fa-check-circle fa-lg"></i>
                            </span>`;
                    }

                    // Table rows (hidden in DOM, but used by excel export)
                    tblrowv += `
                        <tr data-employeeid='${row.EmployeeID}' data-employeename='${row.EmployeeName}' data-period="${row.DateFrom}|${row.DateTo}" data-paymentdate="${row.PaymentDate || ''}" data-updatedby="${updatedByKey}">
                            <td>${i + 1}</td>
                            <td>${row.EmployeeID}-${row.EmployeeName}</td>
                            <td>${dateperiodRaw}</td>
                            <td>${presentDays} / ${parseFloat(row.PayPerDay).toFixed(2)}</td>
                            <td>${netSalary.toFixed(2)}</td>
                            <td>${paidAmount.toFixed(2)}</td>
                            <td style="color:#dc2626; font-weight:600;">${(netSalary - paidAmount).toFixed(2)}</td>
                            <td>${paymentDateFormatted}</td>
                            <td>${updatedByDisplay}</td>
                            <td>${row.PaymentReferenceFilepath || 'Paid'}</td>
                        </tr>`;

                    const basicWages = parseFloat(row.basiclabourpayment) || 0;

                    // Responsive Strip layout
                    stripsHtml += `
                        <div class="wages-strip-item highlight-diff" data-employeeid="${row.EmployeeID}" data-employeename="${row.EmployeeName}" data-period="${row.DateFrom}|${row.DateTo}" data-basicwages="${basicWages}" data-netsalary="${netSalary}" data-paidamount="${paidAmount}" data-presentdays="${presentDays}" data-paymentdate="${row.PaymentDate || ''}" data-updatedby="${updatedByKey}">
                            <div class="sno-cell">${i + 1}</div>
                            <div class="labour-name-cell text-start">
                                <span class="fw-semibold text-dark">${row.EmployeeID}-${row.EmployeeName}</span>
                                <div class="d-inline-flex gap-1">${attfileId}</div>
                            </div>
                            <div class="mobile-field">
                                <span class="mobile-label d-lg-none">Duration:</span>
                                <span>${dateperiodRaw}</span>
                            </div>
                            <div class="mobile-field">
                                <span class="mobile-label d-lg-none">Days/Perday:</span>
                                <span>${presentDays} / ${parseFloat(row.PayPerDay).toFixed(2)} ${uprbtn}</span>
                            </div>
                            <div class="mobile-field">
                                <span class="mobile-label d-lg-none">Net Payable:</span>
                                <span>₹${netSalary.toFixed(2)}</span>
                            </div>
                            <div class="mobile-field">
                                <span class="mobile-label d-lg-none">Paid Amount:</span>
                                <span>₹${paidAmount.toFixed(2)}</span>
                            </div>
                            <div class="mobile-field">
                                <span class="mobile-label d-lg-none">Pending Amount:</span>
                                <span style="color:#dc2626; font-weight:700;">₹${(netSalary - paidAmount).toFixed(2)}</span>
                            </div>
                            <div class="mobile-field">
                                <span class="mobile-label d-lg-none">Payment Date:</span>
                                <span>${paymentDateFormatted}</span>
                            </div>
                            <div class="mobile-field">
                                <span class="mobile-label d-lg-none">Updated By:</span>
                                <span>${updatedByDisplay}</span>
                            </div>
                            <div class="mobile-field text-end">
                                <span class="mobile-label d-lg-none">Paid Ref. File:</span>
                                <span>${refFileHtml}</span>
                            </div>
                        </div>`;
                }
                
                $("#paidlabourwagespayment_tbl tbody").html(tblrowv);
                $("#paidwagesstripsList").html(stripsHtml);

                filterLabourWages();
            } else {
                $("#paidlabourwagespayment_tbl tbody").html(
                    `<tr><td colspan="10" class="text-center text-danger">
                        No partial paid wages data available for selected period.
                    </td></tr>`
                );
                $("#paidwagesstripsList").html(
                    `<div class="alert alert-warning text-center p-4">
                        <i class="ti ti-info-circle fs-1 d-block mb-2 opacity-50"></i>
                        No partial paid wages data available for selected period.
                    </div>`
                );
                $(".kpi-paid-amount, .kpi-total-wages, .kpi-pending-amount, .kpi-total-labours, .kpi-total-days").html('0');
                $("#stripsHeader").hide();
                $("#stripsTotalRow").hide();
                $("#kpiSummaryCards, #searchFilterRow").show();
                $("#paidwagesstripsList").show();
            }
        });
    }
});

$(document).on("input", "#paidsearchsalaryPaytbl", function() {
    filterLabourWages();
});

$(document).on("change", "#periodFilterSelect", function() {
    filterLabourWages();
});

$(document).on("change", "#labourFilterSelect", function() {
    filterLabourWages();
});

$(document).on("change", "#paymentDateFilterSelect", function() {
    filterLabourWages();
});

$(document).on("change", "#updatedByFilterSelect", function() {
    filterLabourWages();
});

// Global totals for KPI display
let grandTotalWages = 0;
let grandTotalPaid = 0;
let grandTotalPending = 0;
let grandTotalLabours = 0;
let grandTotalPaidLabours = 0;
let grandTotalDays = 0;
let grandTotalPaidDays = 0;

// Independent KPI function - NOT connected to grid filters
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
                // Update KPI cards
                $(".kpi-paid-amount").html(parseFloat(res.grandTotalPaid).toFixed(2));
                $(".kpi-total-wages").html(parseFloat(res.grandTotalWages).toFixed(2));
                $(".kpi-pending-amount").html(parseFloat(res.grandTotalPending).toFixed(2));
                $(".kpi-paid-labours").html(res.paidLabourCount);
                $(".kpi-total-labours").html(res.grandTotalLabours);
                $(".kpi-paid-days").html(res.paidDays);
                $(".kpi-total-days").html(res.partialTotalDays);
            }
        }
    });
}

function filterLabourWages() {
    var searchVal = $("#paidsearchsalaryPaytbl").val().toLowerCase().trim();
    var periodVal = $("#periodFilterSelect").val(); // Array or null
    var labourVal = $("#labourFilterSelect").val(); // Array or null
    if (labourVal && Array.isArray(labourVal)) {
        labourVal = labourVal.map(v => v.toLowerCase());
    }
    var paymentDateVal = $("#paymentDateFilterSelect").val(); // Array or null
    var updatedByVal = $("#updatedByFilterSelect").val(); // Array or null
    if (updatedByVal && Array.isArray(updatedByVal)) {
        updatedByVal = updatedByVal.map(v => v.toString().toUpperCase().trim());
    }

    let visibleCount = 0;
    let totalWagesAmount = 0; // Sum of NetSalary
    let totalPaidAmount = 0; // Sum of PaidAmount
    let totalPendingAmount = 0; // Sum of Pending
    let totalDays = 0;

    // Filter strip items
    let visibleStripIdx = 0;
    $(".wages-strip-item").each(function() {
        var empIdAttr = $(this).data("employeeid");
        if (empIdAttr === undefined || empIdAttr === null) return;

        var employeeName = ($(this).data("employeename") || "").toString().toLowerCase();
        var employeeId = empIdAttr.toString().toLowerCase();
        var period = $(this).data("period");
        var basicWages = parseFloat($(this).data("basicwages")) || 0;
        var netSalary = parseFloat($(this).data("netsalary")) || 0;
        var paidAmount = parseFloat($(this).data("paidamount")) || 0;
        var presentDays = parseFloat($(this).data("presentdays")) || 0;
        var paymentDate = ($(this).data("paymentdate") || "").toString();
        var updatedBy = ($(this).data("updatedby") || "").toString().toUpperCase().trim();

        var matchesSearch = (!searchVal || employeeName.indexOf(searchVal) > -1 || employeeId.indexOf(searchVal) > -1 || updatedBy.toLowerCase().indexOf(searchVal) > -1);
        var matchesPeriod = (!periodVal || periodVal.length === 0 || periodVal.indexOf(period) > -1);
        var matchesLabour = (!labourVal || labourVal.length === 0 || labourVal.indexOf(employeeId) > -1);
        var matchesPaymentDate = (!paymentDateVal || paymentDateVal.length === 0 || paymentDateVal.indexOf(paymentDate) > -1);
        var matchesUpdatedBy = (!updatedByVal || updatedByVal.length === 0 || updatedByVal.indexOf(updatedBy) > -1);

        if (matchesSearch && matchesPeriod && matchesLabour && matchesPaymentDate && matchesUpdatedBy) {
            $(this).show();
            visibleStripIdx++;
            $(this).find(".sno-cell").text(visibleStripIdx);
            
            visibleCount++;
            totalWagesAmount += netSalary;
            totalPaidAmount += paidAmount;
            totalPendingAmount += (netSalary - paidAmount);
            totalDays += presentDays;
        } else {
            $(this).hide();
        }
    });

    // Filter hidden table rows
    let visibleTableIdx = 0;
    $("#paidlabourwagespayment_tbl tbody tr").each(function() {
        var empIdAttr = $(this).data("employeeid");
        if (empIdAttr === undefined || empIdAttr === null) return;

        var employeeName = ($(this).data("employeename") || "").toString().toLowerCase();
        var employeeId = empIdAttr.toString().toLowerCase();
        var period = $(this).data("period");
        var paymentDate = ($(this).data("paymentdate") || "").toString();
        var updatedBy = ($(this).data("updatedby") || "").toString().toUpperCase().trim();

        var matchesSearch = (!searchVal || employeeName.indexOf(searchVal) > -1 || employeeId.indexOf(searchVal) > -1 || updatedBy.toLowerCase().indexOf(searchVal) > -1);
        var matchesPeriod = (!periodVal || periodVal.length === 0 || periodVal.indexOf(period) > -1);
        var matchesLabour = (!labourVal || labourVal.length === 0 || labourVal.indexOf(employeeId) > -1);
        var matchesPaymentDate = (!paymentDateVal || paymentDateVal.length === 0 || paymentDateVal.indexOf(paymentDate) > -1);
        var matchesUpdatedBy = (!updatedByVal || updatedByVal.length === 0 || updatedByVal.indexOf(updatedBy) > -1);

        if (matchesSearch && matchesPeriod && matchesLabour && matchesPaymentDate && matchesUpdatedBy) {
            $(this).show();
            visibleTableIdx++;
            $(this).find("td:first").text(visibleTableIdx);
        } else {
            $(this).hide();
        }
    });

    // Update Strips Total Row
    $(".total-net-payable-sum").text(totalWagesAmount.toFixed(2));
    $(".total-paid-amount-sum").text(totalPaidAmount.toFixed(2));
    $(".total-pending-amount-sum").text(totalPendingAmount.toFixed(2));

    if (visibleCount > 0) {
        $("#stripsTotalRow").show();
    } else {
        $("#stripsTotalRow").hide();
    }

    // Update Available Filters (Cascading)
    let availablePeriods = new Set();
    let availableLabours = new Set();
    let availablePaymentDates = new Set();
    let availableUpdatedBys = new Set();

    $(".wages-strip-item").each(function() {
        var empIdAttr = $(this).data("employeeid");
        if (empIdAttr === undefined || empIdAttr === null) return;
        var employeeName = ($(this).data("employeename") || "").toString().toLowerCase();
        var employeeId = empIdAttr.toString().toLowerCase();
        var period = $(this).data("period");
        var paymentDate = ($(this).data("paymentdate") || "").toString();
        var updatedBy = ($(this).data("updatedby") || "").toString().toUpperCase().trim();

        var matchesSearch = (!searchVal || employeeName.indexOf(searchVal) > -1 || employeeId.indexOf(searchVal) > -1 || updatedBy.toLowerCase().indexOf(searchVal) > -1);
        var matchesPeriod = (!periodVal || periodVal.length === 0 || periodVal.indexOf(period) > -1);
        var matchesLabour = (!labourVal || labourVal.length === 0 || labourVal.indexOf(employeeId) > -1);
        
        var matchesPaymentDate = true;
        if (typeof paymentDateVal !== 'undefined') {
            matchesPaymentDate = (!paymentDateVal || paymentDateVal.length === 0 || paymentDateVal.indexOf(paymentDate) > -1);
        }
        var matchesUpdatedBy = true;
        if (typeof updatedByVal !== 'undefined') {
            matchesUpdatedBy = (!updatedByVal || updatedByVal.length === 0 || updatedByVal.indexOf(updatedBy) > -1);
        }

        if (matchesSearch && matchesLabour && matchesPaymentDate && matchesUpdatedBy) availablePeriods.add(period);
        if (matchesSearch && matchesPeriod && matchesPaymentDate && matchesUpdatedBy) availableLabours.add(employeeId);
        if (matchesSearch && matchesPeriod && matchesLabour && matchesUpdatedBy) availablePaymentDates.add(paymentDate);
        if (matchesSearch && matchesPeriod && matchesLabour && matchesPaymentDate) availableUpdatedBys.add(updatedBy);
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
    if ($("#paymentDateFilterSelect").length > 0) {
        $("#paymentDateFilterSelect option").each(function() {
            let isDisabled = !availablePaymentDates.has($(this).val());
            if ($(this).prop("disabled") !== isDisabled) {
                $(this).prop("disabled", isDisabled);
                updateSelect2 = true;
            }
        });
    }
    if ($("#updatedByFilterSelect").length > 0) {
        $("#updatedByFilterSelect option").each(function() {
            let isDisabled = !availableUpdatedBys.has($(this).val().toString().toUpperCase().trim());
            if ($(this).prop("disabled") !== isDisabled) {
                $(this).prop("disabled", isDisabled);
                updateSelect2 = true;
            }
        });
    }

    if (updateSelect2 && $.fn.select2) {
        if ($("#periodFilterSelect").length > 0) {
            let ph = $("#periodFilterSelect").data("select2") ? $("#periodFilterSelect").data("select2").options.options.placeholder : "Select Date Range(s)";
            $("#periodFilterSelect").select2({ placeholder: ph, allowClear: true, width: '100%' });
        }
        if ($("#labourFilterSelect").length > 0) {
            let ph = $("#labourFilterSelect").data("select2") ? $("#labourFilterSelect").data("select2").options.options.placeholder : "Select Labour(s)";
            $("#labourFilterSelect").select2({ placeholder: ph, allowClear: true, width: '100%' });
        }
        if ($("#paymentDateFilterSelect").length > 0) {
            let ph = $("#paymentDateFilterSelect").data("select2") ? $("#paymentDateFilterSelect").data("select2").options.options.placeholder : "Payment Date(s)";
            $("#paymentDateFilterSelect").select2({ placeholder: ph, allowClear: true, width: '100%' });
        }
        if ($("#updatedByFilterSelect").length > 0) {
            let ph = $("#updatedByFilterSelect").data("select2") ? $("#updatedByFilterSelect").data("select2").options.options.placeholder : "Updated By";
            $("#updatedByFilterSelect").select2({ placeholder: ph, allowClear: true, width: '100%' });
        }
    }
}

$(document).on("click", ".view-remark", function() {
    let remark = $(this).data("remark") || "No remark available";
    let empNametr = $(this).closest("tr, .wages-strip-item").data("employeename") || "Unknown";
    Swal.fire({
        title: "Attendance Edit Remark",
        html: `
            <div style="text-align:left;">
                <p><strong>Labour Name:</strong> ${empNametr}</p>
                <p><strong>Remark:</strong> ${remark}</p>
            </div>
        `,
        icon: "info",
        confirmButtonText: "Close"
    });
});

$(document).on("click", ".viewpaidreceipt", function() {
    var routurl = $("body").data("routurl");
    let paidreceiptpath = $(this).data("paidreceipt");
    if (paidreceiptpath) {
        openPopup(routurl + "preview.php?f=" + paidreceiptpath, "Paid Receipt");
    }
});

function PaidPaymentLabourWagesData(attyearmonth, daterange, locationid, callback) {
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
        url: baseurl + "modal/hrm_laboursalarymodal.php?action=PaidPaymentLabourWagesData",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'JSON',
        success: function (res) {
            callback(res);
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
