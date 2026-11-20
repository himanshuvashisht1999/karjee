<?php
if(isset($_GET['vw'])==1){
    $key=$_GET['k'];
    $rooturl="";
}else{
    require('header.php');
}
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
<div class="container mt-2">
  <div class="row mb-2">
    <div class="col-12"></div>
  </div>

  <!-- Card 1: Calculate Attendance -->
    <div class="card" id="card1">
        <div class="card-header text-center">
          <h3><strong>Labour Payment Disbursement</strong></h3>
          <span></span>
        </div>
        <div class="card-body">
            <div class="row justify-content-center mt-2">
                <div class="col-md-4">
                    <label for="rpmonthlist" class="form-label">Choose the Wages Month</label>
                    <select class="form-select" id="rpmonthlist"></select>
                </div>
                <div class="col-md-4">
                    <label for="rpdaterangelist" class="form-label">Wages Date Range</label>
                    <select class="form-select" id="rpdaterangelist"></select>
                </div>
                <div class="col-md-4">
                    <label for="wageslocationlist" class="form-label">Wages Location</label>
                    <select class="form-select" id="wageslocationlist"></select>
                </div>
            </div>
            <!-- <div class="row">
                <div class="col-md-12">
                    <button class="btn btn-sm btn-primary float-end mt-4" id="exportlbpaymentpdf"><i class="fa fa-download" aria-hidden="true"></i> Download Labour Wages as PDF</button>
                </div>
                <div class="col-md-12">
                    <label><b>Upload Labour Wages File</b></label><br/>
                    <input type="file" class="form-control" name="referenceDocument" id="referenceDocument" />
                </div>
            </div> -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <label><b>Is there any pending payment?</b></label><br/>
                    <label><input type="radio" name="ispendingpayment" class="ispendingpayment" value="yes" /> YES</label>
                    <label><input type="radio" name="ispendingpayment" class="ispendingpayment" value="no" /> NO</label>
                </div>
                <div class="col-md-12 mt-4" id="upddisbursediv" style="display:none;">
                    <button class="btn btn-primary float-end" id="updateFinalPaymentStatusbtn">UPDATE LABOUR DISBURSEMENT PAYMENT</button>
                </div>
            </div>
            <div class="text-center" id="loadingdiv"></div>
            <div class="row mt-2" id="dataviewdiv" style="display:none;">
                <div class="col-md-12 mt-2">
                  <div class="table-responsive">
                    <table class="table table-bordered" border="1" id="calculatedLaboursalDatatbl">
                      <thead>
                        <tr class="noexport">
                            <th class="noexport" colspan="4">
                                <h6 class="text-danger">Note: Select specific labour if their payment is still pending.</h6>
                                <h5 class="text-primary mt-2">Pending Balance for Disbursement : <span id="pendingBalancelbl">0</span></h5>
                            </th>
                            <th class="noexport" colspan="4">
                                <label>Search to Filter</label>
                                <input type="search" class="form-control" border=1 placeholder="Search...." id="searchrptPaytbl" oninput="filterTable('#searchrptPaytbl', '#calculatedLaboursalDatatbl');">
                            </th>
                        </tr>
                        <tr>
                            <th>Select All<br/><input type="checkbox" class="msselectall" id="mnsltlabourslry"></th>
                            <th class="individual" style="width:50px;">S.No</th>
                            <th style="width:150px;">Labour <br/>Name</th>
                            <th style="width:150px;">Date From</th>
                            <th style="width:150px;">Date To</th>
                            <th style="width:100px;">Pay/Day</th>
                            <th style="width:100px;">Calculated<br/>Days</th>
                            <th style="width:100px;">Net Payable</th>
                        </tr>
                      </thead>
                      <tbody></tbody>
                    </table>
                  </div>
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
LabourSalaryMonthYearList("afterapproval", function(res) {
    // console.log(res);
    let $list = $("#rpmonthlist");
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
        $list.append('<option value="" disabled>No data available</option>');
    }
});

