<?php
if(isset($_GET['vw'])==1){

}else{
  require('header.php');
}
?>
<div class="container mt-2 mb-4" id="employeedeductionworkcontainer">
    <div class="card">
        <div class="card-body" id="addDrddiv">
            <div class="form-row mt-2">
                <div class="col-md-12">
                    <i class="fa fa-sync refresh_DrcompPayroll" style="font-size:20px; cursor: pointer;"></i>

                    <span style='font-size:12px; float:right;'>You can select from existing or <a href="#" class="text-primary addnewDrCompttoMaster">Create a new deduction</a></span>
                </div>
                <div class="col-md-12 mt-2">
                    <div class="table-responsive">
                        <table id="adddeductioncomponenttbl"></table>
                    </div>
                </div>
            </div>
            <div class="form-row mt-4">
                <div class="col-md-12">
                    <button id="addDrdtoEmpbtn" class="btn btn-info pull-right"><i class="fa fa-plus-circle"></i> Add Deduction</button>
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
hrm_deductioncomponent('all','all', function(res) {
    // console.log(res);
    if (res.length > 0) {
        let ehtmrow = ``;
        res.forEach(item => {
            ehtmrow += `<tr>
                <td class="text-center p-1">
                    <input type="radio" name="addDrdradio" data-respectivedivid="addDrddiv_${item.id}" data-DeductionName="${item.DeductionName}" data-DeductionId="${item.DeductionName}" value="addDeductionamount_${item.id}" />
                </td>
                <td class="p-1">
                    <label>${item.DeductionName}<i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="${item.description}" aria-hidden="true" style="cursor:pointer; font-size:12px; margin: 2px;"></i><br><small class="text-muted">Payable on ${item.Frequency}</small></label>
                    <div class="input-group addDrddiv" id="addDrddiv_${item.id}" style="display:none;">
                        <div class="input-group-prepend"><span class="input-group-text"><span class="symbollabel"></span></span></div>
                        <input type="text" class="form-control addEarningamount float-only" id="addDeductionamount_${item.id}" data-deductionId="${item.id}" data-addDeductionName="${item.DeductionName}" data-addsortname="${item.sortname}" data-addprecentoftct="${item.percentofctc}" data-addprecentofbasic="${item.percentofbasic}" oninput="convertToFloat(this)" />
                    </div>
                </td>
            </tr>`;            
        });
        $("#adddeductioncomponenttbl").html(ehtmrow);
        var crsymbolval = $("#currencyofempsalary option:selected").data("currencysymbol");
        $(".symbollabel").html(crsymbolval);
    }
});

$(document).on("click", ".refresh_DrcompPayroll", function(){
    hrm_deductioncomponent('all','all', function(res) {
        // console.log(res);
        if (res.length > 0) {
            let ehtmrow = ``;
            res.forEach(item => {
                ehtmrow += `<tr>
                    <td class="text-center p-1">
                        <input type="radio" name="addDrdradio" data-respectivedivid="addDrddiv_${item.id}" data-DeductionName="${item.DeductionName}" value="addDeductionamount_${item.id}" />
                    </td>
                    <td class="p-1">
                        <label>${item.DeductionName}<i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="${item.description}" aria-hidden="true" style="cursor:pointer; font-size:12px; margin: 2px;"></i><br><small class="text-muted">Payable on ${item.Frequency}</small></label>
                        <div class="input-group addDrddiv" id="addDrddiv_${item.id}" style="display:none;">
                            <div class="input-group-prepend"><span class="input-group-text"><span class="symbollabel"></span></span></div>
                            <input type="text" class="form-control addEarningamount float-only" id="addDeductionamount_${item.id}" data-addDeductionName="${item.DeductionName}" data-addsortname="${item.sortname}" data-addprecentoftct="${item.percentofctc}" data-addprecentofbasic="${item.percentofbasic}" data-deductionId="${item.id}" oninput="convertToFloat(this)" />
                        </div>
                    </td>
                </tr>`;            
            });
            $("#adddeductioncomponenttbl").html(ehtmrow);
            var crsymbolval = $("#currencyofempsalary option:selected").data("currencysymbol");
            $(".symbollabel").html(crsymbolval);
        }
    });
});

$(document).on("click", "input[name='addDrdradio']", function(){
    $(".addDrddiv").hide();
    // $(this).closest("tr").find('div.addDrddiv').show();
    var DeductionNameLbl = $(this).data("deductionname");
    $("#addDrdtoEmpbtn").html("Add "+DeductionNameLbl);
});

$(document).on("click", "#addDrdtoEmpbtn", function(){
    $("input[name='addDrdradio']:checked").each(function(){
        let componentinputid = $("input[name='addDrdradio']:checked").val();
        var DeductionName = $("#"+componentinputid).data("adddeductionname");
        var deductionId = $("#"+componentinputid).data("deductionid");
        var deductionAmount = $("#"+componentinputid).val();
        let modalId = $(this).closest(".modal").attr("id");
        var htmrow='';
        if(componentinputid){
             htmrow += '<div class="form-check form-switch mt-2">';
            htmrow += '<input class="form-check-input deductioncomponent" type="checkbox" id="component_' + deductionId + '" name="deductioncomponent" data-componentid="' + deductionId + '" value="' + deductionId + '">';
            htmrow += '<label class="form-check-label" for="component_' + deductionId + '">Is applicable for ' + DeductionName + '?</label>';
            htmrow += '</div>';
            $("#deductionhtmlid").append(htmrow);
            $("#"+modalId+"").modal("hide");
            alertmessage("success", "New Deduction : "+DeductionName+" added successfully");
            $("input[name='addDrdradio']").prop("checked", false);
        }else{
            alert('somthing wrong or Amunt is less than zero, Please enter valid amount!');
            return false;
        }
    });
    
});
</script>