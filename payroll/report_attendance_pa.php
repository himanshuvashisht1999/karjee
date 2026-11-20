<?php
if (isset($_GET['vw']) == 1) {
    $key = $_GET['k'];
    $rooturl = "";
} else {
    require('header.php');
}
?>
<div class="container-fluid mt-3">
    <div class="card hrm-card">
        <div class="card-header">
            <h4><i class="ti ti-file-analytics me-2"></i> Attendance P/A Report</h4>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="hrm-label">From Date</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="ti ti-calendar"></i></span>
                        <input type="date" class="form-control hrm-input" id="pa_datefrom" value="<?php echo date('Y-m-01'); ?>">
                    </div>
                </div>
                <div class="col-md-5">
                    <label class="hrm-label">To Date</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="ti ti-calendar"></i></span>
                        <input type="date" class="form-control hrm-input" id="pa_dateto" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100" id="btnGetPAReport">Get Report</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Table Card -->
    <div class="card hrm-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4><i class="ti ti-list-check me-2"></i> <span id="pa_report_header">Report Data</span></h4>
            <div class="d-flex align-items-center gap-2">
                <div class="dropdown">
                    <button class="btn btn-light btn-sm shadow-sm text-primary fw-bold dropdown-toggle d-inline-flex align-items-center" data-bs-toggle="dropdown">
                        <i class="ti ti-file-export me-1"></i> Export
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                        <li><button class="dropdown-item py-2" onclick="exportPAReportPDF()"><i class="ti ti-file-type-pdf me-2 text-danger"></i> Export PDF</button></li>
                        <li><button class="dropdown-item py-2" onclick="exportPAReportExcel()"><i class="ti ti-file-type-xls me-2 text-success"></i> Export Excel</button></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="d-flex align-items-center justify-content-end gap-2 p-2 bg-light border-bottom">
                <select id="pa_filterDept" class="form-select form-select-sm" style="width: 160px;"><option value="">All Depts</option></select>
                <select id="pa_filterDesg" class="form-select form-select-sm" style="width: 160px;"><option value="">All Desgs</option></select>
                <input type="text" id="pa_searchInput" class="form-control form-control-sm" placeholder="Search in table..." style="width: 200px;">
            </div>
            <div class="hrm-table-wrapper" style="max-height: 500px; overflow-y: auto;">
                <table class="table hrm-table table-hover table-bordered mb-0 text-nowrap" id="tblPAReport">
                    <thead id="tblPAReportHead">
                        <tr>
                            <th class="text-center">S.No</th>
                            <th>Employee Code</th>
                            <th>Employee Name</th>
                            <th class="export-col" style="display:none;">Dept</th>
                            <th class="export-col" style="display:none;">Desg</th>
                            <!-- dynamic dates will go here -->
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
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
    function exportPAReportPDF() {
        $('.export-col').show();
        $('.emp-name-badge').hide();
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF("l", "mm", "a2");
        doc.setFontSize(14);
        var title = $("#pa_report_header").text();
        doc.text(title, 14, 15);
        
        const table = document.getElementById("tblPAReport");
        const clone = table.cloneNode(true);
        const badges = clone.querySelectorAll('.emp-name-badge');
        badges.forEach(badge => badge.remove());
        
        const presentCells = clone.querySelectorAll('.col-present-cell');
        presentCells.forEach(cell => {
            if (cell.hasAttribute('data-total-present')) {
                cell.textContent = cell.getAttribute('data-total-present');
            }
        });
        
        doc.autoTable({
            html: clone,
            startY: 22,
            theme: "grid",
            styles: { fontSize: 7, cellPadding: 1 },
            headStyles: { fillColor: [41, 128, 185] }
        });
        doc.save("Attendance_PA_Report.pdf");
        $('.export-col').hide();
        $('.emp-name-badge').show();
    }

    function exportPAReportExcel() {
        $('.export-col').show();
        $('.emp-name-badge').hide();
        const table = document.getElementById("tblPAReport");
        if (!table) return;
        const clone = table.cloneNode(true);
        const badges = clone.querySelectorAll('.emp-name-badge');
        badges.forEach(badge => badge.remove());

        const presentCells = clone.querySelectorAll('.col-present-cell');
        presentCells.forEach(cell => {
            if (cell.hasAttribute('data-total-present')) {
                cell.textContent = cell.getAttribute('data-total-present');
            }
        });

        const ws = XLSX.utils.table_to_sheet(clone, { origin: "A3" });
        var title = $("#pa_report_header").text();
        XLSX.utils.sheet_add_aoa(ws, [[title]], { origin: "A1" });
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Report");
        XLSX.writeFile(wb, "Attendance_PA_Report.xlsx");
        $('.export-col').hide();
        $('.emp-name-badge').show();
    }

    $(document).ready(function() {
        function applyFilters() {
            var search = $("#pa_searchInput").val().toLowerCase();
            var dept = $("#pa_filterDept").val().toLowerCase();
            var desg = $("#pa_filterDesg").val().toLowerCase();
            
            $("#tblPAReport tbody tr").each(function() {
                var text = $(this).text().toLowerCase();
                var rowDept = $(this).find('.export-col').eq(0).text().toLowerCase();
                var rowDesg = $(this).find('.export-col').eq(1).text().toLowerCase();
                
                var matchSearch = text.indexOf(search) > -1;
                var matchDept = (dept === "" || rowDept === dept);
                var matchDesg = (desg === "" || rowDesg === desg);
                
                $(this).toggle(matchSearch && matchDept && matchDesg);
            });
        }

        $("#pa_searchInput").on("keyup", applyFilters);
        $("#pa_filterDept, #pa_filterDesg").on("change", applyFilters);

        $("#btnGetPAReport").click(function() {
            var datefrom = $("#pa_datefrom").val();
            var dateto = $("#pa_dateto").val();
            var projectid = $("body").data("projectid");
            
            var df = new Date(datefrom);
            var dt = new Date(dateto);
            var ffrom = df.getDate() + "-" + df.toLocaleString('default', {month:'short'}) + "-" + df.getFullYear();
            var fto = dt.getDate() + "-" + dt.toLocaleString('default', {month:'short'}) + "-" + dt.getFullYear();
            $("#pa_report_header").text("Monthly Attendance P/A Report For Period : " + ffrom + " To " + fto);
            
            getAttendancePAReport(projectid, datefrom, dateto);
        });
        // Initial load
        $("#btnGetPAReport").trigger('click');
    });
</script>