$(document).on("change", "#rpmonthlist", function() {
    LabourSalaryDateRangeList($("#rpmonthlist").val(), function(res) {
        // console.log(res);
        let $list = $("#rpdaterangelist");
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
            if (res.length === 1) {
                $list.prop("selectedIndex", 1).trigger("change");
            }
        } else {
            $list.append('<option value="" disabled>No data available for approval</option>');
        }
    });
});

$(document).on("change", "#rpdaterangelist", function() {
    var periodext = $("#rpdaterangelist").val();
    LabourAttendanceLocationList("withindates",$("#rpdaterangelist").val(),function(res){
        var lctlist="";
        $("#wageslocationlist").empty();
        if(res.length>0){
            if(res.length>1){
                lctlist +="<option value='all'>All Location</option>";
            }
            
            for (var i = 0; i < res.length; i++) {
                lctlist +="<option value='"+res[i].LocationId+"'>"+res[i].LocationName+"</option>";
            }
        }
        if(lctlist){
            $("#wageslocationlist").append(lctlist);
        }
        if(res.length==1){
            $("#wageslocationlist").prop("selectedIndex", 0).trigger("change");
        }
    });
});

$(document).on("change", "#wageslocationlist", function () {
    var selectyearmonth = $("#rpdaterangelist").val();
    var yearmonthtext = $("#rpdaterangelist option:selected").text();
     let loadingMsg = $("<div id='loadingMsg' class='alert alert-info text-center p-3' style='margin-top:16px;'>Loading labour wages data...</div>");
    // $(".projectnamelbl").html($("body").data("projectname"));
    // $(".salarypriodlbl").html(yearmonthtext);
    $("#loadingdiv").append(loadingMsg);
    let locationid = $("#wageslocationlist").val();
    if(selectyearmonth){
        $("#calculatedLaboursalDatatbl tbody").empty();
        CalculatedSalaryData_Labour('rpmonthlist','rpdaterangelist',locationid,function(res){
            var sslhtml="";
            if(res.length>0){
                var trdnone="";
                var pstatus="";
                let unpaidcound=0;
                for (var i = 0; i < res.length; i++) {
                    if(res[i].ReferenceNo){
                        trdnone = "";
                        pstatus = "";
                        if(res[i].PaymentStatus=='Paid' || res[i].PaymentStatus=='paid'){
                            trdnone = "d-none";
                            pstatus = "Paid";
                        }else{
                            unpaidcound++;
                            pstatus = "Un-Paid";
                            sslhtml += `<tr class="${trdnone}" data-employeeid="${res[i].EmployeeID}"  data-employeename="${res[i].EmployeeName}" 
                            data-salarymonth="${res[i].SalaryMonth}" 
                            data-salaryyear="${res[i].SalaryYear}" 
                            data-datefrom="${res[i].DateFrom}" 
                            data-dateto="${res[i].DateTo}" 
                            data-salaryid="${res[i].SalaryID}" 
                            data-netsalary="${res[i].NetSalary}" data-paymentstatus="${res[i].PaymentStatus}" data-paymentdate="${res[i].PaymentDate}"><td><input type="checkbox" class="payperdaycheckbox" /></td><td class="individual">${i + 1}</td><td>${res[i].EmployeeID}-${res[i].EmployeeName}</td><td>${res[i].DateFrom}</td><td>${res[i].DateTo}</td><td>${res[i].PayPerDay}</td><td>${res[i].CalculatedPresentDays}</td><td>${res[i].NetSalary}</td></tr>`;
                        }
                    }
                }
                if(sslhtml){
                    $("#calculatedLaboursalDatatbl tbody").html(sslhtml);
                    $("#loadingMsg").remove();
                }else{
                    $("#loadingMsg").html("No data available for the month of "+yearmonthtext);
                }
            }else{
                $("#loadingMsg").html("No data available for the month of "+yearmonthtext);
            }
        });
    }    
});


