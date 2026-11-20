<?php include('header.php'); ?>

<style>
    :root {
        --cal-header-bg: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
        /* Dark Blue */
        --cal-accent: #0d6efd;
        /* Yellow for highlight */
        --cal-today-bg: #0d6efd;
        --cal-today-text: #ffffff;
        --cal-border: #e2e8f0;
        --sidebar-bg: #f8fafc;
    }

    #employeesalarycontainer {
        max-width: 1300px;
        margin-bottom: 30px !important;
    }

    .calendar-layout {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }

    .calendar-main {
        flex: 1;
        min-width: 300px;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        border: 1px solid var(--cal-border);
    }

    .calendar-sidebar {
        width: 320px;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        border: 1px solid var(--cal-border);
        border-left: 4px solid #0d6efd;
        display: flex;
        flex-direction: column;
    }

    /* Calendar Header */
    .cal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        background: #fff;
    }

    .cal-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
    }

    .cal-nav-btn {
        background: #fff;
        border: 1.5px solid #0d6efd;
        color: #0d6efd;
        padding: 5px 15px;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }

    .cal-nav-btn:hover {
        background: #0d6efd;
        color: #fff;
    }

    /* Grid Styling */
    .cal-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
    }

    .cal-day-header {
        background: var(--cal-header-bg);
        color: #fff;
        text-align: center;
        padding: 15px 5px;
        font-weight: 600;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .cal-cell {
        aspect-ratio: 1.5 / 1;
        border-right: 1px solid #f1f5f9;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        position: relative;
        padding: 10px;
        cursor: default;
    }

    .cal-cell:nth-child(7n) {
        border-right: none;
    }

    .cal-date-num {
        font-size: 1.1rem;
        font-weight: 500;
        color: #475569;
        width: 35px;
        height: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        z-index: 1;
        transition: all 0.2s;
    }

    .cal-cell.today .cal-date-num {
        background: var(--cal-today-bg);
        color: var(--cal-today-text);
        font-weight: 700;
        box-shadow: 0 4px 10px rgba(13, 110, 253, 0.3);
    }

    .cal-cell.is-holiday .cal-date-num {
        color: #dc3545;
        font-weight: 700;
        position: relative;
    }

    .cal-cell.is-holiday::after {
        content: '';
        position: absolute;
        bottom: 8px;
        width: 5px;
        height: 5px;
        background: #dc3545;
        border-radius: 50%;
    }

    .cal-cell.other-month {
        background: #fafafa;
    }

    .cal-cell.other-month .cal-date-num {
        color: #cbd5e1;
    }

    /* Sidebar Content */
    .sidebar-header {
        padding: 20px;
        border-bottom: 1px solid #f1f5f9;
    }

    .sidebar-header h4 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 700;
        color: #0d6efd;
    }

    .sidebar-body {
        padding: 20px;
        flex: 1;
        overflow-y: auto;
    }

    .event-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .event-item {
        margin-bottom: 15px;
        display: flex;
        gap: 10px;
        align-items: flex-start;
    }

    .event-bullet {
        margin-top: 6px;
        width: 6px;
        height: 6px;
        min-width: 6px;
        background: #334155;
        border-radius: 50%;
    }

    .event-content {
        font-size: 0.95rem;
        color: #334155;
    }

    .event-date {
        display: block;
        font-size: 0.8rem;
        color: #64748b;
        font-weight: 600;
    }

    .no-events {
        color: #94a3b8;
        font-style: italic;
    }

    @media (max-width: 992px) {
        .calendar-sidebar {
            width: 100%;
        }
    }
</style>

<div class="container mt-2" id="employeesalarycontainer">
    <div class="row mb-3 align-items-center">
        <div class="col-md-6">
            <h4 class="mb-0 fw-bold text-dark"><i class="ti ti-calendar-event me-2 text-primary"></i>Holiday Calendar
            </h4>
        </div>
        <div class="col-md-6 d-flex justify-content-end align-items-center gap-2">
            <div class="year-selector-container">
                <select class="form-select form-select-sm rounded-pill px-3 shadow-sm border-primary" id="holidayyear"
                    style="min-width: 120px;"></select>
            </div>
        </div>
    </div>

    <div class="calendar-layout">
        <!-- Main Calendar Grid -->
        <div class="calendar-main">
            <div class="cal-header">
                <button class="cal-nav-btn" id="prevMonth">&larr; Prev</button>
                <h3 class="cal-title" id="monthDisplay">Month Year</h3>
                <button class="cal-nav-btn" id="nextMonth">Next &rarr;</button>
            </div>
            <div class="cal-grid" id="calendarGrid">
                <!-- Day Headers -->
                <div class="cal-day-header">Sun</div>
                <div class="cal-day-header">Mon</div>
                <div class="cal-day-header">Tue</div>
                <div class="cal-day-header">Wed</div>
                <div class="cal-day-header">Thu</div>
                <div class="cal-day-header">Fri</div>
                <div class="cal-day-header">Sat</div>
                <!-- Dates will be injected here -->
            </div>
        </div>

        <!-- Utility Sidebar -->
        <div class="calendar-sidebar">
            <div class="sidebar-header">
                <h4>All Events Of Utility</h4>
            </div>
            <div class="sidebar-body">
                <ul class="event-list" id="eventList">
                    <li class="no-events">Loading events...</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>

