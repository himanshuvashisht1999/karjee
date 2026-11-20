<?php
if (isset($_GET['vw']) == 1) {
    $key = $_GET['k'];
    $rooturl = "";
} else {
    require('header.php');
}
?>
<style>
/* Dark Yellow / Amber Gold Scrollbar Styling */
.hrm-table-wrapper::-webkit-scrollbar {
    width: 10px;
    height: 10px;
}
.hrm-table-wrapper::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 5px;
}
.hrm-table-wrapper::-webkit-scrollbar-thumb {
    background: #d97706 !important; /* Dark Yellow / Amber */
    border-radius: 5px;
    border: 2px solid #f1f5f9;
}
.hrm-table-wrapper::-webkit-scrollbar-thumb:hover {
    background: #b45309 !important; /* Darker Yellow */
}
.hrm-table-wrapper {
    scrollbar-width: thin;
    scrollbar-color: #d97706 #f1f5f9;
}

/* Compact Column Widths for Screen Fit */
.col-sno {
    width: 38px !important;
    min-width: 38px !important;
    max-width: 38px !important;
    padding: 6px 2px !important;
    text-align: center !important;
}
.col-code {
    width: 95px !important;
    min-width: 95px !important;
    max-width: 95px !important;
    font-size: 11px !important;
    padding: 6px 4px !important;
    white-space: nowrap !important;
}
.col-name {
    width: 150px !important;
    min-width: 150px !important;
    max-width: 150px !important;
    font-size: 12px !important;
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    padding: 6px 6px !important;
}
.col-loc {
    width: 90px !important;
    min-width: 90px !important;
    max-width: 90px !important;
    font-size: 11px !important;
    padding: 6px 4px !important;
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
}
.col-date-cell {
    width: 36px !important;
    min-width: 34px !important;
    max-width: 40px !important;
    padding: 4px 2px !important;
    font-size: 11px !important;
    text-align: center !important;
}
.col-summary-cell {
    width: 42px !important;
    min-width: 38px !important;
    max-width: 46px !important;
    padding: 6px 2px !important;
    font-size: 12px !important;
    text-align: center !important;
}

