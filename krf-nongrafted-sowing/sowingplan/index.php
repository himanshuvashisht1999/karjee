<?php
  include('../../header.php');
  date_default_timezone_set("Asia/Calcutta");
  $mdid ="";
  if(isset($_GET['mdid'])){
    $mdid = $_GET['mdid'];
  }
  $dddr = loginmodulesdetails_frommaster($authconn,$companyid,$subcompanyid,$projectid,"moduleid",$mdid);
  $groupid ="";
  if(count($dddr)>0){
    $groupid = $dddr[0]['groupid'];
  }
?>
<style>
  #sowingstatustbl col {
    background-clip: padding-box !important;
  } 
</style>
<div class="container1 my-4">
  <h3 class="mb-4 text-center">Non Grafted Weekly Sowing Planner</h3>
  <div class="row p-2">
    <div class="col-md-12">
      <button class="btn btn-sm btn-deepblue" onclick="window.location.reload();"> <i class="fa fa-sync"></i> Refresh</button>
          
    </div>
  </div>  
  <div class="card p-3 mb-4">

  <div style="margin-bottom: 10px;">
    <button id="btnPrev" class="btn btn-primary">Previous Week</button>
    <button id="btnNext" class="btn btn-primary">Next Week</button>
    <select id="weekDropdown" class="form-control" style="width:200px; display:inline-block;">
    <option value="">Select Week</option>
</select>
    <span id="lblWeek" style="font-weight:bold; margin-left:15px;"></span>
</div>

 
    <div class="row">
      <div class="col-md-12">
        <div class="table-responsive">
          <table class="table table-bordered" id="sowingstatustbl">
            <colgroup>
              <col span="2" style="background:#f0f0f0;">   <!-- S.No + # -->              
              <col style="width:450px;">                   <!-- Product Name -->
              <col style="width:150px;">   <!-- Order Qty -->
              <col style="width:450px;">    
            </colgroup>

            <thead class="table-light">
              <tr class="sticky-top">
                <th>S.No</th>
                <th>#</th> 
                <th>Product Name</th>
                <th>Sowing Qty</th>   
                <th>Client Name</th>
              </tr>
            </thead>
            <tbody></tbody>
            <tfoot></tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>

</div>
<?php include('../../footer.php'); ?>
 <script>

let fullData = [];
let weekOffset = 0;   // 0 = current week, -1 = previous, +1 = next

$(document).ready(function () {

    // Load data first time
    loadAllWeeks("sowingstatustbl");
    // Button events
    $("#btnPrev").click(function() {
        weekOffset--;
        renderCurrentWeek("sowingstatustbl");
    });

    $("#btnNext").click(function() {
        weekOffset++;
        renderCurrentWeek("sowingstatustbl");
    });
    $("#weekDropdown").change(function() {
        let selectedWeek = $(this).val();

        if (selectedWeek === "") return;

        if (selectedWeek === "ALL") {
            renderAllWeeks("sowingstatustbl");  // ⭐ NEW
            return;
        }

        loadWeekFromDropdown(parseInt(selectedWeek));
    });

});


// ---------------- Load All Weeks -----------------

function loadAllWeeks(tableid) {
    let projectid = "<?php echo $projectid ?>";
    let formData = new FormData();
    formData.append('projectid', projectid);
    $.ajax({
        url: "modal/queries.php?action=oderforsowing_nongrafted",
        type: "POST",
        data: formData,
        dataType: "json",
        contentType: false,
        processData: false,
        cache: false,
        success: function (res) {
            fullData = res || [];
            populateWeekDropdown();   // ⬅ ADD THIS 
            renderCurrentWeek(tableid);
        }
    });
}



// ---------------- RENDER CURRENT WEEK ------------------

function renderCurrentWeek(tableid) {
    let { monday, sunday } = getWeekRange();   // based on weekOffset
    let weekStart = formatDMY(monday);
    let weekEnd   = formatDMY(sunday);
    let weekNumber = getWeekNumberFromDate(monday);
    //$("#lblWeek").text(`Week : ${weekStart} to ${weekEnd}`);
    $("#lblWeek").text(`Week ${weekNumber} : ${weekStart} to ${weekEnd}`);
    // Filter records between these dates
    let filtered = fullData.filter(r => {
        let sowDate = new Date(r.sowing_start_date);
        return sowDate >= monday && sowDate <= sunday;
    });

    if (filtered.length === 0) {
        $("#" + tableid + " tbody").html(`
            <tr>
                <td colspan="10" style="text-align:center; color:red; font-weight:bold;">
                    For the week (${weekStart} to ${weekEnd}) no Sowing is pending
                </td>
            </tr>
        `);
        return;
    }

    let rows = "";
    let k = 1;
    filtered.forEach(r => {
        rows += `
        <tr data-productid="${r.productid}"
            data-expecteddelivery_stk="${r.expected_delivery_date}"
            data-sowingstartday="${r.sowing_start_date}"
            data-provisonorderno="${r.psono}" 
            data-stockorder_no="${r.stockorder_no}"
            data-growthperiod="${r.growthperiod}"
            data-clientid="${r.clientid}">
            <td>${k++}</td>
            <td class='row-checkbox noexport'>
                <input type='checkbox' class='chkweekqty' style='transform: scale(2);'>
            </td>
            <td>${r.productname}</td>
            <td>${r.balqty}</td>
            <td>${r.client_name}</td>
        </tr>`;
    });
    $("#" + tableid + " tbody").html(rows);
}