$(document).on("change", "input[name='ispendingpayment']", function(){
    $("input.payperdaycheckbox").prop("checked", false);
    let pendingentry = $("#calculatedLaboursalDatatbl tbody tr").length;
    if(pendingentry>0){
        if($(this).val()=="yes"){
            $("#dataviewdiv").show();
            $("#upddisbursediv").show();
        }else if($(this).val()=="no"){
            $("#upddisbursediv").hide();
            $("#dataviewdiv").hide();
            Swal.fire({
              title: "Are you sure?",
              text: "Your pending cash balance is zero (0), and all labour payments have been disbursed.",
              icon: "warning",
              showCancelButton: true,
              confirmButtonColor: "#3085d6",
              cancelButtonColor: "#d33",
              confirmButtonText: "Yes, Procee to update"
            }).then((result) => {
              if (result.isConfirmed) {
                AllUpdateFinalPaymentStatus('referenceDocument', 'calculatedLaboursalDatatbl');
                // Swal.fire({
                //   title: "Success",
                //   text: "Your file has been deleted.",
                //   icon: "success"
                // });
              }
            });
        }
    }else{
        Swal.fire({
          title: "Warning!",
          text: "You have no pending labour wages for disbursement",
          icon: "warning"
        });
    }
});

$(document).ready(function () {
    handleMasterCheckboxAll("mnsltlabourslry", "payperdaycheckbox");
});

$(document).on("click", "input.msselectall", function() {
    setTimeout(function() {
        let pendingcashBalance = 0;
        $("#calculatedLaboursalDatatbl tbody tr").each(function () {
            let checkbox = $(this).find("input.payperdaycheckbox");
            if (checkbox.is(":checked")) {
                let netSalary = Number($(this).data("netsalary")) || 0;
                pendingcashBalance += netSalary;
            }
        });

        // Update label and alert
        pendingcashBalance = pendingcashBalance || 0;
        $("#pendingBalancelbl").html(pendingcashBalance.toFixed(2));
    }, 100); // 5000 milliseconds = 5 seconds
});

$(document).on("click", "input.payperdaycheckbox", function () {
    let pendingcashBalance = 0;
    $("#calculatedLaboursalDatatbl tbody tr").each(function () {
        let checkbox = $(this).find("input.payperdaycheckbox");
        if (checkbox.is(":checked")) {
            pendingcashBalance += parseFloat($(this).data("netsalary")) || 0;
        }
    });
    // Always update the label (show 0 if nothing selected)
    $("#pendingBalancelbl").html(pendingcashBalance.toFixed(2));
});


$("#updateFinalPaymentStatusbtn").on("click", function () {
    let pendingcashBalanceamount = $("#pendingBalancelbl").html().trim();
    // Remove trailing .00 if present
    if (pendingcashBalanceamount.endsWith(".00")) {
        pendingcashBalanceamount = pendingcashBalanceamount.slice(0, -3);
    }
    Swal.fire({
        title: "Confirm Cash Balance",
        text: "Please enter the pending cash balance to confirm.",
        input: "text",
        inputPlaceholder: "Enter " + pendingcashBalanceamount,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        confirmButtonText: "Confirm",
        preConfirm: (inputValue) => {
            if (inputValue.trim() !== pendingcashBalanceamount) {
                Swal.showValidationMessage(
                    "Entered amount must match " + pendingcashBalanceamount
                );
                return false;
            }
            return true;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            AllUpdateFinalPaymentStatus('referenceDocument', 'calculatedLaboursalDatatbl');
        }
    });
});

$("#exportlbpaymentpdf").on("click", function(){
    var completed = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var loginname = $("body").data("loginname");
    var datefromdateex = $("#rpdaterangelist").val();
    var dateFrom ="";
    var dateTo ="";
    if(datefromdateex){
        var datessp = datefromdateex.split("|");
        dateFrom = datessp[0];
        dateTo = datessp[1];
    }
    if(dateFrom && dateTo){
        window.open("labour_PaymentSheetPDF.php?companyid="+companyid+"&subcompanyid="+subcompanyid+"&projectid="+projectid+"&datefrom="+dateFrom+"&dateto="+dateTo+"&loginname="+loginname);
    }else{
        alertmessage('warning', 'Invalid Date From and Date To');
    }
});
</script>