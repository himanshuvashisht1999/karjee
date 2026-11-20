<?php
include('../header.php');
date_default_timezone_set("Asia/Calcutta");
?>
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

<style>
    /* Premium Table Header color matching screenshot */
    #dispatchTableHead th {
        background-color: #23b185 !important; /* Vibrant teal/green from screenshot */
        color: white !important;
        text-align: left;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 13px;
        letter-spacing: 0.5px;
        vertical-align: middle;
        border: none !important;
    }

    #dispatchplandetails {
        border-collapse: collapse;
        width: 100%;
        min-width: 1200px; /* Ensures columns stay properly aligned and scroll horizontally instead of clipping */
    }

    #dispatchplandetails td {
        vertical-align: middle;
        font-size: 14px;
        padding: 12px 10px;
    }
    
    .table-responsive {
        border-radius: 8px;
        overflow-x: auto; /* Enable horizontal scrolling on smaller viewports */
        border: 1px solid #dee2e6;
    }
    
    .table-hover tbody tr:hover {
        background-color: rgba(35, 177, 133, 0.05) !important;
    }

    /* Missing location badge/button styling */
    .location-missing-btn {
        background: #a1c4fd;
        color: #0b3d75;
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        text-align: center;
        display: inline-block;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    /* Week navigator buttons matching colors from screenshot */
    #dispatchPrevWeekBtn {
        background-color: #f3a614 !important; /* Orange/Yellow from screenshot */
        color: black !important;
        border: none;
        padding: 8px 16px;
        font-weight: 600;
        border-radius: 6px;
        transition: background-color 0.2s;
    }
    #dispatchPrevWeekBtn:hover {
        background-color: #d9910d !important;
    }

    #dispatchNextWeekBtn {
        background-color: #0c8c5e !important; /* Green from screenshot */
        color: white !important;
        border: none;
        padding: 8px 16px;
        font-weight: 600;
        border-radius: 6px;
        transition: background-color 0.2s;
    }
    #dispatchNextWeekBtn:hover {
        background-color: #096d49 !important;
    }

    #btnMarkDispatched {
        background-color: #0c8c5e !important;
        color: white !important;
        border: none;
        padding: 8px 16px;
        font-weight: 600;
        border-radius: 6px;
        transition: opacity 0.2s, background-color 0.2s;
    }
    #btnMarkDispatched:hover:not(:disabled) {
        background-color: #096d49 !important;
    }
    #btnMarkDispatched:disabled {
        opacity: 0.65;
        cursor: not-allowed;
    }

    #dispatchTableHead th.text-center {
        text-align: center !important;
    }

    /* Select controls */
    .form-control, .form-select {
        border-radius: 6px;
        border: 1px solid #ced4da;
        padding: 8px 12px;
        font-weight: 500;
    }

    /* Date display format styling */
    .date-highlight {
        font-weight: 500;
    }

    /* Custom Checkbox Styling to match modern look */
    .form-check-input {
        border-radius: 4px;
        border: 1.5px solid #23b185;
        width: 18px;
        height: 18px;
        cursor: pointer;
    }
    .form-check-input:checked {
        background-color: #0c8c5e !important;
        border-color: #0c8c5e !important;
    }
    .form-check-input:focus {
        border-color: #23b185;
        box-shadow: 0 0 0 0.25rem rgba(35, 177, 133, 0.25);
    }

    /* Reset global scaled/margin checkbox styles from header.php inside our dispatch table to prevent overlapping */
    #dispatchplandetails input[type="checkbox"].form-check-input {
        transform: none !important;
        -webkit-transform: none !important;
        -ms-transform: none !important;
        margin: 0 !important;
        position: relative !important;
        display: inline-block !important;
    }

    /* Enforce strict width and centering on checkbox column cells */
    #dispatchplandetails th:first-child,
    #dispatchplandetails td:first-child {
        width: 50px !important;
        min-width: 50px !important;
        max-width: 50px !important;
        text-align: center !important;
        vertical-align: middle !important;
    }

    /* Responsive title sizing */
    @media (max-width: 768px) {
        h3.page-title {
            font-size: 18px !important;
        }
    }

    /* Color styles for row highlight based on week status */
    .row-past-due {
        background-color: #fcebeb !important; /* light red/pink */
    }
    .row-current-week {
        background-color: #fff8e1 !important; /* light yellow */
    }
    .row-next-week {
        background-color: #e8f9f0 !important; /* light green */
    }
