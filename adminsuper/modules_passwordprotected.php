<?php
include('../header.php');
?>
<div class="main-container">
    <div class="row mt-2">
        <div class="col-sm-12 text-center">
            <h2 class="thin ">Module Password Management</h2>
        </div>
    </div>
    <div class="row mt-4">
        <div class="col-md-4">
            <label class=''>Choose Company</label>
            <select class="form-control" id="grpcompany"></select>
        </div>
        <div class="col-md-4">
            <label class=''>Choose Sub-Company</label>
            <select class="form-control" id="grpsubcompany"></select>
        </div>
        <div class="col-md-4">
            <label class=''>Choose Project</label>
            <select class="form-control" id="grpproject"></select>
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
        <div class="col-md-12">
            <!-- <button class="btn btn-success" id="moduleauthtocompanybtn" onclick="savemodulewithgroupforcompany($('#grpcompany').val(),$('#grpsubcompany').val(),$('#grpproject').val(),$('#authorizedmodules').val(),'authcmodulesgrptbl','moduleauthtocompanybtn');" style="position: fixed; bottom: 50px; right: 50px; display: none;">Save Authoriztion Module With Group</button> -->
        </div>
        
    </div>
</div>

<?php
include('../footer.php');
?>
<script type="text/javascript">
var loginid = $("body").data("loginid");
var rooturl = $("#hidden_rooturl").val();
var companyid = $("#hidden_companyid").val();
var subcompanyid = $("#hidden_subcompanyid").val();
var projectid = $("#hidden_projectid").val();

authorizedcompanylist_frommaster('hidden_rooturl',loginid,companyid,'grpcompany');

$(document).on("change", "#grpcompany", function(){
    authorizedsubcompanylist_frommaster('hidden_rooturl',loginid,'grpcompany',subcompanyid,'grpsubcompany');
});
$(document).on("change", "#grpsubcompany", function(){
    authorizedprojectlist_frommaster(rooturl,$("#grpcompany").val(),$("#grpsubcompany").val(),'grpproject',projectid);
});

$(document).on("change", "#grpproject", function(){
    authorizedmodules_forprojects('hidden_rooturl','grpcompany','grpsubcompany','grpproject',function(ress){
        // console.log(ress);
        $("#authorizedmodules").empty();
        if(ress.length>0){
          $("#authorizedmodules").append("<option value='' selected disabled>Choose Module</option>");
          for(var i=0; i<ress.length; i++){
            $("#authorizedmodules").append("<option value='"+ress[i].moduleid+"'>"+ress[i].modulename+"</option>");
            if(ress.length==1){
              $("#authorizedmodules").val(ress[i].moduleid).trigger("change");              
            }
          }     
        }
    });
});




$(document).on("change", "#authorizedmodules", function(){
    var companyid = $('#grpcompany').val();
    var subcompanyid = $('#grpsubcompany').val();
    var projectid = $('#grpproject').val();
    var moduleid = $('#authorizedmodules').val();
    authorizedloginmoduleid_ofprojects('hidden_rooturl','grpcompany','grpsubcompany','grpproject','authorizedmodules',function(ress){
        // console.log(ress);
        $("#authcmodulesgrptbl tbody").empty();
        if(ress.length>0){
            var ggptbl=""
            for(var i=0; i<ress.length; i++){
                ggptbl += "<tr data-mdsno='"+ress[i].sno+"' data-loginid='"+ress[i].loginid+"'><td>"+ress[i].loginname+"</td><td><input type='text' class='form-control modulepassword' value='"+ress[i].authpass+"'></td><td class='text-center'><button class='btn btn-info btn-sm updpasswordbtn' ONCLICK='updatepassword_inmoduleforuser(\"hidden_rooturl\",\"grpcompany\",\"grpsubcompany\",\"grpproject\",\"authorizedmodules\",\"authcmodulesgrptbl\")' > UPDATE PASSWORD</button></td></tr>";            
            }
            if(ggptbl){
                $("#authcmodulesgrptbl tbody").append(ggptbl);
            }
        }
    });
});

// $(document).on("click", ".updpasswordbtn", function () {
//     var companyid = $('#grpcompany').val();
//     var subcompanyid = $('#grpsubcompany').val();
//     var projectid = $('#grpproject').val();
//     var authsno = $(this).closest("tr").data('mdsno');
//     var authgroupsno = $(this).closest("tr").data('authgrpsno');
//     Removemodule_FromCompanyAuthoriztion(companyid,subcompanyid,projectid,authgroupsno,function(rrs){
//         // console.log(rrs);
//         if(rrs.status=="Success" || rrs.status=="success"){
//             $('#grpproject').prop("selectedIndex", 0);
//             alertmessage('success', rrs.message);
//             $('#grpproject').val(projectid).trigger("change");
//         }else{
//             alertmessage('error', rrs.message);
//         }
//     });
// });

</script>