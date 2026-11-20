<?php
if (isset($_GET['vw']) == 1) {
    $key = $_GET['k'] ?? '';
    $rooturl = "";
} else {
    require('header.php');
}
?>
<style>
/* Custom Dark Yellow & Slate Scrollbar Styling */
.hrm-table-wrapper::-webkit-scrollbar {
    width: 10px;
    height: 10px;
}
.hrm-table-wrapper::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 5px;
}
.hrm-table-wrapper::-webkit-scrollbar-thumb {
    background: #d97706 !important;
    border-radius: 5px;
    border: 2px solid #f1f5f9;
}
.hrm-table-wrapper::-webkit-scrollbar-thumb:hover {
    background: #b45309 !important;
}
.hrm-table-wrapper {
    scrollbar-width: thin;
    scrollbar-color: #d97706 #f1f5f9;
}

/* Keyframe for Active Border Pulse Animation */
@keyframes kpiPulseBorder {
    0% {
        box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.4);
        border-color: #2563eb !important;
    }
    50% {
        box-shadow: 0 0 0 7px rgba(37, 99, 235, 0.15);
        border-color: #3b82f6 !important;
    }
    100% {
        box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.4);
        border-color: #2563eb !important;
    }
}

/* Interactive Card Styling */
.kpi-card {
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    border: 2px solid #cbd5e1 !important;
    background-color: #ffffff;
    border-radius: 10px !important;
    position: relative;
}
.kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.09) !important;
    border-color: #94a3b8 !important;
}
.kpi-card.active-kpi {
    background-color: #eff6ff !important; /* Soft highlighted background */
    border-color: #2563eb !important;
    animation: kpiPulseBorder 2s infinite ease-in-out;
}
.kpi-card.active-kpi .kpi-title {
    color: #1e40af !important;
    font-weight: 800 !important;
}

/* Large Visible Stat Number */
.kpi-count-val {
    font-size: 1.55rem !important;
    font-weight: 800 !important;
    line-height: 1.15;
    letter-spacing: -0.5px;
}

/* Sticky Table Header & Screen Fit Container */
.hrm-table-wrapper {
    max-height: calc(100vh - 340px) !important;
    min-height: 380px;
    overflow-y: auto !important;
    overflow-x: auto;
    position: relative;
    border-radius: 6px;
}

@media (min-width: 992px) {
    .hrm-table-wrapper {
        overflow-x: hidden !important;
    }
}

#tblFixedLabourReport {
    width: 100% !important;
    margin-bottom: 0;
    table-layout: auto;
}

#tblFixedLabourReport th,
#tblFixedLabourReport td {
    padding: 6px 8px !important;
}

#tblFixedLabourReport td.col-worked-loc {
    white-space: normal !important;
}

#tblFixedLabourReport thead th {
    position: sticky !important;
    top: 0 !important;
    z-index: 10 !important;
    background-color: #1e293b !important;
    color: #ffffff !important;
    border-bottom: 2px solid #0f172a !important;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.12) !important;
    vertical-align: middle;
    white-space: nowrap;
}

#tblFixedLabourReport thead th.text-success {
    color: #4ade80 !important;
}

#tblFixedLabourReport thead th.text-danger {
    color: #f87171 !important;
}

/* Badges & Tables */
.super-small {
    font-size: 11px !important;
}
.badge-loc {
    font-size: 11.5px !important;
    padding: 3px 7px !important;
    margin-right: 3px;
    margin-bottom: 2px;
    display: inline-block;
}
</style>

