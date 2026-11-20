<?php
if (isset($_GET['vw']) == 1) {
    $key = $_GET['k'];
    $rooturl = "";
} else {
    require('header.php');
}
?>
<style>
    #attendanceviewbyadmin thead th.att-sortable {
        cursor: pointer;
        user-select: none;
        white-space: nowrap;
    }

    .holiday-card {
        width: 100%;
        margin: 10px auto;
        border-radius: 15px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .table thead th {
        background-color: #0d6efd;
        color: #fff;
        vertical-align: middle;
        white-space: nowrap;
    }

    .filter-row th {
        background-color: #f8f9fa !important;
        color: #000 !important;
    }

    @media (max-width: 768px) {
        .table-responsive {
            font-size: 13px;
        }
    }

    /* ===========================
       GRID (MOBILE FIRST & DESKTOP)
    =========================== */
    .kpi-row-compact {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
    }

    @media (min-width: 992px) {
        .kpi-row-compact {
            grid-template-columns: repeat(6, 1fr);
        }
    }

    /* ===========================
       LIGHT KPI CARDS BY DEFAULT (COMPACT)
    =========================== */
    .kpi-card.compact {
        padding: 6px 8px;
        min-height: 48px;
        border-radius: 8px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 1px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #334155;
        cursor: pointer;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.03);
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
    }

    .kpi-card.compact .kpi-icon {
        font-size: 14px;
        color: #64748b;
        transition: color 0.2s ease;
    }

    .kpi-card.compact .kpi-label {
        font-size: 10.5px;
        font-weight: 600;
        letter-spacing: 0.2px;
        color: #64748b;
        transition: color 0.2s ease;
    }

    .kpi-card.compact .counter {
        font-size: 15px;
        font-weight: 800;
        line-height: 1.1;
        color: #0f172a;
        transition: color 0.2s ease;
    }

    .kpi-card.compact:hover {
        transform: translateY(-2px);
        background: #f1f5f9;
        border-color: #cbd5e1;
    }

    /* ===========================
       ACTIVE CARD: DARK & ANIMATED
    =========================== */
    .kpi-card.compact.active {
        border-color: transparent !important;
        color: #ffffff !important;
        transform: translateY(-2px) scale(1.03);
        box-shadow: 0 8px 18px rgba(0, 0, 0, 0.2);
        animation: kpiActivePulse 2.2s infinite ease-in-out;
        z-index: 2;
    }

    .kpi-card.compact.active .kpi-icon,
    .kpi-card.compact.active .kpi-label,
    .kpi-card.compact.active .counter {
        color: #ffffff !important;
    }

    @keyframes kpiActivePulse {
        0% {
            transform: translateY(-2px) scale(1.03);
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.2);
        }
        50% {
            transform: translateY(-4px) scale(1.05);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.28);
        }
        100% {
            transform: translateY(-2px) scale(1.03);
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.2);
        }
    }

    /* Vibrant Dark Themes when Active */
    .kpi-card[data-view="markinemployeedata"].active {
        background: linear-gradient(135deg, #0e7490, #06b6d4) !important;
    }

    .kpi-card[data-view="presentemployeedata"].active {
        background: linear-gradient(135deg, #15803d, #22c55e) !important;
    }

    .kpi-card[data-view="misemployeedata"].active {
        background: linear-gradient(135deg, #c2410c, #f97316) !important;
    }

    .kpi-card[data-view="lateemployeedata"].active {
        background: linear-gradient(135deg, #b45309, #eab308) !important;
    }

    .kpi-card[data-view="earlylogoutdata"].active {
        background: linear-gradient(135deg, #b91c1c, #ef4444) !important;
    }

    .kpi-card[data-view="permissiondata"].active {
        background: linear-gradient(135deg, #1d4ed8, #3b82f6) !important;
    }

    .kpi-card[data-view="absentdata"].active {
        background: linear-gradient(135deg, #6b21a8, #a855f7) !important;
    }

    .kpi-card[data-view="uninformeddata"].active {
        background: linear-gradient(135deg, #1f2937, #374151) !important;
    }
</style>

<div class="container-fluid mt-3" id="employeesalarycontainer">
    

    <!-- Filter Card -->
    <div class="card hrm-card">
        <div class="card-header">
            <h4><i class="ti ti-filter me-2"></i>Attendance Filters</h4>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="hrm-label" for="daterangefiter" style="cursor: pointer;">Date</label>
                    <div class="input-group" id="dateFilterWrap" style="cursor: pointer;">
                        <span class="input-group-text bg-white"><i class="ti ti-calendar"></i></span>
                        <input type="text" class="form-control hrm-input" id="daterangefiter"
                            placeholder="Select date..." readonly style="cursor: pointer; background-color: #fff;">
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="hrm-label">Employee Filter</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="ti ti-users"></i></span>
                        <select class="form-select hrm-select" id="EmployeeListdrp"></select>
                    </div>
                </div>
            </div>

            <!-- KPI Row -->
            <div class="row mt-4 g-2">
                <div class="col-12">
                    <div class="kpi-row kpi-row-compact">
                        <div class="kpi-card compact active" data-view="markinemployeedata">
                            <i class="ti ti-user-check kpi-icon"></i>
                            <span class="kpi-label">Mark-In</span>
                            <span class="counter" data-key="markinemployeecount">0</span>
                        </div>
                        <div class="kpi-card compact" data-view="presentemployeedata">
                            <i class="ti ti-user-check kpi-icon"></i>
                            <span class="kpi-label">Present</span>
                            <span class="counter" data-key="presentemployeecount">0</span>
                        </div>
                        <div class="kpi-card compact" data-view="misemployeedata">
                            <i class="ti ti-alert-triangle kpi-icon"></i>
                            <span class="kpi-label">MIS Attendance</span>
                            <span class="counter" data-key="misemployeecount">0</span>
                        </div>
                        <div class="kpi-card compact" data-view="lateemployeedata">
                            <i class="ti ti-clock-exclamation kpi-icon"></i>
                            <span class="kpi-label">Late</span>
                            <span class="counter" data-key="lateemployeecount">0</span>
                        </div>
                        <div class="kpi-card compact" data-view="earlylogoutdata">
                            <i class="ti ti-logout kpi-icon"></i>
                            <span class="kpi-label">Early Out</span>
                            <span class="counter" data-key="earlylogout">0</span>
                        </div>
                        <div class="kpi-card compact" data-view="absentdata">
                            <i class="ti ti-user-x kpi-icon"></i>
                            <span class="kpi-label">Absent</span>
                            <span class="counter" data-key="absent">0</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Attendance Table Card -->
    <div class="card hrm-card">
        <div class="card-header">
            <h4><i class="ti ti-list-check me-2"></i><span id="employeeattheader">Employee Attendance</span></h4>
            <div class="dropdown">
                <button
                    class="btn btn-light btn-sm shadow-sm text-primary fw-bold dropdown-toggle d-inline-flex align-items-center"
                    data-bs-toggle="dropdown">
                    <i class="ti ti-file-export me-1"></i> Export
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                    <li><button class="dropdown-item py-2"
                            onclick="exportEmployeePDF($('#employeeattheader').html())"><i
                                class="ti ti-file-type-pdf me-2 text-danger"></i> Export PDF</button></li>
                    <li><button class="dropdown-item py-2"
                            onclick="exportEmployeeExcel($('#employeeattheader').html())"><i
                                class="ti ti-file-type-xls me-2 text-success"></i> Export Excel</button></li>
                </ul>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="p-3 bg-light border-bottom">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i
                                    class="ti ti-search text-muted"></i></span>
                            <input type="search" class="form-control border-start-0" placeholder="Search attendance..."
                                id="searchrptlvap" oninput="filterTable('#searchrptlvap', '#attendanceviewbyadmin');">
                        </div>
                    </div>
                    <div class="col-md-4 d-none" id="absentSubFilterContainer">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i
                                    class="ti ti-filter text-muted"></i></span>
                            <select class="form-select border-start-0" id="absentSubFilter" onchange="filterAbsentList();">
                                <option value="">All Absent Statuses</option>
                                <option value="Permission">Permission</option>
                                <option value="Uninformed">Uninformed</option>
                                <option value="Weekoff">Weekoff</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="hrm-table-wrapper">
                <table class="table hrm-table table-hover mb-0" id="attendanceviewbyadmin">
                    <thead>
                        <tr>
                            <th class="text-center att-sortable" data-sort="sno" title="Reset to original order">S.No<i class="ti ti-arrows-sort opacity-50 att-sort-icon ms-1"></i></th>
                            <th class="att-sortable" data-sort="employeeid">Employee ID<i class="ti ti-arrows-sort opacity-50 att-sort-icon ms-1"></i></th>
                            <th class="att-sortable" data-sort="empoyeename">Employee Name<i class="ti ti-arrows-sort opacity-50 att-sort-icon ms-1"></i></th>
                            <th class="att-sortable" data-sort="attendancedate">Attendance Date<i class="ti ti-arrows-sort opacity-50 att-sort-icon ms-1"></i></th>
                            <th class="att-sortable" data-sort="markin">Mark In<i class="ti ti-arrows-sort opacity-50 att-sort-icon ms-1"></i></th>
                            <th class="att-sortable" data-sort="markout">Mark Out<i class="ti ti-arrows-sort opacity-50 att-sort-icon ms-1"></i></th>
                            <th class="att-sortable" data-sort="workingduration">Duration<i class="ti ti-arrows-sort opacity-50 att-sort-icon ms-1"></i></th>
                            <th class="att-sortable" data-sort="status">Status<i class="ti ti-arrows-sort opacity-50 att-sort-icon ms-1"></i></th>
                            <th class="att-sortable" data-sort="location">Location/Device<i class="ti ti-arrows-sort opacity-50 att-sort-icon ms-1"></i></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
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
    let companyid = $("body").data("companyid");
    let subcompanyid = $("body").data("subcompanyid");
    let projectid = $("body").data("projectid");
    function exportEmployeePDF(titleText = "Employee Attendance Report") {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF("l", "mm", "a4");
        doc.setFontSize(14);
        doc.text(titleText, 14, 15);
        doc.autoTable({
            html: "#attendanceviewbyadmin",
            startY: 22,
            theme: "grid",
            styles: {
                fontSize: 9
            },
            headStyles: {
                fillColor: [41, 128, 185]
            },
            didParseCell: function (data) {

                // Remove no-export columns
                if (
                    data.cell.raw &&
                    data.cell.raw.classList &&
                    data.cell.raw.classList.contains("no-export")
                ) {
                    data.cell.text = "";
                    data.cell.styles.cellPadding = 0;
                    data.cell.styles.lineWidth = 0;
                }
            }
        });

        doc.save("EmployeeAttendanceList.pdf");
    }


    function exportEmployeeExcel(titleText = "Employee Attendance List") {
        const table = document.getElementById("attendanceviewbyadmin");
        if (!table) {
            Swal.fire({
                icon: 'error',
                title: 'Export Failed',
                text: 'Attendance table not found.'
            });
            return;
        }

        // Clone table
        const clone = table.cloneNode(true);
        // Remove no-export columns
        clone.querySelectorAll(".no-export").forEach(el => el.remove());
        // Convert table to worksheet (ONLY ONCE)
        const ws = XLSX.utils.table_to_sheet(clone, { origin: "A3" });
        // Add title at top
        XLSX.utils.sheet_add_aoa(ws, [[titleText]], { origin: "A1" });
        // Create workbook
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Employees");
        // Export file
        XLSX.writeFile(wb, "EmployeeAttendance_List.xlsx");
    }


    // 📅 Initialize Single Date Picker
    $('#daterangefiter').daterangepicker({
        singleDatePicker: true,
        showDropdowns: true,
        locale: {
            format: 'MM/DD/YYYY'
        }
    });

    // Open calendar on clicking anywhere inside the date input box / container
    $(document).on("click", "#dateFilterWrap, #daterangefiter", function (e) {
        let drp = $('#daterangefiter').data('daterangepicker');
        if (drp && !drp.isShowing) {
            drp.show();
        }
    });

    $(document).on("change", "#daterangefiter", function () {
        loadAttendanceSummary($("#daterangefiter").val());
    });

    // 👥 Employee Filter change handler
    $(document).on("change", "#EmployeeListdrp", function () {
        let $activeCard = $(".kpi-card.active");
        if ($activeCard.length) {
            let dataKey = $activeCard.data("view");
            let headerText = getKpiHeaderText(dataKey);
            renderAttendanceTable(dataKey, headerText);
        }
    });

    // Initial load
    loadAttendanceSummary($("#daterangefiter").val());

    // 🔁 Auto refresh every 30 seconds
    setInterval(function () {
        loadAttendanceSummary($("#daterangefiter").val());
    }, 30000);

    $(document).on("click", ".kpi-card", function () {
        $(".kpi-card").removeClass("active");
        $(this).addClass("active");
        let dataKey = $(this).data("view");
        let headerText = getKpiHeaderText(dataKey);
        renderAttendanceTable(dataKey, headerText);
    });



    function filterAbsentList() {
        let $activeCard = $(".kpi-card.active");
        if ($activeCard.length) {
            let dataKey = $activeCard.data("view");
            let headerText = getKpiHeaderText(dataKey);
            renderAttendanceTable(dataKey, headerText);
        }
    }

    function animateCounter(el, target) {
        let start = 0;
        let duration = 800;
        let increment = target / (duration / 16);
        let counter = setInterval(() => {
            start += increment;
            if (start >= target) {
                el.textContent = target;
                clearInterval(counter);
            } else {
                el.textContent = Math.floor(start);
            }
        }, 16);
    }

</script>