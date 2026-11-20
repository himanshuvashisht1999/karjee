<?php
include('../header.php');
$editprjidid ="";
$prj_projectid ="";
$prj_projectname = "";
$prj_projectemail = "";
$prj_sno = "";
$hdr="";
if(isset($_GET['edprjid'])){
    $btntxt="UPDATE PROJECT DETAILS";
    $editprjidid=$_GET['edprjid'];
    $prjj=projectdetails($conn,$companyid,$subcompanyid,"projectid",$editprjidid);
    if(count($prjj)>0){
        foreach ($prjj as $lgkey) {
            $prj_sno = $lgkey['sno'];
            $prj_companyid = $lgkey['companyid'];
            $prj_subcompanyid = $lgkey['subcompanyid'];
            $prj_projectid = $lgkey['projectid'];
            $prj_projectname = $lgkey['projectname'];
            $prj_projectemail = $lgkey['projectemail'];
            $hdr = "UPDATE DETAILS OF PROJECT (".strtoupper($lgkey['projectname']).")";
        }
    }
}else{
    $btntxt="SAVE PROJECT DETAILS";
    $hdr = "NEW PROJECT REGISTRATION";
    $prj_companyid=$companyid;
}
?>
<input type="hidden" id="editprojectid" value="<?php echo $prj_projectid; ?>" />
<div class="card">
    <div class="card-header">
        <a class="btn btn-deepblue pull-right" href="<?php echo $baseurl; ?>project_management.php?k=<?php echo $_GET['k']; ?>">Back</a>
        <h3><?= $hdr; ?></h3>
    </div>
    <div class="card-body">
        <div class="main-container">
            <div class="row mt-2">
                <div class="col-md-6">
                    <label>What is the company name?</label>
                    <select class="form-control" id="prj_companyid" onchange="authorizedsubcompanylist_frommaster('hidden_rooturl','<?php echo $loginid; ?>','prj_companyid','<?php echo $prj_subcompanyid; ?>','prj_subcompanyid');" ></select>
                </div>
                <div class="col-md-6">
                    <label>What is the sub-company name?</label>
                    <select class="form-control" id="prj_subcompanyid"></select>
                </div>        
            </div>
            <div class="row mt-2">
                <div class="col-md-6">
                    <label>Name of Project</label>
                    <input type="hidden" class="form-control" id="prj_projectid" value="<?php echo $prj_projectid; ?>" autocomplete="off" />
                    <input type="text" class="form-control" id="prj_projectname" value="<?php echo $prj_projectname; ?>" autocomplete="off" />
                </div>
                <div class="col-md-6">
                    <label>Email address of Project.</label>
                    <input type="text" class="form-control" placeholder="Enter email address of project" id="prj_emailaddress"  value="<?php echo $prj_projectemail; ?>" autocomplete="off" />
                </div>        
            </div>
            <div class="row mt-2" style="display:none;">
                <div class="col-md-6">
                    <p><b>Is this active project?</b></p>
                    <label><input type="radio" name="isactiveuser" value="yes" checked /> YES</label>
                    <label><input type="radio" name="isactiveuser" value="no" /> NO</label>
                </div>
            </div>
            <div class="row mt-2" id="issubprojectdiv">
                <div class="col-md-12">
                    <p><b>Do you have sub-project details?</b></p>
                    <label><input type="radio" name="issubproject" value="yes" checked /> YES</label>
                    <label><input type="radio" name="issubproject" value="no" /> NO</label>
                </div>
            </div>
            <div class="row mt-4" id="issubprojectdetailsdiv">
                <div class="col-md-12">
                    <div class="table-responsive bg-light">
                        <table class="table table-light table-bordered" id="subprojecttbl">
                            <thead>
                                <tr>
                                    <th>Sub-Project Name</th>
                                    <th style="width:120px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr data-subprojectidtd='' data-subprojectdbsno=''>
                                    <td><input type="text" class="subprojectnameinput form-control" placeholder="Emter sub-project name" /></td>
                                    <td style="width:120px;">
                                        <i class="fa fa-trash delete-btn" style="font-size:22px; margin:5px 10px; color:red; cursor: pointer;" onclick="deleteRowNew('subprojecttbl');"></i>
                                        <i class="fa fa-plus" style="font-size:22px; margin:5px 10px; color:green; cursor: pointer;" onclick="addRowmanualhtml('subprojecttbl');"></i>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>   
            <div class="row mt-4">
                <div class="col-md-12 text-center">
                    <button id="newprojectbtn" class="btn btn-info" onclick="savetodbprojectdetails_tomaster('hidden_rooturl','prj_companyid','prj_subcompanyid','prj_projectid','prj_projectname','prj_emailaddress','isactiveuser','issubproject','subprojecttbl','newprojectbtn')"><?php echo $btntxt; ?></button>
                </div>
            </div>
            <?php 
            if($prj_projectid){
            ?>
            <div id="accountsofprj_div mt-4">
                <div class="row mt-4">
                    <div class="col-md-12">
                        <h2 class="text-center">Account(s) of Project : <?php echo $prj_projectname; ?></h2>
                    </div>
              		<div class="col-md-12">
                        <button class="btn btn-info m-2 btn-sm float-right" onclick="openModalForAddNewBank('hidden_rooturl');"><i class='fa fa-plus' style='font-size: 18px;'></i> Add New Account </button>
                        <table id="projectaccountlist_tbl" class="table table-bordered table-light table-striped">
              				<thead>
              					<tr>
              						<th style="width: 50px; text-align: center;">S.No</th>
                                    <th>Account Name</th>
                                    <th>Bank Name</th>
                                    <th>Bank Account Number</th>
                                    <th>Bank IFSC Code</th>
                                    <th>Account Type</th>
                                    <th>Branch Name</th>
                                    <th>Branch Address</th>
                                    <th style="width: 100px; text-align: center;">Action</th>
              					</tr>
              				</thead>
              				<tbody></tbody>
              			</table>
              		</div>
                </div>
            </div>
            
            <?php 
            }else{
            ?>
            <!-- <label><input type="checkbox" class="addbankaccount_chk" /> Do you want to add new bank account?</label> -->
            <?php    
            }
            ?>
            <!-- <div class="modal fade" id="addnewaccount_modal" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-lg" style="width: 98%;">
                    <div class="modal-content">
                        <div class="modal-header bg-success">
                            <h5 class="modal-title " id="accheader">NEW BANK ACCOUNT DETAILS ( <span id="accsubheader">For Project : <?php echo $prj_projectname; ?></span> )</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">X</button>
                        </div>
                        <div class="modal-body">
                            <div class="row mt-2">
                                <div class="col-md-12">
                                    <label>Account Name</label>
                                    <input type="text" class="form-control" name="accountname" id="accountname" placeholder="Enter account name" />
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-12">
                                    <label>Bank Name</label>
                                    <input type="text" class="form-control" name="bannkname" id="bannkname" placeholder="Enter bank name" />
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-12">
                                    <label>Bank Account Number</label>
                                    <input type="text" class="form-control" name="bannkaccountno" id="bannkaccountno" placeholder="Enter bank account number" />
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-12">
                                    <label>Account IFSC Code</label>
                                    <input type="text" class="form-control" name="bannkaccountifsccode" id="bannkaccountifsccode" placeholder="Enter bank account ifsc code" />
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-12">
                                    <label>Bank Branch Name</label>
                                    <input type="text" class="form-control" name="bannkbranchname" id="bannkbranchname" placeholder="Enter bank branch name" />
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-12">
                                    <label>Bank Branch Address</label>
                                    <input type="text" class="form-control" name="bannkbranchaddress" id="bannkbranchaddress" placeholder="Enter bank branch address" />
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-12">
                                    <label>Bank Type</label><br/>
                                    <label><input type="radio" name="newpbanktype" value="Bank" checked /> Bank </label>
                                    <label><input type="radio" name="newpbanktype" value="FD" /> FD </label>
                                    <label><input type="radio" name="newpbanktype" value="Imprest" /> Imprest </label>
                                </div>
                            </div>
                            <div class="row mt-2" id="isremovediv" style="display: none;">
                                <div class="col-md-12">
                                    <label><input type="checkbox" id="isremovecheckbox" /> Sure, You want to remove above account from master list?</label>
                                </div>
                            </div>
                            <div class="row mt-4">
                                <div class="col-md-12 text-center">
                                    <button type="button" class="btn btn-info" id="saveupdateaccountdetails" onclick="savetodbprojectaccountdetails_tomaster('hidden_rooturl','hidden_companyid','hidden_subcompanyid','editprojectid','accountname','bannkname','bannkaccountno','bannkaccountifsccode','bannkbranchname','bannkbranchaddress','newpbanktype','isremovecheckbox','hidden_loginname','saveupdateaccountdetails');">Save Account Details of Bank</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> -->
        </div>
    </div>