</style>

<div class="container-fluid my-4">
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h3 class="mb-0 text-dark font-weight-bold page-title" style="font-size: 24px; white-space: nowrap;">
                    Weekly Internal Dispatch Schedule
                </h3>
                <button class="btn btn-sm btn-outline-secondary" onclick="window.location.reload();">
                    <i class="fa fa-sync"></i> Refresh
                </button>
            </div>
        </div>
        <div class="card-body">
            <!-- Unified strip layout for selectors and buttons -->
            <div class="d-flex flex-wrap align-items-end gap-3 mb-3 p-3 bg-light rounded-3 shadow-sm border">
                <div style="min-width: 200px; flex: 1;">
                    <label class="form-label fw-bold text-secondary small text-uppercase mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Select Month</label>
                    <select id="dispatchMonthFilter" class="form-select" style="height: 42px;"></select>
                </div>
                <div style="min-width: 260px; flex: 1.5;">
                    <label class="form-label fw-bold text-secondary small text-uppercase mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Select Week</label>
                    <select id="dispatchWeekSelector" class="form-select" style="height: 42px;"></select>
                </div>
                <div class="d-flex gap-2 flex-wrap ms-md-auto align-items-center mt-2 mt-md-0">
                    <button class="btn" id="btnMarkDispatched" disabled style="height: 42px;">
                        <i class="fa fa-truck text-white me-1"></i> Mark Selected as Dispatched
                    </button>
                    <button class="btn" id="dispatchPrevWeekBtn" style="height: 42px;">← Previous Week</button>
                    <button class="btn" id="dispatchNextWeekBtn" style="height: 42px;">Next Week →</button>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="dispatchplandetails">
                            <thead id="dispatchTableHead">
                                <tr>
                                    <th class="text-center" style="width: 50px; vertical-align: middle;">
                                        <input type="checkbox" id="selectAllDispatch" class="form-check-input">
                                    </th>
                                    <th>Sowing Location</th>
                                    <th>Polyhouse Name</th>
                                    <th>Order No & Product Name</th>
                                    <th>Sowing Date</th>
                                    <th>Dispatch Date</th>
                                    <th>Variety of Seed</th>
                                    <th>Order Qty</th>
                                    <th>Sowing Qty</th>
                                    <th>Trays to Dispatch</th>
                                </tr>
                            </thead>
                            <tbody id="scheduleTable">
                                <tr>
                                    <td colspan="10" class="text-center py-4 text-muted">
                                        Loading schedule...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('../footer.php'); ?>
<script type="text/javascript" src="<?php echo $prefix; ?>krf-orderandsales/js/pjs_function.js"></script>

