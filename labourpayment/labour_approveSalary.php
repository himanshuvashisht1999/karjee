<?php
if(isset($_GET['vw'])==1){
    $key=$_GET['k'];
    $rooturl="";
	$mdid ="";
	if(isset($_GET['mdid'])){
	  $mdid = $_GET['mdid'];
	}
	$dddr = loginmodulesdetails_frommaster($authconn,$companyid,$subcompanyid,$projectid,"moduleid",$mdid);
	$groupid ="";
	if(count($dddr)>0){
	  $groupid = $dddr[0]['groupid'];
	}
}else{
    require('header.php');
}

?>
<div class="container mt-2">
  <div class="row mb-2">
    <div class="col-12"></div>
  </div>

  <!-- Card 1: Calculate Attendance -->
    <div class="card" id="card1">
        <div class="card-header">
          <strong>Labour wages are pending approval for payment</strong><br/>
          <span></span>
        </div>
        <div class="card-body">
            <!-- <div class="row justify-content-center mt-2">
                <div class="col-md-6">
                    <label for="apmonthlist" class="form-label">Choose the Wages Month</label>
                    <select class="form-select" id="apmonthlist"></select>
                </div>
                <div class="col-md-6">
                    <label for="sldaterangelist" class="form-label">Wages Date Range</label>
                    <select class="form-select" id="sldaterangelist"></select>
                </div>
            </div> -->
            <div class="row justify-content-center mt-2">                
                <div class="col-md-4">
                    <label for="apmonthlist" class="form-label">Choose the Wages Month</label>
                    <select class="form-select" id="apmonthlist"></select>
                </div>
                <div class="col-md-4">
                    <label for="sldaterangelist" class="form-label">Wages Date Range</label>
                    <select class="form-select" id="sldaterangelist"></select>
                </div>
                <div class="col-md-4">
                    <label for="labourwageslocationlist" class="form-label">Wages Location</label>
                    <select class="form-select" id="labourwageslocationlist"></select>
                </div>
            </div>
            <div class="text-center" id="loadingdiv"></div>
            <div class="row mt-4 align-items-end">
                <div class="col-md-7">
                    <b>Labour Wages Period : <span class="wagesperiod"></span></b><br/>
                    <b>Total Wages Amount : <span class ="ttllabourwagesamountlbl"></span></b><br/>
                    <b>Total Labours : <span class ="ttllabourcount"></span></b><br/>
                    <b>Total Days : <span class ="ttlworkeddayscount"></span></b><br/>
                </div>
                <div class="col-md-5 mt-2 mt-md-0">
                    <label for="searchlabourinput" class="form-label fw-bold">Search Labour</label>
                    <input type="search" class="form-control" id="searchlabourinput" placeholder="Search by Labour Name or ID..." oninput="filterTable('#searchlabourinput', '#LabourcalculatesalryReadytoBooktbl');">
                </div>
                <div class="col-12 mt-2">
                  <div class="table-responsive">
                    <table class="table table-bordered" id="LabourcalculatesalryReadytoBooktbl">
                      <thead>
                        <tr class="d-none">
                          <th colspan="10">
                            <h4>Title : Labour Wages</h4>    
                          </th>
                        </tr>
                        <tr class="d-none">
                          <th colspan="10">
                            <h5>Period : <span class="wagesperiod"></span></h5>
                          </th>
                        </tr>
                        <tr class="d-none">
                          <th colspan="10">
                            <h5>Total Wages Amount : <span class ="ttllabourwagesamountlbl"></span></h5>
                          </th>
                        </tr>
                        <tr class="d-none">
                          <th colspan="10">
                            <h5>Total Labours : <span class ="ttllabourcount"></span></h5>
                          </th>
                        </tr>
                        <tr class="d-none">
                          <th colspan="10">
                            <h5>Total Days : <span class ="ttlworkeddayscount"></span></h5>
                          </th>
                        </tr>
                        <tr>
                          <th>Select <br/><input type="checkbox" id="lbmainapprslry"></th>
                          <th>Labour Name</th>
                          <th>Calculated Days</th>
                          <th>Net Payable</th>
                        </tr>
                      </thead>
                      <tbody></tbody>
                    </table>
                  </div>
                </div>
                <div class="col-md-12 mt-4">
                    <button class="btn btn-primary float-end d-none" style="position:fixed; bottom: 50px; right: 50px;" id="Labourcrtbookfromslrydatabtn" onclick="LabourCreateBookFromCalculatedSalaryData('LabourcalculatesalryReadytoBooktbl','Labourcrtbookfromslrydatabtn');">Approved & Create Book for Cash Withdrawal</button>
                </div>
            </div>            
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
// Initialize by showing the first card
let companyid =  $("body").data("companyid");
let subcompanyid =  $("body").data("subcompanyid");
let projectid =  $("body").data("projectid");

