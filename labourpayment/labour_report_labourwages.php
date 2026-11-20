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
<style>
.view-remark-badge {
    background-color: #eff6ff;
    color: #2563eb;
    border: 1px solid #bfdbfe;
    padding: 0.2rem 0.6rem;
    font-size: 0.72rem;
    font-weight: 600;
    border-radius: 50rem; /* pill shape */
    transition: all 0.2s;
    text-decoration: none;
    display: inline-block;
    cursor: pointer;
}
.view-remark-badge:hover {
    background-color: #2563eb;
    color: #ffffff;
}
</style>
<div class="container mt-2">
  <div class="row mb-2">
    <div class="col-12"></div>
  </div>

  <!-- Card 1: Calculate Attendance -->
    <div class="card" id="card1">
        <div class="card-header text-center">
          <h3><strong>Labour Wages Report</strong></h3>
          <span></span>
        </div>
        <div class="card-body">
            <div class="row justify-content-center mt-2">
                <div class="col-md-4">
                    <label for="rmdatefrom" class="form-label">Date From</label>
                    <input type="date" class="form-control gtremportdata" id="rmdatefrom" />
                </div>
                <div class="col-md-4">
                    <label for="rmdateto" class="form-label">Date To</label>
                    <input type="date" class="form-control gtremportdata" id="rmdateto" />
                </div>
                <div class="col-md-4">
                    <label for="wageslocationlist" class="form-label">Wages Location</label>
                    <select class="form-select gtremportdata" id="wageslocationlist"></select>
                </div>
            </div>
            <div class="text-center" id="loadingdiv"></div>
            
            <div class="row mt-4">
                <div class="col-md-12 text-center">
                    <button class="btn btn-sm btn-primary" id="exportlbpaymentpdf" style="display:none;"><i class="fa fa-download" aria-hidden="true"></i> Download Labour Wages as PDF</button>
                    <button class="btn btn-sm btn-primary" id="exportlbpaymentexcel" onclick="gridtoexcel_withfilter_chunk('labourwagespayment_tbl', 'Labour Wages', 'landscape',function(res){})" style="display:none;"><i class="fa fa-download" aria-hidden="true"></i> Download Labour Wages as EXCEL</button>
                </div>
            </div>
            <div class="row" id="datagridviewdiv" style="display:none;">
                <div class="col-md-12 mt-2">
                  <div class="table-responsive" style="height:500px; scroll-y:auto;">
                    <table class="table table-bordered" id="labourwagespayment_tbl">
                      <thead class="thead-light">
                        <tr class="noexport">
                          <th colspan="5" class="noexport">
                            <h4>Title : Labour Wages</h4>    
                            <h5>Period : <span class="wagesperiod"></span></h5>
                            <h5>Total Wages Amount : <span class ="ttllabourwagesamountlbl"></span></h5>
                            <h5>Total Labours : <span class ="ttllabourcount"></span></h5>
                            <h5>Total Days : <span class ="ttlworkeddayscount"></span></h5>
                          </th>
                          <th colspan="6" class="noexport">
                            <label>Search to Filter</label>
                            <input type="search" class="form-control" border=1 placeholder="Search...." id="searchcallabsalaryPaytbl" oninput="filterTable('#searchcallabsalaryPaytbl', '#labourwagespayment_tbl');">
                          </th>
                        </tr>
                        <tr class="d-none">
                          <th colspan="11">
                            <h4>Title : Labour Wages</h4>    
                          </th>
                        </tr>
                        <tr class="d-none">
                          <th colspan="11">
                            <h5>Period : <span class="wagesperiod"></span></h5>
                          </th>
                        </tr>
                        <tr class="d-none">
                          <th colspan="11">
                            <h5>Total Wages Amount : <span class ="ttllabourwagesamountlbl"></span></h5>
                          </th>
                        </tr>
                        <tr class="d-none">
                          <th colspan="11">
                            <h5>Total Labours : <span class ="ttllabourcount"></span></h5>
                          </th>
                        </tr>
                        <tr class="d-none">
                          <th colspan="11">
                            <h5>Total Days : <span class ="ttlworkeddayscount"></span></h5>
                          </th>
                        </tr>
                        <tr>
                            <th>S.No</th>
                            <th>Labour Name</th>
                            <th>Day</th>
                            <th>Wages Amount </th>
                            <th>Incentive Grafting</th>
                            <th>Incentive Seasional</th>
                            <th>Overtime Amount </th>
                            <th>Other Payment</th>
                            <th>Payable Amount</th>
                            <th style='display:none;'>Sign/Thumb</th>
                            <th style='display:none;'>Date</th>
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

 
getLabourSalaryLocationList("","",function(res){
    var lctlist="";
    $("#wageslocationlist").empty();
    if(res.length>0){
        for (var i = 0; i < res.length; i++) {
            lctlist +="<option value='"+res[i].LocationId+"'>"+res[i].LocationName+"</option>";
        }
    }
    if(lctlist){
        $("#wageslocationlist").append(lctlist);
    }
    if(res.length==1){
        $("#wageslocationlist").prop("selectedIndex", 0).trigger("change");
    }else if(res.length>1){
        $("#wageslocationlist").val("all").trigger("change");
    }
});