</div>
<?php
include('../footer.php');
?>
<script type="text/javascript">    
    var rooturl = $("#hidden_rooturl").val();
    authorizedcompanylist_frommaster("hidden_rooturl","<?php echo $loginid; ?>","<?php echo $prj_companyid; ?>", "prj_companyid");
    bankaccountlistwithcallback_frmmaster(rooturl,"<?php echo $prj_companyid; ?>","<?php echo $prj_subcompanyid; ?>","<?php echo $prj_projectid; ?>",function(res){
        if(res.length>0){
          for (var i = 0; i < res.length; i++) {
            $("#projectaccountlist_tbl tbody").append("<tr><td style='width: 50px; text-align: center;'>"+(i + 1)+"</td><td class='ed_accountname'>"+res[i].accountname+"</td><td class='ed_bankname'>"+res[i].bankname+"</td><td class='ed_accountno'>"+res[i].accountno+"</td><td class='ed_ifsccode'>"+res[i].ifsccode+"</td><td class='ed_accounttype'>"+res[i].account_type+"</td><td class='ed_branchname'>"+res[i].branchname+"</td><td class='ed_branchaddress'>"+res[i].branchaddress+"</td><td class='accedit_td text-center'><i class='fa fa-edit editaccinfo' style='font-size: 28px; margin:4px; color:rgb(144, 12, 63); cursor:pointer;'></i> <i class='fa fa-trash removeaccinfo' style='font-size: 28px; margin:4px; color:rgb(144, 12, 32); cursor:pointer;'></i><input type='hidden' class='ed_dbsno' value='"+res[i].sno+"' /></td></tr>");
          }
        }else{
            $("#projectaccountlist_tbl tbody").append("<tr><td colspan='9' class='text-center'>Data not available add new bank details of project</td></tr>");
        }
    });
   $(document).on("click", "#reloadaccountlist", function(){
        bankaccountlistwithcallback_frmmaster(rooturl,"<?php echo $companyid; ?>","<?php echo $subcompanyid; ?>","<?php echo $projectid; ?>",function(res){
            if(res.length>0){
              for (var i = 0; i < res.length; i++) {
                $("#projectaccountlist_tbl tbody").append("<tr><td style='width: 50px; text-align: center;'>"+(i + 1)+"</td><td class='ed_accountname'>"+res[i].accountname+"</td><td class='ed_bankname'>"+res[i].bankname+"</td><td class='ed_accountno'>"+res[i].accountno+"</td><td class='ed_ifsccode'>"+res[i].ifsccode+"</td><td class='ed_accounttype'>"+res[i].account_type+"</td><td class='ed_branchname'>"+res[i].branchname+"</td><td class='ed_branchaddress'>"+res[i].branchaddress+"</td><td class='accedit_td text-center'><i class='fa fa-edit editaccinfo' style='font-size: 28px; margin:4px; color:rgb(144, 12, 63); cursor:pointer;'></i> <i class='fa fa-trash removeaccinfo' style='font-size: 28px; margin:4px; color:rgb(144, 12, 32); cursor:pointer;'></i><input type='hidden' class='ed_dbsno' value='"+res[i].sno+"' /></td></tr>");
              }
            }else{
                $("#projectaccountlist_tbl tbody").append("<tr><td colspan='9' class='text-center'>Data not available add new bank details of project</td></tr>");
            }
        });
    });

    $(document).on("click", ".editaccinfo", function(){
        openModalForAddNewBank('hidden_rooturl');
        $("#isremovediv").hide();
        $("#isremovecheckbox").prop("checked", false);
        var ed_accountname = $(this).closest("tr").find("td.ed_accountname").text();
        var ed_bankname = $(this).closest("tr").find("td.ed_bankname").text();
        var ed_accounttype = $(this).closest("tr").find("td.ed_accounttype").text();
        var ed_accountno = $(this).closest("tr").find("td.ed_accountno").text();
        var ed_ifsccode = $(this).closest("tr").find("td.ed_ifsccode").text();
        var ed_branchname = $(this).closest("tr").find("td.ed_branchname").text();
        var ed_branchaddress = $(this).closest("tr").find("td.ed_branchaddress").text();
        var ed_dbsno = $(this).closest("tr").find("input.ed_dbsno").val();
        // accountname
        // bannkname
        // bannkaccountno
        // bankifsccode
        // ibanno
        // swiftcode
        // sortcode
        // branchname
        // branchaddress

        $("#accountname").val(ed_accountname);
        $("#bannkname").val(ed_bankname);
        $("#bannkaccountno").val(ed_accountno);
        $("#bannkaccountno").data("data_dbsno", ed_dbsno);
        $("#bankifsccode").val(ed_ifsccode);
        $("#branchname").val(ed_branchname);
        $("#branchaddress").val(ed_branchaddress);
        $("input[name='newbanktype'][value='"+ed_accounttype+"']").prop("checked","checked");
    });

    $(document).on("click", ".removeaccinfo", function(){
         openModalForAddNewBank('hidden_rooturl');
        $("#isremovediv").show();
        $("#isremovecheckbox").prop("checked", true);
        var ed_accountname = $(this).closest("tr").find("td.ed_accountname").text();
        var ed_bankname = $(this).closest("tr").find("td.ed_bankname").text();
        var ed_accounttype = $(this).closest("tr").find("td.ed_accounttype").text();
        var ed_accountno = $(this).closest("tr").find("td.ed_accountno").text();
        var ed_ifsccode = $(this).closest("tr").find("td.ed_ifsccode").text();
        var ed_branchname = $(this).closest("tr").find("td.ed_branchname").text();
        var ed_branchaddress = $(this).closest("tr").find("td.ed_branchaddress").text();
        var ed_dbsno = $(this).closest("tr").find("input.ed_dbsno").val();
        $("#accountname").val(ed_accountname);
        $("#bannkname").val(ed_bankname);
        $("#bannkaccountno").val(ed_accountno);
        $("#bannkaccountno").data("data_dbsno", ed_dbsno);
        $("#bankifsccode").val(ed_ifsccode);
        $("#branchname").val(ed_branchname);
        $("#branchaddress").val(ed_branchaddress);
        $("input[name='newbanktype'][value='"+ed_accounttype+"']").prop("checked","checked");
    });

    $(document).on("click", "input[name='issubproject']", function(){
        if($(this).val()=="no"){
            $("#issubprojectdetailsdiv").hide();
            // subprojecttbl
        }else{
            $("#issubprojectdetailsdiv").show();
        }
    });

    function addRowmanualhtml(tableid){
        $("#" + tableid + " tbody").append(`<tr data-subprojectidtd='' data-subprojectdbsno=''><td class='subprojectnamttd'><input type='text' class='subprojectnameinput form-control' placeholder='Enter sub-project name' value='' /></td><td style='width:120px;'><i class='fa fa-trash delete-btn' style='font-size:22px; margin:5px 10px; color:red; cursor: pointer;' onclick="deleteRowNew('subprojecttbl');"></i><i class='fa fa-plus' style='font-size:22px; margin:5px 10px; color:green; cursor: pointer;' onclick="addRowmanualhtml('` + tableid + `');"></i></td></tr>`);

    }
    if("<?php echo $prj_projectid; ?>"){
        subprojectdetails_callback('hidden_rooturl','hidden_companyid','hidden_subcompanyid','prj_projectid',function(res){
            if(res.length>1){
                $("#issubprojectdiv").hide();
            }
            if(res.length>0){
                $("#subprojecttbl tbody").empty();
                for (var i = 0; i < res.length; i++) {
                    $("#subprojecttbl tbody").append("<tr data-subprojectidtd='"+res[i].subprojectid+"' data-subprojectdbsno='"+res[i].sno+"'><td class='subprojectnamttd'><input type='text' class='subprojectnameinput form-control' placeholder='Emter sub-project name' value='"+res[i].subprojectname+"' /></td><td style='width:120px;'><i class='fa fa-trash delete-btn' style='font-size:22px; margin:5px 10px; color:red; cursor: pointer;' onclick='deleteRowNew(\"subprojecttbl\");'></i> <i class='fa fa-plus' style='font-size:22px; margin:5px 10px; color:green; cursor: pointer;' onclick='addRowmanualhtml(\"subprojecttbl\");'></i></td></tr>");
                }
            }else{
                // $("#subprojecttbl tbody").append("<tr><td colspan='2' class='text-center'>Data not available add new sub project</td></tr>");
            }       
        });
    }
</script>