LabourSalaryMonthYearList("pendingforapproval", function(res) {
    // console.log(res);
    let $list = $("#apmonthlist");
    $list.empty(); // clear previous options
    if (res.length > 0) {
        $list.append('<option value="" selected disabled>Choose Wages Month</option>');
        res.sort(function(a, b) {
            if (a.SalaryYear === b.SalaryYear) {
                return b.SalaryMonth - a.SalaryMonth;
            }
            return b.SalaryYear - a.SalaryYear;
        });
        for (let i = 0; i < res.length; i++) {
            $list.append(
                '<option value="' + res[i].SalaryYear + '-' + res[i].SalaryMonth + '">' +
                res[i].MonthName + ' - ' + res[i].SalaryYear +
                '</option>'
            );
        }
        if (res.length === 1) {
            $list.prop("selectedIndex", 1).trigger("change");
        }
    } else {
        $list.append('<option value="" disabled>No data available for approval</option>');
    }
});

$(document).on("change", "#apmonthlist", function() {
    LabourSalaryDateRangeList($("#apmonthlist").val(), function(res) {
        // console.log(res);
        let $list = $("#sldaterangelist");
        $list.empty(); // clear previous options
        if (res.length > 0) {
            $list.append('<option value="" selected disabled>Choose Wages Date Range</option>');
            // sort by DateFrom descending (latest first)
            res.sort(function(a, b) {
                return new Date(b.DateFrom) - new Date(a.DateFrom);
            });

            for (let i = 0; i < res.length; i++) {
                $list.append(
                    '<option value="' + res[i].DateFrom + '|' + res[i].DateTo + '">' +
                    res[i].DateFrom + ' - ' + res[i].DateTo +
                    '</option>'
                );
            }
            // auto-select if only one option exists
            if (res.length > 1) {
                $list.append('<option value="fullmonth">Full Month</option>');
            }
            // auto-select if only one option exists
            if (res.length === 1) {
                $list.prop("selectedIndex", 1).trigger("change");
            }
        } else {
            $list.append('<option value="" disabled>No data available for approval</option>');
        }
    });
});

$(document).on("change", "#sldaterangelist", function() {
    let yearmonth = $("#apmonthlist").val();
    let daterange = $("#sldaterangelist").val();
    LabourPendingSalaryLocationList(yearmonth, daterange, function(res) {
        var lctlist = "";
        $("#labourwageslocationlist").empty();
        if (Array.isArray(res) && res.length > 0) {
            if (res.length > 1) {
                lctlist += "<option value='all'>All Location</option>";
            }
            for (var i = 0; i < res.length; i++) {
                lctlist += "<option value='" + res[i].LocationId + "'>" + res[i].LocationName + "</option>";
            }
            $("#labourwageslocationlist").html(lctlist);
            if (res.length > 1) {
                $("#labourwageslocationlist").val("all").trigger("change");
            } else {
                $("#labourwageslocationlist").val(res[0].LocationId).trigger("change");
            }
        } else {
            lctlist = "<option value='all'>All Location</option>";
            $("#labourwageslocationlist").html(lctlist);
            $("#labourwageslocationlist").val("all").trigger("change");
        }
    });
});