// ---------------- WEEK RANGE HELPERS ------------------
function getWeekRange(baseDate = new Date()) {

    let date = new Date(baseDate);
    date.setDate(date.getDate() + weekOffset * 7);

    let day = date.getDay(); // Sun=0, Mon=1,... Sat=6
    let diffToMonday = (day === 0 ? -6 : 1 - day);

    let monday = new Date(date);
    monday.setDate(date.getDate() + diffToMonday);

    let sunday = new Date(monday);
    sunday.setDate(monday.getDate() + 6);

    return { monday, sunday };
}

function formatDMY(d) {
    const months = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
    return d.getDate().toString().padStart(2,'0') + "-" +
           months[d.getMonth()] + "-" +
           d.getFullYear().toString().slice(-2);
}
function getWeekNumberFromDate(date) {
    // Clone date
    let d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));

    // Get Monday of this week
    let day = d.getUTCDay(); 
    day = (day === 0 ? 7 : day);
    d.setUTCDate(d.getUTCDate() + 1 - day);

    // Start of year (first Monday)
    let yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
    let yearStartDay = yearStart.getUTCDay();
    yearStartDay = (yearStartDay === 0 ? 7 : yearStartDay);
    yearStart.setUTCDate(yearStart.getUTCDate() + 1 - yearStartDay);

    // Weeks difference
    let diff = d - yearStart;
    return Math.floor(diff / (7 * 24 * 60 * 60 * 1000)) + 1;
}
function populateWeekDropdown() {
    let weeks = new Set();
    fullData.forEach(r => {
        let sowDate = new Date(r.sowing_start_date);

        let day = sowDate.getDay();
        let diffToMonday = (day === 0 ? -6 : 1 - day);
        let monday = new Date(sowDate);
        monday.setDate(sowDate.getDate() + diffToMonday);

        let weekNo = getWeekNumberFromDate(monday);
        weeks.add(weekNo);
    });

    let sortedWeeks = Array.from(weeks).sort((a, b) => a - b);

    $("#weekDropdown").empty().append(`
        <option value="">Select Week</option>
        <option value="ALL">All</option>   <!-- ⭐ NEW -->
    `);

    sortedWeeks.forEach(week => {
        $("#weekDropdown").append(`
            <option value="${week}">Week ${week}</option>
        `);
    });
}

function loadWeekFromDropdown(weekNumber) {
    // Compute the Monday of that year’s week
    let year = new Date().getFullYear();
    // Find reference date = first Monday of the year
    let firstJan = new Date(year, 0, 1);
    let day = firstJan.getDay();
    let firstMonday = new Date(firstJan);

    if (day !== 1) {
        let diffToMonday = (day === 0 ? 1 : 1 - day);
        firstMonday.setDate(firstJan.getDate() + diffToMonday);
    }

    // Now add (weekNumber - 1) * 7 days to get that week’s Monday
    let monday = new Date(firstMonday);
    monday.setDate(firstMonday.getDate() + (weekNumber - 1) * 7);

    let sunday = new Date(monday);
    sunday.setDate(monday.getDate() + 6);

    let weekStart = formatDMY(monday);
    let weekEnd   = formatDMY(sunday);

    $("#lblWeek").text(`Week ${weekNumber} : ${weekStart} to ${weekEnd}`);

    let filtered = fullData.filter(r => {
        let sowDate = new Date(r.sowing_start_date);
        return sowDate >= monday && sowDate <= sunday;
    });

    if (filtered.length === 0) {
        $("#sowingstatustbl tbody").html(`
            <tr>
                <td colspan="10" style="text-align:center; color:red; font-weight:bold;">
                    For Week ${weekNumber} (${weekStart} to ${weekEnd}) no Sowing is pending
                </td>
            </tr>
        `);
        return;
    }

    renderTableRows(filtered);
}
function renderTableRows(filtered) {
    let rows = "";
    let k = 1;
    filtered.forEach(r => {
        rows += `
        <tr>
            <td>${k++}</td>
            <td><input type='checkbox' class='chkweekqty' style='transform: scale(2);'></td>
            <td>${r.productsku}</td>
            <td>${r.balqty}</td>
            <td>${r.client_name}</td>
        </tr>`;
    });
    $("#sowingstatustbl tbody").html(rows);
}



function renderAllWeeks(tableid) {
    $("#lblWeek").text("All Weeks");
    if (!fullData || fullData.length === 0) {
        $("#" + tableid + " tbody").html(`
            <tr>
                <td colspan="10" style="text-align:center; color:red; font-weight:bold;">
                    No Sowing Data Available
                </td>
            </tr>
        `);
        return;
    }
    let rows = "";
    let k = 1;
    fullData.forEach(r => {
        rows += `
        <tr>
            <td>${k++}</td>
            <td><input type='checkbox' class='chkweekqty' style='transform: scale(2);'></td>
            <td>${r.productsku}</td>
            <td>${r.balqty}</td>
            <td>${r.client_name}</td>
        </tr>`;
    });
    $("#" + tableid + " tbody").html(rows);
}
</script>