/* Light Gender Badges with Black Text */
.badge-gender-m {
    background-color: #e0f2fe !important;
    color: #111827 !important;
    border: 1px solid #bae6fd !important;
    font-size: 8.5px;
    padding: 1px 5px;
    font-weight: 600;
}
.badge-gender-w {
    background-color: #fce7f3 !important;
    color: #111827 !important;
    border: 1px solid #fbcfe8 !important;
    font-size: 8.5px;
    padding: 1px 5px;
    font-weight: 600;
}
</style>
<div class="container-fluid mt-3">
    <div class="card hrm-card">
        <div class="card-header">
            <h4><i class="ti ti-file-analytics me-2"></i> Labour Attendance P/A Report</h4>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="hrm-label">Wages Location</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="ti ti-map-pin"></i></span>
                        <select class="form-select hrm-input" id="pa_locationlist">
                            <option value="all">All Location</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="hrm-label">From Date</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="ti ti-calendar"></i></span>
                        <input type="date" class="form-control hrm-input" id="pa_datefrom" value="<?php echo date('Y-m-01'); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="hrm-label">To Date</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="ti ti-calendar"></i></span>
                        <input type="date" class="form-control hrm-input" id="pa_dateto" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100" id="btnGetPAReport"><i class="ti ti-search me-1"></i> Get Report</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="row g-3 my-2" id="pa_summary_cards" style="display: none;">
        <div class="col-md-2">
            <div class="card border-0 shadow-sm rounded-3 bg-white p-3 text-center border-start border-primary border-4">
                <div class="text-muted fw-bold text-uppercase" style="font-size: 12px;"><i class="ti ti-users me-1 text-primary"></i> Total Labours</div>
                <div class="fw-bold text-dark mt-2" id="sum_total_labours" style="font-size: 22px; line-height: 1.2;">0</div>
                <div class="mt-2 d-flex justify-content-center gap-1 flex-wrap">
                    <span class="badge badge-gender-m">M: <span id="sum_man_count">0</span></span>
                    <span class="badge badge-gender-w">W: <span id="sum_female_count">0</span></span>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm rounded-3 bg-white p-3 text-center border-start border-info border-4">
                <div class="text-muted fw-bold text-uppercase" style="font-size: 12px;"><i class="ti ti-calendar me-1 text-info"></i> Total Days</div>
                <div class="fw-bold text-dark mt-2" id="sum_total_days" style="font-size: 22px; line-height: 1.2;">0</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm rounded-3 bg-white p-3 text-center border-start border-success border-4">
                <div class="text-muted fw-bold text-uppercase" style="font-size: 12px;"><i class="ti ti-user-check me-1 text-success"></i> Present (P)</div>
                <div class="fw-bold text-success mt-2" id="sum_total_p" style="font-size: 22px; line-height: 1.2;">0</div>
                <div class="mt-2 d-flex justify-content-center gap-1 flex-wrap">
                    <span class="badge badge-gender-m">M: <span id="sum_p_man_count">0</span></span>
                    <span class="badge badge-gender-w">W: <span id="sum_p_female_count">0</span></span>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm rounded-3 bg-white p-3 text-center border-start border-warning border-4">
                <div class="text-muted fw-bold text-uppercase" style="font-size: 12px;"><i class="ti ti-clock me-1 text-warning"></i> Half Days (HD)</div>
                <div class="fw-bold text-warning mt-2" id="sum_total_hd" style="font-size: 22px; line-height: 1.2;">0</div>
                <div class="mt-2 d-flex justify-content-center gap-1 flex-wrap">
                    <span class="badge badge-gender-m">M: <span id="sum_hd_man_count">0</span></span>
                    <span class="badge badge-gender-w">W: <span id="sum_hd_female_count">0</span></span>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm rounded-3 bg-white p-3 text-center border-start border-danger border-4">
                <div class="text-muted fw-bold text-uppercase" style="font-size: 12px;"><i class="ti ti-user-x me-1 text-danger"></i> Absent (A)</div>
                <div class="fw-bold text-danger mt-2" id="sum_total_a" style="font-size: 22px; line-height: 1.2;">0</div>
                <div class="mt-2 d-flex justify-content-center gap-1 flex-wrap">
                    <span class="badge badge-gender-m">M: <span id="sum_a_man_count">0</span></span>
                    <span class="badge badge-gender-w">W: <span id="sum_a_female_count">0</span></span>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm rounded-3 bg-white p-3 text-center border-start border-primary border-4" style="background-color: #f0f7ff;">
                <div class="text-primary fw-bold text-uppercase" style="font-size: 12px;"><i class="ti ti-coins me-1"></i> Payable Days</div>
                <div class="fw-bold text-primary mt-2" id="sum_total_payable" style="font-size: 22px; line-height: 1.2;">0</div>
                <div class="mt-2 d-flex justify-content-center gap-1 flex-wrap">
                    <span class="badge badge-gender-m">M: <span id="sum_payable_man_count">0</span></span>
                    <span class="badge badge-gender-w">W: <span id="sum_payable_female_count">0</span></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Location-wise Summary Breakdown (Shown when All Location selected) -->
    <div class="card hrm-card my-3" id="card_location_summary" style="display: none;">
        <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fs-14 fw-bold text-dark"><i class="ti ti-map-pin me-2 text-primary"></i> Location-wise Attendance Summary Breakdown</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover mb-0 text-nowrap" id="tblLocationSummary">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 50px;">S.No</th>
                            <th>Location Name</th>
                            <th class="text-center">Total Labours</th>
                            <th class="text-center text-success">Full Days (P)</th>
                            <th class="text-center text-warning">Half Days (HD)</th>
                            <th class="text-center text-danger">Missing Out (MIS)</th>
                            <th class="text-center text-danger">Absent Days (A)</th>
                            <th class="text-center text-primary">Payable Days</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Calculation Rules Alert -->
    <div class="alert alert-info d-flex align-items-center my-3 py-2 px-3 border-info" style="border-radius: 8px; background-color: #eff6ff; color: #1e40af;">
        <i class="ti ti-info-circle me-2 fs-18" style="color: #2563eb;"></i>
        <span class="fs-13"><strong>Calculation Rules:</strong> Half-Day (HD): Working Hours &gt; 2 hrs &amp; &lt; 6 hrs (0.5 day) | Full-Day (P): Working Hours &ge; 6 hrs (1.0 day) | Absent (A): Working Hours &le; 2 hrs</span>
    </div>

    <!-- Report Table Card -->
    <div class="card hrm-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4><i class="ti ti-list-check me-2"></i> <span id="pa_report_header">Labour Attendance P/A Report</span></h4>
            <button class="btn btn-success btn-sm shadow-sm text-white fw-bold d-inline-flex align-items-center" onclick="exportPAReportExcel()">
                <i class="ti ti-file-type-xls me-1 fs-16"></i> Export Excel
            </button>
        </div>
        <div class="card-body p-0">
            <div class="d-flex align-items-center justify-content-end gap-2 p-2 bg-light border-bottom">
                <input type="text" id="pa_searchInput" class="form-control form-control-sm" placeholder="Search in table..." style="width: 240px;">
            </div>
            <div class="hrm-table-wrapper" style="max-height: 550px; overflow-y: auto;">
                <table class="table hrm-table table-hover table-bordered mb-0 text-nowrap" id="tblPAReport">
                    <thead id="tblPAReportHead" class="table-light sticky-top">
                        <tr>
                            <th class="text-center col-sno">S.NO</th>
                            <th class="col-code">EMPLOYEE CODE</th>
                            <th class="col-name">EMPLOYEE NAME</th>
                            <!-- Dynamic Date columns will go here -->
                            <th class="text-center text-success fw-bold col-summary-cell">P</th>
                            <th class="text-center text-warning fw-bold col-summary-cell">HD</th>
                            <th class="text-center text-danger fw-bold col-summary-cell">MIS</th>
                            <th class="text-center text-danger fw-bold col-summary-cell">A</th>
                            <th class="text-center fw-bold col-summary-cell" style="color: #f97316 !important;">Payable</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Click "Get Report" to load attendance data.</td>
                        </tr>
                    </tbody>
                    <tfoot id="tblPAReportFoot" class="sticky-bottom"></tfoot>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>