$(document).on("change", ".gtremportdata", function () {
    $(".ttllabourwagesamountlbl").html('');
    var datefrom = $("#rmdatefrom").val();
    var dateto = $("#rmdateto").val();
    let locationid = $("#wageslocationlist").val();    
    if(datefrom!="" && dateto!="" && locationid!=""){
        $(".wagesperiod").html(changeDateFormatYmd_dMY(datefrom)+" To "+changeDateFormatYmd_dMY(dateto));
        let loadingMsg = $("<div id='loadingMsg' class='alert alert-danger text-center p-3' style='margin-top:16px;'>Loading labour wages data...</div>");
        $("#loadingdiv").html(loadingMsg);        
        CalculatedSalaryDataLabour_forreport(datefrom, dateto,locationid, function (res) {
            if (res && res.length > 0) {
                $("#loadingdiv").html('').addClass('d-none');
                $("#datagridviewdiv, #exportlbpaymentpdf, #exportlbpaymentexcel").show();
                let tblrowv = "";
                let totalwagesamount = 0;
                let ttlworkeddayscount = 0;

                for (let i = 0; i < res.length; i++) {
                    const row = res[i];
                    const netSalary = parseFloat(row.NetSalary) || 0;
                    const presentoldDays = parseFloat(row.CalculatedoldPresentDays) || 0;
                    const presentDays = parseFloat(row.CalculatedPresentDays) || 0;
                    totalwagesamount += netSalary;
                    ttlworkeddayscount += presentDays;

                    let oind = row.otherinfo || [];
                    // console.log(oind);
                    let uprbtn = "";
                    let attfileId ="";
                    if (Array.isArray(oind) && oind.length > 0) {
                        oind.forEach((v) => {
                            if (v.UpdateRemark && v.UpdateRemark.trim() !== "") {
                                uprbtn += `
                                    <span class="view-remark-badge view-remark" 
                                        data-remark="${v.UpdateRemark}" data-rmPresentdays="${presentDays}" data-rmoldPresentdays="${presentoldDays}">
                                        View Remark
                                    </span>`;
                            }

                            if (v.attFileid) {
                                attfileId += `
                                    <span class="badge badge-info view-attfile" style="cursor:pointer;" 
                                        data-attfileid="${v.attFileid}">
                                        View Attendance Excel
                                    </span>`;
                            }
                        });
                    }
                    let paidStatus = "";
                    if ((row.PaymentStatus === "Paid" || row.PaymentStatus === "paid" || row.PaymentStatus === "PAID") && row.PaymentReferenceFilepath !== "") {
                        paidStatus += `
                            <span class="viewpaidreceipt text-success" style="cursor:pointer;"
                                data-paidreceipt="${row.PaymentReferenceFilepath}" title="View Paid Receipt">
                                <i class="fa fa-file fa-lg"></i>
                            </span>`;
                    }else if ((row.PaymentStatus === "Paid" || row.PaymentStatus === "paid" || row.PaymentStatus === "PAID") && (row.PaymentReferenceFilepath === "" || row.PaymentReferenceFilepath == null)) {
                        paidStatus += `
                            <span class="text-muted" title="Paid (No Ref. File)">
                                <i class="fa fa-check-circle fa-lg"></i>
                            </span>`;
                    }

                    tblrowv += `
                        <tr data-employeeid='${row.EmployeeID}' data-employeename='${row.EmployeeName}'>
                            <td>${i + 1}</td>
                            <td>${row.EmployeeID} - ${row.EmployeeName}  ${attfileId}</td>
                            <td>${presentDays} ${uprbtn}</td>
                            <td>${row.basiclabourpayment || 0}</td>
                            <td>${row.incentivegrafting || 0}</td>
                            <td>${row.incentiveseasional || 0}</td>
                            <td>${row.overtimepayment || 0}</td>
                            <td>${row.otherpayment || 0}</td>
                            <td>${netSalary.toFixed(2)}  ${paidStatus}</td>
                            <td style="display:none;"></td>
                            <td style="display:none;"></td>
                        </tr>`;
                }

                $("#labourwagespayment_tbl tbody").html(tblrowv);
                $(".ttllabourwagesamountlbl").html(totalwagesamount.toFixed(2));
                $(".ttllabourcount").html(res.length);
                $(".ttlworkeddayscount").html(ttlworkeddayscount);
            } else {
                $("#labourwagespayment_tbl tbody").html(
                    `<tr><td colspan="11" class="text-center text-danger">
                        No wages data available for selected month.
                    </td></tr>`
                );
                $(".ttllabourwagesamountlbl, .ttllabourcount, .ttlworkeddayscount").html('0');
            }
        });
    }
});
$(document).on("click", ".view-remark", function() {
    let remark = $(this).data("remark") || "No remark available";
    let Presentdays = $(this).data("rmPresentdays") || "0";
    let oldPresentdays = $(this).data("rmoldPresentdays") || "0";   
    
    let empNametr = $(this).closest("tr").data("employeename") || "Unknown";
    Swal.fire({
        title: "Attendance Edit Remark",
        html: `
            <div style="text-align:left;">
                <p><strong>Labour Name:</strong> ${empNametr}</p>
                <p><strong>Old Preset Days:</strong> ${oldPresentdays}</p>
                <p><strong>New Preset Days:</strong> ${Presentdays}</p>
                <p><strong>Remark:</strong> ${remark}</p>
            </div>
        `,
        icon: "info",
        confirmButtonText: "Close"
    });
});
// view-attfile
$(document).on("click", ".view-attfile", function() {
    var form_data = new FormData();
    var routurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    let attfileid = $(this).data("attfileid") || "0";
    
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("projectid", projectid);
    form_data.append("attfileid", attfileid);
    // alert(routurl);
    $.ajax({
        type: "POST",
        url: routurl + "modal/hrm_laboursalarymodal.php?action=GetAttFileData",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'JSON',
        success: function(res) {
            // console.log(res);
            if(res.length>0){
                for (var i = 0; i < res.length; i++) {
                    // window.open(routurl+"preview.php?f="+res[i].file_path);
                    openPopup(routurl + "preview.php?f=" + res[i].file_path, "Labour Attendance");
                }
            }
        }
    });
});
$(document).on("click", ".viewpaidreceipt", function() {
    var routurl = $("body").data("routurl");
    let paidreceiptpath = $(this).data("paidreceipt") || "0";
    openPopup(routurl + "preview.php?f=" + paidreceiptpath, "Paid Receipt");    
});

$("#exportlbpaymentpdf").on("click", function(){
    var completed = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var loginname = $("body").data("loginname");
    var datefromdateex = $("#attdaterangelist").val();
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