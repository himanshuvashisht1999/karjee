<?php
include('../header.php');
include('functions/product_functions.php');
include('functions/vendor_functions.php');
include('functions/seed_functions.php');

date_default_timezone_set("Asia/Calcutta");
$mdid = "";
if (isset($_GET['mdid'])) {
    $mdid = $_GET['mdid'];
}
$dddr = loginmodulesdetails_frommaster($authconn, $companyid, $subcompanyid, $projectid, "moduleid", $mdid);
$groupid = "";
if (count($dddr) > 0) {
    $groupid = $dddr[0]['groupid'];
}
?>
<style>
    #filterBox input {
        width: 200px;
    }
</style>
<div class="container-fluid my-4">
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h3 class="mb-0 text-primary font-weight-bold">
                        <i class="fa fa-calendar-check mr-2"></i>Production Sowing Schedule
                    </h3>
                </div>
                <div class="col-md-6 text-right">
                    <button class="btn btn-sm btn-success mr-2" id="exportExcelBtn">
                        <i class="fa fa-file-excel mr-1"></i> Export Excel
                    </button>
                    <button class="btn btn-sm btn-info" onclick="window.location.reload();">
                        <i class="fa fa-sync mr-1"></i> Refresh
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-3">
                    <label class="small text-muted font-weight-bold">Filter Month</label>
                    <select id="monthFilter" class="form-control">
                        <option value="all">All Months</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small text-muted font-weight-bold">Select Week Range</label>
                    <select id="weekSelector" class="form-control"></select>
                </div>
                <div class="col-md-6 d-flex align-items-end justify-content-end">
                    <div class="d-flex align-items-center mr-4 mb-1">
                        <span class="badge badge-danger px-2 py-1 mr-1" title="Sowing Date is in the past">Past Due</span>
                        <span class="badge badge-warning text-dark px-2 py-1 mr-1" title="Sowing falls in current week">Current Week</span>
                        <span class="badge badge-success px-2 py-1" title="Sowing falls in next week">Next Week</span>
                    </div>
                    <button class="btn btn-warning mr-2" id="prevWeekBtn">
                        <i class="fa fa-arrow-left mr-1"></i> Previous
                    </button>
                    <button class="btn btn-success" id="nextWeekBtn">
                        Next <i class="fa fa-arrow-right ml-1"></i>
                    </button>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="table-responsive border rounded">
                        <table class="table table-hover align-middle mb-0" id="scheduleDataTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Week Range</th>
                                    <th>Sowing Date</th>
                                    <th>Delivery Date</th>
                                    <th>Stock Order No</th>
                                    <th>Seed Name</th>
                                    <th>Root Type</th>
                                    <th>Client Name</th>
                                    <th class="text-center">Sowing Qty</th>
                                    <th class="text-center">Cycle Days</th>
                                </tr>
                            </thead>
                            <tbody id="scheduleTable"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include('../footer.php'); ?>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
    $(document).ready(function () {
        let projectid = $("body").data("projectid");
        let routurl = $("body").data("routurl");

        // Load on page start, auto-select current week
        loadSchedule("current");

        function loadSchedule(selectedWeek = "current") {
            var routurl = $('body').data('routurl');
            var companyid = $('body').data('companyid');
            var subcompanyid = $('body').data('subcompanyid');
            var projectid = $('body').data('projectid');
            var loginid = $('body').data('loginid');

            const formData = new FormData();
            formData.append('companyid', companyid);
            formData.append('subcompanyid', subcompanyid);
            formData.append('projectid', projectid);

            $.ajax({
                type: 'POST',
                url: `${routurl}krf-orderandsales/modal/sowing_modal.php?action=sowingProductLIst`,
                data: formData,
                contentType: false,
                processData: false,
                cache: false,
                dataType: 'JSON',
                success: function (data) {
                    let list = data;

                    if (typeof list === 'string') {
                        try { list = JSON.parse(list); }
                        catch (e) { console.error("Failed to parse JSON:", list); return; }
                    }

                    if (!Array.isArray(list)) {
                        console.error("Expected array but got:", typeof list);
                        return;
                    } 
                    let tbody = $("#scheduleTable");
                    tbody.html("");

                    //--------------------------------------
                    // 1?? Compute TODAY & CURRENT WEEK
                    //--------------------------------------
                    let todayObj = new Date();
                    let todayStr = todayObj.toISOString().split("T")[0];

                    let day = todayObj.getDay(); // 0=Sun,1=Mon...
                    let diffToMonday = (day === 0) ? -6 : (1 - day);

                    let currentMonday = new Date(todayObj);
                    currentMonday.setDate(todayObj.getDate() + diffToMonday);
                    currentMonday.setHours(0, 0, 0, 0);

                    let currentSunday = new Date(currentMonday);
                    currentSunday.setDate(currentMonday.getDate() + 6);
                    currentSunday.setHours(23, 59, 59, 999);

                    let currentStart = currentMonday.toISOString().split("T")[0];
                    let currentEnd = currentSunday.toISOString().split("T")[0];

                    let currentWeekRange = `${currentStart} to ${currentEnd}`;

                    // Next week Monday (relative to today)
                    let nextWeekMonday = new Date(currentMonday);
                    nextWeekMonday.setDate(currentMonday.getDate() + 7);
                    let nextWeekMondayStr = nextWeekMonday.toISOString().split("T")[0];

                    //--------------------------------------
                    // 2?? Apply MONTH FILTER
                    //--------------------------------------
                    let selectedMonth = $("#monthFilter").val() || "all";
                    let filteredList = list.filter(row => {
                        if (selectedMonth !== "all") {
                            let month = row.week_start.substring(0, 7); // yyyy-mm
                            if (month !== selectedMonth) return false;
                        }
                        return true;
                    });

                    //--------------------------------------
                    // 3?? Build WEEK LIST (UNIQUE + SORTED)
                    //--------------------------------------
                    let weekMap = {}; // week_start → week_end

                    filteredList.forEach(row => {
                        if (!weekMap[row.week_start]) {
                            weekMap[row.week_start] = row.week_end;
                        }
                    });

                    // sorted keys (ASC)
                    let sortedWeekStarts = Object.keys(weekMap).sort();

                    // Ensure current week exists in list ONLY IF "all" or current week is in selected month
                    let curStart = currentStart;
                    let curMonth = curStart.substring(0, 7);
                    if (selectedMonth === "all" || selectedMonth === curMonth) {
                        if (!sortedWeekStarts.includes(curStart)) {
                            sortedWeekStarts.push(curStart);
                            sortedWeekStarts.sort();
                            weekMap[curStart] = currentEnd;
                        }
                    }

                    //--------------------------------------
                    // 4?? Populate weekSelector dropdown
                    //--------------------------------------
                    let $week = $("#weekSelector");
                    $week.html("");

                    sortedWeekStarts.forEach(ws => {
                        let we = weekMap[ws];
                        let weekRange = `${ws} to ${we}`;
                        $week.append(`<option value="${weekRange}">${weekRange}</option>`);
                    });

                    //--------------------------------------
                    // 5️⃣ Select week correctly
                    //--------------------------------------
                    if (selectedWeek === "first") {
                        if (sortedWeekStarts.length > 0) {
                            selectedWeek = `${sortedWeekStarts[0]} to ${weekMap[sortedWeekStarts[0]]}`;
                        } else {
                            selectedWeek = currentWeekRange;
                        }
                    } else if (selectedWeek === "current" || selectedWeek === "currentWeek") {
                        selectedWeek = currentWeekRange;
                    }

                    // if selectedWeek not found → fall back to first available or currentWeekRange
                    if ($week.find(`option[value="${selectedWeek}"]`).length === 0) {
                        if (sortedWeekStarts.length > 0) {
                            selectedWeek = `${sortedWeekStarts[0]} to ${weekMap[sortedWeekStarts[0]]}`;
                        } else {
                            selectedWeek = currentWeekRange;
                        }
                    }

                    $week.val(selectedWeek);

                    //--------------------------------------
                    // 6️⃣ Filter rows for selected week
                    //--------------------------------------
                    let weekFiltered = filteredList.filter(row => {
                        return (`${row.week_start} to ${row.week_end}`) === selectedWeek;
                    });

                    //--------------------------------------
                    // 7️⃣ No entries → show message
                    //--------------------------------------
                    if (weekFiltered.length === 0) {
                        tbody.append(`
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted font-weight-bold">
                                    <i class="fa fa-info-circle mr-2"></i>No entries found for week: ${selectedWeek}
                                </td>
                            </tr>
                        `);
                        populateMonths(list);
                        return;
                    }


                    //--------------------------------------
                    // 9️⃣ Display rows
                    //--------------------------------------
                    let lastWeekRange = "";

                    weekFiltered.forEach(row => {
                        let rowClass = "";
                        if (row.sowing_start_date < todayStr) rowClass = "table-danger";
                        else if (todayStr >= row.week_start && todayStr <= row.week_end) rowClass = "table-warning";
                        else if (row.week_start === nextWeekMondayStr) rowClass = "table-success";
                        else rowClass = "";

                        tbody.append(`
                            <tr class="${rowClass}">
                                <td class="small">${selectedWeek}</td>
                                <td class="fw-bold">${changeDateFormatYmd_dMY(row.sowing_start_date)}</td>
                                <td>${changeDateFormatYmd_dMY(row.expected_delivery_date)}</td>
                                <td class="small fw-bold">${row.stockorder_no}</td>
                                <td class="fw-bold text-primary">${row.seedname || 'N/A'}</td>
                                <td>
                                    <span class="badge ${row.roottype === 'Root' ? 'badge-info' : 'badge-dark'} px-2">
                                        ${row.roottype}
                                    </span>
                                </td>
                                <td>${row.client_name}</td>
                                <td class="text-center fw-bold">${row.balqty}</td>
                                <td class="text-center">${row.growthperiod} Days</td>
                            </tr>
                        `);
                    });

                    populateMonths(list);
                }
            });
        }

        function populateMonths(list) {
            let months = new Set();
            list.forEach(row => months.add(row.week_start.substring(0, 7)));

            let $month = $("#monthFilter");
            let currentValue = $month.val() || "all";

            $month.html(`<option value="all">All Months</option>`);
            Array.from(months).sort().forEach(m => {
                $month.append(`<option value="${m}">${m}</option>`);
            });

            if ($month.find(`option[value="${currentValue}"]`).length) {
                $month.val(currentValue);
            }
        }

        $("#monthFilter").on("change", function () {
            loadSchedule("first");
        });

        $("#weekSelector").on("change", function () {
            loadSchedule($(this).val());
        });

        $("#prevWeekBtn").click(function () {
            let $opt = $("#weekSelector option");
            let cur = $("#weekSelector").val();
            let idx = $opt.index($opt.filter(`[value="${cur}"]`));
            if (idx > 0) {
                $("#weekSelector").val($opt.eq(idx - 1).val()).trigger("change");
            }
        });

        $("#nextWeekBtn").click(function () {
            let $opt = $("#weekSelector option");
            let cur = $("#weekSelector").val();
            let idx = $opt.index($opt.filter(`[value="${cur}"]`));
            if (idx < $opt.length - 1) {
                $("#weekSelector").val($opt.eq(idx + 1).val()).trigger("change");
            }
        });

        // Export Excel
        $("#exportExcelBtn").on("click", function () {
            let table = document.getElementById("scheduleDataTable");
            let wb = XLSX.utils.table_to_book(table, { sheet: "Sowing Schedule" });
            let fileName = "Sowing_Schedule_" + new Date().toISOString().split('T')[0] + ".xlsx";
            XLSX.writeFile(wb, fileName);
        });
    });
</script>
