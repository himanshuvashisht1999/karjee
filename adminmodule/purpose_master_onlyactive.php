<?php
include('../header.php');
?>
<div class="card">
    <div class="cord-body">
        <div class="main-container p-2">
            <div class="row">
                <div class="col-md-12">
                    <h3 class="text-center">ACTIVE PURPOSE DETAILS</h3>
                </div>
            </div>    
            <div class="row">
                <div class="col-md-12">
                    <a class="btn btn-deepblue btn-sm pull-right" onclick="history.back();">Back</a>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mt-2">
                    <label>Purpose Category Type</label><br/>
                    <label><input type="radio" class="m-2"  name="purposecategirytype" value="debit" checked /> Withdrawal</label>
                    <label><input type="radio" class="m-2" name="purposecategirytype" value="credit" /> Deposit</label>
                </div>
                <div class="col-md-6 mt-2" id="filterfromtbldiv" style="display:none;">
                    <label>Filter</label>
                    <input type="text" id='filterformtbl' oninput="filterTable('#filterformtbl', '#tblpurpose');" class="form-control" placeholder="Type for Searching ......">
                </div>
                <div class="col-md-12 mt-2">
                    <button class="btn btn-sm mb-2 btn-success pull-right" onclick="location.reload();"><i class="fa fa-sync"></i> Refresh Page</button>
                    <button class="btn-sm btn-success"  onclick="gridtoexcel_withfilter_chunk('tblpurpose', 'Purpose Code Master', 'landscape',function(res){})" style="cursor:pointer;"> <i class="fa fa-download" aria-hidden="true"></i> Export To Excel</button>

                    <div class="table-responsive bg-light" style="max-height:100vh;overflow-y:auto;">
                        <table id="tblpurpose" class="table-mobile"  style="display:none;">
                            <thead class="">
                                <tr>
                                    <th colspan="4">Purpose Code Master List for <b class="ppcattypelbl">Withdrawals</b></th>
                                </tr>
                                <tr>
                                    <th colspan="4">Project: <?php echo $projectname; ?></th>
                                </tr>
                                <tr>
                                    <th class='sticky-top bg-primary' style="min-width:50px;">S.No</th>
                                    <th class='sticky-top bg-primary'>Provided Code</th>
                                    <th class='sticky-top bg-primary'>Category of Purpose</th>
                                    <th class='sticky-top bg-primary'>Purpose</th>
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
include('../footer.php');
?>
<script type="text/javascript">
purposecodeof_clientsidedata('hidden_rooturl','withdrawal','allactive','',function(res){
    // console.log(res);
    if(res.length>0){
        $("#tblpurpose tbody").html('');
        var ppcarow='';
        let c=1;
        let isPlusButton="";
        for (var i = 0; i < res.length; i++) {
            ppcarow += "<tr data-clpdbsno="+res[i].sno+"><td>"+ c +"</td><td class='clt_providedcode'>"+res[i].providedcode+"</td><td class='clt_subcategoryname' data-subcategoryid='"+res[i].subcategoryid+"'>"+res[i].subcategoryname+"</td><td class='clt_purposename'>"+res[i].purposename+"</td></tr>";
            c++;
        }
        if(ppcarow){
            $("#tblpurpose tbody").html(ppcarow);
            $("#savepurpose").data("source","manual");
            // $("#savepurpose").show();
            $("#tblpurpose").show();
            $("#importexceldiv").hide();
            $("#filterfromtbldiv").show();
        }
    }else{
        $("#importexceldiv").show();
        $("#filterfromtbldiv").hide();
    }
});

$(document).on("click","input[name='purposecategirytype']", function(){
    $("#tblpurpose tbody").html('');
    if($(this).val()=="debit"){
        $(".ppcattypelbl").html("Withdrawals");
        purposecodeof_clientsidedata('hidden_rooturl','withdrawal','allactive','',function(res){
            // console.log(res);
            if(res.length>0){
                var ppcarow='';
                let c=1;
                let isPlusButton="";
                for (var i = 0; i < res.length; i++) {
                    ppcarow += "<tr data-clpdbsno="+res[i].sno+"><td>"+ c +"</td><td class='clt_providedcode'>"+res[i].providedcode+"</td><td class='clt_subcategoryname' data-subcategoryid='"+res[i].subcategoryid+"'>"+res[i].subcategoryname+"</td><td class='clt_purposename'>"+res[i].purposename+"</td></tr>";

                    c++;
                }
                if(ppcarow){
                    $("#tblpurpose tbody").html(ppcarow);
                    $("#savepurpose").data("source","manual");
                    // $("#savepurpose").show();
                    $("#tblpurpose").show();
                    $("#importexceldiv").hide();
                    $("#filterfromtbldiv").show();
                }
            }else{
                $("#importexceldiv").show();
                $("#filterfromtbldiv").hide();
            }
        });
    }else{
        $(".ppcattypelbl").html("Deposit");
        purposecodeof_clientsidedata('hidden_rooturl','deposit','allactive','',function(res){
            // console.log(res);
            if(res.length>0){
                var ppcarow='';
                let c=1;
                let isPlusButton="";
                for (var i = 0; i < res.length; i++) {
                    isPlusButton = '<i class="fa fa-plus addrowprp" style="font-size: 20px; margin: 4px; color: rgb(144, 12, 63); cursor: pointer;"></i>';

                    ppcarow += "<tr data-clpdbsno="+res[i].sno+"><td>"+ c +"</td><td class='clt_providedcode'>"+res[i].providedcode+"</td><td class='clt_subcategoryname' data-subcategoryid='"+res[i].subcategoryid+"'>"+res[i].subcategoryname+"</td><td class='clt_purposename'>"+res[i].purposename+"</td><td>"+isPlusButton+" <i class='fa fa-edit editclientpurpose' style='font-size: 20px; margin: 2px; cursor:pointer; color: blue;'></i> &nbsp; <i class='fa fa-trash removeclientpurpose' style='font-size: 20px; margin: 2px; cursor:pointer; color: red;'></i> &nbsp; <i class='fa fa-undo undorowdata' style='font-size: 20px; margin: 2px; cursor:pointer; color: green; display:none;'></i> <button class='btn btn-sm btn-deepblue updpurposebtn' id='updpurposebtn_"+res[i].sno+"' style='margin:2px; color:rgb(144, 12, 32); cursor:pointer; display:none;'> <i class='fa fa-save' style='color: rgb(144, 12, 32)'></i> Save</button></td></tr>";
                    c++;
                }
                if(ppcarow){
                    $("#tblpurpose tbody").html(ppcarow);
                    $("#savepurpose").data("source","manual");
                    // $("#savepurpose").show();
                    $("#tblpurpose").show();
                    $("#importexceldiv").hide();
                    $("#filterfromtbldiv").show();
                }
            }else{
                $("#importexceldiv").show();
                $("#filterfromtbldiv").hide();
            }
        });
        
    }
});
</script>