<script>
    function prepareExportTable(originalTableId) {
        const table = document.getElementById(originalTableId);
        if (!table) return null;
        
        const clone = table.cloneNode(true);
        
        // 1. Update thead: Insert LOCATION header after EMPLOYEE NAME
        const theadTr = clone.querySelector("thead tr");
        if (theadTr) {
            const ths = theadTr.querySelectorAll("th");
            if (ths.length >= 3) {
                const locTh = document.createElement("th");
                locTh.innerText = "LOCATION";
                locTh.style.fontWeight = "bold";
                ths[2].after(locTh);
            }
        }
        
        // 2. Update tbody rows: Separate EMPLOYEE NAME and LOCATION into two distinct columns
        const tbodyTrs = clone.querySelectorAll("tbody tr");
        tbodyTrs.forEach(tr => {
            const tds = tr.querySelectorAll("td");
            if (tds.length >= 3) {
                const nameTd = tds[2];
                const strongEl = nameTd.querySelector("strong");
                const badgeEl  = nameTd.querySelector("span.badge");
                
                const empName = strongEl ? strongEl.innerText.trim() : nameTd.innerText.trim();
                const locName = badgeEl ? badgeEl.innerText.trim() : "";
                
                nameTd.innerText = empName;
                
                const locTd = document.createElement("td");
                locTd.innerText = locName;
                nameTd.after(locTd);
            }
        });
        
        // 3. Update tfoot: Adjust colspan from 3 to 4 for "TOTAL PRESENT COUNT:"
        const tfootTr = clone.querySelector("tfoot tr");
        if (tfootTr) {
            const firstTd = tfootTr.querySelector("td");
            if (firstTd && firstTd.hasAttribute("colspan")) {
                firstTd.setAttribute("colspan", "4");
            }
        }
        
        return clone;
    }

    function exportPAReportPDF() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF("l", "mm", "a2");
        doc.setFontSize(14);
        var title = $("#pa_report_header").text();
        doc.text(title, 14, 15);
        
        const clone = prepareExportTable("tblPAReport");
        if (!clone) return;
        
        doc.autoTable({
            html: clone,
            startY: 22,
            theme: "grid",
            styles: { fontSize: 7, cellPadding: 1 },
            headStyles: { fillColor: [41, 128, 185] }
        });
        doc.save("Labour_Attendance_PA_Report.pdf");
    }

    function exportPAReportExcel() {
        const clone = prepareExportTable("tblPAReport");
        if (!clone) return;
        const ws = XLSX.utils.table_to_sheet(clone, { origin: "A3" });
        var title = $("#pa_report_header").text();
        XLSX.utils.sheet_add_aoa(ws, [[title]], { origin: "A1" });
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "PA_Report");
        XLSX.writeFile(wb, "Labour_Attendance_PA_Report.xlsx");
    }

    function fetchLabourPAReport(projectid, datefrom, dateto, locationid) {
        var baseurl = $("body").data("routurl") || "";
        var form_data = new FormData();
        form_data.append("projectid", projectid);
        form_data.append("datefrom", datefrom);
        form_data.append("dateto", dateto);
        form_data.append("locationid", locationid || "all");

        $.ajax({
            type: "POST",
            url: baseurl + "modal/hrm_laboursalarymodal.php?action=getLabourAttendancePAReport",
            data: form_data,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function(res) {
                if (res.status === "success") {
                    renderPATable(res.dates, res.data);
                    calculateAndRenderSummary(res.dates, res.data, locationid);
                } else {
                    $("#tblPAReport tbody").html(`<tr><td colspan="8" class="text-center text-danger py-4">${res.message || "Failed to load report data."}</td></tr>`);
                    $("#tblPAReportFoot").empty();
                    $("#pa_summary_cards, #card_location_summary").hide();
                }
            },
            error: function() {
                $("#tblPAReport tbody").html(`<tr><td colspan="8" class="text-center text-danger py-4">Server error while fetching report.</td></tr>`);
                $("#tblPAReportFoot").empty();
                $("#pa_summary_cards, #card_location_summary").hide();
            }
        });
    }

    function renderPATable(dates, data) {
        let theadHtml = `
            <tr>
                <th class="text-center col-sno">S.NO</th>
                <th class="col-code">EMPLOYEE CODE</th>
                <th class="col-name">EMPLOYEE NAME</th>
        `;

        if (Array.isArray(dates)) {
            dates.forEach(dObj => {
                theadHtml += `<th class="text-center col-date-cell">${dObj.disp}</th>`;
            });
        }

        theadHtml += `
                <th class="text-center text-success fw-bold col-summary-cell">P</th>
                <th class="text-center text-warning fw-bold col-summary-cell">HD</th>
                <th class="text-center text-danger fw-bold col-summary-cell">MIS</th>
                <th class="text-center text-danger fw-bold col-summary-cell">A</th>
                <th class="text-center fw-bold col-summary-cell" style="color: #f97316 !important;">Payable</th>
            </tr>
        `;
        $("#tblPAReportHead").html(theadHtml);

        let datePresentCounts = {};
        if (Array.isArray(dates)) {
            dates.forEach(dObj => {
                datePresentCounts[dObj.date] = 0;
            });
        }

        let grandP = 0;
        let grandHD = 0;
        let grandMIS = 0;
        let grandA = 0;
        let grandPayable = 0;

        let tbodyHtml = "";
        if (Array.isArray(data) && data.length > 0) {
            data.forEach((row, idx) => {
                grandP += (row.total_p || 0);
                grandHD += (row.total_hd || 0);
                grandMIS += (row.total_mis || 0);
                grandA += (row.total_a || 0);
                grandPayable += (row.payable_days || 0);

                tbodyHtml += `
                    <tr>
                        <td class="text-center align-middle col-sno">${idx + 1}</td>
                        <td class="fw-semibold text-secondary align-middle col-code">${row.labourid}</td>
                        <td class="align-middle col-name" title="${row.FullName}">
                            <strong class="text-dark d-block text-truncate">${row.FullName}</strong>
                            <span class="badge bg-light text-dark border fs-10 text-truncate max-w-100" style="padding: 1px 5px; font-size: 10px;">${row.location}</span>
                        </td>
                `;

                if (Array.isArray(dates)) {
                    dates.forEach(dObj => {
                        let st = row.attendance[dObj.date] || 'A';
                        let badgeClass = 'bg-soft-danger text-danger';
                        if (st === 'P') {
                            badgeClass = 'bg-soft-success text-success fw-bold';
                            datePresentCounts[dObj.date] = (datePresentCounts[dObj.date] || 0) + 1;
                        } else if (st === 'HD') {
                            badgeClass = 'bg-soft-warning text-warning fw-bold';
                            datePresentCounts[dObj.date] = (datePresentCounts[dObj.date] || 0) + 1;
                        } else if (st === 'MIS') {
                            badgeClass = 'bg-soft-danger text-danger fw-bold';
                            datePresentCounts[dObj.date] = (datePresentCounts[dObj.date] || 0) + 1;
                        }

                        tbodyHtml += `<td class="text-center align-middle col-date-cell"><span class="badge ${badgeClass} border px-1">${st}</span></td>`;
                    });
                }

                tbodyHtml += `
                        <td class="text-center fw-bold text-success align-middle col-summary-cell">${row.total_p}</td>
                        <td class="text-center fw-bold text-warning align-middle col-summary-cell">${row.total_hd}</td>
                        <td class="text-center fw-bold text-danger align-middle col-summary-cell">${row.total_mis || 0}</td>
                        <td class="text-center fw-bold text-danger align-middle col-summary-cell">${row.total_a}</td>
                        <td class="text-center fw-bold text-primary align-middle col-summary-cell">${row.payable_days}</td>
                    </tr>
                `;
            });

            // Build Summary Footer Row at the end of the grid
            let tfootHtml = `
                <tr class="table-dark fw-bold" style="background-color: #0f172a; color: #ffffff;">
                    <td colspan="3" class="text-end text-uppercase" style="letter-spacing: 0.5px; font-size: 12px; padding: 6px 10px;">TOTAL PRESENT COUNT:</td>
            `;

            if (Array.isArray(dates)) {
                dates.forEach(dObj => {
                    let cnt = datePresentCounts[dObj.date] || 0;
                    tfootHtml += `<td class="text-center text-warning fw-bold col-date-cell" style="font-size: 12px;">${cnt}</td>`;
                });
            }

            tfootHtml += `
                    <td class="text-center text-success fw-bold col-summary-cell" style="font-size: 13px;">${grandP}</td>
                    <td class="text-center text-warning fw-bold col-summary-cell" style="font-size: 13px;">${grandHD}</td>
                    <td class="text-center text-danger fw-bold col-summary-cell" style="font-size: 13px;">${grandMIS}</td>
                    <td class="text-center text-danger fw-bold col-summary-cell" style="font-size: 13px;">${grandA}</td>
                    <td class="text-center text-primary fw-bold col-summary-cell" style="font-size: 13px; background-color: #1e293b;">${grandPayable.toFixed(1).replace('.0', '')}</td>
                </tr>
            `;
            $("#tblPAReportFoot").html(tfootHtml);
        } else {
            let totalCols = (dates ? dates.length : 0) + 8;
            tbodyHtml = `<tr><td colspan="${totalCols}" class="text-center text-muted py-4">No labour attendance records found for selected period.</td></tr>`;
            $("#tblPAReportFoot").empty();
        }

        $("#tblPAReport tbody").html(tbodyHtml);
    }

    function calculateAndRenderSummary(dates, data, selectedLocation) {
        let totalLabours = data ? data.length : 0;
        let totalDays = dates ? dates.length : 0;
        let totalP = 0;
        let totalHD = 0;
        let totalMIS = 0;
        let totalA = 0;
        let totalPayable = 0;
        let totalMan = 0;
        let totalFemale = 0;

        let maleP = 0, femaleP = 0;
        let maleHD = 0, femaleHD = 0;
        let maleMIS = 0, femaleMIS = 0;
        let maleA = 0, femaleA = 0;
        let malePayable = 0, femalePayable = 0;

        let locMap = {};

        if (Array.isArray(data)) {
            data.forEach(row => {
                let p = (row.total_p || 0);
                let hd = (row.total_hd || 0);
                let mis = (row.total_mis || 0);
                let a = (row.total_a || 0);
                let payable = (row.payable_days || 0);

                totalP += p;
                totalHD += hd;
                totalMIS += mis;
                totalA += a;
                totalPayable += payable;

                let g = (row.Gender || row.gender || '').toString().trim().toLowerCase();
                let isFemale = (g === 'female' || g === 'f' || g === 'woman' || g === 'women' || g === 'w');
                if (isFemale) {
                    totalFemale++;
                    femaleP += p;
                    femaleHD += hd;
                    femaleMIS += mis;
                    femaleA += a;
                    femalePayable += payable;
                } else {
                    totalMan++;
                    maleP += p;
                    maleHD += hd;
                    maleMIS += mis;
                    maleA += a;
                    malePayable += payable;
                }

                let loc = row.location || 'Default';
                if (!locMap[loc]) {
                    locMap[loc] = {
                        count: 0, man: 0, female: 0,
                        p: 0, maleP: 0, femaleP: 0,
                        hd: 0, maleHD: 0, femaleHD: 0,
                        mis: 0, maleMIS: 0, femaleMIS: 0,
                        a: 0, maleA: 0, femaleA: 0,
                        payable: 0, malePayable: 0, femalePayable: 0
                    };
                }
                locMap[loc].count += 1;
                if (isFemale) {
                    locMap[loc].female += 1;
                    locMap[loc].femaleP += p;
                    locMap[loc].femaleHD += hd;
                    locMap[loc].femaleMIS += mis;
                    locMap[loc].femaleA += a;
                    locMap[loc].femalePayable += payable;
                } else {
                    locMap[loc].man += 1;
                    locMap[loc].maleP += p;
                    locMap[loc].maleHD += hd;
                    locMap[loc].maleMIS += mis;
                    locMap[loc].maleA += a;
                    locMap[loc].malePayable += payable;
                }
                locMap[loc].p += p;
                locMap[loc].hd += hd;
                locMap[loc].mis += mis;
                locMap[loc].a += a;
                locMap[loc].payable += payable;
            });
        }

        $("#sum_total_labours").text(totalLabours);
        $("#sum_man_count").text(totalMan);
        $("#sum_female_count").text(totalFemale);

        $("#sum_total_days").text(totalDays);

        $("#sum_total_p").text(totalP);
        $("#sum_p_man_count").text(maleP);
        $("#sum_p_female_count").text(femaleP);

        $("#sum_total_hd").text(totalHD);
        $("#sum_hd_man_count").text(maleHD);
        $("#sum_hd_female_count").text(femaleHD);

        $("#sum_total_a").text(totalA);
        $("#sum_a_man_count").text(maleA);
        $("#sum_a_female_count").text(femaleA);

        $("#sum_total_payable").text(totalPayable.toFixed(1).replace('.0', ''));
        $("#sum_payable_man_count").text(malePayable.toFixed(1).replace('.0', ''));
        $("#sum_payable_female_count").text(femalePayable.toFixed(1).replace('.0', ''));
        $("#pa_summary_cards").show();

        // Location breakdown table
        let locKeys = Object.keys(locMap);
        if ((!selectedLocation || selectedLocation === 'all') && locKeys.length > 0) {
            let locBody = "";
            locKeys.forEach((locName, idx) => {
                let item = locMap[locName];
                locBody += `
                    <tr>
                        <td class="text-center align-middle">${idx + 1}</td>
                        <td class="align-middle"><strong class="text-dark"><i class="ti ti-map-pin me-1 text-muted"></i>${locName}</strong></td>
                        <td class="text-center align-middle">
                            <span class="fw-bold text-dark d-block fs-14">${item.count}</span>
                            <div class="d-flex justify-content-center gap-1 mt-1">
                                <span class="badge badge-gender-m">M: ${item.man}</span>
                                <span class="badge badge-gender-w">W: ${item.female}</span>
                            </div>
                        </td>
                        <td class="text-center align-middle">
                            <span class="fw-bold text-success d-block fs-14">${item.p}</span>
                            <div class="d-flex justify-content-center gap-1 mt-1">
                                <span class="badge badge-gender-m">M: ${item.maleP}</span>
                                <span class="badge badge-gender-w">W: ${item.femaleP}</span>
                            </div>
                        </td>
                        <td class="text-center align-middle">
                            <span class="fw-bold text-warning d-block fs-14">${item.hd}</span>
                            <div class="d-flex justify-content-center gap-1 mt-1">
                                <span class="badge badge-gender-m">M: ${item.maleHD}</span>
                                <span class="badge badge-gender-w">W: ${item.femaleHD}</span>
                            </div>
                        </td>
                        <td class="text-center align-middle">
                            <span class="fw-bold text-danger d-block fs-14">${item.mis}</span>
                            <div class="d-flex justify-content-center gap-1 mt-1">
                                <span class="badge badge-gender-m">M: ${item.maleMIS}</span>
                                <span class="badge badge-gender-w">W: ${item.femaleMIS}</span>
                            </div>
                        </td>
                        <td class="text-center align-middle">
                            <span class="fw-bold text-danger d-block fs-14">${item.a}</span>
                            <div class="d-flex justify-content-center gap-1 mt-1">
                                <span class="badge badge-gender-m">M: ${item.maleA}</span>
                                <span class="badge badge-gender-w">W: ${item.femaleA}</span>
                            </div>
                        </td>
                        <td class="text-center align-middle">
                            <span class="fw-bold text-primary d-block fs-14">${item.payable.toFixed(1).replace('.0', '')}</span>
                            <div class="d-flex justify-content-center gap-1 mt-1">
                                <span class="badge badge-gender-m">M: ${item.malePayable.toFixed(1).replace('.0', '')}</span>
                                <span class="badge badge-gender-w">W: ${item.femalePayable.toFixed(1).replace('.0', '')}</span>
                            </div>
                        </td>
                    </tr>
                `;
            });
            $("#tblLocationSummary tbody").html(locBody);
            $("#card_location_summary").show();
        } else {
            $("#card_location_summary").hide();
        }
    }

    function initLocationList() {
        if (typeof getLabourSalaryLocationList === 'function') {
            getLabourSalaryLocationList("", "", function(res) {
                var lctlist = "<option value='all'>All Location</option>";
                if (res && res.length > 0) {
                    for (var i = 0; i < res.length; i++) {
                        let locName = (res[i].LocationName || '').toLowerCase().trim();
                        let locId = (res[i].LocationId || '').toString().toLowerCase().trim();
                        if (locName === "all" || locId === "all" || locName === "all location") {
                            continue;
                        }
                        lctlist += `<option value="${res[i].LocationId}">${res[i].LocationName}</option>`;
                    }
                }
                $("#pa_locationlist").html(lctlist).val("all");
            });
        }
    }

    $(document).ready(function() {
        initLocationList();

        $("#pa_searchInput").on("keyup", function() {
            var search = $(this).val().toLowerCase();
            $("#tblPAReport tbody tr").each(function() {
                var text = $(this).text().toLowerCase();
                $(this).toggle(text.indexOf(search) > -1);
            });
        });

        $("#btnGetPAReport").click(function() {
            var datefrom = $("#pa_datefrom").val();
            var dateto = $("#pa_dateto").val();
            var locationid = $("#pa_locationlist").val();
            var projectid = $("body").data("projectid");
            
            var df = new Date(datefrom);
            var dt = new Date(dateto);
            var ffrom = df.getDate() + "-" + df.toLocaleString('default', {month:'short'}) + "-" + df.getFullYear();
            var fto = dt.getDate() + "-" + dt.toLocaleString('default', {month:'short'}) + "-" + dt.getFullYear();
            $("#pa_report_header").text("Labour Attendance P/A Report For Period: " + ffrom + " To " + fto);
            
            fetchLabourPAReport(projectid, datefrom, dateto, locationid);
        });

        $("#btnGetPAReport").trigger('click');
    });
</script>