// 
// $(document).on("change", "#labourwageslocationlist", function () {
//     var selectyearmonth = $("#sldaterangelist").val();
//     var yearmonthtext = $("#sldaterangelist option:selected").text();
//     var periodext = $("#sldaterangelist").val();
//     var datefrom = "";
//     var dateto = "";
//     if(periodext){
//         var periodextexp = periodext.split("|");
//         var datefrom = periodextexp[0];
//         var dateto = periodextexp[1];
//     }

//     let locationid = $("#labourwageslocationlist").val() || "all";
//     if(selectyearmonth){
//         let loadingMsg = $("<div id='loadingMsg' class='alert alert-danger text-center p-3' style='margin-top:16px;'>Loading labour Wages data...</div>");
//         $("#loadingdiv").append(loadingMsg);
//         $(".wagesperiod").html(changeDateFormatYmd_dMY(datefrom)+" To "+changeDateFormatYmd_dMY(dateto));
//         $("#LabourcalculatesalryReadytoBooktbl tbody").empty();
//         LabourPendingBookFromCalculatedSalaryData('apmonthlist','sldaterangelist', locationid, function(res){
//             var sslhtml="";
//             $("#Labourcrtbookfromslrydatabtn").addClass("d-none");
//             if(res.length>0){
//                 $("#Labourcrtbookfromslrydatabtn").removeClass("d-none");
//                 let totalwagesamount=0;
//                 let totalworkeddays =0;
//                 for (var i = 0; i < res.length; i++) {
//                     totalwagesamount += parseFloat(res[i].NetSalary) || 0;
//                     totalworkeddays += parseFloat(res[i].CalculatedPresentDays) || 0;
//                     sslhtml += `<tr data-employeeid="${res[i].EmployeeID}" 
//                     data-salarymonth="${res[i].SalaryMonth}" 
//                     data-salaryyear="${res[i].SalaryYear}" 
//                     data-datefrom="${res[i].DateFrom}" 
//                     data-dateto="${res[i].DateTo}" 
//                     data-netsalary="${res[i].NetSalary}"><td><input type="checkbox" class="lbslrdbookcheckbox" /></td><td>${res[i].EmployeeID}-${res[i].EmployeeName}</td><td>${res[i].CalculatedPresentDays}</td><td>${res[i].NetSalary}</td></tr>`;
//                 }
//                 $("#loadingMsg").html("");
//                 $("#loadingMsg").html("<h3>Labour Wages Pending Approval for Payment</h3>");
//                 $("#loadingMsg").removeClass("alert-danger");
//                 $("#loadingMsg").addClass("alert-success");
//                 if(sslhtml){
//                     $("#LabourcalculatesalryReadytoBooktbl tbody").html(sslhtml);
//                     $(".ttllabourwagesamountlbl").html(totalwagesamount.toFixed(2));
//                     $(".ttllabourcount").html(res.length);
//                     $(".ttlworkeddayscount").html(totalworkeddays);

//                 }else{
//                     $(".ttllabourwagesamountlbl").html('0');
//                     $(".ttllabourcount").html('0');
//                     $(".ttlworkeddayscount").html('0');
//                     $("#LabourcalculatesalryReadytoBooktbl tbody").html("<tr><td colspan='8'>No data available for the month of "+yearmonthtext+"</td></tr>");
//                 }
//             }else{
//                 $("#loadingdiv").html("");
//                 $(".ttllabourwagesamountlbl").html('0');
//                 $(".ttllabourcount").html('0');
//                 $(".ttlworkeddayscount").html('0');
//                 $("#LabourcalculatesalryReadytoBooktbl tbody").html("<tr><td colspan='8' class='text-center'>No data available for the month of "+yearmonthtext+"</td></tr>");
//             }
//         });
//     }else{
//         alert('exist else');
//     }    
// });