<script>
$(document).ready(function() {
    let globalWeekMap = {};
    let globalSortedWeeks = [];
    loadActiveDispatchDates();
    
    function loadActiveDispatchDates() {
        let projectid = $("body").data("projectid");
        
        $.ajax({
                            url: "modal/timetable_modal.php?action=GetActiveDispatchDates",
            type: "POST",
            data: { projectid: projectid },
            dataType: "json",
            success: function(dates) {
                console.log(dates);
                if (!Array.isArray(dates)) dates = [];
                
                // Map dates to Mondays (week starts)
                let weekMap = {};
                dates.forEach(item => {
                    if (!item.frsowingdate) return;
                    let d = new Date(item.frsowingdate);
                    let day = d.getDay();
                    let diffToMonday = (day === 0) ? -6 : (1 - day);
                    let monday = new Date(d);
                    monday.setDate(d.getDate() + diffToMonday);
                    monday.setHours(0, 0, 0, 0);

                    let sunday = new Date(monday);
                    sunday.setDate(monday.getDate() + 6);
                    sunday.setHours(23, 59, 59, 999);

                    let ws = monday.toISOString().split("T")[0];
                    let we = sunday.toISOString().split("T")[0];
                    weekMap[ws] = we;
                });
                
                let today = new Date();
                let curMonthStr = today.toISOString().substring(0, 7); // yyyy-mm
                
                let tDay = today.getDay();
                let tDiff = (tDay === 0) ? -6 : (1 - tDay);
                let tMon = new Date(today);
                tMon.setDate(today.getDate() + tDiff);
                let tSun = new Date(tMon);
                tSun.setDate(tMon.getDate() + 6);
                let tWS = tMon.toISOString().split("T")[0];
                let tWE = tSun.toISOString().split("T")[0];
                let currentWeekRange = `${tWS} to ${tWE}`;
                
                if (!weekMap[tWS]) {
                    weekMap[tWS] = tWE;
                }
                
                let sortedWeeks = Object.keys(weekMap).sort();
                globalWeekMap = weekMap;
                globalSortedWeeks = sortedWeeks;
                
                // Populate Month Filter
                let months = new Set();
                sortedWeeks.forEach(ws => months.add(ws.substring(0, 7)));
                months.add(curMonthStr);

                let monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
                
                $("#dispatchMonthFilter").html("");
                Array.from(months).sort().forEach(m => {
                    let [y, mi] = m.split("-");
                    let mName = monthNames[parseInt(mi) - 1];
                    let display = `${mName}-${y}`;
                    $("#dispatchMonthFilter").append(`<option value="${m}">${display}</option>`);
                });
                $("#dispatchMonthFilter").val(curMonthStr);
                
                // Populate weekSelector
                populateWeeks(globalWeekMap, globalSortedWeeks, currentWeekRange);
                
                // Setup change events
                $("#dispatchMonthFilter").off("change").on("change", function() {
                    populateWeeks(globalWeekMap, globalSortedWeeks, "first");
                });
            }
        });
    }

    function populateWeeks(weekMap, sortedWeeks, defaultWeek) {
        let selectedMonth = $("#dispatchMonthFilter").val();
        let displayWeeks = sortedWeeks.filter(ws => {
            if (selectedMonth === "all") return true;
            return ws.substring(0, 7) === selectedMonth;
        });
        
        let $week = $("#dispatchWeekSelector");
        $week.html("");
        displayWeeks.forEach(ws => {
            let range = `${ws} to ${weekMap[ws]}`;
            $week.append(`<option value="${range}">${range}</option>`);
        });
        
        let selectedWeek = defaultWeek;
        if (selectedWeek === "current" || selectedWeek === "first" || selectedWeek === "last") {
            let today = new Date();
            let tDay = today.getDay();
            let tDiff = (tDay === 0) ? -6 : (1 - tDay);
            let tMon = new Date(today);
            tMon.setDate(today.getDate() + tDiff);
            let tSun = new Date(tMon);
            tSun.setDate(tMon.getDate() + 6);
            let tWS = tMon.toISOString().split("T")[0];
            let tWE = tSun.toISOString().split("T")[0];
            let currentWeekRange = `${tWS} to ${tWE}`;
            
            if (selectedWeek === "current") {
                selectedWeek = currentWeekRange;
            } else if (selectedWeek === "first" && displayWeeks.length > 0) {
                selectedWeek = `${displayWeeks[0]} to ${weekMap[displayWeeks[0]]}`;
            } else if (selectedWeek === "last" && displayWeeks.length > 0) {
                let lastIdx = displayWeeks.length - 1;
                selectedWeek = `${displayWeeks[lastIdx]} to ${weekMap[displayWeeks[lastIdx]]}`;
            }
        }
        
        if ($week.find(`option[value="${selectedWeek}"]`).length === 0) {
            if (displayWeeks.length > 0) {
                selectedWeek = `${displayWeeks[0]} to ${weekMap[displayWeeks[0]]}`;
            } else {
                let today = new Date();
                let tDay = today.getDay();
                let tDiff = (tDay === 0) ? -6 : (1 - tDay);
                let tMon = new Date(today);
                tMon.setDate(today.getDate() + tDiff);
                let tSun = new Date(tMon);
                tSun.setDate(tMon.getDate() + 6);
                let tWS = tMon.toISOString().split("T")[0];
                let tWE = tSun.toISOString().split("T")[0];
                let currentWeekRange = `${tWS} to ${tWE}`;
                
                $week.append(`<option value="${currentWeekRange}">${currentWeekRange}</option>`);
                selectedWeek = currentWeekRange;
            }
        }
        
        $week.val(selectedWeek);
        loadDispatchSchedule(selectedWeek);
    }

    $("#dispatchPrevWeekBtn").off("click").on("click", function() {
        let select = document.getElementById("dispatchWeekSelector");
        if (select && select.selectedIndex > 0) {
            select.selectedIndex = select.selectedIndex - 1;
            $(select).trigger("change");
        } else {
            // We are at the first week of the month. Move to previous month.
            let monthSelect = document.getElementById("dispatchMonthFilter");
            if (monthSelect && monthSelect.selectedIndex > 0) {
                monthSelect.selectedIndex = monthSelect.selectedIndex - 1;
                populateWeeks(globalWeekMap, globalSortedWeeks, "last");
            }
        }
    });

    $("#dispatchNextWeekBtn").off("click").on("click", function() {
        let select = document.getElementById("dispatchWeekSelector");
        if (select && select.selectedIndex < select.options.length - 1) {
            select.selectedIndex = select.selectedIndex + 1;
            $(select).trigger("change");
        } else {
            // We are at the last week of the month. Move to next month.
            let monthSelect = document.getElementById("dispatchMonthFilter");
            if (monthSelect && monthSelect.selectedIndex < monthSelect.options.length - 1) {
                monthSelect.selectedIndex = monthSelect.selectedIndex + 1;
                populateWeeks(globalWeekMap, globalSortedWeeks, "first");
            }
        }
    });

    $("#dispatchWeekSelector").off("change").on("change", function() {
        loadDispatchSchedule($(this).val());
    });

    function loadDispatchSchedule(weekRange) {
        let projectid = $("body").data("projectid");
        let [datefrom, dateto] = weekRange.split(" to ");
        
        $.ajax({
                            url: "modal/timetable_modal.php?action=GetGraftingDispatchData",
            type: "POST",
            data: {
                projectid: projectid,
                datefrom: datefrom,
                dateto: dateto
            },
            dataType: "json",
            success: function(res) {
                renderDispatchTable(res, weekRange);
            }
        });
    }

    function renderDispatchTable(data, weekRange) {
        let tbody = $("#scheduleTable");
        tbody.html("");
        
        // Reset checkbox and buttons on week load
        $("#selectAllDispatch").prop("checked", false);
        $("#btnMarkDispatched").prop("disabled", true);
        
        if (!Array.isArray(data) || data.length === 0) {
            tbody.append(`
                <tr>
                    <td colspan="10" class="text-center py-4 text-muted fw-bold">
                        <i class="fa fa-info-circle mr-2"></i>No entries found for week: ${weekRange}
                    </td>
                </tr>
            `);
            return;
        }
        
        let today = new Date();
        today.setHours(0, 0, 0, 0);
        let todayStr = today.toISOString().split("T")[0];
        
        // Sort by dispatch date ASC
        data.sort((a, b) => new Date(a.dispatch_date) - new Date(b.dispatch_date));
        
        data.forEach((row, index) => {
            // Highlight logic
            let rowClass = "";
            let dispDateStr = row.dispatch_date;
            
            if (dispDateStr < todayStr) {
                rowClass = "row-past-due";
            } else if (dispDateStr === todayStr) {
                rowClass = "row-current-week";
            } else {
                let [ws, we] = weekRange.split(" to ");
                if (dispDateStr >= ws && dispDateStr <= we) {
                    // Falls in selected week
                    rowClass = "row-current-week";
                }
            }
            
            // Location column
            let locationCol = "";
            if (row.location_name && row.location_name !== "Location Missing") {
                locationCol = row.location_name;
            } else {
                locationCol = `<div class="location-missing-btn">Location Missing For Sowing</div>`;
            }
            
            // Polyhouse name column
            let polyhouseNameCol = row.polyhousename || "N/A";
            
            // Order No and Product Name in 1 Column
            let orderAndProductCol = `
                <div class="fw-bold text-dark">${row.productname}</div>
                <div class="text-muted small" style="font-size: 11px;">${row.stockorder_no || 'N/A'}</div>
            `;
            
            // Variety column
            let varietyCol = `${row.seed_name} (${row.roottype})`;
            
            // Format Dates
            let formattedSowingDate = changeDateFormatYmd_dMY_local(row.sowing_date_internal);
            let formattedDispatchDate = changeDateFormatYmd_dMY_local(row.dispatch_date);
            
            tbody.append(`
                <tr class="${rowClass}">
                    <td class="text-center" style="vertical-align: middle;">
                        <input type="checkbox" class="dispatch-select-row form-check-input" 
                               data-stockorderno="${row.stockorder_no || ''}"
                               data-productid="${row.productid || ''}"
                               data-roottype="${row.roottype || ''}"
                               data-polyhouseid="${row.polyhouseid || ''}"
                               data-dispatchdate="${row.dispatch_date || ''}">
                    </td>
                    <td>${locationCol}</td>
                    <td>${polyhouseNameCol}</td>
                    <td>${orderAndProductCol}</td>
                    <td class="fw-bold date-highlight">${formattedSowingDate}</td>
                    <td class="fw-bold date-highlight">${formattedDispatchDate}</td>
                    <td>${varietyCol}</td>
                    <td>${row.order_qty}</td>
                    <td class="fw-bold">${row.total_qty}</td>
                    <td class="fw-bold">${row.tray_count}</td>
                </tr>
            `);
        });
    }

    // Local Date Formatter fallback helper
    function changeDateFormatYmd_dMY_local(dateStr) {
        if (!dateStr) return "";
        let parts = dateStr.split("-");
        if (parts.length !== 3) return dateStr;
        
        let months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
        let y = parts[0];
        let m = parseInt(parts[1]) - 1;
        let d = parseInt(parts[2]);
        
        if (m >= 0 && m < 12) {
            let dStr = d < 10 ? '0' + d : d;
            return `${dStr}-${months[m]}-${y}`;
        }
        return dateStr;
    }

    // Select/Deselect All Checkboxes
    $(document).on("change", "#selectAllDispatch", function() {
        let checked = $(this).is(":checked");
        $(".dispatch-select-row").prop("checked", checked);
        toggleMarkDispatchedBtn();
    });

    // Individual Row Checkbox Change
    $(document).on("change", ".dispatch-select-row", function() {
        let allChecked = $(".dispatch-select-row").length === $(".dispatch-select-row:checked").length;
        $("#selectAllDispatch").prop("checked", allChecked);
        toggleMarkDispatchedBtn();
    });

    // Helper to enable/disable button
    function toggleMarkDispatchedBtn() {
        let anyChecked = $(".dispatch-select-row:checked").length > 0;
        $("#btnMarkDispatched").prop("disabled", !anyChecked);
    }

    // "Mark Selected as Dispatched" Button Click Handler
    $(document).on("click", "#btnMarkDispatched", function() {
        let selectedItems = [];
        $(".dispatch-select-row:checked").each(function() {
            selectedItems.push({
                stockorder_no: $(this).data("stockorderno"),
                productid: $(this).data("productid"),
                roottype: $(this).data("roottype"),
                polyhouseid: $(this).data("polyhouseid"),
                dispatch_date: $(this).data("dispatchdate")
            });
        });

        if (selectedItems.length === 0) {
            alert("Please select at least one item to mark as dispatched.");
            return;
        }

        if (confirm("Are you sure you want to mark " + selectedItems.length + " selected row(s) as Dispatched? This will update all matching trays.")) {
            let projectid = $("body").data("projectid");
            let loginid = $("body").data("loginid");
            
            $.ajax({
                                url: "modal/timetable_modal.php?action=MarkSelectedDispatched",
                type: "POST",
                data: {
                    projectid: projectid,
                    loginid: loginid,
                    items: selectedItems
                },
                dataType: "json",
                success: function(response) {
                    if (response.status === "success") {
                        alert(response.message);
                        // Reload schedule and dropdown dates
                        loadActiveDispatchDates();
                    } else {
                        alert("Error: " + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error(xhr, status, error);
                    alert("An error occurred while processing your request.");
                }
            });
        }
    });
});
</script>
