<?php
if(isset($_GET['vw'])==1){

}else{
  require('header.php');
}
?>
<div class="container mt-2 mb-4" id="employeeworkcontainer">
    <div class="card" id="earningbasiccontent">
        <div class="card-body" id="addearningdiv">
            <div class="form-row mt-2">
                <div class="col-md-12">
                    <label>Earning Name</label>
                    <select type="select" class="form-select select2" name="newEarningName" id="newEarningName" ></select>
                </div>
            </div>
            <div class="form-row mt-2">
                <div class="col-md-12">
                    <label>Sort Name</label>
                    <input type="text" class="form-control" name="newEarningSortName" id="newEarningSortName"></select>
                </div>
            </div>
            <div class="form-row">
                <div class="col-md-12">
                    <label>Description</label>
                    <textarea class="form-control" name="newEarningDescription" id="newEarningDescription"></textarea>
                </div>
            </div>
            <div class="form-row">
                <div class="col-md-12">
                    <label>Pay Type</label><br/>
                    <label><input type="radio" class="m-2" name="newEarningPaytype" value="Fixed"  checked /> <small class="text-muted">Fixed Pay: (Fixed amount paid at the end of every month.)</small></label>
                    <label><input type="radio" class="m-2" name="newEarningPaytype" value="Percentage" /> <small class="text-muted">Variable Pay: (Variable amount paid during any payroll.)</small></label>
                </div>
            </div>
            <div class="form-row">
                <div class="col-md-12">
                    <label>Calculation Type</label><br/>
                    <label><input type="radio" class="m-2" name="newEarningCalculationType" value="flatamount" checked /> <small class="text-muted">Flat Amount</small></label>
                    <label><input type="radio" class="m-2" name="newEarningCalculationType" value="Basic" /> <small class="text-muted">Percent of Basic</small></label>
                    <label><input type="radio" class="m-2" name="newEarningCalculationType" value="Gross" /> <small class="text-muted">Percent of Gross</small></label>
                    <label><input type="radio" class="m-2" name="newEarningCalculationType" value="CTC" /> <small class="text-muted">Percent of CTC</small></label>
                </div>
            </div>
            <div class="form-row mt-2">
                <div class="col-md-12">
                    <label id="calculationtypelbl">Flat Amount</label>
                    <input type="number" class="form-control" name="calculationtypevalue" id="calculationtypevalue" />
                </div>
            </div>
            <div class="form-row mt-4">
                <div class="col-md-12">
                    <button id="addnewEearningtoEmpbtn" data-earningcomponentid='' class="btn btn-info pull-right" onclick="SaveOrUpdateEarningComponenttoPayroll('newEarningName','newEarningDescription','newEarningPaytype','newEarningCalculationType','calculationtypevalue','addnewEearningtoEmpbtn')">Add Earning</button>
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
hrm_earningcomponentGlobal("","",function(res){
    $("#newEarningName").empty();
    var glearninglist = "";
    if (res && res.length > 0) {
        if (res.length > 1) {
            glearninglist += "<option value='' selected disabled>Select</option>";
        }
        for (var i = 0; i < res.length; i++) {
            glearninglist += "<option value='" + res[i].id + "' data-componentname='"+res[i].ComponentName+"' data-sortname='"+res[i].sortname+"' data-description='"+res[i].description+"' data-frequency='"+res[i].frequency+"' data-defaultforemaplyee='"+res[i].defaultforemaplyee+"'>" + res[i].ComponentName + "</option>";
        }
        if (glearninglist) {
            $("#newEarningName").append(glearninglist);
        }
    } else {
        $("#newEarningName").append("No data found");
    }
});
$(document).on("change", "#newEarningName", function(){
    $("#newEarningSortName").val('');
    $("#newEarningDescription").val('');
    $("#newEarningFrequency").val('').trigger("change");

    var sortname = $("#newEarningName option:selected").data("sortname");
    var description = $("#newEarningName option:selected").data("description");
    var frequency = $("#newEarningName option:selected").data("frequency");
    var defaultforemaplyee = $("#newEarningName option:selected").data("defaultforemaplyee");
    $("#newEarningSortName").val(sortname);
    $("#newEarningDescription").val(description);
    $("#newEarningFrequency").val(frequency).trigger("change");
    var earningId = $("#newEarningName").val();
    // alert(deductionId);
    $("#addnewDeductiontoEmpbtn").data("earningid", earningId);
});

// newEarningPaytype
// Fixed
// Percentage

$(document).on("click", "input[name='newEarningPaytype']", function(){
    $("input[name='newEarningCalculationType']").prop("checked", false);
    if($("input[name='newEarningPaytype']:checked").val()=="Fixed"){
        $("input[name='newEarningCalculationType'][value='flatamount']").prop("checked", true);
        // $(".dedctiontypepercentdiv").show();
    }else{
        $("input[name='newEarningCalculationType'][value='flatamount']").prop("checked", false);
        // $(".dedctiontypepercentdiv").hide();
    }
});
</script>