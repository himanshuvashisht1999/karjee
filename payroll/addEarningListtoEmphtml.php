<?php
if(isset($_GET['vw'])==1){

}else{
  require('../header.php');
}
?>
<div class="container mt-2 mb-4" id="employeeworkcontainer">
    <div class="card">
        <div class="card-body" id="addearningdiv">
            <div class="row mt-2">
                <div class="col-12">
                    <i class="fa fa-sync refresh_ErcompPayroll" style="font-size: 20px; cursor: pointer;"></i>
                    <span class="float-end" style="font-size: 12px;">
                        You can select from existing or 
                        <a href="#" class="text-primary addnewErCompttoMaster">Create a new earning</a>
                    </span>
                </div>
                <div class="col-12 mt-2">
                    <div class="table-responsive">
                        <table id="adderningcomponenttbl" class="table table-bordered table-sm"></table>
                    </div>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-12 text-end">
                    <button id="addEearningtoEmpbtn" class="btn btn-primary"><i class="fa fa-plus-circle"></i> Add Earning</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
if(isset($_GET['vw'])==1){

}else{
  require('../footer.php'); 
}
?>
<script type="text/javascript">
hrm_earningcomponent('defaultforemployee','defaultforemployee', function(res) {
    if (res.length > 0) {
        let ehtmrow = ``;
        res.forEach(item => {
            ehtmrow += `<tr>
                <td class="text-center p-1">
                    <input type="radio" name="addEarningradio" data-respectivedivid="addEarningdiv_${item.id}" data-componentname="${item.componentname}" value="addEarningamount_${item.id}" />
                </td>
                <td class="p-1">
                    <label>${item.componentname}<i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="${item.description}" aria-hidden="true" style="cursor:pointer; font-size:12px; margin: 2px;"></i><br><small class="text-muted">Payable on ${item.frequency}</small></label>
                    <div class="input-group d-none addEarningdiv" id="addEarningdiv_${item.id}" style="display:none;">
                        <div class="input-group-prepend"><span class="input-group-text"><span class="symbollabel"></span></span></div>
                        <input type="text" class="form-control addEarningamount float-only" id="addEarningamount_${item.id}" data-addcomponentname="${item.componentname}" data-addsortname="${item.sortname}" data-addprecentoftct="${item.percentofctc}" data-addprecentofbasic="${item.percentofbasic}" data-earningId="${item.id}" oninput="convertToFloat(this)" />
                    </div>
                </td>
            </tr>`;            
        });
        $("#adderningcomponenttbl").html(ehtmrow);
        var crsymbolval = $("#currencyofempsalary option:selected").data("currencysymbol");
        $(".symbollabel").html(crsymbolval);
    }
});

$(document).on("click", ".refresh_ErcompPayroll", function(){
    hrm_earningcomponent('defaultforemployee','defaultforemployee', function(res) {
        if (res.length > 0) {
            let ehtmrow = ``;
            res.forEach(item => {
                ehtmrow += `<tr>
                    <td class="text-center p-1">
                        <input type="radio" name="addEarningradio" data-respectivedivid="addEarningdiv_${item.id}" data-componentname="${item.componentname}" value="addEarningamount_${item.id}" />
                    </td>
                    <td class="p-1">
                        <label>${item.componentname}<i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="${item.description}" aria-hidden="true" style="cursor:pointer; font-size:12px; margin: 2px;"></i><br><small class="text-muted">Payable on ${item.frequency}</small></label>
                        <div class="input-group addEarningdiv" id="addEarningdiv_${item.id}" style="display:none;">
                            <div class="input-group-prepend"><span class="input-group-text"><span class="symbollabel"></span></span></div>
                            <input type="text" class="form-control addEarningamount float-only" id="addEarningamount_${item.id}" data-addcomponentname="${item.componentname}" data-addsortname="${item.sortname}" data-addprecentoftct="${item.percentofctc}" data-addprecentofbasic="${item.percentofbasic}" data-earningId="${item.id}" oninput="convertToFloat(this)" />
                        </div>
                    </td>
                </tr>`;            
            });
            $("#adderningcomponenttbl").html(ehtmrow);
            var crsymbolval = $("#currencyofempsalary option:selected").data("currencysymbol");
            $(".symbollabel").html(crsymbolval);
        }
    });
});

$(document).on("click", "input[name='addEarningradio']", function(){
    $(".addEarningdiv").hide();
    // $(this).closest("tr").find('div.addEarningdiv').show();
    $("#addEearningtoEmpbtn").html("Add "+$(this).data("componentname"));
});

$(document).on("click", "#addEearningtoEmpbtn", function(){
    $("input[name='addEarningradio']:checked").each(function(){
        let componentinputid = $("input[name='addEarningradio']:checked").val();
        var newehtmrow=``;
        var ComponentName = $("#"+componentinputid).data("addcomponentname");
        var sortname = $("#"+componentinputid).data("addsortname");
        var componentid = $("#"+componentinputid).data("earningId");
        var earningAmount = $("#"+componentinputid).val();
        let modalId = $(this).closest(".modal").attr("id");
        if(componentinputid){
             newehtmrow += `<div class="form-row" id="addenewcomponentdivc_${componentid}">
                <div class="col-md-12">
                    <label>${ComponentName} 
                        <i class="fa fa-question-circle d-none" data-toggle="tooltip" data-placement="top" title="Enter ${ComponentName}" aria-hidden="true" style="cursor:pointer; font-size:12px; margin: 2px;"></i>
                        <i class="fa fa-trash text-danger" style="cursor:pointer; float:right;" onclick="removeContainer('addenewcomponentdivc_${componentid}')"></i>
                    </label>
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><span class="symbollabel"></span></span>
                        </div>
                        <input type="text" class="form-control componentname earningAmount" data-componentname="${ComponentName}"   data-componentpercent="" data-percentinputid="" data-componenttype="earning" data-sortname="${sortname}" data-earningcomponentid="${componentid}"  
                            id="earningComponentamount_${componentid}" placeholder="Enter ${ComponentName}" value="${earningAmount}" />
                    </div>
                </div>
            </div>`;
            $("#addmoreEarningcomponent").append(newehtmrow);
            $("#"+modalId+"").modal("hide");
            alertmessage("success", "New Earning component "+ComponentName+" added successfully");

        }else{
            alert('somthing wrong or Amunt is less than zero, Please enter valid amount!');
            return false;
        }
    }
});


</script>