<div class="container-fluid mt-3 mb-5">
    <div class="card hrm-card mb-3 border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="mb-0 fw-bold text-dark"><i class="ti ti-file-analytics me-2 text-primary"></i> Labour Attendance Fixed Days Report</h4>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="hrm-label fw-bold">Attendance Location</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="ti ti-map-pin text-primary"></i></span>
                        <select class="form-select hrm-input fw-semibold" id="fixed_locationlist">
                            <option value="all">All Locations</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="hrm-label fw-bold">Attendance Month</label>
                    <div class="input-group" id="fixed_month_group" style="cursor: pointer;">
                        <span class="input-group-text bg-white"><i class="ti ti-calendar text-primary"></i></span>
                        <input type="month" class="form-control hrm-input fw-semibold" id="fixed_month" value="<?php echo date('Y-m'); ?>" onclick="try{ this.showPicker(); }catch(e){}" style="cursor: pointer;">
                    </div>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-primary w-100 fw-bold py-2 shadow-sm" id="btnGetFixedReport">
                        <i class="ti ti-search me-1"></i> Get Report
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance KPI Cards (1 Row Animated Active Cards) -->
    <div class="row g-2 mb-3">
        <!-- Card 1: Total Labour -->
        <div class="col-md-2">
            <div class="card kpi-card py-2 px-3 bg-white border-start border-primary border-4 active-kpi" id="card_total_labour_box" onclick="selectKpiFilter('all', this)">
                <div class="text-muted fw-bold text-uppercase super-small text-truncate kpi-title"><i class="ti ti-users me-1 text-primary"></i> Total Labour</div>
                <div class="kpi-count-val text-dark mt-1" id="card_total_labour">0</div>
            </div>
        </div>
        <!-- Card 2: Avg Attendance -->
        <div class="col-md-2">
            <div class="card kpi-card py-2 px-3 bg-white border-start border-info border-4" id="card_avg_attendance_box">
                <div class="text-muted fw-bold text-uppercase super-small text-truncate kpi-title"><i class="ti ti-calculator me-1 text-info"></i> Avg Attendance</div>
                <div class="kpi-count-val text-info mt-1" id="card_avg_attendance">0.0 Days</div>
            </div>
        </div>
        <!-- Card 3: <26 Days -->
        <div class="col-md-2">
            <div class="card kpi-card py-2 px-3 bg-white border-start border-warning border-4" id="card_less_26_box" onclick="selectKpiFilter('less_26', this)">
                <div class="text-muted fw-bold text-uppercase super-small text-truncate kpi-title"><i class="ti ti-calendar-minus me-1 text-warning"></i> &lt;26 Days</div>
                <div class="kpi-count-val text-warning mt-1" id="card_less_26">0</div>
            </div>
        </div>
        <!-- Card 4: 26 Days -->
        <div class="col-md-2">
            <div class="card kpi-card py-2 px-3 bg-white border-start border-secondary border-4" id="card_exact_26_box" onclick="selectKpiFilter('exact_26', this)">
                <div class="text-muted fw-bold text-uppercase super-small text-truncate kpi-title"><i class="ti ti-calendar-event me-1 text-secondary"></i> 26 Days</div>
                <div class="kpi-count-val text-dark mt-1" id="card_exact_26">0</div>
            </div>
        </div>
        <!-- Card 5: 27-29 Days -->
        <div class="col-md-2">
            <div class="card kpi-card py-2 px-3 bg-white border-start border-primary border-4" id="card_27_29_box" onclick="selectKpiFilter('range_27_29', this)">
                <div class="text-muted fw-bold text-uppercase super-small text-truncate kpi-title"><i class="ti ti-calendar-time me-1 text-primary"></i> 27-29 Days</div>
                <div class="kpi-count-val text-primary mt-1" id="card_27_29">0</div>
            </div>
        </div>
        <!-- Card 6: 30-31 Days -->
        <div class="col-md-2">
            <div class="card kpi-card py-2 px-3 bg-white border-start border-success border-4" id="card_30_31_box" onclick="selectKpiFilter('range_30_31', this)">
                <div class="text-muted fw-bold text-uppercase super-small text-truncate kpi-title"><i class="ti ti-calendar-check me-1 text-success"></i> 30-31 Days</div>
                <div class="kpi-count-val text-success mt-1" id="card_30_31">0</div>
            </div>
        </div>
    </div>

    <!-- Location-wise Attendance Summary Breakdown Table -->
    <div class="card hrm-card my-4 border-0 shadow-sm" id="card_location_summary_wrapper">
        <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fs-15 fw-bold text-dark"><i class="ti ti-map-pin me-2 text-primary"></i> Location-wise Attendance Summary Breakdown</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover mb-0 text-nowrap align-middle" id="tblFixedLocationSummary">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center" style="width: 50px;">S.No</th>
                            <th>Location Name</th>
                            <th class="text-center fw-bold">Total Labours</th>
                            <th class="text-center fw-bold text-info">Avg Attendance (Days)</th>
                            <th class="text-center fw-bold text-warning">&lt;26 Days</th>
                            <th class="text-center fw-bold">26 Days</th>
                            <th class="text-center fw-bold text-primary">27-29 Days</th>
                            <th class="text-center fw-bold text-success">30-31 Days</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="8" class="text-center py-4 text-muted">Click "Get Report" to load location breakdown.</td></tr>
                    </tbody>
                    <tfoot class="table-light border-top border-dark border-2"></tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Labour Detailed List Table -->
    <div class="card hrm-card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0 fw-bold text-dark" id="labour_list_title">
                <i class="ti ti-list-check me-2 text-primary"></i> Labour List (Total Labour)
            </h5>
            <div class="d-flex align-items-center gap-2">
                <input type="text" id="fixed_searchInput" class="form-control form-control-sm" placeholder="Search by Labour ID or Name..." style="width: 250px;" onkeyup="filterLabourTable()">
                <button class="btn btn-success btn-sm shadow-sm text-white fw-bold d-inline-flex align-items-center" onclick="exportFixedReportExcel()">
                    <i class="ti ti-file-type-xls me-1 fs-16"></i> Export Excel
                </button>
            </div>
        </div>
        <!-- Grid Summary Cards -->
        <div class="px-3 pt-3 pb-2 bg-light-subtle border-bottom">
            <div class="row g-3 mb-1">
                <div class="col-md-4 col-sm-4">
                    <div class="card border-0 bg-white shadow-sm py-2 px-3 border-start border-4 border-primary rounded-3 h-100">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted fw-bold text-uppercase d-block" style="font-size: 11px;">Total Days</span>
                                <h4 class="mb-0 fw-bolder text-dark" id="grid_sum_total_days">0</h4>
                            </div>
                            <div class="avatar avatar-sm bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                <i class="ti ti-calendar-stats fs-18"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-sm-4">
                    <div class="card border-0 bg-white shadow-sm py-2 px-3 border-start border-4 border-success rounded-3 h-100">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-success fw-bold text-uppercase d-block" style="font-size: 11px;">Total Present</span>
                                <h4 class="mb-0 fw-bolder text-success" id="grid_sum_total_present">0</h4>
                            </div>
                            <div class="avatar avatar-sm bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                <i class="ti ti-user-check fs-18"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-sm-4">
                    <div class="card border-0 bg-white shadow-sm py-2 px-3 border-start border-4 border-danger rounded-3 h-100">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-danger fw-bold text-uppercase d-block" style="font-size: 11px;">Total Absent</span>
                                <h4 class="mb-0 fw-bolder text-danger" id="grid_sum_total_absent">0</h4>
                            </div>
                            <div class="avatar avatar-sm bg-danger-subtle text-danger rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                <i class="ti ti-user-x fs-18"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="hrm-table-wrapper">
                <table class="table hrm-table table-hover table-bordered mb-0 align-middle w-100" id="tblFixedLabourReport">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 45px;">S.No</th>
                            <th style="width: 105px;">Labour Id</th>
                            <th style="width: 160px;">Labour Name</th>
                            <th>Worked Location(s)</th>
                            <th class="text-center" style="width: 80px;">Total Days</th>
                            <th class="text-center text-success fw-bold" style="width: 95px;">Total Present</th>
                            <th class="text-center text-danger fw-bold" style="width: 95px;">Total Absent</th>
                            <th class="text-center fw-bold" style="width: 110px;">Attendance %</th>
                        </tr>
                    </thead>
                    <tbody id="tblFixedLabourReportBody">
                        <tr><td colspan="8" class="text-center py-4 text-muted">Click "Get Report" to view labour list.</td></tr>
                    </tbody>
                    <tfoot class="table-light border-top border-dark border-2"></tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