$(document).on("change", "#labourwageslocationlist", function () {

    let periodext = $("#sldaterangelist").val();
    let yearmonthtext = $("#sldaterangelist option:selected").text();

    let datefrom = "";
    let dateto = "";

    if (periodext) {
        let periodextexp = periodext.split("|");
        datefrom = periodextexp[0];
        dateto = periodextexp[1];
    }

    let locationid = $("#labourwageslocationlist").val() || "all";

    if (!periodext) {
        Swal.fire({
            icon: 'warning',
            title: 'Select Period',
            text: 'Please select salary period first'
        });
        return;
    }

    // 🔄 Show loading Swal
    Swal.fire({
        title: 'Loading Labour Wages',
        text: 'Please wait...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    $(".wagesperiod").html(
        changeDateFormatYmd_dMY(datefrom) +
        " To " +
        changeDateFormatYmd_dMY(dateto)
    );

    $("#LabourcalculatesalryReadytoBooktbl tbody").empty();
    $("#Labourcrtbookfromslrydatabtn").addClass("d-none");

    LabourPendingBookFromCalculatedSalaryData(
        'apmonthlist',
        'sldaterangelist',
        locationid,
        function (res) {

            let sslhtml = "";
            let totalwagesamount = 0;
            let totalworkeddays = 0;

            if (Array.isArray(res) && res.length > 0) {

                if (locationid !== "all") {
                    $("#Labourcrtbookfromslrydatabtn").removeClass("d-none");
                } else {
                    $("#Labourcrtbookfromslrydatabtn").addClass("d-none");
                }

                for (let i = 0; i < res.length; i++) {

                    totalwagesamount += parseFloat(res[i].NetSalary) || 0;
                    totalworkeddays += parseFloat(res[i].CalculatedPresentDays) || 0;
                    sslhtml += `
                        <tr data-employeeid="${res[i].EmployeeID}"
                            data-salarymonth="${res[i].SalaryMonth}"
                            data-salaryyear="${res[i].SalaryYear}"
                            data-datefrom="${res[i].DateFrom}"
                            data-dateto="${res[i].DateTo}"
                            data-locationid="${res[i].LocationId}"
                            data-netsalary="${res[i].NetSalary}">
                            <td><input type="checkbox" class="lbslrdbookcheckbox" /></td>
                            <td>${res[i].EmployeeID} - ${res[i].EmployeeName}</td>
                            <td>${res[i].CalculatedPresentDays}</td>
                            <td>${res[i].NetSalary}</td>
                        </tr>`;
                }

                $("#LabourcalculatesalryReadytoBooktbl tbody").html(sslhtml);
                $(".ttllabourwagesamountlbl").html(totalwagesamount.toFixed(2));
                $(".ttllabourcount").html(res.length);
                $(".ttlworkeddayscount").html(totalworkeddays);

                Swal.fire({
                    icon: 'success',
                    title: 'Data Loaded',
                    text: 'Labour wages pending approval loaded successfully',
                    timer: 1500,
                    showConfirmButton: false
                });

            } else {

                $("#Labourcrtbookfromslrydatabtn").addClass("d-none");

                $("#LabourcalculatesalryReadytoBooktbl tbody").html(
                    `<tr>
                        <td colspan="8" class="text-center">
                            No data available for the month of ${yearmonthtext}
                        </td>
                    </tr>`
                );

                $(".ttllabourwagesamountlbl").html('0');
                $(".ttllabourcount").html('0');
                $(".ttlworkeddayscount").html('0');

                Swal.fire({
                    icon: 'info',
                    title: 'No Data Found',
                    text: 'No labour wages pending for selected period'
                });
            }
        }
    );
});

$(document).on("keyup search input", "#searchlabourinput", function () {
    var value = $(this).val().toLowerCase().trim();
    $("#LabourcalculatesalryReadytoBooktbl tbody tr").filter(function () {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
    });
});

$(document).ready(function () {
     handleMasterCheckboxAll("lbmainapprslry", "lbslrdbookcheckbox");
});
</script>