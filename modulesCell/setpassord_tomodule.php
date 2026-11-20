<?php
include('header.php');
?>
<div class="main-container">
    <div class="row mt-2">
        <div class="col-sm-12 text-center">
            <h4 class="thin ">Module Password Management</h4>
        </div>
    </div>
    <div class="row">
        <div class="col-md-8"></div>
        <div class="col-md-4">
            <label class=''>Choose Module</label>
            <select class="form-control select2" id="authorizedmodules"></select>
        </div>
    </div>
    <div class="row mt-2">
        <div class="col-md-12">
            <div class="table-responsive">
                <table class="table table-bordered table-light" id="authcmodulesgrptbl">
                    <thead>
                        <tr>
                            <th colspan="2">Authorized Persons</th>
                            <th colspan="2" class="noexport"><input type="text" class="form-control" id="mmdlds" oninput="filterTable('#mmdlds', '#authcmodulesgrptbl')" placeholder="search here....." /></th>
                        </tr>
                        <tr>
                            <th>Authorized Person Name To View</th>
                            <th>Password</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
include('footer.php');
?>
<script type="text/javascript">
var loginid = $("body").data("loginid");
var rooturl = $("#hidden_rooturl").val();
var companyid = $("#hidden_companyid").val();
var subcompanyid = $("#hidden_subcompanyid").val();
var projectid = $("#hidden_projectid").val();


GetClientModulesDetailsFromMaster(function(ress){
    $("#authorizedmodules").empty();
    // console.log(ress);
    if(ress.length>0){
        $("#authorizedmodules").append("<option value='' selected disabled>Choose Module</option>");
        let st=0;
        for(var i=0; i<ress.length; i++){
            if(ress[i].ispasswordprotected=="yes"){
                st++;
                $("#authorizedmodules").append("<option value='"+ress[i].moduleid+"'>"+ress[i].modulename+"</option>");
            }
            if(st==1){
              $("#authorizedmodules").val(ress[i].moduleid).trigger("change");              
            } 
        }
        if(st==0){
            $("#authorizedmodules").append("<option value=''>Password-protected module is not available</option>");
        }
    }
});


$(document).on("change", "#authorizedmodules", function(){
    var moduleid = $('#authorizedmodules').val();
    authorizedlistFormodulepassword(moduleid,function(ress){
        $("#authcmodulesgrptbl tbody").empty();
        if(ress.length>0){
            var ggptbl=""
            for(var i=0; i<ress.length; i++){
                ggptbl += "<tr data-mdsno='"+ress[i].sno+"' data-loginid='"+ress[i].loginid+"'><td>"+ress[i].loginname+"</td><td><input type='text' class='form-control modulepassword' value='"+ress[i].authpass+"'></td><td class='text-center'><button class='btn btn-info btn-sm updpasswordbtn' ONCLICK='updatepassword_inmoduleforuser("+moduleid+",\"authcmodulesgrptbl\")' > UPDATE PASSWORD</button></td></tr>";            
            }
            if(ggptbl){
                $("#authcmodulesgrptbl tbody").append(ggptbl);
            }
        }
    });
});
</script>