if (isset($_GET['vw']) == 1) {
} else {
    require('footer.php');
}
?>

<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
    let globalReportData = {
        labours: [],
        location_summary: [],
        kpi: {}
    };
    let currentCategoryFilter = 'all';

    $(document).ready(function() {
        loadLocationOptions();
        fetchFixedDaysReport();

        $('#btnGetFixedReport').click(function() {
            fetchFixedDaysReport();
        });

        $('#fixed_locationlist, #fixed_month').change(function() {
            fetchFixedDaysReport();
        });

        $('#fixed_month_group').click(function(e) {
            var el = document.getElementById('fixed_month');
            if (el && typeof el.showPicker === 'function') {
                try { el.showPicker(); } catch(err) {}
            }
        });
    });

    function loadLocationOptions() {
        if (typeof getLabourSalaryLocationList === 'function') {
            getLabourSalaryLocationList("", "", function(res) {
                if (res && res.length > 0) {
                    var html = '<option value="all">All Locations</option>';
                    res.forEach(function(loc) {
                        var locName = (loc.LocationName || '').trim();
                        var locId = loc.LocationId || loc.sno;
                        if (!locId || locName.toLowerCase() === "all" || locName.toLowerCase() === "all location") return;
                        html += `<option value="${locId}">${locName}</option>`;
                    });
                    $('#fixed_locationlist').html(html);
                }
            });
        } else {
            var baseurl = $("body").data("routurl") || "";
            $.ajax({
                type: "POST",
                url: baseurl + "modal/hrm_laboursalarymodal.php?action=getLabourSalaryLocationList",
                dataType: "json",
                success: function(res) {
                    if (res && res.length > 0) {
                        var html = '<option value="all">All Locations</option>';
                        res.forEach(function(loc) {
                            var locName = (loc.LocationName || '').trim();
                            var locId = loc.LocationId || loc.sno;
                            if (!locId || locName.toLowerCase() === "all" || locName.toLowerCase() === "all location") return;
                            html += `<option value="${locId}">${locName}</option>`;
                        });
                        $('#fixed_locationlist').html(html);
                    }
                }
            });
        }
    }
    function fetchFixedDaysReport() {
        var baseurl = $("body").data("routurl") || "";
        var locationid = $('#fixed_locationlist').val() || 'all';
        var month = $('#fixed_month').val() || '<?php echo date('Y-m'); ?>';

        // Hide location summary table when a specific location is selected
        if (locationid === 'all') {
            $('#card_location_summary_wrapper').show();
        } else {
            $('#card_location_summary_wrapper').hide();
        }

        $('#tblFixedLocationSummary tbody').html('<tr><td colspan="8" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Loading attendance report data...</td></tr>');
        $('#tblFixedLabourReportBody').html('<tr><td colspan="8" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Loading labour list...</td></tr>');

        var form_data = new FormData();
        form_data.append("locationid", locationid);
        form_data.append("month", month);

        $.ajax({
            type: "POST",
            url: baseurl + "modal/hrm_laboursalarymodal.php?action=getLabourAttendanceFixedDaysReport",
            data: form_data,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function(res) {
                if (res.status === "success") {
                    globalReportData = res;
                    renderKpiCards(res.kpi);
                    renderLocationSummary(res.location_summary);
                    renderLabourTable();
                } else {
                    Swal.fire('Error', res.message || 'Failed to load report data.', 'error');
                }
            },
            error: function(xhr, status, error) {
                var errDetail = 'Server error while fetching attendance report.';
                if (xhr.responseText) {
                    try {
                        var errObj = JSON.parse(xhr.responseText);
                        if (errObj && errObj.message) errDetail = errObj.message;
                    } catch(e) {
                        errDetail = xhr.responseText.substring(0, 300);
                    }
                }
                Swal.fire('Error', errDetail, 'error');
            }
        });
    }

    function renderKpiCards(kpi) {
        if (!kpi) return;
        $('#card_total_labour').text(kpi.total_labour || 0);
        $('#card_avg_attendance').text((kpi.avg_attendance || 0) + ' Days');
        $('#card_less_26').text(kpi.less_26 || 0);
        $('#card_exact_26').text(kpi.exact_26 || 0);
        $('#card_27_29').text(kpi.range_27_29 || 0);
        $('#card_30_31').text(kpi.range_30_31 || 0);
    }

    function renderLocationSummary(summaryList) {
        var html = "";
        var tfootHtml = "";
        if (!summaryList || summaryList.length === 0) {
            html = '<tr><td colspan="8" class="text-center py-3 text-muted">No location summary data available.</td></tr>';
        } else {
            var sumLabours = 0;
            var sumPresentDays = 0;
            var sumLess26 = 0;
            var sumExact26 = 0;
            var sumRange27_29 = 0;
            var sumRange30_31 = 0;

            summaryList.forEach(function(row, idx) {
                var labCount = parseInt(row.total_labours || 0);
                var avgAtt = parseFloat(row.avg_attendance || 0);
                var presSum = parseFloat(row.present_sum || (labCount * avgAtt) || 0);

                sumLabours += labCount;
                sumPresentDays += presSum;
                sumLess26 += parseInt(row.less_26 || 0);
                sumExact26 += parseInt(row.exact_26 || 0);
                sumRange27_29 += parseInt(row.range_27_29 || 0);
                sumRange30_31 += parseInt(row.range_30_31 || 0);

                html += `<tr>
                    <td class="text-center fw-semibold">${idx + 1}</td>
                    <td class="fw-bold text-dark">${row.location_name}</td>
                    <td class="text-center fw-bold">${row.total_labours}</td>
                    <td class="text-center fw-bold text-info">${row.avg_attendance} Days</td>
                    <td class="text-center fw-bold text-warning">${row.less_26}</td>
                    <td class="text-center fw-bold">${row.exact_26}</td>
                    <td class="text-center fw-bold text-primary">${row.range_27_29}</td>
                    <td class="text-center fw-bold text-success">${row.range_30_31}</td>
                </tr>`;
            });

            var overallAvg = (sumLabours > 0) ? (sumPresentDays / sumLabours).toFixed(1) : "0.0";

            tfootHtml = `<tr class="table-secondary border-top border-dark border-2">
                <td colspan="2" class="text-end text-uppercase fw-bolder pe-3 text-dark" style="font-size: 16px !important; font-weight: 900 !important; padding: 12px 10px !important;">TOTAL:</td>
                <td class="text-center fw-bolder text-dark" style="font-size: 16px !important; font-weight: 900 !important; padding: 12px 10px !important;">${sumLabours}</td>
                <td class="text-center fw-bolder text-info" style="font-size: 16px !important; font-weight: 900 !important; padding: 12px 10px !important;">${overallAvg} Days</td>
                <td class="text-center fw-bolder text-warning" style="font-size: 16px !important; font-weight: 900 !important; padding: 12px 10px !important;">${sumLess26}</td>
                <td class="text-center fw-bolder text-dark" style="font-size: 16px !important; font-weight: 900 !important; padding: 12px 10px !important;">${sumExact26}</td>
                <td class="text-center fw-bolder text-primary" style="font-size: 16px !important; font-weight: 900 !important; padding: 12px 10px !important;">${sumRange27_29}</td>
                <td class="text-center fw-bolder text-success" style="font-size: 16px !important; font-weight: 900 !important; padding: 12px 10px !important;">${sumRange30_31}</td>
            </tr>`;
        }

        $('#tblFixedLocationSummary tbody').html(html);
        $('#tblFixedLocationSummary tfoot').html(tfootHtml);
    }

    function selectKpiFilter(category, cardElement) {
        currentCategoryFilter = category;
        $('.kpi-card').removeClass('active-kpi');
        if (cardElement) {
            $(cardElement).addClass('active-kpi');
        }

        renderLabourTable();
    }

    function renderLabourTable() {
        var labours = globalReportData.labours || [];
        var searchVal = ($('#fixed_searchInput').val() || '').toLowerCase().trim();

        var titleText = "Labour List (Total Labour)";
        if (currentCategoryFilter === 'less_26') titleText = "Labour List (<26 Days)";
        else if (currentCategoryFilter === 'exact_26') titleText = "Labour List (26 Days)";
        else if (currentCategoryFilter === 'range_27_29') titleText = "Labour List (27-29 Days)";
        else if (currentCategoryFilter === 'range_30_31') titleText = "Labour List (30-31 Days)";

        var filtered = labours.filter(function(item) {
            // Category filter
            if (currentCategoryFilter !== 'all' && item.category !== currentCategoryFilter) {
                return false;
            }
            // Search text filter
            if (searchVal) {
                var idMatch = (item.labourid || '').toLowerCase().indexOf(searchVal) !== -1;
                var nameMatch = (item.name || '').toLowerCase().indexOf(searchVal) !== -1;
                var locMatch = false;
                if (item.locations) {
                    item.locations.forEach(function(l) {
                        if ((l.location_name || '').toLowerCase().indexOf(searchVal) !== -1) locMatch = true;
                    });
                }
                return idMatch || nameMatch || locMatch;
            }
            return true;
        });

        $('#labour_list_title').html(`<i class="ti ti-list-check me-2 text-primary"></i> ${titleText} <span class="badge bg-primary-subtle text-primary border ms-2">${filtered.length}</span>`);

        var html = "";
        var tfootHtml = "";
        var gridDaysSum = 0;
        var gridPresentSum = 0;
        var gridAbsentSum = 0;

        if (filtered.length === 0) {
            html = '<tr><td colspan="8" class="text-center py-4 text-muted">No labours found matching the selected filter.</td></tr>';
            tfootHtml = "";
        } else {
            filtered.forEach(function(row, idx) {
                gridDaysSum += parseInt(row.total_days || 0);
                gridPresentSum += parseFloat(row.total_present || 0);
                gridAbsentSum += parseFloat(row.total_absent || 0);

                var locBadgesHtml = "";
                if (row.locations && row.locations.length > 0) {
                    row.locations.forEach(function(loc) {
                        locBadgesHtml += `<span class="badge bg-light text-dark border badge-loc"><i class="ti ti-map-pin text-primary me-1"></i>${loc.location_name} <strong class="text-primary">(${loc.days})</strong></span>`;
                    });
                } else {
                    locBadgesHtml = '<span class="text-muted small">N/A</span>';
                }

                var pct = row.att_percentage || 0;
                var badgeClass = 'bg-danger-subtle text-danger border-danger-subtle';
                if (pct >= 85) badgeClass = 'bg-success-subtle text-success border-success-subtle';
                else if (pct >= 70) badgeClass = 'bg-primary-subtle text-primary border-primary-subtle';
                else if (pct >= 50) badgeClass = 'bg-warning-subtle text-warning-emphasis border-warning-subtle';

                html += `<tr>
                    <td class="text-center fw-semibold" style="font-size: 13px;">${idx + 1}</td>
                    <td class="fw-bold font-monospace text-dark" style="font-size: 13px;">${row.labourid}</td>
                    <td class="fw-bold text-dark" style="font-size: 13px;">${row.name}</td>
                    <td class="col-worked-loc">${locBadgesHtml}</td>
                    <td class="text-center font-monospace" style="font-size: 14px; font-weight: 700;">${row.total_days}</td>
                    <td class="text-center font-monospace text-success" style="font-size: 15px; font-weight: 800;">${row.total_present}</td>
                    <td class="text-center font-monospace text-danger" style="font-size: 15px; font-weight: 800;">${row.total_absent}</td>
                    <td class="text-center">
                        <span class="badge ${badgeClass} border px-2 py-1 fw-bold mb-1" style="font-size: 12px;">${pct}%</span>
                        <div class="progress" style="height: 5px;">
                            <div class="progress-bar ${pct >= 85 ? 'bg-success' : (pct >= 50 ? 'bg-warning' : 'bg-danger')}" style="width: ${pct}%;"></div>
                        </div>
                    </td>
                </tr>`;
            });

            var overallPct = gridDaysSum > 0 ? ((gridPresentSum / gridDaysSum) * 100).toFixed(1) : "0.0";

            tfootHtml = `<tr class="table-secondary border-top border-dark border-2">
                <td colspan="3" class="text-end text-uppercase fw-bolder pe-3 text-dark" style="font-size: 15px !important; font-weight: 900 !important; padding: 12px 10px !important;">TOTAL:</td>
                <td class="col-worked-loc fw-bolder text-dark" style="font-size: 14px !important; font-weight: 800 !important; padding: 12px 10px !important;">${filtered.length} Labours</td>
                <td class="text-center font-monospace fw-bolder text-dark" style="font-size: 16px !important; font-weight: 900 !important; padding: 12px 10px !important;">${gridDaysSum}</td>
                <td class="text-center font-monospace fw-bolder text-success" style="font-size: 16px !important; font-weight: 900 !important; padding: 12px 10px !important;">${gridPresentSum}</td>
                <td class="text-center font-monospace fw-bolder text-danger" style="font-size: 16px !important; font-weight: 900 !important; padding: 12px 10px !important;">${gridAbsentSum}</td>
                <td class="text-center fw-bolder text-dark" style="font-size: 15px !important; font-weight: 900 !important; padding: 12px 10px !important;">${overallPct}%</td>
            </tr>`;
        }

        $('#grid_sum_total_days').text(gridDaysSum);
        $('#grid_sum_total_present').text(gridPresentSum);
        $('#grid_sum_total_absent').text(gridAbsentSum);

        $('#tblFixedLabourReportBody').html(html);
        $('#tblFixedLabourReport tfoot').html(tfootHtml);
    }

    function filterLabourTable() {
        renderLabourTable();
    }

    function exportFixedReportExcel() {
        if (!globalReportData) return;

        var locSelectText = $('#fixed_locationlist option:selected').text() || 'All Locations';
        var monthVal = $('#fixed_month').val() || '<?php echo date('Y-m'); ?>';
        var kpi = globalReportData.kpi || {};
        var labours = globalReportData.labours || [];
        var locSummary = globalReportData.location_summary || [];

        // Active Card Category Title & Filename Slug
        var catTitle = "Labour Attendance Summary Report";
        var catSlug = "all_labour";
        if (currentCategoryFilter === 'less_26') {
            catTitle = "Labour Attendance <26 Days Report";
            catSlug = "less_26days";
        } else if (currentCategoryFilter === 'exact_26') {
            catTitle = "Labour Attendance 26 Days Report";
            catSlug = "26days";
        } else if (currentCategoryFilter === 'range_27_29') {
            catTitle = "Labour Attendance 27-29 Days Report";
            catSlug = "27_29days";
        } else if (currentCategoryFilter === 'range_30_31') {
            catTitle = "Labour Attendance 30-31 Days Report";
            catSlug = "30_31days";
        }

        var aoa = [];

        // Title & Filters Header
        aoa.push([catTitle + " - " + locSelectText]);
        aoa.push(["Attendance Month:", monthVal, "Attendance Location:", locSelectText, "Active Filter:", catTitle]);
        aoa.push([]);

        // KPI Summary Block
        aoa.push(["EXECUTIVE ATTENDANCE SUMMARY"]);
        aoa.push(["Total Labour", "Avg Attendance", "<26 Days", "26 Days", "27-29 Days", "30-31 Days"]);
        aoa.push([
            kpi.total_labour || 0,
            (kpi.avg_attendance || 0) + " Days",
            kpi.less_26 || 0,
            kpi.exact_26 || 0,
            kpi.range_27_29 || 0,
            kpi.range_30_31 || 0
        ]);
        aoa.push([]);

        // Location Breakdown Table (if visible or available)
        if ($('#card_location_summary_wrapper').is(':visible') && locSummary.length > 0) {
            aoa.push(["LOCATION-WISE ATTENDANCE SUMMARY BREAKDOWN"]);
            aoa.push(["S.No", "Location Name", "Total Labours", "Avg Attendance (Days)", "<26 Days", "26 Days", "27-29 Days", "30-31 Days"]);

            var sumLabours = 0;
            var sumPresentDays = 0;
            var sumLess26 = 0;
            var sumExact26 = 0;
            var sumRange27_29 = 0;
            var sumRange30_31 = 0;

            locSummary.forEach(function(r, idx) {
                var labCount = parseInt(r.total_labours || 0);
                var avgAtt = parseFloat(r.avg_attendance || 0);
                var presSum = parseFloat(r.present_sum || (labCount * avgAtt) || 0);

                sumLabours += labCount;
                sumPresentDays += presSum;
                sumLess26 += parseInt(r.less_26 || 0);
                sumExact26 += parseInt(r.exact_26 || 0);
                sumRange27_29 += parseInt(r.range_27_29 || 0);
                sumRange30_31 += parseInt(r.range_30_31 || 0);

                aoa.push([
                    idx + 1,
                    r.location_name,
                    r.total_labours,
                    r.avg_attendance + " Days",
                    r.less_26,
                    r.exact_26,
                    r.range_27_29,
                    r.range_30_31
                ]);
            });

            var overallAvg = (sumLabours > 0) ? (sumPresentDays / sumLabours).toFixed(1) : "0.0";
            aoa.push([
                "TOTAL",
                "",
                sumLabours,
                overallAvg + " Days",
                sumLess26,
                sumExact26,
                sumRange27_29,
                sumRange30_31
            ]);
            aoa.push([]);
        }

        // Labour Detailed List
        var searchVal = ($('#fixed_searchInput').val() || '').toLowerCase().trim();
        var filteredLabours = labours.filter(function(item) {
            if (currentCategoryFilter !== 'all' && item.category !== currentCategoryFilter) return false;
            if (searchVal) {
                var idMatch = (item.labourid || '').toLowerCase().indexOf(searchVal) !== -1;
                var nameMatch = (item.name || '').toLowerCase().indexOf(searchVal) !== -1;
                var locMatch = false;
                if (item.locations) {
                    item.locations.forEach(function(l) {
                        if ((l.location_name || '').toLowerCase().indexOf(searchVal) !== -1) locMatch = true;
                    });
                }
                return idMatch || nameMatch || locMatch;
            }
            return true;
        });

        aoa.push([catTitle.toUpperCase() + " (" + filteredLabours.length + " LABOURS)"]);
        aoa.push(["S.No", "Labour ID", "Labour Name", "Worked Location(s)", "Total Days", "Total Present", "Total Absent", "Attendance %"]);

        filteredLabours.forEach(function(row, idx) {
            var locStr = "";
            if (row.locations && row.locations.length > 0) {
                locStr = row.locations.map(function(l) {
                    return l.location_name + " (" + l.days + ")";
                }).join(", ");
            } else {
                locStr = "N/A";
            }

            aoa.push([
                idx + 1,
                row.labourid,
                row.name,
                locStr,
                row.total_days,
                row.total_present,
                row.total_absent,
                row.att_percentage + "%"
            ]);
        });

        var wb = XLSX.utils.book_new();
        var ws = XLSX.utils.aoa_to_sheet(aoa);
        XLSX.utils.book_append_sheet(wb, ws, "Attendance_Report");

        // Format Location Name, Month Name & Active Filter for output filename
        // Example: attendance_26days_july26_karjee.xlsx
        var monthShortYear = monthVal;
        if (monthVal && monthVal.indexOf('-') !== -1) {
            var mParts = monthVal.split('-');
            var yearNum = mParts[0].substring(2); // "2026" -> "26"
            var monthIdx = parseInt(mParts[1], 10) - 1;
            var monthNamesLower = ["january", "february", "march", "april", "may", "june", "july", "august", "september", "october", "november", "december"];
            if (monthNamesLower[monthIdx]) {
                monthShortYear = monthNamesLower[monthIdx] + yearNum; // e.g. "july26"
            }
        }

        var cleanLocSlug = locSelectText.trim().toLowerCase().replace(/[^a-z0-9]/g, '_').replace(/_+/g, '_');
        var fileName = "attendance_" + catSlug + "_" + monthShortYear + "_" + cleanLocSlug + ".xlsx";

        XLSX.writeFile(wb, fileName);
    }
</script>
