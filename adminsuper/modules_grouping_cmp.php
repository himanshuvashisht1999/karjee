<?php
include('../header.php');
?>
<div class="main-container">
    <div class="row mt-2">
        <div class="col-sm-12 text-center">
            <h2 class="thin ">Assign Modules to Groups for a Project (Company)</h2>
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
    <div class="row mt-2">
        <div class="col-md-8">
            <label>&nbsp;</label>
            <button class="btn btn-success" id="addnewgroupnamebtn" onclick="AddGroupName('hidden_rooturl');">Add New Group Name</button>
        </div>
        <div class="col-md-4">
            <label class=''>Choose Group</label>
            <select class="form-control" id="mdgrplist"></select>
        </div>
    </div>
    <div class="row mt-2">
        <div class="col-md-12">
            <div class="table-responsive bg-light">
                <table class="table table-bordered table-light" id="authcmodulesgrptbl">
                    <thead>
                        <tr>
                            <th colspan="2">Modules Master with Group</th>
                            <th colspan="2" class="noexport"><input type="text" class="form-control" id="mmdlds" oninput="filterTable('#mmdlds', '#authcmodulesgrptbl')" placeholder="search here....." /></th>
                        </tr>
                        <tr>
                            <th>#</th>
                            <th>Module Name</th>
                            <th>Group Name</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
        <div class="col-md-12">
            <button class="btn btn-success" id="moduleauthtocompanybtn" onclick="savemodulewithgroupforcompany($('#grpcompany').val(),$('#grpsubcompany').val(),$('#grpproject').val(),$('#mdgrplist').val(),'authcmodulesgrptbl','moduleauthtocompanybtn');" style="position: fixed; bottom: 50px; right: 50px; display: none;">Save Authoriztion Module With Group</button>
        </div>
        
    </div>
</div>

<?php
include('../footer.php');
?>
<script type="text/javascript">
var loginid = $("body").data("loginid");
var rooturl = $("#hidden_rooturl").val();

authorizedcompanylist_frommaster('hidden_rooturl',loginid,'','grpcompany');

$(document).on("change", "#grpcompany", function(){
    authorizedsubcompanylist_frommaster('hidden_rooturl',loginid,'grpcompany','','grpsubcompany');
});
$(document).on("change", "#grpsubcompany", function(){
    authorizedprojectlist_frommaster(rooturl,$("#grpcompany").val(),$("#grpsubcompany").val(),'grpproject');
});

modulesgrouplistonly('hidden_routurl','all','all',function(ress){
    $("#mdgrplist").empty();
    if(ress.length>0){
      $("#mdgrplist").append("<option value='' selected disabled>Choose group</option>");
      for(var i=0; i<ress.length; i++){
        $("#mdgrplist").append("<option value='"+ress[i].groupid+"'>"+ress[i].groupname+"</option>");
        if(ress.length==1){
          $("#mdgrplist").val(ress[i].groupid).trigger("change");              
        }
      }     
    }
});

$(document).on("change", "#grpproject", function(){
    var companyid = $('#grpcompany').val();
    var subcompanyid = $('#grpsubcompany').val();
    var projectid = $('#grpproject').val();
    // alert(companyid+'=='+subcompanyid+'=='+projectid);
    moduleslistonlywithCompanyAuthoriztion(companyid,subcompanyid,projectid,'all','all',function(ress){
        // console.log(ress);
        $("#authcmodulesgrptbl tbody").empty();
        if(ress.length>0){
            var ggptbl=""
            for(var i=0; i<ress.length; i++){
                if(ress[i].groupid){
                    ggptbl += "<tr data-mdsno='"+ress[i].sno+"' data-authgrpsno='"+ress[i].authoprjsno+"' data-groupid='"+ress[i].groupid+"'><td><input type='checkbox' class='authmdlcheck' /></td><td>"+ress[i].modulename+" <i class='text-muted'>["+ress[i].maingroupname+"]</i>"+"</td><td>"+ress[i].groupname+"</td><td class='text-center'><button class='btn btn-info btn-sm rmcmpmdwithgrp'> Remove Module</button></td></tr>";            
                }else{
                    ggptbl += "<tr data-mdsno='"+ress[i].sno+"' data-authgrpsno='' data-groupid=''><td><input type='checkbox' class='authmdlcheck' /></td><td>"+ress[i].modulename+" <i class='text-muted'>["+ress[i].maingroupname+"]</i>"+"</td><td></td><td class='text-center'></td></tr>";              
                }
            }
            if(ggptbl){
                $("#authcmodulesgrptbl tbody").append(ggptbl);
            }
        }
    });
});



$(document).on("click", ".authmdlcheck", function() {
    let authchklength = $(".authmdlcheck:checked").length;
    if(authchklength>0){
        $("#moduleauthtocompanybtn").show();
    }else{
        $("#moduleauthtocompanybtn").hide();
    }
});
$(document).on("click", ".rmcmpmdwithgrp", function () {
    var companyid = $('#grpcompany').val();
    var subcompanyid = $('#grpsubcompany').val();
    var projectid = $('#grpproject').val();
    var authsno = $(this).closest("tr").data('mdsno');
    var authgroupsno = $(this).closest("tr").data('authgrpsno');
    Removemodule_FromCompanyAuthoriztion(companyid,subcompanyid,projectid,authgroupsno,function(rrs){
        // console.log(rrs);
        if(rrs.status=="Success" || rrs.status=="success"){
            $('#grpproject').prop("selectedIndex", 0);
            alertmessage('success', rrs.message);
            $('#grpproject').val(projectid).trigger("change");
        }else{
            alertmessage('error', rrs.message);
        }
    });
});

</script>