<script type="text/javascript">
    let currentDate = new Date();
    let allHolidays = [];

    $(document).ready(function () {
        loadYearDropdown();
    });

    function loadYearDropdown() {
        fetchHolidayYearsFromMaster_callback((res) => {
            const $dropdown = $("#holidayyear");
            const currentYear = new Date().getFullYear();
            $dropdown.empty();

            if (Array.isArray(res) && res.length > 0) {
                res.forEach(year => {
                    const selected = (year == currentYear) ? "selected" : "";
                    $dropdown.append(`<option value="${year}" ${selected}>Year ${year}</option>`);
                });
                initCalendar();
            } else {
                $dropdown.append(`<option value="${currentYear}" selected>Year ${currentYear}</option>`);
                initCalendar();
            }
        });
    }

    async function initCalendar() {
        const selectedYear = $("#holidayyear").val() || currentDate.getFullYear();
        await fetchHolidays(selectedYear);
        renderCalendar();

        $("#prevMonth").click(() => {
            let oldYear = currentDate.getFullYear();
            currentDate.setMonth(currentDate.getMonth() - 1);
            syncYearDropdown(oldYear);
            renderCalendar();
        });

        $("#nextMonth").click(() => {
            let oldYear = currentDate.getFullYear();
            currentDate.setMonth(currentDate.getMonth() + 1);
            syncYearDropdown(oldYear);
            renderCalendar();
        });

        $("#holidayyear").change(async function () {
            const newYear = parseInt($(this).val());
            currentDate.setFullYear(newYear);
            await fetchHolidays(newYear);
            renderCalendar();
        });
    }

    function syncYearDropdown(oldYear) {
        const newYear = currentDate.getFullYear();
        if (newYear !== oldYear) {
            if ($("#holidayyear option[value='" + newYear + "']").length === 0) {
                $("#holidayyear").append(`<option value="${newYear}">Year ${newYear}</option>`);
            }
            $("#holidayyear").val(newYear);
            fetchHolidays(newYear);
        }
    }

    function fetchHolidays(year) {
        return new Promise((resolve) => {
            fetchHolidayFromMaster_callback(year, function (res) {
                allHolidays = res || [];
                resolve();
            });
        });
    }

    function renderCalendar() {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();

        const monthNames = ["January", "February", "March", "April", "May", "June",
            "July", "August", "September", "October", "November", "December"];

        $("#monthDisplay").text(`${monthNames[month]} ${year}`);

        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const prevMonthLastDay = new Date(year, month, 0).getDate();

        const $grid = $("#calendarGrid");
        // Keep the headers, remove the dates
        $grid.find(".cal-cell").remove();

        // Previous month days
        for (let i = firstDay; i > 0; i--) {
            const dayNum = prevMonthLastDay - i + 1;
            appendCell(dayNum, true);
        }

        // Current month days
        const today = new Date();
        const monthEvents = [];

        for (let i = 1; i <= daysInMonth; i++) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
            const holiday = allHolidays.find(h => h.HolidayDate === dateStr);

            const isToday = today.getDate() === i && today.getMonth() === month && today.getFullYear() === year;

            appendCell(i, false, isToday, !!holiday);

            if (holiday) {
                monthEvents.push({
                    name: holiday.HolidayName,
                    date: i,
                    fullDate: dateStr
                });
            }
        }

        // Next month days to fill the grid (usually up to 42 cells total)
        const totalFilled = firstDay + daysInMonth;
        const remaining = 42 - totalFilled;
        for (let i = 1; i <= remaining; i++) {
            appendCell(i, true);
        }

        renderEvents(monthEvents);
    }

    function appendCell(day, isOther, isToday = false, isHoliday = false) {
        const classes = ['cal-cell'];
        if (isOther) classes.push('other-month');
        if (isToday) classes.push('today');
        if (isHoliday) classes.push('is-holiday');

        $("#calendarGrid").append(`
            <div class="${classes.join(' ')}">
                <span class="cal-date-num">${day}</span>
            </div>
        `);
    }

    function renderEvents(events) {
        const $list = $("#eventList");
        $list.empty();

        if (events.length === 0) {
            $list.append('<li class="no-events">&bull; No events in this month.</li>');
            return;
        }

        // Sort by date
        events.sort((a, b) => a.date - b.date);

        events.forEach(e => {
            $list.append(`
                <li class="event-item">
                    <div class="event-bullet"></div>
                    <div class="event-content">
                        <span class="event-date">${changeDateFormatYmd_dMY(e.fullDate)}</span>
                        ${e.name}
                    </div>
                </li>
            `);
        });
    }

    if (typeof changeDateFormatYmd_dMY !== 'function') {
        function changeDateFormatYmd_dMY(dateString) {
            const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
            const d = new Date(dateString);
            return d.getDate() + "-" + months[d.getMonth()] + "-" + d.getFullYear();
        }
    }
</script>