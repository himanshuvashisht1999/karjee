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
        <div class="card-header text-center">
          <h3><strong>Labour Wages Edit/Update</strong></h3>
          <span></span>
        </div>
        <div class="card-body">
            <div class="row">
                    <div class="col-md-4">
                        <label>Do you update entry as?</label><br/>
                        <label><input type="radio" name="selectionradio" value="individual" checked /> Individual</label>
                        <label><input type="radio" name="selectionradio" value="multiple" /> Multiple</label>
                    </div>
                    <div class="col-md-4 text-center">
                        <button class="btn btn-sm btn-outline-primary mt-4 d-none" id="updatemultipleentry">Update Labour Payment</button>
                    </div>
                    <div class="col-md-4">
                        <label>Search to Filter</label>
                        <input type="search" class="form-control" placeholder="Search...." id="searchMissingPaytbl" oninput="filterTable('#searchMissingPaytbl', '#MissingPerDayPaymenttbl');">
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-12 mt-2">
                        <div class="table-responsive" style="height:500px; scroll-y:auto;">
                            <table class="table table-bordered table-light" id="MissingPerDayPaymenttbl">
                                <thead>
                                    <tr>
                                        <th class="multiple d-none">Select <br/><input type="checkbox" id="mnsltlabourslry"></th>
                                        <th>Employee Name</th>
                                        <th>Pay PerDay Amount<br>
                                            <input type="text" class="form-control bordered" id="payperdayinput" placeholder="Enter the pay per day wages to all" name="payperdayinput" onkeypress="return isNumberKey(event)" /></th>
                                        <th class="individual">Action</th>
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
$(document).on("click", "input[name='selectionradio']", function(){
    let selecteraioval = $(this).val();
    if(selecteraioval=="individual"){
        $(".multiple").addClass("d-none");
        $(".individual").removeClass("d-none");
        $("#updatemultipleentry").addClass("d-none");
    }else{
        $(".multiple").removeClass("d-none");
        $(".individual").addClass("d-none");
        $("#updatemultipleentry").removeClass("d-none");
    }
});
$(document).ready(function () {
     handleMasterCheckboxAll("mnsltlabourslry", "payperdaycheckbox");
});

LabourMasterData("allactive", "all",function(res){
    // console.log(res);
    $("#MissingPerDayPaymenttbl tbody").empty();
    var tblrow = "";
    for (var i = 0; i < res.length; i++) {
        var employeeid = res[i].EmployeeID;
        var employeename = res[i].FullName;
        tblrow += '<tr data-employeeid="' + employeeid + '"><td class="multiple d-none"><input type="checkbox" class="payperdaycheckbox" /></td>' +
            '<td>' + employeeid + ' - ' + employeename + '</td>' +
            '<td><input type="text" class="form-control labourPerDayPayment" value="'+res[i].PerDayPayment+'" /></td>' +
            '<td class="individual"><button type="button" class="btn btn-sm btn-success updatePerdayPaymentbtn" onclick="UpdatePayPerDayPayment(this)">UPDATE</button></td>' +
            '</tr>';
    }

    if (tblrow) {
        $("#MissingPerDayPaymentbtn").removeClass("d-none");
        $("#MissingPerDayPaymentbtn").html(res.length +" Labours have not updated Per Day Payment");
        $("#MissingPerDayPaymenttbl tbody").html(tblrow);
    }
});

$(document).on("click", "#updatemultipleentry", function () {
    var routurl = $("body").data("routurl");
    var key = $("body").data("key");
    let ttlrowentry = $(".payperdaycheckbox").length;
    let ttlcheckedrows = $(".payperdaycheckbox:checked").length;
    let excuteentry=0;
    $(".payperdaycheckbox").each(function () {
        var payperdayAmount = $.trim($(this).closest("tr").find("input.labourPerDayPayment").val());
        if ($(this).is(":checked") && parseFloat(payperdayAmount) > 0) {
            $(this).closest("tr").find("button.updatePerdayPaymentbtn").trigger("click");
            excuteentry++;
        }
    });
    if(excuteentry>0 && ttlcheckedrows===excuteentry){
        alertmessage("success","Pay per days payment successfully updated.");
    }else{
        alertmessage("warning", "Selection of labour is missing!");
        return false;
    }
});

$(document).on("input", "#payperdayinput", function(){
    let payPerDayInput = $("#payperdayinput").val() ?? 0;
    $(".labourPerDayPayment").each(function(){
        $(this).closest("tr").find("input.labourPerDayPayment").val(payPerDayInput).trigger("input");
    });
});
</script>