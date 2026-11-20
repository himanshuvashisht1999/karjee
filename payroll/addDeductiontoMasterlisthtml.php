<?php
if(isset($_GET['vw'])==1){

}else{
  require('header.php');
}
?>
<div class="container mt-2 mb-4" id="employeedeductioncompcontainer">
    <div class="card" id="earningbasiccontent">
        <div class="card-body">
            <div class="form-row mt-2">
                <div class="col-md-12">
                    <label>New Deduction Name</label>
                    <select class="form-select select2" name="newDeductionName" id="newDeductionName"></select>
                </div>
            </div>
            <div class="form-row mt-2">
                <div class="col-md-12">
                    <label>Sort Name</label>
                    <input type="text" class="form-control" name="newDeductionSortName" id="newDeductionSortName"></select>
                </div>
            </div>
            <div class="form-row">
                <div class="col-md-12">
                    <label>Description</label>
                    <textarea class="form-control" name="newDedctionDescription" id="newDedctionDescription"></textarea>
                </div>
            </div>
            <div class="form-row">
                <div class="col-md-12">
                    <label>Deduction Frequency</label><br/>
                    <label><input type="radio" class="m-2" name="newDeductionFrequency" value="Monthly"  checked /> <small class="text-muted">Monthly</small></label><br/>
                    <label><input type="radio" class="m-2" name="newDeductionFrequency" value="Yearly" /> <small class="text-muted">Yearly</small></label><br/>
                    <label><input type="radio" class="m-2" name="newDeductionFrequency" value="One-Time" /> <small class="text-muted">One-Time</small></label><br/>
                    <label><input type="radio" class="m-2" name="newDeductionFrequency" value="Weekly" /> <small class="text-muted">Weekly</small></label><br/>
                </div>
            </div>
            <div class="form-row mt-2">
                <div class="col-md-12">
                    <label>Deduction Type</label><br/>
                    <label><input type="radio" class="m-2" name="newDeductionType" value="Percentage" /> <small class="text-muted">Percentage</small></label>
                    <label><input type="radio" class="m-2" name="newDeductionType" value="Fixed"  checked /> <small class="text-muted">Fixed</small></label>
                </div>
            </div>
            <div class="form-row mt-2 dedctiontypepercentdiv" id="dedctiontypepercentdiv" style="display:none;">
                <div class="col-md-12">
                    <label>Employee Deduction Base</label><br/>
                    <label><input type="radio" class="m-2" name="EmpDeductionBase" value="Basic" /> <small class="text-muted">Basic</small></label><br/>
                    <label><input type="radio" class="m-2" name="EmpDeductionBase" value="Gross" /> <small class="text-muted">Gross</small></label><br/>
                    <label><input type="radio" class="m-2" name="EmpDeductionBase" value="CTC" /> <small class="text-muted">CTC</small></label>
                </div>
            </div>            
            <div class="form-row mt-2">
                <div class="col-md-12">
                    <label>Employee Dedction Value</label>
                    <input type="number" class="form-control" name="EmployeeDedctionValue" id="EmployeeDedctionValue" />
                </div>
            </div>
            <div class="form-row mt-2 dedctiontypepercentdiv" id="dedctiontypepercentdiv" style="display:none;">
                <div class="col-md-12">
                    <label>Employer Deduction Base</label><br/>
                    <label><input type="radio" class="m-2" name="EmplrDeductionBase" value="Basic" /> <small class="text-muted">Basic</small></label><br/>
                    <label><input type="radio" class="m-2" name="EmplrDeductionBase" value="Gross" /> <small class="text-muted">Gross</small></label><br/>
                    <label><input type="radio" class="m-2" name="EmplrDeductionBase" value="CTC" /> <small class="text-muted">CTC</small></label>
                </div>
            </div>
            <div class="form-row mt-2">
                <div class="col-md-12">
                    <label>Employer Dedction Value</label>
                    <input type="number" class="form-control" name="EmployerDedctionValue" id="EmployerDedctionValue" />
                </div>
            </div>
            <div class="form-row mt-2">
                <div class="col-md-12">
                    <label><input type="checkbox" class="m-2" id="iscontributeinctc"> Is employer's contribution in CTC?</label>
                </div>
                <div class="col-md-12">
                    <label><input type="checkbox" class="m-2" id="iscontributeinfbp"> Is employer's contribution in FBP?</label>
                </div>
                <div class="col-md-12">
                    <label><input type="checkbox" class="m-2" id="iscontributeinproratabasis"> Is calculation on pro-rata basis?</label>
                </div>
            </div>
            <div class="form-row mt-4">
                <div class="col-md-12">
                    <button id="addnewDeductiontoEmpbtn" data-deductionid='' class="btn btn-primary text-right" onclick="SaveOrUpdateDeductiontoPayroll('newDeductionName','newDeductionSortName','newDedctionDescription','newDeductionFrequency','newDeductionType','EmpDeductionBase','EmplrDeductionBase','EmployeeDedctionValue','EmployerDedctionValue','iscontributeinctc','iscontributeinfbp','iscontributeinproratabasis','addnewDeductiontoEmpbtn');">Add Deduction</button>
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
<script type="text/javascript">
hrm_deductioncomponentGlobal("","",function(res){
    $("#newDeductionName").empty();
    var gldeductionlist = "";
    if (res && res.length > 0) {
        if (res.length > 1) {
            gldeductionlist += "<option value='' selected disabled>Select</option>";
        }
        for (var i = 0; i < res.length; i++) {
            gldeductionlist += "<option value='" + res[i].id + "' data-componentname='"+res[i].ComponentName+"' data-sortname='"+res[i].sortname+"' data-description='"+res[i].description+"' data-frequency='"+res[i].frequency+"' data-defaultforemaplyee='"+res[i].defaultforemaplyee+"'>" + res[i].ComponentName + "</option>";
        }
        if (gldeductionlist) {
            $("#newDeductionName").append(gldeductionlist);
        }
    } else {
        $("#newDeductionName").append("No data found");
    }
});

$(document).on("change", "#newDeductionName", function(){
    $("#newDeductionSortName").val('');
    $("#newDedctionDescription").val('');
    $("#newDeductionFrequency").val('').trigger("change");

    var sortname = $("#newDeductionName option:selected").data("sortname");
    var description = $("#newDeductionName option:selected").data("description");
    var frequency = $("#newDeductionName option:selected").data("frequency");
    var defaultforemaplyee = $("#newDeductionName option:selected").data("defaultforemaplyee");
    $("#newDeductionSortName").val(sortname);
    $("#newDedctionDescription").val(description);
    $("#newDeductionFrequency").val(frequency).trigger("change");
    var deductionId = $("#newDeductionName").val();
    // alert(deductionId);
    $("#addnewDeductiontoEmpbtn").data("deductionid", deductionId);
});
$(document).on("click", "input[name='newDeductionType']", function(){
    if($("input[name='newDeductionType']:checked").val()=="Percentage"){
        $(".dedctiontypepercentdiv").show();
    }else{
        $(".dedctiontypepercentdiv").hide();
    }
});
</script>